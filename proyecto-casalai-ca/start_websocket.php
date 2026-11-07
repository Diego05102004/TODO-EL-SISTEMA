<?php

// En hosting compartido, el websocket no puede ejecutarse debido a restricciones
// Este archivo ahora solo intenta iniciar el websocket si es posible, sin causar errores

try {
    // Intentar usar directorio temporal con permisos de escritura
    $tempDir = sys_get_temp_dir();
    if (!is_writable($tempDir)) {
        // Si /tmp no es escribible, intentar usar el directorio del proyecto
        $tempDir = __DIR__ . '/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
    }
    
    $lockFile = $tempDir . '/websocket_casalai.lock';

    // Si el WebSocket ya fue iniciado, no hacer nada
    if (file_exists($lockFile)) {
        return;
    }

    // Crear archivo lock solo si el directorio es escribible
    if (is_writable($tempDir)) {
        @file_put_contents($lockFile, getmypid());
    }

    // Ruta absoluta al websocket
    $websocketPath = __DIR__ . '/websocket_server.php';

    // Detectar sistema operativo
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    if ($isWindows) {
        // Windows (XAMPP / WAMP)
        pclose(popen("start /B php \"$websocketPath\"", "r"));
    } else {
        // Linux / Unix - verificar si exec() está disponible
        if (function_exists('exec')) {
            @exec("nohup php \"$websocketPath\" > /dev/null 2>&1 &");
        }
        // Si exec() no está disponible, el websocket no se iniciará pero el sitio seguirá funcionando
    }
} catch (Exception $e) {
    // Si hay algún error, el sitio continuará funcionando sin websocket
    error_log("WebSocket no pudo iniciarse: " . $e->getMessage());
}
