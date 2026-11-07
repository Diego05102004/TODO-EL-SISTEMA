<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Mostrar información del servidor
echo "<h2>Información del servidor</h2>";
echo "Sistema operativo: " . PHP_OS . "<br>";
echo "Versión de PHP: " . phpversion() . "<br>";
echo "Usuario del servidor web: " . get_current_user() . "<br>";

// Verificar si hay sesión activa
echo "<h2>Estado de la sesión</h2>";
if (session_status() === PHP_SESSION_NONE) {
    echo "Sesión no iniciada<br>";
} else {
    echo "Sesión activa<br>";
    echo "ID de sesión: " . session_id() . "<br>";
}

// Verificar el directorio de respaldos
$backupDir = __DIR__ . '/db/backup';
echo "<h2>Verificando directorio de respaldos</h2>";
if (!file_exists($backupDir)) {
    echo "El directorio de respaldos no existe: $backupDir<br>";
    echo "Intentando crear el directorio...<br>";
    if (!mkdir($backupDir, 0777, true)) {
        echo "<span style='color:red'>Error: No se pudo crear el directorio de respaldos. Verifica los permisos.</span><br>";
    } else {
        echo "<span style='color:green'>Directorio creado exitosamente: $backupDir</span><br>";
    }
} else {
    echo "El directorio de respaldos existe: $backupDir<br>";
}

// Verificar permisos del directorio
if (is_writable($backupDir)) {
    echo "El directorio tiene permisos de escritura<br>";
} else {
    echo "<span style='color:red'>ADVERTENCIA: El directorio no tiene permisos de escritura</span><br>";
}

// Verificar si la clase Backup existe
echo "<h2>Verificando clase Backup</h2>";
$backupFile = __DIR__ . '/Modelo/Usuario/ProyectoCasalaiCa/Clases/backup.php';
if (!file_exists($backupFile)) {
    echo "<span style='color:red'>Error: No se encontró el archivo de la clase Backup: $backupFile</span><br>";
} else {
    echo "Archivo de la clase Backup encontrado: $backupFile<br>";
    
    // Leer las primeras líneas del archivo para verificar el namespace
    $lines = file($backupFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $namespace = '';
    foreach ($lines as $line) {
        if (strpos($line, 'namespace') === 0) {
            $namespace = $line;
            break;
        }
    }
    echo "Namespace de la clase: " . htmlspecialchars($namespace) . "<br>";
    
    // Verificar si la clase se puede cargar
    try {
        require_once $backupFile;
        echo "Clase Backup cargada correctamente<br>";
        
        // Verificar si se puede crear una instancia
        try {
            $backup = new \Usuario\ProyectoCasalaiCa\Clases\Backup('P');
            echo "<span style='color:green'>Instancia de Backup creada exitosamente</span><br>";
        } catch (Exception $e) {
            echo "<span style='color:red'>Error al crear instancia de Backup: " . $e->getMessage() . "</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>Error al cargar la clase Backup: " . $e->getMessage() . "</span><br>";
    }
}

// Verificar si mysqldump está disponible
echo "<h2>Verificando mysqldump</h2>";
$mysqldumpPath = '';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
} else {
    // Linux/Unix
    $mysqldumpPath = '/usr/bin/mysqldump';
}

if (file_exists($mysqldumpPath)) {
    echo "mysqldump encontrado en: $mysqldumpPath<br>";
    
    // Verificar versión de mysqldump
    $output = [];
    $return_var = 0;
    exec("$mysqldump --version", $output, $return_var);
    
    if ($return_var === 0 && !empty($output[0])) {
        echo "Versión de mysqldump: " . $output[0] . "<br>";
    } else {
        echo "<span style='color:orange'>No se pudo obtener la versión de mysqldump</span><br>";
    }
} else {
    echo "<span style='color:red'>Advertencia: No se encontró mysqldump en la ruta esperada: $mysqldumpPath</span><br>";
    echo "Por favor, asegúrate de que MySQL esté instalado y la ruta a mysqldump sea correcta.<br>";
}

// Verificar configuración de PHP
echo "<h2>Configuración de PHP</h2>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";

// Verificar si hay errores en el log de PHP
$errorLog = ini_get('error_log');
echo "<h2>Log de errores</h2>";
if (file_exists($errorLog)) {
    echo "Archivo de log: $errorLog<br>";
    $lastLines = `tail -n 20 "$errorLog"`;
    echo "<pre>Últimas líneas del log de errores:<br>" . htmlspecialchars($lastLines) . "</pre>";
} else {
    echo "No se encontró el archivo de log de errores en: $errorLog<br>";
}

// Verificar si hay errores de inclusión
echo "<h2>Errores de inclusión</h2>";
$included = get_included_files();
$errorInclusion = [];
foreach ($included as $file) {
    if (!file_exists($file)) {
        $errorInclusion[] = "Archivo incluido no encontrado: $file";
    }
}

if (empty($errorInclusion)) {
    echo "No se encontraron errores de inclusión de archivos.<br>";
} else {
    echo "<span style='color:red'>Errores de inclusión encontrados:</span><br>";
    foreach ($errorInclusion as $error) {
        echo "- $error<br>";
    }
}

echo "<h2>Verificación completada</h2>";
?>
