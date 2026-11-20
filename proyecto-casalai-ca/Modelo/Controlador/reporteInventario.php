<?php  
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reportes_inventario') {
    header('Content-Type: application/json; charset=utf-8');

    $fechaInicio = $_POST['fechaInicio'] ?? null;
    $fechaFin = $_POST['fechaFin'] ?? null;
    $tipoReporte = $_POST['tipoReporte'] ?? 'todos';
    $proveedor = $_POST['proveedor'] ?? null;
    $producto = $_POST['producto'] ?? null;
    $anio = $_POST['anio'] ?? null;

    $recepcion = new Recepcion();

    try {
        $datos = [
            'proveedores' => $recepcion->getRecepcionesPorProveedor(
                $fechaInicio !== '' ? $fechaInicio : null,
                $fechaFin !== '' ? $fechaFin : null
            ),
            'productos' => $recepcion->getProductosMasRecibidos(
                $fechaInicio !== '' ? $fechaInicio : null,
                $fechaFin !== '' ? $fechaFin : null,
                $proveedor !== '' ? $proveedor : null
            ),
            'mensual' => $recepcion->getRecepcionesMensuales(
                $anio !== '' ? (int) $anio : null
            )
        ];

        echo json_encode(['status' => 'success', 'data' => $datos], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudieron cargar los reportes de recepción'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$idRol = $_SESSION['id_rol'] ?? null;
$bitacora = new Bitacora();

$recepcion = new Recepcion();
$RecepcionesProveedor = $recepcion->getRecepcionesPorProveedor();
$ProductosRecibidos = $recepcion->getProductosMasRecibidos();
$RecepcionMensual = $recepcion->getRecepcionesMensuales();


$pagina = "reporteInventario";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacora->registrarBitacora(
            $_SESSION['id_usuario'],
            'Recepcion',
            'ACCESAR',
            'El usuario accedió al módulo de Reportes de Inventario',
            'media'
        );
    }
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacora->registrarBitacora(
            $_SESSION['id_usuario'],
            'Recepcion',
            'ACCESAR',
            'El usuario accedió al módulo de Reportes de Inventario',
            'media'
        );
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>