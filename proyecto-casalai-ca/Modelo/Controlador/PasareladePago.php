<?php 
ob_start();
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\PasareladePago;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;

define('MODULO_PASARELA_PAGOS', "Pasarela"); // Define el ID del módulo

// Registrar acceso al módulo si hay sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}
if (!defined('SKIP_SIDE_EFFECTS')) {
    $bitacoraModel = new Bitacora();
    $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        MODULO_PASARELA_PAGOS,
        'ACCESAR',
        'El usuario accedió al módulo de pasarela de pagos',
        'baja'
    );
}
$data = [];
$dolarService = new DolarService();
$precioDolar = $dolarService->obtenerPrecioDolar();
$dolarService->guardarPrecioCache($precioDolar);

// Manejar generación de reportes PDF
try {
    $dolarService = new DolarService();
    $precioDolar = $dolarService->obtenerPrecioDolar();
    $dolarService->guardarPrecioCache($precioDolar);
    
    // Asignar a $data
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $data['monitors'] = [
        'bcv' => [
            'price' => 35.50,
            'updated' => date('Y-m-d H:i:s') . ' (valor por defecto)'
        ]
    ];
    error_log('Error obteniendo precio dólar: ' . $e->getMessage());
}
// --- BLOQUE AJAX ---
// Procesar solo si la petición es AJAX y trae la clave "accion"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'];

    switch ($accion) {
        case 'ingresar':
            if (empty($_POST['pagos'])) {
                echo json_encode(['status' => 'error', 'message' => 'No se han enviado datos de pagos']);
                exit;
            }

            $id_factura = $_POST['id_factura'];
            $pagos = $_POST['pagos'];
            $resultados = [];
            $errores = [];

            // Validar datos de entrada usando las validaciones de registrar pago
            $pasarela = new PasareladePago();
            
            // Validar cada pago individualmente
            foreach ($pagos as $index => $pagoData) {
                $tipoPago = trim((string)($pagoData['tipo'] ?? ''));
                $observaciones = $tipoPago === 'Zelle'
                    ? trim((string)($pagoData['observaciones'] ?? ''))
                    : '';
                $datos_validacion = [
                    'id_factura' => $id_factura,
                    'estatus_pago' => $pagoData['estatus_pago'] ?? null,
                    'observaciones' => $observaciones
                ];
                
                $errores_validacion = $pasarela->validarRegistrarPago($datos_validacion);
                
                if (!empty($errores_validacion)) {
                    $errores[] = "Pago " . ($index + 1) . ": " . implode(', ', $errores_validacion);
                }
                if ($tipoPago === 'Zelle' && $observaciones === '') {
                    $errores[] = "Pago " . ($index + 1) . ": debe indicar el nombre del titular de la cuenta Zelle";
                } elseif (mb_strlen($observaciones, 'UTF-8') > 200) {
                    $errores[] = "Pago " . ($index + 1) . ": el nombre del titular no debe exceder los 200 caracteres";
                }
            }
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de los pagos',
                    'errors' => $errores
                ]);
                exit;
            }

            foreach ($pagos as $index => $pagoData) {
                try {
                    $pasarela = new PasareladePago();
                    $pasarela->setCuenta($pagoData['cuenta']);
                    $pasarela->setReferencia($pagoData['referencia']);
                    $pasarela->setFecha(date('Y-m-d'));
                    $pasarela->setTipo($pagoData['tipo']);
                    $pasarela->setFactura($id_factura);
                    $pasarela->setMonto($pagoData['monto']);
                    $pasarela->setObservaciones(
                        $pagoData['tipo'] === 'Zelle'
                            ? trim((string)($pagoData['observaciones'] ?? ''))
                            : ''
                    );

                    // Validar referencia duplicada
                    if ($pasarela->validarCodigoReferencia()) {
                        $errores[] = "La referencia {$pagoData['referencia']} ya existe";
                        continue;
                    }

                    // Manejo de comprobante
                    $comprobanteNombre = null;
                    if (!empty($_FILES['pagos']['name'][$index]['comprobante'])) {
                        $comprobanteTmp = $_FILES['pagos']['tmp_name'][$index]['comprobante'];
                        $comprobanteOriginal = $_FILES['pagos']['name'][$index]['comprobante'];

                        // Validar comprobante usando las nuevas validaciones
                        $archivoData = [
                            'name' => $comprobanteOriginal,
                            'tmp_name' => $comprobanteTmp,
                            'error' => $_FILES['pagos']['error'][$index]['comprobante'],
                            'size' => $_FILES['pagos']['size'][$index]['comprobante']
                        ];
                        $errores_comprobante = $pasarela->validarComprobante($archivoData);
                        
                        if (!empty($errores_comprobante)) {
                            $errores[] = "Error en comprobante para referencia {$pagoData['referencia']}: " . implode(', ', $errores_comprobante);
                            continue;
                        }

                        // Detectar extensión original de forma segura
                        $extension = strtolower(pathinfo($comprobanteOriginal, PATHINFO_EXTENSION));

                        // Validar que sea una extensión permitida (seguridad)
                        $extensionesPermitidas = PasareladePago::EXTENSIONES_COMPROBANTE;
                        if (!in_array($extension, $extensionesPermitidas)) {
                            $errores[] = "Formato de archivo no permitido para la referencia {$pagoData['referencia']}";
                            continue;
                        }

                        // Generar nombre único con la extensión original
                        $comprobanteNombre = 'comprobante_' . time() . '_' . $index . '.' . $extension;
                        $destino = 'comprobantes/' . $comprobanteNombre;

                        // Crear carpeta si no existe
                        if (!is_dir('comprobantes')) {
                            mkdir('comprobantes', 0755, true);
                        }

                        // Mover archivo
                        if (!move_uploaded_file($comprobanteTmp, $destino)) {
                            $errores[] = "Error al subir el comprobante para la referencia {$pagoData['referencia']}";
                            continue;
                        }

                        $pasarela->setComprobante($comprobanteNombre);
                    }


                    // Guardar pago
                    if ($pasarela->pasarelaTransaccion('Ingresar')) {
                        if (!defined('SKIP_SIDE_EFFECTS')) {
                            $bitacoraModel = new Bitacora();
                            $bitacoraModel->registrarBitacora(
                                $_SESSION['id_usuario'],
                                MODULO_PASARELA_PAGOS,
                                'INGRESAR',
                                'Ingreso de pago con referencia ' . $pagoData['referencia'],
                                'alta'
                            );
                        }
                        // Crear notificación
                        $bd_seguridad = new BD('S');
                        $pdo_seguridad = $bd_seguridad->getConexion();
                        $notificacionModel = new NotificacionModel($pdo_seguridad);
                        $notificacionModel->crear(
                            $_SESSION['id_usuario'],
                            'pago',
                            'Nuevo pago registrado',
                            "Se ha registrado un nuevo pago con referencia " . $pagoData['referencia'] . " por el usuario " . $_SESSION['name'],
                            null,
                            'alta',
                            MODULO_PASARELA_PAGOS,
                            'ingresar'
                        );
                        $resultados[] = [
                            'status' => 'success',
                            'referencia' => $pagoData['referencia'],
                            'comprobante' => $comprobanteNombre
                        ];
                    } else {
                        $errores[] = "Error al ingresar el pago con referencia {$pagoData['referencia']}: fallo en pasarelaTransaccion('Ingresar')";
                        error_log("Error detallado pagoIngresar - Referencia: {$pagoData['referencia']}, Datos: " . json_encode([
                            'cuenta' => $pagoData['cuenta'],
                            'referencia' => $pagoData['referencia'],
                            'tipo' => $pagoData['tipo'],
                            'monto' => $pagoData['monto'],
                            'factura' => $id_factura
                        ]));
                    }
                } catch (Exception $e) {
                    $errores[] = "Error procesando pago {$index}: " . $e->getMessage();
                }
            }

            // Respuesta JSON según resultados
            if (empty($errores)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Los Datos de los pagos se registraron correctamente ahora debe esperar que su pago sea verificado por un administrador',
                    'pagos' => $resultados
                ]);
            } elseif (!empty($resultados)) {
                echo json_encode([
                    'status' => 'partial',
                    'message' => 'Algunos pagos no se pudieron procesar',
                    'pagos_exitosos' => $resultados,
                    'errores' => $errores
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Ningún pago pudo ser procesado',
                    'errores' => $errores
                ]);
            }
            exit; // DETENER EJECUCIÓN DESPUÉS DE RESPONDER JSON
            

        case 'cambiar_estatus':
            if (empty($_POST['id_detalles']) || empty($_POST['estatus'])) {
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para cambiar estatus']);
                exit;
            }

            $id_detalles = $_POST['id_detalles'];
            $nuevo_estatus = $_POST['estatus'];

            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $pasarela = new PasareladePago();
            $datos_validacion = [
                'id_detalles' => $id_detalles,
                'estatus' => $nuevo_estatus
            ];
            $errores = $pasarela->validarCambiarEstatus($datos_validacion);

            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para cambiar estatus',
                    'errors' => $errores
                ]);
                exit;
            }

            $pasarela->setIdDetalles($id_detalles);
            
            if ($pasarela->cambiarEstatus($nuevo_estatus)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_PASARELA_PAGOS,
                        'MODIFICAR',
                        'El usuario cambió el estatus del pago ' . $id_detalles . ' a ' . $nuevo_estatus,
                        'media'
                    );
                }
                echo json_encode(['status' => 'success', 'message' => 'Estatus cambiado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }
}

// --- BLOQUE VISTA ---
// Si no es AJAX, carga la vista normalmente
if (isset($_POST['id_factura'])) {
    $idFactura = $_POST['id_factura'];
    
    // Validar datos de entrada usando las nuevas validaciones centralizadas
    $pasarela = new PasareladePago();
    $datos_validacion = ['id_factura' => $idFactura];
    $errores = $pasarela->validarConsultarPagos($datos_validacion);
    
    if (!empty($errores)) {
        // Mostrar error pero continuar con la vista
        $_SESSION['error_pagos'] = $errores;
    }
    
    $facturaModel = new Factura();
    $cuentaModel = new Cuentabanco();
    $listadocuentas = $cuentaModel->consultarCuentabanco();
    $monto = $facturaModel->obtenerMontoTotalFactura($idFactura);

    $pagina = "PasareladePago";
    if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
        require_once("Vista/VistaNew/" . $pagina . ".php");
    } elseif (is_file("Vista/" . $pagina . ".php")) {
        require_once("Vista/" . $pagina . ".php");
    } else {
        echo "Página en construcción";
    }
} else {
    header("Location: ?pagina=gestionarfactura");
    exit;
}

ob_end_flush();
?>
