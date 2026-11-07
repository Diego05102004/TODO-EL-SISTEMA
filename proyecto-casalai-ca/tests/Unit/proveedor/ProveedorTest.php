<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Proveedores;

/*
 * Pruebas unitarias del módulo de Proveedores.
 *
 * Se usan dobles de prueba de PDO para evitar tocar la BD.
 * Escenarios: registrar, existe nombre, obtener último, por ID, modificar,
 * eliminar, listar, reportes (suministro, ranking, comparación precios, dependencia),
 * cambiar estatus.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDoubleProveedor
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
        // Validaciones mínimas dependiendo de la operación
        if (stripos($this->sql, 'INSERT INTO tbl_proveedores') !== false) {
            $nombre = $params[':nombre'] ?? ($this->bound[':nombre'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre requerido');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_proveedores') !== false) {
            // Si es cambio de estatus, no exigir nombre
            if (stripos($this->sql, 'SET estado =') === false) {
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
class PDODoubleProveedor extends PDO
{
    public function __construct() {}

    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // existeNombreProveedor: SELECT COUNT(*) FROM tbl_proveedores WHERE nombre_proveedor = ?
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_proveedores WHERE nombre_proveedor') !== false) {
            return new StatementDoubleProveedor($sql, [ 'scalar' => 1 ]);
        }
        // INSERT proveedor
        if (stripos($sql, 'INSERT INTO tbl_proveedores') !== false) {
            return new StatementDoubleProveedor($sql);
        }
        // Ultimo proveedor
        if (stripos($sql, 'SELECT * FROM tbl_proveedores ORDER BY id_proveedor DESC LIMIT 1') !== false) {
            return new StatementDoubleProveedor($sql, [
                'row' => [
                    'id_proveedor' => 99,
                    'nombre_proveedor' => 'UltimoProv',
                ],
            ]);
        }
        // obtenerProveedorPorId
        if (stripos($sql, 'SELECT * FROM tbl_proveedores WHERE id_proveedor =') !== false) {
            return new StatementDoubleProveedor($sql, [
                'row' => [
                    'id_proveedor' => 5,
                    'nombre_proveedor' => 'ProvX',
                    'correo_proveedor' => 'x@prov.test',
                ],
            ]);
        }
        // UPDATE proveedor
        if (stripos($sql, 'UPDATE tbl_proveedores SET') !== false) {
            return new StatementDoubleProveedor($sql);
        }
        // DELETE proveedor
        if (stripos($sql, 'DELETE FROM tbl_proveedores WHERE id_proveedor') !== false) {
            return new StatementDoubleProveedor($sql);
        }
        // Listado proveedores
        if (stripos($sql, 'SELECT * FROM tbl_proveedores') !== false) {
            return new StatementDoubleProveedor($sql, [
                'rows' => [
                    ['id_proveedor' => 2, 'nombre_proveedor' => 'ProvA'],
                    ['id_proveedor' => 1, 'nombre_proveedor' => 'ProvB'],
                ],
            ]);
        }
        // Reporte comparacion precios producto (prepare)
        if (stripos($sql, 'SELECT') !== false && stripos($sql, 'AVG(d.costo) AS precio_promedio') !== false) {
            return new StatementDoubleProveedor($sql, [
                'rows' => [
                    ['id_producto' => 1, 'nombre_producto' => 'P1', 'nombre_proveedor' => 'ProvA', 'precio_promedio' => 10.5, 'cantidad_registros' => 3],
                ],
            ]);
        }
        // Dependencia proveedores (prepare)
        if (stripos($sql, 'ROUND(') !== false && stripos($sql, 'dependencia_porcentaje') !== false) {
            return new StatementDoubleProveedor($sql, [
                'rows' => [
                    ['nombre_proveedor' => 'ProvA', 'monto_total_pagado' => 1000, 'dependencia_porcentaje' => 60.0],
                    ['nombre_proveedor' => 'ProvB', 'monto_total_pagado' => 666, 'dependencia_porcentaje' => 40.0],
                ],
            ]);
        }
        return new StatementDoubleProveedor($sql);
    }

    public function query($query, $fetchMode = null, ...$fetchModeArgs)
    {
        // Ranking de proveedores
        if (stripos($query, 'FROM tbl_recepcion_productos r') !== false && stripos($query, 'ORDER BY total DESC') !== false) {
            return new StatementDoubleProveedor($query, [
                'rows' => [
                    ['nombre_proveedor' => 'ProvA', 'nombre_producto' => 'Prod1', 'cantidad' => 5, 'costo' => 10, 'total' => 50, 'fecha' => '2025-10-01'],
                    ['nombre_proveedor' => 'ProvB', 'nombre_producto' => 'Prod2', 'cantidad' => 3, 'costo' => 20, 'total' => 60, 'fecha' => '2025-09-28'],
                ],
            ]);
        }
        // Dependencia de proveedores (query directo)
        if (stripos($query, 'dependencia_porcentaje') !== false && stripos($query, 'GROUP BY p.nombre_proveedor') !== false) {
            return new StatementDoubleProveedor($query, [
                'rows' => [
                    ['nombre_proveedor' => 'ProvA', 'monto_total_pagado' => 1000, 'dependencia_porcentaje' => 60.0],
                    ['nombre_proveedor' => 'ProvB', 'monto_total_pagado' => 666, 'dependencia_porcentaje' => 40.0],
                ],
            ]);
        }
        return new StatementDoubleProveedor($query);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class ProveedorTest extends TestCase
{
    private function nuevoProveedorConPDOStub(): Proveedores
    {
        $ref = new ReflectionClass(Proveedores::class);
        /** @var Proveedores $p */
        $p = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleProveedor();
        $prop = new ReflectionProperty(Proveedores::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($p, $pdo);
        return $p;
    }

    // PRV-UNIT-001: Registrar proveedor (happy path)
    public function testRegistrarProveedorHappyPath(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $p->setNombre('Proveedor Nuevo');
        $p->setRif1('J-123');
        $p->setRepresentante('Rep X');
        $p->setRif2('V-321');
        $p->setCorreo('prov@test.com');
        $p->setDireccion('Dirección');
        $p->setTelefono1('0414-0000000');
        $p->setTelefono2('0412-0000000');
        $p->setObservacion('Obs');
        $this->assertTrue($p->registrarProveedor());
    }

    // PRV-UNIT-002: Existe nombre de proveedor (true)
    public function testExisteNombreProveedorTrue(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $this->assertTrue($p->existeNombreProveedor('Proveedor Existente'));
    }

    // PRV-UNIT-003: Obtener último proveedor
    public function testObtenerUltimoProveedor(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $row = $p->obtenerUltimoProveedor();
        $this->assertIsArray($row);
        $this->assertSame('UltimoProv', $row['nombre_proveedor']);
    }

    // PRV-UNIT-004: Obtener proveedor por ID
    public function testObtenerProveedorPorId(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $row = $p->obtenerProveedorPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('ProvX', $row['nombre_proveedor']);
    }

    // PRV-UNIT-005: Modificar proveedor
    public function testModificarProveedor(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $p->setNombre('Editado');
        $p->setRif1('J-999');
        $p->setRepresentante('Rep Y');
        $p->setRif2('V-111');
        $p->setCorreo('e@test.com');
        $p->setDireccion('Dir');
        $p->setTelefono1('0414');
        $p->setTelefono2('0424');
        $p->setObservacion('Obs');
        $this->assertTrue($p->modificarProveedor(5));
    }

    // PRV-UNIT-006: Eliminar proveedor
    public function testEliminarProveedor(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $this->assertTrue($p->eliminarProveedor(5));
    }

    // PRV-UNIT-007: Listar proveedores
    public function testGetProveedoresListado(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $lista = $p->getproveedores();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_proveedor', $lista[0]);
        $this->assertArrayHasKey('nombre_proveedor', $lista[0]);
    }

    // PRV-UNIT-008: Reporte suministro proveedores (top 10)
    public function testObtenerReporteSuministroProveedores(): void
    {
        // Este método usa prepare/execute y fetchAll con una query JOIN múltiple
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->obtenerReporteSuministroProveedores();
        $this->assertIsArray($rows);
    }

    // PRV-UNIT-009: Ranking de proveedores
    public function testGetRankingProveedores(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->getRankingProveedores();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('nombre_proveedor', $rows[0]);
        $this->assertArrayHasKey('total', $rows[0]);
    }

    // PRV-UNIT-010: Comparación de precios de producto
    public function testGetComparacionPreciosProducto(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->getComparacionPreciosProducto();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('precio_promedio', $rows[0]);
    }

    // PRV-UNIT-011: Dependencia de proveedores
    public function testGetDependenciaProveedores(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->getDependenciaProveedores();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('dependencia_porcentaje', $rows[0]);
    }

    // PRV-UNIT-012: Cambiar estatus
    public function testCambiarEstatus(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $ref = new ReflectionProperty(Proveedores::class, 'id_proveedor');
        $ref->setAccessible(true);
        $ref->setValue($p, 7);
        $this->assertTrue($p->cambiarEstatus('A'));
    }
}
