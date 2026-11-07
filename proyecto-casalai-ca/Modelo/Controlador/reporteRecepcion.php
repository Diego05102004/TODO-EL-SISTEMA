<?php
// Requires organizados al inicio
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

define('MODULO_RECEPCION', "Recepcion");

$id_rol = $_SESSION['id_rol'];

// Permisos
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('recepcion'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'reportes_recepcion':
            header('Content-Type: application/json; charset=utf-8');
            $k = new Recepcion();
            // Parámetros opcionales
            $fechaInicio = $_POST['fechaInicio'] ?? null;
            $fechaFin    = $_POST['fechaFin'] ?? null;
            $tipoReporte = $_POST['tipoReporte'] ?? 'todos';
            
            $datos = $k->generarReporteRecepcion($tipoReporte, $fechaInicio, $fechaFin);
            echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        break;

        default:
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
        break;
    }
}

// Funciones auxiliares
function getrecepcion() {
    $recepcion = new Recepcion();
    return $recepcion->getrecepcion();
}

// Datos para reportes
$r = new Recepcion();
$RecepcionesProveedor = $r->getRecepcionesPorProveedor();
$ProductosRecibidos = $r->getProductosMasRecibidos();
$RecepcionMensual = $r->getRecepcionesMensuales();

$pagina = "reporteRecepcion";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Recepcion',
                'ACCESAR',
                'El usuario accedió al módulo de Reportes de Recepcion',
                'media'
            );
        }
    }
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Recepcion',
                'ACCESAR',
                'El usuario accedió al módulo de Reportes de Recepcion',
                'media'
            );
        }
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>