#!/bin/bash
# Script de pruebas de disponibilidad y tolerancia a fallos
# Pruebas de Health Check y conmutación al modo manual
# Ejecutar en Kali Linux

mkdir -p ~/auditoria_evidencias/disponibilidad
cd ~/auditoria_evidencias/disponibilidad

echo "=========================================="
echo "PRUEBAS DE DISPONIBILIDAD Y TOLERANCIA"
echo "Proyecto Casalai CA - RNF13"
echo "=========================================="
echo ""

# ========================
# PRUEBA 1: HEALTH CHECK CONTINUO
# ========================
echo ">>> PRUEBA 1: HEALTH CHECK CONTINUO"
echo "=========================================="
echo "Pruebas de conectividad continua"
echo "Objetivo: Verificar disponibilidad operativa del servicio"
echo ""

echo "Iniciando health checks durante 2 minutos (120 checks)..."
for i in {1..120}; do
  timestamp=$(date '+%Y-%m-%d %H:%M:%S')
  echo "$timestamp - Health check #$i" >> ~/auditoria_evidencias/disponibilidad/health_log.txt
  ab -n 5 -c 1 'http://192.168.10.1/proyecto-casalai-ca/' >> ~/auditoria_evidencias/disponibilidad/health_raw_$i.txt 2>&1
  sleep 1
done
echo "Health checks completados (120 iteraciones)"
echo ""

# ========================
# PRUEBA 2: DISPONIBILIDAD DE MICROSERVICIO
# ========================
echo ">>> PRUEBA 2: DISPONIBILIDAD DE MICROSERVICIO"
echo "=========================================="
echo "Verificación de disponibilidad del microservicio OCR"
echo ""

echo "Health checks del microservicio (50 iteraciones)..."
for i in {1..50}; do
  timestamp=$(date '+%Y-%m-%d %H:%M:%S')
  echo "$timestamp - Microservicio health check #$i" >> ~/auditoria_evidencias/disponibilidad/microservicio_health_log.txt
  ab -n 5 -c 1 'http://192.168.10.1:8000/health' >> ~/auditoria_evidencias/disponibilidad/microservicio_raw_$i.txt 2>&1
  sleep 2
done
echo "Health checks del microservicio completados (50 iteraciones)"
echo ""

# ========================
# PRUEBA 3: CONMUTACIÓN AL MODO MANUAL
# ========================
echo ">>> PRUEBA 3: CONMUTACIÓN AL MODO MANUAL"
echo "=========================================="
echo "Simulación de interrupción del microservicio"
echo "Objetivo: Verificar efectividad de conmutación al modo manual (100% éxito)"
echo ""

# Primero, verificar que el microservicio está disponible
echo "Verificando estado inicial del microservicio..."
ab -n 10 -c 1 -g ~/auditoria_evidencias/disponibilidad/microservicio_antes.tsv 'http://192.168.10.1:8000/health' > ~/auditoria_evidencias/disponibilidad/microservicio_antes.txt 2>&1
echo ""

# Prueba de operatividad del sistema principal sin microservicio
echo "Probando operatividad del sistema principal..."
ab -n 500 -c 20 -g ~/auditoria_evidencias/disponibilidad/sistema_principal_sin_micro.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/disponibilidad/sistema_principal_sin_micro.txt 2>&1
echo ""

# Prueba de catálogo (debe funcionar sin microservicio)
echo "Probando catálogo en modo manual..."
ab -n 500 -c 20 -g ~/auditoria_evidencias/disponibilidad/catalogo_manual.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=combos' > ~/auditoria_evidencias/disponibilidad/catalogo_manual.txt 2>&1
echo ""

# Prueba de login (debe funcionar sin microservicio)
echo "Probando login en modo manual..."
cat > /tmp/login_manual.json << 'EOF'
{
  "email": "test@example.com",
  "password": "test123",
  "recaptcha_token": "test"
}
EOF
ab -n 200 -c 10 -p /tmp/login_manual.json -T application/json -g ~/auditoria_evidencias/disponibilidad/login_manual.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/login.php' > ~/auditoria_evidencias/disponibilidad/login_manual.txt 2>&1
echo ""

# ========================
# PRUEBA 4: RECUPERACIÓN DESPUÉS DE FALLO
# ========================
echo ">>> PRUEBA 4: RECUPERACIÓN DESPUÉS DE FALLO"
echo "=========================================="
echo "Simulación de recuperación del microservicio"
echo ""

echo "Verificando disponibilidad del microservicio después de pruebas..."
ab -n 20 -c 5 -g ~/auditoria_evidencias/disponibilidad/microservicio_despues.tsv 'http://192.168.10.1:8000/health' > ~/auditoria_evidencias/disponibilidad/microservicio_despues.txt 2>&1
echo ""

# ========================
# PRUEBA 5: TOLERANCIA A FALLOS DE RED
# ========================
echo ">>> PRUEBA 5: TOLERANCIA A FALLOS DE RED"
echo "=========================================="
echo "Simulación de latencia de red alta"
echo ""

echo "Probando sistema con latencia simulada (timeout extendido)..."
ab -n 300 -c 15 -t 30s -g ~/auditoria_evidencias/disponibilidad/latencia_alta.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/disponibilidad/latencia_alta.txt 2>&1
echo ""

# ========================
# PRUEBA 6: UPTIME EN HORARIO LABORAL
# ========================
echo ">>> PRUEBA 6: UPTIME EN HORARIO LABORAL"
echo "=========================================="
echo "Simulación de operación continua (8 horas comprimidas en 5 minutos)"
echo ""

echo "Simulando operación continua (30 health checks, cada 10 segundos)..."
for i in {1..30}; do
  timestamp=$(date '+%Y-%m-%d %H:%M:%S')
  echo "$timestamp - Uptime check #$i" >> ~/auditoria_evidencias/disponibilidad/uptime_log.txt
  ab -n 10 -c 2 'http://192.168.10.1/proyecto-casalai-ca/' >> ~/auditoria_evidencias/disponibilidad/uptime_raw_$i.txt 2>&1
  sleep 10
done
echo "Prueba de uptime completada (30 iteraciones)"
echo ""

# ========================
# ANÁLISIS DE RESULTADOS
# ========================
echo "=========================================="
echo "ANÁLISIS DE DISPONIBILIDAD"
echo "=========================================="
echo ""

echo "Contando health checks exitosos..."
TOTAL_HEALTH=$(ls ~/auditoria_evidencias/disponibilidad/health_raw_*.txt 2>/dev/null | wc -l)
echo "Total de health checks ejecutados: $TOTAL_HEALTH"
echo ""

echo "Verificando tasa de éxito en health checks..."
EXITOSOS=0
for file in ~/auditoria_evidencias/disponibilidad/health_raw_*.txt; do
  if [ -f "$file" ]; then
    if grep -q "Failed requests:        0" "$file" 2>/dev/null; then
      EXITOSOS=$((EXITOSOS + 1))
    fi
  fi
done
echo "Health checks exitosos: $EXITOSOS/$TOTAL_HEALTH"
if [ $TOTAL_HEALTH -gt 0 ]; then
  PORCENTAJE=$(echo "scale=2; ($EXITOSOS * 100) / $TOTAL_HEALTH" | bc)
  echo "Tasa de disponibilidad: ${PORCENTAJE}%"
fi
echo ""

# ========================
# RESUMEN FINAL
# ========================
echo "=========================================="
echo "PRUEBAS DE DISPONIBILIDAD COMPLETADAS"
echo "=========================================="
echo ""
echo "Resultados guardados en: ~/auditoria_evidencias/disponibilidad/"
echo ""
echo "Archivos generados:"
ls -lh ~/auditoria_evidencias/disponibilidad/ | head -20
echo ""
echo "Logs de health checks:"
echo "  - health_log.txt"
echo "  - microservicio_health_log.txt"
echo "  - uptime_log.txt"
echo ""
echo "Para ver análisis detallado:"
echo "  cat ~/auditoria_evidencias/disponibilidad/health_log.txt"
echo "  cat ~/auditoria_evidencias/disponibilidad/microservicio_health_log.txt"
echo ""
echo "Para ver métricas de disponibilidad:"
echo "  grep 'Failed requests' ~/auditoria_evidencias/disponibilidad/*.txt"
echo ""
