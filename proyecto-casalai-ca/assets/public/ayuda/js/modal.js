// Modal de Ayuda - Funcionalidad Principal
class ModalAyudaUsuario {
    constructor() {
        this.currentSlide = 0;
        this.totalSlides = 7; // Máximo de slides, se ajustará dinámicamente

        this.modal = null;
        this.overlay = null;
        this.ayudaPrincipal = null;
        this.ayudaTarjetas = null;
        this.tarjetas = [];
        this.tarjetasOriginales = [];
        this.btnPrev = null;
        this.btnNext = null;
        this.navDots = [];
        this.btnClose = null;
        this.animationDirection = 'right';
        this._onKeyDown = null;
        
        this.init();
    }
    
    init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupModal());
        } else {
            this.setupModal();
        }
    }
    
    setupModal() {
        // Obtener elementos del DOM
        this.modal = document.getElementById('modalAyuda');
        if (!this.modal) {
            console.error('No se encontró el modal de ayuda');
            return;
        }
        
        this.overlay = this.modal;
        this.ayudaPrincipal = document.getElementById('ayudaPrincipal');
        this.ayudaTarjetas = document.getElementById('ayudaTarjetas');
        this.btnPrev = document.getElementById('btnNavPrev');
        this.btnNext = document.getElementById('btnNavNext');
        this.btnClose = document.getElementById('cerrarModalAyuda');
        
        // Detectar qué tarjetas existen realmente en el DOM
        const tarjetasExistentes = [];
        const mapeoIndices = {};
        
        // Buscar todas las tarjetas con data-tarjeta
        document.querySelectorAll('[data-tarjeta]').forEach(tarjeta => {
            const nombre = tarjeta.dataset.tarjeta;
            if (!tarjetasExistentes.includes(nombre)) {
                tarjetasExistentes.push(nombre);
            }
        });
        
        console.log('🔍 Tarjetas encontradas en el DOM:', tarjetasExistentes);
        
        // Crear array de tarjetas basado en las que realmente existen
        this.tarjetas = [null]; // Slide 0 es la sección principal
        
        // Orden específico para mantener consistencia
        const ordenTarjetas = ['registrar', 'detallar', 'modificar', 'reporte', 'estatus', 'anular', 'eliminar', 'descargar', 'formatos'];
        
        ordenTarjetas.forEach(nombre => {
            if (tarjetasExistentes.includes(nombre)) {
                const tarjeta = document.querySelector(`[data-tarjeta="${nombre}"]`);
                this.tarjetas.push(tarjeta);
                mapeoIndices[nombre] = this.tarjetas.length - 1;
                console.log(`✅ Tarjeta "${nombre}" encontrada -> slide ${mapeoIndices[nombre]}`);
            } else {
                console.log(`❌ Tarjeta "${nombre}" NO encontrada`);
            }
        });
        
        // Guardar referencia original
        this.tarjetasOriginales = [...this.tarjetas];
        
        // Calcular totalSlides basado en las tarjetas encontradas (incluyendo el slide principal)
        this.totalSlides = this.tarjetas.length;
        
        // Obtener dots del HTML pero limitar al número real de slides
        this.navDots = document.querySelectorAll('.nav-indicators .nav-dot');
        
        // Depuración
        console.log('🔍 Depuración Modal:');
        console.log('- Total dots encontrados:', this.navDots.length);
        console.log('- TotalSlides calculado:', this.totalSlides);
        console.log('- Tarjetas encontradas:', this.tarjetasOriginales.map((t, i) => t ? `Slide ${i}: ${t.dataset.tarjeta}` : `Slide ${i}: null`));
        
        // Mapeo de índices para navegación contextual
        this.mapeoContextos = {};
        Object.keys(mapeoIndices).forEach(contexto => {
            this.mapeoContextos[contexto] = mapeoIndices[contexto];
        });
        
        console.log('- Mapeo de contextos:', this.mapeoContextos);
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Inicializar estado
        this.updateSlide();
    }
    
    // Método para obtener el índice correcto del slide basado en el contexto
    getSlideIndex(contexto) {
        const contextoTarjeta = document.querySelector(`[data-tarjeta="${contexto}"]`);
        if (!contextoTarjeta) return 0; // Si no existe, ir al principal
        
        // Buscar en el array original (con nulls) el índice
        const index = this.tarjetasOriginales.indexOf(contextoTarjeta);
        return index >= 0 ? index : 0;
    }
    
    setupEventListeners() {
        // Botón cerrar
        if (this.btnClose) {
            this.btnClose.addEventListener('click', () => this.closeModal());
        }
        
        // Cerrar al hacer clic en el overlay
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.closeModal();
                }
            });
        }
        
        // Navegación con botones
        if (this.btnPrev) {
            this.btnPrev.addEventListener('click', () => this.prevSlide());
        }
        
        if (this.btnNext) {
            this.btnNext.addEventListener('click', () => this.nextSlide());
        }
        
        // Navegación con indicadores
        this.navDots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        // Navegación con teclado (1 handler por instancia)
        if (!this._onKeyDown) {
            this._onKeyDown = (e) => {
                if (!this.modal || !this.modal.classList.contains('active')) return;
                
                switch(e.key) {
                    case 'Escape':
                        this.closeModal();
                        break;
                    case 'ArrowLeft':
                        this.prevSlide();
                        break;
                    case 'ArrowRight':
                        this.nextSlide();
                        break;
                }
            };
            document.addEventListener('keydown', this._onKeyDown);
        }
        
        // Navegación con gestos táctiles (para móviles)
        this.setupTouchGestures();
    }
    
    setupTouchGestures() {
        if (!this.modal) return;
        
        let touchStartX = 0;
        let touchEndX = 0;
        
        this.modal.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        this.modal.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe(touchStartX, touchEndX);
        }, { passive: true });
    }
    
    handleSwipe(startX, endX) {
        const swipeThreshold = 50;
        const diff = startX - endX;
        
        if (Math.abs(diff) < swipeThreshold) return;
        
        if (diff > 0) {
            // Swipe left - siguiente slide
            this.nextSlide();
        } else {
            // Swipe right - slide anterior
            this.prevSlide();
        }
    }
    
    openModal(contexto = null) {
        if (!this.modal) return;
        
        // Resetear al primer slide solo si no hay contexto específico
        if (!contexto) {
            this.currentSlide = 0;
        }
        this.updateSlide();
        
        // Mostrar modal
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Foco en el modal para accesibilidad
        this.modal.setAttribute('aria-hidden', 'false');
        this.modal.focus();
        
        // Emitir evento personalizado
        this.emitEvent('modal:opened');
    }
    
    closeModal() {
        // Restaurar scroll siempre
        document.body.style.overflow = '';

        if (!this.modal) return;
        
        this.modal.classList.remove('active');
        
        // Resetear al slide principal para limpiar contexto
        this.currentSlide = 0;
        this.updateSlide();
        
        // Accesibilidad
        this.modal.setAttribute('aria-hidden', 'true');
        
        // Emitir evento personalizado
        this.emitEvent('modal:closed');
    }
    
    prevSlide() {
        if (this.currentSlide > 0) {
            this.animationDirection = 'left';
            this.currentSlide--;
            this.updateSlide();
        }
    }
    
    nextSlide() {
        if (this.currentSlide < this.totalSlides - 1) {
            this.animationDirection = 'right';
            this.currentSlide++;
            this.updateSlide();
        }
    }
    
    goToSlide(slideIndex) {
        if (slideIndex >= 0 && slideIndex < this.totalSlides) {
            this.animationDirection = slideIndex > this.currentSlide ? 'right' : 'left';
            this.currentSlide = slideIndex;
            this.updateSlide();
        }
    }
    
    updateSlide() {
        console.log(`updateSlide: currentSlide=${this.currentSlide}, totalSlides=${this.totalSlides}`);
        
        // Actualizar visibilidad del contenido
        if (this.currentSlide === 0) {
            console.log('Mostrando sección principal');
            // Mostrar sección principal
            if (this.ayudaPrincipal) {
                this.ayudaPrincipal.style.display = 'block';
                this.ayudaPrincipal.classList.remove('slide-in-left', 'slide-in-right');
                void this.ayudaPrincipal.offsetWidth; // Forzar reflow
                this.ayudaPrincipal.classList.add(this.animationDirection === 'right' ? 'slide-in-right' : 'slide-in-left');
            }
            if (this.ayudaTarjetas) {
                this.ayudaTarjetas.style.display = 'none';
            }
        } else {
            console.log('Mostrando tarjetas');
            // Mostrar tarjetas
            if (this.ayudaPrincipal) {
                this.ayudaPrincipal.style.display = 'none';
            }
            if (this.ayudaTarjetas) {
                this.ayudaTarjetas.style.display = 'block';
            }
            
            // Ocultar todas las tarjetas
            this.tarjetasOriginales.forEach((tarjeta, index) => {
                if (tarjeta) {
                    if (index === this.currentSlide) {
                        console.log(`Mostrando tarjeta en slide ${index}: ${tarjeta.dataset.tarjeta}`);
                        tarjeta.style.display = 'block';
                        tarjeta.classList.remove('slide-in-left', 'slide-in-right');
                        void tarjeta.offsetWidth; // Forzar reflow
                        tarjeta.classList.add(this.animationDirection === 'right' ? 'slide-in-right' : 'slide-in-left');
                    } else {
                        tarjeta.style.display = 'none';
                    }
                }
            });
        }
        
        // Actualizar botones de navegación
        this.updateNavigationButtons();
        
        // Actualizar indicadores
        this.updateIndicators();
        
        // Emitir evento de cambio
        this.emitEvent('slide:changed', { slide: this.currentSlide });
    }
    
    updateNavigationButtons() {
        if (this.btnPrev) {
            this.btnPrev.disabled = this.currentSlide === 0;
        }
        
        if (this.btnNext) {
            this.btnNext.disabled = this.currentSlide === this.totalSlides - 1;
        }
    }
    
    updateIndicators() {
        this.navDots.forEach((dot, index) => {
            if (index === this.currentSlide) {
                dot.classList.add('nav-dot-active');
            } else {
                dot.classList.remove('nav-dot-active');
            }
        });
    }
    
    emitEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail: {
                modal: this.modal,
                currentSlide: this.currentSlide,
                totalSlides: this.totalSlides,
                ...detail
            }
        });
        document.dispatchEvent(event);
    }
    
    // Métodos públicos
    isOpen() {
        return this.modal && this.modal.classList.contains('active');
    }
    
    getCurrentSlide() {
        return this.currentSlide;
    }
    
    getTotalSlides() {
        return this.totalSlides;
    }
}

// Inicializar el modal cuando se cargue el script
let modalAyudaUsuario = null;

// Función para inicializar el modal (se puede llamar desde otros scripts)
function inicializarModalAyudaUsuario() {
    if (!modalAyudaUsuario) {
        modalAyudaUsuario = new ModalAyudaUsuario();
    }
    return modalAyudaUsuario;
}

// Auto-inicialización
(function() {
    // Esperar a que jQuery esté disponible si es necesario
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            inicializarModalAyudaUsuario();
        });
    } else {
        // Inicialización vanilla JS
        inicializarModalAyudaUsuario();
    }
})();

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.ModalAyudaUsuario = ModalAyudaUsuario;
    window.inicializarModalAyudaUsuario = inicializarModalAyudaUsuario;
    window.modalAyudaUsuario = modalAyudaUsuario;
}