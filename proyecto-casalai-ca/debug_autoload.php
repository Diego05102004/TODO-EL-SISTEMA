<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";

// 1. Verificar si el archivo composer.json existe
$composerJsonPath = __DIR__ . '/composer.json';
echo "1. Verificando composer.json... ";
if (file_exists($composerJsonPath)) {
    echo "OK\n";
    $composerConfig = json_decode(file_get_contents($composerJsonPath), true);
    echo "   - Configuración de autoload: " . json_encode($composerConfig['autoload'] ?? 'No definido', JSON_PRETTY_PRINT) . "\n\n";
} else {
    die("ERROR: No se encontró composer.json en la ruta: $composerJsonPath\n");
}

// 2. Verificar si el autoloader de Composer existe
$autoloadPath = __DIR__ . '/vendor/autoload.php';
echo "2. Verificando autoloader de Composer... ";
if (file_exists($autoloadPath)) {
    echo "OK\n";
    require_once $autoloadPath;
} else {
    echo "NO ENCONTRADO\n";
    echo "   Ejecuta 'composer install' en la raíz del proyecto.\n";
    exit(1);
}

// 3. Verificar si la clase Backup existe
$backupClass = 'Usuario\\ProyectoCasalaiCa\\Clases\\Backup';
echo "\n3. Verificando clase Backup...\n";

// Ruta esperada según PSR-4
$expectedPath = __DIR__ . '/Modelo/Usuario/ProyectoCasalaiCa/Clases/Backup.php';
echo "   - Ruta esperada: $expectedPath\n";
echo "   - ¿Existe el archivo? " . (file_exists($expectedPath) ? 'Sí' : 'No') . "\n";

// Verificar con class_exists
$classExists = class_exists($backupClass);
echo "   - ¿La clase existe según class_exists()? " . ($classExists ? 'Sí' : 'No') . "\n";

// Si no existe, intentar cargarla manualmente
if (!$classExists && file_exists($expectedPath)) {
    echo "   - Intentando cargar manualmente...\n";
    include_once $expectedPath;
    $classExists = class_exists($backupClass);
    echo "   - ¿La clase existe después de cargar manualmente? " . ($classExists ? 'Sí' : 'No') . "\n";
}

// 4. Verificar el autoloader de Composer
echo "\n4. Información del autoloader:\n";
$autoloaders = spl_autoload_functions();
if ($autoloaders) {
    echo "   - Se encontraron " . count($autoloaders) . " autoloaders registrados\n";
    
    // Mostrar información detallada del autoloader de Composer
    $composerAutoloader = null;
    foreach ($autoloaders as $loader) {
        if (is_array($loader) && isset($loader[0]) && is_object($loader[0])) {
            $class = get_class($loader[0]);
            if (strpos($class, 'ComposerAutoloader') === 0) {
                $composerAutoloader = $loader[0];
                echo "   - Autoloader de Composer encontrado: $class\n";
                break;
            }
        }
    }
    
    if ($composerAutoloader) {
        // Usar reflexión para obtener información del ClassLoader
        $reflection = new ReflectionClass($composerAutoloader);
        $classLoader = $reflection->getProperty('loader');
        $classLoader->setAccessible(true);
        $loader = $classLoader->getValue($composerAutoloader);
        
        // Obtener los prefijos PSR-4
        $prefixes = $loader->getPrefixesPsr4();
        echo "   - Prefijos PSR-4 registrados:\n";
        foreach ($prefixes as $prefix => $paths) {
            echo "      - $prefix => " . implode(', ', $paths) . "\n";
        }
    }
}

// 5. Verificar include_path
echo "\n5. Rutas de inclusión (include_path):\n";
$includePaths = explode(PATH_SEPARATOR, get_include_path());
foreach ($includePaths as $path) {
    echo "   - $path\n";
}

// 6. Verificar si el archivo de la clase Backup es legible
if (file_exists($expectedPath)) {
    echo "\n6. Permisos del archivo Backup.php:\n";
    $perms = fileperms($expectedPath);
    echo "   - Permisos: " . substr(sprintf('%o', $perms), -4) . "\n";
    echo "   - ¿Es legible? " . (is_readable($expectedPath) ? 'Sí' : 'No') . "\n";
}

echo "\n=== FIN DEL INFORME DE DEPURACIÓN ===\n";
echo "</pre>";
