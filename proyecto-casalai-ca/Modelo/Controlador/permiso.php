<?php
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuario;
use Usuario\ProyectoCasalaiCa\Config\BD;
define('MODULO_PERMISOS', "Permisos");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$permisos = new Permisos();

$permisosActuales = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

$roles = $permisos->getRoles();

$modulos_permiso = $permisos->getModulos();

$acciones = ['ingresar','consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardarPermisos'])) {
    $permisosForm = $_POST['permisos'] ?? [];
    
    $errores = $permisos->validarModificarPermisos($permisosForm, $roles, $modulos_permiso, $acciones);
    
    if (!empty($errores)) {
        $_SESSION['error_permisos'] = $errores;
        header("Location: ?pagina=permiso&error=1");
        exit;
    }

    $permisos->guardarPermisos($_POST['permisos'] ?? [], $roles, $modulos_permiso, $acciones);
    $bd_seguridad = new BD();
    $pdo_seguridad = $bd_seguridad->getConexion();
    $notificacionesModel = new NotificacionModel($pdo_seguridad);
    $notificacionesModel->crear(
        $_SESSION['id_usuario'],
        'seguridad',
        'Permisos actualizados',
        'Se han actualizado los permisos de los roles del sistema por el usuario ' . $_SESSION['name'],
        null,
        'media',
        MODULO_PERMISOS,
        'modificar'
    );
    
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERMISOS,
            'MODIFICAR',
            'El usuario modificó los permisos de los roles del sistema',
            'media'
        );
    }

    header("Location: ?pagina=permiso&ok=1");
    exit;
}

$pagina = "permiso";
// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    require_once("Vista/VistaNew/" . $pagina . ".php");
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERMISOS,
            'ACCESAR',
            'El usuario accedió al módulo de Permisos',
            'media'
        );
    }
} elseif (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERMISOS,
            'ACCESAR',
            'El usuario accedió al módulo de Permisos',
            'media'
        );
    }
} else {
    echo "Página en construcción";
}