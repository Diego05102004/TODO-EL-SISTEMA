"""Sistema de adaptadores de base de datos para el microservicio."""

from abc import ABC, abstractmethod
from typing import List, Dict, Optional
from dataclasses import dataclass
import logging

logger = logging.getLogger(__name__)


@dataclass
class Producto:
    """Representación de un producto del catálogo."""
    id: str
    nombre: str
    modelo: str
    marca: str
    costo_unitario: float


@dataclass
class Proveedor:
    """Representación de un proveedor del sistema."""
    id: str
    nombre: str


class AdaptadorBaseDatos(ABC):
    """Clase base abstracta para adaptadores de base de datos."""
    
    @abstractmethod
    def obtener_productos(self) -> List[Producto]:
        """Obtiene todos los productos del catálogo."""
        pass
    
    @abstractmethod
    def obtener_proveedores(self) -> List[Proveedor]:
        """Obtiene todos los proveedores del sistema."""
        pass
    
    @abstractmethod
    def buscar_producto_por_nombre(self, nombre: str) -> Optional[Producto]:
        """Busca un producto por nombre (búsqueda aproximada)."""
        pass
    
    @abstractmethod
    def buscar_proveedor_por_nombre(self, nombre: str) -> Optional[Proveedor]:
        """Busca un proveedor por nombre (búsqueda aproximada)."""
        pass
    
    @abstractmethod
    def cerrar_conexion(self):
        """Cierra la conexión a la base de datos."""
        pass


class AdaptadorMySQL(AdaptadorBaseDatos):
    """Adaptador para bases de datos MySQL."""
    
    def __init__(self, host: str, port: int, user: str, password: str, database: str):
        self.host = host
        self.port = port
        self.user = user
        self.password = password
        self.database = database
        self.connection = None
        self._conectar()
    
    def _conectar(self):
        """Establece la conexión a MySQL."""
        try:
            import mysql.connector
            self.connection = mysql.connector.connect(
                host=self.host,
                port=self.port,
                user=self.user,
                password=self.password,
                database=self.database
            )
            logger.info(f"Conectado a MySQL: {self.host}:{self.port}/{self.database}")
        except Exception as e:
            logger.error(f"Error conectando a MySQL: {e}")
            raise
    
    def obtener_productos(self) -> List[Producto]:
        """Obtiene todos los productos del catálogo."""
        try:
            cursor = self.connection.cursor(dictionary=True)
            query = """
                SELECT p.id_producto, p.nombre_producto, p.descripcion_producto, 
                       p.id_modelo, p.id_categoria, p.precio, p.estado,
                       m.nombre_modelo, ma.nombre_marca
                FROM tbl_productos p
                LEFT JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
                LEFT JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                WHERE p.estado = 'habilitado'
            """
            cursor.execute(query)
            productos = []
            for row in cursor.fetchall():
                producto = Producto(
                    id=str(row['id_producto']),
                    nombre=row['nombre_producto'],
                    modelo=row.get('nombre_modelo', ''),
                    marca=row.get('nombre_marca', ''),
                    costo_unitario=float(row.get('precio', 0))
                )
                productos.append(producto)
                # Log individual para depuración
                logger.info(f"Producto en MySQL: '{producto.nombre}' (modelo: '{producto.modelo}', marca: '{producto.marca}')")
            cursor.close()
            logger.info(f"Obtenidos {len(productos)} productos desde MySQL")
            return productos
        except Exception as e:
            logger.error(f"Error obteniendo productos: {e}")
            return []
    
    def obtener_proveedores(self) -> List[Proveedor]:
        """Obtiene proveedores desde la tabla tbl_proveedores del sistema."""
        try:
            cursor = self.connection.cursor(dictionary=True)
            # Solo se requiere el nombre del proveedor
            query = """
                SELECT id_proveedor, nombre_proveedor, estado
                FROM tbl_proveedores 
                WHERE estado = 'habilitado'
            """
            cursor.execute(query)
            proveedores = []
            for row in cursor.fetchall():
                proveedores.append(Proveedor(
                    id=str(row['id_proveedor']),
                    nombre=row['nombre_proveedor']
                ))
            cursor.close()
            logger.info(f"Obtenidos {len(proveedores)} proveedores desde MySQL")
            return proveedores
        except Exception as e:
            logger.error(f"Error obteniendo proveedores: {e}")
            return []
    
    def buscar_producto_por_nombre(self, nombre: str) -> Optional[Producto]:
        """Busca un producto por nombre con búsqueda aproximada."""
        try:
            cursor = self.connection.cursor(dictionary=True)
            query = """
                SELECT p.id_producto, p.nombre_producto, p.descripcion_producto, 
                       p.id_modelo, p.id_categoria, p.precio, p.estado,
                       m.nombre_modelo, ma.nombre_marca
                FROM tbl_productos p
                LEFT JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
                LEFT JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                WHERE p.nombre_producto LIKE %s 
                AND p.estado = 'habilitado'
                LIMIT 1
            """
            cursor.execute(query, (f"%{nombre}%",))
            row = cursor.fetchone()
            cursor.close()
            if row:
                logger.info(f"Producto encontrado en MySQL: '{row['nombre_producto']}' (modelo: '{row.get('nombre_modelo', '')}', marca: '{row.get('nombre_marca', '')}')")
                return Producto(
                    id=str(row['id_producto']),
                    nombre=row['nombre_producto'],
                    modelo=row.get('nombre_modelo', ''),
                    marca=row.get('nombre_marca', ''),
                    costo_unitario=float(row.get('precio', 0))
                )
            logger.info(f"Producto NO encontrado en MySQL con LIKE '%{nombre}%'")
            return None
        except Exception as e:
            logger.error(f"Error buscando producto: {e}")
            return None
    
    def buscar_proveedor_por_nombre(self, nombre: str) -> Optional[Proveedor]:
        """Busca un proveedor por nombre con búsqueda aproximada."""
        try:
            cursor = self.connection.cursor(dictionary=True)
            query = """
                SELECT id_proveedor, nombre_proveedor, estado
                FROM tbl_proveedores 
                WHERE nombre_proveedor LIKE %s 
                AND estado = 'habilitado'
                LIMIT 1
            """
            cursor.execute(query, (f"%{nombre}%",))
            row = cursor.fetchone()
            cursor.close()
            if row:
                return Proveedor(
                    id=str(row['id_proveedor']),
                    nombre=row['nombre_proveedor']
                )
            return None
        except Exception as e:
            logger.error(f"Error buscando proveedor: {e}")
            return None
    
    def cerrar_conexion(self):
        """Cierra la conexión a MySQL."""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            logger.info("Conexión MySQL cerrada")


class AdaptadorCSV(AdaptadorBaseDatos):
    """Adaptador para archivos CSV (fallback para otros sistemas)."""
    
    def __init__(self, ruta_productos: str, ruta_proveedores: Optional[str] = None):
        self.ruta_productos = ruta_productos
        self.ruta_proveedores = ruta_proveedores
        self._productos: List[Producto] = []
        self._proveedores: List[Proveedor] = []
        self._cargar_datos()
    
    def _cargar_datos(self):
        """Carga datos desde archivos CSV."""
        import csv
        from pathlib import Path
        
        # Cargar productos
        if Path(self.ruta_productos).exists():
            try:
                with open(self.ruta_productos, 'r', encoding='utf-8') as f:
                    reader = csv.DictReader(f)
                    for row in reader:
                        self._productos.append(Producto(
                            id=row.get('id', ''),
                            nombre=row.get('nombre', ''),
                            modelo=row.get('modelo', ''),
                            marca=row.get('marca', ''),
                            costo_unitario=float(row.get('costo_unitario', 0))
                        ))
                logger.info(f"Cargados {len(self._productos)} productos desde CSV")
            except Exception as e:
                logger.error(f"Error cargando productos CSV: {e}")
        
        # Cargar proveedores si existe el archivo
        if self.ruta_proveedores and Path(self.ruta_proveedores).exists():
            try:
                with open(self.ruta_proveedores, 'r', encoding='utf-8') as f:
                    reader = csv.DictReader(f)
                    for row in reader:
                        self._proveedores.append(Proveedor(
                            id=row.get('id', ''),
                            nombre=row.get('nombre', '')
                        ))
                logger.info(f"Cargados {len(self._proveedores)} proveedores desde CSV")
            except Exception as e:
                logger.error(f"Error cargando proveedores CSV: {e}")
    
    def obtener_productos(self) -> List[Producto]:
        return self._productos
    
    def obtener_proveedores(self) -> List[Proveedor]:
        return self._proveedores
    
    def buscar_producto_por_nombre(self, nombre: str) -> Optional[Producto]:
        nombre_lower = nombre.lower()
        for producto in self._productos:
            if nombre_lower in producto.nombre.lower():
                return producto
        return None
    
    def buscar_proveedor_por_nombre(self, nombre: str) -> Optional[Proveedor]:
        nombre_lower = nombre.lower()
        for proveedor in self._proveedores:
            if nombre_lower in proveedor.nombre.lower():
                return proveedor
        return None
    
    def cerrar_conexion(self):
        """No hay conexión que cerrar para CSV."""
        pass


class FabricaAdaptadores:
    """Fábrica para crear adaptadores según configuración."""
    
    @staticmethod
    def crear_adaptador(tipo: str, config: Dict) -> Optional[AdaptadorBaseDatos]:
        """
        Crea un adaptador según el tipo y configuración.
        
        Tipos soportados:
        - 'mysql': Conexión a base de datos MySQL
        - 'csv': Archivos CSV (fallback)
        """
        if tipo == 'mysql':
            return AdaptadorMySQL(
                host=config.get('host', 'localhost'),
                port=config.get('port', 3306),
                user=config.get('user', ''),
                password=config.get('password', ''),
                database=config.get('database', '')
            )
        elif tipo == 'csv':
            return AdaptadorCSV(
                ruta_productos=config.get('ruta_productos', ''),
                ruta_proveedores=config.get('ruta_proveedores')
            )
        else:
            logger.error(f"Tipo de adaptador no soportado: {tipo}")
            return None
