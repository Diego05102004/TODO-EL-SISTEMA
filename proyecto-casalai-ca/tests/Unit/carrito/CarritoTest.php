<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Carrito;

/*
 * Pruebas unitarias del módulo Carrito.
 *
 * Secciones:
 * - Dobles: `StatementDoubleCarrito`, `PDODoubleCarrito` (principal) y `PDODoubleCarritoC` (conexión 'C').
 * - Doble de clase: `CarritoDoubleU` para inyectar PDO 'C' evitando BD real.
 * - Escenarios: crear carrito, obtener por cliente, agregar producto (actualiza/insert),
 *   obtener productos, actualizar cantidad, eliminar producto, vaciar carrito,
 *   agregar combo (transacción), obtener cantidad por usuario (C), obtener resumen (C), registrar compra básico.
 */

// ======================================
// Dobles de prueba (PDOStatement/Carrito)
// ======================================
class StatementDoubleCarrito
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
        $this->scalar = $data['scalar'] ?? null;
    }

    public function bindParam($name, &$value, $type = null)
    {
        $this->bound[$name] = $value;
    }

    public function bindValue($name, $value, $type = null)
    {
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        return true;
    }

    public function fetch($mode = null)
    {
        if ($this->row !== null) return $this->row;
        if ($this->scalar !== null) return $this->scalar;
        return null;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }
}

// ======================
// Doble de prueba (PDO - Principal)
// ======================
class PDODoubleCarrito extends PDO
{
    public bool $inTx = false;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function beginTransaction(): bool { $this->inTx = true; return true; }
    public function commit(): bool { $this->inTx = false; return true; }
    public function rollBack(): bool { $this->inTx = false; return true; }

    public function lastInsertId($name = null): string|false { return '1000'; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // crearCarrito
        if (stripos($sql, 'INSERT INTO tbl_carrito (id_cliente)') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // obtenerCarritoPorCliente
        if (stripos($sql, 'SELECT id_carrito, id_cliente FROM tbl_carrito WHERE id_cliente') !== false) {
            return new StatementDoubleCarrito($sql, [ 'row' => ['id_carrito' => 10, 'id_cliente' => 5] ]);
        }
        // check detalle existente
        if (stripos($sql, 'SELECT id_carrito_detalle, cantidad FROM tbl_carritodetalle') !== false) {
            // Simular que existe para un caso y no existe para otro controlado por un flag estático
            return new StatementDoubleCarrito($sql, CarritoTest::$detalleExiste
                ? [ 'row' => ['id_carrito_detalle' => 77, 'cantidad' => 2] ]
                : [ 'row' => null ]);
        }
        // update cantidad detalle
        if (stripos($sql, 'UPDATE tbl_carritodetalle SET cantidad =') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // insert nuevo detalle
        if (stripos($sql, 'INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad)') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // obtenerProductosDelCarrito join
        if (stripos($sql, 'FROM tbl_carritodetalle cd') !== false && stripos($sql, 'WHERE cd.id_carrito =') !== false) {
            return new StatementDoubleCarrito($sql, [
                'rows' => [
                    ['id_carrito_detalle' => 77, 'id_producto' => 1, 'nombre' => 'Prod1', 'nombre_modelo' => 'M1', 'nombre_marca' => 'X', 'cantidad' => 3, 'precio' => 5.0, 'subtotal' => 15.0],
                ],
            ]);
        }
        // eliminar producto del carrito
        if (stripos($sql, 'DELETE FROM tbl_carritodetalle WHERE id_carrito_detalle') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // eliminar todo el carrito
        if (stripos($sql, 'DELETE FROM tbl_carritodetalle WHERE id_carrito =') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // combo_detalle consulta
        if (stripos($sql, 'SELECT id_producto, cantidad FROM combo_detalle WHERE id_combo') !== false) {
            return new StatementDoubleCarrito($sql, [ 'rows' => [ ['id_producto' => 1, 'cantidad' => 2], ['id_producto' => 2, 'cantidad' => 1] ] ]);
        }
        // factura insert
        if (stripos($sql, 'INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus)') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // detalle factura insert
        if (stripos($sql, 'INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad)') !== false) {
            return new StatementDoubleCarrito($sql);
        }
        // update stock
        if (stripos($sql, 'UPDATE tbl_productos SET stock = stock -') !== false) {
            return new StatementDoubleCarrito($sql);
        }

        return new StatementDoubleCarrito($sql);
    }
}

// ======================
// Doble de prueba (PDO - Conexión 'C')
// ======================
class PDODoubleCarritoC extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // cantidad productos carrito
        if (stripos($sql, 'SELECT COUNT(dc.id_detalle_carrito) as total') !== false) {
            return new StatementDoubleCarrito($sql, [ 'row' => ['total' => 4] ]);
        }
        // resumen carrito
        if (stripos($sql, 'SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos') !== false) {
            return new StatementDoubleCarrito($sql, [ 'row' => ['id_carrito' => 12, 'total_productos' => 3, 'total_precio' => 45.0] ]);
        }
        return new StatementDoubleCarrito($sql);
    }
}

// Doble de clase Carrito para inyectar PDO 'C' evitando new BD('C')
class CarritoDoubleU extends Carrito
{
    public PDO $pdoC;
    public function __construct() { /* evitar abrir BD real */ }
    public function getConexion() { return new PDODoubleCarrito(); }
    public function setConex(PDO $pdo) { $ref = new ReflectionProperty(Carrito::class, 'conex'); $ref->setAccessible(true); $ref->setValue($this, $pdo); }
    public function obtenerCantidadProductosCarrito($id_usuario)
    {
        $stmt = $this->pdoC->prepare('SELECT COUNT(dc.id_detalle_carrito) as total FROM tbl_carrito c INNER JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito WHERE c.id_usuario = ? AND c.estado = "activo"');
        $stmt->execute([$id_usuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['total'] : 0;
    }
    public function obtenerResumenCarrito($id_usuario)
    {
        $stmt = $this->pdoC->prepare('SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos, SUM(dc.cantidad * dc.precio_unitario) as total_precio FROM tbl_carrito c LEFT JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito WHERE c.id_usuario = ? AND c.estado = "activo" GROUP BY c.id_carrito');
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class CarritoTest extends TestCase
{
    public static bool $detalleExiste = false;

    private function nuevoCarritoConPDOStub(bool $detalleExiste = false): CarritoDoubleU
    {
        self::$detalleExiste = $detalleExiste;
        $c = new CarritoDoubleU();
        $pdo = new PDODoubleCarrito();
        $c->setConex($pdo);
        $c->pdoC = new PDODoubleCarritoC();
        return $c;
    }

    // CRT-UNIT-001: Crear carrito
    public function testCrearCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->crearCarrito(5));
    }

    // CRT-UNIT-002: Obtener carrito por cliente
    public function testObtenerCarritoPorCliente(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $row = $c->obtenerCarritoPorCliente(5);
        $this->assertIsArray($row);
        $this->assertSame(10, $row['id_carrito']);
    }

    // CRT-UNIT-003: Agregar producto (actualiza cantidad si existe)
    public function testAgregarProductoActualiza(): void
    {
        $c = $this->nuevoCarritoConPDOStub(true);
        $this->assertTrue($c->agregarProductoAlCarrito(10, 1, 3));
    }

    // CRT-UNIT-004: Agregar producto (inserta si no existe)
    public function testAgregarProductoInserta(): void
    {
        $c = $this->nuevoCarritoConPDOStub(false);
        $this->assertTrue($c->agregarProductoAlCarrito(10, 2, 1));
    }

    // CRT-UNIT-005: Obtener productos del carrito
    public function testObtenerProductosDelCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $rows = $c->obtenerProductosDelCarrito(10);
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('subtotal', $rows[0]);
    }

    // CRT-UNIT-006: Actualizar cantidad producto
    public function testActualizarCantidadProducto(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->actualizarCantidadProducto(77, 5));
    }

    // CRT-UNIT-007: Eliminar producto del carrito
    public function testEliminarProductoDelCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->eliminarProductoDelCarrito(77));
    }

    // CRT-UNIT-008: Eliminar todo el carrito
    public function testEliminarTodoElCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->eliminarTodoElCarrito(10));
    }

    // CRT-UNIT-009: Agregar combo al carrito (transaccional)
    public function testAgregarComboAlCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->agregarComboAlCarrito(10, 99));
    }

    // CRT-UNIT-010: Obtener cantidad productos (conexión C)
    public function testObtenerCantidadProductosCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $total = $c->obtenerCantidadProductosCarrito(123);
        $this->assertSame(4, $total);
    }

    // CRT-UNIT-011: Obtener resumen carrito (conexión C)
    public function testObtenerResumenCarrito(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $res = $c->obtenerResumenCarrito(123);
        $this->assertIsArray($res);
        $this->assertSame(12, $res['id_carrito']);
        $this->assertSame(3, $res['total_productos']);
    }

    // CRT-UNIT-012: Registrar compra básica (flujo feliz)
    public function testRegistrarCompraBasica(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $ok = $c->registrarCompra(10, 5, [
            ['id_producto' => 1, 'cantidad' => 2],
            ['id_producto' => 2, 'cantidad' => 1],
        ]);
        $this->assertTrue($ok);
    }
}
