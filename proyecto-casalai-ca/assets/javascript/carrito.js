$(document).ready(function() {
    // Manejar registro de compra
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
                        accion: 'registrar_compra'
                    },
                    dataType: 'json' // Esperamos JSON pero manejaremos errores
                }).then(response => {
                    // Si llegamos aquí, jQuery ya parseó JSON correctamente
                    return response;
                }).catch(error => {
                    console.error('Error en AJAX:', error);
                    console.error('Respuesta del servidor:', error.responseText);
                    
                    let errorMessage = 'Error en la solicitud';
                    
                    // Si hay respuesta de texto, mostrarla
                    if (error.responseText) {
                        // Si parece HTML, extraer solo el texto
                        if (error.responseText.includes('<')) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = error.responseText;
                            errorMessage = tempDiv.textContent || tempDiv.innerText || 'Error del servidor';
                        } else {
                            errorMessage = error.responseText;
                        }
                    } else if (error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                    } else if (error.statusText) {
                        errorMessage = `Error: ${error.statusText}`;
                    } else if (typeof error === 'string') {
                        errorMessage = error;
                    }
                    
                    Swal.showValidationMessage(errorMessage);
                    throw error; // Re-lanzar el error para que SweetAlert lo maneje
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

    // Manejar eliminación de todo el carrito
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
                        try {
                            // Si response ya es un objeto, usarlo directamente, sino parsearlo
                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.status === 'success') {
                                Swal.fire(
                                    'Carrito vaciado!',
                                    data.message,
                                    'success'
                                ).then(() => {
                                    // Recargar la página completamente
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message
                                });
                            }
                        } catch (e) {
                            console.error('Error procesando respuesta:', e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al procesar la respuesta del servidor'
                            });
                        }
                    }
                });
            }
        });
    });

    // Manejar cambio de cantidad en el carrito
    $(document).on('change', '.cantidad', function() {
        const idCarritoDetalle = $(this).data('id-carrito-detalle');
        const cantidadCombos = parseInt($(this).val(), 10);
        const unidadesPorCombo = parseInt($(this).data('units-per-combo'), 10) || 1;
        const cantidad = cantidadCombos * unidadesPorCombo;

        if (cantidadCombos < 1) {
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
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.status === 'success') {
                        // Recargar la página completamente
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (e) {
                    console.error('Error procesando respuesta:', e, response);
                    Swal.fire('Error', 'Error al procesar la respuesta del servidor', 'error');
                }
            }
        });
    });

    // Manejar eliminación de producto del carrito
    $(document).on('click', '.btn-eliminar', function() {
        const idCarritoDetalle = $(this).data('id');
        const esCombo = String($(this).data('es-combo')) === '1';

        Swal.fire({
            title: esCombo ? '¿Eliminar el combo completo?' : '¿Estás seguro?',
            text: esCombo
                ? 'Se eliminarán todos los productos que pertenecen a este combo.'
                : '¡No podrás revertir esta acción!',
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
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.status === 'success') {
                            Swal.fire(
                                'Eliminado!',
                                data.message,
                                'success'
                            ).then(() => {
                                // Recargar la página completamente
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    } catch (e) {
                        console.error('Error procesando respuesta:', e, response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al procesar la respuesta del servidor'
                        });
                    }
                }
                });
            }
        });
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
                    title: 'Error',
                    text: 'Error en la solicitud AJAX: ' + error
                });
            }
        });
    });

    // Botón de ayuda principal
    $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda detectado');
        // cargarYMostrarModalAyuda(); // Sin contexto específico
    });
});
