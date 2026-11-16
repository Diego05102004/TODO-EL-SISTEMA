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
        self.assertEqual(
            {item["id"] for item in dashboard["productos_congelados"]},
            {1, 2},
        )

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

    def test_decision_and_discount_feedback_without_stars_is_not_invalid(self):
        analytics.append_feedback({
            "recomendacion_id": "combo_estrategico:1,2",
            "tipo": "combo_estrategico",
            "decision": "aprobada",
            "descuento_feedback": "adecuado",
            "usuario_id": 7,
        })

        with self.assertNoLogs(level="WARNING"):
            self.assertEqual(analytics.get_feedback_averages(), {})

        self.assertEqual(
            analytics.get_feedback_decisions(7),
            {"combo_estrategico": (1, 0)},
        )

    def test_discount_feedback_personalizes_future_combo_discounts(self):
        analytics.append_feedback({
            "recomendacion_id": "combo_estrategico:1,2",
            "tipo": "combo_estrategico",
            "descuento_feedback": "mucho",
            "usuario_id": 7,
        })
        analytics.append_feedback({
            "recomendacion_id": "combo_estrategico:1,3",
            "tipo": "combo_estrategico",
            "descuento_feedback": "mucho",
            "usuario_id": 7,
        })
        analytics.append_feedback({
            "recomendacion_id": "combo_estrategico:2,3",
            "tipo": "combo_estrategico",
            "descuento_feedback": "poco",
            "usuario_id": 8,
        })

        self.assertAlmostEqual(analytics.get_discount_adjustment(7), -1.5)
        self.assertAlmostEqual(analytics.get_discount_adjustment(8), 1.0)
        self.assertEqual(analytics.get_discount_adjustment(9), 0.0)

    def test_rejected_decisions_reduce_recommendation_priority(self):
        analytics.append_feedback({
            "recomendacion_id": "combo_estrategico:1,2",
            "tipo": "combo_estrategico",
            "decision": "aprobada",
            "usuario_id": 7,
        })
        analytics.append_feedback({
            "recomendacion_id": "combo_estrategico:1,3",
            "tipo": "combo_estrategico",
            "decision": "rechazada",
            "usuario_id": 7,
        })

        decisions = analytics.get_feedback_decisions(7)
        factor = analytics._feedback_priority_factor("combo_estrategico", {}, decisions)

        self.assertEqual(decisions["combo_estrategico"], (1, 1))
        self.assertAlmostEqual(factor, 0.85)

    def test_strategic_frozen_stock_recommendations_precede_other_actions(self):
        recommendations = [
            {"tipo": "combo", "prioridad_ajustada": 100.0},
            {"tipo": "reposicion", "prioridad_ajustada": 0.01},
            {"tipo": "combo_estrategico", "prioridad_ajustada": 0.02},
        ]

        ordered = analytics._prioritize_recommendations(recommendations)

        self.assertEqual(
            [item["tipo"] for item in ordered],
            ["combo_estrategico", "reposicion", "combo"],
        )

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

        payload = {
            "producto_id": None,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 15,
            "productos": products,
            "ventas": sales,
        }
        dashboard = analytics.build_dashboard(payload)
        adjusted_dashboard = analytics.build_dashboard(payload, discount_adjustment=-6)

        strategic_combos = [
            item for item in dashboard["recomendaciones"]
            if item["tipo"] == "combo_estrategico"
        ]
        adjusted_combos = [
            item for item in adjusted_dashboard["recomendaciones"]
            if item["tipo"] == "combo_estrategico"
        ]
        self.assertTrue(any(item["productos"] == [2, 1] for item in strategic_combos))
        self.assertEqual(dashboard["productos_congelados"][0]["id"], 2)
        self.assertLess(adjusted_combos[0]["descuento_sugerido"], strategic_combos[0]["descuento_sugerido"])

    def test_dashboard_returns_all_frozen_products_not_only_a_short_preview(self):
        products = [
            {
                **self.products[0],
                "id_producto": product_id,
                "nombre_producto": f"Congelado {product_id}",
                "stock": 20 + product_id,
                "stock_minimo": 2,
            }
            for product_id in range(1, 8)
        ]
        products.append({
            **self.products[1],
            "id_producto": 8,
            "nombre_producto": "Producto popular",
            "stock": 1,
            "stock_minimo": 0,
        })
        sales = [
            {
                "id_factura": 3000 + index,
                "id_producto": 8,
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

        self.assertEqual(len(dashboard["productos_congelados"]), 7)
        self.assertEqual(
            {item["nombre"] for item in dashboard["productos_congelados"]},
            {f"Congelado {product_id}" for product_id in range(1, 8)},
        )

    def test_selected_frozen_product_keeps_strategic_combo_recommendations(self):
        products = [
            {
                **self.products[0],
                "id_producto": 1,
                "nombre_producto": "Producto popular",
                "stock": 1,
                "stock_minimo": 0,
            },
            {
                **self.products[1],
                "id_producto": 2,
                "nombre_producto": "Producto congelado",
                "stock": 100,
                "stock_minimo": 10,
            },
        ]
        sales = [
            {
                "id_factura": 4000 + index,
                "id_producto": 1,
                "fecha": (date.today() - timedelta(days=index)).isoformat(),
                "cantidad": 2,
            }
            for index in range(16)
        ]
        sales.append({
            "id_factura": 5000,
            "id_producto": 2,
            "fecha": (date.today() - timedelta(days=52)).isoformat(),
            "cantidad": 1,
        })

        dashboard = analytics.build_dashboard({
            "producto_id": 2,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 30,
            "productos": products,
            "ventas": sales,
        })

        self.assertIn(2, {item["id"] for item in dashboard["productos_congelados"]})
        self.assertTrue(any(
            item["tipo"] == "combo_estrategico"
            and item["producto_congelado"]["id"] == 2
            for item in dashboard["recomendaciones"]
        ))

    def test_selected_frozen_without_sales_pairs_with_ranked_seller_beyond_global_limit(self):
        products = [
            {
                **self.products[0],
                "id_producto": product_id,
                "nombre_producto": f"Congelado {product_id}",
                "stock": 100,
                "stock_minimo": 10,
            }
            for product_id in range(1, 12)
        ]
        products.extend([
            {
                **self.products[1],
                "id_producto": 12,
                "nombre_producto": "Producto con ventas activas",
                "stock": 2,
                "stock_minimo": 0,
            },
            {
                **self.products[1],
                "id_producto": 13,
                "nombre_producto": "Producto más popular",
                "stock": 5,
                "stock_minimo": 0,
            },
        ])
        sales = [
            {
                "id_factura": 6000 + index,
                "id_producto": 12,
                "fecha": (date.today() - timedelta(days=index)).isoformat(),
                "cantidad": 6,
            }
            for index in range(2)
        ] + [
            {
                "id_factura": 7000 + index,
                "id_producto": 13,
                "fecha": (date.today() - timedelta(days=index)).isoformat(),
                "cantidad": 10,
            }
            for index in range(3)
        ]

        dashboard = analytics.build_dashboard({
            "producto_id": 11,
            "categoria_id": None,
            "frecuencia": "diaria",
            "horizonte": 30,
            "productos": products,
            "ventas": sales,
        })

        self.assertEqual(dashboard["calidad"]["estado"], "sin_ventas")
        combos = [
            item for item in dashboard["recomendaciones"]
            if item["tipo"] == "combo_estrategico"
        ]
        self.assertEqual(
            [item["producto_popular"]["id"] for item in combos],
            [13, 12],
        )
        self.assertTrue(all(
            item["producto_congelado"]["id"] == 11
            for item in combos
        ))


if __name__ == "__main__":
    unittest.main()
