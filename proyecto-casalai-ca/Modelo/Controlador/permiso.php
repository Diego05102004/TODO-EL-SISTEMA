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

// Roles que no pueden editarse desde esta vista (protección anti-tamper).
// Sus permisos nunca se envían ni se modifican: el SP solo borra/reinserta
// los roles incluidos en $rolesEditables.
$rolesExcluidosNombres = ['administrador', 'superusuario', 'cliente'];
$rolesEditables = array_values(array_filter($roles, function ($rol) use ($rolesExcluidosNombres) {
    return !in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'] ?? '')), $rolesExcluidosNombres);
}));
$idsRolesEditables = array_map(function ($rol) { return (int)$rol['id_rol']; }, $rolesEditables);

$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function permiso_responder($payload, $status = 200, $esAjax = false) {
    if ($esAjax) {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardarPermisos'])) {
    // Descartar cualquier permiso enviado para roles no editables
    $permisosForm = $_POST['permisos'] ?? [];
    if (is_array($permisosForm)) {
        $permisosForm = array_intersect_key($permisosForm, array_flip($idsRolesEditables));
    }

    $errores = $permisos->validarModificarPermisos($permisosForm, $rolesEditables, $modulos_permiso, $acciones);

    if (!empty($errores)) {
        if ($esAjax) {
            permiso_responder(['success' => false, 'errores' => $errores], 400, true);
        }
        $_SESSION['error_permisos'] = $errores;
        header("Location: ?pagina=permiso&error=1");
        exit;
    }

    try {
        $permisos->guardarPermisos($permisosForm, $rolesEditables, $modulos_permiso, $acciones);
    } catch (Exception $e) {
        error_log('Error al guardar permisos: ' . $e->getMessage());
        if ($esAjax) {
            permiso_responder(['success' => false, 'error' => 'No se pudieron guardar los permisos. Intente nuevamente.'], 500, true);
        }
        $_SESSION['error_permisos'] = ['general' => 'No se pudieron guardar los permisos.'];
        header("Location: ?pagina=permiso&error=1");
        exit;
    }
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

    if ($esAjax) {
        permiso_responder(['success' => true, 'message' => 'Permisos actualizados correctamente'], 200, true);
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