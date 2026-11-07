@echo off
echo Creando tarea programada para inicio automático del WebSocket...
schtasks /create /tn "Casalai WebSocket Server" /xml "websocket_autostart.xml"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ¡Tarea programada creada exitosamente!
    echo El servidor WebSocket iniciará automáticamente al iniciar sesión.
    echo.
    echo Para verificar la tarea programada:
    echo - Ve a Programador de Tareas de Windows
    echo - Busca "Casalai WebSocket Server"
    echo.
    echo Para eliminar la tarea programada en el futuro:
    echo schtasks /delete /tn "Casalai WebSocket Server" /f
) else (
    echo.
    echo Error al crear la tarea programada.
    echo Asegúrate de ejecutar este script como Administrador.
)

pause
