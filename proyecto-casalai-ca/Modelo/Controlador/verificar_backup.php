<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si el directorio de respaldos existe y tiene permisos de escritura
$backupDir = __DIR__ . '/../db/backup';

// Intentar crear el directorio si no existe
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        die("Error: No se pudo crear el directorio de respaldos: $backupDir");
    }
    echo "Directorio de respaldos creado exitosamente: $backupDir<br>";
}

// Verificar permisos de escritura
if (!is_writable($backupDir)) {
    die("Error: El directorio de respaldos no tiene permisos de escritura: $backupDir");
}

echo "El directorio de respaldos está correctamente configurado: $backupDir<br>";

// Verificar si la clase Backup existe
echo "Verificando la clase Backup...<br>";
$backupFile = __DIR__ . '/../Usuario/ProyectoCasalaiCa/Clases/backup.php';
if (!file_exists($backupFile)) {
    die("Error: No se encontró el archivo de la clase Backup: $backupFile");
}

// Incluir la clase Backup
require_once $backupFile;

// Verificar si la clase Backup existe
echo "Clase Backup cargada correctamente<br>";

// Verificar si se puede crear una instancia de Backup
try {
    $backup = new Backup('P');
    echo "Instancia de Backup creada correctamente<br>";
    
    // Verificar si el comando mysqldump está disponible
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
    } else {
        echo "Advertencia: No se encontró mysqldump en la ruta esperada: $mysqldumpPath<br>";
        echo "Por favor, asegúrate de que MySQL esté instalado y la ruta a mysqldump sea correcta.<br>";
    }
    
} catch (Exception $e) {
    die("Error al crear instancia de Backup: " . $e->getMessage());
}

echo "<h3>Verificación completada. Si ves este mensaje, la configuración básica parece correcta.</h3>";
?>
