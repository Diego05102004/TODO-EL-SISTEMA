USE `casalai_principal`;

ALTER TABLE `tbl_despachos`
  DROP FOREIGN KEY `tbl_despachos_ibfk_1`,
  ADD CONSTRAINT `tbl_despachos_ibfk_1`
    FOREIGN KEY (`id_clientes`) REFERENCES `tbl_clientes` (`id_clientes`)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `tbl_facturas`
  DROP FOREIGN KEY `tbl_facturas_ibfk_1`,
  ADD CONSTRAINT `tbl_facturas_ibfk_1`
    FOREIGN KEY (`cliente`) REFERENCES `tbl_clientes` (`id_clientes`)
    ON DELETE RESTRICT ON UPDATE CASCADE;
