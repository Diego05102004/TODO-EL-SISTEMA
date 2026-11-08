<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Importar la clase Auth para validación JWT
require_once __DIR__ . '/../Config/Auth.php';

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;

define('MODULO_BITACORA', "Bitacora");

// Verificar autenticación JWT y autorización (solo Administrador y SuperUsuario)
$payload = Auth::requireAuth(['Administrador', 'SuperUsuario']);

// Obtener datos del usuario desde el JWT
$userId = Auth::getUserId($payload);
$userRole = Auth::getUserRole($payload);

// Establecer variables de sesión para compatibilidad con código existente
$_SESSION['id_usuario'] = $userId;
$_SESSION['rol'] = $userRole;

$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

$id_rol = $_SESSION['id_rol'] ?? 0;
$permisosModuloBitacora = $permisos->getPermisosUsuarioModulo($id_rol, 'Bitacora');

// Respuesta AJAX para la actualización en tiempo real de la tabla.
// Se atiende antes del registro de ACCESAR para no inundar la bitácora con cada sondeo.
if (isset($_GET['ajax']) && $_GET['ajax'] === 'registros') {
    // Descartar cualquier salida residual del buffer para no contaminar el JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    if (empty($permisosModuloBitacora['consultar'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tiene permiso para consultar la bitácora']);
        exit;
    }
    $limiteAjax = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;
    $limiteAjax = max(1, min(500, $limiteAjax));
    try {
        $bitacoraAjax = new Bitacora();
        $registrosAjax = $bitacoraAjax->obtenerRegistrosDetallados($limiteAjax);
        echo json_encode(['status' => 'success', 'registros' => $registrosAjax]);
    } catch (Exception $e) {
        error_log('Error AJAX bitácora: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'No se pudieron obtener los registros']);
    }
    exit;
}

if (!defined('SKIP_SIDE_EFFECTS')) {
    $bitacoraModel = new Bitacora();
    
    $datosRegistro = [
        'id_usuario' => $_SESSION['id_usuario'],
        'modulo' => MODULO_BITACORA,
        'accion' => 'ACCESAR',
        'descripcion' => 'El usuario accedió al módulo de bitácora',
        'prioridad' => 'baja'
    ];
    
    $errores = $bitacoraModel->validarRegistrar($datosRegistro);
    if (empty($errores)) {
        $bitacoraModel->registrarBitacora(
            $datosRegistro['id_usuario'],
            $datosRegistro['modulo'],
            $datosRegistro['accion'],
            $datosRegistro['descripcion'],
            $datosRegistro['prioridad']
        );
    }
}

$bitacoraModel = new Bitacora();
$registros = [];
$erroresConsulta = [];

// Obtener parámetros de consulta
$datosConsulta = [
    'limite' => $_GET['limite'] ?? 500,
    'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
    'fecha_fin' => $_GET['fecha_fin'] ?? null,
    'id_usuario' => $_GET['id_usuario'] ?? null,
    'id_modulo' => $_GET['id_modulo'] ?? null,
    'accion' => $_GET['accion'] ?? null,
    'prioridad' => $_GET['prioridad'] ?? null
];

// Validar parámetros de consulta
$erroresConsulta = $bitacoraModel->validarConsultar($datosConsulta);

if (empty($erroresConsulta)) {
    try {
        // Usar el límite validado o el valor por defecto
        $limite = isset($datosConsulta['limite']) ? (int)$datosConsulta['limite'] : 500;
        $registros = $bitacoraModel->obtenerRegistrosDetallados($limite);
    } catch (Exception $e) {
        $registros = [];
        $erroresConsulta['sistema'] = 'Error al consultar los registros: ' . $e->getMessage();
    }
} else {
    // Si hay errores de validación, usar valores por defecto
    try {
        $registros = $bitacoraModel->obtenerRegistrosDetallados(500);
    } catch (Exception $e) {
        $registros = [];
        $erroresConsulta['sistema'] = 'Error al consultar los registros: ' . $e->getMessage();
    }
}

// Render de vista: prioriza Vista/VistaNew/ con fallback a Vista/
$pagina = "bitacora";
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
