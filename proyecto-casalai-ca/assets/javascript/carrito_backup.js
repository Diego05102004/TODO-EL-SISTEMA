$(document).ready(function() {
    // Asignar eventos iniciales al cargar la página
    asignarEventosCarrito();

    // Manejar el cambio en el filtro de marcas
    $('#registrar-compra').on('click', function() {
        const datos = [];
        $('.tabladeConsultas tbody tr').each(function() {
            // Evita la fila de total y la fila de "no hay productos"
            if ($(this).find('.cantidad').length === 0) return;

            const cantidadInput = $(this).find('.cantidad');
            const cantidad = parseInt(cantidadInput.val() || 0);
            const idCarritoDetalle = cantidadInput.data('id-carrito-detalle');
            const idProducto = cantidadInput.data('id-producto');
            const nombre = $(this).find('td').eq(1).text().trim();
            const precioText = $(this).find('td').eq(3).text().replace('BS', '').replace(/,/g, '').trim();
            const subtotalText = $(this).find('td').eq(4).text().replace('BS', '').replace(/,/g, '').trim();
            const precio = parseFloat(precioText) || 0;
            const subtotal = parseFloat(subtotalText) || 0;

            if (idProducto && cantidad) {
                datos.push({
                    id_carrito_detalle: idCarritoDetalle,
                    id_producto: idProducto,
                    nombre: nombre,
                    cantidad: cantidad,
                    precio_unitario: precio,
                    subtotal: subtotal
                });
            }
        });

        if (datos.length === 0) {
            Swal.fire('Error', 'No hay productos válidos en el carrito.', 'error');
            return;
        }

        Swal.fire({
            title: '¿Confirmar compra?',
            text: "¡Se registrará la compra con los productos actuales en el carrito!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar compra',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        accion: 'registrar_compra',
                        productos: datos.map(d => d.id_producto),
                        cantidad: datos.map(d => d.cantidad)
                    }
                }).then(response => {
                    return JSON.parse(response);
                }).catch(error => {
                    Swal.showValidationMessage(
                        `Error en la solicitud: ${error}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pedido Registrado!',
                        text: result.value.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '?pagina=gestionarfactura';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.value.message
                    });
                }
            }
        });
    });

    let modalAyudaInstance = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
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
        $.get('assets/public/ayuda/carrito.php')
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

    // Manejar el cambio en el filtro de marcas
    $('#filtroMarca').on('change', function() {
        const idMarca = $(this).val();
        
        $.ajax({
            url: '',
            type: 'POST',
            data: {
                accion: 'filtrar_por_marca',
                id_marca: idMarca
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        $('#tablaProductos').html(data.html);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
        type: 'POST',
        data: { accion: 'obtener_datos_carrito' },
        success: function(response) {
            try {
                const data = typeof response === 'object' ? response : JSON.parse(response);
                if (data.status === 'success') {
                    // Actualizar el cuerpo de la tabla
                    const tbody = document.querySelector('#tabla-carrito tbody');
                    if (tbody) {
                        tbody.innerHTML = data.html;
                    }
                    
                    // Actualizar el total
                    const totalElement = document.querySelector('#total-carrito');
                    if (totalElement) {
                        totalElement.textContent = '$' + data.total.toFixed(2);
                    }
                    
                    // Reasignar eventos a los nuevos elementos
                    asignarEventosCarrito();
                }
            } catch (e) {
                console.error('Error al procesar respuesta:', e);
                // Fallback: recargar la página si hay error
                location.reload();
            }
        },
        error: function() {
            // Fallback: recargar la página si hay error de conexión
            location.reload();
        }
    });
}

// Función para actualizar el contador del carrito en la navbar
function actualizarContadorCarrito() {
    // Usar la función existente de catalogo.js si está disponible
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    } else if (typeof sincronizarCarritoNavbar === 'function') {
        sincronizarCarritoNavbar();
    } else {
        // Fallback: actualizar manualmente
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
                            newBadge.style.display = 'flex';
                            cartBtn.appendChild(newBadge);
                        }
                    }
                } else {
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge) {
                        cartBadge.style.display = 'none';
                    }
                }
            }
        });
    }
}

// Función para reasignar eventos a los elementos dinámicos del carrito
function asignarEventosCarrito() {
    // Reasignar evento a los botones de eliminar
    $('.btn-eliminar').off('click').on('click', function() {
        const idCarritoDetalle = $(this).data('id');
        if (idCarritoDetalle) {
            // Llamar a la función existente de eliminación
            eliminarProductoCarrito(idCarritoDetalle);
        }
    });
    
    // Reasignar evento a los inputs de cantidad
    $('.cantidad-producto').off('change').on('change', function() {
        const idCarritoDetalle = $(this).data('id-carrito-detalle');
        const nuevaCantidad = $(this).val();
        if (idCarritoDetalle && nuevaCantidad) {
            // Llamar a la función existente de actualización
            actualizarCantidadCarrito(idCarritoDetalle, nuevaCantidad);
        }
    });
}

// Funciones wrapper para mantener compatibilidad
function eliminarProductoCarrito(idCarritoDetalle) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    accion: 'eliminar_del_carrito',
                    id_carrito_detalle: idCarritoDetalle
                },
                success: function(response) {
                    response = JSON.parse(response);
                    if (response.status === 'success') {
                        Swal.fire('Eliminado!', response.message, 'success');
                        actualizarTablaCarrito();
                        actualizarContadorCarrito();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

function actualizarCantidadCarrito(idCarritoDetalle, cantidad) {
    $.ajax({
        url: '',
        type: 'POST',
        data: {
            accion: 'actualizar_cantidad',
            id_carrito_detalle: idCarritoDetalle,
            cantidad: cantidad
        },
        success: function(response) {
            response = JSON.parse(response);
            if (response.status === 'success') {
                actualizarTablaCarrito();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

    // Manejar cambio de cantidad en el carrito
    $(document).on('change', '.cantidad', function() {
        const idCarritoDetalle = $(this).data('id-carrito-detalle');
        const cantidad = $(this).val();

        if (cantidad < 1) {
            $(this).val(1);
            return;
        }

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                accion: 'actualizar_cantidad',
                id_carrito_detalle: idCarritoDetalle,
                cantidad: cantidad
            },
            success: function(response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            }
        });
    });

    // Manejar eliminación de producto del carrito
    $(document).on('click', '.btn-eliminar', function() {
        const idCarritoDetalle = $(this).data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminarlo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        accion: 'eliminar_del_carrito',
                        id_carrito_detalle: idCarritoDetalle
                    },
                    success: function(response) {
                        response = JSON.parse(response);
                        if (response.status === 'success') {
                            Swal.fire(
                                'Eliminado!',
                                response.message,
                                'success'
                            ).then(() => {
                                // Actualizar la tabla dinámicamente en lugar de recargar la página
                                actualizarTablaCarrito();
                                actualizarContadorCarrito();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    }
                });
            }
        });
    });

    // Manejar vaciado completo del carrito
    $('#eliminar-todo-carrito').on('click', function() {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡Se eliminarán todos los productos del carrito!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, vaciar carrito',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        accion: 'eliminar_todo_carrito'
                    },
                    success: function(response) {
                        response = JSON.parse(response);
                        if (response.status === 'success') {
                            Swal.fire(
                                'Carrito vaciado!',
                                response.message,
                                'success'
                            ).then(() => {
                                // Actualizar la tabla dinámicamente en lugar de recargar la página
                                actualizarTablaCarrito();
                                actualizarContadorCarrito();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    }
                });
            }
        });
    });

    /*Manejar registro de compra

*/
    // Delegación de eventos para los botones de agregar al carrito
    $(document).on('click', '.btn-agregar-carrito', function() {
        const idProducto = $(this).data('id-producto');
        
        $.ajax({
            url: '',
            type: 'POST',
            data: {
                accion: 'agregar_al_carrito',
                id_producto: idProducto
            },
            success: function(response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message,
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la solicitud AJAX'
                });
            }
        });
    });
