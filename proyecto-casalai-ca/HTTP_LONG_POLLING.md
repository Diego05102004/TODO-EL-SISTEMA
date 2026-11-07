# HTTP Long Polling

El endpoint `?pagina=long_poll_notificaciones&action=notifications` mantiene una solicitud GET abierta hasta 20 segundos. La primera llamada envía `initial=1` para obtener el estado actual; las siguientes consultan el último ID de notificación del usuario cada 800 ms y devuelven la lista de no leídas cuando hay cambios. Al vencer el plazo, responde con `timeout: true`; el cliente inicia la siguiente petición secuencialmente.

El endpoint también ofrece `action=cart_count`, `action=exchange_rate` y `action=mark_read`. La última acción es POST, verifica un token CSRF y limita la actualización al usuario autenticado en la sesión.

## Cliente web

Las plantillas cargan `assets/javascript/http-long-poll-client.js` y mantienen el renderizado de notificaciones existente. El cliente permite una sola petición de long polling a la vez, usa un timeout superior al del servidor y aplica backoff exponencial con jitter cuando hay errores.

## Cliente React Native

El mismo archivo exporta `Client` como CommonJS. Puede copiarse/importarse en la app React Native:

```js
import { useEffect } from 'react';
import { AppState } from 'react-native';
const { Client } = require('./http-long-poll-client');

function useNotificacionesLongPoll(setNotificaciones) {
  useEffect(() => {
    const client = new Client({
      endpoint: 'https://TU_HOST/?pagina=long_poll_notificaciones',
      credentials: 'include',
      headers: { Accept: 'application/json' },
      onMessage: (event) => {
        if (event.tipo === 'nueva_notificacion') {
          setNotificaciones(event.notificaciones);
        }
      },
      onError: (error) => console.warn('Long polling:', error.message)
    });

    if (AppState.currentState === 'active') client.start();
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') client.start();
      else client.stop();
    });

    return () => {
      subscription.remove();
      client.stop();
    };
  }, [setNotificaciones]);
}
```

El endpoint autentica con la sesión PHP de la web. En React Native, la app debe conservar y enviar la cookie de sesión obtenida durante el inicio de sesión; `credentials: 'include'` por sí solo no garantiza el manejo de cookies en todas las versiones/plataformas de React Native. Si la app usa Bearer/JWT en vez de la sesión PHP, hace falta adaptar la autenticación del endpoint a ese mecanismo existente antes de usarlo.

## PHP y operación

- `session_write_close()` se ejecuta tras copiar el ID autenticado y el token CSRF a variables locales; así el long poll no bloquea otras peticiones de la misma sesión.
- El endpoint limita la espera a 20 segundos y establece un límite de ejecución de 25 segundos. Configura proxies/timeouts de hosting por encima del valor del servidor.
- La conexión SQL es no persistente y se cierra al salir del bucle. Conviene indexar `tbl_notificaciones` por `(id_usuario, id_notificacion)` y `(id_usuario, leido, fecha_hora)` si el esquema/plan de consultas lo justifica.
- Evita lanzar intervalos de polling paralelos: cada cliente debe esperar la respuesta anterior antes de abrir otra solicitud.
- Usa HTTPS y cookies de sesión `Secure`, `HttpOnly` y `SameSite` en producción.
- Un long poll mantiene un worker PHP ocupado por cliente concurrente. Ajusta el timeout y la frecuencia a los límites del hosting; para muchos clientes concurrentes se requiere un servicio de push o una cola administrada.
