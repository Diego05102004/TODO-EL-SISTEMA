<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;

class Factura extends BD
{
    private $id;
    private $fecha;
    private $cliente;
    private $descuento;
    private $estatus;
    private $id_producto;
    private $cantidad;

    private $cedula;
    private $encryption;
    
    // Constantes para validaciones
    const MAX_DESCUENTO = 100;
    const MIN_DESCUENTO = 0;
    const MAX_CANTIDAD = 999999;
    const MIN_CANTIDAD = 1;
    const MAX_CLIENTE = 50;
    const MIN_CLIENTE = 3;
    const ESTADOS_PERMITIDOS = ['Borrador', 'Pagada Presencialmente', 'Pagada', 'Anulada'];
    const ESTADOS_PAGO = ['En Proceso', 'Pago Incompleto', 'Pago Procesado', 'Pago No Encontrado'];

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getFecha() { return $this->fecha; }
    public function setFecha($fecha) { $this->fecha = $fecha; }

    public function getCliente() { return $this->cliente; }
    public function setCliente($cliente) { $this->cliente = $cliente; }

    public function getDescuento() { return $this->descuento; }
    public function setDescuento($descuento) { $this->descuento = $descuento; }

    public function getEstatus() { return $this->estatus; }
    public function setEstatus($estatus) { $this->estatus = $estatus; }

    public function getIdProducto() { return $this->id_producto; }
    public function setIdProducto($id_producto) { $this->id_producto = $id_producto; }

    public function getCantidad() { return $this->cantidad; }
    public function setCantidad($cantidad) { $this->cantidad = $cantidad; }
    public function getCedula() { return $this->cedula; }
    public function setCedula($cedula) { $this->cedula = $cedula; }

    public function __construct($tipo = 'P') {
    }

    private function descifrarDatosCliente(array $registros): array {
        if ($registros === []) {
            return $registros;
        }

        if (!($this->encryption instanceof Encryption)) {
            $this->encryption = new Encryption();
        }

        foreach ($registros as &$registro) {
            foreach (['cliente', 'nombre', 'direccion', 'telefono', 'correo'] as $campo) {
                if (isset($registro[$campo]) && is_string($registro[$campo])) {
                    $registro[$campo] = $this->encryption->decrypt($registro[$campo]);
                }
            }
        }
        unset($registro);

        return $registros;
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

    public function registrar() {
        return $this->r_registrar();
    }
    private function r_registrar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $this->fecha,
                $this->cliente,
                $this->descuento,
                $this->estatus
            ]);
            return $pdo->lastInsertId();
        });
    }

    public function agregarProducto($idFactura, $idProducto, $cantidad, $idCombo = null) {
        return $this->a_agregarProducto($idFactura, $idProducto, $cantidad, $idCombo);
    }
    private function a_agregarProducto($idFactura, $idProducto, $cantidad, $idCombo = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idFactura, $idProducto, $cantidad, $idCombo){
            $stmtPrecio = $pdo->prepare("SELECT p.precio, c.descuento, cd.cantidad AS cantidad_combo
                FROM tbl_productos p
                LEFT JOIN tbl_combo c ON c.id_combo = :id_combo
                LEFT JOIN tbl_combo_detalle cd
                    ON cd.id_combo = c.id_combo AND cd.id_producto = p.id_producto
                WHERE p.id_producto = :id_producto");
            $stmtPrecio->execute([
                ':id_combo' => $idCombo === null ? null : (int)$idCombo,
                ':id_producto' => (int)$idProducto
            ]);
            $producto = $stmtPrecio->fetch(PDO::FETCH_ASSOC);
            if (!$producto) {
                throw new PDOException('El producto no existe.');
            }

            $precioUnitario = (float)$producto['precio'];
            if ($idCombo !== null) {
                if ($producto['descuento'] === null || (int)$producto['cantidad_combo'] <= 0 ||
                    (int)$cantidad % (int)$producto['cantidad_combo'] !== 0) {
                    throw new PDOException('La cantidad no corresponde a una composición válida del combo.');
                }
                $precioUnitario = Productos::calcularPrecioConDescuento(
                    (float)$producto['precio'],
                    (float)$producto['descuento']
                );
            }

            $sql = "INSERT INTO tbl_factura_detalle
                (factura_id, id_producto, cantidad, precio_unitario, id_combo)
                VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idFactura, $idProducto, $cantidad, $precioUnitario, $idCombo]);
            return true;
        });
    }
    
    public function facturaTransaccion($transaccion) {
        switch ($transaccion) {
            case 'Ingresar':
                return $this->facturaIngresar();                 
            case 'Consultar':
                return $this->facturaConsultar();
            case 'ConsultarTodas':
                return $this->facturaConsultarTodas();
            case 'Cancelar':
                return $this->facturaCancelar($this->id);
            case 'Procesar':
                return $this->facturaProcesar($this->id, $this->estatus);
            case 'DescargarFactura':
                return $this->facturaDescargar($this->id);
            default:
                throw new PDOException("Transacción no válida.");
        }
    }

    private function facturaIngresar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            // Validar datos básicos antes de iniciar la transacción
            $erroresValidacion = $this->validarDatosRegistro();
            if (!empty($erroresValidacion)) {
                return ['error' => implode(' ', $erroresValidacion)];
            }

            // Buscar ID del cliente por su cédula
            $stmtCliente = $pdo->prepare("SELECT id_clientes FROM tbl_clientes WHERE cedula = ?");
            $stmtCliente->execute([$this->cliente]); // aquí $this->cliente sería la cédula
            $clienteData = $stmtCliente->fetch(PDO::FETCH_ASSOC);

            if (!$clienteData) {
                throw new PDOException("No se encontró un cliente con la cédula indicada.");
            }

            $id_cliente = $clienteData['id_clientes'];

            // Insertar en tabla factura
            $stmt = $pdo->prepare("INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) VALUES (?, ?, ?, ?)");
            $stmt->execute([$this->fecha, $id_cliente, $this->descuento, $this->estatus]);

            $factura_id = $pdo->lastInsertId();
            if (!$factura_id) {
                throw new PDOException("No se pudo insertar la factura.");
            }

            // Validar que $this->id_producto y $this->cantidad sean arrays y tengan la misma longitud
            if (!is_array($this->id_producto) || !is_array($this->cantidad) || count($this->id_producto) !== count($this->cantidad)) {
                throw new PDOException("Datos de productos y cantidades inválidos o no coinciden.");
            }

            // Preparar la consulta para obtener el precio del producto
            $stmtPrecio = $pdo->prepare("SELECT precio FROM tbl_productos WHERE id_producto = ?");

            // Modificar la inserción para incluir precio_unitario
            $stmt2 = $pdo->prepare("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");

            $detalle_insertados = 0;

            foreach ($this->id_producto as $index => $id_producto) {
                $cantidad = $this->cantidad[$index];

                if (empty($id_producto) || empty($cantidad)) {
                    throw new PDOException("Producto o cantidad vacío en el índice $index.");
                }

                // 1. Buscar el precio actual del producto en la base de datos
                $stmtPrecio->execute([$id_producto]);
                $productoData = $stmtPrecio->fetch(PDO::FETCH_ASSOC);

                if (!$productoData) {
                    throw new PDOException("El producto con ID $id_producto no existe.");
                }

                $precio_actual = $productoData['precio'];

                // 2. Insertar el detalle incluyendo el precio copiado
                $stmt2->execute([$factura_id, $id_producto, $cantidad, $precio_actual]);
                $detalle_insertados += $stmt2->rowCount();
            }

            if ($detalle_insertados !== count($this->id_producto)) {
                throw new PDOException("No se insertaron todos los detalles de la factura.");
            }

            return true;
        });
    }

    public function registrarDesdeCarrito($cedula, $idUsuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula, $idUsuario) {
            $stmtCliente = $pdo->prepare("SELECT id_clientes FROM tbl_clientes WHERE cedula = ?");
            $stmtCliente->execute([$cedula]);
            $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
            if (!$cliente) {
                throw new PDOException('No se encontró un cliente con la cédula indicada.');
            }

            $stmtCart = $pdo->prepare("SELECT c.id_carrito
                FROM tbl_carrito c
                WHERE c.id_cliente = ?
                ORDER BY c.fecha_creacion DESC
                LIMIT 1");
            $stmtCart->execute([(int)$idUsuario]);
            $cart = $stmtCart->fetch(PDO::FETCH_ASSOC);
            if (!$cart) {
                throw new PDOException('No se encontró un carrito para el cliente.');
            }
            $idCarrito = (int)$cart['id_carrito'];

            $stmtRows = $pdo->prepare("SELECT cd.id_carrito_detalle, cd.id_producto, cd.cantidad,
                    cd.precio_unitario AS precio_carrito,
                    cd.id_combo, p.precio AS precio_base, p.stock, c.descuento,
                    cdCombo.cantidad AS cantidad_combo
                FROM tbl_carritodetalle cd
                INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
                LEFT JOIN tbl_combo c ON c.id_combo = cd.id_combo
                LEFT JOIN tbl_combo_detalle cdCombo
                    ON cdCombo.id_combo = cd.id_combo AND cdCombo.id_producto = cd.id_producto
                WHERE cd.id_carrito = ?
                ORDER BY cd.id_carrito_detalle");
            $stmtRows->execute([$idCarrito]);
            $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                throw new PDOException('El carrito está vacío.');
            }

            $quantitiesByProduct = [];
            $combosEnCarrito = [];
            foreach ($rows as $row) {
                $idProducto = (int)$row['id_producto'];
                $cantidad = (int)$row['cantidad'];
                if ($cantidad <= 0) {
                    throw new PDOException('El carrito contiene una cantidad inválida.');
                }
                $quantitiesByProduct[$idProducto] = ($quantitiesByProduct[$idProducto] ?? 0) + $cantidad;

                if ($row['id_combo'] !== null) {
                    $descuento = $row['descuento'];
                    $cantidadCombo = (int)$row['cantidad_combo'];
                    if ($descuento === null || !is_numeric($descuento) ||
                        (float)$descuento < 0 || (float)$descuento > 100 ||
                        $cantidadCombo <= 0 || $cantidad % $cantidadCombo !== 0) {
                        throw new PDOException('Un producto del carrito ya no coincide con la composición de su combo.');
                    }

                    $idCombo = (int)$row['id_combo'];
                    if (isset($combosEnCarrito[$idCombo][$idProducto])) {
                        throw new PDOException('El carrito contiene productos duplicados dentro de un combo.');
                    }
                    $combosEnCarrito[$idCombo][$idProducto] = [
                        'cantidad' => $cantidad
                    ];
                }
            }

            $stmtComposicionCombo = $pdo->prepare("SELECT id_producto, cantidad
                FROM tbl_combo_detalle
                WHERE id_combo = ?");
            foreach ($combosEnCarrito as $idCombo => $productosCombo) {
                $stmtComposicionCombo->execute([(int)$idCombo]);
                $composicion = $stmtComposicionCombo->fetchAll(PDO::FETCH_ASSOC);
                if (!$composicion || count($composicion) !== count($productosCombo)) {
                    throw new PDOException('El carrito contiene un combo incompleto o con una composición modificada.');
                }

                $cantidadCombos = null;
                foreach ($composicion as $componente) {
                    $idProducto = (int)$componente['id_producto'];
                    $cantidadComponente = (int)$componente['cantidad'];
                    if ($cantidadComponente <= 0 || !isset($productosCombo[$idProducto])) {
                        throw new PDOException('El carrito contiene un combo incompleto o con una composición modificada.');
                    }

                    $detalleCombo = $productosCombo[$idProducto];
                    if ($detalleCombo['cantidad'] % $cantidadComponente !== 0) {
                        throw new PDOException('Las cantidades de los productos del combo no son consistentes.');
                    }

                    $cantidadComponenteCombos = intdiv($detalleCombo['cantidad'], $cantidadComponente);
                    if ($cantidadCombos !== null && $cantidadCombos !== $cantidadComponenteCombos) {
                        throw new PDOException('Las cantidades de los productos del combo no coinciden.');
                    }
                    $cantidadCombos = $cantidadComponenteCombos;
                }
            }

            foreach ($rows as $row) {
                $idProducto = (int)$row['id_producto'];
                if ($quantitiesByProduct[$idProducto] > (int)$row['stock']) {
                    throw new PDOException('No hay stock suficiente para completar la compra.');
                }
            }

            $stmtFactura = $pdo->prepare("INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus)
                VALUES (NOW(), ?, 0, 'Borrador')");
            $stmtFactura->execute([(int)$cliente['id_clientes']]);
            $idFactura = (int)$pdo->lastInsertId();
            if ($idFactura <= 0) {
                throw new PDOException('No se pudo crear la factura.');
            }

            $stmtUpdateComboPrice = $pdo->prepare("UPDATE tbl_combo_detalle
                SET precio_unitario = :precio_unitario
                WHERE id_combo = :id_combo AND id_producto = :id_producto");
            $stmtUpdateCartPrice = $pdo->prepare("UPDATE tbl_carritodetalle
                SET precio_unitario = :precio_unitario
                WHERE id_carrito_detalle = :id_carrito_detalle");
            $stmtFacturaDetalle = $pdo->prepare("INSERT INTO tbl_factura_detalle
                (factura_id, id_producto, cantidad, precio_unitario, id_combo)
                VALUES (:factura_id, :id_producto, :cantidad, :precio_unitario, :id_combo)");

            foreach ($rows as $row) {
                $precioUnitario = $row['precio_carrito'] === null
                    ? (float)$row['precio_base']
                    : (float)$row['precio_carrito'];
                if ($row['id_combo'] !== null) {
                    $precioUnitario = Productos::calcularPrecioConDescuento(
                        (float)$row['precio_base'],
                        (float)$row['descuento']
                    );
                    $stmtUpdateComboPrice->execute([
                        ':precio_unitario' => $precioUnitario,
                        ':id_combo' => (int)$row['id_combo'],
                        ':id_producto' => (int)$row['id_producto']
                    ]);
                }

                $stmtUpdateCartPrice->execute([
                    ':precio_unitario' => $precioUnitario,
                    ':id_carrito_detalle' => (int)$row['id_carrito_detalle']
                ]);
                $stmtFacturaDetalle->execute([
                    ':factura_id' => $idFactura,
                    ':id_producto' => (int)$row['id_producto'],
                    ':cantidad' => (int)$row['cantidad'],
                    ':precio_unitario' => $precioUnitario,
                    ':id_combo' => $row['id_combo'] === null ? null : (int)$row['id_combo']
                ]);
            }

            $stmtEmptyCart = $pdo->prepare("DELETE FROM tbl_carritodetalle WHERE id_carrito = ?");
            $stmtEmptyCart->execute([$idCarrito]);

            return true;
        });
    }

    public function facturaIngresarMovil($data = []) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($data) {
            // Extraer datos del parámetro $data
            $cedula = $data['cedula'] ?? null;
            $fecha = $data['fecha'] ?? date('Y-m-d');
            $descuento = $data['descuento'] ?? 0;
            $estatus = $data['estatus'] ?? 'Borrador';
            $id_productos = $data['id_producto'] ?? [];
            $cantidades = $data['cantidad'] ?? [];

            // Validaciones básicas
            if (empty($cedula)) {
                throw new PDOException("La cédula del cliente es requerida.");
            }

            if (empty($id_productos) || !is_array($id_productos)) {
                throw new PDOException("Debe proporcionar al menos un producto.");
            }

            if (empty($cantidades) || !is_array($cantidades)) {
                throw new PDOException("Debe proporcionar las cantidades de los productos.");
            }

            if (count($id_productos) !== count($cantidades)) {
                throw new PDOException("La cantidad de productos no coincide con las cantidades.");
            }

            // Buscar ID del cliente por su cédula
            $stmtCliente = $pdo->prepare("SELECT id_clientes FROM tbl_clientes WHERE cedula = ?");
            $stmtCliente->execute([$cedula]);
            $clienteData = $stmtCliente->fetch(PDO::FETCH_ASSOC);

            if (!$clienteData) {
                throw new PDOException("No se encontró un cliente con la cédula indicada.");
            }

            $id_cliente = $clienteData['id_clientes'];

            // Insertar en tabla factura
            $stmt = $pdo->prepare("INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fecha, $id_cliente, $descuento, $estatus]);

            $factura_id = $pdo->lastInsertId();
            if (!$factura_id) {
                throw new PDOException("No se pudo insertar la factura.");
            }

            // Preparar la consulta para obtener el precio del producto
            $stmtPrecio = $pdo->prepare("SELECT precio FROM tbl_productos WHERE id_producto = ?");

            // Modificar la inserción para incluir precio_unitario
            $stmt2 = $pdo->prepare("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");

            $detalle_insertados = 0;

            foreach ($id_productos as $index => $id_producto) {
                $cantidad = $cantidades[$index];

                if (empty($id_producto) || empty($cantidad)) {
                    throw new PDOException("Producto o cantidad vacío en el índice $index.");
                }

                // 1. Buscar el precio actual del producto en la base de datos
                $stmtPrecio->execute([$id_producto]);
                $productoData = $stmtPrecio->fetch(PDO::FETCH_ASSOC);

                if (!$productoData) {
                    throw new PDOException("El producto con ID $id_producto no existe.");
                }

                $precio_actual = $productoData['precio'];

                // 2. Insertar el detalle incluyendo el precio copiado
                $stmt2->execute([$factura_id, $id_producto, $cantidad, $precio_actual]);
                $detalle_insertados += $stmt2->rowCount();
            }

            if ($detalle_insertados !== count($id_productos)) {
                throw new PDOException("No se insertaron todos los detalles de la factura.");
            }

            return [
                'factura_id' => $factura_id,
                'numero_factura' => 'FAC-' . $factura_id,
                'cliente_id' => $id_cliente,
                'cedula' => $cedula,
                'fecha' => $fecha,
                'descuento' => $descuento,
                'estatus' => $estatus,
                'items_insertados' => $detalle_insertados
            ];
        });
    }

    public function obtenerUltimaFactura() {
        return $this->o_ultimaFactura();
    }
    private function o_ultimaFactura() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT MAX(id_factura) AS ultima_factura FROM tbl_facturas";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['ultima_factura'] : null;
        });
    }

    public function obtenerFacturasListado() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $stmt = $pdo->query("SELECT
                    f.id_factura,
                    f.fecha,
                    COALESCE(c.nombre, 'Cliente no registrado') AS cliente,
                    f.estatus,
                    ROUND(COALESCE(SUM(df.cantidad * df.precio_unitario), 0), 2) AS total
                FROM tbl_facturas f
                LEFT JOIN tbl_clientes c ON c.id_clientes = f.cliente
                LEFT JOIN tbl_factura_detalle df ON df.factura_id = f.id_factura
                GROUP BY f.id_factura, f.fecha, c.nombre, f.estatus
                ORDER BY f.id_factura DESC");

            $facturas = $this->descifrarDatosCliente($stmt->fetchAll(PDO::FETCH_ASSOC));
            $detalleFacturas = [];

            $stmtDetalle = $pdo->query("SELECT df.factura_id, p.nombre_producto, df.cantidad,
                    CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                        THEN p.precio ELSE df.precio_unitario END AS precio_unitario,
                    df.id_combo, cb.nombre_combo
                FROM tbl_factura_detalle df
                INNER JOIN tbl_productos p ON p.id_producto = df.id_producto
                LEFT JOIN tbl_combo cb ON cb.id_combo = df.id_combo
                ORDER BY df.factura_id, p.nombre_producto");

            foreach ($stmtDetalle->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
                $idFactura = (int)($detalle['factura_id'] ?? 0);
                if ($idFactura > 0) {
                    $detalleFacturas[$idFactura][] = $detalle;
                }
            }

            return [
                'facturas' => $facturas,
                'detalleFacturas' => $detalleFacturas
            ];
        });
    }

    private function facturaConsultarTodas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            // Primero obtenemos información de pagos para validar después
            $sqlPagos = "SELECT id_factura, estatus FROM tbl_detalles_pago";
            $stmtPagos = $pdo->prepare($sqlPagos);
            $stmtPagos->execute();
            $todosPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear un mapa de estatus de pago por factura
            $estatusPorFactura = [];
            foreach ($todosPagos as $pago) {
                $idFactura = $pago['id_factura'];
                if (!isset($estatusPorFactura[$idFactura])) {
                    $estatusPorFactura[$idFactura] = [];
                }
                $estatusPorFactura[$idFactura][] = $pago['estatus'];
            }

            $sqlDetalles = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
                p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
                CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                    THEN p.precio ELSE df.precio_unitario END AS precio,
                df.id_combo, cb.nombre_combo, df.cantidad, f.descuento, f.estatus
            FROM tbl_factura_detalle df
            JOIN tbl_facturas f ON f.id_factura = df.factura_id
            JOIN tbl_clientes c ON c.id_clientes = f.cliente
            JOIN tbl_productos p ON df.id_producto = p.id_producto
            LEFT JOIN tbl_combo cb ON cb.id_combo = df.id_combo
            JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
            JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
            ORDER BY f.id_factura DESC";

            $stmt = $pdo->prepare($sqlDetalles);
            $stmt->execute();
            $detalles = $this->descifrarDatosCliente($stmt->fetchAll(PDO::FETCH_ASSOC));

            if (!$detalles) {
                return ['resultado' => 'error', 'mensaje' => 'No hay facturas registradas.'];
            }
            
            $tasa = (new DolarService())->obtenerPrecioDolar();

            // Obtener los estatus y observaciones desde tbl_detalles_pago
            $stmtPagos = $pdo->prepare("SELECT id_factura, estatus, observaciones FROM tbl_detalles_pago");
            $stmtPagos->execute();
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
            $mapaPagos = [];
            foreach ($pagos as $pago) {
                $mapaPagos[$pago['id_factura']] = [
                    'estatus' => $pago['estatus'],
                    'observaciones' => $pago['observaciones']
                ];
            }

            $facturasAgrupadas = [];
            foreach ($detalles as $item) {
                $facturasAgrupadas[$item['id_factura']][] = $item;
            }

            $html = '<div class="accordion w-100" id="accordionFacturas" style="width: 100%; height: 100%;">';

            foreach ($facturasAgrupadas as $id_factura => $items) {
                $fila = $items[0]; // Datos comunes
                $estatus = $fila['estatus'];
                
                // Verificar si es una factura pagada presencialmente y si todos los pagos están procesados
                $esPagadaPresencialmente = ($estatus == 'Pagada Presencialmente');
                $esBorrador = ($estatus == 'Borrador');
                $esAnulada = (stripos((string)$estatus, 'anulada') !== false);
                $todosPagosProcesados = true;
                
                if ($esPagadaPresencialmente && isset($estatusPorFactura[$id_factura])) {
                    foreach ($estatusPorFactura[$id_factura] as $estatusPago) {
                        if ($estatusPago !== 'Pago Procesado') {
                            $todosPagosProcesados = false;
                            break;
                        }
                    }
                }
                
                // Si es pagada presencialmente pero no todos los pagos están procesados, saltar esta factura
                if ($esPagadaPresencialmente && !$todosPagosProcesados) {
                    continue;
                }

                $datosCliente = '<p><strong>Cliente:</strong> ' . htmlspecialchars((string)($fila['nombre'] ?? '')) .  ' | ';
                $datosCliente .= '<strong>Cédula:</strong> ' . htmlspecialchars((string)($fila['cedula'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Teléfono:</strong> ' . htmlspecialchars((string)($fila['telefono'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Dirección:</strong> ' . htmlspecialchars((string)($fila['direccion'] ?? '')) . ' | ';
                //$datosCliente .= '<strong>Descuento:</strong> ' . htmlspecialchars((string)($fila['descuento'] ?? '')) . '% | ';
                
                // Aplicar estilo especial para diferentes estatus
                if ($esPagadaPresencialmente) {
                    $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-success">' . htmlspecialchars($estatus) . '</span></p>';
                } else if ($esBorrador) {
                    $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-warning">' . htmlspecialchars($estatus) . '</span></p>';
                } else if ($esAnulada) {
                    $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-danger">' . htmlspecialchars($estatus) . '</span></p>';
                } else {
                    $datosCliente .= '<strong>Estatus:</strong> ' . htmlspecialchars($estatus) . '</p>';
                }

                $form = '<input type="hidden" name="id_factura" value="' . $id_factura . '">';
                $mensajePago = '';
                $botones = '';

                $estatusPago = $mapaPagos[$id_factura]['estatus'] ?? null;
                $observaciones = $mapaPagos[$id_factura]['observaciones'] ?? null;
                $estadoResumen = $estatus;
                if (stripos((string)$estatus, 'anul') === false && stripos((string)$estatus, 'cancel') === false && $estatusPago && $estatusPago !== 'Pago Procesado') {
                    $estadoResumen = $estatusPago;
                }

                if ($esBorrador) {
                    $mensajePago = '<div class="alert alert-warning"><strong>Borrador:</strong> El pago aún no ha sido enviado para validación.</div>';
                    $botones = '
                        <form action="?pagina=PasareladePago" method="POST" style="display:inline;">
                            <input type="hidden" name="id_factura" value="' . $id_factura . '">
                            <button type="submit" class="btn btn-success btn-lg">Pagar</button>
                        </form>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="id_factura" value="' . $id_factura . '">
                            <button type="button" class="btn btn-danger btn-lg cancelar" name="accion" value="cancelar">Cancelar</button>
                        </form>';
                } else if ($esAnulada) {
                    $mensajePago = '<div class="alert alert-danger"><strong>Anulada:</strong> La factura ha sido anulada.</div>';
                } else if ($estatusPago) {
                    switch ($estatusPago) {
                        case 'En Proceso':
                            $mensajePago = '<div class="alert alert-info"><strong>En proceso:</strong> El pago está siendo validado por un administrador. Por favor, espere la confirmación.</div>';
                            break;

                        case 'Pago Incompleto':
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago incompleto:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '</div>';
                            break;

                        case 'Pago Procesado':
                            // Mensaje especial para facturas pagadas presencialmente
                            if ($esPagadaPresencialmente) {
                                $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
                            } else {
                                $mensajePago = '<div class="alert alert-success"><strong>Pago procesado correctamente.</strong></div>';
                            }
                            $botones = '
                            <form action="" method="POST" target="_blank" style="display:inline;">
                                <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                                <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
                            </form>';
                            break;

                        case 'Pago No Encontrado':
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago no encontrado:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '<br>Por favor, verifique los datos registrados en la pasarela de pago.</div>';
                            break;

                        default:
                            $mensajePago = '<div class="alert alert-secondary">Estado de pago no reconocido.</div>';
                            break;
                    }
                } else if ($esPagadaPresencialmente) {
                    // Caso especial para facturas pagadas presencialmente sin registro en tbl_detalles_pago
                    $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
                    $botones = '
                    <form action="" method="POST" target="_blank" style="display:inline;">
                        <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                        <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
                    </form>';
                }

                $contenido = '<div class="w-100">' . $datosCliente . $mensajePago;
                $contenido .= '<div class="table-responsive w-100">';
                $contenido .= '<table class="table table-bordered w-100">';
                $contenido .= '<thead><tr><th>Producto</th><th>modelo</th><th>Marca</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>';

                $total = 0;
                foreach ($items as $item) {
                    $subtotal = $item['precio']*$tasa * $item['cantidad'];
                    $total += $subtotal;

                    $contenido .= '<tr>';
                    $producto = htmlspecialchars($item['producto']);
                    if (!empty($item['id_combo'])) {
                        $producto .= ' <small>(Combo: ' . htmlspecialchars((string)($item['nombre_combo'] ?? ('#' . $item['id_combo']))) . ')</small>';
                    }
                    $contenido .= '<td>' . $producto . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($item['nombre_modelo']) . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($item['nombre_marca']) . '</td>';
                    $contenido .= '<td>' . $item['cantidad'] . '</td>';
                    $contenido .= '<td>' . number_format($item['precio']*$tasa, 2) . ' BS</td>';
                    $contenido .= '</tr>';
                }

                $descuento = $total * $fila['descuento'] / 100;
                $subtotalConDescuento = $total - $descuento;
                $impuesto = $subtotalConDescuento * 0.16;
                $montoTotal = $subtotalConDescuento + $impuesto;
                $contenido .= '<tr><td colspan="4"><strong>Total con Impuestos:</strong></td><td>' . number_format($montoTotal, 2) . ' BS</td></tr>';            
                $contenido .= '</tbody></table></div>' . $form . $botones . '</div>';

                $html .= '<div class="accordion-item w-100" data-summary-status="' . htmlspecialchars((string)$estadoResumen, ENT_QUOTES, 'UTF-8') . '">';
                $html .= '<h2 class="accordion-header" id="heading' . $id_factura . '">';
                
                // Estilo especial para el encabezado de facturas según su estatus
                if ($esPagadaPresencialmente) {
                    $html .= '<button class="accordion-button collapsed w-100 bg-success text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
                } else if ($esBorrador) {
                    $html .= '<button class="accordion-button collapsed w-100  text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
                } else {
                    $html .= '<button class="accordion-button collapsed w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
                }
                
                $html .= 'Pedido #' . $id_factura . ' - Fecha: ' . $fila['fecha'] . '</button></h2>';
                $html .= '<div id="collapse' . $id_factura . '" class="accordion-collapse collapse" aria-labelledby="heading' . $id_factura . '" data-bs-parent="#accordionFacturas">';
                $html .= '<div class="accordion-body w-100">' . $contenido . '</div></div></div>';
            }

            $html .= '</div>';

            $resultadoListado = [
                'resultado' => 'listado',
                'mensaje' => $html
            ];
        return $resultadoListado;
        });
    }

    private function facturaConsultar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            if (empty($this->cedula)) {
                $pdo->cerrar();
                $pdo = null;
                return ['resultado' => 'error', 'mensaje' => 'No se ha proporcionado la cédula para consultar facturas.'];
            }
            
            // Primero obtenemos información de pagos para validar después
            $sqlPagos = "SELECT id_factura, estatus FROM tbl_detalles_pago";
            $stmtPagos = $pdo->prepare($sqlPagos);
            $stmtPagos->execute();
            $todosPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear un mapa de estatus de pago por factura
            $estatusPorFactura = [];
            foreach ($todosPagos as $pago) {
                $idFactura = $pago['id_factura'];
                if (!isset($estatusPorFactura[$idFactura])) {
                    $estatusPorFactura[$idFactura] = [];
                }
                $estatusPorFactura[$idFactura][] = $pago['estatus'];
            }
            
            $sqlDetalles = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
                p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
                CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                    THEN p.precio ELSE df.precio_unitario END AS precio,
                df.id_combo, cb.nombre_combo, df.cantidad, f.descuento, f.estatus
            FROM tbl_factura_detalle df
            JOIN tbl_facturas f ON f.id_factura = df.factura_id
            JOIN tbl_clientes c ON c.id_clientes = f.cliente
            JOIN tbl_productos p ON df.id_producto = p.id_producto
            LEFT JOIN tbl_combo cb ON cb.id_combo = df.id_combo
            JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
            JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
            WHERE c.cedula = :cedula
            ORDER BY f.id_factura DESC";

            $stmt = $pdo->prepare($sqlDetalles);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->execute();
            $detalles = $this->descifrarDatosCliente($stmt->fetchAll(PDO::FETCH_ASSOC));

            if (!$detalles) {
                return ['resultado' => 'error', 'mensaje' => 'No hay facturas registradas para esta cédula.'];
            }
            
            $tasa = (new DolarService())->obtenerPrecioDolar();
            
            // Obtener los estatus y observaciones desde tbl_detalles_pago
            $stmtPagos = $pdo->prepare("SELECT id_factura, estatus, observaciones FROM tbl_detalles_pago");
            $stmtPagos->execute();
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
            $mapaPagos = [];
            foreach ($pagos as $pago) {
                $mapaPagos[$pago['id_factura']] = [
                    'estatus' => $pago['estatus'],
                    'observaciones' => $pago['observaciones']
                ];
            }

            $facturasAgrupadas = [];
            foreach ($detalles as $item) {
                $facturasAgrupadas[$item['id_factura']][] = $item;
            }

            $html = '<div class="accordion w-100" id="accordionFacturas" style="width: 100%; height: 100%;">';

            foreach ($facturasAgrupadas as $id_factura => $items) {
                $fila = $items[0]; // Datos comunes
                $estatus = $fila['estatus'];
                
                // Verificar si es una factura pagada presencialmente y si todos los pagos están procesados
                $esPagadaPresencialmente = ($estatus == 'Pagada Presencialmente');
                $esBorrador = ($estatus == 'Borrador');
                $esAnulada = (stripos((string)$estatus, 'anulada') !== false);
                $todosPagosProcesados = true;
                
                if ($esPagadaPresencialmente && isset($estatusPorFactura[$id_factura])) {
                    foreach ($estatusPorFactura[$id_factura] as $estatusPago) {
                        if ($estatusPago !== 'Pago Procesado') {
                            $todosPagosProcesados = false;
                            break;
                        }
                    }
                }
                
                // Si es pagada presencialmente pero no todos los pagos están procesados, saltar esta factura
                if ($esPagadaPresencialmente && !$todosPagosProcesados) {
                    continue;
                }

                $datosCliente = '<p><strong>Cliente:</strong> ' . htmlspecialchars((string)($fila['nombre'] ?? '')) .  ' | ';
                $datosCliente .= '<strong>Cédula:</strong> ' . htmlspecialchars((string)($fila['cedula'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Teléfono:</strong> ' . htmlspecialchars((string)($fila['telefono'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Dirección:</strong> ' . htmlspecialchars((string)($fila['direccion'] ?? '')) . ' | ';
                //$datosCliente .= '<strong>Descuento:</strong> ' . htmlspecialchars((string)($fila['descuento'] ?? '')) . '% | ';
                
                // Aplicar estilo especial para diferentes estatus
                if ($esPagadaPresencialmente) {
                    $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-success">' . htmlspecialchars($estatus) . '</span></p>';
                } else if ($esBorrador) {
                    $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-warning">' . htmlspecialchars($estatus) . '</span></p>';
                } else if ($esAnulada) {
                    $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-danger">' . htmlspecialchars($estatus) . '</span></p>';   
                } else {
                    $datosCliente .= '<strong>Estatus:</strong> ' . htmlspecialchars($estatus) . '</p>';
                }

                $form = '<input type="hidden" name="id_factura" value="' . $id_factura . '">';
                $mensajePago = '';
                $botones = '';

                $estatusPago = $mapaPagos[$id_factura]['estatus'] ?? null;
                $observaciones = $mapaPagos[$id_factura]['observaciones'] ?? null;
                $estadoResumen = $estatus;
                if (stripos((string)$estatus, 'anul') === false && stripos((string)$estatus, 'cancel') === false && $estatusPago && $estatusPago !== 'Pago Procesado') {
                    $estadoResumen = $estatusPago;
                }

                if ($esBorrador) {
                    $mensajePago = '<div class="alert alert-warning"><strong>Borrador:</strong> El pago aún no ha sido enviado para validación.</div>';
                    $botones = '
                        <form action="?pagina=PasareladePago" method="POST" style="display:inline;">
                            <input type="hidden" name="id_factura" value="' . $id_factura . '">
                            <button type="submit" class="btn btn-success btn-lg">Pagar</button>
                        </form>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="id_factura" value="' . $id_factura . '">
                            <button type="button" class="btn btn-danger btn-lg cancelar" name="accion" value="cancelar">Cancelar</button>
                        </form>';
                } else if ($estatusPago) {
                    switch ($estatusPago) {
                        case 'En Proceso':
                            $mensajePago = '<div class="alert alert-info"><strong>En proceso:</strong> El pago está siendo validado por un administrador. Por favor, espere la confirmación.</div>';
                            break;

                        case 'Pago Incompleto':
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago incompleto:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '</div>';
                            break;

                        case 'Pago Procesado':
                            // Mensaje especial para facturas pagadas presencialmente
                            if ($esPagadaPresencialmente) {
                                $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
                            } else {
                                $mensajePago = '<div class="alert alert-success"><strong>Pago procesado correctamente.</strong></div>';
                            }
                            $botones = '
                            <form action="" method="POST" target="_blank" style="display:inline;">
                                <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                                <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
                            </form>';
                            break;

                        case 'Pago No Encontrado':
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago no encontrado:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '<br>Por favor, verifique los datos registrados en la pasarela de pago.</div>';
                            break;

                        default:
                            $mensajePago = '<div class="alert alert-secondary">Estado de pago no reconocido.</div>';
                            break;
                    }
                } else if ($esPagadaPresencialmente) {
                    // Caso especial para facturas pagadas presencialmente sin registro en tbl_detalles_pago
                    $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
                    $botones = '
                    <form action="" method="POST" target="_blank" style="display:inline;">
                        <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                        <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
                    </form>';
                }

                $contenido = '<div class="w-100">' . $datosCliente . $mensajePago;
                $contenido .= '<div class="table-responsive w-100">';
                $contenido .= '<table class="table table-bordered w-100">';
                $contenido .= '<thead><tr><th>Producto</th><th>modelo</th><th>Marca</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>';

                $total = 0;
                foreach ($items as $item) {
                    $subtotal = $item['precio'] * $tasa * $item['cantidad'];
                    $total += $subtotal;

                    $contenido .= '<tr>';
                    $producto = htmlspecialchars($item['producto'] ?? '');
                    if (!empty($item['id_combo'])) {
                        $producto .= ' <small>(Combo: ' . htmlspecialchars((string)($item['nombre_combo'] ?? ('#' . $item['id_combo']))) . ')</small>';
                    }
                    $contenido .= '<td>' . $producto . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($item['nombre_modelo'] ?? '') . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($item['nombre_marca'] ?? '') . '</td>';
                    $contenido .= '<td>' . ($item['cantidad'] ?? '0') . '</td>';
                    $contenido .= '<td>' . number_format(($item['precio'] ?? 0) * $tasa, 2) . ' BS</td>';
                    $contenido .= '</tr>';
                }

                $descuento = $total * $fila['descuento'] / 100;
                $subtotalConDescuento = $total - $descuento;
                $impuesto = $subtotalConDescuento * 0.16;
                $montoTotal = $subtotalConDescuento + $impuesto;
                $contenido .= '<tr><td colspan="4"><strong>Total con Impuestos:</strong></td><td>' . number_format($montoTotal, 2) . ' BS</td></tr>';            
                $contenido .= '</tbody></table></div>' . $form . $botones . '</div>';

                $html .= '<div class="accordion-item w-100" data-summary-status="' . htmlspecialchars((string)$estadoResumen, ENT_QUOTES, 'UTF-8') . '">';
                $html .= '<h2 class="accordion-header" id="heading' . $id_factura . '">';
                
                // Estilo especial para el encabezado de facturas según su estatus
                if ($esPagadaPresencialmente) {
                    $html .= '<button class="accordion-button collapsed w-100 bg-success text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
                } else if ($esBorrador) {
                    $html .= '<button class="accordion-button collapsed w-100  text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
                } else {
                    $html .= '<button class="accordion-button collapsed w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
                }
                
                $html .= 'Pedido #' . $id_factura . ' - Fecha: ' . $fila['fecha'] . '</button></h2>';
                $html .= '<div id="collapse' . $id_factura . '" class="accordion-collapse collapse" aria-labelledby="heading' . $id_factura . '" data-bs-parent="#accordionFacturas">';
                $html .= '<div class="accordion-body w-100">' . $contenido . '</div></div></div>';
            }

            $html .= '</div>';

            $resulListado = [
                'resultado' => 'listado',
                'mensaje' => $html
            ];
        return $resulListado;
        });
    }
    

public function facturaConsultarMovil()
{
    return $this->ejecutarConConexionSegura(function ($pdo) {
        if (empty($this->cedula)) {
            $pdo->cerrar();
            $pdo = null;

            return [
                'resultado' => 'error',
                'mensaje' => 'No se ha proporcionado la cédula para consultar facturas.',
                'data' => [
                    'facturas' => []
                ]
            ];
        }

        try {
            $sql = "
                SELECT 
                    f.id_factura,
                    f.fecha,
                    c.nombre,
                    c.cedula,
                    c.telefono,
                    c.direccion,
                    p.nombre_producto AS producto,
                    m.nombre_modelo,
                    mar.nombre_marca,
                    CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                        THEN p.precio ELSE df.precio_unitario END AS precio,
                    df.id_combo,
                    cb.nombre_combo,
                    df.cantidad,
                    f.descuento,
                    f.estatus
                FROM tbl_factura_detalle df
                JOIN tbl_facturas f ON f.id_factura = df.factura_id
                JOIN tbl_clientes c ON c.id_clientes = f.cliente
                JOIN tbl_productos p ON df.id_producto = p.id_producto
                LEFT JOIN tbl_combo cb ON cb.id_combo = df.id_combo
                JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
                JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
                WHERE c.cedula = :cedula
                ORDER BY f.id_factura DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
            $stmt->execute();

            $facturasMap = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idFactura = (int)($row['id_factura'] ?? 0);

                if (!isset($facturasMap[$idFactura])) {
                    $facturasMap[$idFactura] = [
                        'id_factura' => $idFactura,
                        'numero_factura' => 'FAC-' . $idFactura,
                        'fecha' => $row['fecha'] ?? date('Y-m-d'),
                        'estado' => trim((string)($row['estatus'] ?? 'Borrador')),
                        'descuento' => (float)($row['descuento'] ?? 0),
                        'cliente' => [
                            'nombre' => trim((string)($row['nombre'] ?? 'Cliente')),
                            'cedula' => trim((string)($row['cedula'] ?? '')),
                            'telefono' => trim((string)($row['telefono'] ?? '')),
                            'direccion' => trim((string)($row['direccion'] ?? ''))
                        ],
                        'items' => [],
                        'total_suma' => 0
                    ];
                }

                $precio = (float)($row['precio'] ?? 0);
                $cantidad = (int)($row['cantidad'] ?? 0);
                $subtotal = $precio * $cantidad;

                $facturasMap[$idFactura]['total_suma'] += $subtotal;

                $facturasMap[$idFactura]['items'][] = [
                    'producto' => trim((string)($row['producto'] ?? 'Producto')),
                    'modelo' => trim((string)($row['nombre_modelo'] ?? '')),
                    'marca' => trim((string)($row['nombre_marca'] ?? '')),
                    'precio' => $precio,
                    'id_combo' => isset($row['id_combo']) ? (int)$row['id_combo'] : null,
                    'nombre_combo' => $row['nombre_combo'] ?? null,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];
            }

            $facturas = [];

            foreach ($facturasMap as $factura) {
                $subtotal = (float)$factura['total_suma'];
                $descuento = (float)$factura['descuento'];

                $totalFinal = $subtotal;
                if ($descuento > 0) {
                    $totalFinal = $subtotal - (($subtotal * $descuento) / 100);
                }

                $facturas[] = [
                    'id_factura' => $factura['id_factura'],
                    'numero_factura' => $factura['numero_factura'],
                    'fecha' => $factura['fecha'],
                    'total' => round($totalFinal, 2),
                    'estado' => ucfirst(strtolower(trim($factura['estado']))),
                    'descuento' => $descuento,
                    'metodo_pago' => 'Sin método',
                    'cliente' => $factura['cliente']['nombre'],
                    'cliente_detalle' => $factura['cliente'],
                    'items' => $factura['items']
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Listado de facturas obtenido correctamente.',
                'data' => [
                    'facturas' => $facturas
                ]
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Error al consultar facturas.',
                'data' => [
                    'facturas' => []
                ],
                'debug' => $e->getMessage()
            ];
        }
        });
    }
    


    public function facturaCancelar($id) {
        return $this->c_facturaCancelar($id);
    }
    private function c_facturaCancelar($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $stmt = $pdo->prepare("UPDATE tbl_facturas SET estatus = 'Anulada' WHERE id_factura = ?");
            return $stmt->execute([$id]);
        });
    }

    public function facturaProcesar($id, $estatus) {
        return $this->p_facturaProcesar($id, $estatus);
    }
    private function p_facturaProcesar($id, $estatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $estatus){
            $stmt = $pdo->prepare("UPDATE tbl_facturas SET estatus = ? WHERE id_factura = ?");
            return $stmt->execute([$estatus, $id]);
        });
    }

    public function obtenerMontoTotalFactura($id_factura) {
        return $this->o_montoTotalFactura($id_factura);
    }
    private function o_montoTotalFactura($id_factura) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_factura) {
            try {
                $sql = "SELECT 
                        ROUND(
                            (
                                SUM(CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                                    THEN p.precio ELSE df.precio_unitario END * df.cantidad) * (1 - (f.descuento / 100))
                            ) * 1.16, 2
                        ) AS total_con_impuesto
                    FROM tbl_factura_detalle df
                    JOIN tbl_facturas f ON f.id_factura = df.factura_id
                    JOIN tbl_productos p ON df.id_producto = p.id_producto
                    WHERE f.id_factura = :id_factura
                    GROUP BY f.id_factura";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
                $stmt->execute();
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$resultado) {
                    return 0;
                }

                $tasa = 1;
                try {
                    $stmtDolar = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                    $stmtDolar->execute();
                    $resultDolar = $stmtDolar->fetch(PDO::FETCH_ASSOC);
                    
                    if ($resultDolar && (time() - strtotime($resultDolar['fecha'])) < 86400) {
                        $tasa = floatval($resultDolar['precio']);
                    }
                } catch (PDOException $e) {
                    error_log('Error al obtener cache del dólar: ' . $e->getMessage());
                }

                $total = $resultado['total_con_impuesto'] * $tasa;
                return $total;

            } catch (PDOException $e) {
                error_log('Error al obtener monto total de factura: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    public function facturaDescargar($id_factura) {
        return $this->f_descargar($id_factura);
    }

    public function facturaDescargarMovil($data = []) {
        return $this->f_descargarMovil($data);
    }

    private function f_descargarMovil($data) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($data) {
            $id_factura = $data['id'] ?? $data['id_factura'] ?? null;

            if (empty($id_factura)) {
                throw new PDOException("El ID de factura es requerido.");
            }

            try {
                $stmt = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Validar si la tasa está vigente (menos de 24 horas) o asignar una por defecto (1)
                $tasa = 1;
                if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                    $tasa = floatval($result['precio']);
                }
            } catch (PDOException $e) {
                error_log('Error al obtener cache del dólar: ' . $e->getMessage());
                $tasa = 1;
            }

            // Verificar si la factura existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_facturas WHERE id_factura = ?");
            $stmt_check->execute([$id_factura]);
            $count_factura = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($count_factura['count'] == 0) {
                throw new PDOException("La factura ID $id_factura no existe.");
            }

            // Consulta de la factura
            $sql = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
                        p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
                        CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                            THEN p.precio ELSE df.precio_unitario END AS precio,
                        df.id_combo, cb.nombre_combo, df.cantidad, f.descuento, f.estatus
                    FROM tbl_factura_detalle df
                    JOIN tbl_facturas f ON f.id_factura = df.factura_id
                    JOIN tbl_clientes c ON c.id_clientes = f.cliente
                    JOIN tbl_productos p ON df.id_producto = p.id_producto
                    LEFT JOIN tbl_combo cb ON cb.id_combo = df.id_combo
                    JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
                    JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
                    WHERE f.id_factura = :id_factura";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
            $stmt->execute();
            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($facturas)) {
                throw new PDOException("No se encontraron detalles para la factura ID $id_factura");
            }
            
            // Agregar precio_convertido
            foreach ($facturas as &$factura) {
                $factura['precio_convertido'] = $factura['precio'] * $tasa;
            }
            
            // Agrupar por factura para estructura de respuesta
            $facturaMap = [];
            foreach ($facturas as $row) {
                $id = $row['id_factura'];
                if (!isset($facturaMap[$id])) {
                    $facturaMap[$id] = [
                        'id_factura' => $row['id_factura'],
                        'numero_factura' => 'FAC-' . $row['id_factura'],
                        'fecha' => $row['fecha'],
                        'estatus' => ucfirst(strtolower(trim($row['estatus']))),
                        'descuento' => (float)$row['descuento'],
                        'tasa_cambio' => $tasa,
                        'cliente' => [
                            'nombre' => trim($row['nombre']),
                            'cedula' => trim($row['cedula']),
                            'telefono' => trim($row['telefono']),
                            'direccion' => trim($row['direccion'])
                        ],
                        'items' => []
                    ];
                }
                
                $facturaMap[$id]['items'][] = [
                    'producto' => trim($row['producto']),
                    'modelo' => trim($row['nombre_modelo']),
                    'marca' => trim($row['nombre_marca']),
                    'precio' => (float)$row['precio'],
                    'id_combo' => isset($row['id_combo']) ? (int)$row['id_combo'] : null,
                    'nombre_combo' => $row['nombre_combo'] ?? null,
                    'precio_convertido' => (float)$row['precio_convertido'],
                    'cantidad' => (int)$row['cantidad'],
                    'subtotal' => (float)$row['precio'] * (int)$row['cantidad']
                ];
            }

            // Calcular totales
            foreach ($facturaMap as &$factura) {
                $subtotal = 0;
                foreach ($factura['items'] as $item) {
                    $subtotal += $item['subtotal'];
                }
                $factura['subtotal'] = $subtotal;
                $factura['total'] = $factura['descuento'] > 0 
                    ? $subtotal - (($subtotal * $factura['descuento']) / 100)
                    : $subtotal;
            }

            return [
                'factura' => array_values($facturaMap)[0],
                'tasa_cambio' => $tasa
            ];
        });
    }
    private function f_descargar($id_factura) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_factura) {
            try {
                $stmt = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Validar si la tasa está vigente (menos de 24 horas) o asignar una por defecto (1)
                $tasa = 1;
                if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                    $tasa = floatval($result['precio']);
                }
            } catch (PDOException $e) {
                error_log('Error al obtener cache del dólar: ' . $e->getMessage());
                $tasa = 1; // Valor por defecto en caso de error
            }

            // DEBUG: Verificar si la factura existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_facturas WHERE id_factura = ?");
            $stmt_check->execute([$id_factura]);
            $count_factura = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($count_factura['count'] == 0) {
                die("DEBUG: La factura ID $id_factura NO existe en tbl_facturas");
            }

            // DEBUG: Verificar si tiene detalles
            $stmt_check_detalle = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_factura_detalle WHERE factura_id = ?");
            $stmt_check_detalle->execute([$id_factura]);
            $count_detalle = $stmt_check_detalle->fetch(PDO::FETCH_ASSOC);
            
            if ($count_detalle['count'] == 0) {
                die("DEBUG: La factura ID $id_factura existe pero NO tiene detalles en tbl_factura_detalle");
            }

            // Consulta de la factura (misma estructura que la consulta funcional de la tabla)
            $sql = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
                        p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
                        p.precio AS precio_base,
                        CASE WHEN df.precio_unitario IS NULL OR (df.id_combo IS NULL AND df.precio_unitario = 0)
                            THEN p.precio ELSE df.precio_unitario END AS precio,
                        df.id_combo, cb.nombre_combo, df.cantidad, f.descuento, f.estatus
                    FROM tbl_factura_detalle df
                    JOIN tbl_facturas f ON f.id_factura = df.factura_id
                    JOIN tbl_clientes c ON c.id_clientes = f.cliente
                    JOIN tbl_productos p ON df.id_producto = p.id_producto
                    LEFT JOIN tbl_combo cb ON cb.id_combo = df.id_combo
                    JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
                    JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
                    WHERE f.id_factura = :id_factura";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
            $stmt->execute();
            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // DEBUG: Mostrar resultados
            if (empty($facturas)) {
                die("DEBUG: La consulta SQL no devolvió resultados. SQL: $sql, ID: $id_factura");
            } else {
                // DEBUG: Mostrar cantidad de resultados
                error_log("DEBUG: Se encontraron " . count($facturas) . " resultados para factura ID $id_factura");
            }
            
            // Agregar precio_convertido después de la consulta (si es necesario)
            if (!empty($facturas)) {
                foreach ($facturas as &$factura) {
                    $factura['precio_convertido'] = $factura['precio'] * $tasa;
                    $factura['precio_base_convertido'] = (float)$factura['precio_base'] * $tasa;
                }
            }
            $facturas = $this->descifrarDatosCliente($facturas);
            return $facturas;
        });
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para el registro de una nueva factura
     */
    private function validarDatosRegistro() {
        $errores = [];
        
        // Validar fecha
        if (empty($this->fecha)) {
            $errores[] = 'La fecha es obligatoria';
        } elseif (!strtotime($this->fecha)) {
            $errores[] = 'La fecha no tiene un formato válido';
        }
        
        // Validar cliente (cedula)
        if (empty($this->cliente)) {
            $errores[] = 'La cédula del cliente es obligatoria';
        } elseif (mb_strlen($this->cliente) < self::MIN_CLIENTE || mb_strlen($this->cliente) > self::MAX_CLIENTE) {
            $errores[] = 'La cédula del cliente debe tener entre ' . self::MIN_CLIENTE . ' y ' . self::MAX_CLIENTE . ' caracteres';
        }
        
        // Validar descuento
        if (!is_numeric($this->descuento)) {
            $errores[] = 'El descuento debe ser un número';
        } elseif ($this->descuento < self::MIN_DESCUENTO || $this->descuento > self::MAX_DESCUENTO) {
            $errores[] = 'El descuento debe estar entre ' . self::MIN_DESCUENTO . ' y ' . self::MAX_DESCUENTO;
        }
        
        // Validar estatus
        if (empty($this->estatus)) {
            $errores[] = 'El estatus es obligatorio';
        } elseif (!in_array($this->estatus, self::ESTADOS_PERMITIDOS)) {
            $errores[] = 'El estatus no es válido. Valores permitidos: ' . implode(', ', self::ESTADOS_PERMITIDOS);
        }
        
        // Validar productos
        if (empty($this->id_producto) || !is_array($this->id_producto)) {
            $errores[] = 'Debe seleccionar al menos un producto';
        }
        
        // Validar cantidades
        if (empty($this->cantidad) || !is_array($this->cantidad)) {
            $errores[] = 'Debe especificar las cantidades de los productos';
        } elseif (count($this->id_producto) !== count($this->cantidad)) {
            $errores[] = 'El número de productos y cantidades debe coincidir';
        } else {
            // Validar cada cantidad
            foreach ($this->cantidad as $index => $cantidad) {
                if (!is_numeric($cantidad) || $cantidad < self::MIN_CANTIDAD || $cantidad > self::MAX_CANTIDAD) {
                    $errores[] = 'La cantidad del producto ' . ($index + 1) . ' debe estar entre ' . self::MIN_CANTIDAD . ' y ' . self::MAX_CANTIDAD;
                }
            }
        }
        
        return $errores;
    }

    /**
     * Valida los datos para consultar facturas
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID de la factura (opcional)
        if (isset($datos['id_factura'])) {
            if (!is_numeric($datos['id_factura']) || $datos['id_factura'] <= 0) {
                $errores['id_factura'] = 'El ID de la factura debe ser un número positivo';
            }
        }
        
        // Validar cédula del cliente (opcional)
        if (isset($datos['cedula'])) {
            $cedula = trim($datos['cedula']);
            if (empty($cedula)) {
                $errores['cedula'] = 'La cédula del cliente es obligatoria';
            } elseif (mb_strlen($cedula) < self::MIN_CLIENTE || mb_strlen($cedula) > self::MAX_CLIENTE) {
                $errores['cedula'] = 'La cédula del cliente debe tener entre ' . self::MIN_CLIENTE . ' y ' . self::MAX_CLIENTE . ' caracteres';
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
        
        // Validar estatus de la factura (opcional)
        if (isset($datos['estatus'])) {
            $estatus = trim($datos['estatus']);
            if (!empty($estatus) && !in_array($estatus, self::ESTADOS_PERMITIDOS)) {
                $errores['estatus'] = 'El estatus debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para cancelar facturas
     */
    private function validarCancelar($datos) {
        $errores = [];
        
        // Validar ID de la factura
        if (!isset($datos['id_factura'])) {
            $errores['id_factura'] = 'El ID de la factura es obligatorio';
        } elseif (!is_numeric($datos['id_factura']) || $datos['id_factura'] <= 0) {
            $errores['id_factura'] = 'El ID de la factura debe ser un número positivo';
        }
        
        // Validar motivo de cancelación (opcional)
        if (isset($datos['motivo_cancelacion'])) {
            $motivo = trim($datos['motivo_cancelacion']);
            if (mb_strlen($motivo) > 200) {
                $errores['motivo_cancelacion'] = 'El motivo de cancelación no debe exceder los 200 caracteres';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarFacturas($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para cancelar (método público)
     */
    public function validarCancelarFactura($datos) {
        return $this->validarCancelar($datos);
    }
}
?>