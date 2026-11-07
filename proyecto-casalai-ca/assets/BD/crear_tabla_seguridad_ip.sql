-- Crear tabla para control de seguridad de IPs
-- Base de datos: seguridadlai

USE seguridadlai;

CREATE TABLE IF NOT EXISTS `seguridad_ip` (
  `id_seguridad_ip` int(11) NOT NULL AUTO_INCREMENT,
  `direccion_ip` varchar(45) NOT NULL COMMENT 'Dirección IP del cliente',
  `username` varchar(50) DEFAULT NULL COMMENT 'Usuario asociado si está logueado',
  `tipo_bloqueo` enum('ip','usuario') NOT NULL DEFAULT 'ip' COMMENT 'Tipo de bloqueo aplicado',
  `motivo_bloqueo` varchar(200) DEFAULT NULL COMMENT 'Motivo del bloqueo',
  `peticiones_totales` int(11) DEFAULT 0 COMMENT 'Total de peticiones registradas',
  `peticiones sospechosas` int(11) DEFAULT 0 COMMENT 'Peticiones consideradas sospechosas',
  `fecha_ultima_peticion` datetime DEFAULT NULL COMMENT 'Fecha y hora de última petición',
  `fecha_bloqueo` datetime DEFAULT NULL COMMENT 'Fecha y hora del bloqueo',
  `fecha_desbloqueo` datetime DEFAULT NULL COMMENT 'Fecha y hora de desbloqueo automático',
  `esta_bloqueado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 si está bloqueado, 0 si no',
  `nivel_riesgo` enum('bajo','medio','alto','critico') NOT NULL DEFAULT 'bajo' COMMENT 'Nivel de riesgo asignado',
  `agente_usuario` text DEFAULT NULL COMMENT 'User Agent del navegador',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
  PRIMARY KEY (`id_seguridad_ip`),
  UNIQUE KEY `unique_ip_username` (`direccion_ip`, `username`),
  KEY `idx_direccion_ip` (`direccion_ip`),
  KEY `idx_username` (`username`),
  KEY `idx_esta_bloqueado` (`esta_bloqueado`),
  KEY `idx_fecha_bloqueo` (`fecha_bloqueo`),
  KEY `idx_nivel_riesgo` (`nivel_riesgo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla para control de seguridad y bloqueo de IPs';

-- Insertar registros iniciales para IPs de confianza (whitelist)
INSERT INTO `seguridad_ip` (`direccion_ip`, `username`, `tipo_bloqueo`, `motivo_bloqueo`, `nivel_riesgo`, `esta_bloqueado`) VALUES
('127.0.0.1', NULL, 'ip', 'IP local - confianza', 'bajo', 0),
('::1', NULL, 'ip', 'IPv6 local - confianza', 'bajo', 0);

-- Crear vista para IPs bloqueadas activas
CREATE OR REPLACE VIEW `v_ips_bloqueadas` AS
SELECT 
    si.direccion_ip,
    si.username,
    si.motivo_bloqueo,
    si.fecha_bloqueo,
    si.fecha_desbloqueo,
    si.nivel_riesgo,
    TIMESTAMPDIFF(MINUTE, si.fecha_bloqueo, NOW()) as minutos_bloqueado
FROM seguridad_ip si
WHERE si.esta_bloqueado = 1 
AND (si.fecha_desbloqueo IS NULL OR si.fecha_desbloqueo > NOW());

-- Crear vista para estadísticas de seguridad
CREATE OR REPLACE VIEW `v_estadisticas_seguridad` AS
SELECT 
    COUNT(*) as total_registros,
    COUNT(CASE WHEN esta_bloqueado = 1 THEN 1 END) as ips_bloqueadas,
    COUNT(CASE WHEN nivel_riesgo = 'critico' THEN 1 END) as riesgo_critico,
    COUNT(CASE WHEN nivel_riesgo = 'alto' THEN 1 END) as riesgo_alto,
    COUNT(CASE WHEN nivel_riesgo = 'medio' THEN 1 END) as riesgo_medio,
    COUNT(CASE WHEN nivel_riesgo = 'bajo' THEN 1 END) as riesgo_bajo,
    SUM(peticiones_totales) as total_peticiones,
    SUM(peticiones_sospechosas) as total_sospechosas
FROM seguridad_ip
WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
