<?php
namespace Usuario\ProyectoCasalaiCa\Config;

use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use RuntimeException;

require_once __DIR__ . '/loadEnv.php';

final class WebSocketConfig
{
    private const JWT_ISSUER = 'casalai-web';
    private const JWT_AUDIENCE = 'casalai-websocket';
    private const JWT_LIFETIME_SECONDS = 90;

    private static function env(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);
        if ($value !== false && trim($value) !== '') {
            return trim($value);
        }

        $value = $_ENV[$name] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $default;
    }

    public static function settings(?string $requestHost = null): array
    {
        $portValue = self::env('WEBSOCKET_PORT', '8080');
        if (!ctype_digit($portValue)) {
            throw new RuntimeException('WEBSOCKET_PORT debe ser un puerto numérico.');
        }

        $port = (int)$portValue;
        $isAlwaysDataPort = $port >= 8300 && $port <= 8499;
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('WEBSOCKET_PORT está fuera del rango válido.');
        }

        $production = self::env('APP_ENV', '') === 'production' || $isAlwaysDataPort;
        if ($production && !$isAlwaysDataPort) {
            throw new RuntimeException('En producción de AlwaysData WEBSOCKET_PORT debe estar entre 8300 y 8499.');
        }

        $tlsCertificate = self::env('WEBSOCKET_TLS_CERT');
        $tlsPrivateKey = self::env('WEBSOCKET_TLS_KEY');
        if (($tlsCertificate === null) !== ($tlsPrivateKey === null)) {
            throw new RuntimeException('WEBSOCKET_TLS_CERT y WEBSOCKET_TLS_KEY deben configurarse juntos.');
        }
        if ($production && ($tlsCertificate === null || $tlsPrivateKey === null)) {
            throw new RuntimeException('AlwaysData requiere certificado TLS y clave para habilitar WSS en el puerto del servicio.');
        }
        if ($tlsCertificate !== null && (!is_file($tlsCertificate) || !is_readable($tlsCertificate))) {
            throw new RuntimeException('WEBSOCKET_TLS_CERT no existe o no es legible.');
        }
        if ($tlsPrivateKey !== null && (!is_file($tlsPrivateKey) || !is_readable($tlsPrivateKey))) {
            throw new RuntimeException('WEBSOCKET_TLS_KEY no existe o no es legible.');
        }

        $publicUrl = self::env('WEBSOCKET_PUBLIC_URL');
        if ($publicUrl === null && $requestHost !== null && self::isLocalHost($requestHost)) {
            $publicUrl = 'ws://127.0.0.1:' . $port;
        }
        if ($production && ($publicUrl === null || stripos($publicUrl, 'wss://') !== 0)) {
            throw new RuntimeException('Configure WEBSOCKET_PUBLIC_URL con una dirección wss:// de AlwaysData.');
        }
        if (!$production && $publicUrl !== null && stripos($publicUrl, 'ws://') !== 0 && stripos($publicUrl, 'wss://') !== 0) {
            throw new RuntimeException('WEBSOCKET_PUBLIC_URL debe comenzar con ws:// o wss://.');
        }
        if ($production && (int)parse_url($publicUrl, PHP_URL_PORT) !== $port) {
            throw new RuntimeException('El puerto de WEBSOCKET_PUBLIC_URL debe coincidir con WEBSOCKET_PORT.');
        }

        return [
            'port' => $port,
            'address' => self::env('WEBSOCKET_BIND_ADDRESS', '0.0.0.0'),
            'production' => $production,
            'tls_certificate' => $tlsCertificate,
            'tls_private_key' => $tlsPrivateKey,
            'public_url' => $publicUrl,
            'allowed_origins' => self::allowedOrigins($production),
        ];
    }

    private static function isLocalHost(string $host): bool
    {
        $hostname = strtolower((string)(parse_url('//' . $host, PHP_URL_HOST) ?: $host));
        return in_array($hostname, ['localhost', '127.0.0.1', '::1'], true);
    }

    private static function allowedOrigins(bool $production): array
    {
        $configured = self::env('WEBSOCKET_ALLOWED_ORIGINS');
        if ($configured === null) {
            if ($production) {
                throw new RuntimeException('Configure WEBSOCKET_ALLOWED_ORIGINS para proteger el handshake.');
            }
            return ['localhost', '127.0.0.1'];
        }

        $origins = [];
        foreach (explode(',', $configured) as $entry) {
            $entry = trim($entry);
            if ($entry === '' || $entry === '*') {
                throw new RuntimeException('WEBSOCKET_ALLOWED_ORIGINS debe contener hosts explícitos, no comodines.');
            }
            $host = parse_url(strpos($entry, '://') !== false ? $entry : 'https://' . $entry, PHP_URL_HOST);
            if (!is_string($host) || $host === '') {
                throw new RuntimeException('Hay un host inválido en WEBSOCKET_ALLOWED_ORIGINS.');
            }
            $origins[] = strtolower($host);
        }

        return array_values(array_unique($origins));
    }

    private static function jwtSecret(): string
    {
        $secret = self::env('WEBSOCKET_JWT_SECRET');
        if ($secret === null || strlen($secret) < 32) {
            throw new RuntimeException('WEBSOCKET_JWT_SECRET debe configurarse con al menos 32 caracteres aleatorios.');
        }
        return $secret;
    }

    public static function validateSecret(): void
    {
        self::jwtSecret();
    }

    public static function issueToken(int $userId): string
    {
        if ($userId < 1) {
            throw new RuntimeException('No se puede emitir un token WebSocket para un usuario inválido.');
        }

        $now = time();
        return JWT::encode([
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'typ' => 'websocket',
            'sub' => (string)$userId,
            'iat' => $now,
            'nbf' => $now - 1,
            'exp' => $now + self::JWT_LIFETIME_SECONDS,
        ], self::jwtSecret(), 'HS256');
    }

    public static function verifyToken(string $token): int
    {
        $claims = (array)JWT::decode($token, new Key(self::jwtSecret(), 'HS256'));
        $subject = filter_var($claims['sub'] ?? null, FILTER_VALIDATE_INT);
        $issuedAt = filter_var($claims['iat'] ?? null, FILTER_VALIDATE_INT);

        if (($claims['iss'] ?? null) !== self::JWT_ISSUER
            || ($claims['aud'] ?? null) !== self::JWT_AUDIENCE
            || ($claims['typ'] ?? null) !== 'websocket'
            || $subject === false
            || $subject < 1
            || $issuedAt === false
            || $issuedAt > time()
            || time() - $issuedAt > self::JWT_LIFETIME_SECONDS) {
            throw new RuntimeException('Token WebSocket inválido.');
        }

        return (int)$subject;
    }

}
