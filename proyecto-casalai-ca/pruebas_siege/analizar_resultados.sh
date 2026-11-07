#!/bin/bash
# Script para analizar y generar reporte de resultados de Siege

echo "=========================================="
echo "ANÁLISIS DE RESULTADOS DE PRUEBAS DE CARGA"
echo "=========================================="
echo ""

MODULOS=("login" "catalogo" "ocr" "ventas")
ESCENARIOS=("bajo" "medio" "alto")

for modulo in "${MODULOS[@]}"; do
    RESULTADOS_DIR="resultados_${modulo}"

    if [ ! -d "$RESULTADOS_DIR" ]; then
        echo "⚠️  No se encontraron resultados para módulo: $modulo"
        echo ""
        continue
    fi

    echo "=========================================="
    echo "MÓDULO: $modulo"
    echo "=========================================="
    echo ""

    for escenario in "${ESCENARIOS[@]}"; do
        LOG_FILE="${RESULTADOS_DIR}/escenario1_${escenario}.log"

        if [ -f "$LOG_FILE" ]; then
            echo ">>> Escenario: $escenario"
            echo "Archivo: $LOG_FILE"
            echo ""

            # Extraer métricas clave del log de Siege
            if [ -s "$LOG_FILE" ]; then
                echo "Estadísticas del log:"
                # Total de transacciones
                TOTAL=$(grep -c "HTTP" "$LOG_FILE" 2>/dev/null || echo "0")
                echo "  - Total de solicitudes: $TOTAL"

                # Respuestas exitosas
                EXITO=$(grep -c "200" "$LOG_FILE" 2>/dev/null || echo "0")
                echo "  - Exitosas (200): $EXITO"

                # Fallos
                FALLOS=$(grep -c -E "4[0-9]{2}|5[0-9]{2}" "$LOG_FILE" 2>/dev/null || echo "0")
                echo "  - Fallidas (4xx/5xx): $FALLOS"

                # Tasa de éxito
                if [ "$TOTAL" -gt 0 ]; then
                    TASA=$(echo "scale=2; ($EXITO * 100) / $TOTAL" | bc)
                    echo "  - Tasa de éxito: ${TASA}%"
                fi

                # Tiempos de respuesta (si están disponibles en formato CSV)
                if grep -q "," "$LOG_FILE"; then
                    # Procesar si el log está en formato CSV
                    echo "  - Tiempos de respuesta disponibles en el log"
                fi
            else
                echo "  (Log vacío)"
            fi
            echo ""
        else
            echo ">>> Escenario: $escenario - NO EJECUTADO"
            echo ""
        fi
    done
done

echo "=========================================="
echo "ANÁLISIS COMPLETADO"
echo "=========================================="
echo ""
echo "Para análisis detallado, use:"
echo "  siege --log=ARCHIVO.log --file=URLS.txt --list"
