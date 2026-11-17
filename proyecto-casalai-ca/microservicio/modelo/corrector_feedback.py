import ast
import json
import math
import re
import unicodedata
from collections import defaultdict
from difflib import SequenceMatcher
from typing import Any, Dict, Iterable, List, Optional, Tuple


MINIMUM_CORRECTION_SUPPORT = 2
DOCUMENT_FIELDS = (
    "numero_factura",
    "subtotal_factura",
    "porcentaje_iva",
    "monto_iva",
    "total_factura",
)
PRODUCT_FIELDS = ("nombre", "modelo", "marca", "serial", "cantidad", "costo")
NUMERIC_FIELDS = {
    "subtotal_factura",
    "porcentaje_iva",
    "monto_iva",
    "total_factura",
    "cantidad",
    "costo",
}


def _text(value: object) -> str:
    if value is None or (isinstance(value, float) and math.isnan(value)):
        return ""
    return str(value).strip()


def _normalize(value: object) -> str:
    normalized = unicodedata.normalize("NFKD", _text(value).casefold())
    without_accents = "".join(
        character for character in normalized
        if not unicodedata.combining(character)
    )
    return re.sub(r"[^a-z0-9]+", " ", without_accents).strip()


def _parse_items(value: object) -> List[dict]:
    if isinstance(value, list):
        return [item for item in value if isinstance(item, dict)]
    if not isinstance(value, str) or not value.strip():
        return []
    try:
        parsed = json.loads(value)
    except json.JSONDecodeError:
        try:
            parsed = ast.literal_eval(value)
        except (SyntaxError, ValueError):
            return []
    return [item for item in parsed if isinstance(item, dict)] if isinstance(parsed, list) else []


def _parse_object(value: object) -> dict:
    if isinstance(value, dict):
        return value
    if not isinstance(value, str) or not value.strip():
        return {}
    try:
        parsed = json.loads(value)
    except json.JSONDecodeError:
        return {}
    return parsed if isinstance(parsed, dict) else {}


def _feedback_fields(row: dict) -> Tuple[dict, dict]:
    envelope = _parse_object(row.get("discrepancias_corregidas"))
    predicted = _parse_object(envelope.get("campos_predichos"))
    predicted.update(_parse_object(row.get("campos_predichos")))
    confirmed = _parse_object(envelope.get("campos_confirmados"))
    confirmed.update(_parse_object(row.get("campos_confirmados")))
    predicted.setdefault("nombre_proveedor", row.get("prediccion_proveedor"))
    predicted.setdefault("numero_factura", row.get("prediccion_correlativo"))
    predicted.setdefault("subtotal_factura", row.get("prediccion_subtotal"))
    predicted.setdefault("total_factura", row.get("prediccion_total"))
    predicted.setdefault("productos", row.get("prediccion_productos"))
    confirmed.setdefault("nombre_proveedor", row.get("ground_truth_proveedor"))
    confirmed.setdefault("numero_factura", row.get("ground_truth_correlativo"))
    confirmed.setdefault("subtotal_factura", row.get("ground_truth_subtotal"))
    confirmed.setdefault("total_factura", row.get("ground_truth_total"))
    confirmed.setdefault("productos", row.get("ground_truth_productos"))
    return predicted, confirmed


def _value_key(value: object) -> str:
    text = _text(value)
    if not text:
        return ""
    try:
        numeric = float(text.replace(",", "."))
    except ValueError:
        return _normalize(text)
    if math.isfinite(numeric):
        return format(numeric, ".12g")
    return ""


def _field_value_key(field: str, value: object) -> str:
    return _value_key(value) if field in NUMERIC_FIELDS else _normalize(value)


def _product_anchor(product: dict) -> str:
    identity = [
        _normalize(product.get(field))
        for field in ("nombre", "modelo", "marca")
    ]
    return "|".join(identity)


def _document_product_context(products: List[dict]) -> str:
    signatures = []
    for product in products:
        anchor = _product_anchor(product)
        if not anchor.strip("|"):
            continue
        quantity = _field_value_key("cantidad", product.get("cantidad"))
        cost = _field_value_key(
            "costo", product.get("costo", product.get("costo_unitario"))
        )
        signatures.append("|".join((anchor, quantity, cost)))
    return "|".join(sorted(signatures))


def _document_field_source_key(
    field: str,
    value: object,
    supplier_key: str,
    product_context: str,
) -> str:
    context = product_context if field in {
        "subtotal_factura", "porcentaje_iva", "monto_iva", "total_factura"
    } else supplier_key
    if field in {
        "subtotal_factura", "porcentaje_iva", "monto_iva", "total_factura"
    }:
        context = "|".join((supplier_key, product_context))
    return "|".join((field, context, _field_value_key(field, value)))


def _record_id(row: dict) -> Optional[str]:
    reception_id = _text(row.get("id_recepcion"))
    return reception_id or None


def _product_pair_candidates(
    predicted: List[dict], corrected: List[dict]
) -> List[Tuple[int, int]]:
    candidates = []
    for predicted_index, predicted_item in enumerate(predicted):
        predicted_name = _normalize(predicted_item.get("nombre") or predicted_item.get("descripcion"))
        predicted_model = _normalize(predicted_item.get("modelo"))
        predicted_brand = _normalize(predicted_item.get("marca"))
        if not any((predicted_name, predicted_model, predicted_brand)):
            continue

        for corrected_index, corrected_item in enumerate(corrected):
            corrected_name = _normalize(corrected_item.get("nombre") or corrected_item.get("descripcion"))
            corrected_model = _normalize(corrected_item.get("modelo"))
            corrected_brand = _normalize(corrected_item.get("marca"))
            if not corrected_name:
                continue

            name_similarity = SequenceMatcher(
                None, predicted_name, corrected_name
            ).ratio()
            model_similarity = (
                SequenceMatcher(None, predicted_model, corrected_model).ratio()
                if predicted_model and corrected_model
                else 0.0
            )
            brand_similarity = (
                1.0 if predicted_brand and predicted_brand == corrected_brand else 0.0
            )
            serial_similarity = (
                SequenceMatcher(
                    None,
                    _normalize(predicted_item.get("serial")),
                    _normalize(corrected_item.get("serial")),
                ).ratio()
                if predicted_item.get("serial") and corrected_item.get("serial")
                else 0.0
            )
            if (
                name_similarity < 0.35
                and model_similarity < 0.5
                and brand_similarity == 0
                and serial_similarity < 0.5
            ):
                continue
            candidates.append(
                (
                    predicted_index,
                    corrected_index,
                    name_similarity * 0.55
                    + model_similarity * 0.2
                    + brand_similarity * 0.15
                    + serial_similarity * 0.1,
                )
            )

    candidates.sort(key=lambda candidate: candidate[2], reverse=True)
    paired_predicted = set()
    paired_corrected = set()
    pairs = []
    for predicted_index, corrected_index, score in candidates:
        if predicted_index in paired_predicted or corrected_index in paired_corrected:
            continue
        competing_scores = [
            other_score
            for other_predicted, other_corrected, other_score in candidates
            if (
                (other_predicted == predicted_index and other_corrected != corrected_index)
                or (other_corrected == corrected_index and other_predicted != predicted_index)
            )
        ]
        if score < 0.35 or (competing_scores and score - max(competing_scores) < 0.15):
            continue
        pairs.append((predicted_index, corrected_index))
        paired_predicted.add(predicted_index)
        paired_corrected.add(corrected_index)
    return pairs


def _target_signature(target: dict) -> str:
    return json.dumps(target, sort_keys=True, ensure_ascii=False)


def build_correction_rules(
    rows: Iterable[dict],
    minimum_support: int = MINIMUM_CORRECTION_SUPPORT,
) -> Dict[str, Any]:
    supplier_evidence = defaultdict(lambda: defaultdict(set))
    product_evidence = defaultdict(lambda: defaultdict(set))
    product_targets = {}
    document_field_evidence = defaultdict(
        lambda: defaultdict(lambda: defaultdict(set))
    )
    document_field_targets = {}

    for row in rows:
        receipt_id = _record_id(row)
        if receipt_id is None:
            continue
        predicted_fields, confirmed_fields = _feedback_fields(row)
        predicted_supplier = _text(
            predicted_fields.get(
                "nombre_proveedor", row.get("prediccion_proveedor")
            )
        )
        corrected_supplier = _text(
            confirmed_fields.get(
                "nombre_proveedor", row.get("ground_truth_proveedor")
            )
        )
        predicted_supplier_key = _normalize(predicted_supplier)
        corrected_supplier_key = _normalize(corrected_supplier)
        if (
            predicted_supplier_key
            and corrected_supplier_key
            and predicted_supplier_key != corrected_supplier_key
        ):
            supplier_evidence[predicted_supplier_key][corrected_supplier].add(receipt_id)

        predicted_items = _parse_items(
            predicted_fields.get(
                "productos", row.get("prediccion_productos")
            )
        )
        corrected_items = _parse_items(
            confirmed_fields.get(
                "productos", row.get("ground_truth_productos")
            )
        )
        product_context = _document_product_context(predicted_items)
        for field in DOCUMENT_FIELDS:
            predicted_value = predicted_fields.get(field)
            confirmed_value = confirmed_fields.get(field)
            if predicted_value in (None, "") or confirmed_value in (None, ""):
                continue
            source_key = _document_field_source_key(
                field, predicted_value, predicted_supplier_key, product_context
            )
            target_key = _field_value_key(field, confirmed_value)
            if (
                not source_key
                or not target_key
                or _field_value_key(field, predicted_value) == target_key
            ):
                continue
            document_field_evidence[field][source_key][target_key].add(receipt_id)
            document_field_targets[field, source_key, target_key] = _text(confirmed_value)

        for predicted_index, corrected_index in _product_pair_candidates(
            predicted_items, corrected_items
        ):
            predicted_item = predicted_items[predicted_index]
            corrected_item = corrected_items[corrected_index]
            anchor = _product_anchor(predicted_item)
            if not anchor.strip("|"):
                continue

            for field in PRODUCT_FIELDS:
                predicted_value = predicted_item.get(field)
                if field == "costo":
                    predicted_value = predicted_item.get(
                        "costo", predicted_item.get("costo_unitario")
                    )
                corrected_value = corrected_item.get(field)
                if predicted_value in (None, "") or corrected_value in (None, ""):
                    continue
                source_key = "|".join(
                    (
                        predicted_supplier_key,
                        anchor,
                        field,
                        _field_value_key(field, predicted_value),
                    )
                )
                target_key = _field_value_key(field, corrected_value)
                if (
                    not target_key
                    or _field_value_key(field, predicted_value) == target_key
                ):
                    continue
                target = {field: _text(corrected_value)}
                target_signature = _target_signature(target)
                product_evidence[source_key][target_signature].add(receipt_id)
                product_targets[target_signature] = target

    return {
        "proveedor": _finalize_evidence(supplier_evidence, minimum_support),
        "producto": _finalize_evidence(
            product_evidence, minimum_support, product_targets
        ),
        "campos_factura": {
            field: _finalize_evidence(
                field_evidence,
                minimum_support,
                {
                    target_key: document_field_targets[field, source_key, target_key]
                    for source_key, target_evidence in field_evidence.items()
                    for target_key in target_evidence
                },
            )
            for field, field_evidence in document_field_evidence.items()
        },
    }


def _finalize_evidence(
    evidence: dict,
    minimum_support: int,
    targets: Optional[dict] = None,
) -> List[dict]:
    rules = []
    for source_key, target_evidence in evidence.items():
        if len(target_evidence) != 1:
            continue
        target_signature, receipt_ids = next(iter(target_evidence.items()))
        if len(receipt_ids) < minimum_support:
            continue
        if targets is None:
            target_value = target_signature
        else:
            target_value = targets[target_signature]
        rules.append(
            {
                "source_key": source_key,
                "target": target_value,
                "support": len(receipt_ids),
            }
        )
    return rules


def suggest_corrections(
    corrections: dict,
    supplier: str,
    products: List[dict],
    document_fields: Optional[dict] = None,
) -> List[dict]:
    suggestions = []
    supplier_key = _normalize(supplier)
    for rule in corrections.get("proveedor", []):
        if supplier_key and supplier_key == rule.get("source_key"):
            suggestions.append(
                {
                    "campo": "proveedor",
                    "valor_actual": supplier,
                    "valor_sugerido": rule["target"],
                    "evidencia": rule["support"],
                    "requiere_confirmacion": True,
                }
            )
            break

    document_fields = document_fields or {}
    product_context = _document_product_context(products)
    invoice_rule_by_key = {
        rule.get("source_key"): rule
        for field_rules in corrections.get("campos_factura", {}).values()
        for rule in field_rules
    }
    invoice_targets = {}
    invoice_support = {}
    for field in DOCUMENT_FIELDS:
        value = document_fields.get(field)
        if value in (None, ""):
            continue
        key = _document_field_source_key(
            field, value, supplier_key, product_context
        )
        rule = invoice_rule_by_key.get(key)
        if rule:
            invoice_targets[field] = rule["target"]
            invoice_support[field] = rule["support"]
    for field, target in invoice_targets.items():
        suggestions.append(
            {
                "campo": field,
                "valor_actual": document_fields[field],
                "valor_sugerido": target,
                "evidencia": invoice_support[field],
                "requiere_confirmacion": True,
            }
        )

    product_rule_by_key = {
        rule.get("source_key"): rule
        for rule in corrections.get("producto", [])
    }
    for index, product in enumerate(products):
        anchor = _product_anchor(product)
        if not anchor.strip("|"):
            continue
        target = {}
        support = []
        for field in PRODUCT_FIELDS:
            value = product.get(field)
            if field == "costo":
                value = product.get("costo", product.get("costo_unitario"))
            if value in (None, ""):
                continue
            key = "|".join(
                (supplier_key, anchor, field, _field_value_key(field, value))
            )
            rule = product_rule_by_key.get(key)
            if rule:
                target.update(rule["target"])
                support.append(rule["support"])
        if target:
            suggestions.append(
                {
                    "campo": "producto",
                    "indice_producto": index,
                    "valor_actual": {
                        field: product.get(field)
                        for field in PRODUCT_FIELDS
                        if product.get(field) not in (None, "")
                    },
                    "valor_sugerido": target,
                    "evidencia": min(support),
                    "requiere_confirmacion": True,
                }
            )
    return suggestions


def evaluate_corrections(
    rows: List[dict],
    minimum_support: int = MINIMUM_CORRECTION_SUPPORT,
) -> dict:
    field_metrics = defaultdict(
        lambda: {"evaluables": 0, "sugeridos": 0, "correctos": 0}
    )
    documents_with_suggestions = 0
    evaluated_documents = 0
    evaluated_receipt_ids = set()

    for row in rows:
        document_has_suggestion = False
        held_out_id = _record_id(row)
        if held_out_id is None or held_out_id in evaluated_receipt_ids:
            continue
        evaluated_receipt_ids.add(held_out_id)
        evaluated_documents += 1
        training_rows = [
            training_row
            for training_row in rows
            if (
                _record_id(training_row) is not None
                and _record_id(training_row) != held_out_id
            )
        ]
        corrections = build_correction_rules(training_rows, minimum_support)

        predicted_fields, confirmed_fields = _feedback_fields(row)
        predicted_supplier = _text(predicted_fields.get("nombre_proveedor"))
        confirmed_supplier = _text(confirmed_fields.get("nombre_proveedor"))
        predicted_items = _parse_items(predicted_fields.get("productos"))
        confirmed_items = _parse_items(confirmed_fields.get("productos"))
        suggestions = suggest_corrections(
            corrections,
            predicted_supplier,
            predicted_items,
            predicted_fields,
        )

        scalar_values = {
            "proveedor": (predicted_supplier, confirmed_supplier),
            **{
                field: (
                    predicted_fields.get(field),
                    confirmed_fields.get(field),
                )
                for field in DOCUMENT_FIELDS
            },
        }
        for field, (predicted_value, confirmed_value) in scalar_values.items():
            if predicted_value in (None, "") or confirmed_value in (None, ""):
                continue
            if _field_value_key(field, predicted_value) == _field_value_key(
                field, confirmed_value
            ):
                continue
            field_metrics[field]["evaluables"] += 1
            field_suggestion = next(
                (item for item in suggestions if item.get("campo") == field),
                None,
            )
            if field_suggestion:
                field_metrics[field]["sugeridos"] += 1
                document_has_suggestion = True
                if _field_value_key(
                    field, field_suggestion["valor_sugerido"]
                ) == _field_value_key(field, confirmed_value):
                    field_metrics[field]["correctos"] += 1

        for predicted_index, corrected_index in _product_pair_candidates(
            predicted_items, confirmed_items
        ):
            predicted_item = predicted_items[predicted_index]
            confirmed_item = confirmed_items[corrected_index]
            product_suggestion = next(
                (
                    item
                    for item in suggestions
                    if item.get("campo") == "producto"
                    and item.get("indice_producto") == predicted_index
                ),
                None,
            )
            suggested_values = (
                product_suggestion.get("valor_sugerido", {})
                if product_suggestion
                else {}
            )
            for field in PRODUCT_FIELDS:
                predicted_value = predicted_item.get(field)
                if field == "costo":
                    predicted_value = predicted_item.get(
                        "costo", predicted_item.get("costo_unitario")
                    )
                confirmed_value = confirmed_item.get(field)
                if predicted_value in (None, "") or confirmed_value in (None, ""):
                    continue
                if _field_value_key(field, predicted_value) == _field_value_key(
                    field, confirmed_value
                ):
                    continue
                metric_name = f"producto.{field}"
                field_metrics[metric_name]["evaluables"] += 1
                if field in suggested_values:
                    field_metrics[metric_name]["sugeridos"] += 1
                    document_has_suggestion = True
                    if _field_value_key(
                        field, suggested_values[field]
                    ) == _field_value_key(field, confirmed_value):
                        field_metrics[metric_name]["correctos"] += 1
        if document_has_suggestion:
            documents_with_suggestions += 1

    total_suggestions = sum(
        values["sugeridos"] for values in field_metrics.values()
    )
    total_correct = sum(
        values["correctos"] for values in field_metrics.values()
    )
    total_evaluable = sum(
        values["evaluables"] for values in field_metrics.values()
    )
    per_field = {
        field: {
            **values,
            "precision": (
                values["correctos"] / values["sugeridos"]
                if values["sugeridos"]
                else None
            ),
            "cobertura": (
                values["sugeridos"] / values["evaluables"]
                if values["evaluables"]
                else 0.0
            ),
        }
        for field, values in sorted(field_metrics.items())
    }
    return {
        "metodo": "leave_one_reception_out",
        "documentos_evaluados": evaluated_documents,
        "documentos_con_sugerencias": documents_with_suggestions,
        "campos_con_error": total_evaluable,
        "campos_sugeridos": total_suggestions,
        "campos_sugeridos_correctamente": total_correct,
        "precision_campos_sugeridos": (
            total_correct / total_suggestions if total_suggestions else None
        ),
        "cobertura_campos_evaluables": (
            total_suggestions / total_evaluable if total_evaluable else 0.0
        ),
        "por_campo": per_field,
    }
