import os
import json
import pandas as pd
from pathlib import Path

# Obtiene la ruta absoluta del directorio actual del script
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
csv_path = os.path.join(BASE_DIR, 'feedback_dataset.csv')

class OrquestadorReentrenamiento:
    def __init__(self, model_dir="models/"):
        # Usamos la ruta absoluta al CSV de feedback dataset
        self.csv_path = Path(csv_path)
        self.model_dir = Path(BASE_DIR) / model_dir # Asegura que models esté en la misma ruta
        self.model_dir.mkdir(parents=True, exist_ok=True)

    def cargar_dataset(self):
        """
        Carga el dataset desde el archivo CSV de feedback.
        """
        if not self.csv_path.exists():
            print(f"El archivo CSV no existe en la ruta: {self.csv_path}")
            return []
        
        # Lee el CSV usando pandas y lo convierte a una lista de diccionarios
        df = pd.read_csv(self.csv_path)
        return df.to_dict(orient="records")

    def ejecutar_pipeline(self):
        dataset = self.cargar_dataset()
        if len(dataset) < 10:
            print(f"Muestras insuficientes para reentrenar ({len(dataset)}/10 requeridas).")
            return False

        print(f"Iniciando reentrenamiento con {len(dataset)} muestras de feedback acumuladas...")
        
        # Simulación de generación de nueva versión de artefacto
        nueva_version_id = f"v1.{len(dataset)}"
        
        # Guardar metadatos de la nueva versión
        metadata = {
            "version": nueva_version_id,
            "muestras_entrenamiento": len(dataset),
            "fecha": str(Path(__file__).stat().st_mtime)
        }
        
        metadata_path = self.model_dir / "latest_metadata.json"
        with open(metadata_path, "w", encoding="utf-8") as f:
            json.dump(metadata, f, indent=4)
            
        print(f"Modelo reentrenado y guardado con éxito bajo la versión: {nueva_version_id}")
        return True

if __name__ == "__main__":
    orquestador = OrquestadorReentrenamiento()
    orquestador.ejecutar_pipeline()