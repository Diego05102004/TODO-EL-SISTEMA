DELIMITER $$

CREATE PROCEDURE sp_eliminar_producto(
    IN p_id_producto INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_producto_eliminar VARCHAR(255);
    DECLARE v_serial_producto_eliminar VARCHAR(15);

    -- MANEJADOR ESPECÍFICO PARA RESTRICCIÓN RELACIONAL (Error 1451)
    -- Si la cuenta ya está vinculada a pagos de facturas o compras, impide el borrado.
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: No se puede eliminar producto porque registra movimientos históricos en el sistema. Considere inhabilitarla.';
    END;

    -- Manejador de fallas generales
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar la eliminación física del producto';
    END;

    START TRANSACTION;

    -- Bloqueo exclusivo de seguridad y extracción forense pre-mortem
    SELECT `nombre_producto`, `serial` INTO v_nombre_producto_eliminar, v_serial_producto_eliminar
    FROM `tbl_productos`
    WHERE `id_producto` = p_id_producto
    LIMIT 1 
    FOR UPDATE;

    -- Remoción física del registro
    DELETE FROM `tbl_productos`
    WHERE `id_producto` = p_id_producto;

    -- Envío síncrono a bitácora con prioridad ALTA
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Productos', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_producto', p_id_producto, 'nombre_producto', v_nombre_producto_eliminar, 'serial', v_serial_producto_eliminar), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó físicamente del sistema el producto "', IFNULL(v_nombre_producto_eliminar, 'Desconocido'), '" (SERIAL: ', IFNULL(v_serial_producto_eliminar, 'Desconocido'), ').')
    );

    COMMIT;
END $$

DELIMITER ;
