<?php
// Script para corregir archivos de prueba y ejecutar pruebas unitarias

// 1. Actualizar CategoriaControllerTest.php
$categoriaTestPath = __DIR__ . '/tests/Integration/Categoria/CategoriaControllerTest.php';
if (file_exists($categoriaTestPath)) {
    $content = file_get_contents($categoriaTestPath);
    
    // Reemplazar requires con use statements
    $content = preg_replace('/require_once.*?;/', '', $content);
    
    // Agregar use statements al inicio si no existen
    if (strpos($content, 'use Usuario\\ProyectoCasalaiCa\\Config\\BD;') === false) {
        $content = str_replace(
            '<?php\n',
            '<?php\n' .
            'use PHPUnit\\Framework\\TestCase;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Clases\\Categoria;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Clases\\Permiso;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Clases\\Bitacora;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Config\\Config;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Config\\BD;\n' .
            '\n' .
            '// Cargar el autoloader de Composer\n' .
            'require_once __DIR__ . ''/../../../vendor/autoload.php'';\n\n',
            $content
        );
    }
    
    file_put_contents($categoriaTestPath, $content);
    echo "✅ CategoriaControllerTest.php actualizado\n";
}

// 2. Actualizar BitacoraControllerTest.php
$bitacoraTestPath = __DIR__ . '/tests/Integration/Bitacora/BitacoraControllerTest.php';
if (file_exists($bitacoraTestPath)) {
    $content = file_get_contents($bitacoraTestPath);
    
    // Reemplazar requires con use statements
    $content = preg_replace('/require_once.*?;/', '', $content);
    
    // Agregar use statements al inicio si no existen
    if (strpos($content, 'use Usuario\\ProyectoCasalaiCa\\Config\\BD;') === false) {
        $content = str_replace(
            '<?php\n',
            '<?php\n' .
            'use PHPUnit\\Framework\\TestCase;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Clases\\Bitacora;\n' .
            'use Usuario\\ProyectoCasalaiCa\\Config\\BD;\n' .
            '\n' .
            '// Cargar el autoloader de Composer\n' .
            'require_once __DIR__ . ''/../../../vendor/autoload.php'';\n\n',
            $content
        );
    }
    
    file_put_contents($bitacoraTestPath, $content);
    echo "✅ BitacoraControllerTest.php actualizado\n";
}

// 3. Ejecutar las pruebas
echo "\nEjecutando pruebas...\n\n";

// Ejecutar pruebas de NotificacionModuleTest
$output = [];
exec('cd ' . escapeshellarg(__DIR__) . ' && vendor/bin/phpunit tests/Unit/Notificacion/NotificacionModuleTest.php 2>&1', $output, $returnCode);
echo "\n🔹 Resultados de NotificacionModuleTest:\n" . implode("\n", $output) . "\n\n";

// Si hay errores, mostrarlos
if ($returnCode !== 0) {
    echo "❌ Se encontraron errores en las pruebas.\n";
    exit(1);
}

echo "✅ ¡Todas las pruebas pasaron correctamente!\n";
