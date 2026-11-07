<?php
use PHPUnit\Framework\TestCase;

final class FinanzaControllerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->limpiarTablas();
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
        foreach (['tbl_ingresos_egresos'] as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function runController(): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $phpBinary = PHP_BINARY;
        $wrapper = tempnam(sys_get_temp_dir(), 'it_fin_') . '.php';

        $escapedRoot = addslashes($projectRoot);
        $escapedDoubles = addslashes($projectRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'doubles');
        $script  = "<?php\n";
        $script .= "error_reporting(E_ALL);\n";
        $script .= "ini_set('display_errors','1');\n";

        $script .= "if (session_status() === PHP_SESSION_NONE) { session_start(); }\n";
        $script .= "if (!isset(\$_SESSION['id_usuario'])) { \$_SESSION['id_usuario'] = 1; }\n";
        $script .= "if (!isset(\$_SESSION['id_rol'])) { \$_SESSION['id_rol'] = 1; }\n";
        $script .= "if (!isset(\$_SESSION['name'])) { \$_SESSION['name'] = 'Tester'; }\n";
        $script .= "\$_SERVER['REQUEST_METHOD'] = 'GET';\n";
        $script .= "define('SKIP_SIDE_EFFECTS', true);\n";
        // Cambiar al directorio de doubles para que Vista/finanza.php sea el stub y no la vista real
        $script .= "chdir('" . $escapedDoubles . "');\n";
        $script .= "// Cargar bootstrap de pruebas (ruta absoluta) para include_path y stubs\n";
        $script .= "require '" . $escapedRoot . "/tests/bootstrap.php';\n";
        $script .= "// Asegurar limpieza total de buffers (el controlador abre uno propio)\n";
        $script .= "ob_start();\n";
        $script .= "require 'controlador/finanza.php';\n";
        $script .= "while (ob_get_level() > 0) { ob_end_clean(); }\n";

        // Empaquetar variables globales producidas por el controlador
        $script .= "$"."data = [\n";
        $script .= "  'finanzas' => isset($"."finanzas) ? $"."finanzas : null,\n";
        $script .= "  'ingresosPorMes' => isset($"."ingresosPorMes) ? $"."ingresosPorMes : null,\n";
        $script .= "  'egresosPorMes' => isset($"."egresosPorMes) ? $"."egresosPorMes : null,\n";
        $script .= "  'meses' => isset($"."meses) ? $"."meses : null,\n";
        $script .= "  'totalIngresos' => isset($"."totalIngresos) ? $"."totalIngresos : null,\n";
        $script .= "  'totalEgresos' => isset($"."totalEgresos) ? $"."totalEgresos : null,\n";
        $script .= "];\n";
        $script .= "echo json_encode($"."data);\n";

        file_put_contents($wrapper, $script);
        $cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($wrapper);
        $output = shell_exec($cmd);
        @unlink($wrapper);

        $decoded = json_decode((string)$output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        $this->fail('La salida del controlador no fue JSON parseable. Salida: ' . substr((string)$output, 0, 500));
    }

    public function testConsultaSinDatos(): void
    {
        $resp = $this->runController();
        $this->assertIsArray($resp);
        $this->assertIsArray($resp['finanzas']['ingresos'] ?? []);
        $this->assertIsArray($resp['finanzas']['egresos'] ?? []);
        $this->assertSame(0.0, (float)($resp['totalIngresos'] ?? 0));
        $this->assertSame(0.0, (float)($resp['totalEgresos'] ?? 0));
    }

    public function testConsultaConDatosYAgrupacion(): void
    {
        // Seed: dos ingresos y un egreso en el mes actual, un ingreso en mes anterior
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('ingreso', 10.00, 'i1', NOW(), 1)");
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('ingreso', 5.50, 'i2', NOW(), 1)");
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('egreso', 3.00, 'e1', NOW(), 1)");
        $this->pdo->exec("INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado) VALUES ('ingreso', 2.25, 'i_prev', DATE_SUB(NOW(), INTERVAL 1 MONTH), 1)");

        $resp = $this->runController();

        $this->assertIsArray($resp);
        $ingresos = $resp['finanzas']['ingresos'] ?? [];
        $egresos = $resp['finanzas']['egresos'] ?? [];
        $this->assertGreaterThanOrEqual(3, count($ingresos));
        $this->assertGreaterThanOrEqual(1, count($egresos));

        $this->assertIsArray($resp['ingresosPorMes'] ?? null);
        $this->assertIsArray($resp['egresosPorMes'] ?? null);
        $this->assertIsArray($resp['meses'] ?? null);

        $totalIng = (float)($resp['totalIngresos'] ?? -1);
        $totalEgr = (float)($resp['totalEgresos'] ?? -1);
        $this->assertGreaterThan(0, $totalIng);
        $this->assertGreaterThanOrEqual(3.0, $totalEgr);
    }
}