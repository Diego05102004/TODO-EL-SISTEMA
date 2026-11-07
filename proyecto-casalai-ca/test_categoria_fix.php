<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular sesión
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['id_rol'] = 1;

// Simular GET request
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    echo "Probando el controlador de categoría con las inclusiones manuales...<br>";
    
    // Incluir el controlador directamente
    ob_start();
    include 'Modelo/Controlador/categoria.php';
    $output = ob_get_clean();
    
    if (strpos($output, 'Lista de Categorias') !== false || strpos($output, 'Gestionar Categoria') !== false) {
        echo "✅ El controlador se carga correctamente y muestra la interfaz de categorías<br>";
        echo "✅ El error HTTP 500 ha sido solucionado<br>";
    } else {
        echo "⚠️ El controlador se carga pero puede haber problemas con los permisos<br>";
        echo "Salida: " . substr($output, 0, 500) . "...<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}
?>
