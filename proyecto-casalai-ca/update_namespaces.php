<?php
/**
 * Script para actualizar los namespaces en los archivos de prueba.
 * Cambia 'use Usuario\\ProyectoCasalaiCa\\Clases\\' por 'use Usuario\\ProyectoCasalaiCa\\Modelo\\Clases\\'
 */

$baseDir = __DIR__ . '/tests/Unit';
$directories = [
    'Backup', 'Bitacora', 'carrito', 'catalogo', 'Categoria', 'cliente',
    'Cuenta', 'Despacho', 'Finanza', 'login', 'marca', 'modelo', 'Notificacion',
    'ordendespacho', 'Pagos', 'Pedidos', 'perfil', 'Permisos', 'Productos',
    'proveedor', 'Recepcion', 'rol', 'usuario', 'ventaspresenciales'
];

$updatedFiles = 0;

echo "Iniciando actualización de namespaces...\n";

foreach ($directories as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        echo "Directorio no encontrado: $path\n";
        continue;
    }

    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $newContent = str_replace(
            'use Usuario\\ProyectoCasalaiCa\\Clases\\',
            'use Usuario\\ProyectoCasalaiCa\\Modelo\\Clases\\',
            $content
        );

        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            echo "Actualizado: $file\n";
            $updatedFiles++;
        }
    }
}

echo "\nProceso completado. Se actualizaron $updatedFiles archivos.\n";
