DELIMITER $$

CREATE PROCEDURE sp_modificar_producto(
    IN p_id_producto INT,
    IN p_serial VARCHAR(20),
    IN p_nombre_producto VARCHAR(20),
    IN p_descripcion_producto VARCHAR(255),
    IN p_id_modelo INT(11),
    IN p_stock INT(3),
    IN p_stock_minimo INT(3),
    IN p_stock_maximo INT(3),
    IN p_clausula_garantia VARCHAR(255),
    IN p_precio FLOAT(10,2),
    IN p_estado VARCHAR(20),
    IN p_nombre_categoria VARCHAR(20),
    IN p_id_usuario_auditor INT,
    IN p_caracteristicas JSON,
    IN p_imagen VARCHAR(255),
    OUT p_resultado INT
)
BEGIN
    DECLARE v_id_categoria INT;
    DECLARE v_nombre_categoria_normalizado VARCHAR(50);
    DECLARE v_nombre_tabla_caracteristicas VARCHAR(100);
    DECLARE v_nombre_categoria_actual VARCHAR(50);
    DECLARE v_nombre_tabla_caracteristicas_actual VARCHAR(100);

    -- Manejador de excepciones simplificado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    SET v_nombre_categoria_normalizado = LOWER(TRIM(p_nombre_categoria));
    
    SET v_id_categoria = (SELECT id_categoria FROM tbl_categoria WHERE LOWER(TRIM(nombre_categoria)) = v_nombre_categoria_normalizado LIMIT 1);

    IF v_id_categoria IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La categoría especificada no existe.';
    END IF;

    -- Obtener categoría actual del producto
    SELECT c.nombre_categoria INTO v_nombre_categoria_actual
    FROM tbl_productos p
    LEFT JOIN tbl_categoria c ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = p_id_producto;

    -- Si la categoría cambió, eliminar de la tabla anterior
    IF v_nombre_categoria_actual IS NOT NULL AND v_nombre_categoria_actual != v_nombre_categoria_normalizado THEN
        SET v_nombre_tabla_caracteristicas_actual = CONCAT('cat_', REPLACE(LOWER(v_nombre_categoria_actual), ' ', '_'));
        
        IF EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = v_nombre_tabla_caracteristicas_actual
        ) THEN
            SET @sql = CONCAT('DELETE FROM ', v_nombre_tabla_caracteristicas_actual, ' WHERE id_producto = ', p_id_producto);
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;

    -- Actualizar producto principal
    UPDATE tbl_productos 
    SET serial = p_serial,
        nombre_producto = p_nombre_producto,
        descripcion_producto = p_descripcion_producto,
        id_modelo = p_id_modelo,
        id_categoria = v_id_categoria,
        stock = p_stock,
        stock_minimo = p_stock_minimo,
        stock_maximo = p_stock_maximo,
        clausula_garantia = p_clausula_garantia,
        precio = p_precio,
        estado = p_estado,
        imagen = p_imagen
    WHERE id_producto = p_id_producto;

    -- Insertar o actualizar características en la nueva tabla de categoría
    SET v_nombre_tabla_caracteristicas = CONCAT('cat_', REPLACE(v_nombre_categoria_normalizado, ' ', '_'));
    
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = v_nombre_tabla_caracteristicas
    ) THEN
        IF p_caracteristicas IS NOT NULL AND JSON_LENGTH(p_caracteristicas) > 0 THEN
            -- Verificar si ya existe registro de características
            SET @sql = CONCAT('SELECT COUNT(*) FROM ', v_nombre_tabla_caracteristicas, ' WHERE id_producto = ', p_id_producto);
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usar la variable de sesión para obtener el resultado
            SET @existe = FOUND_ROWS();
            
            IF @existe > 0 THEN
                -- Actualizar características existentes
                SET @sql = CONCAT('UPDATE ', v_nombre_tabla_caracteristicas, ' SET ');
                
                SET @keys = JSON_KEYS(p_caracteristicas);
                SET @key_count = JSON_LENGTH(@keys);
                SET @i = 0;
                
                WHILE @i < @key_count DO
                    SET @key = JSON_UNQUOTE(JSON_EXTRACT(@keys, CONCAT('$[', @i, ']')));
                    SET @value = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$.', @key)));
                    
                    IF @i > 0 THEN
                        SET @sql = CONCAT(@sql, ', ');
                    END IF;
                    
                    SET @sql = CONCAT(@sql, @key, ' = ', QUOTE(@value));
                    
                    SET @i = @i + 1;
                END WHILE;
                
                SET @sql = CONCAT(@sql, ' WHERE id_producto = ', p_id_producto);
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            ELSE
                -- Insertar nuevas características
                SET @sql = CONCAT('INSERT INTO ', v_nombre_tabla_caracteristicas, ' SET id_producto = ', p_id_producto);
                
                SET @keys = JSON_KEYS(p_caracteristicas);
                SET @key_count = JSON_LENGTH(@keys);
                SET @i = 0;
                
                WHILE @i < @key_count DO
                    SET @key = JSON_UNQUOTE(JSON_EXTRACT(@keys, CONCAT('$[', @i, ']')));
                    SET @value = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$.', @key)));
                    
                    SET @sql = CONCAT(@sql, ', ', @key, ' = ', QUOTE(@value));
                    
                    SET @i = @i + 1;
                END WHILE;
                
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        ELSE
            -- Asegurar que exista un registro aunque no haya características
            SET @sql = CONCAT('INSERT IGNORE INTO ', v_nombre_tabla_caracteristicas, ' (id_producto) VALUES (', p_id_producto, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;

    INSERT INTO casalai_seguridad.tbl_bitacora (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES (
        NOW(), 
        'Productos', 
        'MODIFICAR', 
        JSON_OBJECT('id_producto', p_id_producto, 'serial', p_serial, 'nombre_producto', p_nombre_producto, 'descripcion_producto', p_descripcion_producto, 'id_modelo', p_id_modelo, 'id_categoria', v_id_categoria, 'stock', p_stock, 'stock_minimo', p_stock_minimo, 'stock_maximo', p_stock_maximo, 'clausula_garantia', p_clausula_garantia, 'precio', p_precio, 'estado', p_estado, 'caracteristicas', p_caracteristicas, 'imagen', p_imagen), 
        NULL, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se modificó el producto: ', p_nombre_producto, ' (ID: ', p_id_producto, ', Serial: ', p_serial, ', Categoría: ', p_nombre_categoria, ')')
    );

    COMMIT;
    SET p_resultado = 1;
END $$

DELIMITER ;
