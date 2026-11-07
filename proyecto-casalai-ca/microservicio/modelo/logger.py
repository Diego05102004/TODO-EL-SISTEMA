import pandas as pd
import os
import logging
from datetime import datetime

DATA_PATH = "microservicio/entrenamiento/feedback_dataset.csv"

# Configurar logger
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

def log_prediction_data(input_features: dict, prediction_result: dict):
    """Almacena los datos de entrada y la predicción generada para auditoría y reentrenamiento en feedback_dataset.csv."""
    # Mapear los datos al formato de feedback_dataset.csv
    record = {
        "timestamp": datetime.now().isoformat(),
        "id_recepcion": input_features.get("id_recepcion", ""),
        "id_factura": input_features.get("numero_factura", ""),
        "prediccion_proveedor": prediction_result.get("proveedor", ""),
        "ground_truth_proveedor": input_features.get("nombre_proveedor", ""),
        "error_proveedor": 0,  # Se calculará después
        "prediccion_correlativo": prediction_result.get("numero_factura", ""),
        "ground_truth_correlativo": input_features.get("numero_factura", ""),
        "error_correlativo": 0,  # Se calculará después
        "prediccion_productos": str(prediction_result.get("productos", [])),
        "ground_truth_productos": str(input_features.get("productos", [])),
        "error_productos": 0,  # Se calculará después
        "prediccion_subtotal": prediction_result.get("subtotal", 0),
        "ground_truth_subtotal": input_features.get("subtotal", 0),
        "error_subtotal": 0,  # Se calculará después
        "prediccion_total": prediction_result.get("total", 0),
        "ground_truth_total": input_features.get("total", 0),
        "error_total": 0,  # Se calculará después
        "discrepancias_corregidas": str(prediction_result.get("discrepancias", [])),
        "confianza_ocr_promedio": prediction_result.get("confianza", 0)
    }
    
    df_new = pd.DataFrame([record])
    
    if os.path.exists(DATA_PATH):
        df_new.to_csv(DATA_PATH, mode='a', header=False, index=False)
    else:
        # Crear archivo con encabezado si no existe
        header = "timestamp,id_recepcion,id_factura,prediccion_proveedor,ground_truth_proveedor,error_proveedor,prediccion_correlativo,ground_truth_correlativo,error_correlativo,prediccion_productos,ground_truth_productos,error_productos,prediccion_subtotal,ground_truth_subtotal,error_subtotal,prediccion_total,ground_truth_total,error_total,discrepancias_corregidas,confianza_ocr_promedio"
        with open(DATA_PATH, 'w') as f:
            f.write(header + '\n')
        df_new.to_csv(DATA_PATH, mode='a', header=False, index=False)