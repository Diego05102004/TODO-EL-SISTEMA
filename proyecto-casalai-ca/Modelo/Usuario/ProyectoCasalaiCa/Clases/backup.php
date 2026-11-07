<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use PDO;
use PDOException;
use RuntimeException;
use Usuario\ProyectoCasalaiCa\Config\Encryption;

class Backup {
    private $tipo;
    private $config;
    private $encryption;
    
    // Constantes para validaciones
    const MAX_NOMBRE_ARCHIVO = 255;
    const EXTENSIONES_PERMITIDAS = ['sql', 'enc']; // SQL y encriptado
    const TIPOS_BACKUP_PERMITIDOS = ['P', 'S']; // Principal, Seguridad
    const TAMANO_MAXIMO_BACKUP = 104857600; // 100MB en bytes

    public function __construct($tipo = 'P') {
        $this->tipo = $tipo;
        $this->config = $this->getDatabaseConfig();
        $this->encryption = new Encryption();
    }

    /**
     * Obtiene la configuración de la base de datos según el tipo
     */
    private function getDatabaseConfig() {
        if ($this->tipo === 'S') {
            return [
                'host' => 'localhost',
                'dbname' => 'casalai_seguridad',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4'
            ];
        } else {
            return [
                'host' => 'localhost',
                'dbname' => 'casalai_principal',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4'
            ];
        }
    }

    /**
     * Genera un respaldo de la base de datos
     */
    /**
     * Genera un respaldo de la base de datos
     * 
     * @param string $nombreArchivo Nombre del archivo de respaldo
     * @return array [
     *     'success' => bool,
     *     'message' => string,
     *     'archivo' => string,
     *     'tamano' => int,
     *     'error' => string|null,
     *     'debug' => string|null
     * ]
     */
/**
 * Genera un respaldo de la base de datos
 */
public function generar($nombreArchivo) {
    $config = $this->config;
    $resultado = [
        'success' => false,
        'message' => '',
        'archivo' => $nombreArchivo,
        'tamano' => 0,
        'error' => null
    ];
    
    // Configurar la ruta del archivo de respaldo
    $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
    
    // Generar nombre de archivo temporal .sql
    $nombreArchivoTemp = $nombreArchivo;
    if (!str_ends_with($nombreArchivoTemp, '.sql')) {
        $nombreArchivoTemp .= '.sql';
    }
    $rutaArchivoTemp = $rutaCarpeta . $nombreArchivoTemp;
    
    // Nombre final del archivo encriptado
    $nombreArchivoFinal = str_replace('.sql', '.enc', $nombreArchivoTemp);
    $resultado['archivo'] = $nombreArchivoFinal;
    $rutaArchivoFinal = $rutaCarpeta . $nombreArchivoFinal;

    // Crear directorio si no existe
    if (!is_dir($rutaCarpeta)) {
        if (!mkdir($rutaCarpeta, 0775, true)) {
            $errorMsg = "No se pudo crear el directorio de respaldo: $rutaCarpeta";
            error_log($errorMsg);
            $resultado['error'] = $errorMsg;
            $resultado['message'] = 'Error al crear el directorio de respaldos';
            return $resultado;
        }
    }
    
    // Verificar permisos de escritura
    if (!is_writable($rutaCarpeta)) {
        $errorMsg = "El directorio no tiene permisos de escritura: $rutaCarpeta";
        error_log($errorMsg);
        $resultado['error'] = $errorMsg;
        $resultado['message'] = 'Error de permisos en el directorio de respaldos';
        return $resultado;
    }
    
    // Configurar el comando mysqldump
    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    
    // Verificar si existe mysqldump
    if (!file_exists($mysqldump)) {
        $errorMsg = "No se encontró el ejecutable de mysqldump en: $mysqldump";
        error_log($errorMsg);
        $resultado['error'] = $errorMsg;
        $resultado['message'] = 'No se encontró la herramienta de respaldo (mysqldump)';
        return $resultado;
    }
    
    // Configurar opciones de mysqldump
    $opciones = [
        '--user=' . escapeshellarg($config['user']),
        '--password=' . escapeshellarg($config['pass']),
        '--host=' . escapeshellarg($config['host']),
        '--default-character-set=utf8mb4',
        '--add-drop-database',
        '--add-drop-table',
        '--add-locks',
        '--create-options',
        '--disable-keys',
        '--extended-insert',
        '--lock-tables',
        '--quick',
        '--routines',
        '--triggers',
        '--events',
        '--set-charset',
        '--single-transaction',
        '--result-file=' . escapeshellarg($rutaArchivoTemp),
        escapeshellarg($config['dbname']),
        '2>&1'
    ];
    
    // Construir el comando
    $comando = '"' . $mysqldump . '" ' . implode(' ', $opciones);
    
    // Ejecutar el comando
    $output = [];
    $exitCode = 0;
    exec($comando, $output, $exitCode);
    
    // Verificar si el archivo se creó correctamente
    $archivoExiste = file_exists($rutaArchivoTemp);
    $tamanoArchivo = $archivoExiste ? filesize($rutaArchivoTemp) : 0;
    
    if ($archivoExiste && $tamanoArchivo > 0) {
        // Agregar encabezado SQL al inicio del archivo
        $header = $this->getSqlHeader($config['dbname']);
        $currentContent = file_get_contents($rutaArchivoTemp);
        file_put_contents($rutaArchivoTemp, $header . $currentContent);
        
        // Encriptar el archivo
        try {
            $contenidoSql = file_get_contents($rutaArchivoTemp);
            $contenidoEncriptado = $this->encryption->encrypt($contenidoSql);
            file_put_contents($rutaArchivoFinal, $contenidoEncriptado);
            
            // Eliminar el archivo temporal
            unlink($rutaArchivoTemp);
            
            // Actualizar el tamaño del archivo encriptado
            $tamanoArchivo = filesize($rutaArchivoFinal);
            
            $resultado['success'] = true;
            $resultado['message'] = 'Respaldo generado y encriptado correctamente';
            $resultado['tamano'] = $tamanoArchivo;
        } catch (Exception $e) {
            $errorMsg = "Error al encriptar el respaldo: " . $e->getMessage();
            error_log($errorMsg);
            // Si falla la encriptación, mantener el archivo SQL sin encriptar
            $resultado['archivo'] = $nombreArchivoTemp;
            $resultado['success'] = true;
            $resultado['message'] = 'Respaldo generado sin encriptación (error de encriptación)';
            $resultado['tamano'] = filesize($rutaArchivoTemp);
            $resultado['error'] = $errorMsg;
        }
    } else {
        $errorMsg = $archivoExiste ? 'El archivo de respaldo está vacío' : 'No se pudo crear el archivo de respaldo';
        $resultado['error'] = $errorMsg . ' (Código: ' . $exitCode . ')';
        $resultado['message'] = 'Error al generar el respaldo';
        error_log(implode("\n", $output));
    }
    
    return $resultado;
}



/**
 * Devuelve el encabezado SQL para el respaldo
 */
private function getSqlHeader($dbName) {
    $header = "-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)\n";
    $header .= "--\n";
    $header .= "-- Host: localhost    Database: {$dbName}\n";
    $header .= "-- ------------------------------------------------------\n";
    $header .= "-- Server version\t10.4.32-MariaDB\n\n";
    $header .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $header .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $header .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $header .= "/*!40101 SET NAMES utf8mb4 */;\n";
    $header .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
    $header .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
    $header .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
    $header .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
    $header .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
    $header .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
    $header .= "--\n";
    $header .= "-- Current Database: `{$dbName}`\n";
    $header .= "--\n\n";
    $header .= "USE `{$dbName}`;\n\n";
    
    return $header;
}

/**
 * Devuelve el pie de página SQL para el respaldo
 */
private function getSqlFooter() {
    $footer = "\n--\n";
    $footer .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";
    $footer .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
    $footer .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
    $footer .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
    $footer .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
    $footer .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $footer .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $footer .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    $footer .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
    
    return $footer;
}

    /**
     * Restaura un respaldo de la base de datos
     *
     * @param string $nombreArchivo Nombre del archivo de respaldo
     * @return bool Verdadero si el respaldo se restauró correctamente, falso en caso contrario
     */
    /**
     * Restaura un respaldo de la base de datos
     */
    public function restaurar($nombreArchivo) {
        $config = $this->config;
        
        // Configurar la ruta del archivo de respaldo
        $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
        $rutaArchivo = $rutaCarpeta . $nombreArchivo;
        
        // Verificar que el archivo existe
        if (!file_exists($rutaArchivo)) {
            error_log("El archivo de respaldo no existe: $rutaArchivo");
            return false;
        }
        
        // Determinar si el archivo está encriptado
        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        $rutaArchivoSql = $rutaArchivo;
        
        if ($extension === 'enc') {
            // Desencriptar el archivo
            try {
                $contenidoEncriptado = file_get_contents($rutaArchivo);
                $contenidoSql = $this->encryption->decrypt($contenidoEncriptado);
                
                // Crear archivo temporal SQL
                $rutaArchivoSql = $rutaCarpeta . 'temp_restore_' . time() . '.sql';
                file_put_contents($rutaArchivoSql, $contenidoSql);
            } catch (Exception $e) {
                error_log("Error al desencriptar el respaldo: " . $e->getMessage());
                return false;
            }
        }
        
        // Configurar el comando mysql
        $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        
        // Construir el comando
        $comando = sprintf(
            '"%s" --user=%s --password=%s --host=%s %s < "%s" 2>&1',
            $mysql,
            escapeshellarg($config['user']),
            escapeshellarg($config['pass']),
            escapeshellarg($config['host']),
            $config['dbname'],
            $rutaArchivoSql
        );
        
        // Ejecutar el comando
        $output = [];
        $resultado = 0;
        exec($comando, $output, $resultado);
        
        // Registrar el resultado
        $logFile = $rutaCarpeta . 'backup_restore.log';
        $logMessage = '[' . date('c') . "] RESTAURAR: tipo={$this->tipo}, db={$config['dbname']}, archivo={$rutaArchivo}\n";
        $logMessage .= '[' . date('c') . "] Comando: $comando\n";
        $logMessage .= '[' . date('c') . "] Resultado: $resultado\n";
        $logMessage .= '[' . date('c') . "] Salida: " . implode("\n", $output) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Eliminar archivo temporal si se creó
        if ($extension === 'enc' && file_exists($rutaArchivoSql)) {
            unlink($rutaArchivoSql);
        }
        
        if ($resultado !== 0) {
            error_log("Error al restaurar el respaldo. Código: $resultado, Archivo: $rutaArchivo");
            return false;
        }
        
        return true;
    }
    
    public function listar() {
          $ruta = __DIR__ . '/../../../../Modelo/db/respaldo/';
        $archivos = [];
        
        // Crear el directorio si no existe
        if (!is_dir($ruta)) {
            if (!mkdir($ruta, 0775, true)) {
                error_log("No se pudo crear el directorio de respaldos: $ruta");
                return $archivos;
            }
        }
        
        $files = @scandir($ruta);
        if ($files === false) {
            error_log("No se pudo leer el directorio de respaldos: $ruta");
            return $archivos;
        }
        
        foreach ($files as $file) {
            // Mostrar tanto archivos .sql como .enc
            if (preg_match('/\.(sql|enc)$/i', $file)) {
                $filePath = $ruta . $file;
                    $fileInfo = [
                        'nombre' => $file,
                        'tamano' => $this->formatearTamano(filesize($filePath)),
                        'fecha_modificacion' => date('d/m/Y H:i:s', filemtime($filePath)),
                        'tipo' => $this->obtenerTipoBackup($file),
                        'encriptado' => (preg_match('/\.enc$/i', $file) ? true : false)
                    ];
                    $archivos[] = $fileInfo;
                }
            }
            // Ordenar por fecha de modificación (más reciente primero)
            usort($archivos, function($a, $b) {
                return strtotime($b['fecha_modificacion']) - strtotime($a['fecha_modificacion']);
            });
        
        return $archivos;
    }
    

    /**
     * Formatea el tamaño del archivo en un formato legible
     */
    private function formatearTamano($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $unidades[$i];
    }

    /**
     * Obtiene el tipo de backup basado en el nombre del archivo
     */
    private function obtenerTipoBackup($nombreArchivo) {
        $nombreArchivo = strtolower($nombreArchivo);
        if (strpos($nombreArchivo, 'seguridad') !== false) {
            return 'Seguridad';
        } elseif (strpos($nombreArchivo, 'principal') !== false) {
            return 'Principal';
        }
        return 'Desconocido';
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para generar un backup
     */
    private function validarGenerarBackup($datos) {
        $errores = [];
        
        // Validar tipo de backup
        if (isset($datos['tipo'])) {
            $tipo = strtoupper(trim($datos['tipo']));
            if (!in_array($tipo, self::TIPOS_BACKUP_PERMITIDOS)) {
                $errores['tipo'] = 'El tipo de backup debe ser P (Principal) o S (Seguridad)';
            }
        }
        
        // Validar nombre del archivo (si se proporciona)
        if (isset($datos['nombre_archivo']) && $datos['nombre_archivo'] !== '') {
            $nombreArchivo = trim($datos['nombre_archivo']);
            
            // Validar longitud del nombre
            if (mb_strlen($nombreArchivo) > self::MAX_NOMBRE_ARCHIVO) {
                $errores['nombre_archivo'] = 'El nombre del archivo no debe exceder los ' . self::MAX_NOMBRE_ARCHIVO . ' caracteres';
            }
            
            // Validar caracteres del nombre
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo solo puede contener letras, números, guiones, guiones bajos y puntos';
            }
            
            // Validar que no tenga caracteres peligrosos
            if (preg_match('/[<>"\|\&\$\*\?]/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo contiene caracteres no permitidos';
            }
            
            // Validar extensión
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (!empty($extension) && !in_array($extension, self::EXTENSIONES_PERMITIDAS)) {
                $errores['nombre_archivo'] = 'La extensión del archivo debe ser .sql o .enc';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para restaurar un backup
     */
    private function validarRestaurarBackup($datos) {
        $errores = [];
        
        // Validar nombre del archivo
        if (!isset($datos['nombre_archivo']) || empty($datos['nombre_archivo'])) {
            $errores['nombre_archivo'] = 'Debe especificar el nombre del archivo a restaurar';
        } else {
            $nombreArchivo = trim($datos['nombre_archivo']);
            
            // Validar longitud del nombre
            if (mb_strlen($nombreArchivo) > self::MAX_NOMBRE_ARCHIVO) {
                $errores['nombre_archivo'] = 'El nombre del archivo no debe exceder los ' . self::MAX_NOMBRE_ARCHIVO . ' caracteres';
            }
            
            // Validar caracteres del nombre
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo solo puede contener letras, números, guiones, guiones bajos y puntos';
            }
            
            // Validar que no tenga caracteres peligrosos
            if (preg_match('/[<>"\|\&\$\*\?]/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo contiene caracteres no permitidos';
            }
            
            // Validar extensión
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (!in_array($extension, self::EXTENSIONES_PERMITIDAS)) {
                $errores['nombre_archivo'] = 'El archivo debe tener extensión .sql o .enc';
            }
            
            // Validar que el archivo exista
            $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            
            if (!file_exists($rutaArchivo)) {
                $errores['nombre_archivo'] = 'El archivo de backup no existe';
            } elseif (!is_file($rutaArchivo)) {
                $errores['nombre_archivo'] = 'La ruta especificada no es un archivo válido';
            } elseif (!is_readable($rutaArchivo)) {
                $errores['nombre_archivo'] = 'El archivo no tiene permisos de lectura';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para descargar un backup
     */
    private function validarDescargarBackup($datos) {
        return $this->validarRestaurarBackup($datos); // Usa la misma validación que restaurar
    }
    
    /**
     * Valida los datos para eliminar un backup
     */
    private function validarEliminarBackup($datos) {
        $errores = [];
        
        // Validar nombre del archivo
        if (!isset($datos['nombre_archivo']) || empty($datos['nombre_archivo'])) {
            $errores['nombre_archivo'] = 'Debe especificar el nombre del archivo a eliminar';
        } else {
            $nombreArchivo = trim($datos['nombre_archivo']);
            
            // Validar longitud del nombre
            if (mb_strlen($nombreArchivo) > self::MAX_NOMBRE_ARCHIVO) {
                $errores['nombre_archivo'] = 'El nombre del archivo no debe exceder los ' . self::MAX_NOMBRE_ARCHIVO . ' caracteres';
            }
            
            // Validar caracteres del nombre
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo solo puede contener letras, números, guiones, guiones bajos y puntos';
            }
            
            // Validar que no tenga caracteres peligrosos
            if (preg_match('/[<>"\|\&\$\*\?]/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo contiene caracteres no permitidos';
            }
            
            // Validar extensión
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (!in_array($extension, self::EXTENSIONES_PERMITIDAS)) {
                $errores['nombre_archivo'] = 'El archivo debe tener extensión .sql o .enc';
            }
            
            // Validar que el archivo exista
            $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            
            if (!file_exists($rutaArchivo)) {
                $errores['nombre_archivo'] = 'El archivo de backup no existe';
            } elseif (!is_file($rutaArchivo)) {
                $errores['nombre_archivo'] = 'La ruta especificada no es un archivo válido';
            } elseif (!is_writable($rutaArchivo)) {
                $errores['nombre_archivo'] = 'El archivo no tiene permisos de eliminación';
            }
            
            // Validar que no sea un archivo protegido
            $archivosProtegidos = ['backup_restore.log', 'backup_debug.log'];
            if (in_array($nombreArchivo, $archivosProtegidos)) {
                $errores['nombre_archivo'] = 'No se puede eliminar un archivo de sistema';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar un backup individual
     */
    private function validarConsultarBackup($datos) {
        $errores = [];
        
        // Validar nombre del archivo
        if (!isset($datos['nombre_archivo']) || empty($datos['nombre_archivo'])) {
            $errores['nombre_archivo'] = 'Debe especificar el nombre del archivo a consultar';
        } else {
            $nombreArchivo = trim($datos['nombre_archivo']);
            
            // Validar longitud del nombre
            if (mb_strlen($nombreArchivo) > self::MAX_NOMBRE_ARCHIVO) {
                $errores['nombre_archivo'] = 'El nombre del archivo no debe exceder los ' . self::MAX_NOMBRE_ARCHIVO . ' caracteres';
            }
            
            // Validar caracteres del nombre
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo solo puede contener letras, números, guiones, guiones bajos y puntos';
            }
            
            // Validar que no tenga caracteres peligrosos
            if (preg_match('/[<>"\|\&\$\*\?]/', $nombreArchivo)) {
                $errores['nombre_archivo'] = 'El nombre del archivo contiene caracteres no permitidos';
            }
            
            // Validar extensión
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (!in_array($extension, self::EXTENSIONES_PERMITIDAS)) {
                $errores['nombre_archivo'] = 'El archivo debe tener extensión .sql o .enc';
            }
            
            // Validar que el archivo exista
            $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            
            if (!file_exists($rutaArchivo)) {
                $errores['nombre_archivo'] = 'El archivo de backup no existe';
            } elseif (!is_file($rutaArchivo)) {
                $errores['nombre_archivo'] = 'La ruta especificada no es un archivo válido';
            } elseif (!is_readable($rutaArchivo)) {
                $errores['nombre_archivo'] = 'El archivo no tiene permisos de lectura';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para listar backups
     */
    private function validarListarBackups($datos) {
        $errores = [];
        
        // Validar límite de resultados (si se proporciona)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite < 1 || $limite > 100) {
                $errores['limite'] = 'El límite debe estar entre 1 y 100 resultados';
            }
        }
        
        // Validar tipo de filtro (si se proporciona)
        if (isset($datos['tipo_filtro'])) {
            $tiposValidos = ['todos', 'principal', 'seguridad'];
            if (!in_array(strtolower($datos['tipo_filtro']), $tiposValidos)) {
                $errores['tipo_filtro'] = 'El tipo de filtro debe ser: todos, principal o seguridad';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para generar un backup (método público)
     */
    public function validarGenerar($datos) {
        return $this->validarGenerarBackup($datos);
    }
    
    /**
     * Valida los datos para restaurar un backup (método público)
     */
    public function validarRestaurar($datos) {
        return $this->validarRestaurarBackup($datos);
    }
    
    /**
     * Valida los datos para descargar un backup (método público)
     */
    public function validarDescargar($datos) {
        return $this->validarDescargarBackup($datos);
    }
    
    /**
     * Valida los datos para eliminar un backup (método público)
     */
    public function validarEliminar($datos) {
        return $this->validarEliminarBackup($datos);
    }
    
    /**
     * Valida los datos para listar backups (método público)
     */
    public function validarListar($datos) {
        return $this->validarListarBackups($datos);
    }
    
    /**
     * Valida los datos para consultar un backup (método público)
     */
    public function validarConsultar($datos) {
        return $this->validarConsultarBackup($datos);
    }
    
    /**
     * Verifica si el directorio de backup existe y tiene permisos
     */
    private function verificarDirectorioBackup() {
        $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
        
        // Crear directorio si no existe
        if (!is_dir($rutaCarpeta)) {
            if (!mkdir($rutaCarpeta, 0775, true)) {
                return false;
            }
        }
        
        // Verificar permisos de escritura
        if (!is_writable($rutaCarpeta)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Limpia y sanitiza un nombre de archivo
     */
    private function sanitizarNombreArchivo($nombreArchivo) {
        // Eliminar caracteres peligrosos
        $nombreArchivo = preg_replace('/[<>"\|\&\$\*\?]/', '', $nombreArchivo);
        
        // Eliminar espacios y reemplazar con guiones bajos
        $nombreArchivo = preg_replace('/\s+/', '_', $nombreArchivo);
        
        // Eliminar caracteres no permitidos excepto letras, números, guiones, guiones bajos y puntos
        $nombreArchivo = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $nombreArchivo);
        
        // Limitar longitud
        if (mb_strlen($nombreArchivo) > self::MAX_NOMBRE_ARCHIVO) {
            $nombreArchivo = mb_substr($nombreArchivo, 0, self::MAX_NOMBRE_ARCHIVO);
        }
        
        return $nombreArchivo;
    }
}

?>
