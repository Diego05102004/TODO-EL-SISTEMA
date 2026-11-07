<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\marca;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
define('MODULO_MARCA', 4);

$id_rol = $_SESSION['id_rol'] ?? 0;

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('marcas'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
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

            $marca = new marca();
            
            $marca->setnombre_marca($_POST['nombre_marca']);

            $errores = $marca->validarRegistrar($_POST);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }

            if ($marca->registrarMarca($id_usuario_sesion)) {
                $marcaRegistrada = $marca->obtenerUltimaMarca();
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Marca registrada correctamente',
                    'marca' => $marcaRegistrada
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la marca'
                ]);
            }
            exit;
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('marcas'));
            echo json_encode($permisosActualizados);
            exit;

        case 'obtener_marcas':
            $id_marca = $_POST['id_marca'];
            if ($id_marca !== null) {
                $marca = new marca();
                $marca = $marca->obtenermarcasPorId($id_marca);
                if ($marca !== null) {
                    echo json_encode($marca);

                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Marca no encontrada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de Marca no proporcionado']);
            }
            exit;

        case 'modificar':
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');

            $id_marca = $_POST['id_marca'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }
            
            $marca = new marca();

            $marca->setIdMarca($id_marca);
            $marca->setnombre_marca($_POST['nombre_marca']);

            $errores = $marca->validarModificar($_POST);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }

            $marcaVieja = $marca->obtenermarcasPorId($id_marca);
            if ($marca->modificarmarcas($id_marca, $id_usuario_sesion)) {
                $marcaActualizada = $marca->obtenermarcasPorId($id_marca);
                
                echo json_encode([
                    'status' => 'success',
                    'marca' => $marcaActualizada
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al modificar la marca'
                ]);
            }
            exit;

        case 'eliminar':
            $id_marca = $_POST['id_marca'] ?? null;
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $marca = new marca();
            $errores = $marca->validarEliminar($_POST['id_marca']);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }
        
            $eliminada = $marca->obtenermarcasPorId($id_marca);
            
            if ($marca->eliminarmarcas($id_marca, $id_usuario_sesion)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar la marca'
                ]);
            }
            exit;

        default:
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
    }
}


if (!function_exists('getmarcas')) {
    function getmarcas()
    {
        $marca = new marca();
        return $marca->getmarcas();
    }
}


$pagina = "marca";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            'Marcas',
            'ACCESAR',
            'El usuario accedió al módulo de marcas',
            'media'
        );
    }
    $marcas = getmarcas();
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            'Marcas',
            'ACCESAR',
            'El usuario accedió al módulo de marcas',
            'media'
        );
    }
    $marcas = getmarcas();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>