<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Combos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

// Definir constantes para IDs de módulo
define('MODULO_COMBOS', "Carrito");

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$comboModel = new Combos();
$bitacoraModel = new Bitacora();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        header('Content-Type: application/json; charset=utf-8');
        $accion = $_POST['accion'];

        switch ($accion) {
            case 'consultar_combo':
                try {
                    $id_combo = $_POST['id_combo'] ?? '';
                    $termino = $_POST['termino'] ?? '';
                    $estado = $_POST['estado'] ?? '';

                    // Preparar datos para validación
                    $datos_validacion = [];
                    if (!empty($id_combo)) $datos_validacion['id_combo'] = $id_combo;
                    if (!empty($termino)) $datos_validacion['termino'] = $termino;
                    if (!empty($estado)) $datos_validacion['estado'] = $estado;

                    // Validar datos usando las nuevas validaciones centralizadas
                    $errores = $comboModel->validarConsultar($datos_validacion);
                    if (!empty($errores)) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Errores de validación',
                            'errors' => $errores
                        ]);
                        exit;
                    }

                    // Aquí iría la lógica para consultar el combo
                    // Por ahora, simulamos que se consulta exitosamente
                    $combo_data = [
                        'id_combo' => $id_combo ?: rand(1, 1000),
                        'nombre_combo' => 'Combo Consultado',
                        'estado' => $estado ?: 'activo'
                    ];

                    // Registrar en bitácora
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_COMBOS,
                            'CONSULTAR',
                            "El usuario consultó el combo: " . ($id_combo ?: 'búsqueda general'),
                            'baja'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Combo consultado exitosamente',
                        'combo' => $combo_data
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                exit;

            case 'crear_combo':
                try {
                    $nombre = $_POST['nombre_combo'] ?? '';
                    $descripcion = $_POST['descripcion'] ?? '';
                    $descuentoRaw = $_POST['descuento'] ?? null;
                    $productosJson = (string)($_POST['productos'] ?? '');
                    $productos = json_decode($productosJson, true, 512, JSON_THROW_ON_ERROR);

                    if (!is_numeric($descuentoRaw) || (float)$descuentoRaw < 0 || (float)$descuentoRaw > 100) {
                        throw new InvalidArgumentException('El descuento sugerido debe ser un porcentaje entre 0 y 100.');
                    }
                    $descuento = (float)$descuentoRaw;

                    // Preparar datos para validación
                    $datos_validacion = [
                        'nombre_combo' => $nombre,
                        'descripcion' => $descripcion,
                        'productos' => $productos
                    ];

                    // Validar datos usando las nuevas validaciones centralizadas
                    $errores = $comboModel->validarCrear($datos_validacion);
                    if (!empty($errores)) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Errores de validación',
                            'errors' => $errores
                        ]);
                        exit;
                    }

                    $productosModel = new Productos();
                    $productosValidos = [];
                    foreach ($productos as $producto) {
                        $idProducto = (int)$producto['id'];
                        $cantidad = (int)$producto['cantidad'];
                        if (!$productosModel->obtenerProductoPorId($idProducto)) {
                            throw new RuntimeException('Uno de los productos seleccionados para el combo no existe.');
                        }
                        $productosValidos[] = [
                            'id' => $idProducto,
                            'cantidad' => $cantidad
                        ];
                    }

                    $id_combo = $productosModel->crearCombo(
                        trim($nombre),
                        trim($descripcion),
                        $productosValidos,
                        $descuento
                    );
                    if ((int)$id_combo <= 0) {
                        throw new RuntimeException('No se pudo confirmar el registro del combo en la base de datos.');
                    }

                    // Registrar en bitácora
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_COMBOS,
                            'INCLUIR',
                            "El usuario creó un nuevo combo: $nombre (ID: $id_combo, descuento: $descuento%)",
                            'alta'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Combo creado exitosamente',
                        'id_combo' => (int)$id_combo
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                exit;

            case 'modificar_combo':
                try {
                    $id_combo = $_POST['id_combo'] ?? '';
                    $nombre = $_POST['nombre_combo'] ?? '';
                    $descripcion = $_POST['descripcion'] ?? '';
                    $productos = json_decode($_POST['productos'], true);

                    // Preparar datos para validación
                    $datos_validacion = [
                        'id_combo' => $id_combo,
                        'nombre_combo' => $nombre,
                        'descripcion' => $descripcion,
                        'productos' => $productos
                    ];

                    // Validar datos usando las nuevas validaciones centralizadas
                    $errores = $comboModel->validarModificar($datos_validacion);
                    if (!empty($errores)) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Errores de validación',
                            'errors' => $errores
                        ]);
                        exit;
                    }

                    // Aquí iría la lógica para modificar el combo
                    // Por ahora, simulamos que se modifica exitosamente

                    // Registrar en bitácora
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_COMBOS,
                            'MODIFICAR',
                            "El usuario modificó el combo: $nombre (ID: $id_combo)",
                            'media'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Combo modificado exitosamente'
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                exit;

            case 'eliminar_combo':
                try {
                    $id_combo = $_POST['id_combo'] ?? '';

                    // Preparar datos para validación
                    $datos_validacion = [
                        'id_combo' => $id_combo
                    ];

                    // Validar datos usando las nuevas validaciones centralizadas
                    $errores = $comboModel->validarEliminar($datos_validacion);
                    if (!empty($errores)) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Errores de validación',
                            'errors' => $errores
                        ]);
                        exit;
                    }

                    // Aquí iría la lógica para eliminar el combo
                    // Por ahora, simulamos que se elimina exitosamente

                    // Registrar en bitácora
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_COMBOS,
                            'ELIMINAR',
                            "El usuario eliminó el combo (ID: $id_combo)",
                            'media'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Combo eliminado exitosamente'
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                exit;

            case 'cambiar_estado_combo':
                try {
                    $id_combo = $_POST['id_combo'] ?? '';
                    $estado = $_POST['estado'] ?? '';

                    // Preparar datos para validación
                    $datos_validacion = [
                        'id_combo' => $id_combo,
                        'estado' => $estado
                    ];

                    // Validar datos usando las nuevas validaciones centralizadas
                    $errores = $comboModel->validarCambiarEstado($datos_validacion);
                    if (!empty($errores)) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Errores de validación',
                            'errors' => $errores
                        ]);
                        exit;
                    }

                    // Aquí iría la lógica para cambiar el estado del combo
                    // Por ahora, simulamos que se cambia exitosamente

                    // Registrar en bitácora
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $accionEstado = $estado == 'activo' ? 'activó' : 'desactivó';
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_COMBOS,
                            'CAMBIAR_ESTADO',
                            "El usuario $accionEstado el combo (ID: $id_combo)",
                            'media'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Estado del combo actualizado correctamente'
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                exit;

            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Acción no válida'
                ]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Obtener datos para la vista
try {
    $combos = $comboModel->obtenerCombos();
} catch (Exception $e) {
    $combos = [];
}

// Asignar la página y cargar la vista
$pagina = "combo";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_COMBOS,
            'ACCESAR',
            'El usuario accedió al módulo de Combos',
            'media'
        );
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>