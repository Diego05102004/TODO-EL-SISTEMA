<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se puede ejecutar desde PHP CLI.\n");
}

require_once dirname(__DIR__, 2) . '/Modelo/Config/database.php';

const SEED_TABLE = 'tbl_analitica_seed_facturas';
const WEEKS_TO_SEED = 24;
const PRODUCTS_TO_SEED = 6;

function abortSeed(string $message, int $status = 1): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($status);
}

function tableExists(PDO $pdo): bool
{
    $statement = $pdo->query("SHOW TABLES LIKE '" . SEED_TABLE . "'");
    return $statement !== false && $statement->fetchColumn() !== false;
}

function ensureSeedTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . SEED_TABLE . '` (
            `id_factura` INT NOT NULL,
            `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_factura`),
            CONSTRAINT `fk_analitica_seed_factura`
                FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function printUsage(): never
{
    echo "Generador local de historial sintético para analítica predictiva\n\n";
    echo "Vista previa: php seed_historial_local.php\n";
    echo "Insertar:     php seed_historial_local.php --apply\n";
    echo "Productos:    php seed_historial_local.php --apply --products=28,29,30,31,32,33\n";
    echo "Eliminar:     php seed_historial_local.php --remove\n\n";
    echo "La vista previa no modifica la base de datos. --apply crea " .
        (WEEKS_TO_SEED * PRODUCTS_TO_SEED) . " facturas sintéticas.";
    exit(0);
}

$options = getopt('', ['apply', 'remove', 'products:', 'help']);
if (isset($options['help'])) {
    printUsage();
}
if (isset($options['apply']) && isset($options['remove'])) {
    abortSeed('Use --apply o --remove, no ambos.');
}

$database = DB_PRINCIPAL;
if (!in_array(strtolower((string)$database['host']), ['localhost', '127.0.0.1'], true) ||
    ($database['dbname'] ?? '') !== 'casalai_principal') {
    abortSeed('Protección: este script solo acepta localhost y la base casalai_principal.');
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $database['host'],
        $database['dbname'],
        $database['charset']
    );
    $pdo = new PDO($dsn, $database['user'], $database['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if (isset($options['remove'])) {
        if (!tableExists($pdo)) {
            echo "No existe el registro de semillas; no hay facturas sintéticas que eliminar.\n";
            exit(0);
        }

        $count = (int)$pdo->query('SELECT COUNT(*) FROM `' . SEED_TABLE . '`')->fetchColumn();
        if ($count === 0) {
            echo "No hay facturas sintéticas registradas para eliminar.\n";
            exit(0);
        }

        $pdo->beginTransaction();
        $delete = $pdo->exec(
            'DELETE FROM `tbl_facturas`
             WHERE `id_factura` IN (SELECT `id_factura` FROM `' . SEED_TABLE . '`)'
        );
        $pdo->commit();
        echo "Eliminadas {$delete} facturas sintéticas registradas por este script.\n";
        exit(0);
    }

    $requestedProducts = null;
    if (isset($options['products'])) {
        $rawIds = explode(',', (string)$options['products']);
        if (count($rawIds) !== PRODUCTS_TO_SEED) {
            abortSeed('Indique exactamente seis IDs con --products=id1,id2,id3,id4,id5,id6.');
        }
        foreach ($rawIds as $rawId) {
            if (!ctype_digit($rawId) || (int)$rawId < 1) {
                abortSeed('La lista --products solo acepta IDs numéricos positivos.');
            }
        }
        $requestedProducts = array_values(array_unique(array_map('intval', $rawIds)));
        if (count($requestedProducts) !== PRODUCTS_TO_SEED) {
            abortSeed('Los seis IDs de producto deben ser distintos.');
        }
    }

    if ($requestedProducts === null) {
        $productStatement = $pdo->prepare(
            "SELECT id_producto
             FROM tbl_productos
             WHERE estado = 'habilitado'
             ORDER BY id_producto
             LIMIT " . PRODUCTS_TO_SEED
        );
        $productStatement->execute();
        $productIds = array_map('intval', $productStatement->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $placeholders = implode(',', array_fill(0, count($requestedProducts), '?'));
        $productStatement = $pdo->prepare(
            "SELECT id_producto
             FROM tbl_productos
             WHERE estado = 'habilitado' AND id_producto IN ({$placeholders})
             ORDER BY id_producto"
        );
        $productStatement->execute($requestedProducts);
        $productIds = array_map('intval', $productStatement->fetchAll(PDO::FETCH_COLUMN));
    }

    if (count($productIds) !== PRODUCTS_TO_SEED) {
        abortSeed('Se requieren seis productos distintos y habilitados en tbl_productos.');
    }

    $clientStatement = $pdo->query(
        'SELECT id_clientes FROM tbl_clientes WHERE activo = 1 ORDER BY id_clientes'
    );
    $clientIds = array_map('intval', $clientStatement->fetchAll(PDO::FETCH_COLUMN));
    if ($clientIds === []) {
        abortSeed('No hay clientes activos en tbl_clientes para asociar a las facturas.');
    }

    if (tableExists($pdo)) {
        $existingSeeds = (int)$pdo->query(
            'SELECT COUNT(*) FROM `' . SEED_TABLE . '`'
        )->fetchColumn();
        if ($existingSeeds > 0) {
            abortSeed(
                "Ya hay {$existingSeeds} facturas sintéticas registradas. " .
                'Use --remove antes de volver a generar el conjunto.'
            );
        }
    }

    $currentMonday = new DateTimeImmutable('monday this week');
    $lastCompletedMonday = $currentMonday->modify('-1 week');
    $firstMonday = $lastCompletedMonday->modify('-' . (WEEKS_TO_SEED - 1) . ' weeks');
    $lastSunday = $lastCompletedMonday->modify('+6 days');
    $invoiceCount = WEEKS_TO_SEED * count($productIds);

    echo "Base: {$database['dbname']} en {$database['host']}\n";
    echo 'Productos habilitados: ' . count($productIds) . PHP_EOL;
    echo 'Clientes activos disponibles: ' . count($clientIds) . PHP_EOL;
    echo "Semanas completas: " . $firstMonday->format('Y-m-d') .
        ' a ' . $lastSunday->format('Y-m-d') . PHP_EOL;
    echo "Facturas sintéticas: {$invoiceCount} (una por producto y semana)\n";
    echo "Estado: Pago Procesado; las facturas quedarán identificadas en " . SEED_TABLE . ".\n";

    if (!isset($options['apply'])) {
        echo "\nVista previa solamente. Añada --apply para insertar los registros.\n";
        exit(0);
    }

    ensureSeedTable($pdo);
    $pdo->beginTransaction();

    $invoiceInsert = $pdo->prepare(
        "INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus)
         VALUES (:fecha, :cliente, 0, 'Pago Procesado')"
    );
    $detailInsert = $pdo->prepare(
        'INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad)
         VALUES (:factura_id, :producto_id, :cantidad)'
    );
    $seedInsert = $pdo->prepare(
        'INSERT INTO `' . SEED_TABLE . '` (id_factura) VALUES (:factura_id)'
    );

    $invoiceIndex = 0;
    foreach ($productIds as $productIndex => $productId) {
        for ($weekIndex = 0; $weekIndex < WEEKS_TO_SEED; $weekIndex++) {
            $weekStart = $firstMonday->modify("+{$weekIndex} weeks");
            $dayOffset = ($weekIndex + $productIndex) % 6;
            $saleDate = $weekStart->modify("+{$dayOffset} days");
            $clientId = $clientIds[$invoiceIndex % count($clientIds)];
            $quantity = (($weekIndex * 3 + $productId) % 4) + 1;

            $invoiceInsert->execute([
                ':fecha' => $saleDate->format('Y-m-d'),
                ':cliente' => $clientId,
            ]);
            $invoiceId = (int)$pdo->lastInsertId();

            $detailInsert->execute([
                ':factura_id' => $invoiceId,
                ':producto_id' => $productId,
                ':cantidad' => $quantity,
            ]);
            $seedInsert->execute([':factura_id' => $invoiceId]);
            $invoiceIndex++;
        }
    }

    $pdo->commit();
    echo "Insertadas {$invoiceIndex} facturas y sus detalles en una transacción.\n";
    echo "Cada uno de los seis productos tendrá ventas en las 24 semanas completas.\n";
    echo "Para quitarlas sin afectar otras facturas: php seed_historial_local.php --remove\n";
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    abortSeed('Error al preparar el historial sintético: ' . $error->getMessage());
}
