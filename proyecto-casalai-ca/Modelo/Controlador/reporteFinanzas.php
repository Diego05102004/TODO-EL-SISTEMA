<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Finanza;

$id_rol = $_SESSION['id_rol'];

define('MODULO_CUENTA_BANCARIA', 15);
$permisosObj = new Permisos();
$bitacoraModel = new Bitacora();
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('Cuentas bancarias'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('Cuentas bancarias'));
            echo json_encode($permisosActualizados);
            exit;
        
        case 'consultar_cuentas':
            $cuentabanco = new Cuentabanco();
            $cuentas_obt = $cuentabanco->consultarCuentabanco();

            echo json_encode($cuentas_obt);
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
    }
}

function consultarCuentabanco() {
    $cuentabanco = new Cuentabanco();
    return $cuentabanco->consultarCuentabanco();
}

function cuentasReportes() {
    $cuentabanco = new Cuentabanco();
    return $cuentabanco->cuentasReportes();
}

// Consulta automática para la vista
function consultarFinanzas() {
    $finanza = new Finanza();
    return [
        'ingresos' => $finanza->consultarIngresos(),
        'egresos' => $finanza->consultarEgresos()
    ];
}

$finanzas = consultarFinanzas(); // <-- PRIMERO OBTÉN LOS DATOS

// Agrupar ingresos y egresos por mes
function agruparPorMes($registros) {
    $res = [];
    foreach ($registros as $r) {
        $mes = date('Y-m', strtotime($r['fecha']));
        if (!isset($res[$mes])) $res[$mes] = 0;
        $res[$mes] += $r['monto'];
    }
    return $res;
}

$ingresosPorMes = agruparPorMes($finanzas['ingresos']);
$egresosPorMes = agruparPorMes($finanzas['egresos']);



$meses = array_unique(array_merge(array_keys($ingresosPorMes), array_keys($egresosPorMes)));
sort($meses);

$totalIngresos = array_sum(array_column($finanzas['ingresos'], 'monto'));
$totalEgresos = array_sum(array_column($finanzas['egresos'], 'monto'));


$pagina = "reporteFinanzas";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    $cuentabancos = consultarCuentabanco();
    $cuentasReportes = cuentasReportes();
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    $cuentabancos = consultarCuentabanco();
    $cuentasReportes = cuentasReportes();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>