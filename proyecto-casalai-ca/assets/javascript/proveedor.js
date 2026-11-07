$(document).ready(function () {

    // Función para manejar errores específicos del backend
    function manejarErroresBackend(respuesta, esModificacion = false) {
        if (respuesta.field_errors && typeof respuesta.field_errors === 'object') {
            // Limpiar todos los spans de error primero
            if (esModificacion) {
                $("#smnombre_proveedor, #smrif_proveedor, #smnombre_representante, #smrif_representante, #scorreo_proveedor, #smdireccion_proveedor, #smtelefono_1, #smtelefono_2, #smobservacion").text('');
            } else {
                $("#snombre_proveedor, #srif_proveedor, #snombre_representante, #srif_representante, #scorreo_proveedor, #sdireccion_proveedor, #stelefono_1, #stelefono_2, #sobservacion").text('');
            }

            // Mapeo de campos del backend a spans del frontend
            const mapeoCampos = esModificacion ? {
                'nombre_proveedor': '#smnombre_proveedor',
                'rif_proveedor': '#smrif_proveedor', 
                'nombre_representante': '#smnombre_representante',
                'rif_representante': '#smrif_representante',
                'correo_proveedor': '#scorreo_proveedor',
                'direccion_proveedor': '#smdireccion_proveedor',
                'telefono_1': '#smtelefono_1',
                'telefono_2': '#smtelefono_2',
                'observacion': '#smobservacion'
            } : {
                'nombre_proveedor': '#snombre_proveedor',
                'rif_proveedor': '#srif_proveedor',
                'nombre_representante': '#snombre_representante', 
                'rif_representante': '#srif_representante',
                'correo_proveedor': '#scorreo_proveedor',
                'direccion_proveedor': '#sdireccion_proveedor',
                'telefono_1': '#stelefono_1',
                'telefono_2': '#stelefono_2',
                'observacion': '#sobservacion'
            };

            // Mensajes personalizados para errores comunes
            const mensajesPersonalizados = {
                'El nombre del proveedor ya existe': '*Este nombre de proveedor ya está registrado*',
                'El RIF del proveedor ya está registrado': '*Este RIF ya está registrado*',
                'El RIF del representante ya está registrado': '*Este RIF de representante ya está registrado*',
                'El nombre del proveedor debe tener entre 2 y 200 caracteres': '*El nombre debe tener entre 2 y 200 caracteres*',
                'El RIF debe tener entre 5 y 20 caracteres': '*El RIF debe tener entre 5 y 20 caracteres*',
                'El nombre del representante debe tener entre 2 y 200 caracteres': '*El nombre debe tener entre 2 y 200 caracteres*',
                'El RIF del representante debe tener entre 5 y 20 caracteres': '*El RIF debe tener entre 5 y 20 caracteres*',
                'El correo no debe exceder los 255 caracteres': '*El correo es demasiado largo*',
                'El correo debe tener formato usuario@dominio.extensión': '*Formato de correo inválido*',
                'El teléfono debe tener entre 7 y 20 caracteres': '*El teléfono debe tener entre 7 y 20 caracteres*',
                'La dirección no debe exceder los 500 caracteres': '*La dirección es demasiado larga*',
                'La observación no debe exceder los 1000 caracteres': '*La observación es demasiado larga*'
            };

            // Iterar sobre los errores y mostrarlos en los spans correspondientes
            Object.keys(respuesta.field_errors).forEach(function(campo) {
                const spanSelector = mapeoCampos[campo];
                const mensajeError = respuesta.field_errors[campo];
                
                if (spanSelector) {
                    // Buscar mensaje personalizado
                    let mensajeMostrar = mensajeError;
                    Object.keys(mensajesPersonalizados).forEach(function(errorOriginal) {
                        if (mensajeError.includes(errorOriginal)) {
                            mensajeMostrar = mensajesPersonalizados[errorOriginal];
                        }
                    });
                    
                    $(spanSelector).text(mensajeMostrar);
                }
            });

            // Mostrar mensaje general solo si hay errores
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Por favor, corrija los campos marcados',
                showConfirmButton: true
            });
        } else {
            // Si no hay errores específicos, mostrar el mensaje genérico
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: respuesta.message || 'Error en la validación de datos'
            });
        }
    }

    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaProveedores;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaProveedores = $tabla.DataTable({
                language: {
                    url: 'assets/public/js/es-ES.json'
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
                    if ($filter.find('#btnIncluirProveedor').length) return;

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
                        id: 'btnIncluirProveedor',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Proveedor'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaProveedores = $tabla.DataTable();
        }

        // Filtro de estatus (Todos / habilitado / inhabilitado)
        var estatusColIndex = $tabla.find('thead th').filter(function () {
            return $(this).text().trim().toLowerCase() === 'estatus';
        }).index();
        if (estatusColIndex < 0) estatusColIndex = 6;

        if (!$tabla.data('estatusFilterAdded')) {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (!settings.nTable || settings.nTable !== $tabla.get(0)) return true;
                var desired = ($tabla.data('estatusFilterValue') || 'todos').toLowerCase();
                if (desired === 'todos') return true;
                var row = settings.aoData && settings.aoData[dataIndex];
                var cell = row && row.anCells ? row.anCells[estatusColIndex] : null;
                var text = cell ? $(cell).text().trim().toLowerCase() : '';
                return text === desired;
            });
            $tabla.data('estatusFilterAdded', true);
        }

        var $filtroEstatus = $('#filtro-estatus-proveedores');
        if ($filtroEstatus.length) {
            $filtroEstatus.off('change.estatus').on('change.estatus', function () {
                var val = ($(this).val() || 'todos').toLowerCase();
                $tabla.data('estatusFilterValue', val);
                if (!tablaProveedores) {
                    tablaProveedores = $tabla.DataTable();
                }
                tablaProveedores.draw();
            });

            $tabla.data('estatusFilterValue', ($filtroEstatus.val() || 'todos').toLowerCase());
            if (!tablaProveedores) {
                tablaProveedores = $tabla.DataTable();
            }
            tablaProveedores.draw();
        }
    }

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    $("#nombre_proveedor").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]*$/, e);
        let nombre_p = document.getElementById("nombre_proveedor");
        nombre_p.value = space(nombre_p.value);
    });
    $("#nombre_proveedor").on("keyup", function () {
        validarKeyUp(
        /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
        $(this),
        $("#snombre_proveedor"),
        "*Solo letras, de 2 a 50 caracteres*"
        );
    });

    $("#rif_proveedor").on("keypress", function(e){
        validarKeyPress(/^[vejpg0-9-\b]*$/i, e);
    });
    $("#rif_proveedor").on("keyup", function(){
        validarKeyUp(
            /^[VEJPG]-\d{8}-\d$/,
            $(this),
            $("#srif_proveedor"),
            "*Formato válido: (VEJPG)-12345678-9*"
        );
    });
    $("#rif_proveedor").on("input", function() {
        let valor_rp = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');

        let resultado_rp = '';
        if (valor_rp.length > 0) {
            let letra_rp = valor_rp.charAt(0);
            if ('VEJPG'.includes(letra_rp)) {
                resultado_rp = letra_rp;
            } else {
                resultado_rp = '';
            }

            let numeros_rp = valor_rp.substring(1).replace(/\D/g, '');

            if (numeros_rp.length > 0) {
                resultado_rp += '-' + numeros_rp.substring(0, 8);
                if (numeros_rp.length > 8) {
                    resultado_rp += '-' + numeros_rp.substring(8, 9);
                }
            }
        }
        $(this).val(resultado_rp);
    });

    $("#nombre_representante").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]*$/, e);
        let nombre_r = document.getElementById("nombre_representante");
        nombre_r.value = space(nombre_r.value);
    });
    $("#nombre_representante").on("keyup", function () {
        validarKeyUp(
        /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
        $(this),
        $("#snombre_representante"),
        "*Solo letras, de 2 a 50 caracteres*"
        );
    });

    $("#rif_representante").on("keypress", function(e){
        validarKeyPress(/^[VEJPG0-9-\b]*$/i, e);
    });
    $("#rif_representante").on("keyup", function(){
        validarKeyUp(
            /^[VEJPG]-\d{8}-\d$/,
            $(this),
            $("#srif_representante"),
            "*Formato válido: (VEJPG)-12345678-9*"
        );
    });
    $("#rif_representante").on("input", function() {
        let valor_rr = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');

        let resultado_rr = '';
        if (valor_rr.length > 0) {
            let letra_rr = valor_rr.charAt(0);
            if ('VEJPG'.includes(letra_rr)) {
                resultado_rr = letra_rr;
            } else {
                resultado_rr = '';
            }

            let numeros_rr = valor_rr.substring(1).replace(/\D/g, '');

            if (numeros_rr.length > 0) {
                resultado_rr += '-' + numeros_rr.substring(0, 8);
                if (numeros_rr.length > 8) {
                    resultado_rr += '-' + numeros_rr.substring(8, 9);
                }
            }
        }
        $(this).val(resultado_rr);
    });

    $("#correo_proveedor").on("keypress", function (e) {
        validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
    });
    $("#correo_proveedor").on("keyup", function(){
        validarKeyUp(
            /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
            $(this),
            $("#scorreo_proveedor"),
            "*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*"
        );
    });

    $("#direccion_proveedor").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,.-\s\b]*$/, e);
        let direccion = document.getElementById("direccion_proveedor");
        direccion.value = space(direccion.value);
    });
    $("#direccion_proveedor").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,.-\s\b]{2,100}$/,
            $(this),
            $("#sdireccion_proveedor"),
            "*El formato permite letras y números*"
        );
    });

    $("#telefono_1").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#telefono_1").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono_1"),
            "*Formato válido: 0200-000-0000*"
        );
    });
    $("#telefono_1").on("input", function() {
        let valor_t1 = $(this).val().replace(/\D/g, '');
        if(valor_t1.length > 4 && valor_t1.length <= 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
        else if(valor_t1.length > 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
        $(this).val(valor_t1);
    });

    $("#telefono_2").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#telefono_2").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono_2"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#telefono_2").on("input", function() {
        let valor_t2 = $(this).val().replace(/\D/g, '');
        if(valor_t2.length > 4 && valor_t2.length <= 7)
            valor_t2 = valor_t2.slice(0,4) + '-' + valor_t2.slice(4);
        else if(valor_t2.length > 7)
            valor_t2 = valor_t2.slice(0,4) + '-' + valor_t2.slice(4,7) + '-' + valor_t2.slice(7,11);
        $(this).val(valor_t2);
    });

    $("#observacion").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,.-\s\b]*$/, e);
        let observacion = document.getElementById("observacion");
        observacion.value = space(observacion.value);
    });
    $("#observacion").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,.-\s\b]{2,100}$/,
            $(this),
            $("#sobservacion"),
            "*El formato permite letras y números*"
        );
    });

function verificarPermisosEnTiempoRealProveedores() {
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
            $('#btnIncluirProveedor').show();
        } else {
            $('#btnIncluirProveedor').hide();
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
    verificarPermisosEnTiempoRealProveedores();
    setInterval(verificarPermisosEnTiempoRealProveedores, 10000); // 10 segundos
});

    function validarEnvioProveedor(){
        // Nombre del proveedor
        let nombre_p = $("#nombre_proveedor");
        nombre_p.val(space(nombre_p.val()).trim());
        const nombreProvVal = nombre_p.val();
        if (nombreProvVal === "") {
            $("#snombre_proveedor").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el nombre del proveedor','El campo está vacío.');
            return false;
        }
        if (nombreProvVal.length < 2) {
            $("#snombre_proveedor").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre del proveedor','Debe tener mínimo 2 caracteres.');
            return false;
        }

        // RIF del proveedor
        let rif_p = $("#rif_proveedor");
        const rifProvVal = rif_p.val().trim();
        if (rifProvVal === "") {
            $("#srif_proveedor").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el RIF del proveedor','El campo está vacío.');
            return false;
        }
        if (!/^[VEJPG]-\d{8}-\d$/.test(rifProvVal)) {
            $("#srif_proveedor").text("*Formato válido: (VEJPG)-12345678-9*");
            mensajes('error','Verifique el RIF del proveedor','Formato inválido.');
            return false;
        }

        // Nombre del representante
        let nombre_r = $("#nombre_representante");
        nombre_r.val(space(nombre_r.val()).trim());
        const nombreRepVal = nombre_r.val();
        if (nombreRepVal === "") {
            $("#snombre_representante").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el nombre del representante','El campo está vacío.');
            return false;
        }
        if (nombreRepVal.length < 2) {
            $("#snombre_representante").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre del representante','Debe tener mínimo 2 caracteres.');
            return false;
        }

        // RIF del representante
        let rif_r = $("#rif_representante");
        const rifRepVal = rif_r.val().trim();
        if (rifRepVal === "") {
            $("#srif_representante").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el RIF del representante','El campo está vacío.');
            return false;
        }
        if (!/^[VEJPG]-\d{8}-\d$/.test(rifRepVal)) {
            $("#srif_representante").text("*Formato válido: (VEJPG)-12345678-9*");
            mensajes('error','Verifique el RIF del representante','Formato inválido.');
            return false;
        }

        // Correo
        let correo = $("#correo_proveedor");
        const correoVal = correo.val().trim();
        if (correoVal === "") {
            $("#scorreo_proveedor").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el correo electrónico','El campo está vacío.');
            return false;
        }
        if (!/^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/.test(correoVal)) {
            $("#scorreo_proveedor").text("*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*");
            mensajes('error','Verifique el correo electrónico','Formato inválido.');
            return false;
        }

        // Dirección
        let direccion = $("#direccion_proveedor");
        direccion.val(space(direccion.val()).trim());
        const dirVal = direccion.val();
        if (dirVal === "") {
            $("#sdireccion_proveedor").text("*Este campo es obligatorio*");
            mensajes('error','Verifique la dirección','El campo está vacío.');
            return false;
        }
        if (dirVal.length < 2) {
            $("#sdireccion_proveedor").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique la dirección','Debe tener mínimo 2 caracteres.');
            return false;
        }

        // Teléfono principal
        let tel1 = $("#telefono_1");
        const tel1Val = tel1.val().trim();
        if (tel1Val === "") {
            $("#stelefono_1").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el teléfono principal','El campo está vacío.');
            return false;
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(tel1Val)) {
            $("#stelefono_1").text("*Formato válido: 0200-000-0000*");
            mensajes('error','Verifique el teléfono principal','Formato inválido.');
            return false;
        }

        // Teléfono secundario
        let tel2 = $("#telefono_2");
        const tel2Val = tel2.val().trim();
        if (tel2Val === "") {
            $("#stelefono_2").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el teléfono secundario','El campo está vacío.');
            return false;
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(tel2Val)) {
            $("#stelefono_2").text("*Formato válido: 0400-000-0000*");
            mensajes('error','Verifique el teléfono secundario','Formato inválido.');
            return false;
        }

        // Observación
        let observacion = $("#observacion");
        observacion.val(space(observacion.val()).trim());
        const obsVal = observacion.val();
        if (obsVal === "") {
            $("#sobservacion").text("*Este campo es obligatorio*");
            mensajes('error','Verifique la observación','El campo está vacío.');
            return false;
        }
        if (obsVal.length < 2) {
            $("#sobservacion").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique la observación','Debe tener mínimo 2 caracteres.');
            return false;
        }

        return true;
    }

    function agregarFilaProveedor(proveedor) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-nombres">${proveedor.nombre_proveedor}</span>`,
            `<span class="campo-tex-num">${proveedor.rif_proveedor}</span>`,
            `<span class="campo-nombres">${proveedor.nombre_representante}</span>`,
            `<span class="campo-tex-num">${proveedor.correo_proveedor}</span>`,
            `<span class="campo-nombres">${proveedor.direccion_proveedor}</span>`,
            `<span class="campo-numeros">${proveedor.telefono_1}</span>`,
            `<span class="campo-estatus ${proveedor.estado === "habilitado" ? "habilitado" : "inhabilitado"}"
                data-id="${proveedor.id_proveedor}" 
                style="cursor: pointer;">
                ${proveedor.estado}
            </span>`,
            `<ul>
                <button class="btn-detalle" title="Detallar"
                    data-id="${proveedor.id_proveedor}"
                    data-nombre_proveedor="${proveedor.nombre_proveedor}"
                    data-rif_proveedor="${proveedor.rif_proveedor}"
                    data-nombre_representante="${proveedor.nombre_representante}"
                    data-rif_representante="${proveedor.rif_representante || ''}"
                    data-correo_proveedor="${proveedor.correo_proveedor}"
                    data-direccion_proveedor="${proveedor.direccion_proveedor}"
                    data-telefono_1="${proveedor.telefono_1}"
                    data-telefono_2="${proveedor.telefono_2 || ''}"
                    data-observacion="${proveedor.observacion || ''}">
                    <img src="assets/img/eye.svg">
                </button>
                <button class="btn-modificar" id="btnModificarProveedor"
                    data-id="${proveedor.id_proveedor}"
                    data-nombre-proveedor="${proveedor.nombre_proveedor}"
                    data-rif-proveedor="${proveedor.rif_proveedor}"
                    data-nombre-representante="${proveedor.nombre_representante}"
                    data-rif-representante="${proveedor.rif_representante || ''}"
                    data-correo-proveedor="${proveedor.correo_proveedor}"
                    data-direccion-proveedor="${proveedor.direccion_proveedor}"
                    data-telefono-1="${proveedor.telefono_1}"
                    data-telefono-2="${proveedor.telefono_2 || ''}"
                    data-observacion="${proveedor.observacion || ''}">
                    <img src="assets/img/pencil.svg">
                </button>
                <button class="btn-eliminar" data-id="${proveedor.id_proveedor}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', proveedor.id_proveedor);
    }

    function resetProveedor() {
        $("#nombre_proveedor").val('');
        $("#rif_proveedor").val('');
        $("#nombre_representante").val('');
        $("#rif_representante").val('');
        $("#correo_proveedor").val('');
        $("#direccion_proveedor").val('');
        $("#telefono_1").val('');
        $("#telefono_2").val('');
        $("#observacion").val('');
        $("#snombre_proveedor").text('');
        $("#srif_proveedor").text('');
        $("#snombre_representante").text('');
        $("#srif_representante").text('');
        $("#scorreo_proveedor").text('');
        $("#sdireccion_proveedor").text('');
        $("#stelefono_1").text('');
        $("#stelefono_2").text('');
        $("#sobservacion").text('');
    }

    $(document).on('click', '#btnIncluirProveedor', function () {
        $('#incluirproveedor')[0].reset();
        $("#snombre_proveedor").text('');
        $("#srif_proveedor").text('');
        $("#snombre_representante").text('');
        $("#srif_representante").text('');
        $("#scorreo_proveedor").text('');
        $("#sdireccion_proveedor").text('');
        $("#stelefono_1").text('');
        $("#stelefono_2").text('');
        $("#sobservacion").text('');
        $('#registrarProveedorModal').modal('show');
    });

    $('#incluirproveedor').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioProveedor()){
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || respuesta.msg || 'Proveedor registrado correctamente'
                    });
                    agregarFilaProveedor(respuesta.proveedor);
                    resetProveedor();
                } else {
                    manejarErroresBackend(respuesta, false);
                }
            });
        }
    });

    $(document).on('click', '#registrarProveedorModal .close', function() {
        $('#registrarProveedorModal').modal('hide');
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

    $("#modificar_nombre_proveedor").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s]*$/, e);
        let nombre_p = document.getElementById("modificar_nombre_proveedor");
        nombre_p.value = space(nombre_p.value);
    });
    $("#modificar_nombre_proveedor").on("keyup", function () {
        validarKeyUp(
        /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s]{2,50}$/,
        $(this),
        $("#smnombre_proveedor"),
        "*Solo letras, de 2 a 50 caracteres*"
        );
    });

    $("#modificar_rif_proveedor").on("keypress", function(e){
        validarKeyPress(/^[vejpg0-9-\b]*$/i, e);
    });
    $("#modificar_rif_proveedor").on("keyup", function(){
        validarKeyUp(
            /^[VEJPG]-\d{8}-\d$/,
            $(this),
            $("#smrif_proveedor"),
            "*Formato válido: J-12345678-9*"
        );
    });
    $("#modificar_rif_proveedor").on("input", function() {
        let valor_rp = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');

        let resultado_rp = '';
        if (valor_rp.length > 0) {
            let letra_rp = valor_rp.charAt(0);
            if ('VEJPG'.includes(letra_rp)) {
                resultado_rp = letra_rp;
            } else {
                resultado_rp = '';
            }

            let numeros_rp = valor_rp.substring(1).replace(/\D/g, '');

            if (numeros_rp.length > 0) {
                resultado_rp += '-' + numeros_rp.substring(0, 8);
                if (numeros_rp.length > 8) {
                    resultado_rp += '-' + numeros_rp.substring(8, 9);
                }
            }
        }
        $(this).val(resultado_rp);
    });

    $("#modificar_nombre_representante").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]*$/, e);
        let nombre_r = document.getElementById("modificar_nombre_representante");
        nombre_r.value = space(nombre_r.value);
    });
    $("#modificar_nombre_representante").on("keyup", function () {
        validarKeyUp(
        /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
        $(this),
        $("#smnombre_representante"),
        "*Solo letras, de 2 a 50 caracteres*"
        );
    });

    $("#modificar_rif_representante").on("keypress", function(e){
        validarKeyPress(/^[VEJPG0-9-\b]*$/i, e);
    });
    $("#modificar_rif_representante").on("keyup", function(){
        validarKeyUp(
            /^[VEJPG]-\d{8}-\d$/,
            $(this),
            $("#smrif_representante"),
            "*Formato válido: J-12345678-9*"
        );
    });
    $("#modificar_rif_representante").on("input", function() {
        let valor_rr = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');

        let resultado_rr = '';
        if (valor_rr.length > 0) {
            let letra_rr = valor_rr.charAt(0);
            if ('VEJPG'.includes(letra_rr)) {
                resultado_rr = letra_rr;
            } else {
                resultado_rr = '';
            }

            let numeros_rr = valor_rr.substring(1).replace(/\D/g, '');

            if (numeros_rr.length > 0) {
                resultado_rr += '-' + numeros_rr.substring(0, 8);
                if (numeros_rr.length > 8) {
                    resultado_rr += '-' + numeros_rr.substring(8, 9);
                }
            }
        }
        $(this).val(resultado_rr);
    });

    $("#modificar_correo_proveedor").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZñÑ_0-9@,.\b]*$/, e);
    });
    $("#modificar_correo_proveedor").on("keyup", function(){
        validarKeyUp(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            $(this),
            $("#scorreo_proveedor"),
            "*Formato válido: ejemplo@gmail.com*"
        );
    });

    $("#modificar_direccion_proveedor").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,.-\s\b]*$/, e);
        let direccion = document.getElementById("modificar_direccion_proveedor");
        direccion.value = space(direccion.value);
    });
    $("#modificar_direccion_proveedor").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,.-\s\b]{2,100}$/,
            $(this),
            $("#smdireccion_proveedor"),
            "*El formato permite letras y números*"
        );
    });

    $("#modificar_telefono_1").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#modificar_telefono_1").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#smtelefono_1"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#modificar_telefono_1").on("input", function() {
        let valor_t1 = $(this).val().replace(/\D/g, '');
        if(valor_t1.length > 4 && valor_t1.length <= 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
        else if(valor_t1.length > 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
        $(this).val(valor_t1);
    });

    $("#modificar_telefono_2").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#modificar_telefono_2").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#smtelefono_2"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#modificar_telefono_2").on("input", function() {
        let valor_t2 = $(this).val().replace(/\D/g, '');
        if(valor_t2.length > 4 && valor_t2.length <= 7)
            valor_t2 = valor_t2.slice(0,4) + '-' + valor_t2.slice(4);
        else if(valor_t2.length > 7)
            valor_t2 = valor_t2.slice(0,4) + '-' + valor_t2.slice(4,7) + '-' + valor_t2.slice(7,11);
        $(this).val(valor_t2);
    });

    $("#modificar_observacion").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let observacion = document.getElementById("modificar_observacion");
        observacion.value = space(observacion.value);
    });
    $("#modificar_observacion").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/,
            $(this),
            $("#smobservacion"),
            "*El formato permite letras y números*"
        );
    });

    function validarProveedor(datos) {
        let errores = [];
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s]{2,50}$/.test(datos.nombre_proveedor)) {
            errores.push("El nombre debe tener letras y/o números.");
        }
        if (!/^[VEJPG]-\d{8}-\d$/.test(datos.rif_proveedor)) {
            errores.push("Formato válido: (VEJPG)-12345678-9.");
        }
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/.test(datos.nombre_representante)) {
            errores.push("El nombre debe tener solo letras.");
        }
        if (!/^[VEJPG]-\d{8}-\d$/.test(datos.rif_representante)) {
            errores.push("Formato válido: (VEJPG)-12345678-9.");
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(datos.correo_proveedor)) {
            errores.push("Formato válido: ejemplo@gmail.com.");
        }
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/.test(datos.direccion_proveedor)) {
            errores.push("La dirección debe tener letras y/o números.");
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(datos.telefono_1)) {
            errores.push("Formato válido: 0200-000-0000.");
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(datos.telefono_2)) {
            errores.push("Formato válido: 0400-000-0000.");
        }
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/.test(datos.observacion)) {
            errores.push("La observación debe tener letras y/o números.");
        }
        return errores;
    }

    $(document).on('click', '#btnModificarProveedor', function () {
        $('#modificar_id_proveedor').val($(this).data('id'));
        $('#modificar_nombre_proveedor').val($(this).data('nombre-proveedor'));
        $('#modificar_rif_proveedor').val($(this).data('rif-proveedor'));
        $('#modificar_nombre_representante').val($(this).data('nombre-representante'));
        $('#modificar_rif_representante').val($(this).data('rif-representante'));
        $('#modificar_correo_proveedor').val($(this).data('correo-proveedor'));
        $('#modificar_direccion_proveedor').val($(this).data('direccion-proveedor'));
        $('#modificar_telefono_1').val($(this).data('telefono-1'));
        $('#modificar_telefono_2').val($(this).data('telefono-2'));
        $('#modificar_observacion').val($(this).data('observacion'));
        
        $('#smnombre_proveedor').text('');
        $('#smrif_proveedor').text('');
        $('#smnombre_representante').text('');
        $('#smrif_representante').text('');
        $('#smcorreo_proveedor').text('');
        $('#smdireccion_proveedor').text('');
        $('#smtelefono_1').text('');
        $('#smtelefono_2').text('');
        $('#smobservacion').text('');
        $('#modificarProveedorModal').modal('show');
    });

    $('#FormModificarProveedor').on('submit', function(e) {
        e.preventDefault();

        const datos = {
            nombre_proveedor: $('#modificar_nombre_proveedor').val(),
            rif_proveedor: $('#modificar_rif_proveedor').val(),
            nombre_representante: $('#modificar_nombre_representante').val(),
            rif_representante: $('#modificar_rif_representante').val(),
            correo_proveedor: $('#modificar_correo_proveedor').val(),
            direccion_proveedor: $('#modificar_direccion_proveedor').val(),
            telefono_1: $('#modificar_telefono_1').val(),
            telefono_2: $('#modificar_telefono_2').val(),
            observacion: $('#modificar_observacion').val()
        };

        const errores = validarProveedor(datos);

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
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modificarProveedorModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'El proveedor se ha modificado correctamente'
                    });

                    const tabla = $("#tablaConsultas").DataTable();
                    const id = $("#modificar_id_proveedor").val();
                    const fila = tabla.row(`tr[data-id="${id}"]`);
                    const proveedor = response.proveedor;

                    if (fila.length) {
                        fila.data([
                            `<span class="campo-nombres">${proveedor.nombre_proveedor}</span>`,
                            `<span class="campo-tex-num">${proveedor.rif_proveedor}</span>`,
                            `<span class="campo-nombres">${proveedor.nombre_representante}</span>`,
                            `<span class="campo-tex-num">${proveedor.correo_proveedor}</span>`,
                            `<span class="campo-nombres">${proveedor.direccion_proveedor}</span>`,
                            `<span class="campo-numeros">${proveedor.telefono_1}</span>`,
                            `<span class="campo-estatus ${proveedor.estado === "habilitado" ? "habilitado" : "inhabilitado"}"
                                data-id="${proveedor.id_proveedor}" 
                                style="cursor: pointer;">
                                ${proveedor.estado}
                            </span>`,
                            `<ul>
                                <button class="btn-detalle" title="Detallar"
                                    data-id="${proveedor.id_proveedor}"
                                    data-nombre_proveedor="${proveedor.nombre_proveedor}"
                                    data-rif_proveedor="${proveedor.rif_proveedor}"
                                    data-nombre_representante="${proveedor.nombre_representante}"
                                    data-rif_representante="${proveedor.rif_representante || ''}"
                                    data-correo_proveedor="${proveedor.correo_proveedor}"
                                    data-direccion_proveedor="${proveedor.direccion_proveedor}"
                                    data-telefono_1="${proveedor.telefono_1}"
                                    data-telefono_2="${proveedor.telefono_2 || ''}"
                                    data-observacion="${proveedor.observacion || ''}">
                                    <img src="assets/img/eye.svg">
                                </button>
                                <button class="btn-modificar"
                                    id="btnModificarProveedor"
                                    data-id="${proveedor.id_proveedor}"
                                    data-nombre-proveedor="${proveedor.nombre_proveedor}"
                                    data-rif-proveedor="${proveedor.rif_proveedor}"
                                    data-nombre-representante="${proveedor.nombre_representante}"
                                    data-rif-representante="${proveedor.rif_representante || ''}"
                                    data-correo-proveedor="${proveedor.correo_proveedor}"
                                    data-direccion-proveedor="${proveedor.direccion_proveedor}"
                                    data-telefono-1="${proveedor.telefono_1}"
                                    data-telefono-2="${proveedor.telefono_2 || ''}"
                                    data-observacion="${proveedor.observacion || ''}">
                                    <img src="assets/img/pencil.svg">
                                </button>
                                <button class="btn-eliminar"
                                    data-id="${proveedor.id_proveedor}">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            </ul>`
                        ]).draw(false);

                        const filaNode = fila.node();
                        $(filaNode).attr('data-id', proveedor.id_proveedor);
                        const botonModificar = $(filaNode).find(".btn-modificar");
                        botonModificar.data('nombre_proveedor', proveedor.nombre_proveedor);
                        botonModificar.data('rif_proveedor', proveedor.rif_proveedor);
                        botonModificar.data('nombre_representante', proveedor.nombre_representante);
                        botonModificar.data('rif_representante', proveedor.rif_representante);
                        botonModificar.data('correo_proveedor', proveedor.correo_proveedor);
                        botonModificar.data('direccion_proveedor', proveedor.direccion_proveedor);
                        botonModificar.data('telefono_1', proveedor.telefono_1);
                        botonModificar.data('telefono_2', proveedor.telefono_2);
                        botonModificar.data('observacion', proveedor.observacion);
                    }
                } else {
                    manejarErroresBackend(response, true);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar el porveedor:', textStatus, errorThrown);
                muestraMensaje('Error al modificar el proveedor.');
            }
        });
    });

    $(document).on('click', '#modificarProveedorModal .close', function() {
        $('#modificarProveedorModal').modal('hide');
    });

    $(document).on('click', '.btn-eliminar', function (e) {
        e.preventDefault();
        const btnEliminar = this;
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
                var id_proveedor = $(btnEliminar).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_proveedor', id_proveedor);
                enviarAjax(datos, function(respuesta){
                    const status = (respuesta && respuesta.status) ? String(respuesta.status).toLowerCase() : '';
                    const ok = status === 'success' || status === 'ok' || respuesta.resultado === 'success' || respuesta.success === true;
                    if (ok) {
                        Swal.fire(
                            'Eliminada!',
                            'El proveedor ha sido eliminada.',
                            'success'
                        );
                        const tabla = $('#tablaConsultas').DataTable();
                        const $tr = $(btnEliminar).closest('tr');
                        if ($tr.length) {
                            const $parent = $tr.hasClass('child') ? $tr.prev('tr') : $tr;
                            tabla.row($parent).remove().draw(false);
                            eliminarFilaProveedor(id_proveedor);
                        }
                    } else {
                        Swal.fire('Error', respuesta.message, 'error');
                    }
                });
            }
        });
    });

    function eliminarFilaProveedor(id_proveedor) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_proveedor}"]`);
        if (fila.length) {
            tabla.row(fila).remove().draw(false);
        }
    }

    $(document).on('click', '.campo-estatus', function() {
        const id_proveedor = $(this).data('id');
        cambiarEstatus(id_proveedor);
    });

    function cambiarEstatus(id_proveedor) {
        const span = $(`span.campo-estatus[data-id="${id_proveedor}"]`);
        const estatusActualTexto = span.text().trim();
        const estatusActual = estatusActualTexto.toLowerCase();
        const nuevoEstatus = estatusActual === 'habilitado' ? 'inhabilitado' : 'habilitado';

        span.addClass('cambiando');
            
        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: {
                accion: 'cambiar_estado',
                id_proveedor: id_proveedor,
                nuevo_estatus: nuevoEstatus
            },
            success: function(data) {
                span.removeClass('cambiando');
                if (data.status === 'success') {
                    span.text(nuevoEstatus);
                    span.removeClass('habilitado inhabilitado').addClass(nuevoEstatus);

                    Swal.fire({
                        icon: 'success',
                        title: '¡Estatus actualizado!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    var table = $('#tablaConsultas').DataTable();
                    // Releer los datos desde el DOM actualizado para que DataTables
                    // no reemplace el nuevo estatus con el valor antiguo en memoria
                    table.row(span.closest('tr')).invalidate('dom');
                    table.draw(false);
                } else {
                    span.text(estatusActualTexto);
                    span.removeClass('habilitado inhabilitado').addClass(estatusActual);

                    Swal.fire('Error', data.message || 'Error al cambiar el estatus', 'error');
                }
            },
            error: function(xhr, status, error) {
                span.removeClass('cambiando');
                span.text(estatusActualTexto);
                span.removeClass('habilitado inhabilitado').addClass(estatusActual);

                Swal.fire('Error', 'Error en la conexión', 'error');
            }
        });
    }

    $(document).on('click', '#btnPedidoProducto', function() {
        var boton = $(this);
    
        $('#modificarIdProducto').val(boton.data('id'));
        $('#modificarNombreProducto').val(boton.data('nombre'));
        $('#modificarModelo').val(boton.data('modelo'));
        $('#modificarStockMinimo').val(boton.data('stockminimo'));
    
        $('#PedidoProductoModal').modal('show');
    });

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('PedidoProductoModal');
        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            // Obtener datos del botón
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const modelo = button.closest('tr').children[2].textContent; // Usa la celda de la tabla

            // Llenar el modal
            document.getElementById('modificarIdProducto').value = id;
            document.getElementById('modificarNombreProducto').value = nombre;
            document.getElementById('modificarModelo').value = modelo;
        });
    });

    // Enviar datos de modificación por AJAX al controlador PHP
    $('#FormPedidoProducto').on('submit', function(e) {
        e.preventDefault();

        // Crear un objeto FormData con los datos del formulario
        var formData = new FormData(this);
        formData.append('accion', 'modificar');

        // Enviar la solicitud AJAX al controlador PHP
        $.ajax({
            url: '',
            type: 'POST',
            processData: false,
            contentType: false,
            cache: false,
            data: formData,
            success: function(response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'El pedido se realizó correctamente'
                    }).then(function() {
                        
                        if (response.proveedor) {
                            actualizarFilaProveedor(response.proveedor);
                        }
                        $('#modificar_usuario_modal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        $('body').css('padding-right', '');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo realizar el pedido al proveedor'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al realizar el pedido:', textStatus, errorThrown);
                muestraMensaje('Error al realizar el pedido.');
            }
        });
    });

    $(document).on('click', '#PedidoProductoModal .close', function() {
        $('#PedidoProductoModal').modal('hide');
    });

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
});

// Modal de Ayuda - Integración para Proveedores
$(document).ready(function() {
    console.log('Inicializando modal de ayuda...');
    
    // Cargar CSS del modal de ayuda
    if (!$('link[href*="ayuda/css/modal.css"]').length) {
        $('<link>')
            .attr({
                'rel': 'stylesheet',
                'type': 'text/css',
                'href': 'assets/public/ayuda/css/modal.css'
            })
            .appendTo('head');
    }
    
    // Variable global para el modal
    let modalAyudaInstance = null;
    
    // Función para cargar y mostrar el modal de ayuda
    function cargarYMostrarModalAyuda(contexto = null) {
        console.log('Cargando modal de ayuda...', contexto);
        
        if (modalAyudaInstance) {
            console.log('Modal ya existe, abriendo...');
            modalAyudaInstance.openModal();
            
            // Si hay contexto, ir a la sección específica
            if (contexto) {
                setTimeout(() => {
                    if (contexto === 'registrar') {
                        modalAyudaInstance.goToSlide(1); // Ir a "Registrar"
                    } else if (contexto === 'modificar') {
                        modalAyudaInstance.goToSlide(3); // Ir a "Modificar"
                    }
                }, 300);
            }
            return;
        }
        
        // Cargar HTML del modal
        $.get('assets/public/ayuda/proveedor.php')
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
                console.error('Error al cargar el HTML del modal de proveedor');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el contenido de ayuda'
                });
            });
    }
    
    // Botón de ayuda principal (si existe)
    $('.btn-ayuda').off('click.ayuda').on('click.ayuda', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda detectado');
        cargarYMostrarModalAyuda(); // Sin contexto específico
    });
    
    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal con contexto:', contexto);
        cargarYMostrarModalAyuda(contexto);
    });
});

$(document).on('click', '.btn-detalle', function() {
    const d = this.dataset;
    const nombreProveedor = d.nombre_proveedor || d.nombreproveedordtl || '';
    const rifProveedor = d.rif_proveedor || d.rifproveedordtl || '';
    const nombreRepresentante = d.nombre_representante || d.nombrerepresentantedtl || '';
    const rifRepresentante = d.rif_representante || d.rifrepresentantedtl || '';
    const correo = d.correo_proveedor || d.correodtl || '';
    const direccion = d.direccion_proveedor || d.direcciondtl || '';
    const telefono1 = d.telefono_1 || d.telefono1dtl || '';
    const telefono2 = d.telefono_2 || d.telefono2dtl || '';
    const observacion = d.observacion || d.observaciondtl || '';

    $('#detalle-nombre-proveedor').text(nombreProveedor);
    $('#detalle-rif-proveedor').text(rifProveedor);
    $('#detalle-nombre-representante').text(nombreRepresentante);
    $('#detalle-rif-representante').text(rifRepresentante);
    $('#detalle-correo').text(correo);
    $('#detalle-direccion').text(direccion);
    $('#detalle-telefono-1').text(telefono1);
    $('#detalle-telefono-2').text(telefono2);
    $('#detalle-observacion').text(observacion);

    const modal = document.getElementById('modalDetallesProveedor');
    if (modal) {
        modal.classList.add('mostrar');
    }
});

$('#cerrarModalDetalles').on('click', function() {
    const modal = document.getElementById('modalDetallesProveedor');
    if (modal) modal.classList.remove('mostrar');
});

$(window).on('click', function(e){
    const modal = document.getElementById('modalDetallesProveedor');
    if (modal && e.target === modal) {
        modal.classList.remove('mostrar');
    }
});

function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}