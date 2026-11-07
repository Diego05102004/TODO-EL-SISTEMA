<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\PasareladePago;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Config\BD;

define('MODULO_PASARELA_PAGOS', "Pasarela"); // Define el ID
$bitacoraModel = new Bitacora();
$id_rol = $_SESSION['id_rol'];

$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

$pasarela = new PasareladePago();
$cuentaModel = new Cuentabanco();
$listadocuentas = $cuentaModel->consultarCuentabanco();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, 'Pasarela de pagos');
            echo json_encode($permisosActualizados);
            exit;
        case 'ingresar':
            if (!isset($_POST['pagos']) || empty($_POST['pagos'])) {
                echo json_encode(['status' => 'error', 'message' => 'No se han enviado datos de pagos']);
                exit;
            }
            $id_factura = $_POST['id_factura'];
            $pagos = $_POST['pagos'];
            $resultados = [];
            $errores = [];
            foreach ($pagos as $index => $pagoData) {
                try {
                    $pasarela = new PasareladePago();
                    $pasarela->setCuenta($pagoData['cuenta']);
                    $pasarela->setReferencia($pagoData['referencia']);
                    $pasarela->setFecha(date('Y-m-d'));
                    $pasarela->setTipo($pagoData['tipo']);
                    $pasarela->setFactura($id_factura);
                    $pasarela->setMonto($pagoData['monto']);
                    $pasarela->setObservaciones('');
                    if (!$pasarela->validarCodigoReferencia()) {
                        $errores[] = "La referencia {$pagoData['referencia']} ya existe";
                        continue;
                    }
                    $comprobanteNombre = null;
                    if (isset($_FILES['pagos']['name'][$index]['comprobante']) && $_FILES['pagos']['name'][$index]['comprobante']) {
                        $comprobante = $_FILES['pagos']['tmp_name'][$index]['comprobante'];
                        $comprobanteNombre = 'comprobante_' . time() . '_' . $index . '.jpg';
                        $destino = 'comprobantes/' . $comprobanteNombre;
                        if (!is_dir('comprobantes')) { mkdir('comprobantes', 0755, true); }
                        if (!move_uploaded_file($comprobante, $destino)) {
                            $errores[] = "Error al subir el comprobante para la referencia {$pagoData['referencia']}";
                            continue;
                        }
                        $pasarela->setComprobante($comprobanteNombre);
                    }
                    if ($pasarela->pasarelaTransaccion('Ingresar')) {
                        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PASARELA_PAGOS, 'INGRESAR', 'El usuario incluyó la referencia bancaria: ' . $pagoData['referencia'], 'media');
                        $resultados[] = ['status' => 'success', 'referencia' => $pagoData['referencia'], 'comprobante' => $comprobanteNombre];
                    } else {
                        $errores[] = "Error al ingresar el pago con referencia {$pagoData['referencia']}";
                    }
                } catch (Exception $e) {
                    $errores[] = "Error procesando pago {$index}: " . $e->getMessage();
                }
            }

            if (empty($errores)) {
                echo json_encode(['status' => 'success', 'message' => 'Todos los pagos se registraron correctamente', 'pagos' => $resultados]);
            } else {
                echo json_encode(['status' => 'partial', 'message' => 'Algunos pagos no se pudieron procesar', 'pagos_exitosos' => $resultados, 'errores' => $errores]);
            }
            exit;
        case 'modificar':
            $id = $_POST['id_detalles'];
            $pasarela->setIdDetalles($id);
            $pasarela->setReferencia($_POST['referencia']);
            $pasarela->setFecha($_POST['fecha']);
            $pasarela->setTipo($_POST['tipo']);
            $pasarela->setFactura($_POST['id_factura']);
            $pasarela->setCuenta($_POST['cuenta']);
            $pasarela->setMonto($_POST['monto']);
            if ($pasarela->pasarelaTransaccion('Modificar')) {
                $pagoActualizado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PASARELA_PAGOS, 'MODIFICAR', 'El usuario modificó la referencia bancaria: ' . $pagoActualizado['referencia'], 'media');
                echo json_encode(['status' => 'success', 'pago' => $pagoActualizado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el producto']);
            }
            break;
        case 'modificar_estado':
            $id = $_POST['id_detalles'];
            $nuevoEstatus = $_POST['estatus'];
            $factura = $_POST['id_factura'];
            $pasarela->setIdDetalles($id);
            $pasarela->setEstatus($nuevoEstatus);
            $pasarela->setFactura($factura);
            if ($pasarela->pasarelaTransaccion('Procesar')) {
                $pagoActualizado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PASARELA_PAGOS, 'MODIFICAR', 'El usuario cambió el estatus del pago de la referencia bancaria: ' . $pagoActualizado['referencia'] . ' a ' . $nuevoEstatus, 'media');
                echo json_encode(['status' => 'success', 'pago' => $pagoActualizado]);
                $bd_seguridad = new BD('S');
                $pdo_seguridad = $bd_seguridad->getConexion();
                $notificacionesModel = new NotificacionModel($pdo_seguridad);
                $notificacionesModel->crear($_SESSION['id_usuario'], 'pago', 'Estatus de pago actualizado', "El estatus del pago con referencia " . $pagoActualizado['referencia'] . " ha sido cambiado a " . $nuevoEstatus . " por el usuario " . $_SESSION['name'], null, 'media', MODULO_PASARELA_PAGOS, 'modificar_estado');
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            break;

        case 'eliminar':
            $pasarela = new PasareladePago();
            $id = $_POST['id_detalles'];
            $pasarela->setIdDetalles($id);
            if ($pasarela->pasarelaTransaccion('Eliminar')) {
                $pagoEliminado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PASARELA_PAGOS, 'ELIMINAR', 'El usuario eliminó la referencia bancaria: ' . ($pagoEliminado ? $pagoEliminado['referencia'] : 'ID ' . $id), 'media');
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el producto']);
            }
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
}

if ($_SESSION['nombre_rol'] != 'Cliente') {
    $datos = $pasarela->pasarelaTransaccion('ConsultarTodos');
} else {
    $pasarela->setCedula($_SESSION['cedula']);
    $datos = $pasarela->pasarelaTransaccion('Consultar');
}

$pagina = "pasarela";
// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], 'Pasarela', 'ACCESAR', 'El usuario accedió al módulo de Pagos', 'media');
    }
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/VistaNew/pasarela.php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], 'Pasarela', 'ACCESAR', 'El usuario accedió al módulo de Pagos', 'media');
    }
    require_once("Vista/VistaNew/pasarela.php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
ob_end_flush();
?>