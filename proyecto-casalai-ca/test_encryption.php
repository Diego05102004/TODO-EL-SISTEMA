<?php
/**
 * Prueba de cifrado híbrido RSA+AES para el módulo de clientes
 * 
 * Este archivo prueba la funcionalidad de cifrado/descifrado híbrido
 * implementada en la clase Encryption con datos de la tabla tbl_cliente.
 */

require_once __DIR__ . '/Modelo/Config/loadEnv.php';
require_once __DIR__ . '/Modelo/Config/Encryption.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;

echo "<h1>Prueba de Cifrado Híbrido RSA+AES-256</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

try {
    echo "<h2>Verificando variables de entorno</h2>";
    $publicKey = getenv('RSA_PUBLIC_KEY');
    $privateKey = getenv('RSA_PRIVATE_KEY');
    
    if ($publicKey) {
        echo "<div class='success'>✓ RSA_PUBLIC_KEY cargada (longitud: " . strlen($publicKey) . ")</div>";
    } else {
        echo "<div class='error'>✗ RSA_PUBLIC_KEY no encontrada</div>";
    }
    
    if ($privateKey) {
        echo "<div class='success'>✓ RSA_PRIVATE_KEY cargada (longitud: " . strlen($privateKey) . ")</div>";
    } else {
        echo "<div class='error'>✗ RSA_PRIVATE_KEY no encontrada</div>";
    }
    
    echo "<h2>Creando instancia de Encryption</h2>";
    $encryption = new Encryption();
    echo "<div class='success'>✓ Instancia creada correctamente</div>";
    
    echo "<h2>Datos de prueba (tbl_cliente)</h2>";
    $datosPrueba = [
        'id_clientes' => 1,
        'nombre' => 'Juan Pérez',
        'cedula' => '25.123.456',
        'telefono' => '0414-123-4567',
        'direccion' => 'Calle 123, Barquisimeto, Estado Lara',
        'correo' => 'juan.perez@example.com'
    ];
    
    echo "<pre>";
    print_r($datosPrueba);
    echo "</pre>";
    
    echo "<h2>Cifrando campos sensibles...</h2>";
    $camposACifrar = ['nombre', 'cedula', 'telefono', 'direccion', 'correo'];
    $datosCifrados = $datosPrueba;
    $exitoso = true;
    
    foreach ($camposACifrar as $campo) {
        try {
            $datosCifrados[$campo] = $encryption->encrypt($datosPrueba[$campo]);
            echo "<div class='success'>✓ $campo cifrado (longitud: " . strlen($datosCifrados[$campo]) . ")</div>";
            echo "<div class='info'>  Preview: " . substr($datosCifrados[$campo], 0, 60) . "...</div>";
        } catch (Exception $e) {
            echo "<div class='error'>✗ $campo: " . $e->getMessage() . "</div>";
            $exitoso = false;
        }
    }
    
    if (!$exitoso) {
        echo "<div class='error'><h3>Error al cifrar datos</h3></div>";
        exit;
    }
    
    echo "<h2>Descifrando datos...</h2>";
    $datosDescifrados = $datosCifrados;
    $descifradoExitoso = true;
    
    foreach ($camposACifrar as $campo) {
        try {
            $datosDescifrados[$campo] = $encryption->decrypt($datosCifrados[$campo]);
            if ($datosDescifrados[$campo] === $datosPrueba[$campo]) {
                echo "<div class='success'>✓ $campo descifrado correctamente: " . $datosDescifrados[$campo] . "</div>";
            } else {
                echo "<div class='error'>✗ $campo: Valor descifrado no coincide</div>";
                echo "<div class='info'>  Esperado: " . $datosPrueba[$campo] . "</div>";
                echo "<div class='info'>  Obtenido: " . $datosDescifrados[$campo] . "</div>";
                $descifradoExitoso = false;
            }
        } catch (Exception $e) {
            echo "<div class='error'>✗ $campo: " . $e->getMessage() . "</div>";
            $descifradoExitoso = false;
        }
    }
    
    echo "<h2>Prueba de array completo</h2>";
    try {
        $arrayCifrado = $encryption->encryptArray($datosPrueba, $camposACifrar);
        echo "<div class='success'>✓ Array cifrado correctamente</div>";
        echo "<div class='info'>  Campos cifrados: " . implode(', ', $camposACifrar) . "</div>";
        
        $arrayDescifrado = $encryption->decryptArray($arrayCifrado, $camposACifrar);
        $arrayExitoso = true;
        foreach ($camposACifrar as $campo) {
            if ($arrayDescifrado[$campo] === $datosPrueba[$campo]) {
                echo "<div class='success'>✓ $campo en array descifrado correctamente</div>";
            } else {
                echo "<div class='error'>✗ $campo en array: Valor no coincide</div>";
                $arrayExitoso = false;
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error en prueba de array: " . $e->getMessage() . "</div>";
        $arrayExitoso = false;
    }
    
    echo "<h2>Prueba de múltiples clientes</h2>";
    $clientesPrueba = [
        [
            'id_clientes' => 1,
            'nombre' => 'Juan Pérez',
            'cedula' => '25.123.456',
            'telefono' => '0414-123-4567',
            'direccion' => 'Calle 123, Barquisimeto',
            'correo' => 'juan.perez@example.com'
        ],
        [
            'id_clientes' => 2,
            'nombre' => 'María González',
            'cedula' => '18.456.789',
            'telefono' => '0424-987-6543',
            'direccion' => 'Avenida 456, Valencia',
            'correo' => 'maria.gonzalez@example.com'
        ]
    ];
    
    try {
        $clientesCifrados = $encryption->encryptResults($clientesPrueba, $camposACifrar);
        echo "<div class='success'>✓ " . count($clientesCifrados) . " clientes cifrados</div>";
        
        $clientesDescifrados = $encryption->decryptResults($clientesCifrados, $camposACifrar);
        $resultadosExitoso = true;
        
        foreach ($clientesDescifrados as $index => $cliente) {
            foreach ($camposACifrar as $campo) {
                if ($cliente[$campo] === $clientesPrueba[$index][$campo]) {
                    echo "<div class='success'>✓ Cliente $index, $campo descifrado correctamente</div>";
                } else {
                    echo "<div class='error'>✗ Cliente $index, $campo: Valor no coincide</div>";
                    $resultadosExitoso = false;
                }
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error en prueba de múltiples clientes: " . $e->getMessage() . "</div>";
        $resultadosExitoso = false;
    }
    
    echo "<h2>Resultado final</h2>";
    if ($descifradoExitoso && $arrayExitoso && $resultadosExitoso) {
        echo "<div class='success'><h3>✓ TODAS LAS PRUEBAS EXITOSAS</h3></div>";
        echo "<p>El cifrado híbrido RSA+AES está funcionando correctamente.</p>";
        echo "<p>Los datos de la tabla tbl_cliente pueden cifrarse y descifrarse sin problemas.</p>";
    } else {
        echo "<div class='error'><h3>✗ ALGUNAS PRUEBAS FALLARON</h3></div>";
        echo "<p>Revise los errores mostrados arriba para corregir el problema.</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><h3>Error fatal: " . $e->getMessage() . "</h3></div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
