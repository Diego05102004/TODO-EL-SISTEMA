<?php
// Debug script para categoria.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug de Categoría</h1>";

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Estado de sesión:</h2>";
echo "<pre>";
echo "ID Usuario: " . ($_SESSION['id_usuario'] ?? 'No definido') . "\n";
echo "ID Rol: " . ($_SESSION['id_rol'] ?? 'No definido') . "\n";
echo "</pre>";

// Verificar si las clases existen
echo "<h2>Clases requeridas:</h2>";
echo "<pre>";

// Verificar namespace
if (class_exists('Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria')) {
    echo "✓ Clase Categoria encontrada\n";
} else {
    echo "✗ Clase Categoria NO encontrada\n";
}

if (class_exists('Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos')) {
    echo "✓ Clase Permisos encontrada\n";
} else {
    echo "✗ Clase Permisos NO encontrada\n";
}

if (class_exists('Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora')) {
    echo "✓ Clase Bitacora encontrada\n";
} else {
    echo "✗ Clase Bitacora NO encontrada\n";
}

echo "</pre>";

// Probar crear objetos
echo "<h2>Prueba de objetos:</h2>";
echo "<pre>";

try {
    $permisos = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos();
    echo "✓ Permisos creado exitosamente\n";
    
    $permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
    echo "✓ getPermisosPorRolModulo() ejecutado\n";
    echo "Permisos obtenidos: " . print_r($permisosUsuarioEntrar, true) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error en Permisos: " . $e->getMessage() . "\n";
}

try {
    $categoria = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria();
    echo "✓ Categoria creada exitosamente\n";
    
    $categorias = $categoria->consultarCategorias();
    echo "✓ consultarCategorias() ejecutado\n";
    echo "Categorías obtenidas: " . print_r($categorias, true) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error en Categoria: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Verificar archivos de vista
echo "<h2>Archivos de vista:</h2>";
echo "<pre>";

$vistaPath = "Vista/categoria.php";
if (file_exists($vistaPath)) {
    echo "✓ Vista/categoria.php existe\n";
} else {
    echo "✗ Vista/categoria.php NO existe\n";
}

$headerPath = "Vista/header.php";
if (file_exists($headerPath)) {
    echo "✓ Vista/header.php existe\n";
} else {
    echo "✗ Vista/header.php NO existe\n";
}

$footerPath = "Vista/footer.php";
if (file_exists($footerPath)) {
    echo "✓ Vista/footer.php existe\n";
} else {
    echo "✗ Vista/footer.php NO existe\n";
}

echo "</pre>";

echo "<h2>Variables importantes:</h2>";
echo "<pre>";
echo "id_rol: " . ($id_rol ?? 'No definida') . "\n";
echo "permisosUsuarioEntrar: " . (isset($permisosUsuarioEntrar) ? print_r($permisosUsuarioEntrar, true) : 'No definida') . "\n";
echo "permisosUsuario: " . (isset($permisosUsuario) ? print_r($permisosUsuario, true) : 'No definida') . "\n";
echo "</pre>";

?>
