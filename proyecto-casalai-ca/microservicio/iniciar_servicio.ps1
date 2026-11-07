# Script para iniciar el microservicio unificado de Casa Lai, C.A.
# Autor: Devin AI
# Fecha: 2026-10-25

# Configurar variables de entorno
$env:ANALITICA_PREDICTIVA_TOKEN="7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M"

# Ir al directorio del microservicio
cd $PSScriptRoot

# Verificar si ya hay un proceso en el puerto 8000
$puertoOcupado = netstat -ano | findstr ":8000"
if ($puertoOcupado) {
    Write-Host "⚠️  El puerto 8000 ya está en uso." -ForegroundColor Yellow
    Write-Host "Matando proceso existente..." -ForegroundColor Yellow
    $pid = ($puertoOcupado -split '\s+')[4]
    taskkill /F /PID $pid
    Start-Sleep -Seconds 2
}

# Iniciar el servidor
Write-Host "🚀 Iniciando microservicio en http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Token de analítica configurado" -ForegroundColor Cyan
Write-Host "Presione Ctrl+C para detener el servidor" -ForegroundColor Yellow
Write-Host ""

python -m uvicorn main:app --host 127.0.0.1 --port 8000
