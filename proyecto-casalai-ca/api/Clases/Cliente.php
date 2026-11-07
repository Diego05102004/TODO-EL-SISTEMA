<?php
namespace Usuario\ProyectoCasalaiCa;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;

class Cliente extends BD {
    private $tableclientes = 'tbl_clientes';
    private $nombre;
    private $direccion;
    private $telefono;
    private $cedula;
    private $correo;
    private $activo = 1;
    private $id;
    private $encryption;
    
    // Campos que deben ser cifrados (NOTA: cédula no se cifra porque se usa para búsquedas)
    const CAMPOS_CIFRADOS = ['nombre', 'direccion', 'telefono', 'correo'];
    
    // Constantes para validaciones
    const MAX_REGISTROS_PAGINA = 100;
    const CAMPOS_OBLIGATORIOS = ['nombre', 'cedula'];
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];
    const MAX_NOMBRE_CLIENTE = 550;
    const MIN_NOMBRE_CLIENTE = 2;
    const MAX_DIRECCION = 500;
    const MAX_TELEFONO = 550;
    const MIN_TELEFONO = 7;
    const MAX_CORREO = 550;
    const MAX_CEDULA = 12;
    const MIN_CEDULA = 8;
    const ESTADOS_PERMITIDOS = [1, 0]; // 1 = activo, 0 = inactivo

    public function setnombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getnombre() {
        return $this->nombre;
    }

    public function setdireccion($direccion) {
        $this->direccion = $direccion;
    }

    public function getdireccion() {
        return $this->direccion;
    }

    public function settelefono($telefono) {
        $this->telefono = $telefono;
    }

    public function gettelefono() {
        return $this->telefono;
    }


    public function setcedula($cedula) {
        $this->cedula = $cedula;
    }

    public function getcedula() {
        return $this->cedula;
    }

    public function setcorreo($correo) {
        $this->correo = $correo;
    }

    public function getcorreo() {
        return $this->correo;
    }

    public function setactivo($activo) {
        $this->activo = $activo;
    }

    public function getactivo() {
        return $this->activo;
    }

    
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
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
            throw new \RuntimeException($e->getMessage());
        } finally {
            $this->cerrar();
        }
    }

    // ==================== VALIDACIONES DE BACKEND ====================

    /**
     * Sanitiza los datos de entrada
     */
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
    
    /**
     * Valida el esquema de datos según la operación
     */
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
            if (!isset($datos['id_clientes']) || $datos['id_clientes'] === '' || $datos['id_clientes'] === null) {
                $errores['id_clientes'] = 'El ID del cliente es obligatorio para modificar';
            }
            // Para modificar, no se requieren campos obligatorios específicos
            // Solo se valida que los campos proporcionados (si los hay) tengan formato válido
        }
        
        return $errores;
    }
    
    /**
     * Valida el formato de los datos
     */
    private function validarFormato($datos) {
        $errores = [];
        
        if (isset($datos['nombre'])) {
            $nombre = trim($datos['nombre']);
            if (mb_strlen($nombre) < self::MIN_NOMBRE_CLIENTE || mb_strlen($nombre) > self::MAX_NOMBRE_CLIENTE) {
                $errores['nombre'] = 'El nombre del cliente debe tener entre ' . self::MIN_NOMBRE_CLIENTE . ' y ' . self::MAX_NOMBRE_CLIENTE . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre'] = 'El nombre del cliente solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (isset($datos['cedula'])) {
            $cedula = trim($datos['cedula']);
            if (!preg_match('/^(?:\d{1,2}\.\d{3}\.\d{3})$/', $cedula)) {
                $errores['cedula'] = 'La cédula debe tener el formato 1.234.567 o 12.345.678';
            } elseif (mb_strlen($cedula) < self::MIN_CEDULA || mb_strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' caracteres';
            }
        }
        
        if (isset($datos['telefono']) && $datos['telefono'] !== '') {
            $telefono = trim($datos['telefono']);
            if (mb_strlen($telefono) < self::MIN_TELEFONO || mb_strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        if (isset($datos['direccion']) && $datos['direccion'] !== '') {
            $direccion = trim($datos['direccion']);
            if (mb_strlen($direccion) > self::MAX_DIRECCION) {
                $errores['direccion'] = 'La dirección no debe exceder los ' . self::MAX_DIRECCION . ' caracteres';
            }
        }
        
        if (isset($datos['correo']) && $datos['correo'] !== '') {
            $correo = trim($datos['correo']);
            if (mb_strlen($correo) > self::MAX_CORREO) {
                $errores['correo'] = 'El correo no debe exceder los 550 caracteres';
            } elseif (!preg_match('/^[^@]+@[^@]+\.[^@]+$/', $correo)) {
                $errores['correo'] = 'El correo debe tener formato usuario@dominio.extensión';
            } else {
                list($usuario, $dominio_completo) = explode('@', $correo, 2);
                
                if (strlen($usuario) > 64) {
                    $errores['correo'] = 'El nombre de usuario no debe exceder los 64 caracteres';
                } elseif (strlen($dominio_completo) > 253) {
                    $errores['correo'] = 'El dominio no debe exceder los 253 caracteres';
                } elseif (preg_match('/[<>"\s]/', $usuario)) {
                    $errores['correo'] = 'El nombre de usuario contiene caracteres no permitidos';
                } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $errores['correo'] = 'El formato del correo electrónico no es válido';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida ID de cliente
     */
    private function validarId($id) {
        $errores = [];
        
        if (!is_numeric($id) || $id <= 0) {
            $errores['id_clientes'] = 'El ID del cliente debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida filtros para consultas
     */
    private function validarFiltros($filtros) {
        $errores = [];
        
        // Validar página
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número mayor a 0';
            }
        }
        
        // Validar límite
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite < 1 || $limite > self::MAX_REGISTROS_PAGINA) {
                $errores['limite'] = 'El límite debe estar entre 1 y ' . self::MAX_REGISTROS_PAGINA;
            }
        }
        
        // Validar orden
        if (isset($filtros['orden'])) {
            $ordenes_validos = ['id_clientes', 'nombre', 'cedula', 'telefono', 'correo'];
            if (!in_array($filtros['orden'], $ordenes_validos)) {
                $errores['orden'] = 'El campo de orden no es válido';
            }
        }
        
        // Validar dirección
        if (isset($filtros['direccion'])) {
            $direcciones_validas = ['ASC', 'DESC'];
            if (!in_array(strtoupper($filtros['direccion']), $direcciones_validas)) {
                $errores['direccion'] = 'La dirección de orden debe ser ASC o DESC';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida integridad referencial para eliminación
     */
    private function validarIntegridadReferencial($id, $pdo) {
        $errores = [];
        
        // Verificar si tiene compras asociadas
        $sql = "SELECT COUNT(*) as total FROM tbl_compras WHERE id_clientes = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $compras = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($compras > 0) {
            $errores['integridad'] = "No se puede eliminar el cliente porque tiene {$compras} compra(s) asociada(s)";
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para registrar un cliente
     */
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
        
        if ($this->existeNumeroCedula($datos['cedula'])) {
            $errores['cedula'] = 'La cédula del cliente ya está registrada';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar clientes
     */
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre',
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

        $cliente_existente = $this->obtenerclientesPorId($datos['id_clientes']);
        if (!$cliente_existente) {
            $errores['existencia'] = 'El cliente que intenta modificar no existe';
            return $errores;
        }

        if (isset($datos['cedula']) && 
            $this->existeNumeroCedula($datos['cedula'], $datos['id_clientes'])) {
            $errores['cedula'] = 'La cédula del cliente ya está registrada';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id) {
        $errores = $this->validarId($id);
        if (!empty($errores)) {
            return $errores;
        }
        
        $cliente = $this->obtenerclientesPorId($id);
        if (!$cliente) {
            $errores['existencia'] = 'El cliente que intenta eliminar no existe';
            return $errores;
        }
        
        // Para eliminación, solo validar ID y existencia
        // La integridad referencial se maneja a nivel de base de datos
        return [];
    }

    public function validarDescarga($parametros) {
        $errores = [];
        
        if (!isset($_SESSION['id_usuario']) || !$_SESSION['id_usuario']) {
            $errores['permisos'] = 'No tiene permisos para descargar reportes';
        }
        
        return $errores;
    }
    
    /**
     * Obtiene un cliente por su ID
     */
    public function obtenerclientesPorId($id) {
        return $this->obt_clientesPorId($id); 
    }

    private function obt_clientesPorId($id) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($id) {
            $sql = "CALL sp_obtener_cliente_por_id(:id_clientes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_clientes' => $id]);

            $cliente_obt = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $cliente_obt;
        }, false);
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    /**
     * Obtiene el último cliente registrado
     */
    public function obtenerUltimoCliente() {
        return $this->obt_UltimoCliente(); 
    }

    private function obt_UltimoCliente() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_clientes ORDER BY id_clientes DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $cliente ? $cliente : null;
        });
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    /**
     * Obtiene clientes con filtros aplicados
     */
    public function obtenerClientesConFiltros($filtros = []) {
        $errores = $this->validarConsultar($filtros);
        if (!empty($errores)) {
            return ['error' => $errores];
        }
        
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($filtros) {
            $pagina = (int)($filtros['pagina'] ?? 1);
            $limite = (int)($filtros['limite'] ?? 50);
            $orden = $filtros['orden'] ?? 'nombre';
            $direccion = $filtros['direccion'] ?? 'ASC';
            $busqueda = $filtros['busqueda'] ?? '';
            
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM tbl_clientes WHERE activo = 1";
            $params = [];
            
            if (!empty($busqueda)) {
                $sql .= " AND (nombre LIKE :busqueda OR cedula LIKE :busqueda OR correo LIKE :busqueda)";
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
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sql_total = "SELECT COUNT(*) as total FROM tbl_clientes WHERE activo = 1";
            if (!empty($busqueda)) {
                $sql_total .= " AND (nombre LIKE :busqueda OR cedula LIKE :busqueda OR correo LIKE :busqueda)";
            }
            
            $stmt_total = $pdo->prepare($sql_total);
            foreach ($params as $key => $value) {
                $stmt_total->bindValue($key, $value);
            }
            $stmt_total->execute();
            $total = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'clientes' => $clientes,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'total_paginas' => ceil($total / $limite)
            ];
        });
        
        // Descifrar datos personales de los clientes
        if (isset($resultado['clientes'])) {
            $resultado['clientes'] = $this->encryption->decryptResults($resultado['clientes'], self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    public function ingresarclientes($id_usuario_auditor) {
        return $this->r_cliente($id_usuario_auditor);
    }
    private function r_cliente($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor) {
            error_log("Iniciando r_cliente - ID auditor: $id_usuario_auditor");
            error_log("Nombre: " . $this->nombre);
            error_log("Cédula: " . $this->cedula);
            error_log("Dirección: " . $this->direccion);
            error_log("Teléfono: " . $this->telefono);
            error_log("Correo: " . $this->correo);
            error_log("Activo: " . $this->activo);
            
            // Cifrar datos personales antes de insertar
            $nombre_cifrado = $this->encryption->encrypt($this->nombre);
            $direccion_cifrada = $this->encryption->encrypt($this->direccion);
            $telefono_cifrado = $this->encryption->encrypt($this->telefono);
            $correo_cifrado = $this->encryption->encrypt($this->correo);
            
            error_log("Datos cifrados, llamando stored procedure");
            
            $sql = "CALL sp_registrar_cliente(
                :nombre, 
                :cedula, 
                :direccion, 
                :telefono, 
                :correo, 
                :activo, 
                :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre_cifrado);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':direccion', $direccion_cifrada);
            $stmt->bindParam(':telefono', $telefono_cifrado);
            $stmt->bindParam(':correo', $correo_cifrado);
            $stmt->bindParam(':activo', $this->activo);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT); 
            
            error_log("Ejecutando stored procedure");
            $resultado = $stmt->execute();
            error_log("Resultado execute: " . ($resultado ? 'true' : 'false'));
            
            if (!$resultado) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error en stored procedure: " . print_r($errorInfo, true));
            }
            
            // Cerramos el cursor inmediatamente para limpiar la conexión
            $stmt->closeCursor();
            
            // Retornamos true si se ejecutó correctamente, de lo contrario false
            return $resultado ? true : false;
        }, false);
    }

    public function listarTodosClientes() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $stmt = $pdo->prepare("SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    private function existeNumeroCedula($cedula, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = ?";
            $params = [$cedula];
            if ($excluir_id !== null) {
                $sql .= " AND id_clientes != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $existe = $stmt->fetchColumn() > 0;
            $stmt->closeCursor();
            return $existe;
        });
    }

    function obtenerReporteComprasClientes() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT c.nombre, COUNT(d.id_producto) AS cantidad
            FROM tbl_clientes c
            JOIN tbl_despachos ds ON c.id_clientes = ds.id_clientes
            JOIN tbl_despacho_detalle d ON ds.id_despachos = d.id_despacho
            GROUP BY c.id_clientes, c.nombre
            ORDER BY cantidad DESC
            LIMIT 10;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    public function modificarclientes($id, $id_usuario_auditor) {
        return $this->m_cliente($id, $id_usuario_auditor);
    }
    private function m_cliente($id, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $id_usuario_auditor) {
            // Cifrar datos personales antes de actualizar
            $nombre_cifrado = $this->encryption->encrypt($this->nombre);
            $direccion_cifrada = $this->encryption->encrypt($this->direccion);
            $telefono_cifrado = $this->encryption->encrypt($this->telefono);
            $correo_cifrado = $this->encryption->encrypt($this->correo);
            
            $sql = "CALL sp_modificar_cliente(
                :id_clientes,
                :nombre, 
                :cedula, 
                :direccion, 
                :telefono, 
                :correo, 
                :activo, 
                :id_usuario_auditor
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_clientes', $id, \PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre_cifrado);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':direccion', $direccion_cifrada);
            $stmt->bindParam(':telefono', $telefono_cifrado);
            $stmt->bindParam(':correo', $correo_cifrado);
            $stmt->bindParam(':activo', $this->activo);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }

    public function eliminarclientes($id, $id_usuario_auditor) {
        return $this->e_cliente($id, $id_usuario_auditor);
    }
    private function e_cliente($id, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $id_usuario_auditor){
            $sql = "CALL sp_eliminar_cliente(:id_clientes, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id_clientes', $id, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        }, false);
    }

    public function getclientes() {
        return $this->g_clientes();
    }
    private function g_clientes() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $queryclientes = "CALL sp_consultar_cliente()";
            $stmtclientes = $pdo->prepare($queryclientes);
            $stmtclientes->execute();
            $clientes = $stmtclientes->fetchAll(PDO::FETCH_ASSOC);
            $stmtclientes->closeCursor();
            return $clientes;
        }, false);
        
        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }
}