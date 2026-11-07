# Pruebas de Carga con Siege - Proyecto Casalai CA

Este directorio contiene scripts de Siege para realizar pruebas de carga y estrés sobre los endpoints de la API del proyecto Casalai CA.

## Requisitos

- Kali Linux (o Debian/Ubuntu)
- Siege instalado: `sudo apt-get install siege`
- Servidor API corriendo en `http://192.168.10.1:8000`
- Microservicio OCR corriendo en `http://192.168.10.1:8000`

## Estructura de Archivos

```
pruebas_siege/
├── urls_login.txt              # URLs para pruebas de autenticación
├── urls_catalogo.txt           # URLs para pruebas de catálogo
├── urls_ocr.txt                # URLs para pruebas de OCR
├── urls_ventas.txt             # URLs para pruebas de ventas
├── run_pruebas_login.sh        # Script de pruebas de login
├── run_pruebas_catalogo.sh     # Script de pruebas de catálogo
├── run_pruebas_ocr.sh          # Script de pruebas de OCR
├── run_pruebas_ventas.sh       # Script de pruebas de ventas
├── run_todas_pruebas.sh        # Script maestro (ejecuta todo)
└── README.md                   # Este archivo
```

## Módulos Probados

### 1. Módulo de Autenticación (Login)
- **Endpoint**: `POST /api/login.php`
- **Criterios de Aceptación**:
  - Bajo (1-5 concurrentes): 0.4s
  - Medio (6-15 concurrentes): 0.9s
  - Alto (16-35+ concurrentes): 1.8s
- **RNF**: RNF03 de eficiencia

### 2. Módulo de Catálogo
- **Endpoint**: `GET /api/productos.php`
- **Criterios de Aceptación**:
  - Bajo (1-5 concurrentes): 0.5s
  - Medio (6-15 concurrentes): 1.2s
  - Alto (16-35+ concurrentes): 2.5s
- **RNF**: RNF05 de la App

### 3. Módulo de Recepción Asistida por IA (OCR)
- **Endpoint**: `POST /fase1/extraer` (Microservicio)
- **Criterios de Aceptación**:
  - Bajo (1-5 concurrentes): 1.5s (TMR)
  - Medio (6-15 concurrentes): 2.8s (TMR)
  - Alto (16-35+ concurrentes): 4.2s (Límite RNF11: 5s)
- **RNF**: RNF11 del Microservicio

### 4. Módulo de Ventas Presenciales
- **Endpoint**: `POST /api/gestionarFactura.php`
- **Criterios de Aceptación**:
  - Bajo (1-5 concurrentes): 0.6s
  - Medio (6-15 concurrentes): 1.4s
  - Alto (16-35+ concurrentes): 2.9s
- **RNF**: ACID en MySQL

## Uso

### Ejecutar todas las pruebas

```bash
cd pruebas_siege
chmod +x run_todas_pruebas.sh
./run_todas_pruebas.sh
```

### Ejecutar pruebas de un módulo específico

```bash
cd pruebas_siege
chmod +x run_pruebas_login.sh
./run_pruebas_login.sh
```

### Ejecutar pruebas personalizadas con Siege

```bash
# Prueba simple
siege -c 10 -r 20 -f urls_login.txt

# Prueba con log
siege -c 10 -r 20 -f urls_login.txt --log=mi_prueba.log --timestamp

# Prueba por tiempo (duración)
siege -c 15 -t 30S -f urls_catalogo.txt
```

## Parámetros de Siege

- `-c N`: Número de usuarios concurrentes
- `-r N`: Número de repeticiones por usuario
- `-t T`: Duración de la prueba (ej: 30S, 1M, 5H)
- `-f FILE`: Archivo con URLs
- `--log=FILE`: Archivo de log
- `--timestamp`: Agregar timestamp a cada solicitud

## Escenarios de Prueba

### Escenario 1: Pocos Usuarios (1-5 concurrentes)
- Objetivo: Verificar rendimiento óptimo en carga ligera
- Concurrentes: 3 usuarios
- Repeticiones: 10-15 por usuario

### Escenario 2: Usuarios Intermedios (6-15 concurrentes)
- Objetivo: Verificar estabilidad en carga moderada
- Concurrentes: 10 usuarios
- Repeticiones: 20 por usuario

### Escenario 3: Muchos Usuarios / Pico (16-35+ concurrentes)
- Objetivo: Verificar resistencia bajo carga alta
- Concurrentes: 20-25 usuarios
- Repeticiones: 30 por usuario

## Análisis de Resultados

Los resultados se guardan en directorios específicos:

```
resultados_login/
├── escenario1_bajo.log
├── escenario2_medio.log
└── escenario3_alto.log
```

Para ver estadísticas:

```bash
# Ver estadísticas de un log
siege --log=resultados_login/escenario1_bajo.log --file=urls_login.txt --list

# Ver resumen de Siege
cat resultados_login/escenario1_bajo.log
```

Métricas importantes:
- **Response Time**: Tiempo de respuesta promedio
- **Transaction Rate**: Transacciones por segundo
- **Throughput**: Datos transferidos por segundo
- **Availability**: Porcentaje de disponibilidad
- **Failed Transactions**: Solicitudes fallidas

## Configuración de OCR

Para las pruebas de OCR, necesitas:
1. Una imagen de factura de prueba en `/path/to/test_factura.jpg`
2. Editar `urls_ocr.txt` con la ruta correcta de la imagen
3. El microservicio OCR debe estar corriendo en el puerto 8000

## Consideraciones

- Las pruebas de login requieren credenciales válidas. Modifica `urls_login.txt` con datos de prueba reales.
- Las pruebas de ventas requieren productos válidos en la base de datos. Modifica `urls_ventas.txt` con IDs de productos existentes.
- Para pruebas en producción, reduce el número de repeticiones para evitar impactar la operación real.
- Siege puede causar estrés significativo en el servidor. Asegúrate de tener monitoreo activo durante las pruebas.

## Solución de Problemas

### Siege no reconoce el comando
```bash
sudo apt-get install siege
```

### Error de conexión
- Verifica que el servidor esté corriendo
- Verifica la IP y puerto en los archivos de URLs
- Verifica el firewall en el servidor

### Timeout en OCR
- Las pruebas de OCR son más lentas por naturaleza
- Aumenta el timeout en la configuración de Siege (`/etc/siege/siege.conf`)
- Ajusta el parámetro `timeout` si es necesario

## Referencias

- Documentación de Siege: https://www.joedog.org/siege-home/
- RNF del proyecto: Consultar documentación del proyecto Casalai CA
