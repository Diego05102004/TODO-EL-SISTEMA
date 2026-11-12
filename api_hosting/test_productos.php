<?php
/**
 * Archivo de prueba para verificar los endpoints de productos
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/vendor/autoload.php';

use Usuario\ProyectoCasalaiCa\Productos;

echo "<h1>Prueba de Endpoints de Productos</h1>";

// Crear instancia
$producto = new Productos();

// Definir operaciones
$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'obtenerTodosProductos'
    ],
    'por_id' => [
        'method' => 'GET',
        'handler' => 'obtenerProductoPorId'
    ],
    'por_categoria' => [
        'method' => 'GET',
        'handler' => 'obtenerProductosPorCategoria'
    ],
    'detallado' => [
        'method' => 'GET',
        'handler' => 'obtenerProductoDetalladoApi'
    ]
];

echo "<h2>Verificación de métodos en la clase Productos</h2>";
foreach ($operations as $key => $op) {
    $handler = $op['handler'];
    $exists = method_exists($producto, $handler);
    echo "<p><strong>$key</strong> -> handler: <strong>$handler</strong> - ";
    echo $exists ? "<span style='color:green'>✓ EXISTE</span>" : "<span style='color:red'>✗ NO EXISTE</span>";
    echo "</p>";
}

echo "<h2>Prueba de obtenerTodosProductos</h2>";
try {
    $result = $producto->obtenerTodosProductos([]);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Prueba de obtenerProductoPorId (ID=1)</h2>";
try {
    $result = $producto->obtenerProductoPorId(['id' => 1]);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Prueba de obtenerProductosPorCategoria (categoria=11)</h2>";
try {
    $result = $producto->obtenerProductosPorCategoria(['categoria' => 11]);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
