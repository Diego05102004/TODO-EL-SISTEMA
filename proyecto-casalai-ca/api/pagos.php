<?php
/**
 * Endpoint para gestión de pagos desde la app móvil
 * GET/POST /api/pagos.php
 *
 * Funciones disponibles:
 * - default: Consultar pagos (GET)
 * - pago_ingresar: Registrar un pago (POST)
 *
 * Parámetros:
 * - funcion: Nombre de la función a ejecutar
 * - factura: ID de la factura (para pago_ingresar)
 * - cuenta: ID de la cuenta bancaria (para pago_ingresar)
 * - monto: Monto del pago (para pago_ingresar)
 * - tipo: Tipo de pago (para pago_ingresar)
 * - referencia: Referencia del pago (opcional)
 * - observaciones: Observaciones (opcional)
 * - comprobante: Comprobante del pago (archivo multipart/form-data o base64)
 * - fecha: Fecha del pago (opcional, usa fecha actual por defecto)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/Clases/PasareladePago.php';

use Usuario\ProyectoCasalaiCa\PasareladePago;

// Crear instancia de la clase
$pasarela = new PasareladePago();

// Definir operaciones disponibles
$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'pagoConsultar'
    ],
    'pago_ingresar' => [
        'method' => 'POST',
        'handler' => 'pagoIngresarMovil'
    ]
];

// Procesar la petición
RecibirPeticion($pasarela, $operations, 'client');
