<?php
/**
 * Prueba específica para el cifrado/descifrado de teléfono
 */

require_once __DIR__ . '/Modelo/Config/Encryption.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;

echo "<h1>Prueba de Cifrado de Teléfono</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

try {
    $encryption = new Encryption();
    
    // Prueba 1: Teléfono normal
    echo "<h2>Prueba 1: Teléfono normal</h2>";
    $telefono1 = "0414-575-3363";
    echo "<div class='info'>Original: $telefono1</div>";
    
    $cifrado1 = $encryption->encrypt($telefono1);
    echo "<div class='info'>Cifrado: " . substr($cifrado1, 0, 50) . "...</div>";
    
    $descifrado1 = $encryption->decrypt($cifrado1);
    echo "<div class='info'>Descifrado: $descifrado1</div>";
    
    if ($telefono1 === $descifrado1) {
        echo "<div class='success'>✓ Prueba 1 exitosa</div>";
    } else {
        echo "<div class='error'>✗ Prueba 1 fallida</div>";
    }
    
    // Prueba 2: Teléfono vacío
    echo "<h2>Prueba 2: Teléfono vacío</h2>";
    $telefono2 = "";
    echo "<div class='info'>Original: '$telefono2' (vacío)</div>";
    
    $cifrado2 = $encryption->encrypt($telefono2);
    echo "<div class='info'>Cifrado: '$cifrado2'</div>";
    
    $descifrado2 = $encryption->decrypt($cifrado2);
    echo "<div class='info'>Descifrado: '$descifrado2'</div>";
    
    if ($telefono2 === $descifrado2) {
        echo "<div class='success'>✓ Prueba 2 exitosa</div>";
    } else {
        echo "<div class='error'>✗ Prueba 2 fallida</div>";
    }
    
    // Prueba 3: Teléfono null
    echo "<h2>Prueba 3: Teléfono null</h2>";
    $telefono3 = null;
    echo "<div class='info'>Original: null</div>";
    
    $cifrado3 = $encryption->encrypt($telefono3);
    echo "<div class='info'>Cifrado: ";
    var_dump($cifrado3);
    echo "</div>";
    
    $descifrado3 = $encryption->decrypt($cifrado3);
    echo "<div class='info'>Descifrado: ";
    var_dump($descifrado3);
    echo "</div>";
    
    if ($telefono3 === $descifrado3) {
        echo "<div class='success'>✓ Prueba 3 exitosa</div>";
    } else {
        echo "<div class='error'>✗ Prueba 3 fallida</div>";
    }
    
    // Prueba 4: Teléfono con caracteres especiales
    echo "<h2>Prueba 4: Teléfono con caracteres especiales</h2>";
    $telefono4 = "+58 414-575-3363";
    echo "<div class='info'>Original: $telefono4</div>";
    
    $cifrado4 = $encryption->encrypt($telefono4);
    echo "<div class='info'>Cifrado: " . substr($cifrado4, 0, 50) . "...</div>";
    
    $descifrado4 = $encryption->decrypt($cifrado4);
    echo "<div class='info'>Descifrado: $descifrado4</div>";
    
    if ($telefono4 === $descifrado4) {
        echo "<div class='success'>✓ Prueba 4 exitosa</div>";
    } else {
        echo "<div class='error'>✗ Prueba 4 fallida</div>";
    }
    
    // Prueba 5: Array con teléfono
    echo "<h2>Prueba 5: Array con teléfono</h2>";
    $datos = [
        'nombre' => 'Juan Pérez',
        'telefono' => '0414-575-3363',
        'correo' => 'juan@example.com'
    ];
    
    echo "<div class='info'>Original: ";
    print_r($datos);
    echo "</div>";
    
    $campos = ['nombre', 'telefono', 'correo'];
    $cifradoArray = $encryption->encryptArray($datos, $campos);
    echo "<div class='info'>Array cifrado: ";
    print_r($cifradoArray);
    echo "</div>";
    
    $descifradoArray = $encryption->decryptArray($cifradoArray, $campos);
    echo "<div class='info'>Array descifrado: ";
    print_r($descifradoArray);
    echo "</div>";
    
    if ($datos['telefono'] === $descifradoArray['telefono']) {
        echo "<div class='success'>✓ Prueba 5 exitosa</div>";
    } else {
        echo "<div class='error'>✗ Prueba 5 fallida</div>";
    }
    
    echo "<h2>Revisar logs de error</h2>";
    echo "<div class='warning'>Revisa el archivo de error de PHP para ver los logs generados por la clase Encryption</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h3>Error: " . $e->getMessage() . "</h3></div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
