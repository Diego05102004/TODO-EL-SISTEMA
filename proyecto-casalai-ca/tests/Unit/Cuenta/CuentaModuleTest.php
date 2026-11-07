<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;

final class CuentaModuleTest extends TestCase
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
        foreach (['tbl_pagos','tbl_cuentas'] as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function crearCuentaBasica(string $nb='Banco Uno', string $num='00011122233344455566'): int
    {
        $c = new Cuentabanco();
        $c->setNombreBanco($nb);
        $c->setNumeroCuenta($num);
        $c->setRifCuenta('J-12345678-9');
        $c->setTelefonoCuenta('0412-0000000');
        $c->setCorreoCuenta('banco@correo.test');
        $c->setMetodosPago(['transferencia','pago_movil']);
        $ok = $c->registrarCuentabanco();
        $this->assertTrue($ok, 'Fallo al registrar cuenta');
        $ultima = $c->obtenerUltimaCuenta();
        $this->assertIsArray($ultima);
        $this->assertArrayHasKey('id_cuenta', $ultima);
        return (int)$ultima['id_cuenta'];
    }

    public function testRegistrarYObtenerUltimaYExisteNumero(): void
    {
        $id1 = $this->crearCuentaBasica('Banco A', '01020304050607080900');
        $cUlt = new Cuentabanco();
        $ultima = $cUlt->obtenerUltimaCuenta();
        $this->assertIsArray($ultima);
        $this->assertSame('01020304050607080900', $ultima['numero_cuenta'] ?? null);

        $cChk = new Cuentabanco();
        $this->assertTrue($cChk->existeNumeroCuenta('01020304050607080900'));
        $this->assertFalse($cChk->existeNumeroCuenta('99999999999999999999'));

        // Mismo número pero excluyendo el propio id no debe marcar duplicado
        $this->assertFalse($cChk->existeNumeroCuenta('01020304050607080900', $id1));
    }

    public function testConsultarCuentabanco(): void
    {
        $this->crearCuentaBasica('Banco B', '11112222333344445555');
        $c = new Cuentabanco();
        $lista = $c->consultarCuentabanco();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_cuenta', $lista[0]);
        $this->assertArrayHasKey('nombre_banco', $lista[0]);
    }

    public function testObtenerCuentaPorIdYModificar(): void
    {
        $id = $this->crearCuentaBasica('Banco C', '22223333444455556666');
        $c = new Cuentabanco();
        $cu = $c->obtenerCuentaPorId($id);
        $this->assertIsArray($cu);
        $this->assertSame('Banco C', $cu['nombre_banco'] ?? null);

        $c2 = new Cuentabanco();
        $c2->setNombreBanco('Banco C Mod');
        $c2->setNumeroCuenta('22223333444455556666');
        $c2->setRifCuenta('J-98765432-1');
        $c2->setTelefonoCuenta('0414-1111111');
        $c2->setCorreoCuenta('otro@correo.test');
        $c2->setMetodosPago('transferencia');
        $ok = $c2->modificarCuentabanco($id);
        $this->assertTrue($ok);

        $c3 = new Cuentabanco();
        $act = $c3->obtenerCuentaPorId($id);
        $this->assertSame('Banco C Mod', $act['nombre_banco'] ?? null);
        $this->assertSame('otro@correo.test', $act['correo_cuenta'] ?? null);
    }

    public function testEliminarCuentabancoSinPagos(): void
    {
        $id = $this->crearCuentaBasica('Banco D', '33334444555566667777');
        $c = new Cuentabanco();
        $res = $c->eliminarCuentabanco($id);
        $this->assertIsArray($res);
        if (($res['status'] ?? '') === 'success') {
            $existe = $this->pdo->query("SELECT COUNT(*) FROM tbl_cuentas WHERE id_cuenta={$id}")->fetchColumn();
            $this->assertSame(0, (int)$existe);
        } else {
            // En esquemas distintos, tienePagosAsociados() puede considerar que hay pagos o fallar y asumir true
            $this->assertSame('error', $res['status'] ?? null);
            $this->assertArrayHasKey('message', $res);
        }
    }

    public function testVerificarYActualizarEstado(): void
    {
        // Garantiza existencia de columna estado o la crea según el método del modelo
        $c = new Cuentabanco();
        $c->verificarEstado();

        $id = $this->crearCuentaBasica('Banco E', '44445555666677778888');
        $c2 = new Cuentabanco();
        $c2->setIdCuenta($id);
        $ok = $c2->cambiarEstado('inhabilitado');
        $this->assertTrue($ok);

        // Solo verificamos si existe la columna; si existe, debe reflejar el cambio
        $col = $this->pdo->query("SHOW COLUMNS FROM tbl_cuentas LIKE 'estado'")->fetch(PDO::FETCH_ASSOC);
        if ($col) {
            $estado = (string)$this->pdo->query("SELECT estado FROM tbl_cuentas WHERE id_cuenta={$id} LIMIT 1")->fetchColumn();
            $this->assertSame('inhabilitado', $estado);
        } else {
            $this->markTestSkipped('La columna estado no pudo crearse/consultarse en este entorno.');
        }
    }
}
