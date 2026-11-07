import pandas as pd
from sklearn.ensemble import RandomForestClassifier
import joblib
import os
import json
import numpy as np
from datetime import datetime

def run_retraining():
    # Usar feedback_dataset.csv que contiene predicciones vs ground truth
    feedback_path = "entrenamiento/feedback_dataset.csv"
    if not os.path.exists(feedback_path):
        print("No hay suficientes datos de feedback para reentrenar.")
        return

    df = pd.read_csv(feedback_path)
    
    # Verificar que haya suficientes registros (mínimo 5)
    if len(df) < 5:
        print(f"Solo hay {len(df)} registros de feedback. Se requieren al menos 5 para reentrenar.")
        return

    print(f"Procesando {len(df)} registros de feedback para reentrenamiento...")

    # Calcular métricas de error por campo
    error_promedio_proveedor = df['error_proveedor'].mean()
    error_promedio_correlativo = df['error_correlativo'].mean()
    error_promedio_productos = df['error_productos'].mean()
    error_promedio_subtotal = df['error_subtotal'].mean()
    error_promedio_total = df['error_total'].mean()
    confianza_promedio = df['confianza_ocr_promedio'].mean()

    print("\n=== MÉTRICAS DE ERROR ACTUALES ===")
    print(f"Error Proveedor: {error_promedio_proveedor:.2%}")
    print(f"Error Correlativo: {error_promedio_correlativo:.2%}")
    print(f"Error Productos: {error_promedio_productos:.2%}")
    print(f"Error Subtotal: {error_promedio_subtotal:.2%}")
    print(f"Error Total: {error_promedio_total:.2%}")
    print(f"Confianza OCR Promedio: {confianza_promedio:.2%}")

    # Crear características para el modelo basado SOLO en confianza OCR
    # Esto permite predecir confiabilidad durante la extracción (sin ground_truth)
    features = []
    targets = []

    for _, row in df.iterrows():
        # Característica: solo confianza OCR (disponible durante extracción)
        feat = [row['confianza_ocr_promedio']]
        
        # Target: 1 si el error total es bajo (< 0.3), 0 si es alto
        # Esto entrena al modelo a predecir cuándo una extracción es confiable
        # basándose únicamente en la confianza OCR
        target = 1 if row['error_total'] < 0.3 else 0
        
        features.append(feat)
        targets.append(target)

    X = np.array(features)
    y = np.array(targets)

    # Entrenar modelo RandomForest
    model = RandomForestClassifier(
        n_estimators=100,
        max_depth=10,
        random_state=42
    )
    model.fit(X, y)

    # Guardar el nuevo modelo
    model_path = "modelo/modelo_actual.pkl"
    joblib.dump(model, model_path)
    
    # Guardar métricas en un archivo JSON para referencia
    metrics_path = "entrenamiento/metricas_reentrenamiento.json"
    metrics = {
        'fecha_reentrenamiento': datetime.now().isoformat(),
        'num_registros': len(df),
        'error_promedio_proveedor': float(error_promedio_proveedor),
        'error_promedio_correlativo': float(error_promedio_correlativo),
        'error_promedio_productos': float(error_promedio_productos),
        'error_promedio_subtotal': float(error_promedio_subtotal),
        'error_promedio_total': float(error_promedio_total),
        'confianza_promedio': float(confianza_promedio),
        'precision_modelo': float(model.score(X, y))
    }
    
    with open(metrics_path, 'w', encoding='utf-8') as f:
        json.dump(metrics, f, indent=2, ensure_ascii=False)

    print("\n=== REENTRENAMIENTO COMPLETADO ===")
    print(f"Modelo guardado en: {model_path}")
    print(f"Métricas guardadas en: {metrics_path}")
    print(f"Precisión del modelo: {metrics['precision_modelo']:.2%}")

if __name__ == "__main__":
    run_retraining()