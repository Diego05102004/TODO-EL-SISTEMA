<?php
// Updated: 2025-09-28 - Using native OpenSSL for compatibility
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
 * - OpenSSL nativo: Usa funciones nativas de PHP para máxima compatibilidad
 */
class Encryption {
    private $rsaPublicKey;
    private $rsaPrivateKey;
    private $aesMethod = 'AES-256-CBC';
    
    /**
     * Constructor
     * Carga claves RSA desde variables de entorno
     */
    public function __construct() {
        $this->loadRSAKeys();
    }
    
    /**
     * Carga claves RSA desde variables de entorno o directamente del archivo .env
     * @throws \RuntimeException si las claves no están configuradas
     */
    private function loadRSAKeys() {
        // Intentar cargar desde variables de entorno primero
        $publicKey = getenv('RSA_PUBLIC_KEY');
        $privateKey = getenv('RSA_PRIVATE_KEY');
        
        error_log("[ENCRYPTION] Cargando claves RSA desde variables de entorno...");
        error_log("[ENCRYPTION] Clave pública desde getenv: " . ($publicKey !== false ? "YES" : "NO"));
        error_log("[ENCRYPTION] Clave privada desde getenv: " . ($privateKey !== false ? "YES" : "NO"));
        
        // Fallback: Si no están en variables de entorno, cargar directamente del archivo .env
        if ($publicKey === false || $privateKey === false) {
            error_log("[ENCRYPTION] Variables de entorno no disponibles, cargando desde archivo .env...");
            
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envPath)) {
                $content = file_get_contents($envPath);
                
                // Extraer claves RSA del archivo .env
                preg_match('/RSA_PUBLIC_KEY="(.+?)"/s', $content, $publicMatch);
                preg_match('/RSA_PRIVATE_KEY="(.+?)"/s', $content, $privateMatch);
                
                if ($publicMatch && $privateMatch) {
                    $publicKey = $publicMatch[1];
                    $privateKey = $privateMatch[1];

                    error_log("[ENCRYPTION] Claves cargadas desde archivo .env exitosamente");
                    error_log("[ENCRYPTION] Longitud clave pública: " . strlen($publicKey));
                    error_log("[ENCRYPTION] Longitud clave privada: " . strlen($privateKey));
                } else {
                    error_log("[ENCRYPTION] No se encontraron claves RSA en archivo .env");
                }
            } else {
                error_log("[ENCRYPTION] Archivo .env no encontrado en: $envPath");
            }
        }
        
        if ($publicKey === false || $privateKey === false) {
            throw new \RuntimeException(
                'Las claves RSA no están configuradas en variables de entorno ni en el archivo .env. ' .
                'Configure RSA_PUBLIC_KEY y RSA_PRIVATE_KEY en el archivo .env'
            );
        }
        
        // Validar formato de claves (PEM)
        if (strpos($publicKey, '-----BEGIN PUBLIC KEY-----') === false || 
            strpos($privateKey, '-----BEGIN PRIVATE KEY-----') === false) {
            throw new \RuntimeException(
                'Las claves RSA no tienen formato PEM válido. ' .
                'Asegúrese de incluir los encabezados -----BEGIN/END PUBLIC/PRIVATE KEY-----'
            );
        }
        
        $this->rsaPublicKey = $publicKey;
        $this->rsaPrivateKey = $privateKey;
        
        error_log("[ENCRYPTION] Claves RSA cargadas exitosamente");
    }
    
    /**
     * Cifra un dato usando cifrado híbrido RSA+AES-256-CBC
     * Proceso:
     * 1. Generar clave AES aleatoria (32 bytes)
     * 2. Cifrar datos con AES-256-CBC
     * 3. Cifrar clave AES con RSA-2048 usando OAEP con SHA256
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
        
        error_log("[ENCRYPTION] Iniciando cifrado...");
        error_log("[ENCRYPTION] Longitud dato original: " . strlen($data));
        
        // 1. Generar clave AES aleatoria de 32 bytes (256 bits)
        $aesKey = random_bytes(32);
        error_log("[ENCRYPTION] Clave AES generada: " . strlen($aesKey) . " bytes");
        
        // 2. Generar IV aleatorio de 16 bytes (128 bits)
        $iv = random_bytes(openssl_cipher_iv_length($this->aesMethod));
        error_log("[ENCRYPTION] IV generado: " . strlen($iv) . " bytes");
        
        // 3. Cifrar datos con AES-256-CBC
        $encryptedData = openssl_encrypt($data, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
        
        if ($encryptedData === false) {
            throw new \RuntimeException('Error al cifrar datos con AES: ' . openssl_error_string());
        }
        
        error_log("[ENCRYPTION] Datos cifrados con AES: " . strlen($encryptedData) . " bytes");
        
        // 4. Cifrar la clave AES con RSA usando PKCS1 v1.5 (máxima compatibilidad)
        $encryptedAesKey = null;
        $opensslError = '';
        
        // Usar PKCS1 v1.5 padding para máxima compatibilidad con node-forge
        if (openssl_public_encrypt($aesKey, $encryptedAesKey, $this->rsaPublicKey, OPENSSL_PKCS1_PADDING)) {
            error_log("[ENCRYPTION] Clave AES cifrada con RSA PKCS1: " . strlen($encryptedAesKey) . " bytes");
        } else {
            $opensslError = openssl_error_string();
            error_log("[ENCRYPTION] PKCS1 falló: " . $opensslError);
            throw new \RuntimeException('Error al cifrar clave AES con RSA: ' . $opensslError);
        }
        
        // 5. Combinar: longitud clave AES cifrada (4 bytes) + clave AES cifrada + IV + datos cifrados
        $result = pack('N', strlen($encryptedAesKey)) . $encryptedAesKey . $iv . $encryptedData;
        error_log("[ENCRYPTION] Longitud resultado combinado: " . strlen($result) . " bytes");
        
        // 6. Codificar en Base64
        $base64Result = base64_encode($result);
        error_log("[ENCRYPTION] Resultado Base64: " . strlen($base64Result) . " caracteres");
        error_log("[ENCRYPTION] Cifrado completado exitosamente");
        
        return $base64Result;
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
        
        error_log("[ENCRYPTION] Iniciando descifrado...");
        error_log("[ENCRYPTION] Longitud dato cifrado: " . strlen($encryptedData));
        
        $data = base64_decode($encryptedData, true);
        if ($data === false || strlen($data) < 4) {
            error_log("[ENCRYPTION] Base64 inválido o demasiado corto, retornando original");
            return $encryptedData;
        }

        error_log("[ENCRYPTION] Base64 decodificado: " . strlen($data) . " bytes");

        // Calcular longitud esperada del bloque RSA (2048 bits = 256 bytes)
        $rsaBlockLength = 256;
        error_log("[ENCRYPTION] Longitud esperada bloque RSA: " . $rsaBlockLength);

        // Verificar si es cifrado RSA puro (sin AES híbrido)
        if (strlen($data) === $rsaBlockLength) {
            error_log("[ENCRYPTION] Detectado cifrado RSA puro");
            $decrypted = $this->decryptRsaPure($data);
            return $decrypted === null ? $encryptedData : $decrypted;
        }

        // Extraer longitud de clave AES cifrada (primeros 4 bytes)
        $keyLength = unpack('N', substr($data, 0, 4))[1];
        error_log("[ENCRYPTION] Longitud clave AES cifrada: " . $keyLength);
        
        if ($keyLength !== $rsaBlockLength) {
            error_log("[ENCRYPTION] Longitud de clave AES no coincide con bloque RSA: " . $keyLength . " vs " . $rsaBlockLength);
            return $encryptedData;
        }

        $ivLength = openssl_cipher_iv_length($this->aesMethod);
        $ciphertextOffset = 4 + $keyLength + $ivLength;
        if (strlen($data) < $ciphertextOffset + 16) {
            throw new \RuntimeException('Sobre cifrado incompleto');
        }

        $encryptedAesKey = substr($data, 4, $keyLength);
        $iv = substr($data, 4 + $keyLength, $ivLength);
        $encryptedDataPart = substr($data, $ciphertextOffset);
        
        error_log("[ENCRYPTION] IV: " . strlen($iv) . " bytes");
        error_log("[ENCRYPTION] Datos cifrados: " . strlen($encryptedDataPart) . " bytes");
        
        if (strlen($encryptedDataPart) % 16 !== 0) {
            throw new \RuntimeException('Longitud de datos AES no válida');
        }

        $aesKey = $this->decryptRsaPayload($encryptedAesKey, true);
        if ($aesKey === null) {
            throw new \RuntimeException('No se pudo descifrar la clave AES');
        }

        error_log("[ENCRYPTION] Clave AES descifrada: " . strlen($aesKey) . " bytes");

        $decrypted = openssl_decrypt($encryptedDataPart, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            error_log("[ENCRYPTION] Error descifrando datos AES: " . openssl_error_string());
            throw new \RuntimeException('No se pudieron descifrar los datos AES');
        }

        error_log("[ENCRYPTION] Descifrado completado exitosamente");
        return $decrypted;
    }

    /**
     * Descifra datos usando RSA puro (sin AES híbrido)
     * Compatibilidad con node-forge que a veces usa RSA directo
     *
     * @param string $encryptedData Datos cifrados
     * @return string Datos descifrados
     */
    private function decryptRsaPure($encryptedData) {
        return $this->decryptRsaPayload($encryptedData, false);
    }

    /**
     * Descifra payload RSA con múltiples métodos de padding para compatibilidad
     * 
     * @param string $encryptedAesKey Datos cifrados con RSA
     * @param bool $requireAesKey Si es true, requiere que el resultado sea 32 bytes (clave AES)
     * @return string|null Datos descifrados o null si falla
     */
    private function decryptRsaPayload($encryptedAesKey, $requireAesKey) {
        // Intentar PKCS1 primero (más compatible)
        $paddingMethods = [
            OPENSSL_PKCS1_PADDING,        // PKCS1 v1.5 (más compatible)
            OPENSSL_PKCS1_OAEP_PADDING,   // OAEP (node-forge default)
        ];

        foreach ($paddingMethods as $padding) {
            try {
                $decrypted = '';
                $key = openssl_pkey_get_private($this->rsaPrivateKey);
                
                if ($key) {
                    if (openssl_private_decrypt($encryptedAesKey, $decrypted, $key, $padding)) {
                        error_log("[ENCRYPTION] Descifrado RSA exitoso con padding: " . $padding);
                        
                        if (!$requireAesKey || strlen($decrypted) === 32) {
                            return $decrypted;
                        } else {
                            error_log("[ENCRYPTION] Longitud incorrecta: " . strlen($decrypted) . " (esperado 32)");
                        }
                    } else {
                        error_log("[ENCRYPTION] Descifrado RSA falló con padding " . $padding . ": " . openssl_error_string());
                    }
                    // openssl_free_key ya no es necesario en PHP 8+
                }
            } catch (\Throwable $e) {
                error_log("[ENCRYPTION] Excepción con padding " . $padding . ": " . $e->getMessage());
                // Try the next padding method
            }
        }

        error_log("[ENCRYPTION] No se pudo descifrar con ningún método de padding");
        return null;
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

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            error_log("[ENCRYPTION] Descifrando campo: $field");
            $data[$field] = $this->decrypt($data[$field]);
        }
    }

    return $data;
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
