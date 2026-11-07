# Configuración para Always Data

## Resumen de configuración

Esta guía explica cómo configurar tu aplicación React Native para funcionar con Always Data.

**URL de producción**: `https://casalai.alwaysdata.net/`

## 1. Configuración de Android

### ✅ Permisos de Internet
El archivo `android/app/src/main/AndroidManifest.xml` ya tiene el permiso de Internet configurado:
```xml
<uses-permission android:name="android.permission.INTERNET"/>
```

### ✅ Configuración de Seguridad de Red
Se creó el archivo `android/app/src/main/res/xml/network_security_config.xml` que:
- Permite HTTPS por defecto (requisito de Always Data)
- Permite HTTP solo para desarrollo local (localhost, 10.0.2.2, IPs LAN)
- Configura confianza en certificados del sistema y del usuario

### ✅ Manifest actualizado
El `AndroidManifest.xml` ahora incluye:
```xml
android:networkSecurityConfig="@xml/network_security_config"
android:usesCleartextTraffic="false"
```

## 2. Configuración de URLs HTTPS

### Archivos actualizados para usar HTTPS:

#### `src/config/apiConfig.js`
- API_BASE_URL: `https://casalai.alwaysdata.net/api`
- PROFILE_IMAGE_PUBLIC_URL: `https://casalai.alwaysdata.net/assets/img/uploads/`
- PAGOS_IMAGE_PUBLIC_URL: `https://casalai.alwaysdata.net/assets/img/uploads/comprobantes/`

#### `src/utils/jwt.js`
- DEFAULT_ISSUER: `casalai-api` (coincide con el servidor PHP actual)
- NOTA: El servidor PHP está usando `iss: "casalai-api"` en los tokens JWT

#### `.env.example`
- EXPO_PUBLIC_API_URL: `https://casalai.alwaysdata.net/api`

## 3. Configuración JWT - IMPORTANTE

El servidor PHP está usando `iss: "casalai-api"` en los tokens JWT, pero el cliente espera una URL completa.

**Solución aplicada**: Se actualizó `src/utils/jwt.js` para aceptar `casalai-api` como issuer.

**Opción recomendada**: Actualiza tu servidor PHP para usar la URL completa:
```php
// En tu servidor PHP, cambia el issuer a:
$payload = [
    'iss' => 'https://casalai.alwaysdata.net/',
    'aud' => 'proyecto-casalai-ca',
    // ...
];
```

## 4. Pasos para desplegar en Always Data

### Paso 1: Configurar tu dominio en Always Data
1. Accede a tu panel de Always Data
2. Tu dominio ya está configurado: `casalai.alwaysdata.net`
3. Asegúrate de que HTTPS esté habilitado (Always Data lo proporciona por defecto)

### Paso 2: Subir los archivos PHP del servidor
1. Sube la carpeta `api/` de tu proyecto PHP a Always Data
2. Sube la carpeta `assets/` (imágenes, uploads, etc.)
3. Verifica que la estructura sea:
   ```
   /htdocs/
     /api/
       /auth/
       /productos/
       /pagos/
       ...
     /assets/
       /img/
         /uploads/
         /productos/
     index.php
   ```

### Paso 3: Configurar variables de entorno en el servidor PHP
En AlwaysData, configura los secretos únicamente en variables de entorno privadas o en `api/Config/.env`, fuera del control de versiones y con acceso HTTP denegado. Genera un valor aleatorio nuevo para `JWT_SECRET_KEY` de al menos 32 bytes. No reutilices claves que hayan aparecido en código, documentación o historial de Git. Al cambiar la clave, todos los usuarios deberán iniciar sesión de nuevo.

### reCAPTCHA v2 Checkbox

En la consola de Google, configura el tipo **reCAPTCHA v2 → Casilla “No soy un robot”** y registra estos dominios:

- `casalai.alwaysdata.net` para producción y la APK publicada.
- `localhost` para desarrollo local.

En `api/Config/.env` (local) y en las variables privadas de AlwaysData, configura:

```env
RECAPTCHA_SITE_KEY=<site key pública de reCAPTCHA v2>
RECAPTCHA_SECRET_KEY=<secret key privada de reCAPTCHA v2>
RECAPTCHA_ALLOWED_HOSTNAMES=casalai.alwaysdata.net,localhost,127.0.0.1
```

La app consulta la site key desde `api/recaptcha_config.php`; la secret key solo se usa en PHP para verificar el token con Google. No la pongas en la app, en un `EXPO_PUBLIC_...`, ni en archivos versionados. El backend valida `success` y el hostname devuelto por Google; no aplica el umbral `score` de reCAPTCHA v3.

Si `https://casalai.alwaysdata.net/api/recaptcha_config.php` responde `404`, despliega la versión actual de la API, incluyendo `recaptcha_config.php`, `config.php` y `login.php`, antes de compilar/publicar la app.

Para desarrollo local con Expo Web, coloca estas variables en `.env.local` (este archivo tiene prioridad sobre `.env` y no se sube a Git):

```env
# Expo Web con XAMPP:
EXPO_PUBLIC_API_URL=http://localhost/proyecto-casalai-ca/api
EXPO_PUBLIC_RECAPTCHA_DOMAIN=http://localhost
```

Reinicia Expo después de cambiar las variables: `npx expo start --web --clear`. Para Android Emulator con XAMPP, cambia `localhost` en `EXPO_PUBLIC_API_URL` por `10.0.2.2`. En un teléfono físico, `localhost` apunta al teléfono y no a la PC: usa la IP LAN de la PC en `EXPO_PUBLIC_API_URL` y conserva `EXPO_PUBLIC_RECAPTCHA_DOMAIN=http://localhost`. Para la APK de producción, usa `https://casalai.alwaysdata.net/api` y `EXPO_PUBLIC_RECAPTCHA_DOMAIN=casalai.alwaysdata.net`; vuelve a compilar después de cambiar estas variables.

La clave secreta que se compartió en el chat debe considerarse expuesta: revócala y genera una nueva en Google antes de publicar. Actualiza la nueva clave únicamente en la configuración privada de AlwaysData y en el `.env` local ignorado por Git.

### Paso 4: Compilar la APK de producción
```bash
# En la raíz del proyecto React Native
cd android

# Limpiar builds anteriores
./gradlew clean

# Compilar APK de release
./gradlew assembleRelease

# La APK estará en: android/app/build/outputs/apk/release/app-release.apk
```

### Paso 5: (Opcional) Cambiar entorno a producción
Si quieres cambiar permanentemente a producción en el código:

1. Editar `src/config/envConfig.js`:
```javascript
export const ENV_CONFIG = {
  IS_PRODUCTION: true, // Cambiar a true
  // ...
};
```

2. O crear un archivo `.env` en la raíz:
```env
EXPO_PUBLIC_API_URL=https://casalai.alwaysdata.net/api
```

## 5. Configuración de Servicios Externos

No guardes tokens de Analítica Predictiva ni de Auditor Recepción en `CasaLaiApp`, archivos `.env` con prefijo `EXPO_PUBLIC_`, ni en documentación de la app. Las credenciales deben residir en la API del servidor; la app solo debe llamar a un endpoint propio que actúe como proxy autenticado. `servicesConfig.js` no devuelve encabezados de autenticación mientras no exista ese proxy.
## 6. Verificación

### Verificar conexión HTTPS
1. Abre `https://casalai.alwaysdata.net/api` en tu navegador
2. Deberías ver un JSON de respuesta o mensaje de la API
3. Verifica que el certificado SSL sea válido (candado verde en el navegador)

### Verificar en la APK
1. Instala la APK en un dispositivo Android
2. Revisa los logs con `adb logcat` para ver las peticiones
3. Verifica que las URLs sean HTTPS

### Logs de ejemplo
```
[API] API POST Request: {
  url: 'https://casalai.alwaysdata.net/api/auth/login',
  endpoint: '/auth/login',
  ...
}
```

## 7. Troubleshooting

### Error: "Cleartext HTTP traffic not permitted"
- **Causa**: Intentando conectar a HTTP en producción
- **Solución**: Asegúrate de usar HTTPS en todas las URLs

### Error: "SSL certificate verify failed"
- **Causa**: Certificado SSL no válido o auto-firmado
- **Solución**: Always Data proporciona certificados válidos, verifica tu dominio

### Error: "Network request failed"
- **Causa**: URL incorrecta o servidor no responde
- **Solución**: Verifica que la URL sea correcta y el servidor esté en línea

### Imágenes no cargan
- **Causa**: URLs de imágenes aún en HTTP
- **Solución**: Verifica `PROFILE_IMAGE_PUBLIC_URL` y `PAGOS_IMAGE_PUBLIC_URL` en `apiConfig.js`

### Warning: "issuerMismatch" en logs JWT
- **Causa**: El servidor PHP envía `iss: "casalai-api"` pero el cliente espera una URL
- **Solución**: Ya configurado para aceptar `casalai-api`, pero recomendado actualizar el servidor PHP

## 8. Notas de Seguridad

✅ **HTTPS obligatorio**: Android requiere HTTPS para todas las conexiones en producción
✅ **Cifrado RSA+AES**: Los datos sensibles están cifrados con el sistema híbrido
✅ **Tokens JWT**: La autenticación usa JWT con firma segura
✅ **Certificados**: Always Data proporciona certificados SSL válidos

## 9. Archivos modificados

- ✅ `android/app/src/main/AndroidManifest.xml` - Configuración de red
- ✅ `android/app/src/main/res/xml/network_security_config.xml` - Configuración de seguridad
- ✅ `src/config/apiConfig.js` - URLs HTTPS (casalai.alwaysdata.net)
- ✅ `src/utils/jwt.js` - Issuer actualizado a "casalai-api"
- ✅ `src/config/servicesConfig.js` - Comentarios de producción
- ✅ `src/config/envConfig.js` - Configuración de entorno
- ✅ `.env.example` - Documentación de variables
- ✅ `ALWAYSDATA_SETUP.md` - Este archivo

## 10. Próximos pasos

1. ✅ Sube tu código PHP a Always Data (https://casalai.alwaysdata.net)
2. ⚠️ Actualiza el servidor PHP para usar `JWT_ISSUER=https://casalai.alwaysdata.net/`
3. Compila la APK de release
4. Prueba la APK en un dispositivo real
5. Verifica todas las funcionalidades (login, productos, pagos, etc.)
6. Sube la APK a Google Play Store (opcional)

---

**Importante**: Antes de desplegar en producción, asegúrate de:
- ✅ Verificar que todas las APIs funcionen correctamente
- ✅ Probar el flujo completo de la aplicación
- ✅ Verificar que las imágenes se carguen correctamente
- ✅ Probar en múltiples dispositivos Android
- ✅ Tener un backup de tu código
- ⚠️ Actualizar el servidor PHP para usar el issuer correcto
