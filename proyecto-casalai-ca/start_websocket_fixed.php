<?php
/**
 * Solución temporal para el WebSocket
 * Inicia el servidor usando el mismo PHP que ejecuta el sitio web
 */

// Obtener la ruta de PHP actual
$phpPath = PHP_BINARY; // Esto da la ruta del PHP que está ejecutando este script

echo "PHP detectado: $phpPath\n";

// Ruta al servidor
$websocketPath = __DIR__ . '/websocket_server.php';

if (!file_exists($websocketPath)) {
    die("Error: No se encuentra $websocketPath\n");
}

// Construir comando
$command = "\"$phpPath\" \"$websocketPath\"";

echo "Ejecutando: $command\n";

// Para Windows, usar start /B
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Redirigir salida a nul para que no interfiera
    $fullCommand = "start /B \"\" $command > nul 2>&1";
    pclose(popen($fullCommand, "r"));
    echo "Servidor iniciado en segundo plano (Windows)\n";
} else {
    exec($command . " > /dev/null 2>&1 &");
    echo "Servidor iniciado en segundo plano (Linux/Mac)\n";
}

// Esperar más tiempo para que el servidor inicie completamente
echo "Esperando 5 segundos para que el servidor inicie...\n";
sleep(5);

// Verificar si está corriendo
$socket = @fsockopen('localhost', 8080, $errno, $errstr, 3);
if ($socket) {
    fclose($socket);
    echo "✅ Servidor WebSocket está corriendo en el puerto 8080\n";
    
    // Crear archivo lock
    file_put_contents(sys_get_temp_dir() . '/websocket_casalai.lock', 'running');
} else {
    echo "❌ Servidor WebSocket no responde en el puerto 8080\n";
    echo "Error: $errno - $errstr\n";
    
    // Mostrar procesos PHP para depuración
    echo "\nProcesos PHP activos:\n";
    exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);
    foreach ($output as $line) {
        echo $line . "\n";
    }
}
?>
