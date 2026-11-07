<?php
// Archivo: sidebar.php - Menú lateral reutilizable con acordeón y permisos por rol
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Carrito;

$id_rol = $_SESSION['id_rol'] ?? 0;
$nombre_rol = $_SESSION['nombre_rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

$permisosObj = new Permisos();
$carritoObj = new Carrito();

// Definir módulos con sus rutas correspondientes
$modulos = [
    'Usuario' => ['gestionarusuario', '?pagina=usuario'],
    'Permisos' => ['reporteperfil', '?pagina=reporteUsuario'],
    'Recepcion' => ['gestionarrecepcion', '?pagina=recepcion'],
    'Inventario' => ['reporteinventario', '?pagina=reporteInventario'],
    'Marcas' => ['gestionarmarcas', '?pagina=marca'],
    'Modelos' => ['gestionarmodelos', '?pagina=modelo'],
    'Productos' => ['gestionarproductos', '?pagina=producto'],
    'Categorias' => ['gestionarcategorias', '?pagina=categoria'],
    'ReporteProductos' => ['reporteproductos', '?pagina=reporteProductos'],
    'Proveedores' => ['gestionarproveedores', '?pagina=proveedor'],
    'ReporteProveedores' => ['reporteproveedores', '?pagina=reporteProveedores'],
    'Clientes' => ['gestionarclientes', '?pagina=cliente'],
    'ReporteClientes' => ['reporteclientes', '?pagina=reporteCliente'],
    'Catalogo' => ['gestionarcatalogo', '?pagina=catalogo'],
    'Pedidos' => ['gestionarpedidos', '?pagina=gestionarfactura'],
    'Despacho' => ['gestionardespachos', '?pagina=despacho'],
    'ReporteVentas' => ['reporteventas', '?pagina=reporteVentas'],
    'CuentasBancarias' => ['gestionarcuentas', '?pagina=cuenta'],
    'IngresosEgresos' => ['gestionaringresos', '?pagina=finanza'],
    'Finanzas' => ['reportefinanzas', '?pagina=reporteFinanzas'],
    'Permisos' => ['gestionarpermisos', '?pagina=permiso'],
    'Roles' => ['gestionarroles', '?pagina=rol'],
    'Bitacora' => ['gestionarbitacora', '?pagina=bitacora'],
    'Backup' => ['gestionarbackup', '?pagina=backup'],
];

// Verificar permisos para cada módulo
$permisosConsulta = [];
if ($nombre_rol === 'SuperUsuario') {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = true;
    }
} else {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
    }
}
?>
<!-- Sidebar Navigation -->
<aside class="sidebar" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
    <div class="sidebar-header">
        <div class="logo">
            <img src="assets/img/LOGO.png" alt="CasaLai Logo">
        </div>
        <h2>CasaLai C.A</h2>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Administrar Perfiles -->
        <?php if ($permisosConsulta['Usuario'] ?? false || $permisosConsulta['Permisos'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="perfiles-menu">
                <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                <span class="nav-text">Administrar Perfiles</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="perfiles-menu">
                <?php if ($permisosConsulta['Usuario'] ?? false): ?>
                <a href="?pagina=usuario" class="nav-link <?php echo ($pagina_actual ?? '') === 'usuario' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Usuario</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Permisos'] ?? false): ?>
                <a href="?pagina=reporteUsuario" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteUsuario' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reporte de Perfiles</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Inventario -->
        <?php if ($permisosConsulta['Recepcion'] ?? false || $permisosConsulta['Inventario'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="inventario-menu">
                <span class="nav-icon"><i class="fas fa-boxes"></i></span>
                <span class="nav-text">Administrar Inventario</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="inventario-menu">
                <?php if ($permisosConsulta['Recepcion'] ?? false): ?>
                <a href="?pagina=recepcion" class="nav-link <?php echo ($pagina_actual ?? '') === 'recepcion' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Recepción</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Inventario'] ?? false): ?>
                <a href="?pagina=reporteInventario" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteInventario' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reporte de Inventario</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Productos -->
        <?php if ($permisosConsulta['Marcas'] ?? false || $permisosConsulta['Modelos'] ?? false || $permisosConsulta['Productos'] ?? false || $permisosConsulta['Categorias'] ?? false || $permisosConsulta['ReporteProductos'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="productos-menu">
                <span class="nav-icon"><i class="fas fa-box"></i></span>
                <span class="nav-text">Administrar Productos</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="productos-menu">
                <?php if ($permisosConsulta['Marcas'] ?? false): ?>
                <a href="?pagina=marca" class="nav-link <?php echo ($pagina_actual ?? '') === 'marca' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Marcas</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Modelos'] ?? false): ?>
                <a href="?pagina=modelo" class="nav-link <?php echo ($pagina_actual ?? '') === 'modelo' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Modelos</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Productos'] ?? false): ?>
                <a href="?pagina=producto" class="nav-link <?php echo ($pagina_actual ?? '') === 'producto' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Productos</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Categorias'] ?? false): ?>
                <a href="?pagina=categoria" class="nav-link <?php echo ($pagina_actual ?? '') === 'categoria' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Categorías</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['ReporteProductos'] ?? false): ?>
                <a href="?pagina=reporteProductos" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteProductos' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reporte de Productos</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Proveedores -->
        <?php if ($permisosConsulta['Proveedores'] ?? false || $permisosConsulta['ReporteProveedores'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="proveedores-menu">
                <span class="nav-icon"><i class="fas fa-truck"></i></span>
                <span class="nav-text">Administrar Proveedores</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="proveedores-menu">
                <?php if ($permisosConsulta['Proveedores'] ?? false): ?>
                <a href="?pagina=proveedor" class="nav-link <?php echo ($pagina_actual ?? '') === 'proveedor' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Proveedores</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['ReporteProveedores'] ?? false): ?>
                <a href="?pagina=reporteProveedores" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteProveedores' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reporte de Proveedores</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Clientes -->
        <?php if ($permisosConsulta['Clientes'] ?? false || $permisosConsulta['ReporteClientes'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="clientes-menu">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span class="nav-text">Administrar Clientes</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="clientes-menu">
                <?php if ($permisosConsulta['Clientes'] ?? false): ?>
                <a href="?pagina=cliente" class="nav-link <?php echo ($pagina_actual ?? '') === 'cliente' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Clientes</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['ReporteClientes'] ?? false): ?>
                <a href="?pagina=reporteCliente" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteCliente' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reporte de Clientes</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Ventas -->
        <?php if ($permisosConsulta['Catalogo'] ?? false || $permisosConsulta['Pedidos'] ?? false || $permisosConsulta['Despacho'] ?? false || $permisosConsulta['ReporteVentas'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="ventas-menu">
                <span class="nav-icon"><i class="fas fa-shopping-cart"></i></span>
                <span class="nav-text">Administrar Ventas</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="ventas-menu">
                <?php if ($permisosConsulta['Catalogo'] ?? false): ?>
                <a href="?pagina=catalogo" class="nav-link <?php echo ($pagina_actual ?? '') === 'catalogo' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Catálogo</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Pedidos'] ?? false): ?>
                <a href="?pagina=gestionarfactura" class="nav-link <?php echo ($pagina_actual ?? '') === 'gestionarfactura' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Ventas Presenciales</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Despacho'] ?? false): ?>
                <a href="?pagina=despacho" class="nav-link <?php echo ($pagina_actual ?? '') === 'despacho' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Despachos</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['ReporteVentas'] ?? false): ?>
                <a href="?pagina=reporteVentas" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteVentas' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reporte de Ventas</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Finanzas -->
        <?php if ($permisosConsulta['CuentasBancarias'] ?? false || $permisosConsulta['IngresosEgresos'] ?? false || $permisosConsulta['Finanzas'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="finanzas-menu">
                <span class="nav-icon"><i class="fas fa-dollar-sign"></i></span>
                <span class="nav-text">Administrar Finanzas</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="finanzas-menu">
                <?php if ($permisosConsulta['CuentasBancarias'] ?? false): ?>
                <a href="?pagina=cuenta" class="nav-link <?php echo ($pagina_actual ?? '') === 'cuenta' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Cuentas Bancarias</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['IngresosEgresos'] ?? false): ?>
                <a href="?pagina=finanza" class="nav-link <?php echo ($pagina_actual ?? '') === 'finanza' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Ingresos y Egresos</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Finanzas'] ?? false): ?>
                <a href="?pagina=reporteFinanzas" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteFinanzas' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Reportes de Finanzas</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administrar Seguridad -->
        <?php if ($permisosConsulta['Permisos'] ?? false || $permisosConsulta['Roles'] ?? false || $permisosConsulta['Bitacora'] ?? false || $permisosConsulta['Backup'] ?? false): ?>
        <div class="nav-item">
            <a href="#" class="nav-link accordion-toggle" data-target="seguridad-menu">
                <span class="nav-icon"><i class="fas fa-shield-alt"></i></span>
                <span class="nav-text">Administrar Seguridad</span>
                <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
            </a>
            <div class="nav-submenu" id="seguridad-menu">
                <?php if ($permisosConsulta['Permisos'] ?? false): ?>
                <a href="?pagina=permiso" class="nav-link <?php echo ($pagina_actual ?? '') === 'permiso' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Permisos</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Roles'] ?? false): ?>
                <a href="?pagina=rol" class="nav-link <?php echo ($pagina_actual ?? '') === 'rol' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Roles</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Bitacora'] ?? false): ?>
                <a href="?pagina=bitacora" class="nav-link <?php echo ($pagina_actual ?? '') === 'bitacora' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Bitácora</span>
                </a>
                <?php endif; ?>
                <?php if ($permisosConsulta['Backup'] ?? false): ?>
                <a href="?pagina=backup" class="nav-link <?php echo ($pagina_actual ?? '') === 'backup' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chevron-right"></i></span>
                    <span class="nav-text">Gestionar Backup</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panel Principal -->
        <div class="nav-item">
            <a href="?pagina=dashboard" class="nav-link <?php echo ($pagina_actual ?? '') === 'dashboard' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                <span class="nav-text">Panel Principal</span>
            </a>
        </div>

        <!-- Notificaciones -->
        <div class="nav-item">
            <a href="?pagina=notificacion" class="nav-link <?php echo ($pagina_actual ?? '') === 'notificacion' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span class="nav-text">Notificaciones</span>
            </a>
        </div>

        <!-- Perfil -->
        <div class="nav-item">
            <a href="?pagina=perfil" class="nav-link <?php echo ($pagina_actual ?? '') === 'perfil' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-user"></i></span>
                <span class="nav-text">Perfil</span>
            </a>
        </div>
    </nav>
</aside>

<style>
    /* Estilos del Acordeón del Sidebar */
    .nav-item {
        margin-bottom: 5px;
    }

    .accordion-toggle {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .accordion-icon {
        transition: transform 0.3s ease;
        font-size: 0.8rem;
    }

    .accordion-toggle.active .accordion-icon {
        transform: rotate(90deg);
    }

    .nav-submenu {
        display: none;
        margin-left: 20px;
        padding-left: 10px;
        border-left: 2px solid rgba(255, 255, 255, 0.2);
    }

    .nav-submenu.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    .nav-submenu .nav-link {
        padding: 10px 15px;
        font-size: 0.9rem;
    }

    .nav-submenu .nav-icon {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.7);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
        }
        to {
            opacity: 1;
            max-height: 500px;
        }
    }
</style>

<script>
    // Funcionalidad del Acordeón
    document.addEventListener('DOMContentLoaded', function() {
        const accordionToggles = document.querySelectorAll('.accordion-toggle');
        
        accordionToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('data-target');
                const submenu = document.getElementById(targetId);
                
                if (submenu) {
                    // Cerrar otros submenús
                    document.querySelectorAll('.nav-submenu').forEach(sm => {
                        if (sm.id !== targetId) {
                            sm.classList.remove('show');
                        }
                    });
                    
                    document.querySelectorAll('.accordion-toggle').forEach(at => {
                        if (at !== this) {
                            at.classList.remove('active');
                        }
                    });
                    
                    // Toggle el submenu actual
                    submenu.classList.toggle('show');
                    this.classList.toggle('active');
                }
            });
        });
        
        // Abrir el submenú activo por defecto
        const activeLink = document.querySelector('.nav-submenu .nav-link.active');
        if (activeLink) {
            const submenu = activeLink.closest('.nav-submenu');
            const toggle = submenu.previousElementSibling;
            if (toggle && toggle.classList.contains('accordion-toggle')) {
                submenu.classList.add('show');
                toggle.classList.add('active');
            }
        }
    });
</script>
