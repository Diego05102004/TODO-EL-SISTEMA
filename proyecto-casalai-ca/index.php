<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Cargar configuración de rutas
// Cargar configuración de rutas
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Modelo/Config/paths.php';

// Cargar variables de entorno desde archivo .env
require_once __DIR__ . '/Modelo/Config/loadEnv.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Página por defecto
$pagina = "catalogo"; 

// Obtener la página solicitada
if (!empty($_GET['pagina'])) { 
    $pagina = $_GET['pagina'];  
}

// Verificar sesión de usuario
if (file_exists(MODEL_PATH . "/validalogin.php")) {
    require_once MODEL_PATH . "/validalogin.php";
    $v = new validalogin();
    
    if ($pagina == 'cerrar') {
        $v->destruyesesion();
        header('Location: ' . ROOT_PATH);
        exit;
    } else {
        $name = $v->leesesion();
    }
}

// Manejo especial para recuperación de contraseña
if ($pagina == 'password-recovery') {
    $action = $_GET['action'] ?? 'show_form';
    
    try {
        loadController('PasswordRecovery');
        $controller = new PasswordRecoveryController();
        
        switch ($action) {
            case 'request':
                $controller->procesarSolicitud();
                break;
            case 'reset':
                $controller->procesarReseteo();
                break;
            case 'show_reset_form':
                $controller->mostrarFormularioReseteo();
                break;
            default:
                $controller->mostrarFormularioRecuperacion();
                break;
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
    exit;
} 
// Cargar controlador solicitado
else {
    $controllerFile = CONTROLLER_PATH . "/{$pagina}.php";
    
    if (file_exists($controllerFile)) {
        try {
            require_once $controllerFile;
        } catch (Exception $e) {
            die("Error al cargar el controlador: " . $e->getMessage());
        }
    } else {
        // Mostrar página de error 404 o redirigir
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Página no encontrada</h1>";
        echo "<p>La página que estás buscando no existe.</p>";
        exit;
    }
}