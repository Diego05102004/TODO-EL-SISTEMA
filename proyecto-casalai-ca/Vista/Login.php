<?php
require_once __DIR__ . '/../api/Config/RecaptchaConfig.php';
$recaptchaSiteKey = \Usuario\ProyectoCasalaiCa\Config\RecaptchaConfig::getSiteKey();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script
      crossorigin="anonymous"
    ></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/styles/login-darckort.css">
    <link rel="stylesheet" href="assets/styles/formulario.css">
    <link rel="icon" type="image/png" href="assets/img/LOGO.png">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="assets/public/js/sweetalert2.js"></script>
    <title>Iniciar Sesión</title>
  </head>


<div id="mensajes" style="display:none"
    data-mensaje="<?php echo !empty($mensaje) ? strip_tags($mensaje) : ''; ?>"
    data-tipo="<?php echo (isset($resultado['status']) && $resultado['status'] == 'success') ? 'success' : 'error'; ?>">
</div>

  <body class="fondo" style="height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="container">
      <div class="forms-container">
        <div class="inicio-registro">
          <form method="post" id="f" action="" class="iniciar-sesion-form">

          <input type="text" name="accion" id="accion" style="display:none" />

            <h2 class="title">Iniciar Sesión</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" name="username" id="username"  placeholder="Nombre de Usuario" maxlength="20" required/>
              <span class="span-v" id="susername"></span>
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <div style="display:flex; align-items:center; gap:8px; width:100%;">
                <input style="flex:1;" type="password" name="password" id="password"  placeholder="Contraseña" maxlength="15" required/>
                <button type="button" class="toggle-password" data-target="#password" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;color:gray;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </button>
              </div>
              <span class="span-v" id="spassword"></span>
            </div>
            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <button class="btn btn-vino w-100" id="acceder" name="acceder">Iniciar Sesión</button>
            <!-- Dentro del formulario de inicio de sesión, después del botón -->
              <div class="forgot-password">
                  <a href="index.php?pagina=password-recovery">¿Olvidaste tu contraseña?</a>
              </div>
          </form>


<form method="post" id="registro-usuario-cliente" class="registrar-form">
  <h2 class="title">Registro</h2>

  <div class="input-row">
    <div class="input-field">
      <i class="fas fa-user"></i>
      <input type="text" name="nombre" id="nombre" placeholder="Nombre" maxlength="50" required />
      <span class="span-v" id="snombre"></span>
    </div>

    <div class="input-field">
      <i class="fas fa-user"></i>
      <input type="text" name="apellido" id="apellido" placeholder="Apellido" maxlength="50" required />
      <span class="span-v" id="sapellido"></span>
    </div>
  </div>

  <div class="input-row">
    <div class="input-field">
      <i class="fas fa-phone"></i>
      <input type="text" name="nombre_usuario" id="nombre_usuario" placeholder="Nombre de Usuario" maxlength="20" required />
      <span class="span-v" id="snombre_usuario"></span>
    </div>

    <div class="input-field">
      <i class="fas fa-envelope"></i>
      <input type="text" name="cedula" id="cedula" placeholder="Cédula" maxlength="10" required />
      <span class="span-v" id="scedula"></span>
    </div>
  </div>

  <div class="input-row">
    <div class="input-field">
      <i class="fas fa-phone"></i>
      <input type="text" name="telefono" id="telefono" placeholder="Teléfono" maxlength="13" required />
      <span class="span-v" id="stelefono_usuario"></span>
    </div>

    <div class="input-field">
      <i class="fas fa-envelope"></i>
      <input type="email" name="correo" id="correo" placeholder="Correo Electrónico" maxlength="50" required />
      <span class="span-v" id="scorreo_usuario"></span>
    </div>
  </div>

    <div class="input-field">
      <i class="fas fa-map-marker-alt"></i>
      <input class="form-control" style="padding: 0;" id="direccion" name="direccion" placeholder="Dirección" maxlength="100" required/>
      <span class="span-v" id="sdireccion"></span>
    </div>

  <div class="input-row">
    <div class="input-field">
      <i class="fas fa-lock"></i>
      <div style="display:flex; align-items:center; gap:8px; width:100%;">
        <input type="password" name="clave" id="clave" placeholder="Contraseña" maxlength="15" required />
        <button type="button" class="toggle-password" data-target="#clave" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;color:gray;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
        </button>
      </div>
      <span class="span-v" id="sclave_usuario"></span>
    </div>

    <div class="input-field">
      <i class="fas fa-lock"></i>
      <div style="display:flex; align-items:center; gap:8px; width:100%;">
        <input type="password" name="clave_confirmar" id="clave_confirmar" placeholder="Confirmar Contraseña" maxlength="15" required />
        <button type="button" class="toggle-password" data-target="#clave_confirmar" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;color:gray;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
        </button>
      </div>
      <span class="span-v" id="sclave_confirmar"></span>
    </div>
  </div>

    <div class="input-row">
      <button type="submit" class="btn btn-vino w-100">Registrar</button>
    </div>
  <input type="hidden" name="accion" value="registrar" />
</form>

        </div>
    </div>

    <div class="panels-container">
        <div class="panel left-panel">
          <div class="content">
            <h3>¿Aun no te has registrado?</h3>
            <p>
              
            </p>
            <button class="btn transparent" id="registrar-btn">
              Registrate
            </button>
          </div>
          <img src="assets/img/log.svg" class="image" alt="" />
        </div>
        <div class="panel right-panel">
          <div class="content">
            <h3>¿Ya Tienes una Cuenta?</h3>
            <p>
              
            </p>
            <button class="btn transparent" id="iniciar-sesion-btn">
              Iniciar Sesión
            </button>
          </div>
          <img src="assets/img/register.svg" class="image" alt="" />
        </div>
      </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="assets/javascript/darckort-login.js"></script>
    <script src="assets/javascript/login.js"></script>
    <script>
      // Evitar reenvío del formulario al recargar (PRG ligero en frontend)
      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, document.title, window.location.href);
      }
    </script>
  </body>
</html>