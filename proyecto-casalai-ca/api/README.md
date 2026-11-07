# API para Aplicación Móvil Casa Lai

Esta carpeta contiene los endpoints de la API para la aplicación móvil Casa Lai.

## Estructura de la API

```
api/
├── config.php          # Configuración general de la API
├── auth.php            # Sistema de autenticación con JWT
├── login.php           # Endpoint de login
├── registro.php        # Endpoint de registro de usuarios
├── recuperar.php       # Endpoint de recuperación de contraseña
└── README.md           # Este archivo
```

## Endpoints Disponibles

### 1. Login
**POST** `/api/login.php`

**Body:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "contraseña123"
}
```

**Respuesta exitosa:**
```json
{
  "status": "success",
  "message": "Login exitoso",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id_usuario": 1,
      "username": "usuario",
      "nombre_rol": "Cliente",
      "id_rol": 3,
      "cedula": "12345678",
      "foto_perfil": "ruta/a/foto.jpg"
    },
    "expires_in": 86400
  }
}
```

### 2. Registro
**POST** `/api/registro.php`

**Body:**
```json
{
  "nombre_usuario": "nuevo_usuario",
  "clave": "Contraseña123",
  "nombre": "Juan",
  "apellido": "Pérez",
  "correo": "juan@ejemplo.com",
  "telefono": "04141234567",
  "cedula": "12345678",
  "direccion": "Dirección del usuario"
}
```

### 3. Recuperación de Contraseña
**POST** `/api/recuperar.php`

**Body:**
```json
{
  "email": "usuario@ejemplo.com"
}
```

## Configuración para Producción

### 1. Subir al Host

Cuando subas el sistema al host, la URL de la API será:

```
https://casalai.infinityfree.me/api/login.php
https://casalai.infinityfree.me/api/registro.php
https://casalai.infinityfree.me/api/recuperar.php
```

### 2. Configurar la App Móvil

En la app móvil, actualiza el archivo `cl-app/casa-lai/src/config/apiConfig.js`:

```javascript
// Cambia esta línea con tu URL real del host
export const API_BASE_URL = 'https://casalai.infinityfree.me/api';
```

### 3. Seguridad

**IMPORTANTE:** Antes de subir a producción:

1. Cambia la clave secreta JWT en `api/auth.php` (línea 12):
   ```php
   private static $secretKey = 'TU_CLAVE_SECRETA_MUY_SEGURA';
   ```

2. Desactiva el display de errores en `api/config.php` (líneas 7-8):
   ```php
   // error_reporting(E_ALL);
   // ini_set('display_errors', 1);
   ```

3. Configura CORS apropiadamente según tu dominio:
   ```php
   header('Access-Control-Allow-Origin: https://casalai.infinityfree.me');
   ```

## Autenticación con JWT

La API usa tokens JWT para la autenticación. El token se genera al hacer login y debe enviarse en el header `Authorization` para endpoints protegidos:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

El token tiene una validez de 24 horas (86400 segundos).

## Compatibilidad

La API es compatible con la aplicación móvil React Native/Expo en `cl-app/casa-lai/`. Los cambios realizados incluyen:

- Acepta tanto `email` como `username` para el login
- Retorna respuestas JSON estándar
- Implementa autenticación JWT
- Maneja errores apropiadamente con códigos HTTP

## Testing

Para probar la API localmente, puedes usar herramientas como:
- Postman
- cURL
- Thunder Client (VS Code extension)

Ejemplo con cURL:
```bash
curl -X POST http://localhost/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@ejemplo.com","password":"contraseña"}'
```
