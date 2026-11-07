<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;

final class RecepcionModuleTest extends TestCase
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
            'tbl_ingresos_egresos',
            'tbl_detalle_recepcion_productos',
            'tbl_recepcion_productos',
            'tbl_productos',
            'tbl_modelos',
            'tbl_marcas',
            'tbl_proveedores',
        ];
        foreach ($tablas as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasico(): void
    {
        // Insertar usuario de prueba para satisfacer la llave foránea de la bitácora
        try {
            $this->pdo->prepare("INSERT IGNORE INTO tbl_usuarios (id_usuario, usuario) VALUES (9, 'admin_test')")->execute();
        } catch (\Throwable $e) {
            try {
                $this->pdo->prepare("INSERT IGNORE INTO tbl_usuarios (id_usuario) VALUES (9)")->execute();
            } catch (\Throwable $e2) {}
        }

        $this->pdo->prepare("INSERT INTO tbl_proveedores (nombre_proveedor) VALUES ('Proveedor Test')")->execute();
        
        $this->pdo->prepare("INSERT INTO tbl_marcas (nombre_marca) VALUES ('Marca T')")->execute();
        $id_marca = (int)$this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('Modelo T', :id_marca)");
        $stmt->execute([':id_marca' => $id_marca]);
        $id_modelo = (int)$this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('Prod T', :id_modelo, 'S-001')");
        $stmt->execute([':id_modelo' => $id_modelo]);
    }

    private function getPrimerProveedorId(): int
    {
        return (int)$this->pdo->query('SELECT id_proveedor FROM tbl_proveedores LIMIT 1')->fetchColumn();
    }

    private function getPrimerProductoId(): int
    {
        return (int)$this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
    }

    public function testListadoProductosDevuelveHTML(): void
    {
        $r = new Recepcion();
        $resp = $r->listadoproductos();
        $this->assertIsArray($resp);
        $this->assertSame('listado', $resp['resultado'] ?? null);
        $this->assertIsString($resp['mensaje'] ?? '');
        $this->assertStringContainsString('<tr', $resp['mensaje']);
    }

    public function testRegistrarRecepcionYObtenerUltimaYExisteCorrelativo(): void
    {
        $r = new Recepcion();
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('123456');
        $r->setestado('habilitado');

        $idProd = $this->getPrimerProductoId();
        $res = $r->registrarRecepcion([$idProd], [2], [50.00], 16, 9);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('id_recepcion', $res);
        $this->assertArrayHasKey('recepcion', $res);
        $this->assertSame('123456', $res['recepcion']['correlativo']);

        $ultima = $r->obtenerUltimaRecepcion();
        $this->assertIsArray($ultima);
        $this->assertSame('123456', $ultima['correlativo']);

        $this->assertTrue($r->existeCorrelativo(['correlativo' => '123456']));
        $this->assertFalse($r->existeCorrelativo(['correlativo' => '999999']));

        // Verificar que se creó asiento en ingresos/egresos
        $cnt = (int)$this->pdo->query("SELECT COUNT(*) FROM tbl_ingresos_egresos")->fetchColumn();
        $this->assertGreaterThanOrEqual(1, $cnt, 'Debe crear egreso asociado a la recepción');
    }

    public function testBuscarRecepcionDevuelveProveedorFechaYEstado(): void
    {
        $correlativo = 'DETAIL-001';
        $stmt = $this->pdo->prepare(
            "INSERT INTO tbl_recepcion_productos (id_proveedor, fecha, correlativo, estado)
             VALUES (:proveedor, '2026-10-07', :correlativo, 'habilitado')"
        );
        $stmt->execute([
            ':proveedor' => $this->getPrimerProveedorId(),
            ':correlativo' => $correlativo,
        ]);

        $recepcion = new Recepcion();
        $recepcion->setcorrelativo($correlativo);
        $detalle = $recepcion->buscar();

        $this->assertSame('encontró', $detalle['resultado'] ?? null);
        $this->assertSame('Proveedor Test', $detalle['nombre_proveedor'] ?? null);
        $this->assertSame('2026-10-07', $detalle['fecha'] ?? null);
        $this->assertSame('habilitado', $detalle['estado'] ?? null);

        $detalleModal = $recepcion->obtenerDetalle();
        $this->assertSame('Proveedor Test', $detalleModal['nombre_proveedor'] ?? null);
        $this->assertSame('2026-10-07', $detalleModal['fecha'] ?? null);
        $this->assertArrayNotHasKey('estado', $detalleModal);
    }

    public function testObtenerProductosPorRecepcion(): void
    {
        $r = new Recepcion();
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('200001');
        $r->setestado('habilitado');
        $idProd = $this->getPrimerProductoId();
        $res = $r->registrarRecepcion([$idProd], [3], [10.00], 16, 9);

        $items = $r->obtenerProductosPorRecepcion($res['id_recepcion']);
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
        $this->assertArrayHasKey('codigo', $items[0]);
        $this->assertArrayHasKey('cantidad', $items[0]);
        $this->assertArrayHasKey('costo', $items[0]);
    }

    public function testGetRecepcionListaSoloHabilitados(): void
    {
        $r = new Recepcion();
        // Habilitada
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('300001');
        $r->setestado('habilitado');
        $idProd = $this->getPrimerProductoId();
        $r->registrarRecepcion([$idProd], [1], [5.00], 16, 9);

        // Otra y luego anularla
        $r2 = new Recepcion();
        $r2->setidproveedor($this->getPrimerProveedorId());
        $r2->setcorrelativo('300002');
        $r2->setestado('habilitado');
        $r2->registrarRecepcion([$idProd], [1], [5.00], 16, 9);
        $r2->anularRecepcion('300002', 9);

        $lista = $r->getrecepcion();
        $this->assertIsArray($lista);
        $correlativos = array_map(fn($x) => $x['correlativo'], $lista);
        $this->assertContains('300001', $correlativos);
        $this->assertNotContains('300002', $correlativos);
    }

    public function testAnularRecepcionCambiaEstado(): void
    {
        $r = new Recepcion();
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('400001');
        $r->setestado('habilitado');
        $idProd = $this->getPrimerProductoId();
        $r->registrarRecepcion([$idProd], [1], [1.00], 16, 9);

        $res = $r->anularRecepcion('400001', 9);
        $this->assertSame('success', $res['status'] ?? null);

        $estado = $this->pdo->query("SELECT estado FROM tbl_recepcion_productos WHERE correlativo='400001' LIMIT 1")->fetchColumn();
        $this->assertSame('anulado', $estado);
    }
}