<?php
// Configuración de rutas del sistema
define('ROOT_PATH', dirname(__DIR__));

define('MODEL_PATH', ROOT_PATH . '/Modelo');
define('CONTROLLER_PATH', ROOT_PATH . '/Controlador');
define('VIEW_PATH', ROOT_PATH . '/Vista');
define('CONFIG_PATH', ROOT_PATH);

// Función para cargar clases del modelo
function loadModel($model) {
    $modelFile = MODEL_PATH . "/{$model}.php";
    if (file_exists($modelFile)) {
        require_once $modelFile;
    } else {
        // Intentar con la primera letra en minúscula si no se encuentra
        $modelFile = strtolower(substr($model, 0, 1)) . substr($model, 1);
        $modelFile = MODEL_PATH . "/{$modelFile}.php";
        if (file_exists($modelFile)) {
            require_once $modelFile;
        } else {
            throw new Exception("No se pudo cargar el modelo: {$model}");
        }
    }
}

// Función para cargar controladores
function loadController($controller) {
    $controllerFile = CONTROLLER_PATH . "/{$controller}.php";
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
    } else {
        // Intentar con la primera letra en mayúscula si no se encuentra
        $controllerFile = ucfirst($controller);
        $controllerFile = CONTROLLER_PATH . "/{$controllerFile}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        } else {
            throw new Exception("No se pudo cargar el controlador: {$controller}");
        }
    }
}

// Cargar archivos de configuración
require_once CONFIG_PATH . '/Config/Config.php';
require_once CONFIG_PATH . '/Config/database.php';
?>
