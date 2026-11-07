// Prevenir el retroceso a la pasarela de pagos
if (window.history && window.history.pushState) {
    window.history.pushState('forward', null, '');
    window.onpopstate = function() {
        window.location.href = 'gestionarfactura.php';
    };
}

$(document).ready(function () {
    console.log('jQuery cargado, inicializando modal de ayuda...');
    
    let modalAyudaInstance = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
        console.log('cargarYMostrarModalAyuda llamado con contexto:', contexto);
        
        // Cargar CSS si no está cargado
        if (!$('link[href*="ayuda/css/modal.css"]').length) {
            console.log('Cargando CSS del modal...');
            $('<link>')
                .attr({
                    'rel': 'stylesheet',
                    'type': 'text/css',
                    'href': 'assets/public/ayuda/css/modal.css'
                })
                .appendTo('head');
        }

        // Cargar HTML del modal
        $.get('assets/public/ayuda/pagos.php')
            .done(function(html) {
                console.log('HTML del modal cargado');
                
                // Solo agregar modal si no existe
                if (!$('#modalAyuda').length) {
                    $('body').append(html);
                    console.log('Modal agregado al DOM');
                }

                // Cargar JS del modal si no está cargado
                if (!$('script[src*="ayuda/js/modal.js"]').length) {
                    console.log('Cargando JavaScript del modal...');
                    $.getScript('assets/public/ayuda/js/modal.js')
                        .done(function() {
                            console.log('JavaScript del modal cargado');
                            inicializarModalConContexto(contexto);
                        })
                        .fail(function() {
                            console.error('Error al cargar el JavaScript del modal de ayuda');
                        });
                } else {
                    console.log('JavaScript del modal ya estaba cargado');
                    inicializarModalConContexto(contexto);
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

    function inicializarModalConContexto(contexto) {
        // Inicializar modal
        if (typeof inicializarModalAyudaUsuario === 'function') {
            modalAyudaInstance = inicializarModalAyudaUsuario();
            console.log('Modal inicializado:', modalAyudaInstance);
            console.log('Mapeo de contextos disponible:', modalAyudaInstance.mapeoContextos);

            // Abrir modal con contexto si se proporciona
            if (contexto) {
                setTimeout(() => {
                    const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                    console.log('Contexto:', contexto, '-> Slide:', slideIndex);
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
    }

    // Botón de ayuda principal - abrir según el rol
    $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda principal detectado');
        
        // Determinar contexto según el rol
        const esCliente = '<?php echo isset($_SESSION["nombre_rol"]) && $_SESSION["nombre_rol"] == "Cliente" ? "true" : "false"; ?>';
        const contexto = esCliente === 'true' ? 'consultar' : 'modificar';
        cargarYMostrarModalAyuda(contexto);
    });

    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal con contexto:', contexto);
        cargarYMostrarModalAyuda(contexto);
    });

// Abrir modal y llenar campos
$('#modificarPago').length

// Evita recarga al abrir el menú de acciones
$(document).on('click', '.acciones-boton .vertical', function(e) {
    e.preventDefault();
    $('.desplegable').not($(this).siblings('.desplegable')).hide();
    $(this).siblings('.desplegable').toggle();
});

// Evita recarga en cualquier acción del menú
$(document).on('click', '.acciones-boton a', function(e) {
    e.preventDefault();
});

$(document).on('click', '.modificar', function() {
    console.log("Click en botón modificar");
    var boton = $(this);

    $('#modificarIdDetalles').val(boton.data('id'));
    $('#modificarCuenta').val(boton.data('cuenta'));
    $('#modificarReferencia').val(boton.data('referencia'));
    $('#modificarFecha').val(boton.data('fecha'));

    $('#modificarPago').modal('show');
});
$(document).on('click', '.modificar', function() {
    var boton = $(this);

    $('#modificarIdDetalles').val(boton.data('id'));
    $('#modificarCuenta').val(boton.data('cuenta'));
    $('#modificarReferencia').val(boton.data('referencia'));
    $('#modificarFecha').val(boton.data('fecha'));
    $('#modificarTipo').val(boton.data('tipo')); // <--- ¡AÑADE ESTO!
    $('#modificarFactura').val(boton.data('factura')); // <--- ¡AÑADE ESTO!

    $('#modificarPago').modal('show');
});
// Enviar datos por AJAX
// Modificar pago
$('#modificarPagoForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('accion', 'modificar');
    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        success: function(response) {
            try {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    $('#modificarPago').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'El pago se ha modificado correctamente'
                    });
                    if (response.pago) {
                        actualizarFilaPago(response.pago);
                    }
                } else {
                    muestraMensaje(response.message);
                }
            } catch (e) {
                console.error('Error en la respuesta JSON', e);
                muestraMensaje('Error en la respuesta del servidor.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error al modificar el pago:', textStatus, errorThrown);
            muestraMensaje('Error al modificar el pago.');
        }
    });
});

// Cambiar estatus
$('#formModificarEstado').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('accion', 'modificar_estado');
    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        success: function(response) {
            try {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    $('#modificarEstadoModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'El estado del pago se ha modificado correctamente'
                    });
                    if (response.pago) {
                        actualizarFilaPago(response.pago);
                    }
                } else {
                    muestraMensaje(response.message);
                }
            } catch (e) {
                console.error('Error en la respuesta JSON', e);
                muestraMensaje('Error en la respuesta del servidor.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error al modificar el pago:', textStatus, errorThrown);
            muestraMensaje('Error al modificar el pago.');
        }
    });
});
    

    $('#registrarPagoForm').on('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    formData.append('accion', 'ingresar');

    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            try {
                const data = JSON.parse(response);

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pagos registrados',
                        text: data.message || 'Todos los pagos se registraron correctamente',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } 
                else if (data.status === 'partial') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Registro parcial',
                        html: `
                            <p>${data.message}</p>
                            <ul style="text-align:left;">
                                ${data.errores.map(e => `<li>${e}</li>`).join('')}
                            </ul>
                        `,
                        confirmButtonText: 'Aceptar'
                    });
                } 
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudieron registrar los pagos',
                        confirmButtonText: 'Aceptar'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error procesando la respuesta del servidor',
                    confirmButtonText: 'Aceptar'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la solicitud AJAX: ' + error,
                confirmButtonText: 'Aceptar'
            });
        }
    });
});

  
    $(document).on('click', '.btn-eliminar', function (e) {
        e.preventDefault(); 
        Swal.fire({
            title: '¿Está seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminarlo!'
        }).then((result) => {
            if (result.isConfirmed) {
                var id = $(this).data('id');
                console.log("ID del producto a eliminar: ", id); 
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id', id);
                enviarAjax(datos, function (respuesta) {
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminado!',
                            'El producto ha sido eliminado.',
                            'success'
                        ).then(function() {
                            location.reload(); 
                        });
                    } else {
                        muestraMensaje(respuesta.message);
                    }
                });
            }
        });
    });

function eliminarPago(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminarlo!'
    }).then((result) => {
        if (result.isConfirmed) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviarAjax(datos, function (respuesta) {
                if (respuesta.status === 'success') {
                    Swal.fire(
                        'Eliminado!',
                        'El producto ha sido eliminado.',
                        'success'
                    ).then(function() {
                        location.reload();
                    });
                } else {
                    muestraMensaje(respuesta.message);
                }
            });
        }
    });
}
$(document).on('click', '.modificarEstado', function (e) {
  e.preventDefault();

  const idPago = $(this).data('id');
  const idFactura = $(this).data('factura');
  const estatus = $(this).data('estatus');
  const observaciones = $(this).data('observaciones');

  $('#estadoIdPago').val(idPago);
  $('#modificarIdFactura').val(idFactura);
  $('#estatus').val(estatus);
  $('#observaciones').val(observaciones);

  $('#modificarEstadoModal').modal('show');
});

function actualizarFilaPago(pago) {
    const tabla = $('#tablaConsultas').DataTable();

    const fila = tabla.rows().nodes().to$().filter(function () {
        return $(this).data('id') == pago.id_detalles;
    });

    if (fila.length > 0) {
        // Definir las rutas igual que en PHP
        const rutaPrincipal = "comprobantes/" + pago.comprobante;
        const rutaAlternativa = pago.comprobante;
        const rutaFallback = "assets/img/no-disponible.png";

        // Función para construir el HTML de la imagen
        const construirImagen = (srcFinal) => `
            <img src="${srcFinal}" 
                 alt="Comprobante" 
                 class="img-comprobante"
                 style="max-width:80px;max-height:80px;border-radius:6px;border:1px solid #ccc;
                        padding:3px;object-fit:contain;background:#fff;cursor:zoom-in;"
                 data-src="${srcFinal}"
                 data-factura="${pago.id_factura}"
                 data-referencia="${pago.referencia}">
        `;

        // Promesa para validar la carga de la imagen
        const validarRuta = (src) => {
            return new Promise((resolve) => {
                if (!src || src === 'null' || src.trim() === '') {
                    return resolve(null);
                }
                const img = new Image();
                img.onload = () => resolve(src);
                img.onerror = () => resolve(null);
                img.src = src;
            });
        };

        // Verificar rutas en orden
        Promise.resolve()
            .then(() => validarRuta(rutaPrincipal))
            .then((src) => src || validarRuta(rutaAlternativa))
            .then((src) => {
                const comprobanteHTML = construirImagen(src || rutaFallback);

                // Actualizar la fila
                tabla.row(fila).data([
                    pago.id_factura,
                    pago.tbl_cuentas,
                    pago.tipo,
                    pago.referencia,
                    pago.fecha,
                    `<span class="campo-rango" 
                           data-estatus="${pago.estatus}" 
                           style="cursor: pointer;">
                        ${pago.estatus}
                     </span>`,
                    comprobanteHTML,
                    pago.estatus === 'Pago Procesado' 
                        ? '<span class="text-success">Pago procesado. No es posible cambiar estatus.</span>'
                        : `<button class="btn btn-primary modificarEstado" 
                                 data-id="${pago.id_detalles}"
                                 data-factura="${pago.id_factura}"
                                 data-estatus="${pago.estatus}"
                                 data-observaciones="${pago.observaciones}">
                            Cambiar Estatus
                        </button>`
                ]).draw(false);

                // Reasignar evento de abrir modal
                document.querySelectorAll('.img-comprobante').forEach(img => {
                    img.addEventListener('click', function (e) {
                        e.preventDefault();
                        const src = this.getAttribute('data-src');
                        const factura = this.getAttribute('data-factura');
                        const referencia = this.getAttribute('data-referencia');

                        if (src && src !== 'assets/img/no-disponible.png') {
                            mostrarComprobanteModal(src, factura, referencia);
                        }
                    });
                });
            });

    } else {
        console.warn('No se encontró la fila para el pago con ID:', pago.id_detalles);
    }
}
   
    $('#incluirProductoForm').on('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Éxito',
                            text: 'Producto ingresado exitosamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Error al ingresar el producto',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al procesar la respuesta del servidor',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'Error',
                    text: 'Error en la solicitud AJAX: ' + error,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    let modalAyudaInstancePagos = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyudaPagos(contexto = null) {
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
        $.get('assets/public/ayuda/pagos.php')
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
                                modalAyudaInstancePagos = inicializarModalAyudaUsuario();

                                // Abrir modal con contexto si se proporciona
                                if (contexto) {
                                    setTimeout(() => {
                                        const slideIndex = modalAyudaInstancePagos.mapeoContextos[contexto];
                                        if (slideIndex !== undefined) {
                                            modalAyudaInstancePagos.goToSlide(slideIndex);
                                        }
                                    }, 300);
                                }

                                // Abrir modal
                                modalAyudaInstancePagos.openModal();
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
                        modalAyudaInstancePagos = inicializarModalAyudaUsuario();

                        // Abrir modal con contexto si se proporciona
                        if (contexto) {
                            setTimeout(() => {
                                const slideIndex = modalAyudaInstancePagos.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    modalAyudaInstancePagos.goToSlide(slideIndex);
                                }
                            }, 300);
                        }

                        // Abrir modal
                        modalAyudaInstancePagos.openModal();
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
        
        // Determinar contexto según el rol
        const esCliente = '<?php echo isset($_SESSION["nombre_rol"]) && $_SESSION["nombre_rol"] == "Cliente" ? "true" : "false"; ?>';
        const contexto = esCliente === 'true' ? 'consultar' : 'modificar';
        cargarYMostrarModalAyudaPagos(contexto);
    });

    function enviarAjax(datos, callback) {
        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            contentType: false,
            processData: false,
            cache: false,
            success: function (respuesta) {
                if (typeof respuesta === "string") {
                    respuesta = JSON.parse(respuesta);
                }
                if(callback) callback(respuesta);
            },
            error: function () {
                Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
            }
        });
    }
});

function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}
