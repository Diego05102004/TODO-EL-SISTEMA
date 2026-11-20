<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;

$permisosObj = new Permisos();
$id_rol = $_SESSION['id_rol'] ?? null;
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('productos'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reportes_productos') {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($permisosUsuario['consultar'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para consultar este reporte.']);
        exit;
    }

    $topNRaw = $_POST['topN'] ?? '';
    if (!is_string($topNRaw) || !ctype_digit($topNRaw) || (int) $topNRaw < 1 || (int) $topNRaw > 50) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Top N debe ser un número entre 1 y 50.']);
        exit;
    }

    try {
        $productoModel = new Productos();
        echo json_encode([
            'status' => 'success',
            'data' => $productoModel->getProductosMasVendidos((int) $topNRaw)
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } catch (Throwable $e) {
        error_log('Error al generar reporte de productos más vendidos: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo cargar el reporte de productos más vendidos.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (empty($permisosUsuario['consultar'])) {
    header('Location: ?pagina=acceso-denegado');
    exit;
}

ob_start();

function obtenerModelos() {
    $ModelosModel = new Productos();
    return $ModelosModel->obtenerModelos();
}

$productoModel = new Productos();
// Arrays con la información
$masVendidos   = $productoModel->getProductosMasVendidos();
$stockProductos = $productoModel->getStockProductos();
$rotacion      = $productoModel->getRotacionProductos();
$categorias = $productoModel->CategoriasReporte();

$categoriasDinamicas = $productoModel->obtenerCategoriasDinamicas();

$reporteCategorias = $productoModel->obtenerReporteCategorias();

if (!$reporteCategorias || !is_array($reporteCategorias)) {
    $reporteCategorias = [];
}

$totalCategorias = array_sum(array_column($reporteCategorias, 'cantidad'));
foreach ($reporteCategorias as &$cat) {
    $cat['porcentaje'] = $totalCategorias > 0 ? round(($cat['cantidad'] / $totalCategorias) * 100, 2) : 0;
}
unset($cat);
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo "<pre style='background:#eee;padding:10px;'>";
    echo "reporteCategorias:\n";
    print_r($reporteCategorias);
    echo "\ntotalCategorias: $totalCategorias\n";
    echo "</pre>";
}

if (empty($categoriasDinamicas)) {
    $mostrarFormulario = false;
} else {
    $mostrarFormulario = true;
}
// Asigna el nombre de la página
$pagina = "reporteProductos";
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    $modelos = obtenerModelos();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
