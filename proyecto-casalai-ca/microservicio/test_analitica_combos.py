#!/usr/bin/env python3
"""
Script de prueba para verificar generación de combos en el microservicio de analítica
"""

import json
import urllib.request
import urllib.error
from datetime import date

# Configuración
API_URL = "http://127.0.0.1:8000"
ANALITICA_TOKEN = "7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M"

# Conexión a la base de datos para obtener datos reales
import mysql.connector

try:
    conn = mysql.connector.connect(
        host='localhost',
        database='casalai_principal',
        user='root',
        password=''
    )
    cursor = conn.cursor(dictionary=True)

    # Obtener productos
    cursor.execute("SELECT id_producto, nombre_producto, stock, stock_minimo, id_categoria FROM tbl_productos WHERE stock IS NOT NULL AND stock_minimo IS NOT NULL")
    productos = cursor.fetchall()

    # Obtener ventas
    cursor.execute("""
        SELECT df.factura_id as id_factura, df.id_producto, df.cantidad, f.fecha
        FROM tbl_factura_detalle df
        JOIN tbl_facturas f ON df.factura_id = f.id_factura
        WHERE f.fecha >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    """)
    ventas = cursor.fetchall()

    print(f"[OK] Productos cargados: {len(productos)}")
    print(f"[OK] Ventas cargadas: {len(ventas)}")

    # Preparar payload
    productos_payload = [
        {
            "id_producto": p["id_producto"],
            "nombre_producto": p["nombre_producto"],
            "id_categoria": p["id_categoria"],
            "stock": p["stock"],
            "stock_minimo": p["stock_minimo"],
            "estado": "habilitado"
        }
        for p in productos
    ]

    ventas_payload = [
        {
            "id_factura": v["id_factura"],
            "id_producto": v["id_producto"],
            "fecha": str(v["fecha"]),
            "cantidad": v["cantidad"]
        }
        for v in ventas
    ]

    cursor.close()
    conn.close()

except Exception as e:
    print(f"[ERROR] Error conectando a la base de datos: {e}")
    exit(1)

# Probar endpoint /api/v1/analitica/dashboard
print("\n=== PROBANDO ENDPOINT /api/v1/analitica/dashboard ===")

payload = {
    "frecuencia": "mensual",
    "horizonte": 30,
    "productos": productos_payload,
    "ventas": ventas_payload
}

headers = {
    "Content-Type": "application/json",
    "X-Analitica-Token": ANALITICA_TOKEN
}

try:
    req = urllib.request.Request(
        f"{API_URL}/api/v1/analitica/dashboard",
        data=json.dumps(payload).encode('utf-8'),
        headers=headers,
        method='POST'
    )

    with urllib.request.urlopen(req, timeout=60) as response:
        if response.status == 200:
            data = json.loads(response.read().decode('utf-8'))
            print(f"[OK] Peticion exitosa (HTTP {response.status})")

            # Verificar combos
            if "combos_estrategicos" in data:
                combos = data["combos_estrategicos"]
                print(f"\n[OK] Combos estrategicos generados: {len(combos)}")

                if len(combos) > 0:
                    print("\n--- TOP 5 COMBOS ---")
                    for i, combo in enumerate(combos[:5], 1):
                        print(f"\n{i}. {combo.get('producto', 'N/A')}")
                        print(f"   ID: {combo.get('recomendacion_id', 'N/A')}")
                        print(f"   Senal: {combo.get('senal', 'N/A')}")
                        print(f"   Confianza: {combo.get('confianza', 0):.2f} ({combo.get('nivel_confianza', 'N/A')})")
                        print(f"   Descuento: {combo.get('descuento_sugerido', 0)}%")
                        print(f"   Cantidad sugerida: {combo.get('cantidad_sugerida', 'N/A')}")
                        print(f"   Potencial desbloqueo: {combo.get('potencial_desbloqueo', 0):.2f}")
                        print(f"   Co-ocurrencia historica: {combo.get('co_ocurrencia_historica', 0)}")
                else:
                    print("[WARNING] No se generaron combos")
            else:
                print("[WARNING] La respuesta no contiene 'combos_estrategicos'")
                print(f"Claves disponibles: {list(data.keys())}")
        else:
            print(f"[ERROR] Error en peticion (HTTP {response.status})")
            print(f"Respuesta: {response.read().decode('utf-8')}")

except urllib.error.HTTPError as e:
    print(f"[ERROR] Error HTTP {e.code}: {e.reason}")
    print(f"Respuesta: {e.read().decode('utf-8')}")
except Exception as e:
    print(f"[ERROR] Error en la peticion: {e}")
