<?php
echo "<h2>Verificación de Extensión ZIP</h2>";

echo "<h3>Extensiones Cargadas:</h3>";
echo "<pre>";
$extensions = get_loaded_extensions();
foreach ($extensions as $ext) {
    if (stripos($ext, 'zip') !== false) {
        echo "✅ $ext\n";
    }
}
echo "</pre>";

if (extension_loaded('zip')) {
    echo "<h3>✅ La extensión ZIP está activada</h3>";
    echo "<p>Ya puedes ejecutar composer install</p>";
} else {
    echo "<h3>❌ La extensión ZIP NO está activada</h3>";
    echo "<p>Debes activarla siguiendo las instrucciones en instrucciones_zip.md</p>";
}

echo "<h3>Información de PHP:</h3>";
echo "Versión PHP: " . phpversion() . "<br>";
echo "Archivo php.ini: " . php_ini_loaded_file() . "<br>";

// Verificar si el archivo de la extensión existe
$zip_dll = 'C:\xampp\php\ext\php_zip.dll';
if (file_exists($zip_dll)) {
    echo "✅ Archivo php_zip.dll encontrado en: $zip_dll<br>";
} else {
    echo "❌ Archivo php_zip.dll NO encontrado en: $zip_dll<br>";
}
?>
