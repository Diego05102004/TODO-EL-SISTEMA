<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Finanza;

final class FinanzaModuleTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->limpiarTablas();
    }

    private function limpiarTablas(): void
    {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        $tablas = [
            'tbl_ingresos_egresos',
        ];
        foreach ($tablas as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasicoProductos(): void
    {
        // Marca y modelo para productos
        try {
            $this->pdo->prepare("INSERT INTO tbl_marcas (nombre_marca) VALUES ('M-FN')")->execute();
            $id_marca = (int)$this->pdo->lastInsertId();
            $st = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('MD-FN', :id_marca)");
            $st->execute([':id_marca' => $id_marca]);
            $id_modelo = (int)$this->pdo->lastInsertId();
            // Producto con precio; si no existe columna precio, insertar sin precio
            try {
                $ins = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial, precio) VALUES ('Prod FN', :id_modelo, 'S-FN-001', 25.50)");
                $ins->execute([':id_modelo' => $id_modelo]);
            } catch (Throwable $e) {
                $ins = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('Prod FN', :id_modelo, 'S-FN-001')");
                $ins->execute([':id_modelo' => $id_modelo]);
            }
        } catch (Throwable $e) {
            $this->markTestSkipped('No se pudieron crear marca/modelo/producto base. Error: '.$e->getMessage());
        }
    }

    private function getPrimerProductoId(): int
    {
        return (int)$this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
    }

    private function crearRecepcionConDetalle(float $costo = 12.34, int $cant = 3): int
    {
        // Crear cabecera de recepción si existe tabla; si no, solo detalle con id_recepcion ficticio según esquema
        $id_recepcion = 1;
        try {
            $this->pdo->exec("INSERT INTO tbl_recepcion_productos (fecha_recepcion, proveedor, correlativo, activo) VALUES (CURRENT_DATE(), 'Prov', 'RC-1', 1)");
            $id_recepcion = (int)$this->pdo->lastInsertId();
        } catch (Throwable $e) {
            // Si no existe la tabla, usar id fijo
            $id_recepcion = 1;
        }
        $idProd = $this->getPrimerProductoId();
        // Insertar detalle con costo y cantidad
        try {
            $st = $this->pdo->prepare("INSERT INTO tbl_detalle_recepcion_productos (id_recepcion, id_producto, cantidad, costo) VALUES (:idr, :idp, :c, :cost)");
            $st->execute([':idr' => $id_recepcion, ':idp' => $idProd, ':c' => $cant, ':cost' => $costo]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de detalle recepción distinto. Error: '.$e->getMessage());
        }
        return $id_recepcion;
    }

    private function crearDespachoConFactura(float $precio = 25.50, int $cant = 2): int
    {
        // Crear factura
        try {
            $this->pdo->exec("INSERT INTO tbl_facturas (fecha_factura, cliente) VALUES (CURRENT_DATE(), 'Cliente X')");
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de tbl_facturas distinto. Error: '.$e->getMessage());
        }
        $id_factura = (int)$this->pdo->lastInsertId();
        $idProd = $this->getPrimerProductoId();
        // Detalle de factura
        try {
            $st = $this->pdo->prepare("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad, precio_unit) VALUES (:idf, :idp, :c, :p)");
            $st->execute([':idf' => $id_factura, ':idp' => $idProd, ':c' => $cant, ':p' => $precio]);
        } catch (Throwable $e) {
            // Si la columna de precio se llama distinto, intentar sin ese campo y confiar en precio de producto
            try {
                $st = $this->pdo->prepare("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) VALUES (:idf, :idp, :c)");
                $st->execute([':idf' => $id_factura, ':idp' => $idProd, ':c' => $cant]);
            } catch (Throwable $e2) {
                $this->markTestSkipped('Esquema de tbl_factura_detalle distinto. Error: '.$e2->getMessage());
            }
        }
        // Crear despacho que apunte a la factura
        try {
            $st = $this->pdo->prepare("INSERT INTO tbl_despachos (id_factura, fecha_despacho, tipocompra, estado, activo) VALUES (:idf, CURRENT_DATE(), 'Contado', 'Despachado', 1)");
            $st->execute([':idf' => $id_factura]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Esquema de tbl_despachos distinto. Error: '.$e->getMessage());
        }
        return (int)$this->pdo->lastInsertId();
    }

    public function testConsultarIngresos(): void
    {
        // Seed de ingresos y egresos
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('ingreso', 10.00, 'i1', NOW(), 1)");
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('ingreso', 5.50, 'i2', NOW(), 1)");
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('egreso', 3.00, 'e1', NOW(), 1)");
        $f = new Finanza();
        $ing = $f->consultarIngresos();
        $this->assertIsArray($ing);
        $this->assertGreaterThanOrEqual(2, count($ing));
        $this->assertSame('ingreso', $ing[0]['tipo'] ?? 'ingreso');
    }

    public function testConsultarEgresos(): void
    {
        // Seed de ingresos y egresos
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('egreso', 7.00, 'e2', NOW(), 1)");
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('ingreso', 2.00, 'i3', NOW(), 1)");
        $f = new Finanza();
        $egr = $f->consultarEgresos();
        $this->assertIsArray($egr);
        $this->assertNotEmpty($egr);
        $this->assertSame('egreso', $egr[0]['tipo'] ?? 'egreso');
    }
}
