<?php

// Prevent multiple inclusions
if (!defined('DB_PRINCIPAL_DEFINED')) {
    // Test database configuration
    define('DB_PRINCIPAL', [
        'host' => 'localhost',
        'dbname' => 'casalai_test',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ]);
    
    define('DB_PRINCIPAL_DEFINED', true);
    
    // Include the main configuration file after defining our constants
    require_once __DIR__ . '/../../../Config/Config.php';
}
