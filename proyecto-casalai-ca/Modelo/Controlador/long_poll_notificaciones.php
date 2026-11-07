<?php

use Usuario\ProyectoCasalaiCa\Config\BD;

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/Config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$usuarioId = filter_var($_SESSION['id_usuario'] ?? null, FILTER_VALIDATE_INT);
if ($usuarioId === false || $usuarioId < 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Autenticación requerida.']);
    exit;
}

if (empty($_SESSION['csrf_long_poll'])) {
    $_SESSION['csrf_long_poll'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_long_poll'];
session_write_close();

$respond = static function (array $body, int $status = 200): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

$createDatabase = static function (string $database): array {
    $connection = new BD($database);
    return [$connection, $connection->getConexion()];
};

$formatNotifications = static function (array $notifications): array {
    foreach ($notifications as &$notification) {
        $timestamp = strtotime((string)($notification['fecha_hora'] ?? ''));
        $notification['fecha_formateada'] = $timestamp === false
            ? (string)($notification['fecha_hora'] ?? '')
            : date('d/m/Y H:i:s', $timestamp);
    }
    unset($notification);
    return $notifications;
};

$action = (string)($_GET['action'] ?? 'notifications');

try {
    if ($action === 'mark_read') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $respond(['success' => false, 'error' => 'Método no permitido.'], 405);
        }

        $providedCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (!is_string($providedCsrf) || !hash_equals($csrfToken, $providedCsrf)) {
            $respond(['success' => false, 'error' => 'Token CSRF inválido.'], 403);
        }

        $notificationId = filter_var($_POST['id_notificacion'] ?? null, FILTER_VALIDATE_INT);
        if ($notificationId === false || $notificationId < 1) {
            $respond(['success' => false, 'error' => 'ID de notificación inválido.'], 400);
        }

        [$database, $pdo] = $createDatabase('S');
        try {
            $statement = $pdo->prepare(
                'UPDATE tbl_notificaciones
                 SET leido = 1
                 WHERE id_notificacion = :id AND id_usuario = :usuario'
            );
            $statement->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $statement->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
            $statement->execute();
            $responseBody = [
                'success' => true,
                'tipo' => 'marcar_leida',
                'ok' => $statement->rowCount() > 0,
                'id_notificacion' => $notificationId,
            ];
        } finally {
            $pdo = null;
            $database->cerrar();
        }
        $respond($responseBody);
    }

    if ($action === 'cart_count') {
        [$database, $pdo] = $createDatabase('P');
        try {
            $cartStatement = $pdo->prepare(
                'SELECT id_carrito
                 FROM tbl_carrito
                 WHERE id_cliente = :usuario
                 ORDER BY fecha_creacion DESC
                 LIMIT 1'
            );
            $cartStatement->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
            $cartStatement->execute();
            $cartId = $cartStatement->fetchColumn();

            $count = 0;
            if ($cartId !== false) {
                $countStatement = $pdo->prepare(
                    "SELECT COUNT(*)
                     FROM tbl_carritodetalle
                     WHERE id_carrito = :carrito AND estatus = 'pendiente'"
                );
                $countStatement->bindValue(':carrito', (int)$cartId, PDO::PARAM_INT);
                $countStatement->execute();
                $count = (int)$countStatement->fetchColumn();
            }

            $responseBody = [
                'success' => true,
                'tipo' => 'actualizar_carrito_count',
                'count' => $count,
            ];
        } finally {
            $pdo = null;
            $database->cerrar();
        }
        $respond($responseBody);
    }

    if ($action === 'exchange_rate') {
        [$database, $pdo] = $createDatabase('S');
        try {
            $statement = $pdo->query(
                'SELECT tasa, fecha_actualizacion
                 FROM tbl_tasa_cambio
                 ORDER BY fecha_actualizacion DESC
                 LIMIT 1'
            );
            $rate = $statement->fetch(PDO::FETCH_ASSOC);

            $responseBody = [
                'success' => true,
                'tipo' => 'actualizar_tasa_cambio',
                'tasa' => $rate ? $rate['tasa'] : 1.00,
                'actualizado' => $rate
                    ? date('d/m/Y H:i', strtotime($rate['fecha_actualizacion']))
                    : date('d/m/Y H:i'),
            ];
        } finally {
            $pdo = null;
            $database->cerrar();
        }
        $respond($responseBody);
    }

    if ($action !== 'notifications' || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        $respond(['success' => false, 'error' => 'Acción o método no permitido.'], 405);
    }

    $cursorValue = $_GET['after_id'] ?? '0';
    if (!is_string($cursorValue) || !ctype_digit($cursorValue)) {
        $respond(['success' => false, 'error' => 'Cursor inválido.'], 400);
    }
    $cursor = filter_var($cursorValue, FILTER_VALIDATE_INT);
    if ($cursor === false || $cursor < 0) {
        $respond(['success' => false, 'error' => 'Cursor inválido.'], 400);
    }
    $initialValue = $_GET['initial'] ?? '0';
    if (!in_array($initialValue, ['0', '1'], true)) {
        $respond(['success' => false, 'error' => 'Indicador inicial inválido.'], 400);
    }
    $isInitialRequest = $initialValue === '1';

    ignore_user_abort(false);
    set_time_limit(25);
    $timeoutSeconds = 20;
    $startedAt = microtime(true);
    [$database, $pdo] = $createDatabase('S');
    $loadUnreadNotifications = static function () use ($pdo, $usuarioId, $formatNotifications): array {
        $statement = $pdo->prepare(
            'SELECT id_notificacion, tipo, titulo, mensaje, id_referencia,
                    fecha_hora, leido, prioridad
             FROM tbl_notificaciones
             WHERE id_usuario = :usuario AND leido = 0
             ORDER BY fecha_hora DESC
             LIMIT 5'
        );
        $statement->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $statement->execute();
        return $formatNotifications($statement->fetchAll(PDO::FETCH_ASSOC));
    };

    $responseBody = null;
    $clientDisconnected = false;
    try {
        do {
            if (connection_aborted()) {
                $clientDisconnected = true;
                break;
            }

            $latestStatement = $pdo->prepare(
                'SELECT COALESCE(MAX(id_notificacion), 0)
                 FROM tbl_notificaciones
                 WHERE id_usuario = :usuario'
            );
            $latestStatement->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
            $latestStatement->execute();
            $latestId = (int)$latestStatement->fetchColumn();
            if ($cursor > $latestId) {
                $cursor = $latestId;
            }

            if ($isInitialRequest || $latestId > $cursor) {
                $notifications = $loadUnreadNotifications();

                $responseBody = [
                    'success' => true,
                    'tipo' => 'nueva_notificacion',
                    'notificaciones' => $notifications,
                    'count' => count($notifications),
                    'cursor' => $latestId,
                    'csrf_token' => $csrfToken,
                    'timeout' => false,
                ];
                break;
            }

            if (microtime(true) - $startedAt >= $timeoutSeconds) {
                $notifications = $loadUnreadNotifications();
                $responseBody = [
                    'success' => true,
                    'tipo' => 'nueva_notificacion',
                    'notificaciones' => $notifications,
                    'count' => count($notifications),
                    'cursor' => $latestId,
                    'csrf_token' => $csrfToken,
                    'timeout' => true,
                ];
                break;
            }

            usleep(800000);
        } while (true);
    } finally {
        unset($loadUnreadNotifications);
        $pdo = null;
        $database->cerrar();
    }
    if ($clientDisconnected) {
        return;
    }
    $respond($responseBody);
} catch (Throwable $error) {
    error_log('Error en long polling de notificaciones: ' . $error->getMessage());
    $respond(['success' => false, 'error' => 'No se pudo completar la solicitud.'], 500);
}
