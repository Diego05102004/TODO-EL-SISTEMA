<?php
use PHPUnit\Framework\TestCase;

final class MarcaControllerTest extends TestCase
{
    private PDO $pdo;
    private string $controllerPath;
    private bool $skipStateful = false;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        truncate_tablas_basicas($this->pdo);
        $this->controllerPath = __DIR__ . '/../../Controlador/marca.php';
        // Habilitar pruebas en entorno de pruebas
        $this->skipStateful = false;
        
        // Configurar entorno de pruebas si no está configurado
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'testing');
        }
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');
    }

    private function runController(array $post): array
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $post;

        ob_start();
        require $this->controllerPath;
        $output = ob_get_clean();
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        if (preg_match('/\{.*\}\s*$/s', $output, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        $this->fail('La salida del controlador no fue JSON parseable. Salida: ' . substr($output, 0, 500));
    }

    private function crearMarcaDirecto(string $nombre): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO tbl_marcas (nombre_marca) VALUES (?)');
        $stmt->execute([$nombre]);
        return (int)$this->pdo->lastInsertId();
    }

    public function testRegistrarMarcaSuccess(): void
    {
        $nombre = 'Marca_' . bin2hex(random_bytes(6));
        // Defensa extra: asegurar que no existe antes del POST
        $stmt = $this->pdo->prepare('DELETE FROM tbl_marcas WHERE nombre_marca = ?');
        $stmt->execute([$nombre]);
        
        // Mensaje de depuración
        fwrite(STDERR, "Intentando registrar marca: $nombre\n");
        
        $resp = $this->runController([
            'accion' => 'registrar',
            'nombre_marca' => $nombre
        ]);
        
        // Mensaje de depuración
        fwrite(STDERR, "Respuesta del controlador: " . print_r($resp, true) . "\n");
        
        $this->assertSame('success', $resp['status'] ?? null, 'El estado de la respuesta no es "success"');
        $this->assertIsArray($resp['marca'] ?? null, 'La respuesta no contiene un array de marca');
        $this->assertSame($nombre, $resp['marca']['nombre_marca'] ?? null, 'El nombre de la marca no coincide');
    }

    public function testRegistrarMarcaDuplicada(): void
    {
        $this->crearMarcaDirecto('Repetida');
        $resp = $this->runController([
            'accion' => 'registrar',
            'nombre_marca' => 'Repetida'
        ]);
        $this->assertSame('error', $resp['status'] ?? null);
        $this->assertStringContainsString('ya existe', $resp['message'] ?? '');
    }

    public function testObtenerMarcasPorId(): void
    {
        $id = $this->crearMarcaDirecto('Lookup');
        $resp = $this->runController([
            'accion' => 'obtener_marcas',
            'id_marca' => (string)$id
        ]);
        // Este endpoint retorna directamente el array con nombre_marca en éxito
        $this->assertSame('Lookup', $resp['nombre_marca'] ?? null);
    }

public function testModificarMarcaSuccess(): void
    {
        $id = $this->crearMarcaDirecto('Old');
        $resp = $this->runController([
            'accion' => 'modificar',
            'id_marca' => (string)$id,
            'nombre_marca' => 'New'
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
        $this->assertSame('New', $resp['marca']['nombre_marca'] ?? null);
    }

public function testEliminarMarcaConModelosAsociadosBloquea(): void
    {
        $id = $this->crearMarcaDirecto('ConModelo');
        // Asociar un modelo a la marca
        $stmt = $this->pdo->prepare('INSERT INTO tbl_modelos (id_marca, nombre_modelo) VALUES (?, ?)');
        try {
            $stmt->execute([$id, 'Modelo1']);
        } catch (Throwable $e) {
            $this->markTestSkipped('tbl_modelos requiere columnas diferentes a (id_marca, nombre_modelo). Ajusta el seed según tu esquema. Error: ' . $e->getMessage());
        }

        $resp = $this->runController([
            'accion' => 'eliminar',
            'id_marca' => (string)$id
        ]);
        $this->assertSame('error', $resp['status'] ?? null);
        $this->assertStringContainsString('modelos asociados', $resp['message'] ?? '');
    }

public function testEliminarMarcaSinAsociacionesSuccess(): void
    {
        $id = $this->crearMarcaDirecto('SinModelo');
        $resp = $this->runController([
            'accion' => 'eliminar',
            'id_marca' => (string)$id
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
    }

    public function testAccionNoValida(): void
    {
        $resp = $this->runController([
            'accion' => 'desconocida'
        ]);
        $this->assertSame('error', $resp['status'] ?? null);
        $this->assertStringContainsString('Acción no válida', $resp['message'] ?? '');
    }
}
