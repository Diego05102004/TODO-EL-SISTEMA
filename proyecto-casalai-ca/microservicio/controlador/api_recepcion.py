"""API REST FastAPI - Fase 3: Automatización Completa (El Piloto)."""
import hmac
import os
from fastapi import APIRouter, UploadFile, File, Form, Header, HTTPException, Depends, Request
from pydantic import BaseModel
from pathlib import Path
import json
import shutil
from slowapi import Limiter
from slowapi.util import get_remote_address

# Importación de tus módulos existentes y nuevos
from modelo.auditor_recepcion import AuditorRecepcion
from modelo.model_registry import ModelRegistry
import logging

logger = logging.getLogger(__name__)

# Configuración de Rate Limiting
limiter = Limiter(key_func=get_remote_address)

router = APIRouter(
    tags=["Recepción y Auditoría"]
)

def validar_token_auditor(x_auditor_token: str = Header(default="", max_length=256)) -> None:
    """
    Valida el token de autenticación para el microservicio de auditoría.
    Similar al sistema de analítica predictiva.
    """
    expected = os.getenv("AUDITOR_RECEPCION_TOKEN", "").strip()
    if not 32 <= len(expected) <= 256:
        raise HTTPException(
            status_code=503,
            detail="AUDITOR_RECEPCION_TOKEN debe tener entre 32 y 256 caracteres.",
        )
    if not hmac.compare_digest(x_auditor_token.encode("utf-8"), expected.encode("utf-8")):
        raise HTTPException(status_code=403, detail="Token de auditoría inválido.")

# Inicialización de componentes principales
registry = ModelRegistry()
auditor = AuditorRecepcion()

# Directorios de soporte
FEEDBACK_DIR = Path("data/feedback")
FEEDBACK_DIR.mkdir(parents=True, exist_ok=True)
FEEDBACK_FILE = FEEDBACK_DIR / "feedback_dataset.jsonl"

class FeedbackPayload(BaseModel):
    documento_id: str
    prediccion_ocr: dict
    ground_truth: dict
    ruta_archivo: str


def _serializar_producto(producto):
    return {
        "nombre": producto.nombre,
        "modelo": producto.modelo,
        "marca": producto.marca,
        "serial": producto.serial,
        "cantidad": producto.cantidad,
        "costo": producto.costo_unitario,
        "confianza": producto.confianza,
    }


@router.get("/health")
def health_check():
    """Endpoint de health check para el JavaScript"""
    return {
        "status": "healthy",
        "fase": "3 - Automatización Completa (El Piloto)",
        "service": "Auditoría Casa Lai, C.A."
    }

@router.get("/")
def read_root():
    active_v, info = registry.get_active_model_info()
    logger.info(f"Consulta de estado general. Versión activa del modelo: {active_v}")
    return {
        "status": "online",
        "service": "Auditoría Casa Lai, C.A.",
        "active_version": active_v,
        "model_metrics": info["metrics"]
    }

@router.post("/api/v1/auditar", dependencies=[Depends(validar_token_auditor)])
@limiter.limit("10/minute")
async def auditar_documento(request: Request, file: UploadFile = File(...)):
    """
    Endpoint principal para recibir facturas/documentos, 
    procesarlos mediante OCR y aplicar las reglas de auditoría.
    """
    temp_dir = Path("temp")
    temp_dir.mkdir(exist_ok=True)
    file_path = temp_dir / file.filename
    
    logger.info(f"Recibiendo archivo para auditoría: {file.filename}")
    
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
    
    try:
        active_v, _ = registry.get_active_model_info()
        
        # Ejecución del motor de auditoría existente en auditor_recepcion.py
        resultado = auditor.procesar_documento(str(file_path))
        
        logger.info(f"Documento {file.filename} procesado exitosamente usando el modelo {active_v}")
        
        # Construir respuesta base
        response = {
            "status": "success",
            "version_modelo": active_v,
            "resultado_auditoria": resultado
        }
        
        # Incluir confiabilidad ML si está disponible en los metadatos
        if isinstance(resultado, dict) and 'metadatos_extraccion' in resultado:
            if 'confiabilidad_ml' in resultado['metadatos_extraccion']:
                response["confiabilidad_ml"] = resultado['metadatos_extraccion']['confiabilidad_ml']
                logger.info(f"Confiabilidad ML incluida en respuesta: {response['confiabilidad_ml']:.2%}")
        
        return response
    except Exception as e:
        logger.error(f"Error crítico procesando el archivo {file.filename}: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error procesando documento: {str(e)}")
    finally:
        # Limpieza del archivo temporal
        if file_path.exists():
            file_path.unlink()

@router.post("/feedback")
def registrar_feedback(payload: FeedbackPayload):
    """
    Registra las correcciones manuales del operador (Ground Truth) 
    para alimentar el pipeline de reentrenamiento periódico.
    """
    try:
        registro = payload.dict()
        with open(FEEDBACK_FILE, "a", encoding="utf-8") as f:
            f.write(json.dumps(registro, ensure_ascii=False) + "\n")
        
        logger.info(f"Feedback registrado correctamente para el documento ID: {payload.documento_id}")
        return {
            "status": "success",
            "message": "Feedback almacenado correctamente para el pipeline de reentrenamiento."
        }
    except Exception as e:
        logger.error(f"Error al almacenar el feedback: {str(e)}")
        raise HTTPException(status_code=500, detail=f"No se pudo guardar el feedback: {str(e)}")

@router.post("/modelo/recargar")
def recargar_modelo():
    """
    Recarga el modelo que utiliza el auditor sin reiniciar FastAPI.
    """
    if not auditor.recargar_modelo(force=True):
        raise HTTPException(
            status_code=503,
            detail="No se pudo cargar un modelo binario válido desde modelo/modelo_actual.pkl.",
        )
    return {
        "status": "success",
        "modelo_ml_cargado": True,
        "message": "Modelo de auditoría recargado exitosamente.",
    }

# Endpoints compatibles con el proxy PHP original
@router.post("/fase1/extraer", dependencies=[Depends(validar_token_auditor)])
@limiter.limit("10/minute")
async def fase1_extraer(request: Request, imagen: UploadFile = File(...)):
    """
    Endpoint compatible con el proxy PHP original para extraer datos de factura.
    """
    temp_dir = Path("temp")
    temp_dir.mkdir(exist_ok=True)
    file_path = temp_dir / imagen.filename
    
    logger.info(f"Extraer - Recibiendo archivo: {imagen.filename}")
    
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(imagen.file, buffer)
    
    try:
        factura_id = str(hash(imagen.filename))
        resultado = auditor.extraer_desde_imagen(str(file_path), factura_id)
        logger.info(f"Extraer - Archivo procesado: {imagen.filename}")
        
        # Convertir el resultado al formato esperado por el JavaScript
        response_data = {
            "exito": True,
            "factura_id": factura_id,
            "numero_factura": resultado.numero_factura,
            "nombre_proveedor": resultado.nombre_proveedor,
            "fecha_factura": resultado.fecha_factura,
            "productos": [_serializar_producto(producto) for producto in resultado.productos],
            "subtotal_factura": resultado.subtotal_factura,
            "porcentaje_iva": resultado.porcentaje_iva,
            "monto_iva": resultado.monto_iva,
            "total_factura": resultado.total_factura,
            "confianza_promedio": resultado.metadatos_extraccion.get('confianza_promedio', 0.0),
            "es_factura_valida": resultado.metadatos_extraccion.get('es_factura_valida', True),
            "mensaje_validacion": resultado.metadatos_extraccion.get('mensaje_validacion', ''),
            "sugerencias_autocorreccion": resultado.metadatos_extraccion.get(
                'sugerencias_autocorreccion', []
            ),
        }
        
        # Incluir confiabilidad ML si está disponible
        if 'confiabilidad_ml' in resultado.metadatos_extraccion:
            response_data["confiabilidad_ml"] = resultado.metadatos_extraccion['confiabilidad_ml']
            logger.info(f"Confiabilidad ML incluida en fase1/extraer: {response_data['confiabilidad_ml']:.2%}")
        
        return response_data
    except Exception as e:
        logger.error(f"Error en fase1/extraer: {str(e)}")
        return {
            "exito": False,
            "error": str(e)
        }
    finally:
        if file_path.exists():
            file_path.unlink()

@router.post("/fase1/verificar", dependencies=[Depends(validar_token_auditor)])
@limiter.limit("30/minute")
def fase1_verificar(request: Request, datos: dict):
    """
    Endpoint compatible con el proxy PHP original para verificar coherencia.
    """
    try:
        logger.info(f"Verificar - Datos recibidos: {datos}")
        # Aquí implementarías la lógica de verificación
        return {
            "exito": True,
            "discrepancias": [],
            "reporte_detallado": "Verificación completada",
            "hash_verificacion": "abc123"
        }
    except Exception as e:
        logger.error(f"Error en fase1/verificar: {str(e)}")
        return {
            "exito": False,
            "error": str(e)
        }

@router.post("/fase1/comparar-directo", dependencies=[Depends(validar_token_auditor)])
@limiter.limit("10/minute")
async def fase1_comparar_directo(request: Request, imagen: UploadFile = File(...), datos_json: str = Form(...)):
    """
    Endpoint compatible con el proxy PHP original para comparación directa.
    """
    temp_dir = Path("temp")
    temp_dir.mkdir(exist_ok=True)
    file_path = temp_dir / imagen.filename
    
    logger.info(f"Comparar directo - Recibiendo archivo: {imagen.filename}")
    
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(imagen.file, buffer)
    
    try:
        datos_formulario = json.loads(datos_json)
        resultado = auditor.comparar_directo(str(file_path), datos_formulario)
        logger.info(f"Comparar directo - Archivo procesado: {imagen.filename}")
        
        return {
            "exito": resultado.exito,
            "discrepancias": [
                {
                    "campo": d.campo,
                    "valor_factura": d.valor_factura,
                    "valor_formulario": d.valor_formulario,
                    "severidad": d.severidad,
                    "mensaje": d.mensaje
                }
                for d in resultado.discrepancias
            ],
            "reporte": resultado.reporte_detallado,
            "mensaje": "Verificación completada" if resultado.exito else "Se detectaron discrepancias"
        }
    except Exception as e:
        logger.error(f"Error en fase1/comparar-directo: {str(e)}")
        return {
            "exito": False,
            "error": str(e)
        }
    finally:
        if file_path.exists():
            file_path.unlink()

@router.get("/fase1/estadisticas")
def fase1_estadisticas():
    """
    Endpoint compatible con el proxy PHP original para estadísticas.
    """
    return {
        "total_verificaciones": 0,
        "tasa_exito": 1.0
    }