<?php
namespace Usuario\ProyectoCasalaiCa\Config;

use PDO;
use PDOException;

class BD {
    protected $pdo = null;
    private $config = [];

    public function __construct($tipo = 'P') {
        $this->loadConfig($tipo);
        
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Error de conexión: " . $e->getMessage());
        }
    }
    
    private function loadConfig($tipo) {
        $mainConfig = [
            'host' => 'localhost',
            'dbname' => 'casalai_principal',
            'user' => 'root',
            'pass' => '',
            'charset' => 'utf8mb4'
        ];
        
        $securityConfig = [
            'host' => 'localhost',
            'dbname' => 'casalai_seguridad',
            'user' => 'root',
            'pass' => '',
            'charset' => 'utf8mb4'
        ];
        
        $this->config = ($tipo === 'S') ? $securityConfig : $mainConfig;
    }

    public function getConexion() {
        return $this->pdo;
    }

    public function cerrar() {
        $this->pdo = null;
    }
}
