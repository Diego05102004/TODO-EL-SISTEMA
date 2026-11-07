"""Módulo: auditor_recepcion.py - Modelo de IA para auditoría de recepción. Fase 1: Verificación Asistida."""

import re
import json
import logging
import hashlib
from typing import Dict, List, Optional, Tuple, Any
from dataclasses import dataclass, field, asdict
from datetime import datetime
from pathlib import Path
import pytesseract
from PIL import Image
import cv2
import numpy as np
import joblib
import yaml
import csv
import os
from modelo.adaptador_bd import FabricaAdaptadores, Producto, Proveedor

# Asegurar que el directorio de logs exista
BASE_DIR = Path(__file__).parent.parent
LOGS_DIR = BASE_DIR / "logs_metricas"
LOGS_DIR.mkdir(parents=True, exist_ok=True)

# Rutas de la base de conocimiento externa
CONOCIMIENTO_DIR = BASE_DIR / "conocimiento"
PATRONES_FILE = CONOCIMIENTO_DIR / "patrones_factura.yaml"
REGLAS_FILE = CONOCIMIENTO_DIR / "reglas_validacion.yaml"
CATALOGO_FILE = CONOCIMIENTO_DIR / "catalogo_productos.csv"
CONFIG_ML_FILE = CONOCIMIENTO_DIR / "configuracion_ml.yaml"

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[logging.FileHandler(str(LOGS_DIR / 'auditor_recepcion.log')), logging.StreamHandler()])
logger = logging.getLogger(__name__)

try:
    from pdf2image import convert_from_path
    PDF_AVAILABLE = True
except ImportError:
    PDF_AVAILABLE = False
    convert_from_path = None
    logger.warning("pdf2image no disponible. Los PDFs no podrán procesarse.")

@dataclass
class ProductoExtraido:
    nombre: str = ""
    modelo: str = ""
    marca: str = ""
    serial: str = ""
    cantidad: int = 0
    costo_unitario: float = 0.0
    confianza: float = 0.0

@dataclass
class FacturaExtraida:
    numero_factura: str = ""
    nombre_proveedor: str = ""
    fecha_factura: str = ""
    productos: List[ProductoExtraido] = field(default_factory=list)
    subtotal_factura: float = 0.0
    porcentaje_iva: float = 0.0
    monto_iva: float = 0.0
    total_factura: float = 0.0
    metadatos_extraccion: Dict = field(default_factory=dict)

@dataclass
class Discrepancia:
    campo: str
    valor_factura: str
    valor_formulario: str
    severidad: str
    mensaje: str

@dataclass
class ResultadoVerificacion:
    exito: bool
    discrepancias: List[Discrepancia]
    factura_extraida: Optional[FacturaExtraida]
    datos_formulario: Optional[Dict]
    reporte_detallado: str
    timestamp: str = field(default_factory=lambda: datetime.now().isoformat())
    hash_verificacion: str = ""
    def __post_init__(self):
        if not self.hash_verificacion:
            data = f"{self.timestamp}{json.dumps(self.datos_formulario, sort_keys=True, default=str)}"
            self.hash_verificacion = hashlib.sha256(data.encode()).hexdigest()[:16]

# ==========================================
# FUNCIONES PARA CARGAR BASE DE CONOCIMIENTO
# ==========================================

def cargar_patrones_yaml(ruta: Path) -> Dict:
    """Carga los patrones regex desde el archivo YAML."""
    try:
        with open(ruta, 'r', encoding='utf-8') as f:
            data = yaml.safe_load(f)
        # Convertir estructura YAML a diccionario de patrones
        patrones = {}
        for campo, config in data.get('campos', {}).items():
            patrones[campo] = config.get('patrones', [])
        logger.info(f"Patrones cargados desde {ruta}")
        return patrones
    except Exception as e:
        logger.error(f"Error cargando patrones desde {ruta}: {e}")
        # Retornar patrones por defecto si falla la carga
        return {}

def cargar_reglas_yaml(ruta: Path) -> Dict:
    """Carga las reglas de validación desde el archivo YAML."""
    try:
        with open(ruta, 'r', encoding='utf-8') as f:
            data = yaml.safe_load(f)
        logger.info(f"Reglas cargadas desde {ruta}")
        return data
    except Exception as e:
        logger.error(f"Error cargando reglas desde {ruta}: {e}")
        return {}

def cargar_catalogo_csv(ruta: Path) -> List[Dict]:
    """Carga el catálogo de productos desde el archivo CSV."""
    try:
        catalogo = []
        with open(ruta, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                catalogo.append(row)
        logger.info(f"Catálogo cargado desde {ruta}: {len(catalogo)} productos")
        return catalogo
    except Exception as e:
        logger.error(f"Error cargando catálogo desde {ruta}: {e}")
        return []

def cargar_config_ml_yaml(ruta: Path) -> Dict:
    """Carga la configuración del modelo ML desde el archivo YAML."""
    try:
        with open(ruta, 'r', encoding='utf-8') as f:
            data = yaml.safe_load(f)
        logger.info(f"Configuración ML cargada desde {ruta}")
        return data
    except Exception as e:
        logger.error(f"Error cargando configuración ML desde {ruta}: {e}")
        return {}

class AuditorRecepcion:
    UMBRAL_CONFIANZA_ALTA = 0.85
    UMBRAL_CONFIANZA_MEDIA = 0.60
    PATRONES_POR_DEFECTO = {
        'numero_factura': [
            r'(?:factura|n[°º]|nro|numero)\s*[:#]?\s*([A-Z0-9\-]{5,20})',
            r'(?:factura)\s*(?:n[°º])?\s*[.:]?\s*([A-Z0-9\-]+)',
            r'(?:invoice|inv)\s*(?:#|n[°º])?\s*[.:]?\s*([A-Z0-9\-]+)',
            r'(?:bill)\s*(?:#)?\s*[.:]?\s*([A-Z0-9\-]+)',
            r'(?:receipt)\s*(?:#)?\s*[.:]?\s*([A-Z0-9\-]+)',
            r'#\s*(\d{6,10})',  # Patrón específico para #839204
            r'(\d{6,10})',
            r'#\s*([A-Z0-9\-]+)'
        ],
        'proveedor': [
            r'(?:proveedor|vendedor|supplier|vendor|de|from)[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+(?:S\.A|S\.R\.L|C\.A|LLC|INC|LTDA|S\.A\.S|GmbH|Ltd|Pty)?)',
            r'(?:razon social|nombre|company|empresa)[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)',
            r'(?:sold\s*by|billed\s*by)[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)',
            r'eBay|Amazon|MercadoLibre|AliExpress|Walmart|BestBuy|Newegg|Staples|Office Depot',
            r'(?:HP|Canon|Epson|Brother|Xerox|Dell|Lenovo|Asus|Samsung|LG|Sony|Apple|Microsoft|Logitech|Intel|AMD|NVIDIA)\s+(?:Inc|Corp|LLC|GmbH|Ltd)?'
        ],
        'fecha': [
            r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})',
            r'(\d{4}[/-]\d{1,2}[/-]\d{1,2})',
            r'(\d{1,2}\s+(?:de|of|del)\s+[A-Za-z]+\s+\d{2,4})',
            r'([A-Za-z]+\s+\d{1,2},?\s+\d{4})',
            r'(\d{1,2}\s+[A-Za-z]{3}\s+\d{4})',
            r'(\d{8})'  # Formato compacto DDMMYYYY
        ],
        'producto': [
            r'(?:descripcion|producto|articulo|item|product|description)[:\s]+([^\n]+)',
            r'([A-Z][a-zA-Z0-9\s\-\+]+(?:Printer|Laptop|Desktop|Monitor|Phone|Tablet|Camera|Headphones|Speaker|Mouse|Keyboard|Router|Modem|Scanner|Copier|Ink|Toner|Cartridge|Memory|SSD|HDD|CPU|GPU))',
            r'([A-Z][a-zA-Z0-9\s\-\+]{10,100})'
        ],
        'modelo': [
            r'(?:modelo|model|mod|type)[:\s]+([A-Z0-9\-\s]+)',
            r'([A-Z0-9]{3,15}(?:\s*[A-Z0-9\-]+)*)',
            r'(?:model\s*no|model\s*number)[:\s]*([A-Z0-9\-\s]+)'
        ],
        'marca': [
            r'(?:marca|brand|fabricante|manufacturer|maker)[:\s]+([A-Za-z0-9\s]+)',
            r'(HP|Canon|Epson|Brother|Xerox|Dell|Lenovo|Asus|Samsung|LG|Sony|Apple|Microsoft|Logitech|Intel|AMD|NVIDIA|Kingston|SanDisk|Crucial|Seagate|Western Digital|Toshiba|Fujitsu|Kyocera|Lexmark|Ricoh|Konica|Oki)',
            r'(?:by|from)\s+([A-Z][A-Za-z0-9]+)'
        ],
        'serial': [
            r'(?:serial|s/n|serie|sn)[:\s]+([A-Z0-9\-]+)',
            r'(?:SN|S/N)[:\s]*([A-Z0-9\-]+)',
            r'(?:serial\s*number)[:\s]*([A-Z0-9\-]+)',
            r'([A-Z0-9]{8,20})'
        ],
        'cantidad': [
            r'(?:cantidad|qty|quantity|cant)[:\s]+(\d+)'
        ],
        'costo': [
            r'(?:precio|costo|price|valor|unitario|p/u|unit\s*price)[:\s]+[\$]?\s*(\d+[.,]?\d*)',
            r'[\$]\s*(\d+[.,]?\d{2})',
            r'(\d+[.,]\d{2})',
            r'(?:rate)[:\s]+[\$]?\s*(\d+[.,]?\d*)'
        ],
        'total': [
            r'(?:total|monto total|importe)[:\s]+[\$]?\s*(\d+[.,]?\d{2})',
            r'total\s*[\$]?\s*(\d+[.,]?\d{2})',
            r'(?:grand\s*total|amount\s*due|balance\s*due)[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
        ]
    }

    def __init__(self, config_path: Optional[str] = None):
        self.config = self._cargar_configuracion(config_path)
        self.cache_facturas: Dict[str, FacturaExtraida] = {}
        self.historial_verificaciones: List[ResultadoVerificacion] = []
        
        # Cargar base de conocimiento externa
        self.patrones = self._cargar_patrones()
        self.reglas = self._cargar_reglas()
        
        # Cargar catálogo dinámico desde base de datos usando adaptador
        self.adaptador_bd = self._crear_adaptador_bd()
        self.catalogo_productos = self._cargar_catalogo()
        self.catalogo_proveedores = self._cargar_proveedores()
        self.config_ml = self._cargar_config_ml()
        
        # Actualizar umbrales desde reglas si están disponibles
        if self.reglas and 'umbrales_confianza' in self.reglas:
            self.UMBRAL_CONFIANZA_ALTA = self.reglas['umbrales_confianza'].get('alta', 0.85)
            self.UMBRAL_CONFIANZA_MEDIA = self.reglas['umbrales_confianza'].get('media', 0.60)
        
        # Cargar modelo ML entrenado si existe
        self.modelo_ml = None
        model_path = Path(__file__).parent / "modelo_actual.pkl"
        if model_path.exists():
            try:
                self.modelo_ml = joblib.load(model_path)
                logger.info("Modelo ML cargado exitosamente desde modelo_actual.pkl")
            except Exception as e:
                logger.warning(f"No se pudo cargar el modelo ML: {e}")
        else:
            logger.info("No se encontró modelo_actual.pkl. El modelo ML no estará disponible.")
        
        logger.info("AuditorRecepcion inicializado - Fase 1: Verificación Asistida")
        logger.info(f"Base de conocimiento cargada: {len(self.patrones)} campos, {len(self.catalogo_productos)} productos, {len(self.catalogo_proveedores)} proveedores")
    
    def _cargar_patrones(self) -> Dict:
        """Carga los patrones desde YAML o usa los por defecto."""
        if PATRONES_FILE.exists():
            return cargar_patrones_yaml(PATRONES_FILE)
        else:
            logger.warning(f"No se encontró {PATRONES_FILE}, usando patrones por defecto")
            return self.PATRONES_POR_DEFECTO.copy()
    
    def _cargar_reglas(self) -> Dict:
        """Carga las reglas de validación desde YAML."""
        if REGLAS_FILE.exists():
            return cargar_reglas_yaml(REGLAS_FILE)
        else:
            logger.warning(f"No se encontró {REGLAS_FILE}, usando reglas por defecto")
            return {}
    
    def _cargar_catalogo(self) -> List[Dict]:
        """Carga el catálogo de productos desde el adaptador de base de datos."""
        if self.adaptador_bd:
            try:
                productos = self.adaptador_bd.obtener_productos()
                # Convertir objetos Producto a diccionarios para compatibilidad
                return [
                    {
                        'id': p.id,
                        'nombre': p.nombre,
                        'modelo': p.modelo,
                        'marca': p.marca,
                        'costo_unitario': p.costo_unitario
                    }
                    for p in productos
                ]
            except Exception as e:
                logger.error(f"Error cargando catálogo desde adaptador: {e}")
                # Fallback a CSV si falla la BD
                if CATALOGO_FILE.exists():
                    return cargar_catalogo_csv(CATALOGO_FILE)
                return []
        else:
            # Fallback a CSV si no hay adaptador
            if CATALOGO_FILE.exists():
                return cargar_catalogo_csv(CATALOGO_FILE)
            logger.warning("No hay adaptador BD ni CSV, catálogo vacío")
            return []
    
    def _cargar_proveedores(self) -> List[Dict]:
        """Carga el catálogo de proveedores desde el adaptador de base de datos."""
        if self.adaptador_bd:
            try:
                proveedores = self.adaptador_bd.obtener_proveedores()
                return [
                    {
                        'id': p.id,
                        'nombre': p.nombre
                    }
                    for p in proveedores
                ]
            except Exception as e:
                logger.error(f"Error cargando proveedores desde adaptador: {e}")
                return []
        else:
            logger.warning("No hay adaptador BD, proveedores vacíos")
            return []
    
    def _crear_adaptador_bd(self):
        """Crea el adaptador de base de datos según configuración del entorno."""
        try:
            bd_tipo = os.getenv('BD_TIPO', 'csv')
            
            if bd_tipo == 'mysql':
                config = {
                    'host': os.getenv('BD_HOST', 'localhost'),
                    'port': int(os.getenv('BD_PORT', 3306)),
                    'user': os.getenv('BD_USER', 'MicroserviceCAI'),
                    'password': os.getenv('BD_PASSWORD', 'MSagente#26'),
                    'database': os.getenv('BD_DATABASE', 'casalai')
                }
                adaptador = FabricaAdaptadores.crear_adaptador('mysql', config)
                if adaptador:
                    logger.info(f"Adaptador MySQL creado: {config['host']}:{config['port']}/{config['database']}")
                return adaptador
            elif bd_tipo == 'csv':
                config = {
                    'ruta_productos': str(CATALOGO_FILE),
                    'ruta_proveedores': str(CONOCIMIENTO_DIR / 'proveedores.csv')
                }
                adaptador = FabricaAdaptadores.crear_adaptador('csv', config)
                if adaptador:
                    logger.info(f"Adaptador CSV creado: {config['ruta_productos']}")
                return adaptador
            else:
                logger.warning(f"Tipo de BD no reconocido: {bd_tipo}, usando CSV fallback")
                return None
        except Exception as e:
            logger.error(f"Error creando adaptador BD: {e}")
            return None
    
    def _cargar_config_ml(self) -> Dict:
        """Carga la configuración ML desde YAML."""
        if CONFIG_ML_FILE.exists():
            return cargar_config_ml_yaml(CONFIG_ML_FILE)
        else:
            logger.warning(f"No se encontró {CONFIG_ML_FILE}, usando configuración por defecto")
            return {}

    def _cargar_configuracion(self, config_path: Optional[str]) -> Dict:
        # Configurar ruta de Tesseract para Windows
        tesseract_cmd = 'tesseract'
        import platform
        if platform.system() == 'Windows':
            tesseract_path = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
            if Path(tesseract_path).exists():
                tesseract_cmd = tesseract_path
                logger.info(f"Tesseract encontrado en: {tesseract_path}")
        
        config_default = {'tesseract_cmd': tesseract_cmd, 'idioma_ocr': 'spa+eng', 'preprocesamiento': True,
            'guardar_cache': True, 'umbral_exactitud': 0.90, 'tolerancia_costo': 0.05, 'tolerancia_cantidad': 0}
        if config_path and Path(config_path).exists():
            try:
                with open(config_path, 'r', encoding='utf-8') as f:
                    config_default.update(json.load(f))
            except Exception as e:
                logger.warning(f"Error cargando config: {e}")
        return config_default

    def extraer_desde_imagen(self, ruta_imagen: str, id_cache: Optional[str] = None) -> FacturaExtraida:
        logger.info(f"Iniciando extracción OCR: {ruta_imagen}")
        if not Path(ruta_imagen).exists():
            raise FileNotFoundError(f"Imagen no encontrada: {ruta_imagen}")
        imagenes_procesadas = self._preprocesar_imagen(ruta_imagen)
        if isinstance(imagenes_procesadas, list):
            texto_extraido = "\n".join(self._ejecutar_ocr(imagen) for imagen in imagenes_procesadas)
        else:
            texto_extraido = self._ejecutar_ocr(imagenes_procesadas)
        factura = self._extraer_campos_factura(texto_extraido)
        confianza_promedio = self._calcular_confianza_promedio(factura)
        
        # Validar si el documento es una factura
        es_factura_valida, mensaje_error = self._validar_es_factura(factura, texto_extraido)
        
        factura.metadatos_extraccion = {
            'timestamp': datetime.now().isoformat(),
            'longitud_texto': len(texto_extraido),
            'confianza_promedio': confianza_promedio,
            'lineas_detectadas': len(texto_extraido.split('\n')),
            'metodo': 'OCR_Tesseract + Regex_Contextual',
            'es_factura_valida': es_factura_valida,
            'mensaje_validacion': mensaje_error
        }
        
        # Predecir confiabilidad usando el modelo ML si está disponible
        if self.modelo_ml:
            factura.metadatos_extraccion['confiabilidad_ml'] = self._predecir_confiabilidad(confianza_promedio)
            logger.info(f"Confiabilidad ML predicha: {factura.metadatos_extraccion['confiabilidad_ml']:.2%}")
        
        if id_cache and self.config['guardar_cache']:
            self.cache_facturas[id_cache] = factura
        logger.info(f"Extracción completada. Confianza OCR: {confianza_promedio:.2%}")
        return factura

    def verificar_coherencia(self, id_factura_cache: str, datos_formulario: Dict) -> ResultadoVerificacion:
        logger.info(f"Verificación de coherencia: {id_factura_cache}")
        if id_factura_cache not in self.cache_facturas:
            error_msg = f"Factura no encontrada en cache: {id_factura_cache}"
            logger.error(error_msg)
            return ResultadoVerificacion(exito=False, discrepancias=[Discrepancia(campo="SISTEMA", valor_factura="N/A", valor_formulario="N/A", severidad="CRITICA", mensaje=error_msg)],
                factura_extraida=None, datos_formulario=datos_formulario, reporte_detallado=error_msg)
        factura = self.cache_facturas[id_factura_cache]
        discrepancias: List[Discrepancia] = []
        if 'numero_factura' in datos_formulario:
            disc = self._comparar_campo('Número de Factura', factura.numero_factura, datos_formulario['numero_factura'], 'CRITICA')
            if disc: discrepancias.append(disc)
        if 'nombre_proveedor' in datos_formulario:
            disc = self._comparar_campo_proveedor(factura.nombre_proveedor, datos_formulario['nombre_proveedor'])
            if disc: discrepancias.append(disc)
        productos_form = datos_formulario.get('productos', [])
        for idx, prod_form in enumerate(productos_form):
            disc_producto = self._verificar_producto(factura.productos, prod_form, idx)
            discrepancias.extend(disc_producto)
        exito = len(discrepancias) == 0
        reporte = self._generar_reporte(discrepancias, factura, datos_formulario)
        resultado = ResultadoVerificacion(exito=exito, discrepancias=discrepancias, factura_extraida=factura, datos_formulario=datos_formulario, reporte_detallado=reporte)
        self.historial_verificaciones.append(resultado)
        logger.info(f"Verificación: {'exitosa' if exito else f'{len(discrepancias)} discrepancias'}")
        return resultado

    def comparar_directo(self, ruta_imagen: str, datos_formulario: Dict) -> ResultadoVerificacion:
        id_temp = f"temp_{datetime.now().strftime('%Y%m%d%H%M%S')}_{hashlib.md5(ruta_imagen.encode()).hexdigest()[:8]}"
        factura = self.extraer_desde_imagen(ruta_imagen, id_temp)
        return self.verificar_coherencia(id_temp, datos_formulario)

    def _predecir_confiabilidad(self, confianza_ocr: float) -> float:
        """
        Usa el modelo RandomForest para predecir si la extracción es confiable.
        Retorna probabilidad de que sea confiable (0.0 a 1.0).
        
        El modelo se entrena para predecir confiabilidad basándose únicamente en
        la confianza OCR.
        """
        if not self.modelo_ml:
            logger.warning("Modelo ML no disponible, retornando 0.0")
            return 0.0
        
        try:
            # Feature: solo confianza OCR (disponible durante extracción)
            features = [[confianza_ocr]]
            
            # Obtener probabilidades de predicción
            prediccion = self.modelo_ml.predict_proba(features)[0]
            logger.info(f"Predicción ML para confianza_ocr={confianza_ocr:.2%}: {prediccion}")
            
            # Si el modelo solo tiene una clase, usar la confianza OCR como fallback
            if len(prediccion) == 1:
                logger.warning("Modelo solo tiene una clase, usando confianza OCR como fallback")
                return confianza_ocr
            
            # Retornar probabilidad de clase 1 (confiable)
            # Asumimos que las clases son [0: no confiable, 1: confiable]
            return prediccion[1]
        except Exception as e:
            logger.warning(f"Error prediciendo confiabilidad: {e}")
            # En caso de error, usar la confianza OCR como fallback
            return confianza_ocr

    def _preprocesar_imagen(self, ruta_imagen: str) -> Any:
        if Path(ruta_imagen).suffix.lower() == '.pdf':
            paginas = self._convertir_pdf_a_imagen(ruta_imagen)
            # Optimización: Procesamiento paralelo de páginas con ThreadPoolExecutor
            from concurrent.futures import ThreadPoolExecutor
            with ThreadPoolExecutor(max_workers=4) as executor:
                return list(executor.map(lambda p: self._preprocesar_array(np.array(p)), paginas))

        imagen = cv2.imread(ruta_imagen)
        if imagen is None:
            raise ValueError(f"No se pudo cargar: {ruta_imagen}")
        return self._preprocesar_array(imagen)

    def _preprocesar_array(self, imagen: np.ndarray) -> np.ndarray:
        if not self.config['preprocesamiento']: return imagen
        gris = cv2.cvtColor(imagen, cv2.COLOR_BGR2GRAY)
        # Optimización: Reducir operaciones de preprocesamiento para mejorar velocidad
        # Eliminar denoising costoso, usar Gaussian blur más rápido
        denoised = cv2.GaussianBlur(gris, (3, 3), 0)
        # Optimización: CLAHE con parámetros más agresivos para mejor contraste
        clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(4, 4))
        contraste = clahe.apply(denoised)
        # Optimización: Usar Otsu threshold más rápido que adaptive
        _, binaria = cv2.threshold(contraste, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        return binaria

    def _ejecutar_ocr(self, imagen: np.ndarray) -> str:
        # Configurar pytesseract con la ruta específica
        if 'tesseract_cmd' in self.config and self.config['tesseract_cmd'] != 'tesseract':
            pytesseract.pytesseract.tesseract_cmd = self.config['tesseract_cmd']
        
        config_tesseract = '--psm 6 --oem 3'
        return pytesseract.image_to_string(imagen, lang=self.config['idioma_ocr'], config=config_tesseract)

    def _validar_es_factura(self, factura: FacturaExtraida, texto: str) -> tuple[bool, str]:
        """
        Valida si el documento extraído parece ser una factura válida.
        Retorna (es_valido, mensaje_error).
        """
        # Palabras clave que deben aparecer en una factura
        palabras_clave_factura = [
            'factura', 'invoice', 'bill', 'receipt', 'total', 'subtotal',
            'iva', 'tax', 'impuesto', 'precio', 'costo', 'price', 'cantidad',
            'quantity', 'proveedor', 'vendor', 'supplier'
        ]
        
        texto_lower = texto.lower()
        palabras_encontradas = sum(1 for palabra in palabras_clave_factura if palabra in texto_lower)
        
        # Si hay muy pocas palabras clave, probablemente no es una factura
        if palabras_encontradas < 2:
            return False, "El documento no parece ser una factura. Por favor seleccione una factura válida."
        
        # Validar que tenga al menos algunos campos básicos
        campos_esenciales = [
            factura.numero_factura,
            factura.nombre_proveedor,
            factura.fecha_factura,
            factura.productos
        ]
        campos_llenos = sum(1 for campo in campos_esenciales if campo)
        
        if campos_llenos < 2:
            return False, "El documento no tiene suficiente información para ser considerado una factura válida."
        
        # Validar que tenga montos monetarios
        if factura.subtotal_factura == 0 and factura.total_factura == 0:
            return False, "El documento no contiene montos monetarios. Verifique que sea una factura."
        
        return True, ""

    def _extraer_campos_factura(self, texto: str) -> FacturaExtraida:
        factura = FacturaExtraida()
        texto_limpio = self._limpiar_texto(texto)
        
        # Extraer número de factura y validar que sea solo dígitos
        numero_factura_raw = self._extraer_con_patrones(texto_limpio, self.patrones.get('numero_factura', self.PATRONES_POR_DEFECTO['numero_factura']))
        factura.numero_factura = self._validar_numero_factura(numero_factura_raw)
        
        factura.nombre_proveedor = self._extraer_con_patrones(texto_limpio, self.patrones.get('proveedor', self.PATRONES_POR_DEFECTO['proveedor']))
        factura.fecha_factura = self._extraer_con_patrones(texto_limpio, self.patrones.get('fecha', self.PATRONES_POR_DEFECTO['fecha']))
        factura.productos = self._extraer_productos(texto_limpio)
        
        # Buscar productos en catálogo MySQL para obtener datos correctos
        factura.productos = self._enriquecer_productos_con_catalogo(factura.productos)

        # Patrones más flexibles para subtotal (acepta comas/puntos al final)
        subtotal_match = re.search(
            r'(?:sub\s*total|subtotal|amount|amount\s*before\s*tax)\s*[:\-]?\s*\$?\s*([\d.,]+)',
            texto_limpio,
            re.IGNORECASE | re.MULTILINE
        )
        if subtotal_match:
            factura.subtotal_factura = self._parsear_monto(subtotal_match.group(1))
        else:
            factura.subtotal_factura = round(
                sum(producto.cantidad * producto.costo_unitario for producto in factura.productos), 2
            )

        # Patrones más flexibles para IVA (acepta comas/puntos al final)
        iva_match = re.search(
            r'(?:iva|igv|vat|tax|impuesto|sales\s*tax)\s*'
            r'(?:\(?\s*([\d.,]+)\s*%\s*\)?\s*)?'
            r'[:\-]?\s*\$?\s*([\d.,]+)',
            texto_limpio,
            re.IGNORECASE | re.MULTILINE
        )
        if iva_match:
            factura.porcentaje_iva = self._parsear_monto(iva_match.group(1) or '')
            factura.monto_iva = self._parsear_monto(iva_match.group(2))
            if factura.monto_iva > 0 and factura.porcentaje_iva == 0 and factura.subtotal_factura > 0:
                factura.porcentaje_iva = round(factura.monto_iva / factura.subtotal_factura * 100, 2)

        # Patrones más flexibles para total (acepta comas/puntos al final)
        # Usamos negative lookbehind para evitar capturar "subtotal"
        total_match = re.search(
            r'(?<!sub)(?:total|gran\s+total|total\s+a\s+pagar|importe\s*total|order\s+total|grand\s+total)\s*[:\-]?\s*\$?\s*([\d.,]+)',
            texto_limpio,
            re.IGNORECASE | re.MULTILINE
        )
        if total_match:
            factura.total_factura = self._parsear_monto(total_match.group(1))
        else:
            factura.total_factura = round(factura.subtotal_factura + factura.monto_iva, 2)
        return factura

    def _extraer_con_patrones(self, texto: str, patrones: List[str]) -> str:
        for patron in patrones:
            coincidencias = re.findall(patron, texto, re.IGNORECASE)
            if coincidencias:
                return coincidencias[0].strip() if isinstance(coincidencias[0], str) else str(coincidencias[0])
        return ""

    def _validar_numero_factura(self, numero: str) -> str:
        """
        Valida que el número de factura contenga solo dígitos.
        Si contiene letras, intenta extraer solo la parte numérica.
        """
        if not numero:
            return ""
        
        # Extraer solo dígitos del número
        solo_digitos = re.sub(r'[^\d]', '', numero)
        
        if solo_digitos and len(solo_digitos) >= 4:
            logger.info(f"Número de factura validado: {numero} -> {solo_digitos}")
            return solo_digitos
        else:
            logger.warning(f"Número de factura inválido: {numero} (no contiene suficientes dígitos)")
            return ""

    def _enriquecer_productos_con_catalogo(self, productos: List[ProductoExtraido]) -> List[ProductoExtraido]:
        """
        Busca cada producto en el catálogo MySQL usando nombre+modelo+marca
        para separar correctamente el nombre del modelo y obtener datos exactos.
        """
        logger.info(f"Iniciando enriquecimiento de {len(productos)} productos con catálogo MySQL")
        
        if not self.adaptador_bd:
            logger.warning("No hay adaptador BD, no se pueden enriquecer productos")
            return productos
        
        productos_enriquecidos = []
        
        for idx, producto in enumerate(productos):
            # Limpiar texto basura del modelo (encabezados/pies de página)
            producto.modelo = self._limpiar_texto_basura(producto.modelo or '')
            producto.nombre = self._limpiar_texto_basura(producto.nombre or '')
            
            logger.info(f"Procesando producto {idx+1}: nombre='{producto.nombre}', modelo='{producto.modelo}', marca='{producto.marca}'")
            
            # Intentar buscar en catálogo MySQL
            producto_catalogo = None
            
            # Estrategia 1: Buscar por nombre completo
            if producto.nombre:
                logger.info(f"  Estrategia 1: Buscando por nombre: '{producto.nombre}'")
                producto_catalogo = self.adaptador_bd.buscar_producto_por_nombre(producto.nombre)
                if producto_catalogo:
                    logger.info(f"  ✓ Encontrado en catálogo por nombre")
            
            # Estrategia 2: Si no encontró, intentar separar nombre de modelo y buscar solo el nombre
            if not producto_catalogo:
                nombre_sin_modelo, modelo_extraido = self._separar_nombre_modelo(producto.nombre, producto.modelo)
                if nombre_sin_modelo != producto.nombre:  # Si logró separar
                    logger.info(f"  Estrategia 2: Buscando por nombre sin modelo: '{nombre_sin_modelo}'")
                    producto_catalogo = self.adaptador_bd.buscar_producto_por_nombre(nombre_sin_modelo)
                    if producto_catalogo:
                        logger.info(f"  ✓ Encontrado en catálogo por nombre sin modelo")
            
            # Estrategia 3: Si no encontró, buscar combinando nombre+modelo
            if not producto_catalogo and producto.modelo:
                nombre_completo = f"{producto.nombre} {producto.modelo}"
                logger.info(f"  Estrategia 3: Buscando por nombre+modelo: '{nombre_completo}'")
                producto_catalogo = self.adaptador_bd.buscar_producto_por_nombre(nombre_completo)
                if producto_catalogo:
                    logger.info(f"  ✓ Encontrado en catálogo por nombre+modelo")
            
            # Estrategia 4: Si no encontró, buscar combinando nombre+modelo+marca
            if not producto_catalogo and producto.modelo and producto.marca:
                nombre_completo = f"{producto.nombre} {producto.modelo} {producto.marca}"
                logger.info(f"  Estrategia 4: Buscando por nombre+modelo+marca: '{nombre_completo}'")
                producto_catalogo = self.adaptador_bd.buscar_producto_por_nombre(nombre_completo)
                if producto_catalogo:
                    logger.info(f"  ✓ Encontrado en catálogo por nombre+modelo+marca")
            
            # Estrategia 5: Si el modelo contiene el nombre de otro producto, intentar separar
            if not producto_catalogo and producto.modelo:
                # Buscar si alguna parte del modelo coincide con un producto del catálogo
                logger.info(f"  Estrategia 5: Buscando si el modelo contiene un producto del catálogo: '{producto.modelo}'")
                # Dividir el modelo en palabras y buscar coincidencias parciales
                palabras_modelo = producto.modelo.split()
                for i in range(len(palabras_modelo), 2, -1):  # Probar desde 4 palabras hasta 3
                    parte_modelo = ' '.join(palabras_modelo[:i])
                    logger.info(f"    Buscando por parte del modelo: '{parte_modelo}'")
                    producto_catalogo = self.adaptador_bd.buscar_producto_por_nombre(parte_modelo)
                    if producto_catalogo:
                        logger.info(f"  ✓ Encontrado en catálogo por parte del modelo: '{parte_modelo}'")
                        break
            
            if producto_catalogo:
                # Producto encontrado en catálogo: usar datos del catálogo
                producto.nombre = producto_catalogo.nombre
                producto.modelo = producto_catalogo.modelo
                producto.marca = producto_catalogo.marca
                producto.confianza = 0.95  # Alta confianza al usar catálogo
                logger.info(f"  ✓ Producto enriquecido: nombre='{producto.nombre}', modelo='{producto.modelo}', marca='{producto.marca}'")
            else:
                # Producto no encontrado: intentar separar nombre de modelo
                nombre_original = producto.nombre
                producto.nombre, producto.modelo = self._separar_nombre_modelo(producto.nombre, producto.modelo)
                logger.warning(f"  ✗ Producto no encontrado en catálogo: '{nombre_original}' -> nombre='{producto.nombre}', modelo='{producto.modelo}'")
            
            productos_enriquecidos.append(producto)
        
        logger.info(f"Enriquecimiento completado: {len(productos_enriquecidos)} productos procesados")
        return productos_enriquecidos

    def _limpiar_texto_basura(self, texto: str) -> str:
        """
        Elimina texto basura de encabezados/pies de página de PDFs multipágina.
        Ejemplos: "Continua en la Pagina 2 de 5", "Lote de Importación", etc.
        """
        if not texto:
            return ""
        
        # Patrones de texto basura común en PDFs multipágina
        patrones_basura = [
            r'\s*Continua en la Pagina \d+ de \d+\s*',
            r'\s*Parte \d+ de \d+\s*',
            r'\s*Lote de [^\n]+\s*',
            r'\s*Kit de [^\n]+\s*',
            r'\s*\d{4,}\s*',  # Números aleatorios de 4+ dígitos (como 839204)
            r'\s*Ey \d+ \d+\s*',
            r'\s*Importaci[ée]n y Suministros[^\n]*',
            r'\s*Equipos de Oficina[^\n]*',
            r'\s*CasaLa[^\n]*',
            r'\s*pedido\s*',  # Palabra "pedido" basura
            r'\s*Pedido\s*',
            r'\s*Canon \d+ \d+\s*',  # Números aleatorios tipo Canon 48 00
        ]
        
        for patron in patrones_basura:
            texto = re.sub(patron, ' ', texto, flags=re.IGNORECASE)
        
        # Limpiar espacios múltiples
        texto = re.sub(r'\s+', ' ', texto).strip()
        
        return texto

    def _separar_nombre_modelo(self, nombre: str, modelo_actual: str) -> tuple[str, str]:
        """
        Intenta separar el nombre del producto del modelo cuando están juntos.
        Ejemplo: "Pendrive Kingston 12 Sublime" -> nombre="Pendrive Kingston 12", modelo="Sublime"
        """
        if not nombre:
            return "", modelo_actual or ""
        
        # Si ya tiene modelo, no hacer nada
        if modelo_actual:
            return nombre, modelo_actual
        
        palabras = nombre.split()
        
        # Primero, intentar separar la última palabra si parece un modelo numérico
        # (palabras que son solo números o letras+números)
        if len(palabras) > 1:
            ultima_palabra = palabras[-1]
            # Si la última palabra es solo números o alfanumérica, podría ser un modelo
            if ultima_palabra.isdigit() or (ultima_palabra.isalnum() and any(c.isdigit() for c in ultima_palabra)):
                nombre_parte = ' '.join(palabras[:-1])
                modelo_parte = ultima_palabra
                logger.info(f"Separado nombre/modelo (última palabra numérica): {nombre} -> nombre={nombre_parte}, modelo={modelo_parte}")
                return nombre_parte, modelo_parte
        
        # Lista de palabras comunes que suelen ser modelos
        palabras_modelo = ['Sublime', 'EcoTank', 'LaserJet', 'Envy', 'Photosmart', 
                           'OfficeJet', 'ThinkPad', 'IdeaPad', 'Legion', 'Yoga', 'VivoBook',
                           'ZenBook', 'ROG', 'TUF', 'Inspiron', 'XPS', 'Latitude', 'Precision',
                           'OptiPlex', 'PowerEdge', 'Galaxy', 'Galaxy Tab', 'iPad', 'iPhone',
                           'MacBook', 'iMac', 'Mac mini', 'AirPods', 'Apple Watch', 'Surface',
                           'Echo', 'Fire', 'Kindle', 'Roku', 'Chromecast', 'Nest', 'Ring',
                           'Durabrite', 'Innobella', 'L3250', 'S0156312']
        # Nota: DeskJet, 2775, 662 no están en la lista para evitar separaciones incorrectas
        
        # Buscar la última palabra que podría ser el modelo
        for i in range(len(palabras) - 1, 0, -1):
            palabra = palabras[i]
            if any(modelo.lower() in palabra.lower() for modelo in palabras_modelo):
                # Separar: todo antes es nombre, esta palabra y después es modelo
                nombre_parte = ' '.join(palabras[:i])
                modelo_parte = ' '.join(palabras[i:])
                if nombre_parte and modelo_parte:
                    logger.info(f"Separado nombre/modelo: {nombre} -> nombre={nombre_parte}, modelo={modelo_parte}")
                    return nombre_parte, modelo_parte
        
        # Si no se pudo separar, retornar original
        return nombre, ""

    def _extraer_productos(self, texto: str) -> List[ProductoExtraido]:
        productos_tabulares = self._extraer_productos_tabulares(texto)
        if productos_tabulares:
            return productos_tabulares

        productos = []
        lineas = texto.split('\n')
        
        # Método 1: Buscar líneas con patrones de productos específicos
        for i, linea in enumerate(lineas):
            if any(indicador in linea.lower() for indicador in ['producto', 'descripcion', 'articulo', 'item', 'codigo', 'sku']):
                prod = self._parsear_linea_producto(linea, lineas[i:i+3])
                if prod and prod.nombre: productos.append(prod)
        
        # Método 2: Parsing inteligente para facturas
        if not productos:
            productos = self._extraer_productos_style(texto)
        
        # Método 3: Extracción general como fallback
        if not productos:
            prod = self._extraer_producto_general(texto)
            if prod: productos.append(prod)
            
        return productos

    def _extraer_productos_tabulares(self, texto: str) -> List[ProductoExtraido]:
        patron_fila = re.compile(
            r"^\s*(?P<descripcion>.+?)\s+"
            r"(?P<marca>-?[A-Za-z][A-Za-z0-9&.-]*)\s+"
            r"(?P<serial>[A-Za-z0-9][A-Za-z0-9-]{2,})\s+"
            r"\$\s*(?P<unitario>[\d.,]+)\s+(?P<cantidad>\d+)\s+"
            r"\$\s*(?P<total>[\d.,]+)\s*$",
            re.IGNORECASE
        )
        productos = []
        producto_actual = None

        for linea in texto.splitlines():
            coincidencia = patron_fila.match(linea.strip())
            if coincidencia:
                datos = coincidencia.groupdict()
                descripcion = self._normalizar_descripcion_producto(datos['descripcion'])
                cantidad = int(datos['cantidad'])
                costo_unitario = self._parsear_monto(datos['unitario'])
                total_linea = self._parsear_monto(datos['total'])

                if cantidad > 0 and total_linea > 0 and costo_unitario > 0:
                    total_calculado = costo_unitario * cantidad
                    if abs(total_calculado - total_linea) > max(0.01, total_linea * 0.02):
                        costo_unitario = round(total_linea / cantidad, 2)
                        logger.warning(
                            "Precio unitario OCR inconsistente para %s; se recalculó como total/cantidad.",
                            descripcion
                        )

                producto_actual = ProductoExtraido(
                    nombre=descripcion,
                    marca=datos['marca'].strip(' .:-'),
                    serial=datos['serial'].strip(' .:-'),
                    cantidad=cantidad,
                    costo_unitario=costo_unitario,
                    confianza=0.9
                )
                productos.append(producto_actual)
                continue

            linea_limpia = linea.strip()
            if not producto_actual or not linea_limpia:
                continue
            if re.match(r'^(?:subtotal|iva|impuesto|total|gracias)\b', linea_limpia, re.IGNORECASE):
                producto_actual = None
                continue

            continuacion = self._normalizar_descripcion_producto(linea_limpia)
            if not continuacion:
                continue

            producto_actual.modelo = continuacion

        return productos

    def _normalizar_descripcion_producto(self, descripcion: str) -> str:
        descripcion = re.sub(r"[._'’]+", " ", descripcion)
        descripcion = re.sub(r"[^\w\s&+-]", " ", descripcion, flags=re.UNICODE)
        return re.sub(r"\s+", " ", descripcion).strip()

    def _extraer_productos_style(self, texto: str) -> List[ProductoExtraido]:
        """Extrae productos de facturas"""
        productos = []
        lineas = texto.split('\n')
        
        for i, linea in enumerate(lineas):
            # Buscar patrones como "HP: DeskJet 2775 HP 0002 $1520.00 2 $3040.00"
            if '$' in linea and any(marca in linea.upper() for marca in ['HP', 'CANON', 'EPSON', 'DELL', 'LENOVO']):
                prod = self._parsear_linea(linea)
                if prod: productos.append(prod)
        
        return productos

    def _parsear_linea(self, linea: str) -> Optional[ProductoExtraido]:
        """Parsea líneas con formato Proveedor: MARCA: Modelo MARCA Serial Precio Cantidad Total"""
        try:
            prod = ProductoExtraido()
            
            # Extraer marca (primera palabra antes de ":")
            if ':' in linea:
                marca_part = linea.split(':')[0].strip()
                if marca_part.upper() in ['HP', 'CANON', 'EPSON', 'DELL', 'LENOVO', 'ASUS', 'SAMSUNG']:
                    prod.marca = marca_part
            
            # Extraer modelo (después de ":" hasta el siguiente número o marca)
            resto = linea
            if ':' in linea:
                resto = linea.split(':', 1)[1].strip()
            
            # Buscar patrones de modelo
            import re
            modelo_match = re.search(r'([A-Za-z0-9\s\-\+]{3,20})', resto)
            if modelo_match:
                prod.modelo = modelo_match.group(1).strip()
            
            # Extraer precio
            precio_match = re.search(r'\$(\d+[.,]\d{2})', linea)
            if precio_match:
                prod.costo_unitario = float(precio_match.group(1).replace(',', ''))
            
            # Extraer cantidad
            cantidad_match = re.search(r'\$(\d+[.,]\d{2})\s+(\d+)', linea)
            if cantidad_match:
                prod.cantidad = int(cantidad_match.group(2))
            else:
                # Buscar número antes del precio total
                cantidad_match = re.search(r'(\d+)\s+\$\d+[.,]\d{2}', linea)
                if cantidad_match:
                    prod.cantidad = int(cantidad_match.group(1))
            
            # Extraer serial
            serial_match = re.search(r'([A-Z0-9]{4,10})', linea)
            if serial_match and len(serial_match.group(1)) >= 4:
                prod.serial = serial_match.group(1)
            
            # Asignar nombre basado en marca y modelo
            if prod.marca and prod.modelo:
                prod.nombre = f"{prod.marca} {prod.modelo}"
            elif prod.modelo:
                prod.nombre = prod.modelo
            elif prod.marca:
                prod.nombre = f"Producto {prod.marca}"
            
            # Calcular confianza
            campos_llenos = sum([bool(prod.nombre), bool(prod.modelo), bool(prod.marca), prod.cantidad > 0, prod.costo_unitario > 0])
            prod.confianza = campos_llenos / 5.0
            
            return prod if prod.nombre else None
            
        except Exception as e:
            logger.warning(f"Error parseando línea: {e}")
            return None

    def _parsear_linea_producto(self, linea: str, contexto: List[str]) -> Optional[ProductoExtraido]:
        prod = ProductoExtraido()
        texto_completo = ' '.join(contexto)
        prod.nombre = self._extraer_con_patrones(texto_completo, self.PATRONES['producto'])
        prod.modelo = self._extraer_con_patrones(texto_completo, self.PATRONES['modelo'])
        prod.marca = self._extraer_con_patrones(texto_completo, self.PATRONES['marca'])
        prod.serial = self._extraer_con_patrones(texto_completo, self.PATRONES['serial'])
        cantidad_str = self._extraer_con_patrones(texto_completo, self.PATRONES['cantidad'])
        prod.cantidad = int(cantidad_str) if cantidad_str.isdigit() else 1
        costo_str = self._extraer_con_patrones(texto_completo, self.PATRONES['costo'])
        prod.costo_unitario = self._parsear_monto(costo_str)
        campos_llenos = sum([bool(prod.nombre), bool(prod.modelo), bool(prod.marca), bool(prod.serial), prod.cantidad > 0, prod.costo_unitario > 0])
        prod.confianza = campos_llenos / 6.0
        return prod if prod.nombre else None

    def _extraer_producto_general(self, texto: str) -> Optional[ProductoExtraido]:
        prod = ProductoExtraido()
        prod.nombre = self._extraer_con_patrones(texto, self.PATRONES['producto'])
        prod.modelo = self._extraer_con_patrones(texto, self.PATRONES['modelo'])
        prod.marca = self._extraer_con_patrones(texto, self.PATRONES['marca'])
        prod.serial = self._extraer_con_patrones(texto, self.PATRONES['serial'])
        cantidad_str = self._extraer_con_patrones(texto, self.PATRONES['cantidad'])
        prod.cantidad = int(cantidad_str) if cantidad_str.isdigit() else 1
        costo_str = self._extraer_con_patrones(texto, self.PATRONES['costo'])
        prod.costo_unitario = self._parsear_monto(costo_str)
        return prod if prod.nombre else None

    def _comparar_campo(self, nombre_campo: str, valor_factura: str, valor_formulario: str, severidad: str) -> Optional[Discrepancia]:
        v_factura = self._normalizar_texto(valor_factura)
        v_formulario = self._normalizar_texto(valor_formulario)
        if v_factura != v_formulario:
            return Discrepancia(campo=nombre_campo, valor_factura=valor_factura, valor_formulario=valor_formulario, severidad=severidad, mensaje=f"Diferencia en {nombre_campo}: Factura='{valor_factura}' vs Formulario='{valor_formulario}'")
        return None

    def _comparar_campo_proveedor(self, valor_factura: str, valor_formulario: str) -> Optional[Discrepancia]:
        v_factura = self._normalizar_texto(valor_factura)
        v_formulario = self._normalizar_texto(valor_formulario)
        if v_factura == v_formulario: return None
        similitud = self._calcular_similitud(v_factura, v_formulario)
        if similitud < 0.80:
            return Discrepancia(campo="Nombre del Proveedor", valor_factura=valor_factura, valor_formulario=valor_formulario, severidad="ALTA", mensaje=f"Proveedor diferente (similitud: {similitud:.0%})")
        return None

    def _verificar_producto(self, productos_factura: List[ProductoExtraido], producto_form: Dict, idx: int) -> List[Discrepancia]:
        discrepancias = []
        prod_factura = self._encontrar_producto_correspondiente(productos_factura, producto_form)
        if not prod_factura:
            discrepancias.append(Discrepancia(campo=f"Producto {idx + 1}", valor_factura="No encontrado", valor_formulario=producto_form.get('nombre', 'N/A'), severidad="ALTA", mensaje="Producto no encontrado en factura"))
            return discrepancias
        if 'nombre' in producto_form:
            disc = self._comparar_campo_proveedor(prod_factura.nombre, producto_form['nombre'])
            if disc: disc.campo = f"Producto {idx + 1} - Nombre"; discrepancias.append(disc)
        if 'modelo' in producto_form and prod_factura.modelo:
            disc = self._comparar_campo(f"Producto {idx + 1} - Modelo", prod_factura.modelo, producto_form['modelo'], "MEDIA")
            if disc: discrepancias.append(disc)
        if 'marca' in producto_form and prod_factura.marca:
            disc = self._comparar_campo(f"Producto {idx + 1} - Marca", prod_factura.marca, producto_form['marca'], "MEDIA")
            if disc: discrepancias.append(disc)
        if 'serial' in producto_form and prod_factura.serial:
            disc = self._comparar_campo(f"Producto {idx + 1} - Serial", prod_factura.serial, producto_form['serial'], "ALTA")
            if disc: discrepancias.append(disc)
        if 'cantidad' in producto_form:
            cantidad_form = int(producto_form['cantidad'])
            if prod_factura.cantidad != cantidad_form:
                discrepancias.append(Discrepancia(campo=f"Producto {idx + 1} - Cantidad", valor_factura=str(prod_factura.cantidad), valor_formulario=str(cantidad_form), severidad="CRITICA", mensaje=f"Cantidad diferente: {prod_factura.cantidad} vs {cantidad_form}"))
        if 'costo' in producto_form:
            costo_form = float(producto_form['costo'])
            tolerancia = self.config['tolerancia_costo']
            diferencia = abs(prod_factura.costo_unitario - costo_form) / max(costo_form, 0.01)
            if diferencia > tolerancia:
                discrepancias.append(Discrepancia(campo=f"Producto {idx + 1} - Costo", valor_factura=f"{prod_factura.costo_unitario:.2f}", valor_formulario=f"{costo_form:.2f}", severidad="ALTA", mensaje=f"Costo diferente en {diferencia:.1%}"))
        return discrepancias

    def _encontrar_producto_correspondiente(self, productos: List[ProductoExtraido], prod_form: Dict) -> Optional[ProductoExtraido]:
        nombre_form = self._normalizar_texto(prod_form.get('nombre', ''))
        modelo_form = self._normalizar_texto(prod_form.get('modelo', ''))
        
        for prod in productos:
            nombre_factura = self._normalizar_texto(prod.nombre)
            modelo_factura = self._normalizar_texto(prod.modelo or '')
            
            # Estrategia 1: Comparación directa de nombres
            similitud = self._calcular_similitud(nombre_factura, nombre_form)
            if similitud >= 0.70: 
                logger.info(f"Producto encontrado por nombre directo: {nombre_factura} vs {nombre_form} (similitud: {similitud:.2%})")
                return prod
            
            # Estrategia 2: Si el nombre de la factura incluye el modelo, intentar separar y comparar
            if modelo_factura and modelo_factura in nombre_factura:
                nombre_sin_modelo = nombre_factura.replace(modelo_factura, '').strip()
                similitud_sin_modelo = self._calcular_similitud(nombre_sin_modelo, nombre_form)
                if similitud_sin_modelo >= 0.70:
                    logger.info(f"Producto encontrado ignorando modelo: {nombre_sin_modelo} vs {nombre_form} (similitud: {similitud_sin_modelo:.2%})")
                    return prod
            
            # Estrategia 3: Comparar combinando nombre+modelo del formulario
            if modelo_form:
                nombre_completo_form = f"{nombre_form} {modelo_form}"
                similitud_completa = self._calcular_similitud(nombre_factura, nombre_completo_form)
                if similitud_completa >= 0.70:
                    logger.info(f"Producto encontrado por nombre+modelo: {nombre_factura} vs {nombre_completo_form} (similitud: {similitud_completa:.2%})")
                    return prod
        
        logger.warning(f"No se encontró producto correspondiente: {nombre_form} (modelo: {modelo_form})")
        return None

    def _generar_reporte(self, discrepancias: List[Discrepancia], factura: FacturaExtraida, datos_form: Dict) -> str:
        reporte = ["=" * 60, "REPORTE DE VERIFICACIÓN - FASE 1: EL GUARDIÁN", "=" * 60, f"Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", ""]
        if discrepancias:
            reporte.extend([f"⚠️  RESULTADO: BLOQUEADO - {len(discrepancias)} DISCREPANCIAS ENCONTRADAS", "", "DETALLE DE DISCREPANCIAS:", "-" * 40])
            for i, disc in enumerate(discrepancias, 1):
                icono = {"CRITICA": "🔴", "ALTA": "🟠", "MEDIA": "🟡", "BAJA": "🟢"}.get(disc.severidad, "⚪")
                reporte.extend([f"{i}. {icono} [{disc.severidad}] {disc.campo}", f"   Factura:     {disc.valor_factura}", f"   Formulario:  {disc.valor_formulario}", f"   → {disc.mensaje}", ""])
            reporte.extend(["-" * 40, "ACCIÓN REQUERIDA:", "• Corrija los datos del formulario", "• Vuelva a intentar el registro", "=" * 60])
        else:
            reporte.extend(["✅ RESULTADO: APROBADO - Sin discrepancias", "• El registro puede proceder", "=" * 60])
        return "\n".join(reporte)

    def _calcular_confianza_promedio(self, factura: FacturaExtraida) -> float:
        if not factura.productos: return 0.5
        confianzas = [p.confianza for p in factura.productos]
        confianza_productos = sum(confianzas) / len(confianzas)
        campos_principales = sum([bool(factura.numero_factura), bool(factura.nombre_proveedor), bool(factura.fecha_factura)]) / 3.0
        return (confianza_productos * 0.6) + (campos_principales * 0.4)

    def _limpiar_texto(self, texto: str) -> str:
        lineas = [linea.strip() for linea in texto.split('\n') if linea.strip()]
        return '\n'.join(lineas)

    def _normalizar_texto(self, texto: str) -> str:
        if not texto: return ""
        texto = texto.upper().strip()
        for char in '.,;:!?': texto = texto.replace(char, '')
        return ' '.join(texto.split())

    def _calcular_similitud(self, s1: str, s2: str) -> float:
        if not s1 or not s2: return 0.0
        if s1 == s2: return 1.0
        len1, len2 = len(s1), len(s2)
        max_len = max(len1, len2)
        if max_len == 0: return 1.0
        distancia = self._levenshtein_distance(s1, s2)
        return 1.0 - (distancia / max_len)

    def _levenshtein_distance(self, s1: str, s2: str) -> int:
        if len(s1) < len(s2): return self._levenshtein_distance(s2, s1)
        if len(s2) == 0: return len(s1)
        previous_row = range(len(s2) + 1)
        for i, c1 in enumerate(s1):
            current_row = [i + 1]
            for j, c2 in enumerate(s2):
                insertions = previous_row[j + 1] + 1
                deletions = current_row[j] + 1
                substitutions = previous_row[j] + (c1 != c2)
                current_row.append(min(insertions, deletions, substitutions))
            previous_row = current_row
        return previous_row[-1]

    def _parsear_monto(self, texto: str) -> float:
        if not texto: return 0.0
        # Eliminar caracteres no numéricos excepto punto, coma, signos
        numero = re.sub(r'[^\d,.+-]', '', str(texto)).strip()
        # Eliminar comas/puntos al final del string (común en OCR)
        numero = numero.rstrip('.,')
        if not numero:
            return 0.0

        if ',' in numero and '.' in numero:
            separador_decimal = ',' if numero.rfind(',') > numero.rfind('.') else '.'
            separador_miles = '.' if separador_decimal == ',' else ','
            numero = numero.replace(separador_miles, '')
            numero = numero.replace(separador_decimal, '.')
        elif ',' in numero:
            decimales = len(numero.rsplit(',', 1)[-1])
            numero = numero.replace(',', '.' if decimales in (1, 2) else '')
        elif numero.count('.') > 1:
            ultimo_punto = numero.rfind('.')
            numero = numero[:ultimo_punto].replace('.', '') + numero[ultimo_punto:]

        try:
            return float(numero)
        except ValueError:
            return 0.0

    def obtener_estadisticas(self) -> Dict:
        if not self.historial_verificaciones: return {"total_verificaciones": 0, "tasa_exito": 0.0}
        total = len(self.historial_verificaciones)
        exitosos = sum(1 for r in self.historial_verificaciones if r.exito)
        return {"total_verificaciones": total, "tasa_exito": exitosos / total, "verificaciones_exitosas": exitosos, "verificaciones_fallidas": total - exitosos}

    def limpiar_cache(self, max_edad_horas: int = 24) -> int:
        ahora = datetime.now()
        eliminados = 0
        for id_cache in list(self.cache_facturas.keys()):
            factura = self.cache_facturas[id_cache]
            timestamp_str = factura.metadatos_extraccion.get('timestamp', '')
            if timestamp_str:
                try:
                    timestamp = datetime.fromisoformat(timestamp_str)
                    edad_horas = (ahora - timestamp).total_seconds() / 3600
                    if edad_horas > max_edad_horas:
                        del self.cache_facturas[id_cache]
                        eliminados += 1
                except: pass
        return eliminados

    def _convertir_pdf_a_imagen(self, ruta_pdf: str) -> List[Image.Image]:
        """Convierte las primeras 5 páginas del PDF para procesamiento OCR optimizado."""
        if not PDF_AVAILABLE:
            raise ValueError("Procesamiento de PDF no disponible. Instale pdf2image: pip install pdf2image")
        
        try:
            # Configurar ruta de Poppler para Windows
            import platform
            # Optimización: Reducir DPI de 200 a 150 (44% menos tamaño de imagen)
            # Optimización: Limitar a primeras 5 páginas (info importante al inicio)
            max_paginas = 5
            dpi = 150
            
            if platform.system() == 'Windows':
                poppler_path = r"C:\xampp\htdocs\proyecto-casalai-ca\microservicio\Poppler\poppler-26.09.0\Library\bin"
                if Path(poppler_path).exists():
                    imagenes = convert_from_path(ruta_pdf, dpi=dpi, fmt='jpeg', poppler_path=poppler_path, first_page=1, last_page=max_paginas)
                else:
                    imagenes = convert_from_path(ruta_pdf, dpi=dpi, fmt='jpeg', first_page=1, last_page=max_paginas)
            else:
                imagenes = convert_from_path(ruta_pdf, dpi=dpi, fmt='jpeg', first_page=1, last_page=max_paginas)
            
            if not imagenes:
                raise ValueError("No se pudo extraer ninguna imagen del PDF")

            logger.info(f"PDF convertido a {len(imagenes)} páginas (max {max_paginas}) con DPI {dpi} para OCR")
            return imagenes
            
        except Exception as e:
            logger.error(f"Error convirtiendo PDF {ruta_pdf}: {e}")
            raise ValueError(f"No se pudo procesar el PDF: {str(e)}")

    def exportar_historial(self, ruta: str) -> bool:
        try:
            datos = [{"exito": r.exito, "timestamp": r.timestamp, "hash": r.hash_verificacion, "num_discrepancias": len(r.discrepancias), "datos_formulario": r.datos_formulario} for r in self.historial_verificaciones]
            with open(ruta, 'w', encoding='utf-8') as f:
                json.dump(datos, f, indent=2, default=str)
            return True
        except Exception as e:
            logger.error(f"Error exportando historial: {e}")
            return False
