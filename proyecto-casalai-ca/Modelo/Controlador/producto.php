<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Servicio\AnaliticaPredictivaProxy;

require_once dirname(__DIR__) . '/Servicio/AnaliticaPredictivaProxy.php';

define('MODULO_PRODUCTOS', "Productos");

$id_rol = $_SESSION['id_rol'] ?? 0;

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('productos'));
$accionPermiso = [
    'ingresar' => 'incluir',
    'modificar' => 'modificar',
    'eliminar' => 'eliminar',
    'cambiar_estatus' => 'modificar',
    'reporte_parametrizado' => 'generar reporte',
    'obtener_producto' => 'consultar',
    'analitica_dashboard' => 'consultar',
    'analitica_feedback' => 'consultar',
    'analitica_entrenar' => 'modificar'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($permisosUsuario['consultar'])) {
    header('Location: ?pagina=acceso-denegado');
    exit;
}

// Manejo de solicitudes POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';

    if (isset($accionPermiso[$accion])) {
        $permisoRequerido = $accionPermiso[$accion];
        $autorizado = !empty($permisosUsuario[$permisoRequerido]);
        if ($accion === 'ingresar') {
            $autorizado = $autorizado || !empty($permisosUsuario['ingresar']);
        }
        if (!$autorizado) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'No tiene permiso para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    switch ($accion) {

        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('productos'));
            echo json_encode($permisosActualizados, JSON_UNESCAPED_UNICODE);
            exit;

        case 'ingresar':
            // DEPURACIÓN: Registrar datos recibidos
            error_log("=== DEPURACIÓN: INICIANDO REGISTRO DE PRODUCTO ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            $Producto = new Productos();

            $nombre_producto = $_POST['nombre_producto'] ?? '';
            $descripcion_producto = $_POST['descripcion_producto'] ?? '';
            $modelo = $_POST['modelo'] ?? null;
            $Stock_Actual = $_POST['Stock_Actual'] ?? 0;
            $Stock_Maximo = $_POST['Stock_Maximo'] ?? 0;
            $Stock_Minimo = $_POST['Stock_Minimo'] ?? 0;
            $Clausula_garantia = $_POST['Clausula_garantia'] ?? '';
            $Seriales = $_POST['Seriales'] ?? '';
            $Categoria = $_POST['Categoria'] ?? '';
            $Precio = $_POST['Precio'] ?? 0;
            $Producto->setNombreP($nombre_producto);
            $Producto->setDescripcionP($descripcion_producto);
            $Producto->setIdModelo($modelo);
            $Producto->setStockActual($Stock_Actual);
            $Producto->setStockMax($Stock_Maximo);
            $Producto->setStockMin($Stock_Minimo);
            $Producto->setClausulaDeGarantia($Clausula_garantia);
            $Producto->setCodigo($Seriales);
            $Producto->setCategoria($Categoria);
            $Producto->setPrecio($Precio);
            
            // Procesar imagen ANTES de insertar el producto
            $ruta_imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $imagenData = [
                    'name' => $_FILES['imagen']['name'],
                    'tmp_name' => $_FILES['imagen']['tmp_name'],
                    'error' => $_FILES['imagen']['error'],
                    'size' => $_FILES['imagen']['size']
                ];
                $errores_imagen = $Producto->validarImagen($imagenData);
                
                if (!empty($errores_imagen)) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en la validación de la imagen',
                        'errors' => $errores_imagen
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    $directorio = "../assets/img/productos/";
                    if (!is_dir($directorio)) {
                        mkdir($directorio, 0755, true);
                    }
                    $nombre_original = $_FILES['imagen']['name'];
                    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                    // Usar timestamp para nombre único ya que aún no tenemos id_producto
                    $nombre_nuevo = "producto_" . time() . "_" . uniqid() . "." . $extension;
                    $ruta_destino = $directorio . $nombre_nuevo;

                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                        $ruta_imagen = $ruta_destino;
                        $Producto->setImagen($ruta_imagen);
                    }
                }
            } else {
                $Producto->setImagen(null);
            }
            
            error_log("Datos extraídos:");
            error_log("  nombre_producto: " . $nombre_producto);
            error_log("  descripcion_producto: " . $descripcion_producto);
            error_log("  modelo: " . $modelo);
            error_log("  Categoria: " . $Categoria);
            error_log("  Precio: " . $Precio);

            // Preparar datos para validación
            $datos_validacion = [
                'nombre_producto' => $nombre_producto,
                'descripcion_producto' => $descripcion_producto,
                'id_modelo' => $modelo,
                'stock_actual' => $Stock_Actual,
                'stock_maximo' => $Stock_Maximo,
                'stock_minimo' => $Stock_Minimo,
                'clausula_garantia' => $Clausula_garantia,
                'serial' => $Seriales,
                'categoria' => $Categoria,
                'precio' => $Precio
            ];

            // Validar datos del producto usando las nuevas validaciones centralizadas
            error_log("Validando datos del producto...");
            $errores = $Producto->validarRegistrarProducto($datos_validacion);
            if (!empty($errores)) {
                error_log("❌ Errores de validación encontrados: " . print_r($errores, true));
                // Construir mensaje con todos los errores
                $mensaje_errores = "Error en los datos del producto:\n\n";
                foreach ($errores as $campo => $mensaje) {
                    $mensaje_errores .= "• $campo: $mensaje\n";
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => $mensaje_errores,
                    'errors' => $errores
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            error_log("✅ Validación de datos exitosa");

            // Validar que el modelo exista antes de registrar
            if ($_POST['modelo'] ?? null) {
                $modeloValido = false;
                // Aquí deberías tener una función para verificar si el modelo existe
                // Por ahora, asumimos que si hay un ID, es válido
                $idModelo = (int)$_POST['modelo'];
                if ($idModelo > 0) {
                    $modeloValido = true;
                }
                
                if (!$modeloValido) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El modelo seleccionado no es válido'
                    ]);
                    exit;
                }
            }

            if (!$Producto->validarNombreProducto()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Este Producto ya existe'], JSON_UNESCAPED_UNICODE);
                exit;
            } elseif (!$Producto->validarCodigoProducto()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Este Código Interno ya existe'], JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                try {
                    $resultado = $Producto->ingresarProducto($_POST);
                    if ($resultado) {
                        $id_producto = $resultado;
                        $respuesta = [
                            'status' => 'success',
                            'id_producto' => $id_producto,
                            'imagen' => $ruta_imagen,
                            'mensaje' => $ruta_imagen ? "Producto registrado e imagen guardada correctamente." : "Producto registrado correctamente."
                        ];

                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
                        exit;
                    } else {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['status' => 'error', 'message' => 'Error al registrar producto'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                } catch (Exception $e) {
                    error_log("Error al registrar producto: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            break;

        case 'obtener_producto':
            $id = $_POST['id_producto'] ?? null;
            header('Content-Type: application/json; charset=utf-8');
            if ($id !== null) {
                $Producto = new Productos();
                $producto = $Producto->obtenerProductoPorId($id);
                if ($producto !== null) {
                    echo json_encode($producto, JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de producto no proporcionado'], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;

        case 'modificar':
            $id = $_POST['id_producto'] ?? null;
            if ($id === null) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'ID de producto no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $Producto = new Productos();
            
            // Preparar datos para validación
            $datos_validacion = [
                'id_producto' => $id,
                'nombre_producto' => $_POST['nombre_producto'] ?? '',
                'descripcion_producto' => $_POST['descripcion_producto'] ?? '',
                'id_modelo' => $_POST['modelo'] ?? null,
                'stock_actual' => $_POST['Stock_Actual'] ?? 0,
                'stock_maximo' => $_POST['Stock_Maximo'] ?? 0,
                'stock_minimo' => $_POST['Stock_Minimo'] ?? 0,
                'clausula_garantia' => $_POST['Clausula_garantia'] ?? '',
                'serial' => $_POST['Seriales'] ?? '',
                'precio' => $_POST['Precio'] ?? 0
            ];

            // Validar datos del producto usando las nuevas validaciones centralizadas
            $errores = $Producto->validarModificarProducto($datos_validacion);
            if (!empty($errores)) {
                // Construir mensaje con todos los errores
                $mensaje_errores = "Error en los datos del producto:\n\n";
                foreach ($errores as $campo => $mensaje) {
                    $mensaje_errores .= "• $campo: $mensaje\n";
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => $mensaje_errores,
                    'errors' => $errores
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Verificar que el producto exista antes de modificar
            $productoExistente = $Producto->obtenerProductoPorId($id);
            if (!$productoExistente) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El producto que intenta modificar no existe'
                ]);
                exit;
            }

            // Validar que el modelo exista antes de modificar
            if ($_POST['modelo'] ?? null) {
                $modeloValido = false;
                $idModelo = (int)$_POST['modelo'];
                if ($idModelo > 0) {
                    $modeloValido = true;
                }
                
                if (!$modeloValido) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El modelo seleccionado no es válido'
                    ]);
                    exit;
                }
            }

            $productoViejo = $Producto->obtenerProductoPorId($id);

            // ESTABLECER DATOS EN EL OBJETO PRODUCTO (FALTABA)
            $Producto->setNombreP($_POST['nombre_producto'] ?? '');
            $Producto->setDescripcionP($_POST['descripcion_producto'] ?? '');
            $Producto->setIdModelo($_POST['modelo'] ?? null);
            $Producto->setStockActual($_POST['Stock_Actual'] ?? 0);
            $Producto->setStockMax($_POST['Stock_Maximo'] ?? 0);
            $Producto->setStockMin($_POST['Stock_Minimo'] ?? 0);
            $Producto->setClausulaDeGarantia($_POST['Clausula_garantia'] ?? '');
            $Producto->setCodigo($_POST['Seriales'] ?? '');
            $Producto->setCategoria($_POST['Categoria'] ?? '');
            $Producto->setPrecio($_POST['Precio'] ?? 0);

            // Procesar imagen ANTES de modificar el producto
            $imagen_actual = $productoViejo['imagen'] ?? null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                // Validar imagen usando las nuevas validaciones
                $imagenData = [
                    'name' => $_FILES['imagen']['name'],
                    'tmp_name' => $_FILES['imagen']['tmp_name'],
                    'error' => $_FILES['imagen']['error'],
                    'size' => $_FILES['imagen']['size']
                ];
                $errores_imagen = $Producto->validarImagen($imagenData, false);
                
                if (!empty($errores_imagen)) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en la imagen',
                        'imagen_errors' => $errores_imagen
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                $directorio = "../assets/img/productos/";
                if (!is_dir($directorio)) {
                    mkdir($directorio, 0755, true);
                }
                $nombre_original = $_FILES['imagen']['name'];
                $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                $nombre_nuevo = "producto_" . $id . "." . $extension;
                $ruta_destino = $directorio . $nombre_nuevo;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                    $Producto->setImagen($ruta_destino);
                    // Eliminar imagen anterior
                    if ($imagen_actual && file_exists($imagen_actual)) {
                        @unlink($imagen_actual);
                    }
                } else {
                    $Producto->setImagen($imagen_actual);
                }
            } else {
                // Mantener imagen actual si no se subió una nueva
                $Producto->setImagen($imagen_actual);
            }

            try {
                if ($Producto->modificarProducto($id, $_POST)) {
                    $productoActualizado = $Producto->obtenerProductoPorId($id);
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_PRODUCTOS,
                            'MODIFICAR',
                            'El usuario modificó el producto: ' . ($_POST['nombre_producto'] ?? '') . ' | Antes: ' . json_encode($productoViejo) . ' | Después: ' . json_encode($productoActualizado),
                            'media'
                        );
                    }

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'success', 'mensaje' => 'Producto modificado correctamente'], JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => 'Error al modificar el producto'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } catch (Exception $e) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            break;

        case 'eliminar':
            $id_producto = $_POST['id_producto'] ?? null;
            header('Content-Type: application/json; charset=utf-8');
            if ($id_producto === null) {
                echo json_encode(['status' => 'error', 'message' => 'ID del Producto no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Validar ID del producto
            $id_producto = (int)$id_producto;
            if ($id_producto <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El ID del producto no es válido'
                ]);
                exit;
            }
            
            $producto = new Productos();
            
            // Verificar que el producto exista antes de eliminar
            $productoExistente = $producto->obtenerProductoPorId($id_producto);
            if (!$productoExistente) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El producto que intenta eliminar no existe'
                ]);
                exit;
            }
            $id_usuario = $_SESSION['id_usuario'];

            $datos_validacion = ['id_producto' => $id_producto];
            $errores = $producto->validarEliminarProducto($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para eliminar el producto',
                    'errors' => $errores
                ]);
                exit;
            }

            $response = $producto->eliminarProducto($id_producto, $id_usuario);
            if (is_array($response) && ($response['success'] ?? false)) {

                echo json_encode(['status' => 'success', 'message' => $response['message']], JSON_UNESCAPED_UNICODE);
            } else {
                $msg = is_array($response) ? ($response['message'] ?? 'Error al eliminar producto') : 'Error al eliminar producto';
                echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;

        case 'cambiar_estatus':
            $id = $_POST['id_producto'] ?? null;
            $nuevoEstatus = $_POST['nuevo_estatus'] ?? null;
            header('Content-Type: application/json; charset=utf-8');
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $Producto = new Productos();
            $datos_validacion = [
                'id_producto' => $id,
                'nuevo_estatus' => $nuevoEstatus
            ];
            $errores = $Producto->validarCambiarEstatus($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para cambiar estatus',
                    'errors' => $errores
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $producto = new Productos();
            $producto->setId($id);
            if ($producto->cambiarEstatus($nuevoEstatus)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_PRODUCTOS,
                        'CAMBIAR ESTATUS',
                        'El usuario cambió el estatus del producto ID ' . $id . ' a ' . $nuevoEstatus,
                        'media'
                    );
                }
                echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus'], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;

        case 'reporte_parametrizado':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reporte_parametrizado') {
                $tipoReporte = $_POST['tipoReporte'] ?? '';
                $categoria = $_POST['categoriaSeleccionada'] ?? '';
                $productoModel = new Productos();
                if ($tipoReporte === 'por_categoria') {
                    $datos = $productoModel->obtenerReporteCategorias();
                    $labels = array_column($datos, 'nombre_categoria');
                    $data = array_column($datos, 'cantidad');
                } elseif ($tipoReporte === 'por_categoria_especifica' && $categoria) {
                    $datos = $productoModel->obtenerProductosPorCategoria($categoria);
                    $labels = array_column($datos, 'nombre_producto');
                    $data = array_column($datos, 'stock');
                } elseif ($tipoReporte === 'precios') {
                    $datos = $productoModel->obtenerProductosConPrecios();
                    $labels = array_column($datos, 'nombre_producto');
                    $data = array_column($datos, 'precio');
                } else {
                    $labels = [];
                    $data = [];
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['labels' => $labels, 'data' => $data], JSON_UNESCAPED_UNICODE);
                exit;
            }
            break;

        case 'analitica_dashboard':
            header('Content-Type: application/json; charset=utf-8');
            $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
            $frecuencia = $_POST['frecuencia'] ?? '';
            $horizonte = filter_var($_POST['horizonte'] ?? null, FILTER_VALIDATE_INT);
            $hayProducto = $productoId !== false && $productoId !== null && $productoId > 0;
            $hayCategoria = $categoriaId !== false && $categoriaId !== null && $categoriaId > 0;

            // Permitir que ambos sean nulos (analizar todos los productos)
            // Pero no permitir que ambos tengan valor
            if (($hayProducto && $hayCategoria) ||
                !in_array($frecuencia, ['diaria', 'semanal', 'mensual'], true) ||
                !in_array($horizonte, [15, 30, 60, 90], true)) {
                http_response_code(400);
                echo json_encode(['detail' => 'Seleccione un producto o categoría, frecuencia y horizonte válidos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                $productoModel = new Productos();
                $datosAnalitica = $productoModel->obtenerDatosAnaliticaPredictiva(365);
                $solicitud = array_merge($datosAnalitica, [
                    'producto_id' => $hayProducto ? $productoId : null,
                    'categoria_id' => $hayCategoria ? $categoriaId : null,
                    'frecuencia' => $frecuencia,
                    'horizonte' => $horizonte
                ]);
                $resultado = (new AnaliticaPredictivaProxy())->obtenerDashboard($solicitud);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (\RuntimeException | \JsonException $e) {
                error_log('Error en dashboard de analítica predictiva: ' . $e->getMessage());
                http_response_code(503);
                echo json_encode(['detail' => 'No se pudo consultar la analítica. Verifique que el microservicio esté disponible y configurado.'], JSON_UNESCAPED_UNICODE);
            } catch (\InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(['detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'analitica_entrenar':
            header('Content-Type: application/json; charset=utf-8');
            try {
                $productoModel = new Productos();
                $datosAnalitica = $productoModel->obtenerDatosAnaliticaPredictiva(365);
                if (empty($datosAnalitica['productos']) || empty($datosAnalitica['ventas'])) {
                    http_response_code(422);
                    echo json_encode(['detail' => 'No hay productos o facturas pagadas suficientes para entrenar en los últimos 365 días.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $resultado = (new AnaliticaPredictivaProxy())->entrenar($datosAnalitica);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (\RuntimeException | \JsonException $e) {
                error_log('Error al entrenar analítica predictiva: ' . $e->getMessage());
                http_response_code(503);
                echo json_encode(['detail' => 'No se pudo entrenar el modelo. Verifique el microservicio y la configuración.'], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'analitica_feedback':
            header('Content-Type: application/json; charset=utf-8');
            $recomendacionId = trim((string)($_POST['recomendacion_id'] ?? ''));
            $tipo = $_POST['tipo'] ?? '';
            $estrellas = filter_var($_POST['estrellas'] ?? null, FILTER_VALIDATE_INT);
            if (!preg_match('/^(combo|reposicion):[0-9,]+$/', $recomendacionId) ||
                !in_array($tipo, ['combo', 'reposicion'], true) ||
                $estrellas === false || $estrellas < 1 || $estrellas > 5) {
                http_response_code(400);
                echo json_encode(['detail' => 'La valoración de la recomendación no es válida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                $resultado = (new AnaliticaPredictivaProxy())->guardarFeedback([
                    'recomendacion_id' => $recomendacionId,
                    'tipo' => $tipo,
                    'estrellas' => $estrellas,
                    'usuario_id' => isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null
                ]);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (\RuntimeException | \JsonException $e) {
                error_log('Error al guardar feedback de analítica predictiva: ' . $e->getMessage());
                http_response_code(503);
                echo json_encode(['detail' => 'No se pudo guardar la valoración. Inténtelo nuevamente.'], JSON_UNESCAPED_UNICODE);
            }
            exit;

        default:
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
            exit;
            break;
    }
}

// Funciones auxiliares y carga de vista

function obtenerModelos() {
    $Producto = new Productos();
    return $Producto->obtenerModelos();
}

function obtenerProductos() {
    $Producto = new Productos();
    return $Producto->obtenerProductos();
}

function obtenerProductosMasRecibidos() {
    $recepcion = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion();
    return $recepcion->getProductosMasRecibidos();
}

$productoModel = new Productos();
$masVendidos   = $productoModel->getProductosMasVendidos();
$stockProductos = $productoModel->getStockProductos();
$rotacion      = $productoModel->getRotacionProductos();
$categorias = $productoModel->CategoriasReporte();
$categoriasDinamicas = $productoModel->obtenerCategoriasDinamicas();
$reporteCategorias = $productoModel->obtenerReporteCategorias();
$productosMasRecibidos = obtenerProductosMasRecibidos();

if (!$reporteCategorias || !is_array($reporteCategorias)) {
    $reporteCategorias = [];
}

$totalCategorias = array_sum(array_column($reporteCategorias, 'cantidad'));
foreach ($reporteCategorias as &$cat) {
    $cat['porcentaje'] = $totalCategorias > 0 ? round(($cat['cantidad'] / $totalCategorias) * 100, 2) : 0;
}
unset($cat);

$mostrarFormulario = !empty($categoriasDinamicas);
$pagina = "producto";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Productos',
                'ACCESAR',
                'El usuario accedió al módulo de Productos',
                'media'
            );
        }
    }

    $modelos = obtenerModelos();
    $productos = obtenerProductos();
    $productosMasRecibidos = obtenerProductosMasRecibidos();
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Productos',
                'ACCESAR',
                'El usuario accedió al módulo de Productos',
                'media'
            );
        }
    }

    $modelos = obtenerModelos();
    $productos = obtenerProductos();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>