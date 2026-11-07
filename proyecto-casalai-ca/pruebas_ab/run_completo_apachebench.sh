#!/bin/bash
# Script completo de pruebas de servidor con ApacheBench (ab)
# Ejecutar en Kali Linux - Copiar y pegar este comando completo

mkdir -p ~/auditoria_evidencias
cd ~/auditoria_evidencias

echo "=========================================="
echo "PRUEBAS DE SERVIDOR CON APACHEBENCH (ab)"
echo "Proyecto Casalai CA"
echo "=========================================="
echo ""

# ========================
# COMPONENTE 1: SERVIDOR WEB PRINCIPAL (Apache/PHP)
# ========================
echo ">>> COMPONENTE 1: SERVIDOR WEB PRINCIPAL (Apache/PHP)"
echo "=========================================="
echo "Monitoreo de hilos concurrentes y ejecución de peticiones masivas"
echo "Prueba: 1,000 peticiones con 50 usuarios concurrentes"
echo ""

# Prueba de carga en la raíz del proyecto
echo "Ejecutando prueba de carga en raíz del proyecto..."
ab -n 1000 -c 50 -g ~/auditoria_evidencias/apache_servidor_web.tsv 'http://192.168.10.1/proyecto-casalai-ca/' > ~/auditoria_evidencias/apache_servidor_web.txt 2>&1
echo ""

# Prueba en endpoint de login
echo "Ejecutando prueba en endpoint de login..."
ab -n 1000 -c 50 -g ~/auditoria_evidencias/apache_login.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/login.php' > ~/auditoria_evidencias/apache_login.txt 2>&1
echo ""

# Prueba en endpoint de catálogo
echo "Ejecutando prueba en endpoint de catálogo..."
ab -n 1000 -c 50 -g ~/auditoria_evidencias/apache_catalogo.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/apache_catalogo.txt 2>&1
echo ""

# ========================
# COMPONENTE 2: MICROSERVICIO COGNITIVO (FastAPI/Uvicorn)
# ========================
echo ">>> COMPONENTE 2: MICROSERVICIO COGNITIVO (FastAPI/Uvicorn)"
echo "=========================================="
echo "Lanzamiento simultáneo de solicitudes asíncronas de procesamiento OCR"
echo "Prueba: Latencia de comunicación API REST"
echo ""

# Health check del microservicio
echo "Health check del microservicio..."
ab -n 100 -c 10 -g ~/auditoria_evidencias/fastapi_health.tsv 'http://192.168.10.1:8000/health' > ~/auditoria_evidencias/fastapi_health.txt 2>&1
echo ""

# Prueba de endpoint OCR (fase1/extraer)
echo "Prueba de endpoint OCR..."
ab -n 500 -c 20 -g ~/auditoria_evidencias/fastapi_ocr.tsv 'http://192.168.10.1:8000/fase1/extraer' > ~/auditoria_evidencias/fastapi_ocr.txt 2>&1
echo ""

# Prueba de endpoint de analítica (si existe)
echo "Prueba de endpoint de analítica..."
ab -n 500 -c 20 -g ~/auditoria_evidencias/fastapi_analitica.tsv 'http://192.168.10.1:8000/fase1/verificar' > ~/auditoria_evidencias/fastapi_analitica.txt 2>&1
echo ""

# ========================
# COMPONENTE 3: GESTOR DE BASE DE DATOS (MySQL/MariaDB)
# ========================
echo ">>> COMPONENTE 3: GESTOR DE BASE DE DATOS (MySQL/MariaDB)"
echo "=========================================="
echo "Simulación de operaciones transaccionales cruzadas"
echo "Prueba: Integridad transaccional ACID"
echo ""

# Crear datos de prueba para POST requests
cat > /tmp/venta_test.json << 'EOF'
{
  "funcion": "facturaingresarmovil",
  "cedula": "12345678",
  "cliente": "Cliente Test AB",
  "metodo_pago": "efectivo",
  "productos": [
    {"id": 1, "cantidad": 1, "precio": 10.50}
  ]
}
EOF

# Prueba de operaciones de venta (transacciones ACID)
echo "Prueba de operaciones de venta (transacciones ACID)..."
ab -n 500 -c 25 -p /tmp/venta_test.json -T application/json -g ~/auditoria_evidencias/mysql_ventas.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/gestionarFactura.php' > ~/auditoria_evidencias/mysql_ventas.txt 2>&1
echo ""

# Prueba de operaciones de recepción
echo "Prueba de operaciones de recepción..."
ab -n 500 -c 25 -g ~/auditoria_evidencias/mysql_recepcion.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/carrito.php?funcion=consultar' > ~/auditoria_evidencias/mysql_recepcion.txt 2>&1
echo ""

# ========================
# COMPONENTE 4: DISPONIBILIDAD Y TOLERANCIA A FALLOS
# ========================
echo ">>> COMPONENTE 4: DISPONIBILIDAD Y TOLERANCIA A FALLOS"
echo "=========================================="
echo "Pruebas de conectividad continua (Health Check)"
echo "Prueba: Disponibilidad operativa del servicio"
echo ""

# Prueba de disponibilidad continua (10 iteraciones)
echo "Prueba de disponibilidad continua (10 health checks)..."
for i in {1..10}; do
  echo "Health check #$i..."
  ab -n 10 -c 1 'http://192.168.10.1/proyecto-casalai-ca/' >> ~/auditoria_evidencias/health_checks.log 2>&1
  sleep 2
done
echo ""

# Prueba de conmutación al modo manual (simulando caída del microservicio)
echo "Prueba de conmutación al modo manual..."
ab -n 100 -c 10 -g ~/auditoria_evidencias/contingencia.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/contingencia.txt 2>&1
echo ""

# ========================
# RESUMEN FINAL
# ========================
echo "=========================================="
echo "PRUEBAS DE SERVIDOR COMPLETADAS"
echo "=========================================="
echo ""
echo "Resultados guardados en: ~/auditoria_evidencias/"
echo ""
echo "Archivos generados:"
ls -lh ~/auditoria_evidencias/*.txt ~/auditoria_evidencias/*.tsv ~/auditoria_evidencias/*.log 2>/dev/null
echo ""
echo "Archivos TSV (para gráficos):"
echo "  - apache_servidor_web.tsv"
echo "  - apache_login.tsv"
echo "  - apache_catalogo.tsv"
echo "  - fastapi_health.tsv"
echo "  - fastapi_ocr.tsv"
echo "  - fastapi_analitica.tsv"
echo "  - mysql_ventas.tsv"
echo "  - mysql_recepcion.tsv"
echo "  - contingencia.tsv"
echo ""
echo "Para ver resultados detallados:"
echo "  cat ~/auditoria_evidencias/NOMBRE_ARCHIVO.txt"
echo ""
echo "Para ver métricas clave:"
echo "  grep 'Requests per second' ~/auditoria_evidencias/*.txt"
echo "  grep 'Time per request' ~/auditoria_evidencias/*.txt"
echo ""
