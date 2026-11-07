# Configuración del Microservicio de Casa Lai, C.A.

## 📋 Resumen

Este documento explica cómo iniciar y configurar el microservicio unificado que incluye:
- **Servicio de Recepción y Auditoría** (OCR + Verificación de facturas)
- **Servicio de Analítica Predictiva** (Predicción de demanda + Combos)

---

## 🚀 Iniciar el Microservicio

### Opción 1: Usar el script PowerShell (Recomendado)

```powershell
cd C:\xampp\htdocs\proyecto-casalai-ca\microservicio
.\iniciar_servicio.ps1
```

### Opción 2: Usar el script Batch

```cmd
cd C:\xampp\htdocs\proyecto-casalai-ca\microservicio
iniciar_servicio.bat
```

### Opción 3: Manualmente

```powershell
cd C:\xampp\htdocs\proyecto-casalai-ca\microservicio
$env:ANALITICA_PREDICTIVA_TOKEN="7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M"
python -m uvicorn main:app --host 127.0.0.1 --port 8000
```

---

## 🔧 Configuración del Token de Analítica

El token de analítica debe estar configurado en AMBOS lados:

### 1. En el microservicio Python (ya configurado)

El token se configura automáticamente al iniciar el servidor con los scripts anteriores.

### 2. En el entorno PHP/XAMPP (IMPORTANTE)

Para que el sistema PHP pueda comunicarse con el microservicio, debes configurar la variable de entorno en XAMPP:

#### Opción A: Configurar variable de entorno del sistema (Windows)

1. Presiona `Win + R` y escribe `sysdm.cpl`
2. Ve a la pestaña "Avanzado"
3. Haz clic en "Variables de entorno"
4. En "Variables del sistema", agrega una nueva variable:
   - **Nombre de variable:** `ANALITICA_PREDICTIVA_TOKEN`
   - **Valor de variable:** `7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M`
5. Reinicia Apache/XAMPP

#### Opción B: Configurar en el archivo de configuración de PHP

1. Edita el archivo `C:\xampp\apache\conf\httpd.conf`
2. Agrega al final del archivo:
```apache
SetEnv ANALITICA_PREDICTIVA_TOKEN "7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M"
```
3. Reinicia Apache

#### Opción C: Configurar en el archivo .env del proyecto PHP

Si tu proyecto PHP usa variables de entorno:

1. Crea o edita el archivo `.env` en la raíz del proyecto PHP
2. Agrega:
```
ANALITICA_PREDICTIVA_TOKEN=7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M
ANALITICA_PREDICTIVA_URL=http://127.0.0.1:8000
```

---

## ✅ Verificación

### 1. Verificar que el servidor Python está corriendo

Abre en tu navegador:
- http://127.0.0.1:8000 - Debe mostrar: `{"status":"online",...}`
- http://127.0.0.1:8000/api/v1/analitica - Debe mostrar: `{"status":"online","service":"Analítica Predictiva"}`

### 2. Verificar que el token está configurado en PHP

Crea un archivo de prueba `test_token.php`:
```php
<?php
echo "Token configurado: " . getenv('ANALITICA_PREDICTIVA_TOKEN');
?>
```

Accede a: `http://localhost/test_token.php`

Si muestra el token, está configurado correctamente.

---

## 🛠️ Solución de Problemas

### Error: "No se pudo consultar la analítica"

**Causa:** El token no está configurado en el entorno PHP.

**Solución:** Sigue los pasos de la sección "Configuración del Token de Analítica" - Opción B o C.

### Error: "solo se permite un uso de cada dirección de socket"

**Causa:** El puerto 8000 ya está en uso por otro proceso.

**Solución:** Usa los scripts `iniciar_servicio.ps1` o `iniciar_servicio.bat` que automáticamente matan el proceso anterior.

### Error: "Token de analítica inválido"

**Causa:** El token configurado en PHP no coincide con el del microservicio Python.

**Solución:** Asegúrate de usar el mismo token en ambos lados: `7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M`

---

## 📡 Endpoints Disponibles

### Servidor Unificado (http://127.0.0.1:8000)

#### Recepción y Auditoría
- `GET /health` - Health check
- `POST /fase1/extraer` - Extraer datos de factura
- `POST /fase1/verificar` - Verificar coherencia
- `POST /fase1/comparar-directo` - Comparación directa
- `GET /fase1/estadisticas` - Estadísticas

#### Analítica Predictiva
- `GET /api/v1/analitica` - Estado del servicio
- `POST /api/v1/analitica/dashboard` - Dashboard de predicciones
- `POST /api/v1/analitica/entrenar` - Entrenar modelos
- `POST /api/v1/analitica/feedback` - Feedback de recomendaciones

---

## 📝 Notas Importantes

1. **El servidor debe estar corriendo siempre** que uses el sistema de analítica o recepción
2. **El token debe ser el mismo** en Python y PHP
3. **No cierres la terminal** donde está corriendo el servidor (o ejecútalo en background)
4. **Port 8000** es el puerto configurado (puedes cambiarlo si es necesario)

---

## 🔄 Flujo de Trabajo Recomendado

1. Iniciar el microservicio (usar script `.ps1` o `.bat`)
2. Verificar que cargue sin errores
3. Probar los endpoints en el navegador
4. Usar el sistema PHP normalmente

---
