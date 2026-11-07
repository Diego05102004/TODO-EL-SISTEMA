<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Finanza extends BD {
    private $id_finanzas;
    private $tipo; 
    private $monto;
    private $descripcion;
    private $fecha;
    private $estado;
    private $id_despacho;
    private $id_recepcion;
    
    // Constantes para validaciones
    const MAX_DESCRIPCION = 500;
    const MAX_TIPO = 20;
    const MAX_MONTO = 999999999.99;
    const MIN_MONTO = 0.01;
    const TIPOS_PERMITIDOS = ['ingreso', 'egreso'];
    const ESTADOS_PERMITIDOS = ['1', '0', 'activo', 'inactivo'];

    public function getIdFinanzas() { 
        return $this->id_finanzas; 
    }
    public function setIdFinanzas($id_finanzas) {
        $this->id_finanzas = $id_finanzas; 
    }

    public function getTipo() { 
        return $this->tipo; 
    }
    public function setTipo($tipo) { 
        $this->tipo = $tipo; 
    }

    public function getMonto() { 
        return $this->monto; 
    }
    public function setMonto($monto) { 
        $this->monto = $monto; 
    }

    public function getDescripcion() { 
        return $this->descripcion; 
    }
    public function setDescripcion($descripcion) { 
        $this->descripcion = $descripcion; 
    }

    public function getFecha() { 
        return $this->fecha; 
    }
    public function setFecha($fecha) { 
        $this->fecha = $fecha; 
    }

    public function getEstado() { 
        return $this->estado; 
    }
    public function setEstado($estado) { 
        $this->estado = $estado; 
    }

    public function getIdDespacho() { 
        return $this->id_despacho; 
    }
    public function setIdDespacho($id) { 
        $this->id_despacho = $id; 
    }

    public function getIdRecepcion() { 
        return $this->id_recepcion; 
    }
    public function setIdRecepcion($id) { 
        $this->id_recepcion = $id; 
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

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para consultar finanzas
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID de la transacción (opcional)
        if (isset($datos['id_finanza'])) {
            if (!is_numeric($datos['id_finanza']) || $datos['id_finanza'] <= 0) {
                $errores['id_finanza'] = 'El ID de la transacción debe ser un número positivo';
            }
        }
        
        // Validar tipo (opcional)
        if (isset($datos['tipo'])) {
            $tipo = trim($datos['tipo']);
            if (!empty($tipo) && !in_array($tipo, self::TIPOS_PERMITIDOS)) {
                $errores['tipo'] = 'El tipo debe ser uno de: ' . implode(', ', self::TIPOS_PERMITIDOS);
            } elseif (!empty($tipo) && mb_strlen($tipo) > self::MAX_TIPO) {
                $errores['tipo'] = 'El tipo no debe exceder los ' . self::MAX_TIPO . ' caracteres';
            }
        }
        
        // Validar monto (opcional)
        if (isset($datos['monto'])) {
            $monto = (float)$datos['monto'];
            if ($monto < self::MIN_MONTO) {
                $errores['monto'] = 'El monto debe ser mayor o igual a ' . self::MIN_MONTO;
            } elseif ($monto > self::MAX_MONTO) {
                $errores['monto'] = 'El monto no debe exceder ' . self::MAX_MONTO;
            }
        }
        
        // Validar estado (opcional)
        if (isset($datos['estado'])) {
            $estado = trim($datos['estado']);
            if (!empty($estado) && !in_array($estado, self::ESTADOS_PERMITIDOS)) {
                $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            }
        }
        
        // Validar límite de resultados (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        // Validar fecha de inicio (opcional)
        if (isset($datos['fecha_inicio'])) {
            $fechaInicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaInicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        // Validar fecha de fin (opcional)
        if (isset($datos['fecha_fin'])) {
            $fechaFin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaFin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        // Validar que la fecha de fin no sea anterior a la de inicio
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fechaInicio = new \DateTime($datos['fecha_inicio']);
            $fechaFin = new \DateTime($datos['fecha_fin']);
            if ($fechaFin < $fechaInicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        // Validar ID de despacho (opcional)
        if (isset($datos['id_despacho'])) {
            if (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
                $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
            }
        }
        
        // Validar ID de recepción (opcional)
        if (isset($datos['id_recepcion'])) {
            if (!is_numeric($datos['id_recepcion']) || $datos['id_recepcion'] <= 0) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para generar reporte
     */
    private function validarReporte($datos) {
        $errores = [];
        
        // Validar tipo (opcional)
        if (isset($datos['tipo'])) {
            $tipo = trim($datos['tipo']);
            if (!empty($tipo) && !in_array($tipo, self::TIPOS_PERMITIDOS)) {
                $errores['tipo'] = 'El tipo debe ser uno de: ' . implode(', ', self::TIPOS_PERMITIDOS);
            }
        }
        
        // Validar límite de resultados (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        // Validar año (opcional)
        if (isset($datos['anio'])) {
            $anio = (int)$datos['anio'];
            if ($anio < 2000 || $anio > 2100) {
                $errores['anio'] = 'El año debe estar entre 2000 y 2100';
            }
        }
        
        // Validar mes (opcional)
        if (isset($datos['mes'])) {
            $mes = (int)$datos['mes'];
            if ($mes < 1 || $mes > 12) {
                $errores['mes'] = 'El mes debe estar entre 1 y 12';
            }
        }
        
        // Validar fecha de inicio (opcional)
        if (isset($datos['fecha_inicio'])) {
            $fechaInicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaInicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        // Validar fecha de fin (opcional)
        if (isset($datos['fecha_fin'])) {
            $fechaFin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaFin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        // Validar que la fecha de fin no sea anterior a la de inicio
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fechaInicio = new \DateTime($datos['fecha_inicio']);
            $fechaFin = new \DateTime($datos['fecha_fin']);
            if ($fechaFin < $fechaInicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        // Validar formato de descarga (opcional)
        if (isset($datos['formato_descarga'])) {
            $formato = trim($datos['formato_descarga']);
            $formatosPermitidos = ['pdf', 'excel', 'csv'];
            if (!empty($formato) && !in_array($formato, $formatosPermitidos)) {
                $errores['formato_descarga'] = 'El formato de descarga debe ser uno de: ' . implode(', ', $formatosPermitidos);
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarFinanzas($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para reporte (método público)
     */
    public function validarReporteFinanzas($datos) {
        return $this->validarReporte($datos);
    }
    
    /**
     * Verifica si una transacción financiera existe por ID
     */
    private function verificarTransaccionExistente($idTransaccion) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT COUNT(*) FROM tbl_ingresos_egresos WHERE id_finanzas = :id_finanza";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_finanza', $idTransaccion, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarTransaccionExistente: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    /**
     * Verifica si un despacho existe por ID
     */
    private function verificarDespachoExistente($idDespacho) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT COUNT(*) FROM tbl_despachos WHERE id_despachos = :id_despacho";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_despacho', $idDespacho, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarDespachoExistente: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    /**
     * Verifica si una recepción existe por ID
     */
    private function verificarRecepcionExistente($idRecepcion) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT COUNT(*) FROM tbl_recepciones WHERE id_recepcion = :id_recepcion";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_recepcion', $idRecepcion, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarRecepcionExistente: ' . $e->getMessage());
                return false;
            }
        });
    }

    public function consultarIngresos() {
        return $this->c_ingresos(); 
    }
    private function c_ingresos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_ingresos_egresos WHERE tipo='ingreso' and estado='1' ORDER BY fecha DESC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function consultarEgresos() {
        return $this->c_egresos(); 
    }
    private function c_egresos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_ingresos_egresos WHERE tipo='egreso' and estado='1' ORDER BY fecha DESC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}