<?php
/**
 * Endpoint para gestión de cuentas bancarias desde la app móvil
 * GET/POST /api/cuentas.php
 * 
 * Funciones disponibles:
 * - default: Consultar cuentas bancarias (GET)
 * 
 * Parámetros:
 * - funcion: Nombre de la función a ejecutar
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/Clases/Cuentabanco.php';

use Usuario\ProyectoCasalaiCa\Cuentabanco;

// Crear instancia de la clase
$cuenta = new Cuentabanco();

// Definir operaciones disponibles
$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'consultarCuentabanco'
    ]
];

// Procesar la petición
RecibirPeticion($cuenta, $operations);
