<?php
// Archivo de prueba para depurar problemas de sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Depuración de Sesión y JWT</h1>";

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Estado de la Sesión PHP:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "</pre>";

echo "<h2>Cookies:</h2>";
echo "<pre>";
echo "Cookies: " . print_r($_COOKIE, true) . "\n";
echo "</pre>";

echo "<h2>Estado del Token JWT:</h2>";
require_once __DIR__ . '/Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

$token = Auth::getToken();
echo "Token presente: " . ($token ? "SÍ" : "NO") . "\n";

if ($token) {
    echo "Token (primeros 50 caracteres): " . substr($token, 0, 50) . "...\n";
    
    $tokenState = Auth::inspectToken($token);
    echo "Estado del token: " . $tokenState['status'] . "\n";
    echo "Tiempo restante: " . $tokenState['expires_in'] . " segundos\n";
    
    if ($tokenState['payload']) {
        echo "Payload del token: " . print_r($tokenState['payload'], true) . "\n";
    }
}

echo "<h2>Configuración de PHP:</h2>";
echo "<pre>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "</pre>";

echo "<h2>Prueba de navegación:</h2>";
echo "<a href='index.php?pagina=login'>Ir al Login</a><br>";
echo "<a href='index.php?pagina=dashboard'>Ir al Dashboard</a>";
?>