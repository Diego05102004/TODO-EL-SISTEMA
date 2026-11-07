#!/bin/bash
# Script maestro para ejecutar todas las pruebas de carga
# Ejecutar en Kali Linux con Siege

echo "=========================================="
echo "SUITE COMPLETA DE PRUEBAS DE CARGA"
echo "Proyecto Casalai CA"
echo "=========================================="
echo ""

# Verificar que Siege está instalado
if ! command -v siege &> /dev/null; then
    echo "ERROR: Siege no está instalado"
    echo "Instalar con: sudo apt-get install siege"
    exit 1
fi

echo "Siege está instalado: $(siege --version | head -n 1)"
echo ""

# Ejecutar pruebas de cada módulo
echo "Iniciando pruebas de todos los módulos..."
echo ""

# Login
echo "=========================================="
echo "1/4: Pruebas de Login"
echo "=========================================="
chmod +x run_pruebas_login.sh
./run_pruebas_login.sh
echo ""

# Catálogo
echo "=========================================="
echo "2/4: Pruebas de Catálogo"
echo "=========================================="
chmod +x run_pruebas_catalogo.sh
./run_pruebas_catalogo.sh
echo ""

# OCR
echo "=========================================="
echo "3/4: Pruebas de OCR (Recepción IA)"
echo "=========================================="
chmod +x run_pruebas_ocr.sh
./run_pruebas_ocr.sh
echo ""

# Ventas
echo "=========================================="
echo "4/4: Pruebas de Ventas Presenciales"
echo "=========================================="
chmod +x run_pruebas_ventas.sh
./run_pruebas_ventas.sh
echo ""

echo "=========================================="
echo "TODAS LAS PRUEBAS COMPLETADAS"
echo "=========================================="
echo ""
echo "Resultados disponibles en:"
echo "  - resultados_login/"
echo "  - resultados_catalogo/"
echo "  - resultados_ocr/"
echo "  - resultados_ventas/"
echo ""
echo "Para ver estadísticas de un módulo:"
echo "  siege --log=RESULTADOS/ARCHIVO.log --file=URLS.txt --list"
