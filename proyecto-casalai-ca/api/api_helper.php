<?php
/**
 * Helper para la API - Funciones reutilizables para endpoints
 * Proporciona la función RecibirPeticion() para manejar solicitudes de la app móvil
 */

require_once __DIR__ . '/Config/Encryption.php';
require_once __DIR__ . '/auth.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;
use Usuario\ProyectoCasalaiCa\Config\BD;

function apiRequireAuthContext() {
    $payload = Auth::checkAuth();
    $userId = is_array($payload)
        ? filter_var($payload['user_id'] ?? null, FILTER_VALIDATE_INT)
        : false;

    if ($userId === false || $userId < 1) {
        errorResponse('Debes iniciar sesión para acceder a este recurso.', 401);
    }

    $userData = is_array($payload['data'] ?? null) ? $payload['data'] : [];
    $role = strtolower(trim((string) ($userData['nombre_rol'] ?? '')));

    return [
        'user_id' => (int) $userId,
        'user_data' => $userData,
        'is_admin' => (bool) preg_match('/\b(admin|administrador|super\s?usuario)\b/i', $role),
    ];
}

function apiResolveClientId(\PDO $principalPdo, int $userId): int {
    $securityDatabase = new BD('S');

    try {
        $userStatement = $securityDatabase->getConexion()->prepare(
            'SELECT cedula
             FROM tbl_usuarios
             WHERE id_usuario = :id_usuario
             LIMIT 1'
        );
        $userStatement->execute([':id_usuario' => $userId]);
        $userIdentityNumber = $userStatement->fetchColumn();
    } finally {
        $securityDatabase->cerrar();
    }

    if (!is_string($userIdentityNumber) || trim($userIdentityNumber) === '') {
        errorResponse('No se encontró una identidad válida para el usuario autenticado.', 403);
    }

    $encryption = new Encryption();
    $normalizeIdentity = static function ($value) {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    };
    $normalizeStoredIdentity = static function ($value) use ($encryption, $normalizeIdentity) {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        $value = trim($value);
        if (strlen($value) > 50) {
            try {
                $decryptedValue = $encryption->decrypt(str_replace(' ', '+', $value));
            } catch (\Throwable $e) {
                return '';
            }

            if (!is_string($decryptedValue) || $decryptedValue === $value) {
                return '';
            }
            $value = $decryptedValue;
        }

        return $normalizeIdentity($value);
    };
    $expectedIdentity = $normalizeStoredIdentity($userIdentityNumber);
    if ($expectedIdentity === '') {
        errorResponse('No se encontró una identidad válida para el usuario autenticado.', 403);
    }

    $clientStatement = $principalPdo->query(
        "SELECT id_clientes, cedula
         FROM tbl_clientes
         WHERE cedula IS NOT NULL AND TRIM(cedula) <> ''"
    );
    $matchingClientIds = [];

    foreach ($clientStatement->fetchAll(\PDO::FETCH_ASSOC) as $client) {
        if ($normalizeStoredIdentity($client['cedula'] ?? null) === $expectedIdentity) {
            $matchingClientIds[] = (int) $client['id_clientes'];
        }
    }

    if (count($matchingClientIds) !== 1) {
        errorResponse(
            count($matchingClientIds) === 0
                ? 'No existe un cliente asociado al usuario autenticado.'
                : 'La identidad está asociada a más de un cliente.',
            count($matchingClientIds) === 0 ? 403 : 409
        );
    }

    return $matchingClientIds[0];
}

function apiAssertInvoiceAccess($invoiceId, int $clientId): void {
    if (!is_numeric($invoiceId) || (int) $invoiceId < 1) {
        errorResponse('El identificador de factura no es válido.', 400);
    }

    $database = new BD('P');
    try {
        $statement = $database->getConexion()->prepare(
            'SELECT 1
             FROM tbl_facturas
             WHERE id_factura = :id_factura AND cliente = :id_cliente
             LIMIT 1'
        );
        $statement->execute([
            ':id_factura' => (int) $invoiceId,
            ':id_cliente' => $clientId,
        ]);
        if ($statement->fetchColumn() === false) {
            errorResponse('Factura no encontrada.', 404);
        }
    } finally {
        $database->cerrar();
    }
}

function apiAuthorizeRequest($instance, array &$data, $policy): void {
    if ($policy === null) {
        return;
    }

    $context = apiRequireAuthContext();

    if ($policy === 'self') {
        if (isset($data['id_usuario']) && (int) $data['id_usuario'] !== $context['user_id']) {
            errorResponse('No tienes permiso para acceder a ese usuario.', 403);
        }
        $data['id_usuario'] = $context['user_id'];
        return;
    }

    if ($policy !== 'client') {
        throw new RuntimeException('Política de autorización de API no reconocida.');
    }

    $clientId = null;
    if (!$context['is_admin']) {
        $database = new BD('P');
        try {
            $clientId = apiResolveClientId($database->getConexion(), $context['user_id']);
        } finally {
            $database->cerrar();
        }

        $identity = $context['user_data']['cedula']
            ?? $context['user_data']['cedula_usuario']
            ?? null;
        if (!is_string($identity) || trim($identity) === '') {
            errorResponse('No se encontró una identidad válida para el usuario autenticado.', 403);
        }
        $data['cedula'] = $identity;
        $data['cliente'] = $identity;
        $data['_authenticated_client_id'] = $clientId;
    }

    if (method_exists($instance, 'setAuthenticatedClient')) {
        $instance->setAuthenticatedClient($clientId, $context['is_admin']);
    }

    if (!$context['is_admin']) {
        foreach (['id_factura', 'factura', 'id'] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                apiAssertInvoiceAccess($data[$field], $clientId);
            }
        }
    }
}

function apiDecryptResponseData($value, Encryption $encryption, string $fieldName = '') {
    $encryptedFields = [
        'username',
        'nombre_usuario',
        'nombre',
        'nombres',
        'apellido',
        'apellidos',
        'correo',
        'email',
        'correo_cuenta',
        'telefono',
        'telefono_cuenta',
        'phone',
        'direccion',
        'address',
        'cedula',
        'cedula_usuario',
        'documento',
        'nombre_banco',
        'nombre_cuenta',
        'nombre_cliente',
    ];

    if (is_array($value)) {
        foreach ($value as $key => $nestedValue) {
            $value[$key] = apiDecryptResponseData($nestedValue, $encryption, (string) $key);
        }
        return $value;
    }

    if (!is_string($value) || $value === '' || !in_array(strtolower($fieldName), $encryptedFields, true)) {
        return $value;
    }

    try {
        return $encryption->decryptIfEncrypted($value);
    } catch (\Throwable $error) {
        error_log('[API] No se pudo descifrar un campo de respuesta: ' . strtolower($fieldName));
        throw new RuntimeException('No se pudo preparar la respuesta de la API.');
    }
}

/**
 * Función principal para recibir y procesar peticiones de la app móvil
 * Detecta la función solicitada e invoca el método correspondiente de la clase
 * 
 * @param object $instance Instancia de la clase del modelo
 * @param array $operations Array asociativo con las operaciones permitidas
 *                          Formato: ['nombre_funcion' => ['method' => 'GET|POST|PUT|DELETE', 'handler' => 'nombre_metodo_clase']]
 * 
 * @return void
 */
function RecibirPeticion($instance, $operations, $authorizationPolicy = null) {
    try {
        $requestData = getRequestData();

        // Obtener el nombre de la función solicitada, soportando JSON, POST y GET
        $funcion = $requestData['funcion'] ?? $_GET['funcion'] ?? $_POST['funcion'] ?? null;
        
        if (!$funcion) {
            // Si no se especifica función, usar la operación por defecto (GET)
            $funcion = 'default';
        }
        
        $funcion = strtolower(trim($funcion));
        
        // Verificar si la operación existe
        if (!isset($operations[$funcion])) {
            errorResponse('Función no reconocida: ' . $funcion, 400);
        }
        
        $operation = $operations[$funcion];
        $method = $operation['method'] ?? 'GET';
        $handler = $operation['handler'] ?? $funcion;
        
        // Validar método HTTP
        validateMethod([$method]);
        
        // Reuse the parsed body because php://input can only be consumed once.
        $data = $requestData;

        // Para GET, también incluir parámetros de URL
        if ($method === 'GET') {
            $data = array_merge($data, $_GET);
        }

        apiAuthorizeRequest($instance, $data, $authorizationPolicy);

        // Desencriptar datos sensibles si están cifrados (para todos los métodos)
        $encryption = new Encryption();
        $sensitiveFields = ['username', 'password', 'nombres', 'apellidos', 'cedula', 'cliente', 'correo', 'telefono', 'direccion', 'nombre', 'apellido', 'clave'];
        // Solo desencriptar en api_helper para operaciones que NO son de factura
        // Las operaciones de factura desencriptan internamente como hace cambiarPersonal
        $isMobileInvoice = in_array($funcion, ['facturaingresarmovil', 'facturaingresar'], true);
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                try {
                    // Solo desencriptar si parece estar encriptado (es largo y tiene caracteres de Base64)
                    $value = $data[$field];
                    if (is_string($value) && strlen($value) > 50 && !preg_match('/^\d+$/', $value)) {
                        // Para operaciones de factura, NO desencriptar aquí - hacerlo en la clase
                        if ($isMobileInvoice && in_array($field, ['cliente', 'cedula'], true)) {
                            continue;
                        }
                        // Restaurar espacios en blanco a '+' para Base64
                        $value = str_replace(' ', '+', $value);
                        $decrypted = $encryption->decrypt($value);
                        if (!is_string($decrypted) || $decrypted === $value || trim($decrypted) === '') {
                            throw new \RuntimeException('El descifrado no produjo un valor válido.');
                        }
                        $data[$field] = trim($decrypted);
                    }
                } catch (\Throwable $decryptError) {
                    error_log("[API_HELPER] Error descifrando campo '$field': " . $decryptError->getMessage());
                    if ($isMobileInvoice && in_array($field, ['cliente', 'cedula'], true)) {
                        continue; // No lanzar error para factura, dejar que la clase lo maneje
                    }
                    errorResponse('No se pudieron descifrar los datos: ' . $decryptError->getMessage(), 400);
                }
            }
        }

        // Solo validar cédula para operaciones de factura que realmente la necesitan
        if ($isMobileInvoice && empty($data['cedula'])) {
            errorResponse('La cédula del cliente es requerida o no pudo descifrarse.', 400);
        }
        // No validar que cliente y cédula coincidan aquí porque se cifran por separado
        // con claves AES aleatorias diferentes. La clase Factura los desencripta y valida.

        // Importante: NO desencriptar el campo 'funcion' ya que se usa para enrutamiento

        // Asegurar que la función también quede en $data para métodos con JSON
        if (!isset($data['funcion']) && isset($funcion)) {
            $data['funcion'] = $funcion;
        }

        // Establecer cédula en la instancia si está presente (para Factura)
        if (isset($data['cedula']) && method_exists($instance, 'setCedula')) {
            $instance->setCedula($data['cedula']);
        }

        // Verificar si el método existe en la instancia
        if (!method_exists($instance, $handler)) {
            errorResponse('Método no implementado: ' . $handler, 501);
        }

        // Invocar el método de la clase
        $resultado = call_user_func([$instance, $handler], $data);
        
        // La app móvil no contiene la clave privada RSA; devolver datos legibles sobre HTTPS.
        $resultado = apiDecryptResponseData($resultado, $encryption);

        // Retornar respuesta exitosa
        successResponse($resultado, 'Operación exitosa');
        
    } catch (Exception $e) {
        error_log('[API] Error al procesar solicitud: ' . $e->getMessage());
        errorResponse('No se pudo procesar la solicitud.', 500);
    }
}
