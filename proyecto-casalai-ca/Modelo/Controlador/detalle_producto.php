<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;

$productosModel = new Productos();
$bitacoraModel = new Bitacora();

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$producto = null;
$relacionados = [];

try {
    if ($id_producto > 0) {
        $producto = $productosModel->obtenerProductoDetallado($id_producto);
        // Productos relacionados por categoría
        $relacionados = $productosModel->obtenerRelacionadosPorCategoria($id_producto, 8);
        // Registrar bitácora de acceso a detalle
        if (isset($_SESSION['id_usuario'])) {
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                "Catalogo",
                'ACCESAR',
                'El usuario accedió a detalle de producto: ' . ($producto['nombre_producto'] ?? ('ID ' . $id_producto)),
                'baja'
            );
        }
    }
} catch (Exception $e) {
    $producto = null;
}

// Dólar para mostrar precios en BS
$data = [];
try {
    $dolarService = new DolarService();
    $precioDolar = $dolarService->obtenerPrecioDolar();
    $dolarService->guardarPrecioCache($precioDolar);
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
} catch (Exception $e) {
    $data['monitors'] = [
        'bcv' => [
            'price' => 35.50,
            'updated' => date('Y-m-d H:i:s') . ' (valor por defecto)'
        ]
    ];
}

$pagina = 'detalle_producto';
if (is_file('Vista/' . $pagina . '.php')) {
    require_once('Vista/' . $pagina . '.php');
} else {
    echo 'Página en construcción';
}

ob_end_flush();
