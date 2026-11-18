import os
import sys
import unittest
from pathlib import Path
from unittest.mock import patch

from fastapi import HTTPException

SERVICE_DIR = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(SERVICE_DIR))

previous_directory = Path.cwd()
os.chdir(SERVICE_DIR)
try:
    from controlador.api_recepcion import validar_token_auditor
finally:
    os.chdir(previous_directory)


class AuditorTokenValidationTest(unittest.TestCase):
    def test_accepts_matching_configured_token(self):
        token = "shared-auditor-token-with-at-least-32-chars"
        with patch.dict(os.environ, {"AUDITOR_RECEPCION_TOKEN": token}):
            self.assertIsNone(validar_token_auditor(token))

    def test_rejects_mismatched_token(self):
        token = "shared-auditor-token-with-at-least-32-chars"
        with patch.dict(os.environ, {"AUDITOR_RECEPCION_TOKEN": token}):
            with self.assertRaises(HTTPException) as error:
                validar_token_auditor("another-invalid-token")

        self.assertEqual(error.exception.status_code, 403)

    def test_reports_invalid_server_configuration(self):
        with patch.dict(os.environ, {"AUDITOR_RECEPCION_TOKEN": "too-short"}):
            with self.assertRaises(HTTPException) as error:
                validar_token_auditor("too-short")

        self.assertEqual(error.exception.status_code, 503)


if __name__ == "__main__":
    unittest.main()
