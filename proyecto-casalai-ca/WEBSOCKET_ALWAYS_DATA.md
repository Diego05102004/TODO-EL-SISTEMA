# WebSocket en AlwaysData

**Nota:** la aplicación web fue migrada a HTTP Long Polling y ya no necesita iniciar este servicio para notificaciones. Consulta [HTTP_LONG_POLLING.md](./HTTP_LONG_POLLING.md). Esta guía queda como referencia únicamente para instalaciones que decidan conservar el daemon WebSocket legado.

El servidor usa Ratchet y puede ejecutarse como proceso persistente desde el panel de AlwaysData. La app web obtiene una URL y un JWT de corta duración desde `?pagina=websocket_token`; no debe autenticar al socket enviando el ID de usuario.

## Variables de entorno

Configura las variables tanto para el sitio PHP como para el servicio persistente:

```text
APP_ENV=production
WEBSOCKET_PORT=8300
WEBSOCKET_BIND_ADDRESS=0.0.0.0
WEBSOCKET_PUBLIC_URL=wss://services-TU_CUENTA.alwaysdata.net:8300
WEBSOCKET_ALLOWED_ORIGINS=casalai.alwaysdata.net
WEBSOCKET_JWT_SECRET=<secreto aleatorio de al menos 32 caracteres>
WEBSOCKET_TLS_CERT=/home/TU_CUENTA/certs/fullchain.pem
WEBSOCKET_TLS_KEY=/home/TU_CUENTA/certs/private.key
```

Reemplaza `TU_CUENTA` por el nombre real de la cuenta y agrega a `WEBSOCKET_ALLOWED_ORIGINS` cada host web legítimo, separados por comas. No uses `*`. Genera un secreto con:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

`WEBSOCKET_JWT_SECRET` debe tener exactamente el mismo valor en el proceso web y el servicio. Guárdalo en las variables privadas de AlwaysData o en un `.env` fuera del directorio público; no lo subas al repositorio.

El servidor inicia TLS directamente en el puerto asignado. El certificado debe incluir la cadena completa y ser válido para el nombre de host utilizado por `WEBSOCKET_PUBLIC_URL`. Esta implementación requiere certificado y clave privada para producción. Si el panel de AlwaysData ofrece terminación TLS para el host de servicios, confirma con soporte el esquema de proxy/upgrade antes de desplegar: el listener tendría que adaptarse a esa topología. No publiques `ws://` desde una página HTTPS.

Esta configuración necesita que el sitio PHP y el servicio usen los mismos valores. Las variables definidas por el proceso/sitio en AlwaysData tienen prioridad sobre `.env`; no pongas claves reales en el repositorio.

## Crear el servicio persistente

1. En el panel, asigna al servicio un puerto libre del rango `8300–8499` (este ejemplo usa `8300`).
2. Crea el servicio persistente con el directorio de trabajo en la raíz del proyecto y el comando:

   ```sh
   php /home/TU_CUENTA/proyecto-casalai-ca/websocket_server.php
   ```

3. Configura las variables de entorno anteriores en el servicio y reinícialo.
4. Configura las mismas variables compartidas en el entorno del sitio PHP. El sitio debe seguir servido por HTTPS.
5. Inicia sesión en la app; la conexión debe abrirse a `services-TU_CUENTA.alwaysdata.net:8300` con `wss://`.

El proceso escucha en `0.0.0.0`, valida el `Origin` del handshake, requiere un JWT firmado antes de aceptar comandos, limita cada conexión a 60 mensajes en 10 segundos y limita cada operación costosa a una cada 2 segundos. Los tokens del socket expiran en 90 segundos; el cliente solicita uno nuevo al reconectar.

## Comprobación

- En las herramientas del navegador, **Network → WS** debe mostrar la URL `wss://...:8300` y estado `101 Switching Protocols`.
- El primer mensaje del cliente debe ser `autenticar` con un token; el servidor responde `autenticado`.
- Un handshake desde un host que no esté en `WEBSOCKET_ALLOWED_ORIGINS`, o un mensaje sin token válido, debe ser rechazado/cerrado.
- Revisa los logs del servicio en el panel para errores de certificado, puerto ocupado o variables faltantes.

No uses las rutas `start_websocket.php` ni `verificar_websocket.php` para mantener el proceso en AlwaysData: están pensadas para desarrollo local y el servicio persistente debe administrarse desde el panel.
