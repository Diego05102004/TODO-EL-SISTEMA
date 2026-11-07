<?php
/**
 * Script de prueba para generar token JWT
 * 
 * Este script genera un token JWT y lo establece en una cookie
 * para probar la autenticación en los módulos protegidos.
 */

// Cargar autoload de composer
require_once 'vendor/autoload.php';

// No es necesario require_once de Auth.php porque el autoload lo cargará
use Usuario\ProyectoCasalaiCa\Config\Auth;

// Configurar headers para que la cookie se establezca correctamente
header('Content-Type: text/html; charset=utf-8');

// Generar token para usuario Administrador (ID: 1, Rol: Administrador)
try {
    $token = Auth::generateToken(1, 'Administrador');
    
    // Establecer token en cookie
    Auth::setTokenCookie($token);
    
    // Mostrar información
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Token JWT Generado</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                border-bottom: 2px solid #007bff;
                padding-bottom: 10px;
            }
            .success {
                background-color: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 4px;
                margin: 20px 0;
            }
            .token-box {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                padding: 15px;
                border-radius: 4px;
                word-break: break-all;
                font-family: monospace;
                font-size: 12px;
                margin: 20px 0;
            }
            .info {
                background-color: #e7f3ff;
                color: #004085;
                padding: 15px;
                border-radius: 4px;
                margin: 20px 0;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin: 10px 5px;
            }
            .btn:hover {
                background-color: #0056b3;
            }
            .btn-secondary {
                background-color: #6c757d;
            }
            .btn-secondary:hover {
                background-color: #545b62;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Token JWT Generado Exitosamente</h1>
            
            <div class="success">
                <strong>¡Éxito!</strong> El token JWT ha sido generado y establecido en la cookie.
            </div>
            
            <h2>Información del Token</h2>
            <ul>
                <li><strong>ID Usuario:</strong> 1</li>
                <li><strong>Rol:</strong> Administrador</li>
                <li><strong>Expiración:</strong> 1 hora desde ahora</li>
                <li><strong>Nombre Cookie:</strong> <?php echo Auth::getCookieName(); ?></li>
            </ul>
            
            <h2>Token Generado</h2>
            <div class="token-box">
                <?php echo htmlspecialchars($token); ?>
            </div>
            
            <div class="info">
                <strong>ℹ️ Información:</strong>
                <ul>
                    <li>El token ha sido almacenado en una cookie HttpOnly</li>
                    <li>Puedes copiar este token y pegarlo en <a href="https://jwt.io" target="_blank">jwt.io</a> para verificarlo</li>
                    <li>La clave secreta para verificar en jwt.io es: <code>TuClaveSecretaSuperSegura2024!CambiarEnProduccion</code></li>
                </ul>
            </div>
            
            <h2>Próximos Pasos</h2>
            <p>Ahora puedes acceder a los módulos protegidos:</p>
            
            <a href="?pagina=bitacora" class="btn">Ir a Módulo Bitácora</a>
            <a href="?pagina=login" class="btn btn-secondary">Ir a Login</a>
            <a href="test_jwt.php" class="btn btn-secondary">Regenerar Token</a>
            
            <h2>Generar Token con Otro Rol</h2>
            <p>Para probar con otros roles, modifica este archivo y cambia los parámetros:</p>
            <pre><code>// Para SuperUsuario:
$token = Auth::generateToken(1, 'SuperUsuario');

// Para Cliente:
$token = Auth::generateToken(2, 'cliente');</code></pre>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error al Generar Token</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .error {
                background-color: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 4px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>❌ Error al Generar Token</h1>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
