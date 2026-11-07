<?php
// Script de prueba para verificar la conexión a la base de datos de seguridad
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Prueba de conexión a base de datos de seguridad</h1>";

try {
    $host = '127.0.0.1';
    $dbname = 'casalai_seguridad';
    $username = 'root';
    $password = '';
    
    echo "<p>Intentando conectar a: $host/$dbname</p>";
    
    // Primero conectar sin especificar la base de datos para verificar si existe
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Conexión exitosa al servidor MySQL</p>";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "<p>✅ La base de datos '$dbname' existe</p>";
        
        // Conectar a la base de datos específica
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar si la tabla seguridad_ip existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'seguridad_ip'");
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "<p>✅ La tabla 'seguridad_ip' existe</p>";
            
            // Mostrar estructura de la tabla
            echo "<h2>Estructura de la tabla seguridad_ip:</h2>";
            $stmt = $pdo->query("DESCRIBE seguridad_ip");
            echo "<table border='1'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>{$row['Field']}</td>";
                echo "<td>{$row['Type']}</td>";
                echo "<td>{$row['Null']}</td>";
                echo "<td>{$row['Key']}</td>";
                echo "<td>{$row['Default']}</td>";
                echo "<td>{$row['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Mostrar registros existentes
            echo "<h2>Registros existentes en seguridad_ip:</h2>";
            $stmt = $pdo->query("SELECT * FROM seguridad_ip");
            $rows = $stmt->fetchAll();
            
            if (count($rows) > 0) {
                echo "<p>Se encontraron " . count($rows) . " registros:</p>";
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>IP</th><th>Username</th><th>Peticiones</th><th>Sospechosas</th><th>Bloqueado</th><th>Nivel Riesgo</th></tr>";
                foreach ($rows as $row) {
                    echo "<tr>";
                    echo "<td>{$row['id_seguridad_ip']}</td>";
                    echo "<td>{$row['direccion_ip']}</td>";
                    echo "<td>{$row['username']}</td>";
                    echo "<td>{$row['peticiones_totales']}</td>";
                    echo "<td>{$row['peticiones_sospechosas']}</td>";
                    echo "<td>{$row['esta_bloqueado']}</td>";
                    echo "<td>{$row['nivel_riesgo']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>⚠️ No hay registros en la tabla</p>";
            }
            
            // Intentar insertar un registro de prueba
            echo "<h2>Intentando insertar un registro de prueba:</h2>";
            $testIP = '192.168.1.100';
            $testUsername = 'test_user';
            $now = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("
                INSERT INTO seguridad_ip 
                (direccion_ip, username, tipo_bloqueo, peticiones_totales, peticiones_sospechosas, 
                 fecha_ultima_peticion, esta_bloqueado, nivel_riesgo, agente_usuario)
                VALUES 
                (:direccion_ip, :username, 'ip', 1, 1, :fecha_ultima_peticion, 0, 'bajo', 'Test Script')
            ");
            $stmt->execute([
                ':direccion_ip' => $testIP,
                ':username' => $testUsername,
                ':fecha_ultima_peticion' => $now
            ]);
            
            echo "<p>✅ Registro de prueba insertado exitosamente</p>";
            
            // Verificar el registro insertado
            $stmt = $pdo->prepare("SELECT * FROM seguridad_ip WHERE direccion_ip = :ip");
            $stmt->execute([':ip' => $testIP]);
            $inserted = $stmt->fetch();
            
            if ($inserted) {
                echo "<p>✅ Registro verificado en la base de datos</p>";
                echo "<pre>" . print_r($inserted, true) . "</pre>";
                
                // Eliminar el registro de prueba
                $stmt = $pdo->prepare("DELETE FROM seguridad_ip WHERE direccion_ip = :ip");
                $stmt->execute([':ip' => $testIP]);
                echo "<p>✅ Registro de prueba eliminado</p>";
            }
            
        } else {
            echo "<p>❌ La tabla 'seguridad_ip' NO existe</p>";
            echo "<p>Debe ejecutar el script SQL para crear la tabla: assets/BD/casalai_seguridad.sql</p>";
        }
        
    } else {
        echo "<p>❌ La base de datos '$dbname' NO existe</p>";
        echo "<p>Debe crear la base de datos primero ejecutando el script SQL: assets/BD/casalai_seguridad.sql</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
