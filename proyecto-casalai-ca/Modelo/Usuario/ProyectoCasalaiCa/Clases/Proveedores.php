<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;

class Proveedores extends BD {
    
    const MAX_REGISTROS_PAGINA = 100;
    const MAX_RANGO_FECHAS_DIAS = 365;
    const CAMPOS_OBLIGATORIOS = ['nombre_proveedor', 'rif_proveedor', 'nombre_representante', 'rif_representante'];
    
    const ESTADOS_VALIDOS = ['habilitado', 'inhabilitado'];
    
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];
    
    private $id_proveedor;
    private $nombre;
    private $representante;
    private $rif1;
    private $rif2;
    private $telefono1; 
    private $telefono2;
    private $direccion;
    private $correo;
    private $observacion;
    private $activo=1;
    private $tableproveedor= 'tbl_proveedores';
    private $encryption;
    
    // Campos que deben ser cifrados (NOTA: rif NO se cifra porque se usa para búsquedas)
    // Los nombres de campos deben coincidir con los de la base de datos
    const CAMPOS_CIFRADOS = ['nombre_proveedor', 'nombre_representante', 'telefono_1', 'telefono_2', 'direccion_proveedor', 'correo_proveedor'];

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getRepresentante() {
        return $this->representante;
    }

    public function setRepresentante($representante) {
        $this->representante = $representante;
    }

    public function getRif1() {
        return $this->rif1;
    }

    public function setRif1($rif1) {
        $this->rif1 = $rif1;
    }

    public function getRif2() {
        return $this->rif2;
    }

    public function setRif2($rif2) {
        $this->rif2 = $rif2;
    }

    public function getTelefono1() {
        return $this->telefono1;
    }

    public function setTelefono1($telefono1) {
        $this->telefono1 = $telefono1;
    }

    public function getTelefono2() {
        return $this->telefono2;
    }

    public function setTelefono2($telefono2) {
        $this->telefono2 = $telefono2;
    }

    public function getDireccion() {
        return $this->direccion;
    }

    public function setDireccion($direccion) {
        $this->direccion = $direccion;
    }

    public function getCorreo() {
        return $this->correo;
    }

    public function setCorreo($correo) {
        $this->correo = $correo;
    }

    public function getObservacion() {
        return $this->observacion;
    }

    public function setObservacion($observacion) {
        $this->observacion = $observacion;
    }

    public function getIdProveedor() {
        return $this->id_proveedor;
    }

    public function setIdProveedor($id_proveedor) {
        $this->id_proveedor = $id_proveedor;
    }
    
    public function __construct($tipo = 'P') {
        $this->encryption = new Encryption();
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
            if (!isset($datos['id_proveedor']) || $datos['id_proveedor'] === '' || $datos['id_proveedor'] === null) {
                $errores['id_proveedor'] = 'El ID del proveedor es obligatorio para modificar';
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
        
        if (isset($datos['nombre_proveedor'])) {
            $nombre = trim($datos['nombre_proveedor']);
            if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 200) {
                $errores['nombre_proveedor'] = 'El nombre del proveedor debe tener entre 2 y 200 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre_proveedor'] = 'El nombre solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (isset($datos['rif_proveedor'])) {
            $rif = trim($datos['rif_proveedor']);
            if (mb_strlen($rif) < 5 || mb_strlen($rif) > 20) {
                $errores['rif_proveedor'] = 'El RIF debe tener entre 5 y 20 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $rif)) {
                $errores['rif_proveedor'] = 'El RIF solo puede contener letras, números y guiones';
            }
        }
        
        if (isset($datos['nombre_representante'])) {
            $representante = trim($datos['nombre_representante']);
            if (mb_strlen($representante) < 2 || mb_strlen($representante) > 200) {
                $errores['nombre_representante'] = 'El nombre del representante debe tener entre 2 y 200 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $representante)) {
                $errores['nombre_representante'] = 'El nombre solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (isset($datos['rif_representante'])) {
            $rif_rep = trim($datos['rif_representante']);
            if (mb_strlen($rif_rep) < 5 || mb_strlen($rif_rep) > 20) {
                $errores['rif_representante'] = 'El RIF del representante debe tener entre 5 y 20 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $rif_rep)) {
                $errores['rif_representante'] = 'El RIF solo puede contener letras, números y guiones';
            }
        }
        
        if (isset($datos['correo_proveedor']) && $datos['correo_proveedor'] !== '') {
            $correo = trim($datos['correo_proveedor']);
            
            if (mb_strlen($correo) > 255) {
                $errores['correo_proveedor'] = 'El correo no debe exceder los 255 caracteres';
            } 
            
            elseif (!preg_match('/^[^@]+@[^@]+\.[^@]+$/', $correo)) {
                $errores['correo_proveedor'] = 'El correo debe tener formato usuario@dominio.extensión';
            }
            else {
                list($usuario, $dominio_completo) = explode('@', $correo, 2);
                
                if (strlen($usuario) > 64) {
                    $errores['correo_proveedor'] = 'El nombre de usuario no debe exceder los 64 caracteres';
                }
                elseif (strlen($dominio_completo) > 253) {
                    $errores['correo_proveedor'] = 'El dominio no debe exceder los 253 caracteres';
                }
                elseif (preg_match('/[<>"\s]/', $usuario)) {
                    $errores['correo_proveedor'] = 'El nombre de usuario contiene caracteres no permitidos';
                }
                elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $errores['correo_proveedor'] = 'El formato del correo electrónico no es válido';
                }
            }
        }
        
        if (isset($datos['telefono_1']) && $datos['telefono_1'] !== '') {
            $telefono1 = trim($datos['telefono_1']);
            if (mb_strlen($telefono1) < 7 || mb_strlen($telefono1) > 20) {
                $errores['telefono_1'] = 'El teléfono debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono1)) {
                $errores['telefono_1'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        if (isset($datos['telefono_2']) && $datos['telefono_2'] !== '') {
            $telefono2 = trim($datos['telefono_2']);
            if (mb_strlen($telefono2) < 7 || mb_strlen($telefono2) > 20) {
                $errores['telefono_2'] = 'El teléfono debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono2)) {
                $errores['telefono_2'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        if (isset($datos['direccion_proveedor']) && $datos['direccion_proveedor'] !== '') {
            $direccion = trim($datos['direccion_proveedor']);
            if (mb_strlen($direccion) > 500) {
                $errores['direccion_proveedor'] = 'La dirección no debe exceder los 500 caracteres';
            }
        }
        
        if (isset($datos['observacion']) && $datos['observacion'] !== '') {
            $observacion = trim($datos['observacion']);
            if (mb_strlen($observacion) > 1000) {
                $errores['observacion'] = 'La observación no debe exceder los 1000 caracteres';
            }
        }
        
        return $errores;
    }
    
    private function validarId($id_proveedor) {
        $errores = [];
        
        if ($id_proveedor === null || $id_proveedor === '') {
            $errores['id_proveedor'] = 'El ID del proveedor es obligatorio';
        } elseif (!is_numeric($id_proveedor) || (int)$id_proveedor <= 0) {
            $errores['id_proveedor'] = 'El ID del proveedor debe ser un número positivo';
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
        
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $fecha_inicio = strtotime($filtros['fecha_inicio']);
            $fecha_fin = strtotime($filtros['fecha_fin']);
            
            if ($fecha_inicio && $fecha_fin) {
                if ($fecha_inicio > $fecha_fin) {
                    $errores['fechas'] = 'La fecha de inicio no puede ser mayor a la fecha fin';
                }
                
                $dias_diferencia = ($fecha_fin - $fecha_inicio) / (60 * 60 * 24);
                if ($dias_diferencia > self::MAX_RANGO_FECHAS_DIAS) {
                    $errores['rango_fechas'] = "El rango de fechas no puede exceder " . self::MAX_RANGO_FECHAS_DIAS . " días";
                }
            }
        }
        
        return $errores;
    }
    
    private function validarEstado($estado) {
        $errores = [];
        
        if (!in_array($estado, self::ESTADOS_VALIDOS)) {
            $errores['estado'] = 'El estado debe ser: ' . implode(' o ', self::ESTADOS_VALIDOS);
        }
        
        return $errores;
    }
    
    private function validarReporte($parametros) {
        $errores = [];
        
        if (isset($parametros['formato'])) {
            $formato = strtolower($parametros['formato']);
            if (!in_array($formato, self::FORMATOS_REPORTE)) {
                $errores['formato'] = 'El formato debe ser: ' . implode(', ', self::FORMATOS_REPORTE);
            }
        }
        
        if (isset($parametros['fecha_inicio']) && isset($parametros['fecha_fin'])) {
            $errores = array_merge($errores, $this->validarFiltros([
                'fecha_inicio' => $parametros['fecha_inicio'],
                'fecha_fin' => $parametros['fecha_fin']
            ]));
        }
        
        return $errores;
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
    
    private function validarIntegridadReferencial($id_proveedor, $pdo) {
        $errores = [];
        
        $sql = "SELECT COUNT(*) as total FROM tbl_recepcion_productos WHERE id_proveedor = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_proveedor]);
        $recepciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($recepciones > 0) {
            $errores['integridad'] = "No se puede eliminar el proveedor porque tiene {$recepciones} recepción(es) asociada(s)";
        }
        
        return $errores;
    }
    
    private function validarTransicionEstado($estado_actual, $estado_nuevo) {
        $errores = [];
        
        if ($estado_actual === $estado_nuevo) {
            $errores['transicion'] = 'El proveedor ya se encuentra en el estado ' . $estado_actual;
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
        
        if ($this->existeNombreProveedor($datos['nombre_proveedor'])) {
            $errores['nombre_proveedor'] = 'El nombre del proveedor ya existe';
        }
        
        if ($this->existeRifProveedor($datos['rif_proveedor'])) {
            $errores['rif_proveedor'] = 'El RIF del proveedor ya está registrado';
        }
        
        if ($this->existeRifRepresentante($datos['rif_representante'])) {
            $errores['rif_representante'] = 'El RIF del representante ya está registrado';
        }
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        // Establecer valores por defecto
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre_proveedor',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
    }
    
    public function validarDetallar($id_proveedor) {
        $errores = $this->validarId($id_proveedor);
        if (!empty($errores)) {
            return $errores;
        }
        
        $proveedor = $this->obtenerProveedorPorId($id_proveedor);
        if (!$proveedor) {
            $errores['existencia'] = 'El proveedor solicitado no existe';
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
        
        $proveedor_existente = $this->obtenerProveedorPorId($datos['id_proveedor']);
        if (!$proveedor_existente) {
            $errores['existencia'] = 'El proveedor que intenta modificar no existe';
            return $errores;
        }
        
        if (isset($datos['nombre_proveedor']) && 
            $this->existeNombreProveedor($datos['nombre_proveedor'], $datos['id_proveedor'])) {
            $errores['nombre_proveedor'] = 'El nombre del proveedor ya existe';
        }
        
        if (isset($datos['rif_proveedor']) && 
            $this->existeRifProveedor($datos['rif_proveedor'], $datos['id_proveedor'])) {
            $errores['rif_proveedor'] = 'El RIF del proveedor ya está registrado';
        }
        
        if (isset($datos['rif_representante']) && 
            $this->existeRifRepresentante($datos['rif_representante'], $datos['id_proveedor'])) {
            $errores['rif_representante'] = 'El RIF del representante ya está registrado';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id_proveedor) {
        $errores = $this->validarId($id_proveedor);
        if (!empty($errores)) {
            return $errores;
        }
        
        $proveedor = $this->obtenerProveedorPorId($id_proveedor);
        if (!$proveedor) {
            $errores['existencia'] = 'El proveedor que intenta eliminar no existe';
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            $errores = [];
            $errores_integridad = $this->validarIntegridadReferencial($id_proveedor, $pdo);
            $errores = array_merge($errores, $errores_integridad);
            return $errores;
        });
    }
    
    public function validarCambiarEstatus($id_proveedor, $nuevo_estatus) {
        $errores = [];
        
        $errores_id = $this->validarId($id_proveedor);
        if (!empty($errores_id)) {
            return array_merge($errores, $errores_id);
        }
        
        $errores_estado = $this->validarEstado($nuevo_estatus);
        if (!empty($errores_estado)) {
            return array_merge($errores, $errores_estado);
        }
        
        $proveedor = $this->obtenerProveedorPorId($id_proveedor);
        if (!$proveedor) {
            $errores['existencia'] = 'El proveedor no existe';
            return $errores;
        }
        
        $estado_actual = $proveedor['estado'] ?? 'habilitado';
        $errores_transicion = $this->validarTransicionEstado($estado_actual, $nuevo_estatus);
        $errores = array_merge($errores, $errores_transicion);
        
        return $errores;
    }
    
    public function validarGenerarReporte($parametros) {
        $errores = $this->validarReporte($parametros);
        
        if (!isset($_SESSION['id_usuario']) || !$_SESSION['id_usuario']) {
            $errores['permisos'] = 'No tiene permisos para generar reportes';
        }
        
        return $errores;
    }
    
    public function obtenerProveedoresConFiltros($filtros = []) {
        $errores = $this->validarConsultar($filtros);
        if (!empty($errores)) {
            return ['error' => $errores];
        }
        
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($filtros) {
            $pagina = (int)($filtros['pagina'] ?? 1);
            $limite = (int)($filtros['limite'] ?? 50);
            $orden = $filtros['orden'] ?? 'nombre_proveedor';
            $direccion = $filtros['direccion'] ?? 'ASC';
            $busqueda = $filtros['busqueda'] ?? '';
            
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM tbl_proveedores WHERE 1=1";
            $params = [];
            
            if (!empty($busqueda)) {
                $sql .= " AND (nombre_proveedor LIKE :busqueda OR rif_proveedor LIKE :busqueda OR nombre_representante LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $sql .= " ORDER BY {$orden} {$direccion}";
            
            $sql .= " LIMIT :limite OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sql_total = "SELECT COUNT(*) as total FROM tbl_proveedores WHERE 1=1";
            if (!empty($busqueda)) {
                $sql_total .= " AND (nombre_proveedor LIKE :busqueda OR rif_proveedor LIKE :busqueda OR nombre_representante LIKE :busqueda)";
            }
            
            $stmt_total = $pdo->prepare($sql_total);
            foreach ($params as $key => $value) {
                $stmt_total->bindValue($key, $value);
            }
            $stmt_total->execute();
            $total = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'proveedores' => $proveedores,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'total_paginas' => ceil($total / $limite)
            ];
            
        });
        
        // Descifrar datos personales de los proveedores
        if (isset($resultado['proveedores'])) {
            $resultado['proveedores'] = $this->encryption->decryptResults($resultado['proveedores'], self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    private function existeNombreProveedor($nombre, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE nombre_proveedor = ?";
            $params = [$nombre];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    private function existeRifProveedor($rif, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($rif, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE rif_proveedor = ?";
            $params = [$rif];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    private function existeRifRepresentante($rif, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($rif, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE rif_representante = ?";
            $params = [$rif];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function registrarProveedor($id_usuario_auditor) {
        return $this->r_proveedor($id_usuario_auditor);
    }
    private function r_proveedor($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor){
            // Cifrar datos personales antes de insertar
            $nombre_cifrado = $this->encryption->encrypt($this->nombre);
            $representante_cifrado = $this->encryption->encrypt($this->representante);
            $telefono1_cifrado = $this->encryption->encrypt($this->telefono1);
            $telefono2_cifrado = $this->encryption->encrypt($this->telefono2);
            $direccion_cifrada = $this->encryption->encrypt($this->direccion);
            $correo_cifrado = $this->encryption->encrypt($this->correo);
            
            $sql = "CALL sp_registrar_proveedor(
            :nombre, 
            :rif1, 
            :representante, 
            :rif2, 
            :correo,
            :direccion,
            :telefono1,
            :telefono2,
            :observacion,
            :id_usuario_auditor)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre_cifrado);
            $stmt->bindParam(':rif1', $this->rif1);
            $stmt->bindParam(':representante', $representante_cifrado);
            $stmt->bindParam(':rif2', $this->rif2);
            $stmt->bindParam(':correo', $correo_cifrado);
            $stmt->bindParam(':direccion', $direccion_cifrada);
            $stmt->bindParam(':telefono1', $telefono1_cifrado);
            $stmt->bindParam(':telefono2', $telefono2_cifrado);
            $stmt->bindParam(':observacion', $this->observacion);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }

    public function obtenerUltimoProveedor() {
        return $this->obtUltimoProveedor(); 
    }
    private function obtUltimoProveedor() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_proveedores ORDER BY id_proveedor DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    public function obtenerReporteSuministroProveedores() {
        return $this->obtReporteSuministroProveedores();
    }

    private function obtReporteSuministroProveedores() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT p.nombre_proveedor, SUM(dp.cantidad) AS cantidad
                    FROM tbl_proveedores p
                    JOIN tbl_recepcion_productos r ON p.id_proveedor = r.id_proveedor
                    JOIN tbl_detalle_recepcion_productos dp ON r.id_recepcion = dp.id_recepcion
                    GROUP BY p.id_proveedor, p.nombre_proveedor
                    ORDER BY cantidad DESC
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    public function obtenerProveedorPorId($id_proveedor) {
        return $this->obtProveedorPorId($id_proveedor);
    }
    private function obtProveedorPorId($id_proveedor) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            $query = "CALL sp_obtener_proveedor_por_id(:id_proveedor)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id_proveedor' => $id_proveedor]);

            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); 
            return $proveedor;
        }, false);
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    public function modificarProveedor($id_proveedor, $id_usuario_auditor) {
        return $this->m_proveedor($id_proveedor, $id_usuario_auditor);
    }
    private function m_proveedor($id_proveedor, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor, $id_usuario_auditor) {
            // Cifrar datos personales antes de actualizar
            $nombre_cifrado = $this->encryption->encrypt($this->nombre);
            $representante_cifrado = $this->encryption->encrypt($this->representante);
            $telefono1_cifrado = $this->encryption->encrypt($this->telefono1);
            $telefono2_cifrado = $this->encryption->encrypt($this->telefono2);
            $direccion_cifrada = $this->encryption->encrypt($this->direccion);
            $correo_cifrado = $this->encryption->encrypt($this->correo);
            
            $sql = "CALL sp_modificar_proveedor(
            :id_proveedor,
            :nombre, 
            :rif1, 
            :representante, 
            :rif2, 
            :correo,
            :direccion,
            :telefono1,
            :telefono2,
            :observacion,
            :id_usuario_auditor)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_proveedor', $id_proveedor, \PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre_cifrado);
            $stmt->bindParam(':rif1', $this->rif1);
            $stmt->bindParam(':representante', $representante_cifrado);
            $stmt->bindParam(':rif2', $this->rif2);
            $stmt->bindParam(':correo', $correo_cifrado);
            $stmt->bindParam(':direccion', $direccion_cifrada);
            $stmt->bindParam(':telefono1', $telefono1_cifrado);
            $stmt->bindParam(':telefono2', $telefono2_cifrado);
            $stmt->bindParam(':observacion', $this->observacion);
            $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }

    public function eliminarProveedor($id_proveedor, $id_usuario_auditor) {
        return $this->e_proveedor($id_proveedor, $id_usuario_auditor);
    }
    private function e_proveedor($id_proveedor, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor, $id_usuario_auditor) {
            $sql = "CALL sp_eliminar_proveedor(:id_proveedor, :id_usuario_auditor)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_proveedor', $id_proveedor, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        }, false);
    }

    public function getproveedores() {
        return $this->g_proveedores();
    }
    private function g_proveedores() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $queryproveedores = "CALL sp_consultar_proveedores()";
            $stmtproveedores = $pdo->prepare($queryproveedores);
            $stmtproveedores->execute();

            $preveedores = $stmtproveedores->fetchAll(PDO::FETCH_ASSOC);
            $stmtproveedores->closeCursor();
            return $preveedores;
        }, false);

        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    public function getRankingProveedores() {
        return $this->getRankingProv();
    }
    private function getRankingProv() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT p.nombre_proveedor, pr.nombre_producto, d.cantidad, d.costo, d.cantidad*d.costo AS total, r.fecha
                FROM tbl_recepcion_productos r
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                INNER JOIN tbl_detalle_recepcion_productos d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_productos pr ON pr.id_producto = d.id_producto
                GROUP BY p.nombre_proveedor
                ORDER BY total DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales del proveedor
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    public function getComparacionPreciosProducto() {
        return $this->getComparacionPreciosProd();
    }
    private function getComparacionPreciosProd() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT 
                    pr.id_producto,
                    pr.nombre_producto,
                    p.nombre_proveedor,
                    SUM(d.cantidad) AS cantidad,
                    AVG(d.costo) AS precio_promedio,
                    COUNT(*) AS cantidad_registros,
                    MIN(r.fecha) AS fecha,
                    MONTH(MIN(r.fecha)) AS mes_num,
                    YEAR(MIN(r.fecha)) AS anio
                FROM 
                    tbl_detalle_recepcion_productos d
                INNER JOIN tbl_recepcion_productos r ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                INNER JOIN tbl_productos pr ON pr.id_producto = d.id_producto
                GROUP BY 
                    pr.id_producto,
                    pr.nombre_producto,
                    p.nombre_proveedor
                ORDER BY 
                    pr.id_producto,
                    precio_promedio DESC;
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales del proveedor
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    public function getDependenciaProveedores() {
        return $this->getDependenciaProv();
    }
    private function getDependenciaProv() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT p.nombre_proveedor, SUM(d.cantidad * d.costo) AS monto_total_pagado, 
                ROUND( (SUM(d.cantidad * d.costo) * 100.0 / (SELECT SUM(d2.cantidad * d2.costo) 
                FROM tbl_detalle_recepcion_productos d2 
                INNER JOIN tbl_recepcion_productos r2 ON d2.id_recepcion = r2.id_recepcion) ), 2 )
                 AS dependencia_porcentaje 
                FROM tbl_recepcion_productos r
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor 
                INNER JOIN tbl_detalle_recepcion_productos d ON d.id_recepcion = r.id_recepcion 
                GROUP BY p.nombre_proveedor 
                ORDER BY dependencia_porcentaje DESC;
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales del proveedor
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }


    public function cambiarEstatus($nuevoEstatus, $id_usuario_auditor) {
        return $this->cam_Estatus($nuevoEstatus, $id_usuario_auditor); 
    }
    private function cam_Estatus($nuevoEstatus, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus, $id_usuario_auditor) {
            $sql = "CALL sp_cambiar_estado_proveedor(:id_proveedor, :estatus, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id_proveedor', $this->id_proveedor, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }
}
?>