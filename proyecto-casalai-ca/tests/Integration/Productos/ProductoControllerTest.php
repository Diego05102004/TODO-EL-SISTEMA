<?php
use PHPUnit\Framework\TestCase;

final class ProductoControllerTest extends TestCase
{
    private PDO $pdo;
    private string $controllerPath;
    private int $idMarca;
    private int $idModelo;
    private ?int $idCategoria = null;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->controllerPath = __DIR__ . '/../../../Controlador/producto.php';
        $this->limpiarTablas();
        $this->seedBasico();
    }

    private function limpiarTablas(): void
    {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        foreach (['tbl_productos','tbl_modelos','tbl_marcas','tbl_categoria'] as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasico(): void
    {
        // Marca
        $this->pdo->exec("INSERT INTO tbl_marcas (nombre_marca) VALUES ('Marca IT')");
        $this->idMarca = (int)$this->pdo->lastInsertId();
        // Modelo
        $st = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('Modelo IT', :id_marca)");
        $st->execute([':id_marca' => $this->idMarca]);
        $this->idModelo = (int)$this->pdo->lastInsertId();
        // Categoría (si existe la tabla)
        try {
            $this->pdo->exec("INSERT INTO tbl_categoria (nombre_categoria) VALUES ('Categoria IT')");
            $this->idCategoria = (int)$this->pdo->lastInsertId();
            // Crear tabla dinámica mínima esperada por el modelo: cat_categoria_it
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS cat_categoria_it (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_producto INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            $this->idCategoria = null;
        }
    }

    private function runController(array $post): array
    {
        $projectRoot = realpath(__DIR__ . '/../../..');
        $controllerPath = $this->controllerPath;
        $postExport = var_export($post, true);

        $script = <<<'PHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$projectRoot = %s;
chdir($projectRoot);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = %s;
$_FILES = [];
if (!defined('SKIP_SIDE_EFFECTS')) { define('SKIP_SIDE_EFFECTS', true); }
require $projectRoot . '/tests/bootstrap.php';
require %s;
PHP;

        $script = sprintf(
            $script,
            var_export($projectRoot, true),
            $postExport,
            var_export($controllerPath, true)
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'it_prod_');
        if ($tmpFile === false) {
            $this->fail('No se pudo crear script temporal para ejecutar el controlador.');
        }
        $tmpPhp = $tmpFile . '.php';
        rename($tmpFile, $tmpPhp);
        file_put_contents($tmpPhp, $script);

        $cmd = '"' . PHP_BINARY . '" ' . escapeshellarg($tmpPhp);
        $output = shell_exec($cmd);

        @unlink($tmpPhp);

        $decoded = json_decode((string)$output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        if (preg_match('/\{.*\}\s*$/s', (string)$output, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        $this->fail('La salida del controlador no fue JSON parseable. Salida: ' . substr((string)$output, 0, 500));
    }

    public function testAccionNoValidaProductos(): void
    {
        $resp = $this->runController([
            'accion' => 'desconocida'
        ]);
        $this->assertSame('error', $resp['status'] ?? null);
        $this->assertStringContainsString('Acción no válida', $resp['message'] ?? '');
    }

    public function testIngresarYObtenerProducto(): void
    {
        $resp = $this->runController([
            'accion' => 'ingresar',
            'nombre_producto' => 'Producto IT',
            'descripcion_producto' => 'Desc IT',
            'modelo' => (string)$this->idModelo,
            'Stock_Actual' => 5,
            'Stock_Maximo' => 10,
            'Stock_Minimo' => 1,
            'Clausula_garantia' => 'N/A',
            'Seriales' => 'IT-P-001',
            // El modelo requiere 'tabla_categoria' para deducir id_categoria
            'tabla_categoria' => 'cat_categoria_it',
            'Precio' => 12.50,
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
        $id = (int)($resp['id_producto'] ?? 0);
        $this->assertGreaterThan(0, $id);

        // Obtener por id
        $resp2 = $this->runController([
            'accion' => 'obtener_producto',
            'id_producto' => (string)$id,
        ]);
        $this->assertIsArray($resp2);
        $this->assertNotEmpty($resp2);
    }

    public function testModificarProducto(): void
    {
        // Crear primero
        $create = $this->runController([
            'accion' => 'ingresar',
            'nombre_producto' => 'Prod Mod',
            'descripcion_producto' => 'Desc',
            'modelo' => (string)$this->idModelo,
            'Stock_Actual' => 2,
            'Stock_Maximo' => 5,
            'Stock_Minimo' => 1,
            'Seriales' => 'IT-M-001',
            'tabla_categoria' => 'cat_categoria_it',
            'Precio' => 9.99,
        ]);
        $id = (int)($create['id_producto'] ?? 0);
        $this->assertGreaterThan(0, $id);

        // Modificar
        $resp = $this->runController([
            'accion' => 'modificar',
            'id_producto' => (string)$id,
            'nombre_producto' => 'Prod Modificado',
            'descripcion_producto' => 'Desc2',
            'modelo' => (string)$this->idModelo,
            'Stock_Actual' => 3,
            'Stock_Maximo' => 6,
            'Stock_Minimo' => 1,
            'Seriales' => 'IT-M-001',
            // Para modificar, el modelo espera 'Categoria' con el nombre de la tabla dinámica
            'Categoria' => 'cat_categoria_it',
            'Precio' => 10.50,
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
    }

    public function testCambiarEstatusProducto(): void
    {
        $create = $this->runController([
            'accion' => 'ingresar',
            'nombre_producto' => 'Prod Est',
            'descripcion_producto' => 'Desc',
            'modelo' => (string)$this->idModelo,
            'Stock_Actual' => 1,
            'Stock_Maximo' => 3,
            'Stock_Minimo' => 1,
            'Seriales' => 'IT-E-001',
            'tabla_categoria' => 'cat_categoria_it',
            'Precio' => 5.00,
        ]);
        $id = (int)($create['id_producto'] ?? 0);
        $this->assertGreaterThan(0, $id);

        $resp = $this->runController([
            'accion' => 'cambiar_estatus',
            'id_producto' => (string)$id,
            'nuevo_estatus' => 'inhabilitado',
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
    }

    public function testEliminarProducto(): void
    {
        $create = $this->runController([
            'accion' => 'ingresar',
            'nombre_producto' => 'Prod Del',
            'descripcion_producto' => 'Desc',
            'modelo' => (string)$this->idModelo,
            'Stock_Actual' => 1,
            'Stock_Maximo' => 3,
            'Stock_Minimo' => 1,
            'Seriales' => 'IT-D-001',
            'tabla_categoria' => 'cat_categoria_it',
            'Precio' => 7.00,
        ]);
        $id = (int)($create['id_producto'] ?? 0);
        $this->assertGreaterThan(0, $id);

        // Asegurar que no existan filas dependientes que bloqueen el borrado (FKs)
        $depDeletes = [
            "DELETE FROM cat_categoria_it WHERE id_producto = :id",
            "DELETE FROM tbl_despacho_detalle WHERE id_producto = :id",
            "DELETE FROM tbl_carritodetalle WHERE id_producto = :id",
            "DELETE FROM tbl_combo_detalle WHERE id_producto = :id",
            "DELETE FROM tbl_factura_detalle WHERE id_producto = :id",
        ];
        foreach ($depDeletes as $sql) {
            try { $st = $this->pdo->prepare($sql); $st->execute([':id' => $id]); } catch (Throwable $e) {}
        }

        $resp = $this->runController([
            'accion' => 'eliminar',
            'id_producto' => (string)$id,
        ]);
        if (($resp['status'] ?? null) !== 'success') {
            $this->assertSame('error', $resp['status'] ?? null);
            $this->assertStringContainsString('No se puede eliminar el producto', (string)($resp['message'] ?? ''));
        }
    }

    public function testPermisosTiempoReal(): void
    {
        $resp = $this->runController([
            'accion' => 'permisos_tiempo_real'
        ]);
        $this->assertIsArray($resp);
    }

    public function testReporteParametrizadoPrecios(): void
    {
        // Asegurar al menos un producto
        $this->runController([
            'accion' => 'ingresar',
            'nombre_producto' => 'Prod Rep',
            'descripcion_producto' => 'Desc',
            'modelo' => (string)$this->idModelo,
            'Stock_Actual' => 1,
            'Stock_Maximo' => 3,
            'Stock_Minimo' => 1,
            'Seriales' => 'IT-R-001',
            'tabla_categoria' => 'cat_categoria_it',
            'Precio' => 11.00,
        ]);

        $resp = $this->runController([
            'accion' => 'reporte_parametrizado',
            'tipoReporte' => 'precios',
        ]);
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('labels', $resp);
        $this->assertArrayHasKey('data', $resp);
    }
}
