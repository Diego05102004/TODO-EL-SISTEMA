<?php
/**
 * Endpoint de Registro de Usuario para la API
 * POST /api/registro.php
 * 
 * Body esperado:
 * {
 *   "nombre_usuario": "nombre_usuario",
 *   "clave": "contraseña",
 *   "nombre": "nombre",
 *   "apellido": "apellido",
 *   "correo": "correo@ejemplo.com",
 *   "telefono": "telefono",
 *   "cedula": "cedula",
 *   "direccion": "direccion"
 * }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Clases/Login.php';
require_once __DIR__ . '/Config/Encryption.php';

use Usuario\ProyectoCasalaiCa\Login;
use Usuario\ProyectoCasalaiCa\Config\Encryption;

// Solo permitir método POST
validateMethod(['POST']);

try {
    // Obtener datos de la petición
    $data = getRequestData();
    
    // Desencriptar datos sensibles si están cifrados
    $encryption = new Encryption();
    $sensitiveFields = ['nombre_usuario', 'clave', 'nombre', 'apellido', 'correo', 'telefono', 'cedula', 'direccion'];
    foreach ($sensitiveFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            try {
                // Restaurar espacios en blanco a '+' para Base64
                if (is_string($data[$field])) {
                    $data[$field] = str_replace(' ', '+', $data[$field]);
                }
                $data[$field] = $encryption->decrypt($data[$field]);
            } catch (\Throwable $decryptError) {
                error_log("[REGISTRO] Error descifrando campo '$field': " . $decryptError->getMessage());
                errorResponse('No se pudieron descifrar los datos de registro', 400);
            }
        }
    }
    
    // Validar datos requeridos (después de desencriptar)
    $camposRequeridos = ['nombre_usuario', 'clave', 'nombre', 'apellido', 'correo', 'telefono', 'cedula'];
    foreach ($camposRequeridos as $campo) {
        if (empty($data[$campo])) {
            errorResponse("El campo $campo es obligatorio", 400);
        }
    }
    
    // Validaciones básicas de longitud (después de desencriptar)
    if (mb_strlen($data['nombre_usuario']) < 4 || mb_strlen($data['nombre_usuario']) > 20) {
        errorResponse('El nombre de usuario debe tener entre 4 y 20 caracteres', 400);
    }
    
    if (mb_strlen($data['clave']) < 6) {
        errorResponse('La contraseña debe tener al menos 6 caracteres', 400);
    }
    
    if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
        errorResponse('El formato del correo electrónico no es válido', 400);
    }
    
    // Crear instancia de Login
    $login = new Login();
    
    // Validar datos de entrada
    $datosValidacion = [
        'nombre_usuario' => $data['nombre_usuario'],
        'clave' => $data['clave'],
        'nombre' => $data['nombre'],
        'apellido' => $data['apellido'],
        'correo' => $data['correo'],
        'telefono' => $data['telefono'],
        'cedula' => $data['cedula'],
        'direccion' => $data['direccion'] ?? ''
    ];
    
    $errores = $login->validarRegistroUsuarioDatos($datosValidacion);
    
    if (!empty($errores)) {
        errorResponse('Error de validación', 400, $errores);
    }
    
    // Registrar usuario
    $resultado = $login->registrarUsuarioYCliente($datosValidacion);
    
    if ($resultado['status'] == 'success') {
        successResponse([], $resultado['mensaje']);
    } else {
        errorResponse($resultado['mensaje'], 400);
    }
    
} catch (Exception $e) {
    errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
}
