<?php
ob_start();

// Requires
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;

// Constantes de módulo
define('MODULO_CUENTA_BANCARIA', "Cuentas bancarias");

// Inicializaciones de clases compartidas
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();
$id_rol = $_SESSION['id_rol'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('Cuentas bancarias'));
            echo json_encode($permisosActualizados);
            exit;
        
        case 'registrar':
            header('Content-Type: application/json; charset=utf-8');
            error_log("[CUENTA-CONTROLADOR] Iniciando caso 'registrar'");
            error_log("[CUENTA-CONTROLADOR] POST recibido: " . json_encode($_POST));
            
            // Capturamos con seguridad el ID del usuario logueado en la sesión
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $cuentabanco = new Cuentabanco();
            $cuentabanco->setNombreBanco($_POST['nombre_banco']);
            $cuentabanco->setNumeroCuenta($_POST['numero_cuenta']);
            $cuentabanco->setRifCuenta($_POST['rif_cuenta']);
            $cuentabanco->setTelefonoCuenta($_POST['telefono_cuenta']);
            $cuentabanco->setCorreoCuenta($_POST['correo_cuenta']);
            $cuentabanco->setMetodosPago($_POST['metodos_pago'] ?? []);
            
            // Validar datos de entrada
            $datosValidacion = [
                'nombre_banco' => $_POST['nombre_banco'] ?? '',
                'numero_cuenta' => $_POST['numero_cuenta'] ?? '',
                'rif_cuenta' => $_POST['rif_cuenta'] ?? '',
                'telefono_cuenta' => $_POST['telefono_cuenta'] ?? '',
                'correo_cuenta' => $_POST['correo_cuenta'] ?? '',
                'metodos_pago' => $_POST['metodos_pago'] ?? []
            ];
            
            error_log("[CUENTA-CONTROLADOR] Datos a validar: " . json_encode($datosValidacion));
            
            $errores = $cuentabanco->validarRegistrarCuenta($datosValidacion);
            error_log("[CUENTA-CONTROLADOR] Errores de validación: " . json_encode($errores));
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Errores de validación',
                    'errors' => $errores
                ]);
                exit;
            }
            
            if ($_POST['numero_cuenta'] != ''){
                if ($cuentabanco->existeNumeroCuenta($_POST['numero_cuenta'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El número de cuenta ya existe'
                    ]);
                    exit;
                }
            }

            // MODIFICACIÓN AQUÍ: Pasamos el usuario de la sesión al modelo
            if ($cuentabanco->registrarCuentabanco($id_usuario_sesion)) {
                
                $cuentaRegistrada = $cuentabanco->obtenerUltimaCuenta();
                
                // NOTA: Ya no llamamos a $bitacoraModel->registrarBitacora() desde aquí.
                // El procedimiento 'sp_registrar_cuenta' ya guardó los datos estructurados en la bitácora.

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Cuenta registrada correctamente',
                    'cuenta' => $cuentaRegistrada
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la cuenta'
                ]);
            }
            exit;

        case 'obtener_cuenta':
            $id_cuenta = $_POST['id_cuenta'] ?? null;

            if ($id_cuenta === null || !ctype_digit((string)$id_cuenta)) {
                echo json_encode(['status' => 'error', 'message' => 'ID de cuenta no válido']);
                exit;
            }

            $cuentabanco = new Cuentabanco();
            $cuenta_obt = $cuentabanco->obtenerCuentaPorId($id_cuenta);

            // ADAPTACIÓN: PDO::fetch devuelve false (no null) si el registro no existe en tbl_cuentas
            if ($cuenta_obt !== false && !empty($cuenta_obt)) {
                echo json_encode($cuenta_obt);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cuenta no encontrada']);
            }
            exit;
        
        case 'consultar_cuentas':
            $cuentabanco = new Cuentabanco();
            $cuentas_obt = $cuentabanco->consultarCuentabanco();

            echo json_encode($cuentas_obt);
            exit;
        
        case 'modificar':
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            
            $id_cuenta = $_POST['id_cuenta'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            if ($id_cuenta === null || !ctype_digit((string)$id_cuenta) || !$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID de cuenta o sesión de usuario no válida'
                ]);
                exit;
            }
            
            $cuentabanco = new Cuentabanco();
            $cuentabanco->setIdCuenta($id_cuenta);
            $cuentabanco->setNombreBanco($_POST['nombre_banco']);
            $cuentabanco->setNumeroCuenta($_POST['numero_cuenta']);
            $cuentabanco->setRifCuenta($_POST['rif_cuenta']);
            $cuentabanco->setTelefonoCuenta($_POST['telefono_cuenta']);
            $cuentabanco->setCorreoCuenta($_POST['correo_cuenta']);
            
            $metodos_procesados = is_array($_POST['metodos_pago']) ? implode(',', $_POST['metodos_pago']) : ($_POST['metodos_pago'] ?? '');
            $cuentabanco->setMetodosPago($metodos_procesados);
            
            $datosValidacion = [
                'id_cuenta'  => $id_cuenta,
                'nombre_banco' => $_POST['nombre_banco'] ?? '',
                'numero_cuenta' => $_POST['numero_cuenta'] ?? '',
                'rif_cuenta' => $_POST['rif_cuenta'] ?? '',
                'telefono_cuenta' => $_POST['telefono_cuenta'] ?? '',
                'correo_cuenta' => $_POST['correo_cuenta'] ?? '',
                'metodos_pago' => $_POST['metodos_pago'] ?? []
            ];
            
            $errores = $cuentabanco->validarModificarCuenta($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Errores de validación',
                    'errors' => $errores
                ]);
                exit;
            }
            if ($cuentabanco->existeNumeroCuenta($_POST['numero_cuenta'], $id_cuenta)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El número de cuenta ya existe'
                ]);
                exit;
            }

            // MODIFICACIÓN: Pasamos el ID de la sesión al método
            if ($cuentabanco->modificarCuentabanco($id_cuenta, $id_usuario_sesion)) {
                $cuentabancoActualizada = $cuentabanco->obtenerCuentaPorId($id_cuenta);
                
                // ELIMINADO: Ya no llamamos a $bitacoraModel->registrarBitacora desde PHP.

                echo json_encode([
                    'status' => 'success',
                    'cuenta' => $cuentabancoActualizada
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar la cuenta']);
            }
            exit;

        case 'eliminar':
            $id_cuenta = $_POST['id_cuenta'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            if ($id_cuenta === null || !ctype_digit((string)$id_cuenta) || !$id_usuario_sesion) {
                echo json_encode(['status' => 'error', 'message' => 'Petición no válida']);
                exit;
            }
            
            $cuentabanco = new Cuentabanco();

            $datos_validacion = ['id_cuenta' => $id_cuenta];
            $errores = $cuentabanco->validarEliminarCuenta($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para eliminar la cuenta bancaria',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $resultado = $cuentabanco->eliminarCuentabanco($id_cuenta, $id_usuario_sesion);

            if (is_array($resultado) && $resultado['status'] === 'error') {
                // MANTENEMOS la bitácora manual SÓLO si la eliminación falló preventivamente en PHP
                if (!defined('SKIP_SIDE_EFFECTS') && ($resultado['type'] ?? '') === 'business_rule') {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $id_usuario_sesion,
                        MODULO_CUENTA_BANCARIA,
                        'ELIMINAR_FALLIDO',
                        'Intento de eliminación fallido de cuenta (ID: ' . $id_cuenta . '): ' . ($resultado['message'] ?? ''),
                        'media'
                    );
                }
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['message'],
                    'pagos' => $resultado['pagos'] ?? [],
                    'total_pagos' => $resultado['total_pagos'] ?? 0
                ]);
            } else if ($resultado['status'] === 'success') {
                // ELIMINADO: El registro 'ELIMINAR' exitoso ya lo hizo la base de datos de manera síncrona.
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error inesperado al procesar la solicitud']);
            }
            exit;

        case 'cambiar_estado':
            $id_cuenta = $_POST['id_cuenta'];
            $nuevoEstado = $_POST['estado'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if ($id_cuenta === null || !ctype_digit((string)$id_cuenta) || !$id_usuario_sesion) {
                echo json_encode(['status' => 'error', 'message' => 'Datos de petición o sesión no válidos']);
                exit;
            }
            
            if (!in_array($nuevoEstado, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estado no válido']);
                exit;
            }
            
            $cuentabanco = new Cuentabanco();
            $cuentabanco->setIdCuenta($id_cuenta);

            $datosValidacion = [
                'id_cuenta' => $id_cuenta,
                'estado' => $nuevoEstado
            ];

            $errores = $cuentabanco->validarCambiarEstado($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }
            
            // MODIFICACIÓN: Enviamos el ID del usuario al método
            if ($cuentabanco->cambiarEstado($nuevoEstado, $id_usuario_sesion)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estado']);
            }
            exit;
    }
}

function consultarCuentabanco() {
    $cuentabanco = new Cuentabanco();
    return $cuentabanco->consultarCuentabanco();
}

function cuentasReportes() {
    $cuentabanco = new Cuentabanco();
    return $cuentabanco->cuentasReportes();
}

$pagina = "cuenta";
// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'MODULO_CUENTA_BANCARIA',
                'ACCESAR',
                'El usuario accedió al módulo de Cuentas Bancarias',
                'media'
            );
        }
    }
    $cuentabancos = consultarCuentabanco();
    $cuentasReportes = cuentasReportes();
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'MODULO_CUENTA_BANCARIA',
                'ACCESAR',
                'El usuario accedió al módulo de Cuentas Bancarias',
                'media'
            );
        }
    }
    $cuentabancos = consultarCuentabanco();
    $cuentasReportes = cuentasReportes();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>