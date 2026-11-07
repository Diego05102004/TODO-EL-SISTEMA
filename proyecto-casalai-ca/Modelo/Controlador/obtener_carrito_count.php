<?php
session_start();        
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Incluir manualmente el archivo de configuración de la base de datos
require_once __DIR__ . '/../Config/database.php';
// Incluir manualmente los archivos de las clases necesarias
require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../Usuario/ProyectoCasalaiCa/Clases/Carrito.php';

use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Carrito;

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'count' => 0, 'message' => 'Usuario no autenticado']);
        exit;
    }

    $carritoObj = new Carrito();
    $carritoCliente = $carritoObj->obtenerCarritoPorCliente($_SESSION['id_usuario']);
    
    $count = 0;
    if ($carritoCliente) {
        $productosCarrito = $carritoObj->obtenerProductosDelCarrito($carritoCliente['id_carrito']);
        $count = count($productosCarrito);
    }

    echo json_encode(['success' => true, 'count' => $count]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0, 'error' => $e->getMessage()]);
}
?>