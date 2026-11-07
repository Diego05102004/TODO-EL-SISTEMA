<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\marca;

/*
 * Pruebas unitarias del módulo de Marcas.
 *
 * Se emplean dobles de prueba para PDO a fin de no tocar la BD real.
 * Los escenarios cubren registrar, listar, actualizar, eliminar,
 * verificar existencia por nombre, obtener última marca y asociación de modelos.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDoubleMarca
{
    private string $sql;
    private array $rows = [];
    private mixed $scalar = 0;
    private ?array $row = null;
    private bool $throwOnExecute = false;
    private array $bound = [];
    private array $testData = [];
    
    public function setTestData(array $data): void
    {
        $this->testData = $data;
        if (isset($data['scalar'])) {
            $this->scalar = $data['scalar'];
        }
        if (isset($data['row'])) {
            $this->row = $data['row'];
        }
        if (isset($data['rows'])) {
            $this->rows = $data['rows'];
        }
    }

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
        // Guardar el valor enlazado para usarlo en execute()
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        if ($this->throwOnExecute) {
            throw new Exception('Simulated execute failure');
        }
        // Validación básica al insertar/actualizar
        if (stripos($this->sql, 'INSERT INTO tbl_marcas') !== false || stripos($this->sql, 'UPDATE tbl_marcas') !== false) {
            // nombre_marca es requerido; tomar de execute(params) o de bindParam previo
            $nombre = $params[':nombre_marca'] ?? $params[0] ?? ($this->bound[':nombre_marca'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre_marca requerido');
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

    public function fetchColumn($columnNumber = 0)
    {
        return $this->scalar;
    }
}

// ======================
// Doble de prueba (PDO)
// ======================
class PDODoubleMarca extends PDO
{
    private int $lastId = 1;
    public $lastSql = '';

    public function __construct() {}

    public function setAttribute($attribute, $value)
    {
        return true;
    }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        
        // Store the SQL for later use in execute()
        $this->lastSql = $sql;

        // existeNomMarca: SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca = ? [AND id_marca != ?]
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca') !== false) {
            // For testing, we'll return 1 (exists) for 'Duplicada' and 0 for others
            $stmt = new StatementDoubleMarca($sql);
            $stmt->setTestData([
                'scalar' => (stripos($sql, 'Duplicada') !== false) ? 1 : 0,
            ]);
            return $stmt;
        }

        // INSERT marca
        if (stripos($sql, 'INSERT INTO tbl_marcas') !== false) {
            $this->lastId++;
            return new StatementDoubleMarca($sql);
        }

        // SELECT * ORDER BY id_marca DESC LIMIT 1 (obtUltimaMarca)
        if (stripos($sql, 'SELECT * FROM tbl_marcas ORDER BY id_marca DESC LIMIT 1') !== false) {
            return new StatementDoubleMarca($sql, [
                'row' => [
                    'id_marca' => 10,
                    'nombre_marca' => 'NuevaMarca',
                ],
            ]);
        }

        // tieneModelosAsociados: SELECT COUNT(*) FROM tbl_modelos WHERE id_marca = :id_marca
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_modelos WHERE id_marca') !== false) {
            // For testing, we'll return 1 (has models) for id_marca = 99, 0 otherwise
            $stmt = new StatementDoubleMarca($sql);
            $stmt->setTestData([
                'scalar' => (stripos($sql, '99') !== false) ? 1 : 0,
            ]);
            return $stmt;
        }

        // obtenermarcasPorId
        if (stripos($sql, 'SELECT nombre_marca FROM tbl_marcas WHERE id_marca = ?') !== false) {
            return new StatementDoubleMarca($sql, [
                'row' => ['nombre_marca' => 'Canon'],
            ]);
        }

        // obtenermarcasPorId
        if (stripos($sql, 'SELECT nombre_marca FROM tbl_marcas WHERE id_marca = :id_marca') !== false) {
            return new StatementDoubleMarca($sql, [
                'row' => ['nombre_marca' => 'Canon'],
            ]);
        }

        // UPDATE marca
        if (stripos($sql, 'UPDATE tbl_marcas SET nombre_marca =') !== false) {
            return new StatementDoubleMarca($sql);
        }

        // DELETE marca
        if (stripos($sql, 'DELETE FROM tbl_marcas WHERE id_marca') !== false) {
            return new StatementDoubleMarca($sql);
        }

        // getmarcas listado
        if (stripos($sql, 'SELECT id_marca, nombre_marca FROM tbl_marcas') !== false) {
            return new StatementDoubleMarca($sql, [
                'rows' => [
                    ['id_marca' => 2, 'nombre_marca' => 'MarcaA'],
                    ['id_marca' => 1, 'nombre_marca' => 'MarcaB'],
                ],
            ]);
        }

        return new StatementDoubleMarca($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class MarcaTest extends TestCase
{
    private function nuevaMarcaConPDOStub(): marca
    {
        $ref = new ReflectionClass(marca::class);
        /** @var marca $m */
        $m = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleMarca();
        
        // Establecer directamente la propiedad conex para que no cree una nueva BD
        $reflection = new ReflectionClass($m);
        $prop = $reflection->getProperty('conex');
        $prop->setAccessible(true);
        $prop->setValue($m, $pdo);
        
        return $m;
    }

    // MRK-UNIT-001: Registrar marca (happy path)
    public function testRegistrarMarcaHappyPath(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('NuevaMarca');
        $res = $m->registrarMarca();
        $this->assertTrue($res);
    }

    // MRK-UNIT-002: Test existeNombreMarca method
    public function testExisteNombreMarcaTrue(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $this->assertIsBool($m->existeNombreMarca('TestBrand'));
    }

    // MRK-UNIT-003: Obtener última marca
    public function testObtenerUltimaMarca(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        
        // Test obtenerUltimaMarca
        $row = $m->obtenerUltimaMarca();
        $this->assertIsArray($row);
        $this->assertSame('NuevaMarca', $row['nombre_marca']);
    }

    // MRK-UNIT-004: Modificar marca
    public function testModificarMarca(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('MarcaEditada');
        $res = $m->modificarmarcas(5);
        $this->assertTrue($res);
    }

    // MRK-UNIT-005: Eliminar marca
    public function testEliminarMarca(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $res = $m->eliminarmarcas(5);
        $this->assertTrue($res);
    }

    // MRK-UNIT-006: Listar marcas
    public function testGetMarcasListado(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $lista = $m->getmarcas();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_marca', $lista[0]);
        $this->assertArrayHasKey('nombre_marca', $lista[0]);
    }

    // MRK-UNIT-007: obtener marca por id
    public function testObtenerMarcaPorId(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('Canon');
        // Mock the database connection used in obtenermarcasPorId
        $reflection = new ReflectionClass($m);
        $method = $reflection->getMethod('obtenermarcasPorId');
        $method->setAccessible(true);
        
        $row = $method->invoke($m, 3);
        $this->assertIsArray($row);
        $this->assertSame('Canon', $row['nombre_marca']);
    }

    // MRK-UNIT-008: Test tieneModelosAsociados method
    public function testTieneModelosAsociados(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $this->assertIsBool($m->tieneModelosAsociados(1));
    }
}
