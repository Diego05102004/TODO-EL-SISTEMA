<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Instalación Manual del Autoloader</h2>";

// Crear directorio vendor si no existe
$vendorDir = __DIR__ . '/vendor';
if (!is_dir($vendorDir)) {
    mkdir($vendorDir, 0755, true);
    echo "✅ Directorio vendor creado<br>";
}

// Crear estructura de Composer
$composerDir = $vendorDir . '/composer';
if (!is_dir($composerDir)) {
    mkdir($composerDir, 0755, true);
    echo "✅ Directorio composer creado<br>";
}

// Crear autoloader básico
$autoloadPhp = $vendorDir . '/autoload.php';
$autoloadContent = '<?php
// Autoloader básico para Usuario\ProyectoCasalaiCa
spl_autoload_register(function ($class) {
    // Convertir namespace a ruta de archivo
    $prefix = "Usuario\\\\ProyectoCasalaiCa\\\\";
    $base_dir = __DIR__ . "/../Modelo/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // No es una clase de nuestro namespace
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});
';

if (file_put_contents($autoloadPhp, $autoloadContent)) {
    echo "✅ Archivo autoload.php creado<br>";
} else {
    echo "❌ Error al crear autoload.php<br>";
}

// Crear archivos de Composer
$autoloadClassmap = $composerDir . '/autoload_classmap.php';
$classmapContent = '<?php return array ();';
file_put_contents($autoloadClassmap, $classmapContent);

$autoloadNamespaces = $composerDir . '/autoload_namespaces.php';
$namespacesContent = '<?php return array ();';
file_put_contents($autoloadNamespaces, $namespacesContent);

$autoloadPsr4 = $composerDir . '/autoload_psr4.php';
$psr4Content = '<?php
return array (
    "Usuario\\\\ProyectoCasalaiCa\\\\" => array(__DIR__ . "/../../Modelo"),
);
';
file_put_contents($autoloadPsr4, $psr4Content);

$autoloadStatic = $composerDir . '/autoload_static.php';
$staticContent = '<?php
namespace Composer\Autoload;

class ComposerStaticInit
{
    public static $classMap = array();
    
    public static function getInitializer(ClassLoader $loader)
    {
        return function () use ($loader) {
            // Inicialización estática
        };
    }
}
';
file_put_contents($autoloadStatic, $staticContent);

$ClassLoader = $composerDir . '/ClassLoader.php';
$classLoaderContent = '<?php
namespace Composer\Autoload;

class ClassLoader
{
    private $prefixes = array();
    
    public function add($prefix, $paths)
    {
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = array();
        }
        
        if (is_array($paths)) {
            $this->prefixes[$prefix] = array_merge($this->prefixes[$prefix], $paths);
        } else {
            $this->prefixes[$prefix][] = $paths;
        }
    }
    
    public function loadClass($class)
    {
        foreach ($this->prefixes as $prefix => $paths) {
            if (strpos($class, $prefix) === 0) {
                $relativeClass = substr($class, strlen($prefix));
                $file = str_replace("\\\\", "/", $relativeClass) . ".php";
                
                foreach ($paths as $path) {
                    $fullPath = $path . "/" . $file;
                    if (file_exists($fullPath)) {
                        require_once $fullPath;
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    public function register()
    {
        spl_autoload_register(array($this, "loadClass"));
    }
}
';
file_put_contents($ClassLoader, $classLoaderContent);

echo "<h3>✅ Autoloader básico creado correctamente</h3>";
echo "<p>El módulo de categorías debería funcionar ahora con el autoloader</p>";
?>
