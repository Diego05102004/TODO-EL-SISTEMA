ALTER TABLE `tbl_clientes`
    MODIFY COLUMN `nombre` VARCHAR(2048) DEFAULT NULL;

DROP PROCEDURE IF EXISTS `sp_modificar_cliente`;
DELIMITER $$
CREATE PROCEDURE `sp_modificar_cliente`(
    IN p_id_cliente INT,
    IN p_nombre VARCHAR(2048),
    IN p_cedula VARCHAR(10),
    IN p_direccion TEXT,
    IN p_telefono VARCHAR(400),
    IN p_correo VARCHAR(550),
    IN p_activo TINYINT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_viejo VARCHAR(2048);
    DECLARE v_cedula_viejo VARCHAR(10);
    DECLARE v_direccion_viejo TEXT;
    DECLARE v_telefono_viejo VARCHAR(255);
    DECLARE v_correo_viejo VARCHAR(255);
    DECLARE v_activo_viejo TINYINT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudieron actualizar los datos del cliente.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`
    INTO v_nombre_viejo, v_cedula_viejo, v_direccion_viejo, v_telefono_viejo, v_correo_viejo, v_activo_viejo
    FROM `tbl_clientes`
    WHERE `id_clientes` = p_id_cliente
    LIMIT 1
    FOR UPDATE;

    UPDATE `tbl_clientes`
    SET `nombre` = p_nombre,
        `cedula` = p_cedula,
        `direccion` = p_direccion,
        `telefono` = p_telefono,
        `correo` = p_correo,
        `activo` = p_activo
    WHERE `id_clientes` = p_id_cliente;

    INSERT INTO `casalai_seguridad`.`tbl_bitacora`
        (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Clientes',
        'MODIFICAR',
        JSON_OBJECT('id_clientes', p_id_cliente, 'nombre', p_nombre, 'cedula', p_cedula, 'direccion', p_direccion, 'telefono', p_telefono, 'correo', p_correo, 'activo', p_activo),
        JSON_OBJECT('id_clientes', p_id_cliente, 'nombre', v_nombre_viejo, 'cedula', v_cedula_viejo, 'direccion', v_direccion_viejo, 'telefono', v_telefono_viejo, 'correo', v_correo_viejo, 'activo', v_activo_viejo),
        p_id_usuario_auditor,
        'media',
        CONCAT('Se actualizaron los datos de contacto del cliente: ', p_nombre, '.')
    );

    COMMIT;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_registrar_cliente`;
DELIMITER $$
CREATE PROCEDURE `sp_registrar_cliente`(
    IN p_nombre VARCHAR(2048),
    IN p_cedula VARCHAR(10),
    IN p_direccion TEXT,
    IN p_telefono VARCHAR(400),
    IN p_correo VARCHAR(550),
    IN p_activo TINYINT(1),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id_cliente INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar el registro del cliente.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    INSERT INTO `tbl_clientes` (`nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`)
    VALUES (p_nombre, p_cedula, p_direccion, p_telefono, p_correo, 1);

    SET v_nuevo_id_cliente = LAST_INSERT_ID();

    INSERT INTO `casalai_seguridad`.`tbl_bitacora`
        (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Clientes',
        'INCLUIR',
        JSON_OBJECT('id_clientes', v_nuevo_id_cliente, 'nombre', p_nombre, 'cedula', p_cedula, 'correo', p_correo, 'activo', 1),
        NULL,
        p_id_usuario_auditor,
        'media',
        CONCAT('Se incluyó un nuevo cliente en el sistema: ', p_nombre, ' (C.I: ', p_cedula, ')')
    );

    COMMIT;
END$$
DELIMITER ;
