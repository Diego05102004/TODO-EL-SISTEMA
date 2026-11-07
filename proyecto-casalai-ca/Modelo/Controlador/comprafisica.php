<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Forzar logging para depuración
error_log("[COMPRFISICA-CONTROLADOR] Inicio del controlador - " . date('Y-m-d H:i:s'));

// Página y dependencias
$pagina = 'comprafisica';
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Comprafisica;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Finanza;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Config\BD;
// Constante de módulo
define('MODULO_DESPACHO', "Despacho");

// Inicializaciones
$k = new Comprafisica();
$data = [];

// Evitar llamadas HTTP lentas al BCV en cada request; usar cache si existe
$dolarService = new DolarService();
$precioDolar = 35.50;
try {
    $bdCache = new BD('P');
    $pdoCache = $bdCache->getConexion();
    $stmtCache = $pdoCache->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
    $stmtCache->execute();
    $cache = $stmtCache->fetch(PDO::FETCH_ASSOC);
    if ($cache && isset($cache['precio'], $cache['fecha']) && (time() - strtotime($cache['fecha'])) < 86400) {
        $precioDolar = (float) $cache['precio'];
    } else {
        $precioDolar = $dolarService->obtenerPrecioDolar();
        $dolarService->guardarPrecioCache($precioDolar);
    }
} catch (Exception $e) {
    // Si falla el cache o la consulta, usar el método existente (que ya hace fallback)
    $precioDolar = $dolarService->obtenerPrecioDolar();
}

// Manejar generación de reportes PDF
try {
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
} catch (Exception $e) {
    $data['monitors'] = [
        'bcv' => [
            'price' => 35.50,
            'updated' => date('Y-m-d H:i:s') . ' (valor por defecto)'
        ]
    ];
    error_log('Error asignando precio dólar: ' . $e->getMessage());
}
$id_rol = $_SESSION['id_rol'] ?? 0; // valor por defecto seguro

$cuentaModel = new Cuentabanco();
$despacho = new OrdenDespacho();
$facturaModel = new Factura();

// Usar un nombre de módulo consistente sin acentos para evitar desajustes con la BD
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('compra fisica'));

if (is_file("Vista/" . $pagina . ".php")) {
    $accion = $_POST['accion'] ?? '';

    function getdespacho()
    {
        $despacho = new Comprafisica();
        return $despacho->getdespacho();
    }

    function parsearCantidadFormateada($cantidadFormateada) {
        if (is_numeric($cantidadFormateada)) {
            return floatval($cantidadFormateada);
        }
        
        $cantidadLimpia = str_replace('.', '', $cantidadFormateada);
        $cantidadLimpia = str_replace(',', '.', $cantidadLimpia);
        
        return floatval($cantidadLimpia);
    }

    if (!empty($_POST)) {
        error_log("[COMPRFISICA-CONTROLADOR] POST recibido - Acción: $accion");
        switch ($accion) {
            case 'listado':
                $respuesta = $k->listadoproductos();
                echo json_encode($respuesta);
                break;

            case 'registrar':
                // Forzar salida limpia y JSON
                if (ob_get_length()) {
                    ob_clean();
                }
                header('Content-Type: application/json; charset=utf-8');

                // Logging para depuración
                error_log("[COMPRFISICA] Iniciando caso 'registrar'");
                error_log("[COMPRFISICA] POST data: " . json_encode($_POST));
                
                $idCliente = $_POST['cliente'] ?? null;
                $productos = $_POST['producto'] ?? [];
                $cantidades = $_POST['cantidad'] ?? [];
                $pagos = $_POST['pagos'] ?? [];
                $montoTotal = $_POST['monto_total'] ?? 0;
                $cambio = $_POST['cambio_efectivo'] ?? 0;
                
                error_log("[COMPRFISICA] Datos procesados - Cliente: $idCliente, Productos: " . json_encode($productos));
                error_log("[COMPRFISICA] Cantidades: " . json_encode($cantidades));
                error_log("[COMPRFISICA] Pagos: " . json_encode($pagos));

                // Preparar productos
                $detalleProductos = [];
                foreach ($productos as $i => $prod) {
                    $cantidadFormateada = $cantidades[$i] ?? '0';
                    $cantidadNumerica = parsearCantidadFormateada($cantidadFormateada);
                    $detalleProductos[] = [
                        'id_producto' => $prod,
                        'cantidad' => $cantidadNumerica
                    ];
                }

                // Preparar pagos
                $detallePagos = [];
                foreach ($pagos as $idx => $pago) {
                    $detalle = [
                        'tipo' => $pago['tipo'] ?? '',
                        'cuenta' => $pago['cuenta'] ?? '',
                        'referencia' => $pago['referencia'] ?? '',
                        'fecha' => date("Y-m-d H:i:s"),
                        'monto' => str_replace(',', '.', (string) ($pago['monto'] ?? 0)),
                        'monto_dolar' => $pago['monto_dolar'] ?? null
                    ];

                    // Subida de comprobante (si existe)
                    if (!empty($_FILES['pagos']['name'][$idx]['comprobante'])) {
                        $tmpName = $_FILES['pagos']['tmp_name'][$idx]['comprobante'];
                        $fileName = time() . '_' . basename($_FILES['pagos']['name'][$idx]['comprobante']);
                        $uploadDir = "assets/img/uploads/comprobantes/";
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        move_uploaded_file($tmpName, $uploadDir . $fileName);
                        $detalle['comprobante'] = $uploadDir . $fileName;
                    } else {
                        // asignar imágenes por defecto segun tipo
                        if ($detalle['tipo'] === 'Efectivo') {
                            $detalle['comprobante'] = 'assets/img/uploads/comprobantes/bolivar.png';
                            $detalle['cuenta'] = 0;
                        } elseif ($detalle['tipo'] === 'Efectivo en $') {
                            $detalle['comprobante'] = 'assets/img/uploads/comprobantes/dolar.png';
                            $detalle['cuenta'] = 1;
                        } elseif ($detalle['tipo'] === 'Zelle') {
                            $detalle['comprobante'] = 'assets/img/uploads/comprobantes/zelle.png';
                        } else {
                            $detalle['comprobante'] = null;
                        }
                    }

                    $detallePagos[] = $detalle;
                }

                $datosVenta = [
                    'cliente' => $idCliente,
                    'monto_total' => $montoTotal,
                    'cambio' => $cambio,
                    'productos' => $detalleProductos,
                    'pagos' => $detallePagos
                ];

                // Validar datos de entrada
                error_log("[COMPRFISICA] Validando datos de entrada");
                $errores = $k->validarRegistrar($datosVenta);
                if (!empty($errores)) {
                    error_log("[COMPRFISICA] Errores de validación: " . json_encode($errores));
                    echo json_encode([
                        'resultado' => 'error',
                        'mensaje' => 'Error en los datos de la venta',
                        'errors' => $errores
                    ]);
                    exit;
                }
                error_log("[COMPRFISICA] Datos validados correctamente");

                // Registrar en el modelo
                error_log("[COMPRFISICA] Llamando a registrarCompraFisica con datos: " . json_encode($datosVenta));
                try {
                    $resultado = $k->registrarCompraFisica(
                        $datosVenta,
                        (int) ($_SESSION['id_usuario'] ?? 0)
                    );
                    error_log("[COMPRFISICA] Resultado del modelo: " . json_encode($resultado));
                } catch (Exception $e) {
                    error_log("[COMPRFISICA] Excepción en modelo: " . $e->getMessage());
                    $mensaje = 'No se pudo registrar la venta. Inténtelo de nuevo o consulte al administrador.';
                    if (strpos($e->getMessage(), '1305') !== false &&
                        strpos($e->getMessage(), 'sp_registrar_compra_presencial') !== false) {
                        $mensaje = 'La base de datos casalai_principal no tiene instalado el procedimiento sp_registrar_compra_presencial. Importe la versión actualizada de assets/BD/casalai_principal.sql y vuelva a intentar. La venta no fue registrada.';
                    }
                    echo json_encode([
                        'resultado' => 'error',
                        'mensaje' => $mensaje
                    ]);
                    exit;
                }

                // Verificar si el registro fue exitoso
                if (isset($resultado['status']) && $resultado['status'] === 'error') {
                    error_log("[COMPRFISICA] Error detectado en resultado del modelo");
                    // Hubo un error en el modelo
                    $response = [
                        'resultado' => 'error',
                        'mensaje' => $resultado['mensaje'] ?? 'Error al registrar la compra'
                    ];
                } else {
                    // Registro exitoso
                    $correlativo = $facturaModel->obtenerUltimaFactura() ?? 'N/A';
                    $name = $_SESSION['name'] ?? 'Desconocido';
                    
                    // Crear notificación
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'despacho',
                        'Nueva compra física registrada',
                        "Se ha registrado una nueva compra física #" . $correlativo . " con " . array_sum($cantidades) . " unidades por el usuario " . $_SESSION['name'],
                        null,
                        'media',
                        MODULO_DESPACHO,
                        'ingresar'
                    );

                    // Preparar respuesta exitosa
                    $response = [
                        'resultado' => 'registrar',
                        'mensaje' => 'Venta registrada correctamente',
                        'venta' => $resultado // Asegurar que $resultado tenga la estructura correcta
                    ];
                }

                error_log("[COMPRFISICA] Enviando respuesta: " . json_encode($response));
                echo json_encode($response);
                error_log("[COMPRFISICA] Respuesta enviada exitosamente");
                break;

            case 'buscar_clientes':
                $query = $_POST['query'] ?? '';
                $clientes = $k->buscarClientes($query);
                echo json_encode($clientes);
                break;

            case 'permisos_tiempo_real':
                header('Content-Type: application/json; charset=utf-8');
                $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, 'compra fisica');
                if (is_array($permisosActualizados) && !array_key_exists('incluir', $permisosActualizados) && array_key_exists('ingresar', $permisosActualizados)) {
                    $permisosActualizados['incluir'] = (bool) $permisosActualizados['ingresar'];
                }
                echo json_encode($permisosActualizados);
                exit;

            case 'obtener_detalles':
                // Validar datos de entrada
                $errores = $k->validarDetallar($_POST['id_despachos'] ?? null);
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos inválidos',
                        'errors' => $errores
                    ]);
                    exit;
                }
                
                $idDespacho = $_POST['id_despachos'] ?? null;
                if ($idDespacho) {
                    $respuesta = $k->obtenerDetallesPorDespacho($idDespacho);
                    echo json_encode($respuesta);
                } else {
                    echo json_encode(['error' => true, 'mensaje' => 'ID de recepción no recibido']);
                }
                break;

            default:
                echo json_encode(['status' => 'error', 'message' => 'Acción no válida ' . $accion . '']);
        }
        exit;
    }

    // vista inicial
    $compras = $k->getCompras();
    $proveedores = $k->obtenercliente();
    $productos = $k->consultarproductos();

    $permisos = new Permisos();
    $permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
    $listadocuentas = $cuentaModel->consultarCuentabanco();
    $vistaNueva = "Vista/Vista/" . $pagina . ".php";
    $vistaLegacy = "Vista/" . $pagina . ".php";
    require_once(is_file($vistaNueva) ? $vistaNueva : $vistaLegacy);
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_DESPACHO,
            'ACCESAR',
            'El usuario accedió al módulo de Compra Física',
            'media'
        );
    }
} else {
    echo "pagina en construccion";
}

error_log("[COMPRFISICA-CONTROLADOR] Fin del controlador - " . date('Y-m-d H:i:s'));