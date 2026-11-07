<?php

if (php_sapi_name() !== 'cli') {
    die('Acceso no permitido');
}

require 'vendor/autoload.php';
require_once __DIR__ . '/Modelo/Config/database.php';
require_once __DIR__ . '/Modelo/Config/Config.php';
require_once __DIR__ . '/Modelo/Config/WebSocketConfig.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Http\OriginCheck;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\SocketServer;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\WebSocketConfig;

class NotificacionWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $usuarios = [];
    protected $autenticados = [];
    protected $limitesMensajes = [];
    protected $limitesAcciones = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }


    public function onMessage(ConnectionInterface $from, $msg) {
        $connectionId = spl_object_id($from);
        if (strlen((string)$msg) > 8192) {
            $from->close(1009);
            return;
        }
        if (!$this->permitirMensaje($connectionId)) {
            $from->close(1008);
            return;
        }

        $data = json_decode($msg, true);

        if (!is_array($data)) {
            $from->close(1007);
            return;
        }

        if (!isset($this->autenticados[$connectionId])) {
            if (($data['tipo'] ?? null) !== 'autenticar' || !is_string($data['token'] ?? null)) {
                $from->close(1008);
                return;
            }

            try {
                $usuarioId = WebSocketConfig::verifyToken($data['token']);
            } catch (\Throwable $e) {
                $from->close(1008);
                return;
            }

            $this->autenticados[$connectionId] = $usuarioId;
            $this->usuarios[$usuarioId] = $from;
            $from->send(json_encode(['tipo' => 'autenticado']));
            $this->enviarNotificacionesIniciales($from, $usuarioId);
            return;
        }

        $usuarioId = $this->autenticados[$connectionId];
        $tipo = $data['tipo'] ?? null;
        if (!is_string($tipo)) {
            return;
        }

        if ($tipo === 'marcar_leida') {
            if ($this->permitirAccion($connectionId, $tipo)) {
                $this->procesarMarcarLeida($from, $data);
            }
            return;
        }

        if ($tipo === 'ping_nuevas') {
            if ($this->permitirAccion($connectionId, $tipo)) {
                $this->procesarPingNuevas($from);
            }
            return;
        }

        if ($tipo === 'obtener_tasa_cambio') {
            if ($this->permitirAccion($connectionId, $tipo)) {
                $this->obtenerTasaCambio($from);
            }
            return;
        }

        if ($tipo === 'obtener_carrito_count') {
            if ($this->permitirAccion($connectionId, $tipo)) {
                $this->obtenerCarritoCount($from, $usuarioId);
            }
            return;
        }

        if ($tipo === 'ping') {
            $from->send(json_encode(['tipo' => 'pong']));
        }
    }

    private function permitirMensaje(int $connectionId): bool
    {
        $now = microtime(true);
        $limit = $this->limitesMensajes[$connectionId] ?? ['start' => $now, 'count' => 0];
        if ($now - $limit['start'] >= 10) {
            $limit = ['start' => $now, 'count' => 0];
        }
        $limit['count']++;
        $this->limitesMensajes[$connectionId] = $limit;
        return $limit['count'] <= 60;
    }

    private function permitirAccion(int $connectionId, string $action): bool
    {
        $now = microtime(true);
        $lastActionAt = $this->limitesAcciones[$connectionId][$action] ?? 0;
        if ($now - $lastActionAt < 2) {
            return false;
        }
        $this->limitesAcciones[$connectionId][$action] = $now;
        return true;
    }

    protected function enviarNotificacionesIniciales(ConnectionInterface $conn, int $usuarioId): void
    {
        try {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();

            $stmt = $pdo_seguridad->prepare("SELECT id_notificacion, tipo, titulo, mensaje, id_referencia, fecha_hora, leido, prioridad FROM tbl_notificaciones WHERE id_usuario = :id_usuario AND leido = 0 ORDER BY fecha_hora DESC LIMIT 5");
            $stmt->bindParam(':id_usuario', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear fecha para compatibilidad con el frontend
            foreach ($notificaciones as &$notif) {
                $src = $notif['fecha_hora'] ?? $notif['fecha_creacion'] ?? null;
                if ($src) {
                    $timestamp = strtotime($src);
                    $notif['fecha_formateada'] = $timestamp !== false
                        ? date('d/m/Y H:i:s', $timestamp)
                        : $src;
                } else {
                    $notif['fecha_formateada'] = '';
                }
            }
            unset($notif);

            $conn->send(json_encode([
                'tipo' => 'sync_inicial',
                'notificaciones' => $notificaciones,
                'count' => count($notificaciones),
            ]));
        } catch (\Throwable $e) {
            error_log('No se pudieron cargar las notificaciones iniciales: ' . $e->getMessage());
        } finally {
            if (isset($bd_seguridad)) {
                $bd_seguridad->cerrar();
            }
        }
    }

    protected function procesarPingNuevas(ConnectionInterface $from): void
    {
        // Determinar el usuario asociado a esta conexión
        $usuarioId = array_search($from, $this->usuarios, true);
        if ($usuarioId === false) {
            return;
        }

        try {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();

            $stmt = $pdo_seguridad->prepare("SELECT id_notificacion, tipo, titulo, mensaje, id_referencia, fecha_hora, leido, prioridad FROM tbl_notificaciones WHERE id_usuario = :id_usuario AND leido = 0 ORDER BY fecha_hora DESC LIMIT 5");
            $stmt->bindParam(':id_usuario', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($notificaciones as &$notif) {
                $src = $notif['fecha_hora'] ?? $notif['fecha_creacion'] ?? null;
                if ($src) {
                    $timestamp = strtotime($src);
                    $notif['fecha_formateada'] = $timestamp !== false
                        ? date('d/m/Y H:i:s', $timestamp)
                        : $src;
                } else {
                    $notif['fecha_formateada'] = '';
                }
            }
            unset($notif);

            $from->send(json_encode([
                'tipo' => 'nueva_notificacion',
                'notificaciones' => $notificaciones,
                'count' => count($notificaciones),
            ]));
        } catch (\Throwable $e) {
            error_log('No se pudieron consultar notificaciones recientes: ' . $e->getMessage());
        } finally {
            if (isset($bd_seguridad)) {
                $bd_seguridad->cerrar();
            }
        }
    }

    protected function obtenerTasaCambio(ConnectionInterface $from): void
    {
        try {
            // Obtener la tasa de cambio de la base de datos
            $bd = new BD('S');
            $pdo = $bd->getConexion();
            
            $stmt = $pdo->query("SELECT * FROM tbl_tasa_cambio ORDER BY fecha_actualizacion DESC LIMIT 1");
            $tasa = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($tasa) {
                $from->send(json_encode([
                    'tipo' => 'actualizar_tasa_cambio',
                    'tasa' => $tasa['tasa'],
                    'actualizado' => date('d/m/Y H:i', strtotime($tasa['fecha_actualizacion']))
                ]));
            } else {
                $from->send(json_encode([
                    'tipo' => 'actualizar_tasa_cambio',
                    'tasa' => 1.00,
                    'actualizado' => date('d/m/Y H:i')
                ]));
            }
        } catch (\Exception $e) {
            error_log('No se pudo consultar la tasa de cambio: ' . $e->getMessage());
            $from->send(json_encode([
                'tipo' => 'actualizar_tasa_cambio',
                'error' => 'No se pudo obtener la tasa de cambio actual.'
            ]));
        } finally {
            if (isset($bd)) {
                $bd->cerrar();
            }
        }
    }

    protected function obtenerCarritoCount(ConnectionInterface $from, int $usuarioId): void
    {
        try {
            $bd = new BD('P'); // Usar 'S' para consultas de lectura
            $pdo = $bd->getConexion();

            // Obtener el ID del carrito más reciente del usuario
            $sqlCarrito = "SELECT id_carrito FROM tbl_carrito WHERE id_cliente = :usuario_id ORDER BY fecha_creacion DESC LIMIT 1";
            $stmt = $pdo->prepare($sqlCarrito);
            $stmt->bindParam(':usuario_id', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();
            $carrito = $stmt->fetch(\PDO::FETCH_ASSOC);

            $count = 0;
            if ($carrito) {
                $carritoId = $carrito['id_carrito'];

                // Contar los productos en el carrito con estatus 'pendiente'
                $sqlDetalle = "SELECT COUNT(*) as total FROM tbl_carritodetalle WHERE id_carrito = :carrito_id AND estatus = 'pendiente'";
                $stmt = $pdo->prepare($sqlDetalle);
                $stmt->bindParam(':carrito_id', $carritoId, \PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $count = (int)$result['total'];
            }
            $from->send(json_encode([
                'tipo' => 'actualizar_carrito_count',
                'count' => $count
            ]));

        } catch (\Exception $e) {
            error_log('No se pudo consultar el carrito: ' . $e->getMessage());
            $from->send(json_encode([
                'tipo' => 'actualizar_carrito_count',
                'error' => 'No se pudo consultar el carrito.'
            ]));
        } finally {
            if (isset($bd)) {
                $bd->cerrar();
            }
        }
    }

    protected function procesarMarcarLeida(ConnectionInterface $from, array $data) {
        if (!isset($data['id_notificacion'])) {
            return;
        }

        $usuarioId = array_search($from, $this->usuarios, true);
        if ($usuarioId === false) {
            return;
        }

        $idNotificacion = (int)$data['id_notificacion'];

        try {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();

            $stmt = $pdo_seguridad->prepare("UPDATE tbl_notificaciones SET leido = 1 WHERE id_notificacion = :id AND id_usuario = :usuario");
            $stmt->bindParam(':id', $idNotificacion, \PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();

            $ok = $stmt->rowCount() > 0;

            $respuesta = [
                'tipo' => 'marcar_leida',
                'ok' => $ok,
                'id_notificacion' => $idNotificacion,
            ];

            if (!$ok) {
                $respuesta['error'] = 'No se actualizó ninguna fila';
            }

            $from->send(json_encode($respuesta));
        } catch (\Throwable $e) {
            error_log('No se pudo marcar la notificación como leída: ' . $e->getMessage());
            $from->send(json_encode([
                'tipo' => 'marcar_leida',
                'ok' => false,
                'id_notificacion' => $idNotificacion,
                'error' => 'No se pudo actualizar la notificación.',
            ]));
        } finally {
            if (isset($bd_seguridad)) {
                $bd_seguridad->cerrar();
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $connectionId = spl_object_id($conn);
        if (isset($this->autenticados[$connectionId])) {
            $usuarioId = $this->autenticados[$connectionId];
            if (($this->usuarios[$usuarioId] ?? null) === $conn) {
                unset($this->usuarios[$usuarioId]);
            }
            unset($this->autenticados[$connectionId]);
        }
        unset($this->limitesMensajes[$connectionId], $this->limitesAcciones[$connectionId]);
        $this->clients->detach($conn);
        echo "Conexión cerrada! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

try {
    $settings = WebSocketConfig::settings();
    WebSocketConfig::validateSecret();
    $loop = LoopFactory::create();
    $socketUri = sprintf(
        '%s://%s:%d',
        $settings['tls_certificate'] !== null ? 'tls' : 'tcp',
        $settings['address'],
        $settings['port']
    );
    $socketContext = $settings['tls_certificate'] !== null
        ? ['tls' => [
            'local_cert' => $settings['tls_certificate'],
            'local_pk' => $settings['tls_private_key'],
        ]]
        : [];
    $socket = new SocketServer($socketUri, $socketContext, $loop);
    $component = new HttpServer(
        new OriginCheck(
            new WsServer(new NotificacionWebSocket()),
            $settings['allowed_origins']
        )
    );
    $server = new IoServer($component, $socket, $loop);
} catch (\Throwable $e) {
    fwrite(STDERR, 'No se pudo iniciar WebSocket: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo sprintf("Servidor WebSocket iniciado en %s:%d\n", $settings['address'], $settings['port']);
$server->run();