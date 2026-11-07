# Pruebas de Servidor con ApacheBench (ab) - Proyecto Casalai CA

Este directorio contiene scripts de ApacheBench para realizar pruebas de servidor y componentes del sistema.

## Requisitos

- Kali Linux (o Debian/Ubuntu)
- ApacheBench instalado: `sudo apt-get install apache2-utils`
- Servidor corriendo en `http://192.168.10.1/proyecto-casalai-ca/`
- Microservicio OCR corriendo en `http://192.168.10.1:8000`

## Estructura de Archivos

```
pruebas_ab/
├── run_completo_apachebench.sh    # Script principal de pruebas
├── COMANDO_TERMINAL_AB.txt        # Comando único para copiar y pegar
├── pruebas_especificas_mysql.sh   # Pruebas específicas de MySQL
├── pruebas_disponibilidad.sh      # Pruebas de disponibilidad y tolerancia
└── README.md                      # Este archivo
```

## Componentes Evaluados

### 1. Servidor Web Principal (Apache/PHP)
- **Prueba**: Monitoreo de hilos concurrentes y peticiones masivas
- **Parámetros**: 1,000 peticiones con 50 usuarios concurrentes
- **Métricas**:
  - Estabilidad de sesiones activas
  - Tiempo de respuesta general sin errores
- **Criterio**: 0% de fallas, rendimiento > 700 req/s, tiempos < 110ms

### 2. Microservicio Cognitivo (FastAPI/Uvicorn)
- **Prueba**: Solicitudes asíncronas de OCR y Market Basket Analysis
- **Parámetros**: 500 peticiones con 20 concurrentes
- **Métricas**:
  - Consumo de RAM en picos de minería (RNF17)
  - Latencia de comunicación API REST
- **Criterio**: Ejecución asíncrona aislada sin bloquear PHP

### 3. Gestor de Base de Datos (MySQL/MariaDB)
- **Prueba**: Operaciones transaccionales cruzadas
- **Parámetros**: 500-1000 peticiones con 25-30 concurrentes
- **Métricas**:
  - Integridad transaccional ACID
  - Ejecución limpia de procedimientos almacenados
  - Sin bloqueos de tablas (table locks)
- **Criterio**: Respuesta atómica garantizando consistencia

### 4. Disponibilidad y Tolerancia a Fallos
- **Prueba**: Health checks y simulación de interrupciones
- **Parámetros**: 120 health checks continuos
- **Métricas**:
  - Disponibilidad operativa en horario laboral
  - Efectividad de conmutación al modo manual
- **Criterio**: 100% de éxito en conmutación

## Uso

### Opción 1: Comando Único (Recomendado)

Copia el contenido de `COMANDO_TERMINAL_AB.txt` y pégalo directamente en la terminal de Kali Linux:

```bash
# Copiar el contenido de COMANDO_TERMINAL_AB.txt y pegar en terminal
```

### Opción 2: Script Shell

```bash
cd pruebas_ab
chmod +x run_completo_apachebench.sh
./run_completo_apachebench.sh
```

### Opción 3: Pruebas Específicas

#### Pruebas de MySQL/MariaDB

```bash
chmod +x pruebas_especificas_mysql.sh
./pruebas_especificas_mysql.sh
```

#### Pruebas de Disponibilidad

```bash
chmod +x pruebas_disponibilidad.sh
./pruebas_disponibilidad.sh
```

## Parámetros de ApacheBench

- `-n N`: Número total de peticiones
- `-c N`: Número de peticiones concurrentes
- `-t T`: Duración de la prueba (ej: 30s)
- `-p FILE`: Archivo de datos para POST
- `-T TYPE`: Content-Type para POST
- `-g FILE`: Archivo TSV con datos para gráficos
- `-k`: Usar Keep-Alive

## Archivos Generados

Los resultados se guardan en `~/auditoria_evidencias/`:

### Pruebas Generales
- `apache_servidor_web.txt` - Servidor web principal
- `apache_login.txt` - Endpoint de login
- `apache_catalogo.txt` - Endpoint de catálogo
- `fastapi_health.txt` - Health check microservicio
- `fastapi_ocr.txt` - Endpoint OCR
- `fastapi_analitica.txt` - Endpoint de analítica
- `mysql_ventas.txt` - Transacciones de ventas
- `mysql_recepcion.txt` - Operaciones de recepción
- `contingencia.txt` - Conmutación al modo manual

### Archivos TSV (para gráficos)
- `apache_servidor_web.tsv`
- `apache_login.tsv`
- `apache_catalogo.tsv`
- `fastapi_health.tsv`
- `fastapi_ocr.tsv`
- `fastapi_analitica.tsv`
- `mysql_ventas.tsv`
- `mysql_recepcion.tsv`
- `contingencia.tsv`

### Pruebas Específicas de MySQL
Guardados en `~/auditoria_evidencias/mysql_especifico/`:
- `recepciones_masivas.txt` - Inserciones masivas en recepciones
- `ventas_simultaneas.txt` - Ventas presenciales simultáneas
- `bitacora_concurrente.txt` - Registros de bitácora
- `procedimientos_almacenados.txt` - Procedimientos almacenados
- `triggers_no_lock.txt` - Disparadores sin bloqueos
- `consistencia_acid.txt` - Consistencia transaccional

### Pruebas de Disponibilidad
Guardados en `~/auditoria_evidencias/disponibilidad/`:
- `health_log.txt` - Log de health checks
- `microservicio_health_log.txt` - Health checks del microservicio
- `uptime_log.txt` - Log de uptime
- `microservicio_antes.txt` - Estado antes de fallo
- `microservicio_despues.txt` - Estado después de fallo
- `sistema_principal_sin_micro.txt` - Sistema sin microservicio
- `catalogo_manual.txt` - Catálogo en modo manual
- `login_manual.txt` - Login en modo manual
- `latencia_alta.txt` - Prueba con latencia alta

## Análisis de Resultados

### Ver métricas clave

```bash
# Solicitudes por segundo (rendimiento)
grep 'Requests per second' ~/auditoria_evidencias/*.txt

# Tiempo por solicitud (latencia)
grep 'Time per request' ~/auditoria_evidencias/*.txt

# Solicitudes fallidas
grep 'Failed requests' ~/auditoria_evidencias/*.txt

# Respuestas no exitosas
grep 'Non-2xx responses' ~/auditoria_evidencias/*.txt
```

### Ver resultados detallados

```bash
cat ~/auditoria_evidencias/apache_servidor_web.txt
cat ~/auditoria_evidencias/fastapi_ocr.txt
cat ~/auditoria_evidencias/mysql_ventas.txt
```

### Generar gráficos con archivos TSV

Los archivos `.tsv` pueden usarse para generar gráficos de rendimiento:
- GNUplot
- Excel/Google Sheets
- Scripts de visualización

## Escenarios de Prueba

### Servidor Web Principal
- **Carga**: 1,000 peticiones, 50 concurrentes
- **Objetivo**: Verificar estabilidad de sesiones y tiempos de respuesta
- **Criterio**: 0% fallas, > 700 req/s, < 110ms

### Microservicio Cognitivo
- **Carga**: 500 peticiones, 20 concurrentes
- **Objetivo**: Verificar latencia y consumo de RAM
- **Criterio**: Ejecución asíncrona sin bloquear PHP

### Base de Datos
- **Carga**: 500-1,000 peticiones, 25-30 concurrentes
- **Objetivo**: Verificar integridad ACID y sin bloqueos
- **Criterio**: Respuesta atómica y consistencia

### Disponibilidad
- **Carga**: 120 health checks continuos
- **Objetivo**: Verificar uptime y conmutación manual
- **Criterio**: 100% éxito en conmutación

## Interpretación de Resultados

### Métricas Importantes

1. **Requests per second**: Rendimiento del servidor
   - Bueno: > 500 req/s
   - Excelente: > 700 req/s

2. **Time per request**: Latencia promedio
   - Bueno: < 200ms
   - Excelente: < 100ms

3. **Failed requests**: Solicitudes fallidas
   - Aceptable: < 1%
   - Excelente: 0%

4. **Non-2xx responses**: Errores HTTP
   - Debe ser 0

5. **Transfer rate**: Tasa de transferencia
   - Indica ancho de banda utilizado

## Solución de Problemas

### ApacheBench no está instalado
```bash
sudo apt-get install apache2-utils
```

### Error de conexión
- Verificar que el servidor esté corriendo
- Verificar la IP y puerto
- Verificar el firewall

### Timeout en pruebas
- Aumentar el timeout con `-t`
- Reducir el número de concurrentes

### Errores en POST requests
- Verificar que el archivo JSON sea válido
- Verificar el Content-Type
- Verificar que los datos sean correctos

## Referencias

- Documentación de ApacheBench: `man ab`
- RNF del proyecto: RNF03, RNF05, RNF11, RNF13, RNF17
- Cuadro de Procedimientos y Evaluación del Servidor
