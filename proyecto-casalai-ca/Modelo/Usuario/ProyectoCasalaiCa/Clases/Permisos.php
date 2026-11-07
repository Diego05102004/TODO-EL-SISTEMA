<?php

namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use RuntimeException;
class Permisos extends BD {
    
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const MAX_ID_MODULO = 999999999;
    const MIN_ID_MODULO = 1;
    const MAX_NOMBRE = 100;
    const MIN_NOMBRE = 1;
    const ACCIONES_VALIDAS = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
    const PERMISOS_VALORES = ['on', 'off'];
    
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

    protected function ejecutarConConexionSegura($operation) {
        try {
            parent::__construct('S'); 
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

    private function validarPermisos($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['permisos'] = 'Los datos de permisos deben ser un arreglo';
            return $errores;
        }
        
        foreach ($datos as $id_rol => $permisosRol) {
            if (!is_numeric($id_rol) || (int)$id_rol < self::MIN_ID_ROL || (int)$id_rol > self::MAX_ID_ROL) {
                $errores['rol_id'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
                break;
            }
            
            if (!is_array($permisosRol)) {
                $errores['permisos_rol'] = 'Los permisos por rol deben ser un arreglo';
                break;
            }
            
            foreach ($permisosRol as $id_modulo => $permisosModulo) {
                if (!is_numeric($id_modulo) || (int)$id_modulo < self::MIN_ID_MODULO || (int)$id_modulo > self::MAX_ID_MODULO) {
                    $errores['modulo_id'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
                    break 2;
                }
                
                if (!is_array($permisosModulo)) {
                    $errores['permisos_modulo'] = 'Los permisos por módulo deben ser un arreglo';
                    break 2;
                }
                
                foreach ($permisosModulo as $accion => $valor) {
                    if (!in_array($accion, self::ACCIONES_VALIDAS)) {
                        $errores['accion'] = "La acción '$accion' no es válida. Acciones permitidas: " . implode(', ', self::ACCIONES_VALIDAS);
                        break 3;
                    }
                    
                    if (!in_array($valor, self::PERMISOS_VALORES)) {
                        $errores['valor_permiso'] = 'El valor del permiso debe ser "on" u "off"';
                        break 3;
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarRol($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['rol'] = 'Los datos del rol deben ser un arreglo';
            return $errores;
        }
        
        if (!isset($datos['id_rol'])) {
            $errores['id_rol'] = 'El ID del rol es obligatorio';
        } else {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        if (!isset($datos['nombre_rol'])) {
            $errores['nombre_rol'] = 'El nombre del rol es obligatorio';
        } else {
            $nombre_rol = trim((string)$datos['nombre_rol']);
            if (mb_strlen($nombre_rol) < self::MIN_NOMBRE) {
                $errores['nombre_rol'] = 'El nombre del rol debe tener al menos ' . self::MIN_NOMBRE . ' caracteres';
            } elseif (mb_strlen($nombre_rol) > self::MAX_NOMBRE) {
                $errores['nombre_rol'] = 'El nombre del rol no debe exceder los ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    private function validarModulo($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['modulo'] = 'Los datos del módulo deben ser un arreglo';
            return $errores;
        }
        
        if (!isset($datos['id_modulo'])) {
            $errores['id_modulo'] = 'El ID del módulo es obligatorio';
        } else {
            $id_modulo = (int)$datos['id_modulo'];
            if ($id_modulo < self::MIN_ID_MODULO || $id_modulo > self::MAX_ID_MODULO) {
                $errores['id_modulo'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
            }
        }
        
        if (!isset($datos['nombre_modulo'])) {
            $errores['nombre_modulo'] = 'El nombre del módulo es obligatorio';
        } else {
            $nombre_modulo = trim((string)$datos['nombre_modulo']);
            if (mb_strlen($nombre_modulo) < self::MIN_NOMBRE) {
                $errores['nombre_modulo'] = 'El nombre del módulo debe tener al menos ' . self::MIN_NOMBRE . ' caracteres';
            } elseif (mb_strlen($nombre_modulo) > self::MAX_NOMBRE) {
                $errores['nombre_modulo'] = 'El nombre del módulo no debe exceder los ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    private function validarAccion($accion) {
        $errores = [];
        
        if (!is_string($accion)) {
            $errores['accion'] = 'La acción debe ser una cadena de texto';
            return $errores;
        }
        
        $accion = trim($accion);
        if ($accion === '') {
            $errores['accion'] = 'La acción no puede estar vacía';
        } elseif (!in_array($accion, self::ACCIONES_VALIDAS)) {
            $errores['accion'] = "La acción '$accion' no es válida. Acciones permitidas: " . implode(', ', self::ACCIONES_VALIDAS);
        }
        
        return $errores;
    }
    
    private function validarListas($roles, $modulos, $acciones) {
        $errores = [];
        
        if (!is_array($roles)) {
            $errores['roles'] = 'Los datos de roles deben ser un arreglo';
        } elseif (empty($roles)) {
            $errores['roles'] = 'Debe existir al menos un rol';
        } else {
            foreach ($roles as $rol) {
                $errores_rol = $this->validarRol($rol);
                if (!empty($errores_rol)) {
                    $errores = array_merge($errores, $errores_rol);
                    break;
                }
            }
        }
        
        if (!is_array($modulos)) {
            $errores['modulos'] = 'Los datos de módulos deben ser un arreglo';
        } elseif (empty($modulos)) {
            $errores['modulos'] = 'Debe existir al menos un módulo';
        } else {
            foreach ($modulos as $modulo) {
                $errores_modulo = $this->validarModulo($modulo);
                if (!empty($errores_modulo)) {
                    $errores = array_merge($errores, $errores_modulo);
                    break;
                }
            }
        }
        
        if (!is_array($acciones)) {
            $errores['acciones'] = 'Los datos de acciones deben ser un arreglo';
        } elseif (empty($acciones)) {
            $errores['acciones'] = 'Debe existir al menos una acción';
        } else {
            foreach ($acciones as $accion) {
                $errores_accion = $this->validarAccion($accion);
                if (!empty($errores_accion)) {
                    $errores = array_merge($errores, $errores_accion);
                    break;
                }
            }
        }
        
        return $errores;
    }
    
    public function validarConsultarPermisos($datos) {
        $errores = [];
        
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        if (isset($datos['id_modulo'])) {
            $id_modulo = (int)$datos['id_modulo'];
            if ($id_modulo < self::MIN_ID_MODULO || $id_modulo > self::MAX_ID_MODULO) {
                $errores['id_modulo'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
            }
        }
        
        return $errores;
    }
    
    public function validarSeleccionarRol($datos) {
        return $this->validarRol($datos);
    }
    
    public function validarSeleccionarModulo($datos) {
        return $this->validarModulo($datos);
    }
    
    public function validarModificarPermisos($permisosForm, $roles, $modulos, $acciones) {
        $errores = [];
        
        $errores_listas = $this->validarListas($roles, $modulos, $acciones);
        if (!empty($errores_listas)) {
            return $errores_listas;
        }
        
        $errores_permisos = $this->validarPermisos($permisosForm);
        if (!empty($errores_permisos)) {
            return $errores_permisos;
        }
        
        return $errores;
    }

    public function getRoles() {
        return $this->o_roles();
    }
    private function o_roles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $stmt = $pdo->query("SELECT id_rol, nombre_rol FROM tbl_rol");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getModulos() {
        return $this->o_modulos();
    }
    private function o_modulos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $stmt = $pdo->query("SELECT id_modulo, nombre_modulo FROM tbl_modulos");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getPermisosPorRolModulo() {
        return $this->o_permisosPorRolModulo();
    }
    private function o_permisosPorRolModulo() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $stmt = $pdo->query("SELECT id_rol, id_modulo, accion, estatus FROM tbl_permisos");
            $permisos = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['estatus'] === 'Permitido') {
                    $permisos[$row['id_rol']][$row['id_modulo']][$row['accion']] = true;
                }
            }
            return $permisos;
        });
    }

    public function getPermisosUsuarioModulo($id_rol, $nombre_modulo) {
        return $this->o_permisosUsuarioModulo($id_rol, $nombre_modulo);
    }
    private function o_permisosUsuarioModulo($id_rol, $nombre_modulo) {
        if ((int)$id_rol === 6) {
            return [
                'ingresar' => true,
                'consultar' => true,
                'incluir' => true,
                'modificar' => true,
                'eliminar' => true,
                'generar reporte' => true,
                'generar re' => true
            ];
        }
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol, $nombre_modulo) {
            $stmt = $pdo->prepare("SELECT id_modulo FROM tbl_modulos WHERE LOWER(nombre_modulo) = LOWER(?) LIMIT 1");
            $stmt->execute([$nombre_modulo]);
            $modulo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$modulo) {
                return [
                    'consultar' => false,
                    'incluir' => false,
                    'modificar' => false,
                    'eliminar' => false,
                    'generar reporte' => false
                ];
            }

            $id_modulo = $modulo['id_modulo'];

            $stmt = $pdo->prepare("SELECT accion, estatus FROM tbl_permisos WHERE id_rol = ? AND id_modulo = ?");
            $stmt->execute([$id_rol, $id_modulo]);
            $permisos = [
                'consultar' => false,
                'incluir' => false,
                'ingresar' => false,
                'modificar' => false,
                'eliminar' => false,
                'generar reporte' => false
            ];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $perm) {
                $accionRaw = isset($perm['accion']) ? (string) $perm['accion'] : '';
                $accion = strtolower(trim($accionRaw));
                $accion = preg_replace('/\s+/', ' ', $accion);
                if ($accion === 'reportar') {
                    $accion = 'generar reporte';
                }
                if ($accion === 'incluir') {
                    $accion = 'incluir';
                }
                if ($accion === 'ingresar') {
                    $accion = 'ingresar';
                }
                if ($accion === 'consultar') {
                    $accion = 'consultar';
                }
                if ($accion === 'modificar') {
                    $accion = 'modificar';
                }
                if ($accion === 'eliminar') {
                    $accion = 'eliminar';
                }
                if (array_key_exists($accion, $permisos)) {
                    $permisos[$accion] = ($perm['estatus'] === 'Permitido');
                }
            }
            return $permisos;
        });
    }

    public function guardarPermisos($permisosForm, $roles, $modulos, $acciones) {
        return $this->g_guardarPermisos($permisosForm, $roles, $modulos, $acciones);
    }
    private function g_guardarPermisos($permisosForm, $roles, $modulos, $acciones) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($permisosForm, $roles, $modulos, $acciones) {
            try {
                // Convertir arrays a JSON para pasar al procedimiento almacenado
                $permisosJson = json_encode($permisosForm);
                $rolesJson = json_encode($roles);
                $modulosJson = json_encode($modulos);
                $accionesJson = json_encode($acciones);

                // Llamar al procedimiento almacenado con LOCK IN SHARE MODE
                $sql = "CALL sp_guardar_permisos(?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$permisosJson, $rolesJson, $modulosJson, $accionesJson]);
                $stmt->closeCursor();

                return true;
            } catch (PDOException $e) {
                throw new PDOException('Error al guardar permisos: ' . $e->getMessage());
            }
        });
    }
}
?>
