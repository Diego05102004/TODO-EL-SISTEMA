<?php
/**
 * Sistema de autenticación con JWT para la API
 */

class Auth {
    
    private static function getSecretKey() {
        $secretKey = function_exists('apiEnvironmentValue')
            ? apiEnvironmentValue('JWT_SECRET_KEY')
            : (getenv('JWT_SECRET_KEY') ?: '');

        if (!is_string($secretKey) || strlen($secretKey) < 32) {
            throw new RuntimeException('JWT_SECRET_KEY no está configurada o no cumple la longitud mínima.');
        }

        return $secretKey;
    }

    private static $tokenExpiration = 86400;
    
    /**
     * Generar un token JWT
     */
    public static function generateToken($userId, $userData) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $issuedAt = time();
        $expire = $issuedAt + self::$tokenExpiration;
        
        $payload = [
            'iss' => 'casalai-api',
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'data' => $userData
        ];
        
        // Codificar header y payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Crear firma
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::getSecretKey(), true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        // Retornar token
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Verificar y decodificar un token JWT
     */
    public static function verifyToken($token) {
        if (empty($token)) {
            return null;
        }
        
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) !== 3) {
            return null;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $tokenParts;
        
        // Verificar firma
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::getSecretKey(), true);
        $expectedSignature = self::base64UrlEncode($signature);

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }

        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (
            !is_array($header) ||
            ($header['alg'] ?? null) !== 'HS256' ||
            !is_array($payload) ||
            !isset($payload['exp'], $payload['user_id']) ||
            !is_numeric($payload['exp']) ||
            !is_numeric($payload['user_id']) ||
            (int) $payload['user_id'] < 1 ||
            (int) $payload['exp'] <= time()
        ) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Obtener el token del header Authorization
     */
    public static function getTokenFromHeader() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (!is_array($headers)) {
            $headers = [];
        }
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $authHeader = $value;
                break;
            }
        }

        if (is_string($authHeader) && preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function checkAuth() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return false;
        }
        
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Obtener datos del usuario autenticado
     */
    public static function getUserData() {
        $payload = self::checkAuth();
        
        if (!$payload) {
            return null;
        }
        
        return $payload['data'] ?? null;
    }
    
    /**
     * Obtener ID del usuario autenticado
     */
    public static function getUserId() {
        $payload = self::checkAuth();
        
        if (!$payload) {
            return null;
        }
        
        return $payload['user_id'] ?? null;
    }
    
    /**
     * Codificar en Base64 URL-safe
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodificar desde Base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Refrescar token (generar uno nuevo)
     */
    public static function refreshToken($token) {
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return null;
        }
        
        // Generar nuevo token con los mismos datos
        return self::generateToken($payload['user_id'], $payload['data']);
    }
}
