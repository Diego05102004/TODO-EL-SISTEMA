<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Catalogo;

// Definir constantes para IDs de módulo
define('MODULO_CATALOGO', "Catalogo");

$esAdmin = isset($_SESSION['nombre_rol']) && ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario');

$data = [];
$dolarService = new DolarService();
$precioDolar = $dolarService->obtenerPrecioDolar();
$dolarService->guardarPrecioCache($precioDolar);

$productosModel = new Productos();
$catalogoModel = new Catalogo();
$bitacoraModel = new Bitacora();

// Asignar precio dólar a $data
$data['monitors'] = [
    'bcv' => [
        'price' => $precioDolar,
        'updated' => date('Y-m-d H:i:s')
    ]
];

// Manejar generación de reportes PDF
if (isset($_GET['reporte']) && $esAdmin) {
    switch ($_GET['reporte']) {
        case 'accesos_semanales':
            $datosAccesos = $bitacoraModel->obtenerEstadisticasAccesos();
            $pdf = new PDF('Reporte de Accesos', 'Estadísticas semanales de visitas al catálogo');
            $pdf->generarReporteAccesos($datosAccesos);
            exit;
            
        case 'usuarios_activos':
            $usuariosActivos = $bitacoraModel->obtenerUsuariosMasActivos(10);
            $pdf = new PDF('Reporte de Usuarios Activos', 'Top 10 usuarios con más accesos al catálogo');
            $pdf->generarReporteUsuarios($usuariosActivos);
            exit;
    }
}

function generarReporteAccesosSemanales($bitacoraModel) {
    $datos = $bitacoraModel->obtenerEstadisticasAccesoSemanal();
    $pdf = new PDF();
    $pdf->generarReporteAccesos($datos);
}

// Manejar solicitudes AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {

    try {
        header('Content-Type: application/json; charset=utf-8');
        $accion = $_POST['accion'];
        $contieneEntradaNoPermitida = static function ($valor) {
            if (!is_string($valor)) {
                return true;
            }

            $patrones = [
                '/<\s*\/?\s*[a-z!][^>]*>/i',
                '/\bjavascript\s*:|\bon[a-z]+\s*=/i',
                '/(?:\b(?:union\s+select|select\s+.+\s+from|insert\s+into|update\s+\w+\s+set|delete\s+from|drop\s+(?:table|database))\b|--|\/\*|\*\/|\b(?:or|and)\b\s+[\'"]?[\w\'"]+\s*=\s*[\'"]?[\w\'"]+)/i'
            ];

            foreach ($patrones as $patron) {
                if (preg_match($patron, $valor) === 1) {
                    return true;
                }
            }
            return false;
        };

        if ($accion == 'obtener_datos_reportes') {
            try {
                $estadisticas = $bitacoraModel->obtenerEstadisticasAccesos();
                $usuariosActivos = $bitacoraModel->obtenerUsuariosMasActivos(10);
                
                echo json_encode([
                    'status' => 'success',
                    'estadisticas' => $estadisticas,
                    'usuarios' => $usuariosActivos
                ]);
            } catch (\Throwable $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        if ($accion == 'filtrar_por_marca' || $accion == 'consultar_catalogo') {
            $busqueda = $_POST['busqueda'] ?? '';
            $categoria = $_POST['categoria'] ?? '';
            $marca = $_POST['marca'] ?? '';
            $tipo_item = $_POST['tipo_item'] ?? '';
            $id_marca_raw = $_POST['id_marca'] ?? '';

            if (!is_string($busqueda) || strlen($busqueda) > 100 || $contieneEntradaNoPermitida($busqueda)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La búsqueda contiene texto no permitido o supera el límite de 100 caracteres.'
                ]);
                exit;
            }

            if (!empty($id_marca_raw) && is_numeric($id_marca_raw) && (int)$id_marca_raw > 0) {
                $datos_validacion = ['id_marca' => (int)$id_marca_raw];
                $errores = $catalogoModel->validarFiltrar($datos_validacion);
                
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en los datos para filtrar',
                        'errors' => $errores
                    ]);
                    exit;
                }
            }

            $itemsRaw = $catalogoModel->consultarCatalogo($busqueda, $categoria, $marca, $tipo_item);

            if (!defined('SKIP_SIDE_EFFECTS')) {
                $detalleFiltro = !empty($marca) ? " (Marca: $marca)" : (!empty($id_marca_raw) ? " (Marca ID: $id_marca_raw)" : "");
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_CATALOGO,
                    'CONSULTAR',
                    "Consulta/Filtrado en catálogo mediante vista vw_catalogo" . $detalleFiltro,
                    'baja'
                );
            }

            if (!empty($itemsRaw)) {
                $html = '';
                foreach ($itemsRaw as $item) {
                    $idItem = htmlspecialchars($item['id']);
                    $nombreItem = htmlspecialchars($item['nombre']);
                    $tipoItem = htmlspecialchars($item['tipo_item']);
                    
                    $html .= '<tr class="product-row" data-id="' . $idItem . '" data-tipo="' . $tipoItem . '">
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm btn-agregar-carrito" 
                                            data-id="' . $idItem . '" data-tipo="' . $tipoItem . '">
                                        <i class="bi bi-cart-plus"></i> <span class="btn-text">Agregar</span>
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">';
                    if (!empty($item['imagen'])) {
                        $html .= '<img src="' . htmlspecialchars($item['imagen']) . '" class="product-image"
                                    alt="' . $nombreItem . '"
                                    onerror="this.src=\'assets/img/placeholder-product.png\'">';
                    } else {
                        $html .= '<div class="product-image img-placeholder">
                                    <i class="bi bi-image"></i>
                                  </div>';
                    }
                    $html .= '<div>
                                <strong>' . $nombreItem . '</strong>
                                <div class="text-muted small">' . ucfirst($tipoItem) . '</div>
                              </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge ' . ($item['stock'] > 0 ? 'bg-success' : 'bg-danger') . ' stock-badge">
                                ' . htmlspecialchars($item['stock']) . '
                            </span>
                        </td>
                        <td>' . htmlspecialchars($item['descripcion'] ?? '') . '</td>
                        <td>' . htmlspecialchars($item['marca'] ?? 'CasaLai') . '</td>
                        <td class="fw-bold">$' . number_format($item['precio'], 2) . '</td>
                    </tr>';
                }
                echo json_encode(['status' => 'success', 'html' => $html, 'data' => $itemsRaw]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No hay ítems disponibles',
                    'html' => '<tr><td colspan="6" class="text-center py-4"><i class="bi bi-exclamation-circle"></i> No hay ítems disponibles para esta selección</td></tr>'
                ]);
            }
            exit;
        }

        if ($accion == 'validar_stock') {
            try {
                $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
                $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
                $tipo_item = $_POST['tipo_item'] ?? 'producto';

                if ($id_producto <= 0) {
                    throw new Exception('Ítem no especificado o inválido');
                }
                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser mayor a cero');
                }

                $item = $catalogoModel->obtenerItemPorIdYTipo($id_producto, $tipo_item);
                if (!$item) {
                    throw new Exception('Ítem no encontrado en el catálogo');
                }

                $stock = (int)($item['stock'] ?? 0);
                echo json_encode([
                    'status' => 'success',
                    'stock_disponible' => $stock,
                    'suficiente' => $stock >= $cantidad
                ]);
            } catch (\Throwable $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        if ($accion == 'agregar_al_carrito') {
            try {
                if (!isset($_SESSION['id_usuario'])) {
                    throw new Exception('Debe iniciar sesión para agregar productos al carrito');
                }

                $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
                $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;

                $datos_validacion = [
                    'id_producto' => $id_producto,
                    'cantidad' => $cantidad
                ];
                
                if ($id_combo > 0) {
                    $datos_validacion['id_combo'] = $id_combo;
                }
                $errores = $catalogoModel->validarAgregarCarrito($datos_validacion);
                
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en los datos para agregar al carrito',
                        'errors' => $errores
                    ]);
                    exit;
                }

                $producto = $productosModel->obtenerProductoPorId($id_producto);
                if (!$producto) {
                    throw new Exception('Producto no encontrado');
                }

                $nombreProducto = $producto['nombre_producto'];

                $result = $productosModel->agregarProductoAlCarrito($_SESSION['id_usuario'], $id_producto, $cantidad);

                if ($result === true) {
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'INCLUIR',
                            "El usuario agregó producto al carrito: $nombreProducto (Cantidad: $cantidad)",
                            'alta'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Producto agregado correctamente al carrito'
                    ]);
                } else {
                    $mensaje = is_string($result) ? $result : 'Error al agregar producto al carrito';
                    throw new Exception($mensaje);
                }
            } catch (\Throwable $e) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        $validarDatosCombo = static function ($nombre, $descripcion, $descuentoRaw) use ($contieneEntradaNoPermitida) {
            $nombre = is_string($nombre) ? trim($nombre) : '';
            $descripcion = is_string($descripcion) ? trim($descripcion) : '';
            $descuentoRaw = is_string($descuentoRaw) || is_numeric($descuentoRaw)
                ? trim((string)$descuentoRaw)
                : '';

            foreach ([$nombre, $descripcion, $descuentoRaw] as $valor) {
                if ($contieneEntradaNoPermitida($valor)) {
                    throw new Exception('El contenido ingresado no está permitido.');
                }
            }

            if ($nombre === '' || preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\s._\'-]{0,99}$/u', $nombre) !== 1) {
                throw new Exception('El nombre debe tener entre 1 y 100 caracteres válidos');
            }
            if ($descripcion === '') {
                throw new Exception('La descripción del combo es requerida');
            }

            $longitudDescripcion = function_exists('mb_strlen')
                ? mb_strlen($descripcion, 'UTF-8')
                : preg_match_all('/./us', $descripcion);
            if ($longitudDescripcion === false || $longitudDescripcion > 500) {
                throw new Exception('La descripción no puede superar los 500 caracteres');
            }

            $descuentoRaw = $descuentoRaw === '' ? '0' : str_replace(',', '.', $descuentoRaw);
            if (preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $descuentoRaw) !== 1
                || (float)$descuentoRaw > 100) {
                throw new Exception('El descuento debe ser un porcentaje entre 0 y 100, con hasta dos decimales');
            }

            return [$nombre, $descripcion, (float)$descuentoRaw];
        };

        if ($accion == 'crear_combo') {
            try {
                $nombre = $_POST['nombre_combo'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $descuentoRaw = $_POST['descuento'] ?? null;
                $productos = $_POST['productos'] ?? [];

                [$nombre, $descripcion, $descuento] = $validarDatosCombo($nombre, $descripcion, $descuentoRaw);

                if (!is_array($productos) || empty($productos)) {
                    throw new Exception('Debe seleccionar al menos un producto para el combo');
                }

                $productosValidos = [];
                foreach ($productos as $producto) {
                    $id = isset($producto['id']) ? (int)$producto['id'] : 0;
                    $cantidadRaw = isset($producto['cantidad']) ? (string)$producto['cantidad'] : '';
                    if (preg_match('/^[1-9]\d{0,2}$/', $cantidadRaw) !== 1) {
                        throw new Exception('La cantidad de cada producto debe ser un entero entre 1 y 999');
                    }
                    $cantidad = (int)$cantidadRaw;

                    if ($id <= 0 || $cantidad <= 0) {
                        throw new Exception('Los productos del combo deben tener un ID y una cantidad válidos');
                    }

                    $infoProducto = $productosModel->obtenerProductoPorId($id);
                    if (!$infoProducto) {
                        throw new Exception('Uno de los productos seleccionados para el combo no existe');
                    }

                    $productosValidos[] = [
                        'id' => $id,
                        'cantidad' => $cantidad
                    ];
                }

                if (empty($productosValidos)) {
                    throw new Exception('Debe seleccionar al menos un producto válido para el combo');
                }

                $id_combo = $productosModel->crearCombo($nombre, $descripcion, $productosValidos, $descuento);

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATALOGO,
                        'INCLUIR',
                        "El usuario creó un nuevo combo: $nombre (ID: $id_combo)",
                        'alta'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo creado exitosamente',
                    'id_combo' => $id_combo
                ]);
            } catch (\Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'cambiar_estado_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;
                
                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }
                
                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo) {
                    throw new Exception('Combo no encontrado');
                }
                
                $resultado = $productosModel->cambiarEstadoCombo($id_combo);
                
                if ($resultado) {
                    $nuevoEstado = $productosModel->obtenerComboPorId($id_combo)['activo'];
                    $accionEstado = $nuevoEstado ? 'habilitó' : 'deshabilitó';
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'CAMBIAR_ESTADO',
                            "El usuario $accionEstado el combo: {$combo['nombre_combo']} (ID: $id_combo)",
                            'media'
                        );
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Estado del combo actualizado correctamente',
                    'nuevo_estado' => $productosModel->obtenerComboPorId($id_combo)['activo']
                ]);
                
            } catch (\Throwable $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }
        
        if ($accion == 'actualizar_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;
                $nombre = $_POST['nombre_combo'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $descuentoRaw = $_POST['descuento'] ?? null;
                $productos = $_POST['productos'] ?? [];

                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }

                [$nombre, $descripcion, $descuento] = $validarDatosCombo($nombre, $descripcion, $descuentoRaw);

                if (!is_array($productos) || empty($productos)) {
                    throw new Exception('Debe seleccionar al menos un producto para el combo');
                }

                $comboAntes = $productosModel->obtenerComboPorId($id_combo);
                if (!$comboAntes) {
                    throw new Exception('El combo que intenta actualizar no existe');
                }

                $productosValidos = [];
                foreach ($productos as $producto) {
                    $id = isset($producto['id']) ? (int)$producto['id'] : 0;
                    $cantidadRaw = isset($producto['cantidad']) ? (string)$producto['cantidad'] : '';
                    if (preg_match('/^[1-9]\d{0,2}$/', $cantidadRaw) !== 1) {
                        throw new Exception('La cantidad de cada producto debe ser un entero entre 1 y 999');
                    }
                    $cantidad = (int)$cantidadRaw;

                    if ($id <= 0 || $cantidad <= 0) {
                        throw new Exception('Los productos del combo deben tener un ID y una cantidad válidos');
                    }

                    $infoProducto = $productosModel->obtenerProductoPorId($id);
                    if (!$infoProducto) {
                        throw new Exception('Uno de los productos seleccionados para el combo no existe');
                    }

                    $productosValidos[] = [
                        'id' => $id,
                        'cantidad' => $cantidad
                    ];
                }

                if (empty($productosValidos)) {
                    throw new Exception('Debe seleccionar al menos un producto válido para el combo');
                }

                $result = $productosModel->actualizarCombo($id_combo, $nombre, $descripcion, $productosValidos, $descuento);

                if ($result) {
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'MODIFICAR',
                            "El usuario modificó el combo: {$comboAntes['nombre_combo']} (ID: $id_combo)",
                            'media'
                        );
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo actualizado exitosamente'
                ]);
            } catch (\Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'eliminar_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;

                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }

                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo) {
                    throw new Exception('El combo que intenta eliminar no existe');
                }

                $result = $productosModel->eliminarCombo($id_combo);

                if ($result) {
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'ELIMINAR',
                            "El usuario eliminó el combo: {$combo['nombre_combo']} (ID: $id_combo)",
                            'media'
                        );
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo eliminado exitosamente'
                ]);
            } catch (\Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'obtener_detalles_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;

                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }

                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo) {
                    throw new Exception('Combo no encontrado');
                }

                $detalles = $productosModel->obtenerDetallesCombo($id_combo);

                echo json_encode([
                    'status' => 'success',
                    'combo' => $combo,
                    'detalles' => $detalles
                ]);
            } catch (\Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'agregar_combo_al_carrito') {
            try {
                if (!isset($_SESSION['id_usuario'])) {
                    throw new Exception('Debe iniciar sesión para agregar combos');
                }

                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;
                if ($id_combo <= 0) {
                    throw new Exception('No se especificó el combo o el identificador es inválido');
                }

                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo || (isset($combo['activo']) && !$combo['activo'])) {
                    throw new Exception('El combo no está disponible');
                }

                $result = $productosModel->agregarComboAlCarrito($_SESSION['id_usuario'], $id_combo);
                if ($result !== true) {
                    $mensaje = is_string($result) ? $result : 'Error al agregar combo al carrito';
                    throw new Exception($mensaje);
                }

                $detalles = $catalogoModel->obtenerDetalleCombo($id_combo);

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATALOGO,
                        'INCLUIR',
                        "El usuario agregó combo al carrito: {$combo['nombre_combo']} (ID: $id_combo, Productos: ".count($detalles).")",
                        'alta'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo agregado correctamente al carrito',
                    'productos_agregados' => count($detalles)
                ]);
            } catch (\Throwable $e) {
               echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Obtener datos unificados desde la vista vw_catalogo para la carga inicial
try {
    $catalogoUnificado = $catalogoModel->consultarCatalogo();
    
    // Mapeo para preservar compatibilidad con las variables que espera la plantilla PHP original
    $productos = [];
    foreach ($catalogoUnificado as $item) {
        if (strtolower(trim($item['tipo_item'] ?? '')) === 'producto') {
            $item['id_producto'] = $item['id'];
            $item['nombre_producto'] = $item['nombre'];
            $item['descripcion_producto'] = $item['descripcion'];
            $item['serial'] = $item['serial'] ?? '';
            $productos[] = $item;
        }
    }

    $marcas = $catalogoModel->obtenerMarcasCatalogo();
    $categorias = $catalogoModel->obtenerCategoriasCatalogo();
    $combos = $productosModel->obtenerCombosConDetalles($esAdmin);
    
} catch (\Throwable $e) {
    $productos = [];
    $marcas = [];
    $categorias = [];
    $combos = [];
}

// Asignar la página y cargar la vista
$pagina = "catalogo";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && ($_GET['ajax'] ?? '') !== 'combo_refresh' && isset($_SESSION['id_usuario'])) {
        register_shutdown_function(function() use ($bitacoraModel) {
            try {
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_CATALOGO,
                    'ACCESAR',
                    'El usuario accedió al módulo de Catálogo',
                    'media'
                );
            } catch (\Throwable $e) {
                error_log("Error registrando bitácora en background: " . $e->getMessage());
            }
        });
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>