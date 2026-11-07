<?php
// Incluir autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/Config/RecaptchaConfig.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Importar la clase Auth para generación de token JWT
require_once __DIR__ . '/../Config/Auth.php';
// Importar la clase RateLimiter para control de seguridad
require_once __DIR__ . '/../Config/RateLimiter.php';

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Config\RateLimiter;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Login;
// Verificar si se ha enviado el formulario
if (!empty($_POST)) {
    $o = new Login();
    $h = $_POST['accion'] ?? '';
    
if ($h == 'acceder') {
    // Verificar rate limiting antes de procesar el login
    $username = $_POST['username'] ?? '';
    $rateLimitCheck = RateLimiter::checkLoginAttempt($username);
    
    if (!$rateLimitCheck['allowed']) {
        $mensaje = '<div class="error">' . htmlspecialchars($rateLimitCheck['message']) . '</div>';
    } else {
        // Validar datos de entrada
        $datosValidacion = [
            'username' => $username,
            'password' => $_POST['password'] ?? ''
        ];
        
        $errores = $o->validarInicioSesionDatos($datosValidacion);
        if (!empty($errores)) {
            $mensaje = '<div class="error">Por favor corrija los siguientes errores:<br>';
            foreach ($errores as $campo => $error) {
                $mensaje .= "• $error<br>";
            }
            $mensaje .= '</div>';
            // Registrar intento fallido por validación
            RateLimiter::recordFailedLogin($username);
        } else {
            $o->setUsername($username);
            $o->setPassword($_POST['password'] ?? '');
            $captcha = $_POST['g-recaptcha-response'] ?? '';
            $ip = $_SERVER['REMOTE_ADDR'];
            
            if (empty($captcha) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
                $captchaValido = true;
            } else {
                $captchaResult = \Usuario\ProyectoCasalaiCa\Config\RecaptchaConfig::verifyToken($captcha, $ip);

                if (!empty($captchaResult['configuration_error']) || isset($captchaResult['error'])) {
                    $mensaje = '<div class="error">Error en la verificación del reCAPTCHA: No se pudo conectar con el servidor de verificación. Por favor, inténtalo de nuevo más tarde.</div>';
                    $captchaValido = false;
                    RateLimiter::recordFailedLogin($username);
                } else {
                    $captchaValido = !empty($captchaResult['success']) && !empty($captchaResult['hostname_ok']);
                    
                    if (!$captchaValido) {
                        $errorCodes = $captchaResult['error_codes'] ?? [];
                        $errorMessage = 'Error desconocido';
                        
                        if (in_array('invalid-keys', $errorCodes, true) || in_array('invalid-input-secret', $errorCodes, true)) {
                            $errorMessage = 'Configuración del servidor incorrecta';
                        } elseif (in_array('missing-input-response', $errorCodes, true)) {
                            $errorMessage = 'Por favor completa el reCAPTCHA';
                        } elseif (in_array('invalid-input-response', $errorCodes, true)) {
                            $errorMessage = 'Respuesta de reCAPTCHA inválida';
                        } elseif (in_array('timeout-or-duplicate', $errorCodes, true)) {
                            $errorMessage = 'La verificación expiró, por favor inténtalo de nuevo';
                        }
                        
                        $mensaje = '<div class="error">Error en la verificación del reCAPTCHA: ' . $errorMessage . '</div>';
                        // Registrar intento fallido por captcha inválido
                        RateLimiter::recordFailedLogin($username);
                    }
                }
            }
            
            if (!$captchaValido) {
                // El mensaje ya fue establecido en el bloque anterior
            } else {
                $m = $o->existe();
                
                if ($m['resultado'] == 'existe') {
                    // Registrar login exitoso
                    RateLimiter::recordSuccessfulLogin($username);
                    
                    session_destroy();
                    session_start();

                    $_SESSION['name'] = $m['mensaje'] ?? '';
                    $_SESSION['nombre_rol'] = $m['nombre_rol'] ?? '';
                    $_SESSION['id_usuario'] = $m['id_usuario'] ?? '';
                    $_SESSION['id_rol'] = $m['id_rol'] ?? '';
                    $_SESSION['cedula'] = $m['cedula'] ?? '';
                    $_SESSION['foto_perfil'] = $m['foto_perfil'] ?? '';
                    
                    // Generar token JWT después de iniciar sesión exitosamente
                    try {
                        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
                        Auth::setTokenCookie($token);
                        $_SESSION['jwt_token_created'] = true;
                    } catch (Exception $e) {
                        error_log("Error al generar JWT en login: " . $e->getMessage());
                        // Continuar con el flujo normal incluso si falla la generación del token
                    }
                    
                    // Verificación de WebSocket movida a background para no bloquear el login
                    // Solo verificar si estamos en localhost
                    if ($is_localhost) {
                        register_shutdown_function(function() {
                            @require_once __DIR__ . '/../../verificar_websocket.php';
                            verificarEIniciarWebSocket();
                        });
                    }
                    
                    header('Location: ?pagina=' . ($_SESSION['nombre_rol'] === 'Cliente' ? 'catalogo' : 'dashboard'));
                    exit;
                } elseif ($m['resultado'] == 'bloqueado') {
                    $mensaje = '<div class="error">' . ($m['mensaje'] ?? 'Usuario bloqueado') . '</div>';
                    // Registrar intento fallido por usuario bloqueado
                    RateLimiter::recordFailedLogin($username);
                } else {
                    $mensaje = '<div class="error">' . ($m['mensaje'] ?? 'Error en las credenciales') . '</div>';
                    // Registrar intento fallido por credenciales incorrectas
                    RateLimiter::recordFailedLogin($username);
                }
            }
        }
    }
}

    if ($h == 'solicitar_recuperacion') {
        $email = $_POST['email'] ?? '';
        $resultado = $o->solicitarRecuperacion($email);
        
        if ($resultado['status'] == 'success') {
            $mensaje = "Se ha enviado un enlace de recuperación a tu correo electrónico.";
        } else {
            $mensaje = $resultado['mensaje'] ?? 'Error al procesar la solicitud';
        }
    }

    // Registro de usuario
    if ($h == 'registrar') {
        $datos = [
            'nombre_usuario' => $_POST['nombre_usuario'] ?? '',
            'clave' => $_POST['clave'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'correo' => $_POST['correo'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'cedula' => $_POST['cedula'] ?? '',
            'direccion' => $_POST['direccion'] ?? ''
        ];
        
        // Validar datos de entrada
        $errores = $o->validarRegistroUsuarioDatos($datos);
        if (!empty($errores)) {
            $mensaje = '<div class="error">Por favor corrija los siguientes errores:<br>';
            foreach ($errores as $campo => $error) {
                $mensaje .= "• $error<br>";
            }
            $mensaje .= '</div>';
        } else {
            $resultado = $o->registrarUsuarioYCliente($datos);
            if ($resultado['status'] == 'success') {
                $mensaje = '<span class="success">' . $resultado['mensaje'] . '</span>';
            } else {
                $mensaje = '<span class="error">' . $resultado['mensaje'] . '</span>';
            }
        }
    }
}

// Cargar la vista
$vista = __DIR__ . '/../../Vista/login.php';
if (file_exists($vista)) {
    require_once $vista;
} else {
    die("Error: No se pudo cargar la vista de login");
}
