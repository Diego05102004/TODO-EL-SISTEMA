<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria;

final class CategoriaCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        try {
            $this->pdo->query('SELECT 1 FROM tbl_categoria LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Tabla tbl_categoria no disponible: ' . $e->getMessage());
            return;
        }
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function crearCategoriaBasica(): array
    {
        $c = new Categoria();
        $nombre = 'CatPHP ' . substr(uniqid(), 0, 8);
        $c->setNombreCategoria($nombre);

        $caracteristicas = [
            ['nombre' => 'campo_int', 'tipo' => 'int'],
            ['nombre' => 'campo_float', 'tipo' => 'float'],
            ['nombre' => 'campo_texto', 'tipo' => 'string', 'max' => 50],
        ];

        $ok = $c->registrarCategoria($caracteristicas);
        $this->assertTrue($ok, 'Debe poder registrar la categoría.');

        $ultima = $c->obtenerUltimoCategoria();
        $this->assertIsArray($ultima);
        $this->assertStringStartsWith('CatPHP ', $ultima['nombre_categoria']);

        $idCategoria = (int)$ultima['id_categoria'];
        $nombreGuardado = $ultima['nombre_categoria'];

        return [$c, $idCategoria, $nombreGuardado, $caracteristicas];
    }

    public function testIncluirCategoria(): void
    {
        [$c, $idCategoria, $nombre] = $this->crearCategoriaBasica();

        $row = $c->obtenerCategoriaPorId($idCategoria);
        $this->assertIsArray($row);
        $this->assertEquals($idCategoria, (int)$row['id_categoria']);
        $this->assertEquals($nombre, $row['nombre_categoria']);
    }

    public function testListarCategoria(): void
    {
        [$c, $idCategoria] = $this->crearCategoriaBasica();

        $lista = $c->consultarCategorias();
        $this->assertIsArray($lista);
        $ids = array_column($lista, 'id_categoria');
        $this->assertContains($idCategoria, $ids);
    }

    public function testModificarCategoria(): void
    {
        // Crear una categoría inicial
        [$c, $idCategoria, $nombre] = $this->crearCategoriaBasica();

        // Generar un nombre único para la categoría modificada
        $nuevoNombre = 'CatMod_' . uniqid();
        
        // Definir nuevas características
        $nuevasCaracteristicas = [
            ['nombre' => 'campo_int', 'tipo' => 'int'],
            ['nombre' => 'campo_texto', 'tipo' => 'string', 'max' => 100],
            ['nombre' => 'campo_extra', 'tipo' => 'string', 'max' => 50],
        ];

        try {
            // Intentar modificar la categoría
            $ok = $c->modificarCategoria($idCategoria, $nuevoNombre, $nuevasCaracteristicas);
            $this->assertTrue($ok, 'Debe poder modificar la categoría.');

            // Verificar que la categoría se actualizó correctamente
            $row = $c->obtenerCategoriaPorId($idCategoria);
            $this->assertIsArray($row, 'La categoría modificada no se pudo recuperar');
            $this->assertEquals($nuevoNombre, $row['nombre_categoria'], 'El nombre de la categoría no se actualizó correctamente');
        } catch (PDOException $e) {
            // Si hay un error de tabla existente, limpiar y reintentar
            if (strpos($e->getMessage(), 'already exists') !== false) {
                // Limpiar tablas temporales
                $this->limpiarTablasTemporales($c, $nuevoNombre);
                // Reintentar la modificación
                $ok = $c->modificarCategoria($idCategoria, $nuevoNombre, $nuevasCaracteristicas);
                $this->assertTrue($ok, 'Fallo al reintentar modificar la categoría después de limpiar tablas temporales');
                
                $row = $c->obtenerCategoriaPorId($idCategoria);
                $this->assertIsArray($row);
                $this->assertEquals($nuevoNombre, $row['nombre_categoria']);
            } else {
                // Si es otro tipo de error, lanzarlo
                throw $e;
            }
        }
    }

    /**
     * Limpia tablas temporales que puedan causar conflictos
     */
    private function limpiarTablasTemporales(Categoria $categoria, string $nombreCategoria): void
    {
        $tabla = 'cat_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $nombreCategoria)) . '_' . md5($nombreCategoria);
        $tabla = substr($tabla, 0, 64); // Asegurar que no exceda el límite de longitud
        
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS `$tabla`");
        } catch (PDOException $e) {
            // Ignorar errores al eliminar tablas que no existen
            if (strpos($e->getMessage(), 'exist') === false) {
                throw $e;
            }
        }
    }

    public function testEliminarCategoria(): void
    {
        [$c, $idCategoria] = $this->crearCategoriaBasica();

        $res = $c->eliminarCategoria($idCategoria);
        $this->assertIsArray($res);
        $this->assertSame('success', $res['status'] ?? null);

        $row = $c->obtenerCategoriaPorId($idCategoria);
        $this->assertNull($row);
    }
}
