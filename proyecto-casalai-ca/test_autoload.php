<?php
// Script para verificar si el autoload está funcionando
require_once 'vendor/autoload.php';

echo "Verificando autoload...\n\n";

// Verificar si la clase Firebase\JWT\JWT existe
echo "Firebase\JWT\JWT: " . (class_exists('Firebase\JWT\JWT') ? '✓ EXISTE' : '✗ NO EXISTE') . "\n";

// Verificar si la clase Auth existe
echo "Usuario\ProyectoCasalaiCa\Config\Auth: " . (class_exists('Usuario\ProyectoCasalaiCa\Config\Auth') ? '✓ EXISTE' : '✗ NO EXISTE') . "\n";

// Intentar cargar la clase Auth
try {
    $auth = new \Usuario\ProyectoCasalaiCa\Config\Auth();
    echo "Auth cargada correctamente\n";
} catch (Exception $e) {
    echo "Error al cargar Auth: " . $e->getMessage() . "\n";
}
