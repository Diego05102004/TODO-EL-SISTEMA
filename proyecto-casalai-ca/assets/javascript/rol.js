$(document).ready(function () {

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    // Inicializar DataTable de roles y crear dinámicamente el botón Incluir en el filtro
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaRoles;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaRoles = $tabla.DataTable({
                language: {
                    url: 'assets/public/js/es-ES.json'
                },

                scrollX: true,
                scrollCollapse: true,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 2 } // Deshabilitar ordenamiento en columna de acciones
                ],
                initComplete: function () {
                    var api = this.api();
                    var $wrapper = $(api.table().container());

                    api.columns.adjust();

                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;

                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirRol').length) return;

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
                        id: 'btnIncluirRol',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Rol'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaRoles = $tabla.DataTable();
        }
    }

    $("#nombre_rol").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre_rol");
        nombre.value = space(nombre.value);
    });
    $("#nombre_rol").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,25}$/,
            $(this),
            $("#snombre_rol"),
            "*El formato solo permite letras*"
        );
    });

    function verificarPermisosEnTiempoRealRoles() {
        var datos = new FormData();
        datos.append('accion', 'permisos_tiempo_real');
        enviarAjax(datos, function(permisos) {
            // Si no tiene permiso de consultar
            if (!permisos.consultar) {
                $('#tablaConsultas').hide();
                $('.space-btn-incluir').hide();
                if ($('#mensaje-permiso').length === 0) {
                    $('.contenedor-tabla').prepend('<div id="mensaje-permiso" style="color:red; text-align:center; margin:20px 0;">No tiene permiso para consultar los registros.</div>');
                }
                return;
            } else {
                $('#tablaConsultas').show();
                $('.space-btn-incluir').show();
                $('#mensaje-permiso').remove();
            }

            // Mostrar/ocultar botón de incluir
            if (permisos.incluir) {
                $('#btnIncluirRol').show();
            } else {
                $('#btnIncluirRol').hide();
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
        });
    }

    // Llama la función al cargar la página y luego cada 10 segundos
    $(document).ready(function() {
        verificarPermisosEnTiempoRealRoles();
        setInterval(verificarPermisosEnTiempoRealRoles, 10000); // 10 segundos
    });
    function validarEnvioRol(){
        let nombre = $("#nombre_rol");
        nombre.val(space(nombre.val()).trim());
        const rolVal = nombre.val();

        if (rolVal === "") {
            $("#snombre_rol").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el nombre del rol','El campo está vacío.');
            return false;
        }
        if (rolVal.length < 2) {
            $("#snombre_rol").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre del rol','Debe tener mínimo 2 caracteres.');
            return false;
        }
        return true;
    }

    function agregarFilaRol(rol) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-numeros">${rol.id_rol}</span>`,
            `<span class="campo-nombres">${rol.nombre_rol}</span>`,
            `<ul>
                <button class="btn-modificar"
                    id="btnModificarRol"
                    title="Modificar Rol"
                    data-id="${rol.id_rol}"
                    data-nombre="${rol.nombre_rol}">
                    <img src="assets/img/pencil.svg">
                </button>
                <a href="?pagina=permiso">
                    <button class="btn-permisos"
                        title="Gestionar Permisos">
                        <img src="assets/img/comprobacion-de-lista.svg">
                    </button>
                </a>
                <button class="btn-eliminar"
                    title="Eliminar Rol"
                    data-id="${rol.id_rol}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', rol.id_rol);
    }

    function resetRol() {
        $("#nombre_rol").val('');
        $("#snombre_rol").text('');
    }

    // Abrir modal de registro (botón Incluir Rol dentro del DataTable)
    $(document).on('click', '#btnIncluirRol', function() {
        $('#registrarRol')[0].reset();
        $('#snombre_rol').text('');
        $('#registrarRolModal').modal('show');
    });

    $('#registrarRol').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioRol()){
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || respuesta.msg || 'Rol registrado correctamente'
                    });
                    agregarFilaRol(respuesta.rol);
                    resetRol();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || respuesta.msg || 'No se pudo registrar el rol'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarRolModal .close', function() {
        $('#registrarRolModal').modal('hide');
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

    function muestraMensaje(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: mensaje
        });
    }

    $(document).on('click', '#btnModificarRol', function () {
        $('#modificar_id_rol').val($(this).data('id'));
        $('#modificar_nombre_rol').val($(this).data('nombre'));
        $('#smnombre_rol').text('');
        $('#modificarRolModal').modal('show');
    });

    $("#modificar_nombre_rol").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("modificar_nombre_rol");
        nombre.value = space(nombre.value);
    });
    $("#modificar_nombre_rol").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,25}$/,
            $(this),
            $("#smnombre_rol"),
            "*El formato solo permite letras*"
        );
    });

    $('#modificarRol').on('submit', function(e) {
        e.preventDefault();

        const $nombre = $('#modificar_nombre_rol');
        $nombre.val(space($nombre.val()).trim());
        const valor = $nombre.val();

        if (valor === '') {
            $('#smnombre_rol').text('*Este campo es obligatorio*');
            mensajes('error','Verifique el nombre del rol','El campo está vacío.');
            return;
        }
        if (valor.length < 2) {
            $('#smnombre_rol').text('*Mínimo 2 caracteres*');
            mensajes('error','Verifique el nombre del rol','Debe tener mínimo 2 caracteres.');
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
                if (response.status === 'success') {
                    $('#modificarRolModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'El rol se ha modificado correctamente'
                    });

                    const tabla = $("#tablaConsultas").DataTable();
                    const id = $("#modificar_id_rol").val();
                    const fila = tabla.row(`tr[data-id="${id}"]`);
                    const rol = response.rol;

                    if (fila.length) {
                        fila.data([
                            `<span class="campo-numeros">${rol.id_rol}</span>`,
                            `<span class="campo-nombres">${rol.nombre_rol}</span>`,
                            `<ul>
                                <button class="btn-modificar"
                                    id="btnModificarRol"
                                    title="Modificar Rol"
                                    data-id="${rol.id_rol}"
                                    data-nombre="${rol.nombre_rol}">
                                    <img src="assets/img/pencil.svg">
                                </button>
                                <a href="?pagina=permiso">
                                    <button class="btn-permisos"
                                        title="Gestionar Permisos">
                                        <img src="assets/img/comprobacion-de-lista.svg">
                                    </button>
                                </a>
                                <button class="btn-eliminar"
                                    title="Eliminar Rol"
                                    data-id="${rol.id_rol}">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            </ul>`
                        ]).draw(false);

                        const filaNode = fila.node();
                        const botonModificar = $(filaNode).find(".btn-modificar");
                        botonModificar.data('nombre', rol.nombre_rol);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo modificar el rol'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar el rol:', textStatus, errorThrown);
                muestraMensaje('Error al modificar el rol.');
            }
        });
    });

    $(document).on('click', '#modificarRolModal .close', function() {
        $('#modificarRolModal').modal('hide');
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
                var id_rol = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_rol', id_rol);
                enviarAjax(datos, function(respuesta){
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminada!',
                            'El rol ha sido eliminada.',
                            'success'
                        );
                        eliminarFilaRol(id_rol);
                    } else {
                        Swal.fire('Error', respuesta.message, 'error');
                    }
                });
            }
        });
    });

    function eliminarFilaRol(id_rol) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_rol}"]`);
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
        $.get('assets/public/ayuda/rolypermisos.php')
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

    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal con contexto:', contexto);
        cargarYMostrarModalAyuda(contexto);
    });
});