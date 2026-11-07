<?php
// Script de prueba para verificar conexión al microservicio IA desde PHP

echo "=== Prueba de conexión al microservicio IA ===\n\n";

// 1. Probar con file_get_contents
echo "1. Probando con file_get_contents...\n";
$url = 'http://127.0.0.1:8000/health';
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

try {
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        echo "   ❌ file_get_contents falló\n";
        echo "   Error: " . error_get_last()['message'] . "\n";
    } else {
        echo "   ✅ file_get_contents funcionó\n";
        echo "   Respuesta: " . $response . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Probar con cURL
echo "2. Probando con cURL...\n";
if (!function_exists('curl_init')) {
    echo "   ❌ cURL no está disponible en PHP\n";
} else {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        echo "   ❌ cURL falló\n";
        echo "   Error: " . $error . "\n";
    } else {
        echo "   ✅ cURL funcionó\n";
        echo "   HTTP Code: " . $httpCode . "\n";
        echo "   Respuesta: " . $response . "\n";
    }
}

echo "\n";

// 3. Verificar variable de entorno
echo "3. Verificando variable de entorno RECEPCION_IA_URL...\n";
$envUrl = getenv('RECEPCION_IA_URL');
echo "   Valor: " . ($envUrl ?: 'no definida, usa default http://127.0.0.1:8000') . "\n";

echo "\n=== Fin de pruebas ===\n";
