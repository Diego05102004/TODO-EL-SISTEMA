/**
 * recepcion.js - Script unificado para la gestión de Recepciones
 */

// ==========================================
// 1. SISTEMA DE PROTECCIÓN DE SELECTS
// ==========================================
function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;

        originales[id] = Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    });

    setInterval(() => {
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;

            const opsActuales = Array.from(select.options);
            const opsOriginales = originales[id];
            if (!opsOriginales) return;

            const alterado =
                opsActuales.length !== opsOriginales.length ||
                opsActuales.some((o, i) =>
                    !opsOriginales[i] ||
                    o.value !== opsOriginales[i].value ||
                    o.textContent !== opsOriginales[i].text
                );

            if (alterado) {
                select.innerHTML = "";
                opsOriginales.forEach(optData => {
                    const opt = document.createElement("option");
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    select.appendChild(opt);
                });
            }
        });
    }, interval);
}

function actualizarProteccionSelectsProductos() {
    const idsProductos = [];
    document.querySelectorAll('.producto-select').forEach((sel, index) => {
        if (!sel.id) {
            sel.id = 'producto_dinamico_' + index;
        }
        idsProductos.push(sel.id);
    });
    protegerSelects(['proveedor', ...idsProductos], 1000);
    actualizarDisponibilidadProductos();
}

function actualizarDisponibilidadProductos() {
    const selects = Array.from(document.querySelectorAll('#productosList .producto-select'));
    selects.forEach(select => {
        Array.from(select.options).forEach(opcion => {
            if (!opcion.value) return;
            const seleccionadoEnOtraFila = selects.some(otroSelect =>
                otroSelect !== select && otroSelect.value === opcion.value
            );
            opcion.hidden = seleccionadoEnOtraFila;
            opcion.disabled = seleccionadoEnOtraFila;
        });
    });
}

// ==========================================
// 2. FUNCIONES AUXILIARES Y DE UTILIDAD
// ==========================================
const estadosCAIRecepcion = {
    amigable: {
        imagen: 'CAI (ESTADO-AMIGABLE).jpg',
        dialogo: 'Bienvenido, como te puedo ayudar Maestr@?'
    },
    terca: {
        imagen: 'CAI (ESTADO-TERCA).jpg',
        dialogo: 'Porque no me pediste ayuda'
    },
    preocupada: {
        imagen: 'CAI (ESTADO-PREOCUPADA).jpg',
        dialogo: 'Parece que cometí un error, lo siento Maestr@... ¿Me podrías ayudar a corregirlo?'
    },
    regañona: {
        imagen: 'CAI (ESTADO-REGAÑONA).jpg',
        dialogo: 'Recuerda que debes llenar los campos antes de registrar la Recepción Maestr@, sigue las reglas.'
    }
};

function obtenerPanelCAIRecepcion() {
    const modal = document.getElementById('recepcionModal') ||
        document.getElementById('registrarRecepcionModal');
    if (!modal) return null;

    let panel = modal.querySelector('.cai-recepcion');
    if (panel) return panel;

    const form = modal.querySelector('#recepcionForm, #ingresarRecepcion');
    const destino = form?.querySelector('.cai-recepcion-column, .form-section, .modal-body');
    if (!destino) return null;

    panel = document.createElement('button');
    panel.type = 'button';
    panel.className = 'cai-recepcion';
    panel.setAttribute('aria-controls', 'caiRecepcionSection');
    panel.setAttribute('aria-expanded', 'false');
    panel.setAttribute('aria-label', 'Mostrar análisis asistido por CAI');

    const imagen = document.createElement('img');
    imagen.className = 'cai-recepcion-image';
    imagen.alt = '';

    const contenido = document.createElement('div');
    contenido.className = 'cai-recepcion-content';
    contenido.setAttribute('aria-live', 'polite');
    const nombre = document.createElement('strong');
    nombre.textContent = 'CAI · Asistente de recepción';
    const dialogo = document.createElement('p');
    dialogo.className = 'cai-recepcion-dialogue';
    contenido.append(nombre, dialogo);
    panel.append(imagen, contenido);
    destino.insertBefore(panel, destino.firstChild);

    const distribucion = form.querySelector('.form-row-content');
    panel.addEventListener('click', () => {
        const seccion = modal.querySelector('#caiRecepcionSection');
        if (!seccion) return;
        establecerSeccionCAIRecepcionVisible(seccion.hidden);
    });
    panel.addEventListener('pointerenter', () => {
        if (modal.querySelector('#caiRecepcionSection')?.hidden &&
            window.matchMedia('(hover: hover)').matches) {
            distribucion?.classList.add('cai-card-hover');
        }
    });
    panel.addEventListener('pointerleave', () => {
        distribucion?.classList.remove('cai-card-hover');
    });
    panel.addEventListener('focus', () => {
        if (modal.querySelector('#caiRecepcionSection')?.hidden) {
            distribucion?.classList.add('cai-card-hover');
        }
    });
    panel.addEventListener('blur', () => {
        distribucion?.classList.remove('cai-card-hover');
    });

    return panel;
}

function establecerSeccionCAIRecepcionVisible(visible) {
    const modal = document.getElementById('recepcionModal');
    const seccion = modal?.querySelector('#caiRecepcionSection');
    const boton = modal?.querySelector('.cai-recepcion');
    if (!seccion) return;

    const distribucion = modal.querySelector('.form-row-content');
    if (distribucion) {
        distribucion.classList.toggle('cai-section-visible', visible);
        distribucion.classList.toggle('cai-section-hidden', !visible);
        if (visible) distribucion.classList.remove('cai-card-hover');
        else if (boton?.matches(':hover') && window.matchMedia('(hover: hover)').matches) {
            distribucion.classList.add('cai-card-hover');
        }
    }

    if (boton) {
        const etiqueta = visible
            ? 'Ocultar análisis asistido por CAI'
            : 'Mostrar análisis asistido por CAI';
        boton.setAttribute('aria-expanded', String(visible));
        boton.setAttribute('aria-label', etiqueta);
    }

    const yaVisible = !seccion.hidden;
    if (visible === yaVisible) return;

    const transicion = (Number(seccion.dataset.transitionId) || 0) + 1;
    seccion.dataset.transitionId = String(transicion);
    const duracion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 650;
    const alturaActual = seccion.getBoundingClientRect().height;
    const limpiarEstilosAnimacion = () => {
        seccion.style.removeProperty('height');
        seccion.style.removeProperty('flex');
        seccion.style.removeProperty('max-height');
        seccion.style.removeProperty('overflow');
        seccion.style.removeProperty('opacity');
    };
    const completar = () => {
        seccion.removeEventListener('transitionend', alTerminar);
        if (Number(seccion.dataset.transitionId) !== transicion) return;
        if (!visible) seccion.hidden = true;
        limpiarEstilosAnimacion();
    };
    const alTerminar = event => {
        if (event.target === seccion && event.propertyName === 'height') completar();
    };

    if (visible) {
        seccion.hidden = false;
        seccion.inert = false;
        seccion.setAttribute('aria-hidden', 'false');
        if (!duracion) return;

        const columna = seccion.parentElement;
        const tarjeta = seccion.previousElementSibling;
        const estilosColumna = columna ? window.getComputedStyle(columna) : null;
        const estilosTarjeta = tarjeta ? window.getComputedStyle(tarjeta) : null;
        const espacio = parseFloat(estilosColumna?.rowGap || '0') || 0;
        const margenTarjeta = parseFloat(estilosTarjeta?.marginBottom || '0') || 0;
        const alturaDisponible = columna && tarjeta
            ? columna.clientHeight - tarjeta.getBoundingClientRect().height - espacio - margenTarjeta
            : seccion.scrollHeight;
        const alturaFinal = Math.max(0, alturaDisponible) || seccion.scrollHeight;

        seccion.style.flex = '0 0 auto';
        seccion.style.height = '0px';
        seccion.style.maxHeight = 'none';
        seccion.style.overflow = 'hidden';
        seccion.style.opacity = '0';
        void seccion.offsetHeight;
        seccion.addEventListener('transitionend', alTerminar);
        window.setTimeout(completar, duracion + 100);
        seccion.style.height = `${alturaFinal}px`;
        seccion.style.opacity = '1';
        return;
    }

    seccion.inert = true;
    seccion.setAttribute('aria-hidden', 'true');
    if (!duracion) {
        seccion.hidden = true;
        return;
    }

    const alturaInicial = alturaActual;
    seccion.style.flex = '0 0 auto';
    seccion.style.height = `${alturaInicial}px`;
    seccion.style.maxHeight = 'none';
    seccion.style.overflow = 'hidden';
    seccion.style.opacity = '1';
    void seccion.offsetHeight;
    seccion.addEventListener('transitionend', alTerminar);
    window.setTimeout(completar, duracion + 100);
    seccion.style.height = '0px';
    seccion.style.opacity = '0';
}

function actualizarCAIRecepcion(estado, dialogo) {
    const panel = obtenerPanelCAIRecepcion();
    const configuracion = estadosCAIRecepcion[estado];
    if (!panel || !configuracion) return;

    panel.dataset.mood = estado;
    panel.querySelector('.cai-recepcion-dialogue').textContent =
        dialogo || configuracion.dialogo;
    const imagen = panel.querySelector('.cai-recepcion-image');
    imagen.classList.remove(`cai-recepcion-image--${estado}`);
    void imagen.offsetWidth;
    imagen.classList.add(`cai-recepcion-image--${estado}`);
    imagen.hidden = false;
    imagen.onerror = () => { imagen.hidden = true; };
    imagen.src = `microservicio/auditor_recepcion/${encodeURIComponent(configuracion.imagen)}`;
}

function mostrarAlertaCAIRecepcion(estado, titulo, mensaje, dialogo, opciones = {}) {
    const configuracion = estadosCAIRecepcion[estado];
    const { contenidoAdicional, ...opcionesAlerta } = opciones;
    const textoCAI = dialogo || configuracion.dialogo;
    const contenido = document.createElement('div');
    contenido.className = 'cai-recepcion-swal-content';
    const dialogoCAI = document.createElement('p');
    dialogoCAI.className = 'cai-recepcion-swal-dialogue';
    dialogoCAI.textContent = textoCAI;
    contenido.appendChild(dialogoCAI);
    if (mensaje) {
        const detalle = document.createElement('p');
        detalle.className = 'cai-recepcion-swal-detail';
        detalle.textContent = mensaje;
        contenido.appendChild(detalle);
    }
    if (contenidoAdicional instanceof HTMLElement) {
        contenido.appendChild(contenidoAdicional);
    }

    actualizarCAIRecepcion(estado, textoCAI);
    return Swal.fire({
        ...opcionesAlerta,
        title: titulo,
        html: contenido,
        imageUrl: `microservicio/auditor_recepcion/${encodeURIComponent(configuracion.imagen)}`,
        imageWidth: 112,
        imageHeight: 112,
        imageAlt: `CAI ${estado}`,
        customClass: { image: `cai-recepcion-image--${estado}` },
        confirmButtonText: opcionesAlerta.confirmButtonText || 'Entendido'
    });
}

function mensajes(icono, titulo, mensaje) {
    if (icono === 'error' && (
        document.getElementById('recepcionModal')?.style.display === 'block' ||
        document.getElementById('registrarRecepcionModal')?.classList.contains('show')
    )) {
        return mostrarAlertaCAIRecepcion('regañona', titulo, mensaje);
    }
    Swal.fire({
        icon: icono,
        title: titulo,
        text: mensaje,
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
    });
}

function validarkeypress(er, e) {
    let key = e.keyCode;
    let tecla = String.fromCharCode(key);
    let a = er.test(tecla);

    if (!a) {
        e.preventDefault();
    }
}

function validarkeyup(er, etiqueta, etiquetamensaje, mensaje) {
    let a = er.test(etiqueta.val());

    if (a) {
        etiquetamensaje.text("");
        etiqueta.removeClass("is-invalid").addClass("is-valid");
        return 1;
    } else {
        etiquetamensaje.text(mensaje);
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }
}

const intentosTeclaNumericaInvalida = new WeakSet();

function consumirIntentoTeclaNumericaInvalida(input) {
    if (!intentosTeclaNumericaInvalida.has(input)) {
        return false;
    }

    intentosTeclaNumericaInvalida.delete(input);
    return true;
}

function validarKeyPressDecimal(e, etiquetamensaje, mensaje) {
    let key = e.key;
    if (e.ctrlKey || key === 'Backspace' || key === 'Delete' || key === 'Tab' || key.startsWith('Arrow')) {
        return;
    }
    let invalido = false;
    if (['e', 'E', '+', '-'].includes(key)) {
        invalido = true;
    } else if (!/[0-9.]/.test(key) || (key === '.' && e.target.value.includes('.'))) {
        invalido = true;
    }

    if (invalido) {
        e.preventDefault();
        if (etiquetamensaje && mensaje) {
            intentosTeclaNumericaInvalida.add(e.target);
            etiquetamensaje.text(mensaje);
            $(e.target).removeClass('is-valid').addClass('is-invalid');
        }
    }
}

function validarKeyUpDecimal(etiqueta, etiquetamensaje, mensaje) {
    let val = etiqueta.val().trim();
    let er = /^\d+(\.\d{1,2})?$/;
    
    if (val === '' || er.test(val)) {
        etiquetamensaje.text("");
        etiqueta.removeClass("is-invalid").addClass("is-valid");
        return 1;
    } else {
        etiquetamensaje.text(mensaje);
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }
}

// Validación estricta para el campo IVA (Límite máximo de 99)
function validarKeyUpIva(etiqueta, etiquetamensaje, mensaje) {
    let valStr = etiqueta.val().trim();
    let er = /^\d+(\.\d{1,2})?$/;
    let valNum = parseFloat(valStr);

    if (valStr === '') {
        etiquetamensaje.text("");
        etiqueta.removeClass("is-invalid is-valid");
        return 1;
    }

    if (!er.test(valStr) || isNaN(valNum)) {
        etiquetamensaje.text(mensaje || "*IVA inválido*");
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }

    if (valNum > 99) {
        etiqueta.val('99.00');
        etiquetamensaje.text("*El porcentaje de IVA no puede ser mayor a 99%*");
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }

    if (valNum < 0) {
        etiqueta.val('0.00');
        etiquetamensaje.text("*El porcentaje de IVA no puede ser menor a 0%*");
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }

    etiquetamensaje.text("");
    etiqueta.removeClass("is-invalid").addClass("is-valid");
    return 1;
}

function validarKeyPressEntero(e) {
    let key = e.key;
    if (e.ctrlKey || key === 'Backspace' || key === 'Delete' || key === 'Tab' || key.startsWith('Arrow')) {
        return;
    }
    if (!/[0-9]/.test(key)) {
        e.preventDefault();
    }
}

function validarKeyUpEntero(etiqueta, etiquetamensaje, mensaje) {
    let val = etiqueta.val().trim();
    let er = /^[0-9]+$/;
    
    if (val !== '' && er.test(val) && parseInt(val, 10) > 0) {
        etiquetamensaje.text("");
        etiqueta.removeClass("is-valid is-invalid").addClass("is-valid");
        return 1;
    } else {
        etiquetamensaje.text(mensaje);
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }
}

function limitarDosDecimales(input) {
    let valor = input.value.replace(',', '.');
    let partes = valor.split('.');
    if (partes.length > 1) {
        input.value = partes[0] + '.' + partes[1].substring(0, 2);
    }
}

function limitarDigitos(input, maxDigitos, permitirDecimales = false) {
    let digitosEnteros = 0;
    let despuesDelSeparadorDecimal = false;
    input.value = input.value.replace(/[^0-9.,]/g, '').replace(/[0-9.,]/g, caracter => {
        if (caracter === '.' || caracter === ',') {
            if (!permitirDecimales || despuesDelSeparadorDecimal) {
                return '';
            }
            despuesDelSeparadorDecimal = true;
            return '.';
        }

        if (!despuesDelSeparadorDecimal && digitosEnteros >= maxDigitos) {
            return '';
        }

        if (!despuesDelSeparadorDecimal) {
            digitosEnteros++;
        }
        return caracter;
    });
}

function sanitizarCampoNumerico(input) {
    if (input.id === 'correlativo') {
        input.value = input.value.replace(/\D/g, '').slice(0, 6);
        return;
    }

    if (input.classList.contains('cantidad-input')) {
        input.value = input.value.replace(/\D/g, '').slice(0, 9);
        return;
    }

    if (input.classList.contains('costo-input') && input.id !== 'porcentajeIvaRecepcion') {
        limitarDigitos(input, 9, true);
        limitarDosDecimales(input);
        return;
    }

    if (input.id === 'porcentajeIvaRecepcion') {
        input.value = input.value.replace(/[^0-9.,]/g, '').replace(',', '.');
        limitarDosDecimales(input);
        const partes = input.value.split('.');
        partes[0] = partes[0].slice(0, 2);
        input.value = partes.length > 1
            ? partes[0] + '.' + partes[1].slice(0, 2)
            : partes[0];
    }
}

function obtenerMensajeCampoNumerico(input) {
    if (input.id === 'correlativo') {
        return $("#scorrelativo");
    }
    if (input.classList.contains('cantidad-input')) {
        return $(input).siblings('.scantidad-msg');
    }
    if (input.id === 'porcentajeIvaRecepcion') {
        return $("#sporcentajeIva");
    }
    return $(input).siblings('.scosto-msg');
}

function validarCampoNumerico(input) {
    const $input = $(input);
    const $mensaje = obtenerMensajeCampoNumerico(input);

    if (input.id === 'correlativo') {
        validarkeyup(/^[0-9]{6}$/, $input, $mensaje, "*Formato válido: solo números, 6 dígitos*");
    } else if (input.classList.contains('cantidad-input')) {
        validarKeyUpEntero($input, $mensaje, "*Debe ser un número entero mayor a 0*");
    } else if (input.id === 'porcentajeIvaRecepcion') {
        validarKeyUpIva($input, $mensaje, "*El IVA no puede ser mayor a 99%*");
    } else {
        validarKeyUpDecimal($input, $mensaje, "*Formato válido: solo números con hasta 2 decimales*");
    }
}

function obtenerReglaPegadoNumerico(input) {
    if (input.id === 'correlativo') {
        return /^\d{1,6}$/;
    }
    if (input.classList.contains('cantidad-input')) {
        return /^\d+$/;
    }
    if (input.id === 'porcentajeIvaRecepcion') {
        return /^\d{1,2}(?:[.,]\d{0,2})?$|^$/;
    }
    return /^\d+(?:[.,]\d{0,2})?$/;
}

function space(str) {
    if (!str) return '';
    return str.replace(/\s{2,}/g, ' ');
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const partes = fechaStr.split('-');
    if (partes.length === 3) {
        return `${partes[2].padStart(2, '0')}/${partes[1].padStart(2, '0')}/${partes[0]}`;
    }
    return fechaStr;
}

// ==========================================
// 3. VALIDACIONES DE FORMULARIO
// ==========================================
function validarEnvioRecepcion() {
    let correlativoInput = $("#correlativo");
    let correlativoVal = space(correlativoInput.val()).trim();
    correlativoInput.val(correlativoVal);

    let esValido = true;

    if (validarkeyup(/^[0-9]{6}$/, correlativoInput,$("#scorrelativo"), "*El N° de factura debe tener exactamente 6 dígitos*") == 0) {
        esValido = false;
    }

    let proveedorVal = $("#proveedor").val();
    if (proveedorVal) {
        $("#proveedor").removeClass("is-invalid").addClass("is-valid");
        $("#sproveedor").text("");
    } else {
        $("#proveedor").removeClass("is-valid").addClass("is-invalid");
        $("#sproveedor").text("*Debe seleccionar un proveedor*");
        esValido = false;
    }

    $("#productosList .producto-row").each(function() {
        let $prodSelect =$(this).find('.producto-select');
        if (!$prodSelect.val()) {$prodSelect.removeClass("is-valid").addClass("is-invalid");
            $(this).find('.sproducto-msg').text("*Debe seleccionar un producto*");
            esValido = false;
        } else {
            $prodSelect.removeClass("is-invalid").addClass("is-valid");
            $(this).find('.sproducto-msg').text("");
        }

        let $costoInput =$(this).find('.costo-input');
        limitarDigitos($costoInput[0], 9, true);
        let $costoMensaje = $(this).find('.scosto-msg');
        if ($costoInput.val().trim() === '') {
            $costoInput.removeClass("is-valid").addClass("is-invalid");
            $costoMensaje.text("*Costo inválido*");
            esValido = false;
        } else if (validarKeyUpDecimal($costoInput, $costoMensaje, "*Costo inválido*") == 0) {
            esValido = false;
        }

        let $cantInput =$(this).find('.cantidad-input');
        limitarDigitos($cantInput[0], 9);
        if (validarKeyUpEntero($cantInput,$(this).find('.scantidad-msg'), "*Cantidad inválida*") == 0) {
            esValido = false;
        }
    });

    let $ivaInput =$("#porcentajeIvaRecepcion");
    let $ivaMensaje = $("#sporcentajeIva");
    if ($ivaInput.val().trim() === '') {
        $ivaInput.removeClass("is-valid").addClass("is-invalid");
        $ivaMensaje.text("*Debe ingresar el IVA*");
        esValido = false;
    } else if (validarKeyUpIva($ivaInput, $ivaMensaje, "*IVA inválido (0-99%)*") == 0) {
        esValido = false;
    }

    if (!esValido) {
        actualizarCAIRecepcion('regañona');
        mensajes('error', 'Campos incompletos');
    }

    return esValido;
}

function verificaproductos() {
    var filasModal = $("#productosList .producto-row").length;
    if (filasModal > 0) {
        let valido = true;
        $("#productosList .producto-row").each(function() {
            let prodSel = $(this).find('.producto-select').val();
            let cant = $(this).find('input[name="cantidad[]"]').val();
            if (!prodSel || !cant || Number(cant) <= 0) {
                valido = false;
            }
        });
        if (!valido) {
            actualizarCAIRecepcion('regañona');
            mensajes('error', 'Verifique los productos', 'Debe seleccionar un producto válido y asignar una cantidad mayor a 0');
            return false;
        }
        return true;
    } else {
        actualizarCAIRecepcion('regañona');
        mensajes('error', 'Verifique los productos', 'Debe seleccionar algún producto');
        return false;
    }
}

// ==========================================
// 4. INTEGRACIÓN SEGURA CON EL MICROSERVICIO (IA)
// ==========================================
let asistenteRecepcionIA = null;
let urlPreviewFacturaRecepcion = null;
let datosFacturaExtraidaIA = null;
let asistenteIA = null;
let facturaValidaParaRegistro = true; // Controla si la factura es válida para registro
let discrepanciasActivasIA = null;
const productosEliminadosManualmenteIA = new Set();

function obtenerAsistenteIA() {
    if (!asistenteRecepcionIA && typeof AsistenteRecepcionIA !== 'undefined') {
        try {
            asistenteRecepcionIA = new AsistenteRecepcionIA({
                proxyUrl: 'Modelo/Controlador/recepcion.php',
                debug: true,
                selectores: { alertas: '#estadoIARecepcion' }
            });
        } catch (e) {
            console.error('Error al instanciar AsistenteRecepcionIA:', e);
        }
    }
    return asistenteRecepcionIA;
}

function establecerEstadoIARecepcion(mensaje, estado = 'info') {
    const estadoEl = document.getElementById('estadoIARecepcion');
    if (!estadoEl) return;
    
    if (mensaje && mensaje.includes('<')) {
        estadoEl.innerHTML = mensaje;
    } else {
        estadoEl.textContent = mensaje;
    }
    
    estadoEl.dataset.state = estado;
    estadoEl.hidden = !mensaje;
}

function activarContingenciaIARecepcion(detalle = '') {
    establecerEstadoIARecepcion(
        `El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual.${detalle ? ` Detalle: ${detalle}` : ''}`,
        'error'
    );
}

function normalizarTextoRecepcion(valor) {
    return String(valor || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/[^a-z0-9]/g, '');
}

function buscarOpcionCatalogo(select, texto) {
    if (!select) return null;
    const buscado = normalizarTextoRecepcion(texto);
    if (!buscado) return null;
    const opciones = Array.from(select.options).filter(opcion => opcion.value);
    const exactas = opciones.filter(opcion => normalizarTextoRecepcion(opcion.textContent) === buscado);
    return exactas.length === 1 ? exactas[0] : null;
}

function buscarProductoFacturaEnCatalogo(select, producto) {
    if (!select || !producto) return null;

    const nombreFactura = normalizarTextoRecepcion(producto.nombre);
    if (!nombreFactura) return null;

    const opciones = Array.from(select.options).filter(opcion => opcion.value);
    const exactas = opciones.filter(opcion =>
        normalizarTextoRecepcion(opcion.textContent) === nombreFactura
    );
    if (exactas.length === 1) return exactas[0];

    const marcaFactura = normalizarTextoRecepcion(producto.marca);
    const modeloFactura = normalizarTextoRecepcion(producto.modelo);
    const candidatas = [];

    opciones.forEach(opcion => {
        const nombreCatalogo = normalizarTextoRecepcion(opcion.textContent);
        if (!nombreCatalogo || !nombreFactura.startsWith(nombreCatalogo)) return;

        const marcaCatalogo = normalizarTextoRecepcion(opcion.dataset.marca);
        const modeloCatalogo = normalizarTextoRecepcion(opcion.dataset.modelo);
        const marcaCoincide = Boolean(marcaFactura && marcaCatalogo && marcaFactura === marcaCatalogo);
        const modeloCoincide = Boolean(
            modeloCatalogo &&
            (
                (modeloCatalogo.length >= 3 && nombreFactura.includes(modeloCatalogo)) ||
                (modeloFactura && modeloFactura === modeloCatalogo)
            )
        );
        const nombreExacto = nombreCatalogo === nombreFactura;

        if (!nombreExacto && !marcaCoincide && !modeloCoincide) return;

        candidatas.push({
            opcion,
            puntuacion: (nombreExacto ? 10 : 0) +
                (modeloCoincide ? 3 : 0) +
                (marcaCoincide ? 2 : 0),
            longitudNombre: nombreCatalogo.length
        });
    });

    if (!candidatas.length) return null;

    candidatas.sort((a, b) =>
        b.puntuacion - a.puntuacion || b.longitudNombre - a.longitudNombre
    );
    const mejor = candidatas[0];
    const hayEmpate = candidatas.some((candidata, indice) =>
        indice > 0 &&
        candidata.puntuacion === mejor.puntuacion &&
        candidata.longitudNombre === mejor.longitudNombre
    );

    return hayEmpate ? null : mejor.opcion;
}

function buscarOpcionCatalogoPorDatos(select, producto) {
    if (!select) return null;
    const campos = [
        ['nombre', opcion => opcion.textContent],
        ['modelo', opcion => opcion.dataset.modelo],
        ['marca', opcion => opcion.dataset.marca],
        ['serial', opcion => opcion.dataset.serial]
    ].filter(([campo]) => producto[campo]);
    if (!campos.length) return null;

    const coincidencias = Array.from(select.options).filter(opcion =>
        opcion.value && campos.every(([campo, obtenerValor]) =>
            normalizarTextoRecepcion(obtenerValor(opcion)) ===
            normalizarTextoRecepcion(producto[campo])
        )
    );
    return coincidencias.length === 1 ? coincidencias[0] : null;
}

function mostrarSugerenciasAutocorreccion(factura) {
    const resultado = document.getElementById('resultadoIARecepcion');
    if (!resultado) return;

    const sugerencias = Array.isArray(factura.sugerencias_autocorreccion)
        ? factura.sugerencias_autocorreccion
        : [];
    if (!sugerencias.length) return;

    const titulo = document.createElement('h4');
    titulo.textContent = 'Sugerencias aprendidas por campo (requieren confirmación)';
    resultado.appendChild(titulo);

    const lista = document.createElement('ul');
    sugerencias.forEach(sugerencia => {
        const elemento = document.createElement('li');
        const evidencia = `Evidencia: ${Number(sugerencia.evidencia) || 0} recepciones`;
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'btn btn-sm btn-outline-primary';

        if (sugerencia.campo === 'proveedor') {
            const proveedor = document.getElementById('proveedor');
            const opcion = buscarOpcionCatalogo(proveedor, sugerencia.valor_sugerido);
            elemento.appendChild(document.createTextNode(
                `Proveedor: "${sugerencia.valor_actual}" → "${sugerencia.valor_sugerido}". ${evidencia} `
            ));
            boton.textContent = 'Aceptar proveedor';
            boton.disabled = !opcion;
            boton.addEventListener('click', () => {
                if (!proveedor || !opcion) return;
                proveedor.value = opcion.value;
                proveedor.dispatchEvent(new Event('change', { bubbles: true }));
                boton.disabled = true;
                boton.textContent = 'Proveedor actualizado';
            });
        } else if (sugerencia.campo === 'numero_factura') {
            const correlativo = document.getElementById('correlativo');
            const numeroSugerido = String(sugerencia.valor_sugerido || '').trim();
            elemento.appendChild(document.createTextNode(
                `Número de factura: "${sugerencia.valor_actual}" → ` +
                `"${sugerencia.valor_sugerido}". ${evidencia} `
            ));
            boton.textContent = 'Aceptar número';
            boton.disabled = !correlativo || !/^[0-9]{6}$/.test(numeroSugerido);
            boton.addEventListener('click', () => {
                if (!correlativo || !/^[0-9]{6}$/.test(numeroSugerido)) return;
                correlativo.value = numeroSugerido;
                correlativo.dispatchEvent(new Event('input', { bubbles: true }));
                boton.disabled = true;
                boton.textContent = 'Número actualizado';
            });
        } else if (sugerencia.campo === 'porcentaje_iva') {
            const iva = document.getElementById('porcentajeIvaRecepcion');
            elemento.appendChild(document.createTextNode(
                `Porcentaje de IVA: "${sugerencia.valor_actual}" → ` +
                `"${sugerencia.valor_sugerido}". ${evidencia} `
            ));
            boton.textContent = 'Aceptar IVA';
            boton.disabled = !iva;
            boton.addEventListener('click', () => {
                if (!iva) return;
                iva.value = sugerencia.valor_sugerido;
                iva.dispatchEvent(new Event('input', { bubbles: true }));
                calcularTotal();
                boton.disabled = true;
                boton.textContent = 'IVA actualizado';
            });
        } else if (['subtotal_factura', 'monto_iva', 'total_factura'].includes(sugerencia.campo)) {
            const etiquetas = {
                subtotal_factura: 'Subtotal',
                monto_iva: 'Monto de IVA',
                total_factura: 'Total'
            };
            elemento.textContent =
                `${etiquetas[sugerencia.campo]} sugerido para comparar: ` +
                `"${sugerencia.valor_sugerido}". ${evidencia} ` +
                'El formulario calcula este valor a partir de sus componentes.';
            lista.appendChild(elemento);
            return;
        } else if (sugerencia.campo === 'producto') {
            const camposNombre = {
                nombre: 'nombre',
                modelo: 'modelo',
                marca: 'marca',
                serial: 'serial',
                cantidad: 'cantidad',
                costo: 'costo'
            };
            const camposSugeridos = Object.entries(sugerencia.valor_sugerido || {})
                .map(([campo, valor]) => `${camposNombre[campo] || campo}: "${valor}"`)
                .join(', ');
            const camposActuales = Object.entries(sugerencia.valor_actual || {})
                .map(([campo, valor]) => `${camposNombre[campo] || campo}: "${valor}"`)
                .join(', ');
            const selectores = document.querySelectorAll('#productosList .producto-select');
            const selector = selectores[sugerencia.indice_producto];
            const target = sugerencia.valor_sugerido || {};
            const camposCatalogo = ['nombre', 'modelo', 'marca', 'serial'];
            const requiereCatalogo = camposCatalogo.some(campo => target[campo]);
            const opcion = requiereCatalogo
                ? buscarOpcionCatalogoPorDatos(selector, target)
                : null;
            const fila = selector && selector.closest('.producto-row');
            const campoCantidad = fila && fila.querySelector('input[name="cantidad[]"]');
            const campoCosto = fila && fila.querySelector('input[name="costo[]"]');
            const puedeAplicar = Boolean(selector) &&
                (!requiereCatalogo || Boolean(opcion)) &&
                (!target.cantidad || Boolean(campoCantidad)) &&
                (!target.costo || Boolean(campoCosto));
            elemento.appendChild(document.createTextNode(
                `Producto ${Number(sugerencia.indice_producto) + 1}: ` +
                `${camposActuales ? `${camposActuales} → ` : ''}` +
                `${camposSugeridos || 'sin campos disponibles'}. ${evidencia} `
            ));
            boton.textContent = 'Aceptar campos';
            boton.disabled = !puedeAplicar;
            boton.addEventListener('click', () => {
                if (!puedeAplicar) return;
                if (opcion && selector) {
                    selector.value = opcion.value;
                    seleccionarProducto(selector);
                    selector.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (target.cantidad && campoCantidad) {
                    campoCantidad.value = target.cantidad;
                    campoCantidad.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (target.costo && campoCosto) {
                    campoCosto.value = Number(target.costo).toFixed(2);
                    calcularSubtotal(campoCosto);
                }
                boton.disabled = true;
                boton.textContent = 'Campos actualizados';
            });
        } else {
            return;
        }

        elemento.appendChild(boton);
        lista.appendChild(elemento);
    });

    if (lista.childElementCount) resultado.appendChild(lista);
}

function construirFeedbackCamposIA(prediccion) {
    if (!prediccion) return;
    const productos = Array.from(document.querySelectorAll('#productosList .producto-row'))
        .map(fila => {
            const selector = fila.querySelector('.producto-select');
            const opcion = selector && selector.selectedOptions[0];
            return {
                id_producto: selector ? selector.value : '',
                nombre: opcion && selector.value ? opcion.textContent.trim() : '',
                modelo: opcion && selector.value ? opcion.dataset.modelo || '' : '',
                marca: opcion && selector.value ? opcion.dataset.marca || '' : '',
                serial: opcion && selector.value ? opcion.dataset.serial || '' : '',
                cantidad: Number(fila.querySelector('input[name="cantidad[]"]')?.value || 0),
                costo: Number(fila.querySelector('input[name="costo[]"]')?.value || 0)
            };
        })
        .filter(producto => producto.id_producto);

    const nombreProveedor = document.querySelector('#proveedor option:checked')?.textContent?.trim() || '';
    const subtotal = productos.reduce(
        (suma, producto) => suma + producto.cantidad * producto.costo,
        0
    );
    const porcentajeIva = Number(
        document.getElementById('porcentajeIvaRecepcion')?.value || 0
    );
    const montoIva = Math.round(
        (subtotal * porcentajeIva / 100 + Number.EPSILON) * 100
    ) / 100;

    prediccion.campos_predichos = {
        numero_factura: prediccion.numero_factura || '',
        nombre_proveedor: prediccion.nombre_proveedor || '',
        productos: Array.isArray(prediccion.productos) ? prediccion.productos : [],
        subtotal_factura: Number(prediccion.subtotal_factura || 0),
        porcentaje_iva: Number(prediccion.porcentaje_iva || 0),
        monto_iva: Number(prediccion.monto_iva || 0),
        total_factura: Number(prediccion.total_factura || 0)
    };
    prediccion.campos_confirmados = {
        numero_factura: document.getElementById('correlativo')?.value?.trim() || '',
        nombre_proveedor: nombreProveedor,
        productos,
        subtotal_factura: Number(subtotal.toFixed(2)),
        porcentaje_iva: Number(porcentajeIva.toFixed(2)),
        monto_iva: Number(montoIva.toFixed(2)),
        total_factura: Number((subtotal + montoIva).toFixed(2))
    };
}

function rellenarRecepcionDesdeFactura(factura) {
    if (!factura) return;
    datosFacturaExtraidaIA = factura;
    productosEliminadosManualmenteIA.clear();
    document.querySelectorAll('#productosList .producto-row').forEach(fila => {
        delete fila.dataset.indiceFactura;
    });
    
    // Validar si el documento es una factura válida
    if (factura.es_factura_valida === false) {
        actualizarCAIRecepcion('preocupada');
        establecerEstadoIARecepcion(factura.mensaje_validacion || 'El documento no parece ser una factura válida. Por favor seleccione una factura legítima.', 'error');
        facturaValidaParaRegistro = false;
        bloquearBotonGuardar(true);
        return;
    }
    
    const correlativo = document.getElementById('correlativo');
    const numeroFactura = String(factura.numero_factura || '').trim();
    const numeroFacturaValido = /^[0-9]{6}$/.test(numeroFactura);
    if (correlativo && numeroFacturaValido) {
        correlativo.value = numeroFactura;
        correlativo.dispatchEvent(new Event('input', { bubbles: true }));
    }

    const proveedor = document.getElementById('proveedor');
    const proveedorEncontrado = buscarOpcionCatalogo(proveedor, factura.nombre_proveedor);
    if (proveedor) proveedor.value = proveedorEncontrado ? proveedorEncontrado.value : '';

    const productosDetectados = Array.isArray(factura.productos) ? factura.productos : [];
    const productosList = document.getElementById('productosList');
    if (productosList) {
        while (productosList.querySelectorAll('.producto-row').length > 1) {
            productosList.lastElementChild.remove();
        }
        const primeraFila = productosList.querySelector('.producto-row');
        if (primeraFila) {
            primeraFila.querySelectorAll('select, input').forEach(campo => {
                if (campo.classList.contains('subtotal-display')) campo.value = '$0.00';
                else campo.value = '';
            });
            const selector = primeraFila.querySelector('.producto-select');
            if (selector) {
                selector.classList.remove('is-valid', 'is-invalid');
                delete selector.dataset.productoDuplicado;
            }
            const mensajeProducto = primeraFila.querySelector('.sproducto-msg');
            if (mensajeProducto) mensajeProducto.textContent = '';
        }

        if (productosDetectados.length) {
            productosDetectados.forEach((producto, indice) => {
                const fila = indice === 0
                    ? primeraFila
                    : addProductoRow();
                if (!fila) return;
                fila.dataset.indiceFactura = String(indice);
                const selector = fila.querySelector('.producto-select');
                const opcion = buscarProductoFacturaEnCatalogo(selector, producto);
                if (opcion && selector) {
                    selector.value = opcion.value;
                    seleccionarProducto(selector);
                }
                const inputCant = fila.querySelector('input[name="cantidad[]"]');
                if (inputCant) inputCant.value = producto.cantidad > 0 ? producto.cantidad : 1;
                
                const inputCosto = fila.querySelector('input[name="costo[]"]');
                if (inputCosto) {
                    inputCosto.value = Number(producto.costo_unitario || producto.costo || 0).toFixed(2);
                    calcularSubtotal(inputCosto);
                }
            });
        }
    }

    const inputIva = document.getElementById('porcentajeIvaRecepcion');
    if (inputIva) {
        let ivaVal = Number(factura.porcentaje_iva || 0);
        if (ivaVal > 99) ivaVal = 99;
        inputIva.value = ivaVal.toFixed(2);
    }
    calcularTotal();
    
    // Establecer explícitamente los montos extraídos de la factura
    if (factura.subtotal_factura && document.getElementById('subtotalRecepcion')) {
        document.getElementById('subtotalRecepcion').textContent = '$' + Number(factura.subtotal_factura).toFixed(2);
    }
    if (factura.monto_iva && document.getElementById('montoIvaRecepcion')) {
        document.getElementById('montoIvaRecepcion').textContent = '$' + Number(factura.monto_iva).toFixed(2);
    }
    if (factura.total_factura && document.getElementById('totalValue')) {
        document.getElementById('totalValue').textContent = '$' + Number(factura.total_factura).toFixed(2);
    }
    
    actualizarContadorProductos();
    actualizarDisponibilidadProductos();

    const camposPendientes = [];
    if (!proveedorEncontrado) camposPendientes.push('proveedor');
    if (!numeroFacturaValido) camposPendientes.push('número de factura');
    
    // Validar si los productos están en el catálogo
    const primerSelector = document.querySelector('.producto-select');
    let productos_no_en_catalogo = 0;
    if (productosDetectados.length > 0 && primerSelector) {
        productos_no_en_catalogo = productosDetectados.filter(producto =>
            !buscarProductoFacturaEnCatalogo(primerSelector, producto)
        ).length;
    }
    
    if (productos_no_en_catalogo > 0) {
        camposPendientes.push('selección de productos del catálogo');
    }
    
    const nota = camposPendientes.length
        ? ` Revisa manualmente: ${camposPendientes.join(' y ')}.`
        : ' Revisa los datos antes de guardar.';
    
    // Si todos los productos están fuera del catálogo, mostrar advertencia específica
    if (productos_no_en_catalogo === productosDetectados.length && productosDetectados.length > 0) {
        actualizarCAIRecepcion('preocupada');
        establecerEstadoIARecepcion(`La factura seleccionada no se relaciona con los productos del catálogo. Factura inválida para este sistema.${nota}`, 'error');
        facturaValidaParaRegistro = false;
        bloquearBotonGuardar(true);
        return;
    }
    
    // Factura válida, desbloquear botón
    facturaValidaParaRegistro = true;
    bloquearBotonGuardar(false);
    establecerEstadoIARecepcion(`Análisis completado. Factura: ${factura.numero_factura || 'no detectada'}.${nota}`);
    if (
        camposPendientes.length ||
        !numeroFacturaValido ||
        !factura.nombre_proveedor ||
        !productosDetectados.length
    ) {
        actualizarCAIRecepcion('preocupada');
    } else {
        actualizarCAIRecepcion(
            'amigable',
            '¡Análisis listo, Maestr@! Revisemos los datos antes de registrar la recepción.'
        );
    }

    const resultadoIARecepcionEl = document.getElementById('resultadoIARecepcion');
    if (resultadoIARecepcionEl) {
        const tituloResultado = document.createElement('strong');
        tituloResultado.textContent = 'Resultado del Análisis:';
        resultadoIARecepcionEl.replaceChildren(tituloResultado, document.createElement('br'));
        resultadoIARecepcionEl.appendChild(document.createTextNode(
            `Número de factura detectado: ${factura.numero_factura || 'no detectado'}\n` +
            `Proveedor detectado: ${factura.nombre_proveedor || 'no detectado'}\n` +
            `Productos detectados: ${productosDetectados.length}\n` +
            `Subtotal: $${Number(factura.subtotal_factura || 0).toFixed(2)}\n` +
            `IVA: ${Number(factura.porcentaje_iva || 0).toFixed(2)}% ($${Number(factura.monto_iva || 0).toFixed(2)})\n` +
            `Total factura: $${Number(factura.total_factura || 0).toFixed(2)}\n` +
            `Confianza OCR: ${(Number(factura.confianza_promedio || 0) * 100).toFixed(1)}%`
        ));
        mostrarSugerenciasAutocorreccion(factura);
        resultadoIARecepcionEl.hidden = false;
    }
    evaluarDiscrepanciasInteractivas();
}

function limpiarFacturaIARecepcion() {
    try {
        const aiClient = obtenerAsistenteIA();
        if (aiClient && typeof aiClient.limpiarCache === 'function') {
            aiClient.limpiarCache();
        }
    } catch (e) {
        console.warn('Aviso limpiando caché IA:', e);
    }

    if (urlPreviewFacturaRecepcion) {
        URL.revokeObjectURL(urlPreviewFacturaRecepcion);
    }
    urlPreviewFacturaRecepcion = null;
    discrepanciasActivasIA = null;
    datosFacturaExtraidaIA = null;
    productosEliminadosManualmenteIA.clear();
    document.querySelectorAll('#productosList .producto-row').forEach(fila => {
        delete fila.dataset.indiceFactura;
    });
    facturaValidaParaRegistro = true; // Reiniciar estado al limpiar
    bloquearBotonGuardar(false);
    
    const previewFac = document.getElementById('previewFacturaRecepcion');
    const prevImg = document.getElementById('previewImagenRecepcion');
    const prevPdf = document.getElementById('previewPdfRecepcion');
    const resIA = document.getElementById('resultadoIARecepcion');

    if (previewFac) previewFac.hidden = true;
    if (prevImg) { prevImg.hidden = true; prevImg.removeAttribute('src'); }
    if (prevPdf) { prevPdf.hidden = true; prevPdf.removeAttribute('src'); }
    if (resIA) { resIA.replaceChildren(); resIA.hidden = true; }
    establecerEstadoIARecepcion('');
}

function bloquearBotonGuardar(bloquear) {
    const botonGuardar = document.querySelector('#recepcionModal .modal-footer .btn-save');
    if (botonGuardar) {
        botonGuardar.disabled = bloquear;
        if (bloquear) {
            botonGuardar.title = 'No se puede registrar con una factura inválida';
        } else {
            botonGuardar.title = '';
        }
    }
}

async function analizarFacturaRecepcion(archivo) {
    try {
        const aiClient = obtenerAsistenteIA();
        if (!aiClient) {
            actualizarCAIRecepcion('preocupada');
            activarContingenciaIARecepcion('Librería del microservicio no disponible.');
            return;
        }
        actualizarCAIRecepcion(
            'amigable',
            'Estoy revisando la factura con cuidado, Maestr@. Enseguida le mostraré los datos detectados.'
        );
        establecerEstadoIARecepcion('Analizando factura con OCR...');
        const resultado = await aiClient.extraerDesdeImagen(archivo);
        if (!resultado || !resultado.exito || !resultado.data) {
            actualizarCAIRecepcion('preocupada');
            if (resultado && resultado.contingencia) activarContingenciaIARecepcion(resultado.error);
            else establecerEstadoIARecepcion(`No se pudo analizar la factura: ${resultado?.error || 'faltan datos extraídos'}`, 'error');
            return;
        }
        rellenarRecepcionDesdeFactura(resultado.data);
    } catch (error) {
        console.error('Error al analizar factura con IA:', error);
        actualizarCAIRecepcion('preocupada');
        activarContingenciaIARecepcion(error.message || 'Error de comunicación con el microservicio.');
    }
}

// ==========================================
// 5. GESTIÓN DE MODALES Y PRODUCTOS
// ==========================================
function openModal(type) {
    const modal = document.getElementById('recepcionModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('recepcionForm');
    
    if (type === 'registrar') {
        if (modalTitle) modalTitle.textContent = 'Incluir Recepción';
        if (form) form.reset();
        limpiarFacturaIARecepcion();
        actualizarCAIRecepcion('amigable');
        establecerSeccionCAIRecepcionVisible(false);
        const filas = document.querySelectorAll('#productosList .producto-row');
        filas.forEach((fila, indice) => { if (indice > 0) fila.remove(); });
        actualizarContadorProductos();
        actualizarDisponibilidadProductos();
        calcularTotal();
    }
    if (modal) {
        modal.style.display = 'block';
        modal.style.animation = 'fadeIn 0.3s ease';
    }
}

function closeModal() {
    const modal = document.getElementById('recepcionModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function addProductoRow() {
    const productosList = document.getElementById('productosList');
    if (!productosList) return null;
    const firstRow = productosList.querySelector('.producto-row');
    if (!firstRow) return null;

    const newRow = firstRow.cloneNode(true);
    newRow.style.animation = 'slideDown 0.3s ease';
    delete newRow.dataset.indiceFactura;
    
    const select = newRow.querySelector('.producto-select');
    if (select) {
        select.value = '';
        select.removeAttribute('id');
        delete select.dataset.productoDuplicado;
    }
    const mensajeProducto = newRow.querySelector('.sproducto-msg');
    if (mensajeProducto) mensajeProducto.textContent = '';

    const codigoInput = newRow.querySelector('.producto-codigo');
    if (codigoInput) codigoInput.value = '';
    const marcaInput = newRow.querySelector('.producto-marca');
    if (marcaInput) marcaInput.value = '';
    const modeloInput = newRow.querySelector('.producto-modelo');
    if (modeloInput) modeloInput.value = '';
    const serialInput = newRow.querySelector('.producto-serial');
    if (serialInput) serialInput.value = '';
    
    const cantInput = newRow.querySelector('input[name="cantidad[]"]');
    if (cantInput) cantInput.value = '';
    const costoInput = newRow.querySelector('input[name="costo[]"]');
    if (costoInput) costoInput.value = '';
    
    const subtotalDisplay = newRow.querySelector('.subtotal-display');
    if (subtotalDisplay) subtotalDisplay.value = '$0.00';

    newRow.querySelectorAll('.span-value').forEach(el => el.textContent = '');
    newRow.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));

    productosList.appendChild(newRow);
    actualizarContadorProductos();
    actualizarProteccionSelectsProductos();
    return newRow;
}

function seleccionarProducto(select) {
    const valorSeleccionado = select.value;
    const duplicado = valorSeleccionado && Array.from(
        document.querySelectorAll('#productosList .producto-select')
    ).some(otroSelect => otroSelect !== select && otroSelect.value === valorSeleccionado);
    if (duplicado) {
        select.value = '';
        select.dataset.productoDuplicado = 'true';
        select.classList.remove('is-valid');
        select.classList.add('is-invalid');
        const mensaje = select.closest('.producto-selector-group')?.querySelector('.sproducto-msg');
        if (mensaje) mensaje.textContent = '*Este producto ya está seleccionado en otra fila*';
    } else {
        delete select.dataset.productoDuplicado;
    }

    const opcion = select.selectedOptions[0];
    const fila = select.closest('.producto-row');
    if (!fila) return;
    const codigoInput = fila.querySelector('.producto-codigo');
    if (codigoInput) codigoInput.value = opcion?.dataset.codigo || '';
    const marcaInput = fila.querySelector('.producto-marca');
    if (marcaInput) marcaInput.value = opcion?.dataset.marca || '';
    const modeloInput = fila.querySelector('.producto-modelo');
    if (modeloInput) modeloInput.value = opcion?.dataset.modelo || '';
    const serialInput = fila.querySelector('.producto-serial');
    if (serialInput) serialInput.value = opcion?.dataset.serial || '';
    calcularSubtotal(select);
    actualizarDisponibilidadProductos();
}

function calcularSubtotal(input) {
    const row = input.closest('.producto-row');
    if (!row) return;
    const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]')?.value) || 0;
    const costo = parseFloat(row.querySelector('input[name="costo[]"]')?.value) || 0;
    const subtotal = cantidad * costo;
    const display = row.querySelector('.subtotal-display');
    if (display) display.value = '$' + subtotal.toFixed(2);
    calcularTotal();
}

function calcularTotal() {
    const filas = document.querySelectorAll('#productosList .producto-row');
    const subtotal = Array.from(filas).reduce((suma, fila) => {
        const cantidad = Number(fila.querySelector('input[name="cantidad[]"]')?.value) || 0;
        const costo = Number(fila.querySelector('input[name="costo[]"]')?.value) || 0;
        return suma + cantidad * costo;
    }, 0);

    let porcentajeIva = Number(document.getElementById('porcentajeIvaRecepcion')?.value) || 0;
    if (porcentajeIva > 99) porcentajeIva = 99;

    const montoIva = Math.round((subtotal * (porcentajeIva / 100) + Number.EPSILON) * 100) / 100;

    if (document.getElementById('subtotalRecepcion')) document.getElementById('subtotalRecepcion').textContent = '$' + subtotal.toFixed(2);
    if (document.getElementById('montoIvaRecepcion')) document.getElementById('montoIvaRecepcion').textContent = '$' + montoIva.toFixed(2);
    if (document.getElementById('totalValue')) document.getElementById('totalValue').textContent = '$' + (subtotal + montoIva).toFixed(2);
}

function actualizarContadorProductos() {
    const filas = Array.from(document.querySelectorAll('#productosList .producto-row'));
    const count = filas.length;
    const counterEl = document.querySelector('.product-count');
    if (counterEl) counterEl.textContent = count + ' producto' + (count !== 1 ? 's' : '');
    filas.forEach((fila, indice) => {
        const numero = fila.querySelector('.producto-index');
        if (numero) {
            numero.textContent = String(indice + 1);
            numero.setAttribute('aria-label', `Producto ${indice + 1}`);
        }
    });
}

function removeProductoRow(button) {
    const row = button.closest('.producto-row');
    if (!row) return;
    if (document.querySelectorAll('.producto-row').length > 1) {
        const indiceFactura = row.dataset.indiceFactura || '';
        if (/^\d+$/.test(indiceFactura)) {
            productosEliminadosManualmenteIA.add(Number(indiceFactura));
        }
        row.style.animation = 'slideUp 0.3s ease';
        setTimeout(() => {
            row.remove();
            actualizarContadorProductos();
            calcularTotal();
            actualizarProteccionSelectsProductos();
            evaluarDiscrepanciasInteractivas();
        }, 300);
    } else {
        alert('Debe haber al menos un producto');
    }
}

// ==========================================
// CONSTRUCTOR GRANULAR Y EVALUADOR COMPLETO
// ==========================================
function construirListaGranularDiscrepancias(discrepanciasRaw) {
    const listaGranular = [];

    // 1. Evaluación de IVA %
    if (datosFacturaExtraidaIA && datosFacturaExtraidaIA.porcentaje_iva !== undefined) {
        const ivaFactura = Number(datosFacturaExtraidaIA.porcentaje_iva || 0);
        const ivaFormulario = Number(document.getElementById('porcentajeIvaRecepcion')?.value || 0);
        
        if (Math.abs(ivaFormulario - ivaFactura) > 0.01) {
            listaGranular.push({
                tipo: 'porcentaje_iva',
                campo: 'Porcentaje de IVA (%)',
                valor_factura: `${ivaFactura.toFixed(2)}%`,
                valor_formulario: `${ivaFormulario.toFixed(2)}%`
            });
        }
    }

    // 2. Evaluación de otros campos y productos
    discrepanciasRaw.forEach(item => {
        if (item.campo.toLowerCase().includes('producto')) {
            const indexMatch = item.campo.match(/\d+/);
            const idx = indexMatch ? parseInt(indexMatch[0], 10) - 1 : 0;
            const productoFactura = datosFacturaExtraidaIA?.productos?.[idx] || {};

            const filaForm = document.querySelectorAll('#productosList .producto-row')[idx];
            const nombreForm = filaForm?.querySelector('.producto-select option:checked')?.textContent?.trim() || item.valor_formulario;
            const costoForm = Number(filaForm?.querySelector('input[name="costo[]"]')?.value || 0);
            const cantidadForm = Number(filaForm?.querySelector('input[name="cantidad[]"]')?.value || 0);

            const nombreFactura = productoFactura.nombre || item.valor_factura;
            const costoFactura = Number(productoFactura.costo_unitario || productoFactura.costo || 0);
            const cantidadFactura = Number(productoFactura.cantidad || 0);

            // Diferencia en Nombre
            if (normalizarTextoRecepcion(nombreForm) !== normalizarTextoRecepcion(nombreFactura)) {
                listaGranular.push({
                    tipo: 'producto_nombre',
                    index: idx,
                    campo: `Producto ${idx + 1} (Nombre)`,
                    valor_factura: nombreFactura !== 'No encontrado' ? nombreFactura : 'Según factura',
                    valor_formulario: nombreForm || 'Sin seleccionar'
                });
            }

            // Diferencia en Costo Unitario
            if (costoFactura > 0 && Math.abs(costoForm - costoFactura) > 0.01) {
                listaGranular.push({
                    tipo: 'producto_costo',
                    index: idx,
                    campo: `Producto ${idx + 1} (Costo Unitario)`,
                    valor_factura: `$${costoFactura.toFixed(2)}`,
                    valor_formulario: `$${costoForm.toFixed(2)}`
                });
            }

            // Diferencia en Cantidad
            if (cantidadFactura > 0 && cantidadForm !== cantidadFactura) {
                listaGranular.push({
                    tipo: 'producto_cantidad',
                    index: idx,
                    campo: `Producto ${idx + 1} (Cantidad)`,
                    valor_factura: `${cantidadFactura}`,
                    valor_formulario: `${cantidadForm}`
                });
            }

            if (!listaGranular.some(g => g.index === idx)) {
                listaGranular.push({
                    tipo: 'producto_generico',
                    index: idx,
                    campo: item.campo,
                    valor_factura: item.valor_factura,
                    valor_formulario: item.valor_formulario
                });
            }
        } else if (item.campo.toLowerCase().includes('factura')) {
            listaGranular.push({
                tipo: 'factura',
                campo: item.campo,
                valor_factura: item.valor_factura,
                valor_formulario: item.valor_formulario
            });
        } else if (item.campo.toLowerCase().includes('proveedor')) {
            listaGranular.push({
                tipo: 'proveedor',
                campo: item.campo,
                valor_factura: item.valor_factura,
                valor_formulario: item.valor_formulario
            });
        }
    });

    return listaGranular;
}

function obtenerDiscrepanciasRecepcionActuales() {
    if (!datosFacturaExtraidaIA) return [];
    const correlativoForm = space(document.getElementById('correlativo')?.value || '').trim();
    const proveedorSelect = document.getElementById('proveedor');
    const proveedorOpcion = proveedorSelect?.selectedOptions?.[0];
    const proveedorForm = proveedorOpcion?.value
        ? proveedorOpcion.textContent.trim()
        : '';
    const ivaForm = Number(document.getElementById('porcentajeIvaRecepcion')?.value || 0);

    const productosForm = Array.from(document.querySelectorAll('#productosList .producto-row')).map(fila => ({
        nombre: fila.querySelector('.producto-select option:checked')?.textContent?.trim() || '',
        productoId: fila.querySelector('.producto-select')?.value || '',
        cantidad: Number(fila.querySelector('input[name="cantidad[]"]')?.value || 0),
        costo: Number(fila.querySelector('input[name="costo[]"]')?.value || 0),
        indiceFactura: /^\d+$/.test(fila.dataset.indiceFactura || '')
            ? Number(fila.dataset.indiceFactura)
            : null
    }));

    const productosFactura = Array.isArray(datosFacturaExtraidaIA.productos)
        ? datosFacturaExtraidaIA.productos
        : [];
    const pendientes = [];

    const numeroFactura = String(datosFacturaExtraidaIA.numero_factura || '').trim();
    if (
        !numeroFactura ||
        normalizarTextoRecepcion(correlativoForm) !== normalizarTextoRecepcion(numeroFactura)
    ) {
        pendientes.push({
            tipo: 'factura',
            campo: 'Número de Factura',
            valor_factura: numeroFactura || 'No detectado',
            valor_formulario: correlativoForm || 'Vacio'
        });
    }

    if (datosFacturaExtraidaIA?.nombre_proveedor) {
        if (proveedorForm && normalizarTextoRecepcion(proveedorForm) !== normalizarTextoRecepcion(datosFacturaExtraidaIA.nombre_proveedor)) {
            pendientes.push({
                tipo: 'proveedor',
                campo: 'Proveedor',
                valor_factura: datosFacturaExtraidaIA.nombre_proveedor,
                valor_formulario: proveedorForm
            });
        }
    }
    if (!proveedorForm) {
        pendientes.push({
            tipo: 'proveedor',
            campo: 'Proveedor',
            valor_factura: datosFacturaExtraidaIA?.nombre_proveedor || 'No detectado',
            valor_formulario: 'Sin seleccionar'
        });
    }

    productosFactura.forEach((prodFactura, idxFactura) => {
        const prodForm = productosForm.find(pf => pf.indiceFactura === idxFactura);
        if (!prodForm) {
            if (productosEliminadosManualmenteIA.has(idxFactura)) {
                pendientes.push({
                    tipo: 'producto_eliminado',
                    index: idxFactura,
                    campo: `Producto ${idxFactura + 1} (Eliminado del formulario)`,
                    valor_factura: prodFactura.nombre || 'Según factura',
                    valor_formulario: 'No está en formulario'
                });
            }
            return;
        }

        const nombreFactura = String(prodFactura.nombre || '').trim();
        const opcionFactura = buscarProductoFacturaEnCatalogo(
            document.querySelector('.producto-select'),
            prodFactura
        );
        const mismoProductoCatalogado = Boolean(
            opcionFactura && String(prodForm.productoId) === String(opcionFactura.value)
        );
        if (
            nombreFactura &&
            !mismoProductoCatalogado &&
            normalizarTextoRecepcion(nombreFactura) !== normalizarTextoRecepcion(prodForm.nombre)
        ) {
            pendientes.push({
                tipo: 'producto_nombre',
                index: idxFactura,
                campo: `Producto ${idxFactura + 1} (Nombre)`,
                valor_factura: nombreFactura,
                valor_formulario: prodForm.nombre || 'Sin seleccionar'
            });
        }

        const costoFactura = Number(prodFactura.costo_unitario || prodFactura.costo || 0);
        if (costoFactura > 0 && Math.abs(prodForm.costo - costoFactura) > 0.01) {
            pendientes.push({
                tipo: 'producto_costo',
                index: idxFactura,
                campo: `Producto ${idxFactura + 1} (Costo Unitario)`,
                valor_factura: `$${costoFactura.toFixed(2)}`,
                valor_formulario: `$${prodForm.costo.toFixed(2)}`
            });
        }

        const cantidadFactura = Number(prodFactura.cantidad || 0);
        if (cantidadFactura > 0 && prodForm.cantidad !== cantidadFactura) {
            pendientes.push({
                tipo: 'producto_cantidad',
                index: idxFactura,
                campo: `Producto ${idxFactura + 1} (Cantidad)`,
                valor_factura: `${cantidadFactura}`,
                valor_formulario: `${prodForm.cantidad}`
            });
        }
    });

    productosForm.forEach((prodForm, idxForm) => {
        if (prodForm.indiceFactura === null && prodForm.nombre) {
            pendientes.push({
                tipo: 'producto_agregado',
                index: idxForm,
                campo: `Producto ${idxForm + 1} (Agregado manualmente)`,
                valor_factura: 'No estaba en factura',
                valor_formulario: prodForm.nombre
            });
        }
    });

    const ivaFactura = Number(datosFacturaExtraidaIA?.porcentaje_iva || 0);
    if (Math.abs(ivaForm - ivaFactura) > 0.01) {
        pendientes.push({
            tipo: 'porcentaje_iva',
            campo: 'Porcentaje de IVA (%)',
            valor_factura: `${ivaFactura.toFixed(2)}%`,
            valor_formulario: `${ivaForm.toFixed(2)}%`
        });
    }

    return pendientes;
}

function evaluarDiscrepanciasInteractivas() {
    if (!datosFacturaExtraidaIA) return;
    const pendientes = obtenerDiscrepanciasRecepcionActuales();

    if (pendientes.length > 0) {
        actualizarCAIRecepcion(
            'preocupada',
            'Parece que encontré diferencias en la información, Maestr@. Revisémoslas antes de continuar.'
        );
        const detalleHTML = pendientes.map(item =>
            `<li style="margin-bottom: 3px;"><b>${item.campo}</b>: factura "${item.valor_factura}" / formulario "${item.valor_formulario}"</li>`
        ).join('');

        const mensajeBloqueoHTML = 
            `<div><b>Registro Inaceptable:</b> corrige las discrepancias críticas con la factura.</div>` +
            `<ul style="margin: 8px 0 0 18px; padding: 0; text-align: left; font-size: 0.9em; list-style-type: disc;">${detalleHTML}</ul>`;

        establecerEstadoIARecepcion(mensajeBloqueoHTML, 'error');
    } else {
        const mensajeExitoHTML = `<b>Registro Aceptable:</b> no hay discrepancias pendientes, puede proceder a incluir la recepción.`;
        establecerEstadoIARecepcion(mensajeExitoHTML, 'success');
    }
}

// ==========================================
// 6. ACCIONES: GUARDAR, DETALLAR, ANULAR
// ==========================================
async function saveRecepcion(event) {
    if (event) event.preventDefault();

    // Verificar si la factura es válida para registro
    if (!facturaValidaParaRegistro) {
        actualizarCAIRecepcion('regañona');
        mensajes('error', 'Factura inválida', 'No se puede registrar con una factura que no ha sido validada. Por favor seleccione una factura válida.');
        return;
    }

    if (!validarEnvioRecepcion() || !verificaproductos()) {
        actualizarCAIRecepcion('regañona');
        return;
    }

    const botonGuardar = document.querySelector('#recepcionModal .modal-footer .btn-save');
    if (botonGuardar) botonGuardar.disabled = true;
    
    try {
        const archivo = document.getElementById('fotoFacturaRecepcion')?.files[0];
        let modoContingencia = false;
        let iaVerificada = false;
        let prediccionIA = null; // Para guardar los datos de predicción de la IA
        const aiClient = obtenerAsistenteIA();

        if (archivo) {
            if (!aiClient) {
                modoContingencia = true;
            } else if (!aiClient.getFacturaId()) {
                const extraccion = await aiClient.extraerDesdeImagen(archivo);
                if (!extraccion.exito) {
                    if (!extraccion.contingencia) throw new Error(extraccion.error || 'No se pudo analizar la factura.');
                    modoContingencia = true;
                } else {
                    // Guardar datos de predicción para feedback
                    prediccionIA = extraccion.data;
                    rellenarRecepcionDesdeFactura(extraccion.data);
                }
            } else {
                // Ya existe una extracción previa, recuperar sus datos para feedback
                try {
                    const datosExistentes = aiClient.obtenerDatosExtraidos();
                    if (datosExistentes) {
                        prediccionIA = datosExistentes;
                    }
                } catch (e) {
                    console.log('No se pudieron recuperar datos de extracción previa:', e);
                }
            }

            if (!modoContingencia && aiClient) {
                establecerEstadoIARecepcion('Verificando los datos antes del registro...');
                
                const pendientes = obtenerDiscrepanciasRecepcionActuales();

                if (pendientes.length > 0) {
                    const listaDiscrepancias = document.createElement('ul');
                    Object.assign(listaDiscrepancias.style, {
                        margin: '8px 0 0',
                        padding: '10px 10px 10px 28px',
                        maxHeight: '220px',
                        overflowY: 'auto',
                        borderRadius: '8px',
                        background: '#f8f9fa',
                        color: '#343a40',
                        textAlign: 'left'
                    });
                    pendientes.forEach(item => {
                        const fila = document.createElement('li');
                        fila.style.marginBottom = '6px';
                        const campo = document.createElement('strong');
                        campo.textContent = item.campo;
                        fila.append(campo, document.createTextNode(
                            `: factura "${item.valor_factura}" / formulario "${item.valor_formulario}"`
                        ));
                        listaDiscrepancias.appendChild(fila);
                    });

                    const resultadoSwal = await mostrarAlertaCAIRecepcion(
                        'preocupada',
                        'Diferencias detectadas',
                        '',
                        'Lo siento, Maestr@, he encontrado algunas diferencias entre los datos y la factura. ¿Desea registrar la recepción así o prefiere volver al formulario para corregirlas?',
                        {
                            contenidoAdicional: listaDiscrepancias,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, registrar igual',
                            cancelButtonText: 'Volver al formulario',
                            confirmButtonColor: 'rgb(192, 218, 47)',
                            cancelButtonColor: '#3085d6'
                        }
                    );

                    if (!resultadoSwal.isConfirmed) {
                        if (botonGuardar) botonGuardar.disabled = false;
                        // Actualizar el estado para mostrar las discrepancias actuales
                        evaluarDiscrepanciasInteractivas();
                        return;
                    }
                    // Usuario confirma registro a pesar de discrepancias
                    iaVerificada = true;
                    // Agregar discrepancias a los datos de predicción para feedback
                    if (prediccionIA) {
                        prediccionIA.discrepancias = pendientes;
                    }
                } else {
                    iaVerificada = true;
                }
            }

            if (modoContingencia) {
                activarContingenciaIARecepcion();
                if (!confirm('El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual. La factura se conservará al registrar. ¿Deseas continuar con el registro manual?')) {
                    if (botonGuardar) botonGuardar.disabled = false;
                    return;
                }
            }
        }

        const form = document.getElementById('recepcionForm');
        const formData = new FormData(form);
        formData.delete('foto_factura');
        formData.append('accion', 'registrar');
        if (archivo) formData.append('factura_contingencia', archivo, archivo.name);
        if (modoContingencia) formData.append('modo_contingencia', 'true');
        if (iaVerificada) formData.append('ia_verificada', 'true');
        // Indicar si se usó el microservicio de IA (solo si se verificó con IA)
        if (!modoContingencia && aiClient && iaVerificada) {
            formData.append('usar_ia', 'true');
        }
        // Enviar datos de predicción de la IA para feedback de aprendizaje
        if (prediccionIA) {
            construirFeedbackCamposIA(prediccionIA);
            formData.append('ia_prediccion', JSON.stringify(prediccionIA));
        }

        const response = await fetch('?pagina=recepcion', { method: 'POST', body: formData });
        const data = await response.json();

        if (data && data.status === 'success') {
            const registroConMicroservicio = Boolean(archivo && iaVerificada && !modoContingencia);
            const estadoExito = registroConMicroservicio ? 'amigable' : 'terca';
            const dialogoExito = registroConMicroservicio
                ? '¡Lo logramos, Maestr@! Mi análisis ayudó a registrar correctamente la recepción.'
                : '¿Porque no me pediste ayuda, Maestr@?, pero aun asi me alegra que la recepción se haya registrado correctamente.';
            const mensajeExito = data.message || '';
            actualizarCAIRecepcion(estadoExito, dialogoExito);
            document.getElementById('recepcionModal').style.display = 'none';
            
            if ($.fn.DataTable.isDataTable('table.table-info')) {
                const tabla = $('table.table-info').DataTable();
                
                const rec = data.recepcion || {};
                const rawFecha = rec.fecha || rec.fecha_recepcion || new Date().toISOString().split('T')[0];
                const fechaRaw = typeof rawFecha === 'object' ? (rawFecha.date || String(rawFecha)) : String(rawFecha);
                const nuevaFecha = formatearFecha(fechaRaw);
                const correlativoNum = String(rec.correlativo || document.getElementById('correlativo').value || '');
                const proveedorNombre = String(rec.nombre_proveedor || $('#proveedor option:checked').text() || '');
                const totalMontoStr = document.getElementById('totalValue')?.textContent || '$0.00';
                
                let botonesAccion = `<button class="btn-action btn-detallar" title="Ver Detalles" data-correlativo="${correlativoNum}" data-proveedor="${proveedorNombre}" data-fecha="${fechaRaw}" onclick="viewRecepcion(this)"><img src="assets/img/eye.svg"></button>`;
                
                if (typeof rolUsuarioActual !== 'undefined' && rolUsuarioActual === 'Administrador') {
                    botonesAccion += `<button class="btn-action btn-anular" title="Anular Recepción" onclick="anularRecepcion('${correlativoNum}')"><img src="assets/img/circle-x.svg"></button>`;
                }

                const $nuevaFila =$(`<tr>
                    <td data-order="${fechaRaw}">${nuevaFecha}</td>
                    <td>${correlativoNum}</td>
                    <td>${proveedorNombre}</td>
                    <td>${totalMontoStr}</td>
                    <td class="action-buttons">
                        ${botonesAccion}
                    </td>
                </tr>`);

                tabla.row.add($nuevaFila).draw(false);
            }

            await mostrarAlertaCAIRecepcion(estadoExito, 'Recepción registrada', mensajeExito, dialogoExito);
        } else {
            const facturaDuplicada = data.code === 'DUPLICATE_INVOICE';
            const estadoError = facturaDuplicada ? 'preocupada' : 'regañona';
            const mensajeError = data.message || 'No se pudo registrar la recepción.';
            await mostrarAlertaCAIRecepcion(
                estadoError,
                'No se pudo registrar la recepción',
                facturaDuplicada ? '' : mensajeError,
                facturaDuplicada ? mensajeError : undefined
            );
        }
    } catch (error) {
        console.error('Error al guardar recepción:', error);
        establecerEstadoIARecepcion(error.message || 'Error al guardar recepción.', 'error');
        await mostrarAlertaCAIRecepcion(
            'regañona',
            'No se pudo registrar la recepción',
            error.message || 'Por favor, intente nuevamente.'
        );
    } finally {
        if (botonGuardar) botonGuardar.disabled = false;
    }
}

function viewRecepcion(boton) {
    const datosFila = boton instanceof HTMLElement ? boton.dataset : {};
    const correlativo = datosFila.correlativo || boton;
    const proveedorFila = datosFila.proveedor || '';
    const fechaFila = datosFila.fecha || '';

    function actualizarInformacionGeneral(recepcion = {}) {
        if (document.getElementById('viewCorrelativo')) document.getElementById('viewCorrelativo').textContent = recepcion.correlativo || correlativo || 'N/A';
        if (document.getElementById('viewProveedor')) document.getElementById('viewProveedor').textContent = proveedorFila || recepcion.nombre_proveedor || 'No disponible';
        if (document.getElementById('viewFecha')) document.getElementById('viewFecha').textContent = fechaFila || recepcion.fecha || 'No disponible';
    }

    actualizarInformacionGeneral();

    fetch('?pagina=recepcion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'accion=obtener_detalle_recepcion&correlativo=' + encodeURIComponent(correlativo)
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.status === 'success') {
            const recepcion = data.recepcion;
            actualizarInformacionGeneral(recepcion);
            
            const productosTable = document.getElementById('viewProductos');
            if (productosTable) {
                productosTable.innerHTML = '';
                const productosRecepcion = recepcion.productos || data.productos || [];
                
                const subtotalFactura = recepcion.subtotal_factura !== undefined && recepcion.subtotal_factura !== null ? Number(recepcion.subtotal_factura) : 0;
                const porcentajeIva = recepcion.porcentaje_iva !== undefined && recepcion.porcentaje_iva !== null ? Number(recepcion.porcentaje_iva) : 0;
                const montoIva = recepcion.monto_iva !== undefined && recepcion.monto_iva !== null ? Number(recepcion.monto_iva) : 0;
                const totalFactura = recepcion.total_factura !== undefined && recepcion.total_factura !== null ? Number(recepcion.total_factura) : (subtotalFactura + montoIva);

                productosRecepcion.forEach(prod => {
                    const cantidad = Number(prod.cantidad) || 0;
                    const costo = Number(prod.costo) || 0;
                    const subtotalItem = cantidad * costo;
                    
                    const codigo = prod.codigo || prod.id_producto || 'N/A';
                    const nombreProd = prod.producto || prod.nombre_producto || 'N/A';
                    const marca = prod.marca || prod.nombre_marca || 'N/A';
                    const modelo = prod.modelo || prod.nombre_modelo || 'N/A';
                    const serial = prod.serial || 'N/A';

                    productosTable.innerHTML += `
                        <tr>
                            <td>${nombreProd}</td>
                            <td>${codigo}</td>
                            <td>${marca}</td>
                            <td>${modelo}</td>
                            <td>${serial}</td>
                            <td>${cantidad}</td>
                            <td>$${costo.toFixed(2)}</td>
                            <td>$${subtotalItem.toFixed(2)}</td>
                        </tr>
                    `;
                });

                if (document.getElementById('viewSubtotal')) {
                    document.getElementById('viewSubtotal').textContent = '$' + subtotalFactura.toFixed(2);
                }
                
                const ivaPorcentajeEl = document.getElementById('viewIvaPorcentaje');
                if (ivaPorcentajeEl) {
                    ivaPorcentajeEl.textContent = porcentajeIva > 0 ? porcentajeIva + '%' : '0%';
                }
                
                const ivaMontoEl = document.getElementById('viewMontoIva');
                if (ivaMontoEl) {
                    ivaMontoEl.textContent = montoIva > 0 ? '$' + montoIva.toFixed(2) : 'No registrado';
                }

                if (document.getElementById('viewTotal')) {
                    document.getElementById('viewTotal').textContent = '$' + totalFactura.toFixed(2);
                }
            }
        }
        const modal = document.getElementById('viewModal');
        if (modal) { modal.style.display = 'block'; modal.style.animation = 'fadeIn 0.3s ease'; }
    })
    .catch(() => {
        const modal = document.getElementById('viewModal');
        if (modal) { modal.style.display = 'block'; modal.style.animation = 'fadeIn 0.3s ease'; }
    });
}

function closeRecepcionModal() {
    const modal = document.getElementById('recepcionModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function closeViewModal() {
    const modal = document.getElementById('viewModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function anularRecepcion(correlativo) {
    const anularCorr = document.getElementById('anularCorrelativo');
    if (anularCorr) anularCorr.textContent = correlativo;
    
    const modal = document.getElementById('anularModal');
    if (modal) {
        modal.style.display = 'block';
        modal.style.animation = 'fadeIn 0.3s ease';
    }
}

function closeAnularModal() {
    const modal = document.getElementById('anularModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

async function confirmarAnulacion() {
    const correlativo = document.getElementById('anularCorrelativo')?.textContent;
    if (!correlativo) return;

    const formData = new FormData();
    formData.append('accion', 'anular');
    formData.append('correlativo', correlativo);
    
    const botonConfirmar = document.querySelector('#anularModal .btn-danger');
    if (botonConfirmar) botonConfirmar.disabled = true;

    try {
        const response = await fetch('?pagina=recepcion', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data && data.status === 'success') {
            document.getElementById('anularModal').style.display = 'none';
            
            if ($.fn.DataTable.isDataTable('table.table-info')) {
                const tabla = $('table.table-info').DataTable();
                tabla.rows().every(function() {
                    let $row = $(this.node());
                    let rowCorrelativo = $row.find('td:eq(1)').text().trim();
                    if (rowCorrelativo === correlativo) {
                        tabla.row(this).remove();
                    }
                });
                tabla.draw(false);
            }

            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message || 'La recepción se anuló correctamente.',
                confirmButtonText: 'OK'
            });
        } else {
            await Swal.fire({
                icon: 'error',
                title: 'Error al anular recepción',
                text: data.message || 'Error desconocido',
                confirmButtonText: 'Entendido'
            });
        }
    } catch (error) {
        console.error('Error al anular recepción:', error);
        await Swal.fire({
            icon: 'error',
            title: 'Error al anular recepción',
            text: error.message || 'Por favor, intente nuevamente.',
            confirmButtonText: 'Entendido'
        });
    } finally {
        if (botonConfirmar) botonConfirmar.disabled = false;
    }
}

// ==========================================
// 7. INICIALIZACIÓN DOCUMENT READY
// ==========================================
$(document).ready(function() {
    // DataTable se inicializa automáticamente por data-table.js
    // No duplicamos la inicialización aquí para evitar conflictos al recargar

    actualizarProteccionSelectsProductos();
    $('#registrarRecepcionModal').on('shown.bs.modal', function() {
        actualizarCAIRecepcion('amigable');
    });

    $("#correlativo").on("keypress", function(e){
        validarkeypress(/^[0-9]*$/, e);
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value);
    });

    $("#correlativo").on("keyup", function(){
        validarkeyup(/^[0-9]{6}$/, $(this),$("#scorrelativo"), "*Formato válido: solo números, 6 dígitos*");
        evaluarDiscrepanciasInteractivas();
    });

    $(document).on('paste', '#correlativo, .costo-input, .cantidad-input', function(e) {
        const input = this;
        const clipboardData = e.originalEvent && e.originalEvent.clipboardData
            ? e.originalEvent.clipboardData
            : window.clipboardData;
        if (!clipboardData) {
            return;
        }
        const textoPegado = clipboardData.getData('text');
        const valorPegado = textoPegado.trim();
        const esPegadoValido = obtenerReglaPegadoNumerico(input).test(valorPegado) &&
            (input.id !== 'porcentajeIvaRecepcion' || Number(valorPegado.replace(',', '.')) <= 99);

        if (!esPegadoValido) {
            e.preventDefault();
        }
    });

    $(document).on('input', '#correlativo, .costo-input, .cantidad-input', function() {
        sanitizarCampoNumerico(this);
        validarCampoNumerico(this);
        evaluarDiscrepanciasInteractivas();
    });

    $("#proveedor").on("change", function() {
        if ($(this).val()) {$(this).removeClass("is-invalid").addClass("is-valid");
            $("#sproveedor").text("");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
            $("#sproveedor").text("*Debe seleccionar un proveedor*");
        }
        evaluarDiscrepanciasInteractivas();
    });

    // Delegación de eventos para validaciones
    $(document).on('keypress', '.costo-input:not(#porcentajeIvaRecepcion)', function(e) {
        validarKeyPressDecimal(
            e,
            $(this).siblings('.scosto-msg'),
            '*Costo inválido (Ej: 10.50)*'
        );
    });
    $(document).on('keyup', '.costo-input:not(#porcentajeIvaRecepcion)', function() {
        limitarDigitos(this, 9, true);
        if (!consumirIntentoTeclaNumericaInvalida(this)) {
            validarKeyUpDecimal($(this),$(this).siblings('.scosto-msg'), "*Costo inválido (Ej: 10.50)*");
        }
        evaluarDiscrepanciasInteractivas();
    });

    $(document).on('keypress', '.cantidad-input', function(e) {
        validarKeyPressEntero(e);
    });
    $(document).on('keyup', '.cantidad-input', function() {
        limitarDigitos(this, 9);
        validarKeyUpEntero($(this),$(this).siblings('.scantidad-msg'), "*Debe ser un entero mayor a 0*");
        evaluarDiscrepanciasInteractivas();
    });

    $(document).on('change', '.producto-select', function() {
        if ($(this).val()) {$(this).removeClass("is-invalid").addClass("is-valid");
            $(this).siblings('.sproducto-msg').text("");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
            $(this).siblings('.sproducto-msg').text(
                this.dataset.productoDuplicado === 'true'
                    ? "*Este producto ya está seleccionado en otra fila*"
                    : "*Debe seleccionar un producto*"
            );
        }
        evaluarDiscrepanciasInteractivas();
    });

    $("#porcentajeIvaRecepcion").on('keypress', function(e) {
        validarKeyPressDecimal(
            e,
            $("#sporcentajeIva"),
            '*El IVA debe ser numérico y tener hasta 2 decimales*'
        );
    });
    $("#porcentajeIvaRecepcion").on('keyup input', function() {
        if (!consumirIntentoTeclaNumericaInvalida(this)) {
            validarKeyUpIva($(this), $("#sporcentajeIva"), "*El IVA no puede ser mayor a 99%*");
        }
        calcularTotal();
        evaluarDiscrepanciasInteractivas();
    });

    const archivoFactura = document.getElementById('fotoFacturaRecepcion');
    if (archivoFactura) {
        archivoFactura.addEventListener('change', async function() {
            limpiarFacturaIARecepcion();
            const archivo = this.files[0];
            if (!archivo) return;
            if (archivo.size > 5 * 1024 * 1024) {
                this.value = '';
                establecerEstadoIARecepcion('El archivo debe ser menor a 5 MB.', 'error');
                return;
            }
            urlPreviewFacturaRecepcion = URL.createObjectURL(archivo);
            const contenedorPreview = document.getElementById('previewFacturaRecepcion');
            const previewImagen = document.getElementById('previewImagenRecepcion');
            const previewPdf = document.getElementById('previewPdfRecepcion');
            if (contenedorPreview) contenedorPreview.hidden = false;

            if (archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf')) {
                if (previewPdf) { previewPdf.src = urlPreviewFacturaRecepcion; previewPdf.hidden = false; }
            } else if (archivo.type.startsWith('image/')) {
                if (previewImagen) { previewImagen.src = urlPreviewFacturaRecepcion; previewImagen.hidden = false; }
            } else {
                this.value = '';
                limpiarFacturaIARecepcion();
                establecerEstadoIARecepcion('Selecciona una imagen o un archivo PDF.', 'error');
                return;
            }
            await analizarFacturaRecepcion(archivo);
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('recepcionModal')) closeModal();
        if (event.target === document.getElementById('viewModal')) closeViewModal();
        if (event.target === document.getElementById('anularModal')) closeAnularModal();
    });

    actualizarContadorProductos();
});

const aiClient = new AsistenteRecepcionIA({
    proxyUrl: 'Modelo/Controlador/recepcion.php',
    debug: true
});