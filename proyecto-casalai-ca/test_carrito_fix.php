<?php
// Script para probar el método agregarProductoAlCarrito
require_once 'Modelo/Usuario/ProyectoCasalaiCa/Clases/Productos.php';

try {
    // Crear instancia del modelo
    $productosModel = new ProyectoCasalaiCa\Clases\Productos();
    
    // Datos de prueba
    $id_cliente = 1; // ID de cliente de prueba
    $id_producto = 1; // ID de producto de prueba  
    $cantidad = 1;
    
    echo "Probando agregarProductoAlCarrito...\n";
    echo "ID Cliente: $id_cliente\n";
    echo "ID Producto: $id_producto\n";
    echo "Cantidad: $cantidad\n";
    
    // Probar el método
    $resultado = $productosModel->agregarProductoAlCarrito($id_cliente, $id_producto, $cantidad);
    
    echo "Resultado: " . ($resultado === true ? "SUCCESS" : "FAILED") . "\n";
    if ($resultado !== true) {
        echo "Error: " . (is_string($resultado) ? $resultado : "Error desconocido") . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPCIÓN CAPTURADA: " . $e->getMessage() . "\n";
    echo "Tipo: " . get_class($e) . "\n";
}
?>
