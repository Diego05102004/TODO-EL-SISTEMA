<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Categoria extends BD
{
    private $id_categoria;
    private $nombre_categoria;
    
    // Constantes para validaciones
    const MAX_NOMBRE_CATEGORIA = 100;
    const MIN_NOMBRE_CATEGORIA = 2;
    const MAX_NOMBRE_CARACTERISTICA = 100;
    const MIN_NOMBRE_CARACTERISTICA = 2;
    const MAX_VALOR_STRING = 1000;
    const MAX_VALOR_NUMERICO = 999999;
    const MAX_VALOR_DECIMAL = 999999.99;
    const MAX_LONGITUD_CAMPO = 255;
    const TIPOS_CARACTERISTICA_PERMITIDOS = ['int', 'float', 'string'];

    public function getIdCategoria()
    {
        return $this->id_categoria;
    }

    public function setIdCategoria($id_categoria)
    {
        $this->id_categoria = $id_categoria;
    }

    public function getNombreCategoria()
    {
        return $this->nombre_categoria;
    }

    public function setNombreCategoria($nombre_categoria)
    {
        $this->nombre_categoria = $nombre_categoria;
    }

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

            // Solo iniciar transacción si no hay una activa
            $transactionStarted = false;
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $transactionStarted = true;
            }
            
            $resultado = $operation($pdo);
            
            // Solo hacer commit si iniciamos la transacción
            if ($transactionStarted && $pdo->inTransaction()) {
                $pdo->commit();
            }
            
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

    // ==================== VALIDACIONES DE BACKEND ====================
    
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor);
                
                $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
                
                $valor = addslashes($valor);
                
                $datos_sanitizados[$clave] = $valor;
            } else {
                $datos_sanitizados[$clave] = $valor;
            }
        }
        
        return $datos_sanitizados;
    }
    
    private function validarEsquema($datos, $operacion = 'registrar') {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['esquema'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        if ($operacion === 'registrar') {
            if (!isset($datos['nombre_categoria']) || $datos['nombre_categoria'] === '' || $datos['nombre_categoria'] === null) {
                $errores['nombre_categoria'] = 'El nombre de la categoría es obligatorio';
            }
        } elseif ($operacion === 'modificar') {
            if (!isset($datos['id_categoria']) || $datos['id_categoria'] === '' || $datos['id_categoria'] === null) {
                $errores['id_categoria'] = 'El ID de la categoría es obligatorio para modificar';
            }
            
            if (!isset($datos['nombre_categoria']) || $datos['nombre_categoria'] === '' || $datos['nombre_categoria'] === null) {
                $errores['nombre_categoria'] = 'El nombre de la categoría es obligatorio';
            }
        }
        
        return $errores;
    }
    
    private function validarFormato($datos) {
        $errores = [];
        
        if (isset($datos['nombre_categoria'])) {
            $nombre = trim($datos['nombre_categoria']);
            if (mb_strlen($nombre) < self::MIN_NOMBRE_CATEGORIA || mb_strlen($nombre) > self::MAX_NOMBRE_CATEGORIA) {
                $errores['nombre_categoria'] = 'El nombre de la categoría debe tener entre ' . self::MIN_NOMBRE_CATEGORIA . ' y ' . self::MAX_NOMBRE_CATEGORIA . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre_categoria'] = 'El nombre de la categoría solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar características si existen
        if (isset($datos['caracteristicas']) && is_array($datos['caracteristicas'])) {
            foreach ($datos['caracteristicas'] as $index => $caracteristica) {
                if (!is_array($caracteristica)) {
                    $errores["caracteristica_{$index}"] = 'La característica en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar nombre de la característica
                $nombre_carac = trim($caracteristica['nombre'] ?? '');
                if (empty($nombre_carac)) {
                    $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica es obligatorio';
                } elseif (mb_strlen($nombre_carac) < self::MIN_NOMBRE_CARACTERISTICA || mb_strlen($nombre_carac) > self::MAX_NOMBRE_CARACTERISTICA) {
                    $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica debe tener entre ' . self::MIN_NOMBRE_CARACTERISTICA . ' y ' . self::MAX_NOMBRE_CARACTERISTICA . ' caracteres';
                } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_carac)) {
                    $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica solo puede contener letras, números, espacios y caracteres especiales comunes';
                }

                // Validar tipo
                $tipo = strtolower(trim($caracteristica['tipo'] ?? ''));
                if (!in_array($tipo, self::TIPOS_CARACTERISTICA_PERMITIDOS)) {
                    $errores["caracteristica_{$index}_tipo"] = 'El tipo de característica debe ser: ' . implode(', ', self::TIPOS_CARACTERISTICA_PERMITIDOS);
                }

  

                // Validar longitud máxima (string)
                if ($tipo === 'string' && isset($caracteristica['max'])) {
                    $max = (int)$caracteristica['max'];
                    if ($max <= 0 || $max > self::MAX_LONGITUD_CAMPO) {
                        $errores["caracteristica_{$index}_max"] = 'La longitud máxima debe estar entre 1 y ' . self::MAX_LONGITUD_CAMPO . ' caracteres';
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarFiltros($filtros) {
        $errores = [];
        
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe estar entre 1 y 100 registros';
            }
        }
        
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número positivo';
            }
        }
        
        if (isset($filtros['busqueda']) && is_string($filtros['busqueda'])) {
            $busqueda = trim($filtros['busqueda']);
            if (mb_strlen($busqueda) > 100) {
                $errores['busqueda'] = 'La búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        return $errores;
    }
    
    public function validarId($id_categoria) {
        $errores = [];
        
        if ($id_categoria === null || $id_categoria === '') {
            $errores['id_categoria'] = 'El ID de la categoría es obligatorio';
        } elseif (!is_numeric($id_categoria) || (int)$id_categoria <= 0) {
            $errores['id_categoria'] = 'El ID de la categoría debe ser un número positivo';
        }
        
        return $errores;
    }
    
    private function obtenerCategoriaPorIdSimple($id_categoria) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria) {
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria WHERE id_categoria = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_categoria]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }
    
    private function validarIntegridadReferencial($id_categoria, $pdo) {
        $errores = [];
        
        // Verificar si hay productos asociados a esta categoría
        $this->nombre_categoria = null;
        $categoria_info = $this->o_categoriaPorId($id_categoria, $pdo);
        if ($categoria_info) {
            $this->nombre_categoria = $categoria_info['nombre_categoria'];
            $tabla = $this->generarNombreTabla();
            
            // Verificar si la tabla existe
            $tableExists = $pdo->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
            
            if ($tableExists) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $productCount = (int)$result['total'];
                
                if ($productCount > 0) {
                    $errores['integridad'] = "No se puede eliminar la categoría porque tiene {$productCount} producto(s) asociado(s)";
                }
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrar($datos) {
        $datos = $this->sanitizarDatos($datos);
        
        $errores = $this->validarEsquema($datos, 'registrar');
        if (!empty($errores)) {
            return $errores;
        }
        
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        if ($this->existeNombreCategoria($datos['nombre_categoria'])) {
            $errores['nombre_categoria'] = 'El nombre de la categoría ya existe';
        }
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre_categoria',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
    }
    
    public function validarModificar($datos) {
        $datos = $this->sanitizarDatos($datos);
        
        $errores = $this->validarEsquema($datos, 'modificar');
        if (!empty($errores)) {
            return $errores;
        }
        
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        $categoria_existente = $this->obtenerCategoriaPorIdSimple($datos['id_categoria']);
        if (!$categoria_existente) {
            $errores['existencia'] = 'La categoría que intenta modificar no existe';
            return $errores;
        }
        
        if (isset($datos['nombre_categoria']) && 
            $this->existeNombreCategoria($datos['nombre_categoria'], $datos['id_categoria'])) {
            $errores['nombre_categoria'] = 'El nombre de la categoría ya existe';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id_categoria) {
        $errores = $this->validarId($id_categoria);
        if (!empty($errores)) {
            return $errores;
        }
        
        $categoria = $this->obtenerCategoriaPorIdSimple($id_categoria);
        if (!$categoria) {
            $errores['existencia'] = 'La categoría que intenta eliminar no existe';
            return $errores;
        }
        
        // Verificar integridad referencial sin crear nueva transacción
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria) {
            $errores = [];
            
            // Obtener información de la categoría
            $sql = "SELECT nombre_categoria FROM tbl_categoria WHERE id_categoria = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_categoria]);
            $categoria_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categoria_info) {
                $this->nombre_categoria = $categoria_info['nombre_categoria'];
                $tabla = $this->generarNombreTabla();
                
                // Verificar si la tabla existe
                $tableExists = $pdo->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
                
                if ($tableExists) {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $productCount = (int)$result['total'];
                    
                    if ($productCount > 0) {
                        $errores['integridad'] = "No se puede eliminar la categoría porque tiene {$productCount} producto(s) asociado(s)";
                    }
                }
            }
            
            return $errores;
        });
    }
    
    public function existeNombreCategoria($nombre, $excluirId = null)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre, $excluirId) {
            $sql = "SELECT COUNT(*) FROM tbl_categoria WHERE nombre_categoria = :nombre";
            $params = [':nombre' => $nombre];
            
            if ($excluirId !== null) {
                $sql .= " AND id_categoria != :id";
                $params[':id'] = $excluirId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            return $count > 0;
        });
    }

    private function generarNombreTabla()
    {
        return 'cat_' . strtolower(str_replace(' ', '_', $this->nombre_categoria));
    }

    public function registrarCategoria($caracteristicas, $id_usuario_auditor)
    {
        return $this->r_registrarCategoria($caracteristicas, $id_usuario_auditor);
    }

    private function r_registrarCategoria($caracteristicas, $id_usuario_auditor)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($caracteristicas, $id_usuario_auditor) {
            // Convertir array de características a JSON
            $caracteristicas_json = json_encode($caracteristicas);
            
            // Llamar al procedimiento almacenado
            $sql = "CALL sp_registrar_categoria(:nombre_categoria, :caracteristicas, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
            $stmt->bindParam(':caracteristicas', $caracteristicas_json);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            
            return $resultado;
        });
    }

    private function r_Categoria()
    {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_categoria (nombre_categoria) VALUES (:nombre_categoria)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
            return $stmt->execute();
        });
    }

    private function crearTablaCategoria($caracteristicas)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($caracteristicas) {
            $nombreTabla = $this->generarNombreTabla();
            $sql = "CREATE TABLE IF NOT EXISTS `$nombreTabla` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_producto INT NOT NULL,
        ";

            foreach ($caracteristicas as $carac) {
                $campo = strtolower(str_replace(' ', '_', $carac['nombre']));
                switch ($carac['tipo']) {
                    case 'int':
                        $sql .= "`$campo` INT,";
                        break;
                    case 'float':
                        $sql .= "`$campo` FLOAT,";
                        break;
                    case 'string':
                        $max = (int) ($carac['max'] ?? 255);
                        $sql .= "`$campo` VARCHAR($max),";
                        break;
                }
            }
            // Elimina la última coma y cierra el paréntesis
            $sql = rtrim($sql, ',') . ",
            FOREIGN KEY (id_producto) REFERENCES tbl_productos(id_producto) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            return $pdo->exec($sql) !== false;
        });
    }

    public function modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas, $id_usuario_auditor)
    {
        return $this->m_modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas, $id_usuario_auditor);
    }

    private function m_modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas, $id_usuario_auditor)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria, $nuevo_nombre, $caracteristicas, $id_usuario_auditor) {
            // Convertir array de características a JSON
            $caracteristicas_json = json_encode($caracteristicas);
            
            // Llamar al procedimiento almacenado
            $sql = "CALL sp_modificar_categoria(:id_categoria, :nuevo_nombre, :caracteristicas, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_categoria', $id_categoria, \PDO::PARAM_INT);
            $stmt->bindParam(':nuevo_nombre', $nuevo_nombre);
            $stmt->bindParam(':caracteristicas', $caracteristicas_json);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            
            return $resultado;
        });
    }

    private function m_categoria($id_categoria)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria) {
            $sql = "UPDATE tbl_categoria SET nombre_categoria = :nombre_categoria WHERE id_categoria = :id_categoria";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            
            return $stmt->execute();
        });
    }

    public function eliminarCategoria($id_categoria, $id_usuario_auditor){
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria, $id_usuario_auditor) {
            try {
                // Obtener información de la categoría
                $categoriaInfo = $this->o_categoriaPorId($id_categoria, $pdo);
                if (!$categoriaInfo) {
                    return ['status' => 'error', 'mensaje' => 'Categoría no encontrada'];
                }

                $this->nombre_categoria = $categoriaInfo['nombre_categoria'];
                $nombre_tabla = $this->generarNombreTabla();

                // Llamar al procedimiento almacenado
                $stmt = $pdo->prepare("CALL sp_eliminar_categoria(:id_categoria, :nombre_tabla, :id_usuario_auditor)");
                $stmt->execute([
                    ':id_categoria' => $id_categoria,
                    ':nombre_tabla' => $nombre_tabla,
                    ':id_usuario_auditor' => $id_usuario_auditor
                ]);

                return ['status' => 'success'];

            } catch (\PDOException $e) {
                error_log('Error en eliminarCategoria: ' . $e->getMessage());
                
                // Manejar errores específicos del procedimiento almacenado
                if (strpos($e->getMessage(), 'Categoría no encontrada') !== false) {
                    return ['status' => 'error', 'mensaje' => 'Categoría no encontrada'];
                }
                
                if (strpos($e->getMessage(), 'No se puede eliminar porque tiene') !== false) {
                    return [
                        'status' => 'error',
                        'mensaje' => $e->getMessage()
                    ];
                }
                
                if (strpos($e->getMessage(), '45000') !== false) {
                    // Extraer el mensaje de error del procedimiento
                    $mensaje = preg_replace('/SQLSTATE\[45000\]: /', '', $e->getMessage());
                    return ['status' => 'error', 'mensaje' => $mensaje];
                }

                return [
                    'status' => 'error',
                    'mensaje' => 'Ocurrió un error inesperado al intentar eliminar la categoría.'
                ];
            }
        });
    }

    public function obtenerUltimoCategoria()
    {
        return $this->o_ultimoCategoria();
    }

    private function o_ultimoCategoria()
    {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_categoria ORDER BY id_categoria DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerCategoriaPorId($id_categoria)
    {
        return $this->o_categoriaPorId($id_categoria);
    }

    private function o_categoriaPorId($id_categoria, $conexion = null)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria, $conexion){
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria WHERE id_categoria = :id_categoria ORDER BY id_categoria DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$categoria) {
                return null;
            }

            // Obtener características de la tabla dinámica
            $this->nombre_categoria = $categoria['nombre_categoria'];
            $tabla = $this->generarNombreTabla();
            $caracteristicas = [];
            
            // Verificar si la tabla existe
            $tableExists = $pdo->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
            
            if ($tableExists) {
                $cols = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cols as $col) {
                    if (!in_array($col['Field'], ['id', 'id_producto'])) {
                        $tipo = 'string';
                        if (strpos($col['Type'], 'int') !== false) {
                            $tipo = 'int';
                        } elseif (strpos($col['Type'], 'float') !== false) {
                            $tipo = 'float';
                        }
                        $max = 255;
                        if (preg_match('/varchar\((\d+)\)/i', $col['Type'], $m)) {
                            $max = $m[1];
                        }
                        
                        $caracteristicas[] = [
                            'nombre' => str_replace('_', ' ', ucfirst($col['Field'])),
                            'tipo' => $tipo,
                            'max' => $max
                        ];
                    }
                }
            }
            
            $categoria['caracteristicas'] = $caracteristicas;
            return $categoria;
        });
    }

    public function consultarCategorias()
    {
        return $this->c_consultarCategorias();
    }

    private function c_consultarCategorias()
    {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria ORDER BY id_categoria DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}
?>
