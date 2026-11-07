<?php
// Script de prueba para el módulo de categorías
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos necesarios
require_once 'Modelo/Config/BD.php';
require_once 'Modelo/Usuario/ProyectoCasalaiCa/Clases/categoria.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria;

try {
    echo "<h2>Prueba del Módulo de Categorías</h2>";
    
    // Probar creación de categoría
    $categoria = new Categoria();
    echo "✓ Categoría instanciada correctamente<br>";
    
    // Probar consulta de categorías
    $categorias = $categoria->consultarCategorias();
    echo "✓ Consulta de categorías funciona correctamente<br>";
    echo "Categorías encontradas: " . count($categorias) . "<br>";
    
    // Probar validación
    $datos_test = [
        'nombre_categoria' => 'Categoría de Prueba'
    ];
    $errores = $categoria->validarRegistrar($datos_test);
    if (empty($errores)) {
        echo "✓ Validación de registro funciona correctamente<br>";
    } else {
        echo "Errores de validación: " . json_encode($errores) . "<br>";
    }
    
    echo "<h3>Prueba completada exitosamente</h3>";
    
} catch (Exception $e) {
    echo "<h3>Error durante la prueba:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
