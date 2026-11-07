<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Proveedores;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

// Definir constantes para IDs de módulo y acciones
define('MODULO_PROVEEDORES', "Proveedores"); // Cambiar según tu estructura de módulos

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('proveedores'));
$puedeConsultarProveedores = !empty($permisosUsuario['consultar']);

$reporteProveedor = new Proveedores();
$reporteRankingProveedores = $reporteProveedor->getRankingProveedores();
$reporteComparacion = $reporteProveedor->getComparacionPreciosProducto();
$reporteDependencia = $reporteProveedor->getDependenciaProveedores();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario_accion = $_SESSION['id_usuario'] ?? null;
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    $permisosPorAccion = [
        'registrar' => 'incluir',
        'obtener_proveedor' => 'consultar',
        'modificar' => 'modificar',
        'eliminar' => 'eliminar',
        'cambiar_estado' => 'modificar',
        'generar_reporte' => 'generar reporte'
    ];
    if (isset($permisosPorAccion[$accion]) && empty($permisosUsuario[$permisosPorAccion[$accion]])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'No tiene permiso para realizar esta acción.'
        ]);
        exit;
    }

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('proveedores'));
            echo json_encode($permisosActualizados);
            exit;
            
        case 'registrar':
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }
            
            $proveedor = new Proveedores();
            
            $proveedor->setNombre($_POST['nombre_proveedor']);
            $proveedor->setRif1($_POST['rif_proveedor']);
            $proveedor->setRepresentante($_POST['nombre_representante']);
            $proveedor->setRif2($_POST['rif_representante']);
            $proveedor->setCorreo($_POST['correo_proveedor']);
            $proveedor->setDireccion($_POST['direccion_proveedor']);
            $proveedor->setTelefono1($_POST['telefono_1']);
            $proveedor->setTelefono2($_POST['telefono_2']);
            $proveedor->setObservacion($_POST['observacion']);

            $errores = $proveedor->validarRegistrar($_POST);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }
            
            if ($proveedor->registrarProveedor($id_usuario_sesion)) {
                $proveedorRegistrado = $proveedor->obtenerUltimoProveedor();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Proveedor registrado correctamente',
                    'proveedor' => $proveedorRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar el proveedor'
                ]);
            }
            exit;

        case 'obtener_proveedor':
            $proveedor = new Proveedores();
            
            $errores = $proveedor->validarDetallar($_POST['id_proveedor']);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al obtener el proveedor',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $proveedorData = $proveedor->obtenerProveedorPorId($_POST['id_proveedor']);
            
            echo json_encode([
                'status' => 'success',
                'proveedor' => $proveedorData
            ]);
            exit;

        case 'modificar':
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');

            $id_proveedor = $_POST['id_proveedor'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }
            
            $proveedor = new Proveedores();
            
            $proveedor->setIdProveedor($id_proveedor);
            $proveedor->setNombre($_POST['nombre_proveedor']);
            $proveedor->setRif1($_POST['rif_proveedor']);
            $proveedor->setRepresentante($_POST['nombre_representante']);
            $proveedor->setRif2($_POST['rif_representante']);
            $proveedor->setCorreo($_POST['correo_proveedor']);
            $proveedor->setDireccion($_POST['direccion_proveedor']);
            $proveedor->setTelefono1($_POST['telefono_1']);
            $proveedor->setTelefono2($_POST['telefono_2']);
            $proveedor->setObservacion($_POST['observacion']);

            $errores = $proveedor->validarModificar($_POST);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en la validación de datos',
                    'field_errors' => $errores
                ]);
                exit;
            }
            
            $proveedorViejo = $proveedor->obtenerProveedorPorId($id_proveedor);
                
            if ($proveedor->modificarProveedor($id_proveedor, $id_usuario_sesion)) {
                $proveedorActualizado = $proveedor->obtenerProveedorPorId($id_proveedor);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Proveedor modificado correctamente',
                    'proveedor' => $proveedorActualizado
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el proveedor']);
            }
            exit;

        case 'eliminar':
            $id_proveedor = $_POST['id_proveedor'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
                
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $proveedor = new Proveedores();
            
            $errores = $proveedor->validarEliminar($_POST['id_proveedor']);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar el proveedor',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $proveedorAEliminar = $proveedor->obtenerProveedorPorId($id_proveedor);
            
            if ($proveedor->eliminarProveedor($id_proveedor, $id_usuario_sesion)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Proveedor eliminado correctamente'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Error al eliminar el proveedor'
                ]);
            }
            exit;
        
        case 'cambiar_estado':
            $id_proveedor = $_POST['id_proveedor'];
            $nuevoEstatus = $_POST['nuevo_estatus'];
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            $proveedor = new Proveedores();

            $proveedor->setIdProveedor($id_proveedor);

            $errores = $proveedor->validarCambiarEstatus($_POST['id_proveedor'], $_POST['nuevo_estatus']);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al cambiar el estatus del proveedor',
                    'errors' => $errores
                ]);
                exit;
            }
            
            if ($proveedor->cambiarEstatus($nuevoEstatus, $id_usuario_sesion)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Estatus cambiado correctamente'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Error al cambiar el estatus del proveedor'
                ]);
            }
            exit;

        case 'generar_reporte':
            $proveedor = new Proveedores();
            
            $parametros = $_POST;
            $errores = $proveedor->validarGenerarReporte($parametros);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los parámetros del reporte',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $formato = $parametros['formato'] ?? 'pdf';
            $fecha_inicio = $parametros['fecha_inicio'] ?? null;
            $fecha_fin = $parametros['fecha_fin'] ?? null;
            
            $filtros = [];
            if ($fecha_inicio && $fecha_fin) {
                $filtros['fecha_inicio'] = $fecha_inicio;
                $filtros['fecha_fin'] = $fecha_fin;
            }
            
            $resultado = $proveedor->obtenerProveedoresConFiltros($filtros);
            
            if (isset($resultado['error'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al obtener datos para el reporte',
                    'errors' => $resultado['error']
                ]);
                exit;
            }
            
            if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                $bitacora = new Bitacora();
                $bitacora->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PROVEEDORES,
                    'GENERAR REPORTE',
                    'El usuario generó un reporte de proveedores en formato ' . $formato,
                    'baja'
                );
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Reporte generado correctamente',
                'datos' => $resultado['proveedores'],
                'formato' => $formato,
                'total' => $resultado['total']
            ]);
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
    }
}

function getproveedores($filtros = []) {
    $proveedor = new Proveedores();
    
    $resultado = $proveedor->obtenerProveedoresConFiltros($filtros);
    
    if (isset($resultado['error'])) {
        return [];
    }
    
    return $resultado['proveedores'] ?? [];
}

function getRecepcionesPorProveedor() {
    $recepcion = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion();
    return $recepcion->getRecepcionesPorProveedor();
}

$proveedorModel = new Proveedores();
$reporteSuministroProveedores = $proveedorModel->obtenerReporteSuministroProveedores();
$totalSuministrado = array_sum(array_column($reporteSuministroProveedores, 'cantidad'));
$recepcionesPorProveedor = getRecepcionesPorProveedor();

function obtenerProductosConBajoStock() {
    $producto = new Productos();
    return $producto->obtenerProductosConBajoStock();
}

$pagina = "proveedor";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacora = new Bitacora();
        $bitacora->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PROVEEDORES,
            'ACCESAR',
            'El usuario accedió al módulo de Proveedores',
            'media'
        );
    }
    $proveedores = $puedeConsultarProveedores ? getproveedores() : [];
    $productos = obtenerProductosConBajoStock();
    $recepcionesPorProveedor = $puedeConsultarProveedores ? getRecepcionesPorProveedor() : [];
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacora = new Bitacora();
        $bitacora->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PROVEEDORES,
            'ACCESAR',
            'El usuario accedió al módulo de Proveedores',
            'media'
        );
    }
    $proveedores = $puedeConsultarProveedores ? getproveedores() : [];
    $productos = obtenerProductosConBajoStock();
    $recepcionesPorProveedor = $puedeConsultarProveedores ? getRecepcionesPorProveedor() : [];
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
