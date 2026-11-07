<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\marca;

final class MarcaModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        truncate_tablas_basicas($this->pdo);
    }

    private function crearMarca(string $nombre): array
    {
        $m = new marca();
        $m->setnombre_marca($nombre);
        $ok = $m->registrarMarca();
        $this->assertTrue($ok, 'Fallo al registrar marca');
        $ultima = $m->obtenerUltimaMarca();
        $this->assertIsArray($ultima);
        $this->assertArrayHasKey('id_marca', $ultima);
        $this->assertArrayHasKey('nombre_marca', $ultima);
        return $ultima;
    }

    public function testRegistrarMarcaYObtenerUltima(): void
    {
        $m1 = $this->crearMarca('ACME');
        $m2 = $this->crearMarca('ACME 2');
        $this->assertGreaterThan((int)$m1['id_marca'], (int)$m2['id_marca']);
        $this->assertSame('ACME 2', $m2['nombre_marca']);
    }

    public function testExisteNombreMarca(): void
    {
        $this->crearMarca('Z-Brand');
        $m = new marca();
        $this->assertTrue($m->existeNombreMarca('Z-Brand'));
        $this->assertFalse($m->existeNombreMarca('Inexistente'));
    }

    public function testModificarMarca(): void
    {
        $creada = $this->crearMarca('OldName');
        $m = new marca();
        $m->setIdMarca($creada['id_marca']);
        $m->setnombre_marca('NewName');
        $ok = $m->modificarmarcas($creada['id_marca']);
        $this->assertTrue($ok);
        $act = $m->obtenermarcasPorId($creada['id_marca']);
        $this->assertIsArray($act);
        $this->assertSame('NewName', $act['nombre_marca']);
    }

    public function testEliminarMarcaSinAsociaciones(): void
    {
        $creada = $this->crearMarca('ToDelete');
        $m = new marca();
        $this->assertFalse($m->tieneModelosAsociados($creada['id_marca']));
        $ok = $m->eliminarmarcas($creada['id_marca']);
        $this->assertTrue($ok);
        $this->assertFalse($m->existeNombreMarca('ToDelete'));
    }

    public function testObtenerMarcasPorId(): void
    {
        $creada = $this->crearMarca('Lookup');
        $m = new marca();
        $res = $m->obtenermarcasPorId($creada['id_marca']);
        $this->assertIsArray($res);
        $this->assertSame('Lookup', $res['nombre_marca']);
    }
}
