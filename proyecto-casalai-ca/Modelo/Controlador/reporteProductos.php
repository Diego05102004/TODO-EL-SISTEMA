<?php
// Inicia el almacenamiento en búfer de salida
ob_start();

// Importa los modelos necesarios
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

define('MODULO_PRODUCTOS', 6); // Define el ID del módulo de cuentas bancarias

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

$permisosObj = new Permisos();
$bitacoraModel = new Bitacora();
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('productos'));
// Verifica si la solicitud se realizó mediante el método POST



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
// Verifica si el archivo de vista existe
if (is_file("Vista/" . $pagina . ".php")) {
    // Obtiene los modelos y productos
    $modelos = obtenerModelos();
        require_once("Vista/" . $pagina . ".php");
} else {
    // Muestra un mensaje si la página está en construcción
    echo "Página en construcción";
}

// Termina el almacenamiento en búfer de salida y envía la salida al navegador
ob_end_flush();
