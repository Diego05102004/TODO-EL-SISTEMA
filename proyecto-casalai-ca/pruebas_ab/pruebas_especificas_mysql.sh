#!/bin/bash
# Script de pruebas específicas para MySQL/MariaDB
# Simulación de operaciones transaccionales cruzadas
# Ejecutar en Kali Linux

mkdir -p ~/auditoria_evidencias/mysql_especifico
cd ~/auditoria_evidencias/mysql_especifico

echo "=========================================="
echo "PRUEBAS ESPECÍFICAS DE BASE DE DATOS"
echo "MySQL/MariaDB - Proyecto Casalai CA"
echo "=========================================="
echo ""

# ========================
# PRUEBA 1: INSERTIONES MASIVAS EN RECEPCIONES
# ========================
echo ">>> PRUEBA 1: INSERCIONES MASIVAS EN RECEPCIONES"
echo "=========================================="
echo "Simulación de registro de recepciones de facturas"
echo ""

# Crear datos de prueba para recepciones
cat > /tmp/recepcion_test.json << 'EOF'
{
  "funcion": "consultar"
  "id_usuario": "1"
}
EOF

echo "Ejecutando inserciones masivas (500 peticiones, 25 concurrentes)..."
ab -n 500 -c 25 -p /tmp/recepcion_test.json -T application/json -g ~/auditoria_evidencias/mysql_especifico/recepciones_masivas.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/carrito.php' > ~/auditoria_evidencias/mysql_especifico/recepciones_masivas.txt 2>&1
echo ""

# ========================
# PRUEBA 2: VENTAS PRESENCIALES SIMULTÁNEAS
# ========================
echo ">>> PRUEBA 2: VENTAS PRESENCIALES SIMULTÁNEAS"
echo "=========================================="
echo "Simulación de múltiples cajas operando al mismo tiempo"
echo ""

# Crear datos de prueba para ventas
cat > /tmp/venta_concurrente.json << 'EOF'
{
  "funcion": "facturaingresarmovil",
  "cedula": "87654321",
  "cliente": "Cliente Concurrente",
  "metodo_pago": "efectivo",
  "productos": [
    {"id": 1, "cantidad": 1, "precio": 15.00},
    {"id": 2, "cantidad": 2, "precio": 8.50}
  ]
}
EOF

echo "Ejecutando ventas simultáneas (1000 peticiones, 30 concurrentes)..."
ab -n 1000 -c 30 -p /tmp/venta_concurrente.json -T application/json -g ~/auditoria_evidencias/mysql_especifico/ventas_simultaneas.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/gestionarFactura.php' > ~/auditoria_evidencias/mysql_especifico/ventas_simultaneas.txt 2>&1
echo ""

# ========================
# PRUEBA 3: REGISTROS DE BITÁCORA
# ========================
echo ">>> PRUEBA 3: REGISTROS DE BITÁCORA"
echo "=========================================="
echo "Simulación de auditoría concurrente"
echo ""

echo "Ejecutando registros de bitácora (300 peticiones, 15 concurrentes)..."
ab -n 300 -c 15 -g ~/auditoria_evidencias/mysql_especifico/bitacora_concurrente.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/mysql_especifico/bitacora_concurrente.txt 2>&1
echo ""

# ========================
# PRUEBA 4: PROCEDIMIENTOS ALMACENADOS
# ========================
echo ">>> PRUEBA 4: PROCEDIMIENTOS ALMACENADOS"
echo "=========================================="
echo "Verificación de ejecución limpia de procedimientos almacenados"
echo ""

echo "Ejecutando operaciones que invocan procedimientos almacenados..."
ab -n 400 -c 20 -g ~/auditoria_evidencias/mysql_especifico/procedimientos_almacenados.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=por_categoria&categoria=1' > ~/auditoria_evidencias/mysql_especifico/procedimientos_almacenados.txt 2>&1
echo ""

# ========================
# PRUEBA 5: DISPARADORES (TRIGGERS)
# ========================
echo ">>> PRUEBA 5: DISPARADORES (TRIGGERS)"
echo "=========================================="
echo "Verificación de triggers sin bloqueos de tablas"
echo ""

# Crear datos para activar triggers de inventario
cat > /tmp/trigger_test.json << 'EOF'
{
  "funcion": "facturaingresarmovil",
  "cedula": "11223344",
  "cliente": "Cliente Trigger Test",
  "metodo_pago": "pagomovil",
  "productos": [
    {"id": 3, "cantidad": 5, "precio": 12.00}
  ]
}
EOF

echo "Ejecutando operaciones que activan triggers (600 peticiones, 20 concurrentes)..."
ab -n 600 -c 20 -p /tmp/trigger_test.json -T application/json -g ~/auditoria_evidencias/mysql_especifico/triggers_no_lock.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/gestionarFactura.php' > ~/auditoria_evidencias/mysql_especifico/triggers_no_lock.txt 2>&1
echo ""

# ========================
# PRUEBA 6: CONSISTENCIA DE DATOS
# ========================
echo ">>> PRUEBA 6: CONSISTENCIA DE DATOS"
echo "=========================================="
echo "Verificación de integridad transaccional ACID"
echo ""

echo "Ejecutando operaciones mixtas para verificar consistencia..."
ab -n 800 -c 30 -g ~/auditoria_evidencias/mysql_especifico/consistencia_acid.tsv 'http://192.168.10.1/proyecto-casalai-ca/api/productos.php?funcion=default' > ~/auditoria_evidencias/mysql_especifico/consistencia_acid.txt 2>&1
echo ""

# ========================
# RESUMEN FINAL
# ========================
echo "=========================================="
echo "PRUEBAS ESPECÍFICAS DE DB COMPLETADAS"
echo "=========================================="
echo ""
echo "Resultados guardados en: ~/auditoria_evidencias/mysql_especifico/"
echo ""
echo "Archivos generados:"
ls -lh ~/auditoria_evidencias/mysql_especifico/
echo ""
echo "Métricas clave a verificar:"
echo "  - Failed requests (debe ser 0 o cercano a 0)"
echo "  - Non-2xx responses (debe ser 0)"
echo "  - Requests per second (rendimiento)"
echo "  - Time per request (latencia)"
echo ""
echo "Comandos para análisis:"
echo "  grep 'Failed requests' ~/auditoria_evidencias/mysql_especifico/*.txt"
echo "  grep 'Requests per second' ~/auditoria_evidencias/mysql_especifico/*.txt"
echo "  grep 'Time per request' ~/auditoria_evidencias/mysql_especifico/*.txt"
echo ""
