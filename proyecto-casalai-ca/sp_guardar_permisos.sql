DELIMITER $$

CREATE PROCEDURE sp_guardar_permisos(
    IN p_permisos_json JSON,
    IN p_roles_json JSON,
    IN p_modulos_json JSON,
    IN p_acciones_json JSON
)
BEGIN
    DECLARE v_id_rol INT;
    DECLARE v_id_modulo INT;
    DECLARE v_accion VARCHAR(50);
    DECLARE v_estatus VARCHAR(20);
    DECLARE v_i INT;
    DECLARE v_j INT;
    DECLARE v_k INT;
    DECLARE v_roles_count INT;
    DECLARE v_modulos_count INT;
    DECLARE v_acciones_count INT;
    DECLARE v_permiso_valor TEXT;

    -- Manejador de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- No hacer ROLLBACK aquí, dejar que PHP lo maneje
        RESIGNAL;
    END;

    -- Bloqueo exclusivo de la tabla de permisos para evitar modificaciones concurrentes
    -- FOR UPDATE bloquea todas las filas para lectura y escritura
    SELECT * FROM tbl_permisos LOCK IN SHARE MODE;

    -- Eliminar permisos existentes de los roles especificados (excepto rol 6)
    SET v_roles_count = JSON_LENGTH(p_roles_json);
    SET v_i = 0;
    
    WHILE v_i < v_roles_count DO
        SET v_id_rol = JSON_UNQUOTE(JSON_EXTRACT(p_roles_json, CONCAT('$[', v_i, '].id_rol')));
        
        IF v_id_rol != 6 THEN
            DELETE FROM tbl_permisos WHERE id_rol = v_id_rol;
        END IF;
        
        SET v_i = v_i + 1;
    END WHILE;

    -- Insertar nuevos permisos
    SET v_roles_count = JSON_LENGTH(p_roles_json);
    SET v_modulos_count = JSON_LENGTH(p_modulos_json);
    SET v_acciones_count = JSON_LENGTH(p_acciones_json);
    
    SET v_i = 0;
    WHILE v_i < v_roles_count DO
        SET v_id_rol = JSON_UNQUOTE(JSON_EXTRACT(p_roles_json, CONCAT('$[', v_i, '].id_rol')));
        
        IF v_id_rol != 6 THEN
            SET v_j = 0;
            WHILE v_j < v_modulos_count DO
                SET v_id_modulo = JSON_UNQUOTE(JSON_EXTRACT(p_modulos_json, CONCAT('$[', v_j, '].id_modulo')));
                
                SET v_k = 0;
                WHILE v_k < v_acciones_count DO
                    SET v_accion = JSON_UNQUOTE(JSON_EXTRACT(p_acciones_json, CONCAT('$[', v_k, ']')));
                    
                    -- Verificar si el permiso está marcado como 'on' en el JSON de permisos
                    SET v_permiso_valor = JSON_UNQUOTE(JSON_EXTRACT(p_permisos_json, CONCAT('$.', v_id_rol, '.', v_id_modulo, '.', v_accion)));
                    
                    IF v_permiso_valor = 'on' THEN
                        SET v_estatus = 'Permitido';
                    ELSE
                        SET v_estatus = 'No Permitido';
                    END IF;
                    
                    INSERT INTO tbl_permisos (id_rol, id_modulo, accion, estatus)
                    VALUES (v_id_rol, v_id_modulo, v_accion, v_estatus);
                    
                    SET v_k = v_k + 1;
                END WHILE;
                
                SET v_j = v_j + 1;
            END WHILE;
        END IF;
        
        SET v_i = v_i + 1;
    END WHILE;
END $$

DELIMITER ;
