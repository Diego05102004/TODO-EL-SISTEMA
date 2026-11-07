<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Catalogo;

/*
 * Pruebas unitarias del módulo de Catálogo.
 *
 * Se usan dobles de PDO/PDOStatement. Se cubren:
 * - insertarCombo
 * - obtenerProductos
 * - obtenerCombos
 * - eliminarCombo
 * - obtenerUltimoIdCombo
 * - crearNuevoCombo
 * - insertarProductoEnCombo
 */

// ======================================
// Dobles de prueba (PDOStatement)
// ======================================
class StatementDoubleCatalogo
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];
    private bool $throwOnExecute = false;

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
        $this->scalar = $data['scalar'] ?? null;
        $this->throwOnExecute = $data['throwOnExecute'] ?? false;
    }

    public function bindParam($name, &$value, $type = null)
    {
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        if ($this->throwOnExecute) {
            throw new Exception('Simulated execute failure');
        }
        // Validaciones mínimas para inserts del catálogo
        if (stripos($this->sql, 'INSERT INTO tbl_combo (id_producto, cantidad)') !== false) {
            $idProducto = $params[':id_producto'] ?? ($this->bound[':id_producto'] ?? null);
            $cantidad = $params[':cantidad'] ?? ($this->bound[':cantidad'] ?? null);
            if ($idProducto === null || $idProducto === '') {
                throw new Exception('id_producto requerido');
            }
            if ($cantidad === null || $cantidad === '' || (int)$cantidad <= 0) {
                throw new Exception('cantidad requerida');
            }
        }
        if (stripos($this->sql, 'INSERT INTO tbl_combo (id_combo, id_producto, cantidad)') !== false) {
            $idCombo = $params[':id_combo'] ?? ($this->bound[':id_combo'] ?? null);
            $idProducto = $params[':id_producto'] ?? ($this->bound[':id_producto'] ?? null);
            $cantidad = $params[':cantidad'] ?? ($this->bound[':cantidad'] ?? null);
            if ($idCombo === null || $idProducto === null || $cantidad === null) {
                throw new Exception('parametros requeridos');
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
class PDODoubleCatalogo extends PDO
{
    private int $lastId = 500;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function lastInsertId($name = null): string|false { return (string)$this->lastId; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // insertarCombo (id_producto, cantidad)
        if (stripos($sql, 'INSERT INTO tbl_combo (id_producto, cantidad)') !== false) {
            return new StatementDoubleCatalogo($sql);
        }
        // obtenerProductos join
        if (stripos($sql, 'FROM productos p') !== false && stripos($sql, 'INNER JOIN modelo') !== false) {
            return new StatementDoubleCatalogo($sql, [
                'rows' => [
                    [
                        'id_producto' => 1,
                        'nombre_producto' => 'Prod A',
                        'nombre_modelo' => 'M1',
                        'categoria' => 'Cat1',
                        'stock' => 10,
                        'precio' => 5.5,
                    ],
                ],
            ]);
        }
        // obtenerCombos con GROUP_CONCAT y SUM
        if (stripos($sql, 'GROUP_CONCAT(p.nombre_producto') !== false && stripos($sql, 'SUM(p.precio * c.cantidad)') !== false) {
            return new StatementDoubleCatalogo($sql, [
                'rows' => [
                    [
                        'id_combo' => 10,
                        'productos' => 'Prod A, Prod B',
                        'precio_total' => 30.0,
                    ],
                ],
            ]);
        }
        // eliminarCombo
        if (stripos($sql, 'DELETE FROM tbl_combo WHERE id_combo') !== false) {
            return new StatementDoubleCatalogo($sql);
        }
        // obtenerUltimoIdCombo
        if (stripos($sql, 'SELECT MAX(id_combo) AS ultimo_id FROM tbl_combo') !== false) {
            return new StatementDoubleCatalogo($sql, [
                'row' => ['ultimo_id' => 15],
            ]);
        }
        // crearNuevoCombo (INSERT INTO tbl_combo (fecha_creacion) ...)
        if (stripos($sql, 'INSERT INTO tbl_combo (fecha_creacion)') !== false) {
            $this->lastId = 777;
            return new StatementDoubleCatalogo($sql);
        }
        // insertarProductoEnCombo
        if (stripos($sql, 'INSERT INTO tbl_combo (id_combo, id_producto, cantidad)') !== false) {
            return new StatementDoubleCatalogo($sql);
        }
        return new StatementDoubleCatalogo($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class CatalogoTest extends TestCase
{
    // Helper: crea instancia de Catalogo con PDO stub
    private function nuevoCatalogoConPDOStub(): Catalogo
    {
        $ref = new ReflectionClass(Catalogo::class);
        /** @var Catalogo $c */
        $c = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleCatalogo();
        $prop = new ReflectionProperty(Catalogo::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($c, $pdo);
        return $c;
    }

    // CAT-UNIT-002: Obtener productos activos con join
    public function testObtenerProductos(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $rows = $c->obtenerProductos();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id_producto', $rows[0]);
        $this->assertArrayHasKey('nombre_producto', $rows[0]);
    }

    // CAT-UNIT-003: Obtener combos agregados
    public function testObtenerCombos(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $rows = $c->obtenerCombos();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id_combo', $rows[0]);
        $this->assertArrayHasKey('productos', $rows[0]);
        $this->assertArrayHasKey('precio_total', $rows[0]);
    }

    // CAT-UNIT-004: Eliminar combo
    public function testEliminarCombo(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $this->assertTrue($c->eliminarCombo(10));
    }

    // CAT-UNIT-005: Obtener último id de combo (MAX)
    public function testObtenerUltimoIdCombo(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $ultimo = $c->obtenerUltimoIdCombo();
        $this->assertNotNull($ultimo);
        $this->assertSame(15, $ultimo);
    }

    // CAT-UNIT-006: Crear nuevo combo (lastInsertId)
    public function testCrearNuevoCombo(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $id = $c->crearNuevoCombo();
        $this->assertNotFalse($id);
        $this->assertSame('777', (string)$id);
    }

    // CAT-UNIT-007: Insertar producto en combo
    public function testInsertarProductoEnCombo(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $this->assertTrue($c->insertarProductoEnCombo(10, 1, 3));
    }
}
