<?php
/**
 * Script simple para probar la API en el host
 * Sube este archivo a la carpeta api/ en tu hosting y accede a: https://casalai.infinityfree.me/api/test_simple.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Simple de API - Casa Lai</h1>";

// Test 1: Verificar si los archivos necesarios existen
echo "<h2>Test 1: Verificar Archivos</h2>";
$archivos = [
    __DIR__ . '/config.php',
    __DIR__ . '/auth.php',
    __DIR__ . '/login.php',
    __DIR__ . '/registro.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../Modelo/Config/Config.php',
    __DIR__ . '/../Modelo/Config/database.php',
    __DIR__ . '/../Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php'
];

foreach ($archivos as $archivo) {
    $existe = file_exists($archivo);
    $nombre = basename($archivo);
    echo $existe ? "✅ $nombre existe<br>" : "❌ $nombre NO existe<br>";
}

// Test 2: Verificar conexión a base de datos
echo "<h2>Test 2: Conexión a Base de Datos</h2>";
try {
    require_once __DIR__ . '/../Modelo/Config/Config.php';
    $bd = new \Usuario\ProyectoCasalaiCa\Config\BD('S');
    $pdo = $bd->getConexion();
    
    if ($pdo) {
        echo "✅ Conexión a base de datos exitosa<br>";
        
        // Verificar si hay usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_usuarios");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de usuarios: " . $result['total'] . "<br>";
        
        if ($result['total'] > 0) {
            echo "<br>Usuarios existentes (sin passwords):<br>";
            $stmt = $pdo->query("SELECT id_usuario, username, correo, estatus FROM tbl_usuarios LIMIT 5");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            print_r($usuarios);
            echo "</pre>";
        } else {
            echo "⚠️ No hay usuarios en la base de datos. Necesitas crear uno primero.<br>";
        }
    } else {
        echo "❌ No se pudo conectar a la base de datos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Probar validación de Login
echo "<h2>Test 3: Validación de Login</h2>";
try {
    require_once __DIR__ . '/../Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php';
    $login = new Login();
    
    $datosTest = [
        'username' => 'testuser',
        'password' => 'Test123'
    ];
    
    $errores = $login->validarInicioSesionDatos($datosTest);
    echo "Datos de prueba: username='testuser', password='Test123'<br>";
    echo "Errores de validación: " . (empty($errores) ? "✅ Ninguno" : "❌ " . print_r($errores, true)) . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Probar generación de token JWT
echo "<h2>Test 4: Generación de Token JWT</h2>";
try {
    require_once __DIR__ . '/auth.php';
    
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
    } else {
        echo "❌ Error al verificar token<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Probar endpoint de login directamente
echo "<h2>Test 5: Probar Endpoint de Login</h2>";
echo "<form method='POST'>";
echo "Username/Email: <input type='text' name='test_username' placeholder='Ingresa username o email'><br>";
echo "Password: <input type='text' name='test_password' placeholder='Ingresa password'><br>";
echo "<input type='submit' value='Probar Login'>";
echo "</form>";

if (isset($_POST['test_username']) && isset($_POST['test_password'])) {
    try {
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/auth.php';
        require_once __DIR__ . '/../Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php';
        
        $usernameOrEmail = $_POST['test_username'];
        $password = $_POST['test_password'];
        
        echo "<br>Intentando login con: $usernameOrEmail<br>";
        
        $login = new Login();
        $login->setUsername($usernameOrEmail);
        $login->setPassword($password);
        
        $resultado = $login->existe();
        
        echo "Resultado:<br>";
        echo "<pre>";
        print_r($resultado);
        echo "</pre>";
        
        if ($resultado['resultado'] == 'existe') {
            echo "✅ Login exitoso! Generando token...<br>";
            $userData = [
                'id_usuario' => $resultado['id_usuario'],
                'username' => $resultado['mensaje'],
                'nombre_rol' => $resultado['nombre_rol'],
                'id_rol' => $resultado['id_rol'],
                'cedula' => $resultado['cedula'],
                'foto_perfil' => $resultado['foto_perfil']
            ];
            $token = Auth::generateToken($resultado['id_usuario'], $userData);
            echo "Token generado: " . substr($token, 0, 50) . "...<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
        echo "Stack trace:<br>";
        echo "<pre>";
        echo $e->getTraceAsString();
        echo "</pre>";
    }
}

// Test 6: Probar endpoint de registro
echo "<h2>Test 6: Probar Endpoint de Registro</h2>";
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
    try {
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/auth.php';
        require_once __DIR__ . '/../Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php';
        
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
        
        $login = new Login();
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
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
        echo "Stack trace:<br>";
        echo "<pre>";
        echo $e->getTraceAsString();
        echo "</pre>";
    }
}

echo "<hr>";
echo "<h2>Recomendaciones:</h2>";
echo "<ul>";
echo "<li>Si el Test 2 falla, el problema es la conexión a base de datos - verifica las credenciales en database.php</li>";
echo "<li>Si el Test 5 falla con 'noexiste', verifica que el usuario exista en la base de datos</li>";
echo "<li>Si el Test 5 falla con 'bloqueado', el usuario tiene 3 intentos fallidos - necesitas reiniciarlos</li>";
echo "<li>Si el Test 6 falla, verifica que la contraseña cumpla: 6-15 caracteres, mayúscula, número y carácter especial</li>";
echo "<li>Sube este archivo a la carpeta api/ en tu hosting y accede a: https://casalai.infinityfree.me/api/test_simple.php</li>";
echo "</ul>";
