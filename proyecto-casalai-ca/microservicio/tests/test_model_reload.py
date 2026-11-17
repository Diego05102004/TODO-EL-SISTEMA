import os
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import joblib
from sklearn.dummy import DummyClassifier

from modelo import auditor_recepcion as auditor_module
from modelo.auditor_recepcion import AuditorRecepcion


def build_model(labels):
    model = DummyClassifier(strategy="prior")
    model.fit([[0.2], [0.8], [0.4], [0.6], [0.5]], labels)
    return model


class ModelReloadTest(unittest.TestCase):
    def make_auditor(self):
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)
        auditor.modelo_ml = None
        auditor._modelo_ml_signature = None
        return auditor

    def test_refuses_a_single_class_model(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            model_path = Path(temporary_directory) / "modelo_actual.pkl"
            joblib.dump(build_model([1, 1, 1, 1, 1]), model_path)
            auditor = self.make_auditor()

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                self.assertFalse(auditor.recargar_modelo(force=True))
                self.assertEqual(auditor._predecir_confiabilidad(0.9), 0.0)

            self.assertIsNone(auditor.modelo_ml)

    def test_inference_instance_adopts_a_replaced_valid_model(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            model_path = root / "modelo_actual.pkl"
            replacement_path = root / "new_model.pkl"
            joblib.dump(build_model([1, 1, 1, 1, 0]), model_path)
            auditor = self.make_auditor()

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                first_prediction = auditor._predecir_confiabilidad(0.5)
                self.assertAlmostEqual(first_prediction, 0.8)

                joblib.dump(build_model([1, 0, 0, 0, 0]), replacement_path)
                os.replace(replacement_path, model_path)
                stat = model_path.stat()
                os.utime(
                    model_path,
                    ns=(stat.st_atime_ns, stat.st_mtime_ns + 2_000_000_000),
                )

                second_prediction = auditor._predecir_confiabilidad(0.5)

            self.assertAlmostEqual(second_prediction, 0.2)
            self.assertNotEqual(first_prediction, second_prediction)

    def test_reload_accepts_versioned_bundle_and_loads_corrections(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            model_path = Path(temporary_directory) / "modelo_actual.pkl"
            corrections = {
                "proveedor": [
                    {
                        "source_key": "proveedor anterior",
                        "target": "Proveedor correcto",
                        "support": 2,
                    }
                ],
                "producto": [],
            }
            joblib.dump(
                {
                    "artifact_version": 2,
                    "reliability_model": build_model([0, 1, 0, 1, 1]),
                    "corrections": corrections,
                    "correction_policy": {
                        "minimum_support": 2,
                        "suggestion_only": True,
                    },
                },
                model_path,
            )
            auditor = self.make_auditor()

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                self.assertTrue(auditor.recargar_modelo(force=True))

            self.assertEqual(
                auditor.correcciones_aprendidas,
                {
                    **corrections,
                    "campos_factura": {},
                },
            )

    def test_reload_accepts_v3_field_rules_and_loads_all_corrections(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            model_path = Path(temporary_directory) / "modelo_actual.pkl"
            corrections = {
                "proveedor": [],
                "producto": [],
                "campos_factura": {
                    "total_factura": [
                        {
                            "source_key": "total_factura|distribuidor|cable usb",
                            "target": "11.6",
                            "support": 2,
                        }
                    ]
                },
            }
            joblib.dump(
                {
                    "artifact_version": 3,
                    "reliability_model": build_model([0, 1, 0, 1, 1]),
                    "corrections": corrections,
                    "correction_policy": {
                        "minimum_support": 2,
                        "suggestion_only": True,
                    },
                },
                model_path,
            )
            auditor = self.make_auditor()

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                self.assertTrue(auditor.recargar_modelo(force=True))

            self.assertEqual(auditor.correcciones_aprendidas, corrections)

    def test_reload_keeps_legacy_classifier_compatible_without_corrections(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            model_path = Path(temporary_directory) / "modelo_actual.pkl"
            joblib.dump(build_model([0, 1, 0, 1, 1]), model_path)
            auditor = self.make_auditor()

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                self.assertTrue(auditor.recargar_modelo(force=True))

            self.assertEqual(
                auditor.correcciones_aprendidas,
                {
                    "proveedor": [],
                    "producto": [],
                    "campos_factura": {},
                },
            )

    def test_manual_reload_returns_false_when_artifact_is_missing(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            auditor = self.make_auditor()
            missing_path = Path(temporary_directory) / "missing.pkl"

            with patch.object(auditor_module, "MODEL_PATH", missing_path):
                self.assertFalse(auditor.recargar_modelo(force=True))

            self.assertIsNone(auditor.modelo_ml)


if __name__ == "__main__":
    unittest.main()
