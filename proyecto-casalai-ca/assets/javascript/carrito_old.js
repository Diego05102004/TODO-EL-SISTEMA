$(document).ready(function() {
    // Asignar eventos iniciales al cargar la página
    asignarEventosCarrito();

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
                        response = JSON.parse(response);
                        if (response.status === 'success') {
                            Swal.fire(
                                'Carrito vaciado!',
                                response.message,
                                'success'
                            ).then(() => {
                                // Recargar la página completamente
                                location.reload();
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
                    // Recargar la página completamente
                    location.reload();
                } else {
                    Swal.fire('Error', response.message, 'error');
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
                                // Recargar la página completamente
                                location.reload();
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

// ...

// Función para cargar y mostrar el modal de ayuda con contexto específico
// function cargarYMostrarModalAyuda(contexto = null) {
//     // ...
// }

// ...
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
                    const modalAyudaInstance = inicializarModalAyudaUsuario();

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
