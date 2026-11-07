<?php

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Clientes;
use Usuario\ProyectoCasalaiCa\ModeloClases\Productos;

$permisosObj = new Permisos();
$permisosUsuario = $permisosObj->getPermisosPorRolModulo();
if (is_file("Vista/gestionarfactura.php")) {
    $factura = new Factura();

    try {
        $resumenFacturas = $factura->obtenerFacturasListado();
        $facturas = $resumenFacturas['facturas'] ?? [];
        $detalleFacturas = $resumenFacturas['detalleFacturas'] ?? [];
    } catch (Throwable $e) {
        error_log('Error al cargar facturas en controlador: ' . $e->getMessage());
        $facturas = [];
        $detalleFacturas = [];
    }
    
    // Cargar clientes y productos para el formulario de creación
    try {
        $clientesModel = new Clientes();
        $clientes = $clientesModel->obtenerClientes();
        
        $productosModel = new Productos();
        $productos = $productosModel->obtenerProductoStock();
    } catch (Throwable $e) {
        error_log('Error al cargar clientes/productos: ' . $e->getMessage());
        $clientes = [];
        $productos = [];
    }
    
    if (isset($_POST['descargarFactura'])) {
    $id_factura = $_POST['descargarFactura'];
    $factura->setId($id_factura);
    $res = $factura->facturaTransaccion('DescargarFactura');
    
    // Debug: verificar si $res tiene datos
    if (empty($res)) {
        die("Error: No se encontraron datos para la factura ID: $id_factura");
    }
    
    require_once("Vista/descargarfactura.php");
    exit; // para evitar que se ejecute el resto
}

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        header('Content-Type: application/json; charset=utf-8');

        // Obtiene la acción enviada en la solicitud POST
        if (isset($_POST['accion'])) {
            $accion = $_POST['accion'];
        } else {
            $accion = 'consultar';
        }

        switch ($accion) {

            case 'registrar':
                // Llamada al método para registrar una factura
                $fecha = $_POST['fecha'] ?? '';
                $cliente = $_POST['cliente'] ?? '';
                $descuento = $_POST['descuento'] ?? 0;
                $estatus = $_POST['estatus'] ?? '';
                $id_producto = $_POST['id_producto'] ?? [];
                $cantidad = $_POST['cantidad'] ?? [];

                $errores = [];

                if (empty($fecha)) {
                    $errores[] = 'La fecha de la factura es obligatoria.';
                }
                if (empty($cliente)) {
                    $errores[] = 'El cliente de la factura es obligatorio.';
                }
                if (empty($estatus)) {
                    $errores[] = 'El estatus de la factura es obligatorio.';
                }

                if (!is_array($id_producto) || !is_array($cantidad)) {
                    $errores[] = 'Los productos y cantidades deben enviarse como listas.';
                } elseif (count($id_producto) === 0) {
                    $errores[] = 'Debe agregar al menos un producto a la factura.';
                } elseif (count($id_producto) !== count($cantidad)) {
                    $errores[] = 'La cantidad de productos no coincide con las cantidades indicadas.';
                } else {
                    foreach ($id_producto as $index => $idProdRaw) {
                        $idProd = (int)$idProdRaw;
                        $cant = isset($cantidad[$index]) ? (int)$cantidad[$index] : 0;

                        if ($idProd <= 0) {
                            $errores[] = 'El producto en la posición ' . ($index + 1) . ' no tiene un ID válido.';
                        }
                        if ($cant <= 0) {
                            $errores[] = 'La cantidad del producto en la posición ' . ($index + 1) . ' no es válida.';
                        }
                    }
                }

                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => implode(' ', $errores)
                    ]);
                    break;
                }

                $factura->setFecha($fecha);
                $factura->setCliente($cliente);
                $factura->setDescuento($descuento);
                $factura->setEstatus($estatus);
                $factura->setIdProducto($id_producto);
                $factura->setCantidad($cantidad);
                
                $respuesta = $factura->facturaTransaccion('Ingresar');

                if (is_array($respuesta) && isset($respuesta['error'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $respuesta['error']
                    ]);
                } elseif ($respuesta === true) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Factura registrada correctamente.',
                        'resultado' => 'registrar'
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error desconocido al registrar la factura.'
                    ]);
                }
                break;


            case 'cancelar':
                // Cancelar factura por ID
                $id_factura_raw = $_POST['id_factura'] ?? null;
                $id_factura = is_numeric($id_factura_raw) ? (int)$id_factura_raw : 0;

                if ($id_factura <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de factura no válido.'
                    ]);
                    break;
                }

                $factura->setId($id_factura);
                if ($factura->facturaTransaccion('Cancelar')) {
                    echo json_encode(['status' => 'success', 'message' => 'Factura cancelada correctamente.']);
                    $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], 'Pedidos', 'CANCELAR', 'El usuario canceló la factura con ID: ' . $id_factura, 'media');
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al cancelar la factura.']);
                }

                break;

            default:
            // Consultar facturas  
            if ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'Almacenista' || $_SESSION['nombre_rol'] == 'SuperUsuario') {
              $respuesta = $factura->facturaTransaccion('ConsultarTodas');
            echo json_encode($respuesta);  
            exit;
            }else {
            if (!isset($_SESSION['cedula'])) {
                echo json_encode([
                    'resultado' => 'error',
                    'mensaje' => 'No se encontró la cédula del cliente en la sesión.'
                ]);
                break;
            }
            $factura->setCedula($_SESSION['cedula']); // Asegurarse de que cedula esté definida
            $respuesta = $factura->facturaTransaccion('Consultar');
            echo json_encode($respuesta);
            
            };

                break;
        }
        exit;
    }

    

    // Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/gestionarfactura.php")) {
    require_once("Vista/VistaNew/gestionarfactura.php");
} elseif (is_file("Vista/gestionarfactura.php")) {
    require_once("Vista/gestionarfactura.php");
} else {
    echo "Página en construcción";
}
        if (isset($_SESSION['id_usuario'])) {
$bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
    $_SESSION['id_usuario'],
    'Pedidos',
    'ACCESAR',
    'El usuario accedió al módulo de Pedidos',
    'media'
);}
} else {
    echo "Página en construcción";
}

?>