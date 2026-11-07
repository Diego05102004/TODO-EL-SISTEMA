<?php
/**
 * Script simple para iniciar el servidor WebSocket
 * Método directo sin complicaciones
 */

echo "Iniciando servidor WebSocket...\n";

// Ruta al servidor
$websocketPath = __DIR__ . '/websocket_server.php';

if (!file_exists($websocketPath)) {
    die("Error: No se encuentra $websocketPath\n");
}

// Iniciar el servidor directamente
$command = "php \"$websocketPath\"";

echo "Ejecutando: $command\n";

// Para Windows, usar start /B
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    pclose(popen("start /B " . $command, "r"));
    echo "Servidor iniciado en segundo plano (Windows)\n";
} else {
    exec($command . " > /dev/null 2>&1 &");
    echo "Servidor iniciado en segundo plano (Linux/Mac)\n";
}

// Esperar un momento
sleep(2);

// Verificar si está corriendo
$socket = @fsockopen('localhost', 8080, $errno, $errstr, 2);
if ($socket) {
    fclose($socket);
    echo "✅ Servidor WebSocket está corriendo en el puerto 8080\n";
} else {
    echo "❌ Servidor WebSocket no responde en el puerto 8080\n";
    echo "Error: $errno - $errstr\n";
}
?>
