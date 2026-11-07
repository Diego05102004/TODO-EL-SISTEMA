<?php
// Script para corregir los namespaces en todas las pruebas
$testFiles = [
    // Unit Tests
    'tests/Unit/ventaspresenciales/CompraTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Comprafisica;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Comprafisica;'
    ],
    'tests/Unit/usuario/UsuarioTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Usuarios;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Usuarios;'
    ],
    'tests/Unit/rol/RolTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Rol;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Rol;'
    ],
    'tests/Unit/Recepcion/RecepcionModuleTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Recepcion;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Recepcion;'
    ],
    'tests/Unit/proveedor/ProveedorTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Proveedores;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Proveedores;'
    ],
    'tests/Unit/Productos/ProductosCrudTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Productos;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Productos;'
    ],
    'tests/Unit/Permisos/PermisosReadTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Permisos;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Permisos;'
    ],
    'tests/Unit/perfil/PerfilTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Perfil;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Perfil;'
    ],
    'tests/Unit/Pedidos/FacturaCrudTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Factura;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Factura;'
    ],
    'tests/Unit/Pagos/PagosCrudTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\PasareladePago;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\PasareladePago;'
    ],
    'tests/Unit/ordendespacho/OrdenDespachoTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\OrdenDespacho;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\OrdenDespacho;'
    ],
    'tests/Unit/Notificacion/NotificacionModuleTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\NotificacionModel;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\NotificacionModel;'
    ],
    'tests/Unit/modelo/ModeloTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Modelo;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Modelo;'
    ],
    'tests/Unit/marca/MarcaTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Marca;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Marca;'
    ],
    'tests/Unit/login/LoginTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Login;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Login;'
    ],
    'tests/Unit/Finanza/FinanzaModuleTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Finanza;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Finanza;'
    ],
    'tests/Unit/Despacho/DespachoModuleTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Despacho;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Despacho;'
    ],
    'tests/Unit/Cuenta/CuentaModuleTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Cuentabanco;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Cuentabanco;'
    ],
    'tests/Unit/cliente/ClienteTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\cliente;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\cliente;'
    ],
    'tests/Unit/Bitacora/BitacoraCrudTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Bitacora;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Bitacora;'
    ],
    'tests/Unit/carrito/CarritoTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Carrito;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Carrito;'
    ],
    'tests/Unit/Backup/BackupModuleTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Backup;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Backup;'
    ],
    
    // Integration Tests
    'tests/Integration/marca/MarcaFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Marca;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Marca;'
    ],
    'tests/Integration/usuario/UsuarioFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Usuarios;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Usuarios;'
    ],
    'tests/Integration/ventaspresenciales/VentasPresencialesFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Comprafisica;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Comprafisica;'
    ],
    'tests/Integration/rol/RolFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Rol;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Rol;'
    ],
    'tests/Integration/proveedor/ProveedorFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Proveedores;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Proveedores;'
    ],
    'tests/Integration/perfil/PerfilFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Perfil;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Perfil;'
    ],
    'tests/Integration/ordendespacho/OrdenDespachoFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\OrdenDespacho;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\OrdenDespacho;'
    ],
    'tests/Integration/Notificacion/NotificacionModelTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Notificacion\\\\Model;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Notificacion\\\\Model;'
    ],
    'tests/Integration/modelo/ModeloFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Modelo;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Modelo;'
    ],
    'tests/Integration/login/LoginFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Login;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Login;'
    ],
    'tests/Integration/cliente/ClienteFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Cliente;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Cliente;'
    ],
    'tests/Integration/Cuenta/CuentaControllerTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Cuentabanco;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Cuentabanco;'
    ],
    'tests/Integration/Categoria/CategoriaTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\categoria;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\categoria;'
    ],
    'tests/Integration/Categoria/CategoriaControllerTest2.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\categoria;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\categoria;'
    ],
    'tests/Integration/Categoria/CategoriaControllerTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Categoria;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Categoria;'
    ],
    'tests/Integration/carrito/CarritoFeatureTest.php' => [
        'old' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Clases\\\\Carrito;',
        'new' => 'use Usuario\\\\ProyectoCasalaiCa\\\\Modelo\\\\Clases\\\\Carrito;'
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
