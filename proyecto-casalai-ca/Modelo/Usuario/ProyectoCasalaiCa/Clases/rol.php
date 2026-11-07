<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Rol extends BD {

    private $id_rol;
    private $nombre_rol;

    // Constantes de validación
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const MAX_NOMBRE_ROL = 15;
    const MIN_NOMBRE_ROL = 2;
    const CAMPOS_OBLIGATORIOS = ['nombre_rol'];
    const MAX_REGISTROS_PAGINA = 100;

    public function getIdRol() { 
        return $this->id_rol; 
    }
    public function setIdRol($id_rol) { 
        $this->id_rol = $id_rol; 
    }

    public function getNombreRol() { 
        return $this->nombre_rol; 
    }
    public function setNombreRol($nombre_rol) { 
        $this->nombre_rol = $nombre_rol; 
    }

    public function __construct($tipo = 'S') {
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
            parent::__construct('S'); 
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
        
        $campos_requeridos = self::CAMPOS_OBLIGATORIOS;
        
        if ($operacion === 'registrar') {
            foreach ($campos_requeridos as $campo) {
                if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
                    $errores[$campo] = "El campo {$campo} es obligatorio";
                }
            }
        } elseif ($operacion === 'modificar') {
            if (!isset($datos['id_rol']) || $datos['id_rol'] === '' || $datos['id_rol'] === null) {
                $errores['id_rol'] = 'El ID del rol es obligatorio para modificar';
            }
            
            $campos_modificar = array_intersect(array_keys($datos), $campos_requeridos);
            if (empty($campos_modificar)) {
                $errores['modificacion'] = 'Debe proporcionar al menos un campo para modificar';
            }
        }
        
        return $errores;
    }
    
    private function validarFormato($datos) {
        $errores = [];
        
        if (isset($datos['nombre_rol'])) {
            $nombre = trim($datos['nombre_rol']);
            if (mb_strlen($nombre) < self::MIN_NOMBRE_ROL || mb_strlen($nombre) > self::MAX_NOMBRE_ROL) {
                $errores['nombre_rol'] = 'El nombre del rol debe tener entre ' . self::MIN_NOMBRE_ROL . ' y ' . self::MAX_NOMBRE_ROL . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre_rol'] = 'El nombre del rol solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        return $errores;
    }
    
    private function validarFiltros($filtros) {
        $errores = [];
        
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite <= 0 || $limite > self::MAX_REGISTROS_PAGINA) {
                $errores['limite'] = "El límite debe estar entre 1 y " . self::MAX_REGISTROS_PAGINA . " registros";
            }
        }
        
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    private function validarId($id_rol) {
        $errores = [];
        
        if ($id_rol === null || $id_rol === '') {
            $errores['id_rol'] = 'El ID del rol es obligatorio';
        } elseif (!is_numeric($id_rol) || (int)$id_rol < self::MIN_ID_ROL || (int)$id_rol > self::MAX_ID_ROL) {
            $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
        }
        
        return $errores;
    }
    
    private function validarIntegridadReferencial($id_rol, $pdo) {
        $errores = [];
        
        $sql = "SELECT COUNT(*) as total FROM tbl_usuarios WHERE id_rol = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_rol]);
        $usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($usuarios > 0) {
            $errores['integridad'] = "No se puede eliminar el rol porque tiene {$usuarios} usuario(s) asignado(s)";
        }
        
        return $errores;
    }

    private function validarRol($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['rol'] = 'Los datos del rol deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID del rol
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        // Validar nombre del rol
        if (isset($datos['nombre_rol'])) {
            $nombre_rol = trim((string)$datos['nombre_rol']);
            if ($nombre_rol === '') {
                $errores['nombre_rol'] = 'El nombre del rol es obligatorio';
            } elseif (mb_strlen($nombre_rol) < self::MIN_NOMBRE_ROL || mb_strlen($nombre_rol) > self::MAX_NOMBRE_ROL) {
                $errores['nombre_rol'] = 'El nombre del rol debe tener entre ' . self::MIN_NOMBRE_ROL . ' y ' . self::MAX_NOMBRE_ROL . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_rol)) {
                $errores['nombre_rol'] = 'El nombre del rol solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        return $errores;
    }
    
    // Métodos públicos de validación
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre_rol',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
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
        
        if ($this->existeNombreRol($datos['nombre_rol'])) {
            $errores['nombre_rol'] = 'El nombre del rol ya existe';
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
        
        $rol_existente = $this->obtenerRolPorId($datos['id_rol']);
        if (!$rol_existente) {
            $errores['existencia'] = 'El rol que intenta modificar no existe';
            return $errores;
        }
        
        if (isset($datos['nombre_rol']) && 
            $this->existeNombreRol($datos['nombre_rol'], $datos['id_rol'])) {
            $errores['nombre_rol'] = 'El nombre del rol ya existe';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id_rol) {
        $errores = $this->validarId($id_rol);
        if (!empty($errores)) {
            return $errores;
        }
        
        $rol = $this->obtenerRolPorId($id_rol);
        if (!$rol) {
            $errores['existencia'] = 'El rol que intenta eliminar no existe';
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $errores = [];
            $errores_integridad = $this->validarIntegridadReferencial($id_rol, $pdo);
            $errores = array_merge($errores, $errores_integridad);
            return $errores;
        });
    }

    public function registrarRol($id_usuario_auditor) {
        return $this->m_registrarRol($id_usuario_auditor);
    }

    private function m_registrarRol($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor) {
            
            $sql = "CALL sp_registrar_rol(:nombre_rol, :id_usuario_auditor)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindValue(':nombre_rol', $this->nombre_rol);
            $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            
            return $resultado;
        }, false);
    }

    private function existeNombreRol($nombre_rol, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre_rol, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_rol WHERE nombre_rol = ?";
            $params = [$nombre_rol];
            if ($excluir_id !== null) {
                $sql .= " AND id_rol != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $ret = $stmt->fetchColumn() > 0;
            return $ret;
        });
    }

    public function obtenerUltimoRol() {
        return $this->obtUltimoRol(); 
    }
    private function obtUltimoRol() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT * FROM tbl_rol ORDER BY id_rol DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $rol = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $rol ? $rol : null;
            } catch (PDOException $e) {
                
                return null;
            }
        });
    }

    public function obtenerRolPorId($id_rol) {
        return $this->rolporid($id_rol); 
    }
    private function rolporid($id_rol) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $query = "CALL sp_obtener_rol_por_id(:id_rol)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id_rol' => $id_rol]);
            $rol_obt = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rol_obt;
        }, false);
    }

    public function consultarRoles() {
        return $this->c_roles(); 
    }
    private function c_roles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "CALL sp_consultar_rol()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $roles_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $roles_obt;
        }, false);
    }

    public function modificarRol($id_rol, $id_usuario_auditor) {
        return $this->m_rol($id_rol, $id_usuario_auditor); 
    }
    private function m_rol($id_rol, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol, $id_usuario_auditor) {
            
            $sql = "CALL sp_modificar_rol(
                :id_rol,
                :nombre_rol,
                :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_rol', $id_rol, \PDO::PARAM_INT);
            $stmt->bindParam(':nombre_rol', $this->nombre_rol);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $ok = $stmt->execute();
            $stmt->closeCursor();
            return $ok;
        }, false);
    }

    public function eliminarRol($id_rol, $id_usuario_auditor) {
        return $this->e_rol($id_rol, $id_usuario_auditor); 
    }
    private function e_rol($id_rol, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol, $id_usuario_auditor) {
            $sql = "CALL sp_eliminar_rol(:id_rol, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_rol', $id_rol, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        }, false);
    }

    public function tieneUsuariosAsignados($id_rol) {
        return $this->tieneUsuAsignados($id_rol); 
    }
    private function tieneUsuAsignados($id_rol) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE id_rol = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_rol]);
            $ret = $stmt->fetchColumn() > 0;
            return $ret;
        });
    }
}
?>