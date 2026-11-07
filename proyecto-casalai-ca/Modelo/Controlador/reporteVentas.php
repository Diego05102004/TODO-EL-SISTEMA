<?php  
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;


$reporteDespacho = new Despacho();
$despachoEstado = $reporteDespacho->getDespachosEstado();
$despachoMes = $reporteDespacho->getProductosDespachadosPorMes();
$despachoCliente = $reporteDespacho->getDespachosPorCliente();
$despachoTipoCompra = $reporteDespacho->getDespachosPorTipoCompra();
$proveedores = $reporteDespacho->obtenercliente();
$productos = $reporteDespacho->consultarproductos();
function getdespacho() {
    $despacho = new Despacho();
    return $despacho->getdespacho();
}

// vista inicial
$despachos = getdespacho();

// Total de despachos
$totalDespachos = count($despachos);



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