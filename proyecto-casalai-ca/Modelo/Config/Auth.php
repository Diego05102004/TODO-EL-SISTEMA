<?php
namespace Usuario\ProyectoCasalaiCa\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

/**
 * Clase de autenticación JWT para gestión de tokens de acceso
 * 
 * Esta clase proporciona métodos para generar, validar y gestionar tokens JWT
 * con soporte híbrido para aplicaciones web (cookies) y móviles (headers).
 * 
 * @package Usuario\ProyectoCasalaiCa\Config
 * @version 1.0.0
 */
class Auth {
    
    /**
     * Carga variables de entorno desde archivo .env (con cache)
     * 
     * @return void
     */
    private static function loadEnv() {
        static $loaded = false;
        
        if ($loaded) {
            return; // Ya cargado, no repetir
        }
        
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                if (strpos($line, '=') === false) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
        
        $loaded = true;
    }
    
    /**
     * Obtiene la clave secreta desde variables de entorno
     * 
     * @return string Clave secreta
     */
    private static function getSecretKey() {
        self::loadEnv();
        return $_ENV['JWT_SECRET_KEY'] ?? 'd6e32e8d9c57eeef6fb09bebe9cb579bc75836c0d8327914bf689369d4e5f76b';
    }
    
    /**
     * Obtiene el issuer desde variables de entorno
     * 
     * @return string Issuer
     */
    private static function getIssuer() {
        self::loadEnv();
        return $_ENV['JWT_ISSUER'] ?? 'http://localhost/proyecto-casalai-ca/';
    }
    
    /**
     * Obtiene el audience desde variables de entorno
     * 
     * @return string Audience
     */
    private static function getAudience() {
        self::loadEnv();
        return $_ENV['JWT_AUDIENCE'] ?? 'proyecto-casalai-ca';
    }
    
    /**
     * Algoritmo de firma del token
     * 
     * @var string
     */
    private static $algorithm = 'HS256';
    
    /**
     * Tiempo de expiración del token en segundos (8 horas)
     * 
     * @var int
     */
    private static $tokenExpiration = 28800; // 8 horas (28800 segundos)
    
    /**
     * Nombre de la cookie para almacenar el token JWT
     * 
     * @var string
     */
    private static $cookieName = 'jwt_token';
    
    /**
     * Genera un token JWT para un usuario autenticado
     * 
     * @param int $userId ID del usuario
     * @param string $userRole Rol del usuario (Administrador, SuperUsuario, cliente, etc.)
     * @return string Token JWT generado
     * @throws Exception Si hay error al generar el token
     */
    public static function generateToken($userId, $userRole, $customExpiration = null) {
        try {
            $issuedAt = time();
            $expire = $issuedAt + ($customExpiration !== null ? (int) $customExpiration : self::$tokenExpiration);
            
            $payload = [
                'iat' => $issuedAt,           // Tiempo de emisión
                'exp' => $expire,             // Tiempo de expiración
                'sub' => $userId,             // ID del usuario (subject)
                'role' => $userRole,          // Rol del usuario
                'type' => 'access',           // Tipo de token
                'iss' => self::getIssuer(),   // Issuer (quién emitió el token)
                'aud' => self::getAudience()  // Audience (a quién está destinado)
            ];
            
            $token = JWT::encode($payload, self::getSecretKey(), self::$algorithm);
            
            return $token;
        } catch (Exception $e) {
            error_log("Error al generar JWT: " . $e->getMessage());
            throw new Exception("Error al generar token de acceso");
        }
    }
    
    /**
     * Establece el token JWT en una cookie HttpOnly
     * 
     * @param string $token Token JWT a almacenar
     * @param int|null $expirationTime Tiempo de expiración en segundos (opcional, usa el valor por defecto si es null)
     * @return void
     */
    public static function setTokenCookie($token, $expirationTime = null) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $isLocalhost = $_SERVER['SERVER_NAME'] === 'localhost' || 
                       $_SERVER['SERVER_NAME'] === '127.0.0.1';
        
        // En localhost, no usar Secure para permitir HTTP
        $secureFlag = $isSecure && !$isLocalhost;
        
        // Usar el tiempo de expiración proporcionado o el valor por defecto
        $expires = $expirationTime !== null ? time() + $expirationTime : time() + self::$tokenExpiration;
        
        // Cambiar SameSite a Lax para mejor compatibilidad
        $cookieOptions = [
            'expires' => $expires,
            'path' => '/',
            'domain' => '', // Dominio actual
            'secure' => $secureFlag,
            'httponly' => true,
            'samesite' => 'Lax' // Cambiado de Strict a Lax para evitar problemas
        ];
        
        setcookie(self::$cookieName, $token, $cookieOptions);
    }
    
    /**
     * Elimina la cookie del token JWT
     * 
     * @return void
     */
    public static function clearTokenCookie() {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $isLocalhost = $_SERVER['SERVER_NAME'] === 'localhost' || 
                       $_SERVER['SERVER_NAME'] === '127.0.0.1';
        
        $secureFlag = $isSecure && !$isLocalhost;
        
        $cookieOptions = [
            'expires' => time() - 3600, // Expirar en el pasado
            'path' => '/',
            'domain' => '',
            'secure' => $secureFlag,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        setcookie(self::$cookieName, '', $cookieOptions);
    }
    
    /**
     * Obtiene el token JWT de la cookie o del header Authorization
     * 
     * @return string|null Token JWT o null si no existe
     */
    public static function getToken() {
        // Primero buscar en cookie HttpOnly
        if (isset($_COOKIE[self::$cookieName])) {
            return $_COOKIE[self::$cookieName];
        }
        // Si no está en cookie, buscar en header Authorization: Bearer
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    /**
     * Evalúa el estado del token JWT sin invalidar la sesión de inmediato.
     *
     * @param string|null $token Token JWT a validar
     * @return array Estado del token: valid|warning|expired|invalid|missing
     */
    public static function inspectToken($token = null) {
        try {
            if ($token === null) {
                $token = self::getToken();
            }

            if ($token === null) {
                return [
                    'status' => 'missing',
                    'payload' => null,
                    'expires_in' => null
                ];
            }

            $decoded = JWT::decode($token, new Key(self::getSecretKey(), self::$algorithm));
            $payload = (array) $decoded;

            if (isset($payload['iss']) && $payload['iss'] !== self::getIssuer()) {
                error_log("Token inválido: issuer no coincide");
                return ['status' => 'invalid', 'payload' => null, 'expires_in' => 0];
            }

            if (isset($payload['aud']) && $payload['aud'] !== self::getAudience()) {
                error_log("Token inválido: audience no coincide");
                return ['status' => 'invalid', 'payload' => null, 'expires_in' => 0];
            }

            $expiresAt = isset($payload['exp']) ? (int) $payload['exp'] : 0;
            $expiresIn = $expiresAt - time();

            if ($expiresIn <= 0) {
                return ['status' => 'expired', 'payload' => null, 'expires_in' => 0];
            }

            if ($expiresIn <= 30) {
                return ['status' => 'warning', 'payload' => $payload, 'expires_in' => $expiresIn];
            }

            return ['status' => 'valid', 'payload' => $payload, 'expires_in' => $expiresIn];
        } catch (ExpiredException $e) {
            error_log("Token expirado: " . $e->getMessage());
            return ['status' => 'expired', 'payload' => null, 'expires_in' => 0];
        } catch (SignatureInvalidException $e) {
            error_log("Firma de token inválida: " . $e->getMessage());
            return ['status' => 'invalid', 'payload' => null, 'expires_in' => 0];
        } catch (Exception $e) {
            error_log("Error al validar token: " . $e->getMessage());
            return ['status' => 'invalid', 'payload' => null, 'expires_in' => 0];
        }
    }

    /**
     * Valida un token JWT y retorna el payload decodificado
     * Soporte híbrido: busca primero en cookie, luego en header Authorization
     * 
     * @param string|null $token Token JWT a validar (opcional, si es null busca en cookie/header)
     * @return array|false Payload decodificado si es válido, false si no es válido
     */
    public static function validateToken($token = null) {
        $tokenState = self::inspectToken($token);
        return in_array($tokenState['status'], ['valid', 'warning'], true) ? $tokenState['payload'] : false;
    }
    
    /**
     * Verifica si el token es válido y retorna el payload
     * Método de conveniencia para uso en controladores
     * 
     * @return array|false Payload decodificado si es válido, false si no
     */
    public static function checkAuth() {
        return self::validateToken();
    }
    
    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param array $payload Payload del token JWT
     * @param array $allowedRoles Array de roles permitidos
     * @return bool True si el rol está permitido, false si no
     */
    public static function hasRole($payload, $allowedRoles) {
        if (!$payload || !isset($payload['role'])) {
            return false;
        }
        
        return in_array($payload['role'], $allowedRoles);
    }
    
    /**
     * Obtiene el ID del usuario desde el payload del token
     * 
     * @param array $payload Payload del token JWT
     * @return int|null ID del usuario o null si no está disponible
     */
    public static function getUserId($payload) {
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }
        
        return (int) $payload['sub'];
    }
    
    /**
     * Obtiene el rol del usuario desde el payload del token
     * 
     * @param array $payload Payload del token JWT
     * @return string|null Rol del usuario o null si no está disponible
     */
    public static function getUserRole($payload) {
        if (!$payload || !isset($payload['role'])) {
            return null;
        }
        
        return $payload['role'];
    }
    
    /**
     * Envía una respuesta de error de autenticación
     * Detecta si es una solicitud API o web y responde apropiadamente
     * 
     * @param string $message Mensaje de error
     * @param int $code Código HTTP (default: 401)
     * @return void
     */
    public static function sendAuthError($message = 'No autorizado', $code = 401) {
        http_response_code($code);
        
        // Destruir la sesión cuando el token expira o es inválido
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
        
        // Limpiar también la cookie del token JWT
        self::clearTokenCookie();
        
        // Obtener el token actual para depuración
        $token = self::getToken();
        
        // Detectar si es una solicitud API (AJAX o Accept: application/json)
        $isApiRequest = isset($_SERVER['HTTP_ACCEPT']) && 
                        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isApiRequest || $isAjaxRequest) {
            // Respuesta JSON para API/AJAX
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'code' => $code,
                'debug_token' => $token ? substr($token, 0, 50) . '...' : 'No token found'
            ]);
        } else {
            // Respuesta HTML para navegadores web
            header('Content-Type: text/html; charset=utf-8');
            $tokenDisplay = $token ? htmlspecialchars(substr($token, 0, 100)) : 'No token found';
            echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Autenticación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
        }
        .error-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .token-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .token-info h3 {
            margin-top: 0;
            color: #495057;
            font-size: 16px;
        }
        .token-info code {
            background: #e9ecef;
            padding: 8px;
            border-radius: 3px;
            font-size: 12px;
            word-break: break-all;
            display: block;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin: 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <h1>Error de Autenticación</h1>
        <p>' . htmlspecialchars($message) . '</p>
        

        
        <div>
            <a href="?pagina=login" class="btn">Ir al Login</a>
        </div>
    </div>
    <script>
        // Mostrar alert y redirigir después de 5 segundos
        setTimeout(function() {
            alert("' . htmlspecialchars($message) . '\\n\\nToken: ' . $tokenDisplay . '");
        }, 500);
    </script>
</body>
</html>';
        }
        exit;
    }
    
    /**
     * Middleware para proteger endpoints que requieren autenticación
     * 
     * @param array $allowedRoles Array de roles permitidos (vacío = cualquier rol autenticado)
     * @return array|false Payload del token si es válido, envía error y termina si no
     */
    public static function requireAuth($allowedRoles = []) {
        $payload = self::validateToken();
        
        if (!$payload) {
            self::sendAuthError('Token inválido o expirado. Por favor, inicie sesión nuevamente.');
        }
        
        // Verificar roles si se especificaron
        if (!empty($allowedRoles)) {
            if (!self::hasRole($payload, $allowedRoles)) {
                self::sendAuthError('No tiene permisos suficientes para acceder a este recurso.', 403);
            }
        }
        
        return $payload;
    }
    
    /**
     * Obtiene el tiempo de expiración del token en segundos
     * 
     * @return int Tiempo de expiración en segundos
     */
    public static function getTokenExpiration() {
        return self::$tokenExpiration;
    }
    
    /**
     * Extiende el token JWT actual por un tiempo adicional
     * 
     * @param int $extensionTime Tiempo de extensión en segundos
     * @return string|false Nuevo token JWT o false si falla
     */
    public static function extendToken($extensionTime) {
        try {
            // Validar el token actual
            $payload = self::validateToken();
            
            if (!$payload) {
                return false;
            }
            
            // Generar nuevo token con el tiempo extendido
            $issuedAt = time();
            $expire = $issuedAt + $extensionTime;
            
            $newPayload = [
                'iat' => $issuedAt,           // Tiempo de emisión
                'exp' => $expire,             // Nuevo tiempo de expiración
                'sub' => $payload['sub'],     // ID del usuario (subject)
                'role' => $payload['role'],   // Rol del usuario
                'type' => 'access',           // Tipo de token
                'iss' => self::getIssuer(),   // Issuer (quién emitió el token)
                'aud' => self::getAudience()  // Audience (a quién está destinado)
            ];
            
            $newToken = JWT::encode($newPayload, self::getSecretKey(), self::$algorithm);
            
            return $newToken;
        } catch (Exception $e) {
            error_log("Error al extender JWT: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el nombre de la cookie del token
     * 
     * @return string Nombre de la cookie
     */
    public static function getCookieName() {
        return self::$cookieName;
    }
}
