<?php
/**
 * Endpoint de Login para la API
 * POST /api/login.php
 */

error_log("[LOGIN] Archivo login.php cargado - DEBUG INICIO");

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Clases/Login.php';
require_once __DIR__ . '/Config/Encryption.php';
require_once __DIR__ . '/Config/RecaptchaConfig.php';

use Usuario\ProyectoCasalaiCa\Login;
use Usuario\ProyectoCasalaiCa\Config\Encryption;

error_log("[LOGIN] Archivos requeridos cargados exitosamente");

function validarReCaptcha($token) {
    if (!is_string($token)) {
        return ['success' => false, 'error_codes' => ['invalid-input-response']];
    }

    return \Usuario\ProyectoCasalaiCa\Config\RecaptchaConfig::verifyToken(
        $token,
        $_SERVER['REMOTE_ADDR'] ?? null
    );
}

// Solo permitir método POST
validateMethod(['POST']);
try{
$data = getRequestData();
if (!is_array($data)) {
    errorResponse('El cuerpo de la solicitud no es válido', 400);
}
    
    // Desencriptar datos sensibles si están cifrados
    $encryption = new Encryption();
    $sensitiveFields = ['email', 'username', 'password'];
    foreach ($sensitiveFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            try {
                if (is_string($data[$field])) {
                    $data[$field] = str_replace(' ', '+', $data[$field]);
                }
                $decryptedValue = $encryption->decrypt($data[$field]);
                $data[$field] = $decryptedValue;
            } catch (\Throwable $decryptError) {
                error_log("[LOGIN] No se pudo descifrar el campo '$field': " . $decryptError->getMessage());
                errorResponse('No se pudieron descifrar los datos de inicio de sesión', 400);
            }
        }
    }


    $usernameOrEmail = $data['email'] ?? $data['username'] ?? '';
    $recaptchaToken  = $data['recaptcha_token'] ?? $data['recaptchaToken'] ?? '';

    if (empty($usernameOrEmail) || empty($data['password'])) {
        errorResponse('El correo/usuario y contraseña son obligatorios', 400);
    }
    
    // Validar datos desencriptados manualmente (después de desencriptar)
    if (mb_strlen($usernameOrEmail) < 4 || mb_strlen($usernameOrEmail) > 20) {
        errorResponse('El nombre de usuario debe tener entre 4 y 20 caracteres', 400);
    }
    
    if (mb_strlen($data['password']) < 6) {
        errorResponse('La contraseña debe tener al menos 6 caracteres', 400);
    }

    if (empty($recaptchaToken)) {
        errorResponse('Es necesario completar la verificación del reCAPTCHA', 400);
    }

    $resCaptcha = validarReCaptcha($recaptchaToken);
    if (!empty($resCaptcha['configuration_error'])) {
        error_log('[reCAPTCHA] Configuración del API incompleta o inválida');
        errorResponse('La verificación de reCAPTCHA no está configurada correctamente', 503);
    }
    if (
        empty($resCaptcha['success']) ||
        empty($resCaptcha['hostname_ok'])
    ) {
        error_log('[reCAPTCHA] Token rechazado: ' . json_encode([
            'error_codes' => $resCaptcha['error_codes'] ?? [],
            'hostname_ok' => $resCaptcha['hostname_ok'] ?? false,
            'challenge_ts' => $resCaptcha['challenge_ts'] ?? null,
        ]));
        $errorCodes = $resCaptcha['error_codes'] ?? [];
        if (in_array('invalid-keys', $errorCodes, true) || in_array('invalid-input-secret', $errorCodes, true)) {
            error_log('[reCAPTCHA] Google rechazó la configuración de claves; verifica que la secret key corresponda a la site key v2.');
            errorResponse('La configuración de reCAPTCHA no coincide. Contacta al administrador.', 503);
        }
        if (in_array('timeout-or-duplicate', $errorCodes, true)) {
            errorResponse('La verificación expiró o ya fue utilizada. Completa reCAPTCHA otra vez.', 400);
        }
        errorResponse('La verificación de reCAPTCHA ha fallado. Completa el reto otra vez.', 400);
    }

    // Crear instancia de Login
    $login = new Login();
    
    // Validación básica (solo verifica que campos no estén vacíos)
    $datosValidacion = [
        'username' => $usernameOrEmail,
        'password' => $data['password']
    ];
    
    $errores = $login->validarInicioSesionDatos($datosValidacion);
    
    if (!empty($errores)) {
        errorResponse('Error de validación', 400, $errores);
    }
    
    $login->setUsername($usernameOrEmail);
    $login->setPassword($data['password']);
    
    $resultado = $login->existe();
    
    if ($resultado['resultado'] == 'existe') {
        $cedulaUsuario = $resultado['cedula'] ?? '';
        if (is_string($cedulaUsuario) && $cedulaUsuario !== '') {
            $cedulaUsuario = $encryption->decryptIfEncrypted($cedulaUsuario);
        }

        $userData = [
            'id_usuario' => $resultado['id_usuario'],
            'username'   => $resultado['mensaje'],
            'nombre_rol' => $resultado['nombre_rol'],
            'id_rol'     => $resultado['id_rol'],
            'cedula'     => $cedulaUsuario,
            'foto_perfil' => $resultado['foto_perfil']
        ];
        
        $token = Auth::generateToken($resultado['id_usuario'], $userData);
        
        successResponse([
            'token'      => $token,
            'user'       => $userData,
            'expires_in' => 86400
        ], 'Login exitoso');
        
    } elseif ($resultado['resultado'] == 'bloqueado') {
        errorResponse($resultado['mensaje'], 403);
    } else {
        errorResponse($resultado['mensaje'], 401);
    }
    
} catch (Exception $e) {
    error_log('[LOGIN] Error al procesar autenticación: ' . $e->getMessage());
    errorResponse('No se pudo completar el inicio de sesión.', 500);
}