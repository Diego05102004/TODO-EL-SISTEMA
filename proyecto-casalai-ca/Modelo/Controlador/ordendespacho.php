<?php
ob_start();
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/paths.php';
require __DIR__ . '/../../vendor/autoload.php';
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;

define('MODULO_ORDEN_DESPACHO', "Ordenes de despacho");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada']);
    exit;
}

// Load permissions using Composer autoload
$id_rol = isset($_SESSION['id_rol']) ? $_SESSION['id_rol'] : null;
try {
    $permisos = new Permisos();
    $permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
    $permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('Ordenes de despacho'));
} catch (Exception $e) {
    error_log("Error cargando permisos: " . $e->getMessage());
    $permisosUsuario = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, 'Ordenes de despacho');
            echo json_encode($permisosActualizados);
            exit;

        case 'obtenerOrden':
            $id = $_POST['id_despachos'] ?? null;
            if ($id !== null) {
                $ordenModel = new OrdenDespacho();
                $orden = $ordenModel->obtenerOrdenPorId($id);
                if ($orden !== null) {
                    echo json_encode(['status' => 'success', 'datos' => $orden]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Orden de despacho no encontrada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de la orden no proporcionado']);
            }
            break;

        case 'descargarOrden':
            // Captura el ID de la orden enviado por POST
            $id_orden_raw = $_POST['descargarOrden'] ?? null;
            $id_orden = is_numeric($id_orden_raw) ? (int)$id_orden_raw : 0;

            error_log("[DEBUG] descargarOrden - ID recibido: " . $id_orden);

            if ($id_orden <= 0) {
                error_log("[DEBUG] descargarOrden - ID no válido");
                die("Error: ID de orden de compra no válido.");
            }

            // Instanciar el modelo y obtener datos de la orden
            try {
                $ordenModel = new OrdenDespacho();

                $datos_validacion = ['id_orden_despachos' => $id_orden_raw];
                $errores = $ordenModel->validarDescargarOrden($datos_validacion);
                
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en los datos para descargar la orden de despacho',
                        'errors' => $errores
                    ]);
                    exit;
                }

                error_log("[DEBUG] descargarOrden - Modelo instanciado");
                
                // Obtener datos completos de la orden para el PDF
                $orden = $ordenModel->obtenerDatosParaPDF($id_orden);
                error_log("[DEBUG] descargarOrden - Datos obtenidos: " . print_r($orden, true));
                
                if (empty($orden) || !is_array($orden)) {
                    error_log("[DEBUG] descargarOrden - No se encontraron datos");
                    die("Error: No se encontraron datos para la orden de despacho ID: $id_orden");
                }
                
                // Verificar datos requeridos
                if (!isset($orden['id_orden_despachos']) || !isset($orden['id_factura'])) {
                    error_log("[DEBUG] descargarOrden - Datos incompletos");
                    die("Error: Datos de la orden de despacho incompletos.");
                }
                
                error_log("[DEBUG] descargarOrden - Datos válidos, cargando vista PDF");
                
            } catch (Exception $e) {
                error_log("[DEBUG] descargarOrden - Error: " . $e->getMessage());
                die("Error al obtener datos de la orden: " . $e->getMessage());
            }
            
            // Carga el archivo encargado de estructurar y descargar el PDF
            require_once(__DIR__ . "/../../Vista/descargarOrdenDespacho.php");
            exit; // Detiene el flujo por completo para evitar que se pinte la vista HTML estándar
            break;
        

        case 'cambiar_estatus':
            $id = $_POST['id_despachos'];
            $nuevoEstatus = $_POST['nuevo_estatus'];
            if (!in_array($nuevoEstatus, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estatus no válido']);
                exit;
            }
            $ordendespacho = new OrdenDespacho();
            $ordendespacho->setId($id);
            if ($ordendespacho->cambiarEstatus($nuevoEstatus)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    try {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_ORDEN_DESPACHO, 'CAMBIAR ESTATUS', 'El usuario cambió el estatus de la orden de despacho ID ' . $id . ' a ' . $nuevoEstatus, 'media');
                    } catch (Exception $e) {
                        error_log("Error registrando bitácora: " . $e->getMessage());
                    }
                }
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            break;

        case 'cambiar_estado_orden':
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            // 1. Verificación estricta de sesión activa
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            // 2. Extracción limpia y segura de parámetros POST
            $id = isset($_POST['id_orden_despachos']) ? (int)$_POST['id_orden_despachos'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
            $estado_actual = $_POST['estado_actual'] ?? '';
            
            // 3. Validación de consistencia elemental
            if ($id <= 0 || !in_array($estado_actual, ['Por Entregar', 'Entregada'], true)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos inválidos para cambiar el estado de la orden.']);
                break;
            }
            
            // 4. Conmutación controlada de estados (Toggle)
            $nuevo_estado = ($estado_actual === 'Por Entregar') ? 'Entregada' : 'Por Entregar';
            
            // 5. Instanciación y Validación con el método correcto
            $ordenModel = new OrdenDespacho();

            // Pasamos tanto el ID como el nuevo estado calculado
            $datosValidacion = [
                'id_orden_despachos' => $id,
                'nuevo_estado' => $nuevo_estado
            ];

            $errores = $ordenModel->validarCambiarEstatus($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }

            // 6. Ejecución en la Base de Datos
            $resultado = $ordenModel->cambiarEstadoOrden($id, $nuevo_estado, $id_usuario_sesion);
            
            // Respuesta JSON unificada basada en el retorno del modelo
            if (isset($resultado['status']) && $resultado['status'] === 'success') {

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $notificacionModel = new NotificacionModel('S');
                    
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'], 
                        'orden_despacho', 
                        'Estado de orden de despacho actualizado', 
                        "Se ha cambiado el estado de la orden de despacho con ID " . $id . " a '" . $nuevo_estado . "' por el usuario " . ($_SESSION['name'] ?? ''), 
                        'media', 
                        MODULO_ORDEN_DESPACHO, 
                        'actualizar', 
                        $id
                    );
                }

                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['message'] ?? 'No se pudo cambiar el estado de la orden.'
                ]);
            }
            break;

        case 'anularOrden':
            header('Content-Type: application/json; charset=utf-8');
            
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            // 1. Verificación estricta de sesión activa
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            // 2. Captura segura y casteo a entero en un solo paso
            $idOrden = isset($_POST['id_orden_despachos']) ? (int)$_POST['id_orden_despachos'] : 0;
            
            // 3. Validación de consistencia elemental
            if ($idOrden <= 0) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'ID de orden de despacho no válido.'
                ]);
                break;
            }

            // 4. Instanciación y ejecución de la baja lógica
            $ordenModel = new OrdenDespacho();

            $datos_validacion = ['id_orden_despachos' => $idOrden];
            $errores = $ordenModel->validarAnularOrden($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para anular la orden de despacho',
                    'errors' => $errores
                ]);
                exit;
            }

            $resultado = $ordenModel->anularOrdenDespacho($idOrden, $id_usuario_sesion);

            // 5. Gestión controlada de efectos secundarios (Notificaciones)
            if (isset($resultado['status']) && $resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $notificacionModel = new NotificacionModel('S');
                    
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'], 
                        'orden_despacho', 
                        'Orden de despacho anulada', 
                        "Se ha anulado la orden de despacho con ID " . $idOrden . " por parte del usuario " . ($_SESSION['name'] ?? ''), 
                        'media', 
                        MODULO_ORDEN_DESPACHO, 
                        'eliminar', 
                        $idOrden
                    );
                }
            }

            // 6. Retorno final estructurado de la operación (Éxito o error del SP)
            echo json_encode($resultado);
            break;
    }
    exit;
}

function getordendespacho() {
    $ordendespacho = new OrdenDespacho();
    return $ordendespacho->getordendespacho();
}

$pagina = "ordendespacho";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        try {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_ORDEN_DESPACHO, 'ACCESAR', 'El usuario accedió al módulo de Ordenes de Despacho', 'media');
        } catch (Exception $e) {
            error_log("Error registrando bitácora: " . $e->getMessage());
        }
    }
    $ordendespacho = getordendespacho();
    $ordenModel = new OrdenDespacho();
    $facturas = $ordenModel->obtenerFacturasDisponibles();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
ob_end_flush();
?>