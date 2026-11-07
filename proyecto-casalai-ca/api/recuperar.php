<?php
/**
 * Endpoint de Recuperación de Contraseña para la API
 * POST /api/recuperar.php
 * 
 * Body esperado:
 * {
 *   "email": "correo@ejemplo.com"
 * }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Clases/Login.php';

use Usuario\ProyectoCasalaiCa\Login;

// Solo permitir método POST
validateMethod(['POST']);

try {
    // Obtener datos de la petición
    $data = getRequestData();
    
    // Validar email
    if (empty($data['email'])) {
        errorResponse('El correo electrónico es obligatorio', 400);
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        errorResponse('El formato del correo electrónico no es válido', 400);
    }
    
    // Crear instancia de Login
    $login = new Login();
    
    // Solicitar recuperación
    $resultado = $login->solicitarRecuperacion($data['email']);
    
    if ($resultado['status'] == 'success') {
        // En un entorno real, aquí se enviaría el email con el token
        // Por ahora, devolvemos el token para propósitos de desarrollo
        successResponse([
            'token' => $resultado['token'],
            'id_usuario' => $resultado['id_usuario']
        ], 'Se ha generado un token de recuperación. En producción, se enviaría por correo electrónico.');
    } else {
        errorResponse($resultado['mensaje'], 400);
    }
    
} catch (Exception $e) {
    errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
}
