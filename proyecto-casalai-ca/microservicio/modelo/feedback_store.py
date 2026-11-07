import json
from datetime import datetime
from pathlib import Path

class FeedbackStore:
    def __init__(self, storage_dir="data/feedback"):
        self.storage_dir = Path(storage_dir)
        self.storage_dir.mkdir(parents=True, exist_ok=True)
        self.log_file = self.storage_dir / "feedback_dataset.jsonl"

    def registrar_correccion(self, documento_id: str, datos_ocr: dict, datos_corregidos: dict, ruta_archivo: str):
        """
        Guarda la diferencia entre la predicción del OCR y la corrección real del usuario.
        """
        registro = {
            "documento_id": documento_id,
            "timestamp": datetime.now().isoformat(),
            "ruta_archivo": ruta_archivo,
            "prediccion_ocr": datos_ocr,
            "ground_truth": datos_corregidos
        }
        
        # Guardado incremental en formato JSON Lines
        with open(self.log_file, "a", encoding="utf-8") as f:
            f.write(json.dumps(registro, ensure_ascii=False) + "\n")
            
        return {"status": "success", "message": "Feedback registrado correctamente para reentrenamiento."}