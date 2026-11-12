<?php
/**
 * Configuración de URLs para la API
 * Centraliza las URLs base para desarrollo y producción
 */

// Detectar entorno
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$env = getenv('APP_ENV') ?: ($host === 'casalai.alwaysdata.net' ? 'production' : 'development');

// Configuración de URLs según entorno
$config = [
    'development' => [
        'base_url' => 'http://localhost/proyecto-casalai-ca',
        'api_base_url' => 'http://localhost/proyecto-casalai-ca/api',
        'assets_url' => 'http://localhost/proyecto-casalai-ca/assets',
        'images_url' => 'http://localhost/proyecto-casalai-ca/assets/img',
    ],
    'production' => [
        'base_url' => 'https://casalai.alwaysdata.net',
        'api_base_url' => 'https://casalai.alwaysdata.net/api',
        'assets_url' => 'https://casalai.alwaysdata.net/assets',
        'images_url' => 'https://casalai.alwaysdata.net/assets/img',
    ]
];

// Configuración actual basada en el entorno
$currentConfig = $config[$env] ?? $config['development'];

/**
 * Obtiene la URL base configurada
 * @return string URL base
 */
function getBaseUrl() {
    return getCurrentConfig()['base_url'];
}

/**
 * Obtiene la URL base de la API
 * @return string URL base de la API
 */
function getApiBaseUrl() {
    return getCurrentConfig()['api_base_url'];
}

/**
 * Obtiene la URL de assets
 * @return string URL de assets
 */
function getAssetsUrl() {
    return getCurrentConfig()['assets_url'];
}

/**
 * Obtiene la URL de imágenes
 * @return string URL de imágenes
 */
function getImagesUrl() {
    return getCurrentConfig()['images_url'];
}

function getCurrentConfig() {
    global $config, $env;
    return $config[$env] ?? $config['development'];
}

/**
 * Construye URL completa para una imagen
 * @param string $imagePath Ruta relativa de la imagen
 * @return string URL completa de la imagen
 */
function getImageUrl($imagePath) {
    if (!is_string($imagePath) || trim($imagePath) === '') {
        return null;
    }

    $imagePath = str_replace('\\', '/', trim($imagePath));
    if (preg_match('#^https?://#i', $imagePath)) {
        return $imagePath;
    }

    $imagePath = ltrim($imagePath, '/');
    if (strpos($imagePath, 'assets/') === 0) {
        $relativePath = $imagePath;
    } elseif (strpos($imagePath, 'img/') === 0) {
        $relativePath = 'assets/' . $imagePath;
    } elseif (strpos($imagePath, 'productos/') === 0) {
        $relativePath = 'assets/img/' . $imagePath;
    } else {
        $relativePath = 'assets/img/productos/' . $imagePath;
    }

    return rtrim(getBaseUrl(), '/') . '/' . $relativePath;
}

/**
 * Obtiene el entorno actual
 * @return string Entorno (development|production)
 */
function getEnvironment() {
    global $env;
    return $env;
}
