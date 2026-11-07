# Ejemplos de Implementación JWT en Controladores

Este documento muestra cómo aplicar la clase `Auth` en los controladores para proteger los endpoints según los roles de usuario.

## Requisitos Previos

1. Instalar la dependencia de JWT:
```bash
composer require firebase/php-jwt
```

2. Ejecutar composer dump-autoload para actualizar el autoloader:
```bash
composer dump-autoload
```

## Ejemplo 1: Módulo 19 - Bitácora (Solo Administrador y SuperUsuario)

**Archivo:** `Modelo/Controlador/bitacora.php`

```php
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

// Importar la clase Auth
require_once __DIR__ . '/../Config/Auth.php';

// Verificar autenticación y autorización
$payload = Auth::requireAuth(['Administrador', 'SuperUsuario']);

// Obtener datos del usuario desde el JWT
$userId = Auth::getUserId($payload);
$userRole = Auth::getUserRole($payload);

// Resto del código del controlador
$bitacora = new Bitacora();
$accion = $_POST['accion'] ?? '';

// ... resto del código del controlador
```

## Ejemplo 2: Módulo 22 - Perfil de Usuario (Cualquier usuario autenticado)

**Archivo:** `Modelo/Controlador/perfil.php`

```php
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Perfil;

// Importar la clase Auth
require_once __DIR__ . '/../Config/Auth.php';

// Verificar autenticación (cualquier rol autenticado)
$payload = Auth::requireAuth();

// Obtener ID del usuario desde el JWT para consultar solo sus propios datos
$userId = Auth::getUserId($payload);
$userRole = Auth::getUserRole($payload);

// Resto del código del controlador
$perfil = new Perfil();
$accion = $_POST['accion'] ?? '';

// Ejemplo: Obtener perfil del usuario autenticado
if ($accion === 'obtener_perfil') {
    $datosPerfil = $perfil->obtenerPerfilPorId($userId);
    echo json_encode($datosPerfil);
    exit;
}

// Ejemplo: Actualizar perfil del usuario autenticado
if ($accion === 'actualizar_perfil') {
    $datosActualizados = $_POST;
    $resultado = $perfil->actualizarPerfil($userId, $datosActualizados);
    echo json_encode($resultado);
    exit;
}

// ... resto del código del controlador
```

## Ejemplo 3: Módulo 16 - Finanzas (Solo Administrador y SuperUsuario)

**Archivo:** `Modelo/Controlador/finanza.php`

```php
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Finanza;

// Importar la clase Auth
require_once __DIR__ . '/../Config/Auth.php';

// Verificar autenticación y autorización
$payload = Auth::requireAuth(['Administrador', 'SuperUsuario']);

// Obtener datos del usuario desde el JWT
$userId = Auth::getUserId($payload);
$userRole = Auth::getUserRole($payload);

// Resto del código del controlador
$finanza = new Finanza();
$accion = $_POST['accion'] ?? '';

// ... resto del código del controlador
```

## Ejemplo 4: Módulo 11 - Carrito (Solo rol "cliente")

**Archivo:** `Modelo/Controlador/carrito.php`

```php
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Carrito;

// Importar la clase Auth
require_once __DIR__ . '/../Config/Auth.php';

// Verificar autenticación y autorización (solo clientes)
$payload = Auth::requireAuth(['cliente']);

// Obtener ID del usuario desde el JWT para consultar solo su carrito
$userId = Auth::getUserId($payload);
$userRole = Auth::getUserRole($payload);

// Resto del código del controlador
$carrito = new Carrito();
$accion = $_POST['accion'] ?? '';

// Ejemplo: Obtener carrito del cliente autenticado
if ($accion === 'obtener_carrito') {
    $itemsCarrito = $carrito->obtenerCarritoPorCliente($userId);
    echo json_encode($itemsCarrito);
    exit;
}

// Ejemplo: Agregar producto al carrito del cliente autenticado
if ($accion === 'agregar_producto') {
    $idProducto = $_POST['id_producto'] ?? null;
    $cantidad = $_POST['cantidad'] ?? 1;
    $resultado = $carrito->agregarProducto($userId, $idProducto, $cantidad);
    echo json_encode($resultado);
    exit;
}

// ... resto del código del controlador
```

## Ejemplo 5: Login - Generación de Token JWT

**Archivo:** `Modelo/Controlador/login.php` (o el controlador de autenticación existente)

```php
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use Usuario\ProyectoCasalaiCa\Config\Auth;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuario;

// Importar la clase Auth
require_once __DIR__ . '/../Config/Auth.php';

$usuarioModel = new Usuario();
$accion = $_POST['accion'] ?? '';

if ($accion === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Verificar credenciales
    $usuario = $usuarioModel->verificarCredenciales($username, $password);
    
    if ($usuario) {
        try {
            // Generar token JWT
            $token = Auth::generateToken($usuario['id_usuario'], $usuario['rol']);
            
            // Establecer token en cookie HttpOnly
            Auth::setTokenCookie($token);
            
            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'token' => $token, // Opcional: para aplicaciones móviles
                'user' => [
                    'id' => $usuario['id_usuario'],
                    'username' => $usuario['username'],
                    'rol' => $usuario['rol']
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar token de acceso'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ]);
    }
    exit;
}

if ($accion === 'logout') {
    // Eliminar cookie del token
    Auth::clearTokenCookie();
    
    // Destruir sesión
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logout exitoso'
    ]);
    exit;
}
```

## Ejemplo 6: Verificación de Token en API REST

Para endpoints de API REST que usan el header `Authorization: Bearer <token>`:

```php
<?php
// API endpoint para verificar token
require_once __DIR__ . '/../Config/Auth.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar token (busca en cookie o header Authorization)
$payload = Auth::validateToken();

if (!$payload) {
    Auth::sendAuthError('Token inválido o expirado');
}

// Token válido, retornar información del usuario
echo json_encode([
    'success' => true,
    'user' => [
        'id' => Auth::getUserId($payload),
        'role' => Auth::getUserRole($payload),
        'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
    ]
]);
```

## Notas Importantes

1. **Instalación de dependencia**: Antes de usar, ejecutar:
   ```bash
   composer require firebase/php-jwt
   composer dump-autoload
   ```

2. **Clave secreta**: En producción, cambiar `self::$secretKey` en `Auth.php` por una clave segura almacenada en variables de entorno.

3. **HTTPS en producción**: La cookie `Secure` solo se activará cuando el sitio use HTTPS. En localhost funciona con HTTP por compatibilidad.

4. **Soporte híbrido**: La clase Auth busca el token primero en la cookie (para web) y luego en el header `Authorization: Bearer` (para móvil/API).

5. **Manejo de errores**: Todos los métodos de Auth manejan excepciones internamente y retornan `false` en caso de error, con logging en `error_log`.

6. **Tiempo de expiración**: Configurado en 1 hora (3600 segundos). Se puede modificar cambiando `self::$tokenExpiration` en `Auth.php`.

## Testing

Para probar la autenticación:

1. **Web**: Login normal, el token se almacenará en cookie HttpOnly automáticamente.
2. **Móvil/API**: Login debe retornar el token en la respuesta JSON, y las solicitudes deben incluirlo en el header:
   ```
   Authorization: Bearer <token_jwt>
   ```
