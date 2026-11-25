<?php
// TEMPORAL - pruebas locales con XAMPP. Restaurar credenciales de AlwaysData
// (mysql-casalai.alwaysdata.net / casalai / ****) antes de desplegar.
// Copia de producción disponible en api_hosting/Config/database.php

// Base de Datos Principal "casalai"
define('DB_PRINCIPAL', [
    'host' => 'localhost',
    'dbname' => 'casalai_principal',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8'
]);

// Base de Datos Secundaria "seguridadlai"
define('DB_SEGURIDAD', [
    'host' => 'localhost',
    'dbname' => 'casalai_seguridad',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8'
]);

date_default_timezone_set('America/Caracas');
?>
