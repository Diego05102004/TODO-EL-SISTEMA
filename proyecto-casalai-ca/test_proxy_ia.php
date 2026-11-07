<?php
// Script de prueba del proxy IA

require_once 'Modelo/Servicio/RecepcionIAProxy.php';

use Usuario\ProyectoCasalaiCa\Modelo\Servicio\RecepcionIAProxy;

echo "=== Prueba del Proxy IA ===\n\n";

// Crear instancia del proxy
$proxy = new RecepcionIAProxy('http://127.0.0.1:8000');

echo "1. Probando health check...\n";
$resultado = $proxy->health();
echo "   HTTP Status: " . $resultado['http_status'] . "\n";
echo "   Data: " . json_encode($resultado['data'], JSON_PRETTY_PRINT) . "\n";

if ($resultado['http_status'] === 503) {
    echo "   ❌ Error 503 - Detalle: " . ($resultado['data']['detail'] ?? 'Sin detalle') . "\n";
} else {
    echo "   ✅ Éxito\n";
}

echo "\n=== Fin de prueba ===\n";
