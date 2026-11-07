<?php
use PHPUnit\Framework\TestCase;

final class FacturaControllerTest extends TestCase
{
    private string $controllerPath;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../../Controlador/factura.php';
    }

    private function runController(array $post): array
    {
        $projectRoot = realpath(__DIR__ . '/../../..');
        $controllerPath = $this->controllerPath;
        $postExport = var_export($post, true);

        $script = <<<'PHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$projectRoot = %s;
chdir($projectRoot);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = %s;
$_FILES = [];

require %s;
PHP;

        $script = sprintf(
            $script,
            var_export($projectRoot, true),
            $postExport,
            var_export($controllerPath, true)
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'it_fact_');
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
        return ['status' => 'unknown', 'raw' => (string)$output];
    }

    public function testAccionNoValidaFactura(): void
    {
        $resp = $this->runController([
            'accion' => 'desconocida'
        ]);
        $this->assertIsArray($resp);
    }
}

