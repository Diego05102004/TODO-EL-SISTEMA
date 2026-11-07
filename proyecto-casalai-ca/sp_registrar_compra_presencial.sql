-- -----------------------------------------------------------------------------
-- PROCEDIMIENTO: REGISTRAR COMPRA PRESENCIAL
-- Maneja concurrencia con REPEATABLE READ y depuración
-- -----------------------------------------------------------------------------

USE `casalai_principal`;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_registrar_compra_presencial $$

CREATE PROCEDURE sp_registrar_compra_presencial(
    IN p_id_cliente INT,
    IN p_productos JSON,
    IN p_pagos JSON,
    IN p_fecha DATE,
    IN p_id_usuario_auditor INT,
    OUT p_resultado JSON
)
BEGIN
    -- Declaración de variables
    DECLARE v_id_despacho INT;
    DECLARE v_id_factura INT;
    DECLARE v_monto_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_descripcion TEXT DEFAULT 'Venta: ';
    DECLARE v_contador INT DEFAULT 0;
    DECLARE v_total_productos INT;
    DECLARE v_total_pagos INT;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_nombre_producto VARCHAR(255);
    DECLARE v_nombre_modelo VARCHAR(255);
    DECLARE v_nombre_marca VARCHAR(255);
    DECLARE v_serial VARCHAR(100);
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_nombre_cliente VARCHAR(255);
    DECLARE v_cedula VARCHAR(50);
    DECLARE v_telefono VARCHAR(50);
    DECLARE v_correo VARCHAR(255);
    DECLARE v_pago_tipo VARCHAR(50);
    DECLARE v_pago_monto DECIMAL(10,2);
    DECLARE v_pago_referencia VARCHAR(100);
    DECLARE v_pago_id_cuenta INT;
    DECLARE v_pago_comprobante VARCHAR(255);
    DECLARE v_error_msg VARCHAR(500);
    
    -- Variables para construir JSON de productos y pagos
    DECLARE v_productos_json JSON DEFAULT '[]';
    DECLARE v_pagos_json JSON DEFAULT '[]';
    DECLARE v_producto_item JSON;
    DECLARE v_pago_item JSON;
    
    -- Variable para depuración
    DECLARE v_debug_log TEXT DEFAULT '';
    
    -- Manejador de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
        @sqlstate = RETURNED_SQLSTATE, @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
        
        SET v_debug_log = CONCAT(v_debug_log, '[ERROR] Código: ', @errno, ', Mensaje: ', @text);
        
        SET p_resultado = JSON_OBJECT(
            'status', 'error',
            'mensaje', CONCAT('Error interno: ', @text),
            'debug', v_debug_log,
            'codigo_error', @errno
        );
        
        ROLLBACK;
    END;

    IF p_id_usuario_auditor IS NULL OR p_id_usuario_auditor <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El usuario auditor de la venta no es válido.';
    END IF;

    -- Nota: PHP ya maneja la transacción a través de ejecutarConConexionSegura
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Iniciando lógica dentro de transacción existente\n');
    
    -- 1️⃣ Crear despacho
    INSERT INTO tbl_despachos (id_clientes, fecha_despacho, tipocompra, activo)
    VALUES (p_id_cliente, p_fecha, 'Presencial', 1);
    
    SET v_id_despacho = LAST_INSERT_ID();
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Despacho creado ID: ', v_id_despacho, '\n');
    
    -- 2️⃣ Procesar productos
    SET v_total_productos = JSON_LENGTH(p_productos);
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Total productos: ', v_total_productos, '\n');
    
    WHILE v_contador < v_total_productos DO
        -- Extraer datos del producto del JSON
        SET v_id_producto = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].id_producto')));
        SET v_cantidad = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].cantidad')));
        
        -- Convertir cantidad a número si viene como string
        SET v_cantidad = CAST(v_cantidad AS UNSIGNED);
        
        SET v_debug_log = CONCAT(v_debug_log, '[INFO] Procesando producto ID: ', v_id_producto, ', Cantidad: ', v_cantidad, '\n');
        
        -- Insertar detalle de despacho
        INSERT INTO tbl_despacho_detalle (id_despacho, id_producto, cantidad)
        VALUES (v_id_despacho, v_id_producto, v_cantidad);
        
        -- Consultar información del producto
        SELECT 
            p.nombre_producto,
            m.nombre_modelo,
            mar.nombre_marca,
            p.serial,
            p.precio
        INTO v_nombre_producto, v_nombre_modelo, v_nombre_marca, v_serial, v_precio
        FROM tbl_productos p
        INNER JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
        INNER JOIN tbl_marcas mar ON m.id_marca = mar.id_marca
        WHERE p.id_producto = v_id_producto
        FOR UPDATE; -- Bloqueo para concurrencia
        
        -- Calcular subtotal
        SET v_subtotal = v_precio * v_cantidad;
        SET v_monto_total = v_monto_total + v_subtotal;
        
        -- Construir descripción
        SET v_descripcion = CONCAT(v_descripcion, v_nombre_producto, ' (', v_cantidad, '), ');
        
        -- Construir JSON del producto
        SET v_producto_item = JSON_OBJECT(
            'id_producto', v_id_producto,
            'codigo', v_id_producto,
            'nombre', v_nombre_producto,
            'modelo', v_nombre_modelo,
            'marca', v_nombre_marca,
            'serial', v_serial,
            'precio', v_precio,
            'cantidad', v_cantidad,
            'subtotal', v_subtotal
        );
        
        -- Agregar al array de productos
        SET v_productos_json = JSON_ARRAY_APPEND(v_productos_json, '$', v_producto_item);
        
        SET v_debug_log = CONCAT(v_debug_log, '[INFO] Producto: ', v_nombre_producto, ', Subtotal: ', v_subtotal, '\n');
        
        SET v_contador = v_contador + 1;
    END WHILE;
    
    -- Limpiar descripción (quitar última coma)
    SET v_descripcion = TRIM(TRAILING ', ' FROM v_descripcion);
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Monto total: ', v_monto_total, '\n');
    
    -- 3️⃣ Crear factura
    INSERT INTO tbl_facturas (cliente, fecha, descuento, estatus)
    VALUES (p_id_cliente, p_fecha, 0, 'Pagada en Oficina');
    
    SET v_id_factura = LAST_INSERT_ID();
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Factura creada ID: ', v_id_factura, '\n');
    
    -- 4️⃣ Insertar detalles de factura
    SET v_contador = 0;
    WHILE v_contador < v_total_productos DO
        SET v_id_producto = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].id_producto')));
        SET v_cantidad = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].cantidad')));
        SET v_cantidad = CAST(v_cantidad AS UNSIGNED);
        
        INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad)
        VALUES (v_id_factura, v_id_producto, v_cantidad);
        
        SET v_contador = v_contador + 1;
    END WHILE;
    
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Detalles de factura insertados\n');
    
    -- 5️⃣ Consultar cliente
    SELECT nombre, cedula, telefono, correo
    INTO v_nombre_cliente, v_cedula, v_telefono, v_correo
    FROM tbl_clientes
    WHERE id_clientes = p_id_cliente;
    
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Cliente: ', v_nombre_cliente, '\n');
    
    -- 6️⃣ Registrar pagos
    IF p_pagos IS NOT NULL AND JSON_LENGTH(p_pagos) > 0 THEN
        SET v_contador = 0;
        SET v_total_pagos = JSON_LENGTH(p_pagos);
        
        WHILE v_contador < v_total_pagos DO
            SET v_pago_tipo = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].tipo')));
            SET v_pago_monto = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].monto')));
            SET v_pago_referencia = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].referencia')));
            SET v_pago_id_cuenta = JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].cuenta'));
            SET v_pago_comprobante = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].comprobante')));
            
            -- Convertir monto a número
            SET v_pago_monto = CAST(v_pago_monto AS DECIMAL(10,2));
            
            INSERT INTO tbl_detalles_pago (id_factura, tipo, id_cuenta, referencia, monto, comprobante, fecha)
            VALUES (v_id_factura, v_pago_tipo, v_pago_id_cuenta, v_pago_referencia, v_pago_monto, v_pago_comprobante, NOW());
            
            -- Construir JSON del pago
            SET v_pago_item = JSON_OBJECT(
                'tipo', v_pago_tipo,
                'monto', v_pago_monto,
                'referencia', v_pago_referencia,
                'id_cuenta', v_pago_id_cuenta,
                'comprobante', v_pago_comprobante,
                'estatus', 'Pago Procesado'
            );
            
            -- Agregar al array de pagos
            SET v_pagos_json = JSON_ARRAY_APPEND(v_pagos_json, '$', v_pago_item);
            
            SET v_debug_log = CONCAT(v_debug_log, '[INFO] Pago registrado: ', v_pago_tipo, ', Monto: ', v_pago_monto, '\n');
            
            SET v_contador = v_contador + 1;
        END WHILE;
    END IF;
    
    -- 7️⃣ Registrar ingreso en finanzas
    INSERT INTO tbl_ingresos_egresos (id_despacho, tipo, monto, descripcion, fecha, estado)
    VALUES (v_id_despacho, 'ingreso', v_monto_total, CONCAT('Venta presencial #', v_id_factura, ' - ', v_descripcion), NOW(), 1);
    
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Ingreso registrado en finanzas\n');
    
    -- Nota: PHP maneja el COMMIT/ROLLBACK a través de ejecutarConConexionSegura
    
    -- 8️⃣ Construir resultado JSON
    SET p_resultado = JSON_OBJECT(
        'status', 'success',
        'id_factura', v_id_factura,
        'fecha_factura', p_fecha,
        'id_despacho', v_id_despacho,
        'nombre_cliente', v_nombre_cliente,
        'cedula', v_cedula,
        'telefono', v_telefono,
        'correo', v_correo,
        'monto_total', v_monto_total,
        'descripcion', v_descripcion,
        'debug', v_debug_log
    );
    
    -- Insertar arrays JSON correctamente
    SET p_resultado = JSON_SET(p_resultado, '$.productos', v_productos_json);
    SET p_resultado = JSON_SET(p_resultado, '$.pagos', v_pagos_json);

    -- Auditoría síncrona en la base de datos de seguridad.
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`,
        `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(),
        'Compra Fisica',
        'INCLUIR',
        JSON_OBJECT(
            'id_despacho', v_id_despacho,
            'id_factura', v_id_factura,
            'id_cliente', p_id_cliente,
            'nombre_cliente', v_nombre_cliente,
            'productos', JSON_EXTRACT(v_productos_json, '$'),
            'pagos', JSON_EXTRACT(v_pagos_json, '$'),
            'monto_total', v_monto_total,
            'fecha', p_fecha,
            'descripcion', v_descripcion
        ),
        NULL,
        p_id_usuario_auditor,
        'media',
        CONCAT(
            'Se registró una venta presencial #', v_id_factura,
            ' para el cliente "', IFNULL(v_nombre_cliente, 'N/A'), '".'
        )
    );
    
END$$

DELIMITER ;
