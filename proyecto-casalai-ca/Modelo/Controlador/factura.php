<?php

if (!is_file("Modelo/factura.php")) {
 
    echo "Falta definir la clase " . $pagina;
    exit;
}

require_once("Modelo/factura.php");
require_once("Modelo/producto.php");

if (is_file("Vista/factura.php")) {

    if (!empty($_POST)) {

        $factura = new Factura();
        $accion = $_POST['accion'];
        
        switch ($accion) {
            case 'consultar':
                // Validar datos de entrada
                $datosValidacion = [
                    'id_factura' => $_POST['id_factura'] ?? null,
                    'cedula' => $_POST['cedula'] ?? null,
                    'limite' => $_POST['limite'] ?? null,
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                    'fecha_fin' => $_POST['fecha_fin'] ?? null,
                    'estatus' => $_POST['estatus'] ?? null
                ];
                
                $errores = $factura->validarConsultarFacturas($datosValidacion);
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos inválidos',
                        'errors' => $errores
                    ]);
                    break;
                }
                
                // Aquí iría la lógica de consulta
                echo json_encode(['status' => 'success', 'message' => 'Validación exitosa']);
                break;
                
            case 'cancelar':
                // Validar datos de entrada
                $datosValidacion = [
                    'id_factura' => $_POST['id_factura'] ?? null,
                    'motivo_cancelacion' => $_POST['motivo_cancelacion'] ?? null
                ];
                
                $errores = $factura->validarCancelarFactura($datosValidacion);
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos inválidos',
                        'errors' => $errores
                    ]);
                    break;
                }
                
                // Aquí iría la lógica de cancelación
                echo json_encode(['status' => 'success', 'message' => 'Validación exitosa']);
                break;
                
            case 'listadoproductos':
                $o = new Producto();
                $respuesta = $o->listadoproductos();
                echo json_encode($respuesta);
                break;
                
            case 'registrar':
                /*$respuesta = $o->registrar($_POST['cliente'], $_POST['idp'], $_POST['cant'], $_POST['correlativo']);
                echo json_encode($respuesta);*/
                break;
                
            default:
                echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
                break;
        }
        exit;
    }

    require_once("Vista/factura.php");

} else {
    echo "pagina en construccion";
}
?>
