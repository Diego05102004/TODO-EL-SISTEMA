<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuarios;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Rol;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
define('MODULO_USUARIO', 1);

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();

$id_rol = $_SESSION['id_rol'] ?? null;

$bitacoraModel = new Bitacora();

$usuarioModel = new Usuarios();
$rolModel = new Rol();

// --- Código relacionado al reporte de usuarios por rol ---
$reporteRoles = $usuarioModel->obtenerReporteRoles();
$selecionarRol = $rolModel->consultarRoles();
// Nuevos datasets crudos para agregación en el cliente (sin AJAX)
$usuariosTodos = $usuarioModel->getusuarios('todos');
$usuariosHabilitados = $usuarioModel->getusuarios('habilitado');
$usuariosDeshabilitados = $usuarioModel->getusuarios('inhabilitado');

// Debug: verificar datos
error_log("DEBUG: Total usuarios: " . count($usuariosTodos));
error_log("DEBUG: Usuarios habilitados: " . count($usuariosHabilitados));
error_log("DEBUG: Usuarios deshabilitados: " . count($usuariosDeshabilitados));
if (!empty($usuariosTodos)) {
    foreach ($usuariosTodos as $usuario) {
        error_log("DEBUG: Usuario ID: {$usuario['id_usuario']}, Estatus: '{$usuario['estatus']}'");
    }
}

$totalRoles = array_sum(array_column($reporteRoles, 'cantidad'));
foreach ($reporteRoles as &$rol) {
    $rol['porcentaje'] = $totalRoles > 0 ? round(($rol['cantidad'] / $totalRoles) * 100, 2) : 0;
}
unset($rol);
// ---------------------------------------------------------

$pagina = "reporteUsuario";
if (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_USUARIO,
            'ACCESAR',
            'El usuario accedió al módulo de Reportes de Usuarios',
            'media'
        );
    }
    require_once("Vista/" . $pagina . ".php");

} else {
    echo "Página en construcción";
}

ob_end_flush();
?>