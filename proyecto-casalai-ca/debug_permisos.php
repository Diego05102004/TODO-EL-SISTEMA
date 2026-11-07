<?php
// Debug de permisos
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Debug de Permisos</h1>";

echo "<h2>Sesión actual:</h2>";
echo "<pre>";
echo "ID Usuario: " . ($_SESSION['id_usuario'] ?? 'No definido') . "\n";
echo "ID Rol: " . ($_SESSION['id_rol'] ?? 'No definido') . "\n";
echo "</pre>";

if (!class_exists('Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos')) {
    echo "❌ La clase Permisos no existe\n";
    exit;
}

try {
    $permisos = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos();
    echo "✅ Objeto Permisos creado\n";
    
    // Probar getPermisosPorRolModulo
    echo "<h2>getPermisosPorRolModulo():</h2>";
    $permisosPorRolModulo = $permisos->getPermisosPorRolModulo();
    echo "<pre>";
    print_r($permisosPorRolModulo);
    echo "</pre>";
    
    // Probar getPermisosUsuarioModulo
    echo "<h2>getPermisosUsuarioModulo():</h2>";
    $id_rol = $_SESSION['id_rol'] ?? 0;
    echo "Buscando permisos para rol: $id_rol y módulo: 'categorias'\n";
    $permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, 'categorias');
    echo "<pre>";
    print_r($permisosUsuario);
    echo "</pre>";
    
    echo "<h2>Verificación de módulos en BD:</h2>";
    $modulos = $permisos->getModulos();
    echo "<pre>";
    print_r($modulos);
    echo "</pre>";
    
    echo "<h2>Verificación de roles en BD:</h2>";
    $roles = $permisos->getRoles();
    echo "<pre>";
    print_r($roles);
    echo "</pre>";
    
    // Simular la lógica de la vista
    echo "<h2>Simulación de lógica de la vista:</h2>";
    $idRol = $_SESSION['id_rol'] ?? 0;
    $idModulo = "categoria";
    
    echo "idRol: $idRol\n";
    echo "idModulo: $idModulo\n";
    
    if (isset($permisosPorRolModulo[$idRol])) {
        echo "✅ Se encontraron permisos para el rol $idRol\n";
        echo "Permisos disponibles: <pre>";
        print_r($permisosPorRolModulo[$idRol]);
        echo "</pre>";
        
        // Buscar el módulo por nombre
        $moduloEncontrado = false;
        foreach ($permisosPorRolModulo[$idRol] as $id_modulo_bd => $permisos_modulo) {
            echo "Verificando módulo ID: $id_modulo_bd\n";
            
            // Obtener nombre del módulo
            foreach ($modulos as $modulo) {
                if ($modulo['id_modulo'] == $id_modulo_bd) {
                    echo "Nombre del módulo: " . $modulo['nombre_modulo'] . "\n";
                    if (strtolower($modulo['nombre_modulo']) === strtolower($idModulo)) {
                        echo "✅ ¡Módulo encontrado!\n";
                        $moduloEncontrado = true;
                        
                        if (isset($permisos_modulo['consultar']) && $permisos_modulo['consultar'] === true) {
                            echo "✅ Permiso de consultar concedido\n";
                        } else {
                            echo "❌ Permiso de consultar denegado\n";
                        }
                    }
                }
            }
        }
        
        if (!$moduloEncontrado) {
            echo "❌ No se encontró el módulo '$idModulo' en los permisos\n";
        }
    } else {
        echo "❌ No se encontraron permisos para el rol $idRol\n";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<pre>";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    echo "</pre>";
}

?>
