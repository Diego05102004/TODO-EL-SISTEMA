<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\PasareladePago;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;

class PagosCrudTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = test_pdo();
        $this->pdo->beginTransaction();
        $this->limpiarTablas();
        $this->seedBasicoPagos();
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

    private function anyCuentaId(): ?int {
        $val = $this->pdo->query('SELECT id_cuenta FROM tbl_cuentas LIMIT 1')->fetchColumn();
        return $val ? (int)$val : null;
    }

    private function limpiarTablas(): void {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        $tablas = [
            'tbl_detalles_pago',
            'tbl_factura_detalle',
            'tbl_facturas',
            'tbl_cuentas',
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

    private function seedBasicoPagos(): void {
        // Cliente base
        try {
            $st = $this->pdo->prepare("INSERT INTO tbl_clientes (cedula, nombre, apellido) VALUES ('V12345678','Nombre','Apellido')");
            $st->execute();
        } catch (Throwable $e) {
            try {
                $st = $this->pdo->prepare("INSERT INTO tbl_clientes (cedula, nombre_cliente, apellido_cliente) VALUES ('V12345678','Nombre','Apellido')");
                $st->execute();
            } catch (Throwable $e2) {
                $this->pdo->exec("INSERT INTO tbl_clientes (cedula) VALUES ('V12345678')");
            }
        }

        // Marca/Modelo/Producto base
        try { $this->pdo->exec("INSERT INTO tbl_marcas (nombre_marca) VALUES ('M-PG')"); } catch (Throwable $e) {}
        $id_marca = (int)($this->pdo->query('SELECT id_marca FROM tbl_marcas LIMIT 1')->fetchColumn() ?: 1);
        try { $st = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('MD-PG', :m)"); $st->execute([':m' => $id_marca]); } catch (Throwable $e) {}
        $id_modelo = (int)($this->pdo->query('SELECT id_modelo FROM tbl_modelos LIMIT 1')->fetchColumn() ?: 1);
        try {
            $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial, precio) VALUES ('Prod PG', :id_modelo, 'S-PG-001', 10.00)");
            $st->execute([':id_modelo' => $id_modelo]);
        } catch (Throwable $e) {
            try { $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('Prod PG', :id_modelo, 'S-PG-001')"); $st->execute([':id_modelo' => $id_modelo]); } catch (Throwable $e2) {
                $this->pdo->exec("INSERT INTO tbl_productos (nombre_producto) VALUES ('Prod PG')");
            }
        }

        // Cuenta base
        try {
            $this->pdo->exec("INSERT INTO tbl_cuentas (nombre_banco, numero_cuenta, rif_cuenta, telefono_cuenta, correo_cuenta, metodos) VALUES ('Banco X','01020304050607080900','J-12345678-9','04121234567','banco@example.com','Transferencia')");
        } catch (Throwable $e) {
            $this->pdo->exec("INSERT INTO tbl_cuentas (nombre_banco, numero_cuenta) VALUES ('Banco X','01020304050607080900')");
        }
    }

    private function ensureFacturaId(): ?int {
        // Usar el cliente que ya existe del seedBasicoPagos
        $cedula = 'V12345678';
        $idProd = $this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
        
        if (!$idProd) {
            $this->pdo->exec("INSERT INTO tbl_productos (nombre_producto) VALUES ('Producto Prueba')");
            $idProd = $this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
        }
        
        // Obtener el id_clientes correspondiente a la cedula
        $stmt = $this->pdo->prepare('SELECT id_clientes FROM tbl_clientes WHERE cedula = ?');
        $stmt->execute([$cedula]);
        $idCliente = $stmt->fetchColumn();
        
        if (!$idCliente) {
            return null;
        }
        
        $f = new Factura();
        $f->setFecha(date('Y-m-d'));
        $f->setCliente($cedula); // Factura usará la cedula para buscar el id_clientes
        $f->setDescuento(0);
        $f->setEstatus('Borrador');
        $f->setIdProducto([(int)$idProd]);
        $f->setCantidad([1]);
        $ok = $f->facturaTransaccion('Ingresar');
        if ($ok !== true && is_array($ok) && isset($ok['error'])) {
            return null;
        }
        
        // Verificar que la factura se creó y tiene cliente
        $facturaId = (int)$this->pdo->query('SELECT MAX(id_factura) FROM tbl_facturas')->fetchColumn();
        if ($facturaId) {
            $facturaCheck = $this->pdo->prepare('SELECT cliente FROM tbl_facturas WHERE id_factura = ?');
            $facturaCheck->execute([$facturaId]);
            $facturaCliente = $facturaCheck->fetchColumn();
            if (!$facturaCliente) {
                return null;
            }
        }
        
        return $facturaId;
    }
    private function crearPagoBase(): array {
        $idCuenta = $this->anyCuentaId();
        $idFactura = $this->ensureFacturaId();

        // Asumimos datos sembrados de forma determinista
        $this->assertNotEmpty($idCuenta);
        $this->assertNotEmpty($idFactura);

        $p = new PasareladePago();
        $p->setFactura($idFactura);
        $p->setCuenta($idCuenta);
        $p->setObservaciones('Pago prueba PHPUnit');
        $p->setTipo('Transferencia');
        $p->setReferencia('REF-' . uniqid());
        $p->setFecha(date('Y-m-d H:i:s'));
        // Column 'comprobante' es NOT NULL en tu esquema; usa un marcador mínimo
        $p->setComprobante('N/A');
        $p->setMonto(100.50);

        $okIng = $p->pasarelaTransaccion('Ingresar');
        $this->assertTrue($okIng === true, 'Debe poder registrar pago.');

        $idPago = (int)$this->pdo->query('SELECT MAX(id_detalles) FROM tbl_detalles_pago')->fetchColumn();
        $this->assertGreaterThan(0, $idPago);

        return [$p, $idPago, $idFactura, $idCuenta];
    }

    public function testIngresarPago(): void {
        [$p, $idPago] = $this->crearPagoBase();
        $this->assertGreaterThan(0, $idPago);
    }

    public function testConsultarPagos(): void {
        // Precondición: al menos un pago registrado
        $this->crearPagoBase();

        $p = new PasareladePago();
        $todos = $p->pasarelaTransaccion('ConsultarTodos');
        $this->assertIsArray($todos);
    }

    public function testProcesarPago(): void {
        [$p, $idPago] = $this->crearPagoBase();

        $p->setIdDetalles($idPago);
        $p->setEstatus('Pago Procesado');

        $okProc = $p->pasarelaTransaccion('Procesar');
        $this->assertTrue($okProc === true);

        $row = $p->obtenerPagoPorId($idPago);
        $this->assertEquals('Pago Procesado', $row['estatus']);
    }
}
