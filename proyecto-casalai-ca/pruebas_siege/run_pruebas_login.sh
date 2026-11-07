#!/bin/bash
# Script de pruebas de carga para Módulo de Autenticación (Login)
# Ejecutar en Kali Linux con Siege

BASE_URL="http://192.168.10.1:8000"
URLS_FILE="urls_login.txt"
OUTPUT_DIR="resultados_login"

mkdir -p $OUTPUT_DIR

echo "=========================================="
echo "PRUEBAS DE CARGA - MÓDULO DE AUTENTICACIÓN"
echo "=========================================="
echo ""

# Escenario 1: Pocos Usuarios (1-5 concurrentes)
echo ">>> Escenario 1: Pocos Usuarios (1-5 concurrentes)"
echo "Criterio: 0.4 segundos"
echo "Comportamiento esperado: Acceso instantáneo y redirección fluida"
echo ""
siege -c 3 -r 10 -f $URLS_FILE --log=$OUTPUT_DIR/escenario1_bajo.log --timestamp
echo ""

# Escenario 2: Usuarios Intermedios (6-15 concurrentes)
echo ">>> Escenario 2: Usuarios Intermedios (6-15 concurrentes)"
echo "Criterio: 0.9 segundos"
echo "Comportamiento esperado: Consultas a tbl_usuarios responden de forma estable"
echo ""
siege -c 10 -r 20 -f $URLS_FILE --log=$OUTPUT_DIR/escenario2_medio.log --timestamp
echo ""

# Escenario 3: Muchos Usuarios / Pico (16-35+ concurrentes)
echo ">>> Escenario 3: Muchos Usuarios / Pico (16-35+ concurrentes)"
echo "Criterio: 1.8 segundos"
echo "Comportamiento esperado: Ligera cola de procesamiento sin pérdida de sesiones"
echo ""
siege -c 25 -r 30 -f $URLS_FILE --log=$OUTPUT_DIR/escenario3_alto.log --timestamp
echo ""

echo "=========================================="
echo "Pruebas de Login completadas"
echo "Resultados guardados en: $OUTPUT_DIR/"
echo "=========================================="
