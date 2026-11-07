function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    // Guardar opciones originales de cada select
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;

        originales[id] = Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    });

    // Monitorear periódicamente cambios en las opciones
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

                console.warn(`⚠ Opciones del <select id="${id}"> fueron alteradas. Restauradas automáticamente.`);
            }
        });
    }, interval);
}

$(document).ready(function () {
    var nombre_rol = "<?php echo $_SESSION['nombre_rol'] ?? ''; ?>";
    var esSuperUsuario = nombre_rol === 'SuperUsuario';

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    // Inicializar DataTable de categorías y crear dinámicamente el botón Incluir en el filtro
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaCategorias;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaCategorias = $tabla.DataTable({
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
                    if ($filter.find('#btnIncluirCategoria').length) return;

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
                        id: 'btnIncluirCategoria',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Categoria'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaCategorias = $tabla.DataTable();
        }
    }

    protegerSelects(['categoria', 'modificar_categoria']);

    $("#nombre_categoria").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/, e);
        let nombre = document.getElementById("nombre_categoria");
        nombre.value = space(nombre.value);
    });

    // Al presionar Limpiar (type=reset), remover iconos y ocultar max (sin re-disparar reset)
    $('#registrarCategoria').on('reset', function(){
        setTimeout(function(){
            // Limpiar spans
            $("#snombre_categoria").text('');
            $("#snombre_caracteristica").text('');
            // Quitar clases de validación
            $("#nombre_categoria").removeClass("is-valid is-invalid");
            $('#caracteristicasContainer .caracteristica-item input, #caracteristicasContainer .caracteristica-item select')
                .removeClass('is-valid is-invalid');
            // Ocultar campo max (quitar required)
            $('#caracteristicasContainer .caracteristica-item input[name*="[max]"]').each(function(){
                $(this).css('display','none').prop('required', false);
            });
            // Dejar solo una característica limpia
            const $cont = $('#caracteristicasContainer');
            const $items = $cont.find('.caracteristica-item');
            if ($items.length > 1) { $items.slice(1).remove(); }
            const $first = $cont.find('.caracteristica-item').first();
            if ($first.length) {
                $first.find('input[name*="[nombre]"]').val('');
                const $tipoFirst = $first.find('select[name*="[tipo]"]');
                const $maxFirst = $first.find('input[name*="[max]"]');
                $tipoFirst.val('');
                $maxFirst.val('').css('display','none').prop('required', false);
                $first.find('input, select').removeClass('is-valid is-invalid');
            }
            // Reactivar botón agregar
            const btnAgregar = document.getElementById('agregarCaracteristica');
            if (btnAgregar) btnAgregar.disabled = false;
        }, 0);
    });
    
    $("#nombre_categoria").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/,
            $(this),
            $("#snombre_categoria"),
            "*El formato permite letras y números*"
        );
    });

    function verificarPermisosEnTiempoRealCategoria() {
        var datos = new FormData();
        datos.append('accion', 'permisos_tiempo_real');
        enviarAjax(datos, function(permisos) {
            // Si es SuperUsuario, mostrar todo y salir
            if (esSuperUsuario) {
                $('#tablaConsultas').show();
                $('.space-btn-incluir').show();
                $('#btnIncluirCategoria').show();
                $('.btn-modificar').show();
                $('.btn-eliminar').show();
                $('#mensaje-permiso').remove();
                $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').show();
                return;
            }
            
            console.log(permisos); // Para depuración
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
                $('#btnIncluirCategoria').show();
            } else {
                $('#btnIncluirCategoria').hide();
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
    verificarPermisosEnTiempoRealCategoria();
    setInterval(verificarPermisosEnTiempoRealCategoria, 10000);

    function validarEnvioCategoria(){
        let $input = $("#nombre_categoria");
        let $span = $("#snombre_categoria");
        let val = space($input.val()).trim();
        $input.val(val);

        if (val.length === 0) {
            $input.removeClass("is-valid").addClass("is-invalid");
            $span.text("*Campo obligatorio*");
            mensajes('error','Verifique el nombre de la categoria','El campo no puede estar vacío');
            return false;
        }
        if (val.length < 2) {
            $input.removeClass("is-valid").addClass("is-invalid");
            $span.text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre de la categoria','Debe tener al menos 2 caracteres');
            return false;
        }
        if(validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/,
            $input,
            $span,
            "*El nombre debe tener letras y/o números*"
        )==0){
            mensajes('error','Verifique el nombre de la categoria','Debe tener letras y/o números');
            return false;
        }
        return true;
    }

    function validarCaracteristicas() {
        let ok = true;
        let mensajeError = '';

        $('.caracteristica-item').each(function(index, item) {
            const $nombre = $(item).find('input[name*="[nombre]"]');
            const $tipo = $(item).find('select[name*="[tipo]"]');
            const $max = $(item).find('input[name*="[max]"]');

            const nombreVal = $.trim($nombre.val());
            const tipoVal = $tipo.val();

            // Nombre: obligatorio y mínimo 2 caracteres
            if (nombreVal.length === 0) {
                mensajeError = 'El nombre de la característica está vacío.';
                $nombre.removeClass('is-valid').addClass('is-invalid').focus();
                ok = false; return false;
            }
            if (nombreVal.length < 2) {
                mensajeError = 'El nombre de la característica debe tener al menos 2 caracteres.';
                $nombre.removeClass('is-valid').addClass('is-invalid').focus();
                ok = false; return false;
            }
            // Formato permitido (letras, números y espacios)
            if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/.test(nombreVal)) {
                mensajeError = 'El nombre de la característica permite letras y números (2-20).';
                $nombre.removeClass('is-valid').addClass('is-invalid').focus();
                ok = false; return false;
            }

            // Tipo requerido
            if (!tipoVal) {
                mensajeError = 'Debe seleccionar un tipo para cada característica.';
                $tipo.removeClass('is-valid').addClass('is-invalid').focus();
                ok = false; return false;
            }

            // Si es string, validar max > 0
            if (tipoVal === 'string') {
                const maxVal = ($max.val() || '').trim();
                const maxNum = parseInt(maxVal, 10);
                if (maxVal === '' || isNaN(maxNum) || maxNum <= 0) {
                    mensajeError = 'El campo "Máx. caracteres" debe ser un número mayor a 0.';
                    $max.removeClass('is-valid').addClass('is-invalid').focus();
                    ok = false; return false;
                }
            }

            // Marcar válidos si pasaron
            $nombre.removeClass('is-invalid').addClass('is-valid');
            $tipo.removeClass('is-invalid').addClass('is-valid');
            if (tipoVal === 'string') { $max.removeClass('is-invalid').addClass('is-valid'); }
        });

        if (!ok) {
            mensajes('error','Verifica la caracteristica', mensajeError);
        }
        return ok;
    }

    function agregarFilaCategoria(categoria) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-numeros">${categoria.id_categoria}</span>`,
            `<span class="campo-nombres">${categoria.nombre_categoria}</span>`,
            `<ul>
                <button class="btn-modificar"
                    id="btnModificarCategoria"
                    title="Modificar Categoria"
                    data-id="${categoria.id_categoria}"
                    data-nombre="${categoria.nombre_categoria}">
                    <img src="assets/img/pencil.svg">
                </button>
                <button class="btn-eliminar"
                    title="Eliminar Categoria"
                    data-id="${categoria.id_categoria}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', categoria.id_categoria);
    }

    function resetCategoria() {
        // Resetear valores del formulario
        $("#registrarCategoria")[0].reset();
        $("#snombre_categoria").text('');
        $("#snombre_caracteristica").text('');
        // Quitar estados de validación del nombre de categoría
        $("#nombre_categoria").removeClass("is-valid is-invalid");
        // Quitar estados de validación de todos los inputs/selects dinámicos
        $('#caracteristicasContainer .caracteristica-item input, #caracteristicasContainer .caracteristica-item select')
            .removeClass('is-valid is-invalid');
        // Forzar ocultar y limpiar campo max si el tipo no es string
        $('#caracteristicasContainer .caracteristica-item').each(function(){
            const $item = $(this);
            const $tipo = $item.find('select[name*="[tipo]"]');
            const $max = $item.find('input[name*="[max]"]');
            if ($tipo.val() !== 'string') {
                $max.val('');
                $max.css('display','none');
                $max.prop('required', false);
                $max.removeClass('is-valid is-invalid');
            }
            // Limpiar select tipo
            $tipo.val('');
        });

        // Dejar solo la primera característica (si existe) y eliminar las adicionales
        const $cont = $('#caracteristicasContainer');
        const $items = $cont.find('.caracteristica-item');
        if ($items.length > 0) {
            // Conservar el primero
            const $first = $items.first();
            // Remover el resto
            $items.slice(1).remove();
            // Limpiar valores del primero
            $first.find('input[name*="[nombre]"]').val('');
            const $tipoFirst = $first.find('select[name*="[tipo]"]');
            const $maxFirst = $first.find('input[name*="[max]"]');
            $tipoFirst.val('');
            $maxFirst.val('').css('display','none').prop('required', false);
            $first.find('input, select').removeClass('is-valid is-invalid');
        }

        // Reactivar botón Agregar característica
        const btnAgregar = document.getElementById('agregarCaracteristica');
        if (btnAgregar) btnAgregar.disabled = false;
    }

    $(document).on('click', '#btnIncluirCategoria', function() {
        resetCategoria();
        $('#registrarCategoriaModal').modal('show');
    });

    // Al cerrar el modal, limpiar estados visuales
    $('#registrarCategoriaModal').on('hidden.bs.modal', function(){
        resetCategoria();
    });

    // Al abrir el modal, asegurar estados limpios
    $('#registrarCategoriaModal').on('shown.bs.modal', function(){
        $("#nombre_categoria").removeClass("is-valid is-invalid");
        $('#caracteristicasContainer .caracteristica-item input, #caracteristicasContainer .caracteristica-item select')
            .removeClass('is-valid is-invalid');
        // Ocultar campo max si tipo no es string al abrir
        $('#caracteristicasContainer .caracteristica-item').each(function(){
            const $item = $(this);
            const $tipo = $item.find('select[name*="[tipo]"]');
            const $max = $item.find('input[name*="[max]"]');
            if ($tipo.val() !== 'string') {
                $max.val('');
                $max.css('display','none');
                $max.prop('required', false);
            }
        });
        // Asegurar solo una fila inicial
        const $cont = $('#caracteristicasContainer');
        const $items = $cont.find('.caracteristica-item');
        if ($items.length > 1) {
            $items.slice(1).remove();
        }
        const btnAgregar = document.getElementById('agregarCaracteristica');
        if (btnAgregar) btnAgregar.disabled = false;
    });

    $('#registrarCategoria').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioCategoria() && validarCaracteristicas()) {
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            
            // Convertir valores de max a números antes de enviar
            $('#caracteristicasContainer input[name*="[max]"]').each(function() {
                const maxValor = $(this).val();
                const maxNumero = parseInt(maxValor, 10) || 255;
                datos.set($(this).attr('name'), maxNumero.toString());
            });
            
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || respuesta.msg || 'Categoria registrada correctamente'
                    });
                    agregarFilaCategoria(respuesta.categoria);
                    $('#registrarCategoriaModal').modal('hide');    
                    resetCategoria();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || respuesta.msg || 'No se pudo registrar la categoria'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarCategoriaModal .close', function() {
        $('#registrarCategoriaModal').modal('hide');
    });

    function enviarAjax(datos, callback) {
        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'json', // Esperamos una respuesta JSON
            success: function (respuesta) {
                // Si llegamos aquí, la respuesta es JSON válido
                if(callback) callback(respuesta);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error en la solicitud AJAX:', {
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    responseText: jqXHR.responseText
                });

                let errorMessage = 'Error en la solicitud al servidor';
                let responseData = null;

                try {
                    // Intentar parsear la respuesta como JSON
                    if (jqXHR.responseText) {
                        responseData = JSON.parse(jqXHR.responseText);
                        if (responseData && responseData.message) {
                            errorMessage = responseData.message;
                        }
                    } else if (jqXHR.status === 0) {
                        errorMessage = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
                    } else if (jqXHR.status === 404) {
                        errorMessage = 'No se encontró el recurso solicitado.';
                    } else if (jqXHR.status === 500) {
                        errorMessage = 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.';
                    }
                } catch (e) {
                    // Si la respuesta no es JSON, mostrarla como texto plano
                    console.log('La respuesta no es un JSON válido, mostrando como texto plano');
                    if (jqXHR.responseText) {
                        // Limpiar cualquier HTML o script que pueda estar en la respuesta
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = jqXHR.responseText;
                        const textContent = tempDiv.textContent || tempDiv.innerText || '';
                        
                        // Mostrar solo las primeras líneas para evitar mensajes muy largos
                        const lines = textContent.split('\n').filter(line => line.trim() !== '');
                        errorMessage = 'Error en el servidor: ' + (lines[0] || 'Error desconocido');
                        
                        // Si hay un error de PHP, mostrar más detalles en la consola
                        if (textContent.includes('Fatal error') || textContent.includes('Parse error')) {
                            console.error('Error de PHP detectado:', textContent);
                            errorMessage = 'Error en el servidor (ver consola para más detalles)';
                        }
                    }
                }

                // Mostrar el error al usuario
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage,
                    footer: 'Si el problema persiste, contacta al administrador.'
                });

                // Llamar al callback con un objeto de error estándar
                if (callback) {
                    callback({
                        status: 'error',
                        message: errorMessage,
                        error: {
                            status: jqXHR.status,
                            statusText: jqXHR.statusText,
                            responseText: jqXHR.responseText
                        }
                    });
                }
            }
        });
    }

    $("#modificar_nombre_categoria").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/, e);
        let nombre = document.getElementById("modificar_nombre_categoria");
        nombre.value = space(nombre.value);
    });
    
    $("#modificar_nombre_categoria").on("keyup", function(){
        const $input = $(this);
        const $span = $("#smnombre_categoria");
        const val = $input.val().trim();
        if (val.length === 0) {
            $input.removeClass("is-valid").addClass("is-invalid");
            $span.text("*Campo obligatorio*");
            return;
        }
        if (val.length < 2) {
            $input.removeClass("is-valid").addClass("is-invalid");
            $span.text("*Mínimo 2 caracteres*");
            return;
        }
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/,
            $input,
            $span,
            "*El formato permite letras y números*"
        );
    });

    function validarCategoria(datos) {
        let errores = [];
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/.test(datos.nombre_categoria)) {
            errores.push("El nombre debe tener letras y/o números.");
        }
        return errores;
    }

    $(document).on('click', '#btnModificarCategoria', function () {
        $('#modificar_id_categoria').val($(this).data('id'));
        $('#modificar_nombre_categoria').val($(this).data('nombre'));
        $('#smnombre_categoria').text('');
        $('#modificarCategoriaModal').modal('show');
    });

    $('#modificarCategoria').on('submit', function(e) {
        e.preventDefault();

        const datos = {
            nombre_categoria: $('#modificar_nombre_categoria').val().trim()
        };

        const errores = validarCategoria(datos);

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errores.join('<br>')
            });
            return;
        }

        // Crear array para almacenar las características
        const caracteristicas = [];
        
        // Buscar en el contenedor de características
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        if (contenedor) {
            const caracteristicasElements = contenedor.querySelectorAll('.caracteristica-item');
            
            caracteristicasElements.forEach((caracteristica, index) => {
                const nombreInput = caracteristica.querySelector('input[name$="[nombre]"]');
                const tipoSelect = caracteristica.querySelector('select[name$="[tipo]"]');
                const maxInput = caracteristica.querySelector('input[name$="[max]"]');
                
                const nombre = nombreInput ? nombreInput.value.trim() : '';
                const tipo = tipoSelect ? tipoSelect.value : 'string';
                const maxValor = maxInput ? maxInput.value : '255';
                const maxNumero = parseInt(maxValor, 10) || 255; // Convertir a número, usar 255 como fallback
                
                // Incluir la característica aunque el nombre esté vacío
                caracteristicas.push({
                    nombre: nombre || 'caracteristica_' + Date.now(), // Nombre por defecto si está vacío
                    tipo: tipo,
                    max: tipo === 'string' ? maxNumero : null
                });
            });
        }

        // Crear objeto con todos los datos para depuración
        const datosEnvio = {
            accion: 'modificar',
            id_categoria: $('#modificar_id_categoria').val(),
            nombre_categoria: datos.nombre_categoria,
            caracteristicas: caracteristicas
        };

        // Crear FormData con todos los datos necesarios
        const formData = new FormData();
        formData.append('accion', 'modificar');
        formData.append('id_categoria', datosEnvio.id_categoria);
        formData.append('nombre_categoria', datosEnvio.nombre_categoria);
        formData.append('caracteristicas', JSON.stringify(datosEnvio.caracteristicas));
        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modificarCategoriaModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'La categoria se ha modificado correctamente'
                    });

                    const tabla = $("#tablaConsultas").DataTable();
                    const id = $("#modificar_id_categoria").val();
                    const fila = tabla.row(`tr[data-id="${id}"]`);
                    const categoria = response.categoria;

                    if (fila.length) {
                        fila.data([
                            `<span class="campo-numeros">${categoria.id_categoria}</span>`,
                            `<span class="campo-nombres">${categoria.nombre_categoria}</span>`,
                            `<ul>
                                <button class="btn-modificar"
                                    id="btnModificarCategoria"
                                    title="Modificar Categoria"
                                    data-id="${categoria.id_categoria}"
                                    data-nombre="${categoria.nombre_categoria}">
                                    <img src="assets/img/pencil.svg">
                                </button>
                                <button class="btn-eliminar"
                                    title="Eliminar Categoria"
                                    data-id="${categoria.id_categoria}">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            </ul>`
                        ]).draw(false);

                        const filaNode = fila.node();
                        const botonModificar = $(filaNode).find(".btn-modificar");
                        botonModificar.data('nombre', categoria.nombre_categoria);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo modificar la categoria'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar la categoria:', textStatus, errorThrown, jqXHR.responseText);
                let errorMsg = 'Error al modificar la categoría. Intente nuevamente.';
                
                // Intentar parsear la respuesta como JSON primero
                try {
                    const response = JSON.parse(jqXHR.responseText);
                    if (response && response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    // Si no es JSON válido, mostrar un mensaje genérico
                    console.error('La respuesta no es un JSON válido:', e);
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg,
                    footer: 'Si el problema persiste, contacte al administrador.'
                });
            }
        });
    });

    $(document).on('click', '#modificarCategoriaModal .close', function() {
        $('#modificarCategoriaModal').modal('hide');
    });

    // Validaciones delegadas: nombre de característica (keypress/keyup) y tipo (change)
    $(document).on('keypress', '#caracteristicasContainer input[name*="[nombre]"], #modificar_caracteristicasContainer input[name*="[nombre]"]', function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/, e);
        this.value = space(this.value);
    });

    $(document).on('keyup', '#caracteristicasContainer input[name*="[nombre]"], #modificar_caracteristicasContainer input[name*="[nombre]"]', function(){
        const $input = $(this);
        const val = $input.val().trim();
        if (val.length === 0) {
            $input.removeClass('is-valid').addClass('is-invalid');
            return;
        }
        if (val.length < 2) {
            $input.removeClass('is-valid').addClass('is-invalid');
            return;
        }
        if (/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/.test(val)) {
            $input.removeClass('is-invalid').addClass('is-valid');
        } else {
            $input.removeClass('is-valid').addClass('is-invalid');
        }
    });

    $(document).on('change', '#caracteristicasContainer select[name*="[tipo]"], #modificar_caracteristicasContainer select[name*="[tipo]"]', function(){
        const $sel = $(this);
        const cont = $sel.closest('.caracteristica-item');
        const $max = cont.find('input[name*="[max]"]');
        if (!$sel.val()) {
            $sel.removeClass('is-valid').addClass('is-invalid');
        } else {
            $sel.removeClass('is-invalid').addClass('is-valid');
        }
        // Configurar restricciones cuando es string
        if ($sel.val() === 'string') {
            $max.attr({ min: 1, max: 100, maxlength: 3, step: 1 });
        }
    });

    // Restringir a enteros 1-100 (3 dígitos máx) sin decimales
    $(document).on('keypress', '#caracteristicasContainer input[name*="[max]"], #modificar_caracteristicasContainer input[name*="[max]"]', function(e){
        const ch = String.fromCharCode(e.which);
        if (!/[0-9]/.test(ch)) { e.preventDefault(); return; }
        // Limitar a 3 dígitos
        if (this.value && this.value.length >= 3 && this.selectionStart === this.selectionEnd) {
            e.preventDefault();
        }
    });

    $(document).on('input keyup change', '#caracteristicasContainer input[name*="[max]"], #modificar_caracteristicasContainer input[name*="[max]"]', function(){
        const $max = $(this);
        // Eliminar no dígitos
        let val = ($max.val() || '').replace(/\D+/g, '');
        if (val.length > 3) val = val.slice(0,3);
        let num = parseInt(val || '0', 10);
        if (isNaN(num)) num = 0;
        // Clampear entre 1 y 100
        if (num > 100) num = 100;
        $max.val(num ? String(num) : '');
        if (!num || num < 1) {
            $max.removeClass('is-valid').addClass('is-invalid');
        } else {
            $max.removeClass('is-invalid').addClass('is-valid');
        }
    });

    // FUNCIÓN DE ELIMINACIÓN CON VALIDACIÓN DE PRODUCTOS ASOCIADOS
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
                var id_categoria = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_categoria', id_categoria);
                
                enviarAjax(datos, function(respuesta){
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminada!',
                            'La categoría ha sido eliminada.',
                            'success'
                        );
                        eliminarFilaCategoria(id_categoria);
                    } else {
                        // Mostrar mensaje específico si hay productos asociados
                        if (respuesta.message && respuesta.message.includes('productos registrados') || 
                            respuesta.message && respuesta.message.includes('productos asociados')) {
                            let mensaje = respuesta.message;
                            
                            // Si tenemos información de productos específicos, mostrarla
                            if (respuesta.productos && respuesta.productos.length > 0) {
                                mensaje += '<br><br><strong>Productos asociados (' + (respuesta.total_productos || respuesta.productos.length) + '):</strong><ul style="text-align: left; max-height: 200px; overflow-y: auto;">';
                                respuesta.productos.forEach(function(producto) {
                                    mensaje += '<li><strong>' + (producto.nombre_producto || producto.nombre) + '</strong>';
                                    if (producto.codigo_producto) {
                                        mensaje += ' (Código: ' + producto.codigo_producto + ')';
                                    }
                                    mensaje += '</li>';
                                });
                                
                                if (respuesta.total_productos > respuesta.productos.length) {
                                    mensaje += '<li>... y ' + (respuesta.total_productos - respuesta.productos.length) + ' productos más</li>';
                                }
                                
                                mensaje += '</ul>';
                                
                                mensaje += '<br><strong>Acción requerida:</strong><br>';
                                mensaje += '1. Elimine todos los productos de esta categoría, o<br>';
                                mensaje += '2. Reasigne los productos a otra categoría antes de eliminar esta.';
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Eliminación negada',
                                html: mensaje,
                                width: '700px',
                                customClass: {
                                    popup: 'scrollable-swal'
                                }
                            });
                        } else {
                            Swal.fire('Error', respuesta.message || 'Error al eliminar la categoría', 'error');
                        }
                    }
                });
            }
        });
    });

    function eliminarFilaCategoria(id_categoria) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_categoria}"]`);
        tabla.row(fila).remove().draw();
    }

    // FUNCIONES PARA MANEJO DE CARACTERÍSTICAS
    document.addEventListener('DOMContentLoaded', () => {
        const contenedor = document.getElementById('caracteristicasContainer');
        const btnAgregar = document.getElementById('agregarCaracteristica');

        let contador = 0;
        const maxCaracteristicas = 5;

        const crearInputCaracteristica = (id, puedeEliminar = true) => {
            const div = document.createElement('div');
            div.classList.add('caracteristica-item');
            div.dataset.index = id;

            div.innerHTML = `
                <input type="text" name="caracteristicas[${id}][nombre]" placeholder="Nombre" class="form-control" maxlength="20" required>
                <select name="caracteristicas[${id}][tipo]" class="form-select tipo-caracteristica" required>
                    <option value="" disable hidden>Tipo</option>
                    <option value="int">Entero</option>
                    <option value="float">Decimal</option>
                    <option value="string">Texto</option>
                </select>
                <input type="number" name="caracteristicas[${id}][max]" placeholder="Máx. caracteres" class="form-control max-caracteres" min="1" max="255" style="display:none;">
                ${puedeEliminar ? `<button type="button" class="btn btn-danger btn-eliminar-caracteristicas">✖</button>` : ''}
            `;

            // Mostrar/ocultar el campo de max según el tipo
            const selectTipo = div.querySelector('.tipo-caracteristica');
            const inputMax = div.querySelector('.max-caracteres');
            selectTipo.addEventListener('change', function() {
                if (this.value === 'string') {
                    inputMax.style.display = '';
                    inputMax.required = true;
                } else {
                    inputMax.style.display = 'none';
                    inputMax.value = '';
                    inputMax.required = false;
                }
            });

            if (puedeEliminar) {
                div.querySelector('.btn-eliminar-caracteristicas').addEventListener('click', () => {
                    contenedor.removeChild(div);
                    // Recalcular total actual tras eliminar y actualizar botón
                    const total = contenedor.querySelectorAll('.caracteristica-item').length;
                    btnAgregar.disabled = (total >= maxCaracteristicas);
                });
            }

            contenedor.appendChild(div);
        };

        // Agrega una característica inicial no eliminable
        crearInputCaracteristica(contador++, false);

        btnAgregar.addEventListener('click', () => {
            // Recalcular contador según elementos actuales para soportar resets
            contador = contenedor.querySelectorAll('.caracteristica-item').length;
            if (contador < maxCaracteristicas) {
                crearInputCaracteristica(contador++);
            }
            // Actualizar estado del botón según el total actual
            const total = contenedor.querySelectorAll('.caracteristica-item').length;
            btnAgregar.disabled = (total >= maxCaracteristicas);
        });
    });

    // Utilidad para crear inputs de características en el modal de modificar
    function crearInputCaracteristicaMod(id, nombre = '', tipo = '', max = '', puedeEliminar = true) {
        const div = document.createElement('div');
        div.classList.add('caracteristica-item');
        div.dataset.index = id;

        div.innerHTML = `
            <input type="text" name="caracteristicas[${id}][nombre]" placeholder="Nombre" class="form-control" maxlength="20" required value="${nombre}">
            <select name="caracteristicas[${id}][tipo]" class="form-select tipo-caracteristica" required>
                <option value="" disable hidden>Tipo</option>
                <option value="int" ${tipo === 'int' ? 'selected' : ''}>Entero</option>
                <option value="float" ${tipo === 'float' ? 'selected' : ''}>Decimal</option>
                <option value="string" ${tipo === 'string' ? 'selected' : ''}>Texto</option>
            </select>
            <input type="number" name="caracteristicas[${id}][max]" placeholder="Máx. caracteres" class="form-control max-caracteres" min="1" max="255" value="${tipo === 'string' ? max : ''}" style="${tipo === 'string' ? '' : 'display:none;'}">
            ${puedeEliminar ? `<button type="button" class="btn btn-danger btn-eliminar-caracteristicas">✖</button>` : ''}
        `;

        // Mostrar/ocultar el campo de max según el tipo
        const selectTipo = div.querySelector('.tipo-caracteristica');
        const inputMax = div.querySelector('.max-caracteres');
        selectTipo.addEventListener('change', function() {
            if (this.value === 'string') {
                inputMax.style.display = '';
                inputMax.required = true;
            } else {
                inputMax.style.display = 'none';
                inputMax.value = '';
                inputMax.required = false;
            }
        });

        if (puedeEliminar) {
            div.querySelector('.btn-eliminar-caracteristicas').addEventListener('click', () => {
                div.parentNode.removeChild(div);
                // Recalcular total actual tras eliminar y actualizar botón
                const contenedor = document.getElementById('modificar_caracteristicasContainer');
                modificarContador = contenedor.querySelectorAll('.caracteristica-item').length;
                if (modificarBtnAgregar) {
                    modificarBtnAgregar.disabled = (modificarContador >= modificarMaxCaracteristicas);
                }
            });
        }

        return div;
    }

    let modificarContador = 0;
    const modificarMaxCaracteristicas = 5;
    let modificarBtnAgregar = null;

    // Abrir modal de modificar y cargar datos
    $(document).on('click', '.btn-modificar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        // Limpiar el formulario
        $('#modificarCategoria')[0].reset();
        $('#modificar_id_categoria').val(id);
        $('#modificar_nombre_categoria').val(nombre);
        $('#smnombre_categoria').text('');

        // Limpiar contenedor de características
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        contenedor.innerHTML = '';
        modificarContador = 0;

        // AJAX para obtener datos de la categoría y sus características
        const datos = new FormData();
        datos.append('accion', 'obtener_categoria');
        datos.append('id_categoria', id);

        // Mostrar loading
        $('#modificarCategoriaModal').modal('show');
        $('#modificarCategoriaModal .modal-body').append('<div id="loading" class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Cargando...</div>');

        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (categoria) {
                // Ocultar loading
                $('#loading').remove();
                
                // Establecer valores del formulario
                $('#modificar_nombre_categoria').val(categoria.nombre_categoria);

                // Limpiar contenedor primero
                contenedor.innerHTML = '';
                
                // Cargar características si existen
                if (categoria.caracteristicas && categoria.caracteristicas.length > 0) {
                    categoria.caracteristicas.forEach((carac, index) => {
                        // Solo permitir eliminar si no es la primera característica
                        const puedeEliminar = index > 0;
                        const div = crearInputCaracteristicaMod(
                            modificarContador++,
                            carac.nombre || '',
                            carac.tipo || 'string',
                            carac.max || '255',
                            puedeEliminar
                        );
                        contenedor.appendChild(div);
                    });
                } else {
                    // Si no hay características, agrega una vacía no eliminable
                    contenedor.appendChild(crearInputCaracteristicaMod(modificarContador++, '', '', '', false));
                }

                // Habilitar o deshabilitar el botón de agregar
                modificarBtnAgregar = document.getElementById('modificar_agregarCaracteristica');
                modificarBtnAgregar.disabled = modificarContador >= modificarMaxCaracteristicas;

                // Limpiar estados visuales y ajustar visibilidad/attrs de max según tipo
                $('#modificar_caracteristicasContainer .caracteristica-item input, #modificar_caracteristicasContainer .caracteristica-item select')
                    .removeClass('is-valid is-invalid');
                $('#modificar_caracteristicasContainer .caracteristica-item').each(function(){
                    const $item = $(this);
                    const $tipo = $item.find('select[name*="[tipo]"]');
                    const $max = $item.find('input[name*="[max]"]');
                    if ($tipo.val() === 'string') {
                        $max.css('display','').prop('required', true).attr({min:1, max:100, maxlength:3, step:1});
                    } else {
                        $max.val('').css('display','none').prop('required', false);
                    }
                });

                $('#modificarCategoriaModal').modal('show');
            },
            error: function () {
                Swal.fire('Error', 'No se pudo obtener la categoría.', 'error');
            }
        });
    });

    // Agregar característica en el modal de modificar
    $(document).on('click', '#modificar_agregarCaracteristica', function () {
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        // Recalcular contador desde el DOM para soportar cierres/resets
        modificarContador = contenedor.querySelectorAll('.caracteristica-item').length;
        if (modificarContador < modificarMaxCaracteristicas) {
            contenedor.appendChild(crearInputCaracteristicaMod(modificarContador++));
        }
        // Actualizar estado del botón
        const total = contenedor.querySelectorAll('.caracteristica-item').length;
        this.disabled = (total >= modificarMaxCaracteristicas);
    });

    // Al mostrar el modal de modificar, limpiar estados visuales
    $('#modificarCategoriaModal').on('shown.bs.modal', function(){
        $('#modificar_caracteristicasContainer .caracteristica-item input, #modificar_caracteristicasContainer .caracteristica-item select')
            .removeClass('is-valid is-invalid');
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        modificarContador = contenedor ? contenedor.querySelectorAll('.caracteristica-item').length : 0;
        if (modificarBtnAgregar) {
            modificarBtnAgregar.disabled = (modificarContador >= modificarMaxCaracteristicas);
        }
        // Ajustar visibilidad de max según tipo y setear attrs si corresponde
        $('#modificar_caracteristicasContainer .caracteristica-item').each(function(){
            const $item = $(this);
            const $tipo = $item.find('select[name*="[tipo]"]');
            const $max = $item.find('input[name*="[max]"]');
            if ($tipo.val() === 'string') {
                $max.css('display','').prop('required', true).attr({min:1, max:100, maxlength:3, step:1});
            } else {
                $max.val('').css('display','none').prop('required', false);
            }
        });
    });

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

    function cargarYMostrarModalAyuda(contexto = null) {
        if (!$('link[href*="ayuda/css/modal.css"]').length) {
            $('<link>')
                .attr({
                    rel: 'stylesheet',
                    type: 'text/css',
                    href: 'assets/public/ayuda/css/modal.css'
                })
                .appendTo('head');
        }

        if (modalAyudaInstance) {
            modalAyudaInstance.openModal();
            if (contexto) {
                setTimeout(() => {
                    const slideIndex = modalAyudaInstance.mapeoContextos && modalAyudaInstance.mapeoContextos[contexto];
                    if (slideIndex !== undefined) {
                        modalAyudaInstance.goToSlide(slideIndex);
                    }
                }, 300);
            }
            return;
        }

        $.get('assets/public/ayuda/categorias.php')
            .done(function(html) {
                if (!$('#modalAyuda').length) {
                    $('body').append(html);
                }

                $.getScript('assets/public/ayuda/js/modal.js')
                    .done(function() {
                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
                            modalAyudaInstance.openModal();

                            if (contexto) {
                                setTimeout(() => {
                                    const slideIndex = modalAyudaInstance.mapeoContextos && modalAyudaInstance.mapeoContextos[contexto];
                                    if (slideIndex !== undefined) {
                                        modalAyudaInstance.goToSlide(slideIndex);
                                    }
                                }, 300);
                            }
                        } else {
                            console.error('La función inicializarModalAyudaUsuario no está disponible');
                        }
                    })
                    .fail(function() {
                        console.error('Error al cargar el JavaScript del modal de ayuda');
                    });
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

    $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
        e.preventDefault();
        cargarYMostrarModalAyuda();
    });

    $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        cargarYMostrarModalAyuda(contexto);
    });
});
