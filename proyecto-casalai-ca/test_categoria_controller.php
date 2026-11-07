<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular sesión
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['id_rol'] = 1;

// Incluir el controlador
try {
    echo "Incluyendo controlador de categoría...<br>";
    
    // Incluir las dependencias necesarias
    require_once 'Modelo/Config/BD.php';
    require_once 'Modelo/Usuario/ProyectoCasalaiCa/Clases/categoria.php';
    
    echo "Dependencias cargadas correctamente<br>";
    
    // Probar crear una instancia de Categoria
    $categoria = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria();
    echo "Instancia de Categoria creada correctamente<br>";
    
    // Probar consultar categorías
    $categorias = $categoria->consultarCategorias();
    echo "Categorías consultadas: " . count($categorias) . "<br>";
    
    // Probar obtener una categoría específica
    if (!empty($categorias)) {
        $primeraCategoria = $categoria->obtenerCategoriaPorId($categorias[0]['id_categoria']);
        echo "Primera categoría obtenida: " . $primeraCategoria['nombre_categoria'] . "<br>";
    }
    
    echo "✅ Todas las pruebas básicas funcionaron correctamente<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
