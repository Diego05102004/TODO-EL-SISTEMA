<?php 
require_once "utils.php";
require_once __DIR__ . "/../../../vendor/autoload.php";
session_start();

// Habilitar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determinar tipo de usuario basado en la sesión
$esCliente = isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] == 'Cliente';
$esAdministrador = isset($_SESSION['nombre_rol']) && ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario');

// Sistema de permisos dinámico
$id_rol = $_SESSION['id_rol'] ?? 0;
$nombre_rol = $_SESSION['nombre_rol'] ?? '';

$permisosObj = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos();

// Definir módulos del manual con sus secciones correspondientes
$modulosManual = [
    'Usuario' => [
        'titulo' => 'Gestión de Usuarios',
        'icono' => 'bi-people',
        'seccion_id' => 'gestion-usuarios-sistema',
        'descripcion' => 'Administre los usuarios del sistema, sus roles y permisos.'
    ],
    'Recepcion' => [
        'titulo' => 'Recepción de Productos',
        'icono' => 'bi-truck',
        'seccion_id' => 'recepcion-productos',
        'descripcion' => 'Registre la entrada de nuevos productos al inventario.'
    ],
    'Despacho' => [
        'titulo' => 'Despacho de Productos',
        'icono' => 'bi-box-arrow-right',
        'seccion_id' => 'despacho-productos',
        'descripcion' => 'Gestione la salida de productos del inventario.'
    ],
    'Marcas' => [
        'titulo' => 'Gestión de Marcas',
        'icono' => 'bi-tag',
        'seccion_id' => 'gestion-marcas-almacenista',
        'descripcion' => 'Administre las marcas de productos del sistema.'
    ],
    'Modelos' => [
        'titulo' => 'Gestión de Modelos',
        'icono' => 'bi-layers',
        'seccion_id' => 'gestion-modelos-almacenista',
        'descripcion' => 'Gestione los modelos de productos disponibles.'
    ],
    'Productos' => [
        'titulo' => 'Gestión de Productos',
        'icono' => 'bi-box',
        'seccion_id' => 'gestion-productos-almacenista',
        'descripcion' => 'Administre el catálogo completo de productos.'
    ],
    'Categorias' => [
        'titulo' => 'Gestión de Categorías',
        'icono' => 'bi-grid-3x3',
        'seccion_id' => 'gestion-categorias-almacenista',
        'descripcion' => 'Organice los productos por categorías.'
    ],
    'Compra Física' => [
        'titulo' => 'Ventas Presenciales',
        'icono' => 'bi-cash',
        'seccion_id' => 'ventas-presenciales',
        'descripcion' => 'Gestione las ventas realizadas en el punto de venta físico.'
    ],
    'Proveedores' => [
        'titulo' => 'Gestión de Proveedores',
        'icono' => 'bi-building',
        'seccion_id' => 'gestion-proveedores-admin',
        'descripcion' => 'Administre la información de proveedores del sistema.'
    ],
    'Clientes' => [
        'titulo' => 'Gestión de Clientes',
        'icono' => 'bi-person-check',
        'seccion_id' => 'gestion-clientes-admin',
        'descripcion' => 'Mantenga el registro de clientes y su historial.'
    ],
    'Catalogo' => [
        'titulo' => 'Catálogo de Productos',
        'icono' => 'bi-book',
        'seccion_id' => 'catalogo-productos',
        'descripcion' => 'Visualice y gestione el catálogo de productos.'
    ],
    'pasarela' => [
        'titulo' => 'Gestión de Pagos',
        'icono' => 'bi-credit-card',
        'seccion_id' => 'gestion-pagos',
        'descripcion' => 'Administre los pagos y transacciones del sistema.'
    ],
    'Pedidos' => [
        'titulo' => 'Gestión de Pedidos',
        'icono' => 'bi-receipt',
        'seccion_id' => 'gestion-pedidos',
        'descripcion' => 'Gestione los pedidos de los clientes.'
    ],
    'Ordenes de despacho' => [
        'titulo' => 'Órdenes de Despacho',
        'icono' => 'bi-list-check',
        'seccion_id' => 'ordenes-despacho',
        'descripcion' => 'Administre las órdenes de despacho de productos.'
    ],
    'Cuentas bancarias' => [
        'titulo' => 'Cuentas Bancarias',
        'icono' => 'bi-bank',
        'seccion_id' => 'gestion-cuentas-bancarias',
        'descripcion' => 'Gestione las cuentas bancarias del sistema.'
    ],
    'Finanzas' => [
        'titulo' => 'Gestión Financiera',
        'icono' => 'bi-currency-dollar',
        'seccion_id' => 'gestion-finanzas',
        'descripcion' => 'Administre ingresos, egresos y finanzas del sistema.'
    ],
    'permisos' => [
        'titulo' => 'Gestión de Permisos',
        'icono' => 'bi-key',
        'seccion_id' => 'gestion-permisos',
        'descripcion' => 'Configure los permisos del sistema.'
    ],
    'Roles' => [
        'titulo' => 'Gestión de Roles',
        'icono' => 'bi-shield',
        'seccion_id' => 'gestion-roles',
        'descripcion' => 'Administre los roles de usuario del sistema.'
    ],
    'bitacora' => [
        'titulo' => 'Bitácora del Sistema',
        'icono' => 'bi-journal-text',
        'seccion_id' => 'gestion-bitacora',
        'descripcion' => 'Visualice el registro de actividades del sistema.'
    ],
    'Backup' => [
        'titulo' => 'Gestión de Backup',
        'icono' => 'bi-cloud-download',
        'seccion_id' => 'gestion-backup',
        'descripcion' => 'Administre las copias de seguridad del sistema.'
    ]
];

// Obtener permisos del usuario (por módulo y por acción)
$permisosPorModulo = [];
$permisosConsulta = [];

// Normalizador de acciones: el manual usa "ingresar" como visibilidad del módulo.
// En BD/Permisos.php no existe "ingresar"; lo mapeamos a "consultar".
$normalizarAccion = static function (string $accion): string {
    $accion = mb_strtolower(trim($accion));
    if ($accion === 'ingresar') {
        return 'consultar';
    }
    if ($accion === 'generar re' || $accion === 'generar reporte' || $accion === 'generar reportes') {
        return 'generar reporte';
    }
    return $accion;
};

if ($nombre_rol === 'SuperUsuario' || (int)$id_rol === 6) {
    foreach ($modulosManual as $moduloBD => $info) {
        $permisosPorModulo[$moduloBD] = [
            'consultar' => true,
            'incluir' => true,
            'modificar' => true,
            'eliminar' => true,
            'generar reporte' => true,
        ];
        $permisosConsulta[$moduloBD] = true;
    }
} else {
    try {
        foreach ($modulosManual as $moduloBD => $info) {
            $permisoData = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD);
            $permisosPorModulo[$moduloBD] = [
                'consultar' => (bool)($permisoData['consultar'] ?? false),
                'incluir' => (bool)($permisoData['incluir'] ?? false),
                'modificar' => (bool)($permisoData['modificar'] ?? false),
                'eliminar' => (bool)($permisoData['eliminar'] ?? false),
                'generar reporte' => (bool)($permisoData['generar reporte'] ?? false),
            ];
            // "ingresar" (visibilidad) = permiso consultar
            $permisosConsulta[$moduloBD] = $permisosPorModulo[$moduloBD]['consultar'];
        }
    } catch (Exception $e) {
        foreach ($modulosManual as $moduloBD => $info) {
            $permisosPorModulo[$moduloBD] = [
                'consultar' => false,
                'incluir' => false,
                'modificar' => false,
                'eliminar' => false,
                'generar reporte' => false,
            ];
            $permisosConsulta[$moduloBD] = false;
        }
    }
}

$puedeIngresar = static function (string $modulo) use ($permisosConsulta): bool {
    return !empty($permisosConsulta[$modulo]);
};

$puedeAccion = static function (string $modulo, string $accion) use ($permisosPorModulo, $normalizarAccion): bool {
    $accion = $normalizarAccion($accion);
    return !empty($permisosPorModulo[$modulo][$accion]);
};

$modulos = [
    'Usuario',
      'Recepcion',
       'Despacho',
       'Marcas',
       'Modelos',
       'Productos',
       'Categorias',
    'Compra Física',
    'Proveedores',
    'Clientes',
    'Catalogo',
    'pasarela',
    'Pedidos',
    'Ordenes de despacho',
    'Cuentas bancarias',
    'Finanzas',
    'permisos',
    'Roles',
    'bitacora',
    'Backup' 
];
    $acciones =  [
        'ingresar',
        'incluir',
        'modificar',
        'eliminar',
        'consultar',
        'generar re'
    ];
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="img/logo.png">
    <title>Manual de Usuario - Casa Lai.Ca</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .manual-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--secondary-color);
        }
        
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .nav-pills .nav-link {
            color: var(--primary-color);
            border-radius: 5px;
            margin: 5px 0;
            font-weight: 500;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f0f0f0;
        }
        
        .step-number {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .quick-links {
            position: sticky;
            top: 20px;
        }
        
        .screenshot {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 100%;
            height: auto;
        }
        
        .note {
            background-color: #e7f4ff;
            border-left: 4px solid var(--secondary-color);
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .warning {
            background-color: #fff8e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .tip {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .hidden-section {
            display: none;
        }
        
        .access-message {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        
        .toc {
            position: sticky;
            top: 20px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .toc::-webkit-scrollbar {
            width: 6px;
        }
        
        .toc::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .toc::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .toc::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .toc-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .toc-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .toc-item {
            margin-bottom: 8px;
        }
        
        .toc-link {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        
        .toc-link:hover {
            background-color: #f0f7ff;
            color: var(--secondary-color);
            border-left-color: var(--secondary-color);
            transform: translateX(3px);
        }
        
        .toc-link.active {
            background-color: #e3f2fd;
            color: var(--secondary-color);
            border-left-color: var(--secondary-color);
            font-weight: 600;
        }
        
        .toc-link.active::after {
            content: " ✓";
            color: var(--secondary-color);
            font-weight: bold;
            margin-left: auto;
        }
        
        .toc-link i {
            margin-right: 8px;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .toc-sublist {
            list-style: none;
            padding-left: 0;
            margin: 8px 0 0 0;
        }
        
        .toc-sublist .toc-link {
            padding: 6px 12px 6px 32px;
            font-size: 0.9rem;
            font-weight: 400;
        }
        
        /* Indicador de progreso de lectura */
        .toc-progress {
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            background: var(--secondary-color);
            border-radius: 3px;
            transition: height 0.3s ease;
            z-index: 10;
        }
        
        /* Mejoras para móvil */
        @media (max-width: 768px) {
            .toc {
                max-height: 200px;
                margin-bottom: 20px;
            }
            
            .toc-link {
                padding: 6px 8px;
                font-size: 0.85rem;
            }
            
            .toc-sublist .toc-link {
                padding: 4px 8px 4px 24px;
                font-size: 0.8rem;
            }
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        
        .feature-card {
            height: 100%;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .feature-card .card-body {
            padding: 1.5rem;
        }
        
        .feature-card .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .feature-card .icon-wrapper i {
            font-size: 1.5rem;
            color: var(--secondary-color);
        }
        
        .feature-card .card-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .feature-card .card-text {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="manual-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">Manual de Usuario</h1>
                    <p class="lead mb-0">Guía completa para el uso del Sistema de Gestión de Inventario y Ventas</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <img src="img/logo-lg.png" alt="Casa Lai.Ca" style="height: 85px;">
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Sidebar Navigation -->
<!-- Sidebar Navigation -->
<div class="col-lg-3 mb-4">
    <div class="toc">
        <div class="toc-progress" id="tocProgress"></div>
        <h5 class="toc-title">Tabla de Contenidos</h5>
        <ul class="toc-list">
           <?php if($_SESSION): ?> 
            <?php if(!$esCliente): ?>
            <li class="toc-item"><a href="#introduccion" class="toc-link"><i class="bi bi-house-door"></i> Introducción</a></li>
            <?php endif; ?>
           <?php endif; ?>
            
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <?php if(!$esCliente): ?>
                <li class="toc-item"><a href="#dashboard" class="toc-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <?php endif; ?>
                <li class="toc-item"><a href="#mi-cuenta" class="toc-link"><i class="bi bi-person"></i> Mi Cuenta</a></li>
                
                <!-- Secciones disponibles para todos los usuarios -->
                <li class="toc-item">
                    <a href="#seccion-cliente" class="toc-link"><i class="bi bi-person"></i> Sección para Clientes</a>
                    <ul class="toc-sublist ms-3 mt-2">
                        <li><a href="#carrito" class="toc-link">Carrito de Compras</a></li>
                        <li><a href="#mis-pedidos" class="toc-link">Mis Pedidos</a></li>
                    </ul>
                </li>
                
                <?php if ($esAdministrador): ?>
                    <!-- Secciones para Administradores -->
                    <li class="toc-item">
                        <a href="#seccion-almacenista" class="toc-link"><i class="bi bi-box-seam"></i> Sección para Almacenistas</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#recepcion-productos" class="toc-link">Recepción de Productos</a></li>
                            <li><a href="#despacho-productos" class="toc-link">Despacho de Productos</a></li>
                            <li><a href="#gestion-marcas-almacenista" class="toc-link">Gestión de Marcas</a></li>
                            <li><a href="#gestion-modelos-almacenista" class="toc-link">Gestión de Modelos</a></li>
                            <li><a href="#gestion-productos-almacenista" class="toc-link">Gestión de Productos</a></li>
                            <li><a href="#gestion-categorias-almacenista" class="toc-link">Gestión de Categorías</a></li>
                        </ul>
                    </li>
                    
                    <li class="toc-item">
                        <a href="#seccion-administrador" class="toc-link"><i class="bi bi-shield-check"></i> Sección para Administradores</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#gestion-proveedores-admin" class="toc-link">Gestión de Proveedores</a></li>
                            <li><a href="#gestion-clientes-admin" class="toc-link">Gestión de Clientes</a></li>
                            <li><a href="#gestion-usuarios-sistema" class="toc-link">Gestión de Usuarios</a></li>
                            <li><a href="#gestion-roles-permisos" class="toc-link">Gestión de Roles y Permisos</a></li>
                            <li><a href="#gestion-cuentas-bancarias" class="toc-link">Gestión de Cuentas Bancarias</a></li>
                            <li><a href="#catalogo-combos-promocionales" class="toc-link">Combos Promocionales</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            <?php else: ?>
                <li class="toc-item"><a href="#iniciar-sesion" class="toc-link"><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión</a></li>
            <?php endif; ?>
            
       <!--     <li class="toc-item"><a href="#preguntas-frecuentes" class="toc-link"><i class="bi bi-question-circle"></i> Preguntas Frecuentes</a></li> -->
        </ul>
    </div>
</div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Introduction Section -->
                 <?php if($_SESSION): ?>
                    <?php if(!$esCliente): ?>
                <section id="introduccion" class="section-card">
                    <h2 class="section-title">Introducción</h2>
                    <div class="row">
                        <div class="col-md-8">
                            <p>Bienvenido al <strong>Sistema de Gestión de Inventario y Ventas</strong> de <strong>Casa Lai, C.A.</strong> Esta plataforma ha sido diseñada para optimizar y agilizar los procesos de gestión de inventario, ventas y administración de su negocio.</p>
                            
                            <div class="note">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Nota:</strong> Este manual le guiará a través de todas las funcionalidades del sistema según su rol de usuario.
                            </div>
                            
                            <h4 class="mt-4 mb-3">Características Principales</h4>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                            <h5 class="card-title">Gestión de Inventario</h5>
                                            <p class="card-text">Control completo sobre el inventario de productos, categorías y existencias.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-cart"></i>
                                            </div>
                                            <h5 class="card-title">Ventas y Facturación</h5>
                                            <p class="card-text">Proceso de venta simplificado con generación de facturas electrónicas.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-people"></i>
                                            </div>
                                            <h5 class="card-title">Gestión de Clientes</h5>
                                            <p class="card-text">Mantenga un registro detallado de sus clientes y su historial de compras.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-graph-up"></i>
                                            </div>
                                            <h5 class="card-title">Reportes y Análisis</h5>
                                            <p class="card-text">Genere informes detallados para el análisis del rendimiento del negocio.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Acceso Rápido</h5>
                                    <div class="d-grid gap-2">
                                        <a href="#dashboard" class="btn btn-outline-primary text-start"><i class="bi bi-speedometer2 me-2"></i> Ir al Dashboard</a>
                                        <?php if ($esCliente): ?>
                                            <a href="#seccion-cliente" class="btn btn-outline-primary text-start"><i class="bi bi-cart me-2"></i> Ver Sección Cliente</a>
                                        <?php endif; ?>
                                        <a href="#preguntas-frecuentes" class="btn btn-outline-secondary text-start mt-3"><i class="bi bi-question-circle me-2"></i> Preguntas Frecuentes</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5 class="card-title">Soporte Técnico</h5>
                                    <p class="card-text small">¿Necesita ayuda? Nuestro equipo de soporte está disponible para asistirle.</p>
                                    <a href="#soporte" class="btn btn-outline-success w-100"><i class="bi bi-headset me-2"></i> Contactar Soporte</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
                <?php endif; ?>
    <?php if($_SESSION): ?>
<!-- Dashboard Section -->
 <?php if(!$esCliente): ?>

<section id="dashboard" class="section-card">
    <h2 class="section-title">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </h2>
    
    <div class="row">
        <div class="">
            <p>El Dashboard es el centro de control principal del sistema. Aquí encontrará un resumen de la información más relevante según su rol de usuario.</p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Vista General</h5>
                    <p>Al iniciar sesión, será dirigido al Dashboard que muestra:</p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <?= renderImagen("dashboard", "vista2.png") ?>
                            <p class="text-muted small mt-2">Vista principal del Dashboard</p>
                        </div>
                        <div class="col-md-6">
                            <?= renderImagen("dashboard", "barra-lateral.png") ?>
                            <p class="text-muted small mt-2">Barra lateral de navegación</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Barra Superior</h5>
                    <p>En la parte superior derecha encontrará:</p>
                    <div class="text-center mb-3">
                        <?= renderImagen("dashboard", "perfil2.png") ?>
                    </div>
                    <ul>
                        <li><strong>Icono de Conversión de Dólar</strong>: Muestra la tasa de cambio actual del BCV.</li>
                        <li><strong>Icono de Notificaciones</strong>: Muestra las notificaciones recientes del sistema.</li>
                        <li><strong>Icono de Ayuda</strong>: Acceso directo a este manual de usuario.</li>
                        <li><strong>Perfil de Usuario</strong>: Muestra su nombre y rol actual.</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Conversión de Dólar</h5>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p>Para consultar la tasa de cambio:</p>
                            <ol>
                                <li>Haga clic en el icono de conversión de dólar <i class="bi bi-currency-exchange"></i></li>
                                <li>Se mostrará un panel con las tasas actualizadas</li>
                                <li>Las tasas se actualizan automáticamente según el BCV</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Ventana de Conversión de Dolar</h6>
                            <?= renderImagen("dashboard", "conversion-dolar-abierto.png") ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Notificaciones</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p>El sistema le notificará sobre:</p>
                            <ul>
                                <li>Nuevos mensajes</li>
                                <li>Actualizaciones del sistema</li>
                                <li>Actividad reciente</li>
                                <li>Recordatorios importantes</li>
                            </ul>
                            <p>Para ver todas las notificaciones, haga clic en "Ver más" en el panel de notificaciones.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Central de Notificaciones</h6>
                            <?= renderImagen("dashboard", "notificaciones-abiertas.png") ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        

    </div>
    
    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        El Dashboard se adapta automáticamente según su rol de usuario, mostrando solo la información relevante para sus funciones.
    </div>
</section>

<?php endif; ?>
                <!-- Mi Cuenta Section -->
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <section id="mi-cuenta" class="section-card">
                        <h2 class="section-title">Mi Cuenta</h2>
                        <p>Administre la información de su perfil y preferencias de usuario.</p>
                        
                        <?php
                        $datos_perfil = [
                            "id" => "perfil",
                            "nombre_singular" => "Perfil",
                            "gestionable" => [
                                "Información Personal",
                                "Cambio de Contraseña",
                                "Cambio de Correo Electronico"
                            ],
                            "instrucciones" => [
                                "Actualice sus datos personales",
                                "Cambie su contraseña regularmente",
                                "Cambie su correo electrónico"
                            ]
                        ];
                        plantilla("inicio-perfil", $datos_perfil);
                        ?>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Acceso al Perfil</h4>
                                <p>Haga clic en su nombre de usuario en la esquina superior derecha para acceder a su perfil.</p>
                                <h6 class="text-success">Menú de Perfíl</h6>
                                <?= renderImagen("dashboard", "mi-cuenta.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Información Personal</h4>
                                <p>En la sección de perfil podrá actualizar su información personal.</p>
                                <h6 class="text-success">Actualización de Datos Personales</h6>
                                <?= renderImagen("perfil", "perfil-informacion-personal.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Correo</h4>
                                <p>En la sección de perfil podrá actualizar y cambiar su correo Electronico.</p>
                                <h6 class="text-success">Actualización de Correo Electrónico</h6>
                                <?= renderImagen("perfil", "perfil-cuenta.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Contraseña</h4>
                                <p>En la sección de perfil podrá actualizar y cambiar su contraseña.</p>
                                <h6 class="text-success">Actualización de Contraseña</h6>
                                <?= renderImagen("perfil", "perfil-password.png") ?>
                            </div>
                        </div>
                        
                        <div class="warning mt-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Importante:</strong> Mantenga su informacion personal y contraseña segura y no la comparta con nadie por seguridad.
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Sección Clientes -->
                <?php  if ($esCliente):  ?>
                <section id="seccion-cliente" class="section-card">
                    <h2 class="section-title">
                        <i class="bi bi-person me-2"></i>Sección para Clientes
                    </h2>
                    
                    <div class="row">
                        <div>
                            <p>Como cliente, tendrá acceso a las siguientes funcionalidades para realizar sus compras de manera sencilla y segura.</p>
                        </div> 

                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Guía Rápida</h5>
                                </div>
                                <div class="card-body">
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">1</div>
                                            <div>
                                                <h6 class="mb-1">Explorar Catálogo</h6>
                                                <p class="small text-muted mb-0">Navegue por productos y combos</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">2</div>
                                            <div>
                                                <h6 class="mb-1">Agregar al Carrito</h6>
                                                <p class="small text-muted mb-0">Seleccione los productos deseados</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">3</div>
                                            <div>
                                                <h6 class="mb-1">Revisar Carrito</h6>
                                                <p class="small text-muted mb-0">Ajuste cantidades si es necesario</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step">
                                        <div class="d-flex">
                                            <div class="step-number">4</div>
                                            <div>
                                                <h6 class="mb-1">Prefacturar</h6>
                                                <p class="small text-muted mb-0">Genere su resumen de compra</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="card mt-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Consejos de Compra</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="bi bi-tag-fill text-warning me-2"></i>
                                            Aproveche los combos promocionales
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-search text-primary me-2"></i>
                                            Use el buscador para encontrar productos
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-heart-fill text-danger me-2"></i>
                                            Guarde sus productos favoritos
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-bell-fill text-info me-2"></i>
                                            Reciba notificaciones de ofertas
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Catálogo de Productos -->
                        <div class="card mt-4 mb-4">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-grid-3x3-gap me-2"></i>Catálogo de Productos
                                </h5>
                                <p>Acceda al catálogo completo de productos disponibles para su compra.</p>
                                
                                <div class="row">
                                    <div>
                                        <h6 class="text-success">Vista principal del catálogo</h6>
                                        <?= renderImagen("catalogo", "vista.png") ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Características:</h6>
                                        <ul>
                                            <li>Navegación por categorías</li>
                                            <li>Búsqueda de productos</li>
                                            <li>Filtros avanzados</li>
                                            <li>Visualización de precios</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info mt-3">
                                            <i class="bi bi-info-circle me-2"></i>
                                            En la parte superior encontrará pestañas para acceder a <strong>Combos Promocionales</strong> con ofertas especiales.
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para agregar productos al carrito -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Agregar Productos al Carrito</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ol>
                                                    <li class="mb-2">Navegue por el catálogo y encuentre el producto deseado.</li>
                                                    <li class="mb-2">Haga clic en el botón <strong>"Agregar"</strong> en la parte izquierda del producto.</li>
                                                    <li class="mb-2">El producto se añadirá automáticamente a su carrito.</li>
                                                    <li>Verá un mensaje de confirmación y el contador del carrito se actualizará.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-success">Botón para agregar producto al carrito</h6>
                                                <?= renderImagen("catalogo", "agregar.png") ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Combos Promocionales -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-tags me-2"></i>Combos Promocionales
                                </h5>
                                <p>Descubra nuestras ofertas especiales y paquetes con descuento.</p>
                                
                                <div class="row">
                                    <div>
                                        <h6 class="text-success">Vista de combos promocionales</h6>
                                        <?= renderImagen("catalogo", "vista-2.png") ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mx-auto">
                                        <h6>Ventajas de los combos:</h6>
                                        <ul>
                                            <li>Precios especiales</li>
                                            <li>Productos complementarios</li>
                                            <li>Ahorro garantizado</li>
                                            <li>Stock limitado</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Carrito de Compras -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-cart3 me-2"></i>Gestión del Carrito
                                </h5>
                                <p>Administre los productos que desea comprar antes de finalizar su pedido.</p>
                                
                                <div class="row">
                                    <div>
                                        <h6 class="text-success">Vista del carrito de compras</h6>
                                        <?= renderImagen("carrito", "carrito.png") ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mx-auto">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Ajustar cantidad</strong>: Aumentar o disminuir unidades</li>
                                            <li><strong>Eliminar producto</strong>: Quitar items individuales</li>
                                            <li><strong>Vaciar carrito</strong>: Eliminar todo el contenido</li>
                                            <li><strong>Actualizar totales</strong>: Ver costos en tiempo real</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para gestionar el carrito -->
                                <div class="card mt-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Pasos para Gestionar el Carrito</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">1. Ajustar Cantidad de Productos</h6>
                                                <div class="row">
                                                    <div class="col-md-7">
                                                        <ol>
                                                            <li><strong>Paso 1:</strong> Ubique el producto que desea modificar.</li>
                                                            <li><strong>Paso 2:</strong> Use los botones <strong>+</strong> y <strong>-</strong> para aumentar o disminuir la cantidad.</li>
                                                            <li><strong>Paso 3:</strong> El total se actualizará automáticamente.</li>
                                                            <li><strong>Paso 4:</strong> Puede ingresar directamente la cantidad deseada.</li>
                                                        </ol>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-plus-circle text-success me-2"></i>
                                                            <i class="bi bi-dash-circle text-danger me-2"></i>
                                                            Botones de cantidad
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">2. Eliminar Productos del Carrito</h6>
                                                <div class="row">
                                                    <div class="col-md-7">
                                                        <ol>
                                                            <li><strong>Paso 1:</strong> Encuentre el producto que desea eliminar.</li>
                                                            <li><strong>Paso 2:</strong> Haga clic en el ícono de <strong>basura</strong> 🗑️ junto al producto.</li>
                                                            <li><strong>Paso 3:</strong> Confirme la eliminación en el mensaje emergente.</li>
                                                            <li><strong>Paso 4:</strong> El producto será removido y el total actualizado.</li>
                                                        </ol>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-trash text-danger me-2"></i>
                                                            Eliminar producto
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">3. Vaciar Todo el Carrito</h6>
                                                <div class="row">
                                                    <div class="col-md-7">
                                                        <ol>
                                                            <li><strong>Paso 1:</strong> Haga clic en el botón <strong>"Vaciar Carrito"</strong>.</li>
                                                            <li><strong>Paso 2:</strong> Confirme que desea eliminar todos los productos.</li>
                                                            <li><strong>Paso 3:</strong> El carrito quedará completamente vacío.</li>
                                                            <li><strong>Paso 4:</strong> Podrá comenzar a agregar nuevos productos.</li>
                                                        </ol>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-trash3 text-warning me-2"></i>
                                                            Vaciar carrito
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Prefactura -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-file-earmark-text me-2"></i>Proceso de Facturación
                                </h5>
                                <p>Genere facturas para sus ventas con un proceso detallado paso a paso.</p>
                                
                                <!-- Pasos detallados para facturación -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Procesar Factura</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en <strong>"LISTADO DE PRODUCTOS"</strong> para ver productos disponibles.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Seleccione los productos deseados haciendo clic en ellos.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ajuste las cantidades usando los botones <strong>+/-</strong> o escribiendo directamente.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Verifique el stock disponible (no puede exceder el stock actual).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> El subtotal se calculará automáticamente.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Complete los datos del cliente si es necesario.</li>
                                                    <li class="mb-2"><strong>Paso 7:</strong> Haga clic en <strong>"Procesar Pre-Factura"</strong> cuando esté listo.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-cart-plus text-success me-2"></i>
                                                    <strong>Agregar Productos</strong>
                                                    <br><small>Click en producto</small>
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-calculator me-2"></i>
                                                    <strong>Total:</strong> Calculado automáticamente
                                                </div>
                                                <div class="alert alert-warning border mt-2">
                                                    <i class="bi bi-box-seam me-2"></i>
                                                    <strong>Stock:</strong> Validado automáticamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para gestión de productos en factura -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Gestión de Productos</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Para <strong>agregar productos</strong>: haga clic en la tabla del modal.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Para <strong>eliminar productos</strong>: presione el botón <strong>"X"</strong> rojo.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Para <strong>modificar cantidades</strong>: use los botones <strong>+/-</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Los productos con stock 0 están ocultos automáticamente.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> El sistema validará que no exceda el stock disponible.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-dash-circle text-danger me-2"></i>
                                                    <strong>Eliminar</strong>
                                                    <br><small>Botón X rojo</small>
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-plus-slash-minus me-2"></i>
                                                    <strong>Cantidades:</strong> Botones +/-
                                                </div>
                                                <div class="alert alert-warning border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Stock 0:</strong> Ocultos automáticamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Validaciones y restricciones -->
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Validaciones y Restricciones</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ul>
                                                    <li class="mb-2">No puede procesar una factura sin productos.</li>
                                                    <li class="mb-2">Las cantidades no pueden superar el stock disponible.</li>
                                                    <li class="mb-2">Los precios se calculan automáticamente según el producto.</li>
                                                    <li class="mb-2">El total se actualiza en tiempo real.</li>
                                                    <li class="mb-2">Todos los campos obligatorios deben estar completos.</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-shield-check text-info me-2"></i>
                                                    <strong>Validación</strong>
                                                    <br><small>Automática</small>
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <strong>Importante:</strong> Revise antes de procesar
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para cancelar factura -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Cancelar Factura</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Use el botón <strong>"Cancelar"</strong> si necesita anular la factura</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Confirme la cancelación en el mensaje de advertencia</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-x-circle text-danger me-2"></i>
                                                    <strong>Cancelar</strong>
                                                    <br><small>Con confirmación</small>
                                                </div>
                                                <div class="alert alert-warning border mt-2">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <strong>Atención:</strong> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <h6 class="text-success">Proceso de facturación detallado</h6>
                                        <?= renderImagen("carrito", "prefacturar.png") ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Resumen del proceso:</h6>
                                        <ol>
                                            <li>Seleccione productos del catálogo</li>
                                            <li>Ajuste cantidades y verifique stock</li>
                                            <li>Confirme datos del cliente</li>
                                            <li>Procese pre-factura</li>
                                            <li>Descargue factura final</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- pedidos -->
                        <div class="card mt-4" id="gestion-pedidos-almacenista">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-tag me-2"></i>Gestión de Pedidos
                                </h5>
                                <p>Consulte sus pedidos y realice los pagos o cancelaciones correspondientes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Pedidos realizados</li>
                                            <li>Estado de pagos</li>
                                            <li>Información de pedidos</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Pagar</strong>: Realizar pago</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Cancelar</strong>: Retractar solicitud</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos detallados para realizar pago de pedido -->
                                <div class="card mt-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Pasos para Realizar los Pagos</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">1. Pago a través de Pago Móvil o Transferencia</h6>
                                                <div class="row">
                                                    <div class="col-md-7 mt-2">
                                                        <ol>
                                                            <li class="mb-2"><strong>Paso 1:</strong> Seleccione el <strong>pedido a pagar</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el botón <strong>"Pagar"</strong> (color verde).</li>
                                                            <li class="mb-2"><strong>Paso 3:</strong> Seleccione el <strong>método de pago</strong> (Pago Móvil o Transferencia).</li>
                                                            <li class="mb-2"><strong>Paso 4:</strong> Seleccione el <strong>banco emisor</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 5:</strong> Ingrese el <strong>N° de referecia</strong> del pago realizado.</li>
                                                            <li class="mb-2"><strong>Paso 6:</strong> Agregue la imagen del <strong>comprobante de pago</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 7:</strong> Ingrese el <strong>monto pagado</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 8:</strong> Haga clic en <strong>"Registrar Pago"</strong> para confirmar.</li>
                                                        </ol>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-plus-circle text-success me-2"></i>
                                                            <strong>Realizar Pago:</strong><br> Botón "Registrar Pago" verde
                                                        </div>
                                                        <div class="alert alert-light border mt-2">
                                                            <i class="bi bi-image me-2"></i>
                                                            <strong>Imagen:</strong> JPG/PNG <br> requerida
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">2. Pago a través de Zelle</h6>
                                                <div class="row">
                                                    <div class="col-md-7 mt-2">
                                                        <ol>
                                                            <li class="mb-2"><strong>Paso 1:</strong> Seleccione el <strong>pedido a pagar</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el botón <strong>"Pagar"</strong> (color verde).</li>
                                                            <li class="mb-2"><strong>Paso 3:</strong> Seleccione el <strong>método de pago</strong> (Zelle).</li>
                                                            <li class="mb-2"><strong>Paso 4:</strong> Seleccione el <strong>banco emisor</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 5:</strong> Ingrese el nombre del <strong>propietario</strong> de la cuenta Zelle.</li>
                                                            <li class="mb-2"><strong>Paso 6:</strong> Ingrese el <strong>N° de referecia</strong> del pago realizado.</li>
                                                            <li class="mb-2"><strong>Paso 7:</strong> Agregue la imagen del <strong>comprobante de pago</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 8:</strong> Ingrese el <strong>monto pagado</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 9:</strong> Haga clic en <strong>"Registrar Pago"</strong> para confirmar.</li>
                                                        </ol>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-plus-circle text-success me-2"></i>
                                                            <strong>Realizar Pago:</strong><br> Botón "Registrar Pago" verde
                                                        </div>
                                                        <div class="alert alert-light border mt-2">
                                                            <i class="bi bi-image me-2"></i>
                                                            <strong>Imagen:</strong> JPG/PNG <br> requerida
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="note col-md-11 mx-auto">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            En caso de ser necerio, puede asociar más pago al mismo pedido. Para ello dar clic en el botón "Agregar Método de Pago" y repita el procedimiento.
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para cancelar pedido -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Cancelar Pedido</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Seleccione el pedido a cancelar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el botón <strong>Cancelar</strong> (color rojo).</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la cancelación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Cancelar:</strong><br> Botón "Cancelar" rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php  endif;  ?>

                <!-- Carrito de Compras -->
                <section id="carrito" class="section-card">
                    <h2 class="section-title">Carrito de Compras</h2>
                    <?php
                    $datos_carrito = [
                        "id" => "carrito",
                        "nombre_singular" => "Producto en Carrito",
                        "nombre_plural" => "Productos en Carrito",
                        "gestionable" => [
                            "Ajustar cantidades",
                            "Eliminar productos",
                            "Ver resumen de compra"
                        ],
                        "instrucciones" => [
                            "Haga clic en 'Agregar al carrito' para registrar productos",
                            "Ajuste las cantidades según necesite",
                            "Revise el resumen antes de proceder al pago"
                        ]
                    ];
                    plantilla("inicio", $datos_carrito);
                    ?>
                    
                    <div class="note mt-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Nota:</strong> Los productos en el carrito se mantendrán hasta que los elimine manualmente.
                    </div>
                </section>


                <!-- Mis Pedidos -->
                <section id="mis-pedidos" class="section-card">
                    <h2 class="section-title">Pedidos Realizados</h2>
                    <?php
                    $datos_pedidos = [
                        "id" => "pedidos",
                        "nombre_singular" => "Pedido",
                        "nombre_plural" => "Pedidos Realizados",
                        "gestionable" => [
                            "Ver historial de pedidos",
                            "Ver estado de pedido",
                            "Descargar facturas",
                            "Anular pedidos",
                            "Llevar a Cabo el pago por los productos pedidos"
                        ],
                        "instrucciones" => [
                            "Consulte el estado de sus pedidos recientes",
                            "Consulte el estatus del pedido en tiempo real",
                            "Descargue sus facturas en formato PDF",
                            "Anule el pedido si es necesario"
                        ]
                    ];
                    plantilla("inicio", $datos_pedidos);
                    ?>
                    
                    <div class="tip mt-4">
                        <i class="bi bi-lightbulb-fill me-2"></i>
                        <strong>Consejo:</strong> Puede hacer seguimiento de sus pedidos en esta sección.
                    </div>
                </section>

                <!-- Sección Almacenista -->
                <?php
                $tieneInventario = (
                    $puedeIngresar('Recepcion') ||
                    $puedeIngresar('Despacho') ||
                    $puedeIngresar('Marcas') ||
                    $puedeIngresar('Modelos') ||
                    $puedeIngresar('Productos') ||
                    $puedeIngresar('Categorias')
                );
                ?>
                <?php if ($tieneInventario): ?>
                <section id="seccion-almacenista" class="section-card">
                    <h2 class="section-title">
                        <i class="bi bi-box-seam me-2"></i>Sección para Almacenistas
                    </h2>
                    
                    <div class="row">
                        <div>
                            <p>Como almacenista, tendrá acceso completo a la gestión de inventario y control de productos del sistema.</p>
                        </div>

                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Flujo de Trabajo</h5>
                                </div>
                                <div class="card-body">
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">1</div>
                                            <div>
                                                <h6 class="mb-1">Configurar Categorías</h6>
                                                <p class="small text-muted mb-0">Establezca la estructura base</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">2</div>
                                            <div>
                                                <h6 class="mb-1">Agregar Marcas</h6>
                                                <p class="small text-muted mb-0">Registre los proveedores</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">3</div>
                                            <div>
                                                <h6 class="mb-1">Crear Modelos</h6>
                                                <p class="small text-muted mb-0">Defina las variantes</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">4</div>
                                            <div>
                                                <h6 class="mb-1">Registrar Productos</h6>
                                                <p class="small text-muted mb-0">Complete el inventario</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step">
                                        <div class="d-flex">
                                            <div class="step-number">5</div>
                                            <div>
                                                <h6 class="mb-1">Gestionar Movimientos</h6>
                                                <p class="small text-muted mb-0">Controlar entradas y salidas</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">Alertas de Inventario</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                            Stock mínimo alcanzado
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                            Producto sin stock
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-arrow-up-circle-fill text-info me-2"></i>
                                            Stock máximo excedido
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-clock-fill text-primary me-2"></i>
                                            Productos por recibir
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Recepción -->
                        <?php if ($puedeIngresar('Recepcion')): ?>
                        <div class="card mt-4 mb-4" id="recepcion-productos">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-truck me-2"></i>Recepción de Productos
                                </h5>
                                <p>Registre la entrada de nuevos productos al inventario desde proveedores.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Fecha de recepción</li>
                                            <li>Correlación</li>
                                            <li>Proveedor</li>
                                            <li>Productos</li>
                                            <li>Cantidad recibida</li>
                                            <li>Costo inversión</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nueva recepción</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Ver información completa</li>
                                            <li><strong>Anular</strong>: Remover recepción</li>
                                            <li><strong>Reportes</strong>: Gráficas parametrizadas</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar recepción -->
                                <?php if ($puedeAccion('Recepcion', 'incluir')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nueva Recepción</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"Nueva Recepción"</strong> en la parte superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>N° de la factura</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Seleccione el <strong>proveedor</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Haga clic en <strong>"Lista de Productos"</strong> y seleccione los productos recibidos, costo por unidad y cantidad.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Haga clic en <strong>"Registrar"</strong>.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-plus-circle text-success me-2"></i>
                                                    <strong>Nueva Recepción:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>N° de Factura:</strong><br> (único en el sistema)
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-calculator me-2"></i>
                                                    <strong>Costo:</strong> Valor unitario requerido
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar:</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                        </div>
                                        <div class="note col-md-11 mx-auto">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            Puede agregar y remover productos de la recepción si es necesario antes de confirmar.
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Pasos para detallar recepcion -->
                                <?php if ($puedeAccion('Recepcion', 'consultar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Recepción</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa de la recepción.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Pasos para modificar recepción -->
                                <?php if ($puedeAccion('Recepcion', 'modificar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Recepción</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice la recepción en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-light border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Pasos para anular recepción -->
                                <?php if ($puedeAccion('Recepcion', 'eliminar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Anular Recepción</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre la recepción que desea anular.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la anulación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Anular:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Pasos para generar reportes de recepciones -->
                                <?php if ($puedeAccion('Recepcion', 'generar reporte')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Recepciones</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Elije el tipo de reporte: (Todos los Reportes, Recepciones por Proveedor, Productos mas Vendidos, Recepciones Mensuales).</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-pie-chart me-2"></i>
                                                    <strong>Gráficas:</strong><br> 5 tipos disponibles
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Despacho -->
                        <?php if ($puedeIngresar('Despacho')): ?>
                        <div class="card mb-4" id="despacho-productos">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-box-arrow-right me-2"></i>Despacho de Productos
                                </h5>
                                <p>Gestione la salida de productos del inventario hacia los clientes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Fecha de despacho</li>
                                            <li>Cliente</li>
                                            <li>Tipo de compra</li>
                                            <li>Productos</li>
                                            <li>Cantidad despachada</li>
                                            <li>Precio unitario</li>
                                            <li>Total del despacho</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Proceso de despacho:</h6>
                                        <ul>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Ver información completa</li>
                                            <li><strong>Anular</strong>: Remover despacho</li>
                                            <li><strong>Reportes</strong>: Gráficas parametrizadas</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos para detallar despacho -->
                                <?php if ($puedeAccion('Despacho', 'consultar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa del despacho.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus de Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>check</strong> (color verde) del despacho y cambiará automáticamente.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus:</strong><br> Botón "check" verde
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Instantáneo:</strong><br> Sin confirmación
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-danger border">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para anular despacho -->
                                <?php if ($puedeAccion('Despacho', 'eliminar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Anular Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre el despacho que desea anular.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la anulación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Anular:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Pasos para generar reportes de despachos -->
                                <?php if ($puedeAccion('Despacho', 'generar reporte')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Despachos</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Elije el tipo de reporte: (Todos los reportes, Por Estatus, Mensuales, Por Cliente o Por Tipo de Compra).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-pie-chart me-2"></i>
                                                    <strong>Gráficas:</strong><br> 5 tipos disponibles
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="note mt-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Los despachos reducen automáticamente el stock de productos del inventario.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Orden de Despacho -->
                        <div class="card mb-4" id="despacho-productos">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-box-arrow-right me-2"></i>Despacho de Productos
                                </h5>
                                <p>Gestione la verificación y entrega de las ordenes de despacho hacia los clientes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Fecha</li>
                                            <li>N° de orden de despacho</li>
                                            <li>Código de orden de compra</li></li>
                                            <li>Cliente</li>
                                            <li>Estatus</li>
                                            <li>Productos</li>
                                            <li>Cantidad</li>
                                            <li>Costo total</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Proceso de despacho:</h6>
                                        <ul>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Ver información completa</li>
                                            <li><strong>Descargar</strong>: Entregar orden de despacho</li>
                                            <li><strong>Anular</strong>: Remover orden de despacho</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos para detallar orden de despacho -->
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Orden de Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa de la orden de despacho.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus de la Orden de Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>check</strong> (color verde) de la orden de despacho y cambiará automáticamente.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus:</strong><br> Botón "check" verde
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Instantáneo:</strong><br> Sin confirmación
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-danger border">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para descargar orden de despacho -->
                                <div class="card mt-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Pasos para Descargar Orden de Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono de <strong>descarga</strong> <i class="bi bi-download text-info me-2"></i>en la columna "Acciones" para obtener la orden de despacho en formato PDF.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-download text-info me-2"></i>
                                                    <strong>Descargar:</strong> Ícono descarga
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-archive me-2"></i>
                                                    <strong>Archivo:</strong> Formato PDF
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para anular orden de despacho -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Anular Orden de Despacho</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre la orden de despacho que desea anular.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la anulación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Anular:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Marcas -->
                        <?php if ($puedeIngresar('Marcas')): ?>
                        <div class="card mt-4" id="gestion-marcas-almacenista">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-tag me-2"></i>Gestión de Marcas
                                </h5>
                                <p>Administre las marcas de productos para organizar mejor el inventario.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre de la marca</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nueva marca</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover marca</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar marca -->
                                <?php if ($puedeAccion('Marcas', 'incluir')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nueva Marca</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div>
                                                <h6 class="text-success">Formulario de Nueva Marca</h6>
                                                <div class="text-center">
                                                    <?= renderImagen('marca', 'incluir-modal.png', 'Botón "+" verde para crear nueva marca') ?>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) para nueva marca.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>nombre</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-patch-plus text-success me-2"></i>
                                                    <strong>Nueva Marca:</strong><br> Botón "+" verde
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Nombre de la Marca:</strong><br> (único en el sistema)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Pasos para modificar marca -->
                                <?php if ($puedeAccion('Marcas', 'modificar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Marca</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice la marca en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Pasos para eliminar marca -->
                                <?php if ($puedeAccion('Marcas', 'eliminar')): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Marca</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre al marca que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="note mt-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Las marcas ayudan a identificar y clasificar productos por fabricante.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Modelos -->
                        <div class="card mt-4" id="gestion-modelos-almacenista">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-cpu me-2"></i>Gestión de Modelos
                                </h5>
                                <p>Administre los modelos de productos para especificar versiones y variantes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre del modelo</li>
                                            <li>Marca asociada</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo modelo</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover modelo</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar modelo -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nuevo Modelo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div>
                                                <h6 class="text-success">Formulario de Nuevo Modelo</h6>
                                                <div class="text-center">
                                                    <?= renderImagen("modelo", "incluir-modal.png") ?>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) para nuevo modelo.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Seleccione la <strong>marca</strong> asociada.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>nombre</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-patch-plus text-success me-2"></i>
                                                    <strong>Nuevo Modelo:</strong><br> Botón "+" verde
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Nombre del Modelo:</strong><br> (único en el sistema)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar modelo -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Modelo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice el modelo en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para eliminar modelo -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Modelo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre el modelo que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="note mt-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Los modelos especifican versiones y variantes dentro de cada marca.
                                </div>
                            </div>
                        </div>

                        <!-- Productos -->
                        <div class="card mb-4 mt-4" id="gestion-productos-almacenista">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-box me-2"></i>Gestión de Productos
                                </h5>
                                <p>Administre el catálogo completo de productos del inventario.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Foto del producto</li>
                                            <li>Nombre y descripción</li>
                                            <li>Stock Actual/Máximo/Mínimo</li>
                                            <li>Número de serial</li>
                                            <li>Cláusula de garantía</li>
                                            <li>Categoría</li>
                                            <li>Precio</li>
                                            <li>Estatus</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo producto</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Ver información completa del producto</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover producto</li>
                                            <li><strong>Estatus</strong>: Actualizar estatus (habilitado/inhabilitado)</li>
                                            <li><strong>Reporte</strong>: Generar reportes</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar producto -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nuevo Producto</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Complete todos los campos obligatorios marcados con <strong>*</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese el <strong>nombre</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Seleccione el <strong>modelo/marca</strong> del producto.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Cargue una <strong>imagen</strong> del producto.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Ingrese una <strong>descripción</strong> breve.</li>
                                                    <li class="mb-2"><strong>Paso 7:</strong> Ingrese el <strong>stock</strong> (Actual, Máximo y Mínimo).</li>
                                                    <li class="mb-2"><strong>Paso 8:</strong> Redacte la <strong>cláusula de garantía</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 9:</strong> Seleccione la <strong>categoría</strong> y complete características específicas.</li>
                                                    <li class="mb-2"><strong>Paso 10:</strong> Ingrese el <strong>código serial</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 11:</strong> Ingrese su <strong>precio</strong> de venta.</li>
                                                    <li class="mb-2"><strong>Paso 12:</strong> Haga clic en <strong>"Registrar"</strong> para guardar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nuevo Producto:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-warning border mt-2">
                                                    <i class="bi bi-image me-2"></i>
                                                    <strong>Imagen:</strong> JPG/PNG requerida
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar:</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para detallar producto -->
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Producto</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa del producto.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar producto -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Producto</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice al producto en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-light border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para eliminar producto -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Producto</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre el producto que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning col-md-11 mx-auto">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Al eliminar un producto, se eliminarán todos sus datos incluyendo la imagen asociada.
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus de Proveedor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el estatus (habilitado/inhabilitado) del proveedor y cambiará automáticamente.</li>
                                                </ol>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus</strong><br> Click en el estatus (habilitado/inhabilitado)
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Instantáneo:</strong> Sin confirmación
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para generar reportes -->
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Productos</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Use el filtro de estatus para <strong>mostrar</strong>: Todos/Habilitados/Inhabilitados.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Elije el tipo de reporte: (Top Productos Más Vendidos, Stock Alto vs Bajo, Rotación de Productos).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Elije el top de productos.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-pie-chart me-2"></i>
                                                    <strong>Gráficas:</strong><br> 5 tipos disponibles
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Categorías -->
                        <div class="card" id="gestion-categorias-almacenista">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-folder me-2"></i>Gestión de Categorías
                                </h5>
                                <p>Administre las categorías para organizar mejor el catálogo de productos.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre de la categoría</li>
                                            <li>Características</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nueva categoría</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover categoría</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar categoría -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nueva Categoría</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) para nueva categoría.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>nombre</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Agregue las <strong>características específicas</strong> para la categoría.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nueva Categoría:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-person me-2"></i>
                                                    <strong>Nombre de Categoría:</strong> (único en el sistema)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-list-check me-2"></i>
                                                    <strong>Características:</strong><br> Mínimo 1 requerida
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar categoría -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Categoría</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice la categoría en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para eliminar categoría -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Categoría</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre la categoría que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="note mt-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Las categorías ayudan a organizar y filtrar productos en el catálogo.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Sección Administrador -->
                <?php
                $tieneAdmin = (
                    $puedeIngresar('Usuario') ||
                    $puedeIngresar('permisos') ||
                    $puedeIngresar('Roles') ||
                    $puedeIngresar('bitacora') ||
                    $puedeIngresar('Backup') ||
                    $puedeIngresar('Proveedores') ||
                    $puedeIngresar('Cuentas bancarias') ||
                    $puedeIngresar('Finanzas')
                );
                ?>
                <?php if ($tieneAdmin): ?>
                <section id="seccion-administrador" class="section-card">
                    <h2 class="section-title">
                        <i class="bi bi-shield-check me-2"></i>Sección para Administradores
                    </h2>
                    
                    <div class="row">
                        <div>
                            <p>Como administrador, tendrá control total sobre el sistema incluyendo gestión de usuarios, finanzas y configuración general.</p>
                        </div>
                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">Panel de Control</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <div class="display-6 text-danger mb-1">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <h6>Acceso Administrativo</h6>
                                    </div>
                                    
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-people me-2"></i>Usuarios</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-person-badge me-2"></i>Roles</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-bank me-2"></i>Finanzas</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-gear me-2"></i>Configuración</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Estadísticas del Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="display-6 text-info mb-1">100%</div>
                                            <p class="small text-muted mb-0">Uso del Sistema</p>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="display-6 text-info mb-1">24/7</div>
                                            <p class="small text-muted mb-0">Disponibilidad</p>
                                        </div>
                                        <div class="col-6">
                                            <div class="display-6 text-info mb-1">0</div>
                                            <p class="small text-muted mb-0">Errores Críticos</p>
                                        </div>
                                        <div class="col-6">
                                            <div class="display-6 text-info mb-1">99.9</div>
                                            <p class="small text-muted mb-0">Rendimiento</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Accesos Rápidos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-download me-2"></i>Generar Backup
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-arrow-repeat me-2"></i>Sincronizar Datos
                                        </button>
                                        <button class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-graph-up me-2"></i>Ver Reportes
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-shield-exclamation me-2"></i>Auditoría
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proveedores -->
                        <div class="card mt-4 mb-4" id="gestion-proveedores-admin">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-building me-2"></i>Gestión de Proveedores
                                </h5>
                                <p>Administre la información de todos los proveedores de productos y servicios.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre del proveedor y su rerpesentante</li>
                                            <li>RIF del proveedor y su rerpesentante</li>
                                            <li>Correo electrónico</li>
                                            <li>Dirección</li>
                                            <li>N° de teléfono (Principal y Secundario)</li>
                                            <li>Observaciones</li>
                                            <li>Estatus</li>
                                            <li>Suministro de los proveedores</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo proveedor</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Ver información completa del proveedor</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover proveedor</li>
                                            <li><strong>Estatus</strong>: Actualizar estatus (habilitado/inhabilitado)</li>
                                            <li><strong>Reporte</strong>: Generar reportes</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos detallados para registrar proveedor -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nuevo Proveedor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Complete todos los campos obligatorios marcados con <strong>"*"</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese <strong>nombre completo</strong> (Proveedor y Representante).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Ingrese el <strong>RIF</strong> (Proveedor y Representante).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Ingrese un <strong>correo electrónico</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Ingrese una <strong>dirección completa</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 7:</strong> Ingrese el <strong>N° de teléfono</strong> (Principal y Secundario).</li>
                                                    <li class="mb-2"><strong>Paso 8:</strong> Ingrese una o varias <strong>observaciones</strong> (mínimo 4 caracteres).</li>
                                                    <li class="mb-2"><strong>Paso 9:</strong> Haga clic en <strong>"Registrar"</strong> para guardar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nuevo Cliente:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>RIF:</strong> (VEJPG)-12345678-9
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-phone me-2"></i>
                                                    <strong>Teléfono:</strong> 0400-000-0000
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar:</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para detallar proveedor -->
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Proveedor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa del proveedor.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar proveedor -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Proveedor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice al proveedor en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-light border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para eliminar proveedor -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Proveedor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre al proveedor que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus de Proveedor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el estatus (habilitado/inhabilitado) del proveedor y cambiará automáticamente.</li>
                                                </ol>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus</strong><br> Click en el estatus (habilitado/inhabilitado)
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Instantáneo:</strong> Sin confirmación
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para generar reportes -->
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Proveedores</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Use el filtro de estatus para <strong>mostrar</strong>: Todos/Habilitados/Inhabilitados.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Elije el tipo de reporte: (Todos los Reportes, Suministro, Rancking, Comparación Mensual o Dependencia).</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-pie-chart me-2"></i>
                                                    <strong>Gráficas:</strong><br> 5 tipos disponibles
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border mt-2">
                                                    <i class="bi bi-download me-2"></i>
                                                    <strong>Descarga:</strong> PDF o Gráfica
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Clientes -->
                        <div class="card mb-4" id="gestion-clientes-admin">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-people me-2"></i>Gestión de Clientes
                                </h5>
                                <p>Administre la base de datos de clientes del sistema.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre completo</li>
                                            <li>Cédula</li>
                                            <li>N° de teléfono</li>
                                            <li>Dirección (Estado/Ciudad/Calle o Avenida)</li>
                                            <li>Correo electrónico</li>
                                            <li>Historial de compras</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo cliente</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover cliente</li>
                                            <li><strong>Reporte</strong>: Estadísticas de compras</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar cliente -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nuevo Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Complete todos los campos obligatorios marcados con <strong>*</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese el <strong>nombre completo</strong> (solo letras, mínimo 2 caracteres).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Ingrese la <strong>cédula</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Ingrese el <strong>N° de teléfono</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Ingrese una <strong>dirección completa</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 7:</strong> Ingrese un <strong>correo electrónico</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 8:</strong> Haga clic en <strong>"Registrar"</strong> para guardar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nuevo Cliente:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Cédula:</strong><br> 1.234.567 o 12.345.678
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-phone me-2"></i>
                                                    <strong>Teléfono:</strong> 0400-000-0000
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-envelope me-2"></i>
                                                    <strong>Correo:</strong> (gmail, outlook, yahoo, icloud)
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar:</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar cliente -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice al cliente en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para eliminar cliente -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre al cliente que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para generar reporte -->
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reporte de Clientes</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Consulte la sección <strong>"Top 10 Clientes por Productos Comprados"</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Visualice el gráfico de barras y la tabla detallada.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-graph-up me-2"></i>
                                                    <strong>Estadísticas:</strong> Top 10 clientes
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Usuarios -->
                        <div class="card mb-4" id="gestion-usuarios-sistema">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-person-badge me-2"></i>Gestión de Usuarios del Sistema
                                </h5>
                                <p>Administre los usuarios que tienen acceso al sistema.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre de usuario</li>
                                            <li>Contraseña</li>
                                            <li>Información personal</li>
                                            <li>Rol asignado</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo usuario</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover usuario</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Pasos detallados para registrar usuario -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nuevo Usuario</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div>
                                                <h6 class="text-success">Formulario de Nuevo Usuario</h6>
                                                <div class="text-center">
                                                    <?= renderImagen("usuario", "incluir-modal.png") ?>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Complete todos los campos obligatorios marcados con <strong>*</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese el <strong>nombre y apellido</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Ingrese la <strong>cédula</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Ingrese el <strong>N° de teléfono</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Ingrese el <strong>nombre de usuario</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 7:</strong> Ingrese un <strong>correo electrónico</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 8:</strong> Seleccione un <strong>rol</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 9:</strong> Ingrese una <strong>contraseña</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 10:</strong> Ingrese <strong>nuevamente</strong> la <strong>contraseña</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 11:</strong> Haga clic en <strong>"Registrar"</strong>.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nuevo Usuario:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Cédula:</strong><br> 1.234.567 o 12.345.678
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-phone me-2"></i>
                                                    <strong>Teléfono:</strong> 0400-000-0000
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-person me-2"></i>
                                                    <strong>Nombre de Usuario:</strong> (único en el sistema)
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-envelope me-2"></i>
                                                    <strong>Correo:</strong> (gmail, outlook, yahoo, icloud)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-key me-2"></i>
                                                    <strong>Contraseña:</strong> (6-15 caracteres, con al menos 1 mayúscula, 1 número y 1 caracter especial)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar usuario -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Usuario</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice al usuario en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para eliminar usuario -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Usuario</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre al usuario que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus de Usuario</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el estatus (habilitado/inhabilitado) del usuario y cambiará automáticamente.</li>
                                                </ol>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus</strong><br> Click en el estatus (habilitado/inhabilitado)
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Instantáneo:</strong> Sin confirmación
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para generar reportes de usuarios -->
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Usuarios</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Elije el tipo de reporte: Usuarios por (Rol, Estatus, Dominio de Correo, Inicial de Nombre, Inicial de Apellido o Prefijo Telefónico).</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Elige el rol de los usuarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para resetear contraseña -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Resetear Contraseña</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-info">SweetAlert de Confirmación</h6>
                                                <div class="text-center mb-3">
                                                    <?= renderImagen("usuarios", "sweetalert-resetear.png") ?>
                                                    <p class="text-muted small mt-2">Mensaje de confirmación para resetear contraseña</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Proceso paso a paso:</h6>
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice el usuario que necesita reseteo de contraseña</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de <strong>llave</strong> 🔑 junto al usuario</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme que desea resetear la contraseña en el SweetAlert</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> El sistema generará una <strong>contraseña temporal</strong></li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> La nueva contraseña se enviará automáticamente al correo del usuario</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> El usuario deberá cambiarla en el próximo inicio de sesión</li>
                                                </ol>
                                                
                                                <div class="alert alert-info mt-3">
                                                    <i class="bi bi-envelope-check me-2"></i>
                                                    <strong>Notificación:</strong> Correo enviado automáticamente
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <h6 class="text-info">SweetAlert de Éxito</h6>
                                                <div class="text-center">
                                                    <?= renderImagen("usuarios", "sweetalert-reseteado.png") ?>
                                                    <p class="text-muted small mt-2">Mensaje de éxito al resetear contraseña</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-key text-info me-2"></i>
                                                    <strong>Resetear Contraseña</strong>
                                                    <br><small>Ícono de llave</small>
                                                </div>
                                                <div class="alert alert-warning border mt-2">
                                                    <i class="bi bi-envelope me-2"></i>
                                                    <strong>Importante:</strong> El usuario debe tener correo válido
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-shield-lock me-2"></i>
                                                    <strong>Seguridad:</strong> Contraseña temporal de un solo uso
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Roles -->
                        <div class="card mb-4" id="gestion-roles-permisos">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-person-badge-fill me-2"></i>Gestión de Roles y Permisos
                                </h5>
                                <p>Defina los roles y permisos para controlar el acceso al sistema.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre del rol</li>
                                            <li>Acceso a módulos</li>
                                            <li>Operaciones CRUD</li>
                                            <li>Visibilidad de datos</li>
                                            <li>Generación de reportes</li>
                                            <li>Configuración del sistema</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo rol</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Gestionar Permisos</strong>: Asignar permisos al rol</li>
                                            <li><strong>Eliminar</strong>: Remover rol</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos detallados para registrar rol -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nueva Rol</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) para nuevo rol.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>nombre</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-patch-plus text-success me-2"></i>
                                                    <strong>Nueva Marca:</strong><br> Botón "+" verde
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Nombre de la Marca:</strong><br> (único en el sistema)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar rol -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Rol</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice el rol en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos detallados para gestionar los permisos del rol -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Gestionar los Permisos del Rol</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>Gestionar Permisos</strong> (color verde) del rol a configurar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Seleccione que <strong>acciones</strong> podrá realizar el usuario que tenga este rol en cada <strong>módulo</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Haga clic en <strong>"Guardar Permisos"</strong> para confirmar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-patch-plus text-success me-2"></i>
                                                    <strong>Gestionar Permisos:</strong><br> Botón "Gestionar Permisos" verde
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para eliminar rol -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Rol</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre el rol que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagos -->
                        <div class="card mb-4" id="gestion-pagos-bancarias">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bank me-2"></i>Gestión de Pagos
                                </h5>
                                <p>Administre los pagos realizados por los clientes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Estatus de los pagos</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Estatus</strong>: Actualizar estatus <br> (Procesado/No Encontrado/Incompleto)</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus del Pago</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"Cambiar Estatus"</strong> del pago.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Seleccione el <strong>estatus</strong> a asignar (Procesado/No Encontrado/Incompleto).</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Haga clic en <strong>"Guardar Cambios"</strong> para realizar el cambio.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border mt-2">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus:</strong><br> Botón "Cambiar Estatus" azul
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bancos -->
                        <div class="card mb-4" id="gestion-cuentas-bancarias">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bank me-2"></i>Gestión de Cuentas Bancarias
                                </h5>
                                <p>Administre las cuentas bancarias para transacciones financieras.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre del banco</li>
                                            <li>Número de cuenta</li>
                                            <li>RIF</li>
                                            <li>Número de teléfono</li>
                                            <li>Correo electrónico</li>
                                            <li>Tipo de moneda</li>
                                            <li>Metodos de pago</li>
                                            <li>Estatus</li>
                                            <li>Saldo actual</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Agregar</strong>: Nueva cuenta</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Ver información completa</li>
                                            <li><strong>Modificar</strong>: Actualizar datos</li>
                                            <li><strong>Eliminar</strong>: Remover cuenta</li>
                                            <li><strong>Estatus</strong>: Actualizar estatus (habilitado/inhabilitado)</li>
                                            <li><strong>Conciliación</strong>: Balance de cuentas</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos detallados para registrar cuenta bancaria -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nueva Cuenta Bancaria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Complete todos los campos obligatorios marcados con <strong>*</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese el <strong>nombre del banco</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Ingrese el <strong>N° de cuenta</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Ingrese el <strong>RIF</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Ingrese el <strong>N° de teléfono</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 7:</strong> Ingrese un <strong>correo electrónico</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 8:</strong> Seleccione un tipo de <strong>moneda</strong> (Bolívares o Dolares).</li>
                                                    <li class="mb-2"><strong>Paso 9:</strong> Seleccione el o los <strong>métodos de pago</strong> (Pago Móvil, Transferencia y/o Zelle).</li>
                                                    <li class="mb-2"><strong>Paso 10:</strong> Haga clic en <strong>"Registrar"</strong>.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nuevo Usuario:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-bank me-2"></i>
                                                    <strong>N° de Cuenta:</strong><br> 0100-0000-00-0000000000
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>RIF:</strong> (VEJPG)-12345678-9
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-phone me-2"></i>
                                                    <strong>Teléfono:</strong> 0400-000-0000
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-envelope me-2"></i>
                                                    <strong>Correo:</strong> (gmail, outlook, yahoo, icloud)
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para detallar cuenta bancaria -->
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Cuenta Bancaria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa de la cuenta bancaria.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar cuenta bancaria -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Cuenta Bancaria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice la cuenta bancaria en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para eliminar cuenta bancaria -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Cuenta Bancaria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre la cuenta bancaria que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus de Cuenta Bancaria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el estatus (habilitado/inhabilitado) de la cuenta bancaria y cambiará automáticamente.</li>
                                                </ol>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus</strong><br> Click en el estatus (habilitado/inhabilitado)
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info border">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Instantáneo:</strong> Sin confirmación
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para generar reportes de cuentas bancarias -->
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Cuentas Bancarias</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Use el filtro de estatus para <strong>mostrar</strong>: Todos/Habilitados/Inhabilitados.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Elije el tipo de reporte: Agrupar por (Método de Pago, Banco, Cliente o Estatus).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-pie-chart me-2"></i>
                                                    <strong>Gráficas:</strong><br> 5 tipos disponibles
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Finanzas -->
                        <div class="card mb-4" id="gestion-finanzas-bancarias">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bank me-2"></i>Gestión de Ingresos y Egresos
                                </h5>
                                <p>Administre los Ingresos y Egresos de la empresa.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Ingresos y Egresos</li>
                                            <li>Fecha</li>
                                            <li>RIF</li>
                                            <li>Monto</li>
                                            <li>Decripción (Productos involucrados)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Reporte</strong>: Generar gráfica</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos para generar reportes de finanzas -->
                                <div class="card mt-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Pasos para Generar Reportes de Ingresos y Egresos</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Use el filtro de estatus para <strong>mostrar</strong>: Todos/Habilitados/Inhabilitados.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Elije el tipo de reporte: Agrupar por (Método de Pago, Banco, Cliente o Estatus).</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                                                    <li class="mb-2"><strong>Paso 6:</strong> Haga clic en <strong>"Generar"</strong> para visualizar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-pie-chart me-2"></i>
                                                    <strong>Gráficas:</strong><br> 5 tipos disponibles
                                                </div>
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-file-earmark-bar-graph text-secondary me-2"></i>
                                                    <strong>Reportes:</strong> Múltiples tipos
                                                </div>
                                                <div class="alert alert-warning border">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                    <strong>Reporte PDF:</strong> Descarga automática
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Catálogo de Combos -->
                        <div class="card" id="catalogo-combos-promocionales">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-tags-fill me-2"></i>Catálogo de Combos Promocionales
                                </h5>
                                <p>Configure y gestione los combos promocionales para ofrecer mejores precios a los clientes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Nombre del combo</li>
                                            <li>Descripción</li>
                                            <li>Productos incluidos</li>
                                            <li>Precio especial</li>
                                            <li>Descuento aplicado</li>
                                            <li>Fecha de vigencia</li>
                                            <li>Estatus</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nuevo combo</li>
                                            <li><strong>Modificar</strong>: Actualizar productos</li>
                                            <li><strong>Eliminar</strong>: Desactivar combo</li>
                                            <li><strong>Estatus</strong>: Actualizar estatus (habilitado/inhabilitado)</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos detallados para registrar combo -->
                                <div class="card mt-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Pasos para Registrar Nuevo Rol</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) para nueva categoría.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>nombre</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Ingrese una <strong>descripción</strong> breve.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Agregue los <strong>productos</strong> y la <strong>cantidad</strong> de cada uno.</li>
                                                    <li class="mb-2"><strong>Paso 5:</strong> Haga clic en <strong>"Guardar Combo"</strong> para confirmar.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-person-plus text-success me-2"></i>
                                                    <strong>Nuevo Combo:</strong><br> Botón "+" verde
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-person me-2"></i>
                                                    <strong>Cantidad de Productos:</strong> Mínimo 2 productos
                                                </div>
                                                <div class="alert alert-light border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pasos para modificar combo -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Modificar Combo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Localice el combo en la tabla.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                                    <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-pencil text-info me-2"></i>
                                                    <strong>Modificar:</strong> Ícono lápiz
                                                </div>
                                                <div class="alert alert-info border mt-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para eliminar combo -->
                                <div class="card mt-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Pasos para Eliminar Combo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Encuentre el combo que desea eliminar.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                                    <li class="mb-2"><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <strong>Eliminar:</strong> Ícono X rojo
                                                </div>
                                                <div class="alert alert-danger border mt-2">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para cambiar estatus -->
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Pasos para Cambiar Estatus del Combo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el estatus (habilitado/inhabilitado) del combo.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en <strong>"Confirmar"</strong> para realizar el cambio.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border mt-2">
                                                    <i class="bi bi-toggle-on text-info me-2"></i>
                                                    <strong>Cambiar Estatus:</strong><br> Click en el estatus (habilitado/inhabilitado)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="note mt-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Los combos ayudan a aumentar las ventas y mejorar la satisfacción del cliente.
                                </div>
                            </div>
                        </div>

                        <!-- ventas presenciales -->
                        <div class="card mt-4" id="gestion-ventas-almacenista">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-tag me-2"></i>Gestión de Ventas Presenciales
                                </h5>
                                <p>Consulte sus ventas presenciales y realice los pagos o cancelaciones correspondientes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información gestionable:</h6>
                                        <ul>
                                            <li>Ventas presenciales realizadas</li>
                                            <li>Fecha de la venta</li>
                                            <li>Cliente</li>
                                            <li>Costo de la compra</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Operaciones disponibles:</h6>
                                        <ul>
                                            <li><strong>Registrar</strong>: Nueva venta presencial</li>
                                            <li><strong>Consultar</strong>: Ver lista completa</li>
                                            <li><strong>Detallar</strong>: Información detallada de la venta presencial</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pasos detallados para registrar venta -->
                                <div class="card mt-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Pasos para Registrar la Venta Presencial</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <ul>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Busque el <strong>Cliente</strong>.</li>
                                                    <li class="mb-2"><strong>Paso 2:</strong> Haga clic en <strong>"Lista de Productos"</strong> y seleccione los productos recibidos, costo por unidad y cantidad.</li>
                                                </ul>

                                                <hr>

                                                <h6 class="text-primary">1. Registro de venta por Pago Móvil o Transferencia</h6>
                                                <div class="row">
                                                    <div class="col-md-7 mt-2">
                                                        <ul>
                                                            <li class="mb-2"><strong>Paso 3:</strong> Seleccione el <strong>banco emisor</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 4:</strong> Ingrese el <strong>N° de referecia</strong> del pago realizado.</li>
                                                            <li class="mb-2"><strong>Paso 5:</strong> Agregue la imagen del <strong>comprobante de pago</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 6:</strong> Ingrese el <strong>monto pagado</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 7:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-plus-circle text-success me-2"></i>
                                                            <strong>Realizar Pago:</strong><br> Botón "Registrar" azul
                                                        </div>
                                                        <div class="alert alert-light border mt-2">
                                                            <i class="bi bi-image me-2"></i>
                                                            <strong>Imagen:</strong> JPG/PNG <br> requerida
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">2. Registro de venta en efectivo</h6>
                                                <div class="row">
                                                    <div class="col-md-7 mt-2">
                                                        <ul>
                                                            <li class="mb-2"><strong>Paso 3:</strong> Ingrese el <strong>monto pagado</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-plus-circle text-success me-2"></i>
                                                            <strong>Regristrar Venta:</strong><br> Botón "Registrar" azul
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="text-primary">2. Registro de venta por Zelle</h6>
                                                <div class="row">
                                                    <div class="col-md-7 mt-2">
                                                        <ul>
                                                            <li class="mb-2"><strong>Paso 3:</strong> Seleccione el <strong>banco emisor</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 4:</strong> Ingrese el nombre del <strong>propietario</strong> de la cuenta Zelle.</li>
                                                            <li class="mb-2"><strong>Paso 5:</strong> Ingrese el <strong>monto pagado</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 6:</strong> Ingrese el <strong>N° de referecia</strong> del pago realizado.</li>
                                                            <li class="mb-2"><strong>Paso 7:</strong> Agregue la imagen del <strong>comprobante de pago</strong>.</li>
                                                            <li class="mb-2"><strong>Paso 8:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="alert alert-light border">
                                                            <i class="bi bi-plus-circle text-success me-2"></i>
                                                            <strong>Realizar Pago:</strong><br> Botón "Registrar" azul
                                                        </div>
                                                        <div class="alert alert-light border mt-2">
                                                            <i class="bi bi-image me-2"></i>
                                                            <strong>Imagen:</strong> JPG/PNG <br> requerida
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="note col-md-11 mx-auto">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            En caso de que el cliente no este registrado en el sistema, dar clic en el botón "Nuevo" (color verde) y procesa a registrarlo. Al terminar, vuelva a "Ventas Presenciales" y repita el procedimiento.
                                        </div>
                                    </div>
                                </div>

                                <!-- Pasos para detallar ventas -->
                                <div class="card mt-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Pasos para Detallar Venta Presencial</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <ol>
                                                    <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i>en la columna "Acciones" para ver la información completa de la venta.</li>
                                                </ol>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-eye text-warning me-2"></i>
                                                    <strong>Detallar:</strong> Ícono ojo
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <?php /* if ($esCliente || $esAdministrador): */ ?>
                    <!-- Sección para Clientes -->
                    <?php /* if ($esCliente): */ ?>
                        <!-- Catálogo de Productos -->
                        <section id="seccion-cliente" class="section-card">
                            <h2 class="section-title">Catálogo de Productos</h2>
                            <?php
                            $datos_catalogo = [
                                "id" => "catalogo",
                                "nombre_singular" => "Producto",
                                "nombre_plural" => "Productos",
                                "gestionable" => [
                                    "Ver lista de productos disponibles",
                                    "Filtrar productos por categoría",
                                    "Buscar productos específicos",
                                    "Ver detalles completos de cada producto"
                                ],
                                "instrucciones" => [
                                    "Navegue por las diferentes categorías de productos",
                                    "Utilice la barra de búsqueda para encontrar productos específicos",
                                    "Haga clic en un producto para ver más detalles"
                                ]
                            ];
                            plantilla("inicio", $datos_catalogo);
                            ?>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h4>Vista de Productos</h4>
                                    <p>Explore los productos disponibles en el catálogo.</p>
                                    <?= renderImagen("catalogo", "vista-productos.png") ?>
                                </div>
                                <div class="col-md-6">
                                    <h4>Detalles del Producto</h4>
                                    <p>Vea información detallada de cada producto.</p>
                                    <?= renderImagen("catalogo", "detalle-producto.png") ?>
                                </div>
                            </div>
                        </section>

                    <?php /* endif; */ ?>
                    
                    <!-- Sección para Administradores -->
                    <?php /* if ($esAdministrador): */ ?>
                        <?php /* include 'plantillas/seccion-almacenista.php'; */ ?>
                        <?php /* include 'plantillas/seccion-administrador.php'; */ ?>
                    <?php /* endif; */ ?>
                <?php /* else: */ ?>
                <?php endif; ?>
                    <!-- Sección de Inicio de Sesión -->
                    <section id="iniciar-sesion" class="section-card">
                        <h2 class="section-title">Iniciar Sesión</h2>
                        <p>Para acceder al sistema, siga estos pasos:</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Paso 1: Ingrese sus credenciales</h5>
                                        <p class="card-text">Ingrese su nombre de usuario y contraseña en los campos correspondientes.</p>
                                        <?= renderImagen("login", "login.png") ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Paso 2: Acceda al sistema</h5>
                                        <p class="card-text">Haga clic en el botón "Iniciar Sesión" para acceder a su cuenta.</p>
                                        <?= renderImagen("login", "dashboard.png") ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="note mt-4">
                            <i class="bi bi-question-circle-fill me-2"></i>
                            <strong>¿Problemas para iniciar sesión?</strong> Contacte al administrador del sistema para restablecer su contraseña.
                        </div>
                    </section>
                <?php /* endif; */ ?>

                <!-- Preguntas Frecuentes 
                <section id="preguntas-frecuentes" class="section-card">
                    <h2 class="section-title">Preguntas Frecuentes</h2>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    ¿Cómo cambio mi contraseña?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Para cambiar su contraseña, siga estos pasos:</p>
                                    <ol>
                                        <li>Haga clic en su nombre de usuario en la esquina superior derecha.</li>
                                        <li>Seleccione "Mi Perfil".</li>
                                        <li>Haga clic en "Cambiar Contraseña".</li>
                                        <li>Ingrese su contraseña actual y la nueva contraseña dos veces.</li>
                                        <li>Haga clic en "Guardar Cambios".</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    ¿Cómo realizo una nueva venta?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Para realizar una nueva venta:</p>
                                    <ol>
                                        <li>Vaya a la sección "Ventas" en el menú lateral.</li>
                                        <li>Haga clic en "Nueva Venta".</li>
                                        <li>Seleccione el cliente o cree uno nuevo.</li>
                                        <li>Agregue los productos al carrito.</li>
                                        <li>Revise el resumen y haga clic en "Finalizar Venta".</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    ¿Cómo genero un reporte de ventas?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Para generar un reporte de ventas:</p>
                                    <ol>
                                        <li>Vaya a la sección "Reportes" en el menú lateral.</li>
                                        <li>Seleccione el tipo de reporte "Ventas".</li>
                                        <li>Establezca el rango de fechas.</li>
                                        <li>Seleccione los filtros adicionales si es necesario.</li>
                                        <li>Haga clic en "Generar Reporte".</li>
                                        <li>Puede exportar el reporte a PDF o Excel según necesite.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
               -->
                <!-- Footer -->
                <footer class="text-center text-muted py-4 mt-5 border-top">
                    <p class="mb-1">© <?= date('Y') ?> Casa Lai, C.A. Todos los derechos reservados.</p>
                    <p class="mb-0">Versión del Sistema: 2.0.0</p>
                </footer>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Activar popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Smooth scrolling para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const offset = 80; // Ajuste para el header sticky
                    const targetPosition = targetElement.offsetTop - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Actualizar URL sin recargar la página
                    history.pushState(null, null, targetId);
                    
                    // Marcar el enlace como activo
                    updateActiveLink(targetId);
                }
            });
        });
        
        // Función para actualizar el enlace activo
        function updateActiveLink(targetId) {
            // Remover clase active de todos los enlaces
            document.querySelectorAll('.toc-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Agregar clase active a todos los enlaces que apuntan al mismo target
            const activeLinks = document.querySelectorAll(`.toc-link[href="${targetId}"]`);
            activeLinks.forEach(link => {
                link.classList.add('active');
            });
            
            // Si hay enlaces activos, hacer scroll al primero
            if (activeLinks.length > 0) {
                const firstActiveLink = activeLinks[0];
                const toc = document.querySelector('.toc');
                const tocRect = toc.getBoundingClientRect();
                const linkRect = firstActiveLink.getBoundingClientRect();
                
                if (linkRect.bottom > tocRect.bottom - 20) {
                    toc.scrollTop += (linkRect.bottom - tocRect.bottom) + 20;
                } else if (linkRect.top < tocRect.top + 20) {
                    toc.scrollTop -= (tocRect.top - linkRect.top) + 20;
                }
            }
        }
        
        // Detectar sección activa al hacer scroll
        function updateActiveOnScroll() {
            const sections = document.querySelectorAll('section[id]');
            const scrollPosition = window.scrollY + 100;
            
            let currentSection = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    currentSection = '#' + section.getAttribute('id');
                }
            });
            
            if (currentSection) {
                updateActiveLink(currentSection);
            }
        }
        
        // Event listener para scroll
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(updateActiveOnScroll, 100);
        });
        
        // Actualizar enlace activo al cargar la página
        window.addEventListener('load', () => {
            const hash = window.location.hash || '#introduccion';
            updateActiveLink(hash);
            updateActiveOnScroll();
            updateTocProgress();
        });
        
        // Actualizar indicador de progreso
        function updateTocProgress() {
            const tocProgress = document.getElementById('tocProgress');
            if (!tocProgress) return;
            
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight - windowHeight;
            const scrolled = window.scrollY;
            const progress = (scrolled / documentHeight) * 100;
            
            const tocHeight = document.querySelector('.toc').offsetHeight;
            const progressHeight = (progress / 100) * tocHeight;
            
            tocProgress.style.height = Math.min(progressHeight, tocHeight) + 'px';
        }
        
        // Actualizar progreso al hacer scroll
        window.addEventListener('scroll', () => {
            updateTocProgress();
        });
        
        // Manejar el tema claro/oscuro
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const html = document.documentElement;
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Actualizar ícono
                const icon = this.querySelector('i');
                if (newTheme === 'dark') {
                    icon.classList.remove('bi-moon');
                    icon.classList.add('bi-sun');
                } else {
                    icon.classList.remove('bi-sun');
                    icon.classList.add('bi-moon');
                }
            });
            
            // Cargar tema guardado
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            
            // Actualizar ícono según el tema guardado
            const icon = themeToggle.querySelector('i');
            if (savedTheme === 'dark') {
                icon.classList.remove('bi-moon');
                icon.classList.add('bi-sun');
            }
        }
    </script>
</body>
</html>
