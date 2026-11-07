<?php
/**
 * Endpoint de Registro de Cliente para la API
 * POST /api/registrarCliente.php
 * 
 * Registra un nuevo usuario cliente en:
 * - Base de datos de seguridad (tbl_usuarios)
 * - Base de datos principal (tbl_clientes)
 * 
 * Body esperado:
 * {
 *   "username": "nombre_usuario",
 *   "password": "contraseña",
 *   "nombres": "Nombres del cliente",
 *   "apellidos": "Apellidos del cliente",
 *   "cedula": "12345678",
 *   "correo": "cliente@email.com",
 *   "telefono": "0416-123-4567",
 *   "direccion": "Dirección del cliente"
 * }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/Clases/Usuarios.php';
require_once __DIR__ . '/Clases/Cliente.php';

use Usuario\ProyectoCasalaiCa\Usuarios;
use Usuario\ProyectoCasalaiCa\Cliente;

// Crear instancias de las clases
$usuario = new Usuarios();
$cliente = new Cliente();

// Definir operaciones disponibles
$operations = [
    'default' => [
        'method' => 'POST',
        'handler' => 'registrarCliente'
    ]
];

// Procesar la petición
RecibirPeticion($usuario, $operations);
