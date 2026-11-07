<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;

// Dobles simples para simular PDO y PDOStatement
class FakeStmt
{
    public string $sql;
    public array $bound = [];
    public array $executedWith = [];
    
    public function __construct(string $sql) { 
        $this->sql = $sql; 
    }
    
    public function bindParam($param, &$var, $type = null) { 
        $this->bound[$param] = $var; 
        return true; 
    }
    
    public function execute($params = null) { 
        if ($params !== null) {
            // Si los parámetros son un array asociativo (nombrados)
            if (array_keys($params) !== range(0, count($params) - 1)) {
                $this->executedWith[] = $params;
            } else {
                // Convertir array numérico a asociativo basado en la consulta SQL
                $namedParams = [];
                if (strpos($this->sql, ':id') !== false) {
                    $namedParams[':id'] = $params[0] ?? null;
                }
                if (strpos($this->sql, ':u') !== false) {
                    $namedParams[':u'] = $params[1] ?? null;
                }
                $this->executedWith[] = $namedParams;
            }
        } else {
            $this->executedWith[] = [];
        }
        return true; 
    }
}

class FakePDO
{
    public ?FakeStmt $lastStmt = null;
    public function prepare($sql) { 
        $this->lastStmt = new FakeStmt($sql); 
        return $this->lastStmt; 
    }
}

final class NotificacionModuleTest extends TestCase
{
    public function testCrearInsertaConParametrosYEjecuta(): void
    {
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $ok = $m->crear(1, 'tipoX', 'Titulo', 'Mensaje', 'media', 15, 'ACCION', 123);
        $this->assertTrue($ok);
        $this->assertNotNull($fake->lastStmt);
        $this->assertStringContainsString('INSERT INTO tbl_notificaciones', $fake->lastStmt->sql);
        $this->assertSame('tipoX', $fake->lastStmt->bound[':tipo'] ?? null);
        $this->assertSame('Titulo', $fake->lastStmt->bound[':titulo'] ?? null);
        $this->assertSame('Mensaje', $fake->lastStmt->bound[':mensaje'] ?? null);
        $this->assertSame(123, $fake->lastStmt->bound[':id_referencia'] ?? null);
        $this->assertSame('media', $fake->lastStmt->bound[':prioridad'] ?? null);
        $this->assertSame(15, $fake->lastStmt->bound[':id_modulo'] ?? null);
        $this->assertSame('ACCION', $fake->lastStmt->bound[':accion'] ?? null);
    }

    public function testMarcarComoLeidoEjecutaUpdate(): void
    {
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $ok = $m->marcarComoLeida(1, 100);
        $this->assertTrue($ok);
        $this->assertNotNull($fake->lastStmt);
        $this->assertStringContainsString('UPDATE tbl_notificaciones SET leido = 1', $fake->lastStmt->sql);
        
        // Verificar que se llamó a execute con los parámetros correctos
        $this->assertNotEmpty($fake->lastStmt->executedWith);
        $params = $fake->lastStmt->executedWith[0] ?? [];
        
        // Verificar que los parámetros están en el formato esperado (nombrados o posicionales)
        if (array_key_exists(0, $params)) {
            // Formato posicional [id, usuario]
            $this->assertSame(1, $params[0] ?? null);
            $this->assertSame(100, $params[1] ?? null);
        } else {
            // Formato nombrado [':id' => id, ':u' => usuario]
            $this->assertArrayHasKey(':id', $params);
            $this->assertSame(1, $params[':id'] ?? null);
            $this->assertArrayHasKey(':u', $params);
            $this->assertSame(100, $params[':u'] ?? null);
        }
    }

    public function testNotificarPagoCreaNotificacionCorrectamente(): void
    {
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $ok = $m->notificarPago(1, 10, 'procesado');
        $this->assertTrue($ok);
        $this->assertNotNull($fake->lastStmt);
        $this->assertStringContainsString('INSERT INTO tbl_notificaciones', $fake->lastStmt->sql);
        $this->assertSame('pago', $fake->lastStmt->bound[':tipo'] ?? null);
        $this->assertSame('Estado de pago actualizado', $fake->lastStmt->bound[':titulo'] ?? null);
    }

    public function testNotificarDespachoCreaNotificacionCorrectamente(): void
    {
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $ok = $m->notificarDespacho(1, 77, 'enviado');
        $this->assertTrue($ok);
        $this->assertNotNull($fake->lastStmt);
        $this->assertStringContainsString('INSERT INTO tbl_notificaciones', $fake->lastStmt->sql);
        $this->assertSame('despacho', $fake->lastStmt->bound[':tipo'] ?? null);
        $this->assertSame('Estado de despacho', $fake->lastStmt->bound[':titulo'] ?? null);
    }
}