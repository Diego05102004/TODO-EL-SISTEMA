@echo off
echo Eliminando tarea programada del WebSocket...
schtasks /delete /tn "Casalai WebSocket Server" /f

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ¡Tarea programada eliminada exitosamente!
    echo El servidor WebSocket ya no iniciará automáticamente.
) else (
    echo.
    echo No se encontró la tarea programada o ocurrió un error.
)

pause
