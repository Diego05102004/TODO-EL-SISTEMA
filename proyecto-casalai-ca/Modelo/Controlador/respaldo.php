<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'Modelo/backup.php';
require_once 'Modelo/bitacora.php';
require_once 'Modelo/permiso.php';
define('MODULO_BACKUP', "Respaldo");

// Inicialización de permisos para la vista
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

// Función simple de depuración a archivo
function backup_debug_log($mensaje) {
    $logDir = __DIR__ . '/../db/backup/';
    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
    $logFile = $logDir . 'backup_debug.log';
    @file_put_contents($logFile, '[' . date('c') . "] CONTROLADOR: " . $mensaje . "\n", FILE_APPEND);
}

// Capturar errores fatales y registrar en log (y devolver JSON si aplica)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = 'FATAL: ' . $error['message'] . ' en ' . $error['file'] . ':' . $error['line'];
        backup_debug_log($msg);
        if (isset($_GET['accion']) && $_GET['accion'] === 'generar') {
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['success' => false, 'error' => 'Error fatal en el servidor.', 'detalle' => $msg]);
        }
    }
});

if (isset($_GET['accion'])) {

    if ($_GET['accion'] === 'generar') {
        header('Content-Type: application/json');
        try {
            // asegurar dir de logs
            backup_debug_log('--- INICIO GENERAR ---');
            $tipo = isset($_GET['tipo']) && $_GET['tipo'] === 'S' ? 'S' : 'P';
            $backup = new Backup($tipo);
            $nombreArchivo = 'backup_' . ($tipo === 'S' ? 'seguridad' : 'principal') . '_' . date('Ymd_His') . '.sql';
            $ok = $backup->generar($nombreArchivo);
            $ruta = realpath(__DIR__ . '/../db/backup/' . $nombreArchivo);
            backup_debug_log("Acción GENERAR tipo={$tipo}, archivo={$nombreArchivo}, ok=" . ($ok ? '1' : '0') . ", ruta=" . ($ruta ?: 'N/A'));

            if ($ok && $ruta && file_exists($ruta)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    if (isset($_SESSION['id_usuario'])) {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_BACKUP,
                            'GENERAR',
                            'Se generó un backup ' . ($tipo === 'S' ? 'de seguridad' : 'principal') . ': ' . $nombreArchivo,
                            'media'
                        );
                    }
                }
                echo json_encode(['success' => true, 'archivo' => $nombreArchivo]);
            } else {
                $logFile = __DIR__ . '/../db/backup/backup_debug.log';
                $logMsg = file_exists($logFile) ? @file_get_contents($logFile) : '';
                backup_debug_log('Error al GENERAR backup (ok=0 o archivo no existe).');
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al generar el backup. Ver debug.',
                    'debug' => $logMsg
                ]);
            }
        } catch (Throwable $e) {
            backup_debug_log('Excepción en GENERAR: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Excepción en el servidor.',
                'detalle' => $e->getMessage()
            ]);
        }
        exit;
    }

    // ... resto del código igual ...

    if ($_GET['accion'] === 'descargar') {
        $archivo = $_GET['archivo'] ?? '';
        $ruta = realpath(__DIR__ . '/../db/backup/' . $archivo);
        backup_debug_log('Acción DESCARGAR archivo=' . $archivo . ', existe=' . ((bool)$ruta && file_exists($ruta) ? '1' : '0'));
        if ($archivo && file_exists($ruta)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        } else {
            echo "Archivo no encontrado";
            exit;
        }
    }

    if ($_GET['accion'] === 'consultar') {
        header('Content-Type: application/json');
        $backup = new Backup();
        $backups = $backup->listar();
        sort($backups);
        backup_debug_log('Acción CONSULTAR, total=' . count($backups));
        echo json_encode($backups);
        exit;
    }

    if ($_GET['accion'] === 'restaurar') {
        $archivo = $_GET['archivo'] ?? '';
        $ruta = realpath(__DIR__ . '/../db/backup/' . $archivo);
        header('Content-Type: application/json');
        backup_debug_log('Acción RESTAURAR archivo=' . $archivo . ', existe=' . ($ruta && file_exists($ruta) ? '1' : '0'));
        if ($archivo && file_exists($ruta)) {
            $backup = new Backup();
            $ok = $backup->restaurar($archivo);
            backup_debug_log('Resultado RESTAURAR ok=' . ($ok ? '1' : '0'));
            if ($ok) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    if (isset($_SESSION['id_usuario'])) {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_BACKUP,
                            'RESTAURAR',
                            'Se restauró el backup: ' . $archivo,
                            'alta'
                        );
                    }
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al restaurar']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Archivo no encontrado']);
        }
        exit;
    }
}

// Renderizado de la vista
$pagina = "backup";
if (is_file("Vista/" . $pagina . ".php")) {
    $backup = new Backup();
    $backups = $backup->listar();
    require_once("Vista/" . $pagina . ".php");
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_BACKUP,
                'ACCESAR',
                'El usuario accedió al módulo de Backup',
                'media'
            );
        }
    }
} else {
    echo "Página en construcción";
}
?>