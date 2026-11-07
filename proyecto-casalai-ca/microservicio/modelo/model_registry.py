import json
from pathlib import Path
import shutil
from datetime import datetime

class ModelRegistry:
    def __init__(self, models_dir="models/"):
        self.models_dir = Path(models_dir)
        self.models_dir.mkdir(parents=True, exist_ok=True)
        self.metadata_file = self.models_dir / "active_metadata.json"
        self._inicializar_metadata()

    def _inicializar_metadata(self):
        """Crea el archivo de metadatos inicial si no existe."""
        if not self.metadata_file.exists():
            metadata_inicial = {
                "active_version": "v1.0.0",
                "versions": {
                    "v1.0.0": {
                        "path": "auditor_model_v1.0.0.pkl",
                        "metrics": {"accuracy": 1.0},
                        "created_at": datetime.now().isoformat()
                    }
                }
            }
            with open(self.metadata_file, "w", encoding="utf-8") as f:
                json.dump(metadata_inicial, f, indent=4)

    def get_active_model_info(self):
        """Devuelve la versión activa y sus metadatos."""
        with open(self.metadata_file, "r", encoding="utf-8") as f:
            data = json.load(f)
        active_v = data["active_version"]
        return active_v, data["versions"][active_v]

    def register_model(self, version: str, temp_model_path: str, metrics: dict):
        """Registra un nuevo modelo entrenado, lo copia al directorio y lo activa."""
        target_path = self.models_dir / f"auditor_model_{version}.pkl"
        shutil.copy(temp_model_path, target_path)

        with open(self.metadata_file, "r", encoding="utf-8") as f:
            data = json.load(f)

        data["versions"][version] = {
            "path": target_path.name,
            "metrics": metrics,
            "created_at": datetime.now().isoformat()
        }
        data["active_version"] = version

        with open(self.metadata_file, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=4)
        
        return f"Modelo {version} registrado y activado exitosamente."

    def rollback(self, version: str):
        """Permite volver a una versión anterior estable del modelo."""
        with open(self.metadata_file, "r", encoding="utf-8") as f:
            data = json.load(f)
        
        if version in data["versions"]:
            data["active_version"] = version
            with open(self.metadata_file, "w", encoding="utf-8") as f:
                json.dump(data, f, indent=4)
            return True
        return False