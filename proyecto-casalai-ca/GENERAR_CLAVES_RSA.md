# Generación de Claves RSA para Cifrado Híbrido

Este documento explica cómo generar las claves RSA necesarias para el sistema de cifrado híbrido RSA+AES-256-CBC.

## Requisitos

- OpenSSL instalado en el sistema
- Acceso a terminal/consola de comandos

## Pasos para Generar Claves RSA

### 1. Generar Clave Privada RSA (2048 bits)

```bash
openssl genrsa -out private.pem 2048
```

Esto generará un archivo `private.pem` con la clave privada RSA de 2048 bits.

### 2. Generar Clave Pública RSA desde la Clave Privada

```bash
openssl rsa -in private.pem -pubout -out public.pem
```

Esto generará un archivo `public.pem` con la clave pública correspondiente.

### 3. Verificar las Claves Generadas

**Verificar clave privada:**
```bash
openssl rsa -in private.pem -check -noout
```

**Verificar clave pública:**
```bash
openssl rsa -in public.pem -pubin -check -noout
```

### 4. Formatear Claves para Variables de Entorno

Las claves deben estar en formato PEM con saltos de línea como `\n` para usarlas en variables de entorno.

**En Linux/Mac (bash):**
```bash
# Leer clave pública y formatear
RSA_PUBLIC_KEY=$(cat public.pem | sed 's/$/\\n/' | tr -d '\n' | sed 's/\\n$//')
echo "RSA_PUBLIC_KEY=\"$RSA_PUBLIC_KEY\""

# Leer clave privada y formatear
RSA_PRIVATE_KEY=$(cat private.pem | sed 's/$/\\n/' | tr -d '\n' | sed 's/\\n$//')
echo "RSA_PRIVATE_KEY=\"$RSA_PRIVATE_KEY\""
```

**En Windows (PowerShell):**
```powershell
# Leer clave pública
$publicKey = Get-Content public.pem -Raw
$publicKeyFormatted = $publicKey -replace "`r`n", "\n"
Write-Host "RSA_PUBLIC_KEY=`"$publicKeyFormatted`""

# Leer clave privada
$privateKey = Get-Content private.pem -Raw
$privateKeyFormatted = $privateKey -replace "`r`n", "\n"
Write-Host "RSA_PRIVATE_KEY=`"$privateKeyFormatted`""
```

### 5. Configurar Variables de Entorno

**Opción A: Archivo .env (Desarrollo)**

Cree un archivo `.env` en la raíz del proyecto:

```env
RSA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...\n-----END PUBLIC KEY-----"
RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQD...\n-----END PRIVATE KEY-----"
```

**Opción B: Variables de Entorno del Sistema (Producción)**

**En Linux/Mac:**
```bash
# Temporal (solo sesión actual)
export RSA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."
export RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n..."

# Permanente (agregar a ~/.bashrc o ~/.profile)
echo 'export RSA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."' >> ~/.bashrc
echo 'export RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n..."' >> ~/.bashrc
source ~/.bashrc
```

**En Windows:**
```powershell
# Temporal (solo sesión actual)
$env:RSA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."
$env:RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n..."

# Permanente (variables de entorno del sistema)
# Panel de Control > Sistema > Configuración avanzada del sistema > Variables de entorno
```

### 6. Cargar Variables de Entorno en PHP

Agregue al inicio de su aplicación (antes de usar la clase Encryption):

```php
// Cargar variables desde archivo .env si existe
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Saltar comentarios
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $value = trim($value);
            // Remover comillas si están presentes
            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }
            // Convertir \n a saltos de línea reales
            $value = str_replace('\\n', "\n", $value);
            putenv(trim($name) . '=' . $value);
            $_ENV[trim($name)] = $value;
        }
    }
}
```

## Seguridad Importante

1. **Nunca compartir la clave privada**: La clave privada debe mantenerse en secreto absoluto
2. **Proteger archivos de claves**: Establecer permisos restrictivos (chmod 600 en Linux/Mac)
3. **No commit al repositorio**: Nunca agregar archivos de claves o .env al control de versiones
4. **Rotación de claves**: Genere nuevas claves periódicamente (recomendado cada 6-12 meses)
5. **Backup seguro**: Mantenga copias de seguridad de la clave privada en ubicaciones seguras
6. **Entornos separados**: Use claves diferentes para desarrollo, staging y producción

## Verificación del Sistema

Después de configurar las claves, verifique que el sistema funciona correctamente:

```php
<?php
require_once 'Modelo/Config/Encryption.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;

try {
    $encryption = new Encryption();
    
    $testData = "Datos de prueba para cifrado";
    echo "Original: $testData\n";
    
    $encrypted = $encryption->encrypt($testData);
    echo "Cifrado: $encrypted\n";
    
    $decrypted = $encryption->decrypt($encrypted);
    echo "Descifrado: $decrypted\n";
    
    if ($testData === $decrypted) {
        echo "✓ Cifrado híbrido funcionando correctamente\n";
    } else {
        echo "✗ Error: Los datos descifrados no coinciden\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

## Migración de Datos Existentes

**IMPORTANTE**: Los datos cifrados con el sistema anterior (AES-256-CBC puro) no serán compatibles con el nuevo sistema híbrido.

Para migrar datos existentes:

1. Mantenga ambas versiones de la clase Encryption temporalmente
2. Descifre datos con el sistema antiguo
3. Cifre datos con el nuevo sistema híbrido
4. Actualice la base de datos
5. Elimine el sistema antiguo

## Solución de Problemas

**Error: "Las claves RSA no están configuradas en variables de entorno"**
- Verifique que las variables RSA_PUBLIC_KEY y RSA_PRIVATE_KEY estén configuradas
- Verifique que el archivo .env existe y se está cargando correctamente

**Error: "Las claves RSA no tienen formato PEM válido"**
- Verifique que las claves incluyan los encabezados -----BEGIN/END PUBLIC/PRIVATE KEY-----
- Verifique que los saltos de línea estén correctamente formateados como \n

**Error al cifrar/descifrar**
- Verifique que las claves sean válidas usando los comandos de verificación
- Verifique que la versión de OpenSSL soporte RSA-OAEP (OpenSSL 1.0.2+)
