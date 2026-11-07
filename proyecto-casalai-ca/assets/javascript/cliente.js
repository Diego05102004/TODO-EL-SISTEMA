$(document).ready(function () {

    // MENSAJE //
    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    // Inicializar DataTable de clientes y crear dinámicamente el botón Incluir en el filtro
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaClientes;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaClientes = $tabla.DataTable({
                language: {
                    "url": "assets/public/js/es-ES.json"
                },
                scrollX: true,
                scrollCollapse: true,
                columnDefs: [
                    { orderable: false, targets: 5 } // Deshabilitar ordenamiento para columna de acciones
                ],
                initComplete: function () {
                    var api = this.api();
                    var $wrapper = $(api.table().container());

                    api.columns.adjust();

                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;

                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirCliente').length) return;

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
                        id: 'btnIncluirCliente',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Cliente'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaClientes = $tabla.DataTable();
        }
    }

    $("#nombre").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre");
        nombre.value = space(nombre.value);
    });

    $("#nombre").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/,
            $(this),
            $("#snombre"),
            "*El formato solo permite letras*"
        );
    });

    $("#cedula").on("keypress", function(e){
        validarKeyPress(/^[0-9.]*$/, e);
    });

    $("#cedula").on("keyup", function(){
        validarKeyUp(
            /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
            $(this),
            $("#scedula"),
            "*El formato debe ser 1.234.567 o 12.345.678*"
        );
    });
    $("#cedula").on("input", function() {
        let d = $(this).val().replace(/\D/g, '');
        let out = d;
        if (d.length === 7) {
            // X.XXX.XXX
            out = d.slice(0,1) + '.' + d.slice(1,4) + '.' + d.slice(4,7);
        } else if (d.length === 8) {
            // XX.XXX.XXX
            out = d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5,8);
        }
        $(this).val(out);
    });

    $("#telefono").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });

    $("#telefono").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#telefono").on("input", function() {
        let valor_t1 = $(this).val().replace(/\D/g, '');
        if(valor_t1.length > 4 && valor_t1.length <= 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
        else if(valor_t1.length > 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
        $(this).val(valor_t1);
    });

    $("#direccion").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let direccion = document.getElementById("direccion");
        direccion.value = space(direccion.value);
    });

    $("#direccion").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{4,100}$/,
            $(this),
            $("#sdireccion"),
            "*El formato permite letras y números*"
        );
    });

    $("#correo").on("keypress", function (e) {
        validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
    });

    $("#correo").on("keyup", function () {
        validarKeyUp(
        /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
        $(this),
        $("#scorreo"),
        "*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*"
        );
    });
    
function verificarPermisosEnTiempoRealClientes() {
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
            $('#btnIncluirCliente').show();
        } else {
            $('#btnIncluirCliente').hide();
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
    verificarPermisosEnTiempoRealClientes();
    setInterval(verificarPermisosEnTiempoRealClientes, 10000); // 10 segundos
});
    function validarEnvioCliente(){
        let nombre = $("#nombre");
        nombre.val(space(nombre.val()).trim());
        const nombreVal = nombre.val();
        if (nombreVal === "") {
            $("#snombre").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el nombre','El campo está vacío.');
            return false;
        }
        if (nombreVal.length < 2) {
            $("#snombre").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre','Debe tener mínimo 2 caracteres.');
            return false;
        }
        if(validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/,
            $("#nombre"),
            $("#snombre"),
            "*El nombre debe tener solo letras*"
        )==0){
            mensajes('error','Verifique el nombre','Debe tener solo letras');
            return false;
        }

        let cedula = $("#cedula");
        cedula.val(space(cedula.val()).trim());
        const cedVal = cedula.val();
        if (cedVal === "") {
            $("#scedula").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el número de Cédula','El campo está vacío.');
            return false;
        }
        if (!/^(?:\d{1,2}\.\d{3}\.\d{3})$/.test(cedVal)) {
            $("#scedula").text("*Formato válido: 1.234.567 o 12.345.678*");
            mensajes('error','Verifique el número de Cédula','Formato inválido.');
            return false;
        }

        let telefono = $("#telefono");
        const telVal = telefono.val().trim();
        if (telVal === "") {
            $("#stelefono").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el teléfono','El campo está vacío.');
            return false;
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(telVal)) {
            $("#stelefono").text("*Formato válido: 0400-000-0000*");
            mensajes('error','Verifique el teléfono','Formato inválido.');
            return false;
        }

        let direccion = document.getElementById("direccion");
        direccion.value = space(direccion.value).trim();
        const dirVal = $("#direccion").val().trim();
        if (dirVal === "") {
            $("#sdireccion").text("*Este campo es obligatorio*");
            mensajes('error','Verifique la dirección','El campo está vacío.');
            return false;
        }
        if (dirVal.length < 4) {
            $("#sdireccion").text("*Mínimo 4 caracteres*");
            mensajes('error','Verifique la dirección','Debe tener mínimo 4 caracteres.');
            return false;
        }
        if(validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{4,100}$/,
            $("#direccion"),
            $("#sdireccion"),
            "*El formato permite letras y números*"
        )==0){
            mensajes('error','Verifique la dirección','Debe tener solo letras y números');
            return false;
        }
        {
            const correoVal = $("#correo").val().trim();
            if (correoVal === "") {
                $("#scorreo").text("*Este campo es obligatorio*");
                mensajes('error','Verifique el correo','El campo está vacío.');
                return false;
            }
            if (!/^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/.test(correoVal)) {
                $("#scorreo").text("*Formato correcto: ejemplo@gmail.com*");
                mensajes('error','Verifique el correo','Formato inválido');
                return false;
            }
        }
        return true;
    }

    function agregarFilaCliente(cliente) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-nombres">${cliente.nombre}</span>`,
            `<span class="campo-numeros">${cliente.cedula}</span>`,
            `<span class="campo-nombres">${cliente.direccion}</span>`,
            `<span class="campo-numeros">${cliente.telefono}</span>`,
            `<span class="campo-tex-num">${cliente.correo}</span>`,
            `<ul>
                <button class="btn-modificar"
                    id="btnModificarCliente"
                    title="Modificar Cliente"
                    data-id="${cliente.id_clientes}"
                    data-nombre="${cliente.nombre}"
                    data-cedula="${cliente.cedula}"
                    data-direccion="${cliente.direccion}"
                    data-telefono="${cliente.telefono}"
                    data-correo="${cliente.correo}">
                    <img src="assets/img/pencil.svg">
                </button>
                <button class="btn-eliminar"
                    title="Eliminar Cliente"
                    data-id="${cliente.id_clientes}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', cliente.id_clientes);
    }

    function resetCliente() {
        $("#nombre").val('');
        $("#cedula").val('');
        $("#direccion").val('');
        $("#telefono").val('');
        $("#correo").val('');
        $("#snombre").text('');
        $("#scedula").text('');
        $("#sdireccion").text('');
        $("#stelefono").text('');
        $("#scorreo").text('');
    }

    // Abrir modal de registro (botón Incluir Cliente dentro del DataTable)
    $(document).on('click', '#btnIncluirCliente', function() {
        $('#ingresarclientes')[0].reset();
        $('#snombre').text('');
        $('#scedula').text('');
        $('#sdireccion').text('');
        $('#stelefono').text('');
        $('#scorreo').text('');
        $('#registrarClienteModal').modal('show');
    });

    $('#ingresarclientes').on('submit', function(e) {
    e.preventDefault();

        if(validarEnvioCliente()){
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: datos,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(respuesta){
                    if(respuesta.status === "success"){
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: respuesta.message || 'Cliente registrado correctamente'
                        }).then(() => {
                            if(respuesta.status === "success" && respuesta.cliente){
                                agregarFilaCliente(respuesta.cliente);
                                resetCliente();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: respuesta.message || 'Error al registrar el cliente'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX completo en registro:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    // Si es error 500, mostrar información detallada
                    if (xhr.status === 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error del servidor (500)',
                            html: `
                                <p><strong>Error interno del servidor</strong></p>
                                <p>Revise la consola del navegador para más detalles.</p>
                                <p><small>Response: ${xhr.responseText.substring(0, 200)}...</small></p>
                            `,
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'Ocurrió un error al comunicarse con el servidor'
                        });
                    }
                }
            });
        }
    });

    $(document).on('click', '#registrarClienteModal .close', function() {
        $('#registrarClienteModal').modal('hide');
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
            error: function(xhr, status, error) {
                console.error('Error AJAX completo:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                // Si es error 500, mostrar información detallada
                if (xhr.status === 500) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error del servidor (500)',
                        html: `
                            <p><strong>Error interno del servidor</strong></p>
                            <p>Revise la consola del navegador para más detalles.</p>
                            <p><small>Response: ${xhr.responseText.substring(0, 200)}...</small></p>
                        `,
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
                }
            }
        });
    }

    $(document).on('click', '#btnModificarCliente', function () {
        $('#modificar_id_clientes').val($(this).data('id'));
        $('#modificarnombre').val($(this).data('nombre'));
        $('#modificarcedula').val($(this).data('cedula'));
        $('#modificardireccion').val($(this).data('direccion'));
        $('#modificartelefono').val($(this).data('telefono'));
        $('#modificarcorreo').val($(this).data('correo'));
        $('#smodificarnombre').text('');
        $('#smodificarcedula').text('');
        $('#smodificardireccion').text('');
        $('#smodificartelefono').text('');
        $('#smodificarcorreo').text('');
        $('#modificar_clientes_modal').modal('show');
    });

    $("#modificarnombre").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre");
        nombre.value = space(nombre.value);
    });

    $("#modificarnombre").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/,
            $(this),
            $("#smodificarnombre"),
            "*El formato solo permite letras*"
        );
    });

    $("#modificarcedula").on("keypress", function(e){
        validarKeyPress(/^[0-9]*$/, e);
    });

    $("#modificarcedula").on("keyup", function(){
        validarKeyUp(
            /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
            $(this),
            $("#scedula"),
            "*Formato válido: 1.234.567 o 12.345.678*"
        );
    });

    $("#modificarcedula").on("input", function() {
        let d = $(this).val().replace(/\D/g, '');
        let out = d;
        if (d.length === 7) {
            // X.XXX.XXX
            out = d.slice(0,1) + '.' + d.slice(1,4) + '.' + d.slice(4,7);
        } else if (d.length === 8) {
            // XX.XXX.XXX
            out = d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5,8);
        }
        $(this).val(out);
    });

    $("#modificartelefono").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });

    $("#modificartelefono").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#smodificartelefono"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#modificartelefono").on("input", function() {
        let valor_t1 = $(this).val().replace(/\D/g, '');
        if(valor_t1.length > 4 && valor_t1.length <= 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
        else if(valor_t1.length > 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
        $(this).val(valor_t1);
    });

    $("#modificardireccion").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let direccion = document.getElementById("direccion");
        direccion.value = space(direccion.value);
    });

    $("#modificardireccion").on("keyup", function(){
        const val = $(this).val().trim();
        if (val === "") {
            $("#smodificardireccion").text("*Este campo es obligatorio*");
            return;
        }
        if (val.length < 4) {
            $("#smodificardireccion").text("*Mínimo 4 caracteres*");
            return;
        }
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{4,100}$/,
            $(this),
            $("#smodificardireccion"),
            "*El formato permite letras y números*"
        );
    });

    $("#modificarcorreo").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZñÑ_0-9@,.\b]*$/, e);
    });

    $("#modificarcorreo").on("keyup", function(){
        validarKeyUp(
            /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
            $(this),
            $("#smodificarcorreo"),
            "*Formato válido: example@gmail.com*"
        );
    });

    function validarCliente(datos) {
        let errores = [];
        const nom = (datos.nombre || "").trim();
        if (nom === "") {
            errores.push("Este campo es obligatorio.");
        } else if (nom.length < 2) {
            errores.push("Mínimo 2 caracteres.");
        }
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/.test(nom)) {
            errores.push("El nombre debe tener solo letras.");
        }
        const ced = (datos.cedula || "").trim();
        if (ced === "") {
            errores.push("Este campo es obligatorio.");
        }
        if (!/^(?:\d{1,2}\.\d{3}\.\d{3})$/.test(ced)) {
            errores.push("Formato válido: 1.234.567 o 12.345.678");
        }
        const dir = (datos.direccion || "").trim();
        if (dir === "") {
            errores.push("Este campo es obligatorio.");
        } else if (dir.length < 4) {
            errores.push("Mínimo 4 caracteres.");
        }
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{4,100}$/.test(dir)) {
            errores.push("El formato permite letras y números.");
        }
        const tel = (datos.telefono || "").trim();
        if (tel === "") {
            errores.push("Este campo es obligatorio.");
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(tel)) {
            errores.push("Formato correcto: 0400.000.0000");
        }
        if (!datos.correo || datos.correo.trim() === "") {
            errores.push("Este campo es obligatorio.");
        } else if (!/^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/.test(datos.correo)) {
            errores.push("Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com");
        }
        return errores;
    }

    $('#modificarclientes').on('submit', function(e) {
        e.preventDefault();

        const datos = {
            nombre: $('#modificarnombre').val(),
            cedula: $('#modificarcedula').val(),
            direccion: $('#modificardireccion').val(),
            telefono: $('#modificartelefono').val(),
            correo: $('#modificarcorreo').val()
        }

        const errores = validarCliente(datos);

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errores.join('<br>')
            });
            return;
        }

        var formData = new FormData(this);
        formData.append('accion', 'modificar');

        $.ajax({
            url: '',
            type: 'POST',
            processData: false,
            contentType: false,
            cache: false,
            data: formData,
            dataType: 'json',
            success: function(response) {
            if (response.status === 'success') {
                $('#modificar_clientes_modal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Modificado',
                    text: 'El Cliente se ha modificado correctamente'
                });

                const tabla = $("#tablaConsultas").DataTable();
                const id = $("#modificar_id_clientes").val();
                const fila = tabla.row(`tr[data-id="${id}"]`);
                const cliente = response.cliente;

                if (fila.length) {
                    fila.data([
                        `<span class="campo-nombres">${cliente.nombre}</span>`,
                        `<span class="campo-numeros">${cliente.cedula}</span>`,
                        `<span class="campo-nombres">${cliente.direccion}</span>`,
                        `<span class="campo-numeros">${cliente.telefono}</span>`,
                        `<span class="campo-tex-num">${cliente.correo}</span>`,
                        `<ul>
                            <div>
                                <button class="btn-modificar"
                                    id="btnModificarCliente"
                                    title="Modificar Cliente"
                                    data-id="${cliente.id_clientes}"
                                    data-nombre="${cliente.nombre}"
                                    data-cedula="${cliente.cedula}"
                                    data-direccion="${cliente.direccion}"
                                    data-telefono="${cliente.telefono}"
                                    data-correo="${cliente.correo}">
                                    <img src="assets/img/pencil.svg">
                                </button>
                            </div>
                            <div>
                                <button class="btn-eliminar"
                                    title="Eliminar Cliente"
                                    data-id="${cliente.id_clientes}">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            </div>
                        </ul>`
                    ]).draw(false);

                    const filaNode = fila.node();
                    const botonModificar = $(filaNode).find(".btn-modificar");
                    botonModificar.data('nombre', cliente.nombre);
                    botonModificar.data('cedula', cliente.cedula);
                    botonModificar.data('direccion', cliente.direccion);
                    botonModificar.data('telefono', cliente.telefono);
                    botonModificar.data('correo', cliente.correo);
                }
            } else {
                    muestraMensaje(response.message || 'No se pudo modificar el cliente');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                muestraMensaje('Error al modificar el Cliente.');
            }
        });
    });

    $(document).on('click', '#modificar_clientes_modal .close', function() {
        $('#modificar_clientes_modal').modal('hide');
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
                var id_clientes = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_clientes', id_clientes);
                enviarAjax(datos, function(respuesta){
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminada!',
                            'El cliente ha sido eliminado correctamente.',
                            'success'
                        );
                        eliminarFilaCliente(id_clientes);
                    } else {
                        Swal.fire('Error', respuesta.message, 'error');
                    }
                });
            }
        });
    });

    function eliminarFilaCliente(id_clientes) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_clientes}"]`);
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
        $.get('assets/public/ayuda/cliente.php')
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

function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}