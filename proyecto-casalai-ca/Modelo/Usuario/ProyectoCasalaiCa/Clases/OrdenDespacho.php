<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class OrdenDespacho extends BD {
    
    private $id;
    private $factura;
    private $cliente;
    private $fecha;
    private $estado;
    private $activo = 1;
    private $tableordendespacho = 'tbl_orden_despachos';
    
    // Constantes para validaciones
    const MAX_CORRELATIVO = 10;
    const MIN_CORRELATIVO = 4;
    const MAX_ID_ORDEN = 999999999;
    const MIN_ID_ORDEN = 1;
    const MAX_ID_FACTURA = 999999999;
    const MIN_ID_FACTURA = 1;
    const ESTADOS_VALIDOS = ['Por Entregar', 'Entregada', 'Anulada'];
    const ESTADOS_VALIDOS_CAMBIO = ['habilitado', 'inhabilitado'];

    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
    }

    public function getFactura() {
        return $this->factura;
    }
    public function setFactura($factura) {
        $this->factura = $factura;
    }

    public function getCliente() {
        return $this->cliente;
    }
    public function setCliente($cliente) {
        $this->cliente = $cliente;
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

    public function obtenerFacturasDisponibles() {
        return $this->obt_facturasDisponibles(); 
    }
    private function obt_facturasDisponibles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT f.id_factura, f.fecha, c.nombre
                    FROM tbl_facturas f
                    INNER JOIN tbl_clientes c ON f.cliente = c.id_clientes
                    WHERE f.estatus = 'Pagada'
                    AND f.id_factura NOT IN (
                        SELECT DISTINCT id_factura 
                        FROM tbl_orden_despachos 
                        WHERE id_factura IS NOT NULL
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
    
    public function getordendespacho() {
        return $this->g_ordenesDespacho();
    }

    private function g_ordenesDespacho() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            
            $query = "CALL sp_consultar_ordenes_despacho()";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $ordendespacho = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($ordendespacho as &$despacho) {
                $sqlProd = "CALL sp_obtener_productos_factura_despacho(?)";
                $stmtProd = $pdo->prepare($sqlProd);
                $stmtProd->execute([$despacho['id_factura']]);
                $despacho['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
                $stmtProd->closeCursor();
            }

            return $ordendespacho;
        }, false);
    }

    public function obtenerOrdenPorId($id) {
        return $this->obt_ordenPorId($id); 
    }

    private function obt_ordenPorId($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $query = "CALL sp_obtener_orden_despacho_por_id(?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            $ordendespacho = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $ordendespacho ? $ordendespacho : false;
        }, false);
    }

    public function cambiarEstatus($nuevoEstatus) {
        return $this->cambiar_Estatus($nuevoEstatus); 
    }
    private function cambiar_Estatus($nuevoEstatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus){
            try {
                $sql = "UPDATE tbl_usuarios SET estatus = :estatus WHERE id_usuario = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':estatus', $nuevoEstatus);
                $stmt->bindParam(':id', $this->id);
                return $stmt->execute();
            } catch (PDOException $e) {
                return false;
            }
        });
    }

    public function obtenerDatosParaPDF($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            try {
                $query = "SELECT od.*, c.nombre as cliente, c.cedula 
                        FROM tbl_orden_despachos od
                        JOIN tbl_facturas f ON od.id_factura = f.id_factura
                        JOIN tbl_clientes c ON f.cliente = c.id_clientes
                        WHERE od.id_orden_despachos = ?";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$id]);
                $orden = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$orden) {
                    return null;
                }

                $queryProductos = "SELECT p.serial as codigo, p.nombre_producto as producto, 
                                df.cantidad, p.precio as precio,
                                (df.cantidad * p.precio) as subtotal
                                FROM tbl_factura_detalle df
                                JOIN tbl_productos p ON df.id_producto = p.id_producto
                                WHERE df.factura_id = ?";
                
                $stmtProductos = $pdo->prepare($queryProductos);
                $stmtProductos->execute([$orden['id_factura']]);
                $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

                $total = array_sum(array_column($productos, 'subtotal'));

                return [
                    'id_orden_despachos' => $orden['id_orden_despachos'],
                    'id_factura' => $orden['id_factura'],
                    'cliente' => $orden['cliente'],
                    'cedula' => $orden['cedula'],
                    'fecha_despacho' => $orden['fecha_despacho'],
                    'estado' => $orden['estado'],
                    'productos' => $productos,
                    'total' => $total
                ];

            } catch (PDOException $e) {
                error_log("Error al obtener datos para PDF: " . $e->getMessage());
                return null;
            } 
        });
    }


    public function getDetallesCompra($idDespacho) {
        return $this->g_detallesCompra($idDespacho); 
    }
    private function g_detallesCompra($idDespacho) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idDespacho){
            $sqlProductos = "
                SELECT p.nombre_producto, d.cantidad, p.precio, (d.cantidad * p.precio) AS subtotal
                FROM tbl_despacho_detalle d
                INNER JOIN tbl_productos p ON p.id_producto = d.id_producto
                WHERE d.id_despacho = ?
            ";
            $stmtProd = $pdo->prepare($sqlProductos);
            $stmtProd->execute([$idDespacho]);
            $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

            return [
                'productos' => $productos
            ];
        });
    }

    public function cambiarEstadoOrden($id, $nuevoEstado, $id_usuario_auditor) {
        return $this->cam_estadoOrden($id, $nuevoEstado, $id_usuario_auditor); 
    }

    private function cam_estadoOrden($id, $nuevoEstado, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $nuevoEstado, $id_usuario_auditor) {
            try {
                $query = "CALL sp_cambiar_estado_orden_despacho(:id_orden_despachos, :estatus, :id_usuario_auditor)";
                $stmt = $pdo->prepare($query);
                
                $stmt->bindParam(':estatus', $nuevoEstado);
                $stmt->bindParam(':id_orden_despachos', $id, PDO::PARAM_INT);
                $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, PDO::PARAM_INT);

                $stmt->execute();
                $stmt->closeCursor();

                return ['status' => 'success', 'message' => 'Estado actualizado correctamente'];

            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }, false);
    }

    public function anularOrdenDespacho($idOrden, $id_usuario_auditor) {
        return $this->an_orden_despacho($idOrden, $id_usuario_auditor); 
    }

    private function an_orden_despacho($idOrden, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idOrden, $id_usuario_auditor){
            try {
                $sql = "CALL sp_anular_orden_despacho(:id_orden_despachos, :id_usuario_auditor)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':id_orden_despachos', $idOrden, PDO::PARAM_INT);
                $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, PDO::PARAM_INT);

                $stmt->execute();
                $stmt->closeCursor();
                
                return ['status' => 'success'];

            } catch (PDOException $e) {
                return [
                    'status' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
        }, false);
    }

    public function crearPorFactura($idFactura) {
        return $this->c_PorFactura($idFactura); 
    }
    private function c_PorFactura($idFactura) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idFactura){
            try {
                error_log("crearPorFactura - Iniciando proceso para factura ID: $idFactura");
                
                $sqlDup = "SELECT id_orden_despachos FROM tbl_orden_despachos WHERE id_factura = :id AND activo = 1 LIMIT 1";
                $stmtD = $pdo->prepare($sqlDup);
                $stmtD->bindParam(':id', $idFactura, PDO::PARAM_INT);
                $stmtD->execute();
                $existente = $stmtD->fetch(PDO::FETCH_ASSOC);
                
                if ($existente) {
                    error_log("crearPorFactura - Ya existe una orden activa para factura $idFactura: {$existente['id_orden_despachos']}");
                    return ['status' => 'exists'];
                }

                error_log("crearPorFactura - Consultando datos de cliente para factura $idFactura");
                $sqlCliente = "SELECT f.cliente AS id_cliente, c.nombre AS nombre_cliente
                            FROM tbl_facturas f
                            INNER JOIN tbl_clientes c ON c.id_clientes = f.cliente
                            WHERE f.id_factura = :id";
                $stmtC = $pdo->prepare($sqlCliente);
                $stmtC->bindParam(':id', $idFactura, PDO::PARAM_INT);
                $stmtC->execute();
                $row = $stmtC->fetch(PDO::FETCH_ASSOC);
                
                if (!$row) {
                    error_log("crearPorFactura - No se encontró la factura $idFactura o su cliente");
                    return ['status' => 'error', 'message' => 'Factura no encontrada'];
                }
                
                $clienteId = (int)$row['id_cliente'];
                $clienteNombre = $row['nombre_cliente'];
                error_log("crearPorFactura - Cliente encontrado: ID=$clienteId, Nombre='$clienteNombre'");
                
                if ($clienteNombre === null || $clienteNombre === '') {
                    $clienteNombre = 'Cliente Desconocido';
                    error_log("crearPorFactura - Cliente sin nombre, usando valor por defecto");
                }

                error_log("crearPorFactura - Insertando nueva orden de despacho");
                $sqlIns = "INSERT INTO tbl_orden_despachos (id_factura, cliente, fecha_despacho, estado, activo)
                        VALUES (:id_factura, :cliente, NOW(), 'Por Entregar', 1)";
                $stmt = $pdo->prepare($sqlIns);
                $stmt->bindParam(':id_factura', $idFactura, PDO::PARAM_INT);
                $stmt->bindParam(':cliente', $clienteNombre, PDO::PARAM_STR);
                $ok = $stmt->execute();
                
                if ($ok) {
                    $idOrden = $pdo->lastInsertId();
                    error_log("crearPorFactura - Orden creada exitosamente: ID=$idOrden");
                    return ['status' => 'success', 'id' => $idOrden];
                } else {
                    error_log("crearPorFactura - Error al insertar orden: " . json_encode($stmt->errorInfo()));
                    return ['status' => 'error', 'message' => 'No se pudo crear la orden de despacho'];
                }
            } catch (Exception $e) {
                error_log("crearPorFactura - Excepción: " . $e->getMessage());
                error_log("crearPorFactura - Stack trace: " . $e->getTraceAsString());
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        });
    }

    // ==================== VALIDACIONES DE BACKEND ====================

    /**
     * Valida los datos para operaciones de orden de despacho
     */
    private function validarOrdenDespacho($datos, $requiereFactura = true) {
        $errores = [];
        
        // Validar correlativo (solo para creación)
        if ($requiereFactura) {
            if (!isset($datos['correlativo'])) {
                $errores['correlativo'] = 'El correlativo es obligatorio';
            } else {
                $correlativo = trim((string)$datos['correlativo']);
                if (empty($correlativo)) {
                    $errores['correlativo'] = 'El correlativo es obligatorio';
                } elseif (!preg_match('/^[0-9]{' . self::MIN_CORRELATIVO . ',' . self::MAX_CORRELATIVO . '}$/', $correlativo)) {
                    $errores['correlativo'] = 'El correlativo debe tener entre ' . self::MIN_CORRELATIVO . ' y ' . self::MAX_CORRELATIVO . ' dígitos numéricos';
                }
            }
        }
        
        // Validar ID de la factura (solo para creación)
        if ($requiereFactura) {
            $idFactura = null;
            if (isset($datos['factura'])) {
                $idFactura = (int)$datos['factura'];
            } elseif (isset($datos['id_factura'])) {
                $idFactura = (int)$datos['id_factura'];
            }
            
            if ($idFactura === null || $idFactura < self::MIN_ID_FACTURA || $idFactura > self::MAX_ID_FACTURA) {
                $errores['factura'] = 'Debe seleccionar una factura válida';
            }
        }
        
        // Validar ID de la orden (solo para operaciones que lo requieran)
        if (isset($datos['id_orden_despachos'])) {
            if (!is_numeric($datos['id_orden_despachos']) || $datos['id_orden_despachos'] < self::MIN_ID_ORDEN || $datos['id_orden_despachos'] > self::MAX_ID_ORDEN) {
                $errores['id_orden_despachos'] = 'El ID de la orden debe ser un número entre ' . self::MIN_ID_ORDEN . ' y ' . self::MAX_ID_ORDEN;
            }
        }
        
        // Validar fecha (opcional)
        if (isset($datos['fecha']) && !empty($datos['fecha'])) {
            $fecha = trim((string)$datos['fecha']);
            $dt = \DateTime::createFromFormat('Y-m-d', $fecha);
            $errors = \DateTime::getLastErrors();
            if (!$dt || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
                $errores['fecha'] = 'La fecha no tiene un formato válido (AAAA-MM-DD)';
            } else {
                $hoy = new \DateTime('today');
                if ($dt > $hoy) {
                    $errores['fecha'] = 'No se permiten fechas futuras';
                }
            }
        }
        
        // Validar estado (opcional)
        if (isset($datos['estado']) && !empty($datos['estado'])) {
            if (!in_array($datos['estado'], self::ESTADOS_VALIDOS)) {
                $errores['estado'] = 'El estado no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================

    /**
     * Valida los datos para consultar orden (método público)
     */
    public function validarConsultarOrden($datos) {
        $errores = [];
        
        // Validar ID de la orden (opcional para consulta)
        if (isset($datos['id_orden_despachos'])) {
            if (!is_numeric($datos['id_orden_despachos']) || $datos['id_orden_despachos'] < self::MIN_ID_ORDEN || $datos['id_orden_despachos'] > self::MAX_ID_ORDEN) {
                $errores['id_orden_despachos'] = 'El ID de la orden debe ser un número entre ' . self::MIN_ID_ORDEN . ' y ' . self::MAX_ID_ORDEN;
            }
        }
        
        // Validar ID de la factura (opcional para consulta)
        if (isset($datos['id_factura'])) {
            if (!is_numeric($datos['id_factura']) || $datos['id_factura'] < self::MIN_ID_FACTURA || $datos['id_factura'] > self::MAX_ID_FACTURA) {
                $errores['id_factura'] = 'El ID de la factura debe ser un número entre ' . self::MIN_ID_FACTURA . ' y ' . self::MAX_ID_FACTURA;
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para detallar orden (método público)
     */
    public function validarDetallarOrden($datos) {
        return $this->validarConsultarOrden($datos);
    }
    
    /**
     * Valida los datos para cambiar estatus (método público)
     */
    private function validarEstatus($datos) {
        $errores = [];
        
        // Validar ID de la orden
        if (!isset($datos['id_orden_despachos'])) {
            $errores['id_orden_despachos'] = 'El ID de la orden es obligatorio';
        } elseif (!is_numeric($datos['id_orden_despachos']) || $datos['id_orden_despachos'] < self::MIN_ID_ORDEN || $datos['id_orden_despachos'] > self::MAX_ID_ORDEN) {
            $errores['id_orden_despachos'] = 'El ID de la orden debe ser un número entre ' . self::MIN_ID_ORDEN . ' y ' . self::MAX_ID_ORDEN;
        }
        
        // Validar el nuevo estado de la orden
        if (!isset($datos['nuevo_estado'])) {
            $errores['nuevo_estado'] = 'El nuevo estado es obligatorio';
        } elseif (!in_array($datos['nuevo_estado'], self::ESTADOS_VALIDOS)) {
            $errores['nuevo_estado'] = 'El estado no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para descargar orden (método público)
     */
    private function validarDescargar($datos) {
        $errores = [];
        
        // Validar ID de la orden
        if (!isset($datos['id_orden_despachos'])) {
            $errores['id_orden_despachos'] = 'El ID de la orden es obligatorio';
        } elseif (!is_numeric($datos['id_orden_despachos']) || $datos['id_orden_despachos'] < self::MIN_ID_ORDEN || $datos['id_orden_despachos'] > self::MAX_ID_ORDEN) {
            $errores['id_orden_despachos'] = 'El ID de la orden debe ser un número entre ' . self::MIN_ID_ORDEN . ' y ' . self::MAX_ID_ORDEN;
        }
        
        return $errores;
    }

    private function validarAnular($datos) {
        $errores = [];
        
        if (!isset($datos['id_orden_despachos'])) {
            $errores['id_orden_despachos'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_orden_despachos']) || $datos['id_orden_despachos'] <= 0) {
            $errores['id_orden_despachos'] = 'El ID del despacho debe ser un número positivo';
        }
        
        return $errores;
    }

    public function validarCambiarEstatus($datos) {
        return $this->validarEstatus($datos);
    }

    public function validarDescargarOrden($datos) {
        return $this->validarDescargar($datos);
    }
    
    /**
     * Valida los datos para anular orden (método público)
     */
    public function validarAnularOrden($datos) {
        return $this->validarAnular($datos);
    }
    
    /**
     * Valida los datos para crear orden (método público)
     */
    public function validarCrearOrden($datos) {
        return $this->validarOrdenDespacho($datos, true);
    }
    
    /**
     * Verifica si una orden existe por ID
     */
    private function verificarOrdenExistente($idOrden) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try{
                $sql = "SELECT COUNT(*) FROM tbl_orden_despachos WHERE id_orden_despachos = :id_orden_despachos AND activo = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_orden_despachos', $idOrden, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarOrdenExistente: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    /**
     * Verifica si una factura existe por ID
     */
    private function verificarFacturaExistente($idFactura) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT COUNT(*) FROM tbl_facturas WHERE id_factura = :id_factura";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_factura', $idFactura, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarFacturaExistente: ' . $e->getMessage());
                return false;
            }
        });
    }
}