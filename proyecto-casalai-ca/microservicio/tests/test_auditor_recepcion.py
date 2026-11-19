import unittest

from microservicio.modelo.auditor_recepcion import (
    AuditorRecepcion,
    PATRONES_FILE,
    ProductoExtraido,
    cargar_patrones_yaml,
)


class ExtraccionProductosFacturaTest(unittest.TestCase):
    def crear_auditor_sin_bd(self):
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)
        auditor.patrones = auditor.PATRONES_POR_DEFECTO
        auditor.adaptador_bd = None
        auditor.catalogo_productos = []
        auditor.catalogo_proveedores = []
        return auditor

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
        auditor = self.crear_auditor_sin_bd()

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
        auditor = self.crear_auditor_sin_bd()

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
        auditor = self.crear_auditor_sin_bd()
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
        auditor = self.crear_auditor_sin_bd()

        factura = auditor._extraer_campos_factura(texto_ocr)

        self.assertEqual(factura.subtotal_factura, 20.0)
        self.assertEqual(factura.porcentaje_iva, 0.0)
        self.assertEqual(factura.monto_iva, 0.0)
        self.assertEqual(factura.total_factura, 20.0)


class ValidacionCatalogoFacturaTest(unittest.TestCase):
    def crear_auditor_sin_bd(self):
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)
        auditor.patrones = cargar_patrones_yaml(PATRONES_FILE)
        auditor.catalogo_productos = []
        auditor.catalogo_proveedores = []
        return auditor

    def test_numero_factura_requiere_seis_digitos_ascii_y_contexto(self):
        auditor = self.crear_auditor_sin_bd()
        configuraciones = (
            auditor.patrones["numero_factura"],
            auditor.PATRONES_POR_DEFECTO["numero_factura"],
        )

        for patrones in configuraciones:
            for texto in (
                "Factura: 012345",
                "Factura: 012345.",
                "Factura N°: 012345",
                "N° de factura:\n012345",
            ):
                with self.subTest(texto=texto, patrones=patrones):
                    self.assertEqual(
                        auditor._extraer_numero_factura(texto, patrones),
                        "012345",
                    )

            for texto in (
                "Factura: 12345",
                "Factura: 1234567",
                "Factura: INV-123456",
                "Factura: 123456-A",
                "Factura: １２３４５６",
            ):
                with self.subTest(texto=texto, patrones=patrones):
                    self.assertEqual(
                        auditor._extraer_numero_factura(texto, patrones),
                        "",
                    )

    def test_proveedor_se_devuelve_solo_si_coincide_con_el_catalogo(self):
        auditor = self.crear_auditor_sin_bd()
        auditor.catalogo_proveedores = [{"id": 3, "nombre": "Oficina Real S.A."}]

        self.assertEqual(
            auditor._extraer_proveedor_catalogo(
                "FACTURA\nLA TRANSACCION Sim\nSubtotal: 100.00"
            ),
            "",
        )
        self.assertEqual(
            auditor._extraer_proveedor_catalogo("Proveedor: Oficina Real S.A."),
            "Oficina Real S.A.",
        )

    def test_producto_se_canonicaliza_separando_modelo_del_nombre(self):
        auditor = self.crear_auditor_sin_bd()
        auditor.catalogo_productos = [{
            "id": 11,
            "nombre": "Auriculares Bluetooth",
            "modelo": "Buds 6 Lite",
            "marca": "Xiaomi",
        }]
        producto = ProductoExtraido(nombre="Auriculares Bluetooth Buds 6 Lite")

        producto_enriquecido = auditor._enriquecer_productos_con_catalogo([producto])[0]

        self.assertEqual(producto_enriquecido.nombre, "Auriculares Bluetooth")
        self.assertEqual(producto_enriquecido.modelo, "Buds 6 Lite")

    def test_producto_que_no_coincide_con_catalogo_no_se_asigna(self):
        auditor = self.crear_auditor_sin_bd()
        auditor.catalogo_productos = [{
            "id": 11,
            "nombre": "Auriculares Bluetooth",
            "modelo": "Buds 6 Lite",
            "marca": "Xiaomi",
        }]
        producto = ProductoExtraido(nombre="Teclado Mecánico K55")

        self.assertIsNone(auditor._buscar_producto_en_catalogo(producto))

    def test_producto_parcial_no_se_asigna_sin_confirmacion_de_modelo(self):
        auditor = self.crear_auditor_sin_bd()
        auditor.catalogo_productos = [{
            "id": 11,
            "nombre": "Auriculares Bluetooth",
            "modelo": "",
            "marca": "",
        }]
        producto = ProductoExtraido(nombre="Auriculares Bluetooth Buds 6 Lite")

        self.assertIsNone(auditor._buscar_producto_en_catalogo(producto))


if __name__ == "__main__":
    unittest.main()