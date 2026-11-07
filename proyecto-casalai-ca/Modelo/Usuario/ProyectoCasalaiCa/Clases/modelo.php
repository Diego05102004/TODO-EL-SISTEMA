<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class modelo extends BD{
    private $id_marca;
    private $nombre_modelo;
    private $id_modelo;
    
    // Constantes para validaciones
    const MAX_NOMBRE_MODELO = 25;
    const MIN_NOMBRE_MODELO = 2;
    const MAX_ID_MODELO = 999999999;
    const MIN_ID_MODELO = 1;
    const MAX_ID_MARCA = 999999999;
    const MIN_ID_MARCA = 1;

    public function getnombre_modelo() {
        return $this->nombre_modelo;
    }

    public function setnombre_modelo($nombre_modelo) {
        $this->nombre_modelo = $nombre_modelo;
    }

    public function getid_marca() {
        return $this->id_marca;
    }

    public function setid_marca($id_marca) {
        $this->id_marca = $id_marca;
    }

    public function getIdModelo() {
        return $this->id_modelo;
    }
    public function setIdModelo($id_modelo) {
        $this->id_modelo = $id_modelo;
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

    public function existeNombreModelo($nombre_modelo, $id_marca, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre_modelo, $id_marca, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE nombre_modelo = ? AND id_marca = ?";
            $params = [$nombre_modelo, $id_marca];
            if ($excluir_id !== null) {
                $sql .= " AND id_modelo != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function registrarModelo($id_usuario_auditor) {
        return $this->r_modelos($id_usuario_auditor);
    }
    private function r_modelos($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor){
            $sql = "CALL sp_registrar_modelo(:nombre_modelo, :id_marca, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_modelo', $this->nombre_modelo);
            $stmt->bindParam(':id_marca', $this->id_marca);
            $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }

    public function obtenerUltimoModelo() {
        return $this->obtUltimoModelo();
    }
    private function obtUltimoModelo() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT m.id_modelo, m.nombre_modelo, m.id_marca, ma.nombre_marca
                FROM tbl_modelos m
                JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                ORDER BY m.id_modelo DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $modelo = $stmt->fetch(PDO::FETCH_ASSOC);
            return $modelo ? $modelo : null;
        });
    }

    public function obtenerModeloPorId($id_modelo) {
        return $this->obtModeloPorId($id_modelo);
    }
    private function obtModeloPorId($id_modelo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo){
            $sql = "CALL sp_obtener_modelo_por_id(:id_modelo)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_modelo' => $id_modelo]);
            $modelo_obt = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $modelo_obt;
        }, false);
    }

    public function getModelos() {
        return $this->g_modelos();
    }
    private function g_modelos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $querymodelos = "CALL sp_consultar_modelos()";
            $stmtmodelos = $pdo->prepare($querymodelos);
            $stmtmodelos->execute();
            $modelos = $stmtmodelos->fetchAll(PDO::FETCH_ASSOC);
            $stmtmodelos->closeCursor();
            return $modelos;
        }, false);
    }

    public function modificarModelo($id_modelo, $id_usuario_auditor) {
        return $this->m_modelo($id_modelo, $id_usuario_auditor);
    }
    private function m_modelo($id_modelo, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo, $id_usuario_auditor){

            $sql = "CALL sp_modificar_modelo(
                :id_modelo,
                :nombre_modelo,
                :id_marca,
                :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo, \PDO::PARAM_INT);
            $stmt->bindParam(':nombre_modelo', $this->nombre_modelo);
            $stmt->bindParam(':id_marca', $this->id_marca, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $ok = $stmt->execute();
            $stmt->closeCursor();
            return $ok;
        }, false);
    }

    public function eliminarModelo($id_modelo, $id_usuario_auditor) {
        return $this->e_modelo($id_modelo, $id_usuario_auditor);
    }
    private function e_modelo($id_modelo, $id_usuario_auditor) {
        $productosAsociados = $this->tieneProductosAsociados($id_modelo);
        
        if ($productosAsociados['tiene_productos']) {
            return [
                'status' => 'error', 
                'mensaje' => 'No se puede eliminar el modelo porque tiene productos asociados.',
                'productos' => $productosAsociados['productos'],
                'total_productos' => $productosAsociados['total']
            ];
        }

        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo, $id_usuario_auditor){
            $sql = "CALL sp_eliminar_modelo(:id_modelo, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id_modelo', $id_modelo, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        }, false);
    }

    public function tieneProductosAsociados($id_modelo) {
        return $this->tieneProductosAso($id_modelo);
    }
    private function tieneProductosAso($id_modelo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo){
            $sql = "SELECT COUNT(*) as total FROM tbl_productos WHERE id_modelo = :id_modelo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $resultado['total'];

            if ($count > 0) {
                // Obtener información de los productos asociados
                $sqlProductos = "SELECT nombre_producto, codigo_producto 
                            FROM tbl_productos 
                            WHERE id_modelo = :id_modelo 
                            ORDER BY nombre_producto 
                            LIMIT 5";
                $stmtProductos = $pdo->prepare($sqlProductos);
                $stmtProductos->bindParam(':id_modelo', $id_modelo, PDO::PARAM_INT);
                $stmtProductos->execute();
                $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
                
                return [
                    'tiene_productos' => true,
                    'productos' => $productos,
                    'total' => $count
                ];
            }

            return ['tiene_productos' => false];
        });
    }

    public function obtenerModeloConMarcaPorId($id_modelo) {
        return $this->obtModeloConMarcaPorId($id_modelo);
    }
    private function obtModeloConMarcaPorId($id_modelo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo){
            $sql = "SELECT m.id_modelo, m.nombre_modelo, m.id_marca, ma.nombre_marca
                FROM tbl_modelos m
                JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                WHERE m.id_modelo = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_modelo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function getmarcas() {
        return $this->g_marcas();
    }
    private function g_marcas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "CALL sp_consultar_marcas()";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
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
        
        $marca = $this->ejecutarConConexionSegura(function($pdo) use ($datos) {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE id_marca = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$datos['id_marca']]);
            return $stmt->fetchColumn() > 0;
        });
        
        if (!$marca) {
            $errores['id_marca'] = 'La marca seleccionada no existe';
        }
        
        if ($this->existeNombreModelo($datos['nombre_modelo'], $datos['id_marca'])) {
            $errores['nombre_modelo'] = '*Ya existe un modelo con este nombre para la marca seleccionada*';
        }
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre_modelo',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
    }
    
    public function validarDetallar($id_modelo) {
        $errores = $this->validarId($id_modelo);
        if (!empty($errores)) {
            return $errores;
        }
        
        $modelo = $this->obtenerModeloPorId($id_modelo);
        if (!$modelo) {
            $errores['existencia'] = 'El modelo solicitado no existe';
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
        
        $marca = $this->ejecutarConConexionSegura(function($pdo) use ($datos) {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE id_marca = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$datos['id_marca']]);
            return $stmt->fetchColumn() > 0;
        });
        
        if (!$marca) {
            $errores['id_marca'] = 'La marca seleccionada no existe';
        }
        
        $modelo_existente = $this->obtenerModeloPorId($datos['id_modelo']);
        if (!$modelo_existente) {
            $errores['existencia'] = 'El modelo que intenta modificar no existe';
            return $errores;
        }
        
        if (isset($datos['nombre_modelo']) && 
            $this->existeNombreModelo($datos['nombre_modelo'], $datos['id_marca'], $datos['id_modelo'])) {
            $errores['nombre_modelo'] = '*Ya existe un modelo con este nombre para la marca seleccionada*';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id_modelo) {
        $errores = $this->validarId($id_modelo);
        if (!empty($errores)) {
            return $errores;
        }
        
        $modelo = $this->obtenerModeloPorId($id_modelo);
        if (!$modelo) {
            $errores['existencia'] = 'El modelo que intenta eliminar no existe';
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_modelo) {
            $errores = [];
            $errores_integridad = $this->validarIntegridadReferencial($id_modelo, $pdo);
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
            $campos_obligatorios = ['nombre_modelo', 'id_marca'];
            foreach ($campos_obligatorios as $campo) {
                if (!isset($datos[$campo])) {
                    $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
                }
            }
        }
        
        if ($operacion === 'modificar') {
            $campos_obligatorios = ['id_modelo', 'nombre_modelo', 'id_marca'];
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
        
        if (isset($datos['nombre_modelo'])) {
            $nombre_modelo = trim($datos['nombre_modelo']);
            if (empty($nombre_modelo)) {
                $errores['nombre_modelo'] = 'El nombre del modelo es obligatorio';
            } elseif (mb_strlen($nombre_modelo) < self::MIN_NOMBRE_MODELO || mb_strlen($nombre_modelo) > self::MAX_NOMBRE_MODELO) {
                $errores['nombre_modelo'] = 'El nombre del modelo debe tener entre ' . self::MIN_NOMBRE_MODELO . ' y ' . self::MAX_NOMBRE_MODELO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_modelo)) {
                $errores['nombre_modelo'] = 'El nombre del modelo solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] < self::MIN_ID_MARCA || $datos['id_marca'] > self::MAX_ID_MARCA) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número entre ' . self::MIN_ID_MARCA . ' y ' . self::MAX_ID_MARCA;
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
            $ordenes_validos = ['id_modelo', 'nombre_modelo', 'id_marca'];
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
    
    private function validarId($id_modelo) {
        $errores = [];
        
        if ($id_modelo === null || $id_modelo === '') {
            $errores['id_modelo'] = 'El ID del modelo es obligatorio';
        } elseif (!is_numeric($id_modelo) || $id_modelo < self::MIN_ID_MODELO || $id_modelo > self::MAX_ID_MODELO) {
            $errores['id_modelo'] = 'El ID del modelo debe ser un número entre ' . self::MIN_ID_MODELO . ' y ' . self::MAX_ID_MODELO;
        }
        
        return $errores;
    }
    
    private function validarIntegridadReferencial($id_modelo, $pdo) {
        $errores = [];
        
        $sql = "SELECT COUNT(*) as total FROM tbl_productos WHERE id_modelo = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_modelo]);
        $productos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($productos > 0) {
            $errores['integridad'] = "No se puede eliminar el modelo porque tiene {$productos} producto(s) asociado(s)";
        }
        
        return $errores;
    }

    private function verificarModeloExistente($idModelo) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE id_modelo = :id_modelo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_modelo', $idModelo, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }

    private function verificarMarcaExistente($idMarca) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE id_marca = :id_marca";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_marca', $idMarca, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }
}
?>