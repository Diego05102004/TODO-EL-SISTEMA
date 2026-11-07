<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;

class FacturaCrudTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = test_pdo();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function anyClienteCedula(): ?string {
        $ced = $this->pdo->query('SELECT cedula FROM tbl_clientes LIMIT 1')->fetchColumn();
        return $ced ?: null;
    }

    private function someProductosIds(int $n = 1): array {
        $stmt = $this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT ' . (int)$n);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function crearFacturaBase(): array {
        $cedula = $this->anyClienteCedula();
        $ids = $this->someProductosIds(2);
        if (!$cedula || count($ids) === 0) {
            $this->markTestSkipped('No hay clientes o productos en BD para probar Factura.');
        }

        $f = new Factura();
        $f->setFecha(date('Y-m-d'));
        $f->setCliente($cedula);
        $f->setDescuento(0);
        $f->setEstatus('Borrador');
        $f->setIdProducto($ids);
        $f->setCantidad(array_fill(0, count($ids), 1));

        $resIngresar = $f->facturaTransaccion('Ingresar');
        $this->assertTrue(
            $resIngresar === true || (is_array($resIngresar) && !isset($resIngresar['error'])),
            'Debe poder ingresar factura.'
        );

        $idFactura = (int)$this->pdo->query('SELECT MAX(id_factura) FROM tbl_facturas')->fetchColumn();
        $this->assertGreaterThan(0, $idFactura);

        return [$f, $idFactura, $cedula];
    }

    public function testIncluirFactura(): void {
        [$f, $idFactura, $cedula] = $this->crearFacturaBase();
        $this->assertGreaterThan(0, $idFactura);
    }

    public function testConsultarFactura(): void {
        // Crear una factura de prueba
        [$f, $idFactura, $cedula] = $this->crearFacturaBase();
        
        // Verificar que la factura se creó correctamente
        $this->assertGreaterThan(0, $idFactura, 'La factura no se creó correctamente');
        
        // Consultar la factura
        $consulta = new Factura();
        $consulta->setCedula($cedula);
        $resConsulta = $consulta->facturaTransaccion('Consultar');

        // Verificaciones básicas
        $this->assertIsArray($resConsulta, 'La consulta no devolvió un array');
        $this->assertArrayHasKey('resultado', $resConsulta, 'La clave "resultado" no existe en la respuesta');
        $this->assertArrayHasKey('mensaje', $resConsulta, 'La clave "mensaje" no existe en la respuesta');
        
        // Verificar que el resultado es 'listado' y el mensaje contiene HTML
        $this->assertSame('listado', $resConsulta['resultado']);
        $this->assertStringContainsString('Pedido #', $resConsulta['mensaje'], 'El mensaje no contiene el formato esperado');
        
        // Verificar que la factura existe en la base de datos
        $facturaEnBD = $this->pdo->query(
            "SELECT COUNT(*) FROM tbl_facturas WHERE id_factura = " . (int)$idFactura
        )->fetchColumn();
        
        $this->assertEquals(1, (int)$facturaEnBD, 'La factura no existe en la base de datos');
    }

    public function testProcesarFactura(): void {
        [$f, $idFactura, $cedula] = $this->crearFacturaBase();

        $f->setId($idFactura);
        $okProcesar = $f->facturaTransaccion('Procesar');
        $this->assertTrue($okProcesar === true);
    }

    public function testCancelarFactura(): void {
        [$f, $idFactura, $cedula] = $this->crearFacturaBase();

        // Si la lógica requiere procesar antes de cancelar, lo hacemos como precondición
        $f->setId($idFactura);
        $f->facturaTransaccion('Procesar');

        $f->setId($idFactura);
        $okCancelar = $f->facturaTransaccion('Cancelar');
        $this->assertTrue($okCancelar === true);

        $estatus = $this->pdo
            ->query('SELECT estatus FROM tbl_facturas WHERE id_factura=' . (int)$idFactura)
            ->fetchColumn();
        $this->assertNotEmpty($estatus);
    }
}
