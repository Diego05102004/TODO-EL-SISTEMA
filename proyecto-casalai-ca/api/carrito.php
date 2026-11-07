<?php
/**
 * Endpoint móvil para consultar y modificar el carrito del usuario autenticado.
 * GET: funcion=consultar
 * POST: funcion=agregar|actualizar|eliminar|vaciar
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Clases/Carrito.php';

use Usuario\ProyectoCasalaiCa\Carrito;

$carrito = new Carrito();

$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'consultarCarrito',
    ],
    'consultar' => [
        'method' => 'GET',
        'handler' => 'consultarCarrito',
    ],
    'agregar' => [
        'method' => 'POST',
        'handler' => 'agregarProductos',
    ],
    'actualizar' => [
        'method' => 'POST',
        'handler' => 'actualizarProducto',
    ],
    'eliminar' => [
        'method' => 'POST',
        'handler' => 'eliminarProducto',
    ],
    'vaciar' => [
        'method' => 'POST',
        'handler' => 'vaciarCarrito',
    ],
];

RecibirPeticion($carrito, $operations);
