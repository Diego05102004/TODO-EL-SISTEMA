<?php
/**
 * Script para verificar y asegurar que el servidor WebSocket esté corriendo
 * Se ejecuta automáticamente después del login exitoso
 */

// Determinar si se está ejecutando como include o como script independiente
$esInclude = !defined('WEBSOCKET_VERIFICACION_STANDALONE');

if (!$esInclude) {
    define('WEBSOCKET_VERIFICACION_STANDALONE', true);
}

// Función para registrar logs solo si no es include
function logWebSocket($mensaje) {
    global $esInclude;
    if (!$esInclude) {
        echo "<script>console.log('$mensaje');</script>";
    }
}

// Verificar si el servidor WebSocket está corriendo
function verificarWebSocketServer() {
    // Intentar conectar al servidor WebSocket
    $socket = @fsockopen('localhost', 8080, $errno, $errstr, 2);
    
    if ($socket) {
        fclose($socket);
        logWebSocket('Verificación: Servidor WebSocket responde en puerto 8080');
        return true; // El servidor está corriendo
    }
    
    logWebSocket('Verificación: Servidor WebSocket no responde en puerto 8080 (Error: $errno - $errstr)');
    return false; // El servidor no está corriendo
}

// Iniciar el servidor WebSocket si no está corriendo
function iniciarWebSocketServer() {
    $lockFile = sys_get_temp_dir() . '/websocket_casalai.lock';
    
    // Verificar si ya hay un proceso corriendo
    if (file_exists($lockFile)) {
        $pid = file_get_contents($lockFile);
        // Verificar si el proceso todavía existe
        if (function_exists('posix_kill')) {
            // Linux/Mac
            if (posix_kill($pid, 0)) {
                logWebSocket('Servidor WebSocket ya está corriendo (PID: $pid)');
                return true; // El proceso todavía está corriendo
            }
        } else {
            // Windows - verificar con tasklist
            $output = [];
            exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
            foreach ($output as $line) {
                if (strpos($line, (string)$pid) !== false) {
                    logWebSocket('Servidor WebSocket ya está corriendo (PID: $pid)');
                    return true; // El proceso todavía está corriendo
                }
            }
        }
        // Si llegamos aquí, el proceso ya no existe, eliminar el lock
        unlink($lockFile);
    }
    
    // Ruta absoluta al websocket
    $websocketPath = __DIR__ . DIRECTORY_SEPARATOR . 'websocket_server.php';
    
    if (!file_exists($websocketPath)) {
        logWebSocket('No se encuentra el archivo: $websocketPath');
        return false;
    }
    
    // Detectar sistema operativo
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    logWebSocket('Iniciando servidor WebSocket...');
    
    if ($isWindows) {
        // Windows - método corregido usando PHP_BINARY
        try {
            logWebSocket('Iniciando servidor WebSocket en Windows...');
            
            // Usar la misma ruta de PHP que ejecuta el script
            $phpPath = PHP_BINARY;
            $websocketPath = str_replace('/', '\\', $websocketPath);
            $command = "\"$phpPath\" \"$websocketPath\"";
            
            logWebSocket('Ejecutando: $command');
            
            // Redirigir salida y ejecutar en segundo plano
            $fullCommand = "start /B \"\" $command > nul 2>&1";
            exec($fullCommand . ' 2>NUL', $output, $returnCode);
            
            if ($returnCode === 0) {
                logWebSocket('Comando de inicio ejecutado correctamente');
                
                // Esperar más tiempo para que el servidor inicie completamente
                sleep(5);
                
                // Crear archivo lock
                file_put_contents($lockFile, 'running');
                
            } else {
                logWebSocket('Error ejecutando comando. Código: $returnCode');
                return false;
            }
            
        } catch (Exception $e) {
            logWebSocket('Error iniciando servidor: ' . $e->getMessage());
            return false;
        }
        
    } else {
        // Linux / Unix
        $command = "nohup php \"$websocketPath\" > /dev/null 2>&1 &";
        exec($command);
        
        // Crear archivo lock
        file_put_contents($lockFile, getmypid());
    }
    
    logWebSocket('Servidor WebSocket iniciado, verificando conexión...');
    return true;
}

// Función mejorada para verificar y esperar a que el servidor inicie
function verificarEIniciarWebSocket() {
    $maxIntentos = 5;
    $intentos = 0;
    
    // Primero verificar si ya está corriendo
    if (verificarWebSocketServer()) {
        logWebSocket('El servidor WebSocket ya está corriendo');
        return true;
    }
    
    logWebSocket('El servidor WebSocket no está corriendo, intentando iniciarlo...');
    
    // Intentar iniciar el servidor
    if (!iniciarWebSocketServer()) {
        logWebSocket('No se pudo iniciar el servidor WebSocket');
        return false;
    }
    
    // Esperar y verificar que inicie correctamente
    while ($intentos < $maxIntentos) {
        sleep(2);
        $intentos++;
        
        if (verificarWebSocketServer()) {
            logWebSocket('Servidor WebSocket iniciado correctamente');
            return true;
        }
        
        logWebSocket('Intento ' . $intentos . ' de ' . $maxIntentos . ': servidor aún no responde');
    }
    
    logWebSocket('No se pudo iniciar el servidor WebSocket después de ' . $maxIntentos . ' intentos');
    return false;
}

// Ejecutar solo si es un script independiente
if (!$esInclude) {
    verificarEIniciarWebSocket();
}
?>
