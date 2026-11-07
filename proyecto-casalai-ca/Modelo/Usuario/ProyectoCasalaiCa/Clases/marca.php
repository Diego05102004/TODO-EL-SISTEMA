<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class marca extends BD {
    private $tablemarcas = 'tbl_marcas';
    private $nombre_marca;
    private $id_marca;
    
    const MAX_NOMBRE_MARCA = 25;
    const MIN_NOMBRE_MARCA = 2;
    const MAX_ID_MARCA = 999999999;
    const MIN_ID_MARCA = 1;

    public function getnombre_marca() {
        return $this->nombre_marca;
    }
    public function setnombre_marca($nombre_marca) {
        $this->nombre_marca = $nombre_marca;
    }
    
    public function getIdMarca() {
        return $this->id_marca;
    }
    public function setIdMarca($id_marca) {
        $this->id_marca = $id_marca;
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

    /**
     * @param callable $operation
     * @param bool $usarTransaccion
     * @return mixed
     */
    protected function ejecutarConConexionSegura($operation, $usarTransaccion = true) {
        try {
            parent::__construct('P'); 
            $pdo = parent::getConexion(); 

            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO no es válida o es nula.");
            }

            // SOLO iniciamos transacción si el flag es true
            if ($usarTransaccion) {
                $pdo->beginTransaction();
            }

            $resultado = $operation($pdo);

            // SOLO confirmamos transacción si el flag es true
            if ($usarTransaccion) {
                $pdo->commit();
            }
            
            return $resultado;
        } catch (\Exception $e) {
            $pdo = parent::getConexion();
            // SOLO hacemos rollback si correspondía usar transacción y sigue activa
            if ($usarTransaccion && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            $this->cerrar();
        }
    }

    private function existeNombreMarca($nombre_marca, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre_marca, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca = ?";
            $params = [$nombre_marca];
            if ($excluir_id !== null) {
                $sql .= " AND id_marca != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function registrarMarca($id_usuario_auditor) {
        return $this->r_marca($id_usuario_auditor);
    }
    private function r_marca($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor){
            $sql = "CALL sp_registrar_marca(:nombre_marca, :id_usuario_auditor)";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nombre_marca', $this->nombre_marca);
            $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            
            return $resultado;
        }, false);
    }

    public function obtenerUltimaMarca() {
        return $this->obtUltimaMarca(); 
    }
    private function obtUltimaMarca() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_marcas ORDER BY id_marca DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function tieneModelosAsociados($id_marca) {
        return $this->verificarModelosAsociados($id_marca);
    }
    private function verificarModelosAsociados($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE id_marca = :id_marca";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }

    public function obtenermarcasPorId($id_marca) {
        return $this->obtmarcasPorId($id_marca);
    }
    private function obtmarcasPorId($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_marca) {
            $query = "CALL sp_obtener_marca_por_id(:id_marca)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id_marca' => $id_marca]);
            $marca_obt = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $marca_obt;
        }, false);
    }

    public function modificarmarcas($id_marca, $id_usuario_auditor) {
        return $this->m_marcas($id_marca, $id_usuario_auditor);
    }
    private function m_marcas($id_marca, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_marca, $id_usuario_auditor) {
            
            $sql = "CALL sp_modificar_marca(
                :id_marca,
                :nombre_marca,
                :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca, \PDO::PARAM_INT);
            $stmt->bindParam(':nombre_marca', $this->nombre_marca);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $ok = $stmt->execute();
            $stmt->closeCursor();
            return $ok;
        }, false);
    }

    public function eliminarmarcas($id_marca, $id_usuario_auditor) {
        return $this->e_marcas($id_marca, $id_usuario_auditor);
    }
    private function e_marcas($id_marca, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_marca, $id_usuario_auditor) {
            $sql = "CALL sp_eliminar_marca(:id_marca, :id_usuario_auditor)";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id_marca', $id_marca, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        }, false);
    }

    public function getmarcas() {
        return $this->g_marcas();
    }
    private function g_marcas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $querymarcas = "CALL sp_consultar_marcas()";
            $stmtmarcas = $pdo->prepare($querymarcas);
            $stmtmarcas->execute();
            $marcas = $stmtmarcas->fetchAll(PDO::FETCH_ASSOC);
            $stmtmarcas->closeCursor();
            return $marcas;
        }, false);
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================

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
        
        if ($this->existeNombreMarca($datos['nombre_marca'])) {
            $errores['nombre_marca'] = 'El nombre de la marca ya existe';
        }
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre_marca',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
    }
    
    public function validarDetallar($id_marca) {
        $errores = $this->validarId($id_marca);
        if (!empty($errores)) {
            return $errores;
        }
        
        $marca = $this->obtenerMarcaPorId($id_marca);
        if (!$marca) {
            $errores['existencia'] = 'La marca solicitada no existe';
        }
        
        return $errores;
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
        
        $marca_existente = $this->obtenerMarcaPorId($datos['id_marca']);
        if (!$marca_existente) {
            $errores['existencia'] = 'La marca que intenta modificar no existe';
            return $errores;
        }
        
        if (isset($datos['nombre_marca']) && 
            $this->existeNombreMarca($datos['nombre_marca'], $datos['id_marca'])) {
            $errores['nombre_marca'] = 'El nombre de la marca ya existe';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id_marca) {
        $errores = $this->validarId($id_marca);
        if (!empty($errores)) {
            return $errores;
        }
        
        $marca = $this->obtenerMarcaPorId($id_marca);
        if (!$marca) {
            $errores['existencia'] = 'La marca que intenta eliminar no existe';
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_marca) {
            $errores = [];
            $errores_integridad = $this->validarIntegridadReferencial($id_marca, $pdo);
            $errores = array_merge($errores, $errores_integridad);
            return $errores;
        });
    }
    
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
            $errores['datos'] = 'Los datos proporcionados no son válidos';
            return $errores;
        }
        
        if ($operacion === 'registrar') {
            $campos_obligatorios = ['nombre_marca'];
            foreach ($campos_obligatorios as $campo) {
                if (!isset($datos[$campo])) {
                    $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
                }
            }
        }
        
        if ($operacion === 'modificar') {
            $campos_obligatorios = ['id_marca', 'nombre_marca'];
            foreach ($campos_obligatorios as $campo) {
                if (!isset($datos[$campo])) {
                    $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
                }
            }
        }
        
        return $errores;
    }
    
    private function validarFormato($datos) {
        $errores = [];
        
        if (isset($datos['nombre_marca'])) {
            $nombre_marca = trim($datos['nombre_marca']);
            if (empty($nombre_marca)) {
                $errores['nombre_marca'] = 'El nombre de la marca es obligatorio';
            } elseif (mb_strlen($nombre_marca) < self::MIN_NOMBRE_MARCA || mb_strlen($nombre_marca) > self::MAX_NOMBRE_MARCA) {
                $errores['nombre_marca'] = 'El nombre de la marca debe tener entre ' . self::MIN_NOMBRE_MARCA . ' y ' . self::MAX_NOMBRE_MARCA . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_marca)) {
                $errores['nombre_marca'] = 'El nombre de la marca solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        return $errores;
    }
    
    private function validarFiltros($filtros) {
        $errores = [];
        
        if (isset($filtros['pagina'])) {
            if (!is_numeric($filtros['pagina']) || $filtros['pagina'] < 1) {
                $errores['pagina'] = 'La página debe ser un número mayor a 0';
            }
        }
        
        if (isset($filtros['limite'])) {
            if (!is_numeric($filtros['limite']) || $filtros['limite'] < 1 || $filtros['limite'] > 100) {
                $errores['limite'] = 'El límite debe ser un número entre 1 y 100';
            }
        }
        
        if (isset($filtros['orden'])) {
            $ordenes_validos = ['id_marca', 'nombre_marca'];
            if (!in_array($filtros['orden'], $ordenes_validos)) {
                $errores['orden'] = 'El campo de orden no es válido';
            }
        }
        
        if (isset($filtros['direccion'])) {
            $direcciones_validas = ['ASC', 'DESC'];
            if (!in_array(strtoupper($filtros['direccion']), $direcciones_validas)) {
                $errores['direccion'] = 'La dirección de orden no es válida';
            }
        }
        
        return $errores;
    }
    
    private function validarId($id_marca) {
        $errores = [];
        
        if ($id_marca === null || $id_marca === '') {
            $errores['id_marca'] = 'El ID de la marca es obligatorio';
        } elseif (!is_numeric($id_marca) || $id_marca < self::MIN_ID_MARCA || $id_marca > self::MAX_ID_MARCA) {
            $errores['id_marca'] = 'El ID de la marca debe ser un número entre ' . self::MIN_ID_MARCA . ' y ' . self::MAX_ID_MARCA;
        }
        
        return $errores;
    }
    
    public function obtenerMarcaPorId($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_marca) {
            $query = "SELECT * FROM tbl_marcas WHERE id_marca = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id_marca]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }
    
    private function validarIntegridadReferencial($id_marca, $pdo) {
        $errores = [];
        
        $sql = "SELECT COUNT(*) as total FROM tbl_modelos WHERE id_marca = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_marca]);
        $modelos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($modelos > 0) {
            $errores['integridad'] = "No se puede eliminar la marca porque tiene {$modelos} modelo(s) asociado(s)";
        }
        
        return $errores;
    }
}
?>
