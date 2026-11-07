<?php
/**
 * Script para cerrar el servidor WebSocket
 */

function cerrarWebSocketServer() {
    $lockFile = sys_get_temp_dir() . '/websocket_casalai.lock';
    
    // Método 1: Usar el archivo lock para obtener el PID
    if (file_exists($lockFile)) {
        $pid = file_get_contents($lockFile);
        
        if (function_exists('posix_kill')) {
            // Linux/Mac
            if (posix_kill($pid, 15)) { // SIGTERM
                echo "<script>console.log('Servidor WebSocket detenido (PID: $pid)');</script>";
                unlink($lockFile);
                return true;
            }
        } else {
            // Windows - usar taskkill
            $output = [];
            exec("taskkill /PID $pid /F 2>NUL", $output, $return);
            
            if ($return === 0) {
                echo "<script>console.log('Servidor WebSocket detenido (PID: $pid)');</script>";
                unlink($lockFile);
                return true;
            }
        }
    }
    
    // Método 2: Buscar y matar procesos PHP que usen el puerto 8080
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - buscar procesos PHP usando netstat
        $output = [];
        exec('netstat -ano | findstr :8080', $output);
        
        foreach ($output as $line) {
            if (strpos($line, 'LISTENING') !== false) {
                $parts = preg_split('/\s+/', $line);
                $pid = end($parts);
                
                if (is_numeric($pid)) {
                    exec("taskkill /PID $pid /F 2>NUL", $killOutput, $return);
                    if ($return === 0) {
                        echo "<script>console.log('Servidor WebSocket detenido (PID: $pid)');</script>";
                        return true;
                    }
                }
            }
        }
        
        // Alternativa: matar todos los procesos PHP relacionados con websocket_server.php
        exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);
        foreach ($output as $line) {
            if (strpos($line, 'php.exe') !== false) {
                $parts = str_getcsv($line);
                if (isset($parts[1]) && is_numeric($parts[1])) {
                    $pid = $parts[1];
                    // Verificar si este proceso está usando websocket_server.php
                    exec("wmic process where ProcessId=$pid get Commandline /format:list 2>NUL", $cmdline);
                    if (implode('', $cmdline) && strpos(implode('', $cmdline), 'websocket_server.php') !== false) {
                        exec("taskkill /PID $pid /F 2>NUL", $killOutput, $return);
                        if ($return === 0) {
                            echo "<script>console.log('Servidor WebSocket detenido (PID: $pid)');</script>";
                            return true;
                        }
                    }
                }
            }
        }
    } else {
        // Linux/Mac - usar lsof y kill
        exec("lsof -ti:8080 | xargs kill -9 2>/dev/null", $output, $return);
        if ($return === 0) {
            echo "<script>console.log('Servidor WebSocket detenido');</script>";
            return true;
        }
        
        // Alternativa: buscar procesos PHP
        exec("ps aux | grep websocket_server.php | grep -v grep", $output);
        foreach ($output as $line) {
            $parts = preg_split('/\s+/', $line);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $pid = $parts[1];
                exec("kill -9 $pid 2>/dev/null", $killOutput, $return);
                if ($return === 0) {
                    echo "<script>console.log('Servidor WebSocket detenido (PID: $pid)');</script>";
                    return true;
                }
            }
        }
    }
    
    // Limpiar archivo lock si existe
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    
    echo "<script>console.log('No se encontró servidor WebSocket corriendo');</script>";
    return false;
}

// Verificar si está corriendo antes de cerrar
function verificarWebSocketServer() {
    $socket = @fsockopen('localhost', 8080, $errno, $errstr, 2);
    
    if ($socket) {
        fclose($socket);
        return true;
    }
    
    return false;
}

// Ejecutar cierre
if (verificarWebSocketServer()) {
    echo "<script>console.log('Cerrando servidor WebSocket...');</script>";
    cerrarWebSocketServer();
    
    // Verificar que se cerró
    sleep(1);
    if (!verificarWebSocketServer()) {
        echo "<script>console.log('✅ Servidor WebSocket cerrado correctamente');</script>";
    } else {
        echo "<script>console.warn('⚠️ No se pudo cerrar el servidor WebSocket');</script>";
    }
} else {
    echo "<script>console.log('ℹ️ El servidor WebSocket no está corriendo');</script>";
}
?>
