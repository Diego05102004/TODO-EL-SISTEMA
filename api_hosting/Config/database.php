<?php
// Base de Datos Principal "casalai"
define('DB_PRINCIPAL', [
    'host' => 'mysql-casalai.alwaysdata.net',
    'dbname' => 'casalai_principal',
    'user' => 'casalai',
    'pass' => 'Diego.10',
    'charset' => 'utf8'
]);

// Base de Datos Secundaria "seguridadlai"
define('DB_SEGURIDAD', [
    'host' => 'mysql-casalai.alwaysdata.net',
    'dbname' => 'casalai_seguridad',
    'user' => 'casalai',
    'pass' => 'Diego.10',
    'charset' => 'utf8'
]);

date_default_timezone_set('America/Caracas');
?>
