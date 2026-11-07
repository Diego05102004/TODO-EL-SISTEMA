<?php
// Requires organizados al inicio
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Modelo\Servicio\RecepcionIAProxy;

require_once dirname(__DIR__) . '/Servicio/RecepcionIAProxy.php';

define('MODULO_RECEPCION', "Recepcion"); // Define el ID del módulo de cuentas bancarias

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

// Permisos: mantener variables compatibles con la vista y añadir consulta específica del módulo
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('recepcion'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'ia_health':
            header('Content-Type: application/json; charset=utf-8');
            $respuestaIA = (new RecepcionIAProxy())->health();
            http_response_code($respuestaIA['http_status']);
            echo json_encode($respuestaIA['data'], JSON_UNESCAPED_UNICODE);
            exit;

        case 'ia_extraer':
            header('Content-Type: application/json; charset=utf-8');
            $recepcionIA= new RecepcionIAProxy();
            $respuestaIA = $recepcionIA->extraer($_FILES['imagen'] ?? []);
            http_response_code($respuestaIA['http_status']);
            echo json_encode($respuestaIA['data'], JSON_UNESCAPED_UNICODE);
            exit;

        case 'ia_verificar':
            header('Content-Type: application/json; charset=utf-8');
            $solicitudIA = json_decode($_POST['solicitud'] ?? '', true);
            if (!is_array($solicitudIA)) {
                http_response_code(400);
                echo json_encode(['detail' => 'La solicitud de verificación no es válida.']);
                exit;
            }
            $recepcionIA= new RecepcionIAProxy();
            $respuestaIA = $recepcionIA->verificar($solicitudIA);
            http_response_code($respuestaIA['http_status']);
            echo json_encode($respuestaIA['data'], JSON_UNESCAPED_UNICODE);
            exit;

        case 'ia_comparar':
            header('Content-Type: application/json; charset=utf-8');
            $respuestaIA = (new RecepcionIAProxy())->comparar($_FILES['imagen'] ?? [], $_POST['datos_json'] ?? '');
            http_response_code($respuestaIA['http_status']);
            echo json_encode($respuestaIA['data'], JSON_UNESCAPED_UNICODE);
            exit;

        case 'listado':
            $k = new Recepcion();
            $respuesta = $k->listadoproductos();
            echo json_encode($respuesta);
        break;
        
        case 'productos_recepcion':
            $id_recepcion = $_POST['id_recepcion'];
            $recepcion = new Recepcion();
            $productos = $recepcion->obtenerProductosPorRecepcion($id_recepcion);
            echo json_encode($productos);
        break;

        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('recepcion'));
            echo json_encode($permisosActualizados);
        break;

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

            $k = new Recepcion();

            // VALIDACIÓN MEJORADA: Verificar datos requeridos
            if (!isset($_POST['proveedor']) || empty($_POST['proveedor'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Debe seleccionar un proveedor',
                    'field' => 'proveedor'
                ]);
                exit;
            }

            if (!isset($_POST['correlativo']) || empty(trim($_POST['correlativo']))) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Debe ingresar el número de factura',
                    'field' => 'correlativo'
                ]);
                exit;
            }

            if (!isset($_POST['producto']) || !is_array($_POST['producto']) || empty($_POST['producto'][0])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Debe agregar al menos un producto',
                    'field' => 'producto'
                ]);
                exit;
            }

            if (!isset($_POST['cantidad']) || !is_array($_POST['cantidad'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Las cantidades son requeridas',
                    'field' => 'cantidad'
                ]);
                exit;
            }

            if (!isset($_POST['costo']) || !is_array($_POST['costo'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Los costos son requeridos',
                    'field' => 'costo'
                ]);
                exit;
            }

            foreach ($_POST['producto'] as $index => $id_producto) {
                if (empty($id_producto)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => "El producto en la fila " . ($index + 1) . " es inválido",
                        'field' => 'producto_' . $index
                    ]);
                    exit;
                }

                $cantidad = floatval($_POST['cantidad'][$index] ?? 0);
                if ($cantidad <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => "La cantidad en la fila " . ($index + 1) . " debe ser mayor a 0",
                        'field' => 'cantidad_' . $index
                    ]);
                    exit;
                }

                $costo = floatval($_POST['costo'][$index] ?? 0);
                if ($costo <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => "El costo en la fila " . ($index + 1) . " debe ser mayor a 0",
                        'field' => 'costo_' . $index
                    ]);
                    exit;
                }
            }

            $porcentajeIva = trim((string)($_POST['porcentaje_iva'] ?? ''));
            if ($porcentajeIva === '') {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Debe ingresar el porcentaje de IVA.',
                    'field' => 'porcentaje_iva'
                ]);
                exit;
            }
            if (!is_numeric($porcentajeIva) || (float)$porcentajeIva < 0 || (float)$porcentajeIva > 100) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El porcentaje de IVA debe estar entre 0 y 100.',
                    'field' => 'porcentaje_iva'
                ]);
                exit;
            }
            $porcentajeIva = (float)$porcentajeIva;

            $modoContingencia = ($_POST['modo_contingencia'] ?? '') === 'true';
            $usoMicroservicio = isset($_POST['usar_ia']) && $_POST['usar_ia'] === 'true';

            // Capturar datos de predicción de la IA si se usó el microservicio
            $prediccionIA = null;
            if (isset($_POST['ia_prediccion'])) {
                $prediccionIA = json_decode($_POST['ia_prediccion'], true);
            }

            if ($usoMicroservicio && !$modoContingencia) {
                if (!isset($_POST['ia_verificada']) || $_POST['ia_verificada'] !== 'true') {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'La verificación con IA no fue completada. Corrija las discrepancias o use el modo manual.',
                        'field' => 'ia_verificacion'
                    ]);
                    exit;
                }
            }

            // Validación adicional usando las validaciones existentes
            $datos_validacion = [
                'idproveedor' => $_POST['proveedor'],
                'correlativo' => $_POST['correlativo'],
                'estado' => 'habilitado'
            ];

            $errores = $k->validarRegistrar($datos_validacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de la recepción',
                    'errors' => $errores
                ]);
                exit;
            }

            $rutaFacturaResguardada = null;
            $rutaManifiestoFactura = null;
            $manifiestoFactura = null;
            if (isset($_FILES['factura_contingencia']) && $_FILES['factura_contingencia']['error'] !== UPLOAD_ERR_NO_FILE) {
                $archivoFactura = $_FILES['factura_contingencia'];
                if ($archivoFactura['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'No se pudo recibir la factura adjunta. Verifique el límite de carga del servidor.']);
                    exit;
                }
                if ($archivoFactura['size'] > 5 * 1024 * 1024) {
                    http_response_code(413);
                    echo json_encode(['status' => 'error', 'message' => 'La factura adjunta debe ser menor a 5 MB.']);
                    exit;
                }

                $mimeFactura = (new finfo(FILEINFO_MIME_TYPE))->file($archivoFactura['tmp_name']);
                $extensionesFactura = [
                    'application/pdf' => 'pdf',
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/webp' => 'webp',
                    'image/bmp' => 'bmp',
                    'image/tiff' => 'tif'
                ];
                if (!isset($extensionesFactura[$mimeFactura])) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'La factura debe ser un PDF o una imagen compatible.']);
                    exit;
                }

                $directorioFacturas = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'comprobantes' . DIRECTORY_SEPARATOR . 'recepcion';
                if (!is_dir($directorioFacturas) && !mkdir($directorioFacturas, 0750, true) && !is_dir($directorioFacturas)) {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'No se pudo preparar el resguardo privado de facturas.']);
                    exit;
                }

                $correlativoArchivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $_POST['correlativo']);
                $nombreArchivoFactura = sprintf(
                    '%d_%s_%s_%s.%s',
                    (int)$_POST['proveedor'],
                    $correlativoArchivo,
                    date('YmdHis'),
                    bin2hex(random_bytes(6)),
                    $extensionesFactura[$mimeFactura]
                );
                $rutaFacturaResguardada = $directorioFacturas . DIRECTORY_SEPARATOR . $nombreArchivoFactura;
                $rutaManifiestoFactura = $rutaFacturaResguardada . '.json';

                if (!move_uploaded_file($archivoFactura['tmp_name'], $rutaFacturaResguardada)) {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar la copia privada de la factura.']);
                    exit;
                }
                if (DIRECTORY_SEPARATOR !== '\\') {
                    chmod($rutaFacturaResguardada, 0640);
                }

                $manifiestoFactura = [
                    'correlativo' => trim($_POST['correlativo']),
                    'proveedor_id' => (int)$_POST['proveedor'],
                    'usuario_id' => (int)($_SESSION['id_usuario'] ?? 0),
                    'archivo' => $nombreArchivoFactura,
                    'estado' => ($_POST['modo_contingencia'] ?? '') === 'true' ? 'pendiente_ocr' : 'resguardada',
                    'subido_en' => date(DATE_ATOM),
                    'sha256' => hash_file('sha256', $rutaFacturaResguardada)
                ];
                $jsonManifiesto = json_encode($manifiestoFactura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                if ($jsonManifiesto === false || file_put_contents($rutaManifiestoFactura, $jsonManifiesto, LOCK_EX) === false) {
                    unlink($rutaFacturaResguardada);
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar el manifiesto de la factura.']);
                    exit;
                }
            }

            $productos_data = [
                'idproducto' => $_POST['producto'],
                'cantidad' => $_POST['cantidad'],
                'costo' => $_POST['costo']
            ];

            $k->setidproveedor($_POST['proveedor']);
            $k->setcorrelativo($_POST['correlativo']);
            $k->setestado('habilitado');

            if ($_POST['correlativo'] != ''){
                if ($k->existeCorrelativo(['correlativo' => $_POST['correlativo']])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El número de factura ya existe'
                    ]);
                    exit;
                }
            }
            
            try {
                if ($k->registrarRecepcion(
                    $_POST['producto'],
                    $_POST['cantidad'],
                    $_POST['costo'],
                    $porcentajeIva, 
                    $id_usuario_sesion)) {

                    $recepcionRegistrada = $k->obtenerUltimaRecepcion();
                    $resultado = $recepcionRegistrada !== null;
                } else {
                    $recepcionRegistrada = null;
                    $resultado = false;
                }
            } catch (Throwable $e) {
                error_log('Error al registrar recepción mediante SP: ' . $e->getMessage());
                $recepcionRegistrada = null;
                $resultado = false;
            }

            if ($resultado && $recepcionRegistrada) {
                if ($manifiestoFactura !== null) {
                    $manifiestoFactura['estado'] = ($_POST['modo_contingencia'] ?? '') === 'true' ? 'pendiente_ocr' : 'recepcion_registrada';
                    $manifiestoFactura['id_recepcion'] = (int)$recepcionRegistrada['id_recepcion'];
                    file_put_contents(
                        $rutaManifiestoFactura,
                        json_encode($manifiestoFactura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        LOCK_EX
                    );
                }
                $id_recepcion = $recepcionRegistrada['id_recepcion'];

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    try {
                        $bd_seguridad = new BD('S');
                        $pdo_seguridad = $bd_seguridad->getConexion();
                        $notificacionModel = new NotificacionModel($pdo_seguridad);
                        $notificacionModel->crear(
                            $_SESSION['id_usuario'],
                            'recepcion',
                            'Nueva Recepción Registrada',
                            "Se ha registrado una nueva recepción #".$_POST['correlativo']." con ".array_sum($_POST['cantidad'])." unidades por el usuario ".$_SESSION['name'],
                            'media',
                            MODULO_RECEPCION,
                            'ingresar',
                            $id_recepcion
                        );
                    } catch (Throwable $e) {
                        error_log('No se pudo crear la notificación de recepción registrada: ' . $e->getMessage());
                    }
                }

                try {
                    // Guardar feedback dataset para aprendizaje del microservicio
                    if ($usoMicroservicio && $prediccionIA !== null) {
                        $productosRegistrados = $k->obtenerProductosPorRecepcion($recepcionRegistrada['id_recepcion']);
                        
                        $subtotalFacturaCalc = 0;
                        if (isset($_POST['cantidad'], $_POST['costo']) && is_array($_POST['cantidad'])) {
                            foreach ($_POST['cantidad'] as $i => $cant) {
                                $subtotalFacturaCalc += (floatval($cant) * floatval($_POST['costo'][$i] ?? 0));
                            }
                        }
                        $montoIvaCalc = $subtotalFacturaCalc * ($porcentajeIva / 100);
                        $totalFacturaCalc = $subtotalFacturaCalc + $montoIvaCalc;

                        $productosGt = [];
                        foreach ($productosRegistrados as $prod) {
                            $productosGt[] = [
                                'nombre' => $prod['nombre_producto'] ?? $prod['descripcion'] ?? '',
                                'cantidad' => intval($prod['cantidad'] ?? 0),
                                'costo' => floatval($prod['costo'] ?? 0)
                            ];
                        }

                        $datosFeedback = [
                            'id_recepcion' => $recepcionRegistrada['id_recepcion'],
                            'id_factura' => $_POST['correlativo'] ?? '',
                            'prediccion_proveedor' => $prediccionIA['nombre_proveedor'] ?? '',
                            'ground_truth_proveedor' => $recepcionRegistrada['nombre_proveedor'] ?? '',
                            'prediccion_correlativo' => $prediccionIA['numero_factura'] ?? '',
                            'ground_truth_correlativo' => $_POST['correlativo'] ?? '',
                            'prediccion_productos' => $prediccionIA['productos'] ?? [],
                            'ground_truth_productos' => $productosGt,
                            'prediccion_subtotal' => $prediccionIA['subtotal_factura'] ?? 0,
                            'ground_truth_subtotal' => $subtotalFacturaCalc,
                            'prediccion_total' => $prediccionIA['total_factura'] ?? 0,
                            'ground_truth_total' => $totalFacturaCalc,
                            'discrepancias_corregidas' => $prediccionIA['discrepancias'] ?? [],
                            'confianza_ocr_promedio' => $prediccionIA['confianza_promedio'] ?? 0
                        ];

                        $resultadoFeedback = $k->agregarFeedbackDataset($datosFeedback);
                        
                        if (!$resultadoFeedback) {
                            error_log('ADVERTENCIA: agregarFeedbackDataset retornó falso al intentar escribir en feedback_dataset.csv');
                        }
                    }
                } catch (Throwable $e) {
                    error_log('Error crítico al actualizar datasets de entrenamiento: ' . $e->getMessage() . ' en línea ' . $e->getLine());
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Recepción Registrada Correctamente',
                    'factura_resguardada' => $rutaFacturaResguardada !== null,
                    'procesamiento_pendiente' => ($_POST['modo_contingencia'] ?? '') === 'true',
                    'recepcion' => $recepcionRegistrada
                ]);
            } else {
                if ($rutaFacturaResguardada !== null && is_file($rutaFacturaResguardada)) unlink($rutaFacturaResguardada);
                if ($rutaManifiestoFactura !== null && is_file($rutaManifiestoFactura)) unlink($rutaManifiestoFactura);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la recepción'
                ]);
            }
            break;

        case 'obtener_recepcion':
        case 'obtener_detalle_recepcion':
            header('Content-Type: application/json; charset=utf-8');
            $correlativo = $_POST['correlativo'] ?? null;
            $k = new Recepcion();
            $k->setcorrelativo($correlativo);
            $respuesta = $accion === 'obtener_detalle_recepcion'
                ? $k->obtenerDetalle()
                : $k->buscar();
            
            if (!$respuesta || ($respuesta['resultado'] ?? '') !== 'encontró') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró el correlativo: ' . $correlativo
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // Obtener productos de la recepción
                $id_recepcion = $accion === 'obtener_detalle_recepcion'
                    ? (int)($respuesta['id_recepcion'] ?? 0)
                    : $k->obtenerIdRecepcionPorCorrelativo($correlativo);
                $productos = $k->obtenerProductosPorRecepcion($id_recepcion);
                $respuesta['productos'] = $productos;
                
                echo json_encode([
                    'status' => 'success',
                    'recepcion' => $respuesta,
                    'productos' => $productos
                ], JSON_UNESCAPED_UNICODE);
            }
        break;

        case 'buscar':
            $k = new Recepcion();
            $correlativo = $_POST['correlativo'] ?? null;
            $k->setcorrelativo($correlativo);
            $respuesta = $k->buscar();
            if (!$respuesta) {
                echo json_encode([
                    "resultado" => "no_encontro",
                    "mensaje" => "No se encontró el correlativo: " . $correlativo
                ]);
            } else {
                echo json_encode($respuesta);
            }
            break;
        
        case 'anular':
            header('Content-Type: application/json; charset=utf-8');
            $k = new Recepcion();
            $correlativo = $_POST['correlativo'] ?? '';
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
                
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }
            
            $datos_validacion = ['correlativo' => $correlativo];
            $errores = $k->validarAnular($correlativo);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para anular la recepción',
                    'errors' => $errores
                ]);
                exit;
            }
            
            // Verificar que la recepción exista antes de anular
            $k->setcorrelativo($correlativo);
            $recepcionExistente = $k->buscar();
            if (!$recepcionExistente || $recepcionExistente['resultado'] !== 'encontró') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La recepción no existe o ya fue anulada'
                ]);
                exit;
            }
            
            // Obtener id_recepcion antes de anular para eliminar del feedback
            $id_recepcion = $k->obtenerIdRecepcionPorCorrelativo($correlativo);
            
            $resultado = $k->anularRecepcion($correlativo, $id_usuario_sesion);

            if (($resultado['status'] ?? '') === 'success') {
                // Eliminar registro del feedback_dataset.csv para evitar contaminación del entrenamiento
                try {
                    $k->eliminarFeedbackDataset($id_recepcion);
                } catch (Throwable $e) {
                    error_log('No se pudo eliminar el registro del feedback_dataset: ' . $e->getMessage());
                }
                
                // Obtener id_recepcion para referenciar en notificación
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    try {
                        $bd_seguridad = new BD('S');
                        $pdo_seguridad = $bd_seguridad->getConexion();
                        $notificacionModel = new NotificacionModel($pdo_seguridad);
                        $notificacionModel->crear(
                            $_SESSION['id_usuario'],
                            'recepcion',
                            'Recepción Anulada',
                            "Se ha anulado la recepción #".$correlativo." por parte del usuario ".($_SESSION['name'] ?? ''),
                            'media',
                            MODULO_RECEPCION,
                            'eliminar',
                            $id_recepcion
                        );
                    } catch (Throwable $e) {
                        error_log('No se pudo crear la notificación de recepción anulada: ' . $e->getMessage());
                    }
                }
            }
            echo json_encode($resultado);
        break;

        case 'reportes_recepcion':
            header('Content-Type: application/json; charset=utf-8');
            $k = new Recepcion();
            // Parámetros opcionales
            $fechaInicio = $_POST['fechaInicio'] ?? null;
            $fechaFin    = $_POST['fechaFin'] ?? null;
            $anio        = $_POST['anio'] ?? null;
            $proveedorId = $_POST['proveedorId'] ?? null;

            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = [
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'anio' => $anio,
                'proveedorId' => $proveedorId
            ];
            $errores = $k->validarReporte($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para generar el reporte',
                    'errors' => $errores
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                $resp = [
                    'proveedores' => $k->getRecepcionesPorProveedor($fechaInicio, $fechaFin),
                    'productos'   => $k->getProductosMasRecibidos($fechaInicio, $fechaFin, $proveedorId),
                    'mensual'     => $k->getRecepcionesMensuales($anio)
                ];
                echo json_encode(['status' => 'success', 'data' => $resp], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida '.$accion.'']);
    }
    exit;
}

function getrecepcion() {
    $recepcion = new Recepcion();
    return $recepcion->getrecepcion(); // Consulta resumen: fecha, correlativo, proveedor, tamaño, costo inversión
}

function getproductos() {
    $recepcion = new Recepcion();
    return $recepcion->listadoproductos();
}

$r = new Recepcion();
$RecepcionesProveedor = $r->getRecepcionesPorProveedor();
$ProductosRecibidos = $r->getProductosMasRecibidos();
$RecepcionMensual = $r->getRecepcionesMensuales();

$proveedores = (new Recepcion())->obtenerproveedor();
$productos = (new Recepcion())->consultarproductos();
$pagina = "recepcion";

// Verificar si se solicita la vista de reporte
if (isset($_GET['pagina']) && $_GET['pagina'] === 'reporteRecepcion') {
    $pagina = "reporteRecepcion";
}

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Recepcion',
                'ACCESAR',
                'El usuario accedió al módulo de Recepcion',
                'media'
            );
        }
    }
    $recepciones = getrecepcion();
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Recepcion',
                'ACCESAR',
                'El usuario accedió al módulo de Recepcion',
                'media'
            );
        }
    }
    $recepciones = getrecepcion();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>