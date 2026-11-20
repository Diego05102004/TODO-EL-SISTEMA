<?php
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Proveedores;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_rol = $_SESSION['id_rol'] ?? null;
$permisosObj = new Permisos();
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, 'proveedores');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reportes_proveedores') {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($permisosUsuario['consultar'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para consultar estos reportes.']);
        exit;
    }

    $fechaInicioRaw = $_POST['fechaInicio'] ?? '';
    $fechaFinRaw = $_POST['fechaFin'] ?? '';
    if (!is_string($fechaInicioRaw) || !is_string($fechaFinRaw)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'El rango de fechas no es válido.']);
        exit;
    }

    $fechaInicio = $fechaInicioRaw === ''
        ? null
        : DateTimeImmutable::createFromFormat('!Y-m-d', $fechaInicioRaw);
    $fechaFin = $fechaFinRaw === ''
        ? null
        : DateTimeImmutable::createFromFormat('!Y-m-d', $fechaFinRaw);

    if (($fechaInicioRaw !== '' && (!$fechaInicio || $fechaInicio->format('Y-m-d') !== $fechaInicioRaw)) ||
        ($fechaFinRaw !== '' && (!$fechaFin || $fechaFin->format('Y-m-d') !== $fechaFinRaw))) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Ingresa fechas válidas en formato AAAA-MM-DD.']);
        exit;
    }

    if ($fechaInicio && $fechaFin && $fechaInicio > $fechaFin) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'La fecha de inicio no puede ser posterior a la fecha fin.']);
        exit;
    }

    if ($fechaInicio && $fechaFin && $fechaInicio->diff($fechaFin)->days > Proveedores::MAX_RANGO_FECHAS_DIAS) {
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'message' => 'El rango de fechas no puede exceder ' . Proveedores::MAX_RANGO_FECHAS_DIAS . ' días.'
        ]);
        exit;
    }

    try {
        $proveedorModel = new Proveedores();
        $inicio = $fechaInicio ? $fechaInicio->format('Y-m-d') : null;
        $fin = $fechaFin ? $fechaFin->format('Y-m-d') : null;
        $datos = [
            'suministro' => $proveedorModel->getReporteSuministroProveedores($inicio, $fin),
            'ranking' => $proveedorModel->getReporteRankingProveedores($inicio, $fin),
            'comparacion' => $proveedorModel->getComparacionMensualSuministros($inicio, $fin),
            'dependencia' => $proveedorModel->getReporteDependenciaProveedores($inicio, $fin)
        ];

        echo json_encode(
            ['status' => 'success', 'data' => $datos],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    } catch (Throwable $e) {
        error_log('Error al generar reportes de proveedores: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudieron cargar los reportes de proveedores.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (empty($permisosUsuario['consultar'])) {
    header('Location: ?pagina=acceso-denegado');
    exit;
}

define('MODULO_PROVEEDORES', 8);
$reporteProveedor = new Proveedores();

$pagina = 'reporteProveedores';
if (is_file('Vista/VistaNew/' . $pagina . '.php')) {
    $reportesProveedor = [
        'suministro' => $reporteProveedor->getReporteSuministroProveedores(),
        'ranking' => $reporteProveedor->getReporteRankingProveedores(),
        'comparacion' => $reporteProveedor->getComparacionMensualSuministros(),
        'dependencia' => $reporteProveedor->getReporteDependenciaProveedores()
    ];
    require_once 'Vista/VistaNew/' . $pagina . '.php';
} elseif (is_file('Vista/' . $pagina . '.php')) {
    $reporteSuministroProveedores = $reporteProveedor->obtenerReporteSuministroProveedores();
    $reporteRankingProveedores = $reporteProveedor->getRankingProveedores();
    $reporteComparacion = $reporteProveedor->getComparacionPreciosProducto();
    $reporteDependencia = $reporteProveedor->getDependenciaProveedores();
    $totalSuministrado = array_sum(array_column($reporteSuministroProveedores, 'cantidad'));
    require_once 'Vista/' . $pagina . '.php';
} else {
    echo 'Página en construcción';
}
?>
