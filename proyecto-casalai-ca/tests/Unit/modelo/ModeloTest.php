<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\modelo;

/*
 * Pruebas unitarias del módulo de Modelos.
 *
 * Se usan dobles de prueba para PDO/PDOStatement, evitando acceso a BD.
 * Escenarios: registrar, existe por nombre, obtener último, obtener por ID,
 * listar marcas (para combo), modificar, eliminar (con/sin productos asociados),
 * obtener modelo con marca por ID, listar modelos con marca.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDoubleModelo
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
        // Validaciones mínimas para INSERT/UPDATE de modelos
        if (stripos($this->sql, 'INSERT INTO tbl_modelos') !== false || stripos($this->sql, 'UPDATE tbl_modelos') !== false) {
            $nombre = $params[':nombre_modelo'] ?? $params[0] ?? ($this->bound[':nombre_modelo'] ?? null);
            $marca  = $params[':id_marca'] ?? $params[1] ?? ($this->bound[':id_marca'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre_modelo requerido');
            }
            if ($marca === null || $marca === '') {
                throw new Exception('id_marca requerido');
            }
        }
        return true;
    }

    public function fetch($mode = null)
    {
        // Caso especial: COUNT(*) as total (tieneProductosAsociados)
        if ($this->row !== null) {
            return $this->row;
        }
        if ($this->scalar !== null) {
            return $this->scalar;
        }
        return null;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }

    public function fetchColumn($columnNumber = 0)
    {
        return $this->scalar;
    }
}

// ======================
// Doble de prueba (PDO)
// ======================
class PDODoubleModelo extends PDO
{
    public static bool $productosAsociados = true; // controla COUNT en productos asociados

    public function __construct() {}

    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);

        // existeNombreModelo: SELECT COUNT(*) FROM tbl_modelos WHERE nombre_modelo ...
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_modelos WHERE nombre_modelo') !== false) {
            return new StatementDoubleModelo($sql, [
                'scalar' => 1, // simular que existe
            ]);
        }

        // INSERT modelo
        if (stripos($sql, 'INSERT INTO tbl_modelos') !== false) {
            return new StatementDoubleModelo($sql);
        }

        // obtenerUltimoModelo: SELECT ... JOIN ... ORDER BY ... LIMIT 1
        if (stripos($sql, 'FROM tbl_modelos m') !== false && stripos($sql, 'ORDER BY m.id_modelo DESC LIMIT 1') !== false) {
            return new StatementDoubleModelo($sql, [
                'row' => [
                    'id_modelo' => 50,
                    'nombre_modelo' => 'UltimoModelo',
                    'id_marca' => 10,
                    'nombre_marca' => 'MarcaY',
                ],
            ]);
        }

        // obtenerModeloPorId: SELECT * FROM tbl_modelos WHERE id_modelo = ?
        if (stripos($sql, 'SELECT * FROM tbl_modelos WHERE id_modelo') !== false) {
            return new StatementDoubleModelo($sql, [
                'row' => [
                    'id_modelo' => 7,
                    'nombre_modelo' => 'ModeloPorId',
                    'id_marca' => 3,
                ],
            ]);
        }

        // UPDATE modelo
        if (stripos($sql, 'UPDATE tbl_modelos SET nombre_modelo') !== false) {
            return new StatementDoubleModelo($sql);
        }

        // tieneProductosAsociados: COUNT(*) as total FROM tbl_productos WHERE id_modelo = :id_modelo
        if (stripos($sql, 'SELECT COUNT(*) as total FROM tbl_productos WHERE id_modelo') !== false) {
            $total = self::$productosAsociados ? 2 : 0;
            return new StatementDoubleModelo($sql, [
                'row' => ['total' => $total],
            ]);
        }

        // listar productos asociados (top 5)
        if (stripos($sql, 'FROM tbl_productos') !== false && stripos($sql, 'ORDER BY nombre_producto') !== false) {
            return new StatementDoubleModelo($sql, [
                'rows' => [
                    ['nombre_producto' => 'Prod1', 'codigo_producto' => 'P001'],
                    ['nombre_producto' => 'Prod2', 'codigo_producto' => 'P002'],
                ],
            ]);
        }

        // DELETE modelo
        if (stripos($sql, 'DELETE FROM tbl_modelos WHERE id_modelo') !== false) {
            return new StatementDoubleModelo($sql);
        }

        // obtenerModeloConMarcaPorId: JOIN por id
        if (stripos($sql, 'FROM tbl_modelos m') !== false && stripos($sql, 'WHERE m.id_modelo =') !== false) {
            return new StatementDoubleModelo($sql, [
                'row' => [
                    'id_modelo' => 7,
                    'nombre_modelo' => 'ModeloJoin',
                    'id_marca' => 3,
                    'nombre_marca' => 'MarcaJoin',
                ],
            ]);
        }

        // getModelos listado con JOIN
        if (stripos($sql, 'FROM tbl_modelos AS mo') !== false && stripos($sql, 'INNER JOIN tbl_marcas AS ma') !== false) {
            return new StatementDoubleModelo($sql, [
                'rows' => [
                    ['id_modelo' => 3, 'id_marca' => 2, 'nombre_modelo' => 'M1', 'nombre_marca' => 'MarcaA'],
                    ['id_modelo' => 2, 'id_marca' => 1, 'nombre_modelo' => 'M0', 'nombre_marca' => 'MarcaB'],
                ],
            ]);
        }

        // getmarcas (combo) usa ->query() pero por si acaso
        if (stripos($sql, 'SELECT id_marca, nombre_marca FROM tbl_marcas') !== false) {
            return new StatementDoubleModelo($sql, [
                'rows' => [
                    ['id_marca' => 1, 'nombre_marca' => 'MarcaA'],
                    ['id_marca' => 2, 'nombre_marca' => 'MarcaB'],
                ],
            ]);
        }

        return new StatementDoubleModelo($sql);
    }

    public function query($query, $fetchMode = null, ...$fetchModeArgs)
    {
        // Soporte explícito a getmarcas() que usa query directamente
        if (stripos($query, 'SELECT id_marca, nombre_marca FROM tbl_marcas') !== false) {
            return new StatementDoubleModelo($query, [
                'rows' => [
                    ['id_marca' => 1, 'nombre_marca' => 'MarcaA'],
                    ['id_marca' => 2, 'nombre_marca' => 'MarcaB'],
                ],
            ]);
        }
        return new StatementDoubleModelo($query);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class ModeloTest extends TestCase
{
    private function nuevoModeloConPDOStub(): modelo
    {
        $ref = new ReflectionClass(modelo::class);
        /** @var modelo $m */
        $m = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleModelo();
        $prop = new ReflectionProperty(modelo::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($m, $pdo);
        return $m;
    }

    // MOD-UNIT-001: Registrar modelo (happy path)
    public function testRegistrarModeloHappyPath(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $m->setnombre_modelo('NuevoModelo');
        $m->setid_marca(10);
        $res = $m->registrarModelo();
        $this->assertTrue($res);
    }

    // MOD-UNIT-002: Existe nombre modelo (true)
    public function testExisteNombreModeloTrue(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $exists = $m->existeNombreModelo('Existente');
        $this->assertTrue($exists);
    }

    // MOD-UNIT-003: Obtener último modelo
    public function testObtenerUltimoModelo(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $row = $m->obtenerUltimoModelo();
        $this->assertIsArray($row);
        $this->assertSame('UltimoModelo', $row['nombre_modelo']);
        $this->assertSame('MarcaY', $row['nombre_marca']);
    }

    // MOD-UNIT-004: Obtener modelo por id
    public function testObtenerModeloPorId(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $row = $m->obtenerModeloPorId(7);
        $this->assertIsArray($row);
        $this->assertSame('ModeloPorId', $row['nombre_modelo']);
    }

    // MOD-UNIT-005: Listar marcas (combo)
    public function testGetMarcasCombo(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $lista = $m->getmarcas();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_marca', $lista[0]);
        $this->assertArrayHasKey('nombre_marca', $lista[0]);
    }

    // MOD-UNIT-006: Modificar modelo
    public function testModificarModelo(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $m->setnombre_modelo('Editado');
        $m->setid_marca(9);
        $res = $m->modificarModelo(7);
        $this->assertTrue($res);
    }

    // MOD-UNIT-007: Eliminar modelo con productos asociados (error)
    public function testEliminarModeloConProductosAsociados(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        PDODoubleModelo::$productosAsociados = true;
        $res = $m->eliminarModelo(7);
        $this->assertIsArray($res);
        $this->assertSame('error', $res['status']);
        $this->assertArrayHasKey('productos', $res);
        $this->assertArrayHasKey('total_productos', $res);
    }

    // MOD-UNIT-008: Eliminar modelo sin productos asociados (success)
    public function testEliminarModeloSinProductosAsociados(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        PDODoubleModelo::$productosAsociados = false;
        $res = $m->eliminarModelo(7);
        $this->assertIsArray($res);
        $this->assertSame('success', $res['status']);
    }

    // MOD-UNIT-009: Listar modelos (JOIN)
    public function testGetModelosListado(): void
    {
        $m = $this->nuevoModeloConPDOStub();
        $lista = $m->getModelos();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_modelo', $lista[0]);
        $this->assertArrayHasKey('nombre_marca', $lista[0]);
    }
}
