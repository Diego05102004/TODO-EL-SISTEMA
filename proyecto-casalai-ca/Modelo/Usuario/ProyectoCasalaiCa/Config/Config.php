<?php

namespace Usuario\ProyectoCasalaiCa\Config;

class Config
{
    // Configuración de la base de datos
    public const DB_PRINCIPAL = 'P';
    public const DB_SEGURIDAD = 'S';
    
    // Otras constantes de configuración
    public const APP_NAME = 'Proyecto Casalai';
    public const APP_VERSION = '1.0.0';
    
    // Configuración de rutas
    public const UPLOAD_DIR = __DIR__ . '/../../../uploads/';
    
    // Configuración de correo
    public const MAIL_FROM = 'noreply@casalai.com';
    public const MAIL_FROM_NAME = 'Sistema Casalai';
    
    // Configuración de sesión
    public const SESSION_TIMEOUT = 1800; // 30 minutos
    
    // Configuración de paginación
    public const ITEMS_PER_PAGE = 10;
    
    // Otras configuraciones según sea necesario
}
