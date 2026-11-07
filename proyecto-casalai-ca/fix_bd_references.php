<?php
// Script para corregir las referencias a la clase BD
$files = [
    // Archivos en Modelo/Usuario/ProyectoCasalaiCa/Clases/
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Productos.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Permisos.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/PasareladePago.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/OrdenDespacho.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/modelo.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/marca.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Finanza.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Factura.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/DolarService.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Despacho.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/Cuentabanco.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/combo.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/categoria.php',
    'Modelo/Usuario/ProyectoCasalaiCa/Clases/catalogo.php',
    
    // Archivos en Modelo/Controlador/
    'Modelo/Controlador/recepcion.php',
    'Modelo/Controlador/permiso.php',
    'Modelo/Controlador/PasareladePago.php',
    'Modelo/Controlador/pasarela.php',
    'Modelo/Controlador/ordendespacho.php',
    'Modelo/Controlador/obtener_notificaciones.php',
    'Modelo/Controlador/obtener_carrito_count.php',
    'Modelo/Controlador/marcar_notificacion.php',
    'Modelo/Controlador/despacho.php',
    'Modelo/Controlador/comprafisica.php',
    
    // Archivos en Vista/
    'Vista/NavBar.php',
    'Vista/NewNavBar.php'
];

$baseDir = __DIR__ . '/';
$updated = 0;

echo "Iniciando corrección de referencias a BD...\n";

foreach ($files as $file) {
    $fullPath = $baseDir . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $newContent = str_replace(
            'use Usuario\\ProyectoCasalaiCa\\Config\\Config\\BD',
            'use Usuario\\ProyectoCasalaiCa\\Config\\BD',
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
