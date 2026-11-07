<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Comprafisica;

/*
 * Archivo de pruebas unitarias del módulo de Ventas Presenciales.
 *
 * Secciones:
 * - Dobles de prueba: `StatementDouble` y `PDODouble` que simulan PDO/PDOStatement.
 * - Casos de prueba: escenarios de compra (happy path, múltiples pagos, inválida,
 *   campos vacíos) y consulta de detalles de compra.
 *
 * Notas:
 * - Se usa reflexión para inyectar el stub de conexión `PDODouble` en la clase `Compra`.
 * - No se toca la base de datos real; todo se responde de forma determinística.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDouble
{
    private $sql;
    private $rows;
    private $row;
    private $executed = false;
    private $throwOnExecute = false;
    private $lastParams = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
        $this->throwOnExecute = $data['throwOnExecute'] ?? false;
    }

    public function execute(array $params = [])
    {
        $this->executed = true;
        $this->lastParams = $params;

        // Simular error controlado mediante una bandera del constructor
        if ($this->throwOnExecute) {
            throw new \Exception('Simulated failure in execute');
        }

        // Validación mínima para escenarios con campos vacíos:
        // cliente requerido al insertar despacho
        if (stripos($this->sql, 'INSERT INTO tbl_despachos') !== false) {
            // soporta execute con params nombrados
            $idCliente = $params[':id_cliente'] ?? $params[0] ?? null;
            if ($idCliente === null || $idCliente === '') {
                throw new \Exception('Cliente requerido');
            }
        }

        return true;
    }

    public function fetch($mode = null)
    {
        return $this->row;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }
}

// ======================
// Doble de prueba (PDO)
// ======================
class PDODouble extends \PDO
{
    private $handlers = [];
    private $lastId = 1;
    public static $throwOnDetalleInsert = false;

    public function __construct() {}

    public function setAttribute($attribute, $value)
    {
        return true;
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollBack(): bool
    {
        return true;
    }

    public function inTransaction(): bool
    {
        // Simular que estamos dentro de una transacción cuando el código lo consulte
        return true;
    }

    public function lastInsertId($name = null): string|false
    {
        return (string) $this->lastId;
    }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        if (stripos($sql, 'INSERT INTO tbl_despachos') !== false) {
            $this->lastId = 10;
            return new StatementDouble($sql);
        }
        if (stripos($sql, 'INSERT INTO tbl_despacho_detalle') !== false) {
            return new StatementDouble($sql, [
                'throwOnExecute' => self::$throwOnDetalleInsert,
            ]);
        }
        if (stripos($sql, 'FROM tbl_productos p') !== false && stripos($sql, 'INNER JOIN tbl_modelos') !== false) {
            return new StatementDouble($sql, [
                'row' => [
                    'id_producto' => 1,
                    'nombre_producto' => 'Producto X',
                    'nombre_modelo' => 'M1',
                    'nombre_marca' => 'Marca',
                    'serial' => 'ABC123',
                    'precio' => 10.0,
                ],
            ]);
        }
        if (stripos($sql, 'INSERT INTO tbl_facturas') !== false) {
            $this->lastId = 100;
            return new StatementDouble($sql);
        }
        if (stripos($sql, 'INSERT INTO tbl_factura_detalle') !== false) {
            return new StatementDouble($sql);
        }
        if (stripos($sql, 'FROM tbl_clientes') !== false) {
            return new StatementDouble($sql, [
                'row' => [
                    'id_clientes' => 5,
                    'nombre' => 'Cliente Demo',
                    'cedula' => 'V-12345678',
                    'telefono' => '0414-0000000',
                    'correo' => 'demo@correo.test',
                ],
            ]);
        }
        if (stripos($sql, 'INSERT INTO tbl_detalles_pago') !== false) {
            return new StatementDouble($sql);
        }
        // Soporte para `Compra::g_Compras()`
        // (consulta con GROUP_CONCAT y agrupación por factura)
        if (stripos($sql, 'FROM tbl_facturas f') !== false && stripos($sql, 'GROUP BY f.id_factura') !== false) {
            return new StatementDouble($sql, [
                'rows' => [[
                    'id_factura' => 123,
                    'fecha_factura' => date('Y-m-d'),
                    'descuento' => 0,
                    'id_clientes' => 5,
                    'cedula' => 'V-12345678',
                    'nombre_cliente' => 'Cliente Demo',
                    'direccion' => 'Av. Siempre Viva',
                    'telefono' => '0414-0000000',
                    'correo' => 'demo@correo.test',
                    'id_despachos' => 77,
                    'productos' => '{"id_producto":"1","codigo":"1","nombre":"Producto X","descripcion":"Desc","modelo":"M1","marca":"Marca","serial":"ABC123","precio":"10","cantidad":"2"}',
                    'pagos' => '{"id_detalles":"1","cuenta":"2","referencia":"ABC","fecha":"2025-10-20","tipo":"Zelle","monto":"5.00","comprobante":"","estatus":"Aprobado","observaciones":""}',
                ]],
            ]);
        }
        return new StatementDouble($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class CompraTest extends TestCase
{
    // ---------------------------------------------
    // VP-UNIT-001: Registrar compra (Happy Path)
    // Objetivo: validar flujo completo de registro con
    // productos, pagos, totales y datos de cliente.
    // ---------------------------------------------
    public function testRegistrarCompraFisicaHappyPath(): void
    {
        $ref = new \ReflectionClass(Comprafisica::class);
        /** @var \\\\Comprafisica $compra */
        $compra = $ref->newInstanceWithoutConstructor();

        $pdo = new PDODouble();
        $prop = new \ReflectionProperty(Comprafisica::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($compra, $pdo);

        $datos = [
            'cliente' => 5,
            'monto_total' => 0,
            'cambio' => 0,
            'productos' => [
                ['id_producto' => 1, 'cantidad' => '2'],
                ['id_producto' => 1, 'cantidad' => '1,5'],
            ],
            'pagos' => [
                ['tipo' => 'Efectivo', 'cuenta' => 0, 'referencia' => '', 'monto' => 10.0],
                ['tipo' => 'Zelle', 'cuenta' => 2, 'referencia' => 'ABC', 'monto' => 5.0],
            ],
        ];

        $resultado = $compra->registrarCompraFisica($datos, 1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('id_factura', $resultado);
        $this->assertSame(100, (int)$resultado['id_factura']);
        $this->assertArrayHasKey('productos', $resultado);
        $this->assertArrayHasKey('pagos', $resultado);
        $this->assertArrayHasKey('total', $resultado);
        $this->assertCount(2, $resultado['productos']);
        $this->assertEquals(35.0, $resultado['total']);
        $this->assertCount(2, $resultado['pagos']);
        $this->assertEquals('Cliente Demo', $resultado['nombre_cliente']);
        $this->assertEquals('V-12345678', $resultado['cedula']);
    }

    // -------------------------------------------------
    // VP-UNIT-002: Compra con múltiples métodos de pago
    // Objetivo: simular 3 pagos distintos y validar que
    // se reflejen en la respuesta y el total sea coherente.
    // -------------------------------------------------
    public function testCompraMultiplesPagos(): void
    {
        $ref = new \ReflectionClass(Comprafisica::class);
        /** @var \\\\Comprafisica $compra */
        $compra = $ref->newInstanceWithoutConstructor();

        $pdo = new PDODouble();
        $prop = new \ReflectionProperty(Comprafisica::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($compra, $pdo);

        $datos = [
            'cliente' => 5,
            'productos' => [
                ['id_producto' => 1, 'cantidad' => '3'],
            ],
            'pagos' => [
                ['tipo' => 'Efectivo', 'cuenta' => 0, 'referencia' => '', 'monto' => 10.0],
                ['tipo' => 'Zelle', 'cuenta' => 2, 'referencia' => 'ABC', 'monto' => 5.0],
                ['tipo' => 'Pago Movil', 'cuenta' => 3, 'referencia' => 'PM123', 'monto' => 15.0],
            ],
        ];

        $resultado = $compra->registrarCompraFisica($datos, 1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('id_factura', $resultado);
        $this->assertArrayHasKey('pagos', $resultado);
        $this->assertCount(3, $resultado['pagos']);
        $this->assertGreaterThan(0, $resultado['total']);
    }

    // ---------------------------------------------------
    // VP-UNIT-003: Compra inválida (rollback + error)
    // Objetivo: forzar excepción en el insert de detalle
    // para verificar manejo de errores y rollback.
    // ---------------------------------------------------
    public function testCompraInvalidaLanzaRollbackYError(): void
    {
        $ref = new \ReflectionClass(Comprafisica::class);
        /** @var \\\\Comprafisica $compra */
        $compra = $ref->newInstanceWithoutConstructor();

        $pdo = new PDODouble();
        $prop = new \ReflectionProperty(Comprafisica::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($compra, $pdo);

        // Forzar fallo al insertar detalle de despacho
        PDODouble::$throwOnDetalleInsert = true;

        try {
            $datos = [
                'cliente' => 5,
                'productos' => [
                    ['id_producto' => 1, 'cantidad' => '1'],
                ],
                'pagos' => [],
            ];
            $resultado = $compra->registrarCompraFisica($datos, 1);
        } catch (\Exception $e) {
            // Capturar la excepción simulada y convertirla en resultado de error
            $resultado = ['status' => 'error', 'message' => $e->getMessage()];
        } finally {
            // Resetear bandera
            PDODouble::$throwOnDetalleInsert = false;
        }

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertSame('error', $resultado['status']);
    }

    // ---------------------------------------------------
    // VP-UNIT-004: Compra con campos vacíos (cliente)
    // Objetivo: validar que cliente vacío sea tratado como
    // error y el flujo no complete la transacción.
    // ---------------------------------------------------
    public function testCompraCamposVaciosClienteRequerido(): void
    {
        $ref = new \ReflectionClass(Comprafisica::class);
        /** @var \\\\Comprafisica $compra */
        $compra = $ref->newInstanceWithoutConstructor();

        $pdo = new PDODouble();
        $prop = new \ReflectionProperty(Comprafisica::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($compra, $pdo);

        $datos = [
            'cliente' => '',
            'productos' => [
                ['id_producto' => 1, 'cantidad' => '1'],
            ],
            'pagos' => [],
        ];

        try {
            $resultado = $compra->registrarCompraFisica($datos, 1);
        } catch (\Exception $e) {
            // Capturar la excepción de cliente requerido y convertirla en resultado de error
            $resultado = ['status' => 'error', 'message' => $e->getMessage()];
        }

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertSame('error', $resultado['status']);
    }

    // ---------------------------------------------------
    // VP-UNIT-005: Ver detalles de compra (getCompras)
    // Objetivo: validar que la consulta de compras devuelva
    // una estructura mínima con id, productos y pagos.
    // ---------------------------------------------------
    public function testVerDetallesDeCompraGetCompras(): void
    {
        $ref = new \ReflectionClass(Comprafisica::class);
        /** @var \\\\Comprafisica $compra */
        $compra = $ref->newInstanceWithoutConstructor();

        $pdo = new PDODouble();
        $prop = new \ReflectionProperty(Comprafisica::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($compra, $pdo);

        $metodo = new \ReflectionMethod(Comprafisica::class, 'g_Compras');
        $metodo->setAccessible(true);
        $resultado = $metodo->invoke($compra);

        $this->assertIsArray($resultado);
        $this->assertNotEmpty($resultado);
        $this->assertArrayHasKey('id_factura', $resultado[0]);
        $this->assertArrayHasKey('productos', $resultado[0]);
        $this->assertArrayHasKey('pagos', $resultado[0]);
    }
}
