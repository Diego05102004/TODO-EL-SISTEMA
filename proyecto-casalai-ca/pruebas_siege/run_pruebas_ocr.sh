#!/bin/bash
# Script de pruebas de carga para Módulo de Recepción IA (OCR)
# Ejecutar en Kali Linux con Siege
# NOTA: Las pruebas de OCR requieren archivos de imagen reales

BASE_URL="http://192.168.10.1:8000"
URLS_FILE="urls_ocr.txt"
OUTPUT_DIR="resultados_ocr"

mkdir -p $OUTPUT_DIR

echo "=========================================="
echo "PRUEBAS DE CARGA - MÓDULO DE RECEPCIÓN IA (OCR)"
echo "=========================================="
echo ""

# Escenario 1: Pocos Usuarios (1-5 concurrentes)
echo ">>> Escenario 1: Pocos Usuarios (1-5 concurrentes)"
echo "Criterio: 1.5 segundos (TMR)"
echo "Comportamiento esperado: Extracción rápida de texto de factura digital sin retardos"
echo ""
siege -c 3 -r 5 -f $URLS_FILE --log=$OUTPUT_DIR/escenario1_bajo.log --timestamp
echo ""

# Escenario 2: Usuarios Intermedios (6-15 concurrentes)
echo ">>> Escenario 2: Usuarios Intermedios (6-15 concurrentes)"
echo "Criterio: 2.8 segundos (TMR)"
echo "Comportamiento esperado: El motor OCR (Tesseract) procesa imágenes con alta resolución fluidamente"
echo ""
siege -c 10 -r 10 -f $URLS_FILE --log=$OUTPUT_DIR/escenario2_medio.log --timestamp
echo ""

# Escenario 3: Muchos Usuarios / Pico (16-35+ concurrentes)
echo ">>> Escenario 3: Muchos Usuarios / Pico (16-35+ concurrentes)"
echo "Criterio: 4.2 segundos (Límite RNF11: 5s bajo carga)"
echo "Comportamiento esperado: Soporta ráfagas de hasta 15+ solicitudes simultáneas sin degradación crítica"
echo ""
siege -c 20 -r 15 -f $URLS_FILE --log=$OUTPUT_DIR/escenario3_alto.log --timestamp
echo ""

echo "=========================================="
echo "Pruebas de OCR completadas"
echo "Resultados guardados en: $OUTPUT_DIR/"
echo "=========================================="
