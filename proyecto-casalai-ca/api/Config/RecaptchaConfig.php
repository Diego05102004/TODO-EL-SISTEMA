<?php
namespace Usuario\ProyectoCasalaiCa\Config;

final class RecaptchaConfig
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function getSiteKey(): string
    {
        return self::getValue('RECAPTCHA_SITE_KEY');
    }

    public static function verifyToken(string $token, ?string $remoteIp = null): array
    {
        $secretKey = self::getValue('RECAPTCHA_SECRET_KEY');
        if ($secretKey === '') {
            return ['success' => false, 'configuration_error' => true];
        }
        if (trim($token) === '') {
            return ['success' => false, 'error_codes' => ['missing-input-response']];
        }

        $postFields = [
            'secret' => $secretKey,
            'response' => trim($token),
        ];
        if ($remoteIp !== null && $remoteIp !== '') {
            $postFields['remoteip'] = $remoteIp;
        }

        $curl = curl_init(self::VERIFY_URL);
        if ($curl === false) {
            return ['success' => false, 'error' => 'No se pudo iniciar la verificación'];
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            error_log('[reCAPTCHA] Error de conexión con Google: ' . $curlError);
            return ['success' => false, 'error' => 'No se pudo verificar el token'];
        }

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !is_array($result)) {
            error_log('[reCAPTCHA] Respuesta inválida de Google (HTTP ' . $httpCode . ')');
            return ['success' => false, 'error' => 'Respuesta de verificación inválida'];
        }

        $hostname = strtolower(trim((string) ($result['hostname'] ?? '')));
        $hostname = preg_replace('/:\d+$/', '', $hostname);
        $configuredHostnames = self::getValue('RECAPTCHA_ALLOWED_HOSTNAMES');
        $allowedHostnames = $configuredHostnames === ''
            ? ['casalai.alwaysdata.net', 'localhost', '127.0.0.1']
            : array_values(array_filter(array_map(
                static function ($host) {
                    $host = strtolower(trim($host));
                    return preg_replace('/:\d+$/', '', $host);
                },
                explode(',', $configuredHostnames)
            )));

        return [
            'success' => ($result['success'] ?? false) === true,
            'hostname' => $hostname,
            'hostname_ok' => in_array($hostname, $allowedHostnames, true),
            'error_codes' => $result['error-codes'] ?? [],
            'challenge_ts' => $result['challenge_ts'] ?? null,
        ];
    }

    private static function getValue(string $name): string
    {
        $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $requestHost)) {
            $privateValue = self::getPrivateValue($name);
            if ($privateValue !== '') {
                return $privateValue;
            }
        }

        foreach ([getenv($name), $_SERVER[$name] ?? null, $_ENV[$name] ?? null] as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        return self::getPrivateValue($name);
    }

    private static function getPrivateValue(string $name): string
    {
        $envPath = __DIR__ . '/.env';
        if (!is_file($envPath)) {
            return '';
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            throw new \RuntimeException('No se pudo leer la configuración privada de reCAPTCHA.');
        }

        $pattern = '/^' . preg_quote($name, '/') . '\s*=\s*(?:"(?P<double>[^"\r\n]*)"|\'(?P<single>[^\r\n]*)\'|(?P<plain>[^#\r\n]*))/m';
        if (!preg_match($pattern, $contents, $matches, PREG_UNMATCHED_AS_NULL)) {
            return '';
        }

        foreach (['double', 'single', 'plain'] as $group) {
            if ($matches[$group] !== null) {
                return trim($matches[$group]);
            }
        }

        return '';
    }
}
