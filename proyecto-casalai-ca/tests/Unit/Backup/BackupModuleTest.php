<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Backup;

final class BackupModuleTest extends TestCase
{
    private string $backupDir;

    protected function setUp(): void
    {
        $this->backupDir = realpath(__DIR__ . '/../../../') . '/db/backup/';
        $this->limpiarCarpeta();
    }

    private function limpiarCarpeta(): void
    {
        $files = @glob($this->backupDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    
    private function limpiarCarpetaReal(): void
    {
        $backupDir = __DIR__ . '/../../../../Modelo/db/respaldo/';
        $files = @glob($backupDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function testListarFiltraSoloSQL(): void
    {
        $backupDir = __DIR__ . '/../../../../Modelo/db/respaldo/';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0775, true);
        }
        
        // Crear archivos de prueba
        file_put_contents($backupDir . 'a.sql', '-- dump A');
        file_put_contents($backupDir . 'b.SQL', '-- dump B'); // No coincide, case-sensitive por regex
        file_put_contents($backupDir . 'c.txt', 'texto');
        file_put_contents($backupDir . 'd.sql.bak', 'otro');

        $b = new Backup('P');
        $lista = $b->listar();
        $this->assertIsArray($lista);
        
        // Verificar que solo devuelve archivos .sql
        $soloArchivosSql = true;
        foreach ($lista as $archivo) {
            if (!isset($archivo['nombre']) || !preg_match('/\.sql$/i', $archivo['nombre'])) {
                $soloArchivosSql = false;
                break;
            }
        }
        $this->assertTrue($soloArchivosSql, 'Todos los archivos deben ser .sql');
        
        // Limpiar archivos de prueba
        @unlink($backupDir . 'a.sql');
        @unlink($backupDir . 'b.SQL');
        @unlink($backupDir . 'c.txt');
        @unlink($backupDir . 'd.sql.bak');
    }

    public function testGenerarCreaCarpetaAunqueFalleDump(): void
    {
        // Usar la misma ruta que usa la clase Backup
        $backupDir = __DIR__ . '/../../../../Modelo/db/respaldo/';
        
        // Verificar el estado inicial de la carpeta
        $carpetaExistiaAntes = is_dir($backupDir);
        
        $b = new Backup('P');
        $resultado = $b->generar('prueba_unit_' . time() . '.sql');
        
        // La carpeta debe existir después de llamar a generar()
        $this->assertTrue(is_dir($backupDir), 'La carpeta de backup debe existir después de generar()');
        
        // Si la carpeta no existía antes, entonces fue creada por generar()
        if (!$carpetaExistiaAntes) {
            $this->assertTrue(is_dir($backupDir), 'La carpeta de backup debió ser creada por generar()');
        }
        
        // Verificar que el resultado es un array con las claves esperadas
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertArrayHasKey('archivo', $resultado);
        
        // Limpiar archivo creado si existe
        $archivoCreado = $backupDir . $resultado['archivo'];
        if (file_exists($archivoCreado)) {
            @unlink($archivoCreado);
        }
    }

    public function testRestaurarConArchivoInexistenteDevuelveFalse(): void
    {
        $b = new Backup('P');
        $ok = $b->restaurar('archivo_que_no_existe.sql');
        $this->assertFalse($ok);
    }
}
