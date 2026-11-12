<?php
/**
 * Configuración general para la API
 * Establece headers, CORS y configuraciones base
 */

$requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$isProduction = (getenv('APP_ENV') ?: '') === 'production'
    || $requestHost === 'casalai.alwaysdata.net';
error_reporting(E_ALL);
ini_set('display_errors', $isProduction ? '0' : '1');

// Headers para API
$allowedOrigins = [
    'https://casalai.alwaysdata.net',
];
if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $requestHost)) {
    $allowedOrigins[] = 'http://localhost:8081';
    $allowedOrigins[] = 'http://127.0.0.1:8081';
}

$allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '') {
    if (!in_array($origin, $allowedOrigins, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => 'Origen no permitido']);
        exit;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Private-Network: true');
header('Vary: Origin');
header('Content-Type: application/json; charset=UTF-8');

function apiEnvironmentValue($name) {
    $value = getenv($name);
    if ($value !== false && trim($value) !== '') {
        return trim($value, " \t\n\r\0\x0B\"'");
    }

    foreach ([$_SERVER[$name] ?? '', $_ENV[$name] ?? ''] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value, " \t\n\r\0\x0B\"'");
        }
    }

    $envPath = __DIR__ . '/Config/.env';
    if (!is_file($envPath)) {
        return '';
    }

    $contents = file_get_contents($envPath);
    if ($contents === false) {
        throw new RuntimeException('No se pudo leer la configuración privada de la API.');
    }

    $pattern = '/^' . preg_quote($name, '/') . '\s*=\s*(?:"(?P<double>[^"\r\n]*)"|\'(?P<single>[^\'\r\n]*)\'|(?P<plain>[^#\r\n]*))/m';
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once __DIR__ . '/api_config.php';

// Log para debugging sin consumir php://input.
// El cuerpo JSON solo puede leerse una vez y getRequestData() es quien debe procesarlo.
error_log('API Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir configuración de base de datos local
require_once __DIR__ . '/Config/database.php';

// Incluir clase BD directamente para asegurar disponibilidad
require_once __DIR__ . '/Config/BD.php';

// Incluir autoload de Composer local
require_once __DIR__ . '/vendor/autoload.php';

// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Respuesta JSON estándar
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Respuesta de error
function errorResponse($message, $statusCode = 400, $errors = null) {
    $response = [
        'status' => 'error',
        'message' => $message
    ];
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    jsonResponse($response, $statusCode);
}

// Respuesta de éxito
function successResponse($data, $message = 'Operación exitosa') {
    $response = [
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ];
    
    jsonResponse($response, 200);
}

// Obtener datos de la petición
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');

    $rawBody = file_get_contents('php://input');
    if ($rawBody !== false && trim($rawBody) !== '') {
        $data = json_decode($rawBody, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        if (stripos($contentType, 'application/json') !== false) {
            error_log('JSON inválido recibido por la API: ' . json_last_error_msg());
            return [];
        }
    }
    
    return $_POST;
}

// Validar método HTTP
function validateMethod($allowedMethods) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (!in_array($method, $allowedMethods)) {
        errorResponse('Método no permitido', 405);
    }
}
