import csv
import json
import os
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import joblib

import retrain
from modelo import auditor_recepcion as auditor_module
from modelo.auditor_recepcion import AuditorRecepcion
from modelo.corrector_feedback import suggest_corrections


class RetrainingTest(unittest.TestCase):
    def write_feedback(self, path, labels):
        fields = [*retrain.REQUIRED_FEEDBACK_COLUMNS]
        with path.open("w", encoding="utf-8", newline="") as feedback_file:
            writer = csv.DictWriter(feedback_file, fieldnames=fields)
            writer.writeheader()
            for index, label in enumerate(labels):
                field_error = 0.05 if label else 0.5
                writer.writerow(
                    {
                        "id_recepcion": index + 1,
                        "prediccion_proveedor": "",
                        "ground_truth_proveedor": "",
                        "prediccion_productos": "[]",
                        "ground_truth_productos": "[]",
                        "error_proveedor": field_error,
                        "error_correlativo": field_error,
                        "error_productos": field_error,
                        "error_subtotal": field_error,
                        "error_total": field_error,
                        "confianza_ocr_promedio": 0.6 + index * 0.05,
                    }
                )

    def test_trains_to_script_relative_paths_outside_microservice_cwd(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory) / "microservice"
            workdir = Path(temporary_directory) / "elsewhere"
            (root / "entrenamiento").mkdir(parents=True)
            (root / "modelo").mkdir()
            workdir.mkdir()
            self.write_feedback(root / "entrenamiento" / "feedback_dataset.csv", [0, 1, 0, 1, 1])

            with patch.object(retrain, "BASE_DIR", root):
                previous_directory = Path.cwd()
                try:
                    os.chdir(workdir)
                    metrics = retrain.run_retraining()
                finally:
                    os.chdir(previous_directory)

            model_path = root / "modelo" / "modelo_actual.pkl"
            metrics_path = root / "entrenamiento" / "metricas_reentrenamiento.json"
            self.assertTrue(model_path.is_file())
            self.assertTrue(metrics_path.is_file())
            artifact = joblib.load(model_path)
            self.assertEqual(artifact["artifact_version"], 3)
            model = artifact["reliability_model"]
            self.assertEqual(set(model.classes_.tolist()), {0, 1})
            self.assertEqual(
                artifact["corrections"],
                {"proveedor": [], "producto": [], "campos_factura": {}},
            )
            saved_metrics = json.loads(metrics_path.read_text(encoding="utf-8"))
            self.assertEqual(saved_metrics["num_registros"], 5)
            self.assertEqual(saved_metrics["distribucion_clases"], {
                "no_confiable": 2,
                "confiable": 3,
            })
            self.assertEqual(metrics["accuracy_entrenamiento"], saved_metrics["accuracy_entrenamiento"])

    def test_trained_correction_bundle_reloads_and_produces_suggestions(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            model_path = root / "modelo_actual.pkl"
            metrics_path = root / "metrics.json"
            self.write_feedback(feedback_path, [1, 1, 0, 0, 1])
            with feedback_path.open(encoding="utf-8", newline="") as feedback_file:
                rows = list(csv.DictReader(feedback_file))

            predicted_products = json.dumps([
                {
                    "nombre": "Cable USB 5m",
                    "modelo": "USB-5",
                    "marca": "Acme",
                    "cantidad": 2,
                    "costo": 4.5,
                }
            ])
            corrected_products = json.dumps([
                {
                    "nombre": "Cable USB 5 m",
                    "modelo": "USB-5",
                    "marca": "Acme",
                    "cantidad": 2,
                    "costo": 4.5,
                }
            ])
            for index in range(2):
                rows[index]["prediccion_proveedor"] = "Distribuidora ACME"
                rows[index]["ground_truth_proveedor"] = "Distribuidora Acme, C.A."
                rows[index]["prediccion_productos"] = predicted_products
                rows[index]["ground_truth_productos"] = corrected_products
                rows[index]["id_recepcion"] = index + 1
            with feedback_path.open("w", encoding="utf-8", newline="") as feedback_file:
                writer = csv.DictWriter(
                    feedback_file,
                    fieldnames=retrain.REQUIRED_FEEDBACK_COLUMNS,
                )
                writer.writeheader()
                writer.writerows(rows)

            retrain.run_retraining(feedback_path, model_path, metrics_path)
            auditor = AuditorRecepcion.__new__(AuditorRecepcion)
            auditor.modelo_ml = None
            auditor.correcciones_aprendidas = {"proveedor": [], "producto": []}
            auditor._modelo_ml_signature = None

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                self.assertTrue(auditor.recargar_modelo(force=True))

            suggestions = suggest_corrections(
                auditor.correcciones_aprendidas,
                "Distribuidora ACME",
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
            metrics = json.loads(metrics_path.read_text(encoding="utf-8"))
            self.assertEqual(len(suggestions), 2)
            self.assertEqual(
                metrics["correcciones_activas"],
                {
                    "por_campo": {
                        "proveedor": 1,
                        "numero_factura": 0,
                        "producto": 1,
                        "producto.nombre": 1,
                        "producto.modelo": 0,
                        "producto.marca": 0,
                        "producto.serial": 0,
                        "producto.cantidad": 0,
                        "producto.costo": 0,
                        "subtotal_factura": 0,
                        "porcentaje_iva": 0,
                        "monto_iva": 0,
                        "total_factura": 0,
                    }
                },
            )
            self.assertEqual(
                metrics["evaluacion_correcciones"]["precision_campos_sugeridos"],
                None,
            )

    def test_refuses_less_than_five_samples_without_replacing_artifacts(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            model_path = root / "modelo_actual.pkl"
            metrics_path = root / "metrics.json"
            self.write_feedback(feedback_path, [0, 1, 0, 1])
            model_path.write_bytes(b"existing-model")
            metrics_path.write_text("existing-metrics", encoding="utf-8")

            with self.assertRaisesRegex(ValueError, "al menos 5"):
                retrain.run_retraining(feedback_path, model_path, metrics_path)

            self.assertEqual(model_path.read_bytes(), b"existing-model")
            self.assertEqual(metrics_path.read_text(encoding="utf-8"), "existing-metrics")

    def test_refuses_dataset_missing_either_class_without_replacing_model(self):
        for labels in ([1] * 5, [0] * 5):
            with self.subTest(labels=labels), tempfile.TemporaryDirectory() as temporary_directory:
                root = Path(temporary_directory)
                feedback_path = root / "feedback.csv"
                model_path = root / "modelo_actual.pkl"
                metrics_path = root / "metrics.json"
                self.write_feedback(feedback_path, labels)
                model_path.write_bytes(b"existing-model")

                with self.assertRaisesRegex(ValueError, "ambas clases"):
                    retrain.run_retraining(feedback_path, model_path, metrics_path)

                self.assertEqual(model_path.read_bytes(), b"existing-model")
                self.assertFalse(metrics_path.exists())

    def test_reliability_target_uses_all_field_errors_not_only_invoice_total(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            self.write_feedback(feedback_path, [1] * 5)
            with feedback_path.open(encoding="utf-8", newline="") as feedback_file:
                rows = list(csv.DictReader(feedback_file))
            for row in rows:
                row["error_proveedor"] = 0.5
                row["error_correlativo"] = 0.5
                row["error_productos"] = 0.5
                row["error_subtotal"] = 0.5
                row["error_total"] = 0.0
            with feedback_path.open("w", encoding="utf-8", newline="") as feedback_file:
                writer = csv.DictWriter(feedback_file, fieldnames=retrain.REQUIRED_FEEDBACK_COLUMNS)
                writer.writeheader()
                writer.writerows(rows)

            with self.assertRaisesRegex(ValueError, "ambas clases"):
                retrain.run_retraining(
                    feedback_path,
                    root / "modelo_actual.pkl",
                    root / "metrics.json",
                )

    def test_rejects_non_numeric_and_out_of_range_feedback(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            self.write_feedback(feedback_path, [0, 1, 0, 1, 1])
            with feedback_path.open(encoding="utf-8", newline="") as feedback_file:
                rows = list(csv.DictReader(feedback_file))
            rows[0]["confianza_ocr_promedio"] = "1.5"
            with feedback_path.open("w", encoding="utf-8", newline="") as feedback_file:
                writer = csv.DictWriter(feedback_file, fieldnames=retrain.REQUIRED_FEEDBACK_COLUMNS)
                writer.writeheader()
                writer.writerows(rows)

            with self.assertRaisesRegex(ValueError, "entre 0 y 1"):
                retrain.run_retraining(
                    feedback_path,
                    root / "modelo_actual.pkl",
                    root / "metrics.json",
                )


if __name__ == "__main__":
    unittest.main()
