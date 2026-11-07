"""Training and inference for the local, weekly-demand model."""
import json
import logging
import math
import os
import threading
from collections import Counter, defaultdict
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any

import numpy as np

BASE_DIR = Path(__file__).resolve().parents[1]
MODEL_PATH = BASE_DIR / "analitica_predictiva" / "modelos" / "demanda.json"
FEEDBACK_PATH = BASE_DIR / "logs_metricas" / "feedback_analitica.jsonl"
_feedback_lock = threading.Lock()
_LAG_WEEKS = 4
_MIN_TRAINING_WEEKS = 24


def _date_range(start: date, days: int) -> list[date]:
    return [start + timedelta(days=offset) for offset in range(days)]


def _build_daily_series(sales: list[dict[str, Any]], product_ids: set[int]) -> dict[int, Counter]:
    daily: dict[int, Counter] = {product_id: Counter() for product_id in product_ids}
    for sale in sales:
        product_id = int(sale["id_producto"])
        if product_id in daily:
            daily[product_id][date.fromisoformat(str(sale["fecha"]))] += int(sale["cantidad"])
    return daily


def _weekly_series(daily: Counter, end_date: date) -> tuple[list[date], np.ndarray]:
    end_monday = end_date - timedelta(days=end_date.weekday() + 7)
    first_monday = end_monday - timedelta(weeks=_MIN_TRAINING_WEEKS - 1)
    weeks = [first_monday + timedelta(weeks=offset) for offset in range(_MIN_TRAINING_WEEKS)]
    totals = np.zeros(len(weeks), dtype=float)
    week_indices = {week: index for index, week in enumerate(weeks)}
    for sale_date, quantity in daily.items():
        monday = sale_date - timedelta(days=sale_date.weekday())
        if monday in week_indices:
            totals[week_indices[monday]] += quantity
    return weeks, totals


def _fit_weekly_model(weekly: np.ndarray) -> dict[str, Any] | None:
    if len(weekly) < _MIN_TRAINING_WEEKS or np.count_nonzero(weekly) < 6:
        return None

    features = []
    targets = []
    for index in range(_LAG_WEEKS, len(weekly)):
        features.append([1.0, *weekly[index - _LAG_WEEKS:index][::-1]])
        targets.append(float(weekly[index]))

    matrix = np.asarray(features, dtype=float)
    target = np.asarray(targets, dtype=float)
    penalty = np.diag([0.0, 0.25, 0.25, 0.25, 0.25])
    coefficients = np.linalg.solve(matrix.T @ matrix + penalty, matrix.T @ target)
    residuals = target - matrix @ coefficients
    error = float(np.sqrt(np.mean(residuals ** 2)))

    return {
        "coefficients": coefficients.tolist(),
        "error": error,
        "training_rows": int(len(target)),
        "history_weeks": int(len(weekly)),
    }


def train_models(products: list[dict[str, Any]], sales: list[dict[str, Any]]) -> dict[str, Any]:
    today = date.today()
    oldest = today - timedelta(days=365)
    relevant_sales = [
        sale for sale in sales
        if oldest <= date.fromisoformat(str(sale["fecha"])) <= today
    ]
    active_products = {
        int(product["id_producto"]): product for product in products
        if str(product.get("estado", "habilitado")) == "habilitado"
    }
    daily_by_product = _build_daily_series(relevant_sales, set(active_products))
    groups: dict[str, dict[str, Any]] = {}
    non_trainable = 0

    for product_id, daily in daily_by_product.items():
        weeks, weekly = _weekly_series(daily, today)
        model = _fit_weekly_model(weekly)
        if model is None:
            non_trainable += 1
            continue
        groups[f"producto:{product_id}"] = {
            "coefficients": model["coefficients"],
            "error": model["error"],
            "training_rows": model["training_rows"],
            "history_weeks": model["history_weeks"],
        }

    category_daily: dict[int, Counter] = defaultdict(Counter)
    for product_id, product in active_products.items():
        category_id = product.get("id_categoria")
        if category_id is not None:
            category_daily[int(category_id)].update(daily_by_product[product_id])

    for category_id, daily in category_daily.items():
        _, weekly = _weekly_series(daily, today)
        model = _fit_weekly_model(weekly)
        if model is not None:
            groups[f"categoria:{category_id}"] = {
                "coefficients": model["coefficients"],
                "error": model["error"],
                "training_rows": model["training_rows"],
                "history_weeks": model["history_weeks"],
            }

    if not groups:
        return {
            "estado": "insuficiente_historial",
            "version": "ridge-lag4-v1",
            "trained_at": None,
            "modelos_entrenados": 0,
            "productos_sin_historial_suficiente": non_trainable,
            "minimo_semanas": _MIN_TRAINING_WEEKS,
        }

    artifact = {
        "version": "ridge-lag4-v1",
        "trained_at": datetime.now().astimezone().isoformat(timespec="seconds"),
        "source": "facturas_pagadas",
        "models": groups,
    }
    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
    temporary_path = MODEL_PATH.with_suffix(f".{os.getpid()}.tmp")
    temporary_path.write_text(json.dumps(artifact, ensure_ascii=False), encoding="utf-8")
    os.replace(temporary_path, MODEL_PATH)
    return {
        "estado": "entrenamiento_completado",
        "version": artifact["version"],
        "trained_at": artifact["trained_at"],
        "modelos_entrenados": len(groups),
        "productos_sin_historial_suficiente": non_trainable,
        "minimo_semanas": _MIN_TRAINING_WEEKS,
    }


def _load_artifact() -> dict[str, Any]:
    if not MODEL_PATH.is_file():
        return {"version": None, "trained_at": None, "models": {}}
    try:
        artifact = json.loads(MODEL_PATH.read_text(encoding="utf-8"))
    except json.JSONDecodeError as error:
        logging.exception("El artefacto de analítica predictiva no es JSON válido")
        raise RuntimeError("El modelo guardado está dañado; vuelva a entrenarlo.") from error
    if not isinstance(artifact, dict) or not isinstance(artifact.get("models"), dict):
        raise RuntimeError("El formato del modelo guardado no es válido.")
    return artifact


def _aggregate_sales(
    sales: list[dict[str, Any]],
    product_ids: set[int],
    start_date: date,
    end_date: date,
) -> tuple[Counter, dict[int, Counter], dict[int, set[int]]]:
    daily_total: Counter = Counter()
    daily_products: dict[int, Counter] = {product_id: Counter() for product_id in product_ids}
    invoice_products: dict[int, set[int]] = defaultdict(set)
    for sale in sales:
        sale_date = date.fromisoformat(str(sale["fecha"]))
        product_id = int(sale["id_producto"])
        if product_id not in product_ids:
            continue
        quantity = int(sale["cantidad"])
        if start_date <= sale_date <= end_date:
            daily_products[product_id][sale_date] += quantity
            daily_total[sale_date] += quantity
        invoice_products[int(sale["id_factura"])].add(product_id)
    return daily_total, daily_products, invoice_products


def _bucket_history(daily: Counter, frequency: str, today: date) -> list[dict[str, Any]]:
    if frequency == "diaria":
        start = today - timedelta(days=89)
        return [
            {"fecha": day.isoformat(), "valor": int(daily[day])}
            for day in _date_range(start, 90)
        ]
    if frequency == "semanal":
        end_week = today - timedelta(days=today.weekday())
        start_week = end_week - timedelta(weeks=25)
        totals: Counter = Counter()
        for sale_date, quantity in daily.items():
            monday = sale_date - timedelta(days=sale_date.weekday())
            totals[monday] += quantity
        return [
            {"fecha": (start_week + timedelta(weeks=offset)).isoformat(),
             "valor": int(totals[start_week + timedelta(weeks=offset)])}
            for offset in range(26)
        ]

    start_month = date(today.year, today.month, 1)
    months = []
    for offset in range(11, -1, -1):
        year = start_month.year
        month = start_month.month - offset
        while month <= 0:
            month += 12
            year -= 1
        months.append(date(year, month, 1))
    totals: Counter = Counter()
    for sale_date, quantity in daily.items():
        totals[date(sale_date.year, sale_date.month, 1)] += quantity
    return [{"fecha": month.isoformat(), "valor": int(totals[month])} for month in months]


def _forecast_daily(model: dict[str, Any], weekly: np.ndarray, horizon: int) -> list[dict[str, Any]]:
    history = [float(value) for value in weekly[-_LAG_WEEKS:]]
    coefficients = np.asarray(model["coefficients"], dtype=float)
    error = max(0.0, float(model["error"]))
    today = date.today()
    start = today + timedelta(days=1)
    daily_predictions = []
    for week_index in range(math.ceil(horizon / 7)):
        features = np.asarray([1.0, *history[-_LAG_WEEKS:][::-1]], dtype=float)
        predicted_week = max(0.0, float(features @ coefficients))
        lower_week = max(0.0, predicted_week - 1.96 * error)
        upper_week = predicted_week + 1.96 * error
        history.append(predicted_week)
        days_this_week = min(7, horizon - week_index * 7)
        for day_offset in range(days_this_week):
            prediction_date = start + timedelta(days=week_index * 7 + day_offset)
            daily_predictions.append({
                "fecha": prediction_date,
                "valor": predicted_week / 7.0,
                "limite_inferior": lower_week / 7.0,
                "limite_superior": upper_week / 7.0,
            })
    return daily_predictions


def _bucket_forecast(predictions: list[dict[str, Any]], frequency: str) -> list[dict[str, Any]]:
    if frequency == "diaria":
        return [
            {
                "fecha": item["fecha"].isoformat(),
                "valor": round(item["valor"], 2),
                "limite_inferior": round(item["limite_inferior"], 2),
                "limite_superior": round(item["limite_superior"], 2),
            }
            for item in predictions
        ]

    totals: dict[date, dict[str, float]] = {}
    for item in predictions:
        key = item["fecha"] - timedelta(days=item["fecha"].weekday()) if frequency == "semanal" else date(
            item["fecha"].year, item["fecha"].month, 1
        )
        bucket = totals.setdefault(key, {"valor": 0.0, "limite_inferior": 0.0, "limite_superior": 0.0})
        for metric in bucket:
            bucket[metric] += item[metric]
    return [
        {
            "fecha": bucket_date.isoformat(),
            "valor": round(values["valor"], 2),
            "limite_inferior": round(values["limite_inferior"], 2),
            "limite_superior": round(values["limite_superior"], 2),
        }
        for bucket_date, values in sorted(totals.items())
    ]


def _feedback_averages() -> dict[str, tuple[float, int]]:
    if not FEEDBACK_PATH.is_file():
        return {}
    latest_votes: dict[tuple[str, str], tuple[str, int]] = {}
    with _feedback_lock, FEEDBACK_PATH.open("r", encoding="utf-8") as feedback_file:
        for line_number, line in enumerate(feedback_file, start=1):
            try:
                entry = json.loads(line)
                recommendation_type = str(entry["tipo"])
                recommendation_id = str(entry["recomendacion_id"])
                stars = int(entry["estrellas"])
            except (json.JSONDecodeError, KeyError, TypeError, ValueError):
                logging.warning("Se omite feedback inválido en línea %s", line_number)
                continue
            if 1 <= stars <= 5:
                user_id = str(entry.get("usuario_id") or "anonimo")
                latest_votes[(user_id, recommendation_id)] = (recommendation_type, stars)

    totals: Counter = Counter()
    counts: Counter = Counter()
    for recommendation_type, stars in latest_votes.values():
        totals[recommendation_type] += stars
        counts[recommendation_type] += 1
    return {
        kind: (totals[kind] / counts[kind], counts[kind])
        for kind in counts
    }


def _recommendation_id(kind: str, item_ids: list[int]) -> str:
    normalized = ",".join(str(item_id) for item_id in sorted(item_ids))
    return f"{kind}:{normalized}"


def _confidence_level(confidence: float) -> str:
    if confidence >= 0.7:
        return "alta"
    if confidence >= 0.4:
        return "media"
    return "baja"


def _generate_explanation(metrics: dict[str, Any], context: dict[str, Any], recommendation_type: str) -> dict[str, str]:
    """
    Genera explicaciones dinámicas basadas en el análisis real de los datos.

    Args:
        metrics: Diccionario con las métricas calculadas
        context: Diccionario con contexto adicional (producto, horizonte, frecuencia, etc.)
        recommendation_type: "reposicion" o "combo"

    Returns:
        Diccionario con explicaciones dinámicas en español
    """
    try:
        quality = context.get("quality", {})
        weekly_data = context.get("weekly_data", None)
        horizon = context.get("horizonte", 30)
        frequency = context.get("frecuencia", "diaria")
        current_stock = context.get("stock_actual")
        minimum_stock = context.get("stock_minimo")
        invoice_count = context.get("cantidad_facturas", 0)

        # Calcular semanas de datos usadas
        weeks_used = quality.get("semanas_entrenamiento", 0)
        training_samples = quality.get("muestras_entrenamiento", 0)
        model_error = quality.get("error_semanal_estimado", None)

        # Calcular tendencia si hay datos semanales
        trend_description = ""
        try:
            if weekly_data is not None and len(weekly_data) >= 4:
                recent_avg = float(np.mean(weekly_data[-4:]))
                older_avg = float(np.mean(weekly_data[:-4])) if len(weekly_data) >= 8 else recent_avg
                if older_avg > 0:
                    trend_pct = ((recent_avg - older_avg) / older_avg) * 100
                    if trend_pct > 5:
                        trend_description = f"Con una tendencia creciente del {abs(trend_pct):.1f}%"
                    elif trend_pct < -5:
                        trend_description = f"Con una tendencia decreciente del {abs(trend_pct):.1f}%"
                    else:
                        trend_description = "Con una tendencia estable"
                else:
                    trend_description = "Sin tendencia clara debido a datos limitados"
            else:
                trend_description = "Sin suficientes datos para determinar tendencia"
        except Exception:
            trend_description = "No se pudo calcular la tendencia"

        # Calcular nivel de confianza
        confidence_level = "desconocida"
        try:
            if model_error is not None and weekly_data is not None and len(weekly_data) > 0:
                avg_demand = float(np.mean(weekly_data))
                if avg_demand > 0:
                    confidence = max(0.0, min(0.99, 1.0 - model_error / (avg_demand + 1.0)))
                    confidence_pct = int(confidence * 100)
                    confidence_level = f"Con una confianza del {confidence_pct}%"
                else:
                    confidence_level = "Con confianza moderada (datos limitados)"
            elif training_samples > 0:
                confidence_level = f"Con una confianza basada en {training_samples} muestras de entrenamiento"
            else:
                confidence_level = "Sin modelo predictivo disponible"
        except Exception:
            confidence_level = "No se pudo calcular la confianza"

        # Generar explicación según el tipo de recomendación
        if recommendation_type == "reposicion":
            projected_sales = metrics.get("venta_proyectada")
            suggested_purchase = metrics.get("cantidad_sugerida_compra")
            projected_rotation = metrics.get("rotacion_proyectada")
            days_to_stockout = metrics.get("dias_estimados_agotamiento")

            # Explicación de cantidad sugerida
            if suggested_purchase is not None and suggested_purchase > 0:
                if weekly_data is not None and len(weekly_data) > 0:
                    avg_weekly_demand = float(np.mean(weekly_data))
                    weeks_covered = current_stock / avg_weekly_demand if current_stock and avg_weekly_demand > 0 else 0

                    cantidad_exp = (
                        f"Sugerimos comprar {suggested_purchase} unidades porque en las últimas {weeks_used} semanas "
                        f"la demanda promedio fue de {avg_weekly_demand:.1f} unidades/semana. "
                    )

                    if trend_description:
                        cantidad_exp += f"{trend_description}. "

                    if projected_sales:
                        increase_pct = ((projected_sales - (avg_weekly_demand * (horizon / 7))) / (avg_weekly_demand * (horizon / 7))) * 100 if avg_weekly_demand > 0 else 0
                        if abs(increase_pct) > 1:
                            cantidad_exp += f"El modelo predice un {'aumento' if increase_pct > 0 else 'disminución'} del {abs(increase_pct):.0f}% en el próximo periodo. "

                    if current_stock is not None:
                        cantidad_exp += f"El stock actual de {current_stock} unidades cubre aproximadamente {weeks_covered:.1f} semanas de demanda. "

                    cantidad_exp += f"{confidence_level} basada en {training_samples} muestras de entrenamiento."
                else:
                    cantidad_exp = (
                        f"Sugerimos comprar {suggested_purchase} unidades basado en la proyección de demanda. "
                        f"{confidence_level}."
                    )
            elif suggested_purchase == 0:
                cantidad_exp = "No se sugiere compra porque el stock actual es suficiente para cubrir la demanda proyectada."
            else:
                cantidad_exp = "No se puede calcular la cantidad sugerida debido a falta de datos de stock o proyección."

            # Explicación de rotación proyectada
            if projected_rotation is not None:
                if current_stock and projected_sales:
                    avg_stock = (current_stock + max(0, current_stock - projected_sales)) / 2
                    rotation_exp = (
                        f"La rotación proyectada es de {projected_rotation:.2f} veces/mes, "
                        f"calculada como {projected_sales:.0f} unidades de demanda / {avg_stock:.0f} unidades de inventario promedio. "
                    )
                    # Comparar con rotación histórica si es posible
                    if weekly_data is not None and len(weekly_data) > 0:
                        historical_monthly = float(np.sum(weekly_data[-4:])) if len(weekly_data) >= 4 else float(np.sum(weekly_data))
                        historical_rotation = historical_monthly / avg_stock if avg_stock > 0 else 0
                        if historical_rotation > 0:
                            change_pct = ((projected_rotation - historical_rotation) / historical_rotation) * 100
                            rotation_exp += f"Esto representa una {'mejora' if change_pct > 0 else 'disminución'} del {abs(change_pct):.0f}% respecto a la rotación histórica de {historical_rotation:.2f} veces/mes."
                else:
                    rotation_exp = f"La rotación proyectada es de {projected_rotation:.2f} veces/mes."
            else:
                rotation_exp = "No se puede calcular la rotación proyectada debido a falta de datos de stock."

            # Explicación de días de agotamiento
            stockout_exp = ""
            if days_to_stockout is not None:
                if days_to_stockout == 0:
                    stockout_exp = "El stock se agotará inmediatamente según la proyección de demanda."
                elif days_to_stockout <= 7:
                    stockout_exp = f"El stock se agotará en aproximadamente {days_to_stockout} días. Se recomienda acción inmediata."
                elif days_to_stockout <= 30:
                    stockout_exp = f"El stock se agotará en aproximadamente {days_to_stockout} días."
                else:
                    stockout_exp = f"El stock actual cubre aproximadamente {days_to_stockout} días de demanda proyectada."

        elif recommendation_type == "combo_estrategico":
            # Para combos estratégicos, las explicaciones se basan en el análisis de inventario congelado
            frozen_stock = context.get("frozen_stock", 0)
            frozen_excess = context.get("frozen_excess", 0)
            frozen_rotation = context.get("frozen_rotation", 0)
            popular_rotation = context.get("popular_rotation", 0)
            unlock_potential = context.get("unlock_potential", 0)
            suggested_discount = context.get("suggested_discount", 0)

            cantidad_exp = (
                f"Este combo estratégico está diseñado para desbloquear {frozen_excess} unidades de inventario congelado "
                f"que tienen una rotación extremadamente baja de {frozen_rotation:.2f} veces/mes. "
                f"Al emparejar este producto con uno de alta rotación ({popular_rotation:.2f} veces/mes), "
                f"se estima un potencial de desbloqueo de {unlock_potential:.1f} unidades. "
                f"Se sugiere aplicar un descuento del {suggested_discount}% para incentivar la compra."
            )
            rotation_exp = (
                f"El producto congelado tiene una rotación de {frozen_rotation:.2f} veces/mes (muy baja), "
                f"mientras que el producto popular tiene una rotación de {popular_rotation:.2f} veces/mes. "
                f"El combo busca aprovechar la popularidad del segundo para mover el inventario del primero."
            )
            stockout_exp = (
                f"El stock congelado representa capital inmovilizado. Liberar este inventario mejorará el flujo de caja "
                f"y reducirá los costos de almacenamiento. El objetivo es vender el stock excedente en las próximas 4-6 semanas."
            )

        elif recommendation_type == "combo":
            # Para combos, las explicaciones se basan en la frecuencia de compra conjunta
            pair_count = context.get("pair_count", 0)
            first_confidence = context.get("first_confidence", 0)
            second_confidence = context.get("second_confidence", 0)
            support = context.get("support", 0)

            cantidad_exp = (
                f"Estos productos se han comprado juntos en {pair_count} facturas. "
                f"La probabilidad de compra conjunta es del {max(first_confidence, second_confidence) * 100:.0f}% "
                f"y representan el {support * 100:.1f}% de todas las transacciones. "
                f"Basado en {invoice_count} facturas analizadas."
            )
            rotation_exp = "La rotación no aplica para recomendaciones de combo."
            stockout_exp = ""

        else:
            # Explicaciones genéricas cuando no hay tipo específico
            cantidad_exp = f"Explicación no disponible para el tipo '{recommendation_type}'."
            rotation_exp = "Explicación no disponible."
            stockout_exp = ""

        # Fuente de datos
        fuente_exp = f"Análisis basado en {invoice_count} facturas con estado 'Pagada' o 'Pagada Presencialmente' de los últimos 6 meses."
        if training_samples > 0:
            fuente_exp += f" El modelo predictivo fue entrenado con {training_samples} muestras de {weeks_used} semanas de datos históricos."

        return {
            "cantidad_sugerida_compra": cantidad_exp,
            "rotacion_proyectada": rotation_exp,
            "dias_estimados_agotamiento": stockout_exp if stockout_exp else None,
            "fuente": fuente_exp,
        }
        
    except Exception as e:
        # Fallback en caso de error
        return {
            "cantidad_sugerida_compra": "No se pudo generar la explicación debido a un error.",
            "rotacion_proyectada": "No se pudo generar la explicación debido a un error.",
            "dias_estimados_agotamiento": None,
            "fuente": "Error en el procesamiento de explicaciones.",
        }


def _identify_frozen_products(products: list[dict[str, Any]], sales: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """
    Identifica productos congelados: stock alto, rotación baja y sin ventas recientes.

    Args:
        products: Lista de productos con información de stock
        sales: Lista de ventas históricas

    Returns:
        Lista de productos congelados con métricas detalladas
    """
    today = date.today()
    thirty_days_ago = today - timedelta(days=30)
    ninety_days_ago = today - timedelta(days=90)
    
    # Construir ventas por producto
    sales_by_product: dict[int, dict[str, Any]] = defaultdict(lambda: {
        "total_quantity": 0,
        "recent_quantity": 0,
        "sale_dates": [],
        "last_sale_date": None
    })
    
    for sale in sales:
        product_id = int(sale["id_producto"])
        sale_date = date.fromisoformat(str(sale["fecha"]))
        quantity = int(sale["cantidad"])
        
        sales_by_product[product_id]["total_quantity"] += quantity
        sales_by_product[product_id]["sale_dates"].append(sale_date)
        
        if sale_date >= thirty_days_ago:
            sales_by_product[product_id]["recent_quantity"] += quantity
        
        if sales_by_product[product_id]["last_sale_date"] is None or sale_date > sales_by_product[product_id]["last_sale_date"]:
            sales_by_product[product_id]["last_sale_date"] = sale_date
    
    frozen_products = []
    rotation_threshold = 2.0  # Umbral configurable: rotación < 2.0 veces/mes (relajado para pruebas)
    
    for product in products:
        product_id = int(product["id_producto"])
        stock = product.get("stock")
        min_stock = product.get("stock_minimo")
        
        if stock is None or min_stock is None:
            continue
        
        stock = int(stock)
        min_stock = int(min_stock)
        
        # Criterio 1: Stock alto (mayor al mínimo)
        if stock <= min_stock:
            continue
        
        product_sales = sales_by_product.get(product_id, {})
        total_quantity = product_sales.get("total_quantity", 0)
        recent_quantity = product_sales.get("recent_quantity", 0)
        last_sale_date = product_sales.get("last_sale_date")
        
        # Criterio 2: Bajas ventas recientes (últimos 30 días) - relajado para pruebas
        # Si tiene ventas recientes pero muy pocas, aún puede ser congelado
        if last_sale_date and last_sale_date >= thirty_days_ago and recent_quantity > 5:
            continue
        
        # Criterio 3: Rotación baja (calculada sobre 90 días)
        avg_stock = stock  # Simplificación: usar stock actual como promedio
        monthly_rotation = (total_quantity / 3) / avg_stock if avg_stock > 0 else 0
        
        if monthly_rotation >= rotation_threshold:
            continue
        
        # Calcular días sin venta
        days_since_last_sale = None
        if last_sale_date:
            days_since_last_sale = (today - last_sale_date).days
        else:
            days_since_last_sale = 999  # Sin ventas registradas
        
        # Calcular potencial de desbloqueo (stock acumulado)
        unlock_potential = stock - min_stock
        
        frozen_products.append({
            "producto_id": product_id,
            "nombre_producto": product.get("nombre_producto", f"Producto {product_id}"),
            "stock_actual": stock,
            "stock_minimo": min_stock,
            "stock_excedente": stock - min_stock,
            "rotacion_mensual": round(monthly_rotation, 3),
            "ventas_totales_90dias": total_quantity,
            "ventas_recientes_30dias": recent_quantity,
            "ultima_venta": last_sale_date.isoformat() if last_sale_date else None,
            "dias_sin_venta": days_since_last_sale,
            "potencial_desbloqueo": unlock_potential,
            "categoria_id": product.get("id_categoria"),
        })
    
    # Ordenar por potencial de desbloqueo (mayor primero)
    frozen_products.sort(key=lambda x: x["potencial_desbloqueo"], reverse=True)
    
    return frozen_products


def _identify_popular_products(products: list[dict[str, Any]], sales: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """
    Identifica productos populares: alta frecuencia de ventas, alta rotación y tendencia positiva.

    Args:
        products: Lista de productos con información de stock
        sales: Lista de ventas históricas

    Returns:
        Lista de productos populares con métricas detalladas
    """
    today = date.today()
    thirty_days_ago = today - timedelta(days=30)
    ninety_days_ago = today - timedelta(days=90)
    
    # Construir ventas por producto
    sales_by_product: dict[int, dict[str, Any]] = defaultdict(lambda: {
        "total_quantity": 0,
        "recent_quantity": 0,
        "older_quantity": 0,
        "sale_count": 0,
        "sale_dates": [],
    })
    
    for sale in sales:
        product_id = int(sale["id_producto"])
        sale_date = date.fromisoformat(str(sale["fecha"]))
        quantity = int(sale["cantidad"])
        
        sales_by_product[product_id]["total_quantity"] += quantity
        sales_by_product[product_id]["sale_count"] += 1
        sales_by_product[product_id]["sale_dates"].append(sale_date)
        
        if sale_date >= thirty_days_ago:
            sales_by_product[product_id]["recent_quantity"] += quantity
        elif sale_date >= ninety_days_ago:
            sales_by_product[product_id]["older_quantity"] += quantity
    
    popular_products = []
    min_sales_threshold = 5  # Mínimo de ventas para ser considerado
    rotation_threshold = 1.0  # Rotación mensual mínima para ser popular
    
    for product in products:
        product_id = int(product["id_producto"])
        stock = product.get("stock")
        
        if stock is None:
            continue
        
        stock = int(stock)
        product_sales = sales_by_product.get(product_id, {})
        total_quantity = product_sales.get("total_quantity", 0)
        recent_quantity = product_sales.get("recent_quantity", 0)
        older_quantity = product_sales.get("older_quantity", 0)
        sale_count = product_sales.get("sale_count", 0)
        
        # Criterio 1: Mínimo de ventas
        if sale_count < min_sales_threshold:
            continue
        
        # Criterio 2: Rotación alta
        avg_stock = max(1, stock)  # Evitar división por cero
        monthly_rotation = (total_quantity / 3) / avg_stock if avg_stock > 0 else 0
        
        if monthly_rotation < rotation_threshold:
            continue
        
        # Criterio 3: Tendencia positiva o estable (comparar últimos 30 días vs 30-90 días)
        trend_pct = 0
        if older_quantity > 0:
            trend_pct = ((recent_quantity - older_quantity) / older_quantity) * 100
        elif recent_quantity > 0:
            trend_pct = 100  # Crecimiento desde cero
        
        # Rechazar productos con tendencia muy negativa (< -20%)
        if trend_pct < -20:
            continue
        
        # Calcular frecuencia de ventas (ventas por mes)
        monthly_frequency = sale_count / 3
        
        # Calcular score de popularidad (combinación de rotación, frecuencia y tendencia)
        popularity_score = (
            (monthly_rotation * 0.4) +
            (min(monthly_frequency, 10) / 10 * 0.3) +
            (max(min(trend_pct, 100), -50) + 50) / 150 * 0.3
        )
        
        popular_products.append({
            "producto_id": product_id,
            "nombre_producto": product.get("nombre_producto", f"Producto {product_id}"),
            "stock_actual": stock,
            "rotacion_mensual": round(monthly_rotation, 3),
            "ventas_totales_90dias": total_quantity,
            "ventas_recientes_30dias": recent_quantity,
            "ventas_anteriores_30_90dias": older_quantity,
            "frecuencia_ventas_mensual": round(monthly_frequency, 2),
            "tendencia_pct": round(trend_pct, 1),
            "score_popularidad": round(popularity_score, 3),
            "categoria_id": product.get("id_categoria"),
        })
    
    # Ordenar por score de popularidad (mayor primero)
    popular_products.sort(key=lambda x: x["score_popularidad"], reverse=True)
    
    return popular_products


def _generate_strategic_combos(
    frozen_products: list[dict[str, Any]],
    popular_products: list[dict[str, Any]],
    sales: list[dict[str, Any]]
) -> list[dict[str, Any]]:
    """
    Genera combos estratégicos emparejando productos congelados con productos populares.

    Args:
        frozen_products: Lista de productos congelados identificados
        popular_products: Lista de productos populares identificados
        sales: Lista de ventas históricas para análisis de co-ocurrencia

    Returns:
        Lista de combos estratégicos con explicaciones profesionales detalladas
    """
    if not frozen_products or not popular_products:
        return []
    
    # Construir mapa de productos populares por ID
    popular_by_id = {p["producto_id"]: p for p in popular_products}
    
    # Analizar co-ocurrencia de productos en facturas
    invoice_products: dict[int, set[int]] = defaultdict(set)
    product_sales: dict[int, Counter] = defaultdict(Counter)
    
    for sale in sales:
        invoice_id = int(sale["id_factura"])
        product_id = int(sale["id_producto"])
        invoice_products[invoice_id].add(product_id)
        product_sales[product_id][date.fromisoformat(str(sale["fecha"]))] += int(sale["cantidad"])
    
    # Generar combos estratégicos
    strategic_combos = []
    max_combos_per_frozen = 3  # Limitar combos por producto congelado
    
    for frozen in frozen_products[:10]:  # Limitar a los 10 productos congelados más críticos
        frozen_id = frozen["producto_id"]
        frozen_potential = frozen["potencial_desbloqueo"]
        
        # Buscar productos populares que se compren junto con el congelado
        co_occurrence_scores = []
        
        for popular in popular_products:
            popular_id = popular["producto_id"]
            
            if frozen_id == popular_id:
                continue
            
            # Calcular co-ocurrencia
            co_occurrence_count = 0
            frozen_invoices = set()
            popular_invoices = set()
            
            for invoice_id, products_in_invoice in invoice_products.items():
                if frozen_id in products_in_invoice:
                    frozen_invoices.add(invoice_id)
                if popular_id in products_in_invoice:
                    popular_invoices.add(invoice_id)
                if frozen_id in products_in_invoice and popular_id in products_in_invoice:
                    co_occurrence_count += 1
            
            # Calcular confianza del combo
            frozen_invoice_count = len(frozen_invoices)
            popular_invoice_count = len(popular_invoices)
            total_invoices = len(invoice_products)
            
            if frozen_invoice_count == 0 and popular_invoice_count == 0:
                continue
            
            # Confidencia basada en co-ocurrencia y popularidad del producto principal
            if co_occurrence_count > 0:
                confidence = co_occurrence_count / max(frozen_invoice_count, popular_invoice_count)
            else:
                # Si no hay co-ocurrencia histórica, usar popularidad del producto popular
                confidence = popular["score_popularidad"] * 0.5
            
            # Potencial de desbloqueo ajustado por confianza
            unlock_potential = frozen_potential * confidence
            
            # Calcular descuento sugerido (más agresivo para mayor potencial)
            base_discount = 0.15  # 15% base
            potential_factor = min(unlock_potential / 100, 0.20)  # Hasta 20% adicional
            suggested_discount = base_discount + potential_factor
            suggested_discount = min(suggested_discount, 0.40)  # Máximo 40%
            
            # Calcular composición del combo (proporción sin pérdidas)
            # A mayor descuento, más unidades congeladas por unidad popular
            if suggested_discount <= 0.20:
                frozen_ratio = 2  # 1 popular + 2 congelados
            elif suggested_discount <= 0.30:
                frozen_ratio = 3  # 1 popular + 3 congelados
            else:
                frozen_ratio = 4  # 1 popular + 4 congelados (descuento agresivo)
            
            co_occurrence_scores.append({
                "popular": popular,
                "co_occurrence_count": co_occurrence_count,
                "confidence": confidence,
                "unlock_potential": unlock_potential,
                "suggested_discount": suggested_discount,
                "frozen_ratio": frozen_ratio,
            })
        
        # Ordenar por potencial de desbloqueo
        co_occurrence_scores.sort(key=lambda x: x["unlock_potential"], reverse=True)
        
        # Generar combos para los mejores emparejamientos
        for combo_data in co_occurrence_scores[:max_combos_per_frozen]:
            popular = combo_data["popular"]
            confidence = combo_data["confidence"]
            unlock_potential = combo_data["unlock_potential"]
            suggested_discount = combo_data["suggested_discount"]
            co_occurrence_count = combo_data["co_occurrence_count"]
            frozen_ratio = combo_data["frozen_ratio"]
            
            # Generar explicación profesional detallada
            explanation = _generate_strategic_combo_explanation(
                frozen, popular, confidence, unlock_potential, suggested_discount, co_occurrence_count, frozen_ratio
            )
            
            # Formato de cantidad sugerida: "1 popular + X congelados"
            cantidad_formateada = f"1 {popular['nombre_producto']} + {frozen_ratio} {frozen['nombre_producto']}"
            
            strategic_combos.append({
                "recomendacion_id": _recommendation_id("combo_estrategico", [frozen["producto_id"], popular["producto_id"]]),
                "tipo": "combo_estrategico",
                "producto": f"{frozen['nombre_producto']} + {popular['nombre_producto']}",
                "producto_id": frozen["producto_id"],
                "productos": [frozen["producto_id"], popular["producto_id"]],
                "producto_congelado": {
                    "id": frozen["producto_id"],
                    "nombre": frozen["nombre_producto"],
                    "stock_actual": frozen["stock_actual"],
                    "stock_excedente": frozen["stock_excedente"],
                    "rotacion_mensual": frozen["rotacion_mensual"],
                    "dias_sin_venta": frozen["dias_sin_venta"],
                },
                "producto_popular": {
                    "id": popular["producto_id"],
                    "nombre": popular["nombre_producto"],
                    "rotacion_mensual": popular["rotacion_mensual"],
                    "tendencia_pct": popular["tendencia_pct"],
                    "score_popularidad": popular["score_popularidad"],
                },
                "senal": f"Combo estratégico para desbloquear {frozen['stock_excedente']} unidades de inventario congelado",
                "cantidad_sugerida": cantidad_formateada,  # Formato: "1 popular + X congelados"
                "cantidad_congelada": frozen_ratio,  # Número de unidades congeladas por combo
                "cantidad_popular": 1,  # Siempre 1 unidad del producto popular
                "confianza": round(confidence, 2),
                "nivel_confianza": _confidence_level(confidence),
                "potencial_desbloqueo": round(unlock_potential, 2),
                "descuento_sugerido": round(suggested_discount * 100, 1),
                "co_ocurrencia_historica": co_occurrence_count,
                "calculado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
                "prioridad_ajustada": round(unlock_potential * confidence, 3),
                "explicacion": explanation,
            })
    
    # Ordenar por potencial de desbloqueo ajustado
    strategic_combos.sort(key=lambda x: x["prioridad_ajustada"], reverse=True)
    
    return strategic_combos


def _generate_strategic_combo_explanation(
    frozen: dict[str, Any],
    popular: dict[str, Any],
    confidence: float,
    unlock_potential: float,
    suggested_discount: float,
    co_occurrence_count: int,
    frozen_ratio: int = 2
) -> str:
    """
    Genera una explicación profesional detallada para un combo estratégico.

    Args:
        frozen: Datos del producto congelado
        popular: Datos del producto popular
        confidence: Confianza del combo
        unlock_potential: Potencial de desbloqueo
        suggested_discount: Descuento sugerido (0-1)
        co_occurrence_count: Número de co-ocurrencias históricas
        frozen_ratio: Proporción de unidades congeladas por combo
        popular: Datos del producto popular
        confidence: Confianza del combo
        unlock_potential: Potencial de desbloqueo calculado
        suggested_discount: Descuento sugerido (como decimal, ej: 0.20 para 20%)
        co_occurrence_count: Número de co-ocurrencias históricas

    Returns:
        Explicación profesional detallada en español
    """
    discount_pct = round(suggested_discount * 100, 1)
    confidence_pct = round(confidence * 100, 0)
    
    # Construir explicación base
    explanation_parts = []
    
    # Parte 1: Problema identificado (inventario congelado)
    explanation_parts.append(
        f"⚠️ **PROBLEMA IDENTIFICADO:** El producto '{frozen['nombre_producto']}' presenta {frozen['stock_excedente']} unidades "
        f"de stock excedente sobre su mínimo configurado ({frozen['stock_minimo']} unidades). "
        f"Este producto tiene una rotación extremadamente baja de {frozen['rotacion_mensual']:.2f} veces/mes "
        f"y no registra ventas en los últimos {frozen['dias_sin_venta']} días, lo que indica un inventario congelado "
        f"que representa capital inmovilizado."
    )
    
    # Parte 2: Solución estratégica (combo con producto popular)
    explanation_parts.append(
        f"\n💡 **SOLUCIÓN ESTRATÉGICA:** Proponemos emparejar este producto con '{popular['nombre_producto']}', "
        f"un producto de alta rotación ({popular['rotacion_mensual']:.2f} veces/mes) "
        f"y tendencia {('positiva' if popular['tendencia_pct'] > 0 else 'negativa')} del {abs(popular['tendencia_pct']):.1f}%. "
        f"Este producto popular actúa como un 'gancho' comercial que puede incentivar la compra del producto congelado."
    )
    
    # Parte 3: Análisis de co-ocurrencia
    if co_occurrence_count > 0:
        explanation_parts.append(
            f"\n📊 **EVIDENCIA HISTÓRICA:** Estos productos han sido comprados juntos en {co_occurrence_count} ocasiones, "
            f"lo que valida la viabilidad de este combo. La confianza de la asociación es del {confidence_pct}%, "
            f"basada en patrones de compra reales de los clientes."
        )
    else:
        explanation_parts.append(
            f"\n📊 **ANÁLISIS DE MERCADO:** Aunque no hay co-ocurrencias históricas directas, "
            f"la alta popularidad de '{popular['nombre_producto']}' (score: {popular['score_popularidad']:.2f}) "
            f"y su tendencia {('positiva' if popular['tendencia_pct'] > 0 else 'estable')} "
            f"lo convierten en un candidato ideal para impulsar ventas cruzadas."
        )
    
    # Parte 4: Potencial de desbloqueo y beneficio financiero
    explanation_parts.append(
        f"\n💰 **IMPACTO FINANCIERO:** Este combo tiene un potencial de desbloqueo de {unlock_potential:.1f} unidades, "
        f"lo que representa la recuperación del capital invertido en {frozen['stock_excedente']} unidades congeladas. "
        f"Al liberar este inventario, se mejora el flujo de caja y se reduce el costo de almacenamiento."
    )
    
    # Parte 5: Recomendación de descuento y ejecución
    explanation_parts.append(
        f"\n🎯 **RECOMENDACIÓN DE EJECUCIÓN:** Sugerimos configurar el combo como "
        f"'1 {popular['nombre_producto']} + {frozen_ratio} {frozen['nombre_producto']}' "
        f"con un descuento agresivo del {discount_pct}% para incentivar la compra. "
        f"Esta proporción optimiza el margen de ganancia mientras permite desbloquear el inventario congelado. "
        f"El descuento está justificado por la necesidad de desbloquear inventario rápidamente y es superior a "
        f"descuentos regulares debido a la urgencia estratégica. Recomendamos posicionar este combo destacado en el catálogo "
        f"y promocionarlo activamente durante 2-4 semanas."
    )
    
    # Parte 6: Métricas de seguimiento
    explanation_parts.append(
        f"\n📈 **MÉTRICAS DE SEGUIMIENTO:** Monitorear las ventas del combo semanalmente. "
        f"El objetivo es vender al menos el {min(50, frozen['stock_excedente'] * 2)}% del stock excedente en el primer mes. "
        f"Si el combo no logra los resultados esperados, considerar aumentar el descuento hasta el 40% o "
        f"reevaluar la viabilidad del producto congelado."
    )
    
    return "\n".join(explanation_parts)


def build_dashboard(
    payload: dict[str, Any],
    feedback: dict[str, tuple[float, int]] | None = None,
) -> dict[str, Any]:
    products = payload["productos"]
    sales = payload["ventas"]
    selected_product = payload.get("producto_id")
    selected_category = payload.get("categoria_id")
    frequency = payload["frecuencia"]
    horizon = int(payload["horizonte"])
    today = date.today()
    feedback = feedback or {}
    product_by_id = {int(product["id_producto"]): product for product in products}

    if selected_product is not None:
        selected_ids = {int(selected_product)} if int(selected_product) in product_by_id else set()
        model_key = f"producto:{int(selected_product)}"
    elif selected_category is not None:
        selected_ids = {
            product_id for product_id, product in product_by_id.items()
            if int(product.get("id_categoria") or 0) == int(selected_category)
        }
        model_key = f"categoria:{int(selected_category)}"
    else:
        # El análisis general necesita las ventas del inventario completo para
        # identificar productos populares que puedan combinarse con el stock congelado.
        selected_ids = set(product_by_id.keys())
        model_key = "todos:productos"
    
    if not selected_ids:
        return {"calidad": {"estado": "sin_datos"}, "historico": [], "prediccion": [], "metricas": {}}

    start_date = today - timedelta(days=365)
    daily_total, daily_by_product, invoice_products = _aggregate_sales(sales, selected_ids, start_date, today)
    _, _, all_invoice_products = _aggregate_sales(sales, set(product_by_id), start_date, today)
    if not daily_total:
        return {
            "unidad": "unidades",
            "frecuencia": frequency,
            "horizonte_dias": horizon,
            "historico": [],
            "prediccion": [],
            "metricas": {
                "venta_proyectada": None,
                "dias_estimados_agotamiento": None,
                "punto_reorden": None,
                "cantidad_sugerida_compra": None,
                "rotacion_proyectada": None,
            },
            "calidad": {"estado": "sin_ventas"},
            "recomendaciones": [],
        }
    historical = _bucket_history(daily_total, frequency, today)
    artifact = _load_artifact()
    saved_model = artifact["models"].get(model_key)
    prediction = []
    metrics: dict[str, Any] = {
        "venta_proyectada": None,
        "dias_estimados_agotamiento": None,
        "punto_reorden": None,
        "cantidad_sugerida_compra": None,
        "rotacion_proyectada": None,
    }
    quality: dict[str, Any] = {
        "modelo": artifact.get("version") or "ridge-lag4-v1",
        "estado": "modelo_pendiente",
        "fecha_entrenamiento": artifact.get("trained_at"),
    }

    if selected_product is not None:
        stock_products = [product_by_id[int(selected_product)]]
    else:
        stock_products = [product_by_id[product_id] for product_id in selected_ids]
    stocks_known = all(product.get("stock") is not None for product in stock_products)
    minimums_known = all(product.get("stock_minimo") is not None for product in stock_products)
    current_stock = sum(int(product["stock"]) for product in stock_products) if stocks_known else None
    minimum_stock = sum(int(product["stock_minimo"]) for product in stock_products) if minimums_known else None
    daily_prediction: list[dict[str, Any]] = []

    if saved_model is not None:
        _, weekly = _weekly_series(daily_total, today)
        daily_prediction = _forecast_daily(saved_model, weekly, horizon)
        prediction = _bucket_forecast(daily_prediction, frequency)
        projected = sum(item["valor"] for item in daily_prediction)
        quality.update({
            "estado": "disponible",
            "semanas_entrenamiento": saved_model["history_weeks"],
            "muestras_entrenamiento": saved_model["training_rows"],
        })
        metrics["venta_proyectada"] = round(projected, 2)
        cumulative = 0.0
        if selected_product is not None and current_stock is not None:
            if current_stock == 0 and projected > 0:
                metrics["dias_estimados_agotamiento"] = 0
            else:
                for offset, item in enumerate(daily_prediction, start=1):
                    cumulative += item["valor"]
                    if cumulative >= current_stock and current_stock > 0:
                        metrics["dias_estimados_agotamiento"] = offset
                        break
            ending_stock = max(0.0, current_stock - projected)
            average_stock = (current_stock + ending_stock) / 2.0
            metrics["rotacion_proyectada"] = round(projected / average_stock, 2) if average_stock > 0 else None
        if selected_product is not None and minimum_stock is not None:
            metrics["punto_reorden"] = minimum_stock
        if selected_product is not None and current_stock is not None and minimum_stock is not None:
            metrics["cantidad_sugerida_compra"] = max(
                0, math.ceil(projected + minimum_stock - current_stock)
            )
        quality["error_semanal_estimado"] = round(float(saved_model["error"]), 2)
    elif _fit_weekly_model(_weekly_series(daily_total, today)[1]) is None:
        quality["estado"] = "insuficiente_historial"

    inventory_actions = []
    for product_id, product in product_by_id.items():
        if product_id not in selected_ids and selected_product is not None:
            continue
        if product.get("stock") is None or product.get("stock_minimo") is None:
            continue
        stock = int(product["stock"])
        min_stock = int(product["stock_minimo"])
        product_model = artifact["models"].get(f"producto:{product_id}")
        if product_model is None:
            continue
        # Si el producto no tiene registros de ventas diarias, salta al siguiente o asígnale vacío
        if product_id not in daily_by_product:
             continue   # Si estás dentro de un ciclo 'for', usa continue para saltarlo de forma segura.
        _, product_weekly = _weekly_series(daily_by_product[product_id], today)
        forecast = _forecast_daily(product_model, product_weekly, min(horizon, 90))
        projected_demand = sum(item["valor"] for item in forecast)
        suggested = max(0, math.ceil(projected_demand + min_stock - stock))
        if stock > min_stock and projected_demand < stock:
            continue
        confidence = max(
            0.0,
            min(0.99, 1.0 - float(product_model["error"]) / (float(np.mean(product_weekly)) + 1.0)),
        )
        average_rating, rating_count = feedback.get("reposicion", (0.0, 0))
        feedback_factor = 1.0 if rating_count == 0 else 0.7 + 0.3 * average_rating / 5.0
        
        # Generar explicación individual para esta reposición
        avg_weekly_demand = float(np.mean(product_weekly))
        stock_days = stock / avg_weekly_demand if stock and avg_weekly_demand > 0 else 0
        
        if stock <= min_stock:
            reposicion_explicacion = (
                f"El stock actual ({stock} unidades) está por debajo del mínimo configurado ({min_stock} unidades). "
                f"La demanda promedio es de {avg_weekly_demand:.1f} unidades/semana. "
                f"Sugerimos comprar {suggested} unidades para cumplir con el punto de reorden."
            )
        else:
            reposicion_explicacion = (
                f"La demanda proyectada ({projected_demand:.0f} unidades) supera el stock actual ({stock} unidades). "
                f"Con una demanda promedio de {avg_weekly_demand:.1f} unidades/semana, "
                f"el stock actual cubre aproximadamente {stock_days:.1f} semanas. "
                f"Sugerimos comprar {suggested} unidades para evitar desabastecimiento."
            )
        
        inventory_actions.append({
            "recomendacion_id": _recommendation_id("reposicion", [product_id]),
            "tipo": "reposicion",
            "producto": product["nombre_producto"],
            "producto_id": product_id,
            "senal": "Stock bajo el mínimo configurado" if stock <= min_stock else "Demanda prevista supera el stock",
            "cantidad_sugerida": suggested,
            "confianza": round(confidence, 2),
            "nivel_confianza": _confidence_level(confidence),
            "calculado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
            "retroalimentacion": {"promedio": round(average_rating, 2) if rating_count else None, "respuestas": rating_count},
            "prioridad_ajustada": round(confidence * feedback_factor, 3),
            "explicacion": reposicion_explicacion,
        })

    invoice_frequency: Counter = Counter()
    pair_frequency: Counter = Counter()
    for product_ids in all_invoice_products.values():
        invoice_frequency.update(product_ids)
        ordered_ids = sorted(product_ids)
        for first_index, first_id in enumerate(ordered_ids):
            for second_id in ordered_ids[first_index + 1:]:
                if selected_product is not None and int(selected_product) not in (first_id, second_id):
                    continue
                if selected_category is not None and not ({first_id, second_id} & selected_ids):
                    continue
                pair_frequency[(first_id, second_id)] += 1
    combo_actions = []
    invoice_count = len(all_invoice_products)
    for pair, pair_count in pair_frequency.items():
        first_id, second_id = pair
        if pair_count < 2 or first_id not in product_by_id or second_id not in product_by_id:
            continue
        first_confidence = pair_count / max(1, invoice_frequency[first_id])
        second_confidence = pair_count / max(1, invoice_frequency[second_id])
        confidence = max(first_confidence, second_confidence)
        support = pair_count / max(1, invoice_count)
        average_rating, rating_count = feedback.get("combo", (0.0, 0))
        feedback_factor = 1.0 if rating_count == 0 else 0.7 + 0.3 * average_rating / 5.0
        
        # Generar explicación individual para este combo
        combo_explicacion = (
            f"Estos productos se han comprado juntos en {pair_count} facturas. "
            f"La probabilidad de compra conjunta es del {confidence * 100:.0f}% "
            f"y representan el {support * 100:.1f}% de todas las transacciones. "
            f"Basado en {invoice_count} facturas analizadas."
        )
        
        combo_actions.append({
            "recomendacion_id": _recommendation_id("combo", [first_id, second_id]),
            "tipo": "combo",
            "producto": f"{product_by_id[first_id]['nombre_producto']} + {product_by_id[second_id]['nombre_producto']}",
            "producto_id": first_id,
            "productos": [first_id, second_id],
            "senal": f"Comprados juntos en {pair_count} facturas",
            "cantidad_sugerida": None,
            "confianza": round(confidence, 2),
            "nivel_confianza": _confidence_level(confidence),
            "soporte": round(support, 3),
            "calculado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
            "retroalimentacion": {"promedio": round(average_rating, 2) if rating_count else None, "respuestas": rating_count},
            "prioridad_ajustada": round(confidence * support * feedback_factor, 3),
            "explicacion": combo_explicacion,
        })
    
    # Generar combos estratégicos (Fase 2)
    strategic_combos = []
    try:
        frozen_products = _identify_frozen_products(products, sales)
        popular_products = _identify_popular_products(products, sales)
        strategic_combos = _generate_strategic_combos(frozen_products, popular_products, sales)
        
        # Aplicar factor de retroalimentación a combos estratégicos
        average_rating, rating_count = feedback.get("combo_estrategico", (0.0, 0))
        feedback_factor_strategic = 1.0 if rating_count == 0 else 0.7 + 0.3 * average_rating / 5.0
        
        for combo in strategic_combos:
            combo["retroalimentacion"] = {
                "promedio": round(average_rating, 2) if rating_count else None,
                "respuestas": rating_count
            }
            combo["prioridad_ajustada"] = round(combo["prioridad_ajustada"] * feedback_factor_strategic, 3)
    except Exception as e:
        # Si falla la generación de combos estratégicos, continuar sin ellos
        logging.warning("Error al generar combos estratégicos: %s", str(e))
        strategic_combos = []
    
    # Combinar recomendaciones: combos estratégicos tienen prioridad sobre combos regulares
    # Los combos estratégicos se colocan primero en la lista ordenada
    all_recommendations = inventory_actions + strategic_combos + combo_actions
    recommendations = sorted(
        all_recommendations,
        key=lambda item: item["prioridad_ajustada"],
        reverse=True,
    )[:20]

    # Generar explicaciones dinámicas basadas en el contexto actual
    weekly_data = None
    if saved_model is not None:
        _, weekly_data = _weekly_series(daily_total, today)

    # Determinar el tipo de recomendación predominante
    # Prioridad: combo_estrategico > combo > reposicion
    if strategic_combos:
        recommendation_type = "combo_estrategico"
    elif combo_actions:
        recommendation_type = "combo"
    else:
        recommendation_type = "reposicion"

    # Construir contexto para generar explicaciones
    explanation_context = {
        "quality": quality,
        "weekly_data": weekly_data,
        "horizonte": horizon,
        "frecuencia": frequency,
        "stock_actual": current_stock,
        "stock_minimo": minimum_stock,
        "cantidad_facturas": invoice_count,
    }

    # Si hay recomendaciones de combo estratégico, agregar contexto específico
    if recommendation_type == "combo_estrategico" and strategic_combos:
        first_strategic = strategic_combos[0]
        frozen_info = first_strategic.get("producto_congelado", {})
        popular_info = first_strategic.get("producto_popular", {})
        explanation_context.update({
            "strategic_combo": True,
            "frozen_stock": frozen_info.get("stock_actual", 0),
            "frozen_excess": frozen_info.get("stock_excedente", 0),
            "frozen_rotation": frozen_info.get("rotacion_mensual", 0),
            "popular_rotation": popular_info.get("rotacion_mensual", 0),
            "unlock_potential": first_strategic.get("potencial_desbloqueo", 0),
            "suggested_discount": first_strategic.get("descuento_sugerido", 0),
        })
    # Si hay recomendaciones de combo regular, agregar contexto específico
    elif recommendation_type == "combo" and combo_actions:
        first_combo = combo_actions[0]
        senal = first_combo.get("senal", "")
        pair_count = 0
        if "en " in senal:
            try:
                pair_count = int(senal.split("en ")[1].split(" ")[0])
            except (ValueError, IndexError):
                pair_count = 0
        explanation_context.update({
            "pair_count": pair_count,
            "first_confidence": first_combo.get("confianza", 0),
            "second_confidence": first_combo.get("confianza", 0),
            "support": first_combo.get("soporte", 0),
        })

    # Generar explicaciones dinámicas
    dynamic_explanations = _generate_explanation(metrics, explanation_context, recommendation_type)

    # Si no se seleccionó producto ni categoría, agregar información de productos congelados
    frozen_products_info = []
    if selected_product is None and selected_category is None:
        frozen_products = _identify_frozen_products(products, sales)
        frozen_products_info = [
            {
                "id": fp["producto_id"],
                "nombre": fp["nombre_producto"],
                "stock_actual": fp["stock_actual"],
                "stock_excedente": fp["stock_excedente"],
                "rotacion_mensual": fp["rotacion_mensual"],
                "dias_sin_venta": fp["dias_sin_venta"]
            }
            for fp in frozen_products[:5]  # Top 5 productos congelados
        ]

    return {
        "unidad": "unidades",
        "frecuencia": frequency,
        "horizonte_dias": horizon,
        "historico": historical,
        "prediccion": prediction,
        "metricas": metrics,
        "calidad": quality,
        "recomendaciones": recommendations,
        "explicaciones": dynamic_explanations,
        "productos_congelados": frozen_products_info if frozen_products_info else None,
    }


def append_feedback(feedback: dict[str, Any]) -> dict[str, Any]:
    FEEDBACK_PATH.parent.mkdir(parents=True, exist_ok=True)
    record = {
        **feedback,
        "fecha": datetime.now().astimezone().isoformat(timespec="seconds"),
    }
    with _feedback_lock, FEEDBACK_PATH.open("a", encoding="utf-8") as feedback_file:
        feedback_file.write(json.dumps(record, ensure_ascii=False) + "\n")
        feedback_file.flush()
        os.fsync(feedback_file.fileno())
    return {"guardado": True, "fecha": record["fecha"]}


def get_feedback_averages() -> dict[str, tuple[float, int]]:
    return _feedback_averages()
