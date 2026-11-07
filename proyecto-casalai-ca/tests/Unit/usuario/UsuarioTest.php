<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuarios;

/*
 * Pruebas unitarias del módulo Usuarios.
 *
 * Secciones:
 * - Dobles: `StatementDoubleUsuario`, `PDODoubleUsuarioS` (seguridad) y `PDODoubleUsuarioP` (inventario).
 * - Escenarios: ingresar, modificar, existencias, último, por ID, eliminar,
 *   cambiar estatus, reporte roles, actualizar perfil, listar por estatus.
 */

// ======================================
// Dobles de prueba (PDOStatement/Usuario)
// ======================================
class StatementDoubleUsuario
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

    public function rowCount()
    {
        if (stripos($this->sql, "SHOW COLUMNS FROM tbl_usuarios LIKE 'estatus'") !== false) {
            return 1;
        }
        return count($this->rows);
    }
}

// ======================
// Doble de prueba (PDO - Seguridad)
// ======================
class PDODoubleUsuarioS extends PDO
{
    public bool $inTx = false;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function beginTransaction(): bool { $this->inTx = true; return true; }
    public function commit(): bool { $this->inTx = false; return true; }
    public function rollBack(): bool { $this->inTx = false; return true; }
    public function inTransaction(): bool { return $this->inTx; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT usuario
        if (stripos($sql, 'INSERT INTO tbl_usuarios (username, password, id_rol') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // UPDATE usuario
        if (stripos($sql, 'UPDATE tbl_usuarios SET') !== false && stripos($sql, 'WHERE id_usuario') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // SELECT COUNT(*) usuario/cedula/correo
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE username') !== false) {
            return new StatementDoubleUsuario($sql, ['scalar' => 1]);
        }
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE cedula') !== false) {
            return new StatementDoubleUsuario($sql, ['scalar' => 0]);
        }
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE correo') !== false) {
            return new StatementDoubleUsuario($sql, ['scalar' => 0]);
        }
        // obtenerUltimoUsuario join rol
        if (stripos($sql, 'FROM tbl_usuarios AS usuarios') !== false && stripos($sql, 'ORDER BY usuarios.id_usuario DESC LIMIT 1') !== false) {
            return new StatementDoubleUsuario($sql, [
                'row' => [
                    'id_usuario' => 50,
                    'username' => 'last',
                    'id_rol' => 2,
                    'nombre_rol' => 'Supervisor',
                ],
            ]);
        }
        // obtenerUsuarioPorId join rol
        if (stripos($sql, 'FROM tbl_usuarios AS usuarios') !== false && stripos($sql, 'WHERE usuarios.id_usuario =') !== false) {
            return new StatementDoubleUsuario($sql, [
                'row' => [
                    'id_usuario' => 5,
                    'username' => 'user5',
                    'nombre_rol' => 'Operador',
                ],
            ]);
        }
        // eliminarUsuario
        if (stripos($sql, 'DELETE FROM tbl_usuarios WHERE id_usuario') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // cambiarEstatus
        if (stripos($sql, 'UPDATE tbl_usuarios SET estatus') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // reporte roles
        if (stripos($sql, 'SELECT rol.nombre_rol, COUNT') !== false) {
            return new StatementDoubleUsuario($sql, [
                'rows' => [
                    ['nombre_rol' => 'Admin', 'cantidad' => 2],
                    ['nombre_rol' => 'Operador', 'cantidad' => 1],
                ],
            ]);
        }
        // actualizarPerfil
        if (stripos($sql, 'UPDATE tbl_usuarios SET') !== false && stripos($sql, 'WHERE id_usuario = :id_usuario') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // getusuarios por estatus
        if (stripos($sql, 'WHERE usuarios.estatus =') !== false && stripos($sql, 'ORDER BY usuarios.id_usuario DESC') !== false) {
            return new StatementDoubleUsuario($sql, [
                'rows' => [
                    ['id_usuario' => 1, 'username' => 'a', 'nombre_rol' => 'Admin', 'estatus' => 'habilitado'],
                ],
            ]);
        }
        // SHOW COLUMNS estatus
        if (stripos($sql, "SHOW COLUMNS FROM tbl_usuarios LIKE 'estatus'") !== false) {
            return new StatementDoubleUsuario($sql, ['rows' => [['Field' => 'estatus']]]);
        }

        return new StatementDoubleUsuario($sql);
    }
}

// ======================
// Doble de prueba (PDO - Inventario)
// ======================
class PDODoubleUsuarioP extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT cliente si no existe
        if (stripos($sql, 'INSERT INTO tbl_clientes (nombre, cedula, telefono, direccion, correo, activo)') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // UPDATE cliente en modificarUsuario
        if (stripos($sql, 'UPDATE tbl_clientes SET') !== false) {
            return new StatementDoubleUsuario($sql);
        }
        // SELECT COUNT(*) clienteExiste
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_clientes WHERE cedula') !== false) {
            return new StatementDoubleUsuario($sql, ['scalar' => 0]);
        }
        return new StatementDoubleUsuario($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class UsuarioTest extends TestCase
{
    // Helper: crea `Usuarios` con dos conexiones stub (seguridad/inventario)
    private function nuevoUsuarioConPDOStub(): Usuarios
    {
        $ref = new ReflectionClass(Usuarios::class);
        /** @var Usuarios $u */
        $u = $ref->newInstanceWithoutConstructor();
        $sec = new PDODoubleUsuarioS();
        $inv = new PDODoubleUsuarioP();
        $propSec = new ReflectionProperty(Usuarios::class, 'conex');
        $propSec->setAccessible(true);
        $propSec->setValue($u, $sec);
        $propInv = new ReflectionProperty(Usuarios::class, 'con');
        $propInv->setAccessible(true);
        $propInv->setValue($u, $inv);
        return $u;
    }

    public function testIngresarUsuario(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $u->setUsername('nuevo');
        $u->setClave('clave');
        $u->setRango(2);
        $u->setCorreo('n@test.com');
        $u->setNombre('Ana');
        $u->setApellido('Lopez');
        $u->setTelefono('0414');
        $u->setCedula('V-100');
        $this->assertTrue($u->ingresarUsuario());
    }

    public function testModificarUsuario(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $u->setUsername('edit');
        $u->setRango(3);
        $u->setNombre('Eva');
        $u->setApellido('Perez');
        $u->setCorreo('e@test.com');
        $u->setTelefono('0412');
        $u->setCedula('V-101');
        $this->assertTrue($u->modificarUsuario(5));
    }

    public function testExistencias(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $this->assertTrue($u->existeUsuario('a'));
        $this->assertFalse($u->existeCedula('V-1'));
        $this->assertFalse($u->existeCorreo('a@b.com'));
    }

    public function testObtenerUltimoUsuario(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $row = $u->obtenerUltimoUsuario();
        $this->assertIsArray($row);
        $this->assertSame('last', $row['username']);
    }

    public function testObtenerUsuarioPorId(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $row = $u->obtenerUsuarioPorId(5);
        $this->assertIsArray($row);
        $this->assertSame(5, $row['id_usuario']);
    }

    public function testEliminarUsuario(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $this->assertTrue($u->eliminarUsuario(7));
    }

    public function testCambiarEstatus(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $ref = new ReflectionProperty(Usuarios::class, 'id_usuario');
        $ref->setAccessible(true);
        $ref->setValue($u, 3);
        $this->assertTrue($u->cambiarEstatus('habilitado'));
    }

    public function testObtenerReporteRoles(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $rows = $u->obtenerReporteRoles();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('nombre_rol', $rows[0]);
    }

    public function testGetUsuariosPorEstatus(): void
    {
        $u = $this->nuevoUsuarioConPDOStub();
        $rows = $u->getusuarios('habilitado');
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertSame('habilitado', $rows[0]['estatus']);
    }
}
