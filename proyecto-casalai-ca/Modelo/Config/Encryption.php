<?php
namespace Usuario\ProyectoCasalaiCa\Config;

/**
 * Clase de cifrado híbrido RSA+AES-256-CBC
 * Sigue estándares internacionales de seguridad (NIST, FIPS 197, PKCS#1)
 * 
 * Características:
 * - Algoritmo híbrido: RSA para proteger clave AES + AES-256-CBC para datos
 * - RSA: 2048 bits para cifrado asimétrico de clave AES
 * - AES-256-CBC: Para cifrado simétrico de datos (rápido y eficiente)
 * - Longitud de clave AES: 256 bits (32 bytes), generada aleatoriamente por sesión
 * - Modo AES: CBC (Cipher Block Chaining)
 * - Padding: PKCS7
 * - IV (Initialization Vector): 128 bits (16 bytes), generado aleatoriamente para cada cifrado
 * - Codificación: Base64 para almacenamiento en base de datos
 * - Variables de entorno: Claves RSA cargadas desde entorno para máxima seguridad
 */
class Encryption {
    private $rsaPublicKey;
    private $rsaPrivateKey;
    private $aesMethod = 'AES-256-CBC';

    /**
     * Constructor
     * Carga claves RSA desde variables de entorno o desde .env.
     */
    public function __construct() {
        $this->loadRSAKeys();
    }

    private function loadRSAKeys() {
        $publicKey = getenv('RSA_PUBLIC_KEY');
        $privateKey = getenv('RSA_PRIVATE_KEY');

        $normalizeKey = static function($key) {
            return trim(str_replace(['\\n', "\r\n", "\r"], ["\n", "\n", "\n"], $key));
        };
        $configuredKeysAreParseable = $publicKey !== false && $privateKey !== false &&
            trim($publicKey) !== '' && trim($privateKey) !== '' &&
            @openssl_pkey_get_public($normalizeKey($publicKey)) !== false &&
            @openssl_pkey_get_private($normalizeKey($privateKey)) !== false;

        if (!$configuredKeysAreParseable) {
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (is_file($envPath)) {
                $content = file_get_contents($envPath);
                if ($content === false) {
                    throw new \RuntimeException('No se pudo leer el archivo de configuración RSA.');
                }
                preg_match('/RSA_PUBLIC_KEY="(.+?)"/s', $content, $publicMatch);
                preg_match('/RSA_PRIVATE_KEY="(.+?)"/s', $content, $privateMatch);

                $publicKey = $publicMatch[1] ?? $publicKey;
                $privateKey = $privateMatch[1] ?? $privateKey;
            }
        }

        if ($publicKey === false || $privateKey === false ||
            trim($publicKey) === '' || trim($privateKey) === '') {
            throw new \RuntimeException(
                'Las claves RSA no están configuradas en variables de entorno ni en el archivo .env.'
            );
        }

        $publicKey = $normalizeKey($publicKey);
        $privateKey = $normalizeKey($privateKey);

        $publicKeyResource = @openssl_pkey_get_public($publicKey);
        $privateKeyResource = @openssl_pkey_get_private($privateKey);
        if ($publicKeyResource === false || $privateKeyResource === false) {
            throw new \RuntimeException(
                'No se pudieron interpretar las claves RSA. Verifique que el contenido PEM esté completo y válido.'
            );
        }

        $publicDetails = openssl_pkey_get_details($publicKeyResource);
        $privateDetails = openssl_pkey_get_details($privateKeyResource);
        if ($publicDetails['type'] !== OPENSSL_KEYTYPE_RSA ||
            $privateDetails['type'] !== OPENSSL_KEYTYPE_RSA ||
            !hash_equals($publicDetails['rsa']['n'], $privateDetails['rsa']['n']) ||
            !hash_equals($publicDetails['rsa']['e'], $privateDetails['rsa']['e'])) {
            throw new \RuntimeException('La clave pública y la clave privada RSA no forman una pareja válida.');
        }

        $this->rsaPublicKey = $publicKey;
        $this->rsaPrivateKey = $privateKey;
    }
    
    /**
     * Cifra un dato usando cifrado híbrido RSA+AES-256-CBC
     * Proceso:
     * 1. Generar clave AES aleatoria (32 bytes)
     * 2. Cifrar datos con AES-256-CBC
     * 3. Cifrar clave AES con RSA-2048
     * 4. Combinar: clave AES cifrada + IV + datos cifrados
     * 
     * @param string $data Datos a cifrar
     * @return string Datos cifrados en Base64
     */
    public function encrypt($data) {
        // Manejar valores nulos o vacíos
        if ($data === null) {
            return null;
        }
        
        if ($data === '') {
            return '';
        }
        
        // Asegurar que sea string
        $data = (string)$data;
        
        // 1. Generar clave AES aleatoria de 32 bytes (256 bits)
        $aesKey = openssl_random_pseudo_bytes(32);
        
        // 2. Generar IV aleatorio de 16 bytes (128 bits)
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->aesMethod));
        
        // 3. Cifrar datos con AES-256-CBC
        $encryptedData = openssl_encrypt($data, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
        
        if ($encryptedData === false) {
            throw new \RuntimeException('Error al cifrar datos con AES: ' . openssl_error_string());
        }
        
        // 4. Cifrar clave AES con RSA (clave pública)
        $encryptedAesKey = '';
        if (!openssl_public_encrypt($aesKey, $encryptedAesKey, $this->rsaPublicKey)) {
            throw new \RuntimeException('Error al cifrar clave AES con RSA: ' . openssl_error_string());
        }
        
        // 5. Combinar: longitud clave AES cifrada (4 bytes) + clave AES cifrada + IV + datos cifrados
        $result = pack('N', strlen($encryptedAesKey)) . $encryptedAesKey . $iv . $encryptedData;
        
        // 6. Codificar en Base64
        return base64_encode($result);
    }
    
    /**
     * Descifra un dato cifrado con cifrado híbrido RSA+AES-256-CBC
     * Proceso:
     * 1. Decodificar de Base64
     * 2. Extraer longitud de clave AES cifrada
     * 3. Extraer clave AES cifrada
     * 4. Descifrar clave AES con RSA (clave privada)
     * 5. Extraer IV y datos cifrados
     * 6. Descifrar datos con AES-256-CBC
     * 
     * @param string $encryptedData Datos cifrados en Base64
     * @return string Datos descifrados
     */
    public function decrypt($encryptedData) {
        // Manejar valores nulos o vacíos
        if ($encryptedData === null) {
            return null;
        }
       
        if ($encryptedData === '') {
            return '';
        }
        
        // Asegurar que sea string
        $encryptedData = (string)$encryptedData;
        
        // 1. Decodificar de Base64
        $data = base64_decode($encryptedData);
        
        if ($data === false) {
            // Si no es Base64 válido, podría ser un dato no cifrado (compatibilidad)
            return $encryptedData;
        }
        
        // Verificar longitud mínima (4 bytes longitud + clave AES + IV + datos)
        if (strlen($data) < 4 + 16) {
            // Datos demasiado cortos = probablemente dato no cifrado antiguo
            return $encryptedData;
        }
        
        try {
            // 2. Extraer longitud de clave AES cifrada (primeros 4 bytes)
            $keyLength = unpack('N', substr($data, 0, 4))[1];
            
            error_log("[ENCRYPTION] decrypt: keyLength=$keyLength, dataLength=" . strlen($data));
            
            // Verificar que hay suficientes datos
            if (strlen($data) < 4 + $keyLength + 16) {
                error_log("[ENCRYPTION] decrypt: datos demasiado cortos, retornando original");
                return $encryptedData;
            }
            
            // 3. Extraer clave AES cifrada
            $encryptedAesKey = substr($data, 4, $keyLength);
            
            // 4. Descifrar clave AES con RSA (clave privada)
            $aesKey = '';
            if (!openssl_private_decrypt($encryptedAesKey, $aesKey, $this->rsaPrivateKey)) {
                $error = openssl_error_string();
                error_log("[ENCRYPTION] decrypt: error RSA - $error");
                throw new \RuntimeException('Error al descifrar clave AES con RSA: ' . $error);
            }
            
            error_log("[ENCRYPTION] decrypt: clave AES descifrada exitosamente");
            
            // 5. Extraer IV (16 bytes) y datos cifrados
            $iv = substr($data, 4 + $keyLength, 16);
            $encryptedDataPart = substr($data, 4 + $keyLength + 16);
            
            // 6. Descifrar datos con AES-256-CBC
            $decrypted = openssl_decrypt($encryptedDataPart, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                $error = openssl_error_string();
                error_log("[ENCRYPTION] decrypt: error AES - $error");
                throw new \RuntimeException('Error al descifrar datos con AES: ' . $error);
            }
            
            error_log("[ENCRYPTION] decrypt: datos descifrados exitosamente");
            
            return $decrypted;
            
        } catch (\Exception $e) {
            // Si falla el descifrado, retornar valor original (compatibilidad)
            error_log('Error al descifrar datos: ' . $e->getMessage());
            return $encryptedData;
        }
    }
    
    /**
     * Cifra un array de datos
     * @param array $data Array de datos a cifrar
     * @param array $fields Campos a cifrar
     * @return array Array con campos cifrados
     */
    public function encryptArray($data, $fields) {
        if (!is_array($data)) {
            return $data;
        }
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
/**
 * Descifra un array de datos con debugging avanzado
 * @param array $data Array de datos cifrados
 * @param array $fields Campos a descifrar
 * @param bool $debugMode Activar logging detallado
 * @return array Array con campos descifrados
 */
public function decryptArray($data, $fields, $debugMode = false) {
    if (!is_array($data)) {
        return $data;
    }
    
    // Log de inicio con detalles del array
    error_log("[ENCRYPTION-DEBUG] Iniciando decryptArray para " . count($fields) . " campos");
    error_log("[ENCRYPTION-DEBUG] Estructura del array: " . json_encode(array_keys($data)));
    
    foreach ($fields as $field) {
        if (!isset($data[$field])) {
            error_log("[ENCRYPTION-DEBUG] Campo '$field' NO EXISTE en el array");
            continue;
        }
        
        $valorOriginal = $data[$field];
        
        // 1. DIAGNÓSTICO DEL VALOR ORIGINAL
        $this->diagnosticarValor($field, $valorOriginal);
        
        try {
            // 2. INTENTAR DESCIFRAR
            $valorDescifrado = $this->decryptWithDiagnostico($valorOriginal, $field);
            
            // 3. VERIFICAR RESULTADO
            if ($valorDescifrado !== $valorOriginal) {
                $data[$field] = $valorDescifrado;
                error_log("[ENCRYPTION-DEBUG] ✅ CAMPO '$field' DESCIFRADO EXITOSAMENTE");
                error_log("[ENCRYPTION-DEBUG]   Longitud original: " . strlen($valorOriginal) . 
                         " | Longitud descifrada: " . strlen($valorDescifrado));
                
            } else {
                error_log("[ENCRYPTION-DEBUG] ⚠️ CAMPO '$field' NO SE MODIFICÓ (puede no estar cifrado)");
            }
            
        } catch (\Exception $e) {
            error_log("[ENCRYPTION-DEBUG] ❌ ERROR DESCIFRANDO CAMPO '$field': " . $e->getMessage());
            error_log("[ENCRYPTION-DEBUG]   Tipo de error: " . get_class($e));
            // Mantener valor original
            $data[$field] = $valorOriginal;
        }
    }
    
    error_log("[ENCRYPTION-DEBUG] Finalizado decryptArray");
    return $data;
}

/**
 * Diagnóstico detallado de un valor
 */
private function diagnosticarValor($field, $value) {
    error_log("[ENCRYPTION-DEBUG] 📊 DIAGNÓSTICO CAMPO '$field':");
    error_log("[ENCRYPTION-DEBUG]   - Tipo: " . gettype($value));
    error_log("[ENCRYPTION-DEBUG]   - Longitud: " . (is_string($value) ? strlen($value) : 'N/A'));
    error_log("[ENCRYPTION-DEBUG]   - Es null? " . ($value === null ? 'SI' : 'NO'));
    error_log("[ENCRYPTION-DEBUG]   - Es vacío? " . ($value === '' ? 'SI' : 'NO'));
    
    if (is_string($value) && strlen($value) > 0) {
        // Verificar si parece Base64
        $esBase64 = (bool) preg_match('/^[A-Za-z0-9\+\/=]+$/', $value);
        error_log("[ENCRYPTION-DEBUG]   - Parece Base64? " . ($esBase64 ? 'SI' : 'NO'));
        
        // Verificar si parece cifrado (base64 largo)
        if ($esBase64 && strlen($value) > 24) {
            error_log("[ENCRYPTION-DEBUG]   - Posible dato cifrado: SI (longitud > 24)");
            
            // Intentar decodificar para ver estructura
            $decoded = base64_decode($value, true);
            if ($decoded !== false) {
                error_log("[ENCRYPTION-DEBUG]   - Decodificación Base64: OK (" . strlen($decoded) . " bytes)");
                error_log("[ENCRYPTION-DEBUG]   - Estructura bytes: " . $this->analizarEstructura($decoded));
            } else {
                error_log("[ENCRYPTION-DEBUG]   - ❌ Decodificación Base64: FALLÓ");
            }
        } else {
            error_log("[ENCRYPTION-DEBUG]   - Posible dato plano: SI (no parece cifrado)");
        }
        
        // Verificar caracteres especiales
        if (strlen($value) > 0) {
            $bytes = unpack('C*', $value);
            $hasNonPrintable = false;
            foreach ($bytes as $byte) {
                if ($byte < 32 || $byte > 126) {
                    $hasNonPrintable = true;
                    break;
                }
            }
            error_log("[ENCRYPTION-DEBUG]   - Tiene caracteres no imprimibles? " . ($hasNonPrintable ? 'SI' : 'NO'));
        }
    }
}

/**
 * Analiza la estructura de bytes de un dato cifrado
 */
private function analizarEstructura($data) {
    if (strlen($data) < 4) {
        return "DATOS DEMASIADO CORTOS";
    }
    
    $info = [];
    
    // Leer longitud de clave AES
    $keyLengthBytes = substr($data, 0, 4);
    if (strlen($keyLengthBytes) === 4) {
        $keyLength = unpack('N', $keyLengthBytes)[1];
        $info[] = "longitud_clave_AES_cifrada=$keyLength";
        
        if (strlen($data) >= 4 + $keyLength + 16) {
            $info[] = "Estructura: OK";
            $info[] = "IV presente: SI";
            $info[] = "Datos cifrados: " . (strlen($data) - 4 - $keyLength - 16) . " bytes";
        } else {
            $info[] = "❌ Estructura: INCOMPLETA";
            $info[] = "Faltan bytes: " . (4 + $keyLength + 16 - strlen($data));
        }
    }
    
    return implode(" | ", $info);
}

/**
 * Versión de decrypt con diagnóstico detallado
 */
public function decryptWithDiagnostico($encryptedData, $fieldName) {
    // Manejar valores nulos o vacíos
    if ($encryptedData === null) {
        error_log("[ENCRYPTION-DEBUG] Campo '$fieldName' es null, retornando null");
        return null;
    }
    
    if ($encryptedData === '') {
        error_log("[ENCRYPTION-DEBUG] Campo '$fieldName' es vacío, retornando ''");
        return '';
    }
    
    // Asegurar que sea string
    $encryptedData = (string)$encryptedData;
    
    error_log("[ENCRYPTION-DEBUG] 🔓 Intentando descifrar campo '$fieldName'...");
    error_log("[ENCRYPTION-DEBUG]   Longitud dato: " . strlen($encryptedData));
    
    // 1. Decodificar de Base64
    $data = base64_decode($encryptedData, true);
    
    if ($data === false) {
        error_log("[ENCRYPTION-DEBUG]   ❌ No es Base64 válido, asumiendo dato sin cifrar");
        return $encryptedData;
    }
    
    error_log("[ENCRYPTION-DEBUG]   ✅ Decodificación Base64 OK: " . strlen($data) . " bytes");
    
    // 2. Verificar longitud mínima
    $minLength = 4 + 16; // 4 bytes longitud + IV mínimo
    if (strlen($data) < $minLength) {
        error_log("[ENCRYPTION-DEBUG]   ⚠️ Datos muy cortos (mínimo $minLength bytes), asumiendo dato sin cifrar");
        return $encryptedData;
    }
    
    try {
        // 3. Extraer longitud de clave AES cifrada
        $keyLength = unpack('N', substr($data, 0, 4))[1];
        error_log("[ENCRYPTION-DEBUG]   Longitud clave AES cifrada: $keyLength bytes");
        
        // 4. Verificar estructura completa
        if (strlen($data) < 4 + $keyLength + 16) {
            error_log("[ENCRYPTION-DEBUG]   ❌ Estructura incompleta");
            error_log("[ENCRYPTION-DEBUG]     Necesario: " . (4 + $keyLength + 16) . " bytes");
            error_log("[ENCRYPTION-DEBUG]     Actual: " . strlen($data) . " bytes");
            return $encryptedData;
        }
        
        // 5. Extraer componentes
        $encryptedAesKey = substr($data, 4, $keyLength);
        $iv = substr($data, 4 + $keyLength, 16);
        $encryptedDataPart = substr($data, 4 + $keyLength + 16);
        
        error_log("[ENCRYPTION-DEBUG]   IV extraído: " . bin2hex($iv));
        error_log("[ENCRYPTION-DEBUG]   Datos cifrados: " . strlen($encryptedDataPart) . " bytes");
        
        // 6. Descifrar clave AES con RSA
        $aesKey = '';
        if (!openssl_private_decrypt($encryptedAesKey, $aesKey, $this->rsaPrivateKey)) {
            $error = openssl_error_string();
            error_log("[ENCRYPTION-DEBUG]   ❌ Error RSA: $error");
            throw new \RuntimeException('Error al descifrar clave AES: ' . $error);
        }
        
        error_log("[ENCRYPTION-DEBUG]   ✅ Clave AES descifrada: " . strlen($aesKey) . " bytes");
        
        // 7. Descifrar datos con AES
        $decrypted = openssl_decrypt($encryptedDataPart, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            $error = openssl_error_string();
            error_log("[ENCRYPTION-DEBUG]   ❌ Error AES: $error");
            throw new \RuntimeException('Error al descifrar datos: ' . $error);
        }
        
        error_log("[ENCRYPTION-DEBUG]   ✅ Datos descifrados: " . strlen($decrypted) . " bytes");
        
        return $decrypted;
        
    } catch (\Exception $e) {
        error_log("[ENCRYPTION-DEBUG]   ❌ Excepción: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Método de prueba para verificar el cifrado/descifrado de un campo específico
 */
public function testEncryption($plainText, $fieldName = 'test') {
    error_log("[ENCRYPTION-TEST] =======================");
    error_log("[ENCRYPTION-TEST] Probando campo '$fieldName'");
    error_log("[ENCRYPTION-TEST] Probando valor de longitud: " . strlen($plainText));
    error_log("[ENCRYPTION-TEST] Longitud: " . strlen($plainText));
    
    // 1. Cifrar
    $encrypted = $this->encrypt($plainText);
    error_log("[ENCRYPTION-TEST] Longitud cifrado: " . strlen($encrypted));
    
    // 2. Descifrar
    $decrypted = $this->decryptWithDiagnostico($encrypted, $fieldName);
    error_log("[ENCRYPTION-TEST] Descifrado: '$decrypted'");
    error_log("[ENCRYPTION-TEST] Longitud descifrado: " . strlen($decrypted));
    
    // 3. Verificar
    if ($plainText === $decrypted) {
        error_log("[ENCRYPTION-TEST] ✅ TEST PASADO: cifrado y descifrado correcto");
    } else {
        error_log("[ENCRYPTION-TEST] ❌ TEST FALLIDO: los datos no coinciden");
        error_log("[ENCRYPTION-TEST]   Original: '$plainText'");
        error_log("[ENCRYPTION-TEST]   Descifrado: '$decrypted'");
    }
    
    error_log("[ENCRYPTION-TEST] =======================");
    
    return [
        'original' => $plainText,
        'encrypted' => $encrypted,
        'decrypted' => $decrypted,
        'success' => $plainText === $decrypted
    ];
}
    
    /**
     * Cifra un array de resultados de base de datos
     * @param array $results Array de resultados
     * @param array $fields Campos a cifrar
     * @return array Array con campos cifrados
     */
    public function encryptResults($results, $fields) {
        if (!is_array($results)) {
            return $results;
        }
        
        foreach ($results as &$row) {
            $row = $this->encryptArray($row, $fields);
        }
        
        return $results;
    }
    
    /**
     * Descifra un array de resultados de base de datos
     * @param array $results Array de resultados
     * @param array $fields Campos a descifrar
     * @return array Array con campos descifrados
     */
    public function decryptResults($results, $fields) {
        if (!is_array($results)) {
            return $results;
        }
        
        foreach ($results as &$row) {
            $row = $this->decryptArray($row, $fields);
        }
        
        return $results;
    }
}
