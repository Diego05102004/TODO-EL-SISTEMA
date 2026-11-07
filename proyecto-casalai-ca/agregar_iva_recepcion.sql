ALTER TABLE tbl_recepcion_productos
    ADD COLUMN subtotal_factura DECIMAL(12, 2) NULL,
    ADD COLUMN porcentaje_iva DECIMAL(7, 2) NULL,
    ADD COLUMN monto_iva DECIMAL(12, 2) NULL,
    ADD COLUMN total_factura DECIMAL(12, 2) NULL;

ALTER TABLE tbl_detalle_recepcion_productos
    MODIFY COLUMN costo DECIMAL(12, 2) NOT NULL;