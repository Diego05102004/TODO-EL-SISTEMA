-- Requiere que descuento y precio_unitario ya existan como se describe en
-- assets/BD/casalai_principal.sql. Ejecutar una sola vez en la base existente.
ALTER TABLE tbl_carritodetalle
    ADD COLUMN id_combo INT(11) NULL DEFAULT NULL AFTER precio_unitario,
    ADD KEY id_combo (id_combo);

ALTER TABLE tbl_factura_detalle
    ADD COLUMN id_combo INT(11) NULL DEFAULT NULL AFTER precio_unitario,
    ADD KEY id_combo (id_combo);

-- Inicializa los precios faltantes de registros antiguos. Las filas existentes
-- no tienen origen de combo, por lo que se conservan como unidades individuales.
UPDATE tbl_combo_detalle cd
INNER JOIN tbl_combo c ON c.id_combo = cd.id_combo
INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
SET cd.precio_unitario = ROUND(p.precio * (1 - COALESCE(c.descuento, 0) / 100), 2)
WHERE cd.precio_unitario = 0;

UPDATE tbl_carritodetalle cd
INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
SET cd.precio_unitario = p.precio
WHERE cd.id_combo IS NULL AND cd.precio_unitario = 0;

UPDATE tbl_factura_detalle fd
INNER JOIN tbl_productos p ON p.id_producto = fd.id_producto
SET fd.precio_unitario = p.precio
WHERE fd.id_combo IS NULL AND fd.precio_unitario = 0;
