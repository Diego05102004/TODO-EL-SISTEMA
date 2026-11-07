# Guía para solucionar Error 500 en Hosting

## Problemas identificados y corregidos

1. ✅ **Rutas con backslashes corregidas**: Se cambiaron `\` por `/` en `paths.php` para compatibilidad con servidores Linux
2. ✅ **Display errors habilitado**: Se habilitó en `index.php` para ver el error real
3. ✅ **WebSocket adaptado para hosting compartido**: `start_websocket.php` ahora maneja restricciones de permisos y funciones deshabilitadas

## Errores específicos de InfinityFree/Hosting Compartido

Si ves estos errores:
- `file_put_contents(/tmp/websocket_casalai.lock): Permission denied`
- `Call to undefined function exec()`

**Ya están corregidos**. El archivo `start_websocket.php` ha sido modificado para:
- Usar un directorio alternativo si `/tmp/` no tiene permisos
- Verificar si `exec()` está disponible antes de usarla
- No causar errores fatales si el websocket no puede iniciarse

**El sitio funcionará sin WebSocket**. Las características en tiempo real no estarán disponibles en hosting compartido.

## Pasos para solucionar el Error 500

### 1. Configurar Base de Datos

El archivo `Modelo/Config/database.php` tiene credenciales locales. Debes actualizarlo con las credenciales de tu hosting:

```php
// En Modelo/Config/database.php:
define('DB_PRINCIPAL', [
    'host' => 'localhost',  // O el host que te proporcione tu hosting (InfinityFree usa: sqlxxx.infinityfree.com)
    'dbname' => 'casalai_principal',  // Nombre de tu base de datos en el hosting
    'user' => 'tu_usuario',  // Usuario de base de datos del hosting
    'pass' => 'tu_contraseña',  // Contraseña de base de datos del hosting
    'charset' => 'utf8'
]);
```

**IMPORTANTE**: 
- Crea la base de datos `casalai_principal` en tu hosting usando el archivo `assets/BD/casalai_principal.sql`
- En InfinityFree, el host de base de datos NO es localhost, revisa en tu panel
- Verifica que el usuario tenga permisos suficientes

### 2. Verificar Permisos de Archivos

Los archivos deben tener los permisos correctos:
- **Archivos PHP**: 644 (rw-r--r--)
- **Directorios**: 755 (rwxr-xr-x)
- **Directorio vendor**: 755

Puedes cambiar permisos desde tu cliente FTP o desde el panel del hosting.

### 3. Verificar Versión de PHP

El proyecto requiere PHP 8.0 o superior. En InfinityFree:
- Ve al panel de control
- Selecciona "PHP Version"
- Elige PHP 8.0 o superior

### 4. Verificar Composer/Vendor

El proyecto depende de `vendor/autoload.php`. Asegúrate de subir todo el directorio `vendor/` al hosting.

### 5. Verificar Extensiones de PHP

El proyecto requiere estas extensiones (generalmente habilitadas por defecto):
- PDO
- PDO_MySQL
- mbstring
- json
- session

### 6. Subir los Archivos

Sube todos los archivos al directorio `htdocs` de tu hosting:
- **NO** subas: `.git`, `.venv`, `node_modules`, `tests`, archivos de debug
- **SI** sube: Todo lo demás incluyendo `vendor/`

### 7. Restricciones de Hosting Compartido

**WebSocket no funcionará** en hosting compartido (InfinityFree, 000webhost, etc.) porque:
- No tiene permisos para escribir en `/tmp/`
- La función `exec()` está deshabilitada por seguridad
- No puede ejecutar procesos en segundo plano

**Esto no afecta el funcionamiento principal del sitio**. El sitio funcionará normalmente sin características en tiempo real.

### 8. Crear directorio tmp (opcional)

Si el websocket intenta crear archivos temporales, crea un directorio `tmp` en la raíz con permisos 755.

## Checklist antes de subir

- [ ] Base de datos creada en el hosting con el archivo SQL
- [ ] Credenciales de base de datos actualizadas en `database.php` (revisar host correcto)
- [ ] Directorio `vendor/` subido completo
- [ ] Permisos de archivos correctos (644 para archivos, 755 para directorios)
- [ ] Versión de PHP >= 8.0
- [ ] Extensiones PHP requeridas habilitadas
- [ ] Archivos de desarrollo no subidos (.git, .venv, tests)

## Errores comunes en InfinityFree

1. **"Connection failed"**: El host de base de datos NO es localhost. Revisa en tu panel de InfinityFree el host correcto (sqlxxx.infinityfree.com)
2. **"Access denied for user"**: Usuario o contraseña incorrectos de base de datos
3. **"Unknown database"**: La base de datos no existe o tiene un nombre diferente

## Después de solucionar

Una vez que el sitio funcione, **deshabilita** los errores en producción editando `index.php`:

```php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
```
