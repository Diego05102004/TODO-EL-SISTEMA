<?php
use Usuario\ProyectoCasalaiCa\Config\BD;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar el autoloader de composer
require_once __DIR__ . '/../vendor/autoload.php';

// Configurar variables de sesión para pruebas
$_SESSION['id_usuario'] = $_SESSION['id_usuario'] ?? 1;
$_SESSION['id_rol'] = $_SESSION['id_rol'] ?? 1; // Ajusta si necesitas otro rol

date_default_timezone_set('America/Caracas');

$projectRoot = __DIR__ . DIRECTORY_SEPARATOR . '..';
$testsDoubles = __DIR__ . DIRECTORY_SEPARATOR . 'doubles';
// Prioriza stubs/doubles en pruebas, antes que el código de producción
set_include_path($testsDoubles . PATH_SEPARATOR . $projectRoot . PATH_SEPARATOR . get_include_path());

// Evitar efectos secundarios (inclusión de vistas, redirecciones) en controladores durante pruebas
if (!defined('SKIP_SIDE_EFFECTS')) {
    define('SKIP_SIDE_EFFECTS', true);
}

// Nota: El stub de DolarService está en tests/doubles/Modelo/DolarService.php y se cargará
// gracias a include_path que antepone tests/doubles.
if (!class_exists('DolarService')) {
    $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'doubles' . DIRECTORY_SEPARATOR . 'Modelo' . DIRECTORY_SEPARATOR . 'DolarService.php';
    if (is_file($stubPath)) {
        require_once $stubPath;
    }
}

function test_pdo(): PDO {
    $bd = new BD('P');
    return $bd->getConexion();
}

function truncate_tablas_basicas(PDO $pdo): void {
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
    try { $pdo->exec('TRUNCATE TABLE tbl_modelos'); } catch (Throwable $e) {}
    try { $pdo->exec('TRUNCATE TABLE tbl_marcas'); } catch (Throwable $e) {}
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
}