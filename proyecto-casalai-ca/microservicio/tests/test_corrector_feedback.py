import json
import unittest

from modelo.corrector_feedback import (
    build_correction_rules,
    evaluate_corrections,
    suggest_corrections,
)


def build_row(reception_id, corrected_product_name="Cable USB 5 m"):
    predicted_products = [
        {
            "nombre": "Cable USB 5m",
            "modelo": "USB-5",
            "marca": "Acme",
            "cantidad": 2,
            "costo": 4.5,
        }
    ]
    corrected_products = [
        {
            "nombre": corrected_product_name,
            "modelo": "USB-5",
            "marca": "Acme",
            "cantidad": 2,
            "costo": 4.5,
        }
    ]
    return {
        "id_recepcion": reception_id,
        "prediccion_proveedor": "Distribuidora ACME",
        "ground_truth_proveedor": "Distribuidora Acme, C.A.",
        "prediccion_productos": json.dumps(predicted_products),
        "ground_truth_productos": str(corrected_products),
    }


def build_complete_feedback_row(reception_id):
    predicted_fields = {
        "numero_factura": "00012O",
        "fecha_factura": "2026-11-1?",
        "nombre_proveedor": "Distribuidora ACME",
        "productos": [
            {
                "nombre": "Cble USB",
                "modelo": "US8",
                "marca": "Acme",
                "serial": "00Ol",
                "cantidad": 3,
                "costo": 4.5,
            }
        ],
        "subtotal_factura": 9,
        "porcentaje_iva": 15,
        "monto_iva": 1.35,
        "total_factura": 10.35,
    }
    confirmed_fields = {
        "numero_factura": "000120",
        "fecha_factura": "2026-11-15",
        "nombre_proveedor": "Distribuidora Acme, C.A.",
        "productos": [
            {
                "nombre": "Cable USB",
                "modelo": "USB",
                "marca": "Acme",
                "serial": "0001",
                "cantidad": 2,
                "costo": 5,
            }
        ],
        "subtotal_factura": 10,
        "porcentaje_iva": 16,
        "monto_iva": 1.6,
        "total_factura": 11.6,
    }
    return {
        "id_recepcion": reception_id,
        "discrepancias_corregidas": json.dumps(
            {
                "campos_predichos": predicted_fields,
                "campos_confirmados": confirmed_fields,
            }
        ),
    }


class FeedbackCorrectionTest(unittest.TestCase):
    def test_learns_only_unanimous_aliases_with_independent_support(self):
        rows = [build_row(1), build_row(2)]

        corrections = build_correction_rules(rows)

        self.assertEqual(len(corrections["proveedor"]), 1)
        self.assertEqual(corrections["proveedor"][0]["support"], 2)
        self.assertEqual(len(corrections["producto"]), 1)
        self.assertEqual(corrections["producto"][0]["target"], {"nombre": "Cable USB 5 m"})
        self.assertEqual(corrections["producto"][0]["support"], 2)
        suggestions = suggest_corrections(
            corrections,
            "distribuidora acme",
            [
                {
                    "nombre": "Cable USB 5m",
                    "modelo": "USB-5",
                    "marca": "Acme",
                    "cantidad": 2,
                    "costo": 4.5,
                }
            ],
        )
        self.assertEqual(len(suggestions), 2)
        self.assertTrue(all(item["requiere_confirmacion"] for item in suggestions))

    def test_learns_and_suggests_every_document_and_product_field(self):
        rows = [build_complete_feedback_row(1), build_complete_feedback_row(2)]

        corrections = build_correction_rules(rows)
        predicted_fields = json.loads(
            rows[0]["discrepancias_corregidas"]
        )["campos_predichos"]
        suggestions = suggest_corrections(
            corrections,
            "Distribuidora ACME",
            predicted_fields["productos"],
            predicted_fields,
        )

        suggested_fields = {item["campo"] for item in suggestions}
        self.assertTrue(
            {
                "proveedor",
                "numero_factura",
                "subtotal_factura",
                "porcentaje_iva",
                "monto_iva",
                "total_factura",
                "producto",
            }.issubset(suggested_fields)
        )
        self.assertNotIn("fecha_factura", suggested_fields)
        self.assertNotIn("fecha_factura", corrections["campos_factura"])
        self.assertEqual(len(corrections["campos_factura"]), 5)
        product_suggestion = next(
            item for item in suggestions if item["campo"] == "producto"
        )
        self.assertEqual(
            set(product_suggestion["valor_sugerido"]),
            {"nombre", "modelo", "serial", "cantidad", "costo"},
        )
        self.assertEqual(product_suggestion["evidencia"], 2)

    def test_abstains_when_support_is_insufficient_or_targets_conflict(self):
        one_receipt = build_correction_rules([build_row(1)])
        self.assertEqual(
            one_receipt,
            {"proveedor": [], "producto": [], "campos_factura": {}},
        )

        conflicting = [
            build_row(1),
            build_row(2),
            build_row(3, "Cable USB corto"),
            build_row(4, "Cable USB corto"),
        ]
        corrections = build_correction_rules(conflicting)
        self.assertEqual(corrections["proveedor"][0]["support"], 4)
        self.assertEqual(corrections["producto"], [])

        one_conflict = [
            build_row(1),
            build_row(2),
            build_row(3),
            build_row(4, "Cable USB corto"),
        ]
        self.assertEqual(build_correction_rules(one_conflict)["producto"], [])

    def test_numeric_document_rules_do_not_transfer_to_different_product_sets(self):
        rows = [build_complete_feedback_row(1), build_complete_feedback_row(2)]
        corrections = build_correction_rules(rows)
        predicted_fields = json.loads(
            rows[0]["discrepancias_corregidas"]
        )["campos_predichos"]
        unrelated_products = [
            {
                "nombre": "Mouse inalámbrico",
                "modelo": "M-1",
                "marca": "Acme",
                "cantidad": 1,
                "costo": 9,
            }
        ]

        suggestions = suggest_corrections(
            corrections,
            "Distribuidora ACME",
            unrelated_products,
            predicted_fields,
        )

        self.assertNotIn(
            "subtotal_factura",
            {item["campo"] for item in suggestions},
        )
        self.assertNotIn(
            "total_factura",
            {item["campo"] for item in suggestions},
        )

    def test_duplicate_receipt_ids_do_not_inflate_evidence(self):
        corrections = build_correction_rules([build_row(7), build_row(7)])

        self.assertEqual(
            corrections,
            {"proveedor": [], "producto": [], "campos_factura": {}},
        )

    def test_missing_receipt_ids_are_not_counted_as_independent_evidence(self):
        rows = [build_row(1), build_row(2)]
        for row in rows:
            row["id_recepcion"] = ""

        self.assertEqual(
            build_correction_rules(rows),
            {"proveedor": [], "producto": [], "campos_factura": {}},
        )

    def test_leave_one_receipt_out_metrics_measure_document_level_suggestions(self):
        rows = [build_row(1), build_row(2), build_row(3)]

        metrics = evaluate_corrections(rows)

        self.assertEqual(metrics["metodo"], "leave_one_reception_out")
        self.assertEqual(metrics["documentos_evaluados"], 3)
        self.assertEqual(metrics["documentos_con_sugerencias"], 3)
        self.assertEqual(metrics["precision_campos_sugeridos"], 1.0)
        self.assertEqual(metrics["cobertura_campos_evaluables"], 1.0)

    def test_evaluation_reports_metrics_per_extracted_field(self):
        rows = [
            build_complete_feedback_row(1),
            build_complete_feedback_row(2),
            build_complete_feedback_row(3),
        ]

        metrics = evaluate_corrections(rows)

        self.assertNotIn("fecha_factura", metrics["por_campo"])
        self.assertEqual(
            metrics["por_campo"]["producto.serial"]["precision"],
            1.0,
        )
        self.assertEqual(
            metrics["por_campo"]["producto.costo"]["precision"],
            1.0,
        )


if __name__ == "__main__":
    unittest.main()
