<?php  
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permiso;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

$reporteDespacho = new Despacho();
$despachoEstado = $reporteDespacho->getDespachosEstado();
$despachoMes = $reporteDespacho->getProductosDespachadosPorMes();
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


$r = new Recepcion();
$RecepcionesProveedor = $r->getRecepcionesPorProveedor();
$ProductorRecibidos = $r->getProductosMasRecibidos();
$RecepcionMensual = $r->getRecepcionesMensuales();


$pagina = "reporteInventario";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>