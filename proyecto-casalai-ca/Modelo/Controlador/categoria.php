<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DEBUG_PERMISOS', false);

// Cargar autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

define('MODULO_CATEGORIA', "Categorias");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$id_rol = $_SESSION['id_rol'] ?? 0;

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('categorias'));

// Obtener lista de módulos para mapear nombres a IDs
$modulos = $permisos->getModulos();
$moduloIdPorNombre = [];
foreach ($modulos as $modulo) {
    $moduloIdPorNombre[strtolower($modulo['nombre_modulo'])] = $modulo['id_modulo'];
}

// Debug: mostrar información de permisos
if (defined('DEBUG_PERMISOS') && DEBUG_PERMISOS) {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px;'>";
    echo "<h3>Debug Permisos</h3>";
    echo "<pre>";
    echo "ID Rol: $id_rol\n";
    echo "Módulos disponibles:\n";
    print_r($moduloIdPorNombre);
    echo "Permisos por rol-módulo:\n";
    print_r($permisosUsuarioEntrar);
    echo "Permisos de usuario para categorías:\n";
    print_r($permisosUsuario);
    echo "</pre>";
    echo "</div>";
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'registrar':
            // Validar datos de entrada
            $categoria = new Categoria();

            $errores = $categoria->validarRegistrar($_POST);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de la categoría',
                    'errors' => $errores
                ]);
                exit;
            }

            $categoria->setNombreCategoria($_POST['nombre_categoria']);
            $caracteristicas = isset($_POST['caracteristicas']) ? $_POST['caracteristicas'] : [];

            if ($categoria->registrarCategoria($caracteristicas, $_SESSION['id_usuario'])) {
                $categoriaRegistrado = $categoria->obtenerUltimoCategoria();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Categoria registrada correctamente',
                    'categoria' => $categoriaRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la categoria'
                ]);
            }
            exit;

            case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('categorias'));
            echo json_encode($permisosActualizados);
            exit;

        case 'consultar_categorias':
            $categoria = new Categoria();
            $categorias_obt = $categoria->consultarCategorias();
            echo json_encode($categorias_obt);
            exit;

        case 'obtener_categoria':
            // Validar datos de entrada
            $categoria = new Categoria();
            
            $errores = $categoria->validarConsultar($_POST);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }

            $id_categoria = $_POST['id_categoria'];
            if ($id_categoria !== null) {
                $categoria_obt = $categoria->obtenerCategoriaPorId($id_categoria);
                if ($categoria_obt !== null) {
                    echo json_encode($categoria_obt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Categoria no encontrada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de la categoria no proporcionada']);
            }
            exit;

        case 'modificar':
            // Validar datos de entrada
            $categoria = new Categoria();
            $datosValidacion = [
                'id_categoria' => $_POST['id_categoria'] ?? null,
                'nombre_categoria' => $_POST['nombre_categoria'] ?? '',
                'caracteristicas' => []
            ];
            
            // Decodificar características si están presentes
            if (isset($_POST['caracteristicas'])) {
                if (is_string($_POST['caracteristicas'])) {
                    $datosValidacion['caracteristicas'] = json_decode($_POST['caracteristicas'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('Error al decodificar características JSON: ' . json_last_error_msg());
                        $datosValidacion['caracteristicas'] = [];
                    }
                } else {
                    $datosValidacion['caracteristicas'] = $_POST['caracteristicas'];
                }
            }
            
            $errores = $categoria->validarModificar($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de la categoría',
                    'errors' => $errores
                ]);
                exit;
            }
            
            if (!isset($_POST['id_categoria'], $_POST['nombre_categoria'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Faltan parámetros requeridos'
                ]);
                exit;
            }
            
            $id_categoria  = $_POST['id_categoria'];
            $nuevo_nombre = trim($_POST['nombre_categoria']);
            $caracteristicas = $datosValidacion['caracteristicas'];
            
            $categoria->setIdCategoria($id_categoria);
            $categoria->setNombreCategoria($nuevo_nombre);
            
            // Intentar modificar la categoría
            $resultado = $categoria->modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas, $_SESSION['id_usuario']);
            
            if ($resultado === true) {
                $categoriaActualizada = $categoria->obtenerCategoriaPorId($id_categoria);
                
                echo json_encode([
                    'status' => 'success',
                    'categoria' => $categoriaActualizada
                ]);
            } else {
                $error = 'Error al modificar la categoría';
                if (is_string($resultado)) {
                    $error = $resultado;
                }
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $error
                ]);
            }
            exit;

        case 'eliminar':
            // Validar datos de entrada
            $categoria = new Categoria();
            
            $errores = $categoria->validarEliminar($_POST['id_categoria'] ?? null);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $id_categoria = $_POST['id_categoria'];
            
            // Verificar que la categoría exista antes de eliminar
            $categoriaExistente = $categoria->obtenerCategoriaPorId($id_categoria);
            if (!$categoriaExistente) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La categoría que intenta eliminar no existe'
                ]);
                exit;
            }

            // Intentar eliminar la categoría
            $resultado = $categoria->eliminarCategoria($id_categoria, $_SESSION['id_usuario']);

            if ($resultado['status'] === 'error') {
                // Registrar en bitácora el intento fallido
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        'Categorias',
                        'ELIMINAR_FALLIDO',
                        'Intento de eliminación fallido de categoría (ID: ' . $id_categoria . '): ' . $resultado['mensaje'],
                        'media'
                    );
                }
                
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['mensaje'],
                    'productos' => $resultado['productos'] ?? [],
                    'total_productos' => $resultado['total_productos'] ?? 0
                ]);
            } else {
                // Registrar eliminación exitosa
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        'Categorias',
                        'ELIMINAR',
                        'El usuario eliminó la categoría ID: ' . $id_categoria,
                        'media'
                    );
                }
                
                echo json_encode(['status' => 'success']);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }
}

function consultarCategorias() {
    $categoria = new Categoria();
    return $categoria->consultarCategorias();
}

$pagina = "categoria";
// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    try {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Categorias',
                'ACCESAR',
                'El usuario accedió al módulo de Categorias',
                'media'
            );
        }
        $categorias = consultarCategorias();
        require_once("Vista/VistaNew/" . $pagina . ".php");
    } catch (Exception $e) {
        echo "<div style='padding: 20px; background-color: #ffe6e6; border: 1px solid #ff0000; margin: 20px;'>";
        echo "<h3>Error en el módulo de categorías</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
