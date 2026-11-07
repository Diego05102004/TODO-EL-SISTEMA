<?php
/**
 * Script CLI para destruir el token JWT y la sesión
 * 
 * Uso: php destroy_jwt_token.php
 * 
 * Este script es útil para probar el comportamiento del sistema
 * cuando el token JWT expira o es inválido.
 */

echo "========================================\n";
echo "  Script para destruir token JWT\n";
echo "========================================\n\n";

// Configurar sesión para CLI
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_trans_sid', '0');

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✓ Sesión iniciada\n";
} else {
    echo "✓ Sesión ya estaba activa\n";
}

// Mostrar estado antes de destruir
echo "\n--- Estado antes de destruir ---\n";
$cookieName = 'jwt_token';
$tokenBefore = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : null;
echo "Token JWT en cookie: " . ($tokenBefore ? "EXISTS" : "NOT FOUND") . "\n";
echo "Sesión ID: " . (session_id() ?: "NO SESSION") . "\n";
echo "Datos en sesión: " . (empty($_SESSION) ? "VACÍA" : "CON DATOS") . "\n";

// Destruir la sesión
$_SESSION = [];
session_destroy();
echo "✓ Sesión destruida\n";

// Simular eliminación de cookie del token JWT (para CLI solo mostramos el mensaje)
unset($_COOKIE[$cookieName]);
echo "✓ Cookie del token JWT eliminada (simulado en CLI)\n";

// Mostrar estado después de destruir
echo "\n--- Estado después de destruir ---\n";
$tokenAfter = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : null;
echo "Token JWT en cookie: " . ($tokenAfter ? "EXISTS" : "NOT FOUND") . "\n";
echo "Sesión ID: " . (session_id() ?: "NO SESSION") . "\n";
echo "Datos en sesión: " . (empty($_SESSION) ? "VACÍA" : "CON DATOS") . "\n";

echo "\n========================================\n";
echo "✓ Token JWT y sesión destruidos correctamente\n";
echo "========================================\n\n";
echo "NOTA: En CLI no se pueden eliminar cookies reales del navegador.\n";
echo "Para probar completamente, debes:\n";
echo "1. Ejecutar este script para destruir la sesión del servidor\n";
echo "2. Limpiar las cookies del navegador manualmente\n";
echo "3. Navegar al módulo de bitácora para probar el comportamiento\n";
echo "   cuando el token ha expirado.\n\n";
