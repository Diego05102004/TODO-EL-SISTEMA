import importlib
import os
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

import joblib
from fastapi import HTTPException
from sklearn.dummy import DummyClassifier

from modelo import auditor_recepcion as auditor_module


def build_model(labels):
    model = DummyClassifier(strategy="prior")
    model.fit([[0.2], [0.8], [0.4], [0.6], [0.5]], labels)
    return model


class ApiModelReloadTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        working_directory = Path.cwd()
        cls.import_directory = tempfile.TemporaryDirectory()
        try:
            os.chdir(cls.import_directory.name)
            cls.api_recepcion = importlib.import_module("controlador.api_recepcion")
        finally:
            os.chdir(working_directory)

    @classmethod
    def tearDownClass(cls):
        cls.import_directory.cleanup()

    def test_reload_endpoint_loads_the_model_used_by_auditor(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            model_path = Path(temporary_directory) / "modelo_actual.pkl"
            joblib.dump(build_model([0, 1, 0, 1, 1]), model_path)

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                result = self.api_recepcion.recargar_modelo()

            self.assertEqual(result["status"], "success")
            self.assertIsNotNone(self.api_recepcion.auditor.modelo_ml)

    def test_reload_endpoint_returns_service_unavailable_for_single_class(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            model_path = Path(temporary_directory) / "modelo_actual.pkl"
            joblib.dump(build_model([1, 1, 1, 1, 1]), model_path)

            with patch.object(auditor_module, "MODEL_PATH", model_path):
                with self.assertRaises(HTTPException) as raised:
                    self.api_recepcion.recargar_modelo()

            self.assertEqual(raised.exception.status_code, 503)
            self.assertIsNone(self.api_recepcion.auditor.modelo_ml)

    def test_product_serialization_uses_cost_key_expected_by_feedback(self):
        producto = SimpleNamespace(
            nombre="Producto de prueba",
            modelo="Modelo",
            marca="Marca",
            serial="SERIAL",
            cantidad=2,
            costo_unitario=12.5,
            confianza=0.9,
        )

        serialized = self.api_recepcion._serializar_producto(producto)

        self.assertEqual(serialized["costo"], 12.5)
        self.assertNotIn("costo_unitario", serialized)


if __name__ == "__main__":
    unittest.main()
