<?php
/**
 * Comando para cerrar todas las conexiones WebSocket de forma segura
 */

echo "=== CERRANDO CONEXIONES WEBSOCKET ===\n";

// Método 1: Cerrar el servidor WebSocket principal
echo "1. Buscando procesos PHP en el puerto 8080...\n";

// Obtener todos los procesos PHP que podrían estar ejecutando el WebSocket
exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);

$websocketPids = [];
foreach ($output as $line) {
    if (strpos($line, 'php.exe') !== false) {
        $parts = str_getcsv($line);
        if (isset($parts[1])) {
            $pid = trim($parts[1]);
            // Verificar si este proceso está usando el puerto 8080
            exec("netstat -ano | findstr :8080 | findstr $pid", $portCheck);
            if (!empty($portCheck)) {
                $websocketPids[] = $pid;
                echo "   - Encontrado proceso WebSocket: PID $pid\n";
            }
        }
    }
}

if (!empty($websocketPids)) {
    echo "2. Cerrando procesos WebSocket...\n";
    foreach ($websocketPids as $pid) {
        echo "   - Cerrando PID $pid... ";
        exec("taskkill /PID $pid /F 2>NUL", $result, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅ Cerrado\n";
        } else {
            echo "❌ Error (código: $returnCode)\n";
        }
    }
} else {
    echo "   - No se encontraron procesos WebSocket corriendo\n";
}

// Método 2: Limpiar archivos lock
echo "\n3. Limpiando archivos lock...\n";
$lockFile = sys_get_temp_dir() . '/websocket_casalai.lock';
if (file_exists($lockFile)) {
    echo "   - Eliminando archivo lock: $lockFile\n";
    unlink($lockFile);
    echo "   ✅ Lock eliminado\n";
} else {
    echo "   - No se encontró archivo lock\n";
}

// Método 3: Verificar que el puerto esté libre
echo "\n4. Verificando que el puerto 8080 esté libre...\n";
sleep(2); // Esperar a que los procesos terminen

exec('netstat -ano | findstr :8080', $portCheck);
if (empty($portCheck)) {
    echo "   ✅ Puerto 8080 está libre\n";
} else {
    echo "   ⚠️  Puerto 8080 aún está en uso:\n";
    foreach ($portCheck as $line) {
        echo "      $line\n";
    }
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "El servidor WebSocket debería estar cerrado.\n";
echo "Puedes verificar con: netstat -ano | findstr :8080\n";
?>
