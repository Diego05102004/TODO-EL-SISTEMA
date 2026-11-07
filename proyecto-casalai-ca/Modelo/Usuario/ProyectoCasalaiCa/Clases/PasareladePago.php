<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDeDespacho;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class PasareladePago extends Factura {
    private $id_detalles;
    private $cuenta;
    private $factura;
    private $tipo;
    private $observaciones;    
    private $referencia;    
    private $fecha;
    private $estatus;
    private $comprobante;
    private $monto;
    private $cedula;
    
    // Constantes de validación
    const MAX_ID_DETALLES = 999999999;
    const MIN_ID_DETALLES = 1;
    const MAX_ID_FACTURA = 999999999;
    const MIN_ID_FACTURA = 1;
    const MAX_ID_CUENTA = 999999999;
    const MIN_ID_CUENTA = 1;
    const MAX_REFERENCIA = 50;
    const MIN_REFERENCIA = 1;
    const MAX_OBSERVACIONES = 500;
    const MIN_OBSERVACIONES = 0;
    const MAX_MONTO = 999999999.99;
    const MIN_MONTO = 0.01;
    const TIPOS_VALIDOS = ['Transferencia', 'Deposito', 'Efectivo', 'Tarjeta', 'Cheque', 'Otro'];
    const ESTADOS_VALIDOS = ['Pendiente', 'En Proceso', 'Pago Procesado', 'Anulado'];
    const ESTADOS_VALIDOS_CAMBIO = ['Pendiente', 'En Proceso', 'Pago Procesado', 'Anulado'];
    const EXTENSIONES_COMPROBANTE = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    
    public function getIdDetalles() {
        return $this->id_detalles;
    }

    public function setIdDetalles($id_detalles) {
        $this->id_detalles = $id_detalles;
    }

    public function getCuenta() {
        return $this->cuenta;
    }

    public function setCuenta($cuenta) {
        $this->cuenta = $cuenta;
    }

    public function getFactura() {
        return $this->factura;
    }

    public function setFactura($factura) {
        $this->factura = $factura;
    }

    public function getTipo() {
        return $this->tipo;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function getObservaciones() {
        return $this->observaciones;
    }

    public function setObservaciones($observaciones) {
        $this->observaciones = $observaciones;
    }

    public function getReferencia() {
        return $this->referencia;
    }

    public function setReferencia($referencia) {
        $this->referencia = $referencia;
    }

    public function getFecha() {
        return $this->fecha;
    }

    public function setFecha($fecha) {
        $this->fecha = $fecha;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function setEstatus($estatus) {
        $this->estatus = $estatus;
    }

    public function getComprobante() {
        return $this->comprobante;
    }
    public function setComprobante($comprobante) {
        $this->comprobante = $comprobante;
    }

    public function getMonto() {
        return $this->monto;
    }
    public function setMonto($monto) {
        $this->monto = $monto;
    }

    public function getCedula() {
        return $this->cedula;
    }
    public function setCedula($cedula) {
        $this->cedula = $cedula;
    }

    private function validarPago($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['pago'] = 'Los datos del pago deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID de detalles
        if (isset($datos['id_detalles'])) {
            $id_detalles = (int)$datos['id_detalles'];
            if ($id_detalles < self::MIN_ID_DETALLES || $id_detalles > self::MAX_ID_DETALLES) {
                $errores['id_detalles'] = 'El ID del pago debe ser un número entre ' . self::MIN_ID_DETALLES . ' y ' . self::MAX_ID_DETALLES;
            }
        }
        
        // Validar ID de factura
        if (isset($datos['id_factura'])) {
            $id_factura = (int)$datos['id_factura'];
            if ($id_factura < self::MIN_ID_FACTURA || $id_factura > self::MAX_ID_FACTURA) {
                $errores['id_factura'] = 'El ID de la factura debe ser un número entre ' . self::MIN_ID_FACTURA . ' y ' . self::MAX_ID_FACTURA;
            }
        }
        
        // Validar ID de cuenta
        if (isset($datos['id_cuenta'])) {
            $id_cuenta = (int)$datos['id_cuenta'];
            if ($id_cuenta < self::MIN_ID_CUENTA || $id_cuenta > self::MAX_ID_CUENTA) {
                $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número entre ' . self::MIN_ID_CUENTA . ' y ' . self::MAX_ID_CUENTA;
            }
        }
        
        // Validar referencia
        if (isset($datos['referencia'])) {
            $referencia = trim((string)$datos['referencia']);
            if (mb_strlen($referencia) < self::MIN_REFERENCIA) {
                $errores['referencia'] = 'La referencia debe tener al menos ' . self::MIN_REFERENCIA . ' caracteres';
            } elseif (mb_strlen($referencia) > self::MAX_REFERENCIA) {
                $errores['referencia'] = 'La referencia no debe exceder los ' . self::MAX_REFERENCIA . ' caracteres';
            }
        }
        
        // Validar tipo
        if (isset($datos['tipo'])) {
            $tipo = trim((string)$datos['tipo']);
            if (!in_array($tipo, self::TIPOS_VALIDOS)) {
                $errores['tipo'] = 'El tipo de pago no es válido. Tipos permitidos: ' . implode(', ', self::TIPOS_VALIDOS);
            }
        }
        
        // Validar observaciones
        if (isset($datos['observaciones'])) {
            $observaciones = trim((string)$datos['observaciones']);
            if (mb_strlen($observaciones) > self::MAX_OBSERVACIONES) {
                $errores['observaciones'] = 'Las observaciones no deben exceder los ' . self::MAX_OBSERVACIONES . ' caracteres';
            }
        }
        
        // Validar monto
        if (isset($datos['monto'])) {
            $monto = (float)$datos['monto'];
            if ($monto < self::MIN_MONTO || $monto > self::MAX_MONTO) {
                $errores['monto'] = 'El monto debe estar entre ' . self::MIN_MONTO . ' y ' . self::MAX_MONTO;
            }
        }
        
        // Validar estatus
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        // Validar fecha
        if (isset($datos['fecha'])) {
            $fecha = trim((string)$datos['fecha']);
            if ($fecha === '') {
                $errores['fecha'] = 'La fecha es obligatoria';
            } elseif (!$this->validarFormatoFecha($fecha)) {
                $errores['fecha'] = 'La fecha no tiene un formato válido (AAAA-MM-DD)';
            } elseif (!$this->validarFechaNoFutura($fecha)) {
                $errores['fecha'] = 'No se permiten fechas futuras';
            }
        }
        
        return $errores;
    }
    
    private function validarFormatoFecha($fecha) {
        $formato = 'Y-m-d';
        $fechaObj = DateTime::createFromFormat($formato, $fecha);
        return $fechaObj && $fechaObj->format($formato) === $fecha;
    }
    
    private function validarFechaNoFutura($fecha) {
        $hoy = new DateTime();
        $fechaPago = new DateTime($fecha);
        return $fechaPago <= $hoy;
    }
    
    public function validarComprobante($archivo) {
        $errores = [];
        
        if (!is_array($archivo)) {
            $errores['comprobante'] = 'Los datos del comprobante deben ser un arreglo';
            return $errores;
        }
        
        if (!isset($archivo['name']) || !isset($archivo['tmp_name']) || !isset($archivo['error'])) {
            $errores['comprobante'] = 'Estructura del comprobante inválida';
            return $errores;
        }
        
        // Validar que no haya errores de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores['comprobante'] = 'Error al subir el comprobante: ' . $this->getUploadErrorMessage($archivo['error']);
            return $errores;
        }
        
        // Validar tamaño máximo (5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($archivo['size'] > $maxSize) {
            $errores['comprobante'] = 'El comprobante no debe exceder los 5MB';
            return $errores;
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONES_COMPROBANTE)) {
            $errores['comprobante'] = 'La extensión del archivo no es permitida. Extensiones permitidas: ' . implode(', ', self::EXTENSIONES_COMPROBANTE);
        }
        
        return $errores;
    }
    
    private function getUploadErrorMessage($errorCode) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede escribir el archivo',
            UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión',
            UPLOAD_ERR_NO_TMP_FILE => 'No hay archivo temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede mover el archivo'
        ];
        
        return $messages[$errorCode] ?? 'Error desconocido';
    }
    
    // Métodos públicos de validación
    public function validarConsultarPagos($datos) {
        $errores = [];
        
        // Para consultar pagos, podemos validar por cédula o por factura
        if (isset($datos['cedula'])) {
            $cedula = trim((string)$datos['cedula']);
            if ($cedula === '') {
                $errores['cedula'] = 'La cédula es obligatoria para consultar pagos';
            }
        }
        
        if (isset($datos['id_factura'])) {
            $id_factura = (int)$datos['id_factura'];
            if ($id_factura < self::MIN_ID_FACTURA || $id_factura > self::MAX_ID_FACTURA) {
                $errores['id_factura'] = 'El ID de la factura debe ser un número entre ' . self::MIN_ID_FACTURA . ' y ' . self::MAX_ID_FACTURA;
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para registrar pago
     */
    public function validarRegistrarPago($datos) {
        $errores = [];
        
        // Validar ID de la factura
        if (!isset($datos['id_factura'])) {
            $errores['id_factura'] = 'El ID de la factura es obligatorio';
        } elseif (!is_numeric($datos['id_factura']) || $datos['id_factura'] <= 0) {
            $errores['id_factura'] = 'El ID de la factura debe ser un número positivo';
        }
        
        // Validar estatus del pago
        if (isset($datos['estatus_pago'])) {
            $estatusPago = trim($datos['estatus_pago']);
            if (!empty($estatusPago) && !in_array($estatusPago, ['En Proceso', 'Pago Incompleto', 'Pago Procesado', 'Pago No Encontrado'])) {
                $errores['estatus_pago'] = 'El estatus del pago debe ser uno de: En Proceso, Pago Incompleto, Pago Procesado, Pago No Encontrado';
            }
        }
        
        // Validar observaciones del pago
        if (isset($datos['observaciones'])) {
            $observaciones = trim($datos['observaciones']);
            if (mb_strlen($observaciones) > 500) {
                $errores['observaciones'] = 'Las observaciones no deben exceder los 500 caracteres';
            }
        }
        
        return $errores;
    }
    
    public function validarCambiarEstatus($datos) {
        $errores = [];
        
        // Validar ID del pago (obligatorio)
        if (!isset($datos['id_detalles'])) {
            $errores['id_detalles'] = 'El ID del pago es obligatorio';
        } else {
            $id_detalles = (int)$datos['id_detalles'];
            if ($id_detalles < self::MIN_ID_DETALLES || $id_detalles > self::MAX_ID_DETALLES) {
                $errores['id_detalles'] = 'El ID del pago debe ser un número entre ' . self::MIN_ID_DETALLES . ' y ' . self::MAX_ID_DETALLES;
            }
        }
        
        // Validar nuevo estatus (obligatorio)
        if (!isset($datos['estatus'])) {
            $errores['estatus'] = 'El nuevo estatus es obligatorio';
        } else {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS_CAMBIO)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS_CAMBIO);
            }
        }
        
        return $errores;
    }
    
    public function validarIngresarPagos($pagos) {
        $errores = [];
        
        if (!is_array($pagos)) {
            $errores['pagos'] = 'Los datos de pagos deben ser un arreglo';
            return $errores;
        }
        
        if (empty($pagos)) {
            $errores['pagos'] = 'Debe proporcionar al menos un pago';
            return $errores;
        }
        
        foreach ($pagos as $index => $pago) {
            $errores_pago = $this->validarPago($pago);
            if (!empty($errores_pago)) {
                foreach ($errores_pago as $campo => $error) {
                    $errores["pago_{$index}_{$campo}"] = $error;
                }
            }
        }
        
        return $errores;
    }
    
    // Métodos auxiliares
    private function verificarPagoExistente($id_detalles) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_detalles_pago WHERE id_detalles = ?");
                $stmt->execute([$id_detalles]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        });
    }
    
    private function verificarFacturaExistente($id_factura) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_facturas WHERE id_factura = ?");
                $stmt->execute([$id_factura]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            } 
        });
    }
    
    private function verificarCuentaExistente($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_cuentas WHERE id_cuenta = ?");
                $stmt->execute([$id_cuenta]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        });
    }

    public function pasarelaTransaccion($transaccion) {
        switch ($transaccion) {
            case 'Ingresar':
                return $this->pagoIngresar();                 
            case 'Consultar':
                return $this->pagoConsultar();
                case 'ConsultarTodos':
                return $this->pagoConsultarTodos();
            case 'Modificar':
                return $this->pagoModificar();
            case 'Procesar':
                return $this->pagoProcesar();
            default:
                throw new Exception("Transacción no válida.");
        }
    }

    private function pagoIngresar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                error_log("Intentando insertar pago con datos: " . json_encode([
                    'factura' => $this->factura,
                    'cuenta' => $this->cuenta,
                    'observaciones' => $this->observaciones,
                    'tipo' => $this->tipo,
                    'referencia' => $this->referencia,
                    'fecha' => $this->fecha,
                    'comprobante' => $this->comprobante,
                    'monto' => $this->monto
                ]));
                
                $stmt = $pdo->prepare("
                    INSERT INTO `tbl_detalles_pago`
                    (`id_factura`, `id_cuenta`, `observaciones`, `tipo`, `referencia`, `fecha`, `comprobante`, `monto`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $resultado = $stmt->execute([
                    $this->factura,
                    $this->cuenta,
                    $this->observaciones,
                    $this->tipo,
                    $this->referencia,
                    $this->fecha,
                    $this->comprobante,
                    $this->monto
                ]);

                if (!$resultado) {
                    error_log("Error en INSERT: " . json_encode($stmt->errorInfo()));
                    return false;
                }

                error_log("INSERT exitoso, actualizando factura");

                $updateStmt = $pdo->prepare("
                    UPDATE `tbl_facturas` 
                    SET `estatus` = 'En Proceso' 
                    WHERE `id_factura` = ?
                ");
                $updateResultado = $updateStmt->execute([$this->factura]);

                if (!$updateResultado) {
                    error_log("Error en UPDATE factura: " . json_encode($updateStmt->errorInfo()));
                }

                return true;
            } catch (PDOException $e) {
                error_log("Error en pagoIngresar: " . $e->getMessage());
                error_log("Código SQLSTATE: " . $e->getCode());
                return false;
            } 
        });
    }

    public function pagoIngresarMovil($data = []) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($data) {
            // Extraer datos del parámetro $data
            $factura = $data['factura'] ?? null;
            $cuenta = $data['cuenta'] ?? null;
            $observaciones = $data['observaciones'] ?? '';
            $tipo = $data['tipo'] ?? '';
            $referencia = $data['referencia'] ?? '';
            $fecha = $data['fecha'] ?? date('Y-m-d');
            $comprobante = $data['comprobante'] ?? '';
            $monto = $data['monto'] ?? 0;

            // Validaciones básicas
            if (empty($factura)) {
                throw new PDOException("El ID de factura es requerido.");
            }

            if (empty($cuenta)) {
                throw new PDOException("El ID de cuenta es requerido.");
            }

            if (empty($monto) || $monto <= 0) {
                throw new PDOException("El monto debe ser mayor a 0.");
            }

            if (empty($tipo)) {
                throw new PDOException("El tipo de pago es requerido.");
            }

            // Verificar que la factura existe
            $stmtFactura = $pdo->prepare("SELECT id_factura FROM tbl_facturas WHERE id_factura = ?");
            $stmtFactura->execute([$factura]);
            if (!$stmtFactura->fetch(PDO::FETCH_ASSOC)) {
                throw new PDOException("La factura con ID $factura no existe.");
            }

            // Verificar que la cuenta existe en la tabla real del proyecto
            $stmtCuenta = $pdo->prepare("SELECT id_cuenta FROM tbl_cuentas WHERE id_cuenta = ?");
            $stmtCuenta->execute([$cuenta]);
            if (!$stmtCuenta->fetch(PDO::FETCH_ASSOC)) {
                throw new PDOException("La cuenta con ID $cuenta no existe.");
            }

            try {
                // Insertar el pago
                $stmt = $pdo->prepare("
                    INSERT INTO `tbl_detalles_pago`
                    (`id_factura`, `id_cuenta`, `observaciones`, `tipo`, `referencia`, `fecha`, `comprobante`, `monto`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $resultado = $stmt->execute([
                    $factura,
                    $cuenta,
                    $observaciones,
                    $tipo,
                    $referencia,
                    $fecha,
                    $comprobante,
                    $monto
                ]);

                if (!$resultado) {
                    throw new PDOException("Error al insertar el pago: " . json_encode($stmt->errorInfo()));
                }

                $pago_id = $pdo->lastInsertId();

                // Actualizar estatus de la factura
                $updateStmt = $pdo->prepare("
                    UPDATE `tbl_facturas` 
                    SET `estatus` = 'En Proceso' 
                    WHERE `id_factura` = ?
                ");
                $updateResultado = $updateStmt->execute([$factura]);

                if (!$updateResultado) {
                    throw new PDOException("Error al actualizar la factura: " . json_encode($updateStmt->errorInfo()));
                }

                return [
                    'pago_id' => $pago_id,
                    'factura_id' => $factura,
                    'cuenta_id' => $cuenta,
                    'monto' => $monto,
                    'tipo' => $tipo,
                    'fecha' => $fecha,
                    'referencia' => $referencia,
                    'estatus_factura' => 'En Proceso'
                ];

            } catch (PDOException $e) {
                throw new PDOException("Error en pagoIngresar: " . $e->getMessage());
            }
        });
    }
    private function pagoConsultar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT 
                    dp.id_detalles, 
                    f.id_factura, 
                    cl.nombre, 
                    cl.cedula, 
                    dp.id_cuenta, 
                    c.nombre_banco AS nombre_cuenta, 
                    dp.observaciones, 
                    dp.tipo, 
                    dp.referencia, 
                    dp.fecha, 
                    dp.comprobante,
                    dp.monto, 
                    dp.estatus 
                FROM tbl_detalles_pago dp 
                INNER JOIN tbl_cuentas c ON dp.id_cuenta = c.id_cuenta 
                INNER JOIN tbl_facturas f ON dp.id_factura = f.id_factura 
                INNER JOIN tbl_clientes cl ON f.cliente = cl.id_clientes 
                WHERE cl.cedula = :cedula";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    private function pagoConsultarTodos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT dp.id_detalles, f.id_factura, cl.nombre, cl.cedula, dp.id_cuenta, c.nombre_banco AS nombre_cuenta, dp.observaciones, dp.tipo, dp.referencia, dp.fecha, dp.comprobante,dp.monto, dp.estatus FROM tbl_detalles_pago dp INNER JOIN tbl_cuentas c ON dp.id_cuenta = c.id_cuenta INNER JOIN tbl_facturas f ON dp.id_factura = f.id_factura INNER JOIN tbl_clientes cl ON f.cliente = cl.id_clientes;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    private function pagoModificar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "UPDATE `tbl_detalles_pago` 
                SET `id_factura` = :id_factura,
                    `id_cuenta` = :id_cuenta,
                    `tipo` = :tipo,
                    `referencia` = :referencia,
                    `fecha` = :fecha
                WHERE id_detalles = :id_detalles";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_detalles', $this->id_detalles);
            $stmt->bindParam(':id_factura', $this->factura);
            $stmt->bindParam(':id_cuenta', $this->cuenta);
            $stmt->bindParam(':tipo', $this->tipo);
            $stmt->bindParam(':referencia', $this->referencia);
            $stmt->bindParam(':fecha', $this->fecha);
            return $stmt->execute();
        });
    }

    public function validarCodigoReferencia(){
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_detalles_pago WHERE referencia = :referencia";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':referencia', $this->referencia);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }

    private function pagoProcesar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "UPDATE `tbl_detalles_pago` 
                    SET `estatus` = :estatus
                    WHERE id_detalles = :id_detalles";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->bindParam(':id_detalles', $this->id_detalles);
            $resultado = $stmt->execute();

            error_log("pagoProcesar - Actualizando estatus pago {$this->id_detalles} a '{$this->estatus}': " . ($resultado ? 'EXITO' : 'FALLO'));

            if ($resultado) {
                error_log("pagoProcesar - Llamando a facturaProcesar para factura {$this->factura}");
                $this->facturaProcesar($this->factura, $this->estatus);
                
                error_log("pagoProcesar - Verificando si estatus es 'Pago Procesado': actual='{$this->estatus}'");
                if ($this->estatus === 'Pago Procesado') {
                    try {
                        error_log("pagoProcesar - Creando orden de despacho para factura {$this->factura}");
                        $ordenDespacho = new OrdenDespacho();
                        $resultadoOrden = $ordenDespacho->crearPorFactura($this->factura);
                        error_log("pagoProcesar - Resultado crearPorFactura: " . json_encode($resultadoOrden));
                    } catch (Exception $e) {
                        error_log('Error creando orden de despacho: ' . $e->getMessage());
                        error_log('Stack trace: ' . $e->getTraceAsString());
                    }
                } else {
                    error_log("pagoProcesar - Estatus no es 'Pago Procesado', no se crea orden de despacho");
                }
            } else {
                error_log("pagoProcesar - No se actualizó el estatus del pago, no se continúa el proceso");
            }
            return $resultado;
        });
    }

    public function cambiarEstatus($nuevoEstatus) {
        return $this->c_cambiarEstatus($nuevoEstatus);
    }
    private function c_cambiarEstatus($nuevoEstatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus){
            try {
                $sql = "UPDATE tbl_detalles_pago SET estatus = :estatus WHERE id_detalles = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':estatus', $nuevoEstatus);
                $stmt->bindParam(':id', $this->id_detalles);
                return $stmt->execute();
            } catch (PDOException $e) {
                return false;
            }
        });
    }
    
    public function obtenerPagoPorId($id) {
        return $this->o_pagoPorId($id);
    }
    private function o_pagoPorId($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $sql = "SELECT 
                    dp.id_detalles,
                    dp.id_factura,
                    dp.id_cuenta,
                    c.nombre_banco AS tbl_cuentas,
                    dp.observaciones,
                    dp.tipo,
                    dp.comprobante,
                    dp.referencia,
                    dp.fecha,
                    dp.estatus
                FROM tbl_detalles_pago dp
                INNER JOIN tbl_cuentas c ON dp.id_cuenta = c.id_cuenta
                WHERE dp.id_detalles = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    private function pagoEliminar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "DELETE FROM tbl_detalles_pago WHERE id_detalles = :id_detalles";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_detalles', $this->id_detalles, PDO::PARAM_INT);
            return $stmt->execute();
        });
    }
}
?>