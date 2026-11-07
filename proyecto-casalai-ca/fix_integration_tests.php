<?php
/**
 * Script para corregir los namespaces en las pruebas de integración.
 */

$baseDir = __DIR__ . '/tests/Integration';
$directories = [
    'Backup', 'Bitacora', 'carrito', 'catalogo', 'Categoria', 'cliente',
    'Cuenta', 'Despacho', 'Finanza', 'login', 'marca', 'modelo', 'Notificacion',
    'ordendespacho', 'Pagos', 'Pedidos', 'perfil', 'Permisos', 'Productos',
    'proveedor', 'Recepcion', 'rol', 'usuario', 'ventaspresenciales'
];

$updatedFiles = 0;

echo "Iniciando corrección de namespaces en pruebas de integración...\n";

foreach ($directories as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        echo "Directorio no encontrado: $path\n";
        continue;
    }

    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Patrones a buscar y reemplazar
        $patterns = [
            '/use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\/' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\',
            '/use Usuario\\\\ProyectoCasalaiCa\\\\Clasess?\\\\/' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\',
            '/new \\Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\/' => 'new \\Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\',
            '/extends \\?[A-Za-z0-9_\\\\]*\\\\([A-Za-z0-9_]+)(?=\\s*\{)/' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\$1;' . "\n" . 'class $1Test extends TestCase',
        ];
        
        $newContent = $content;
        $changes = false;
        
        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $newContent, -1, $count);
            if ($count > 0) {
                $changes = true;
            }
        }
        
        if ($changes) {
            file_put_contents($file, $newContent);
            echo "Actualizado: $file\n";
            $updatedFiles++;
        }
    }
}

echo "\nProceso completado. Se actualizaron $updatedFiles archivos.\n";
