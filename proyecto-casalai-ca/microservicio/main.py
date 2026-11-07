from fastapi import FastAPI, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from dotenv import load_dotenv
import os
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded

# Cargar variables de entorno desde archivo .env
load_dotenv()

from config import settings
from modelo.logger import logger

# Importación de los routers ubicados en la carpeta controlador/
# Asegúrate de que api_analitica.py y api_recepcion.py expongan una variable llamada 'router'
try:
    from controlador.api_analitica import router as analitica_router
except ImportError:
    analitica_router = None

try:
    from controlador.api_recepcion import router as recepcion_router
except ImportError:
    recepcion_router = None

# Inicialización de la aplicación FastAPI
app = FastAPI(
    title=settings.PROJECT_NAME,
    description="Microservicio de Recepción y Verificación Asistida - Casalai AI",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc",
)

# Configuración de Rate Limiting
limiter = Limiter(key_func=get_remote_address)
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)

# Configuración de CORS (Permite peticiones desde orígenes cruzados si tu frontend o cliente lo requiere)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, restringe esto a dominios específicos
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("startup")
def startup_event():
    logger.info("Iniciando microservicio unificado de recepción y analítica Casa Lai, C.A...")

# Ruta Raíz de Verificación de Estado (Health Check)
@app.get("/", tags=["Estado del Sistema"])
def root():
    return {
        "status": "online",
        "project": settings.PROJECT_NAME,
        "message": "El microservicio está operando correctamente."
    }

# Agrupación e inclusión de los Routers bajo el prefijo API v1
if recepcion_router:
    app.include_router(
        recepcion_router, 
        prefix=settings.API_V1_STR
    )

if analitica_router:
    app.include_router(
        analitica_router, 
        prefix=settings.API_V1_STR
    )

# Incluir también endpoints de fase1 sin el prefijo /api/v1 para compatibilidad con PHP
if recepcion_router:
    app.include_router(recepcion_router)

# Punto de ejecución local opcional (ej: python main.py)
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:main", host="0.0.0.0", port=8000, reload=True)