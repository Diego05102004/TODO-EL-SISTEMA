<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;

class Cuentabanco extends BD {

    private $id_cuenta;
    private $nombre_banco;
    private $numero_cuenta;
    private $rif_cuenta;
    private $telefono_cuenta;
    private $correo_cuenta;
    private $metodos_pago;
    private $estado;
    private $encryption;
    
    // Campos que deben ser cifrados (NOTA: numero_cuenta y rif NO se cifran porque se usan para búsquedas/identificación)
    const CAMPOS_CIFRADOS = ['nombre_banco', 'telefono_cuenta', 'correo_cuenta'];
    
    const MAX_NOMBRE_BANCO = 500;
    const MIN_NOMBRE_BANCO = 3;
    const MAX_NUMERO_CUENTA = 25;
    const MIN_NUMERO_CUENTA = 10;
    const MAX_TELEFONO_CUENTA = 500;
    const MIN_TELEFONO_CUENTA = 7;
    const MAX_CORREO_CUENTA = 500;
    const MAX_RIF_CUENTA = 12;
    const MIN_RIF_CUENTA = 9;
    const ESTADOS_PERMITIDOS = ['habilitado', 'inhabilitado'];
    const TIPOS_PAGO_PERMITIDOS = ['Pago Movil', 'Transferencia', 'Zelle', 'Efectivo', 'Efectivo $'];

    public function getIdCuenta() { 
        return $this->id_cuenta; 
    }
    public function setIdCuenta($id_cuenta) { 
        $this->id_cuenta = $id_cuenta; 
    }

    public function getNombreBanco() { 
        return $this->nombre_banco; 
    }
    public function setNombreBanco($nombre_banco) { 
        $this->nombre_banco = $nombre_banco; 
    }

    public function getNumeroCuenta() { 
        return $this->numero_cuenta; 
    }
    public function setNumeroCuenta($numero_cuenta) { 
        $this->numero_cuenta = $numero_cuenta; 
    }

    public function getRifCuenta() { 
        return $this->rif_cuenta; 
    }
    public function setRifCuenta($rif_cuenta) { 
        $this->rif_cuenta = $rif_cuenta; 
    }

    public function getTelefonoCuenta() { 
        return $this->telefono_cuenta; 
    }
    public function setTelefonoCuenta($telefono_cuenta) { 
        $this->telefono_cuenta = $telefono_cuenta; 
    }

    public function getCorreoCuenta() { 
        return $this->correo_cuenta; 
    }
    public function setCorreoCuenta($correo_cuenta) { 
        $this->correo_cuenta = $correo_cuenta; 
    }

    public function getMetodosPago() {
        return $this->metodos_pago;
    }

    public function setMetodosPago($metodos_pago) {
        if (is_array($metodos_pago)) {
            $this->metodos_pago = implode(',', $metodos_pago);
        } else {
            $this->metodos_pago = $metodos_pago;
        }
    }

    public function getEstado() {
        return $this->estado;
    }
    public function setEstado($estado) {
        $this->estado = $estado;
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
    
    private function validarRegistrar($datos) {
        error_log("[CUENTA-BANCO] Iniciando validación de registrar cuenta");
        error_log("[CUENTA-BANCO] Datos recibidos: " . json_encode($datos));
        
        $errores = [];
        
        if (!isset($datos['nombre_banco'])) {
            error_log("[CUENTA-BANCO] Error: nombre_banco no está definido");
            $errores['nombre_banco'] = 'El nombre del banco es obligatorio';
        } else {
            $nombre_banco = trim($datos['nombre_banco']);
            if (empty($nombre_banco)) {
                error_log("[CUENTA-BANCO] Error: nombre_banco está vacío");
                $errores['nombre_banco'] = 'El nombre del banco no puede estar vacío';
            } elseif (mb_strlen($nombre_banco) < self::MIN_NOMBRE_BANCO || mb_strlen($nombre_banco) > self::MAX_NOMBRE_BANCO) {
                error_log("[CUENTA-BANCO] Error: nombre_banco longitud inválida - " . mb_strlen($nombre_banco) . " caracteres");
                $errores['nombre_banco'] = 'El nombre del banco debe tener entre ' . self::MIN_NOMBRE_BANCO . ' y ' . self::MAX_NOMBRE_BANCO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_banco)) {
                error_log("[CUENTA-BANCO] Error: nombre_banco contiene caracteres inválidos - " . $nombre_banco);
                $errores['nombre_banco'] = 'El nombre del banco solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (!isset($datos['numero_cuenta'])) {
            error_log("[CUENTA-BANCO] Error: numero_cuenta no está definido");
            $errores['numero_cuenta'] = 'El número de cuenta es obligatorio';
        } else {
            $numero_cuenta = trim($datos['numero_cuenta']);
            if (empty($numero_cuenta)) {
                error_log("[CUENTA-BANCO] Error: numero_cuenta está vacío");
                $errores['numero_cuenta'] = 'El número de cuenta no puede estar vacío';
            } else {
                $numero_limpio = preg_replace('/\D+/', '', $numero_cuenta);
                if (empty($numero_limpio)) {
                    error_log("[CUENTA-BANCO] Error: numero_cuenta no contiene dígitos - " . $numero_cuenta);
                    $errores['numero_cuenta'] = 'El número de cuenta debe contener solo dígitos';
                } elseif (mb_strlen($numero_limpio) < self::MIN_NUMERO_CUENTA || mb_strlen($numero_limpio) > self::MAX_NUMERO_CUENTA) {
                    error_log("[CUENTA-BANCO] Error: numero_cuenta longitud inválida - " . mb_strlen($numero_limpio) . " dígitos");
                    $errores['numero_cuenta'] = 'El número de cuenta debe tener entre ' . self::MIN_NUMERO_CUENTA . ' y ' . self::MAX_NUMERO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['rif_cuenta'])) {
            error_log("[CUENTA-BANCO] Error: rif_cuenta no está definido");
            $errores['rif_cuenta'] = 'El RIF de la cuenta es obligatorio';
        } else {
            $rif_cuenta = trim($datos['rif_cuenta']);
            if (empty($rif_cuenta)) {
                error_log("[CUENTA-BANCO] Error: rif_cuenta está vacío");
                $errores['rif_cuenta'] = 'El RIF de la cuenta no puede estar vacío';
            } else {
                $rif_limpio = str_replace(['-', ' '], '', strtoupper($rif_cuenta));
                if (!preg_match('/^[VEJGCP][0-9]{8,9}$/', $rif_limpio)) {
                    error_log("[CUENTA-BANCO] Error: rif_cuenta formato inválido - " . $rif_limpio);
                    $errores['rif_cuenta'] = 'El formato del RIF no es válido. Debe ser como: V123456789, J123456789, G123456789, P123456789 o C123456789';
                } elseif (mb_strlen($rif_limpio) < self::MIN_RIF_CUENTA || mb_strlen($rif_limpio) > self::MAX_RIF_CUENTA) {
                    error_log("[CUENTA-BANCO] Error: rif_cuenta longitud inválida - " . mb_strlen($rif_limpio) . " caracteres");
                    $errores['rif_cuenta'] = 'El RIF debe tener ' . self::MIN_RIF_CUENTA . ' caracteres (letra + 8-9 dígitos)';
                }
            }
        }
        
        if (!isset($datos['telefono_cuenta'])) {
            error_log("[CUENTA-BANCO] Error: telefono_cuenta no está definido");
            $errores['telefono_cuenta'] = 'El teléfono de la cuenta es obligatorio';
        } else {
            $telefono_cuenta = trim($datos['telefono_cuenta']);
            if (empty($telefono_cuenta)) {
                error_log("[CUENTA-BANCO] Error: telefono_cuenta está vacío");
                $errores['telefono_cuenta'] = 'El teléfono de la cuenta no puede estar vacío';
            } else {
                $telefono_limpio = preg_replace('/\D+/', '', $telefono_cuenta);
                if (empty($telefono_limpio)) {
                    error_log("[CUENTA-BANCO] Error: telefono_cuenta no contiene dígitos - " . $telefono_cuenta);
                    $errores['telefono_cuenta'] = 'El teléfono debe contener solo dígitos';
                } elseif (mb_strlen($telefono_limpio) < self::MIN_TELEFONO_CUENTA || mb_strlen($telefono_limpio) > self::MAX_TELEFONO_CUENTA) {
                    error_log("[CUENTA-BANCO] Error: telefono_cuenta longitud inválida - " . mb_strlen($telefono_limpio) . " dígitos");
                    $errores['telefono_cuenta'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO_CUENTA . ' y ' . self::MAX_TELEFONO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['correo_cuenta'])) {
            error_log("[CUENTA-BANCO] Error: correo_cuenta no está definido");
            $errores['correo_cuenta'] = 'El correo de la cuenta es obligatorio';
        } else {
            $correo_cuenta = trim($datos['correo_cuenta']);
            if (empty($correo_cuenta)) {
                error_log("[CUENTA-BANCO] Error: correo_cuenta está vacío");
                $errores['correo_cuenta'] = 'El correo de la cuenta no puede estar vacío';
            } elseif (mb_strlen($correo_cuenta) > self::MAX_CORREO_CUENTA) {
                error_log("[CUENTA-BANCO] Error: correo_cuenta excede longitud máxima - " . mb_strlen($correo_cuenta) . " caracteres");
                $errores['correo_cuenta'] = 'El correo no debe exceder los ' . self::MAX_CORREO_CUENTA . ' caracteres';
            } elseif (!filter_var($correo_cuenta, FILTER_VALIDATE_EMAIL)) {
                error_log("[CUENTA-BANCO] Error: correo_cuenta formato inválido - " . $correo_cuenta);
                $errores['correo_cuenta'] = 'El formato del correo electrónico no es válido';
            }
        }
        
        if (!isset($datos['metodos_pago'])) {
            error_log("[CUENTA-BANCO] Error: metodos_pago no está definido");
            $errores['metodos_pago'] = 'Debe seleccionar al menos un método de pago';
        } else {
            $metodos_pago = $datos['metodos_pago'];
            error_log("[CUENTA-BANCO] metodos_pago recibidos: " . json_encode($metodos_pago));
            
            if (is_array($metodos_pago)) {
                $metodos_pago = array_filter($metodos_pago, function($metodo) {
                    return !empty($metodo);
                });
            }
            if (empty($metodos_pago)) {
                error_log("[CUENTA-BANCO] Error: metodos_pago está vacío después de filtrar");
                $errores['metodos_pago'] = 'Debe seleccionar al menos un método de pago válido';
            } else {
                foreach ($metodos_pago as $metodo) {
                    if (!in_array($metodo, self::TIPOS_PAGO_PERMITIDOS)) {
                        error_log("[CUENTA-BANCO] Error: método de pago no permitido - " . $metodo);
                        $errores['metodos_pago'] = 'Los métodos de pago permitidos son: ' . implode(', ', self::TIPOS_PAGO_PERMITIDOS);
                        break;
                    }
                }
            }
        }
        
        error_log("[CUENTA-BANCO] Validación completada. Errores encontrados: " . json_encode($errores));
        return $errores;
    }
    
    private function validarConsultar($datos) {
        $errores = [];
        
        if (isset($datos['id_cuenta'])) {
            if (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
                $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
            }
        }
        
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        return $errores;
    }
    
    private function validarDetallar($datos) {
        $errores = [];
        
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        return $errores;
    }
    
    private function validarModificar($datos) {
        $errores = [];
        
        // Validar ID de la cuenta
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        // Validar nombre del banco
        if (!isset($datos['nombre_banco'])) {
            $errores['nombre_banco'] = 'El nombre del banco es obligatorio';
        } else {
            $nombre_banco = trim($datos['nombre_banco']);
            if (empty($nombre_banco)) {
                $errores['nombre_banco'] = 'El nombre del banco no puede estar vacío';
            } elseif (mb_strlen($nombre_banco) < self::MIN_NOMBRE_BANCO || mb_strlen($nombre_banco) > self::MAX_NOMBRE_BANCO) {
                $errores['nombre_banco'] = 'El nombre del banco debe tener entre ' . self::MIN_NOMBRE_BANCO . ' y ' . self::MAX_NOMBRE_BANCO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_banco)) {
                $errores['nombre_banco'] = 'El nombre del banco solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (!isset($datos['numero_cuenta'])) {
            $errores['numero_cuenta'] = 'El número de cuenta es obligatorio';
        } else {
            $numero_cuenta = trim($datos['numero_cuenta']);
            if (empty($numero_cuenta)) {
                $errores['numero_cuenta'] = 'El número de cuenta no puede estar vacío';
            } else {
                $numero_limpio = preg_replace('/\D+/', '', $numero_cuenta);
                if (empty($numero_limpio)) {
                    $errores['numero_cuenta'] = 'El número de cuenta debe contener solo dígitos';
                } elseif (mb_strlen($numero_limpio) < self::MIN_NUMERO_CUENTA || mb_strlen($numero_limpio) > self::MAX_NUMERO_CUENTA) {
                    $errores['numero_cuenta'] = 'El número de cuenta debe tener entre ' . self::MIN_NUMERO_CUENTA . ' y ' . self::MAX_NUMERO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['rif_cuenta'])) {
            $errores['rif_cuenta'] = 'El RIF de la cuenta es obligatorio';
        } else {
            $rif_cuenta = trim($datos['rif_cuenta']);
            if (empty($rif_cuenta)) {
                $errores['rif_cuenta'] = 'El RIF de la cuenta no puede estar vacío';
            } else {
                $rif_limpio = str_replace(['-', ' '], '', strtoupper($rif_cuenta));
                if (!preg_match('/^[VEJGCP][0-9]{8,9}$/', $rif_limpio)) {
                    $errores['rif_cuenta'] = 'El formato del RIF no es válido. Debe ser como: V123456789, J123456789, G123456789, P123456789 o C123456789';
                } elseif (mb_strlen($rif_limpio) < self::MIN_RIF_CUENTA || mb_strlen($rif_limpio) > self::MAX_RIF_CUENTA) {
                    $errores['rif_cuenta'] = 'El RIF debe tener ' . self::MIN_RIF_CUENTA . ' caracteres (letra + 8-9 dígitos)';
                }
            }
        }
        
        if (!isset($datos['telefono_cuenta'])) {
            $errores['telefono_cuenta'] = 'El teléfono de la cuenta es obligatorio';
        } else {
            $telefono_cuenta = trim($datos['telefono_cuenta']);
            if (empty($telefono_cuenta)) {
                $errores['telefono_cuenta'] = 'El teléfono de la cuenta no puede estar vacío';
            } else {
                $telefono_limpio = preg_replace('/\D+/', '', $telefono_cuenta);
                if (empty($telefono_limpio)) {
                    $errores['telefono_cuenta'] = 'El teléfono debe contener solo dígitos';
                } elseif (mb_strlen($telefono_limpio) < self::MIN_TELEFONO_CUENTA || mb_strlen($telefono_limpio) > self::MAX_TELEFONO_CUENTA) {
                    $errores['telefono_cuenta'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO_CUENTA . ' y ' . self::MAX_TELEFONO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['correo_cuenta'])) {
            $errores['correo_cuenta'] = 'El correo de la cuenta es obligatorio';
        } else {
            $correo_cuenta = trim($datos['correo_cuenta']);
            if (empty($correo_cuenta)) {
                $errores['correo_cuenta'] = 'El correo de la cuenta no puede estar vacío';
            } elseif (mb_strlen($correo_cuenta) > self::MAX_CORREO_CUENTA) {
                $errores['correo_cuenta'] = 'El correo no debe exceder los ' . self::MAX_CORREO_CUENTA . ' caracteres';
            } elseif (!filter_var($correo_cuenta, FILTER_VALIDATE_EMAIL)) {
                $errores['correo_cuenta'] = 'El formato del correo electrónico no es válido';
            }
        }
        
        if (isset($datos['metodos_pago'])) {
            $metodos_pago = $datos['metodos_pago'];
            if (is_array($metodos_pago)) {
                $metodos_pago = array_filter($metodos_pago, function($metodo) {
                    return !empty($metodo);
                });
            }
            if (!empty($metodos_pago)) {
                foreach ($metodos_pago as $metodo) {
                    if (!in_array($metodo, self::TIPOS_PAGO_PERMITIDOS)) {
                        $errores['metodos_pago'] = 'Los métodos de pago permitidos son: ' . implode(', ', self::TIPOS_PAGO_PERMITIDOS);
                        break;
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarEliminar($datos) {
        $errores = [];
        
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        return $errores;
    }
    
    private function validarCambiarEstadoCuenta($datos) {
        $errores = [];
        
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        if (!isset($datos['estado'])) {
            $errores['estado'] = 'El estado es obligatorio';
        } elseif (!in_array($datos['estado'], self::ESTADOS_PERMITIDOS)) {
            $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
        }
        
        return $errores;
    }
    
    private function validarReporte($datos) {
        $errores = [];
        
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }

        if (isset($datos['fecha_inicio'])) {
            $fecha_inicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fecha_inicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        if (isset($datos['fecha_fin'])) {
            $fecha_fin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fecha_fin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fecha_inicio = new \DateTime($datos['fecha_inicio']);
            $fecha_fin = new \DateTime($datos['fecha_fin']);
            if ($fecha_fin < $fecha_inicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarCuenta($datos) {
        return $this->validarRegistrar($datos);
    }
    
    public function validarConsultarCuentas($datos) {
        return $this->validarConsultar($datos);
    }
    
    public function validarDetallarCuenta($datos) {
        return $this->validarDetallar($datos);
    }
    
    public function validarModificarCuenta($datos) {
        return $this->validarModificar($datos);
    }
    
    public function validarEliminarCuenta($datos) {
        return $this->validarEliminar($datos);
    }
    
    public function validarCambiarEstado($datos) {
        return $this->validarCambiarEstadoCuenta($datos);
    }
    
    public function validarReporteCuentas($datos) {
        return $this->validarReporte($datos);
    }

    // 1. El método público ahora recibe obligatoriamente el ID del usuario que audita
    public function registrarCuentabanco($id_usuario_auditor) {
        return $this->r_cuentabanco($id_usuario_auditor); 
    }

    // 2. El método privado procesa el CALL enviando el parámetro extra
    private function r_cuentabanco($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor) {

            // Cifrar datos personales antes de insertar
            $nombre_banco_cifrado = $this->encryption->encrypt($this->nombre_banco);
            $telefono_cuenta_cifrado = $this->encryption->encrypt($this->telefono_cuenta);
            $correo_cuenta_cifrado = $this->encryption->encrypt($this->correo_cuenta);
            
            $sql = "CALL sp_registrar_cuenta(
                :nombre_banco, 
                :numero_cuenta, 
                :rif_cuenta, 
                :telefono_cuenta, 
                :correo_cuenta, 
                :metodos, 
                :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_banco', $nombre_banco_cifrado);
            $stmt->bindParam(':numero_cuenta', $this->numero_cuenta);
            $stmt->bindParam(':rif_cuenta', $this->rif_cuenta);
            $stmt->bindParam(':telefono_cuenta', $telefono_cuenta_cifrado);
            $stmt->bindParam(':correo_cuenta', $correo_cuenta_cifrado);
            
            // SOLUCCIÓN CRÍTICA: Convertir el Array de checkboxes a String separado por comas para el tipo SET
            $metodosString = is_array($this->metodos_pago) ? implode(',', $this->metodos_pago) : $this->metodos_pago;
            $stmt->bindParam(':metodos', $metodosString);
            
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor(); // Liberar la conexión adecuadamente despues de ejecutar
            return $resultado;
        }, false);
    }

    public function existeNumeroCuenta($numero_cuenta, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($numero_cuenta, $excluir_id){
            $sql = "SELECT COUNT(*) FROM tbl_cuentas WHERE numero_cuenta = ?";
            $params = [$numero_cuenta];
            if ($excluir_id !== null) {
                $sql .= " AND id_cuenta != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $existe = $stmt->fetchColumn() > 0;
            $stmt->closeCursor();
            return $existe;
        });
    }

    public function obtenerUltimaCuenta() {
        return $this->obtUltimaCuenta(); 
    }
    private function obtUltimaCuenta() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_cuentas ORDER BY id_cuenta DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $cuenta ? $cuenta : null;
        });
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    public function obtenerCuentaPorId($id_cuenta) {
        return $this->cuentaporid($id_cuenta); 
    }

    private function cuentaporid($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cuenta){
            // REEMPLAZO: Invocamos el procedimiento almacenado específico por ID
            $sql = "CALL sp_obtener_cuenta_por_id(:id_cuenta)";
            $stmt = $pdo->prepare($sql);
            
            // Vinculamos de manera segura tipando el parámetro como entero
            $stmt->bindParam(':id_cuenta', $id_cuenta, \PDO::PARAM_INT);
            $stmt->execute();
            
            $cuenta_obt = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // ¡CRÍTICO! Liberamos la conexión de los metadatos del procedimiento
            $stmt->closeCursor(); 
            
            return $cuenta_obt;
        }, false);

        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }

        return $resultado;
    }

    public function consultarCuentabanco() {
        return $this->c_cuentabanco(); 
    }

    public function cuentabancoConsultar() {
        return $this->c_cuentabanco();
    }

    private function c_cuentabanco() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "CALL sp_consultar_cuenta()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $cuentas_obt = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // ¡CRÍTICO! Limpia el canal de MySQL para que la conexión quede libre
            $stmt->closeCursor(); 
            
            return $cuentas_obt;
        }, false);

        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        
        return $resultado;
    }

    public function cuentasReportes() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT 
            c.id_cuenta,
            c.nombre_banco,
            c.numero_cuenta,
            c.metodos,
            dp.fecha,
            dp.tipo,
            dp.monto,
            dp.estatus,
            f.cliente,
            cl.nombre,
            cl.cedula
            FROM tbl_cuentas c 
            INNER JOIN  tbl_detalles_pago dp ON dp.id_cuenta = c.id_cuenta
            INNER JOIN  tbl_facturas f ON dp.id_factura = f.id_factura
            INNER JOIN tbl_clientes cl ON cl.id_clientes = f.cliente";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cuentas_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $cuentas_obt;
        });
        
        // Descifrar datos personales de cuentas bancarias y del cliente
        // Incluimos campos de tbl_cuentas y tbl_clientes
        $camposADescifrar = array_merge(self::CAMPOS_CIFRADOS, ['nombre']);
        $resultado = $this->encryption->decryptResults($resultado, $camposADescifrar);
        
        return $resultado;
    }

    public function modificarCuentabanco($id_cuenta, $id_usuario_auditor) {
        return $this->m_cuentabanco($id_cuenta, $id_usuario_auditor); 
    }

    private function m_cuentabanco($id_cuenta, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cuenta, $id_usuario_auditor){

            // Cifrar datos personales antes de actualizar
            $nombre_banco_cifrado = $this->encryption->encrypt($this->nombre_banco);
            $telefono_cuenta_cifrado = $this->encryption->encrypt($this->telefono_cuenta);
            $correo_cuenta_cifrado = $this->encryption->encrypt($this->correo_cuenta);

            $sql = "CALL sp_modificar_cuenta(
                :id_cuenta, :nombre_banco, :numero_cuenta, :rif_cuenta, 
                :telefono_cuenta, :correo_cuenta, :metodos, :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_cuenta', $id_cuenta, \PDO::PARAM_INT);
            $stmt->bindParam(':nombre_banco', $nombre_banco_cifrado);
            $stmt->bindParam(':numero_cuenta', $this->numero_cuenta);
            $stmt->bindParam(':rif_cuenta', $this->rif_cuenta);
            $stmt->bindParam(':telefono_cuenta', $telefono_cuenta_cifrado);
            $stmt->bindParam(':correo_cuenta', $correo_cuenta_cifrado);
            
            // SOLUCCIÓN CRÍTICA: Convertir el Array de checkboxes a String separado por comas para el tipo SET
            $metodosString = is_array($this->metodos_pago) ? implode(',', $this->metodos_pago) : $this->metodos_pago;
            $stmt->bindParam(':metodos', $metodosString);
            
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            $resultado = $stmt->execute();
            $stmt->closeCursor(); // Liberar la conexión adecuadamente despues de ejecutar
            return $resultado;
        }, false);
    }

    public function eliminarCuentabanco($id_cuenta, $id_usuario_auditor) {
        return $this->e_cuentabanco($id_cuenta, $id_usuario_auditor);
    }

    private function e_cuentabanco($id_cuenta, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cuenta, $id_usuario_auditor) {
            $pagosAsociados = $this->tienePagosAsociados($id_cuenta);
            if ($pagosAsociados['tiene_pagos']) {
                return [
                    'status' => 'error', 
                    'type' => 'business_rule',
                    'message' => 'No se puede eliminar la cuenta porque tiene pagos asociados.',
                    'pagos' => $pagosAsociados['pagos'],
                    'total_pagos' => $pagosAsociados['total']
                ];
            }
            
            // Si no tiene pagos asociados, llamamos de manera segura al procedimiento físico de borrado
            $sql = "CALL sp_eliminar_cuenta(:id_cuenta, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_cuenta', $id_cuenta, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            $result = $stmt->execute();
            $stmt->closeCursor();
            
            if ($result) {
                return ['status' => 'success'];
            } else {
                return [
                    'status' => 'error', 
                    'type' => 'database_error',
                    'message' => 'Error al ejecutar la eliminación en el servidor.',
                    'pagos' => [],
                    'total_pagos' => 0
                ];
            }
        }, false);
    }

    private function tienePagosAsociados($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try{
                $sql = "SELECT COUNT(*) as total FROM tbl_detalles_pago WHERE id_cuenta = :id_cuenta";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                $stmt->execute();
                
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $count = (int)$resultado['total'];

                if ($count > 0) {
                    // Si hay pagos, obtenemos los detalles de los últimos 5 pagos
                    $sqlPagos = "SELECT dp.id_detalle_pago as id_pago, dp.monto, dp.fecha, 
                                cl.nombre, cl.apellido, cl.cedula, f.numero_factura
                            FROM tbl_detalles_pago dp
                            INNER JOIN tbl_facturas f ON dp.id_factura = f.id_factura
                            INNER JOIN tbl_clientes cl ON f.cliente = cl.id_clientes
                            WHERE dp.id_cuenta = :id_cuenta 
                            ORDER BY dp.fecha DESC 
                            LIMIT 5";
                    $stmtPagos = $pdo->prepare($sqlPagos);
                    $stmtPagos->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                    $stmtPagos->execute();
                    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
                    $stmtPagos->closeCursor();
                    
                    return [
                        'tiene_pagos' => true,
                        'pagos' => $pagos,
                        'total' => $count
                    ];
                }

                // Si no hay pagos en tbl_detalles_pago, verificamos en tbl_pagos por compatibilidad
                $sql = "SELECT COUNT(*) as total FROM tbl_pagos WHERE id_cuenta = :id_cuenta";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                $stmt->execute();
                
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $count = (int)$resultado['total'];

                if ($count > 0) {
                    $sqlPagos = "SELECT p.id_pago, p.monto, p.fecha_pago as fecha, 
                                c.nombre, c.apellido, c.cedula, '' as numero_factura
                            FROM tbl_pagos p
                            INNER JOIN tbl_clientes c ON p.id_cliente = c.id_cliente
                            WHERE p.id_cuenta = :id_cuenta 
                            ORDER BY p.fecha_pago DESC 
                            LIMIT 5";
                    $stmtPagos = $pdo->prepare($sqlPagos);
                    $stmtPagos->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                    $stmtPagos->execute();
                    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
                    $stmtPagos->closeCursor();
                    
                    return [
                        'tiene_pagos' => true,
                        'pagos' => $pagos,
                        'total' => $count
                    ];
                }

                return ['tiene_pagos' => false];
            } catch (PDOException $e) {
                error_log("Error al verificar pagos asociados: " . $e->getMessage());
                return ['tiene_pagos' => false];
            }
        });
    }

    public function verificarEstado() {
        return $this->v_estadoCuenta(); 
    }
    private function v_estadoCuenta() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SHOW COLUMNS FROM tbl_cuentas LIKE 'estado'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $columnExists = $stmt->rowCount() == 0;
            $stmt->closeCursor();

            if ($columnExists) {
                $alterSql = "ALTER TABLE tbl_cuentas 
                ADD estado ENUM('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'";
                $pdo->exec($alterSql);
            }
        });
    }

    public function cambiarEstado($nuevoEstado, $id_usuario_auditor) {
        return $this->estadoCuenta($nuevoEstado, $id_usuario_auditor); 
    }

    private function estadoCuenta($nuevoEstado, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstado, $id_usuario_auditor) {
            $sql = "CALL sp_cambiar_estado_cuenta(:id_cuenta, :estado, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':estado', $nuevoEstado);
            $stmt->bindParam(':id_cuenta', $this->id_cuenta, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }
}
?>