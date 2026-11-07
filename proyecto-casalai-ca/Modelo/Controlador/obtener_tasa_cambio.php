<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;

$dolarService = new DolarService();

// Obtener el registro del día
$registroDolar = $dolarService->obtenerRegistroDelDia();

// Preparar la respuesta
$response = [
    'success' => false,
    'tasa' => null,
    'actualizado' => date('d/m/Y H:i')
];

if ($registroDolar && isset($registroDolar['precio'])) {
    $response['success'] = true;
    $response['tasa'] = number_format((float)$registroDolar['precio'], 2, ',', '.');
    $response['actualizado'] = date('d/m/Y H:i', strtotime($registroDolar['fecha']));
} else {
    // Si no hay registro del día, intentar obtener el precio del día
    $precioDia = $dolarService->obtenerPrecioDelDia();
    if ($precioDia !== null) {
        $response['success'] = true;
        $response['tasa'] = number_format($precioDia, 2, ',', '.');
    }
}

echo json_encode($response);
?>