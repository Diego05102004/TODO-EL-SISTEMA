-- -----------------------------------------------------------------------------
-- MIGRACIÓN: CIFRADO DE tbl_detalles_pago.referencia
--
-- 1) Amplía la columna `referencia`: el cifrado híbrido RSA+AES-256-CBC produce
--    valores Base64 de ~400+ caracteres (antes VARCHAR(30) truncaría el texto
--    cifrado y corrompería el descifrado).
-- 2) Reimporta el procedimiento sp_registrar_compra_presencial con la variable
--    v_pago_referencia ampliada a VARCHAR(2048) (ver sp_registrar_compra_presencial.sql
--    o la sección DELIMITER dentro de assets/BD/casalai_principal.sql).
--
-- NOTA: Los registros existentes permanecen en texto plano. El código de la
-- aplicación descifra de forma compatible (si el valor no es ciphertext válido,
-- se devuelve tal cual). Para migrar el histórico a cifrado, ejecutar el script
-- PHP de respaldo/rotación correspondiente o reprocesar los valores con la
-- clase Encryption.
-- -----------------------------------------------------------------------------

USE `casalai_principal`;

ALTER TABLE `tbl_detalles_pago`
    MODIFY `referencia` VARCHAR(2048) NOT NULL;

-- El índice idx_pagos_referencia se conserva: sigue siendo útil para la
-- coincidencia exacta de registros legados en texto plano.
