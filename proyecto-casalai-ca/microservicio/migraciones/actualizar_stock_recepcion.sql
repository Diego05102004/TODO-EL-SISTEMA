-- Ejecutar una sola vez en bases existentes que aún no tengan stock_aplicado.
ALTER TABLE tbl_recepcion_productos
    ADD COLUMN stock_aplicado TINYINT(1) NOT NULL DEFAULT 0;

DROP PROCEDURE IF EXISTS sp_registrar_recepcion;

DELIMITER $$
CREATE PROCEDURE sp_registrar_recepcion(
    IN p_id_proveedor INT,
    IN p_correlativo VARCHAR(255),
    IN p_estado VARCHAR(20),
    IN p_porcentaje_iva DECIMAL(7,2),
    IN p_productos JSON,
    IN p_id_usuario_auditor INT,
    OUT p_id_recepcion INT
)
BEGIN
    DECLARE v_fecha DATE;
    DECLARE v_indice INT DEFAULT 0;
    DECLARE v_cantidad_items INT DEFAULT 0;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_costo DECIMAL(12,2);
    DECLARE v_nombre_producto VARCHAR(255);
    DECLARE v_producto_existe INT DEFAULT 0;
    DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0;
    DECLARE v_monto_iva DECIMAL(12,2) DEFAULT 0;
    DECLARE v_total_factura DECIMAL(12,2) DEFAULT 0;
    DECLARE v_id_detalle INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_recepcion = NULL;
        RESIGNAL;
    END;

    SET p_id_recepcion = NULL;
    SET v_fecha = CURDATE();
    SET v_cantidad_items = JSON_LENGTH(p_productos);

    IF p_id_proveedor IS NULL OR NOT EXISTS (
        SELECT 1 FROM tbl_proveedores WHERE id_proveedor = p_id_proveedor
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El proveedor de la recepción no existe.';
    END IF;
    IF p_correlativo IS NULL OR TRIM(p_correlativo) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El número de factura es obligatorio.';
    END IF;
    IF EXISTS (
        SELECT 1 FROM tbl_recepcion_productos
        WHERE correlativo = p_correlativo AND estado = 'habilitado'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe una recepción activa con ese número de factura.';
    END IF;
    IF p_porcentaje_iva IS NULL OR p_porcentaje_iva < 0 OR p_porcentaje_iva > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El porcentaje de IVA debe estar entre 0 y 100.';
    END IF;
    IF p_estado NOT IN ('habilitado', 'anulado') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El estado de la recepción no es válido.';
    END IF;
    IF p_productos IS NULL OR JSON_VALID(p_productos) = 0 OR v_cantidad_items = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La recepción debe incluir al menos un producto.';
    END IF;

    START TRANSACTION;

    WHILE v_indice < v_cantidad_items DO
        SET v_id_producto = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].id_producto'))) AS UNSIGNED);
        SET v_cantidad = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].cantidad'))) AS UNSIGNED);
        SET v_costo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].costo'))) AS DECIMAL(12,2));

        SELECT COUNT(*), MAX(nombre_producto)
        INTO v_producto_existe, v_nombre_producto
        FROM tbl_productos
        WHERE id_producto = v_id_producto;

        IF v_producto_existe = 0 OR v_cantidad IS NULL OR v_cantidad < 1 OR v_costo IS NULL OR v_costo <= 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Uno de los productos, cantidades o costos de la recepción no es válido.';
        END IF;

        SET v_subtotal = v_subtotal + (v_cantidad * v_costo);
        SET v_indice = v_indice + 1;
    END WHILE;

    SET v_subtotal = ROUND(v_subtotal, 2);
    SET v_monto_iva = ROUND(v_subtotal * p_porcentaje_iva / 100, 2);
    SET v_total_factura = ROUND(v_subtotal + v_monto_iva, 2);

    INSERT INTO tbl_recepcion_productos
        (id_proveedor, fecha, correlativo, estado, subtotal_factura, porcentaje_iva, monto_iva, total_factura, stock_aplicado)
    VALUES
        (p_id_proveedor, v_fecha, TRIM(p_correlativo), p_estado, v_subtotal, p_porcentaje_iva, v_monto_iva, v_total_factura, IF(p_estado = 'habilitado', 1, 0));
    SET p_id_recepcion = LAST_INSERT_ID();

    SET v_indice = 0;
    WHILE v_indice < v_cantidad_items DO
        SET v_id_producto = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].id_producto'))) AS UNSIGNED);
        SET v_cantidad = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].cantidad'))) AS UNSIGNED);
        SET v_costo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].costo'))) AS DECIMAL(12,2));

        INSERT INTO tbl_detalle_recepcion_productos (id_recepcion, id_producto, cantidad, costo)
        VALUES (p_id_recepcion, v_id_producto, v_cantidad, v_costo);
        SET v_id_detalle = LAST_INSERT_ID();

        IF p_estado = 'habilitado' THEN
            UPDATE tbl_productos
            SET stock = COALESCE(stock, 0) + v_cantidad
            WHERE id_producto = v_id_producto;
        END IF;

        SELECT nombre_producto INTO v_nombre_producto
        FROM tbl_productos
        WHERE id_producto = v_id_producto;

        INSERT INTO tbl_ingresos_egresos
            (tipo, monto, descripcion, fecha, estado, id_detalle_recepcion_productos)
        VALUES
            ('egreso', v_costo * v_cantidad, CONCAT('Compra: ', v_nombre_producto, ' (x', v_cantidad, ')'), v_fecha, 1, v_id_detalle);

        SET v_indice = v_indice + 1;
    END WHILE;

    INSERT INTO casalai_seguridad.tbl_bitacora
        (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES
        (NOW(), 'Recepcion', 'INCLUIR',
         JSON_OBJECT('id_recepcion', p_id_recepcion, 'correlativo', p_correlativo, 'subtotal', v_subtotal,
                     'porcentaje_iva', p_porcentaje_iva, 'monto_iva', v_monto_iva, 'total_factura', v_total_factura),
         NULL, p_id_usuario_auditor, 'media', CONCAT('Se registró la recepción ', p_correlativo, '.'));

    COMMIT;
END$$

DROP PROCEDURE IF EXISTS sp_anular_recepcion$$

CREATE PROCEDURE sp_anular_recepcion(
    IN p_correlativo VARCHAR(255),
    IN p_id_usuario_auditor INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE v_id_recepcion INT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_stock_aplicado TINYINT DEFAULT 0;
    DECLARE v_productos_reversion INT DEFAULT 0;
    DECLARE v_existe INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        RESIGNAL;
    END;

    SET p_resultado = 0;
    START TRANSACTION;

    SELECT COUNT(*) INTO v_existe
    FROM tbl_recepcion_productos
    WHERE correlativo = p_correlativo;
    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La recepción no existe.';
    END IF;

    SELECT id_recepcion, estado, stock_aplicado
    INTO v_id_recepcion, v_estado, v_stock_aplicado
    FROM tbl_recepcion_productos
    WHERE correlativo = p_correlativo
    ORDER BY id_recepcion DESC
    LIMIT 1
    FOR UPDATE;

    IF v_estado = 'anulado' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La recepción ya está anulada.';
    END IF;

    IF v_stock_aplicado = 1 THEN
        SELECT COUNT(DISTINCT id_producto)
        INTO v_productos_reversion
        FROM tbl_detalle_recepcion_productos
        WHERE id_recepcion = v_id_recepcion;

        UPDATE tbl_productos p
        INNER JOIN (
            SELECT id_producto, SUM(cantidad) AS cantidad
            FROM tbl_detalle_recepcion_productos
            WHERE id_recepcion = v_id_recepcion
            GROUP BY id_producto
        ) d ON d.id_producto = p.id_producto
        SET p.stock = COALESCE(p.stock, 0) - d.cantidad
        WHERE COALESCE(p.stock, 0) >= d.cantidad;

        IF ROW_COUNT() <> v_productos_reversion THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede anular la recepción porque el stock actual no alcanza para revertirla.';
        END IF;
    END IF;

    UPDATE tbl_recepcion_productos
    SET estado = 'anulado'
    WHERE id_recepcion = v_id_recepcion;

    INSERT INTO casalai_seguridad.tbl_bitacora
        (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES
        (NOW(), 'Recepcion', 'ANULAR',
         JSON_OBJECT('id_recepcion', v_id_recepcion, 'correlativo', p_correlativo, 'estado', 'anulado'),
         JSON_OBJECT('id_recepcion', v_id_recepcion, 'correlativo', p_correlativo, 'estado', v_estado),
         p_id_usuario_auditor, 'media', CONCAT('Se anuló la recepción ', p_correlativo, '.'));

    SET p_resultado = 1;
    COMMIT;
END$$
DELIMITER ;
