<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular sesión
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['id_rol'] = 1;

// Incluir todas las dependencias
try {
    echo "Cargando dependencias...<br>";
    
    require_once 'Modelo/Config/BD.php';
    require_once 'Modelo/Usuario/ProyectoCasalaiCa/Clases/categoria.php';
    require_once 'Modelo/Usuario/ProyectoCasalaiCa/Clases/Permisos.php';
    require_once 'Modelo/Usuario/ProyectoCasalaiCa/Clases/Bitacora.php';
    
    echo "✅ Dependencias cargadas<br>";
    
    // Simular el código del controlador
    $id_rol = $_SESSION['id_rol'] ?? 0;
    
    echo "ID Rol: $id_rol<br>";
    
    $permisos = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos();
    echo "✅ Permisos creado<br>";
    
    $permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
    echo "✅ Permisos por rol-módulo obtenidos<br>";
    
    $permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('categorias'));
    echo "✅ Permisos de usuario para categorías obtenidos<br>";
    
    // Obtener lista de módulos para mapear nombres a IDs
    $modulos = $permisos->getModulos();
    echo "✅ Módulos obtenidos<br>";
    
    $moduloIdPorNombre = [];
    foreach ($modulos as $modulo) {
        $moduloIdPorNombre[strtolower($modulo['nombre_modulo'])] = $modulo['id_modulo'];
    }
    
    echo "Módulos mapeados: " . count($moduloIdPorNombre) . "<br>";
    
    // Probar consultar categorías
    function consultarCategorias() {
        $categoria = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria();
        return $categoria->consultarCategorias();
    }
    
    $categorias = consultarCategorias();
    echo "✅ Categorías consultadas: " . count($categorias) . "<br>";
    
    echo "✅ Todas las pruebas del controlador funcionaron correctamente<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
