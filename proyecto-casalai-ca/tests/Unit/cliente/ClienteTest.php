<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\cliente;

/*
 * Pruebas unitarias del módulo de Clientes.
 *
 * Se usan dobles de PDO/PDOStatement. Para `listarTodosClientes()` se crea un
 * doble de la clase que sobreescribe `getConexion()` para devolver el stub.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDoubleCliente
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
        if (stripos($this->sql, 'INSERT INTO tbl_clientes') !== false) {
            $nombre = $params[':nombre'] ?? ($this->bound[':nombre'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre requerido');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_clientes') !== false) {
            // No exigir nombre cuando es eliminación lógica (activo=0)
            if (stripos($this->sql, 'SET activo = 0') === false) {
                $nombre = $params[':nombre'] ?? ($this->bound[':nombre'] ?? null);
                if ($nombre === null || $nombre === '') {
                    throw new Exception('nombre requerido');
                }
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

    public function fetchColumn($columnNumber = 0)
    {
        return $this->scalar;
    }
}

// ======================
// Doble de prueba (PDO)
// ======================
class PDODoubleCliente extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // existeNumeroCedula: COUNT(*)
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_clientes WHERE cedula') !== false) {
            return new StatementDoubleCliente($sql, ['scalar' => 1]);
        }
        // INSERT
        if (stripos($sql, 'INSERT INTO tbl_clientes') !== false) {
            return new StatementDoubleCliente($sql);
        }
        // Ultimo cliente
        if (stripos($sql, 'SELECT * FROM tbl_clientes ORDER BY id_clientes DESC LIMIT 1') !== false) {
            return new StatementDoubleCliente($sql, [
                'row' => [
                    'id_clientes' => 77,
                    'nombre' => 'UltimoCliente',
                    'cedula' => 'V-111',
                ],
            ]);
        }
        // obtenerclientesPorId
        if (stripos($sql, 'SELECT * FROM tbl_clientes WHERE id_clientes =') !== false) {
            return new StatementDoubleCliente($sql, [
                'row' => [
                    'id_clientes' => 5,
                    'nombre' => 'ClienteX',
                    'cedula' => 'V-123',
                ],
            ]);
        }
        // UPDATE (modificarclientes)
        if (stripos($sql, 'UPDATE tbl_clientes SET nombre =') !== false) {
            return new StatementDoubleCliente($sql);
        }
        // UPDATE lógico eliminar_l
        if (stripos($sql, 'UPDATE tbl_clientes SET activo = 0 WHERE id_clientes') !== false) {
            return new StatementDoubleCliente($sql);
        }
        // DELETE eliminarclientes
        if (stripos($sql, 'DELETE FROM tbl_clientes WHERE id_clientes') !== false) {
            return new StatementDoubleCliente($sql);
        }
        // getclientes listado
        if (stripos($sql, 'SELECT * FROM tbl_clientes') !== false) {
            return new StatementDoubleCliente($sql, [
                'rows' => [
                    ['id_clientes' => 2, 'nombre' => 'Ana', 'cedula' => 'V-2'],
                    ['id_clientes' => 1, 'nombre' => 'Ben', 'cedula' => 'V-1'],
                ],
            ]);
        }
        // listarTodosClientes (activo=1)
        if (stripos($sql, 'SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1') !== false) {
            return new StatementDoubleCliente($sql, [
                'rows' => [
                    ['id_clientes' => 3, 'nombre' => 'Carlos', 'cedula' => 'V-3'],
                ],
            ]);
        }
        return new StatementDoubleCliente($sql);
    }
}

// Doble de clase para poder inyectar stub en listarTodosClientes()
class ClienteDouble extends cliente
{
    public function __construct() { /* evitar BD real */ }
    public function getConexion() { return new PDODoubleCliente(); }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class ClienteTest extends TestCase
{
    private function nuevoClienteConPDOStub(): cliente
    {
        $ref = new ReflectionClass(cliente::class);
        /** @var cliente $c */
        $c = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleCliente();
        $prop = new ReflectionProperty(cliente::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($c, $pdo);
        return $c;
    }

    // CLI-UNIT-001: Registrar cliente
    public function testRegistrarClienteHappyPath(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $c->setnombre('Juan');
        $c->setcedula('V-100');
        $c->setdireccion('Dir');
        $c->settelefono('0414');
        $c->setcorreo('j@test.com');
        $this->assertTrue($c->ingresarclientes());
    }

    // CLI-UNIT-002: Existe número de cédula (true)
    public function testExisteNumeroCedulaTrue(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $this->assertTrue($c->existeNumeroCedula('V-100'));
    }

    // CLI-UNIT-003: Obtener último cliente
    public function testObtenerUltimoCliente(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $row = $c->obtenerUltimoCliente();
        $this->assertIsArray($row);
        $this->assertSame('UltimoCliente', $row['nombre']);
    }

    // CLI-UNIT-004: Obtener cliente por ID
    public function testObtenerClientePorId(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $row = $c->obtenerclientesPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('ClienteX', $row['nombre']);
    }

    // CLI-UNIT-005: Modificar cliente
    public function testModificarCliente(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $c->setnombre('Edit');
        $c->setcedula('V-9');
        $c->setdireccion('D');
        $c->settelefono('T');
        $c->setcorreo('e@test.com');
        $refActivo = new ReflectionProperty(cliente::class, 'activo');
        $refActivo->setAccessible(true);
        $refActivo->setValue($c, 1);
        $this->assertTrue($c->modificarclientes(5));
    }

    // CLI-UNIT-006: Eliminar lógico
    public function testEliminarLogico(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $this->assertTrue($c->eliminar_l(5));
    }

    // CLI-UNIT-007: Eliminar cliente
    public function testEliminarCliente(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $this->assertTrue($c->eliminarclientes(5));
    }

    // CLI-UNIT-008: Listar clientes (g_clientes)
    public function testGetClientesListado(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $lista = $c->getclientes();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_clientes', $lista[0]);
        $this->assertArrayHasKey('nombre', $lista[0]);
    }

    // CLI-UNIT-009: listarTodosClientes() usando ClienteDouble
    public function testListarTodosClientes(): void
    {
        $c = new ClienteDouble();
        $lista = $c->listarTodosClientes();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_clientes', $lista[0]);
    }
}
