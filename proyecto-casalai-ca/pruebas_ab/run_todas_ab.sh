#!/bin/bash
# Script maestro para ejecutar todas las pruebas de ApacheBench
# Ejecutar en Kali Linux

echo "=========================================="
echo "SUITE COMPLETA DE PRUEBAS DE SERVIDOR"
echo "ApacheBench (ab) - Proyecto Casalai CA"
echo "=========================================="
echo ""

# Verificar que ApacheBench está instalado
if ! command -v ab &> /dev/null; then
    echo "ERROR: ApacheBench no está instalado"
    echo "Instalar con: sudo apt-get install apache2-utils"
    exit 1
fi

echo "ApacheBench está instalado: $(ab -V 2>&1 | head -n 1)"
echo ""

# Ejecutar pruebas generales
echo "=========================================="
echo "1/3: Pruebas Generales de Servidor"
echo "=========================================="
chmod +x run_completo_apachebench.sh
./run_completo_apachebench.sh
echo ""

# Ejecutar pruebas específicas de MySQL
echo "=========================================="
echo "2/3: Pruebas Específicas de MySQL"
echo "=========================================="
chmod +x pruebas_especificas_mysql.sh
./pruebas_especificas_mysql.sh
echo ""

# Ejecutar pruebas de disponibilidad
echo "=========================================="
echo "3/3: Pruebas de Disponibilidad"
echo "=========================================="
chmod +x pruebas_disponibilidad.sh
./pruebas_disponibilidad.sh
echo ""

echo "=========================================="
echo "TODAS LAS PRUEBAS DE SERVIDOR COMPLETADAS"
echo "=========================================="
echo ""
echo "Resultados disponibles en:"
echo "  - ~/auditoria_evidencias/ (pruebas generales)"
echo "  - ~/auditoria_evidencias/mysql_especifico/ (pruebas de DB)"
echo "  - ~/auditoria_evidencias/disponibilidad/ (pruebas de uptime)"
echo ""
echo "Para ver resumen de resultados:"
echo "  grep 'Requests per second' ~/auditoria_evidencias/**/*.txt"
echo "  grep 'Failed requests' ~/auditoria_evidencias/**/*.txt"
