<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Catalogo extends BD {
    private $tablaCombo = 'tbl_combo';
    private $cantidad;
    private $id_producto;
    
    const MAX_NOMBRE_COMBO = 100;
    const MAX_DESCRIPCION = 500;
    const MAX_CANTIDAD_PRODUCTO = 999;
    const MAX_PRECIO = 999999.99;
    const ESTADOS_PERMITIDOS = ['activo', 'inactivo', 'pendiente'];
    const ACCIONES_PERMITIDAS = ['consultar', 'agregar', 'crear', 'modificar', 'eliminar', 'cambiar_estado', 'filtrar', 'buscar'];

    public function setIdProducto($id_producto){
        $this->id_producto = $id_producto;
    }

    public function setCantidad($cantidad){
        $this->cantidad = $cantidad;
    }

    public function __construct($tipo = 'P') {
    }
    
    public function getConexion() {
        return $this->pdo;
    }

    protected function ejecutarConConexionSegura($operation) {
        try {
            parent::__construct('P'); 
            $pdo = parent::getConexion(); 

            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO no es válida o es nula.");
            }

            $pdo->beginTransaction();
            $resultado = $operation($pdo);
            $pdo->commit();
            
            return $resultado;
        } catch (\Exception $e) {
            $pdo = parent::getConexion();
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            $this->cerrar();
        }
    }

    public function consultarCatalogo($busqueda = '', $categoria = '', $marca = '', $tipo_item = '') {
        return $this->ejecutarConConexionSegura(function($pdo) use ($busqueda, $categoria, $marca, $tipo_item) {
            try {
                $sql = "SELECT 
                        tipo_item,
                        id,
                        nombre,
                        descripcion,
                        categoria,
                        marca,
                        precio,
                        stock,
                        estado,
                        imagen
                    FROM vw_catalogo
                    WHERE CAST(estado AS UNSIGNED) = 1";

                $params = [];

                if (!empty($busqueda)) {
                    $sql .= " AND (nombre LIKE :busqueda OR descripcion LIKE :busqueda OR marca LIKE :busqueda)";
                    $params[':busqueda'] = '%' . trim($busqueda) . '%';
                }

                if (!empty($categoria)) {
                    $sql .= " AND categoria = :categoria";
                    $params[':categoria'] = trim($categoria);
                }

                if (!empty($marca)) {
                    $sql .= " AND marca = :marca";
                    $params[':marca'] = trim($marca);
                }

                if (!empty($tipo_item)) {
                    $sql .= " AND CAST(tipo_item AS BINARY) = CAST(:tipo_item AS BINARY)";
                    $params[':tipo_item'] = trim($tipo_item);
                }

                $sql .= " ORDER BY tipo_item DESC, nombre ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                error_log("Error en Catalogo::consultarCatalogo: " . $e->getMessage());
                return [];
            }
        });
    }

    public function obtenerItemPorIdYTipo($id, $tipo_item) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $tipo_item) {
            try {
                $sql = "SELECT 
                        tipo_item,
                        id,
                        nombre,
                        descripcion,
                        categoria,
                        marca,
                        precio,
                        stock,
                        estado,
                        imagen
                    FROM vw_catalogo
                    WHERE id = :id 
                    AND CAST(tipo_item AS BINARY) = CAST(:tipo_item AS BINARY)
                    AND CAST(estado AS UNSIGNED) = 1";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':tipo_item' => $tipo_item
                ]);

                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                error_log("Error en Catalogo::obtenerItemPorIdYTipo: " . $e->getMessage());
                return false;
            }
        });
    }

    public function obtenerDetalleCombo($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            try {
                $sql = "SELECT 
                            cd.id_combo,
                            p.id_producto,
                            p.nombre_producto,
                            cd.cantidad,
                            c.descuento,
                            ROUND(p.precio * (1 - COALESCE(c.descuento, 0) / 100), 2) AS precio_unitario,
                            (ROUND(p.precio * (1 - COALESCE(c.descuento, 0) / 100), 2) * cd.cantidad) AS subtotal,
                            p.stock AS stock_disponible,
                            p.imagen
                        FROM tbl_combo_detalle cd
                        INNER JOIN tbl_combo c ON c.id_combo = cd.id_combo
                        INNER JOIN tbl_productos p ON cd.id_producto = p.id_producto
                        WHERE cd.id_combo = :id_combo";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id_combo' => $id_combo]);

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                error_log("Error en Catalogo::obtenerDetalleCombo: " . $e->getMessage());
                return [];
            }
        });
    }

    public function obtenerCategoriasCatalogo() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT DISTINCT categoria FROM vw_catalogo WHERE estado = 'habilitado' ORDER BY categoria ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Throwable $e) {
                error_log("Error en Catalogo::obtenerCategoriasCatalogo: " . $e->getMessage());
                return [];
            }
        });
    }

    public function obtenerMarcasCatalogo() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT DISTINCT marca FROM vw_catalogo
                    WHERE CAST(estado AS UNSIGNED) = 1
                    AND CAST(tipo_item AS BINARY) = CAST('producto' AS BINARY)
                    AND marca IS NOT NULL
                        AND CHAR_LENGTH(marca) > 0
                    ORDER BY marca ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Throwable $e) {
                error_log("Error en Catalogo::obtenerMarcasCatalogo: " . $e->getMessage());
                return [];
            }
        });
    }

    public function insertarCombo(){
        return $this->i_insertarCombo();
    }
    private function i_insertarCombo(){
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO {$this->tablaCombo} (id_producto, cantidad)
                    VALUES (:id_producto, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_producto', $this->id_producto);
            $stmt->bindParam(':cantidad', $this->cantidad);
            return $stmt->execute();
        });
    }

    public function eliminarCombo($id_combo){
        return $this->d_eliminarCombo($id_combo);
    }
    private function d_eliminarCombo($id_combo){
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            $sql = "DELETE FROM {$this->tablaCombo} WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            return $stmt->execute();
        });
    }

    public function crearNuevoCombo() {
        return $this->c_crearNuevoCombo();
    }
    private function c_crearNuevoCombo() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_combo (fecha_creacion) VALUES (NOW())";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute()) {
                return $pdo->lastInsertId();
            }
            return false;
        });
    }

    public function insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        return $this->i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad);
    }
    private function i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo, $id_producto, $cantidad) {
            $sql = "INSERT INTO {$this->tablaCombo} (id_combo, id_producto, cantidad) VALUES (:id_combo, :id_producto, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        });
    }

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para consultar productos o combos
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID de marca (opcional)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] <= 0) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número positivo';
            }
        }
        
        // Validar término de búsqueda (opcional)
        if (isset($datos['termino'])) {
            $termino = trim($datos['termino']);
            if (strlen($termino) > 100) {
                $errores['termino'] = 'El término de búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para agregar al carrito
     */
    private function validarDatosAgregarCarrito($datos) {
        $errores = [];
        
        // Validar ID del producto
        if (!isset($datos['id_producto']) || !is_numeric($datos['id_producto']) || $datos['id_producto'] <= 0) {
            $errores['id_producto'] = 'El ID del producto debe ser un número positivo';
        }
        
        // Validar cantidad
        if (!isset($datos['cantidad'])) {
            $errores['cantidad'] = 'La cantidad es obligatoria';
        } else {
            $cantidad = (int)$datos['cantidad'];
            if ($cantidad <= 0) {
                $errores['cantidad'] = 'La cantidad debe ser un número positivo';
            } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                $errores['cantidad'] = 'La cantidad no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
            }
        }
        
        // Validar ID del combo (opcional)
        if (isset($datos['id_combo'])) {
            if (!is_numeric($datos['id_combo']) || $datos['id_combo'] <= 0) {
                $errores['id_combo'] = 'El ID del combo debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para detallar producto
     */
    private function validarDetallarProducto($datos) {
        $errores = [];
        
        // Validar ID del producto
        if (!isset($datos['id_producto']) || !is_numeric($datos['id_producto']) || $datos['id_producto'] <= 0) {
            $errores['id_producto'] = 'El ID del producto debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para filtrar por marca
     */
    private function validarFiltrarMarca($datos) {
        $errores = [];
        
        // Validar ID de marca (opcional)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] <= 0) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para buscador
     */
    private function validarBuscador($datos) {
        $errores = [];
        
        // Validar término de búsqueda
        if (!isset($datos['termino'])) {
            $errores['termino'] = 'El término de búsqueda es obligatorio';
        } else {
            $termino = trim($datos['termino']);
            if (empty($termino)) {
                $errores['termino'] = 'El término de búsqueda no puede estar vacío';
            } elseif (strlen($termino) > 100) {
                $errores['termino'] = 'El término de búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        // Validar tipo de búsqueda (opcional)
        if (isset($datos['tipo'])) {
            $tipos_permitidos = ['producto', 'combo', 'todos'];
            if (!in_array($datos['tipo'], $tipos_permitidos)) {
                $errores['tipo'] = 'El tipo de búsqueda debe ser uno de: ' . implode(', ', $tipos_permitidos);
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarCatalogo($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para agregar al carrito (método público)
     */
    public function validarAgregarCarrito($datos) {
        return $this->validarDatosAgregarCarrito($datos);
    }
    
    /**
     * Valida los datos para detallar producto (método público)
     */
    public function validarDetallar($datos) {
        return $this->validarDetallarProducto($datos);
    }
    
    /**
     * Valida los datos para filtrar por marca (método público)
     */
    public function validarFiltrar($datos) {
        return $this->validarFiltrarMarca($datos);
    }
    
    /**
     * Valida los datos para buscador (método público)
     */
    public function validarBuscar($datos) {
        return $this->validarBuscador($datos);
    }
    
    /**
     * Verifica si un producto existe y está activo
     */
    private function verificarProductoExistente($idProducto) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_producto, nombre_producto, stock, estado FROM tbl_productos WHERE id_producto = :id_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                return ['existe' => false, 'mensaje' => 'El producto no existe'];
            }
            
            if ($producto['stock'] <= 0) {
                return ['existe' => false, 'mensaje' => 'El producto no tiene stock disponible'];
            }
            
            if ($producto['estado'] != 1) {
                return ['existe' => false, 'mensaje' => 'El producto no está disponible'];
            }
            
            return ['existe' => true, 'producto' => $producto];
        });
    }
    
    /**
     * Verifica si un combo existe y está activo
     */
    private function verificarComboExistente($idCombo) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_combo, nombre_combo, activo FROM tbl_combo WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_combo', $idCombo, PDO::PARAM_INT);
            $stmt->execute();
            $combo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$combo) {
                return ['existe' => false, 'mensaje' => 'El combo no existe'];
            }
            
            if ($combo['activo'] != 1) {
                return ['existe' => false, 'mensaje' => 'El combo no está activo'];
            }
            
            return ['existe' => true, 'combo' => $combo];
        });
    }
}
?>