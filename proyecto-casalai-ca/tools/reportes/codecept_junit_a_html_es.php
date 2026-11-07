<?php
declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Uso: php codecept_junit_a_html_es.php <ruta_junit.xml> <salida.html> [titulo]\n");
    exit(1);
}

[$script, $junitPath, $outPath] = $argv;
$titulo = $argv[3] ?? 'Reporte de Pruebas (Español)';

if (!file_exists($junitPath)) {
    fwrite(STDERR, "No se encuentra junit.xml en: {$junitPath}\n");
    exit(2);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($junitPath);
if ($xml === false) {
    fwrite(STDERR, "No se pudo leer/parsing junit.xml\n");
    foreach (libxml_get_errors() as $error) {
        fwrite(STDERR, $error->message . "\n");
    }
    exit(3);
}

function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$totalTests = 0; $totalFailures = 0; $totalErrors = 0; $totalTime = 0.0;
$rows = [];

// Codeception JUnit puede tener <testsuite> raíz o múltiples niveles.
$testSuites = [];
if ($xml->getName() === 'testsuites') {
    foreach ($xml->testsuite as $suite) { $testSuites[] = $suite; }
} elseif ($xml->getName() === 'testsuite') {
    $testSuites[] = $xml;
} else {
    // fallback: intentar encontrar testcases directos
    $testSuites[] = $xml;
}

foreach ($testSuites as $suite) {
    $suiteName = (string)($suite['name'] ?? 'Suite');
    $suiteTests = (int)($suite['tests'] ?? 0);
    $suiteFailures = (int)($suite['failures'] ?? 0);
    $suiteErrors = (int)($suite['errors'] ?? 0);
    $suiteTime = (float)($suite['time'] ?? 0);

    $totalTests += $suiteTests; $totalFailures += $suiteFailures; $totalErrors += $suiteErrors; $totalTime += $suiteTime;

    foreach ($suite->testcase as $case) {
        $classname = (string)($case['classname'] ?? '');
        $name = (string)($case['name'] ?? '');
        $time = (string)($case['time'] ?? '0');
        $status = 'OK';
        $detalle = '';
        if (isset($case->failure)) {
            $status = 'FALLA';
            $detalle = (string)$case->failure;
        } elseif (isset($case->error)) {
            $status = 'ERROR';
            $detalle = (string)$case->error;
        } elseif (isset($case->skipped)) {
            $status = 'OMITIDO';
            $detalle = (string)$case->skipped;
        }
        $rows[] = [
            'suite' => $suiteName,
            'clase' => $classname,
            'nombre' => $name,
            'tiempo' => $time,
            'estado' => $status,
            'detalle' => $detalle,
        ];
    }
}

$html = [];
$html[] = '<!DOCTYPE html>';
$html[] = '<html lang="es"><head><meta charset="utf-8">';
$html[] = '<meta name="viewport" content="width=device-width, initial-scale=1">';
$html[] = '<title>' . esc($titulo) . '</title>';
$html[] = '<style>body{font-family:Segoe UI,Arial,sans-serif;margin:20px;background:#fafafa;color:#222}';
$html[] = '.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 2px rgba(0,0,0,.04)}';
$html[] = '.badge{display:inline-block;padding:2px 8px;border-radius:9999px;font-size:12px;margin-left:8px}';
$html[] = '.ok{background:#def7ec;color:#03543f}.fail{background:#fde8e8;color:#9b1c1c}.err{background:#fef3c7;color:#92400e}.muted{color:#6b7280}';
$html[] = 'table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border-bottom:1px solid #eee;text-align:left;padding:8px;font-size:14px}';
$html[] = 'th{background:#f9fafb;font-weight:600} pre{white-space:pre-wrap;background:#f9fafb;border:1px solid #eee;border-radius:6px;padding:8px}';
$html[] = '</style></head><body>';

$html[] = '<h1>' . esc($titulo) . '</h1>';
$html[] = '<div class="card">';
$html[] = '<h2>Resumen</h2>';
$html[] = '<p><strong>Pruebas totales:</strong> ' . $totalTests . ' &nbsp; ' .
          '<strong>Fallos:</strong> ' . $totalFailures . ' &nbsp; ' .
          '<strong>Errores:</strong> ' . $totalErrors . ' &nbsp; ' .
          '<strong>Tiempo:</strong> ' . number_format($totalTime, 3) . 's</p>';
$estadoGlobal = ($totalFailures === 0 && $totalErrors === 0) ? 'ok' : 'fail';
$textoGlobal = ($estadoGlobal === 'ok') ? 'ÉXITO' : 'CON INCIDENCIAS';
$html[] = '<p><span class="badge ' . $estadoGlobal . '">' . $textoGlobal . '</span></p>';
$html[] = '<p class="muted">Fuente: ' . esc($junitPath) . '</p>';
$html[] = '</div>';

$html[] = '<div class="card">';
$html[] = '<h2>Casos</h2>';
$html[] = '<table><thead><tr><th>Suite</th><th>Clase</th><th>Nombre</th><th>Tiempo</th><th>Estado</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $badge = 'ok';
    if ($r['estado'] === 'FALLA') $badge = 'fail';
    if ($r['estado'] === 'ERROR') $badge = 'err';
    $html[] = '<tr>' .
              '<td>' . esc($r['suite']) . '</td>' .
              '<td>' . esc($r['clase']) . '</td>' .
              '<td>' . esc($r['nombre']) . '</td>' .
              '<td>' . esc((string)$r['tiempo']) . 's</td>' .
              '<td><span class="badge ' . $badge . '">' . esc($r['estado']) . '</span></td>' .
              '</tr>';
    if (!empty($r['detalle'])) {
        $html[] = '<tr><td colspan="5"><strong>Detalle:</strong><br><pre>' . esc($r['detalle']) . '</pre></td></tr>';
    }
}
$html[] = '</tbody></table>';
$html[] = '<p class="muted">Más detalles y artefactos (HTML/Pantallazos) en la carpeta tests/_output/</p>';
$html[] = '</div>';

$html[] = '</body></html>';

@mkdir(dirname($outPath), 0777, true);
file_put_contents($outPath, implode("\n", $html));

echo "Reporte creado: {$outPath}\n";
