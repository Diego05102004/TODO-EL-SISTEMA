<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;

class Despacho extends BD{
    private $id;
    private $idcliente;
    private $tipocompra;
    private $desc;
    private $fecha;
    private $estado;
    private $correlativo;
    private $tablerecepcion = 'tbl_despachos';
    private $encryption;
    
    // Campos cifrados de clientes (para descifrar en reportes)
    const CAMPOS_CIFRADOS_CLIENTES = ['nombre', 'direccion', 'telefono', 'correo'];
    
    // Constantes para validaciones
    const MAX_DESCRIPCION = 500;
    const MAX_ESTADO = 50;
    const MAX_TIPO_COMPRA = 50;
    const ESTADOS_PERMITIDOS = ['Por Despachar', 'Despachado', 'Anulado'];
    const TIPOS_COMPRA_PERMITIDOS = ['Presencial', 'Online', 'Mixta'];
    const ESTADOS_CAMBIO = ['Por Despachar', 'Despachado'];

    public function getid() {
        return $this->id;
    }
    public function setid($id) {
        $this->id = $id;
    }

    public function getidcliente() {
        return $this->idcliente;
    }
    public function setidcliente($idcliente) {
        $this->idcliente = $idcliente;
    } 

    public function gettipocompra() {
        return $this->tipocompra;
    }
    public function settipocompra($tipocompra) {
        $this->tipocompra = $tipocompra;
    }

    public function getfecha() {
        return $this->fecha;
    }
    public function setfecha($fecha) {
        $this->fecha = $fecha;
    }

    public function getestado() {
        return $this->estado;
    }
    public function setestado($estado) {
        $this->estado = $estado;
    }

    public function getdesc() {
        return $this->desc;
    }
    public function setdesc($desc) {
        $this->desc = $desc;
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
    
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID del despacho (opcional)
        if (isset($datos['id_despacho'])) {
            if (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
                $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
            }
        }
        
        // Validar ID del cliente (opcional)
        if (isset($datos['id_cliente'])) {
            if (!is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
                $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
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
        
        // Validar tipo de compra (opcional)
        if (isset($datos['tipocompra'])) {
            $tipoCompra = trim($datos['tipocompra']);
            if (!empty($tipoCompra) && !in_array($tipoCompra, self::TIPOS_COMPRA_PERMITIDOS)) {
                $errores['tipocompra'] = 'El tipo de compra debe ser uno de: ' . implode(', ', self::TIPOS_COMPRA_PERMITIDOS);
            } elseif (!empty($tipoCompra) && mb_strlen($tipoCompra) > self::MAX_TIPO_COMPRA) {
                $errores['tipocompra'] = 'El tipo de compra no debe exceder los ' . self::MAX_TIPO_COMPRA . ' caracteres';
            }
        }
        
        // Validar estado (opcional)
        if (isset($datos['estado'])) {
            $estado = trim($datos['estado']);
            if (!empty($estado) && !in_array($estado, self::ESTADOS_PERMITIDOS)) {
                $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            } elseif (!empty($estado) && mb_strlen($estado) > self::MAX_ESTADO) {
                $errores['estado'] = 'El estado no debe exceder los ' . self::MAX_ESTADO . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para detallar un despacho
     */
    private function validarDetallar($datos) {
        $errores = [];
        
        // Validar ID del despacho
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
            $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para anular un despacho
     */
    private function validarAnular($datos) {
        $errores = [];
        
        // Validar ID del despacho
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
            $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para cambiar estado de un despacho
     */
    private function validarCambiarEstadoDespacho($datos) {
        $errores = [];
        
        // Validar ID del despacho
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
            $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
        }
        
        // Validar estado actual
        if (!isset($datos['estado_actual'])) {
            $errores['estado_actual'] = 'El estado actual del despacho es obligatorio';
        } elseif (!in_array($datos['estado_actual'], self::ESTADOS_CAMBIO)) {
            $errores['estado_actual'] = 'El estado actual debe ser uno de: ' . implode(', ', self::ESTADOS_CAMBIO);
        }
        
        // Validar nuevo estado (opcional, ya que se calcula automáticamente)
        if (isset($datos['nuevo_estado'])) {
            $nuevoEstado = trim($datos['nuevo_estado']);
            if (!in_array($nuevoEstado, self::ESTADOS_CAMBIO)) {
                $errores['nuevo_estado'] = 'El nuevo estado debe ser uno de: ' . implode(', ', self::ESTADOS_CAMBIO);
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para generar reporte
     */
    private function validarReporte($datos) {
        $errores = [];
        
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
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarDespachos($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para detallar (método público)
     */
    public function validarDetallarDespacho($datos) {
        return $this->validarDetallar($datos);
    }
    
    /**
     * Valida los datos para anular (método público)
     */
    public function validarAnularDespacho($datos) {
        return $this->validarAnular($datos);
    }
    
    /**
     * Valida los datos para cambiar estado (método público)
     */
    public function validarCambiarEstado($datos) {
        return $this->validarCambiarEstadoDespacho($datos);
    }
    
    /**
     * Valida los datos para reporte (método público)
     */
    public function validarReporteDespacho($datos) {
        return $this->validarReporte($datos);
    }

    public function obtenercliente() {
        return $this->obt_cliente();
    }
    private function obt_cliente() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            try{
                $p = $pdo->prepare("SELECT * FROM tbl_clientes");
                $p->execute();
                return $p->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                return ['error' => true, 'mensaje' => $e->getMessage()];
            }
        });
        
        // Descifrar datos personales de los clientes
        if (is_array($resultado) && !isset($resultado['error'])) {
            $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_CLIENTES);
        }
        
        return $resultado;
    }

    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $resultado = $pdo->query("
                    SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                    FROM tbl_productos AS p 
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
                ");
                
                $respuesta = '';
                $totalFilas = 0;
                foreach($resultado as $fila){
                    $respuesta .= "<tr style='cursor:pointer' onclick='colocaproducto(this);'>";
                    $respuesta .= "<td style='display:none'>{$fila['id_producto']}</td>";
                    $respuesta .= "<td>{$fila['id_producto']}</td>";
                    $respuesta .= "<td>{$fila['nombre_producto']}</td>";
                    $respuesta .= "<td>{$fila['nombre_modelo']}</td>";
                    $respuesta .= "<td>{$fila['nombre_marca']}</td>";
                    $respuesta .= "<td>{$fila['serial']}</td>";
                    $respuesta .= "</tr>";
                    $totalFilas++;
                }

                $modalSize = 'modal-md';
                if ($totalFilas > 8) $modalSize = 'modal-lg';
                if ($totalFilas > 20) $modalSize = 'modal-xl';

                $r = [
                    'resultado' => 'listado',
                    'mensaje' => $respuesta,
                    'modalSize' => $modalSize
                ];
            } catch (Exception $e) {
                $r = [
                    'resultado' => 'error',
                    'mensaje' => $e->getMessage(),
                    'modalSize' => 'modal-md'
                ];
            }
            return $r;
        });
    }

    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                    FROM tbl_productos AS p 
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getdespacho() {
        return $this->g_despacho(); 
    }

    private function g_despacho() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "CALL sp_consultar_despachos_activos()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $despachos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($despachos as &$despacho) {
                $sqlProd = "CALL sp_obtener_detalle_despacho(:id_despacho)";
                $stmtProd = $pdo->prepare($sqlProd);
                $stmtProd->bindParam(':id_despacho', $despacho['id_despachos'], PDO::PARAM_INT);
                $stmtProd->execute();
                
                $despacho['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
                $stmtProd->closeCursor();
            }

            return $despachos;
        }, false);
    }

    public function obtenerDespachoPorId($idDespacho) {
        return $this->obt_DespachoPorId($idDespacho); 
    }

    public function obt_DespachoPorId($idDespacho) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idDespacho) {
            $sql = "CALL sp_obtener_depacho_por_id(:id_despacho)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_despacho', $idDespacho, PDO::PARAM_INT);
            $stmt->execute();
            $despacho = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($despacho) {
                $sqlProd = "CALL sp_obtener_detalle_despacho(:id_despacho)";
                $stmtProd = $pdo->prepare($sqlProd);
                $stmtProd->bindParam(':id_despacho', $idDespacho, PDO::PARAM_INT);
                $stmtProd->execute();
                
                $despacho['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
                $stmtProd->closeCursor();
            }

            return $despacho ? $despacho : null;
        }, false);
    }

    public function obt_productos_despacho($id_despacho) {
        return $this->obt_productos_des($id_despacho); 
    }
    private function obt_productos_des($id_despacho) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_despacho){
            $sql = "
                SELECT 
                    p.id_producto AS codigo,
                    p.nombre_producto AS producto,
                    m.nombre_modelo AS modelo,
                    mar.nombre_marca AS marca,
                    p.serial,
                    d.cantidad
                FROM tbl_despacho_detalle AS d
                INNER JOIN tbl_productos AS p ON d.id_producto = p.id_producto
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
                WHERE d.id_despacho = :id_despacho
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_despacho', $id_despacho, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getDespachosEstado($fechaInicio = null, $fechaFin = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin){
            $sql = "
                SELECT 
                    estado AS label, 
                    COUNT(*) AS value
                FROM tbl_orden_despachos
                WHERE 1 = 1
            ";

            $params = [];

            if ($fechaInicio && $fechaFin) {
                $sql .= " AND fecha_despacho BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicio;
                $params[':fechaFin'] = $fechaFin;
            }

            $sql .= " GROUP BY estado ORDER BY value DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getProductosDespachadosPorMes($anio = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($anio){
            $sql = "
                SELECT 
                    MONTH(od.fecha_despacho) AS mes_num, 
                    SUM(dd.cantidad) AS value
                FROM tbl_despacho_detalle dd
                INNER JOIN tbl_orden_despachos od ON dd.id_detalle = od.id_orden_despachos
                WHERE 1 = 1
            ";

            $params = [];

            if ($anio) {
                $sql .= " AND YEAR(od.fecha_despacho) = :anio";
                $params[':anio'] = $anio;
            }

            $sql .= "
                GROUP BY MONTH(od.fecha_despacho)
                ORDER BY mes_num
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Traducción de meses
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            foreach ($resultados as &$fila) {
                $fila['label'] = $meses[$fila['mes_num']] ?? 'Desconocido';
                unset($fila['mes_num']);
            }

            return $resultados;
        });
    }

    public function cambiarEstadoDespacho($id, $nuevoEstado, $idUsuarioSesion) {
        return $this->cam_estadoDespacho($id, $nuevoEstado, $idUsuarioSesion); 
    }

    private function cam_estadoDespacho($id, $nuevoEstado, $idUsuarioSesion) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $nuevoEstado, $idUsuarioSesion) {
            try {
                // Preparación de la llamada al SP transaccional
                $sql = "CALL sp_cambiar_estado_despacho(:id_despachos, :nuevo_estado, :id_usuario)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->bindParam(':id_despachos', $id, PDO::PARAM_INT);
                $stmt->bindParam(':nuevo_estado', $nuevoEstado, PDO::PARAM_STR);
                $stmt->bindParam(':id_usuario', $idUsuarioSesion, PDO::PARAM_INT);
                
                $stmt->execute();
                $stmt->closeCursor(); // Liberación inmediata de búfer

                return [
                    'status' => 'success',
                    'message' => 'Estado del despacho actualizado correctamente.'
                ];

            } catch (PDOException $e) {
                // Captura controlada de los SIGNAL SQLSTATE lanzados por el SP
                return [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }, false);
    }

    public function anularDespacho($idDespacho, $id_usuario_auditor) {
        return $this->an_despacho($idDespacho, $id_usuario_auditor); 
    }

    private function an_despacho($idDespacho, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idDespacho, $id_usuario_auditor){
            try {
                // Invocamos el procedimiento almacenado integrado con la bitácora
                $sql = "CALL sp_anular_despacho(:id_despachos, :id_usuario_auditor)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->bindParam(':id_despachos', $idDespacho, PDO::PARAM_INT);
                $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, PDO::PARAM_INT);
                
                $stmt->execute();
                $stmt->closeCursor(); // Liberamos el búfer inmediatamente

                return [
                    'status' => 'success',
                    'message' => 'El despacho ha sido anulado correctamente.'
                ];

            } catch (PDOException $e) {
                // Captura los errores personalizados lanzados por SIGNAL SQLSTATE
                return [
                    'status' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
        }, false);
    }

    public function getDespachosPorCliente($fechaInicio = null, $fechaFin = null) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin){
            try {
                $sql = "
                    SELECT 
                        c.nombre AS cliente, 
                        COUNT(*) AS total
                    FROM tbl_despachos d
                    INNER JOIN tbl_clientes c ON d.id_clientes = c.id_clientes
                    WHERE d.activo = '1'
                ";

                $params = [];

                if ($fechaInicio && $fechaFin) {
                    $sql .= " AND d.fecha_despacho BETWEEN :fechaInicio AND :fechaFin";
                    $params[':fechaInicio'] = $fechaInicio;
                    $params[':fechaFin'] = $fechaFin;
                }

                $sql .= " GROUP BY c.id_clientes, c.nombre ORDER BY total DESC LIMIT 10";

                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                return ['error' => true, 'mensaje' => $e->getMessage()];
            }
        });
        
        // Descifrar datos personales de los clientes
        if (is_array($resultado) && !isset($resultado['error'])) {
            $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_CLIENTES);
        }
        
        return $resultado;
    }

    public function getDespachosPorTipoCompra($fechaInicio = null, $fechaFin = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin){
            try {
                $sql = "
                    SELECT 
                        tipocompra AS tipo_compra, 
                        COUNT(*) AS total
                    FROM tbl_despachos
                    WHERE activo = '1'
                ";

                $params = [];

                if ($fechaInicio && $fechaFin) {
                    $sql .= " AND fecha_despacho BETWEEN :fechaInicio AND :fechaFin";
                    $params[':fechaInicio'] = $fechaInicio;
                    $params[':fechaFin'] = $fechaFin;
                }

                $sql .= " GROUP BY tipocompra ORDER BY total DESC";

                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                return ['error' => true, 'mensaje' => $e->getMessage()];
            }
        });
    }
}
?>
