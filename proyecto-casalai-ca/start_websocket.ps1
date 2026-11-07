# WebSocket Server Startup Script
# Este script inicia automáticamente el servidor WebSocket

Write-Host "Iniciando servidor WebSocket..." -ForegroundColor Green
Set-Location "c:\xampp\htdocs\Repositorio de GITHUB\proyecto-casalai-main\proyecto-casalai-ca"

# Verificar si PHP está disponible
try {
    $phpVersion = php -v
    Write-Host "PHP encontrado: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "Error: PHP no está instalado o no está en el PATH" -ForegroundColor Red
    exit 1
}

# Iniciar el servidor WebSocket
Write-Host "Iniciando servidor WebSocket en el puerto 8080..." -ForegroundColor Yellow
try {
    php websocket_server.php
} catch {
    Write-Host "Error al iniciar el servidor WebSocket: $_" -ForegroundColor Red
    exit 1
}
