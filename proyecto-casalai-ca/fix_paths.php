<?php
// Script para corregir rutas de archivos en el proyecto

// Directorio raíz del proyecto
$rootDir = __DIR__;

// Función para reemplazar en un archivo
function fixFilePaths($file) {
    $content = file_get_contents($file);
    
    // Patrones a buscar y sus reemplazos
    $patterns = [
        "/require.*[\"']\.\.\\/modelo\\//i" => "require __DIR__ . '/../modelo/",
        "/require.*[\"']\.\.\\/Modelo\\//i" => "require __DIR__ . '/../modelo/",
        "/require.*[\"']modelo\\//i" => "require __DIR__ . '/../modelo/",
        "/require.*[\"']Modelo\\//i" => "require __DIR__ . '/../modelo/"
    ];
    
    $originalContent = $content;
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Solo escribir si hubo cambios
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Corregido: $file\n";
    }
}

// Función para recorrer directorios
function scanDirectory($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            fixFilePaths($path);
        }
    }
}

// Iniciar el escaneo desde el directorio raíz
scanDirectory($rootDir);

echo "Proceso de corrección de rutas completado.\n";
?>
