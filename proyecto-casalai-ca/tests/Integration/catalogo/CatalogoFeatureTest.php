<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Catalogo;
use Usuario\ProyectoCasalaiCa\Config\config;

/*
 * Pruebas de INTEGRACIÓN del módulo de Catálogo.
 *
 * Secciones:
 * - Dobles: `StatementDoubleCatalogoF` y `PDODoubleCatalogoF` (simulan PDO/Statement y lastInsertId).
 * - Escenarios: insertar combo, obtener productos, obtener combos, eliminar combo,
 *   obtener último id de combo, crear nuevo combo, insertar producto en combo.
 */

// ======================================
// Dobles de prueba (PDOStatement/Catalogo)
// ======================================
class StatementDoubleCatalogoF
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
        // Validaciones mínimas
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
class PDODoubleCatalogoF extends PDO
{
    private int $lastId = 900;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function lastInsertId($name = null): string|false { return (string)$this->lastId; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // insertarCombo (id_producto, cantidad)
        if (stripos($sql, 'INSERT INTO tbl_combo (id_producto, cantidad)') !== false) {
            return new StatementDoubleCatalogoF($sql);
        }
        // obtenerProductos join
        if (stripos($sql, 'FROM productos p') !== false && stripos($sql, 'INNER JOIN modelo') !== false) {
            return new StatementDoubleCatalogoF($sql, [
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
            return new StatementDoubleCatalogoF($sql, [
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
            return new StatementDoubleCatalogoF($sql);
        }
        // obtenerUltimoIdCombo
        if (stripos($sql, 'SELECT MAX(id_combo) AS ultimo_id FROM tbl_combo') !== false) {
            return new StatementDoubleCatalogoF($sql, [
                'row' => ['ultimo_id' => 15],
            ]);
        }
        // crearNuevoCombo
        if (stripos($sql, 'INSERT INTO tbl_combo (fecha_creacion)') !== false) {
            $this->lastId = 901;
            return new StatementDoubleCatalogoF($sql);
        }
        // insertarProductoEnCombo
        if (stripos($sql, 'INSERT INTO tbl_combo (id_combo, id_producto, cantidad)') !== false) {
            return new StatementDoubleCatalogoF($sql);
        }
        return new StatementDoubleCatalogoF($sql);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
/**
 * @group catalogo
 */
final class CatalogoFeatureTest extends TestCase
{
    // Helper: crea `catalogo` con PDO stub
    private function nuevoCatalogoConPDOStub(): Catalogo
    {
        $ref = new ReflectionClass(Catalogo::class);
        /** @var Catalogo $c */
        $c = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleCatalogoF();
        $prop = new ReflectionProperty(Catalogo::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($c, $pdo);
        return $c;
    }

    // CAT-FEAT-001: Insertar combo
    public function testInsertarComboIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $c->setIdProducto(1);
        $c->setCantidad(2);
        $this->assertTrue($c->insertarCombo());
    }

    // CAT-FEAT-002: Obtener productos
    public function testObtenerProductosIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $rows = $c->obtenerProductos();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('nombre_producto', $rows[0]);
    }

    // CAT-FEAT-003: Obtener combos
    public function testObtenerCombosIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $rows = $c->obtenerCombos();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('precio_total', $rows[0]);
    }

    // CAT-FEAT-004: Eliminar combo
    public function testEliminarComboIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $this->assertTrue($c->eliminarCombo(10));
    }

    // CAT-FEAT-005: Obtener último id de combo
    public function testObtenerUltimoIdComboIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $this->assertSame(15, $c->obtenerUltimoIdCombo());
    }

    // CAT-FEAT-006: Crear nuevo combo
    public function testCrearNuevoComboIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $id = $c->crearNuevoCombo();
        $this->assertNotFalse($id);
        $this->assertSame('901', (string)$id);
    }

    // CAT-FEAT-007: Insertar producto en combo
    public function testInsertarProductoEnComboIntegracion(): void
    {
        $c = $this->nuevoCatalogoConPDOStub();
        $this->assertTrue($c->insertarProductoEnCombo(10, 1, 3));
    }
}
