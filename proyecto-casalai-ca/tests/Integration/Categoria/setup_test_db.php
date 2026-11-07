<?php
// Database configuration
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'dbname' => 'casalai_test'
];

// Connect to MySQL server
$pdo = new PDO(
    "mysql:host={$config['host']}",
    $config['user'],
    $config['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

function executeSqlFile($pdo, $filePath) {
    if (!file_exists($filePath)) {
        die("SQL file not found: $filePath\n");
    }
    
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        die("Failed to read SQL file: $filePath\n");
    }
    
    // Split SQL statements by semicolon and execute each one
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                die("Error executing SQL: " . $e->getMessage() . "\nStatement: " . substr($statement, 0, 200) . "...\n");
            }
        }
    }
}

try {
    // Create database if not exists
    $pdo->exec("DROP DATABASE IF EXISTS `{$config['dbname']}`");
    $pdo->exec("CREATE DATABASE `{$config['dbname']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config['dbname']}`");

    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Get the project root directory
    $projectRoot = dirname(__DIR__, 4);
    
    // Execute database schema files
    $schemaFiles = [
        $projectRoot . '/database/schema.sql',
        $projectRoot . '/database/migrations/*.sql'
    ];
    
    $tablesCreated = [];
    
    foreach ($schemaFiles as $pattern) {
        $files = glob($pattern);
        if ($files === false) {
            continue;
        }
        
        foreach ($files as $file) {
            executeSqlFile($pdo, $file);
            $tablesCreated[] = basename($file);
        }
    }
    
    // If no schema files were found, create a minimal schema
    if (empty($tablesCreated)) {
        // Create tbl_categoria table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `tbl_categoria` (
                `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
                `nombre_categoria` varchar(100) NOT NULL,
                `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_categoria`),
                UNIQUE KEY `nombre_categoria` (`nombre_categoria`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $tablesCreated[] = 'tbl_categoria';
        
        // Create a sample category table for testing
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `cat_test_category` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_producto` int(11) NOT NULL,
                `color` varchar(50) DEFAULT NULL,
                `tamanio` varchar(20) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `id_producto` (`id_producto`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $tablesCreated[] = 'cat_test_category';
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Test database setup completed successfully!\n";
    echo "Database: {$config['dbname']}\n";
    echo "Tables created: " . implode(', ', $tablesCreated) . "\n";
} catch (PDOException $e) {
    die("Error setting up test database: " . $e->getMessage() . "\n");
}
?>
