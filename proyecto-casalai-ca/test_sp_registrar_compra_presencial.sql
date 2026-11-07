-- -----------------------------------------------------------------------------
-- PRUEBA DEL PROCEDIMIENTO ALMACENADO sp_registrar_compra_presencial
-- -----------------------------------------------------------------------------

USE `casalai_principal`;

-- Prueba 1: Registro de compra presencial con productos y pagos
CALL sp_registrar_compra_presencial(
    1, -- id_cliente (reemplazar con un ID válido)
    '[{"id_producto": 1, "cantidad": 2}]', -- productos JSON (reemplazar con datos válidos)
    '[{"tipo": "Efectivo", "monto": 100.00}]', -- pagos JSON
    CURDATE(), -- fecha
    1, -- id_usuario_auditor (reemplazar con un ID válido)
    @resultado
);

-- Verificar resultado
SELECT @resultado AS resultado;

-- Prueba 2: Verificar que se crearon los registros
-- Despacho
SELECT * FROM tbl_despachos ORDER BY id_despachos DESC LIMIT 1;

-- Detalle de despacho
SELECT * FROM tbl_despacho_detalle ORDER BY id_detalle DESC LIMIT 5;

-- Factura
SELECT * FROM tbl_facturas ORDER BY id_factura DESC LIMIT 1;

-- Detalle de factura
SELECT * FROM tbl_factura_detalle ORDER BY id_detalle DESC LIMIT 5;

-- Pagos
SELECT * FROM tbl_detalles_pago ORDER BY id_detalle_pago DESC LIMIT 5;

-- Ingresos/Egresos
SELECT * FROM tbl_ingresos_egresos ORDER BY id_ingreso_egreso DESC LIMIT 1;

-- Prueba 3: Prueba con múltiples productos
CALL sp_registrar_compra_presencial(
    1, -- id_cliente
    '[{"id_producto": 1, "cantidad": 2}, {"id_producto": 2, "cantidad": 1}]', -- múltiples productos
    '[{"tipo": "Efectivo", "monto": 150.00}, {"tipo": "Transferencia", "monto": 50.00, "referencia": "REF123"}]', -- múltiples pagos
    CURDATE(),
    1, -- id_usuario_auditor
    @resultado
);

SELECT @resultado AS resultado;

-- Prueba 4: Prueba sin pagos (debería funcionar)
CALL sp_registrar_compra_presencial(
    1, -- id_cliente
    '[{"id_producto": 1, "cantidad": 1}]', -- productos
    NULL, -- sin pagos
    CURDATE(),
    1, -- id_usuario_auditor
    @resultado
);

SELECT @resultado AS resultado;
