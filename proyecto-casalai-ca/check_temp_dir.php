<?php
echo "Directorio temporal del sistema: " . sys_get_temp_dir() . "\n";
echo "Ruta completa del circuit breaker: " . sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'casalai-recepcion-ia-circuit.json' . "\n";

// Verificar si existe
$ruta = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'casalai-recepcion-ia-circuit.json';
if (file_exists($ruta)) {
    echo "✅ El archivo existe\n";
    echo "Contenido: " . file_get_contents($ruta) . "\n";
} else {
    echo "❌ El archivo NO existe\n";
}
