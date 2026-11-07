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

$(document).ready(function() {
    // Verificar si DataTable ya está inicializado
    var tablaUsuarios;
    var $tabla = $('#tablaConsultas');

    if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
        // Inicializar DataTable con configuración personalizada
        tablaUsuarios = $tabla.DataTable({
            "language": {
                "url": "assets/public/js/es-ES.json"
            },
            "responsive": true,
            "scrollX": true,
            "scrollCollapse": true,
            "columnDefs": [
                { 
                    "targets": 5, // Índice de la columna de estatus
                    "searchable": true,
                    "type": 'string',
                    "render": function(data, type, row) {
                        // Para la búsqueda, devolvemos el texto del estado
                        if (type === 'filter' || type === 'sort') {
                            return $(data).text().trim().toLowerCase();
                        }
                        return data;
                    }
                },
                { 
                    "orderable": false, 
                    "targets": [6] // Deshabilitar ordenamiento en columna de acciones
                }
            ],
            "initComplete": function () {
                var api = this.api();
                var $wrapper = $(api.table().container());

                api.columns.adjust();

                // Crear el botón directamente dentro del filtro de DataTables
                var $filter = $wrapper.find('.dataTables_filter');
                if (!$filter.length) return;

                // Evitar duplicar el botón si ya existe
                if ($filter.find('#btnIncluirUsuario').length) return;

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
                    id: 'btnIncluirUsuario',
                    'class': 'btn-incluir',
                    type: 'button',
                    title: 'Incluir Usuario'
                }).append($('<img>', { src: 'assets/img/plus.svg' }));

                $btnWrapper.append($btn);
                $filter.append($btnWrapper);
            }
        });
    } else {
        tablaUsuarios = $tabla.DataTable();
    }

    // Detectar dinámicamente el índice de la columna "Estatus" (fallback a 5)
    var estatusColIndex = $tabla.find('thead th').filter(function(){
        return $(this).text().trim().toLowerCase() === 'estatus';
    }).index();
    if (estatusColIndex < 0) estatusColIndex = 5;

    // Registrar filtro global UNA sola vez para esta tabla
    if (!$tabla.data('estatusFilterAdded')) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
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

    // Vincular el cambio del select con espacio de nombres y dibujar
    var $filtro = $('#filtro-estatus');
    $filtro.off('change.estatus').on('change.estatus', function(){
        var val = ($(this).val() || 'todos').toLowerCase();
        $tabla.data('estatusFilterValue', val);
        tablaUsuarios.draw();
    });

    // Establecer valor inicial desde el select y dibujar una vez
    $tabla.data('estatusFilterValue', ($filtro.val() || 'todos').toLowerCase());
    tablaUsuarios.draw();

    // Proteger selects de rol de usuario (incluir y modificar)
    protegerSelects(['rango', 'modificar_rango']);

    // Resto del código...

  if ($.trim($("#mensajes").text()) != "") {
    mensajes("warning", "Atención", $("#mensajes").html());
  }

  $("#nombre").on("keypress", function (e) {
    validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]*$/, e);
    let nombre = document.getElementById("nombre");
    nombre.value = space(nombre.value);
  });
  $("#nombre").on("keyup", function () {
    validarKeyUp(
      /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
      $(this),
      $("#snombre"),
      "*Solo letras, de 2 a 50 caracteres*"
    );
  });

  $("#cedula").on("keypress", function(e){
    validarKeyPress(/^[0-9.]*$/, e);
  });

  $("#apellido_usuario").on("keypress", function (e) {
    validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
    let apellido_usuario = document.getElementById("apellido_usuario");
    apellido_usuario.value = space(apellido_usuario.value);
  });
  $("#apellido_usuario").on("keyup", function () {
    validarKeyUp(
      /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
      $(this),
      $("#sapellido"),
      "*Solo letras, de 2 a 50 caracteres*"
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
        "*Formato válido: 1.234.567 o 12.345.678*"
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

  $("#nombre_usuario").on("keypress", function (e) {
    validarKeyPress(/^[a-zA-Z0-9_]*$/, e);
  });
  $("#nombre_usuario").on("keyup", function () {
    validarKeyUp(
      /^[a-zA-Z0-9_]{4,20}$/,
      $(this),
      $("#snombre_usuario"),
      "*El usuario debe tener entre 4 y 20 caracteres alfanuméricos*"
    );
  });

  $("#telefono_usuario").on("keypress", function (e) {
    validarKeyPress(/^[0-9-]*$/, e);
  });

  $("#telefono_usuario").on("keyup", function () {
    validarKeyUp(
      /^\d{4}-\d{3}-\d{4}$/,
      $(this),
      $("#stelefono_usuario"),
      "*Formato válido: 0400-000-0000*"
    );
  });

  $("#telefono_usuario").on("input", function() {
      let valor = $(this).val().replace(/\D/g, '');
      if(valor.length > 4 && valor.length <= 7)
          valor = valor.slice(0,4) + '-' + valor.slice(4);
      else if(valor.length > 7)
          valor = valor.slice(0,4) + '-' + valor.slice(4,7) + '-' + valor.slice(7,11);
      $(this).val(valor);
  });

  $("#correo_usuario").on("keypress", function (e) {
    validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
  });

  $("#correo_usuario").on("keyup", function () {
    validarKeyUp(
      /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
      $(this),
      $("#scorreo_usuario"),
      "*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*"
    );
  });

  $("#rango").on("change", function(){
    if ($(this).val()) {
      $(this).removeClass("is-invalid").addClass("is-valid");
    } else {
      $(this).removeClass("is-valid").addClass("is-invalid");
    }
  });

  // Toggle mostrar/ocultar contraseña (registro de usuarios)
  $(document).on('click', '.toggle-password', function(){
    var target = $(this).data('target');
    var $input = $(target);
    if (!$input.length) return;
    var type = $input.attr('type') === 'password' ? 'text' : 'password';
    $input.attr('type', type);
    // Cambiar ícono (ojo / ojo tachado)
    var eye = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>';
    var eyeOff = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/><line x1="2" y1="2" x2="22" y2="22" stroke="currentColor" stroke-width="2"/></svg>';
    $(this).html(type === 'text' ? eyeOff : eye);
  });

  $("#clave_usuario").on("keypress", function (e) {
    validarKeyPress(/^[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\\b]*$/, e);
  });
  $("#clave_usuario").on("keyup", function () {
    validarKeyUp(
      /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\])[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\]{6,15}$/,
      $(this),
      $("#sclave_usuario"),
      "*6-15 caracteres, con al menos 1 mayúscula, 1 número y 1 caracter especial*"
    );
  });

  $("#clave_confirmar").on("keypress", function (e) {
    validarKeyPress(/^[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\\b]*$/, e);
  });
  $("#clave_confirmar").on("keyup", function () {
    validarKeyUp(
      /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\])[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\]{6,15}$/,
      $(this),
      $("#sclave_confirmar"),
      "*Ingrese la contraseña nuevamente*"
    );
  });
function verificarPermisosEnTiempoRealUsuarios() {
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
            $('#btnIncluirUsuario').show();
        } else {
            $('#btnIncluirUsuario').hide();
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
    verificarPermisosEnTiempoRealUsuarios();
    setInterval(verificarPermisosEnTiempoRealUsuarios, 10000); // 10 segundos
});
  function validarEnvioUsuario() {
    let valido = true;

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
    
    let apellido_usuario = $("#apellido_usuario");
    apellido_usuario.val(space(apellido_usuario.val()).trim());
    const apellidoVal = apellido_usuario.val();
    if (apellidoVal === "") {
      $("#sapellido").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el apellido','El campo está vacío.');
      return false;
    }
    if (apellidoVal.length < 2) {
      $("#sapellido").text("*Mínimo 2 caracteres*");
      mensajes('error','Verifique el apellido','Debe tener mínimo 2 caracteres.');
      return false;
    }
    
    let cedula = $("#cedula");
    cedula.val(space(cedula.val()).trim());
    const cedVal = cedula.val();
    if (cedVal === "") {
      $("#scedula").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el número de cédula','El campo está vacío.');
      return false;
    }
    if (!/^(?:\d{1,2}\.\d{3}\.\d{3})$/.test(cedVal)) {
      $("#scedula").text("*Formato válido: 1.234.567 o 12.345.678*");
      mensajes('error','Verifique el número de cédula','Formato inválido.');
      return false;
    }

    let telefono_usuario = $("#telefono_usuario");
    const telVal = telefono_usuario.val().trim();
    if (telVal === "") {
      $("#stelefono_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el número de teléfono','El campo está vacío.');
      return false;
    }
    if (!/^\d{4}-\d{3}-\d{4}$/.test(telVal)) {
      $("#stelefono_usuario").text("*Formato válido: 0400-000-0000*");
      mensajes('error','Verifique el número de teléfono','Formato inválido.');
      return false;
    }

    let nombre_usuario = $("#nombre_usuario");
    nombre_usuario.val(space(nombre_usuario.val()).trim());
    const userVal = nombre_usuario.val();
    if (userVal === "") {
      $("#snombre_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el nombre de usuario','El campo está vacío.');
      return false;
    }
    if (userVal.length < 4) {
      $("#snombre_usuario").text("*Mínimo 4 caracteres*");
      mensajes('error','Verifique el nombre de usuario','Debe tener mínimo 4 caracteres.');
      return false;
    }

    let correo_usuario = $("#correo_usuario");
    const correoVal = correo_usuario.val().trim();
    if (correoVal === "") {
      $("#scorreo_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el correo electrónico','El campo está vacío.');
      return false;
    }
    if (!/^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/.test(correoVal)) {
      $("#scorreo_usuario").text("*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*");
      mensajes('error','Verifique el correo electrónico','Formato inválido.');
      return false;
    }

    // VALIDACIÓN DEL TIPO DE USUARIO
    let rango = $("#rango");
    if (!rango.val()) {
      rango.addClass("is-invalid");
      mensajes('error','Verifique el rol','Debe seleccionar un rol para el usuario.');
      return false;
    } else {
      rango.removeClass("is-invalid");
    }

    let clave_usuario = $("#clave_usuario");
    const passVal = clave_usuario.val();
    if (passVal.trim() === "") {
      $("#sclave_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique la contraseña','El campo está vacío.');
      return false;
    }
    if (passVal.length < 6) {
      $("#sclave_usuario").text("*Mínimo 6 caracteres*");
      mensajes('error','Verifique la contraseña','Debe tener mínimo 6 caracteres.');
      return false;
    }
    // Complejidad de contraseña: 6-15, 1 mayúscula, 1 número y 1 caracter especial
    var passPattern = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\])[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\]{6,15}$/;
    if (!passPattern.test(passVal)) {
      $("#sclave_usuario").text("*6-15 caracteres, con al menos 1 mayúscula, 1 número y 1 caracter especial*");
      mensajes('error','Verifique la contraseña','Formato inválido.');
      return false;
    }

    let clave_confirmar = $("#clave_confirmar");
    const pass2Val = clave_confirmar.val();
    if (pass2Val.trim() === "") {
      $("#sclave_confirmar").text("*Este campo es obligatorio*");
      mensajes('error','Verifique la confirmación de la contraseña','El campo está vacío.');
      return false;
    }
    if (pass2Val.length < 6) {
      $("#sclave_confirmar").text("*Mínimo 6 caracteres*");
      mensajes('error','Verifique la confirmación de la contraseña','Debe tener mínimo 6 caracteres.');
      return false;
    }
    if (!passPattern.test(pass2Val)) {
      $("#sclave_confirmar").text("*Debe ingresar la contraseña nuevamente*");
      mensajes('error','Verifique la confirmación de la contraseña','Formato inválido.');
      return false;
    }
      
    if (clave_usuario.val() !== clave_confirmar.val()) {
      $("#sclave_confirmar").text("*Las contraseñas no coinciden*");
      mensajes('error','Verifique las contraseñas','Las contraseñas no coinciden.');
      return false;
    }
    return valido;
  }

function agregarFilaUsuario(usuario) {
    const tabla = $("#tablaConsultas").DataTable();
    const nuevaFila = [
      `<span class="campo-nombres">${usuario.nombres} ${usuario.apellidos}</span>`,
      `<span class="campo-tex-num">${usuario.correo}</span>`,
      `<span class="campo-nombres">${usuario.username}</span>`,
      `<span class="campo-numeros">${usuario.telefono}</span>`,
      `<span class="campo-rango">${usuario.nombre_rol}</span>`,
      `<span class="campo-estatus habilitado" data-id="${usuario.id_usuario}" style="cursor: pointer;">
            habilitado
        </span>`,
      `<ul>
          <div>
              <button class="btn-modificar"
                  data-id="${usuario.id_usuario}"
                  data-username="${usuario.username}"
                  data-nombres="${usuario.nombres}"
                  data-cedula="${usuario.cedula}"
                  data-apellidos="${usuario.apellidos}"
                  data-correo="${usuario.correo}"
                  data-telefono="${usuario.telefono}"
                  data-clave=""
                  data-rango="${usuario.id_rol}">
                  <img src="assets/img/pencil.svg">
              </button>
          </div>
          <div>
              <button class="btn-eliminar"
                  data-id="${usuario.id_usuario}">
                  <img src="assets/img/circle-x.svg">
              </button>
          </div>
      </ul>`
    ];
    const rowNode = tabla.row.add(nuevaFila).draw(false).node();
    $(rowNode).attr("data-id", usuario.id_usuario);
}

  function resetUsuario() {
    $("#nombre").val("");
    $("#snombre").text("");
    $("#apellido_usuario").val("");
    $("#sapellido").text("");
    $("#cedula").val("");
    $("#scedula").text("");
    $("#nombre_usuario").val("");
    $("#snombre_usuario").text("");
    $("#telefono_usuario").val("");
    $("#stelefono_usuario").text("");
    $("#correo_usuario").val("");
    $("#scorreo_usuario").text("");
    $("#rango").val("");
    $("#rango").removeClass("is-valid is-invalid");
    $("#clave_usuario").val("");
    $("#sclave_usuario").text("");
    $("#clave_confirmar").val("");
    $("#sclave_confirmar").text("");
  }

  $(document).on("click", "#btnIncluirUsuario", function () {
    $("#incluirusuario")[0].reset();
    $("#snombre").text("");
    $("#sapellido").text("");
    $("#scedula").text("");
    $("#snombre_usuario").text("");
    $("#scorreo_usuario").text("");
    $("#stelefono_usuario").text("");
    $("#sclave_usuario").text("");
    $("#sclave_confirmar").text("");
    $("#rango").removeClass("is-valid is-invalid");
    $("#registrarUsuarioModal").modal("show");
  });

  // Al presionar "Limpiar", quitar estado visual del select de rol
  $("#incluirusuario").on("reset", function(){
    // Timeout para permitir que el reset del navegador se aplique primero
    setTimeout(function(){
      $("#rango").removeClass("is-valid is-invalid");
    }, 0);
  });

  $("#incluirusuario").on("submit", function (e) {
    e.preventDefault();
    if (validarEnvioUsuario()) {
      var datos = new FormData(this);
      datos.append("accion", "registrar");
      enviarAjax(datos, function (respuesta) {
        if (respuesta.status === "success" && respuesta.usuario) {
          Swal.fire({
            icon: "success",
            title: "Éxito",
            text: respuesta.message || "Usuario registrado correctamente",
          });
          agregarFilaUsuario(respuesta.usuario);
          resetUsuario();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: respuesta.message || "No se pudo registrar el usuario",
          });
        }
      });
    }
  });

  $(document).on("click", "#registrarUsuarioModal .close", function () {
    $("#registrarUsuarioModal").modal("hide");
  });

  function enviarAjax(datos, callback) {
    $.ajax({
      url: "",
      type: "POST",
      data: datos,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'json', // Esperar una respuesta JSON
      success: function (respuesta) {
        if (callback) callback(respuesta);
      },
      error: function (xhr, status, error) {
        try {
          // Intentar extraer el JSON de la respuesta aunque venga con advertencias
          let responseText = xhr.responseText;
          // Buscar el primer { y el último } para extraer el JSON
          const jsonStart = responseText.indexOf('{');
          const jsonEnd = responseText.lastIndexOf('}') + 1;
          if (jsonStart >= 0 && jsonEnd > 0) {
            const jsonString = responseText.substring(jsonStart, jsonEnd);
            const jsonResponse = JSON.parse(jsonString);
            if (callback) callback(jsonResponse);
          } else {
            throw new Error('No se pudo extraer JSON de la respuesta');
          }
        } catch (e) {
          console.error('Error al procesar la respuesta:', e);
          Swal.fire({
            title: "Error",
            text: "Error al procesar la respuesta del servidor: " + error,
            icon: "error"
          });
        }
      }
    });
  }

  $("#modificarnombre").on("keypress", function (e) {
    validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
    let nombre = document.getElementById("modificarnombre");
    nombre.value = space(nombre.value);
  });
  $("#modificarnombre").on("keyup", function () {
    validarKeyUp(
      /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,30}$/,
      $(this),
      $("#smodificarnombre"),
      "*Solo letras, de 2 a 30 caracteres*"
    );
  });

  $("#modificarapellido_usuario").on("keypress", function (e) {
    validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
    let nombre = document.getElementById("modificarapellido_usuario");
    nombre.value = space(nombre.value);
  });
  $("#modificarapellido_usuario").on("keyup", function () {
    validarKeyUp(
      /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,30}$/,
      $(this),
      $("#smodificarapellido_usuario"),
      "*Solo letras, de 2 a 30 caracteres*"
    );
  });

  $("#modificarcedula").on("keypress", function(e){
    validarKeyPress(/^[0-9.]*$/, e);
  });

  $("#modificarcedula").on("keyup", function(){
    validarKeyUp(
        /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
        $(this),
        $("#smodificarcedula"),
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

  $("#modificarnombre_usuario").on("keypress", function (e) {
    validarKeyPress(/^[a-zA-Z0-9_]*$/, e);
  });
  $("#modificarnombre_usuario").on("keyup", function () {
    validarKeyUp(
      /^[a-zA-Z0-9_]{4,20}$/,
      $(this),
      $("#smodificarnombre_usuario"),
      "*El usuario debe tener entre 4 y 20 caracteres alfanuméricos*"
    );
  });

  $("#modificartelefono_usuario").on("keypress", function (e) {
    validarKeyPress(/^[0-9-]*$/, e);
  });

  $("#modificartelefono_usuario").on("keyup", function () {
    validarKeyUp(
      /^\d{4}-\d{3}-\d{4}$/,
      $(this),
      $("#smodificartelefono_usuario"),
      "*Formato válido: 0400-000-0000*"
    );
  });

  $("#modificarcorreo_usuario").on("keypress", function (e) {
    validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
  });

  $("#modificarcorreo_usuario").on("keyup", function () {
    validarKeyUp(
      /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
      $(this),
      $("#smodificarcorreo_usuario"),
      "*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*"
    );
  });

  $("#modificar_rango").on("change", function(){
    if ($(this).val()) {
      $(this).removeClass("is-invalid").addClass("is-valid");
    } else {
      $(this).removeClass("is-valid").addClass("is-invalid");
    }
  });

  $(document).on("click", ".btn-modificar", function () {
    $("#modificar_id_usuario").val($(this).data("id"));
    $("#modificarnombre_usuario").val($(this).data("username"));
    $("#modificarnombre").val($(this).data("nombres"));
    $("#modificarapellido_usuario").val($(this).data("apellidos"));
    $("#modificarcorreo_usuario").val($(this).data("correo"));
    $("#modificartelefono_usuario").val($(this).data("telefono"));
    $("#modificar_rango").val($(this).data("rango"));
    $('#modificarcedula').val($(this).data('cedula'));

    $("#smodificarnombre_usuario").text("");
    $('#smodificarcedula').text('');
    $("#smodificarnombre").text("");
    $("#smodificarapellido_usuario").text("");
    $("#smodificarcorreo_usuario").text("");
    $("#smodificartelefono_usuario").text("");
    // Estado visual inicial del select según valor
    const sel = $("#modificar_rango");
    if (sel.val()) {
      sel.removeClass("is-invalid").addClass("is-valid");
    } else {
      sel.removeClass("is-valid is-invalid");
    }
    $("#modificar_usuario_modal").modal("show");
  });

  $("#modificarusuario").on("submit", function (e) {
    e.preventDefault();

    const modNombre = $("#modificarnombre");
    const modNombreVal = space(modNombre.val()).trim();
    modNombre.val(modNombreVal);
    if (modNombreVal === "") {
      $("#smodificarnombre").text("*Este campo es obligatorio*");
      mensajes('error','Verifique los nombres','El campo está vacío.');
      return;
    }
    if (modNombreVal.length < 2) {
      $("#smodificarnombre").text("*Mínimo 2 caracteres*");
      mensajes('error','Verifique los nombres','Debe tener mínimo 2 caracteres.');
      return;
    }

    const modApellido = $("#modificarapellido_usuario");
    const modApellidoVal = space(modApellido.val()).trim();
    modApellido.val(modApellidoVal);
    if (modApellidoVal === "") {
      $("#smodificarapellido_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique los apellidos','El campo está vacío.');
      return;
    }
    if (modApellidoVal.length < 2) {
      $("#smodificarapellido_usuario").text("*Mínimo 2 caracteres*");
      mensajes('error','Verifique los apellidos','Debe tener mínimo 2 caracteres.');
      return;
    }

    const modUser = $("#modificarnombre_usuario");
    const modUserVal = space(modUser.val()).trim();
    modUser.val(modUserVal);
    if (modUserVal === "") {
      $("#smodificarnombre_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el usuario','El campo está vacío.');
      return;
    }
    if (modUserVal.length < 4) {
      $("#smodificarnombre_usuario").text("*Mínimo 4 caracteres*");
      mensajes('error','Verifique el usuario','Debe tener mínimo 4 caracteres.');
      return;
    }

    const modCedula = $("#modificarcedula");
    const modCedVal = modCedula.val().trim();
    if (modCedVal === "") {
      $("#smodificarcedula").text("*Este campo es obligatorio*");
      mensajes('error','Verifique la cédula','El campo está vacío.');
      return;
    }
    if (!/^(?:\d{1,2}\.\d{3}\.\d{3})$/.test(modCedVal)) {
      $("#smodificarcedula").text("*Formato válido: 1.234.567 o 12.345.678*");
      mensajes('error','Verifique la cédula','Formato inválido.');
      $("#modificarcedula").focus();
      return;
    }

    const modTlf = $("#modificartelefono_usuario");
    const modTlfVal = modTlf.val().trim();
    if (modTlfVal === "") {
      $("#smodificartelefono_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el teléfono','El campo está vacío.');
      return;
    }
    if (!/^\d{4}-\d{3}-\d{4}$/.test(modTlfVal)) {
      $("#smodificartelefono_usuario").text("*Formato válido: 0400-000-0000*");
      mensajes('error','Verifique el teléfono','Formato inválido.');
      $("#modificartelefono_usuario").focus();
      return;
    }

    const modCorreo = $("#modificarcorreo_usuario");
    const modCorreoVal = modCorreo.val().trim();
    if (modCorreoVal === "") {
      $("#smodificarcorreo_usuario").text("*Este campo es obligatorio*");
      mensajes('error','Verifique el correo','El campo está vacío.');
      return;
    }
    if (!/^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/.test(modCorreoVal)) {
      $("#smodificarcorreo_usuario").text("*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*");
      mensajes('error','Verifique el correo','Formato inválido.');
      $("#modificarcorreo_usuario").focus();
      return;
    }

    // Validación del rol (misma lógica del registrar)
    const modRango = $("#modificar_rango");
    if (!modRango.val()) {
      modRango.removeClass("is-valid").addClass("is-invalid");
      mensajes('error','Verifique el rol','Debe seleccionar un rol para el usuario.');
      return;
    } else {
      modRango.removeClass("is-invalid").addClass("is-valid");
    }

  var formData = new FormData(this);
  formData.append("accion", "modificar");
  enviarAjax(formData, function (response) {
    if (response.status === "success") {
      const tabla = $("#tablaConsultas").DataTable();
      const id = $("#modificar_id_usuario").val();
      const fila = tabla.row(`tr[data-id="${id}"]`);
      const usuario = response.usuario;
      
      // Cerrar el modal
      $("#modificar_usuario_modal").modal("hide");
      
      // Limpiar el formulario
      $("#modificarusuario")[0].reset();
      
      // Mostrar mensaje de éxito
      Swal.fire({
        icon: "success",
        title: "¡Éxito!",
        text: "El usuario se ha modificado correctamente"
      });

if (fila.length) {
  fila.data([
    `<span class="campo-nombres">${usuario.nombres} ${usuario.apellidos}</span>`,
    `<span class="campo-tex-num">${usuario.correo}</span>`,
    `<span class="campo-nombres">${usuario.username}</span>`,
    `<span class="campo-numeros">${usuario.telefono}</span>`,
    `<span class="campo-rango">${usuario.nombre_rol}</span>`,
    `<span class="campo-estatus ${
      usuario.estatus === "habilitado" ? "habilitado" : "inhabilitado"
    }" data-id="${usuario.id_usuario}" style="cursor: pointer;">
        ${usuario.estatus}
    </span>`,
    `<ul>
        <div>
            <button class="btn-modificar"
                data-id="${usuario.id_usuario}"
                data-username="${usuario.username}"
                data-nombres="${usuario.nombres}"
                data-apellidos="${usuario.apellidos}"
                data-cedula="${usuario.cedula}"
                data-correo="${usuario.correo}"
                data-telefono="${usuario.telefono}"
                data-clave=""
                data-rango="${usuario.id_rol}">
                <img src="assets/img/pencil.svg">
            </button>
        </div>
        <div>
            <button class="btn-eliminar"
                data-id="${usuario.id_usuario}">
                <img src="assets/img/circle-x.svg">
            </button>
        </div>
    </ul>`
  ]).draw(false);

  // Actualiza los data-* del botón Modificar
  const filaNode = fila.node();
  const botonModificar = $(filaNode).find(".btn-modificar");
  botonModificar.data("username", usuario.username);
  botonModificar.data("nombres", usuario.nombres);
  botonModificar.data("apellidos", usuario.apellidos);
  botonModificar.data("cedula", usuario.cedula);
  botonModificar.data("correo", usuario.correo);
  botonModificar.data("telefono", usuario.telefono);
  botonModificar.data("rango", usuario.id_rol);
}
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: response.message || "No se pudo modificar el usuario",
      });
    }
  });
});

  $(document).on("click", "#modificar_usuario_modal .close", function () {
    $("#modificar_usuario_modal").modal("hide");
  });

  $(document).on("click", ".btn-eliminar", function (e) {
    e.preventDefault();
    let id_usuario = $(this).data("id");
    Swal.fire({
      title: "¿Está seguro?",
      text: "¡No podrás revertir esto!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, eliminarlo!",
    }).then((result) => {
      if (result.isConfirmed) {
        var datos = new FormData();
        datos.append("accion", "eliminar");
        datos.append("id_usuario", id_usuario);
        enviarAjax(datos, function (respuesta) {
          if (respuesta.status === "success") {
            const tabla = $("#tablaConsultas").DataTable();
            const fila = tabla.row(
              `#tablaConsultas tbody tr[data-id="${id_usuario}"]`
            );
            
            // Eliminar la fila de la tabla
            tabla.row(fila).remove().draw();
            
            // Cerrar el modal si está abierto
            $(".modal").modal('hide');
            
            // Mostrar mensaje de éxito
            Swal.fire({
              title: "¡Eliminado!",
              text: "El usuario ha sido eliminado correctamente.",
              icon: "success",
            });
          } else {
            Swal.fire({
              title: "Error",
              text: respuesta.message || "No se pudo eliminar el usuario",
              icon: "error",
            });
          }
        });
      }
    });
  });

  function eliminarFilaUsuario(id_usuario) {
    const tabla = $('#tablaConsultas').DataTable();
    const fila = $(`#tablaConsultas tbody tr[data-id="${id_usuario}"]`);
    tabla.row(fila).remove().draw();
  }

  function mensajes(icono, titulo, mensaje) {
    Swal.fire({
      icon: icono,
      title: titulo,
      text: mensaje,
      showConfirmButton: true,
      confirmButtonText: "Aceptar",
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
    str = (str || "").toString();
    const regex = /\s{2,}/g;
    return str.replace(regex, " ");
  }

  $(document).on("click", ".campo-estatus", function () {
    const id_usuario = $(this).data("id");
    cambiarEstatus(id_usuario);
  });

  function cambiarEstatus(id_usuario) {
    const span = $(`span.campo-estatus[data-id="${id_usuario}"]`);
    const estatusActual = span.text().trim();
    const nuevoEstatus =
      estatusActual === "habilitado" ? "inhabilitado" : "habilitado";

    span.addClass("cambiando");

    $.ajax({
      url: "",
      type: "POST",
      dataType: "json",
      data: {
        accion: "cambiar_estatus",
        id_usuario: id_usuario,
        nuevo_estatus: nuevoEstatus,
      },
      success: function (data) {
        span.removeClass("cambiando");
        if (data.status === "success") {
          span.text(nuevoEstatus);
          span.removeClass("habilitado inhabilitado").addClass(nuevoEstatus);
          Swal.fire({
            icon: "success",
            title: "¡Estatus actualizado!",
            timer: 2000,
            showConfirmButton: false,
          });
          // Actualizar DataTable y aplicar el filtro activo inmediatamente
          var table = $('#tablaConsultas').DataTable();
          table.row(span.closest('tr')).invalidate();
          table.draw(false);
        } else {
          span.text(estatusActual);
          span.removeClass("habilitado inhabilitado").addClass(estatusActual);
          Swal.fire(
            "Error",
            data.message || "Error al cambiar el estatus",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        span.removeClass("cambiando");
        span.text(estatusActual);
        span.removeClass("habilitado inhabilitado").addClass(estatusActual);
        Swal.fire("Error", "Error en la conexión", "error");
      },
    });
  }
});

// Modal de Ayuda para Usuarios - Se ejecuta después del ready principal
(function() {
    // Esperar a que jQuery esté disponible
    function initModalAyuda() {
        if (typeof $ === 'undefined') {
            setTimeout(initModalAyuda, 100);
            return;
        }
        
        console.log('Inicializando modal de ayuda para usuarios...');
        
        // Cargar CSS del modal de ayuda
        $('<link>')
            .appendTo('head')
            .attr({
                type: 'text/css',
                rel: 'stylesheet',
                href: 'assets/public/ayuda/css/modal.css'
            });
        
        // Variable global para el modal
        let modalAyudaInstance = null;
        
        // Función para cargar y mostrar el modal de ayuda
        function cargarYMostrarModalAyuda(contexto) {
            console.log('Cargando modal de ayuda para usuarios...', contexto);
            
            if (modalAyudaInstance) {
                console.log('Modal ya existe, abriendo...');
                modalAyudaInstance.openModal();
                
                // Si hay contexto, ir a la sección específica
                if (contexto) {
                    setTimeout(() => {
                        if (contexto === 'registrar') {
                            modalAyudaInstance.goToSlide(1); // Ir a "Registrar"
                        } else if (contexto === 'modificar') {
                            modalAyudaInstance.goToSlide(2); // Ir a "Modificar"
                        } else if (contexto === 'eliminar') {
                            modalAyudaInstance.goToSlide(3); // Ir a "Eliminar"
                        } else if (contexto === 'estatus') {
                            modalAyudaInstance.goToSlide(4); // Ir a "Cambiar Estatus"
                        } else if (contexto === 'reporte') {
                            modalAyudaInstance.goToSlide(5); // Ir a "Generar Reporte"
                        }
                    }, 300);
                }
                return;
            }
            
            // Cargar el contenido del modal
            $.get('assets/public/ayuda/usuario.php', function(html) {
                console.log('HTML del modal de usuarios cargado');
                
                // Agregar el modal al body
                $('body').append(html);
                
                // Cargar JavaScript del modal y luego inicializar
                $.getScript('assets/public/ayuda/js/modal.js', function() {
                    console.log('JavaScript del modal cargado');
                    
                    // Inicializar el modal
                    if (typeof inicializarModalAyudaUsuario === 'function') {
                        modalAyudaInstance = inicializarModalAyudaUsuario();
                        
                        // Mostrar el modal
                        modalAyudaInstance.openModal();
                        console.log('Modal de ayuda para usuarios abierto correctamente');
                        
                        // Si hay contexto, ir a la sección específica
                        if (contexto) {
                            setTimeout(() => {
                                if (contexto === 'registrar') {
                                    modalAyudaInstance.goToSlide(1); // Ir a "Registrar"
                                } else if (contexto === 'modificar') {
                                    modalAyudaInstance.goToSlide(2); // Ir a "Modificar"
                                } else if (contexto === 'eliminar') {
                                    modalAyudaInstance.goToSlide(3); // Ir a "Eliminar"
                                } else if (contexto === 'estatus') {
                                    modalAyudaInstance.goToSlide(4); // Ir a "Cambiar Estatus"
                                } else if (contexto === 'reporte') {
                                    modalAyudaInstance.goToSlide(5); // Ir a "Generar Reporte"
                                }
                            }, 300);
                        }
                    } else {
                        console.error('La función inicializarModalAyudaUsuario no está disponible');
                    }
                });
            }).fail(function() {
                console.error('Error al cargar el HTML del modal de usuarios');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el contenido de ayuda'
                });
            });
        }
        
        // Modificar el botón de ayuda existente
        $('.btn-ayuda').off('click.ayuda').on('click.ayuda', function(e) {
            e.preventDefault();
            console.log('Clic en botón de ayuda detectado');
            cargarYMostrarModalAyuda(); // Sin contexto específico
        });
        
        // Botón de ayuda dentro de modales
        $(document).on('click.ayuda', '.btn-ayuda-modal', function(e) {
            e.preventDefault();
            const contexto = $(this).data('contexto');
            console.log('Clic en botón de ayuda del modal detectado, contexto:', contexto);
            cargarYMostrarModalAyuda(contexto);
        });
        
        // También agregar soporte para botones con clase similar
        $(document).on('click.ayuda', '.btn-ayuda-usuario, [data-ayuda="usuario"]', function(e) {
            e.preventDefault();
            cargarYMostrarModalAyuda(); // Sin contexto específico
        });
        
        // Event listeners para debugging
        $(document).on('modal:opened', function(e) {
            console.log('Modal de ayuda abierto', e.detail);
        });
        
        $(document).on('modal:closed', function(e) {
            console.log('Modal de ayuda cerrado', e.detail);
        });
        
        $(document).on('slide:changed', function(e) {
            console.log('Slide cambiado a:', e.detail.slide);
        });
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalAyuda);
    } else {
        initModalAyuda();
    }
})();
