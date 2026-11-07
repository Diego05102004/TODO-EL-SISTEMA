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
    echo "Probando el controlador con autoloader de Composer...<br>";
    
    // Incluir el controlador
    ob_start();
    include 'Modelo/Controlador/categoria.php';
    $output = ob_get_clean();
    
    if (strpos($output, 'Lista de Categorias') !== false) {
        echo "✅ ÉXITO: El controlador funciona correctamente con autoloader<br>";
        echo "✅ El error HTTP 500 ha sido solucionado<br>";
        echo "✅ Las clases se cargan correctamente vía Composer<br>";
    } elseif (strpos($output, 'Acceso Denegado') !== false) {
        echo "⚠️ El controlador carga pero hay problema de permisos<br>";
        echo "Esto es normal si el rol no tiene acceso al módulo<br>";
    } else {
        echo "⚠️ El controlador se carga pero la salida es inesperada<br>";
        echo "Primeros 500 caracteres:<br>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "❌ Error Fatal: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
