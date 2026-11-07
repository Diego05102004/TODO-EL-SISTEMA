#!/bin/bash
# Script completo de pruebas de carga - Copiar y pegar en Kali Linux
# Ejecuta todas las pruebas de los 4 módulos con los 3 escenarios

mkdir -p ~/auditoria_evidencias
cd ~/auditoria_evidencias

echo "=========================================="
echo "INICIANDO PRUEBAS DE CARGA COMPLETAS"
echo "Proyecto Casalai CA"
echo "=========================================="
echo ""

# ========================
# MÓDULO 1: AUTENTICACIÓN (LOGIN)
# ========================
echo ">>> MÓDULO 1: AUTENTICACIÓN (LOGIN)"
echo "=========================================="

# Crear archivo de datos de login
cat > /tmp/login_data.json << 'EOF'
{
  "email": "testuser@example.com",
  "password": "testpass123",
  "recaptcha_token": "test_token"
}
EOF

# Escenario 1: Bajo (1-5 concurrentes) - Criterio: 0.4s
echo "Escenario 1 Bajo (3 concurrentes, 30s) - Criterio: 0.4s"
siege -c 3 -t 30s -d1 --content-type="application/json" 'http://192.168.10.1/proyecto-casalai-ca/api/login.php POST < /tmp/login_data.json' > ~/auditoria_evidencias/login_escenario1_bajo.txt 2>&1
echo ""

# Escenario 2: Medio (6-15 concurrentes) - Criterio: 0.9s
echo "Escenario 2 Medio (10 concurrentes, 30s) - Criterio: 0.9s"
siege -c 10 -t 30s -d1 --content-type="application/json" 'http://192.168.10.1/proyecto-casalai-ca/api/login.php POST < /tmp/login_data.json' > ~/auditoria_evidencias/login_escenario2_medio.txt 2>&1
echo ""

# Escenario 3: Alto (16-35+ concurrentes) - Criterio: 1.8s
echo "Escenario 3 Alto (25 concurrentes, 30s) - Criterio: 1.8s"
siege -c 25 -t 30s -d1 --content-type="application/json" 'http://192.168.10.1/proyecto-casalai-ca/api/login.php POST < /tmp/login_data.json' > ~/auditoria_evidencias/login_escenario3_alto.txt 2>&1
echo ""

# ========================
# MÓDULO 2: CATÁLOGO
# ========================
echo ">>> MÓDULO 2: CATÁLOGO"
echo "=========================================="

# Escenario 1: Bajo (1-5 concurrentes) - Criterio: 0.5s
echo "Escenario 1 Bajo (3 concurrentes, 30s) - Criterio: 0.5s"
siege -c 3 -t 30s -d1 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/catalogo_escenario1_bajo.txt 2>&1
echo ""

# Escenario 2: Medio (6-15 concurrentes) - Criterio: 1.2s
echo "Escenario 2 Medio (10 concurrentes, 30s) - Criterio: 1.2s"
siege -c 10 -t 30s -d1 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/catalogo_escenario2_medio.txt 2>&1
echo ""

# Escenario 3: Alto (16-35+ concurrentes) - Criterio: 2.5s
echo "Escenario 3 Alto (25 concurrentes, 30s) - Criterio: 2.5s"
siege -c 25 -t 30s -d1 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/catalogo_escenario3_alto.txt 2>&1
echo ""

# ========================
# MÓDULO 3: OCR (MICROSERVICIO)
# ========================
echo ">>> MÓDULO 3: OCR (MICROSERVICIO)"
echo "=========================================="

# Escenario 1: Bajo (1-5 concurrentes) - Criterio: 1.5s
echo "Escenario 1 Bajo (3 concurrentes, 20s) - Criterio: 1.5s"
siege -c 3 -t 20s -d1 'http://192.168.10.1:8000/fase1/extraer' > ~/auditoria_evidencias/ocr_escenario1_bajo.txt 2>&1
echo ""

# Escenario 2: Medio (6-15 concurrentes) - Criterio: 2.8s
echo "Escenario 2 Medio (10 concurrentes, 20s) - Criterio: 2.8s"
siege -c 10 -t 20s -d1 'http://192.168.10.1:8000/fase1/extraer' > ~/auditoria_evidencias/ocr_escenario2_medio.txt 2>&1
echo ""

# Escenario 3: Alto (16-35+ concurrentes) - Criterio: 4.2s (Límite RNF11: 5s)
echo "Escenario 3 Alto (20 concurrentes, 20s) - Criterio: 4.2s (Límite: 5s)"
siege -c 20 -t 20s -d1 'http://192.168.10.1:8000/fase1/extraer' > ~/auditoria_evidencias/ocr_escenario3_alto.txt 2>&1
echo ""

# ========================
# MÓDULO 4: VENTAS PRESENCIALES
# ========================
echo ">>> MÓDULO 4: VENTAS PRESENCIALES"
echo "=========================================="

# Crear archivo de datos de ventas
cat > /tmp/ventas_data.json << 'EOF'
{
  "funcion": "facturaingresarmovil",
  "cedula": "12345678",
  "cliente": "Cliente Test",
  "metodo_pago": "efectivo",
  "productos": [
    {"id": 1, "cantidad": 2, "precio": 10.50}
  ]
}
EOF

# Escenario 1: Bajo (1-5 concurrentes) - Criterio: 0.6s
echo "Escenario 1 Bajo (3 concurrentes, 30s) - Criterio: 0.6s"
siege -c 3 -t 30s -d1 --content-type="application/json" 'http://192.168.10.1/proyecto-casalai-ca/api/gestionarFactura.php POST < /tmp/ventas_data.json' > ~/auditoria_evidencias/ventas_escenario1_bajo.txt 2>&1
echo ""

# Escenario 2: Medio (6-15 concurrentes) - Criterio: 1.4s
echo "Escenario 2 Medio (10 concurrentes, 30s) - Criterio: 1.4s"
siege -c 10 -t 30s -d1 --content-type="application/json" 'http://192.168.10.1/proyecto-casalai-ca/api/gestionarFactura.php POST < /tmp/ventas_data.json' > ~/auditoria_evidencias/ventas_escenario2_medio.txt 2>&1
echo ""

# Escenario 3: Alto (16-35+ concurrentes) - Criterio: 2.9s
echo "Escenario 3 Alto (25 concurrentes, 30s) - Criterio: 2.9s"
siege -c 25 -t 30s -d1 --content-type="application/json" 'http://192.168.10.1/proyecto-casalai-ca/api/gestionarFactura.php POST < /tmp/ventas_data.json' > ~/auditoria_evidencias/ventas_escenario3_alto.txt 2>&1
echo ""

# ========================
# RESUMEN FINAL
# ========================
echo "=========================================="
echo "PRUEBAS COMPLETADAS"
echo "=========================================="
echo ""
echo "Resultados guardados en: ~/auditoria_evidencias/"
echo ""
echo "Archivos generados:"
ls -lh ~/auditoria_evidencias/*.txt
echo ""
echo "Para ver un reporte específico:"
echo "  cat ~/auditoria_evidencias/NOMBRE_ARCHIVO.txt"
echo ""
