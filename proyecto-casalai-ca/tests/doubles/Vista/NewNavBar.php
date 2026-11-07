<?php
// Stub de NewNavBar para pruebas. Evita dependencias y salidas HTML pesadas.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variables mínimas usadas en vistas
$_SESSION['id_rol'] = $_SESSION['id_rol'] ?? 1;
$_SESSION['id_usuario'] = $_SESSION['id_usuario'] ?? 1;
$_SESSION['name'] = $_SESSION['name'] ?? 'Tester';

// Tasa BCV simulada
if (!class_exists('DolarService')) {
    class DolarService {
        public function obtenerRegistroDelDia(): array { return ['precio' => 40.0, 'fecha' => date('Y-m-d H:i:s')]; }
        public function obtenerPrecioDelDia(): float { return 40.0; }
    }
}
$dolarService = new DolarService();
$registroDolar = $dolarService->obtenerRegistroDelDia();
$tasaBCV = isset($registroDolar['precio']) ? (float)$registroDolar['precio'] : $dolarService->obtenerPrecioDelDia();
$tasaBCVFormateada = number_format($tasaBCV, 2);
$tasaFechaFormateada = isset($registroDolar['fecha']) ? date('d/m/Y H:i', strtotime($registroDolar['fecha'])) : date('d/m/Y H:i');

// No imprimir HTML en pruebas
return;
