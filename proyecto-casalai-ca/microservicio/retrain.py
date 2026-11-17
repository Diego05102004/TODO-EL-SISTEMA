import json
import os
import sys
import tempfile
from datetime import datetime
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier

from modelo.corrector_feedback import (
    MINIMUM_CORRECTION_SUPPORT,
    build_correction_rules,
    evaluate_corrections,
)


BASE_DIR = Path(__file__).resolve().parent
MINIMUM_SAMPLES = 5
RELIABILITY_ERROR_THRESHOLD = 0.3
ERROR_COLUMNS = (
    "error_proveedor",
    "error_correlativo",
    "error_productos",
    "error_subtotal",
    "error_total",
)
REQUIRED_COLUMNS = (*ERROR_COLUMNS, "confianza_ocr_promedio")
CORRECTION_COLUMNS = (
    "id_recepcion",
    "prediccion_proveedor",
    "ground_truth_proveedor",
    "prediccion_productos",
    "ground_truth_productos",
)
REQUIRED_FEEDBACK_COLUMNS = (*REQUIRED_COLUMNS, *CORRECTION_COLUMNS)


def _write_temporary_file(directory: Path, suffix: str) -> Path:
    descriptor, temporary_path = tempfile.mkstemp(
        prefix=".retrain-", suffix=suffix, dir=directory
    )
    os.close(descriptor)
    return Path(temporary_path)


def run_retraining(
    feedback_path: Path | str | None = None,
    model_path: Path | str | None = None,
    metrics_path: Path | str | None = None,
) -> dict:
    feedback_path = (
        Path(feedback_path)
        if feedback_path is not None
        else BASE_DIR / "entrenamiento" / "feedback_dataset.csv"
    )
    model_path = (
        Path(model_path)
        if model_path is not None
        else BASE_DIR / "modelo" / "modelo_actual.pkl"
    )
    metrics_path = (
        Path(metrics_path)
        if metrics_path is not None
        else BASE_DIR / "entrenamiento" / "metricas_reentrenamiento.json"
    )

    if not feedback_path.is_file():
        raise FileNotFoundError(f"No se encontró el dataset de feedback: {feedback_path}")

    df = pd.read_csv(feedback_path)
    missing_columns = [
        column for column in REQUIRED_FEEDBACK_COLUMNS if column not in df.columns
    ]
    if missing_columns:
        raise ValueError(f"Faltan columnas requeridas en el feedback: {', '.join(missing_columns)}")

    if len(df) < MINIMUM_SAMPLES:
        raise ValueError(
            f"Solo hay {len(df)} registros de feedback; se requieren al menos "
            f"{MINIMUM_SAMPLES} para reentrenar."
        )

    for column in REQUIRED_COLUMNS:
        values = pd.to_numeric(df[column], errors="coerce").to_numpy(dtype=float)
        if not np.isfinite(values).all():
            raise ValueError(f"La columna {column} contiene valores vacíos o no numéricos.")
        if ((values < 0) | (values > 1)).any():
            raise ValueError(f"La columna {column} debe contener valores entre 0 y 1.")
        df[column] = values

    extraction_errors = df[list(ERROR_COLUMNS)].mean(axis=1)
    targets = (extraction_errors < RELIABILITY_ERROR_THRESHOLD).astype(int).to_numpy()
    class_counts = {int(label): int((targets == label).sum()) for label in np.unique(targets)}
    if set(class_counts) != {0, 1}:
        raise ValueError(
            "No se puede entrenar un modelo válido: se requieren ejemplos de ambas "
            f"clases (0=no confiable, 1=confiable); distribución encontrada: {class_counts}."
        )

    features = df[["confianza_ocr_promedio"]].to_numpy(dtype=float)
    model = RandomForestClassifier(n_estimators=100, max_depth=10, random_state=42)
    model.fit(features, targets)
    feedback_rows = df.to_dict(orient="records")
    corrections = build_correction_rules(feedback_rows)
    correction_evaluation = evaluate_corrections(feedback_rows)

    training_accuracy = float(model.score(features, targets))
    metrics = {
        "fecha_reentrenamiento": datetime.now().isoformat(),
        "num_registros": len(df),
        "distribucion_clases": {
            "no_confiable": class_counts.get(0, 0),
            "confiable": class_counts.get(1, 0),
        },
        "error_promedio_extraccion": float(extraction_errors.mean()),
        "error_promedio_proveedor": float(df["error_proveedor"].mean()),
        "error_promedio_correlativo": float(df["error_correlativo"].mean()),
        "error_promedio_productos": float(df["error_productos"].mean()),
        "error_promedio_subtotal": float(df["error_subtotal"].mean()),
        "error_promedio_total": float(df["error_total"].mean()),
        "confianza_promedio": float(df["confianza_ocr_promedio"].mean()),
        "accuracy_entrenamiento": training_accuracy,
        "precision_modelo": training_accuracy,
        "correcciones_activas": {
            "por_campo": {
                "proveedor": len(corrections["proveedor"]),
                "numero_factura": len(
                    corrections["campos_factura"].get("numero_factura", [])
                ),
                "producto": len(corrections["producto"]),
                **{
                    f"producto.{field}": sum(
                        1 for rule in corrections["producto"]
                        if field in rule["target"]
                    )
                    for field in ("nombre", "modelo", "marca", "serial", "cantidad", "costo")
                },
                "subtotal_factura": len(
                    corrections["campos_factura"].get("subtotal_factura", [])
                ),
                "porcentaje_iva": len(
                    corrections["campos_factura"].get("porcentaje_iva", [])
                ),
                "monto_iva": len(
                    corrections["campos_factura"].get("monto_iva", [])
                ),
                "total_factura": len(
                    corrections["campos_factura"].get("total_factura", [])
                ),
            },
        },
        "politica_autocorreccion": {
            "soporte_minimo_recepciones": MINIMUM_CORRECTION_SUPPORT,
            "requiere_confirmacion_humana": True,
            "campos_numericos_solo_con_contexto_exactamente_coincidente": True,
            "aplica_sugerencias_automaticamente": False,
        },
        "evaluacion_correcciones": correction_evaluation,
    }
    artifact = {
        "artifact_version": 3,
        "reliability_model": model,
        "corrections": corrections,
        "correction_policy": {
            "minimum_support": MINIMUM_CORRECTION_SUPPORT,
            "suggestion_only": True,
            "numeric_rules_require_exact_context": True,
        },
    }

    model_path.parent.mkdir(parents=True, exist_ok=True)
    metrics_path.parent.mkdir(parents=True, exist_ok=True)
    temporary_model = _write_temporary_file(model_path.parent, ".pkl")
    temporary_metrics = _write_temporary_file(metrics_path.parent, ".json")

    try:
        joblib.dump(artifact, temporary_model)
        candidate = joblib.load(temporary_model)
        candidate_model = candidate["reliability_model"]
        if (
            candidate.get("artifact_version") != 3
            or set(candidate_model.classes_.tolist()) != {0, 1}
            or candidate_model.n_features_in_ != 1
            or set(candidate.get("corrections", {}))
            != {"proveedor", "producto", "campos_factura"}
        ):
            raise ValueError("El artefacto generado no cumple el contrato del modelo de auditoría.")

        with temporary_metrics.open("w", encoding="utf-8") as metrics_file:
            json.dump(metrics, metrics_file, indent=2, ensure_ascii=False)
            metrics_file.write("\n")

        os.replace(temporary_model, model_path)
        os.replace(temporary_metrics, metrics_path)
    finally:
        temporary_model.unlink(missing_ok=True)
        temporary_metrics.unlink(missing_ok=True)

    print(f"Procesando {len(df)} registros de feedback para reentrenamiento...")
    print("Distribución de clases:", metrics["distribucion_clases"])
    print(f"Modelo guardado en: {model_path}")
    print(f"Métricas guardadas en: {metrics_path}")
    print(f"Accuracy de entrenamiento: {metrics['accuracy_entrenamiento']:.2%}")
    return metrics


if __name__ == "__main__":
    try:
        run_retraining()
    except (OSError, ValueError, pd.errors.ParserError) as error:
        print(f"Error de reentrenamiento: {error}", file=sys.stderr)
        sys.exit(1)
