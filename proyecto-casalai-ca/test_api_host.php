<?php
/**
 * Script para probar la API en el host
 * Ejecuta este archivo en el host para verificar si la API funciona
 */

// Configuración
$apiBaseUrl = 'https://casalai.infinityfree.me/api';

echo "<h1>Prueba de API - Casa Lai</h1>";
echo "<h2>URL Base: $apiBaseUrl</h2>";

// Test 1: Probar si la API responde
echo "<h3>Test 1: Verificar si la API responde</h3>";
$testUrl = $apiBaseUrl . '/login.php';
echo "URL: $testUrl<br>";

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'Diego', 'password' => 'test123@']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($error) {
    echo "Error cURL: $error<br>";
}
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><hr>";

// Test 2: Probar endpoint de registro
echo "<h3>Test 2: Probar endpoint de registro</h3>";
$testUrl = $apiBaseUrl . '/registro.php';
echo "URL: $testUrl<br>";

$uniqueCedula = strval(rand(10000000, 99999999));
$registroData = [
    'nombre_usuario' => 'test_usuario_' . $uniqueCedula,
    'clave' => 'Test123@',
    'nombre' => 'Test',
    'apellido' => 'Usuario',
    'correo' => 'test_' . $uniqueCedula . '@test.com',
    'telefono' => '04141234567',
    'cedula' => $uniqueCedula,
    'direccion' => 'Dirección test'
];

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registroData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($error) {
    echo "Error cURL: $error<br>";
}
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><hr>";

// Test 3: Verificar si hay conexión a base de datos
echo "<h3>Test 3: Verificar conexión a base de datos</h3>";
try {
    require_once __DIR__ . '/Modelo/Config/database.php';
    require_once __DIR__ . '/Modelo/Config/Config.php';
    
    $bd = new \Usuario\ProyectoCasalaiCa\Config\BD('S');
    $pdo = $bd->getConexion();
    
    if ($pdo) {
        echo "✅ Conexión a base de datos exitosa<br>";
        
        // Verificar si hay usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_usuarios");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de usuarios en base de datos: " . $result['total'] . "<br>";
        
        // Mostrar algunos usuarios (sin passwords)
        $stmt = $pdo->query("SELECT id_usuario, username, correo, estatus FROM tbl_usuarios LIMIT 5");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<br>Usuarios existentes:<br>";
        echo "<pre>";
        print_r($usuarios);
        echo "</pre>";
    } else {
        echo "❌ No se pudo conectar a la base de datos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Recomendaciones:</h3>";
echo "<ul>";
echo "<li>Si el Test 1 y 2 fallan con error de conexión, verifica que el host permite conexiones HTTPS externas</li>";
echo "<li>Si el HTTP Code es 500, hay un error en el servidor - revisa los logs de error</li>";
echo "<li>Si el Test 3 falla, el problema es la conexión a base de datos en el host</li>";
echo "<li>Si no hay usuarios en la base de datos, necesitas crear uno primero</li>";
echo "</ul>";
