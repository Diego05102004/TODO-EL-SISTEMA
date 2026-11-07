$(document).ready(function(){
        // Verificar si el token JWT fue creado después del login
        if (window.performance && window.performance.navigation.type === 1) {
            // La página fue recargada (posiblemente después de un redirect)
            console.log('Página recargada después de login');
        }
        
        // Verificar si existe la cookie del token JWT
        function checkTokenCookie() {
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.startsWith('jwt_token=')) {
                    console.log('✅ Token JWT encontrado en cookie');
                    return true;
                }
            }
            return false;
        }
        
        // Verificar token al cargar la página
        if (checkTokenCookie()) {
            console.log('Token JWT creado correctamente');
        }
        
        // Toggle mostrar/ocultar contraseña (login y registro)
        $(document).on('click', '.toggle-password', function(){
          var target = $(this).data('target');
          var $input = $(target);
          if (!$input.length) return;
          var type = $input.attr('type') === 'password' ? 'text' : 'password';
          $input.attr('type', type);
          // Swap icon
          var eye = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>';
          var eyeOff = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/><line x1="2" y1="2" x2="22" y2="22" stroke="currentColor" stroke-width="2"/></svg>';
          $(this).html(type === 'text' ? eyeOff : eye);
        });

        // Auto-ajuste de altura del textarea de Dirección (registro)
        (function(){
            const direccionField = document.getElementById('direccion');
            if (!direccionField) return;
            const MIN_H = 55; // px
            const MAX_H = 140; // px
            const autoResize = (el) => {
                el.style.height = 'auto';
                const next = Math.max(MIN_H, el.scrollHeight);
                el.style.height = Math.min(MAX_H, next) + 'px';
                el.style.overflowY = next > MAX_H ? 'auto' : 'hidden';
            };
            const togglePlaceholderCenter = () => {
                if (!direccionField.value) {
                    direccionField.classList.add('placeholder-center');
                } else {
                    direccionField.classList.remove('placeholder-center');
                }
            };
            direccionField.addEventListener('input', () => { autoResize(direccionField); togglePlaceholderCenter(); });
            direccionField.addEventListener('focus', togglePlaceholderCenter);
            direccionField.addEventListener('blur', togglePlaceholderCenter);
            setTimeout(() => { autoResize(direccionField); togglePlaceholderCenter(); }, 0);
        })();

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

        $("#apellido").on("keypress", function (e) {
          validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
          let apellido_usuario = document.getElementById("apellido");
          apellido_usuario.value = space(apellido_usuario.value);
        });
        $("#apellido").on("keyup", function () {
          validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
            $(this),
            $("#sapellido"),
            "*Solo letras, de 2 a 50 caracteres*"
          );
        });

        $("#nombre_usuario").on("keypress", function (e) {
          validarKeyPress(/^[a-zA-Z0-9_]*$/, e);
        });
        $("#nombre_usuario").on("keyup", function () {
          validarKeyUp(
            /^[a-zA-Z0-9_]{4,20}$/,
            $(this),
            $("#snombre_usuario"),
            "*4-20 caracteres alfanuméricos*"
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

        $("#telefono").on("keypress", function (e) {
          validarKeyPress(/^[0-9-]*$/, e);
        });

        $("#telefono").on("keyup", function () {
          validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono_usuario"),
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

        $("#correo").on("keypress", function (e) {
          validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
        });

        $("#correo").on("keyup", function () {
          validarKeyUp(
            /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
            $(this),
            $("#scorreo_usuario"),
            "*Debe terminar en, por ejemplo: @gmail.com*"
          );
        });

        $("#direccion").on("keypress", function(e){
            validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
            let direccion = document.getElementById("direccion");
            direccion.value = space(direccion.value);
        });

        $("#direccion").on("keyup", function(){
            const val = $(this).val().trim();
            if (val === "") {
                $("#sdireccion").text("*Este campo es obligatorio*");
                return;
            }
            if (val.length < 4) {
                $("#sdireccion").text("*Mínimo 4 caracteres*");
                return;
            }
            validarKeyUp(
                /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{4,100}$/,
                $(this),
                $("#sdireccion"),
                "*El formato permite letras y números*"
            );
        });

        $("#clave").on("keypress", function (e) {
          validarKeyPress(/^[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\\b]*$/, e);
        });
        $("#clave").on("keyup", function () {
          validarKeyUp(
            /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\])[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\]{6,15}$/,
            $(this),
            $("#sclave_usuario"),
            "*6-15 caracteres, mínimo un(a) mayúscula, número y caracter especial*"
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

        $("#f input, #registro-usuario-cliente input, #direccion").on("keypress", function(e) {
        if (e.which === 13) {
            e.preventDefault();
            
            if ($(this).closest('form').attr('id') === 'f') {
                $("#acceder").click();
            } else {
                $("#registro-usuario-cliente").submit();
            }
        }
    });

    $("#registro-usuario-cliente").on("submit", function(e){
        let valido = true;
        let mensaje = "";

        // Validar campos vacíos
        $("#registro-usuario-cliente input[required], #registro-usuario-cliente textarea[required]").each(function(){
            if($.trim($(this).val()) === ""){
                valido = false;
                mensaje = "Todos los campos son obligatorios.";
                $(this).focus();
                return false;
            }
        });

        if(!valido){
            muestraMensaje("error", "Error de validación", mensaje);
            e.preventDefault();
            return false;
        }
    });

    if($.trim($("#mensajes").text()) != ""){
      muestraMensaje($("#mensajes").html());
    }
      
      $("#username").on("keypress",function(e){
        validarKeyPress(/^[a-zA-Z0-9_]*$/,e);
      });
      
      $("#username").on("keyup",function(){
        validarKeyUp(/^[a-zA-Z0-9_]{4,20}$/,$(this),
        $("#susername"),"*Ingrese su nombre de usuario*");
      });
      
      $("#password").on("keypress",function(e){
        validarKeyPress(/^[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\\b]*$/, e);
      });
      
      $("#password").on("keyup",function(){
        validarKeyUp(/^[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\\b]{6,15}$/,
        $(this),$("#spassword"),"*Ingrese su contraseña*");
      });
    
      $("#acceder").on("click",function(){
        event.preventDefault();
        if(validarenvio()){
          
          $("#accion").val("acceder");	
          $("#f").submit();
          
        }
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
        $.get('assets/public/ayuda/login.php')
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
});
    
    function validarenvio(){
      
      if(validarKeyUp(/^[A-Za-z0-9_]{4,20}$/,$("#username"),
        $("#susername"),"*El formato es de 4 y 20 caracteres*")==0){
          muestraMensaje("error","¡ERROR!","El usuario es incorrecto, ingrese el usuario nuevamente");
        return false;					
      }	
      else if(validarKeyUp(/^[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-={}\[\]|:;"'<>.,?\/\\]{6,15}$/,
        $("#password"),$("#spassword"),"*El formato es de 6 y 15 caracteres*")==0){
         muestraMensaje("error","¡ERROR!","La contraseña es incorrecta, ingrese la contraseña nuevamente");
        return false;
      } else if ($("#g-recaptcha-response").val() == "") {
        muestraMensaje("error","¡ERROR!","Debes verificar que no eres un robot");
        return false;
      }
      
      return true;
    }
    
    function muestraMensaje(icono,titulo,mensaje){
        Swal.fire({
        icon:icono,
        title:titulo,
        html:mensaje,
        showConfirmButton:true,
        confirmButtonText:'Aceptar',
      });
    }
    
$(document).ready(function() {
    const mensajesDiv = $("#mensajes");
    const mensaje = mensajesDiv.data("mensaje");
    const tipo = mensajesDiv.data("tipo") || "error";

    if (mensaje) {
        muestraMensaje(tipo, tipo === "success" ? "¡Éxito!" : "Error", mensaje);
    }
});
    
    function validarKeyPress(er,e){
      
      key = e.keyCode;
      
      
        tecla = String.fromCharCode(key);
      
      
        a = er.test(tecla);
      
        if(!a){
      
        e.preventDefault();
        }
      
        
    }
    //Función para validar por keyup
    function validarKeyUp(er,etiqueta,etiquetamensaje,
    mensaje){
      a = er.test(etiqueta.val());
      if(a){
        etiquetamensaje.text("");
        return 1;
      }
      else{
        etiquetamensaje.text(mensaje);
        return 0;
      }
    }

    function space(str) {
      str = (str || "").toString();
      const regex = /\s{2,}/g;
      return str.replace(regex, " ");
    }
    