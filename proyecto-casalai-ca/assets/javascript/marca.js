$(document).ready(function () {
var esSuperUsuario = false;
if (typeof window !== "undefined" && window.sessionStorage) {
    esSuperUsuario = sessionStorage.getItem('nombre_rol') === 'SuperUsuario';
}
if (typeof nombre_rol !== "undefined" && nombre_rol === 'SuperUsuario') {
    esSuperUsuario = true;
}

    // Función para manejar errores específicos del backend
    function manejarErroresBackend(respuesta, esModificacion = false) {
        if (respuesta.field_errors && typeof respuesta.field_errors === 'object') {
            // Limpiar todos los spans de error primero
            if (esModificacion) {
                $("#smnombre_marca").text('');
            } else {
                $("#snombre_marca").text('');
            }

            // Mapeo de campos del backend a spans del frontend
            const mapeoCampos = esModificacion ? {
                'nombre_marca': '#smnombre_marca',
                'id_marca': '#smid_marca'
            } : {
                'nombre_marca': '#snombre_marca',
                'id_marca': '#sid_marca'
            };

            // Mostrar errores específicos en sus spans correspondientes
            Object.keys(respuesta.field_errors).forEach(campo => {
                const selector = mapeoCampos[campo];
                if (selector) {
                    $(selector).text(respuesta.field_errors[campo]);
                }
            });

            return true; // Indica que se manejaron errores específicos
        }
        return false; // No hay errores específicos que manejar
    }

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    // Inicializar DataTable de marcas y crear dinámicamente el botón Incluir en el filtro
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaMarcas;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaMarcas = $tabla.DataTable({
                language: {
                    "url": "assets/public/js/es-ES.json"
                },
                scrollX: true,
                scrollCollapse: true,
                order: [[0, 'desc']],
                initComplete: function () {
                    var api = this.api();
                    var $wrapper = $(api.table().container());

                    api.columns.adjust();

                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;

                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirMarca').length) return;

                    // Estilos flex para alinear label + buscador + botón
                    $filter.css({
                        display: 'flex',
                        'align-items': 'center',
                        'justify-content': 'flex-end',
                        gap: '10px'
                    });

                    $filter.find('label').css({ 'margin-bottom': '0' });

                    var $btnWrapper = $('<div>', { 'class': 'space-btn-incluir' });
                    var $btn = $('<button>', {
                        id: 'btnIncluirMarca',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Marca'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaMarcas = $tabla.DataTable();
        }
    }

    $("#nombre_marca").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/, e);
        let nombre = document.getElementById("nombre_marca");
        nombre.value = space(nombre.value);
    });
    $("#nombre_marca").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,25}$/,
            $(this),
            $("#snombre_marca"),
            "*El formato permite letras y números*"
        );
    });
function verificarPermisosEnTiempoReal() {
    var datos = new FormData();
    datos.append('accion', 'permisos_tiempo_real');
    enviarAjax(datos, function(permisos) {
        // Si es SuperUsuario, mostrar todo y salir
        if (esSuperUsuario) {
            $('#tablaConsultas').show();
            $('.space-btn-incluir').show();
            $('#btnIncluirMarca').show();
            $('.btn-modificar').show();
            $('.btn-eliminar').show();
            $('#mensaje-permiso').remove();
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').show();
            return;
        }
        if (!permisos.consultar) {
    $('#tablaConsultas').hide();
    $('.space-btn-incluir').hide();
    if ($('#mensaje-permiso').length === 0) {
        $('.contenedor-tabla').prepend('<div id="mensaje-permiso" style="color:red; text-align:center; margin:20px 0;">No tiene permiso para consultar los registros.</div>');
    }
    return; // Detener ejecución si no tiene permiso de consultar
} else {
    $('#tablaConsultas').show();
    $('.space-btn-incluir').show();
    $('#mensaje-permiso').remove();
}
        // Mostrar/ocultar botón de incluir
        if (permisos.incluir) {
            $('#btnIncluirMarca').show();
        } else {
            $('#btnIncluirMarca').hide();
        }

        // Mostrar/ocultar botones de modificar/eliminar
        $('.btn-modificar').each(function() {
            if (permisos.modificar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        $('.btn-eliminar').each(function() {
            if (permisos.eliminar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Ocultar columna Acciones si ambos permisos son falsos
        if (!permisos.modificar && !permisos.eliminar) {
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').hide();
        } else {
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').show();
        }
        // Dentro de la función verificarPermisosEnTiempoReal, al inicio del callback:

    });

    
}

// Llama la función al cargar la página y luego cada 10 segundos
$(document).ready(function() {
    verificarPermisosEnTiempoReal();
    setInterval(verificarPermisosEnTiempoReal, 1000); // 10 segundos
});
    function validarEnvioMarca(){
        let nombre = $("#nombre_marca");
        nombre.val(space(nombre.val()).trim());
        const userVal = nombre.val();

        if (userVal === "") {
            $("#snombre_marca").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el nombre de la marca','El campo está vacío.');
            return false;
        }
        if (userVal.length < 2) {
            $("#snombre_marca").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre de la marca','Debe tener mínimo 2 caracteres.');
            return false;
        }
        return true;
    }

    function agregarFilaMarca(marca) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-numeros">${marca.id_marca}</span>`,
            `<span class="campo-nombres">${marca.nombre_marca}</span>`,
            `<ul>
                <button class="btn-modificar"
                    title="Modificar Marca"
                    data-id="${marca.id_marca}"
                    data-nombre="${marca.nombre_marca}">
                    <img src="assets/img/pencil.svg">
                </button>
                <button class="btn-eliminar"
                    title="Eliminar Marca"
                    data-id="${marca.id_marca}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', marca.id_marca);
    }

    $(document).on('click', '#btnIncluirMarca', function() {
        $('#registrarMarca')[0].reset();
        $('#snombre_marca').text('');
        $('#registrarMarcaModal').modal('show');
    });
    
    function resetMarca() {
        $("#nombre_marca").val('');
        $("#snombre_marca").text('');
        $('#registrarMarcaModal').modal('hide');
    }

    $('#registrarMarca').on('submit', function(e) {
        e.preventDefault();
        if(validarEnvioMarca()){
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" && respuesta.marca){
                    Swal.fire({icon: 'success', title: 'Éxito', text: respuesta.message || 'Marca registrada correctamente'});
                    agregarFilaMarca(respuesta.marca);
                    resetMarca();
                } else {
                    manejarErroresBackend(respuesta, false);
                }
            });
        }
    });

    $(document).on('click', '#registrarMarcaModal .close', function() {
        $('#registrarMarcaModal').modal('hide');
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

    $("#modificar_nombre_marca").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ0-9\s\b]*$/, e);
        let nombre = document.getElementById("modificar_nombre_marca");
        nombre.value = space(nombre.value);
    });
    $("#modificar_nombre_marca").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ0-9\s\b]{2,25}$/,
            $(this),
            $("#smnombre_marca"),
            "*El formato permite letras y números*"
        );
    });

    $(document).on('click', '.btn-modificar', function () {
        $('#modificar_id_marca').val($(this).data('id'));
        $('#modificar_nombre_marca').val($(this).data('nombre'));
        $('#smnombre_marca').text('');
        // Guardar la fila origen para actualizarla tras el éxito
        const $row = $(this).closest('tr');
        $('#modificarMarcaModal').data('row', $row);
        $('#modificarMarcaModal').modal('show');
    });
    
    $('#modificarMarca').on('submit', function(e) {
        e.preventDefault();

        const $nombre = $('#modificar_nombre_marca');
        $nombre.val(space($nombre.val()).trim());
        const valor = $nombre.val();

        if (valor === '') {
            $('#smnombre_marca').text('*Este campo es obligatorio*');
            mensajes('error','Verifique el nombre de la marca','El campo está vacío.');
            return;
        }
        if (valor.length < 2) {
            $('#smnombre_marca').text('*Mínimo 2 caracteres*');
            mensajes('error','Verifique el nombre de la marca','Debe tener mínimo 2 caracteres.');
            return;
        }

        var formData = new FormData(this);
        formData.append('accion', 'modificar');
        
        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                try {
                    // Asegurarse de que la respuesta sea un objeto
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (response.status === 'success') {
                        // Cerrar el modal de modificación
                        $('#modificarMarcaModal').modal('hide');
                        
                        // Obtener el ID de la marca que se está modificando
                        const idMarca = $('#modificar_id_marca').val();
                        const nuevoNombre = response.marca.nombre_marca;
                        
                        // Actualizar la fila en la tabla
                        const tabla = $('#tablaConsultas').DataTable();
                        const fila = $(`#tablaConsultas tbody tr[data-id="${idMarca}"]`);
                        
                        if (fila.length) {
                            // Actualizar el contenido de las celdas
                            fila.find('td:eq(1) span').text(nuevoNombre).addClass("campo-nombres");
                            // Actualizar los data attributes del botón de modificar
                            fila.find('.btn-modificar')
                                .attr('data-nombre', nuevoNombre);
                            
                            // Mostrar mensaje de éxito
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'La marca se ha actualizado correctamente'
                            });
                        } else {
                            // Si no se encuentra la fila, recargar la tabla
                            tabla.ajax.reload();
                        }
                    } else {
                        manejarErroresBackend(response, true);
                    }
                } catch (error) {
                    console.error('Error al procesar la respuesta:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al procesar la respuesta del servidor'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar la marca:', textStatus, errorThrown);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al intentar modificar la marca'
                });
            }
        });
    });

    $(document).on('click', '#modificarMarcaModal .close', function() {
        $('#modificarMarcaModal').modal('hide');
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
            confirmButtonText: 'Sí, eliminarla!'
        }).then((result) => {
            if (result.isConfirmed) {
                var id_marca = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_marca', id_marca);
                enviarAjax(datos, function(respuesta){
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminada!',
                            'La marca ha sido eliminada.',
                            'success'
                        );
                        eliminarFilaMarca(id_marca);
                    } else {
                        // Mostrar mensaje específico si hay modelos asociados
                        if (respuesta.message && respuesta.message.includes('modelos asociados')) {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se puede eliminar',
                                html: 'No se puede eliminar la marca porque tiene <strong>modelos asociados</strong>. ' +
                                      'Primero debe eliminar o reasignar los modelos antes de eliminar la marca.'
                            });
                        } else {
                            Swal.fire('Error', respuesta.message || 'Error al eliminar la marca', 'error');
                        }
                    }
                });
            }
        });
    });

    function eliminarFilaMarca(id_marca) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_marca}"]`);
        tabla.row(fila).remove().draw();
    }

    function mensajes(icono, titulo, mensaje){
        Swal.fire({
            icon: icono,
            title: titulo,
            text: mensaje,
            showConfirmButton: true,
            confirmButtonText: 'Aceptar',
        });
    }

    function validarKeyPress(er, e) {
        key = e.keyCode;
        tecla = String.fromCharCode(key);
        a = er.test(tecla);

        if (!a) {
            e.preventDefault();
        }
    }

    function validarKeyUp(er, etiqueta, etiquetamensaje, mensaje) {
        a = er.test(etiqueta.val());

        if (a) {
            etiquetamensaje.text("");
            return 1;
        } else {
            etiquetamensaje.text(mensaje);
            return 0;
        }
    }

    function space(str) {
        const regex = /\s{2,}/g;
        var str = str.replace(regex, ' ');
        return str;
    }

    // Modal de Ayuda - Integración para Marcas
    let modalAyudaMarcaInstance = null;

    // Función para cargar y mostrar el modal de ayuda de marcas con contexto específico
    function cargarYMostrarModalAyudaMarca(contexto = null) {
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
        $.get('assets/public/ayuda/marca.php')
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
                                modalAyudaMarcaInstance = inicializarModalAyudaUsuario();

                                // Abrir modal con contexto si se proporciona
                                if (contexto) {
                                    setTimeout(() => {
                                        const slideIndex = modalAyudaMarcaInstance.mapeoContextos[contexto];
                                        if (slideIndex !== undefined) {
                                            modalAyudaMarcaInstance.goToSlide(slideIndex);
                                        }
                                    }, 300);
                                }

                                // Abrir modal
                                modalAyudaMarcaInstance.openModal();
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
                        modalAyudaMarcaInstance = inicializarModalAyudaUsuario();

                        // Abrir modal con contexto si se proporciona
                        if (contexto) {
                            setTimeout(() => {
                                const slideIndex = modalAyudaMarcaInstance.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    modalAyudaMarcaInstance.goToSlide(slideIndex);
                                }
                            }, 300);
                        }

                        // Abrir modal
                        modalAyudaMarcaInstance.openModal();
                    }
                }
            })
            .fail(function() {
                console.error('Error al cargar el HTML del modal de marcas');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el contenido de ayuda'
                });
            });
    }

    // Botón de ayuda principal de marcas
    $('.btn-ayuda').off('click.ayuda-marca').on('click.ayuda-marca', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda de marcas detectado');
        cargarYMostrarModalAyudaMarca(); // Sin contexto específico
    });

    // Botón de ayuda dentro de modales de marcas
    $(document).on('click.ayuda-marca', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal de marcas con contexto:', contexto);
        cargarYMostrarModalAyudaMarca(contexto);
    });
});