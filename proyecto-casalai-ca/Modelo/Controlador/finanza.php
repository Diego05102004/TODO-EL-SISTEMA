<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Finanza;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;

$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();
define('MODULO_FINANZA', "Finanzas");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    // Ya no se exponen acciones POST en este módulo
    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
    exit;
}

// Consulta automática para la vista
function consultarFinanzas() {
    $finanza = new Finanza();
    
    // Validar parámetros GET opcionales
    $datosValidacion = [
        'tipo' => $_GET['tipo'] ?? null,
        'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
        'fecha_fin' => $_GET['fecha_fin'] ?? null,
        'limite' => $_GET['limite'] ?? null,
        'id_despacho' => $_GET['id_despacho'] ?? null,
        'id_recepcion' => $_GET['id_recepcion'] ?? null
    ];
    
    $errores = $finanza->validarConsultarFinanzas($datosValidacion);
    
    if (!empty($errores)) {
        // En caso de errores, devolver datos vacíos con mensaje de error
        return [
            'ingresos' => [],
            'egresos' => [],
            'error' => 'Error en los parámetros de consulta',
            'errores' => $errores
        ];
    }
    
    return [
        'ingresos' => $finanza->consultarIngresos(),
        'egresos' => $finanza->consultarEgresos()
    ];
}

$finanzas = consultarFinanzas(); // <-- PRIMERO OBTÉN LOS DATOS

// Función para generar reportes con validaciones
function generarReporteFinanzas($parametros = []) {
    $finanza = new Finanza();
    
    // Validar parámetros del reporte
    $datosValidacion = [
        'tipo' => $parametros['tipo'] ?? null,
        'fecha_inicio' => $parametros['fecha_inicio'] ?? null,
        'fecha_fin' => $parametros['fecha_fin'] ?? null,
        'limite' => $parametros['limite'] ?? null,
        'anio' => $parametros['anio'] ?? null,
        'mes' => $parametros['mes'] ?? null,
        'formato_descarga' => $parametros['formato_descarga'] ?? null
    ];
    
    $errores = $finanza->validarReporteFinanzas($datosValidacion);
    
    if (!empty($errores)) {
        return [
            'status' => 'error',
            'message' => 'Parámetros de reporte inválidos',
            'errors' => $errores,
            'data' => null
        ];
    }
    
    // Obtener datos según los parámetros
    $ingresos = $finanza->consultarIngresos();
    $egresos = $finanza->consultarEgresos();
    
    // Filtrar según parámetros
    if (isset($parametros['tipo'])) {
        if ($parametros['tipo'] === 'ingreso') {
            $egresos = [];
        } elseif ($parametros['tipo'] === 'egreso') {
            $ingresos = [];
        }
    }
    
    // Aplicar límite si se especifica
    if (isset($parametros['limite'])) {
        $limite = (int)$parametros['limite'];
        $ingresos = array_slice($ingresos, 0, $limite);
        $egresos = array_slice($egresos, 0, $limite);
    }
    
    // Filtrar por fechas si se especifican
    if (isset($parametros['fecha_inicio'])) {
        $fechaInicio = $parametros['fecha_inicio'];
        $ingresos = array_filter($ingresos, function($item) use ($fechaInicio) {
            return $item['fecha'] >= $fechaInicio;
        });
        $egresos = array_filter($egresos, function($item) use ($fechaInicio) {
            return $item['fecha'] >= $fechaInicio;
        });
    }
    
    if (isset($parametros['fecha_fin'])) {
        $fechaFin = $parametros['fecha_fin'];
        $ingresos = array_filter($ingresos, function($item) use ($fechaFin) {
            return $item['fecha'] <= $fechaFin;
        });
        $egresos = array_filter($egresos, function($item) use ($fechaFin) {
            return $item['fecha'] <= $fechaFin;
        });
    }
    
    // Filtrar por año y mes si se especifican
    if (isset($parametros['anio'])) {
        $anio = $parametros['anio'];
        $ingresos = array_filter($ingresos, function($item) use ($anio) {
            return date('Y', strtotime($item['fecha'])) == $anio;
        });
        $egresos = array_filter($egresos, function($item) use ($anio) {
            return date('Y', strtotime($item['fecha'])) == $anio;
        });
    }
    
    if (isset($parametros['mes'])) {
        $mes = $parametros['mes'];
        $ingresos = array_filter($ingresos, function($item) use ($mes) {
            return date('n', strtotime($item['fecha'])) == $mes;
        });
        $egresos = array_filter($egresos, function($item) use ($mes) {
            return date('n', strtotime($item['fecha'])) == $mes;
        });
    }
    
    // Preparar datos del reporte
    $totalIngresos = array_sum(array_column($ingresos, 'monto'));
    $totalEgresos = array_sum(array_column($egresos, 'monto'));
    $balance = $totalIngresos - $totalEgresos;
    
    $reporteData = [
        'ingresos' => array_values($ingresos),
        'egresos' => array_values($egresos),
        'resumen' => [
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'balance' => $balance,
            'cantidad_ingresos' => count($ingresos),
            'cantidad_egresos' => count($egresos)
        ],
        'parametros' => $parametros
    ];
    
    // Formato de descarga si se especifica
    if (isset($parametros['formato_descarga'])) {
        $reporteData['formato_descarga'] = $parametros['formato_descarga'];
    }
    
    return [
        'status' => 'success',
        'message' => 'Reporte generado exitosamente',
        'data' => $reporteData
    ];
}

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

$pagina = "finanza";
// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_FINANZA,
                'ACCESAR',
                'El usuario accedió al módulo de Finanzas',
                'media'
            );
        }
    }
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
        if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_FINANZA,
                'ACCESAR',
                'El usuario accedió al módulo de Finanzas',
                'media'
            );
        }
    }
} else {
    echo "Página en construcción";
}