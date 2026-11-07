<?php
// backup.php - Controlador corregido y robusto

// Mostrar errores en desarrollo (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Root y autoload
$rootDir = realpath(__DIR__ . '/../..');
require_once $rootDir . '/vendor/autoload.php';

// Si por alguna razón tu autoload no carga la clase Backup,
// mantén el require manual (siempre preferible usar solo autoload)
if (!class_exists('Usuario\\ProyectoCasalaiCa\\Clases\\Backup')) {
    @include_once $rootDir . '/Modelo/Usuario/ProyectoCasalaiCa/Clases/Backup.php';
}

require_once $rootDir . '/Modelo/Config/database.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Backup as BackupClass;

// Constantes
define('MODULO_BACKUP', 20);
define('BACKUP_DIR', $rootDir . '/Modelo/db/respaldo/');

// Asegurar existencia del directorio de respaldo y logs
if (!is_dir(BACKUP_DIR)) {
    @mkdir(BACKUP_DIR, 0775, true);
}

// Simple logger hacia BACKUP_DIR/backup_debug.log
function backup_debug_log($mensaje) {
    $logFile = BACKUP_DIR . 'backup_debug.log';
    @file_put_contents($logFile, '[' . date('c') . '] ' . $mensaje . PHP_EOL, FILE_APPEND);
}

// Envolver todo en output buffering para capturar cualquier salida inesperada
ob_start();

// Inicialización de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: detectar petición AJAX
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Helper: enviar JSON y terminar
function send_json($payload, $status = 200) {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload);
    // limpiar buffer de salida antes de salir (para evitar outputs previos)
    while (ob_get_level()) ob_end_flush(); // vacía buffer
    exit;
}

// Helper: manejar respuesta cuando no autenticado
function unauthorized_response() {
    if (is_ajax()) {
        send_json(['success' => false, 'error' => 'No autorizado'], 403);
    } else {
        http_response_code(403);
        echo 'Acceso no autorizado. Por favor inicie sesión.';
        // vaciar buffer y salir
        while (ob_get_level()) ob_end_flush();
        exit;
    }
}

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    backup_debug_log('Acceso no autorizado - usuario no autenticado');
    unauthorized_response();
}

// Manejo centralizado de excepciones (para logging)
set_exception_handler(function($e) {
    backup_debug_log('Excepción no capturada: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (is_ajax()) {
        send_json(['success' => false, 'error' => 'Error interno en el servidor. Revisa logs.'], 500);
    } else {
        http_response_code(500);
        echo 'Error interno en el servidor. Revisa logs.';
        while (ob_get_level()) ob_end_flush();
        exit;
    }
});

// ------------------------------------------------------
//  ACCIONES AJAX: listar, descargar, eliminar, restaurar, generar
// ------------------------------------------------------

$accion = $_GET['accion'] ?? $_POST['accion'] ?? null;

try {
    if ($accion === 'listar') {
        // Validar datos de entrada
        $backup = new BackupClass();
        $datosValidacion = [
            'limite' => $_GET['limite'] ?? null,
            'tipo_filtro' => $_GET['tipo_filtro'] ?? null
        ];
        
        $errores = $backup->validarListar($datosValidacion);
        if (!empty($errores)) {
            send_json(['success' => false, 'error' => 'Datos inválidos', 'detalles' => $errores], 400);
        }
        
        // Listar archivos .sql
        $ruta = BACKUP_DIR;
        $archivos = [];

        if (!is_dir($ruta)) {
            if (!mkdir($ruta, 0775, true)) {
                throw new Exception('No se pudo crear el directorio de respaldos');
            }
        }

        $files = scandir($ruta);
        if ($files === false) {
            throw new Exception('No se pudo leer el directorio de respaldos');
        }

        foreach ($files as $file) {
            if (preg_match('/\.(sql|enc)$/i', $file)) {
                $filePath = $ruta . $file;
                $archivos[] = [
                    'nombre' => $file,
                    'tamano' => file_exists($filePath) ? filesize($filePath) : 0,
                    'tamano_text' => file_exists($filePath) ? filesize($filePath) : 0,
                    'fecha_modificacion' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : '',
                    'tipo' => (stripos($file, 'seguridad') !== false) ? 'Seguridad' : 'Principal',
                    'encriptado' => (preg_match('/\.enc$/i', $file) ? true : false)
                ];
            }
        }

        send_json($archivos, 200);
    }

    if ($accion === 'descargar' && !empty($_GET['archivo'])) {
        // Validar datos de entrada
        $backup = new BackupClass();
        $datosValidacion = [
            'nombre_archivo' => $_GET['archivo']
        ];
        
        $errores = $backup->validarDescargar($datosValidacion);
        if (!empty($errores)) {
            send_json(['success' => false, 'error' => 'Datos inválidos', 'detalles' => $errores], 400);
        }
        
        // Descarga: no devolvemos JSON, devolvemos el archivo
        $archivo = basename($_GET['archivo']);
        $ruta = BACKUP_DIR . $archivo;

        if (file_exists($ruta) && is_file($ruta)) {
            // Limpiar buffer antes de enviar archivo
            while (ob_get_level()) ob_end_clean();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
            header('Content-Length: ' . filesize($ruta));
            header('Pragma: public');
            header('Cache-Control: must-revalidate');
            header('Expires: 0');

            readfile($ruta);
            exit;
        } else {
            // Si es AJAX, enviar JSON; si no, texto simple con código 404
            if (is_ajax()) {
                send_json(['success' => false, 'error' => 'Archivo no encontrado'], 404);
            } else {
                http_response_code(404);
                echo 'Archivo no encontrado';
                exit;
            }
        }
    }

    if ($accion === 'eliminar' && !empty($_GET['archivo'])) {
        // Validar datos de entrada
        $backup = new BackupClass();
        $datosValidacion = [
            'nombre_archivo' => $_GET['archivo']
        ];
        
        $errores = $backup->validarEliminar($datosValidacion);
        if (!empty($errores)) {
            send_json(['success' => false, 'error' => 'Datos inválidos', 'detalles' => $errores], 400);
        }
        
        $archivo = basename($_GET['archivo']);
        $ruta = BACKUP_DIR . $archivo;

        if (file_exists($ruta) && is_file($ruta)) {
            if (unlink($ruta)) {
                send_json(['success' => true, 'message' => 'Archivo eliminado correctamente']);
            } else {
                send_json(['success' => false, 'error' => 'No se pudo eliminar el archivo'], 500);
            }
        } else {
            send_json(['success' => false, 'error' => 'Archivo no encontrado'], 404);
        }
    }

    if ($accion === 'restaurar' && !empty($_GET['archivo'])) {
        // Validar datos de entrada
        $backup = new BackupClass();
        $datosValidacion = [
            'nombre_archivo' => $_GET['archivo']
        ];
        
        $errores = $backup->validarRestaurar($datosValidacion);
        if (!empty($errores)) {
            send_json(['success' => false, 'error' => 'Datos inválidos', 'detalles' => $errores], 400);
        }
        
        $archivo = basename($_GET['archivo']);
        $ruta = BACKUP_DIR . $archivo;

        if (!file_exists($ruta)) {
            send_json(['success' => false, 'error' => 'El archivo de respaldo no existe'], 404);
        }

        // Determinar tipo (ejemplo)
        $tipo = (stripos($archivo, 'seguridad') !== false) ? 'S' : 'P';

        // Instanciar clase Backup
        $backup = new BackupClass($tipo);

        // Ejecutar restauración (tu método debe recibir nombre de archivo)
        $ok = $backup->restaurar($archivo);
        if ($ok) {
            send_json(['success' => true, 'message' => 'Base de datos restaurada correctamente', 'tipo' => ($tipo === 'S' ? 'Seguridad' : 'Principal')]);
        } else {
            send_json(['success' => false, 'error' => 'Error al restaurar la base de datos'], 500);
        }
    }

    if ($accion === 'generar') {
        // Validar datos de entrada
        $backup = new BackupClass();
        $datosValidacion = [
            'tipo' => $_GET['tipo'] ?? $_POST['tipo'] ?? 'P',
            'nombre_archivo' => $_GET['nombre_archivo'] ?? $_POST['nombre_archivo'] ?? null
        ];
        
        $errores = $backup->validarGenerar($datosValidacion);
        if (!empty($errores)) {
            send_json(['success' => false, 'error' => 'Datos inválidos', 'detalles' => $errores], 400);
        }
        
        // permitimos tanto GET como POST (según lo que uses)
        $tipo = (isset($_GET['tipo']) && $_GET['tipo'] === 'S') ? 'S' : (isset($_POST['tipo']) && $_POST['tipo'] === 'S' ? 'S' : 'P');
        $tipoTexto = ($tipo === 'S') ? 'seguridad' : 'principal';

        // Instanciar clase Backup y generar
        $backup = new BackupClass($tipo);
        $nombreArchivo = 'backup_' . $tipoTexto . '_' . date('Ymd_His') . '.sql';

        $resultado = $backup->generar($nombreArchivo); // tu método debe devolver ['success'=>bool,'error'=>...]
        if (isset($resultado['success']) && $resultado['success'] === true) {
            $rutaArchivo = BACKUP_DIR . $nombreArchivo;
            $tamano = file_exists($rutaArchivo) ? filesize($rutaArchivo) : null;
            $fechaMod = file_exists($rutaArchivo) ? date('Y-m-d H:i:s', filemtime($rutaArchivo)) : date('Y-m-d H:i:s');

            send_json([
                'success' => true,
                'message' => 'Respaldo generado correctamente',
                'archivo' => $nombreArchivo,
                'tipo' => $tipoTexto,
                'fecha' => $fechaMod,
                'fecha_modificacion' => $fechaMod,
                'tamano' => $tamano
            ]);
        } else {
            $errorMsg = $resultado['error'] ?? 'Error desconocido al generar el respaldo';
            backup_debug_log('Error generar respaldo: ' . $errorMsg . ' - debug:' . json_encode($resultado['debug'] ?? null));
            send_json(['success' => false, 'error' => $errorMsg, 'debug' => $resultado['debug'] ?? null], 500);
        }
    }

    if ($accion === 'consultar' && !empty($_GET['archivo'])) {
        // Validar datos de entrada
        $backup = new BackupClass();
        $datosValidacion = [
            'nombre_archivo' => $_GET['archivo']
        ];
        
        $errores = $backup->validarConsultar($datosValidacion);
        if (!empty($errores)) {
            send_json(['success' => false, 'error' => 'Datos inválidos', 'detalles' => $errores], 400);
        }
        
        $archivo = basename($_GET['archivo']);
        $ruta = BACKUP_DIR . $archivo;

        if (file_exists($ruta) && is_file($ruta)) {
            // Obtener información detallada del archivo
            $tamano = filesize($ruta);
            $fileInfo = [
                'nombre' => $archivo,
                'tamano' => $tamano,
                'tamano_formateado' => $backup->formatearTamano($tamano),
                'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($ruta)),
                'fecha_creacion' => date('Y-m-d H:i:s', filectime($ruta)),
                'tipo' => (stripos($archivo, 'seguridad') !== false) ? 'Seguridad' : 'Principal',
                'extension' => pathinfo($archivo, PATHINFO_EXTENSION),
                'permisos' => substr(sprintf('%o', fileperms($ruta)), -4),
                'es_legible' => is_readable($ruta),
                'ruta_completa' => $ruta
            ];
            
            send_json(['success' => true, 'data' => $fileInfo]);
        } else {
            send_json(['success' => false, 'error' => 'Archivo no encontrado'], 404);
        }
    }

    // Si no hay acción AJAX, renderizar vista normal (GET a la página)
    // NOTA: aquí asumimos que quieres mostrar la vista cuando accedes por navegador
    $pagina = "backup";
    // Buscar primero en Vista/VistaNew/ y luego en Vista/
    if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
        // Antes de renderizar, limpiar buffer intermedio (captura de warnings) y registrar si existió salida
        $output = trim(ob_get_clean());
        if (!empty($output)) {
            backup_debug_log('Salida inesperada antes de render vista: ' . $output);
        }
        $backup = new BackupClass();
        $backups = $backup->listar();
        require_once("Vista/VistaNew/" . $pagina . ".php");
        exit;
    } elseif (is_file("Vista/" . $pagina . ".php")) {
        // Antes de renderizar, limpiar buffer intermedio (captura de warnings) y registrar si existió salida
        $output = trim(ob_get_clean());
        if (!empty($output)) {
            backup_debug_log('Salida inesperada antes de render vista: ' . $output);
        }
        $backup = new BackupClass();
        $backups = $backup->listar();
        require_once("Vista/" . $pagina . ".php");
        exit;
    } else {
        // Limpiar buffer y mostrar mensaje
        $output = trim(ob_get_clean());
        if (!empty($output)) {
            backup_debug_log('Salida inesperada antes de mensaje construcción: ' . $output);
        }
        echo "Página en construcción";
        exit;
    }

} catch (Exception $e) {
    // Registrar error y devolver JSON si es AJAX
    backup_debug_log('Catch general: ' . $e->getMessage() . ' trace: ' . $e->getTraceAsString());
    if (is_ajax()) {
        send_json(['success' => false, 'error' => $e->getMessage()], 500);
    } else {
        http_response_code(500);
        echo 'Error interno: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}
?>
