@echo off
REM Script para iniciar el microservicio unificado de Casa Lai, C.A.
REM Autor: Devin AI
REM Fecha: 2026-10-25

REM Configurar variables de entorno
set ANALITICA_PREDICTIVA_TOKEN=7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M

REM Ir al directorio del microservicio
cd /d "%~dp0"

REM Verificar si ya hay un proceso en el puerto 8000
netstat -ano | findstr ":8000" >nul
if %errorlevel% == 0 (
    echo El puerto 8000 ya esta en uso. Matando proceso existente...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000"') do taskkill /F /PID %%a
    timeout /t 2 /nobreak >nul
)

REM Iniciar el servidor
echo Iniciando microservicio en http://127.0.0.1:8000
echo Token de analitica configurado
echo Presione Ctrl+C para detener el servidor
echo.

python -m uvicorn main:app --host 127.0.0.1 --port 8000
