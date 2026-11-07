import tempfile
import unittest
from datetime import date, timedelta
from pathlib import Path
from unittest.mock import patch

from microservicio.modelo import analitica_predictiva as analytics


class AnaliticaPredictivaTest(unittest.TestCase):
    def setUp(self):
        self.temp_dir = tempfile.TemporaryDirectory()
        self.model_path = Path(self.temp_dir.name) / "demanda.json"
        self.feedback_path = Path(self.temp_dir.name) / "feedback.jsonl"
        self.model_patch = patch.object(analytics, "MODEL_PATH", self.model_path)
        self.feedback_patch = patch.object(analytics, "FEEDBACK_PATH", self.feedback_path)
        self.model_patch.start()
        self.feedback_patch.start()
        self.addCleanup(self.model_patch.stop)
        self.addCleanup(self.feedback_patch.stop)
        self.addCleanup(self.temp_dir.cleanup)
        self.products = [
            {
                "id_producto": 1,
                "nombre_producto": "Producto uno",
                "id_categoria": 10,
                "stock": 8,
                "stock_minimo": 2,
                "stock_maximo": 20,
                "estado": "habilitado",
            },
            {
                "id_producto": 2,
                "nombre_producto": "Producto dos",
                "id_categoria": 10,
                "stock": 5,
                "stock_minimo": 1,
                "stock_maximo": 15,
                "estado": "habilitado",
            },
        ]
        self.sales = []
        for week_ago in range(24):
            if week_ago % 3 == 0:
                continue
            sale_date = (date.today() - timedelta(weeks=week_ago)).isoformat()
            invoice_id = 1000 + week_ago
            self.sales.extend([
                {"id_factura": invoice_id, "id_producto": 1, "fecha": sale_date, "cantidad": 2 + week_ago % 4},
                {"id_factura": invoice_id, "id_producto": 2, "fecha": sale_date, "cantidad": 1},
            ])

    def test_trains_and_returns_nonnegative_forecast_with_interval(self):
        result = analytics.train_models(self.products, self.sales)

        self.assertGreaterEqual(result["modelos_entrenados"], 3)
        dashboard = analytics.build_dashboard({
            "producto_id": 1,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 15,
            "productos": self.products,
            "ventas": self.sales,
        })

        self.assertEqual(dashboard["calidad"]["estado"], "disponible")
        self.assertEqual(len(dashboard["prediccion"]), 15)
        self.assertTrue(all(item["valor"] >= 0 for item in dashboard["prediccion"]))
        self.assertTrue(all(
            item["limite_inferior"] <= item["valor"] <= item["limite_superior"]
            for item in dashboard["prediccion"]
        ))
        self.assertIsNotNone(dashboard["metricas"]["venta_proyectada"])
        category_dashboard = analytics.build_dashboard({
            "producto_id": None,
            "categoria_id": 10,
            "frecuencia": "semanal",
            "horizonte": 30,
            "productos": self.products,
            "ventas": self.sales,
        })
        self.assertEqual(category_dashboard["calidad"]["estado"], "disponible")
        self.assertIsNotNone(category_dashboard["metricas"]["venta_proyectada"])
        self.assertIsNone(category_dashboard["metricas"]["dias_estimados_agotamiento"])
        self.assertIsNone(category_dashboard["metricas"]["punto_reorden"])
        self.assertIsNone(category_dashboard["metricas"]["cantidad_sugerida_compra"])
        self.assertIsNone(category_dashboard["metricas"]["rotacion_proyectada"])

    def test_missing_inventory_data_stays_unavailable(self):
        analytics.train_models(self.products, self.sales)
        products = [dict(product) for product in self.products]
        products[0]["stock"] = None
        dashboard = analytics.build_dashboard({
            "producto_id": 1,
            "categoria_id": None,
            "frecuencia": "semanal",
            "horizonte": 30,
            "productos": products,
            "ventas": self.sales,
        })

        self.assertIsNone(dashboard["metricas"]["dias_estimados_agotamiento"])
        self.assertIsNone(dashboard["metricas"]["cantidad_sugerida_compra"])
        self.assertIsNone(dashboard["metricas"]["rotacion_proyectada"])

    def test_requires_training_and_reports_insufficient_history(self):
        dashboard = analytics.build_dashboard({
            "producto_id": 1,
            "categoria_id": None,
            "frecuencia": "mensual",
            "horizonte": 30,
            "productos": self.products,
            "ventas": self.sales[:2],
        })

        self.assertEqual(dashboard["calidad"]["estado"], "insuficiente_historial")
        self.assertEqual(dashboard["prediccion"], [])
        self.assertIsNone(dashboard["metricas"]["venta_proyectada"])

    def test_empty_sales_report_no_sales_instead_of_zero_history(self):
        dashboard = analytics.build_dashboard({
            "producto_id": 1,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 15,
            "productos": self.products,
            "ventas": [],
        })

        self.assertEqual(dashboard["calidad"]["estado"], "sin_ventas")
        self.assertEqual(dashboard["historico"], [])
        self.assertEqual(dashboard["prediccion"], [])

    def test_unknown_product_reports_no_matching_active_products(self):
        dashboard = analytics.build_dashboard({
            "producto_id": 999,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 15,
            "productos": self.products,
            "ventas": self.sales,
        })

        self.assertEqual(dashboard["calidad"]["estado"], "sin_datos")

    def test_insufficient_retraining_does_not_overwrite_a_trained_model(self):
        trained = analytics.train_models(self.products, self.sales)
        previous_artifact = self.model_path.read_text(encoding="utf-8")

        result = analytics.train_models(self.products, self.sales[:2])

        self.assertEqual(trained["estado"], "entrenamiento_completado")
        self.assertEqual(result["estado"], "insuficiente_historial")
        self.assertEqual(self.model_path.read_text(encoding="utf-8"), previous_artifact)

    def test_feedback_is_saved_and_aggregated_for_recommendations(self):
        record = {
            "recomendacion_id": "combo:1,2",
            "tipo": "combo",
            "estrellas": 5,
            "usuario_id": 7,
        }

        analytics.append_feedback(record)
        analytics.append_feedback({**record, "estrellas": 3})

        self.assertEqual(analytics.get_feedback_averages()["combo"], (3.0, 1))

    def test_invoice_pairs_are_recommended_from_repeated_baskets(self):
        analytics.train_models(self.products, self.sales)
        dashboard = analytics.build_dashboard({
            "producto_id": 1,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 15,
            "productos": self.products,
            "ventas": self.sales,
        })

        combo_actions = [item for item in dashboard["recomendaciones"] if item["tipo"] == "combo"]
        self.assertTrue(any(item["productos"] == [1, 2] for item in combo_actions))

    def test_unfiltered_dashboard_recommends_combos_for_frozen_products_without_sales(self):
        products = [
            {**self.products[0], "stock": 8, "stock_minimo": 2},
            {**self.products[1], "stock": 20, "stock_minimo": 2},
        ]
        sales = [
            {
                "id_factura": 2000 + index,
                "id_producto": 1,
                "fecha": (date.today() - timedelta(days=index)).isoformat(),
                "cantidad": 3,
            }
            for index in range(16)
        ]

        dashboard = analytics.build_dashboard({
            "producto_id": None,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 15,
            "productos": products,
            "ventas": sales,
        })

        strategic_combos = [
            item for item in dashboard["recomendaciones"]
            if item["tipo"] == "combo_estrategico"
        ]
        self.assertTrue(any(item["productos"] == [2, 1] for item in strategic_combos))
        self.assertEqual(dashboard["productos_congelados"][0]["id"], 2)


if __name__ == "__main__":
    unittest.main()
