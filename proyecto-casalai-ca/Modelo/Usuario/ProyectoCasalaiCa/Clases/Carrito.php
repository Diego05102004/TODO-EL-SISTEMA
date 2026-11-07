<?php

namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Carrito extends BD{
    
    // Constantes para validaciones
    const MAX_CANTIDAD_PRODUCTO = 999;
    const MAX_DESCRIPCION = 500;
    const MAX_NOMBRE_PRODUCTO = 200;
    const MAX_PRECIO = 999999.99;
    const ESTADOS_PERMITIDOS = ['activo', 'inactivo', 'pendiente'];
    const ACCIONES_PERMITIDAS = ['agregar', 'actualizar', 'eliminar', 'vaciar', 'comprar', 'consultar'];
    const TIPOS_PRODUCTO_PERMITIDOS = ['simple', 'combo', 'servicio'];

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
    
    public function crearCarrito($id_cliente) {
        return $this->c_crearCarrito($id_cliente);
    }
    private function c_crearCarrito($id_cliente) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cliente) {
            $sql = "INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            return $stmt->execute();
        });
    }

    public function obtenerCarritoPorCliente($id_cliente) {
        return $this->o_carritoPorCliente($id_cliente);
    }
    private function o_carritoPorCliente($id_cliente) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cliente) {
            $sql = "SELECT id_carrito, id_cliente FROM tbl_carrito WHERE id_cliente = :id_cliente";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function agregarProductoAlCarrito($id_carrito, $id_producto, $cantidad = 1) {
        return $this->a_agregarProducto($id_carrito, $id_producto, $cantidad);
    }
    private function a_agregarProducto($id_carrito, $id_producto, $cantidad = 1) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito, $id_producto, $cantidad) {
            $stmtPrecio = $pdo->prepare("SELECT precio, stock FROM tbl_productos
                WHERE id_producto = :id_producto AND estado = 'habilitado'");
            $stmtPrecio->execute([':id_producto' => (int)$id_producto]);
            $producto = $stmtPrecio->fetch(PDO::FETCH_ASSOC);
            if (!$producto) {
                throw new PDOException('El producto no está disponible');
            }

            $stmtCantidad = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0)
                FROM tbl_carritodetalle
                WHERE id_carrito = :id_carrito AND id_producto = :id_producto");
            $stmtCantidad->execute([
                ':id_carrito' => (int)$id_carrito,
                ':id_producto' => (int)$id_producto
            ]);
            if ((int)$stmtCantidad->fetchColumn() + (int)$cantidad > (int)$producto['stock']) {
                throw new PDOException('No hay stock suficiente para agregar esa cantidad');
            }

            $sqlCheck = "SELECT id_carrito_detalle, cantidad FROM tbl_carritodetalle 
                         WHERE id_carrito = :id_carrito AND id_producto = :id_producto
                           AND id_combo IS NULL";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->bindParam(':id_carrito', $id_carrito);
            $stmtCheck->bindParam(':id_producto', $id_producto);
            $stmtCheck->execute();
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $nuevaCantidad = $existente['cantidad'] + $cantidad;
                $sqlUpdate = "UPDATE tbl_carritodetalle
                              SET cantidad = :cantidad, precio_unitario = :precio_unitario
                              WHERE id_carrito_detalle = :id_carrito_detalle";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                return $stmtUpdate->execute([
                    ':cantidad' => $nuevaCantidad,
                    ':precio_unitario' => (float)$producto['precio'],
                    ':id_carrito_detalle' => $existente['id_carrito_detalle']
                ]);
            } else {
                $sqlInsert = "INSERT INTO tbl_carritodetalle
                              (id_carrito, id_producto, cantidad, precio_unitario, id_combo, estatus)
                              VALUES (:id_carrito, :id_producto, :cantidad, :precio_unitario, NULL, 'pendiente')";
                $stmtInsert = $pdo->prepare($sqlInsert);
                return $stmtInsert->execute([
                    ':id_carrito' => $id_carrito,
                    ':id_producto' => $id_producto,
                    ':cantidad' => $cantidad,
                    ':precio_unitario' => (float)$producto['precio']
                ]);
            }
        });
    }

    public function obtenerProductosDelCarrito($id_carrito) {
        return $this->o_productosCarrito($id_carrito);
    }
    private function o_productosCarrito($id_carrito) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito) {
            $sql = "SELECT cd.id_carrito_detalle, cd.id_combo, c.nombre_combo,
                           p.id_producto, p.imagen, p.nombre_producto AS nombre, mo.nombre_modelo, ma.nombre_marca,
                           cd.cantidad, COALESCE(cdCombo.cantidad, 1) AS cantidad_combo,
                           COALESCE(cd.precio_unitario, p.precio) AS precio,
                           (cd.cantidad * COALESCE(cd.precio_unitario, p.precio)) AS subtotal
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_productos p ON cd.id_producto = p.id_producto
                    INNER JOIN tbl_modelos mo on mo.id_modelo = p.id_modelo
                    INNER JOIN tbl_marcas ma ON ma.id_marca = mo.id_marca
                    LEFT JOIN tbl_combo c ON c.id_combo = cd.id_combo
                    LEFT JOIN tbl_combo_detalle cdCombo
                        ON cdCombo.id_combo = cd.id_combo AND cdCombo.id_producto = cd.id_producto
                    WHERE cd.id_carrito = :id_carrito";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function actualizarCantidadProducto($id_carrito_detalle, $cantidad) {
        return $this->u_actualizarCantidad($id_carrito_detalle, $cantidad);
    }
    private function u_actualizarCantidad($id_carrito_detalle, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito_detalle, $cantidad) {
            if ((int)$cantidad <= 0 || (int)$cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                throw new PDOException('La cantidad debe estar entre 1 y ' . self::MAX_CANTIDAD_PRODUCTO);
            }

            $sqlDetalle = "SELECT cd.id_carrito, cd.id_producto, cd.id_combo, p.precio, p.stock,
                                  c.descuento, coalesce(cdCombo.cantidad, 1) AS cantidad_combo
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
                    LEFT JOIN tbl_combo c ON c.id_combo = cd.id_combo
                    LEFT JOIN tbl_combo_detalle cdCombo
                        ON cdCombo.id_combo = cd.id_combo AND cdCombo.id_producto = cd.id_producto
                    WHERE cd.id_carrito_detalle = :id_carrito_detalle
                    FOR UPDATE";
            $stmtDetalle = $pdo->prepare($sqlDetalle);
            $stmtDetalle->execute([':id_carrito_detalle' => (int)$id_carrito_detalle]);
            $detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);
            if (!$detalle) {
                throw new PDOException('Detalle de carrito no encontrado');
            }

            if ($detalle['id_combo'] !== null) {
                if ($detalle['descuento'] === null || !is_numeric($detalle['descuento']) ||
                    (float)$detalle['descuento'] < 0 || (float)$detalle['descuento'] > 100 ||
                    (int)$detalle['cantidad_combo'] <= 0 ||
                    (int)$cantidad % (int)$detalle['cantidad_combo'] !== 0) {
                    throw new PDOException('La cantidad debe corresponder a unidades completas del combo');
                }

                $cantidadCombos = intdiv((int)$cantidad, (int)$detalle['cantidad_combo']);
                $stmtComponentes = $pdo->prepare("SELECT cd.id_producto, cd.cantidad, p.precio, p.stock
                    FROM tbl_combo_detalle cd
                    INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
                    WHERE cd.id_combo = :id_combo
                    ORDER BY cd.id_producto
                    FOR UPDATE");
                $stmtComponentes->execute([':id_combo' => (int)$detalle['id_combo']]);
                $componentes = $stmtComponentes->fetchAll(PDO::FETCH_ASSOC);
                if (!$componentes) {
                    throw new PDOException('No se encontró la composición del combo');
                }

                $stmtFilasCombo = $pdo->prepare("SELECT id_carrito_detalle, id_producto
                    FROM tbl_carritodetalle
                    WHERE id_carrito = :id_carrito AND id_combo = :id_combo
                    ORDER BY id_producto
                    FOR UPDATE");
                $stmtFilasCombo->execute([
                    ':id_carrito' => (int)$detalle['id_carrito'],
                    ':id_combo' => (int)$detalle['id_combo']
                ]);
                $filasCombo = $stmtFilasCombo->fetchAll(PDO::FETCH_ASSOC);
                $filasPorProducto = [];
                foreach ($filasCombo as $fila) {
                    $idProducto = (int)$fila['id_producto'];
                    if (isset($filasPorProducto[$idProducto])) {
                        throw new PDOException('El combo tiene productos duplicados en el carrito');
                    }
                    $filasPorProducto[$idProducto] = (int)$fila['id_carrito_detalle'];
                }
                if (count($filasPorProducto) !== count($componentes)) {
                    throw new PDOException('El combo está incompleto en el carrito; elimínelo y agréguelo nuevamente');
                }

                $stmtOtherQuantities = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0)
                    FROM tbl_carritodetalle
                    WHERE id_carrito = :id_carrito
                      AND id_producto = :id_producto
                      AND (id_combo IS NULL OR id_combo <> :id_combo)");
                $stmtUpdate = $pdo->prepare("UPDATE tbl_carritodetalle
                    SET cantidad = :cantidad, precio_unitario = :precio_unitario
                    WHERE id_carrito_detalle = :id_carrito_detalle");
                foreach ($componentes as $componente) {
                    $idProducto = (int)$componente['id_producto'];
                    if (!isset($filasPorProducto[$idProducto]) || (int)$componente['cantidad'] <= 0) {
                        throw new PDOException('El combo está incompleto en el carrito; elimínelo y agréguelo nuevamente');
                    }

                    $cantidadComponente = $cantidadCombos * (int)$componente['cantidad'];
                    if ($cantidadComponente > self::MAX_CANTIDAD_PRODUCTO) {
                        throw new PDOException('La cantidad solicitada supera el límite permitido para un producto');
                    }
                    $stmtOtherQuantities->execute([
                        ':id_carrito' => (int)$detalle['id_carrito'],
                        ':id_producto' => $idProducto,
                        ':id_combo' => (int)$detalle['id_combo']
                    ]);
                    if ((int)$stmtOtherQuantities->fetchColumn() + $cantidadComponente > (int)$componente['stock']) {
                        throw new PDOException('No hay stock suficiente para completar la cantidad solicitada del combo');
                    }

                    $precioUnitario = Productos::calcularPrecioConDescuento(
                        (float)$componente['precio'],
                        (float)$detalle['descuento']
                    );
                    $stmtUpdate->execute([
                        ':cantidad' => $cantidadComponente,
                        ':precio_unitario' => $precioUnitario,
                        ':id_carrito_detalle' => $filasPorProducto[$idProducto]
                    ]);
                }
                return true;
            }

            $stmtOtherQuantities = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0)
                FROM tbl_carritodetalle
                WHERE id_carrito = :id_carrito
                  AND id_producto = :id_producto
                  AND id_carrito_detalle <> :id_carrito_detalle");
            $stmtOtherQuantities->execute([
                ':id_carrito' => (int)$detalle['id_carrito'],
                ':id_producto' => (int)$detalle['id_producto'],
                ':id_carrito_detalle' => (int)$id_carrito_detalle
            ]);
            if ((int)$stmtOtherQuantities->fetchColumn() + (int)$cantidad > (int)$detalle['stock']) {
                throw new PDOException('No hay stock suficiente para la cantidad solicitada');
            }

            $sql = "UPDATE tbl_carritodetalle
                    SET cantidad = :cantidad, precio_unitario = :precio_unitario
                    WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':cantidad' => (int)$cantidad,
                ':precio_unitario' => (float)$detalle['precio'],
                ':id_carrito_detalle' => (int)$id_carrito_detalle
            ]);
        });
    }

    public function eliminarProductoDelCarrito($id_carrito_detalle) {
        return $this->d_eliminarProducto($id_carrito_detalle);
    }
    private function d_eliminarProducto($id_carrito_detalle) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito_detalle) {
            $stmtDetalle = $pdo->prepare("SELECT id_carrito, id_combo
                FROM tbl_carritodetalle
                WHERE id_carrito_detalle = :id_carrito_detalle");
            $stmtDetalle->execute([':id_carrito_detalle' => (int)$id_carrito_detalle]);
            $detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);
            if (!$detalle) {
                throw new PDOException('Detalle de carrito no encontrado');
            }

            if ($detalle['id_combo'] !== null) {
                $sql = "DELETE FROM tbl_carritodetalle
                    WHERE id_carrito = :id_carrito AND id_combo = :id_combo";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':id_carrito' => (int)$detalle['id_carrito'],
                    ':id_combo' => (int)$detalle['id_combo']
                ]);
            }

            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id_carrito_detalle' => (int)$id_carrito_detalle]);
        });
    }

    public function eliminarTodoElCarrito($id_carrito) {
        return $this->d_eliminarTodo($id_carrito);
    }
    private function d_eliminarTodo($id_carrito) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito) {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito = :id_carrito";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            return $stmt->execute();
        });
    }

    public function obtenerDetallePorId($id_carrito_detalle) {
        return $this->o_detallePorId($id_carrito_detalle);
    }
    private function o_detallePorId($id_carrito_detalle) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito_detalle) {
            $sql = "SELECT cd.id_carrito_detalle, cd.id_carrito, cd.id_producto, cd.cantidad, c.id_cliente
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_carrito c ON cd.id_carrito = c.id_carrito
                    WHERE cd.id_carrito_detalle = :id_carrito_detalle";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function agregarComboAlCarrito($id_carrito, $id_combo) {
        return $this->a_agregarCombo($id_carrito, $id_combo);
    }
    private function a_agregarCombo($id_carrito, $id_combo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito, $id_combo) {
            $sqlDetalles = "SELECT cd.id_producto, cd.cantidad, p.precio, c.descuento
                            FROM tbl_combo_detalle cd
                            INNER JOIN tbl_combo c ON c.id_combo = cd.id_combo
                            INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
                            WHERE cd.id_combo = :id_combo";
            $stmtDetalles = $pdo->prepare($sqlDetalles);
            $stmtDetalles->execute([':id_combo' => (int)$id_combo]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
            if (!$detalles) {
                throw new PDOException('El combo no contiene productos disponibles');
            }

            foreach ($detalles as $detalle) {
                $precioUnitario = Productos::calcularPrecioConDescuento(
                    (float)$detalle['precio'],
                    (float)$detalle['descuento']
                );
                $sqlInsert = "INSERT INTO tbl_carritodetalle
                    (id_carrito, id_producto, cantidad, precio_unitario, id_combo, estatus)
                    VALUES (:id_carrito, :id_producto, :cantidad, :precio_unitario, :id_combo, 'pendiente')";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':id_carrito' => (int)$id_carrito,
                    ':id_producto' => (int)$detalle['id_producto'],
                    ':cantidad' => (int)$detalle['cantidad'],
                    ':precio_unitario' => $precioUnitario,
                    ':id_combo' => (int)$id_combo
                ]);
            }
            return true;
        });
    }

    public function obtenerCantidadProductosCarrito($id_usuario) {
        return $this->o_cantidadProductos($id_usuario);
    }
    private function o_cantidadProductos($id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario){
            $sql = "SELECT COUNT(dc.id_detalle_carrito) as total
                    FROM tbl_carrito c 
                    INNER JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito 
                    WHERE c.id_usuario = ? AND c.estado = 'activo'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_usuario]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? $resultado['total'] : 0;
        });
    }

    public function obtenerResumenCarrito($id_usuario) {
        return $this->o_resumenCarrito($id_usuario);
    }
    private function o_resumenCarrito($id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario) {
            $sql = "SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos, 
                           SUM(dc.cantidad * dc.precio_unitario) as total_precio
                    FROM tbl_carrito c 
                    LEFT JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito 
                    WHERE c.id_usuario = ? AND c.estado = 'activo'
                    GROUP BY c.id_carrito";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function registrarCompra($id_carrito, $id_cliente, $productos) {
        return $this->r_registrarCompra($id_carrito, $id_cliente, $productos);
    }
    private function r_registrarCompra($id_carrito, $id_cliente, $productos) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_carrito, $id_cliente, $productos) {
            foreach ($productos as $detalle) {
                if (empty($detalle['id_producto']) || empty($detalle['cantidad']) || !is_numeric($detalle['cantidad'])) {
                    throw new PDOException("Uno o más productos tienen datos incompletos o inválidos.");
                }
            }
            $sqlCompra = "INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) 
                          VALUES (NOW(), :id_cliente, 0, 'Borrador')";
            $stmtCompra = $pdo->prepare($sqlCompra);
            $stmtCompra->bindValue(':id_cliente', $id_cliente);
            $stmtCompra->execute();
            $id_factura = $pdo->lastInsertId();

            $stmtPrecio = $pdo->prepare("SELECT p.precio, c.descuento
                FROM tbl_productos p
                LEFT JOIN tbl_combo c ON c.id_combo = :id_combo
                WHERE p.id_producto = :id_producto");
            $stmtDetalle = $pdo->prepare("INSERT INTO tbl_factura_detalle
                (factura_id, id_producto, cantidad, precio_unitario, id_combo)
                VALUES (:id_factura, :id_producto, :cantidad, :precio_unitario, :id_combo)");

            foreach ($productos as $detalle) {
                $idCombo = !empty($detalle['id_combo']) ? (int)$detalle['id_combo'] : null;
                $stmtPrecio->execute([
                    ':id_combo' => $idCombo,
                    ':id_producto' => (int)$detalle['id_producto']
                ]);
                $producto = $stmtPrecio->fetch(PDO::FETCH_ASSOC);
                if (!$producto) {
                    throw new PDOException('Uno de los productos no existe.');
                }

                $precioUnitario = (float)$producto['precio'];
                if ($idCombo !== null) {
                    $stmtComboDetail = $pdo->prepare("SELECT cantidad FROM tbl_combo_detalle
                        WHERE id_combo = :id_combo AND id_producto = :id_producto");
                    $stmtComboDetail->execute([
                        ':id_combo' => $idCombo,
                        ':id_producto' => (int)$detalle['id_producto']
                    ]);
                    $cantidadCombo = (int)$stmtComboDetail->fetchColumn();
                    if ($cantidadCombo <= 0 || (int)$detalle['cantidad'] % $cantidadCombo !== 0 ||
                        $producto['descuento'] === null) {
                        throw new PDOException('La cantidad no corresponde a una composición válida del combo.');
                    }
                    $precioUnitario = Productos::calcularPrecioConDescuento(
                        (float)$producto['precio'],
                        (float)$producto['descuento']
                    );
                }

                $stmtDetalle->execute([
                    ':id_factura' => $id_factura,
                    ':id_producto' => (int)$detalle['id_producto'],
                    ':cantidad' => (int)$detalle['cantidad'],
                    ':precio_unitario' => $precioUnitario,
                    ':id_combo' => $idCombo
                ]);
            }

            $sqlVaciar = "DELETE FROM tbl_carritodetalle WHERE id_carrito = :id_carrito";
            $stmtVaciar = $pdo->prepare($sqlVaciar);
            $stmtVaciar->bindValue(':id_carrito', $id_carrito);
            $stmtVaciar->execute();

            return true;
        });
    }

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para agregar producto al carrito
     */
    private function validarAgregarProducto($datos) {
        $errores = [];
        
        // Validar ID del carrito
        if (!isset($datos['id_carrito']) || !is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
            $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
        }
        
        // Validar ID del producto
        if (!isset($datos['id_producto']) || !is_numeric($datos['id_producto']) || $datos['id_producto'] <= 0) {
            $errores['id_producto'] = 'El ID del producto debe ser un número positivo';
        }
        
        // Validar cantidad
        if (!isset($datos['cantidad'])) {
            $errores['cantidad'] = 'La cantidad es obligatoria';
        } else {
            $cantidad = (int)$datos['cantidad'];
            if ($cantidad <= 0) {
                $errores['cantidad'] = 'La cantidad debe ser un número positivo';
            } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                $errores['cantidad'] = 'La cantidad no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para actualizar cantidad de producto
     */
    private function validarActualizarCantidad($datos) {
        $errores = [];
        
        // Validar ID del detalle del carrito
        if (!isset($datos['id_carrito_detalle']) || !is_numeric($datos['id_carrito_detalle']) || $datos['id_carrito_detalle'] <= 0) {
            $errores['id_carrito_detalle'] = 'El ID del detalle del carrito debe ser un número positivo';
        }
        
        // Validar cantidad
        if (!isset($datos['cantidad'])) {
            $errores['cantidad'] = 'La cantidad es obligatoria';
        } else {
            $cantidad = (int)$datos['cantidad'];
            if ($cantidad <= 0) {
                $errores['cantidad'] = 'La cantidad debe ser un número positivo';
            } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                $errores['cantidad'] = 'La cantidad no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para eliminar producto del carrito
     */
    private function validarEliminarProducto($datos) {
        $errores = [];
        
        // Validar ID del detalle del carrito
        if (!isset($datos['id_carrito_detalle']) || !is_numeric($datos['id_carrito_detalle']) || $datos['id_carrito_detalle'] <= 0) {
            $errores['id_carrito_detalle'] = 'El ID del detalle del carrito debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para vaciar el carrito
     */
    private function validarVaciarCarrito($datos) {
        $errores = [];
        
        // Validar ID del carrito
        if (!isset($datos['id_carrito']) || !is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
            $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para registrar compra
     */
    private function validarRegistrarCompra($datos) {
        $errores = [];
        
        // Validar ID del carrito
        if (!isset($datos['id_carrito']) || !is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
            $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
        }
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente']) || !is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        // Validar productos
        if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
            $errores['productos'] = 'Debe especificar al menos un producto para la compra';
        } else {
            foreach ($datos['productos'] as $index => $producto) {
                if (!is_array($producto)) {
                    $errores["productos_$index"] = 'El producto en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar ID del producto
                if (!isset($producto['id_producto']) || !is_numeric($producto['id_producto']) || $producto['id_producto'] <= 0) {
                    $errores["productos_{$index}_id_producto"] = 'El ID del producto en la posición ' . $index . ' debe ser un número positivo';
                }
                
                // Validar cantidad
                if (!isset($producto['cantidad']) || !is_numeric($producto['cantidad']) || $producto['cantidad'] <= 0) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' debe ser un número positivo';
                } elseif ($producto['cantidad'] > self::MAX_CANTIDAD_PRODUCTO) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar carrito
     */
    private function validarConsultarCarrito($datos) {
        $errores = [];
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente']) || !is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        // Validar ID del carrito (opcional)
        if (isset($datos['id_carrito'])) {
            if (!is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
                $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para filtrar por marca
     */
    private function validarFiltrarMarca($datos) {
        $errores = [];
        
        // Validar ID de marca (opcional)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] <= 0) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para agregar producto al carrito (método público)
     */
    public function validarAgregar($datos) {
        return $this->validarAgregarProducto($datos);
    }
    
    /**
     * Valida los datos para actualizar cantidad de producto (método público)
     */
    public function validarActualizar($datos) {
        return $this->validarActualizarCantidad($datos);
    }
    
    /**
     * Valida los datos para eliminar producto del carrito (método público)
     */
    public function validarEliminar($datos) {
        return $this->validarEliminarProducto($datos);
    }
    
    /**
     * Valida los datos para vaciar el carrito (método público)
     */
    public function validarVaciar($datos) {
        return $this->validarVaciarCarrito($datos);
    }
    
    /**
     * Valida los datos para registrar compra (método público)
     */
    public function validarCompra($datos) {
        return $this->validarRegistrarCompra($datos);
    }
    
    /**
     * Valida los datos para consultar carrito (método público)
     */
    public function validarConsultar($datos) {
        return $this->validarConsultarCarrito($datos);
    }
    
    /**
     * Valida los datos para filtrar por marca (método público)
     */
    public function validarFiltrar($datos) {
        return $this->validarFiltrarMarca($datos);
    }
}