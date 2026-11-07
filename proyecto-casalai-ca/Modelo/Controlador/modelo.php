<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\modelo;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\marca;

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

define('MODULO_MODELOS', 5);

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('modelos'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('modelos'));
            echo json_encode($permisosActualizados);
            exit;
            
        case 'registrar':
            header('Content-Type: application/json; charset=utf-8');
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $modelo = new modelo();
            
            $modelo->setnombre_modelo($_POST['nombre_modelo']);
            $modelo->setid_marca($_POST['id_marca']);

            $errores = $modelo->validarRegistrar($_POST);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }

            if ($modelo->registrarModelo($id_usuario_sesion)) {
                $modeloRegistrado = $modelo->obtenerUltimoModelo();
                echo json_encode([
                    'status' => 'success',
                    'message' => 'modelo registrado correctamente',
                    'modelo' => $modeloRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar el modelo'
                ]);
            }
            exit;

        case 'obtener_modelo':
            $id_modelo = $_POST['id_modelo'];
            if ($id_modelo !== null) {
                $modelo = new modelo();
                $modelo = $modelo->obtenerModeloPorId($id_modelo);
                if ($modelo !== null) {
                    echo json_encode($modelo);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'modelo no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de modelo no proporcionado']);
            }
            exit;

        case 'modificar':
            $id_modelo = $_POST['id_modelo'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $modelo = new modelo();
            
            $modelo->setIdModelo($id_modelo);
            $modelo->setnombre_modelo($_POST['nombre_modelo']);
            $modelo->setid_marca($_POST['id_marca']);

            $errores = $modelo->validarModificar($_POST);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }

            if ($modelo->modificarModelo($id_modelo, $id_usuario_sesion)) {
                $modeloActualizado = $modelo->obtenerModeloConMarcaPorId($id_modelo);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Modelo modificado correctamente',
                    'modelo' => $modeloActualizado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al modificar el modelo'
                ]);
            }
            exit;
            
        case 'eliminar':
            $id_modelo = $_POST['id_modelo'] ?? null;
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $modelo = new modelo();
            $errores = $modelo->validarEliminar($_POST['id_modelo']);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }

            $eliminado = $modelo->obtenerModeloPorId($id_modelo);
            
            if ($modelo->eliminarModelo($id_modelo, $id_usuario_sesion)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar la modelo'
                ]);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
    }
    exit;
}

function getModelos() {
    $modelo = new modelo();
    return $modelo->getModelos();
}

function getmarcas() {
    $marcas = new marca();
    return $marcas->getmarcas();
}

$pagina = "modelo";
// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    $modelos = getModelos();
    $marcas = getmarcas();
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        'Modelos',
        'ACCESAR',
        'El usuario accedió al módulo de Modelos',
        'media'
    );
    }
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    $modelos = getModelos();
    $marcas = getmarcas();
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        'Modelos',
        'ACCESAR',
        'El usuario accedió al módulo de Modelos',
        'media'
        );
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
