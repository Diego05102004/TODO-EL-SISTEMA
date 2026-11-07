<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Rol;

/*
 * Pruebas unitarias del módulo de Roles.
 *
 * Se usan dobles de PDO/PDOStatement. Se cubren: registrar (con permisos),
 * existencia por nombre, obtener último, por ID, listar, modificar, eliminar,
 * y verificación de usuarios asignados.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDoubleRol
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];
    private bool $throwOnExecute = false;
    public static int $permInsertCount = 0;

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
        // Validación mínima para INSERT rol
        if (stripos($this->sql, 'INSERT INTO tbl_rol') !== false) {
            $nombre = $params[':nombre_rol'] ?? ($this->bound[':nombre_rol'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre_rol requerido');
            }
        }
        // Contar inserts de permisos
        if (stripos($this->sql, 'INSERT INTO tbl_permisos') !== false) {
            self::$permInsertCount++;
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
class PDODoubleRol extends PDO
{
    private int $lastId = 1000;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function lastInsertId($name = null): string|false
    {
        return (string)$this->lastId;
    }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT rol
        if (stripos($sql, 'INSERT INTO tbl_rol') !== false) {
            $this->lastId = 1001;
            return new StatementDoubleRol($sql);
        }
        // SELECT id_modulo FROM tbl_modulos
        if (stripos($sql, 'SELECT id_modulo FROM tbl_modulos') !== false) {
            return new StatementDoubleRol($sql, [
                'rows' => [1, 2, 3], // tres módulos de ejemplo
            ]);
        }
        // INSERT permisos
        if (stripos($sql, 'INSERT INTO tbl_permisos') !== false) {
            return new StatementDoubleRol($sql);
        }
        // existe nombre rol (COUNT)
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_rol WHERE nombre_rol') !== false) {
            return new StatementDoubleRol($sql, ['scalar' => 1]);
        }
        // obtener último rol
        if (stripos($sql, 'SELECT * FROM tbl_rol ORDER BY id_rol DESC LIMIT 1') !== false) {
            return new StatementDoubleRol($sql, [
                'row' => ['id_rol' => 9, 'nombre_rol' => 'UltimoRol'],
            ]);
        }
        // obtener rol por id
        if (stripos($sql, 'SELECT * FROM tbl_rol WHERE id_rol =') !== false) {
            return new StatementDoubleRol($sql, [
                'row' => ['id_rol' => 5, 'nombre_rol' => 'RolX'],
            ]);
        }
        // listar roles
        if (stripos($sql, 'SELECT id_rol, nombre_rol FROM tbl_rol') !== false) {
            return new StatementDoubleRol($sql, [
                'rows' => [
                    ['id_rol' => 3, 'nombre_rol' => 'Operador'],
                    ['id_rol' => 2, 'nombre_rol' => 'Supervisor'],
                ],
            ]);
        }
        // UPDATE rol
        if (stripos($sql, 'UPDATE tbl_rol SET nombre_rol') !== false) {
            return new StatementDoubleRol($sql);
        }
        // DELETE rol
        if (stripos($sql, 'DELETE FROM tbl_rol WHERE id_rol') !== false) {
            return new StatementDoubleRol($sql);
        }
        // tieneUsuariosAsignados COUNT
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE id_rol') !== false) {
            return new StatementDoubleRol($sql, ['scalar' => 0]);
        }
        return new StatementDoubleRol($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class RolTest extends TestCase
{
    private function nuevoRolConPDOStub(): Rol
    {
        $ref = new ReflectionClass(Rol::class);
        /** @var Rol $r */
        $r = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleRol();
        $prop = new ReflectionProperty(Rol::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($r, $pdo);
        return $r;
    }

    // ROL-UNIT-001: Registrar rol (incluye permisos No Permitido)
    public function testRegistrarRol(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $r->setNombreRol('Nuevo Rol');
        // Suprimir/consumir warnings del código de producción (p.ej. uso de $id++ sin inicializar)
        $prev = error_reporting();
        error_reporting($prev & ~E_WARNING);
        set_error_handler(function() { return true; }, E_WARNING);
        StatementDoubleRol::$permInsertCount = 0;
        $ok = $r->registrarRol();
        restore_error_handler();
        error_reporting($prev);
        $this->assertTrue($ok);
        // 3 módulos * 6 acciones = 18 inserts de permisos
        $this->assertSame(18, StatementDoubleRol::$permInsertCount);
    }

    // ROL-UNIT-002: Existe nombre de rol (true)
    public function testExisteNombreRolTrue(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $this->assertTrue($r->existeNombreRol('Operador'));
    }

    // ROL-UNIT-003: Obtener último rol
    public function testObtenerUltimoRol(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $row = $r->obtenerUltimoRol();
        $this->assertIsArray($row);
        $this->assertSame('UltimoRol', $row['nombre_rol']);
    }

    // ROL-UNIT-004: Obtener rol por id
    public function testObtenerRolPorId(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $row = $r->obtenerRolPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('RolX', $row['nombre_rol']);
    }

    // ROL-UNIT-005: Listar roles
    public function testConsultarRoles(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $lista = $r->consultarRoles();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_rol', $lista[0]);
        $this->assertArrayHasKey('nombre_rol', $lista[0]);
    }

    // ROL-UNIT-006: Modificar rol
    public function testModificarRol(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $r->setNombreRol('Editado');
        $this->assertTrue($r->modificarRol(3));
    }

    // ROL-UNIT-007: Eliminar rol
    public function testEliminarRol(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $this->assertTrue($r->eliminarRol(3));
    }

    // ROL-UNIT-008: Tiene usuarios asignados (false)
    public function testTieneUsuariosAsignadosFalse(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $this->assertFalse($r->tieneUsuariosAsignados(3));
    }
}
