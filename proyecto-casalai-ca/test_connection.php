<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'Modelo/Config/BD.php';
    
    $bd = new \Usuario\ProyectoCasalaiCa\Config\BD('P');
    $pdo = $bd->getConexion();
    
    echo "Conexión exitosa a la base de datos<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_categoria");
    $result = $stmt->fetch();
    echo "Total categorías: " . $result['total'] . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}
?>
