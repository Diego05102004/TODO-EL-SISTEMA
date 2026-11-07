<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;

/*
 * Pruebas unitarias del módulo Orden de Despacho.
 *
 * Secciones:
 * - Dobles: `StatementDoubleOD` y `PDODoubleOD` (simulan PDO/Statement y respuestas de consultas/updates).
 * - Escenarios: facturas disponibles, obtener por id, listar ordenes con productos, descargar orden,
 *   detalles de compra, cambiar estado, anular orden y cambiar estatus (usuarios).
 */

// ======================================
// Dobles de prueba (PDOStatement/OD)
// ======================================
class StatementDoubleOD
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

    public function execute(array $params = [])
    {
        // Validaciones mínimas para updates
        if (stripos($this->sql, 'UPDATE tbl_orden_despachos SET estado =') !== false) {
            $estado = $params[':estado'] ?? ($this->bound[':estado'] ?? null);
            $id = $params[':id'] ?? ($this->bound[':id'] ?? null);
            if ($estado === null || $id === null) {
                throw new Exception('estado/id requeridos');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_orden_despachos SET activo = 0') !== false) {
            $id = $params[':id'] ?? ($this->bound[':id'] ?? null);
            if ($id === null) {
                throw new Exception('id requerido');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_usuarios SET estatus =') !== false) {
            $estatus = $params[':estatus'] ?? ($this->bound[':estatus'] ?? null);
            $id = $params[':id'] ?? ($this->bound[':id'] ?? null);
            if ($estatus === null || $id === null) {
                throw new Exception('estatus/id requeridos');
            }
        }
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
// Doble de prueba (PDO)
// ======================
class PDODoubleOD extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // Facturas disponibles
        if (stripos($sql, 'FROM tbl_facturas f') !== false && stripos($sql, 'WHERE f.estatus =') !== false) {
            return new StatementDoubleOD($sql, [
                'rows' => [
                    ['id_factura' => 101, 'fecha' => '2025-10-20', 'nombre' => 'Cliente A'],
                    ['id_factura' => 102, 'fecha' => '2025-10-21', 'nombre' => 'Cliente B'],
                ],
            ]);
        }
        // Obtener orden por id
        if (stripos($sql, 'SELECT * FROM tbl_orden_despachos WHERE id_orden_despachos') !== false) {
            return new StatementDoubleOD($sql, [
                'row' => [
                    'id_orden_despachos' => 5,
                    'id_factura' => 200,
                    'cliente' => 'Juan',
                    'fecha_despacho' => '2025-10-22',
                    'estado' => 'Pendiente',
                    'activo' => 1,
                ],
            ]);
        }
        // Listado getordendespacho()
        if (stripos($sql, 'FROM tbl_orden_despachos AS od') !== false && stripos($sql, 'ORDER BY od.fecha_despacho') !== false) {
            return new StatementDoubleOD($sql, [
                'rows' => [
                    ['id_orden_despachos' => 9, 'id_factura' => 300, 'cedula' => 'V-1', 'cliente' => 'Carlos', 'fecha_despacho' => '2025-10-22', 'estado' => 'Pendiente', 'activo' => 1],
                ],
            ]);
        }
        // Productos por factura en getordendespacho() y DescargarOrdenDespacho()
        if (stripos($sql, 'FROM tbl_factura_detalle AS d') !== false && stripos($sql, 'WHERE d.factura_id =') !== false) {
            return new StatementDoubleOD($sql, [
                'rows' => [
                    ['imagen' => null, 'codigo' => 1, 'producto' => 'ProdX', 'modelo' => 'M1', 'marca' => 'Marca', 'serial' => 'S1', 'cantidad' => 2, 'id_detalle' => 11, 'precio_unitario' => 5.0, 'subtotal' => 10.0],
                ],
            ]);
        }
        // DescargarOrdenDespacho (mismo query base pero filtrando por id)
        if (stripos($sql, 'FROM tbl_orden_despachos AS od') !== false && stripos($sql, 'WHERE od.id_orden_despachos =') !== false) {
            return new StatementDoubleOD($sql, [
                'rows' => [
                    ['id_orden_despachos' => 9, 'id_factura' => 300, 'cedula' => 'V-1', 'cliente' => 'Carlos', 'fecha_despacho' => '2025-10-22', 'estado' => 'Pendiente', 'activo' => 1],
                ],
            ]);
        }
        // Detalles compra (tbl_despacho_detalle)
        if (stripos($sql, 'FROM tbl_despacho_detalle d') !== false) {
            return new StatementDoubleOD($sql, [
                'rows' => [
                    ['nombre_producto' => 'ProdY', 'cantidad' => 1, 'precio' => 3.0, 'subtotal' => 3.0],
                ],
            ]);
        }
        // Cambiar estado orden
        if (stripos($sql, 'UPDATE tbl_orden_despachos SET estado =') !== false) {
            return new StatementDoubleOD($sql);
        }
        // Anular orden (activo=0)
        if (stripos($sql, 'UPDATE tbl_orden_despachos SET activo = 0') !== false) {
            return new StatementDoubleOD($sql);
        }
        // Cambiar estatus (usuarios)
        if (stripos($sql, 'UPDATE tbl_usuarios SET estatus =') !== false) {
            return new StatementDoubleOD($sql);
        }

        return new StatementDoubleOD($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class OrdenDespachoTest extends TestCase
{
    // Helper: crea `OrdenDespacho` con PDO stub
    private function nuevaODConPDOStub(): OrdenDespacho
    {
        $ref = new ReflectionClass(OrdenDespacho::class);
        /** @var OrdenDespacho $o */
        $o = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleOD();
        $prop = new ReflectionProperty(OrdenDespacho::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($o, $pdo);
        return $o;
    }

    // OD-UNIT-001: Obtener facturas disponibles
    public function testObtenerFacturasDisponibles(): void
    {
        $o = $this->nuevaODConPDOStub();
        $rows = $o->obtenerFacturasDisponibles();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id_factura', $rows[0]);
        $this->assertArrayHasKey('nombre', $rows[0]);
    }

    // OD-UNIT-002: Obtener orden por ID
    public function testObtenerOrdenPorId(): void
    {
        $o = $this->nuevaODConPDOStub();
        $row = $o->obtenerOrdenPorId(5);
        $this->assertIsArray($row);
        $this->assertSame(5, $row['id_orden_despachos']);
    }

    // OD-UNIT-003: Listar ordenes con productos
    public function testGetOrdenDespachoListado(): void
    {
        $o = $this->nuevaODConPDOStub();
        $list = $o->getordendespacho();
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
        $this->assertArrayHasKey('productos', $list[0]);
        $this->assertNotEmpty($list[0]['productos']);
    }

    // OD-UNIT-004: Descargar orden (por id)
    public function testDescargarOrdenDespacho(): void
    {
        $o = $this->nuevaODConPDOStub();
        $rows = $o->DescargarOrdenDespacho(9);
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('productos', $rows[0]);
    }

    // OD-UNIT-005: Detalles de compra por id despacho
    public function testGetDetallesCompra(): void
    {
        $o = $this->nuevaODConPDOStub();
        $det = $o->getDetallesCompra(1);
        $this->assertIsArray($det);
        $this->assertArrayHasKey('productos', $det);
        $this->assertNotEmpty($det['productos']);
    }

    // OD-UNIT-006: Cambiar estado de orden
    public function testCambiarEstadoOrden(): void
    {
        $o = $this->nuevaODConPDOStub();
        $this->assertTrue($o->cambiarEstadoOrden(9, 'Despachado'));
    }

    // OD-UNIT-007: Anular orden de despacho
    public function testAnularOrdenDespacho(): void
    {
        $o = $this->nuevaODConPDOStub();
        $res = $o->anularOrdenDespacho(9);
        $this->assertIsArray($res);
        $this->assertSame('success', $res['status']);
    }

}
