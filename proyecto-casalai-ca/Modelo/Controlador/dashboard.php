<?php

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
$id_rol = $_SESSION['id_rol'];
$nombre_rol = $_SESSION['nombre_rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

$permisosObj = new Permisos();

$modulos = [
    'Usuario' => ['Gestionar Usuario', 'assets/img/users-round.svg', '?pagina=usuario'],
    'Reporte de Usuarios' => ['Reporte de Usuarios', 'assets/img/chart-column.svg', '?pagina=reporteUsuarios'],
    'Recepcion' => ['Gestionar Recepcion', 'assets/img/package-open.svg', '?pagina=recepcion'],
    'Reporte de Inventario' => ['Reporte de Inventario', 'assets/img/chart-column.svg', '?pagina=reporteInventario'],
    'Despacho' => ['Gestionar Despacho', 'assets/img/package-check.svg', '?pagina=despacho'],
    'Marcas' => ['Gestionar Marcas', 'assets/img/package-search.svg', '?pagina=marca'],
    'Modelos' => ['Gestionar Modelos', 'assets/img/package-search.svg', '?pagina=modelo'],
    'Productos' => ['Gestionar Productos', 'assets/img/package-search.svg', '?pagina=producto'],
    'Categorias' => ['Gestionar Categorias', 'assets/img/package-search.svg', '?pagina=categoria'],
    'Reporte de Productos' => ['Reporte de Productos', 'assets/img/chart-column.svg', '?pagina=reporteProductos'],
    'Compra Física' => ['Gestionar Compra Fisica', 'assets/img/files.svg', '?pagina=comprafisica'],
    'Proveedores' => ['Gestionar Proveedores', 'assets/img/truck.svg', '?pagina=proveedor'],
    'Reporte de Proveedores' => ['Reporte de Proveedores', 'assets/img/chart-column.svg', '?pagina=reporteProveedores'],
    'Clientes' => ['Gestionar Clientes', 'assets/img/users-round.svg', '?pagina=cliente'],
    'Catalogo' => ['Gestionar Catálogo', 'assets/img/book-open.svg', '?pagina=catalogo'],
    'pasarela' => ['Gestionar Pagos', 'assets/img/credit-card.svg', '?pagina=pasarela'],
    'Prefactura' => ['Gestionar Pedidos', 'assets/img/receipt-text.svg', '?pagina=gestionarfactura'],
    'Ordenes de despacho' => ['Gestionar Ordenes de Despacho', 'assets/img/list-ordered.svg', '?pagina=ordendespacho'],
    'Reporte de Ventas' => ['Reporte de Ventas', 'assets/img/chart-column.svg', '?pagina=reporteVentas'],
    'Cuentas bancarias' => ['Gestionar Cuentas Bancarias', 'assets/img/landmark.svg', '?pagina=cuenta'],
    'Finanzas' => ['Gestionar Ingresos y Egresos', 'assets/img/dollar-sign.svg', '?pagina=finanza'],
    'Reporte de Finanzas' => ['Reporte de Finanzas', 'assets/img/chart-column.svg', '?pagina=reporteFinanzas'],
    'permisos' => ['Gestionar Permisos', 'assets/img/key-round.svg', '?pagina=permiso'],
    'Roles' => ['Gestionar Roles', 'assets/img/user-round-search.svg', '?pagina=rol'],
    'bitacora' => ['Gestionar Bitácora', 'assets/img/notebook.svg', '?pagina=bitacora'],
    'Backup' => ['Gestionar Backup', 'assets/img/files.svg', '?pagina=backup'],
];

$permisosConsulta = [];
foreach ($modulos as $moduloBD => $info) {
    $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
}

if ($nombre_rol === 'SuperUsuario') {
    // Forzar todos los permisos como true
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = true;
    }
} else {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
    }
}
if(is_file('Vista/VistaNew/'.$pagina.'.php')){
    require_once ('Vista/VistaNew/'.$pagina.'.php');  //si la pagina existe se carga su vista correspondiente
}elseif(is_file('Vista/'.$pagina.'.php')){
    require_once ('Vista/'.$pagina.'.php');  //si la pagina existe se carga su vista correspondiente
}else{
    echo "PAGINA EN CONSTRUCCIÓN";
}
