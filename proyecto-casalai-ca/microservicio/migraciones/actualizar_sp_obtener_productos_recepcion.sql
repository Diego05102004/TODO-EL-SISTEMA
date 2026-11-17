DROP PROCEDURE IF EXISTS sp_obtener_productos_recepcion;

DELIMITER $$
CREATE PROCEDURE sp_obtener_productos_recepcion(IN p_id_recepcion INT)
BEGIN
    SELECT p.id_producto AS codigo,
           p.nombre_producto AS nombre_producto,
           m.nombre_modelo AS modelo,
           mar.nombre_marca AS marca,
           p.serial,
           d.cantidad,
           d.costo
    FROM tbl_detalle_recepcion_productos d
    INNER JOIN tbl_productos p ON p.id_producto = d.id_producto
    INNER JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
    INNER JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
    WHERE d.id_recepcion = p_id_recepcion
    ORDER BY d.id_detalle_recepcion_productos;
END $$
DELIMITER ;
