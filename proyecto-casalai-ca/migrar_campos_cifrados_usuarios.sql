USE `casalai_seguridad`;

-- El cifrado híbrido produce valores mayores que 255 caracteres.
ALTER TABLE `tbl_usuarios`
    MODIFY `correo` VARCHAR(2048) DEFAULT NULL,
    MODIFY `nombres` VARCHAR(2048) DEFAULT NULL,
    MODIFY `apellidos` VARCHAR(2048) DEFAULT NULL,
    MODIFY `telefono` VARCHAR(2048) DEFAULT NULL;

DROP PROCEDURE IF EXISTS `sp_incluir_usuario`;
DROP PROCEDURE IF EXISTS `sp_modificar_usuario`;

DELIMITER $$

CREATE PROCEDURE `sp_incluir_usuario`(
    IN p_username VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_cedula VARCHAR(10),
    IN p_id_rol INT,
    IN p_correo VARCHAR(2048),
    IN p_nombres VARCHAR(2048),
    IN p_apellidos VARCHAR(2048),
    IN p_telefono VARCHAR(2048),
    IN p_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo registrar el usuario.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `id_rol` FROM `tbl_rol` WHERE `id_rol` = p_id_rol LOCK IN SHARE MODE;

    INSERT INTO `tbl_usuarios`
        (`username`, `password`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono`, `intentos_fallidos`, `estatus`, `foto_perfil`)
    VALUES
        (p_username, p_password, p_cedula, p_id_rol, p_correo, p_nombres, p_apellidos, p_telefono, 0, 'habilitado', NULL);

    SET v_nuevo_id = LAST_INSERT_ID();

    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Usuario',
        'INCLUIR',
        JSON_OBJECT('id_usuario', v_nuevo_id, 'username', p_username, 'cedula', p_cedula, 'id_rol', p_id_rol, 'correo', p_correo, 'estatus', 'habilitado'),
        NULL,
        p_usuario_auditor,
        'media',
        CONCAT('Se incluyó un nuevo usuario en el sistema: ', p_username, ' (C.I: ', p_cedula, ')')
    );

    COMMIT;
END $$

CREATE PROCEDURE `sp_modificar_usuario`(
    IN p_id_usuario INT,
    IN p_username VARCHAR(255),
    IN p_cedula VARCHAR(10),
    IN p_id_rol INT,
    IN p_correo VARCHAR(2048),
    IN p_nombres VARCHAR(2048),
    IN p_apellidos VARCHAR(2048),
    IN p_telefono VARCHAR(2048),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_username VARCHAR(255);
    DECLARE v_cedula VARCHAR(10);
    DECLARE v_id_rol INT;
    DECLARE v_correo VARCHAR(2048);
    DECLARE v_nombres VARCHAR(2048);
    DECLARE v_apellidos VARCHAR(2048);
    DECLARE v_telefono VARCHAR(2048);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo modificar el usuario.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `id_rol` FROM `tbl_rol` WHERE `id_rol` = p_id_rol LOCK IN SHARE MODE;

    SELECT `username`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono`
    INTO v_username, v_cedula, v_id_rol, v_correo, v_nombres, v_apellidos, v_telefono
    FROM `tbl_usuarios`
    WHERE `id_usuario` = p_id_usuario
    LIMIT 1 FOR UPDATE;

    UPDATE `tbl_usuarios`
    SET `username` = p_username,
        `cedula` = p_cedula,
        `id_rol` = p_id_rol,
        `correo` = p_correo,
        `nombres` = p_nombres,
        `apellidos` = p_apellidos,
        `telefono` = p_telefono
    WHERE `id_usuario` = p_id_usuario;

    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Usuario',
        'MODIFICAR',
        JSON_OBJECT('id_usuario', p_id_usuario, 'username', p_username, 'cedula', p_cedula, 'id_rol', p_id_rol, 'correo', p_correo, 'nombres', p_nombres, 'apellidos', p_apellidos, 'telefono', p_telefono),
        JSON_OBJECT('id_usuario', p_id_usuario, 'username', v_username, 'cedula', v_cedula, 'id_rol', v_id_rol, 'correo', v_correo, 'nombres', v_nombres, 'apellidos', v_apellidos, 'telefono', v_telefono),
        p_id_usuario_auditor,
        'media',
        CONCAT('Se modificaron los datos de identidad y contacto del usuario con ID: ', p_id_usuario, '.')
    );

    COMMIT;
END $$

DELIMITER ;

-- Los campos que ya quedaron truncados no se pueden descifrar; hay que restaurarlos desde un backup o reingresar sus valores originales.
