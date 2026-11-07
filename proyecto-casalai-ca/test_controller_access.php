<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular que accedemos al controlador como si fuera por HTTP GET
$_SERVER['REQUEST_METHOD'] = 'GET';

// Simular sesión
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['id_rol'] = 1;

// Capturar la salida
ob_start();

try {
    echo "Simulando acceso al controlador de categoría...<br><br>";
    
    // Incluir el controlador
    include 'Modelo/Controlador/categoria.php';
    
    $contenido = ob_get_clean();
    echo $contenido;
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    ob_end_clean();
    echo "❌ Error Fatal: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
