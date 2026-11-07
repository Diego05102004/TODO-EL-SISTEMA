import os
from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    """
    Configuración centralizada para el microservicio de recepción y analítica.
    Permite sobreescribir valores mediante un archivo .env o variables de entorno del sistema.
    """
    PROJECT_NAME: str = "Casalai AI - Microservicio de Recepción"
    API_V1_STR: str = "/api/v1"
    DEBUG: bool = False

    # Credenciales y Seguridad
    API_KEY: str = os.getenv("API_KEY", "clave_secreta_por_defecto_cambiar_en_produccion")
    
    # Tokens de autenticación para microservicios
    AUDITOR_RECEPCION_TOKEN: str = os.getenv("AUDITOR_RECEPCION_TOKEN", "")
    ANALITICA_PREDICTIVA_TOKEN: str = os.getenv("ANALITICA_PREDICTIVA_TOKEN", "")

    # Rutas de Modelos y Almacenamiento
    MODEL_PATH: str = os.getenv("MODEL_PATH", "modelo/analitica_predictiva.pkl")
    FEEDBACK_PATH: str = os.getenv("FEEDBACK_PATH", "modelo/feedback_store.json")

    # Parámetros de Conexión / Base de Datos (si aplica en el microservicio)
    DATABASE_URL: str = os.getenv("DATABASE_URL", "sqlite:///./casalai_recepcion.db")

    class Config:
        env_file = ".env"
        case_sensitive = True
        extra = "ignore"  # Permite variables de entorno extra sin error

# Instancia global de configuración
settings = Settings()