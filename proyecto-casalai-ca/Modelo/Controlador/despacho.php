<?php  
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;
use Usuario\ProyectoCasalaiCa\Config\BD;
define('MODULO_DESPACHO', "Despacho");

$id_rol = $_SESSION['id_rol'];

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('despacho'));

// Cargar pedidos disponibles para despacho
try {
    $facturaModel = new Factura();
    $pedidos = $facturaModel->obtenerFacturasListado();
    $pedidosList = $pedidos['facturas'] ?? [];
} catch (Throwable $e) {
    error_log('Error al cargar pedidos: ' . $e->getMessage());
    $pedidosList = [];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'listado':
            $k = new Despacho();
            $respuesta = $k->listadoproductos();
            echo json_encode($respuesta);
        break;

        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('despacho'));
            echo json_encode($permisosActualizados);
        break;

        case 'obtener_detalles':
            $k = new Despacho();
            // Validar datos de entrada
            $datosValidacion = [
                'id_despacho' => $_POST['id_despachos'] ?? null
            ];
            
            $errores = $k->validarDetallarDespacho($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }
            
            $idDespacho = isset($_POST['id_despachos']) ? (int)$_POST['id_despachos'] : 0;
            if ($idDespacho > 0) {
                $respuesta = $k->obtenerDetallesPorDespacho($idDespacho);
                echo json_encode($respuesta);
            } else {
                echo json_encode(['error' => true, 'mensaje' => 'ID de despacho no válido']);
            }
        break;

        case 'cambiar_estado_despacho':
            // Forzamos la cabecera JSON limpia para una interpretación perfecta en el Frontend
            header('Content-Type: application/json; charset=utf-8');

            $k = new Despacho();
            
            // Validar datos de entrada
            $datosValidacion = [
                'id_despacho' => $_POST['id'] ?? null,
                'estado_actual' => $_POST['estado_actual'] ?? null
            ];
            
            $errores = $k->validarCambiarEstado($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $estado_actual = isset($_POST['estado_actual']) ? trim($_POST['estado_actual']) : '';

            if ($id <= 0 || !in_array($estado_actual, ['Por Despachar', 'Despachado'], true)) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Datos inválidos para cambiar el estado del despacho'
                ]);
                break;
            }

            // Calculamos el nuevo estado lógico
            $nuevo_estado = ($estado_actual === 'Por Despachar') ? 'Despachado' : 'Por Despachar';
            
            // Capturamos el usuario en sesión para la auditoría síncrona
            $idUsuarioSesion = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

            // Ejecutamos el modelo pasando el tercer parámetro (Auditor)
            $resultado = $k->cambiarEstadoDespacho($id, $nuevo_estado, $idUsuarioSesion);

            // Evaluamos la respuesta del flujo transaccional
            if ($resultado['status'] === 'success') {
                
                // Efectos secundarios (Notificaciones del Sistema)
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $idUsuarioSesion,
                        'despacho',
                        'Estado de despacho actualizado',
                        "Se ha cambiado el estado del despacho con ID ".$id." a '".$nuevo_estado."' por el usuario ".($_SESSION['name'] ?? ''),
                        'media',
                        MODULO_DESPACHO,
                        'actualizar',
                        $id
                    );
                }
                
                // Respondemos éxito al Frontend incluyendo el nuevo estado para actualizar el Badge HTML
                echo json_encode([
                    'status' => 'success', 
                    'message' => $resultado['message'],
                    'nuevo_estado' => $nuevo_estado
                ]);
                
            } else {
                // Si ocurre un error o un SIGNAL SQLSTATE del SP (Ej: "Despacho se encuentra anulado")
                // enviamos el mensaje real y controlado al cliente.
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['message']
                ]);
            }
            break;

        case 'anular':
            header('Content-Type: application/json; charset=utf-8');
            
            $idDespacho = isset($_POST['id_despachos']) ? (int)$_POST['id_despachos'] : 0;
            $id_usuario_auditor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

            if ($idDespacho <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de despacho inválido.']);
                break;
            }
            
            $k = new Despacho();
            $datos_validacion = ['id_despacho' => $idDespacho];
            $errores = $k->validarAnularDespacho($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para anular el despacho',
                    'errors' => $errores
                ]);
                exit;
            }

            $resultado = $k->anularDespacho($idDespacho, $id_usuario_auditor);

            if ($resultado['status'] === 'success') {
                // Notificaciones colaterales del sistema (Módulo general)
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $id_usuario_auditor,
                        'despacho',
                        'Despacho Anulado',
                        "El despacho ID ".$idDespacho." fue anulado por el usuario ".($_SESSION['name'] ?? ''),
                        'alta',
                        MODULO_DESPACHO,
                        'anular',
                        $idDespacho
                    );
                }
            }

            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida '.$accion.'']);
    }
    exit;
}

function getdespacho() {
    $despacho = new Despacho();
    return $despacho->getdespacho();
}

// Reportes de despacho (restaurados)
$despacho = new Despacho();
$despachoEstado = $despacho->getDespachosEstado();
$despachoMes = $despacho->getProductosDespachadosPorMes();

$despachos = getdespacho();

$k = new Despacho();
$proveedores = $k->obtenercliente();
$productos = $k->consultarproductos();

// Total de despachos
$totalDespachos = count($despachos);

$pagina = "despacho";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '3',
        'ACCESAR',
        'El usuario accedió al módulo de Despachos',
        'media'
    );}
    $despachos = getdespacho();
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '3',
        'ACCESAR',
        'El usuario accedió al módulo de Despachos',
        'media'
    );}
    $despachos = getdespacho();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "pagina en construccion";
}
?>