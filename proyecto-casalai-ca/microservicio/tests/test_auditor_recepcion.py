import unittest

from microservicio.modelo.auditor_recepcion import AuditorRecepcion


class ExtraccionProductosFacturaTest(unittest.TestCase):
    def test_extrae_items_multilinea_y_reconcilia_precio_ocr(self):
        texto_ocr = """DESCRIPCION / MODELO MARCA SERIAL UNITARIO CANT. TOTAL
HP. DeskJet.2775 HP 0002 $4520.00 2 $3040.00
DeskJet'2775
Tinta HP Original GT HP. 0013 $250.00 6 $1500.00
662
Tarjeta SD Kingston -Azon 0008 $80.00 10 $800.00
Sublime
Subtotal: $5340.00,
IVA (16%): $854.40
Total: $6194.40"""
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)

        productos = auditor._extraer_productos(texto_ocr)

        self.assertEqual(
            [
                (producto.nombre, producto.modelo, producto.marca, producto.serial,
                 producto.cantidad, producto.costo_unitario)
                for producto in productos
            ],
            [
                ("HP DeskJet 2775", "DeskJet 2775", "HP", "0002", 2, 1520.0),
                ("Tinta HP Original GT", "662", "HP", "0013", 6, 250.0),
                ("Tarjeta SD Kingston", "Sublime", "Azon", "0008", 10, 80.0),
            ],
        )

    def test_extrae_subtotal_iva_y_total_por_separado(self):
        texto_ocr = """DESCRIPCION / MODELO MARCA SERIAL UNITARIO CANT. TOTAL
HP. DeskJet.2775 HP 0002 $4520.00 2 $3040.00
DeskJet'2775
Tinta HP Original GT HP. 0013 $250.00 6 $1500.00
662
Tarjeta SD Kingston -Azon 0008 $80.00 10 $800.00
Sublime
Subtotal: $5340.00,
IVA (16%): $854.40
Total: $6194.40"""
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)

        factura = auditor._extraer_campos_factura(texto_ocr)

        self.assertEqual(factura.subtotal_factura, 5340.0)
        self.assertEqual(factura.porcentaje_iva, 16.0)
        self.assertEqual(factura.monto_iva, 854.4)
        self.assertEqual(factura.total_factura, 6194.4)

    def test_campos_separados_no_generan_discrepancias_falsas(self):
        texto_ocr = """DESCRIPCION / MODELO MARCA SERIAL UNITARIO CANT. TOTAL
HP. DeskJet.2775 HP 0002 $4520.00 2 $3040.00
DeskJet'2775
Tinta HP Original GT HP. 0013 $250.00 6 $1500.00
662
Tarjeta SD Kingston -Azon 0008 $80.00 10 $800.00
Sublime"""
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)
        auditor.config = {"tolerancia_costo": 0.05}
        productos_factura = auditor._extraer_productos(texto_ocr)
        productos_formulario = [
            {"nombre": "HP DeskJet 2775", "modelo": "DeskJet 2775", "marca": "HP", "serial": "0002", "cantidad": 2, "costo": 1520.0},
            {"nombre": "Tinta HP Original GT", "modelo": "662", "marca": "HP", "serial": "0013", "cantidad": 6, "costo": 250.0},
            {"nombre": "Tarjeta SD Kingston", "modelo": "Sublime", "marca": "Azon", "serial": "0008", "cantidad": 10, "costo": 80.0},
        ]

        discrepancias = [
            diferencia
            for indice, producto in enumerate(productos_formulario)
            for diferencia in auditor._verificar_producto(productos_factura, producto, indice)
        ]

        self.assertEqual(discrepancias, [])

    def test_factura_sin_iva_no_inventa_impuesto(self):
        texto_ocr = """DESCRIPCION MARCA SERIAL UNITARIO CANT. TOTAL
Cable USB HP 0001 $10.00 2 $20.00
Subtotal: $20.00
Total: $20.00"""
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)

        factura = auditor._extraer_campos_factura(texto_ocr)

        self.assertEqual(factura.subtotal_factura, 20.0)
        self.assertEqual(factura.porcentaje_iva, 0.0)
        self.assertEqual(factura.monto_iva, 0.0)
        self.assertEqual(factura.total_factura, 20.0)


if __name__ == "__main__":
    unittest.main()