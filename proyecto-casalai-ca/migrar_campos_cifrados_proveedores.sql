USE `casalai_principal`;

-- El sobre RSA+AES supera 255 caracteres; se deja margen para campos largos.
ALTER TABLE `tbl_proveedores`
    MODIFY `nombre_proveedor` VARCHAR(2048) NOT NULL,
    MODIFY `nombre_representante` VARCHAR(2048) DEFAULT NULL,
    MODIFY `correo_proveedor` VARCHAR(2048) DEFAULT NULL,
    MODIFY `direccion_proveedor` VARCHAR(2048) DEFAULT NULL,
    MODIFY `telefono_1` VARCHAR(2048) DEFAULT NULL,
    MODIFY `telefono_2` VARCHAR(2048) DEFAULT NULL;

DROP PROCEDURE IF EXISTS `sp_registrar_proveedor`;
DROP PROCEDURE IF EXISTS `sp_modificar_proveedor`;

DELIMITER $$

CREATE PROCEDURE `sp_registrar_proveedor`(
    IN p_nombre_proveedor VARCHAR(2048),
    IN p_rif_proveedor VARCHAR(15),
    IN p_nombre_representante VARCHAR(2048),
    IN p_rif_representante VARCHAR(15),
    IN p_correo_proveedor VARCHAR(2048),
    IN p_direccion_proveedor VARCHAR(2048),
    IN p_telefono_1 VARCHAR(2048),
    IN p_telefono_2 VARCHAR(2048),
    IN p_observacion TEXT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo registrar el proveedor de forma segura.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    INSERT INTO `tbl_proveedores` (
        `nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`,
        `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`
    ) VALUES (
        p_nombre_proveedor, p_rif_proveedor, p_nombre_representante, p_rif_representante,
        p_correo_proveedor, p_direccion_proveedor, p_telefono_1, p_telefono_2, p_observacion
    );

    SET v_nuevo_id = LAST_INSERT_ID();

    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(),
        'Proveedores',
        'INCLUIR',
        JSON_OBJECT(
            'id_proveedor', v_nuevo_id, 'nombre_proveedor', p_nombre_proveedor, 'rif_proveedor', p_rif_proveedor,
            'nombre_representante', p_nombre_representante, 'rif_representante', p_rif_representante,
            'correo_proveedor', p_correo_proveedor, 'direccion_proveedor', p_direccion_proveedor,
            'telefono_1', p_telefono_1, 'telefono_2', p_telefono_2, 'observacion', p_observacion
        ),
        NULL,
        p_id_usuario_auditor,
        'media',
        CONCAT('Se registró un nuevo proveedor en el sistema: "', p_nombre_proveedor, '" (RIF: ', IFNULL(p_rif_proveedor, 'N/A'), ').')
    );

    COMMIT;
END $$

CREATE PROCEDURE `sp_modificar_proveedor`(
    IN p_id_proveedor INT,
    IN p_nombre_proveedor VARCHAR(2048),
    IN p_rif_proveedor VARCHAR(15),
    IN p_nombre_representante VARCHAR(2048),
    IN p_rif_representante VARCHAR(15),
    IN p_correo_proveedor VARCHAR(2048),
    IN p_direccion_proveedor VARCHAR(2048),
    IN p_telefono_1 VARCHAR(2048),
    IN p_telefono_2 VARCHAR(2048),
    IN p_observacion TEXT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_proveedor_viejo VARCHAR(2048);
    DECLARE v_rif_proveedor_viejo VARCHAR(15);
    DECLARE v_nombre_representante_viejo VARCHAR(2048);
    DECLARE v_rif_representante_viejo VARCHAR(15);
    DECLARE v_correo_proveedor_viejo VARCHAR(2048);
    DECLARE v_direccion_proveedor_viejo VARCHAR(2048);
    DECLARE v_telefono_1_viejo VARCHAR(2048);
    DECLARE v_telefono_2_viejo VARCHAR(2048);
    DECLARE v_observacion_viejo TEXT;
    DECLARE v_estado_viejo ENUM('habilitado','inhabilitado');

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudieron guardar los cambios del proveedor.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT
        `nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`,
        `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`, `estado`
    INTO
        v_nombre_proveedor_viejo, v_rif_proveedor_viejo, v_nombre_representante_viejo, v_rif_representante_viejo,
        v_correo_proveedor_viejo, v_direccion_proveedor_viejo, v_telefono_1_viejo, v_telefono_2_viejo, v_observacion_viejo, v_estado_viejo
    FROM `tbl_proveedores`
    WHERE `id_proveedor` = p_id_proveedor
    LIMIT 1
    FOR UPDATE;

    UPDATE `tbl_proveedores` SET
        `nombre_proveedor` = p_nombre_proveedor,
        `rif_proveedor` = p_rif_proveedor,
        `nombre_representante` = p_nombre_representante,
        `rif_representante` = p_rif_representante,
        `correo_proveedor` = p_correo_proveedor,
        `direccion_proveedor` = p_direccion_proveedor,
        `telefono_1` = p_telefono_1,
        `telefono_2` = p_telefono_2,
        `observacion` = p_observacion
    WHERE `id_proveedor` = p_id_proveedor;

    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(),
        'Proveedores',
        'MODIFICAR',
        JSON_OBJECT(
            'id_proveedor', p_id_proveedor, 'nombre_proveedor', p_nombre_proveedor, 'rif_proveedor', p_rif_proveedor,
            'nombre_representante', p_nombre_representante, 'rif_representante', p_rif_representante,
            'correo_proveedor', p_correo_proveedor, 'direccion_proveedor', p_direccion_proveedor,
            'telefono_1', p_telefono_1, 'telefono_2', p_telefono_2, 'observacion', p_observacion, 'estado', v_estado_viejo
        ),
        JSON_OBJECT(
            'id_proveedor', p_id_proveedor, 'nombre_proveedor', v_nombre_proveedor_viejo, 'rif_proveedor', v_rif_proveedor_viejo,
            'nombre_representante', v_nombre_representante_viejo, 'rif_representante', v_rif_representante_viejo,
            'correo_proveedor', v_correo_proveedor_viejo, 'direccion_proveedor', v_direccion_proveedor_viejo,
            'telefono_1', v_telefono_1_viejo, 'telefono_2', v_telefono_2_viejo, 'observacion', v_observacion_viejo, 'estado', v_estado_viejo
        ),
        p_id_usuario_auditor,
        'media',
        CONCAT('Se actualizaron los datos comerciales del proveedor con ID: ', p_id_proveedor, '.')
    );

    COMMIT;
END $$

DELIMITER ;

-- Valores cifrados que ya fueron truncados antes de aplicar esta migración no se pueden descifrar; requieren restauración desde respaldo o reingreso del dato original.
