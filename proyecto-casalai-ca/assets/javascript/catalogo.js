// catalogo.js - controlador principal del módulo de catálogo

const valoresValidosCatalogo = new WeakMap();
const entradasNoPermitidasCatalogo = new WeakSet();
const patronesEntradaNoPermitidaCatalogo = [
    /<\s*\/?\s*[a-z!][^>]*>/i,
    /\bjavascript\s*:|\bon[a-z]+\s*=/i,
    /(?:\b(?:union\s+select|select\s+.+\s+from|insert\s+into|update\s+\w+\s+set|delete\s+from|drop\s+(?:table|database))\b|--|\/\*|\*\/|\b(?:or|and)\b\s+['"]?[\w'"]+\s*=\s*['"]?[\w'"]+)/i
];

function esCampoProtegidoCatalogo(elemento) {
    return elemento instanceof HTMLInputElement || elemento instanceof HTMLTextAreaElement;
}

function obtenerErrorEntradaCatalogo(elemento) {
    const errorId = `error_${elemento.id}`;
    return document.getElementById(errorId);
}

document.addEventListener('focusin', function(event) {
    if (esCampoProtegidoCatalogo(event.target) &&
        ['nombre_combo', 'descripcion', 'descuento', 'cantidad_producto', 'searchProduct'].includes(event.target.id)) {
        const valorActual = event.target.value;
        const contenidoNoPermitido = patronesEntradaNoPermitidaCatalogo.some(pattern => pattern.test(valorActual));
        valoresValidosCatalogo.set(event.target, contenidoNoPermitido ? '' : valorActual);
    }
}, true);

document.addEventListener('input', function(event) {
    const campo = event.target;
    if (!esCampoProtegidoCatalogo(campo) ||
        !['nombre_combo', 'descripcion', 'descuento', 'cantidad_producto', 'searchProduct'].includes(campo.id)) {
        return;
    }

    if (patronesEntradaNoPermitidaCatalogo.some(pattern => pattern.test(campo.value))) {
        campo.value = valoresValidosCatalogo.get(campo) || '';
        entradasNoPermitidasCatalogo.add(campo);
        event.preventDefault();
        event.stopImmediatePropagation();

        const error = obtenerErrorEntradaCatalogo(campo);
        if (error) {
            error.textContent = 'El contenido ingresado no está permitido.';
        }
        campo.setAttribute('aria-invalid', 'true');
        campo.setCustomValidity('El contenido ingresado no está permitido.');
        return;
    }

    valoresValidosCatalogo.set(campo, campo.value);
    entradasNoPermitidasCatalogo.delete(campo);
    campo.removeAttribute('aria-invalid');
    campo.setCustomValidity('');
    const error = obtenerErrorEntradaCatalogo(campo);
    if (error) {
        error.textContent = '';
    }
}, true);

$(document).ready(function () {
    
    // Variables globales
    const tasaEl = document.getElementById('tasa');
    let tasa = 0;
    if (tasaEl && typeof tasaEl.getAttribute === 'function') {
        const raw = tasaEl.getAttribute('value');
        tasa = isNaN(parseFloat(raw)) ? 0 : parseFloat(raw);
        // Limpiar chips (delegado)
        $(document).on('click', '.crumb-close', function(){
            const type = $(this).data('type');
            if (type === 'brand') {
                $('#filtroMarca').val('');
                filtrarPorMarca();
            }
            if (type === 'search') {
                $('#searchProduct').val('');
                filtrarYBuscarProductos();
            }
            renderCrumbs();
        });

        // Render inicial
        renderCrumbs();
    }

    let productosSeleccionados = [];
    let currentProductId = null;
    let currentComboId = null;
    let accesosSemanalesChart = null;
    let usuariosActivosChart = null;

    // Inicialización
    init();

    // Función de inicialización
    function init() {
        setupEventListeners();
        checkTabPreference();
        updateCartCount();
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Navegación entre tabs
        $('#productos-tab').on('click', mostrarProductos);
        $('#combos-tab').on('click', mostrarCombos);
        $('#reportes-tab').on('click', mostrarReportes);

        // Filtrado de productos
        $('#filtroMarca').on('change', function(){
            renderCrumbs();
            filtrarPorMarca();
        });
        $('#btnSearch').on('click', function(){
            renderCrumbs();
            buscarProductos();
        });
        $('#searchProduct').on('keyup.catalogoBusqueda', function (e) {
            if (entradasNoPermitidasCatalogo.has(this)) {
                e.preventDefault();
                return;
            }
            // Filtrado inmediato en grid; mantener Enter para fallback
            if ($('.productos-grid').length) {
                filtrarYBuscarProductos();
                renderCrumbs();
            }
            if (e.key === 'Enter') buscarProductos();
        });

        // Carrito
        // Solo productos: excluir botones de combo que comparten la clase
        $(document).on('click', '.btn-agregar-carrito:not(.btn-agregar-combo)', agregarAlCarrito);
        $(document).on('click', '.btn-agregar-combo', agregarComboAlCarrito);

        sincronizarCarritoNavbar();

        // Gestión de combos (admin)
        $('#nuevo_combo').on('click', mostrarModalNuevoCombo);
        $(document).on('click', '.btn-editar-combo', editarCombo);
        $(document).on('click', '.btn-eliminar-combo', eliminarCombo);
        $('#agregar_producto').on('click', agregarProductoACombo);
        $(document).on('click', '.btn-eliminar-producto', eliminarProductoDeCombo);
        $('#nombre_combo, #descripcion, #descuento').on('keypress', validarTeclaCombo);
        $('#nombre_combo, #descripcion, #descuento').on('input keyup blur', function() {
            if (entradasNoPermitidasCatalogo.has(this)) return;
            validarCampoCombo(this.id);
        });
        $('#producto_combo').on('change keyup', function() {
            validarCampoProductoCombo('producto_combo');
        });
        $('#cantidad_producto').on('keypress', validarTeclaCombo);
        $('#cantidad_producto').on('input keyup blur', function() {
            if (entradasNoPermitidasCatalogo.has(this)) return;
            validarCampoProductoCombo('cantidad_producto');
        });
        $('#descuento').on('input change', actualizarTablaProductosCombo);
        $('#guardar_combo').on('click', guardarCombo);
        $('#comboModal').on('hidden.bs.modal', limpiarErroresCombo);

        // Modal de cantidad
        $('#confirmarCantidad').on('click', confirmarCantidad);

    }

    function validarTeclaCombo(event) {
        if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;

        const validadores = {
            nombre_combo: /^[\p{L}\p{N}\s._'-]$/u,
            descripcion: /^[^<>]$/u,
            descuento: /^[0-9.,]$/,
            cantidad_producto: /^[0-9]$/
        };
        const validador = validadores[event.currentTarget.id];
        if (validador && !validador.test(event.key)) {
            event.preventDefault();
        }
    }

    function mostrarErrorCombo(selector, mensaje) {
        const $campo = $(selector);
        const errorSelector = `#error_${$campo.attr('id')}`;
        $(errorSelector).text(mensaje);
        $campo.toggleClass('is-invalid', Boolean(mensaje));
        $campo[0].setCustomValidity(mensaje);
        return !mensaje;
    }

    function validarCampoCombo(campoId) {
        const valor = ($(`#${campoId}`).val() || '').trim();
        let valido = false;
        let mensaje = '';

        if (campoId === 'nombre_combo') {
            valido = /^[\p{L}\p{N}][\p{L}\p{N}\s._'-]{0,99}$/u.test(valor);
            mensaje = 'El nombre es obligatorio y debe tener entre 1 y 100 caracteres válidos.';
        } else if (campoId === 'descripcion') {
            valido = valor.length > 0 && valor.length <= 500 && !/[<>]/.test(valor);
            mensaje = 'La descripción es obligatoria y admite hasta 500 caracteres.';
        } else if (campoId === 'descuento') {
            const descuento = valor.replace(',', '.');
            valido = descuento === '' || (/^\d{1,3}(?:\.\d{0,2})?$/.test(descuento) && Number(descuento) <= 100);
            mensaje = 'Ingresa un porcentaje entre 0 y 100, con hasta dos decimales.';
        }

        mostrarErrorCombo(`#${campoId}`, valido ? '' : mensaje);
        return valido;
    }

    function validarCamposCombo() {
        return ['nombre_combo', 'descripcion', 'descuento']
            .map(validarCampoCombo)
            .every(Boolean);
    }

    function validarCampoProductoCombo(campoId) {
        const productoValido = Boolean($('#producto_combo').val());
        const cantidadTexto = ($('#cantidad_producto').val() || '').trim();
        const cantidadValida = /^[1-9]\d{0,2}$/.test(cantidadTexto);
        const valido = campoId === 'producto_combo' ? productoValido : cantidadValida;
        const mensaje = campoId === 'producto_combo'
            ? 'Selecciona un producto.'
            : 'Ingresa una cantidad entera entre 1 y 999.';

        mostrarErrorCombo(`#${campoId}`, valido ? '' : mensaje);
        return valido;
    }

    function validarCamposProductoCombo() {
        return ['producto_combo', 'cantidad_producto']
            .map(validarCampoProductoCombo)
            .every(Boolean);
    }

    function limpiarErroresCombo() {
        $('#comboForm .combo-validation-error').text('');
        $('#comboForm .is-invalid').removeClass('is-invalid').each(function() {
            this.setCustomValidity('');
        });
        $('#comboForm input, #comboForm textarea').each(function() {
            entradasNoPermitidasCatalogo.delete(this);
            this.removeAttribute('aria-invalid');
        });
    }

    // Verificar preferencia de tab
    function checkTabPreference() {
        const tabPreference = localStorage.getItem('catalogoTab');

        if (tabPreference === 'combos') {
            mostrarCombos();
        } else if (tabPreference === 'reportes' && $('#reportes-tab').length) {
            mostrarReportes();
        } else {
            mostrarProductos();
        }
    }

    // Breadcrumb nuevo: rectángulo blanco + sección actual en burbuja azul
    function renderCrumbs(){
        const $wrap = $('#catalogoCrumbs');
        if ($wrap.length === 0) return;
        const brandVal = ($('#filtroMarca').val() || '').toString();
        const brandText = ($('#filtroMarca option:selected').text() || '').trim();
        const term = ($('#searchProduct').val() || '').trim();

        // Determinar cuál es el "actual" para pintar como burbuja azul
        let current = '';
        if (term) current = 'search'; else if (brandVal) current = 'brand'; else current = 'catalog';

        let html = '<div class="bc-wrap"><div class="bc-list">';
        if (current === 'catalog') {
            html += '<span class="bc-current">Catálogo</span>';
        } else {
            html += '<span class="bc-item"><a href="?pagina=catalogo">Catálogo</a></span>';
        }
        if (brandVal) {
            html += '<span class="bc-sep">/</span>';
            if (current === 'brand') {
                html += `<span class="bc-current">Marca: ${escapeHtml(brandText)}</span>`;
            } else {
                html += `<span class="bc-item"><a href="#" data-type="brand">Marca: ${escapeHtml(brandText)}</a></span>`;
            }
        }
        if (term) {
            html += '<span class="bc-sep">/</span>';
            html += `<span class="bc-current">Búsqueda: ${escapeHtml(term)}</span>`;
        }
        html += '</div></div>';
        $wrap.html(html);

        // Delegar click para limpiar filtros
        $wrap.find('a[data-type="brand"]').on('click', function(e){ e.preventDefault(); $('#filtroMarca').val(''); filtrarPorMarca(); renderCrumbs(); });
        $wrap.find('a[data-type="search"]').on('click', function(e){ e.preventDefault(); $('#searchProduct').val(''); filtrarYBuscarProductos(); renderCrumbs(); });
    }

    function escapeHtml(s){
        return String(s)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    }

    // (revert) sin refreshMarcas

    // Mostrar sección de productos
    function mostrarProductos() {
        $('#productos-content').show();
        $('#combos-content').hide();
        $('#reportes-content').hide();

        $('#productos-tab').addClass('active');
        $('#combos-tab').removeClass('active');
        $('#reportes-tab').removeClass('active');

        localStorage.setItem('catalogoTab', 'productos');
    }

    // Mostrar sección de combos
    function mostrarCombos() {
        $('#productos-content').hide();
        $('#combos-content').show();
        $('#reportes-content').hide();

        $('#productos-tab').removeClass('active');
        $('#combos-tab').addClass('active');
        $('#reportes-tab').removeClass('active');

        localStorage.setItem('catalogoTab', 'combos');
    }

    // Mostrar sección de reportes
    function mostrarReportes() {
        $('#productos-content').hide();
        $('#combos-content').hide();
        $('#reportes-content').show();

        $('#productos-tab').removeClass('active');
        $('#combos-tab').removeClass('active');
        $('#reportes-tab').addClass('active');

        localStorage.setItem('catalogoTab', 'reportes');

        cargarDatosReportes();
    }

    // Filtrar productos por marca (grid primero, tabla como fallback)
    function filtrarPorMarca() {
        if ($('.productos-grid').length) {
            filtrarYBuscarProductos();
            return;
        }

        const idMarca = $(this).val();

        if ($.fn.DataTable && $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable('#tablaProductos')) {
            $('#tablaProductos').DataTable().destroy();
        }

        if (!$('#tablaProductos').length) return; // no tabla presente

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: { accion: 'filtrar_por_marca', id_marca: idMarca },
            beforeSend: function () {
                $('#tablaProductos tbody').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                `);
            },
            success: function (response) {
                try {
                    const data = typeof response === 'object' ? response : JSON.parse(response);
                    if (data.status === 'success') {
                        $('#tablaProductos tbody').html(data.html);
                    } else {
                        $('#tablaProductos tbody').html(data.html || '<tr><td colspan="6" class="text-center py-4">No hay productos</td></tr>');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    $('#tablaProductos tbody').html('<tr><td colspan="6" class="text-center py-4">Error al procesar la respuesta</td></tr>');
                }
                if (typeof inicializarDataTableProductos === 'function') {
                    inicializarDataTableProductos();
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                $('#tablaProductos tbody').html('<tr><td colspan="6" class="text-center py-4">Error de conexión</td></tr>');
                if (typeof inicializarDataTableProductos === 'function') {
                    inicializarDataTableProductos();
                }
            }
        });
    }

    // Buscar productos (grid primero, tabla como fallback)
    function buscarProductos() {
        if ($('.productos-grid').length) {
            filtrarYBuscarProductos();
            return;
        }

        const termino = $('#searchProduct').val().trim();
        if (termino.length < 2) {
            Swal.fire('Búsqueda', 'Ingrese al menos 2 caracteres', 'info');
            return;
        }

        if (!$('#tablaProductos').length) return; // no tabla presente

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: { accion: 'buscar_productos', termino: termino },
            beforeSend: function () {
                $('#tablaProductos').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                `);
            },
            success: function (response) {
                try {
                    const data = typeof response === 'object' ? response : JSON.parse(response);
                    if (data.status === 'success') {
                        $('#tablaProductos').html(data.html);
                    } else {
                        mostrarErrorEnTabla(data.message || 'No se encontraron resultados');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    mostrarErrorEnTabla('Error al procesar la respuesta');
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                mostrarErrorEnTabla('Error de conexión');
            }
        });
    }

    // Filtrado en grid por marca y término de búsqueda
    function filtrarYBuscarProductos() {
        const term = ($('#searchProduct').val() || '').trim().toLowerCase();
        const brandVal = ($('#filtroMarca').val() || '').toString();
        const brandText = ($('#filtroMarca option:selected').text() || '').trim().toLowerCase();

        let visibles = 0;
        $('.producto-card').each(function () {
            const $card = $(this);
            const name = ($card.find('.producto-nombre').text() || '').toLowerCase();
            const serial = ($card.find('.producto-serial').text() || '').toLowerCase();
            const desc = ($card.find('.producto-descripcion').text() || '').toLowerCase();
            const brand = ($card.find('.producto-marca').text() || '').toLowerCase();

            const brandMatch = !brandVal || brand === brandText;
            const termMatch = term.length < 2 || name.includes(term) || serial.includes(term) || desc.includes(term) || brand.includes(term);

            const show = brandMatch && termMatch;
            $card.toggle(show);
            if (show) visibles++;
        });

        // Opcional: manejar estado vacío
        const $grid = $('.productos-grid');
        if ($grid.length) {
            let $empty = $grid.siblings('.empty-state.grid-empty');
            if (visibles === 0) {
                if ($empty.length === 0) {
                    $empty = $('<div class="empty-state grid-empty"><i class="bi bi-exclamation-circle"></i><h4>No hay resultados</h4><p>Ajusta los filtros o la búsqueda</p></div>');
                    $grid.after($empty);
                }
                $empty.show();
            } else {
                $empty.hide();
            }
        }
    }

    function sincronizarCarritoNavbar() {
        $.ajax({
            url: '/proyecto-casalai-ca/Modelo/Controlador/obtener_carrito_count.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.success && data.count > 0) {
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = 'flex';
                    } else {
                        const cartBtn = document.getElementById('cart-btn');
                        if (cartBtn) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'cart-count-badge';
                            newBadge.textContent = data.count;
                            cartBtn.appendChild(newBadge);
                        }
                    }
                } else {
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge) {
                        cartBadge.style.display = 'none';
                    }
                }
            },
            error: function(error) {
                console.error('Error sincronizando carrito:', error);
            }
        });
    }

// Agregar producto al carrito (cards) - cantidad fija 1
function agregarAlCarrito(e) {
    e.preventDefault();
    const button = $(this);
    const idProducto = button.data('id-producto');
    const stockDisponible = parseInt(button.data('stock'));

    if (!idProducto) return;
    if (isNaN(stockDisponible) || stockDisponible <= 0) {
        Swal.fire({ icon: 'error', title: 'Sin stock', text: 'Este producto no tiene stock disponible', timer: 1500, showConfirmButton: false });
        return;
    }

    if (typeof usuarioNoLogueado !== 'undefined' && usuarioNoLogueado) {
        MensajeInicio();
        return;
    }

    confirmarAgregarAlCarrito(idProducto, 1, button);
}

// Agregar combo al carrito (puedes dejarlo igual, ya que combos sí pueden tener varias cantidades)
function agregarComboAlCarrito() {
    const button = $(this);
    const idCombo = button.data('id-combo');

    button.prop('disabled', true);
    button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');

    Swal.fire({
        title: 'Agregando combo',
        html: 'Por favor espere mientras se agregan los productos...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: {
            accion: 'agregar_combo_al_carrito',
            id_combo: idCombo
        },
        success: function (response) {
            try {
                const data = typeof response === 'object' ? response : JSON.parse(response);

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Combo agregado al carrito',
                        footer: `Se agregaron ${data.productos_agregados || 'varios'} productos al carrito`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        updateCartCount();
                        location.reload();
                    });
                    sincronizarCarritoNavbar();
                } else {
                    Swal.fire('Error', data.message || 'Error al agregar combo', 'error');
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                Swal.fire('Error', 'Error al procesar la respuesta', 'error');
            }
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.status, xhr.statusText);
            Swal.fire('Error', 'Error de conexión', 'error');
        },
        complete: function () {
            button.prop('disabled', false);
            button.html('<i class="bi bi-cart-plus"></i> Agregar Combo');
        }
    });
}

// Implementación faltante: confirmar y agregar producto al carrito
function confirmarAgregarAlCarrito(idProducto, cantidad, button) {
    if (!idProducto || !cantidad) return;

    // Inhabilitar botón y mostrar spinner
    const originalHtml = button.html();
    button.prop('disabled', true);
    button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: {
            accion: 'agregar_al_carrito',
            id_producto: idProducto,
            cantidad: cantidad
        },
        success: function (response) {
            try {
                const data = typeof response === 'object' ? response : JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Agregado!',
                        text: data.message || 'Producto agregado al carrito',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    updateCartCount();
                    sincronizarCarritoNavbar();
                } else {
                    Swal.fire('Error', data.message || 'No se pudo agregar al carrito', 'error');
                }
            } catch (e) {
                console.error('Error parsing response:', e, response);
                Swal.fire('Error', 'Error al procesar la respuesta del servidor', 'error');
            }
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.status, xhr.statusText);
            Swal.fire('Error', 'Error de conexión', 'error');
        },
        complete: function () {
            button.prop('disabled', false);
            button.html(originalHtml);
        }
    });
}

// ...resto del código...

// Confirmar cantidad y agregar al carrito
function confirmarCantidad() {
    const cantidad = parseInt($('#productoCantidad').val());
    const stockDisponible = parseInt($('#stockActual').text());

    if (isNaN(cantidad) || cantidad < 1) {
        Swal.fire('Error', 'Ingrese una cantidad válida', 'error');
        return;
    }
    if (cantidad > stockDisponible) {
        Swal.fire('Error', 'No hay suficiente stock disponible', 'error');
        return;
    }

    const button = $('.btn-agregar-carrito[data-id-producto="' + currentProductId + '"]');
    confirmarAgregarAlCarrito(currentProductId, cantidad, button);
    $('#cantidadModal').modal('hide');
}

    // Eliminado: duplicados de confirmarCantidad y confirmarAgregarAlCarrito (mantener versiones superiores con complete)

 

    // Mostrar modal para nuevo combo
    function mostrarModalNuevoCombo() {
        $('#comboModalLabel').text('Crear Nuevo Combo');
        $('#id_combo').val('');
        $('#nombre_combo').val('');
        $('#descripcion').val('');
        $('#descuento').val('0');
        $('#producto_combo').val('');
        $('#cantidad_producto').val('1');
        productosSeleccionados = [];
        limpiarErroresCombo();
        actualizarTablaProductosCombo();
        
        // Establecer contexto de ayuda para registrar
        $('.btn-ayuda-modal').attr('data-contexto', 'registrar');
        $('.btn-ayuda-modal').attr('title', 'Ayuda para Registrar Combo');
        
        $('#comboModal').modal('show');
    }

    // Editar combo existente
function editarCombo() {
    const idCombo = $(this).data('id-combo');
    currentComboId = idCombo;

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: {
            accion: 'obtener_detalles_combo',
            id_combo: idCombo
        },
        dataType: 'json',
        beforeSend: function() {
            $('#comboModal').modal('show');
            $('#comboModalLabel').html('<span class="spinner-border spinner-border-sm" role="status"></span> Cargando combo...');
        },
        success: function(response) {
                if (response.status === 'success') {
                    // Actualizar el formulario con los datos del combo
                    $('#comboModalLabel').text('Editar Combo: ' + response.combo.nombre_combo);
                    $('#id_combo').val(idCombo);
                    $('#nombre_combo').val(response.combo.nombre_combo);
                    $('#descripcion').val(response.combo.descripcion);
                    $('#descuento').val(response.combo.descuento ?? 0);
                    $('#producto_combo').val('');
                    $('#cantidad_producto').val('1');
                    limpiarErroresCombo();

                    // Establecer contexto de ayuda para modificar
                    $('.btn-ayuda-modal').attr('data-contexto', 'modificar');
                    $('.btn-ayuda-modal').attr('title', 'Ayuda para Modificar Combo');

                    // Limpiar productos seleccionados
                    productosSeleccionados = [];

                    // Procesar los detalles del combo
                    if (Array.isArray(response.detalles)) {
                        response.detalles.forEach(item => {
                            productosSeleccionados.push({
                                id: item.id_producto,
                                nombre: item.nombre_producto || `Producto ${item.id_producto}`,
                                cantidad: parseInt(item.cantidad) || 1,
                                precio: parseFloat(item.precio) || 0
                            });
                        });
                    }

                    // Actualizar la tabla de productos
                    actualizarTablaProductosCombo();
                } else {
                    Swal.fire('Error', response.message || 'Error al cargar el combo', 'error');
                    $('#comboModal').modal('hide');
                }
        },
        error: function(xhr, status, error) {
            console.error('Error en la solicitud AJAX:', status, error);
            const mensaje = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'No se pudo cargar el combo. Intente nuevamente.';
            Swal.fire('Error', mensaje, 'error');
            $('#comboModal').modal('hide');
        }
    });
}

    // Cambiar estado de combo (habilitar/inhabilitar)
function cambiarEstadoCombo() {
    console.group('=== DEPURACIÓN: cambiarEstadoCombo ===');
    
    try {
        // 1. Obtener referencias a los elementos del modal
        const $modal = $('#cambioEstadoModal');
        const idCombo = $modal.find('#combo_id_estado').val();
        const $btnConfirmar = $('#confirmarCambioEstado');
        const $accionEstado = $('#accionEstado');
        
        console.log('ID del combo a modificar:', idCombo);
        
        if (!idCombo) {
            throw new Error('No se proporcionó un ID de combo válido');
        }

        // 2. Obtener el botón que disparó el evento
        const $btnEstado = $(`.btn-cambiar-estado[data-id-combo="${idCombo}"]`);
        console.log('Botón de estado encontrado:', $btnEstado.length > 0 ? 'Sí' : 'No');
        
        if ($btnEstado.length === 0) {
            throw new Error('No se pudo encontrar el botón de cambio de estado');
        }

        // 3. Obtener datos actuales
        const nombreCombo = $btnEstado.data('nombre-combo') || 'el combo';
        const estadoActual = $btnEstado.data('estado-actual') === 1;
        const nuevoEstado = !estadoActual;
        
        console.log('Datos del combo:', {
            nombre: nombreCombo,
            estadoActual: estadoActual ? 'Habilitado' : 'Inhabilitado',
            nuevoEstado: nuevoEstado ? 'Habilitado' : 'Inhabilitado'
        });

        // 4. Inhabilitar botón y mostrar indicador de carga
        $btnConfirmar
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...');

        console.log('Enviando solicitud AJAX...');
        
        // 5. Realizar la petición AJAX
        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            dataType: 'json',
            data: {
                accion: 'cambiar_estado_combo',
                id_combo: idCombo,
                nuevo_estado: nuevoEstado ? 1 : 0  // Enviar el nuevo estado al servidor
            },
            context: { 
                btnEstado: $btnEstado,
                btnConfirmar: $btnConfirmar,
                modal: $modal,
                accionEstado: $accionEstado
            },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                
                if (response.status === 'success') {
                    // 6. Actualizar la interfaz
                    const $comboCard = this.btnEstado.closest('.combo-card');
                    const $btnAgregarCombo = $comboCard.find('.btn-agregar-combo');
                    const esActivo = response.nuevo_estado;
                    
                    console.log('Actualizando interfaz con nuevo estado:', 
                               esActivo ? 'Habilitado' : 'Inhabilitado');
                    
                    // Actualizar tarjeta del combo
                    $comboCard.toggleClass('disabled-combo', !esActivo);
                    
                    // Actualizar botón de estado
                    this.btnEstado
                        .removeClass('btn-outline-warning btn-outline-success')
                        .addClass(esActivo ? 'btn-outline-warning' : 'btn-outline-success')
                        .html(`<i class="bi ${esActivo ? 'bi-eye-slash' : 'bi-eye'}"></i> ${esActivo ? 'Inhabilitar' : 'Habilitar'}`)
                        .data('estado-actual', esActivo ? 1 : 0);
                    
                    // Actualizar botón de agregar al carrito
                    if ($btnAgregarCombo.length > 0) {
                        $btnAgregarCombo
                            .prop('disabled', !esActivo)
                            .toggleClass('btn-secondary', !esActivo)
                            .toggleClass('btn-success', esActivo)
                            .html(`<i class="bi bi-cart-plus"></i> ${esActivo ? 'Agregar Combo' : 'Combo no disponible'}`);
                    } else {
                        console.warn('No se encontró el botón de agregar al carrito');
                    }
                    
                    // Mostrar notificación
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || 'Estado del combo actualizado correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    console.log('Interfaz actualizada correctamente');
                } else {
                    throw new Error(response.message || 'Error al actualizar el estado del combo');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                let errorMsg = 'Error al cambiar el estado del combo';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.statusText || errorMsg;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg,
                    confirmButtonText: 'Aceptar'
                });
            },
            complete: function() {
                // Restaurar botón de confirmación
                this.btnConfirmar
                    .prop('disabled', false)
                    .html('Confirmar');
                
                // Cerrar modal
                if (this.modal && typeof this.modal.modal === 'function') {
                    this.modal.modal('hide');
                }
                
                console.log('Proceso de cambio de estado completado');
            }
        });
    } catch (error) {
        console.error('Error en cambiarEstadoCombo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error inesperado',
            confirmButtonText: 'Aceptar'
        });
    } finally {
        console.groupEnd();
    }
}

// Función para mostrar el modal de confirmación
function mostrarModalCambioEstado() {
    const $btn = $(this);
    const idCombo = $btn.data('id-combo');
    const estadoActual = $btn.data('estado-actual');
    const nuevoEstado = !estadoActual;
    
    // Actualizar el modal con los datos del combo
    const $modal = $('#cambioEstadoModal');
    $modal.find('#combo_id_estado').val(idCombo);
    $modal.find('#nuevo_estado').val(nuevoEstado ? 1 : 0);
    
    // Actualizar el texto del modal
    const $accionEstado = $('#accionEstado');
    $accionEstado.text(nuevoEstado ? 'habilitar' : 'inhabilitar');
    
    // Mostrar el modal
    $modal.modal('show');
}

// Asignar manejador de eventos al botón de confirmación
$(document).on('click', '#confirmarCambioEstado', cambiarEstadoCombo);

// Asignar manejador de eventos a los botones de cambio de estado
$(document).on('click', '.btn-cambiar-estado', function(e) {
    e.preventDefault();
    mostrarModalCambioEstado.call(this);
});
    // Agregar producto al combo en edición
function agregarProductoACombo() {
    console.group('=== DEPURACIÓN: agregarProductoACombo ===');

    if (!validarCamposProductoCombo()) {
        console.groupEnd();
        return;
    }
    
    // 1. Obtener valores del formulario - USANDO LOS IDs CORRECTOS DE TU HTML
    const $selectProducto = $('#producto_combo'); // ID que tienes en tu HTML
    const idProducto = $selectProducto.val();
    const productoSeleccionado = $selectProducto.find('option:selected');
    const productoTexto = productoSeleccionado.text().trim();
    const cantidad = Number($('#cantidad_producto').val()); // ID que tienes en tu HTML
    const stock = parseInt(productoSeleccionado.data('stock') || 0);
    
    // OBTENER PRECIO - Buscar en los datos del producto
    const precio = parseFloat(productoSeleccionado.data('precio') || 0);

    console.log('Datos del producto:', { 
        idProducto, 
        productoTexto, 
        cantidad, 
        stock,
        precio,
        selectedOption: productoSeleccionado
    });

    // 2. Validaciones mejoradas
    if (!idProducto || idProducto === '' || idProducto === '0') {
        console.error('Error: No se ha seleccionado ningún producto');
        console.log('Valor del select:', idProducto);
        console.log('Texto seleccionado:', productoTexto);
        
        // Mostrar todas las opciones para debug
        console.log('Opciones disponibles:', $selectProducto.find('option').map((i, o) => ({
            value: $(o).val(),
            text: $(o).text().trim(),
            selected: $(o).is(':selected')
        })).get());
        
        Swal.fire({
            icon: 'error',
            title: 'Producto no seleccionado',
            text: 'Debes seleccionar un producto de la lista desplegable',
            confirmButtonText: 'Entendido'
        });
        console.groupEnd();
        return;
    }

    if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 999) {
        console.error('Error: Cantidad inválida', { cantidad });
        mostrarErrorCombo('#cantidad_producto', 'Ingresa una cantidad entera entre 1 y 999.');
        console.groupEnd();
        return;
    }

    if (cantidad > stock) {
        console.error('Error: Stock insuficiente', { cantidad, stock });
        mostrarErrorCombo('#cantidad_producto', `La cantidad no puede superar el stock disponible (${stock}).`);
        console.groupEnd();
        return;
    }

    // 3. Verificar si el producto ya está en el combo
    const productoExistente = productosSeleccionados.find(p => p.id == idProducto);
    console.log('Producto existente:', productoExistente);
    console.log('Lista actual de productos:', productosSeleccionados);

    if (productoExistente) {
        const nuevaCantidad = productoExistente.cantidad + cantidad;
        console.log('Producto ya existe. Cantidad actual:', productoExistente.cantidad, 'Nueva cantidad a sumar:', cantidad);
        
        if (nuevaCantidad > stock) {
            console.error('Error: Stock insuficiente al actualizar', { 
                cantidadActual: productoExistente.cantidad, 
                cantidadASumar: cantidad, 
                stockDisponible: stock 
            });
            Swal.fire('Error', `La cantidad total (${nuevaCantidad}) no puede superar el stock disponible (${stock})`, 'error');
            console.groupEnd();
            return;
        }
        
        // Actualizar cantidad del producto existente
        console.log(`Actualizando cantidad del producto ${idProducto} de ${productoExistente.cantidad} a ${nuevaCantidad}`);
        productoExistente.cantidad = nuevaCantidad;
        Swal.fire({
            icon: 'success',
            title: 'Cantidad actualizada',
            text: `Se actualizó la cantidad del producto a ${nuevaCantidad}`,
            timer: 1500,
            showConfirmButton: false
        });
    } else {
        // Agregar nuevo producto
        const nuevoProducto = {
            id: idProducto,
            nombre: productoTexto,
            cantidad: cantidad,
            precio: precio,
            stock: stock
        };
        console.log('Agregando nuevo producto:', nuevoProducto);
        productosSeleccionados.push(nuevoProducto);
        
        Swal.fire({
            icon: 'success',
            title: 'Producto agregado',
            text: 'Producto añadido al combo correctamente',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // 4. Actualizar interfaz
    console.log('Productos después de actualizar:', productosSeleccionados);
    actualizarTablaProductosCombo();

    // 5. Resetear controles
    $selectProducto.val('').trigger('change');
    $('#cantidad_producto').val(1);
    mostrarErrorCombo('#producto_combo', '');
    mostrarErrorCombo('#cantidad_producto', '');
    
    console.groupEnd();
}

    // Eliminar producto del combo en edición
    function eliminarProductoDeCombo() {
        const idProducto = $(this).data('id-producto');
        productosSeleccionados = productosSeleccionados.filter(p => p.id != idProducto);
        actualizarTablaProductosCombo();
    }
console.log('productosSeleccionados:', JSON.stringify(productosSeleccionados, null, 2));
actualizarTablaProductosCombo();
    // Actualizar tabla de productos del combo
function actualizarTablaProductosCombo() {
    console.log('Ejecutando actualizarTablaProductosCombo');
    console.log('productosSeleccionados:', productosSeleccionados);
    
    const tbody = $('#productos_combo_table tbody');
    tbody.empty();

    if (!productosSeleccionados || productosSeleccionados.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center py-3 text-muted">No hay productos en este combo</td></tr>');
        return;
    }

    try {
        const descuento = Math.min(100, Math.max(0, parseFloat($('#descuento').val()) || 0));
        // Asegurarse de que los datos tengan el formato correcto
        const productosFormateados = productosSeleccionados.map(item => ({
            id: item.id_producto || item.id,
            nombre: item.nombre_producto || item.nombre || `Producto ${item.id_producto || item.id}`,
            cantidad: parseFloat(item.cantidad) || 1,
            precio: parseFloat(item.precio) || 0
        }));

        console.log('Productos formateados:', productosFormateados);

        // Generar las filas de la tabla
        productosFormateados.forEach(producto => {
            const precioUnitarioFinal = producto.precio * (1 - descuento / 100);
            const totalOriginal = (producto.cantidad * producto.precio).toFixed(2);
            const totalFinal = (producto.cantidad * precioUnitarioFinal).toFixed(2);
            console.log(`Agregando producto: ${producto.nombre}, Cantidad: ${producto.cantidad}, Precio: ${producto.precio}`);
            const row = $('<tr>');
            $('<td>').text(producto.nombre).appendTo(row);
            $('<td>').text(producto.cantidad).appendTo(row);
            const totalCell = $('<td>');
            $('<del>').addClass('text-muted me-2').text(`${totalOriginal} BS`).appendTo(totalCell);
            $('<strong>').text(`${totalFinal} BS`).appendTo(totalCell);
            totalCell.appendTo(row);
            const actionCell = $('<td>').addClass('text-center');
            $('<button>')
                .attr('type', 'button')
                .addClass('btn btn-sm btn-danger btn-eliminar-producto')
                .attr('data-id-producto', producto.id)
                .html('<i class="bi bi-trash"></i>')
                .appendTo(actionCell);
            actionCell.appendTo(row);
            tbody.append(row);
        });
    } catch (e) {
        console.error('Error al actualizar la tabla de productos:', e);
        tbody.html('<tr><td colspan="4" class="text-center text-danger">Error al cargar los productos</td></tr>');
    }
    
    // Forzar actualización del DOM
    $('#productos_combo_table').trigger('update');
}

    function refrescarCombosSinRecargar() {
        return $.ajax({
            url: '?pagina=catalogo&ajax=combo_refresh',
            type: 'GET',
            dataType: 'html',
            cache: false
        }).then(function(html) {
            const documento = new DOMParser().parseFromString(html, 'text/html');
            const nuevoContenido = documento.querySelector('#combos-content');
            const contenidoActual = document.querySelector('#combos-content');

            if (!nuevoContenido || !contenidoActual) {
                throw new Error('No se pudo actualizar la lista de combos');
            }

            nuevoContenido.style.display = 'block';
            contenidoActual.replaceWith(nuevoContenido);
            return true;
        });
    }

    // Guardar combo (crear o actualizar)
    function guardarCombo() {
        const idCombo = $('#id_combo').val();
        const nombreCombo = $('#nombre_combo').val().trim();
        const descripcion = $('#descripcion').val().trim();
        const descuentoRaw = ($('#descuento').val() || '').trim().replace(',', '.');
        const descuento = descuentoRaw === '' ? 0 : Number(descuentoRaw);

        if (!validarCamposCombo()) {
            const primerCampoInvalido = document.querySelector('#comboForm .is-invalid');
            if (primerCampoInvalido) primerCampoInvalido.focus();
            Swal.fire({
                icon: 'warning',
                title: 'Campos obligatorios',
                text: 'Debes llenar los campos obligatorios marcados con * antes de guardar el combo.',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        if (productosSeleccionados.length === 0) {
            mostrarErrorCombo('#producto_combo', 'Debes agregar al menos un producto al combo.');
            $('#producto_combo').trigger('focus');
            Swal.fire('Campos obligatorios', 'Debes agregar al menos un producto al combo.', 'warning');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...');

        const accion = idCombo ? 'actualizar_combo' : 'crear_combo';

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: {
                accion: accion,
                id_combo: idCombo,
                nombre_combo: nombreCombo,
                descripcion: descripcion,
                descuento: descuento,
                productos: productosSeleccionados
            },
            dataType: 'json',
            success: function (response) {
                if (response.status !== 'success') {
                    Swal.fire('Error', response.message || 'No se pudo guardar el combo', 'error');
                    return;
                }

                $('#comboModal').modal('hide');
                refrescarCombosSinRecargar()
                    .done(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    })
                    .fail(function(error) {
                        console.error('No se pudo refrescar la lista de combos:', error);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Combo guardado',
                            text: 'El cambio se guardó, pero no se pudo actualizar la lista. Recarga la página para ver los datos más recientes.'
                        });
                    });
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                let mensaje = 'Error al guardar el combo';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }
                Swal.fire('Error', mensaje, 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Combo');
            }
        });
    }

    // Cargar datos para reportes
    function cargarDatosReportes() {
    // Mostrar spinner
    $('#datosEstadisticas').html(`
        <tr>
            <td colspan="4" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </td>
        </tr>
    `);

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: { accion: 'obtener_datos_reportes' },
        dataType: 'json',
        success: function(data) {
            if (data.status === 'success') {
                if (data.estadisticas) {
                    crearGraficoAccesos(data.estadisticas);
                    actualizarTablaEstadisticas(data.estadisticas);
                }
                if (data.usuarios) {
                    crearGraficoUsuarios(data.usuarios);
                }
            } else {
                mostrarErrorEnTabla(data.message || 'Error al cargar datos');
            }
        },
        error: function(xhr) {
            console.error('Error al cargar datos para reportes:', xhr.status, xhr.statusText);
            mostrarErrorEnTabla('Error de conexión al cargar reportes');
        }
    });
}

    // Crear gráfico de accesos semanales
    function crearGraficoAccesos(datos) {
        if (accesosSemanalesChart) {
            accesosSemanalesChart.destroy();
        }

        const semanas = datos.semanas.map(item => 'Sem ' + item.semana.toString().substring(4));
        const accesos = datos.semanas.map(item => item.total_accesos);
        const usuariosUnicos = datos.semanas.map(item => item.usuarios_unicos);

        const ctx = document.getElementById('accesosSemanalesChart').getContext('2d');
        accesosSemanalesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: semanas,
                datasets: [
                    {
                        label: 'Total Accesos',
                        data: accesos,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Usuarios Únicos',
                        data: usuariosUnicos,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Accesos al Catálogo por Semana',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Accesos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Semanas'
                        }
                    }
                }
            }
        });
    }

    function verificarTodo() {
    console.log('=== VERIFICACIÓN COMPLETA ===');
    console.log('1. Elemento tabla:', $('#productos_combo_table').length);
    console.log('2. Array productosSeleccionados:', window.productosSeleccionados);
    console.log('3. HTML de la tabla:', $('#productos_combo_table').html());
    console.log('4. Botón agregar_producto:', $('#agregar_producto').length);
    
    // Verificar si la función existe
    console.log('5. Función actualizarTablaProductosCombo:', typeof actualizarTablaProductosCombo);
}

verificarTodo();
    // Crear gráfico de usuarios más activos
    function crearGraficoUsuarios(usuarios) {
        if (usuariosActivosChart) {
            usuariosActivosChart.destroy();
        }

        const nombres = usuarios.map(user => user.username);
        const accesos = usuarios.map(user => user.total_accesos);
        const porcentajes = usuarios.map(user => user.porcentaje);

        const ctx = document.getElementById('usuariosActivosChart').getContext('2d');
        usuariosActivosChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: nombres,
                datasets: [{
                    label: 'Total de Accesos',
                    data: accesos,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Usuarios con Más Accesos',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const porcentaje = porcentajes[context.dataIndex];
                                return [
                                    `Accesos: ${context.raw}`,
                                    `Participación: ${porcentaje}%`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Accesos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Usuarios'
                        }
                    }
                }
            }
        });
    }

    // Actualizar tabla de estadísticas
    function actualizarTablaEstadisticas(datos) {
    const tbody = $('#datosEstadisticas');
    tbody.empty();

    // Resumen general
    tbody.append(`
        <tr class="table-primary">
            <td colspan="2"><strong>Total General</strong></td>
            <td><strong>${numberFormat(datos.total)}</strong></td>
            <td><strong>${numberFormat(datos.unicos)}</strong></td>
        </tr>
    `);

    // Detalle por semana
    datos.semanas.forEach(item => {
        const semana = item.semana.toString();
        const semanaFormateada = semana.substring(0, 4) + '-S' + semana.substring(4);
        
        tbody.append(`
            <tr>
                <td>${semanaFormateada}</td>
                <td>${numberFormat(item.promedio_diario, 1)}</td>
                <td>${numberFormat(item.total_accesos)}</td>
                <td>${numberFormat(item.usuarios_unicos)}</td>
            </tr>
        `);
    });
}

    // Actualizar contador del carrito usando WebSocket
    function updateCartCount() {
        // Si tenemos WebSocket disponible, lo usamos
        if (window.notificacionesWS) {
            // Enviamos una solicitud para actualizar el contador
            window.notificacionesWS.enviar({ 
                tipo: 'obtener_carrito_count' 
            });
        } else {
            // Fallback a AJAX si WebSocket no está disponible
            $.ajax({
                url: '?pagina=carrito',
                type: 'POST',
                data: {accion: 'obtener_cantidad_carrito'},
                dataType: 'json',
                success: function (data) {
                    actualizarContadorCarritoUI(data.cantidad || 0);
                },
                error: function (xhr) {
                    console.error('Error al obtener cantidad del carrito:', xhr.status, xhr.statusText);
                }
            });
        }
    }
    
    // Función para actualizar la UI del contador del carrito
    function actualizarContadorCarritoUI(cantidad) {
        const cartCountElement = $('.cart-count-badge');
        if (cantidad > 0) {
            cartCountElement.text(cantidad).show();
        } else {
            cartCountElement.hide();
        }
    }

    function numberFormat(num, decimals = 0) {
    return num.toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

    // Mostrar error en tabla de productos
    function mostrarErrorEnTabla(mensaje) {
        $('#tablaProductos').html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="bi bi-exclamation-triangle"></i> ${mensaje}
                </td>
            </tr>
        `);
    }

    // Inicializar DataTable para la tabla de productos y el buscador personalizado
    function inicializarDataTableProductos() {
        // Si no existe la tabla, no inicializar DataTable ni re-vincular el buscador
        if (!$('#tablaProductos').length || !$.fn.DataTable) {
            return;
        }

        const tablaProductosDT = $('#tablaProductos').DataTable({
            language: {
                url: 'assets/public/js/es-ES.json'
            },
            responsive: true,
            columnDefs: [
                { orderable: false, targets: 0 }
            ],
            dom: 'rtip'
        });

        // Sincronizar el input de búsqueda personalizado con el DataTable
        $('#searchProduct').off('keyup.catalogoTabla').on('keyup.catalogoTabla', function () {
            tablaProductosDT.search(this.value).draw();
        });
    }

    // Inicialización al cargar la página
    inicializarDataTableProductos();
})
    let modalAyudaInstance = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
        // Si ya existe una instancia, solo abrir y navegar
        if (modalAyudaInstance) {
            modalAyudaInstance.openModal(contexto);

            if (contexto) {
                setTimeout(() => {
                    const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                    if (slideIndex !== undefined) {
                        modalAyudaInstance.goToSlide(slideIndex);
                    }
                }, 300);
            }
            return;
        }

        // Cargar CSS si no está cargado
        if (!$('link[href*="ayuda/css/modal.css"]').length) {
            $('<link>')
                .attr({
                    'rel': 'stylesheet',
                    'type': 'text/css',
                    'href': 'assets/public/ayuda/css/modal.css'
                })
                .appendTo('head');
        }

        // Cargar HTML del modal
        $.get('assets/public/ayuda/catalogo.php')
            .done(function(html) {
                // Solo agregar modal si no existe
                if (!$('#modalAyuda').length) {
                    $('body').append(html);
                }

                // Cargar JS del modal si no está cargado
                if (!$('script[src*="ayuda/js/modal.js"]').length) {
                    $.getScript('assets/public/ayuda/js/modal.js')
                        .done(function() {
                            // Inicializar modal
                            if (typeof inicializarModalAyudaUsuario === 'function') {
                                modalAyudaInstance = inicializarModalAyudaUsuario();

                                // Asegurar restauración del scroll (por si algún cierre previo falló)
                                document.body.style.overflow = '';

                                // Abrir modal con contexto si se proporciona
                                if (contexto) {
                                    setTimeout(() => {
                                        const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                        if (slideIndex !== undefined) {
                                            modalAyudaInstance.goToSlide(slideIndex);
                                        }
                                    }, 300);
                                }

                                // Abrir modal
                                modalAyudaInstance.openModal();
                            } else {
                                console.error('La función inicializarModalAyudaUsuario no está disponible');
                            }
                        })
                        .fail(function() {
                            console.error('Error al cargar el JavaScript del modal de ayuda');
                        });
                } else {
                    // Si el JS ya está cargado, solo abrir el modal existente
                    if (typeof inicializarModalAyudaUsuario === 'function') {
                        modalAyudaInstance = inicializarModalAyudaUsuario();

                        // Asegurar restauración del scroll (por si algún cierre previo falló)
                        document.body.style.overflow = '';

                        // Abrir modal con contexto si se proporciona
                        if (contexto) {
                            setTimeout(() => {
                                const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    modalAyudaInstance.goToSlide(slideIndex);
                                }
                            }, 300);
                        }

                        // Abrir modal
                        modalAyudaInstance.openModal();
                    }
                }
            })
            .fail(function() {
                console.error('Error al cargar el HTML del modal');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el contenido de ayuda'
                });
            });
    }

    // Botón de ayuda principal
    $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda detectado');
        cargarYMostrarModalAyuda(); // Sin contexto específico
    });

    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        
        // Obtener el contexto actual del botón en tiempo real
        const contextoActual = this.getAttribute('data-contexto');
        console.log('Clic en botón de ayuda modal con contexto actual:', contextoActual);
        
        // Verificar que el contexto sea válido antes de abrir el modal
        if (contextoActual && (contextoActual === 'registrar' || contextoActual === 'modificar')) {
            cargarYMostrarModalAyuda(contextoActual);
        } else {
            console.warn('Contexto no válido o no encontrado:', contextoActual);
            cargarYMostrarModalAyuda(); // Abrir sin contexto específico
        }
    });

// Función para eliminar combo
function eliminarCombo() {
    const $btn = $(this);
    const idCombo = $btn.data('id-combo');
    const $comboCard = $btn.closest('.combo-card');
    const nombreCombo = $comboCard.find('.combo-nombre').text() || 'este combo';

    Swal.fire({
        title: '¿Eliminar Combo?',
        html: `¿Estás seguro de que deseas eliminar el combo <strong>${nombreCombo}</strong>?<br><small class="text-muted">Esta acción cambiará el estado del combo a "eliminado" (estado 2).</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading en el botón
            $btn.prop('disabled', true)
               .html('<span class="spinner-border spinner-border-sm" role="status"></span> Eliminando...');

            $.ajax({
                url: '?pagina=catalogo',
                type: 'POST',
                data: {
                    accion: 'eliminar_combo',
                    id_combo: idCombo
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Ocultar la tarjeta del combo con una animación
                        $comboCard.fadeOut(500, function() {
                            $(this).remove();
                            
                            // Verificar si quedan combos
                            const $combosGrid = $('.combos-grid');
                            if ($combosGrid.find('.combo-card').length === 0) {
                                $combosGrid.html(`
                                    <div class="empty-state">
                                        <i class="bi bi-info-circle"></i>
                                        <h4>No hay combos disponibles</h4>
                                        <p>En este momento no tenemos combos promocionales disponibles.</p>
                                    </div>
                                `);
                            }
                        });

                        // Mostrar SweetAlert de éxito
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado!',
                            text: 'El combo ha sido eliminado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // Restaurar botón
                        $btn.prop('disabled', false)
                           .html('<i class="bi bi-trash"></i> Eliminar');

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'No se pudo eliminar el combo',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                },
                error: function(xhr) {
                    // Restaurar botón
                    $btn.prop('disabled', false)
                       .html('<i class="bi bi-trash"></i> Eliminar');

                    console.error('Error al eliminar combo:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        }
    });
}