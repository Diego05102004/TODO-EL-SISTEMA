<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

// Manejo de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en output, pero registrarlos

// Capturar cualquier error fatal
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Error fatal: " . $error['message'] . " en " . $error['file'] . " línea " . $error['line']);
        
        // Enviar respuesta JSON si hay headers
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => 'Error fatal del servidor',
                'debug' => $error['message'] . " en línea " . $error['line']
            ]);
        }
    }
});

use Usuario\ProyectoCasalaiCa\Modelo\Clases\cliente;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

// Verificar que la sesión esté iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada']);
    exit;
}

$id_rol = isset($_SESSION['id_rol']) ? $_SESSION['id_rol'] : null;
if (!$id_rol) {
    echo json_encode(['status' => 'error', 'message' => 'No hay rol de usuario en sesión']);
    exit;
}

define('MODULO_CLIENTE', "Clientes");

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('clientes'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'registrar':
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }
            // Validar datos de entrada
            $cliente = new cliente();
            
            $cliente->setnombre($_POST['nombre']);
            $cliente->setcedula($_POST['cedula']);
            $cliente->settelefono($_POST['telefono']);
            $cliente->setdireccion($_POST['direccion']);
            $cliente->setcorreo($_POST['correo']);
            $cliente->setactivo(1);

            $errores = $cliente->validarRegistrar($_POST);

            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos del cliente',
                    'errors' => $errores
                ]);
                exit;
            }
            
            if ($cliente->ingresarclientes($id_usuario_sesion)) {
                $clienteRegistrado = $cliente->obtenerUltimoCliente();
                error_log("Cliente registrado: " . print_r($clienteRegistrado, true));

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Cliente registrado correctamente',
                    'cliente' => $clienteRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Error al registrar el Cliente',
                    'debug' => 'La función ingresarclientes() retornó false'
                ]);
            }
            exit;
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('clientes'));
            echo json_encode($permisosActualizados);
            exit;
        case 'obtener_clientes':
            // Validar datos de entrada
            $cliente = new cliente();
            
            $errores = $cliente->validarConsultar($_POST);

            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }

            $id = $_POST['id_clientes'];
            if ($id !== null) {
                $cliente = $cliente->obtenerclientesPorId($id);
                if ($cliente !== null) {
                    echo json_encode($cliente);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de Cliente no proporcionado']);
            }
            exit;

        case 'modificar':
            // Limpiar cualquier salida previa y establecer cabeceras
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');

            $id_cliente = $_POST['id_clientes'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            if ($id_cliente === null || !ctype_digit((string)$id_cliente) || !$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID del cliente o sesión de usuario no válida'
                ]);
                exit;
            }
            
            // Validar datos de entrada
            $cliente = new cliente();

            $id = $_POST['id_clientes'];
            $cliente->setId($id);
            $cliente->setnombre($_POST['nombre']);
            $cliente->setcedula($_POST['cedula']);
            $cliente->settelefono($_POST['telefono']);
            $cliente->setdireccion($_POST['direccion']);
            $cliente->setcorreo($_POST['correo']);

            // Depuración: registrar lo que se recibe
            error_log("Datos recibidos para modificar: " . print_r($_POST, true));

            $errores = $cliente->validarModificar($_POST);
            error_log("Errores de validación modificar: " . print_r($errores, true));

            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos del cliente',
                    'errors' => $errores
                ]);
                exit;
            }

            // Verificar que el cliente exista antes de modificar
            $clienteExistente = $cliente->obtenerclientesPorId($id);
            error_log("Cliente existente para modificar: " . ($clienteExistente ? 'Sí' : 'No'));

            if (!$clienteExistente) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El cliente que intenta modificar no existe'
                ]);
                exit;
            }

            // Mantener el estado actual del cliente (no cambiarlo al modificar)
            $cliente->setactivo($clienteExistente['activo'] ?? 1);

            $resultado = $cliente->modificarclientes($id, $id_usuario_sesion);
            error_log("Resultado de modificación: " . ($resultado ? 'Exitoso' : 'Fallido'));
            
            if ($resultado) {
                $clienteModificado = $cliente->obtenerclientesPorId($id);

                echo json_encode([
                    'status' => 'success',
                    'cliente' => $clienteModificado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Error al modificar el cliente',
                    'debug' => 'La función modificarclientes() retornó false'
                ]);
            }
            exit;

        case 'eliminar':
            // Limpiar cualquier salida previa y establecer cabeceras
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');

            // Validar datos de entrada
            $cliente = new cliente();
            
            // Extraer ID del POST
            $id = isset($_POST['id_clientes']) ? $_POST['id_clientes'] : null;

            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            if ($id === null || !ctype_digit((string)$id) || !$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID del cliente o sesión de usuario no válida'
                ]);
                exit;
            }
            
            // Depuración: registrar lo que se recibe
            error_log("Datos recibidos para eliminar: " . print_r($_POST, true));
            error_log("ID extraído: " . $id);
            
            if (!$id) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID del cliente no proporcionado',
                    'debug' => 'POST data: ' . json_encode($_POST)
                ]);
                exit;
            }
            
            try {
                $errores = $cliente->validarEliminar($id);
                error_log("Errores de validación: " . print_r($errores, true));
                
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos inválidos',
                        'errors' => $errores
                    ]);
                    exit;
                }
                
                // Verificar que el cliente exista antes de eliminar
                $clienteExistente = $cliente->obtenerclientesPorId($id);
                error_log("Cliente existente: " . ($clienteExistente ? 'Sí' : 'No'));
                
                if (!$clienteExistente) {
                    echo json_encode(['status' => 'error', 'message' => 'El cliente que intenta eliminar no existe']);
                    exit;
                }
                
                $resultado = $cliente->eliminarclientes($id, $id_usuario_sesion);
                error_log("Resultado de eliminación: " . ($resultado ? 'Exitoso' : 'Fallido'));
                
                if ($resultado) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Cliente eliminado correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error', 
                        'message' => 'Error al eliminar el Cliente',
                        'debug' => 'La función eliminarclientes() retornó false'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Excepción en eliminar: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en el servidor: ' . $e->getMessage(),
                    'debug' => 'Exception: ' . $e->getTraceAsString()
                ]);
            }
            exit;

        case 'cambiar_estado':
            // Limpiar cualquier salida previa y establecer cabeceras
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');

            $id = isset($_POST['id_clientes']) ? $_POST['id_clientes'] : null;
            $estado_actual = isset($_POST['estado_actual']) ? (int)$_POST['estado_actual'] : null;
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            if ($id === null || !ctype_digit((string)$id) || $estado_actual === null || !$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos para cambiar el estado'
                ]);
                exit;
            }

            try {
                $cliente = new cliente();
                
                // Verificar que el cliente exista
                $clienteExistente = $cliente->obtenerclientesPorId($id);
                if (!$clienteExistente) {
                    echo json_encode(['status' => 'error', 'message' => 'El cliente no existe']);
                    exit;
                }

                // Calcular nuevo estado (invertir el actual)
                $nuevo_estado = ($estado_actual === 1) ? 0 : 1;

                // Actualizar el estado
                $cliente->setId($id);
                $cliente->setnombre($clienteExistente['nombre']);
                $cliente->setcedula($clienteExistente['cedula']);
                $cliente->settelefono($clienteExistente['telefono']);
                $cliente->setdireccion($clienteExistente['direccion']);
                $cliente->setcorreo($clienteExistente['correo']);
                $cliente->setactivo($nuevo_estado);

                $resultado = $cliente->modificarclientes($id, $id_usuario_sesion);

                if ($resultado) {
                    $mensaje_estado = ($nuevo_estado === 1) ? 'habilitado' : 'deshabilitado';
                    echo json_encode([
                        'status' => 'success',
                        'message' => "Cliente {$mensaje_estado} correctamente",
                        'nuevo_estado' => $nuevo_estado
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error al cambiar el estado del cliente'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Excepción en cambiar_estado: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en el servidor: ' . $e->getMessage()
                ]);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }
}

// Función para obtener clientes (usada por otras partes del sistema)
function getclientes() {
    $cliente = new cliente();
    return $cliente->getclientes();
}

// Código para carga de la página (solo si no es una solicitud AJAX)
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $cliente = new cliente();
    $reporteComprasClientes = $cliente->obtenerReporteComprasClientes();
    $totalComprasClientes = array_sum(array_column($reporteComprasClientes, 'cantidad'));
    $pagina = "cliente";
    
    // Buscar primero en Vista/VistaNew/ y luego en Vista/
    if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
        if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                '9',
                'ACCESAR',
                'El usuario accedió al módulo de Clientes',
                'media'
            );
        }
        $clientes = getclientes();
        require_once("Vista/VistaNew/" . $pagina . ".php");
    } elseif (is_file("Vista/" . $pagina . ".php")) {
        if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                '9',
                'ACCESAR',
                'El usuario accedió al módulo de Clientes',
                'media'
            );
        }
        $clientes = getclientes();
        require_once("Vista/" . $pagina . ".php");
    } else {
        echo "Página en construcción";
    }
}

ob_end_flush();
?>
