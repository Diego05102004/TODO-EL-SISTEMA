# Configuración de Conexión Automática WebSocket

## Sistema Automático (Sin Configuración Manual)

El servidor WebSocket ahora se inicia **automáticamente** cuando un usuario inicia sesión. **No se requiere configuración manual ni Programador de Tareas**.

## ¿Cómo funciona el sistema automático?

### 1. Inicio Automático al Login
- Cuando un usuario inicia sesión, el sistema verifica si el servidor WebSocket está corriendo
- Si no está corriendo, lo inicia automáticamente en segundo plano
- Espera y verifica que la conexión se establezca correctamente

### 2. Inicialización del Cliente
- El cliente WebSocket se inicializa automáticamente en cada página
- Detecta si hay un usuario logueado y establece la conexión
- Verifica que la conexión esté activa y repara si es necesario

### 3. Monitoreo Continuo
- El sistema verifica periódicamente (cada 30 segundos) que el servidor esté disponible
- Si detecta que el servidor no está corriendo, intenta reconectar automáticamente

## Características del Sistema

### ✅ **Totalmente Automático**
- Sin necesidad de configuración manual
- Sin requerir Programador de Tareas
- Sin intervención del usuario

### ✅ **Auto-recuperación**
- Si el servidor se detiene, se reinicia automáticamente
- Si la conexión se pierde, se repara automáticamente
- Soporte para múltiples intentos de reconexión

### ✅ **Multiplataforma**
- Funciona en Windows, Linux y Mac
- Detecta automáticamente el sistema operativo
- Usa los comandos apropiados para cada plataforma

### ✅ **Control de Procesos**
- Evita múltiples instancias del servidor
- Verifica si los procesos están activos
- Limpia archivos lock automáticamente

## Proceso Completo

1. **Usuario inicia sesión** → Sistema verifica servidor WebSocket
2. **Si no está corriendo** → Lo inicia automáticamente en segundo plano
3. **Espera y verifica** → Confirma que el servidor inició correctamente
4. **Inicializa cliente** → Conecta automáticamente al WebSocket
5. **Monitoreo continuo** → Verifica periódicamente el estado

## Verificación

Para verificar que el sistema funciona:

1. **Inicia sesión** en el sistema Casalai
2. **Abre la consola** del navegador (F12)
3. **Busca estos mensajes**:
   - "Servidor WebSocket ya está corriendo" o "Iniciando servidor WebSocket..."
   - "WebSocket inicializado automáticamente"
   - "Conexión WebSocket establecida correctamente"

## Solución de Problemas

### Si el WebSocket no inicia automáticamente:

1. **Verifica permisos de PHP**
   - Asegúrate que PHP pueda ejecutar comandos del sistema
   - En XAMPP, verifica que el módulo `proc_open` esté habilitado

2. **Verifica el puerto 8080**
   - Asegúrate que ningún otro programa use el puerto 8080
   - Puedes verificar con: `netstat -an | findstr 8080`

3. **Reinicia sesión**
   - Cierra y vuelve a iniciar tu sesión en el sistema
   - Esto forzará una nueva verificación

### Si ves errores en la consola:

- **"No se pudo iniciar el servidor WebSocket"**: Revisa los permisos de PHP
- **"WebSocket no pudo conectar"**: Verifica que el puerto 8080 esté disponible
- **"Error verificando estado WebSocket"**: Es normal si el servidor está ocupado

## Archivos del Sistema

- `verificar_websocket.php`: Verificación e inicio automático del servidor
- `verificar_websocket_status.php`: Endpoint para verificación periódica
- `websocket_server.php`: Servidor WebSocket principal
- `Vista/header.php`: Inicialización automática del cliente

## Notas Importantes

- **No necesitas instalar nada adicional**
- **No necesitas configurar tareas programadas**
- **El sistema es completamente autogestionado**
- **Funciona en cualquier instalación estándar de PHP**
- **Se reinicia automáticamente si hay caídas**

El sistema está diseñado para ser "plug-and-play" - simplemente funciona sin configuración manual.
