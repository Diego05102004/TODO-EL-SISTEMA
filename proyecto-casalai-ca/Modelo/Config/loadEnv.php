<?php
namespace Usuario\ProyectoCasalaiCa\Config;

/**
 * Cargador de variables de entorno desde archivo .env
 * 
 * Este archivo carga las variables de entorno desde un archivo .env
 * en la raíz del proyecto para facilitar la configuración en desarrollo.
 * 
 * En producción, se recomienda usar variables de entorno del sistema operativo
 * en lugar de este archivo para mayor seguridad.
 */

class EnvLoader {
    /**
     * Carga variables de entorno desde archivo .env
     * @param string $path Ruta al archivo .env (opcional, por defecto busca en raíz del proyecto)
     * @return void
     */
    public static function load($path = null) {
        if ($path === null) {
            // Buscar .env en la raíz del proyecto (2 niveles arriba desde este archivo)
            $path = dirname(__DIR__, 2) . '/.env';
        }
        
        if (!file_exists($path)) {
            return; // No hacer nada si el archivo no existe
        }
        
        // Leer el archivo completo como una cadena para manejar valores multilínea
        $content = file_get_contents($path);
        
        // Dividir por líneas pero mantener el contenido completo para procesar valores multilínea
        $lines = explode("\n", $content);
        
        $currentVar = null;
        $currentValue = '';
        $currentQuote = null;
        $inMultiline = false;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($inMultiline) {
                $closingPosition = strrpos($line, $currentQuote);
                if ($closingPosition !== false) {
                    $currentValue .= "\n" . substr($line, 0, $closingPosition);
                    self::setEnvVar($currentVar, $currentValue);
                    $currentVar = null;
                    $currentValue = '';
                    $currentQuote = null;
                    $inMultiline = false;
                } else {
                    $currentValue .= "\n" . $line;
                }
                continue;
            }
            
            // Ignorar comentarios y líneas vacías (excepto cuando estamos en un valor multilínea)
            if (strpos($trimmedLine, '#') === 0 || $trimmedLine === '') {
                continue;
            }
            
            // Procesar nueva variable
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remover BOM (Byte Order Mark) del nombre de la variable
                $name = preg_replace('/^\xEF\xBB\xBF/', '', $name);
                
                // Verificar si el valor empieza con comillas (posible valor multilínea)
                if ((strpos($value, '"') === 0) || (strpos($value, "'") === 0)) {
                    $quote = $value[0];
                    // Verificar si la comilla de cierre está en la misma línea
                    if (strrpos($value, $quote) !== strlen($value) - 1) {
                        // Valor multilínea
                        $inMultiline = true;
                        $currentVar = $name;
                        $currentQuote = $quote;
                        $currentValue = substr($value, 1); // Remover comilla de apertura
                        continue;
                    }
                }
                
                // Valor de una sola línea
                self::setEnvVar($name, $value);
            }
        }
        
        // Guardar la última variable si estaba en multilínea
        if ($inMultiline && $currentVar !== null) {
            self::setEnvVar($currentVar, $currentValue);
        }
    }
    
    private static function setEnvVar($name, $value) {
        if (getenv($name) !== false || array_key_exists($name, $_ENV) || array_key_exists($name, $_SERVER)) {
            return;
        }

        // Remover comillas si están presentes al inicio y final
        if (strlen($value) >= 2) {
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
        }
        
        // Convertir \n a saltos de línea reales (para claves RSA)
        $value = str_replace('\\n', "\n", $value);
        
        // Establecer variable de entorno
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Cargar automáticamente al incluir este archivo
EnvLoader::load();
