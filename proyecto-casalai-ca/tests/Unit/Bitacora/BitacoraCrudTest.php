<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

class BitacoraCrudTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = test_pdo();
        $this->pdo->beginTransaction();
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        // Asegurar que el id_usuario exista en la BD de seguridad (Bitácora usa 'S')
        try {
            $pdoSeg = (new BD('S'))->getConexion();
            $idUsuario = (int)($pdoSeg->query('SELECT id_usuario FROM tbl_usuarios LIMIT 1')->fetchColumn());
            if (!$idUsuario) {
                $this->markTestSkipped('No hay usuarios en seguridadlai.tbl_usuarios para probar Bitácora.');
            }
            $_SESSION['id_usuario'] = $idUsuario;
        } catch (Throwable $e) {
            $this->markTestSkipped('No se pudo acceder a la BD de seguridad para Bitácora: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function test_incluir_y_listar_bitacora(): void {
        $b = new Bitacora();
        $ok = $b->registrarBitacora(
            (int)$_SESSION['id_usuario'],
            '1',
            'PRUEBA',
            'Registro de prueba de PHPUnit',
            'baja',
            ['anterior' => 'x'],
            ['nuevo' => 'y']
        );
        $this->assertTrue($ok === true, 'Debe registrar en bitácora');

        $rows = $b->obtenerRegistrosDetallados(5);
        $this->assertIsArray($rows);
    }
}

