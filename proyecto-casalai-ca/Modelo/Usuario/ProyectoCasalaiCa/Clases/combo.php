<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;


class Combos extends BD {
    private $conex;
    //private $productos;
    
    // Constantes para validaciones
    const MAX_NOMBRE_COMBO = 100;
    const MAX_DESCRIPCION = 500;
    const MAX_CANTIDAD_PRODUCTO = 999;
    const MAX_PRECIO = 999999.99;
    const ESTADOS_PERMITIDOS = ['activo', 'inactivo', 'pendiente'];
    const ACCIONES_PERMITIDAS = ['consultar', 'agregar', 'crear', 'modificar', 'eliminar', 'cambiar_estado', 'filtrar', 'buscar'];

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

    public function obtenerCombos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_combo, id_producto, cantidad FROM tbl_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function agregarCombo($id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_producto, $cantidad) {
            $sql = "INSERT INTO tbl_combo (id_producto, cantidad) VALUES (:id_producto, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        });
    }

    public function eliminarCombo($id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            $sql = "DELETE FROM tbl_combo WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            return $stmt->execute();
        });
    }

    public function modificarCombo($id_combo, $id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo, $id_producto, $cantidad) {
            $sql = "UPDATE tbl_combo SET id_producto = :id_producto, cantidad = :cantidad WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        });
    }

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para consultar combo
     */
    private function validarConsultarCombo($datos) {
        $errores = [];
        
        // Validar ID del combo (opcional)
        if (isset($datos['id_combo'])) {
            if (!is_numeric($datos['id_combo']) || $datos['id_combo'] <= 0) {
                $errores['id_combo'] = 'El ID del combo debe ser un número positivo';
            }
        }
        
        // Validar término de búsqueda (opcional)
        if (isset($datos['termino'])) {
            $termino = trim($datos['termino']);
            if (strlen($termino) > 100) {
                $errores['termino'] = 'El término de búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        // Validar estado (opcional)
        if (isset($datos['estado'])) {
            if (!in_array($datos['estado'], self::ESTADOS_PERMITIDOS)) {
                $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para crear combo
     */
    private function validarCrearCombo($datos) {
        $errores = [];
        
        // Validar nombre del combo
        if (!isset($datos['nombre_combo'])) {
            $errores['nombre_combo'] = 'El nombre del combo es obligatorio';
        } else {
            $nombre = trim($datos['nombre_combo']);
            if (empty($nombre)) {
                $errores['nombre_combo'] = 'El nombre del combo no puede estar vacío';
            } elseif (strlen($nombre) > self::MAX_NOMBRE_COMBO) {
                $errores['nombre_combo'] = 'El nombre del combo no debe exceder los ' . self::MAX_NOMBRE_COMBO . ' caracteres';
            }
        }
        
        // Validar descripción (opcional)
        if (isset($datos['descripcion'])) {
            $descripcion = trim($datos['descripcion']);
            if (strlen($descripcion) > self::MAX_DESCRIPCION) {
                $errores['descripcion'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
        }
        
        // Validar productos
        if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
            $errores['productos'] = 'Debe especificar al menos un producto para el combo';
        } else {
            foreach ($datos['productos'] as $index => $producto) {
                if (!is_array($producto)) {
                    $errores["productos_$index"] = 'El producto en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar ID del producto
                if (!isset($producto['id']) || !is_numeric($producto['id']) || $producto['id'] <= 0) {
                    $errores["productos_{$index}_id"] = 'El ID del producto en la posición ' . $index . ' debe ser un número positivo';
                }
                
                // Validar cantidad
                if (!isset($producto['cantidad']) || !is_numeric($producto['cantidad']) || $producto['cantidad'] <= 0) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' debe ser un número positivo';
                } elseif ($producto['cantidad'] > self::MAX_CANTIDAD_PRODUCTO) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para modificar combo
     */
    private function validarModificarCombo($datos) {
        $errores = [];
        
        // Validar ID del combo
        if (!isset($datos['id_combo']) || !is_numeric($datos['id_combo']) || $datos['id_combo'] <= 0) {
            $errores['id_combo'] = 'El ID del combo debe ser un número positivo';
        }
        
        // Validar nombre del combo
        if (!isset($datos['nombre_combo'])) {
            $errores['nombre_combo'] = 'El nombre del combo es obligatorio';
        } else {
            $nombre = trim($datos['nombre_combo']);
            if (empty($nombre)) {
                $errores['nombre_combo'] = 'El nombre del combo no puede estar vacío';
            } elseif (strlen($nombre) > self::MAX_NOMBRE_COMBO) {
                $errores['nombre_combo'] = 'El nombre del combo no debe exceder los ' . self::MAX_NOMBRE_COMBO . ' caracteres';
            }
        }
        
        // Validar descripción (opcional)
        if (isset($datos['descripcion'])) {
            $descripcion = trim($datos['descripcion']);
            if (strlen($descripcion) > self::MAX_DESCRIPCION) {
                $errores['descripcion'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
        }
        
        // Validar productos
        if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
            $errores['productos'] = 'Debe especificar al menos un producto para el combo';
        } else {
            foreach ($datos['productos'] as $index => $producto) {
                if (!is_array($producto)) {
                    $errores["productos_$index"] = 'El producto en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar ID del producto
                if (!isset($producto['id']) || !is_numeric($producto['id']) || $producto['id'] <= 0) {
                    $errores["productos_{$index}_id"] = 'El ID del producto en la posición ' . $index . ' debe ser un número positivo';
                }
                
                // Validar cantidad
                if (!isset($producto['cantidad']) || !is_numeric($producto['cantidad']) || $producto['cantidad'] <= 0) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' debe ser un número positivo';
                } elseif ($producto['cantidad'] > self::MAX_CANTIDAD_PRODUCTO) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para eliminar combo
     */
    private function validarEliminarCombo($datos) {
        $errores = [];
        
        // Validar ID del combo
        if (!isset($datos['id_combo']) || !is_numeric($datos['id_combo']) || $datos['id_combo'] <= 0) {
            $errores['id_combo'] = 'El ID del combo debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para cambiar estado de combo
     */
    private function validarCambiarEstadoCombo($datos) {
        $errores = [];
        
        // Validar ID del combo
        if (!isset($datos['id_combo']) || !is_numeric($datos['id_combo']) || $datos['id_combo'] <= 0) {
            $errores['id_combo'] = 'El ID del combo debe ser un número positivo';
        }
        
        // Validar estado (opcional)
        if (isset($datos['estado'])) {
            if (!in_array($datos['estado'], self::ESTADOS_PERMITIDOS)) {
                $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar combo (método público)
     */
    public function validarConsultar($datos) {
        return $this->validarConsultarCombo($datos);
    }
    
    /**
     * Valida los datos para crear combo (método público)
     */
    public function validarCrear($datos) {
        return $this->validarCrearCombo($datos);
    }
    
    /**
     * Valida los datos para modificar combo (método público)
     */
    public function validarModificar($datos) {
        return $this->validarModificarCombo($datos);
    }
    
    /**
     * Valida los datos para eliminar combo (método público)
     */
    public function validarEliminar($datos) {
        return $this->validarEliminarCombo($datos);
    }
    
    /**
     * Valida los datos para cambiar estado (método público)
     */
    public function validarCambiarEstado($datos) {
        return $this->validarCambiarEstadoCombo($datos);
    }
    
    /**
     * Verifica si un combo existe y está activo
     */
    private function verificarComboExistente($idCombo) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_combo, nombre_combo, activo FROM combos WHERE id_combo = :id_combo";
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