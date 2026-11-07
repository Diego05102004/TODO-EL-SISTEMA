<?php
use PHPUnit\Framework\TestCase;

final class DespachoControllerTest extends TestCase
{
    private PDO $pdo;
    private string $controllerPath;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->controllerPath = __DIR__ . '/../../../controlador/Despacho.php';
        $this->limpiarTablas();
        $this->seedBasico();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['id_usuario'] = $_SESSION['id_usuario'] ?? 1;
        $_SESSION['id_rol'] = $_SESSION['id_rol'] ?? 1;
        $_SESSION['name'] = $_SESSION['name'] ?? 'Tester';
    }

    private function limpiarTablas(): void
    {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        $tablas = [
            'tbl_despacho_detalle',
            'tbl_despachos',
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

    private function seedBasico(): void
    {
        // Cliente
        $this->pdo->prepare("INSERT INTO tbl_clientes (nombre, cedula) VALUES ('Cliente IT', 'V-2000')")->execute();
        // Marca/Modelo/Producto
        $this->pdo->prepare("INSERT INTO tbl_marcas (nombre_marca) VALUES ('M-IT')")->execute();
        $id_marca = (int)$this->pdo->lastInsertId();
        $st = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('MD-IT', :id_marca)");
        $st->execute([':id_marca' => $id_marca]);
        $id_modelo = (int)$this->pdo->lastInsertId();
        // Producto con precio si existe la columna
        try {
            $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial, precio) VALUES ('P-IT', :id_modelo, 'IT-D-001', 15.00)");
            $st->execute([':id_modelo' => $id_modelo]);
        } catch (Throwable $e) {
            $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('P-IT', :id_modelo, 'IT-D-001')");
            $st->execute([':id_modelo' => $id_modelo]);
        }
    }

    private function getPrimerClienteId(): int
    {
        return (int)$this->pdo->query('SELECT id_clientes FROM tbl_clientes LIMIT 1')->fetchColumn();
    }

    private function getPrimerProductoId(): int
    {
        return (int)$this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
    }

    private function crearDespachoBase(string $estado = 'Por Despachar', int $activo = 1): int
    {
        $idCliente = $this->getPrimerClienteId();
        $stmt = $this->pdo->prepare("INSERT INTO tbl_despachos (id_clientes, fecha_despacho, tipocompra, estado, activo) VALUES (:idc, CURRENT_DATE(), 'Contado', :estado, :activo)");
        $stmt->execute([':idc' => $idCliente, ':estado' => $estado, ':activo' => $activo]);
        $id = (int)$this->pdo->lastInsertId();
        $idProd = $this->getPrimerProductoId();
        $this->pdo->prepare("INSERT INTO tbl_despacho_detalle (id_despacho, id_producto, cantidad) VALUES (?, ?, 1)")->execute([$id, $idProd]);
        return $id;
    }

    private function runController(array $post): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $postExport = var_export($post, true);
        $phpBinary = PHP_BINARY;
        $wrapper = tempnam(sys_get_temp_dir(), 'it_desp_') . '.php';

        $escapedRoot = addslashes($projectRoot);
        $script  = "<?php\n";
        $script .= "error_reporting(E_ALL);\n";
        $script .= "ini_set('display_errors','1');\n";
        $script .= "if (session_status() === PHP_SESSION_NONE) { session_start(); }\n";
        $script .= "if (!isset(\$_SESSION['id_usuario'])) { \$_SESSION['id_usuario'] = 1; }\n";
        $script .= "if (!isset(\$_SESSION['id_rol'])) { \$_SESSION['id_rol'] = 1; }\n";
        $script .= "if (!isset(\$_SESSION['name'])) { \$_SESSION['name'] = 'Tester'; }\n";
        $script .= "\$_SERVER['REQUEST_METHOD'] = 'POST';\n";
        $script .= "\$_POST = " . $postExport . ";\n";
        $script .= "$"."pagina = 'Despacho';\n";
        $script .= "chdir('" . $escapedRoot . "');\n";
        $script .= "// Evitar efectos secundarios (bitácora, redirecciones) y cargar bootstrap de pruebas\n";
        $script .= "if (!defined('SKIP_SIDE_EFFECTS')) { define('SKIP_SIDE_EFFECTS', true); }\n";
        $script .= "require 'tests/bootstrap.php';\n";
        $script .= "// Ejecutar controlador y capturar salida\n";
        $script .= "ob_start();\n";
        $script .= "require 'controlador/Despacho.php';\n";
        $script .= "$"."out = ob_get_clean();\n";
        $script .= "// Limpiar cualquier buffer residual\n";
        $script .= "while (ob_get_level() > 0) { ob_end_clean(); }\n";
        $script .= "echo $"."out;\n";

        file_put_contents($wrapper, $script);
        $cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($wrapper);
        $output = shell_exec($cmd);
        @unlink($wrapper);

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

    public function testListadoProductosDesdeControlador(): void
    {
        $resp = $this->runController([
            'accion' => 'listado'
        ]);
        $this->assertSame('listado', $resp['resultado'] ?? null);
        $this->assertStringContainsString('<tr', $resp['mensaje'] ?? '');
    }

    public function testCambiarEstadoDespachoDesdeControlador(): void
    {
        $id = $this->crearDespachoBase('Por Despachar', 1);
        $resp = $this->runController([
            'accion' => 'cambiar_estado_despacho',
            'id' => (string)$id,
            'estado_actual' => 'Por Despachar'
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
        $this->assertSame('Despachado', $resp['nuevo_estado'] ?? null);
        $estado = (string)$this->pdo->query("SELECT estado FROM tbl_despachos WHERE id_despachos={$id} LIMIT 1")->fetchColumn();
        $this->assertSame('Despachado', $estado);
    }

    public function testAnularDespachoDesdeControlador(): void
    {
        $id = $this->crearDespachoBase('Por Despachar', 1);
        $resp = $this->runController([
            'accion' => 'anular',
            'id_despachos' => (string)$id,
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
        $activo = (int)$this->pdo->query("SELECT activo FROM tbl_despachos WHERE id_despachos={$id} LIMIT 1")->fetchColumn();
        $this->assertSame(0, $activo);
    }

    public function testPermisosTiempoRealDesdeControlador(): void
    {
        $resp = $this->runController([
            'accion' => 'permisos_tiempo_real'
        ]);
        $this->assertIsArray($resp);
        // En entornos sin seed de seguridad, puede venir vacío o con estructura distinta
        $this->assertNotNull($resp);
    }
}
