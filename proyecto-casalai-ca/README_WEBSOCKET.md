# Inicio Automático del Servidor WebSocket

## Opción 1: Instalar como Tarea Programada (Recomendado)

### Para instalar:
1. **Ejecutar como Administrador**: Haz clic derecho en `install_autostart.bat` y selecciona "Ejecutar como administrador"
2. **Verificar instalación**: Ve al Programador de Tareas de Windows y busca "Casalai WebSocket Server"

### Para desinstalar:
1. **Ejecutar como Administrador**: Haz clic derecho en `uninstall_autostart.bat` y selecciona "Ejecutar como administrador"

## Opción 2: Inicio Manual

### Scripts disponibles:
- `start_websocket.bat` - Script simple para Windows
- `start_websocket.ps1` - Script PowerShell con verificación

## Opción 3: Carpeta de Inicio (Alternativa)

1. Presiona `Win + R` y escribe: `shell:startup`
2. Copia `start_websocket.bat` a esa carpeta

## ¿Qué hace la instalación?

Crea una tarea programada que:
- **Se ejecuta al iniciar sesión** de Windows
- **Inicia el servidor WebSocket** automáticamente
- **Se ejecuta en segundo plano** sin mostrar ventana
- **Se reinicia automáticamente** si hay caídas

## Verificación

Para verificar que está funcionando:
1. Reinicia tu sesión de Windows
2. Abre el navegador y revisa la consola (F12)
3. Deberías ver: "Conexión WebSocket establecida"

## Solución de problemas

Si no funciona:
1. **Verifica permisos**: Asegúrate de ejecutar como administrador
2. **Revisa PHP**: Confirma que PHP está en el PATH del sistema
3. **Verifica puerto**: Asegúrate que el puerto 8080 esté disponible

## Notas importantes

- El servidor se iniciará **automáticamente cada vez** que inicies sesión
- No necesitarás intervención manual después de la instalación
- El sistema de reconexión automática seguirá funcionando si hay interrupciones
