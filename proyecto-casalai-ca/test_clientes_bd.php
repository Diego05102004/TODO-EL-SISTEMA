<?php
/**
 * Prueba de encriptación/desencriptación de clientes desde la base de datos
 * 
 * Este archivo verifica si los datos de los clientes en la base de datos
 * pueden desencriptarse correctamente y si hay problemas con la longitud
 * de los campos en la base de datos.
 */

require_once __DIR__ . '/Modelo/Config/loadEnv.php';
require_once __DIR__ . '/Modelo/Config/Encryption.php';
require_once __DIR__ . '/Modelo/Config/BD.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;

echo "<h1>Prueba de Encriptación/Desencriptación de Clientes (Base de Datos)</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

try {
    // Crear instancia de encriptación
    $encryption = new Encryption();
    echo "<div class='success'>✓ Instancia de Encryption creada</div>";
    
    // Conectar a la base de datos
    $bd = new BD();
    $conexion = $bd->getConexion();
    echo "<div class='success'>✓ Conexión a BD establecida</div>";
    
    // Verificar estructura de la tabla tbl_clientes
    echo "<h2>Estructura de la tabla tbl_clientes</h2>";
    $query = "DESCRIBE tbl_clientes";
    $stmt = $conexion->query($query);
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columnas as $columna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($columna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($columna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($columna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($columna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($columna['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($columna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Obtener todos los clientes
    echo "<h2>Clientes en la base de datos</h2>";
    $query = "SELECT id_clientes, nombre, cedula, telefono, direccion, correo FROM tbl_clientes";
    $stmt = $conexion->query($query);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Total de clientes: " . count($clientes) . "</div>";
    
    if (count($clientes) === 0) {
        echo "<div class='warning'>No hay clientes en la base de datos</div>";
        exit;
    }
    
    // Campos a desencriptar
    $camposACifrar = ['nombre', 'direccion', 'telefono', 'correo'];
    
    echo "<h2>Verificación de desencriptación</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Campo</th><th>Longitud BD</th><th>¿Cifrado?</th><th>Desencriptado</th><th>Estado</th></tr>";
    
    $errores = [];
    $exitosos = 0;
    $fallidos = 0;
    
    foreach ($clientes as $cliente) {
        $id = $cliente['id_clientes'];
        
        foreach ($camposACifrar as $campo) {
            $valor = $cliente[$campo];
            $longitud = strlen($valor);
            
            // Intentar desencriptar
            try {
                $desencriptado = $encryption->decrypt($valor);
                $esCifrado = ($desencriptado !== $valor);
                
                if ($esCifrado) {
                    $longitudDesencriptado = strlen($desencriptado);
                    echo "<tr>";
                    echo "<td>$id</td>";
                    echo "<td>$campo</td>";
                    echo "<td>$longitud</td>";
                    echo "<td class='success'>Sí</td>";
                    echo "<td>" . htmlspecialchars(substr($desencriptado, 0, 30)) . "...</td>";
                    echo "<td class='success'>✓ OK ($longitudDesencriptado chars)</td>";
                    echo "</tr>";
                    $exitosos++;
                } else {
                    echo "<tr>";
                    echo "<td>$id</td>";
                    echo "<td>$campo</td>";
                    echo "<td>$longitud</td>";
                    echo "<td class='warning'>No</td>";
                    echo "<td>" . htmlspecialchars(substr($valor, 0, 30)) . "...</td>";
                    echo "<td class='warning'>No cifrado (dato plano)</td>";
                    echo "</tr>";
                }
            } catch (Exception $e) {
                echo "<tr>";
                echo "<td>$id</td>";
                echo "<td>$campo</td>";
                echo "<td>$longitud</td>";
                echo "<td class='error'>Error</td>";
                echo "<td>-</td>";
                echo "<td class='error'>" . htmlspecialchars($e->getMessage()) . "</td>";
                echo "</tr>";
                $errores[] = [
                    'id' => $id,
                    'campo' => $campo,
                    'error' => $e->getMessage(),
                    'longitud' => $longitud
                ];
                $fallidos++;
            }
        }
    }
    
    echo "</table>";
    
    echo "<h2>Resumen</h2>";
    echo "<div class='info'>Total de campos verificados: " . (count($clientes) * count($camposACifrar)) . "</div>";
    echo "<div class='success'>Desencriptados exitosamente: $exitosos</div>";
    echo "<div class='error'>Errores de desencriptación: $fallidos</div>";
    
    if (!empty($errores)) {
        echo "<h2>Detalle de errores</h2>";
        echo "<table>";
        echo "<tr><th>ID Cliente</th><th>Campo</th><th>Longitud</th><th>Error</th></tr>";
        foreach ($errores as $error) {
            echo "<tr>";
            echo "<td>" . $error['id'] . "</td>";
            echo "<td>" . $error['campo'] . "</td>";
            echo "<td>" . $error['longitud'] . "</td>";
            echo "<td class='error'>" . htmlspecialchars($error['error']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>Análisis de errores</h2>";
        echo "<p>Los errores anteriores indican problemas con:</p>";
        echo "<ul>";
        echo "<li>Longitud insuficiente en el campo de la base de datos</li>";
        echo "<li>Datos corruptos o en formato incorrecto</li>";
        echo "<li>Compatibilidad con el formato de cifrado anterior</li>";
        echo "</ul>";
        
        echo "<h3>Recomendaciones:</h3>";
        echo "<ol>";
        echo "<li>Verificar que los campos en la BD tengan suficiente longitud (mínimo 500 caracteres para campos cifrados)</li>";
        echo "<li>Considerar migrar datos antiguos al nuevo formato de cifrado</li>";
        echo "<li>Implementar un mecanismo de compatibilidad para datos en formato antiguo</li>";
        echo "</ol>";
    } else {
        echo "<div class='success'><h3>✓ Todos los datos se desencriptaron correctamente</h3></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><h3>Error fatal: " . $e->getMessage() . "</h3></div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
