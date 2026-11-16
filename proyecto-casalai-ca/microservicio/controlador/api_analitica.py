"""Authenticated HTTP endpoints for local sales-demand analytics."""
import hmac
import os
import re
import logging
from datetime import date
from typing import Literal

from fastapi import APIRouter, Depends, Header, HTTPException
from pydantic import BaseModel, Field, field_validator, model_validator

from modelo.analitica_predictiva import (
    append_feedback,
    build_dashboard,
    get_discount_adjustment,
    get_feedback_averages,
    get_feedback_decisions,
    get_feedback_recommendation_decisions,
    train_models,
)

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/analitica", tags=["Analítica Predictiva"])

@router.get("/")
def analitica_root():
    return {"status": "online", "service": "Analítica Predictiva"}


def validar_token_analitica(x_analitica_token: str = Header(default="", max_length=256)) -> None:
    expected = os.getenv("ANALITICA_PREDICTIVA_TOKEN", "")
    if len(expected) < 32:
        raise HTTPException(status_code=503, detail="ANALITICA_PREDICTIVA_TOKEN debe tener al menos 32 caracteres.")
    if not hmac.compare_digest(x_analitica_token.encode("utf-8"), expected.encode("utf-8")):
        raise HTTPException(status_code=403, detail="Token de analítica inválido.")


class ProductRow(BaseModel):
    id_producto: int = Field(gt=0)
    nombre_producto: str = Field(min_length=1, max_length=200)
    id_categoria: int | None = Field(default=None, gt=0)
    nombre_categoria: str | None = Field(default=None, max_length=200)
    stock: int | None = Field(default=None, ge=0)
    stock_minimo: int | None = Field(default=None, ge=0)
    stock_maximo: int | None = Field(default=None, ge=0)
    estado: str = "habilitado"


class SaleRow(BaseModel):
    id_factura: int = Field(gt=0)
    id_producto: int = Field(gt=0)
    fecha: str
    cantidad: int = Field(gt=0)

    @field_validator("fecha")
    @classmethod
    def validate_date(cls, value: str) -> str:
        date.fromisoformat(value)
        return value


class DashboardRequest(BaseModel):
    producto_id: int | None = Field(default=None)
    categoria_id: int | None = Field(default=None)
    usuario_id: int | None = Field(default=None, gt=0)
    frecuencia: Literal["diaria", "semanal", "mensual"]
    horizonte: Literal[15, 30, 60, 90]
    productos: list[ProductRow] = Field(max_length=5000)
    ventas: list[SaleRow] = Field(max_length=100000)


class TrainingRequest(BaseModel):
    productos: list[ProductRow] = Field(max_length=5000)
    ventas: list[SaleRow] = Field(max_length=100000)


class FeedbackRequest(BaseModel):
    recomendacion_id: str = Field(min_length=1, max_length=100)
    tipo: Literal["combo", "combo_estrategico", "reposicion"]
    estrellas: int | None = Field(default=None, ge=1, le=5)
    decision: Literal["aprobada", "rechazada"] | None = None
    descuento_feedback: Literal["adecuado", "mucho", "poco"] | None = None
    descuento_sugerido: float | None = Field(default=None, ge=0, le=100)
    descuento_aplicado: float | None = Field(default=None, ge=0, le=100)
    motivo_codigo: Literal[
        "productos_no_adecuados",
        "descuento_alto",
        "cantidad_inadecuada",
        "combo_existente",
        "otro",
    ] | None = None
    motivo: str | None = Field(default=None, max_length=500)
    usuario_id: int | None = Field(default=None, gt=0)

    @field_validator("recomendacion_id")
    @classmethod
    def validate_recommendation_id(cls, value: str) -> str:
        if not re.fullmatch(r"(combo_estrategico|combo|reposicion):[0-9,]+", value):
            raise ValueError("Identificador de recomendación inválido.")
        return value

    @model_validator(mode="after")
    def validate_feedback(self):
        if all(value is None for value in (
            self.estrellas,
            self.decision,
            self.descuento_feedback,
            self.descuento_aplicado,
            self.motivo,
        )):
            raise ValueError("Debe proporcionar una valoración o una respuesta de feedback.")
        if not self.recomendacion_id.startswith(f"{self.tipo}:"):
            raise ValueError("El tipo no coincide con la recomendación.")
        if self.decision == "rechazada" and self.motivo_codigo is None:
            raise ValueError("Un rechazo debe incluir un motivo.")
        return self


@router.post("/dashboard", dependencies=[Depends(validar_token_analitica)])
def dashboard(request: DashboardRequest) -> dict:
    # Permitir que ambos sean nulos (analizar todos los productos o seleccionar automáticamente uno congelado)
    # No permitir que ambos tengan valor
    if (request.producto_id is not None) and (request.categoria_id is not None):
        raise HTTPException(status_code=422, detail="Indique exactamente un producto o una categoría, no ambos.")
    payload = request.model_dump()
    return build_dashboard(
        payload,
        get_feedback_averages(request.usuario_id),
        get_discount_adjustment(request.usuario_id),
        get_feedback_decisions(request.usuario_id),
        get_feedback_recommendation_decisions(request.usuario_id),
    )


@router.post("/entrenar", dependencies=[Depends(validar_token_analitica)])
def entrenar(request: TrainingRequest) -> dict:
    result = train_models(
        [product.model_dump() for product in request.productos],
        [sale.model_dump() for sale in request.ventas],
    )
    return {"estado": "entrenamiento_completado", **result}


@router.post("/feedback", dependencies=[Depends(validar_token_analitica)])
def guardar_feedback(request: FeedbackRequest) -> dict:
    return append_feedback(request.model_dump())
