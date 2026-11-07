<?php
// Script para corregir los namespaces en las pruebas
$testFiles = [
    // Archivos de prueba que necesitan corrección de namespaces
    'tests/Unit/catalogo/CatalogoTest.php' => [
        'old' => 'use Usuario\\ProyectoCasalaiCa\\Clases\\Catalogo;',
        'new' => 'use Usuario\\ProyectoCasalaiCa\\Modelo\\Clases\\Catalogo;'
    ],
    'tests/Integration/catalogo/CatalogoFeatureTest.php' => [
        'old' => 'use Usuario\\ProyectoCasalaiCa\\Clases\\Catalogo;',
        'new' => 'use Usuario\\ProyectoCasalaiCa\\Modelo\\Clases\\Catalogo;'
    ]
];

$baseDir = __DIR__ . '/';
$updated = 0;

echo "Iniciando corrección de namespaces en pruebas...\n";

foreach ($testFiles as $file => $replacements) {
    $fullPath = $baseDir . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $newContent = str_replace(
            $replacements['old'],
            $replacements['new'],
            $content
        );
        
        if ($content !== $newContent) {
            file_put_contents($fullPath, $newContent);
            echo "Actualizado: $file\n";
            $updated++;
        }
    } else {
        echo "No encontrado: $file\n";
    }
}

echo "\nProceso completado. Se actualizaron $updated archivos.\n";
