<?php
/**
 * Endpoint de Perfil de Usuario para la API
 * GET/POST /api/perfil.php
 * 
 * Operaciones disponibles:
 * - default: Obtener datos del usuario por ID (GET)
 * - editar_personal: Actualizar información personal (POST)
 * - cambiar_password: Cambiar contraseña (POST)
 * - cambiar_avatar: Cambiar foto de perfil (POST)
 * 
 * Body esperado para editar_personal:
 * {
 *   "id_usuario": ID del usuario,
 *   "username": "nuevo_username",
 *   "nombres": "nuevo_nombre",
 *   "apellidos": "nuevo_apellido",
 *   "telefono": "nuevo_telefono",
 *   "clave_actual": "contraseña_actual"
 * }
 * 
 * Body esperado para cambiar_password:
 * {
 *   "id_usuario": ID del usuario,
 *   "clave_actual": "contraseña_actual",
 *   "clave_nueva": "nueva_contraseña",
 *   "clave_confirmar": "confirmar_contraseña"
 * }
 * 
 * Body esperado para cambiar_avatar:
 * {
 *   "id_usuario": ID del usuario,
 *   "clave_actual": "contraseña_actual",
 *   "foto_perfil": archivo de imagen
 * }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/Clases/Usuarios.php';

use Usuario\ProyectoCasalaiCa\Usuarios;

// Crear instancia de la clase
$usuario = new Usuarios();

// Definir operaciones disponibles
$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'obtenerPerfil'
    ],
    'cambiar_personal' => [
        'method' => 'POST',
        'handler' => 'cambiarPersonal'
    ],
    'cambiar_password' => [
        'method' => 'POST',
        'handler' => 'cambiarPassword'
    ],
    'cambiar_avatar' => [
        'method' => 'POST',
        'handler' => 'cambiarAvatar'
    ],
    'cambiar_correo' => [
        'method' => 'POST',
        'handler' => 'cambiarCorreo'
    ]
];

// Procesar la petición
RecibirPeticion($usuario, $operations, 'self');
