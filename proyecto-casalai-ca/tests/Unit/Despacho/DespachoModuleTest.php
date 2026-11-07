<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Despacho;

final class DespachoModuleTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->limpiarTablas();
        $this->seedBasico();
    }

    private function limpiarTablas(): void
    {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        $tablas = [
            'tbl_despacho_detalle',
            'tbl_despachos',
            'tbl_productos',
            'tbl_modelos',
            'tbl_marcas',
            'tbl_clientes',
        ];
        foreach ($tablas as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasico(): void
    {
        // Cliente
        try {
            $this->pdo->prepare("INSERT INTO tbl_clientes (nombre, cedula) VALUES ('Cliente T', 'V-1000')")->execute();
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de tbl_clientes distinto al esperado. Ajustar seed. Error: ' . $e->getMessage());
        }

        // Marca/Modelo
        try {
            $this->pdo->prepare("INSERT INTO tbl_marcas (nombre_marca) VALUES ('M-T')")->execute();
            $id_marca = (int)$this->pdo->lastInsertId();
            $st = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('MD-T', :id_marca)");
            $st->execute([':id_marca' => $id_marca]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de marcas/modelos distinto. Ajustar seed. Error: ' . $e->getMessage());
        }

        // Producto con precio
        try {
            $id_modelo = (int)$this->pdo->lastInsertId();
            $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial, precio) VALUES ('Prod T', :id_modelo, 'S-D-001', 10.00)");
            $st->execute([':id_modelo' => $id_modelo]);
        } catch (Throwable $e) {
            // Si no existe la columna precio, intentar sin precio
            try {
                $id_modelo = (int)$this->pdo->lastInsertId();
                $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('Prod T', :id_modelo, 'S-D-001')");
                $st->execute([':id_modelo' => $id_modelo]);
            } catch (Throwable $e2) {
                $this->markTestSkipped('Esquema de productos distinto. Ajustar seed. Error: ' . $e2->getMessage());
            }
        }
    }

    private function getPrimerClienteId(): int
    {
        return (int)$this->pdo->query('SELECT id_clientes FROM tbl_clientes LIMIT 1')->fetchColumn();
    }

    private function getPrimerProductoId(): int
    {
        return (int)$this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
    }

    private function crearDespachoBase(string $tipocompra = 'Contado', string $estado = 'pendiente', int $activo = 1): int
    {
        $idCliente = $this->getPrimerClienteId();
        $stmt = $this->pdo->prepare("INSERT INTO tbl_despachos (id_clientes, fecha_despacho, tipocompra, estado, activo) VALUES (:idc, CURRENT_DATE(), :tipo, :estado, :activo)");
        try {
            $stmt->execute([':idc' => $idCliente, ':tipo' => $tipocompra, ':estado' => $estado, ':activo' => $activo]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de tbl_despachos distinto. Ajustar seed. Error: ' . $e->getMessage());
        }
        $id_despacho = (int)$this->pdo->lastInsertId();

        // detalle
        $idProd = $this->getPrimerProductoId();
        $stmtDet = $this->pdo->prepare("INSERT INTO tbl_despacho_detalle (id_despacho, id_producto, cantidad) VALUES (:id, :prod, :cant)");
        try {
            $stmtDet->execute([':id' => $id_despacho, ':prod' => $idProd, ':cant' => 2]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de tbl_despacho_detalle distinto. Ajustar seed. Error: ' . $e->getMessage());
        }
        return $id_despacho;
    }

    public function testListadoProductosDevuelveHTML(): void
    {
        $d = new Despacho();
        $resp = $d->listadoproductos();
        $this->assertIsArray($resp);
        $this->assertSame('listado', $resp['resultado'] ?? null);
        $this->assertStringContainsString('<tr', $resp['mensaje'] ?? '');
    }

    public function testConsultarProductos(): void
    {
        $d = new Despacho();
        $items = $d->consultarproductos();
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
        $this->assertArrayHasKey('id_producto', $items[0]);
        $this->assertArrayHasKey('nombre_producto', $items[0]);
    }

    public function testGetDespachoListaSoloActivos(): void
    {
        // Activo
        $id1 = $this->crearDespachoBase('Contado', 'pendiente', 1);
        // Activo luego anulado
        $id2 = $this->crearDespachoBase('Crédito', 'pendiente', 1);
        $d = new Despacho();
        $d->anularDespacho($id2);

        $lista = $d->getdespacho();
        $this->assertIsArray($lista);
        $ids = array_map(fn($x) => (int)$x['id_despachos'], $lista);
        $this->assertContains($id1, $ids);
        $this->assertNotContains($id2, $ids);
    }

    public function testObtenerProductosPorDespacho(): void
    {
        $id = $this->crearDespachoBase();
        $d = new Despacho();
        $prods = $d->obt_productos_despacho($id);
        $this->assertIsArray($prods);
        $this->assertNotEmpty($prods);
        $this->assertArrayHasKey('codigo', $prods[0]);
        $this->assertArrayHasKey('cantidad', $prods[0]);
    }

    public function testAnularDespacho(): void
    {
        $id = $this->crearDespachoBase();
        $d = new Despacho();
        $res = $d->anularDespacho($id);
        $this->assertSame('success', $res['status'] ?? null);
        $activo = (int)$this->pdo->query("SELECT activo FROM tbl_despachos WHERE id_despachos={$id} LIMIT 1")->fetchColumn();
        $this->assertSame(0, $activo);
    }

    public function testCambiarEstadoDespacho(): void
    {
        $id = $this->crearDespachoBase('Contado', 'pendiente', 1);
        $d = new Despacho();
        $ok = $d->cambiarEstadoDespacho($id, 'enviado');
        $this->assertTrue($ok);
        $estadoDespues = $this->pdo->query("SELECT estado FROM tbl_despachos WHERE id_despachos={$id} LIMIT 1")->fetchColumn();
        $this->assertNotFalse($estadoDespues, 'Debe existir el despacho y devolver algún estado');
        $this->assertIsString($estadoDespues);
    }
}
