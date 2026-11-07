-- =============================================================================
-- PRÁCTICA DE CONCURRENCIA, AISLAMIENTO Y BLOQUEOS
-- ASIGNATURA: LABORATORIO DE SOFTWARE
-- BASE DE DATOS: casalai_test
-- Grupo de Proyecto:

-- Braynt de Jesús Medina Briseño 
-- Cedula: 28.406.324
-- Correo: DarckortGame@gmail.com 

-- Paula Yeanmary Rivero Paiva 
-- Cedula: 30.125.965
-- Correo: paulagrivero@gmail.com 

-- Simón José Freitez Díaz 
-- Cedula: 30.335.417
-- Correo: simonjfreitezd21103@gmail.com

-- Diego Andrés López Vivas 
-- Cedula: 31.766.314
-- Correo: diego0510lopez@gmail.com
-- =============================================================================

-- Nota: Se desactiva el autocommit para controlar manualmente las transacciones.
SET autocommit = 0;

-- =============================================================================
-- PUNTO 1: TRANSACTION ISOLATION LEVEL
-- =============================================================================

--------------------------------------------------------------------------------
-- ESCENARIO 1: READ UNCOMMITTED (Demostración de Lecturas Sucias)
--------------------------------------------------------------------------------

-- [Paso 1] - El Usuario A : Configura el aislamiento e inicia transacción.
SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;
START TRANSACTION;

-- [Paso 2] - El Usuario A : Actualiza la cabecera (Maestra) e inserta en el detalle.
UPDATE tbl_carrito SET fecha_creacion = NOW() WHERE id_carrito = 12;
INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad, estatus) VALUES (12, 28, 3, 'activo');

-- [Paso 3] - El Usuario B : Configura mismo aislamiento y consulta concurrentemente.
SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;
START TRANSACTION;
SELECT c.id_carrito, cl.nombre, p.id_producto, p.nombre_producto, cd.cantidad, p.precio, 
       (p.precio * cd.cantidad) AS Monto_total, c.fecha_creacion
FROM tbl_carrito AS c
INNER JOIN tbl_carritodetalle AS cd ON c.id_carrito = cd.id_carrito
INNER JOIN tbl_productos AS p ON cd.id_producto = p.id_producto
INNER JOIN tbl_clientes AS cl ON cl.id_clientes = c.id_cliente
WHERE c.id_cliente = 12;
-- NOTA: El Usuario B experimenta una LECTURA SUCIA. Puede ver el registro de la "EPSON EcoTank" 
-- con cantidad 3 y fecha actualizada, a pesar de que El Usuario A NO ha hecho COMMIT.

-- [Paso 4] - El Usuario A : Deshace los cambios simulando un fallo.
ROLLBACK;

-- [Paso 5] - El Usuario B : Vuelve a consultar tras el Rollback del Usuario A.
-- (Ejecutar misma consulta SELECT del Paso 3)
-- NOTA: El registro fantasma desaparece. El Usuario B trabajó con datos que nunca se consolidaron.
COMMIT;


--------------------------------------------------------------------------------
-- ESCENARIO 2: READ COMMITTED (Evita Lectura Sucia / Permite Lectura No Repetible)
--------------------------------------------------------------------------------

-- [Paso 1] - El Usuario A : Configura nivel y abre transacción.
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
UPDATE tbl_carrito SET fecha_creacion = NOW() WHERE id_carrito = 12;
INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad, estatus) VALUES (12, 28, 3, 'activo');

-- [Paso 2] - El Usuario B : Configura el nivel de aislamiento y consulta mientras El Usuario A sigue ejecutando su transaccion.
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
-- (Ejecutar consulta SELECT base)
-- NOTA: El Usuario B NO ve los cambios de El Usuario A (Evita la lectura sucia de la EPSON).

-- [Paso 3] - El Usuario A : Confirma y consolida la transacción.
COMMIT;

-- [Paso 4] - El Usuario B : Consulta de nuevo dentro de su misma transacción.
-- (Ejecutar consulta SELECT base)
-- NOTA: Ahora El Usuario B SÍ ve los cambios confirmados. Se evidencia una LECTURA NO REPETIBLE 
-- porque los datos cambiaron a mitad de su transacción activa.
COMMIT;


--------------------------------------------------------------------------------
-- ESCENARIO 3: REPEATABLE READ (Aislamiento por Instantánea - Por defecto)
--------------------------------------------------------------------------------

-- [Paso 1] - El Usuario B : Inicia primero su transacción de lectura.
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
START TRANSACTION;
-- (Ejecutar consulta SELECT base -> Muestra estado actual con 2 registros)

-- [Paso 2] - El Usuario A : Modifica el detalle concurrentemente y hace COMMIT.
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
START TRANSACTION;
INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad, estatus) VALUES (12, 38, 2, 'activo');
COMMIT;

-- [Paso 3] - El Usuario B : Vuelve a consultar dentro de su transacción abierta.
-- (Ejecutar consulta SELECT base)
-- NOTA: El resultado sigue mostrando 2 registros. Se ignora el COMMIT de la El Usuario A 
-- gracias al control de multi-versión (MVCC) que mantiene la consistencia de lectura.

-- [Paso 4] - El Usuario B : Finaliza y abre una nueva consulta.
COMMIT;
-- (Ejecutar consulta SELECT base -> Ahora sí se lee el tercer registro insertado: "Auriculares Redmi").


--------------------------------------------------------------------------------
-- ESCENARIO 4: SERIALIZABLE (Bloqueo Total por Concurrencia de Lectura)
--------------------------------------------------------------------------------

-- [Paso 1] - El Usuario B : Abre transacción y lee filas.
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
START TRANSACTION;
-- (Ejecutar consulta SELECT base -> Bloquea implícitamente las filas leídas de forma compartida)

-- [Paso 2] - El Usuario A : Intenta escribir sobre las filas retenidas por El Usuario B.
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
START TRANSACTION;
INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad, estatus) VALUES (12, 35, 1, 'activo');
-- COMPORTAMIENTO: La terminal se congela temporalmente y arroja:
-- "ERROR 1205 (HY000): Lock wait timeout exceeded; try restarting transaction"

-- [Paso 3] - El Usuario B : Libera el recurso compartida.
COMMIT;

-- [Paso 4] - El Usuario A : Reintenta la inserción una vez liberado el canal.
INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad, estatus) VALUES (12, 35, 1, 'activo');
COMMIT; -- Query OK.


-- =============================================================================
-- PUNTO 2: LOCK TABLES Y UNLOCK TABLES (Bloqueo Global de Catálogos)
-- =============================================================================

-- [Paso 1] - El Usuario A : Bloquea explícitamente las tablas Maestras y Puentes en modo exclusivo.
LOCK TABLES tbl_productos WRITE, tbl_combo_detalle WRITE;

-- [Paso 2] - El Usuario A : Ejecuta transacciones de actualización interna con seguridad total.
START TRANSACTION;
UPDATE tbl_productos SET precio = 155.00 WHERE id_producto = 30;
INSERT INTO tbl_combo_detalle (id_combo, id_producto, cantidad) VALUES (14, 30, 5);
COMMIT;

-- [Paso 3] - El Usuario B : Intenta modificar el mismo catálogo de productos en paralelo.
START TRANSACTION;
UPDATE tbl_productos SET precio = 220.00 WHERE id_producto = 28;
-- COMPORTAMIENTO CONCURRENTE: El proceso entra en cola de espera. El tiempo de respuesta de MariaDB 
-- refleja una retención prolongada (ej. 1 min 56.726 sec) esperando la liberación global.

-- [Paso 4] - El Usuario A : Abre el tráfico de peticiones liberando las estructuras.
UNLOCK TABLES;

-- [Paso 5] - El Usuario B : La consulta en espera se procesa inmediatamente de forma automática.
COMMIT;


-- =============================================================================
-- PUNTO 3: LOCK IN SHARE MODE (Garantía Referencial Padre-Hijo)
-- =============================================================================

-- [Paso 1] - El Usuario B (Usuario B - Proceso de Compra): Asegura la existencia del registro Padre (Maestra)
START TRANSACTION;
SELECT id_carrito FROM tbl_carrito WHERE id_carrito = 12 LOCK IN SHARE MODE;
-- NOTA: Añade un candado de lectura compartida sobre el Carrito 12.

-- [Paso 2] - El Usuario A (Usuario A - Proceso Administrativo): Intenta borrar el Carrito Padre.
START TRANSACTION;
DELETE FROM tbl_carrito WHERE id_carrito = 12;
-- COMPORTAMIENTO: El DELETE se queda congelado (Inanición condicional). No puede destruir 
-- el registro porque la El Usuario B lo está usando de referencia viva.

-- [Paso 3] - El Usuario B (Usuario B - Proceso de Compra): Inserta con seguridad las filas hijas (Detalle).
INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad) VALUES (12, 30, 1);
COMMIT;
-- NOTA: Al hacer COMMIT, se liberan los candados compartidos.

-- [Paso 4] - Usuario A (Proceso Administrativo): Se reactiva el DELETE retrasado cronológicamente.
-- El motor ejecuta la instrucción suspendida de forma segura tras la salida del Usuario B .
COMMIT;