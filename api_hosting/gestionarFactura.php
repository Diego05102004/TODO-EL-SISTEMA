<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/Clases/Factura.php';

use Usuario\ProyectoCasalaiCa\Factura;

$factura = new Factura();

$operations = [
    'default' => ['method' => 'GET', 'handler' => 'facturaConsultarMovil'],
    'facturaingresarmovil' => ['method' => 'POST', 'handler' => 'facturaIngresarMovil'],
    'facturaingresar' => ['method' => 'POST', 'handler' => 'facturaIngresarMovil'],
    'facturaanular' => ['method' => 'POST', 'handler' => 'facturaAnular'],
    'facturadescargar' => ['method' => 'GET', 'handler' => 'facturaDescargarMovil'],
    'descargar' => ['method' => 'GET', 'handler' => 'facturaDescargarMovil'],
];

RecibirPeticion($factura, $operations, 'client');