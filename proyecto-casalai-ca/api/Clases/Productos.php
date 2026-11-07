<?php
namespace Usuario\ProyectoCasalaiCa;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
class Productos extends BD{

    private $id_centro;
    private $nombre_producto;
    private $descripcion_p;
    private $id_modelo;
    private $stock_actual;
    private $stock_max;
    private $stock_min;
    private $peso;
    private $largo;
    private $alto;
    private $ancho;
    private $clausula_garantia;
    private $serial;
    private $estado;
    private $lleva_lote;
    private $lleva_serial;
    private $categoria;
    private $numero;
    private $color;
    private $tipo;
    private $volumen;
    private $capacidad;
    private $descripcion_otros;
    private $voltaje_entrada;
    private $voltaje_salida;
    private $tomas;
    private $imagen;
    private $id;
    private $precio;

    // Constantes de validación
    const MAX_ID_PRODUCTO = 999999999;
    const MIN_ID_PRODUCTO = 1;
    const MAX_ID_MODELO = 999999999;
    const MIN_ID_MODELO = 1;
    const MAX_NOMBRE_PRODUCTO = 200;
    const MIN_NOMBRE_PRODUCTO = 2;
    const MAX_DESCRIPCION = 1000;
    const MAX_CLAUSULA_GARANTIA = 1000;
    const MAX_SERIAL = 100;
    const MAX_CATEGORIA = 100;
    const MAX_STOCK = 999999;
    const MIN_STOCK = 0;
    const MAX_PRECIO = 999999.99;
    const MIN_PRECIO = 0.01;
    const MAX_DIMENSION = 999999.99;
    const MIN_DIMENSION = 0;
    const ESTADOS_VALIDOS = ['habilitado', 'inhabilitado'];
    const ESTADOS_VALIDOS_CAMBIO = ['habilitado', 'inhabilitado'];
    const EXTENSIONES_IMAGEN = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const MAX_TAMANO_IMAGEN = 5 * 1024 * 1024; // 5MB

    public function getNombreP() {
        return $this->nombre_producto;
    }
        public function setImagen($imagen) {
        $this->imagen = $imagen;
    }

    public function setNombreP($nombre_producto) {
        $this->nombre_producto = $nombre_producto;
    }

    public function getDescripcionP() {
        return $this->descripcion_p;
    }

    public function setDescripcionP($descripcion_p) {
        $this->descripcion_p = $descripcion_p;
    }

    public function getIdModelo() {
        return $this->id_modelo;
    }

    public function setIdModelo($id_modelo) {
        $this->id_modelo = $id_modelo;
    }

    public function getStockActual() {
        return $this->stock_actual;
    }

    public function setStockActual($stock_actual) {
        $this->stock_actual = $stock_actual;
    }

    public function getStockMax() {
        return $this->stock_max;
    }

    public function setStockMax($stock_max) {
        $this->stock_max = $stock_max;
    }

    public function getStockMin() {
        return $this->stock_min;
    }

    public function setStockMin($stock_min) {
        $this->stock_min = $stock_min;
    }

    public function getPeso() {
        return $this->peso;
    }

    public function getClausulaDeGarantia() {
        return $this->clausula_garantia;
    }

    public function setClausulaDeGarantia($clausula_garantia) {
        $this->clausula_garantia = $clausula_garantia;
    }

    public function getCodigo() {
        return $this->serial;
    }

    public function setCodigo($serial) {
        $this->serial = $serial;
    }

    public function getLlevaLote() {
        return $this->lleva_lote;
    }

    public function setLlevaLote($lleva_lote) {
        $this->lleva_lote = $lleva_lote;
    }

    public function getLlevaSerial() {
        return $this->lleva_serial;
    }

    public function setLlevaSerial($lleva_serial) {
        $this->lleva_serial = $lleva_serial;
    }

    public function getCategoria() {
        return $this->categoria;
    }

    public function setCategoria($categoria) {
        $this->categoria = $categoria;
    }
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setPrecio($precio) {
        $this->precio = $precio;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function setPeso($peso) { $this->peso = $peso; }
    public function setAlto($alto) { $this->alto = $alto; }
    public function getAlto() { return $this->alto; }

    public function setAncho($ancho) { $this->ancho = $ancho; }
    public function getAncho() { return $this->ancho; }

    public function setLargo($largo) { $this->largo = $largo; }
    public function getLargo() { return $this->largo; }

    public function setNumero($numero) { $this->numero = $numero; }
    public function getNumero() { return $this->numero; }

    public function setColor($color) { $this->color = $color; }
    public function getColor() { return $this->color; }

    public function setTipo($tipo) { $this->tipo = $tipo; }
    public function getTipo() { return $this->tipo; }

    public function setVolumen($volumen) { $this->volumen = $volumen; }
    public function getVolumen() { return $this->volumen; }

    public function setCapacidad($capacidad) { $this->capacidad = $capacidad; }
    public function getCapacidad() { return $this->capacidad; }

    public function setDescripcionOtros($descripcion) { $this->descripcion_otros = $descripcion; }
    public function getDescripcionOtros() { return $this->descripcion_otros; }

    public function setVoltajeEntrada($voltaje_entrada) { $this->voltaje_entrada = $voltaje_entrada; }
    public function getVoltajeEntrada() { return $this->voltaje_entrada; }

    public function setVoltajeSalida($voltaje_salida) { $this->voltaje_salida = $voltaje_salida; }
    public function getVoltajeSalida() { return $this->voltaje_salida; }

    public function setTomas($tomas) { $this->tomas = $tomas; }
    public function getTomas() { return $this->tomas; }


    public function __construct($tipo = 'P') {
    }
    
    /**
     * @return PDO
     */
    public function getConexion() {
        return $this->pdo;
    }
    
    /**
     * @param callable
     * @return mixed
     */

    protected function ejecutarConConexionSegura($operation) {
    try {
        parent::__construct('P'); 
        $pdo = parent::getConexion(); 

        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException("La conexión PDO no es válida o es nula.");
        }

        // ELIMINADO: $pdo->beginTransaction(); ya que el procedimiento maneja su propia transacción
        
        $resultado = $operation($pdo);
        
        // ELIMINADO: $pdo->commit();
        
        return $resultado;
    } catch (\Exception $e) {
        $pdo = parent::getConexion();
        // El rollback se ejecutará aquí solo si por alguna razón una consulta externa dejó una transacción abierta
        if ($pdo instanceof \PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new \RuntimeException($e->getMessage());
    } finally {
        $this->cerrar();
    }
}


    private function validarProducto($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['producto'] = 'Los datos del producto deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID del producto
        if (isset($datos['id_producto'])) {
            $id_producto = (int)$datos['id_producto'];
            if ($id_producto < self::MIN_ID_PRODUCTO || $id_producto > self::MAX_ID_PRODUCTO) {
                $errores['id_producto'] = 'El ID del producto debe ser un número entre ' . self::MIN_ID_PRODUCTO . ' y ' . self::MAX_ID_PRODUCTO;
            }
        }
        
        // Validar ID del modelo
        if (isset($datos['id_modelo'])) {
            $id_modelo = (int)$datos['id_modelo'];
            if ($id_modelo < self::MIN_ID_MODELO || $id_modelo > self::MAX_ID_MODELO) {
                $errores['id_modelo'] = 'El ID del modelo debe ser un número entre ' . self::MIN_ID_MODELO . ' y ' . self::MAX_ID_MODELO;
            }
        }
        
        // Validar nombre del producto
        if (isset($datos['nombre_producto'])) {
            $nombre_producto = trim((string)$datos['nombre_producto']);
            if ($nombre_producto === '') {
                $errores['nombre_producto'] = 'El nombre del producto es obligatorio';
            } elseif (mb_strlen($nombre_producto) < self::MIN_NOMBRE_PRODUCTO) {
                $errores['nombre_producto'] = 'El nombre del producto debe tener al menos ' . self::MIN_NOMBRE_PRODUCTO . ' caracteres';
            } elseif (mb_strlen($nombre_producto) > self::MAX_NOMBRE_PRODUCTO) {
                $errores['nombre_producto'] = 'El nombre del producto no debe exceder los ' . self::MAX_NOMBRE_PRODUCTO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_producto)) {
                $errores['nombre_producto'] = 'El nombre del producto solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar descripción
        if (isset($datos['descripcion_producto'])) {
            $descripcion = trim((string)$datos['descripcion_producto']);
            if ($descripcion !== '' && mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
                $errores['descripcion_producto'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
        }
        
        // Validar stock actual
        if (isset($datos['stock_actual'])) {
            $stock_actual = (int)$datos['stock_actual'];
            if ($stock_actual < self::MIN_STOCK || $stock_actual > self::MAX_STOCK) {
                $errores['stock_actual'] = 'El stock actual debe estar entre ' . self::MIN_STOCK . ' y ' . self::MAX_STOCK;
            }
        }
        
        // Validar stock máximo
        if (isset($datos['stock_maximo'])) {
            $stock_max = (int)$datos['stock_maximo'];
            if ($stock_max < self::MIN_STOCK || $stock_max > self::MAX_STOCK) {
                $errores['stock_maximo'] = 'El stock máximo debe estar entre ' . self::MIN_STOCK . ' y ' . self::MAX_STOCK;
            }
        }
        
        // Validar stock mínimo
        if (isset($datos['stock_minimo'])) {
            $stock_min = (int)$datos['stock_minimo'];
            if ($stock_min < self::MIN_STOCK || $stock_min > self::MAX_STOCK) {
                $errores['stock_minimo'] = 'El stock mínimo debe estar entre ' . self::MIN_STOCK . ' y ' . self::MAX_STOCK;
            }
        }
        
        // Validar relación entre stocks
        if (isset($datos['stock_actual']) && isset($datos['stock_maximo']) && isset($datos['stock_minimo'])) {
            $stock_actual = (int)$datos['stock_actual'];
            $stock_max = (int)$datos['stock_maximo'];
            $stock_min = (int)$datos['stock_minimo'];
            
            if ($stock_min > $stock_max) {
                $errores['stock_minimo'] = 'El stock mínimo no puede ser mayor al stock máximo';
            }
            if ($stock_actual > $stock_max) {
                $errores['stock_actual'] = 'El stock actual no puede ser mayor al stock máximo';
            }
            if ($stock_actual < $stock_min) {
                $errores['stock_actual'] = 'El stock actual no puede ser menor al stock mínimo';
            }
        }
        
        // Validar cláusula de garantía
        if (isset($datos['clausula_garantia'])) {
            $clausula = trim((string)$datos['clausula_garantia']);
            if ($clausula !== '' && mb_strlen($clausula) > self::MAX_CLAUSULA_GARANTIA) {
                $errores['clausula_garantia'] = 'La cláusula de garantía no debe exceder los ' . self::MAX_CLAUSULA_GARANTIA . ' caracteres';
            }
        }
        
        // Validar serial
        if (isset($datos['serial'])) {
            $serial = trim((string)$datos['serial']);
            if ($serial !== '' && mb_strlen($serial) > self::MAX_SERIAL) {
                $errores['serial'] = 'El serial no debe exceder los ' . self::MAX_SERIAL . ' caracteres';
            }
        }
        
        // Validar categoría
        if (isset($datos['categoria'])) {
            $categoria = trim((string)$datos['categoria']);
            if ($categoria !== '' && mb_strlen($categoria) > self::MAX_CATEGORIA) {
                $errores['categoria'] = 'La categoría no debe exceder los ' . self::MAX_CATEGORIA . ' caracteres';
            }
        }
        
        // Validar precio
        if (isset($datos['precio'])) {
            $precio = (float)$datos['precio'];
            if ($precio < self::MIN_PRECIO || $precio > self::MAX_PRECIO) {
                $errores['precio'] = 'El precio debe estar entre ' . self::MIN_PRECIO . ' y ' . self::MAX_PRECIO;
            }
        }
        
        // Validar dimensiones (peso, largo, alto, ancho)
        $dimensiones = ['peso', 'largo', 'alto', 'ancho'];
        foreach ($dimensiones as $dimension) {
            if (isset($datos[$dimension])) {
                $valor = (float)$datos[$dimension];
                if ($valor < self::MIN_DIMENSION || $valor > self::MAX_DIMENSION) {
                    $errores[$dimension] = 'El ' . $dimension . ' debe estar entre ' . self::MIN_DIMENSION . ' y ' . self::MAX_DIMENSION;
                }
            }
        }
        
        // Validar estatus
        if (isset($datos['estado'])) {
            $estado = trim((string)$datos['estado']);
            if (!in_array($estado, self::ESTADOS_VALIDOS)) {
                $errores['estado'] = 'El estado no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    public function validarImagen($archivo, $requerida = true) {
        $errores = [];
        
        // Si no es requerida y no se envió archivo, no hay error
        if (!$requerida && (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE)) {
            return $errores;
        }
        
        if (!is_array($archivo)) {
            $errores['imagen'] = 'Los datos de la imagen deben ser un arreglo';
            return $errores;
        }
        
        if (!isset($archivo['name']) || !isset($archivo['tmp_name']) || !isset($archivo['error'])) {
            $errores['imagen'] = 'Estructura de la imagen inválida';
            return $errores;
        }
        
        // Validar que no haya errores de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores['imagen'] = 'Error al subir la imagen: ' . $this->getUploadErrorMessage($archivo['error']);
            return $errores;
        }
        
        // Validar tamaño máximo
        if ($archivo['size'] > self::MAX_TAMANO_IMAGEN) {
            $errores['imagen'] = 'La imagen no debe exceder los 5MB';
            return $errores;
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONES_IMAGEN)) {
            $errores['imagen'] = 'La extensión de la imagen no es permitida. Extensiones permitidas: ' . implode(', ', self::EXTENSIONES_IMAGEN);
        }
        
        return $errores;
    }
    
    private function getUploadErrorMessage($errorCode) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede escribir el archivo',
            UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión',
            UPLOAD_ERR_NO_TMP_FILE => 'No hay archivo temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede mover el archivo'
        ];
        
        return $messages[$errorCode] ?? 'Error desconocido';
    }
    
    // Métodos públicos de validación
    public function validarConsultarProducto($datos) {
        $errores = [];
        
        // Para consultar, podemos validar por ID o por nombre
        if (isset($datos['id_producto'])) {
            $id_producto = (int)$datos['id_producto'];
            if ($id_producto < self::MIN_ID_PRODUCTO || $id_producto > self::MAX_ID_PRODUCTO) {
                $errores['id_producto'] = 'El ID del producto debe ser un número entre ' . self::MIN_ID_PRODUCTO . ' y ' . self::MAX_ID_PRODUCTO;
            }
        }
        
        if (isset($datos['nombre_producto'])) {
            $nombre_producto = trim((string)$datos['nombre_producto']);
            if ($nombre_producto !== '' && mb_strlen($nombre_producto) > self::MAX_NOMBRE_PRODUCTO) {
                $errores['nombre_producto'] = 'El nombre del producto no debe exceder los ' . self::MAX_NOMBRE_PRODUCTO . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    public function validarDetallarProducto($datos) {
        $errores = [];
        
        // Para detallar, el ID es obligatorio
        if (!isset($datos['id_producto'])) {
            $errores['id_producto'] = 'El ID del producto es obligatorio';
        } else {
            $id_producto = (int)$datos['id_producto'];
            if ($id_producto < self::MIN_ID_PRODUCTO || $id_producto > self::MAX_ID_PRODUCTO) {
                $errores['id_producto'] = 'El ID del producto debe ser un número entre ' . self::MIN_ID_PRODUCTO . ' y ' . self::MAX_ID_PRODUCTO;
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarProducto($datos) {
        $errores = [];
        
        // Para registrar, requerimos campos obligatorios
        if (!isset($datos['nombre_producto'])) {
            $errores['nombre_producto'] = 'El nombre del producto es obligatorio';
        }
        
        if (!isset($datos['id_modelo'])) {
            $errores['id_modelo'] = 'El modelo es obligatorio';
        }
        
        if (!isset($datos['precio'])) {
            $errores['precio'] = 'El precio es obligatorio';
        }
        
        // Validar el producto completo
        $errores_producto = $this->validarProducto($datos);
        if (!empty($errores_producto)) {
            $errores = array_merge($errores, $errores_producto);
        }
        
        return $errores;
    }
    
    public function validarModificarProducto($datos) {
        $errores = [];
        
        // Para modificar, el ID es obligatorio
        if (!isset($datos['id_producto'])) {
            $errores['id_producto'] = 'El ID del producto es obligatorio';
        }
        
        // Validar el producto completo
        $errores_producto = $this->validarProducto($datos);
        if (!empty($errores_producto)) {
            $errores = array_merge($errores, $errores_producto);
        }
        
        return $errores;
    }
    
    public function validarEliminarProducto($datos) {
        $errores = [];
        
        // Para eliminar, el ID es obligatorio
        if (!isset($datos['id_producto'])) {
            $errores['id_producto'] = 'El ID del producto es obligatorio';
        } else {
            $id_producto = (int)$datos['id_producto'];
            if ($id_producto < self::MIN_ID_PRODUCTO || $id_producto > self::MAX_ID_PRODUCTO) {
                $errores['id_producto'] = 'El ID del producto debe ser un número entre ' . self::MIN_ID_PRODUCTO . ' y ' . self::MAX_ID_PRODUCTO;
            }
        }
        
        return $errores;
    }
    
    public function validarCambiarEstatus($datos) {
        $errores = [];
        
        // Validar ID del producto (obligatorio)
        if (!isset($datos['id_producto'])) {
            $errores['id_producto'] = 'El ID del producto es obligatorio';
        } else {
            $id_producto = (int)$datos['id_producto'];
            if ($id_producto < self::MIN_ID_PRODUCTO || $id_producto > self::MAX_ID_PRODUCTO) {
                $errores['id_producto'] = 'El ID del producto debe ser un número entre ' . self::MIN_ID_PRODUCTO . ' y ' . self::MAX_ID_PRODUCTO;
            }
        }
        
        // Validar nuevo estatus (obligatorio)
        if (!isset($datos['nuevo_estatus'])) {
            $errores['nuevo_estatus'] = 'El nuevo estatus es obligatorio';
        } else {
            $nuevo_estatus = trim((string)$datos['nuevo_estatus']);
            if (!in_array($nuevo_estatus, self::ESTADOS_VALIDOS_CAMBIO)) {
                $errores['nuevo_estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS_CAMBIO);
            }
        }
        
        return $errores;
    }
    
    public function validarReporte($datos) {
        $errores = [];
        
        // Para reporte, validar tipo de reporte
        if (isset($datos['tipo_reporte'])) {
            $tipos_validos = ['por_categoria', 'por_categoria_especifica', 'precios', 'stock'];
            if (!in_array($datos['tipo_reporte'], $tipos_validos)) {
                $errores['tipo_reporte'] = 'El tipo de reporte no es válido. Tipos permitidos: ' . implode(', ', $tipos_validos);
            }
        }
        
        // Validar categoría si se especifica
        if (isset($datos['categoria'])) {
            $categoria = trim((string)$datos['categoria']);
            if ($categoria !== '' && mb_strlen($categoria) > self::MAX_CATEGORIA) {
                $errores['categoria'] = 'La categoría no debe exceder los ' . self::MAX_CATEGORIA . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    public function validarDescarga($datos) {
        $errores = [];
        
        // Para descarga, validar tipo de descarga
        if (isset($datos['tipo_descarga'])) {
            $tipos_validos = ['pdf', 'excel', 'csv'];
            if (!in_array($datos['tipo_descarga'], $tipos_validos)) {
                $errores['tipo_descarga'] = 'El tipo de descarga no es válido. Tipos permitidos: ' . implode(', ', $tipos_validos);
            }
        }
        
        // Validar parámetros adicionales según el tipo
        if (isset($datos['parametros']) && is_array($datos['parametros'])) {
            foreach ($datos['parametros'] as $parametro => $valor) {
                if (is_string($valor) && mb_strlen($valor) > 100) {
                    $errores[$parametro] = 'El parámetro ' . $parametro . ' es demasiado largo';
                }
            }
        }
        
        return $errores;
    }
    
    private function verificarProductoExistente($id_producto) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_productos WHERE id_producto = ?");
                $stmt->execute([$id_producto]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        });
    }
    
    private function verificarModeloExistente($id_modelo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_modelos WHERE id_modelo = ?");
                $stmt->execute([$id_modelo]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        });
    }

    public function guardarImagenProducto($id_producto, $nombre_imagen) {
        return $this->g_guardarImagenProducto($id_producto, $nombre_imagen);
    }
    private function g_guardarImagenProducto($id_producto, $nombre_imagen) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto, $nombre_imagen) {
            $sql = "UPDATE tbl_productos SET imagen = :imagen WHERE id_producto = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':imagen', $nombre_imagen);
            $stmt->bindParam(':id', $id_producto, PDO::PARAM_INT);
            return $stmt->execute();
        });
    }

    public function validarNombreProducto() {
        return $this->v_nombreProducto();
    }
    private function v_nombreProducto() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_productos WHERE nombre_producto = :nombre_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_producto', $this->nombre_producto);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            return $count == 0;
        });
    }
    
    public function validarCodigoProducto() {
        return $this->v_codigoProducto();
    }
    private function v_codigoProducto() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_productos WHERE serial = :serial_Interno";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':serial_Interno', $this->serial);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            return $count == 0;
        });
    }

    public function ingresarProducto($datosCategoria) {
        return $this->g_ingresarProducto($datosCategoria);
    }
    private function g_ingresarProducto($datosCategoria) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($datosCategoria){
            try {
                // Obtener el nombre de la categoría desde la tabla de categoría
                if (empty($datosCategoria['tabla_categoria'])) {
                    throw new PDOException("No se especificó la tabla de categoría.");
                }
                $tablaCategoria = $datosCategoria['tabla_categoria'];
                
                // Extraer el nombre de la categoría del nombre de la tabla
                $nombreCategoria = str_replace('_', ' ', ucfirst(str_replace('cat_', '', $tablaCategoria)));

                // Construir el JSON de características
                $caracteristicasJson = null;
                if (!empty($datosCategoria['carac']) && is_array($datosCategoria['carac'])) {
                    $caracteristicasJson = json_encode($datosCategoria['carac']);
                }

                // Obtener el ID del usuario auditor de la sesión
                $idUsuarioAuditor = $_SESSION['id_usuario'] ?? 1; // Default a 1 si no hay sesión

                // Llamar al procedimiento almacenado con parámetro OUT
                $sql = "CALL sp_registrar_producto(
                    :serial,
                    :nombre_producto,
                    :descripcion_producto,
                    :id_modelo,
                    :stock,
                    :stock_minimo,
                    :stock_maximo,
                    :clausula_garantia,
                    :precio,
                    :estado,
                    :nombre_categoria,
                    :id_usuario_auditor,
                    :caracteristicas,
                    :imagen,
                    @id_producto
                )";

                $stmt = $pdo->prepare($sql);
                
                $stmt->bindParam(':serial', $this->serial);
                $stmt->bindParam(':nombre_producto', $this->nombre_producto);
                $stmt->bindParam(':descripcion_producto', $this->descripcion_p);
                $stmt->bindParam(':id_modelo', $this->id_modelo);
                $stmt->bindParam(':stock', $this->stock_actual);
                $stmt->bindParam(':stock_minimo', $this->stock_min);
                $stmt->bindParam(':stock_maximo', $this->stock_max);
                $stmt->bindParam(':clausula_garantia', $this->clausula_garantia);
                $stmt->bindParam(':precio', $this->precio);
                $stmt->bindValue(':estado', 'habilitado');
                $stmt->bindParam(':nombre_categoria', $nombreCategoria);
                $stmt->bindParam(':id_usuario_auditor', $idUsuarioAuditor);
                $stmt->bindParam(':caracteristicas', $caracteristicasJson);
                $stmt->bindParam(':imagen', $this->imagen);

                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    throw new PDOException("Error al ejecutar procedimiento almacenado: " . $errorInfo[2]);
                }

                $stmt->closeCursor();

                // Obtener el ID del producto insertado desde la variable de sesión
                $stmt = $pdo->query("SELECT @id_producto AS id_producto");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $idProducto = $result['id_producto'];
                
                return $idProducto;

            } catch (PDOException $e) {
                throw new PDOException("Error al ingresar producto: " . $e->getMessage());
            }
        }, false);
    }

    public function actualizarstockProducto($id_producto, $cantidad) {
        return $this->a_actualizarstockProducto($id_producto, $cantidad);
    }
    private function a_actualizarstockProducto($id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto, $cantidad){
            $sql = "UPDATE tbl_productos 
                    SET stock = stock - :cantidad
                    WHERE id_producto = :id_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            return $stmt->execute();
        });
    }
    
    public function obtenerProductoPorId($id) {
        return $this->o_productoPorId($id);
    }
    private function o_productoPorId($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $query = "SELECT * FROM tbl_productos WHERE id_producto = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            return $producto;
        });
    }

    public function obtenerCaracteristicasDinamicasPorProducto($id_producto) {
        return $this->c_caracteristicasPorProducto($id_producto);
    }
    private function c_caracteristicasPorProducto($id_producto) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto) {
            $sql = "SELECT c.nombre_categoria
                    FROM tbl_productos p
                    INNER JOIN tbl_categoria c ON p.id_categoria = c.id_categoria
                    WHERE p.id_producto = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_producto, PDO::PARAM_INT);
            $stmt->execute();
            $nombreCategoria = $stmt->fetchColumn();

            if (!$nombreCategoria) {
                return [];
            }

            $tablaCategoria = 'cat_' . strtolower(str_replace(' ', '_', $nombreCategoria));

            $sqlCat = "SELECT * FROM `$tablaCategoria` WHERE id_producto = :id_producto LIMIT 1";
            $stmtCat = $pdo->prepare($sqlCat);
            $stmtCat->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtCat->execute();
            $row = $stmtCat->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [];
            }

            unset($row['id'], $row['id_producto']);
            return $row;
        });
    }

    public function obtenerProductoDetallado($id_producto) {
        return $this->o_productoDetallado($id_producto);
    }
    private function o_productoDetallado($id_producto) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto){
            $sql = "SELECT p.*, c.nombre_categoria, mo.nombre_modelo, m.nombre_marca, p.imagen
                    FROM tbl_productos p
                    INNER JOIN tbl_categoria c ON p.id_categoria = c.id_categoria
                    INNER JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    INNER JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.id_producto = :id_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                return null;
            }

            $producto['caracteristicas'] = $this->obtenerCaracteristicasDinamicasPorProducto($id_producto);
            return $producto;
        });
    }

    public function CategoriasReporte(){
        return $this->c_categoriasReporte();
    }
    private function c_categoriasReporte(){
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerCategoriasDinamicas() {
        return $this->o_categoriasDinamicas();
    }
    private function o_categoriasDinamicas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SHOW TABLES LIKE 'cat\_%'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $categorias = [];
            foreach ($tablas as $tabla) {
                $nombre_categoria = ucfirst(str_replace('cat_', '', $tabla));
                $cols = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
                $caracteristicas = [];
                foreach ($cols as $col) {
                    if (!in_array($col['Field'], ['id', 'id_producto'])) {
                        $tipo = 'string';
                        if (strpos($col['Type'], 'int') !== false) $tipo = 'int';
                        elseif (strpos($col['Type'], 'float') !== false) $tipo = 'float';
                        $max = 255;
                        if (preg_match('/varchar\((\d+)\)/i', $col['Type'], $m)) $max = $m[1];
                        $caracteristicas[] = [
                            'nombre' => $col['Field'],
                            'tipo' => $tipo,
                            'max' => $max
                        ];
                    }
                }
                $categorias[] = [
                    'tabla' => $tabla,
                    'nombre_categoria' => $nombre_categoria,
                    'caracteristicas' => $caracteristicas
                ];
            }
            return $categorias;
        });
    }

    public function obtenerProductoStock() {
        return $this->o_productoStock();
    }
    private function o_productoStock() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $queryProductos = 'SELECT id_producto, nombre_producto, stock, id_modelo, serial FROM tbl_productos';
            $stmtProductos = $pdo->prepare($queryProductos);
            $stmtProductos->execute();
            $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
            return $productos;
        });
    }

    public function obtenerReporteCategorias() {
        return $this->o_reporteCategorias();
    }
    private function o_reporteCategorias() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT c.nombre_categoria, COUNT(p.id_producto) as cantidad
                    FROM tbl_categoria c
                    LEFT JOIN tbl_productos p ON c.id_categoria = p.id_categoria
                    GROUP BY c.id_categoria, c.nombre_categoria
                    ORDER BY cantidad DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function modificarProducto($id, $datosCategoria) {
        return $this->m_modificarProducto($id, $datosCategoria);
    }
    private function m_modificarProducto($id, $datosCategoria) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $datosCategoria){
            try {
                // Validar que la categoría exista
                if (empty($this->categoria)) {
                    throw new PDOException("No se especificó la categoría.");
                }
                
                // Transformar el nombre de la categoría como en el registro
                $nombreCategoria = str_replace('_', ' ', ucfirst(str_replace('cat_', '', $this->categoria)));
                
                // Construir el JSON de características
                $caracteristicasJson = null;
                if (!empty($datosCategoria['carac']) && is_array($datosCategoria['carac'])) {
                    $caracteristicasJson = json_encode($datosCategoria['carac']);
                }

                // Obtener el ID del usuario auditor de la sesión
                $idUsuarioAuditor = $_SESSION['id_usuario'] ?? 1;

                // Llamar al procedimiento almacenado con parámetro OUT
                $sql = "CALL sp_modificar_producto(
                    :id_producto,
                    :serial,
                    :nombre_producto,
                    :descripcion_producto,
                    :id_modelo,
                    :stock,
                    :stock_minimo,
                    :stock_maximo,
                    :clausula_garantia,
                    :precio,
                    :estado,
                    :nombre_categoria,
                    :id_usuario_auditor,
                    :caracteristicas,
                    :imagen,
                    @resultado
                )";

                $stmt = $pdo->prepare($sql);
                
                $stmt->bindParam(':id_producto', $id);
                $stmt->bindParam(':serial', $this->serial);
                $stmt->bindParam(':nombre_producto', $this->nombre_producto);
                $stmt->bindParam(':descripcion_producto', $this->descripcion_p);
                $stmt->bindParam(':id_modelo', $this->id_modelo);
                $stmt->bindParam(':stock', $this->stock_actual);
                $stmt->bindParam(':stock_minimo', $this->stock_min);
                $stmt->bindParam(':stock_maximo', $this->stock_max);
                $stmt->bindParam(':clausula_garantia', $this->clausula_garantia);
                $stmt->bindParam(':precio', $this->precio);
                $stmt->bindValue(':estado', 'habilitado');
                $stmt->bindParam(':nombre_categoria', $nombreCategoria);
                $stmt->bindParam(':id_usuario_auditor', $idUsuarioAuditor);
                $stmt->bindParam(':caracteristicas', $caracteristicasJson);
                $stmt->bindParam(':imagen', $this->imagen);

                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    throw new PDOException("Error al ejecutar procedimiento almacenado: " . $errorInfo[2]);
                }

                $stmt->closeCursor();

                // Obtener el resultado desde la variable de sesión
                $stmt = $pdo->query("SELECT @resultado AS resultado");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $resultado = $result['resultado'];
                
                return $resultado == 1;

            } catch (PDOException $e) {
                throw new PDOException('Error al modificar producto: ' . $e->getMessage());
            }
        }, false);
    }

    public function eliminarProducto($id, $id_usuario) {
        return $this->e_eliminarProducto($id, $id_usuario);
    }
    private function e_eliminarProducto($id, $id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $id_usuario){
            try {
                $sql = "CALL sp_eliminar_producto(:id_producto, :id_usuario_auditor)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_producto', $id);
                $stmt->bindParam(':id_usuario_auditor', $id_usuario);
                $result = $stmt->execute();
                $stmt->closeCursor();
            
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Producto eliminado exitosamente.'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'No se pudo eliminar el producto.'
                ];
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    return [
                        'success' => false,
                        'message' => 'No se puede eliminar el producto porque tiene registros asociados.'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Error inesperado: ' . $e->getMessage()
                    ];
                }
            }
        }, false);
    }

    public function obtenerModelos() {
        return $this->o_modelos();
    }
    private function o_modelos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "SELECT 
                mo.id_modelo AS tbl_modelos,
                mo.nombre_modelo,
                mar.nombre_marca AS tbl_marcas
            FROM 
                tbl_modelos mo
            JOIN 
                tbl_marcas mar ON mo.id_marca = mar.id_marca;
            ";
            $stmt = $pdo->query($query);
            if ($stmt) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return [];
            }
        });
    }

    public function cambiarEstatus($nuevoEstatus) {
        return $this->c_cambiarEstatus($nuevoEstatus);
    }
    private function c_cambiarEstatus($nuevoEstatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus){
            try {
                $sql = "UPDATE tbl_productos SET estado = :estatus WHERE id_producto = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':estatus', $nuevoEstatus);
                $stmt->bindParam(':id', $this->id);
                return $stmt->execute();
            } catch (PDOException $e) {
                return false;
            }
        });
    }

    public function obtenerProductosConPrecios() {
        return $this->o_productosConPrecios();
    }
    private function o_productosConPrecios() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT nombre_producto, precio FROM tbl_productos";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerMarcas() {
        return $this->o_marcas();
    }
    private function o_marcas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "SELECT id_marca, nombre_marca FROM tbl_marcas";
            $stmt = $pdo->query($query);
            if ($stmt) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return [];
            }
        });
    }

    public function obtenerProductosConMarca() {
        return $this->o_productosConMarca();
    }
    private function o_productosConMarca() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "SELECT p.*, m.nombre_marca as marca 
                    FROM tbl_productos p
                    JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.estado = 'habilitado' AND p.stock > 0
                    ORDER BY p.nombre_producto DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerProductosPorMarca($id_marca) {
        return $this->o_productosPorMarca($id_marca);
    }
    private function o_productosPorMarca($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_marca){
            $query = "SELECT p.*, m.nombre_marca as marca 
                    FROM tbl_productos p
                    JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE m.id_marca = :id_marca AND p.estado = 'habilitado' AND p.stock > 0
                    ORDER BY p.nombre_producto DESC";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_marca', $id_marca);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerCombosDisponibles($esAdmin = false) {
        return $this->o_combosDisponibles($esAdmin);
    }

    /**
     * Obtiene combos con todos sus detalles en una sola query (optimizado para evitar N+1)
     */
    public function obtenerCombosConDetalles($esAdmin = false) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($esAdmin) {
            // Obtener combos
            $whereClause = $esAdmin ? "" : "AND c.activo = 1";
            $sql = "SELECT c.id_combo, c.nombre_combo, c.descripcion, c.descuento, c.activo
                    FROM tbl_combo c
                    WHERE 1=1 $whereClause 
                    ORDER BY c.nombre_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($combos)) {
                return [];
            }
            
            // Obtener todos los detalles de combos en una sola query
            $comboIds = array_column($combos, 'id_combo');
            $placeholders = str_repeat('?,', count($comboIds) - 1) . '?';
            
            $sqlDetalles = "SELECT cd.id_combo, cd.id_producto, cd.cantidad,
                                   p.nombre_producto, p.precio, p.stock, p.imagen,
                                   m.nombre_marca as marca, p.descripcion_producto as descripcion,
                                   c.descuento
                            FROM tbl_combo_detalle cd
                            INNER JOIN tbl_combo c ON c.id_combo = cd.id_combo
                            INNER JOIN tbl_productos p ON cd.id_producto = p.id_producto
                            INNER JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                            INNER JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                            WHERE cd.id_combo IN ($placeholders) AND p.estado = 'habilitado'";
            $stmtDetalles = $pdo->prepare($sqlDetalles);
            $stmtDetalles->execute($comboIds);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar detalles por combo
            $detallesPorCombo = [];
            foreach ($detalles as $detalle) {
                $detalle['precio_unitario'] = round(
                    (float)$detalle['precio'] * (1 - (float)$detalle['descuento'] / 100),
                    2
                );
                $detallesPorCombo[$detalle['id_combo']][] = $detalle;
            }
            
            // Combinar combos con sus detalles
            foreach ($combos as &$combo) {
                $combo['detalles'] = $detallesPorCombo[$combo['id_combo']] ?? [];
                $combo['precio_original'] = 0;
                $combo['precio_total'] = 0;
                foreach ($combo['detalles'] as $detalle) {
                    $combo['precio_original'] += (float)$detalle['precio'] * (int)$detalle['cantidad'];
                    $combo['precio_total'] += (float)$detalle['precio_unitario'] * (int)$detalle['cantidad'];
                }
                $combo['ahorro_estimado'] = $combo['precio_original'] - $combo['precio_total'];
            }
            
            return $combos;
        }, 'S');
    }
    private function o_combosDisponibles($esAdmin = false) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($esAdmin) {
            $sql = "SELECT * FROM tbl_combo";
            if (!$esAdmin) {
                $sql .= " WHERE activo = 1";
            } else {
                $sql .= " WHERE activo IN (0, 1)";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerCombosDisponiblesMovil() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_combo WHERE activo = 1 ORDER BY nombre_combo ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($combos as &$combo) {
                $idCombo = (int)($combo['id_combo'] ?? $combo['id'] ?? 0);
                $combo['id'] = $idCombo;
                $combo['name'] = $combo['nombre_combo'] ?? $combo['name'] ?? 'Combo';
                $combo['nombre'] = $combo['nombre_combo'] ?? $combo['nombre'] ?? 'Combo';
                $combo['description'] = $combo['descripcion'] ?? $combo['description'] ?? '';
                $combo['descripcion'] = $combo['descripcion'] ?? $combo['description'] ?? '';
                $combo['type'] = 'combo';
                $combo['isCombo'] = true;
                $combo['productos'] = $this->obtenerDetallesCombo($idCombo);
                $combo['precio_original'] = 0;
                $combo['precio_total'] = 0;
                foreach ($combo['productos'] as $producto) {
                    $quantity = (int)($producto['cantidad'] ?? 1);
                    $combo['precio_original'] += (float)($producto['precio'] ?? 0) * $quantity;
                    $combo['precio_total'] += (float)($producto['precio_unitario'] ?? 0) * $quantity;
                }
                $combo['precio_final'] = (float)($combo['precio_total'] ?? 0);
                $combo['ahorro_estimado'] = $combo['precio_original'] - $combo['precio_final'];
                $combo['precio'] = $combo['precio_final'];
            }

            return $combos;
        });
    }

    public function obtenerDetallesCombo($id_combo) {
        return $this->o_detallesCombo($id_combo);
    }
    private function o_detallesCombo($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            $query = "SELECT cd.id_producto, cd.cantidad, cd.precio_unitario, c.descuento,
                            p.nombre_producto, p.precio, p.stock, p.imagen,
                            m.nombre_marca as marca, p.descripcion_producto as descripcion
                    FROM tbl_combo_detalle cd
                    INNER JOIN tbl_combo c ON c.id_combo = cd.id_combo
                    INNER JOIN tbl_productos p ON cd.id_producto = p.id_producto
                    LEFT JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    LEFT JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE cd.id_combo = :id_combo AND p.estado = 'habilitado'";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':id_combo', (int)$id_combo, PDO::PARAM_INT);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos as &$producto) {
                $producto['precio_unitario'] = round(
                    (float)$producto['precio'] * (1 - (float)$producto['descuento'] / 100),
                    2
                );
                $imagen = $producto['imagen'] ?? null;
                if ($imagen) {
                    $producto['imagen'] = \getImageUrl($imagen);
                }
            }

            return $productos;
        });
    }

    public function agregarProductoAlCarrito($id_cliente, $id_producto, $cantidad = 1, $id_combo = null) {
        return $this->b_agregarProductoAlCarrito($id_cliente, $id_producto, $cantidad, $id_combo);
    }
    private function b_agregarProductoAlCarrito($id_cliente, $id_producto, $cantidad = 1, $id_combo = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cliente, $id_producto, $cantidad, $id_combo) {
            try {
                $producto = $this->obtenerProductoPorId($id_producto);
                if (!$producto || $producto['stock'] < $cantidad) {
                    throw new PDOException('Producto no disponible o cantidad insuficiente');
                }
                $id_carrito = $this->obtenerOCrearCarrito($id_cliente);
                $sql = "SELECT id_carrito_detalle, cantidad FROM tbl_carritodetalle 
                        WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_carrito', $id_carrito);
                $stmt->bindParam(':id_producto', $id_producto);
                $stmt->execute();
                $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existente) {
                    $nueva_cantidad = $existente['cantidad'] + $cantidad;
                    $sql = "UPDATE tbl_carritodetalle SET cantidad = :cantidad 
                            WHERE id_carrito_detalle = :id_carrito_detalle";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':cantidad', $nueva_cantidad);
                    $stmt->bindParam(':id_carrito_detalle', $existente['id_carrito_detalle']);
                    return $stmt->execute();
                } else {
                    $sql = "INSERT INTO tbl_carritodetalle 
                            (id_carrito, id_producto, cantidad, estatus) 
                            VALUES (:id_carrito, :id_producto, :cantidad, 'pendiente')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id_carrito', $id_carrito);
                    $stmt->bindParam(':id_producto', $id_producto);
                    $stmt->bindParam(':cantidad', $cantidad);
                    return $stmt->execute();
                }
            } catch (PDOException $e) {
                throw $e;
            }
        });
    }

    public function obtenerOCrearCarrito($id_cliente) {
        return $this->obtCrearCarrito($id_cliente);
    }
    private function obtCrearCarrito($id_cliente) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cliente){
            try {
                $sql = "SELECT id_carrito FROM tbl_carrito 
                        WHERE id_cliente = :id_cliente
                        ORDER BY fecha_creacion DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cliente', $id_cliente);
                $stmt->execute();
                $carrito = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($carrito) {
                    return $carrito['id_carrito'];
                }
                
                $sql = "INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cliente', $id_cliente);
                $stmt->execute();
                return $pdo->lastInsertId();
            } catch (PDOException $e) {
                error_log("Error en obtCrearCarrito: " . $e->getMessage());
                throw $e;
            }
        });
    }

    public function agregarComboAlCarrito($id_cliente, $id_combo) {
        return $this->a_agregarComboAlCarrito($id_cliente, $id_combo);
    }
    private function a_agregarComboAlCarrito($id_cliente, $id_combo) {
        try {
            $combo = $this->obtenerComboPorId($id_combo);
            if (!$combo) {
                throw new PDOException("El combo no está disponible");
            }
            $productosCombo = $this->obtenerDetallesCombo($id_combo);
            if (empty($productosCombo)) {
                throw new PDOException("El combo no contiene productos válidos");
            }
            foreach ($productosCombo as $producto) {
                $productoInfo = $this->obtenerProductoPorId($producto['id_producto']);
                if (!$productoInfo || $productoInfo['stock'] < $producto['cantidad']) {
                    $nombre = $productoInfo['nombre_producto'] ?? ('ID: ' . $producto['id_producto']);
                    throw new PDOException("El producto {$nombre} no tiene suficiente stock");
                }
            }
            foreach ($productosCombo as $producto) {
                $resultado = $this->agregarProductoAlCarrito($id_cliente, $producto['id_producto'], $producto['cantidad']);
                if ($resultado !== true) {
                    $mensaje = is_string($resultado) ? $resultado : 'Error al agregar producto del combo al carrito';
                    throw new PDOException($mensaje);
                }
            }
            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function obtenerComboPorId($id_combo) {
        return $this->o_comboPorId($id_combo);
    }
    private function o_comboPorId($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo){
            $sql = "SELECT * FROM tbl_combo WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerTodosProductosParaCombos() {
        return $this->o_todosProductosParaCombos();
    }
    private function o_todosProductosParaCombos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "SELECT p.id_producto, p.nombre_producto, p.stock, m.nombre_marca as marca, p.precio
                    FROM tbl_productos p
                    JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.estado = 'habilitado'
                    ORDER BY p.nombre_producto DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerProductosBajoStock() {
        return $this->o_productosBajoStock();
    }
    private function o_productosBajoStock() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "SELECT p.id_producto, p.nombre_producto, p.stock, p.stock_minimo, 
                            m.nombre_marca as marca
                    FROM tbl_productos p
                    JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.stock <= p.stock_minimo AND p.estado = 'habilitado'
                    ORDER BY (p.stock / p.stock_minimo) DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function buscarProductos($termino) {
        return $this->b_buscarProductos($termino);
    }
    private function b_buscarProductos($termino) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($termino){
            $query = "SELECT p.*, m.nombre_marca as marca 
                    FROM tbl_productos p
                    JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE (p.nombre_producto LIKE :termino OR 
                            p.descripcion_producto LIKE :termino OR
                            p.serial LIKE :termino OR
                            m.nombre_marca LIKE :termino)
                    AND p.estado = 'habilitado'
                    ORDER BY p.nombre_producto DESC";
            $stmt = $pdo->prepare($query);
            $like = "%$termino%";
            $stmt->bindParam(':termino', $like);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function actualizarStock($id_producto, $cantidad) {
        return $this->a_actualizarStock($id_producto, $cantidad);
    }
    private function a_actualizarStock($id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto, $cantidad){
            $sql = "UPDATE tbl_productos SET stock = stock + :cantidad 
                    WHERE id_producto = :id_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':id_producto', $id_producto);
            return $stmt->execute();
        });
    }

    public function agregarProductoACombo($id_combo, $id_producto, $cantidad) {
        return $this->a_agregarProductoACombo($id_combo, $id_producto, $cantidad);
    }
    private function a_agregarProductoACombo($id_combo, $id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo, $id_producto, $cantidad){
            // Verificar que el producto existe
            $producto = $this->obtenerProductoPorId($id_producto);
            if (!$producto) {
                throw new PDOException("El producto con ID $id_producto no existe");
            }
            
            $sql = "INSERT INTO tbl_combo_detalle (id_combo, id_producto, cantidad) 
                    VALUES (:id_combo, :id_producto, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        });
    }

    public function crearCombo($nombre, $descripcion, $productos) {
        return $this->c_crearCombo($nombre, $descripcion, $productos);
    }
    private function c_crearCombo($nombre, $descripcion, $productos) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre, $descripcion, $productos){
            try {
                $pdo->beginTransaction();

                $sql = "INSERT INTO tbl_combo (nombre_combo, descripcion) 
                        VALUES (:nombre, :descripcion)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->execute();

                $id_combo = $pdo->lastInsertId();

                foreach ($productos as $producto) {
                    $this->agregarProductoACombo($id_combo, $producto['id'], $producto['cantidad']);
                }

                $pdo->commit();
                return $id_combo;
            } catch (PDOException $e) {
                if ($pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        });
    }

    public function actualizarCombo($id_combo, $nombre, $descripcion, $productos) {
        return $this->a_actualizarCombo($id_combo, $nombre, $descripcion, $productos);
    }
    private function a_actualizarCombo($id_combo, $nombre, $descripcion, $productos) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo, $nombre, $descripcion, $producto) {
            try {
                $pdo->beginTransaction();

                $sql = "UPDATE tbl_combo 
                        SET nombre_combo = :nombre, 
                            descripcion = :descripcion
                        WHERE id_combo = :id_combo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':id_combo', $id_combo);
                $stmt->execute();

                $sql = "DELETE FROM tbl_combo_detalle WHERE id_combo = :id_combo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_combo', $id_combo);
                $stmt->execute();

                foreach ($productos as $producto) {
                    $this->agregarProductoACombo($id_combo, $producto['id'], $producto['cantidad']);
                }

                $pdo->commit();
                return true;
            } catch (PDOException $e) {
                if ($pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        });
    }

    public function eliminarCombo($id_combo) {
        return $this->e_eliminarCombo($id_combo);
    }
    private function e_eliminarCombo($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            $sql = "UPDATE tbl_combo SET activo = 2 WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            return $stmt->execute();
        });
    }

    public function obtenerInfoCompletaCombo($id_combo) {
        return $this->o_infoCompletaCombo($id_combo);
    }
    private function o_infoCompletaCombo($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            $combo = $this->obtenerComboPorId($id_combo);
            if (!$combo) {
                return null;
            }
            $combo['productos'] = $this->obtenerDetallesCombo($id_combo);
            $combo['precio_original'] = 0;
            $combo['precio_total'] = 0;
            foreach ($combo['productos'] as $producto) {
                $combo['precio_original'] += (float)$producto['precio'] * (int)$producto['cantidad'];
                $combo['precio_total'] += (float)$producto['precio_unitario'] * (int)$producto['cantidad'];
            }
            $combo['ahorro_estimado'] = $combo['precio_original'] - $combo['precio_total'];
            $combo['precio_final'] = $combo['precio_total'];
            return $combo;
        });
    }

    public function obtenerTodosCombosConDetalles() {
        return $this->o_todosCombosConDetalles();
    }
    private function o_todosCombosConDetalles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $combos = $this->obtenerCombosDisponibles();
            foreach ($combos as &$combo) {
                $combo['productos'] = $this->obtenerDetallesCombo($combo['id_combo']);
                $combo['total_productos'] = count($combo['productos']);
                $combo['precio_original'] = 0;
                $combo['precio_total'] = 0;
                foreach ($combo['productos'] as $producto) {
                    $combo['precio_original'] += (float)$producto['precio'] * (int)$producto['cantidad'];
                    $combo['precio_total'] += (float)$producto['precio_unitario'] * (int)$producto['cantidad'];
                }
                $combo['ahorro_estimado'] = $combo['precio_original'] - $combo['precio_total'];
                $combo['precio_final'] = $combo['precio_total'];
            }
            return $combos;
        });
    }

    public function obtenerCantidadCarrito($id_cliente) {
        return $this->o_cantidadCarrito($id_cliente);
    }
    private function o_cantidadCarrito($id_cliente) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cliente) {
            $sql = "SELECT SUM(cd.cantidad) as total 
                    FROM tbl_carritodetalle cd
                    JOIN tbl_carrito c ON cd.id_carrito = c.id_carrito
                    WHERE c.id_cliente = :id_cliente AND cd.estatus = 'pendiente'";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        });
    }

    public function verificarStock($id_producto, $cantidad) {
        return $this->v_verificarStock($id_producto, $cantidad);
    }
    private function v_verificarStock($id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto, $cantidad) {
            $sql = "SELECT stock FROM tbl_productos WHERE id_producto = :id_producto AND estado = 'habilitado'";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($producto && $producto['stock'] >= $cantidad);
        });
    }

    public function cambiarEstadoCombo($id_combo) {
        return $this->c_EstadoCombo($id_combo);
    }
    private function c_EstadoCombo($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo){    
            try {
                $sql = "SELECT activo FROM tbl_combo WHERE id_combo = :id_combo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_combo', $id_combo, PDO::PARAM_INT);
                
                if (!$stmt->execute()) {
                    $error = $stmt->errorInfo();
                    throw new PDOException('Error al obtener el estado actual del combo: ' . $error[2]);
                }
                
                $combo = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$combo) {
                    throw new PDOException('Combo no encontrado');
                }
                
                $nuevoEstado = $combo['activo'] ? 0 : 1;
                
                $sql = "UPDATE tbl_combo SET activo = :activo WHERE id_combo = :id_combo";
                $stmt = $pdo->prepare($sql);
                
                if (!$stmt) {
                    $error = $pdo->errorInfo();
                    throw new PDOException('Error al preparar la consulta: ' . $error[2]);
                }
                
                $stmt->bindParam(':activo', $nuevoEstado, PDO::PARAM_INT);
                $stmt->bindParam(':id_combo', $id_combo, PDO::PARAM_INT);
                
                if (!$stmt->execute()) {
                    $error = $stmt->errorInfo();
                    throw new PDOException('Error al ejecutar la actualización: ' . $error[2]);
                }
                
                return true;
                
            } catch (PDOException $e) {
                error_log('Error en c_EstadoCombo: ' . $e->getMessage());
                throw $e;
            } catch (Exception $e) {
                error_log('Error general en c_EstadoCombo: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    public function getProductosMasVendidos() {
        return $this->g_productosMasVendidos();
    }
    private function g_productosMasVendidos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT 
                    p.id_producto,
                    p.nombre_producto,
                    SUM(fd.cantidad) AS total_vendido
                FROM tbl_factura_detalle fd
                INNER JOIN tbl_productos p ON fd.id_producto = p.id_producto
                GROUP BY p.id_producto, p.nombre_producto
                ORDER BY total_vendido DESC
                LIMIT 10;
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getStockProductos() {
        return $this->g_stockProductos();
    }
    private function g_stockProductos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT 
                    p.nombre_producto,
                    p.stock,
                    p.stock_minimo,
                    p.id_categoria,
                    c.nombre_categoria,
                    CASE 
                        WHEN p.stock <= p.stock_minimo THEN 'Bajo Stock'
                        ELSE 'Alto Stock'
                    END AS categoria_stock
                FROM tbl_productos p 
                INNER JOIN tbl_categoria c ON c.id_categoria = p.id_categoria;
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getRotacionProductos() {
        return $this->g_rotacionProductos();
    }
    private function g_rotacionProductos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT p.id_producto, p.nombre_producto, 
                AVG( CASE WHEN r.fecha IS NOT NULL AND primera_factura.fecha_minima 
                IS NOT NULL THEN DATEDIFF(primera_factura.fecha_minima, r.fecha) ELSE NULL END ) AS dias_promedio
                FROM tbl_recepcion_productos r 
                INNER JOIN tbl_detalle_recepcion_productos dr ON r.id_recepcion = dr.id_recepcion 
                INNER JOIN tbl_productos p ON dr.id_producto = p.id_producto 
                INNER JOIN (
                    SELECT fd.id_producto, MIN(f.fecha) AS fecha_minima 
                    FROM tbl_factura_detalle fd 
                    INNER JOIN tbl_facturas f ON fd.factura_id = f.id_factura 
                    WHERE f.fecha IS NOT NULL 
                    GROUP BY fd.id_producto
                ) primera_factura ON p.id_producto = primera_factura.id_producto 
                WHERE r.fecha IS NOT NULL 
                GROUP BY p.id_producto, p.nombre_producto;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerRelacionadosPorCategoria($id_producto, $limit = 8) {
        return $this->o_relacionadosPorCategoria($id_producto, $limit);
    }
    private function o_relacionadosPorCategoria($id_producto, $limit = 8) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto, $limit){
            $sql = "SELECT p.*, mo.nombre_modelo, m.nombre_marca
                    FROM tbl_productos p
                    INNER JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    INNER JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.id_categoria = (
                        SELECT id_categoria FROM tbl_productos WHERE id_producto = :id_producto
                    )
                    AND p.id_producto <> :id_producto
                    AND p.estado = 'habilitado'
                    ORDER BY p.stock DESC, p.nombre_producto ASC
                    LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
    
    public function obtenerProductos() {
        return $this->o_obtenerProductos();
    }
    private function o_obtenerProductos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $queryProductos = '
                SELECT 
                    tbl_productos.*, 
                    tbl_modelos.nombre_modelo,
                    tbl_marcas.nombre_marca,
                    tbl_categoria.nombre_categoria 
                FROM tbl_productos 
                INNER JOIN tbl_modelos 
                    ON tbl_productos.id_modelo = tbl_modelos.id_modelo
                INNER JOIN tbl_marcas
                    ON tbl_modelos.id_marca = tbl_marcas.id_marca
                INNER JOIN tbl_categoria 
                    ON tbl_productos.id_categoria = tbl_categoria.id_categoria
                ORDER BY tbl_productos.id_producto DESC;
                ';
            $stmtProductos = $pdo->prepare($queryProductos);
            $stmtProductos->execute();
            $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos as &$producto) {
                $idProducto = $producto['id_producto'];
                $nombreTabla = 'cat_' . strtolower(str_replace(' ', '_', $producto['nombre_categoria']));
                $sql = "SHOW TABLES LIKE :tabla";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':tabla' => $nombreTabla]);
                if ($stmt->fetch()) {
                    $sqlCarac = "SELECT * FROM `$nombreTabla` WHERE id_producto = :id";
                    $stmtCarac = $pdo->prepare($sqlCarac);
                    $stmtCarac->bindParam(':id', $idProducto, PDO::PARAM_INT);
                    $stmtCarac->execute();
                    $caracteristicas = $stmtCarac->fetch(PDO::FETCH_ASSOC);
                    $producto['caracteristicas'] = $caracteristicas ?: [];
                } else {
                    $producto['caracteristicas'] = [];
                }
            }
            return $productos;
        });
    }

    public function obtenerProductosConBajoStock() {
        return $this->o_obtenerProductosConBajoStock();
    }
    private function o_obtenerProductosConBajoStock() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $queryProductos = '
                SELECT tbl_productos.*, 
                    tbl_modelos.nombre_modelo, 
                    tbl_categoria.nombre_categoria 
                FROM tbl_productos 
                INNER JOIN tbl_modelos 
                    ON tbl_productos.id_modelo = tbl_modelos.id_modelo 
                INNER JOIN tbl_categoria 
                    ON tbl_productos.id_categoria = tbl_categoria.id_categoria
                WHERE tbl_productos.stock < tbl_productos.stock_minimo
            ';
            $stmtProductos = $pdo->prepare($queryProductos);
            $stmtProductos->execute();
            $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

            return $productos;
        });
    }

    // ==================== MÉTODOS PARA API MÓVIL ====================
    
    /**
     * Obtiene todos los productos habilitados para la API móvil
     * @param array $data Datos de la petición (puede contener filtros)
     * @return array Productos formateados para la app móvil
     */
    public function obtenerCatalogoMovil($data = []) {
        return $this->obtenerTodosProductos($data);
    }

    public function obtenerTodosProductos($data = []) {
        return $this->api_obtenerTodosProductos($data);
    }

    private function formatearProductoMovil(PDO $pdo, array $row, $incluirCaracteristicas = false) {
        $imagen = $row['imagen'] ?? null;

        $producto = [
            'id' => $row['id_producto'],
            'serial' => $row['serial'] ?? '',
            'nombre' => $row['nombre_producto'],
            'descripcion' => $row['descripcion_producto'],
            'categoria_id' => $row['id_categoria'],
            'categoria_nombre' => $row['nombre_categoria'],
            'modelo_id' => $row['id_modelo'],
            'modelo_nombre' => $row['nombre_modelo'],
            'modelo' => $row['nombre_modelo'],
            'marca_id' => $row['id_marca'],
            'marca_nombre' => $row['nombre_marca'],
            'marca' => $row['nombre_marca'],
            'precio' => floatval($row['precio']),
            'stock' => intval($row['stock']),
            'stock_minimo' => intval($row['stock_minimo']),
            'stock_maximo' => intval($row['stock_maximo']),
            'garantia' => $row['clausula_garantia'],
            'imagen' => \getImageUrl($imagen)
        ];

        if ($incluirCaracteristicas) {
            $nombreCategoria = $row['nombre_categoria'] ?? '';
            $tablaCategoria = 'cat_' . strtolower(str_replace(' ', '_', $nombreCategoria));
            if (preg_match('/^cat_[a-z0-9_]+$/', $tablaCategoria)) {
                $stmt = $pdo->prepare(
                    "SELECT * FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = :tabla"
                );
                $stmt->execute([':tabla' => $tablaCategoria]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $stmt = $pdo->prepare("SELECT * FROM `$tablaCategoria` WHERE id_producto = :id LIMIT 1");
                    $stmt->bindValue(':id', $row['id_producto'], PDO::PARAM_INT);
                    $stmt->execute();
                    $caracteristicas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    unset($caracteristicas['id'], $caracteristicas['id_producto']);
                    $producto['caracteristicas'] = $caracteristicas;
                } else {
                    $producto['caracteristicas'] = [];
                }
            } else {
                $producto['caracteristicas'] = [];
            }
        }

        return $producto;
    }
    
    private function api_obtenerTodosProductos($data) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($data) {
            $sql = "SELECT p.*, c.nombre_categoria, mo.nombre_modelo,
                           mo.id_marca, m.nombre_marca
                    FROM tbl_productos p 
                    LEFT JOIN tbl_categoria c ON p.id_categoria = c.id_categoria 
                    LEFT JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    LEFT JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.estado = 'habilitado'
                    ORDER BY p.nombre_producto ASC";
            
            $stmt = $pdo->query($sql);
            $productos = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productos[] = $this->formatearProductoMovil($pdo, $row);
            }
            
            $combos = $this->obtenerCombosDisponiblesMovil();
            
            return [
                'productos' => $productos,
                'combos' => $combos,
                'total' => count($productos) + count($combos)
            ];
        });
    }
    
    /**
     * Obtiene un producto por ID para la API móvil
     * @param array $data Debe contener 'id' del producto
     * @return array Producto formateado para la app móvil
     */
    public function apiObtenerProductoPorId($data) {
        return $this->api_obtenerProductoPorId($data);
    }
    
    private function api_obtenerProductoPorId($data) {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        
        if ($id <= 0) {
            throw new Exception('ID de producto inválido');
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id) {
            $sql = "SELECT p.*, c.nombre_categoria, mo.nombre_modelo,
                           mo.id_marca, m.nombre_marca
                    FROM tbl_productos p 
                    LEFT JOIN tbl_categoria c ON p.id_categoria = c.id_categoria 
                    LEFT JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    LEFT JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.id_producto = :id AND p.estado = 'habilitado'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                throw new Exception('Producto no encontrado');
            }
            
            return $this->formatearProductoMovil($pdo, $row, true);
        });
    }
    
    /**
     * Obtiene productos por categoría para la API móvil
     * @param array $data Debe contener 'categoria' ID
     * @return array Productos de la categoría formateados
     */
    public function apiObtenerProductosPorCategoria($data) {
        return $this->api_obtenerProductosPorCategoria($data);
    }
    
    private function api_obtenerProductosPorCategoria($data) {
        $categoria = isset($data['categoria']) ? intval($data['categoria']) : 0;
        
        if ($categoria <= 0) {
            throw new Exception('ID de categoría inválido');
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($categoria) {
            $sql = "SELECT p.*, c.nombre_categoria, mo.nombre_modelo,
                           mo.id_marca, m.nombre_marca
                    FROM tbl_productos p 
                    LEFT JOIN tbl_categoria c ON p.id_categoria = c.id_categoria 
                    LEFT JOIN tbl_modelos mo ON p.id_modelo = mo.id_modelo
                    LEFT JOIN tbl_marcas m ON mo.id_marca = m.id_marca
                    WHERE p.id_categoria = :categoria AND p.estado = 'habilitado'
                    ORDER BY p.nombre_producto ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':categoria', $categoria, PDO::PARAM_INT);
            $stmt->execute();
            
            $productos = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productos[] = $this->formatearProductoMovil($pdo, $row);
            }
            
            return [
                'productos' => $productos,
                'total' => count($productos),
                'categoria_id' => $categoria
            ];
        });
    }
    
    /**
     * Obtiene producto detallado con características dinámicas para la API móvil
     * @param array $data Debe contener 'id' del producto
     * @return array Producto con características
     */
    public function obtenerProductoDetalladoApi($data) {
        return $this->api_obtenerProductoDetallado($data);
    }
    
    private function api_obtenerProductoDetallado($data) {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        
        if ($id <= 0) {
            throw new Exception('ID de producto inválido');
        }
        
        $productoBase = $this->apiObtenerProductoPorId(['id' => $id]);
        $caracteristicas = $productoBase['caracteristicas'] ?? [];
        
        return [
            'producto' => $productoBase,
            'caracteristicas' => $caracteristicas
        ];
    }
    
}