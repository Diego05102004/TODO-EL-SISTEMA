<?php  
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;


$facturasReporte = [];
$detallesFacturasReporte = [];
$errorFacturasReporte = false;

try {
    $facturaModel = new Factura();
    $resumenFacturas = $facturaModel->obtenerFacturasListado();
    $facturasReporte = $resumenFacturas['facturas'] ?? [];
    $detallesFacturasReporte = $resumenFacturas['detalleFacturas'] ?? [];
    $idsFacturas = array_map('intval', array_column($facturasReporte, 'id_factura'));
    $montosFacturas = $facturaModel->obtenerMontosTotalesFacturas($idsFacturas);

    foreach ($facturasReporte as &$facturaReporte) {
        $idFactura = (int)($facturaReporte['id_factura'] ?? 0);
        $facturaReporte['total_final'] = $montosFacturas[$idFactura] ?? 0;
    }
    unset($facturaReporte);
} catch (Throwable $e) {
    error_log('Error al cargar reportes de facturas de ventas: ' . $e->getMessage());
    $facturasReporte = [];
    $detallesFacturasReporte = [];
    $errorFacturasReporte = true;
}

$despachoEstado = [];
$despachoMes = [];
$despachoCliente = [];
$despachoTipoCompra = [];
$despachos = [];
$errorDespachosReporte = false;

try {
    $reporteDespacho = new Despacho();
    $despachoEstado = $reporteDespacho->getDespachosEstado();
    $despachoMes = $reporteDespacho->getProductosDespachadosPorMes();
    $despachoCliente = $reporteDespacho->getDespachosPorCliente();
    $despachoTipoCompra = $reporteDespacho->getDespachosPorTipoCompra();
    $despachos = $reporteDespacho->getdespacho();

    if (isset($despachoEstado['error'])) {
        error_log('Error al cargar el reporte de despachos por estado: ' . ($despachoEstado['mensaje'] ?? 'Error no especificado'));
        $despachoEstado = [];
        $errorDespachosReporte = true;
    }
    if (isset($despachoMes['error'])) {
        error_log('Error al cargar el reporte mensual de despachos: ' . ($despachoMes['mensaje'] ?? 'Error no especificado'));
        $despachoMes = [];
        $errorDespachosReporte = true;
    }
    if (isset($despachoCliente['error'])) {
        error_log('Error al cargar el reporte de despachos por cliente: ' . ($despachoCliente['mensaje'] ?? 'Error no especificado'));
        $despachoCliente = [];
        $errorDespachosReporte = true;
    }
    if (isset($despachoTipoCompra['error'])) {
        error_log('Error al cargar el reporte por tipo de compra: ' . ($despachoTipoCompra['mensaje'] ?? 'Error no especificado'));
        $despachoTipoCompra = [];
        $errorDespachosReporte = true;
    }
} catch (Throwable $e) {
    error_log('Error al cargar reportes de despachos de ventas: ' . $e->getMessage());
    $despachoEstado = [];
    $despachoMes = [];
    $despachoCliente = [];
    $despachoTipoCompra = [];
    $despachos = [];
    $errorDespachosReporte = true;
}

$totalDespachos = is_array($despachos) ? count($despachos) : 0;



$pagina = "reporteVentas";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>