<?php
/**
 * Script de debug para probar login localmente
 * Ejecuta este archivo para verificar si el login funciona correctamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/auth.php';
require_once __DIR__ . '/Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php';

echo "<h1>Debug de Login - Casa Lai</h1>";

// Test 1: Verificar conexión a base de datos
echo "<h2>Test 1: Conexión a Base de Datos</h2>";
try {
    $login = new Login();
    $pdo = $login->getConexion();
    if ($pdo) {
        echo "✅ Conexión exitosa<br>";
        
        // Verificar usuarios existentes
        $stmt = $pdo->query("SELECT id_usuario, username, correo, estatus, intentos_fallidos FROM tbl_usuarios LIMIT 5");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<br>Usuarios en base de datos:<br>";
        echo "<pre>";
        print_r($usuarios);
        echo "</pre>";
    } else {
        echo "❌ Error de conexión<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Probar validación de datos
echo "<h2>Test 2: Validación de Datos</h2>";
$login = new Login();

// Test con datos válidos
$datosValidos = [
    'username' => 'testuser',
    'password' => 'Test123'
];
$errores = $login->validarInicioSesionDatos($datosValidos);
echo "Datos válidos (testuser, Test123):<br>";
echo "Errores: " . (empty($errores) ? "✅ Ninguno" : "❌ " . print_r($errores, true)) . "<br>";

// Test con datos inválidos
$datosInvalidos = [
    'username' => 'ab',
    'password' => '123'
];
$errores = $login->validarInicioSesionDatos($datosInvalidos);
echo "<br>Datos inválidos (ab, 123):<br>";
echo "Errores: " . (empty($errores) ? "✅ Ninguno" : "❌ " . print_r($errores, true)) . "<br>";

// Test 3: Probar login con usuario existente (si hay)
echo "<h2>Test 3: Probar Login con Usuario Existente</h2>";
try {
    $pdo = $login->getConexion();
    $stmt = $pdo->query("SELECT username FROM tbl_usuarios LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        $username = $usuario['username'];
        echo "Probando login con usuario: $username<br>";
        echo "NOTA: Necesitas proporcionar la contraseña correcta para este usuario<br>";
        echo "<form method='POST'>";
        echo "Contraseña: <input type='text' name='test_password' placeholder='Ingresa la contraseña'>";
        echo "<input type='hidden' name='test_username' value='$username'>";
        echo "<input type='submit' value='Probar Login'>";
        echo "</form>";
        
        if (isset($_POST['test_password'])) {
            $login->setUsername($_POST['test_username']);
            $login->setPassword($_POST['test_password']);
            $resultado = $login->existe();
            echo "<br>Resultado: <pre>";
            print_r($resultado);
            echo "</pre>";
        }
    } else {
        echo "❌ No hay usuarios en la base de datos. Necesitas crear uno primero.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Probar registro
echo "<h2>Test 4: Probar Registro</h2>";
echo "<form method='POST'>";
echo "Nombre Usuario: <input type='text' name='reg_nombre_usuario' value='test_user_" . time() . "'><br>";
echo "Contraseña: <input type='text' name='reg_clave' value='Test123!'><br>";
echo "Nombre: <input type='text' name='reg_nombre' value='Test'><br>";
echo "Apellido: <input type='text' name='reg_apellido' value='User'><br>";
echo "Correo: <input type='text' name='reg_correo' value='test_" . time() . "@test.com'><br>";
echo "Teléfono: <input type='text' name='reg_telefono' value='04141234567'><br>";
echo "Cédula: <input type='text' name='reg_cedula' value='12345678'><br>";
echo "Dirección: <input type='text' name='reg_direccion' value='Test Address'><br>";
echo "<input type='submit' value='Probar Registro'>";
echo "</form>";

if (isset($_POST['reg_nombre_usuario'])) {
    $datosRegistro = [
        'nombre_usuario' => $_POST['reg_nombre_usuario'],
        'clave' => $_POST['reg_clave'],
        'nombre' => $_POST['reg_nombre'],
        'apellido' => $_POST['reg_apellido'],
        'correo' => $_POST['reg_correo'],
        'telefono' => $_POST['reg_telefono'],
        'cedula' => $_POST['reg_cedula'],
        'direccion' => $_POST['reg_direccion']
    ];
    
    echo "<br>Datos de registro:<br>";
    echo "<pre>";
    print_r($datosRegistro);
    echo "</pre>";
    
    $errores = $login->validarRegistroUsuarioDatos($datosRegistro);
    if (empty($errores)) {
        echo "✅ Validación exitosa<br>";
        $resultado = $login->registrarUsuarioYCliente($datosRegistro);
        echo "Resultado del registro:<br>";
        echo "<pre>";
        print_r($resultado);
        echo "</pre>";
    } else {
        echo "❌ Errores de validación:<br>";
        echo "<pre>";
        print_r($errores);
        echo "</pre>";
    }
}

// Test 5: Verificar requisitos de contraseña
echo "<h2>Test 5: Requisitos de Contraseña</h2>";
echo "<strong>Login:</strong> Entre 6 y 15 caracteres (sin requisitos especiales)<br>";
echo "<strong>Registro:</strong> Entre 6 y 15 caracteres, al menos una mayúscula, un número y un carácter especial<br>";
echo "<br>Esto puede causar problemas si la app móvil no cumple con los requisitos de registro.<br>";

// Test 6: Generar token JWT
echo "<h2>Test 6: Generación de Token JWT</h2>";
try {
    $userData = [
        'id_usuario' => 1,
        'username' => 'testuser',
        'nombre_rol' => 'Cliente',
        'id_rol' => 3,
        'cedula' => '12345678',
        'foto_perfil' => ''
    ];
    $token = Auth::generateToken(1, $userData);
    echo "✅ Token generado exitosamente<br>";
    echo "Token (primeros 50 caracteres): " . substr($token, 0, 50) . "...<br>";
    
    // Verificar token
    $payload = Auth::verifyToken($token);
    if ($payload) {
        echo "✅ Token verificado exitosamente<br>";
        echo "Payload:<br>";
        echo "<pre>";
        print_r($payload);
        echo "</pre>";
    } else {
        echo "❌ Error al verificar token<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
