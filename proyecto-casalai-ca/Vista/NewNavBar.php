<?php
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Carrito;
$id_rol = $_SESSION['id_rol'];
$nombre_rol = $_SESSION['nombre_rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

$permisosObj = new Permisos();
$carritoObj = new Carrito();
// Obtener datos de la tasa BCV (registro del día)
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
$dolarService = new DolarService();
$registroDolar = $dolarService->obtenerRegistroDelDia();
$tasaBCV = isset($registroDolar['precio']) ? (float)$registroDolar['precio'] : $dolarService->obtenerPrecioDelDia();
$tasaBCVFormateada = number_format($tasaBCV, 2);
$tasaFechaFormateada = isset($registroDolar['fecha']) ? date('d/m/Y H:i', strtotime($registroDolar['fecha'])) : date('d/m/Y H:i');

$modulos = [
    'Usuario' => ['Gestionar Usuario', 'assets/img/users-round.svg', '?pagina=usuario'],
    'Recepcion' => ['Gestionar Recepcion', 'assets/img/package-open.svg', '?pagina=recepcion'],
    'Despacho' => ['Gestionar Despacho', 'assets/img/package-check.svg', '?pagina=despacho'],
    'Marcas' => ['Gestionar Marcas', 'assets/img/package-search.svg', '?pagina=marca'],
    'Modelos' => ['Gestionar Modelos', 'assets/img/package-search.svg', '?pagina=modelo'],
    'Productos' => ['Gestionar Productos', 'assets/img/package-search.svg', '?pagina=producto'],
    'Categorias' => ['Gestionar Categorias', 'assets/img/package-search.svg', '?pagina=categoria'],
    'Compra Física' => ['Gestionar Ventas Presenciales', 'assets/img/files.svg', '?pagina=comprafisica'],
    'Proveedores' => ['Gestionar Proveedores', 'assets/img/truck.svg', '?pagina=proveedor'],
    'Clientes' => ['Gestionar Clientes', 'assets/img/users-round.svg', '?pagina=cliente'],
    'Catalogo' => ['Gestionar Catálogo', 'assets/img/book-open.svg', '?pagina=catalogo'],
    'pasarela' => ['Gestionar Pagos', 'assets/img/credit-card.svg', '?pagina=pasarela'],
    'Pedidos' => ['Gestionar Pedidos', 'assets/img/receipt-text.svg', '?pagina=gestionarfactura'],
    'Ordenes de despacho' => ['Gestionar Ordenes de Despacho', 'assets/img/list-ordered.svg', '?pagina=ordendespacho'],
    'Cuentas bancarias' => ['Gestionar Cuentas Bancarias', 'assets/img/landmark.svg', '?pagina=cuenta'],
    'Finanzas' => ['Gestionar Ingresos y Egresos', 'assets/img/dollar-sign.svg', '?pagina=finanza'],
    'permisos' => ['Gestionar Permisos', 'assets/img/key-round.svg', '?pagina=permiso'],
    'Roles' => ['Gestionar Roles', 'assets/img/user-round-search.svg', '?pagina=rol'],
    'bitacora' => ['Gestionar Bitácora', 'assets/img/notebook.svg', '?pagina=bitacora'],
    'Backup' => ['Gestionar Backup', 'assets/img/files.svg', '?pagina=backup'],    
];

$permisosConsulta = [];
foreach ($modulos as $moduloBD => $info) {
    $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
}

if ($nombre_rol === 'SuperUsuario') {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = true;
    }
} else {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
    }
}

$bd_seguridad = new BD('S');
$pdo_seguridad = $bd_seguridad->getConexion();

$bd_casalai = new BD('C');
$pdo_casalai = $bd_casalai->getConexion();

// Consulta de notificaciones
$query = "SELECT * FROM tbl_notificaciones 
          WHERE id_usuario = :id_usuario AND leido = 0
          ORDER BY fecha_hora DESC LIMIT 5";
$stmt = $pdo_seguridad->prepare($query);
$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notificaciones as &$notif) {
    if ($notif['tipo'] == 'pago' && $notif['id_referencia']) {
        $query_pago = "SELECT * FROM tbl_detalles_pago WHERE id_detalles = ?";
        $stmt_pago = $pdo_casalai->prepare($query_pago);
        $stmt_pago->execute([$notif['id_referencia']]);
        $notif['detalle_pago'] = $stmt_pago->fetch(PDO::FETCH_ASSOC);
    }
}
unset($notif);

$notificaciones_count = count($notificaciones);

// Obtener cantidad de productos en el carrito
$carrito_count = 0;
if (isset($_SESSION['id_usuario'])) {
    
    $carritoObj = new Carrito();
    $carritoCliente = $carritoObj->obtenerCarritoPorCliente($_SESSION['id_usuario']);
    if ($carritoCliente) {
        $productosCarrito = $carritoObj->obtenerProductosDelCarrito($carritoCliente['id_carrito']);
        $carrito_count = count($productosCarrito);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Casa Lai</title>
    <link rel="stylesheet" href="assets/styles/new_menu.css">
    
    <!-- jQuery (DEBE ir antes de Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar">
        <!-- Sección izquierda con hamburguesa, logo y nombre -->
        <div class="nav-left">
            <!-- Ícono de menú hamburguesa -->
            <div class="hamburger-menu">
                <button class="icon-btn" id="hamburger-btn">
                    <IMG src="assets/img/menu.svg" alt="Menú" class="local-icon">
                </button>
            </div>
            
            <!-- Logo y nombre de la empresa (clickeable para ir al dashboard) -->
            <div class="logo-container" onclick="window.location.href='?pagina=<?= $nombre_rol === 'Cliente' ? 'catalogo' : 'dashboard' ?>'">
                <div class="logo">
                    <IMG src="assets/img/LOGO.png" alt="Logo Casa Lai" height="40">
                </div>
                <div class="company-name">
                    CasaLai C.A
                </div>
            </div>
        </div>
        
        <div class="nav-icons">
            <!-- Botón de tasa de cambio -->
            <button class="icon-btn" id="tasa-cambio-btn">
                <IMG src="assets/img/currency-exchange.svg" alt="Tasa de Cambio" class="local-icon">
            </button>

            <!-- Botón de carrito -->
            <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === 'Cliente'): ?>
                <button class="icon-btn" id="cart-btn">
                    <IMG src="assets/img/shopping-cart2.svg" alt="Carrito" class="local-icon">
                    <?php if (isset($carrito_count) && $carrito_count > 0): ?>
                        <span class="cart-count-badge"><?php echo $carrito_count; ?></span>
                    <?php endif; ?>
                </button>
            <?php endif; ?>

            <!-- Botón de notificaciones -->
            <button class="icon-btn" id="notifications-btn">
                <IMG src="assets/img/bell.svg" alt="Notificaciones" class="local-icon">
                <?php if (isset($notificaciones_count) && $notificaciones_count > 0): ?>
                    <span class="notification-badge"><?php echo $notificaciones_count; ?></span>
                <?php endif; ?>
            </button>

            <!-- Botón de ayuda -->
            <button class="icon-btn">
                <a href="assets/public/casalai-manual/index-new.php" target="_blank">
                    <IMG src="assets/img/info.svg" alt="Ayuda" class="local-icon">
                </a>
            </button>
            
            <!-- Botón de perfil -->
            <button class="icon-btn" id="profile-btn">
<?php  
$inicial = substr($_SESSION['name'] ?? 'U', 0, 1);

// Validar si hay una imagen real en sesión
if (!empty($_SESSION['foto_perfil'])) { 
?>
    <div class="user-avatar">
        <img src="assets/img/uploads/<?php echo $_SESSION['foto_perfil']; ?>" alt="User Avatar">
    </div>
<?php 
} else { 
?>
    <div class="user-avatar">
        <?php echo $inicial; ?>
    </div>
<?php 
} 
?>

                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Usuario'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol'); ?></div>
                </div>
            </button>
        </div>
    </nav>

    <!-- Menú lateral (se activa con el ícono de hamburguesa) -->
    <div class="side-menu" id="side-menu">
        <div class="side-menu-header">
            <h3>Menú Principal</h3>
            <button class="close-btn" id="close-menu">
                <IMG src="assets/img/x.svg" alt="Cerrar" class="local-icon">
            </button>
        </div>
        
        <div class="menu-options">
            <!-- Administrar Perfiles -->
            <?php if (!empty($permisosConsulta['Usuario']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="profiles">
                <span><IMG src="assets/img/users-round.svg" alt="Perfiles" class="menu-icon"> Administrar Perfiles</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="profiles">
                <div class="sub-option" onclick="window.location.href='?pagina=usuario'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Usuario
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteUsuario'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Perfiles
                </div>
            </div>
            <?php endif; ?>

            <!-- Administrar Inventario -->
            <?php if (!empty($permisosConsulta['Recepcion']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="inventory">
                <span><IMG src="assets/img/package-open.svg" alt="Inventario" class="menu-icon"> Administrar Inventario</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="inventory">
                <div class="sub-option" onclick="window.location.href='?pagina=recepcion'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Recepción
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteInventario'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Inventario
                </div>
            </div>
            <?php endif; ?>

            <!-- Administrar Productos -->
            <?php if (($nombre_rol !== 'Cliente') && (!empty($permisosConsulta['Marcas']) || !empty($permisosConsulta['Modelos']) || !empty($permisosConsulta['Productos']) || !empty($permisosConsulta['Categorias']))): ?>
            <div class="menu-option" data-target="products">
                <span><IMG src="assets/img/package-search.svg" alt="Productos" class="menu-icon"> Administrar Productos</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="products">
                <?php if (!empty($permisosConsulta['Marcas'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=marca'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Marcas
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Modelos'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=modelo'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Modelos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Productos'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=producto'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Productos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Categorias'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=categoria'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Categorías
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Marcas']) || !empty($permisosConsulta['Modelos']) || !empty($permisosConsulta['Productos']) || !empty($permisosConsulta['Categorias'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteProductos'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Productos
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Administrar Proveedores -->
            <?php if (!empty($permisosConsulta['Proveedores']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="providers">
                <span><IMG src="assets/img/truck.svg" alt="Proveedores" class="menu-icon"> Administrar Proveedores</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="providers">
                <div class="sub-option" onclick="window.location.href='?pagina=proveedor'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Proveedores
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteProveedores'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Proveedores
                </div>
            </div>
            <?php endif; ?>

            <!-- Administrar Clientes -->
            <?php if (!empty($permisosConsulta['Clientes']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="clients">
                <span><IMG src="assets/img/users-round.svg" alt="Clientes" class="menu-icon"> Administrar Clientes</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="clients">
                <div class="sub-option" onclick="window.location.href='?pagina=cliente'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Clientes
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteCliente'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Clientes
                </div>
            </div>
            
                
          
            <?php endif; ?>

            <!-- Ventas/Compras -->
            <?php if (!empty($permisosConsulta['Catalogo']) || !empty($permisosConsulta['Compra Física']) || !empty($permisosConsulta['pasarela']) || !empty($permisosConsulta['Prefactura']) || !empty($permisosConsulta['Ordenes de despacho']) || !empty($permisosConsulta['Despacho'])): ?>
            <div class="menu-option" data-target="sales">
                <?php if ($nombre_rol === 'Cliente'): ?>
                    <span><IMG src="assets/img/shopping-cart.svg" alt="Compras" class="menu-icon"> Compras</span>
                <?php else: ?>
                    <span><IMG src="assets/img/shopping-cart.svg" alt="Ventas" class="menu-icon"> Administrar Ventas</span>
                <?php endif; ?>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="sales">
                <?php if (!empty($permisosConsulta['Catalogo'])): ?>
                    <?php if ($nombre_rol === 'Cliente'): ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=catalogo'">
                            <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Catálogo
                        </div>
                    <?php else: ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=catalogo'">
                            <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Catálogo
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Compra Física']) && $nombre_rol !== 'Cliente'): ?>
                    <div class="sub-option" onclick="window.location.href='?pagina=comprafisica'">
                        <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Ventas Presenciales
                    </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Pedidos']) && $nombre_rol !== 'Cliente'): ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=gestionarfactura'">
                            <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Pedidos
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($permisosConsulta['Pedidos']) && $nombre_rol == 'Cliente'): ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=gestionarfactura'">
                            <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Mis Pedidos
                        </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['pasarela'])): ?>
                    <?php if ($nombre_rol === 'Cliente'): ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=pasarela'">
                            <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Mis Pagos
                        </div>
                    <?php else: ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=pasarela'">
                            <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Pagos
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Ordenes de despacho']) && $nombre_rol !== 'Cliente'): ?>
                    <div class="sub-option" onclick="window.location.href='?pagina=ordendespacho'">
                        <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Ordenes de Despacho
                    </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Despacho']) && $nombre_rol !== 'Cliente'): ?>
                    <div class="sub-option" onclick="window.location.href='?pagina=despacho'">
                        <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Despacho
                    </div>
                    <div class="sub-option" onclick="window.location.href='?pagina=reporteVentas'">
                        <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Despachos
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Administrar Finanzas -->
            <?php if (!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'] && $nombre_rol !== 'Cliente')): ?>
            <div class="menu-option" data-target="finances">
                <span><IMG src="assets/img/dollar-sign.svg" alt="Finanzas" class="menu-icon"> Administrar Finanzas</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="finances">
                <?php if (!empty($permisosConsulta['Cuentas bancarias'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=cuenta'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Cuentas Bancarias
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Finanzas'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=finanza'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Ingresos y Egresos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteFinanzas'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Reportes de Finanzas
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Administrar Seguridad -->
            <?php if (!empty($permisosConsulta['permisos']) || !empty($permisosConsulta['Roles']) || !empty($permisosConsulta['bitacora']) || !empty($permisosConsulta['Backup'])): ?>
            <div class="menu-option" data-target="security">
                <span><IMG src="assets/img/key-round.svg" alt="Seguridad" class="menu-icon"> Administrar Seguridad</span>
                <IMG src="assets/img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="security">
                <?php if (!empty($permisosConsulta['permisos'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=permiso'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Permisos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Roles'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=rol'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Roles
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['bitacora'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=bitacora'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Bitácora
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Backup'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=backup'">
                    <IMG src="assets/img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Backup
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="tasa-cambio-panel" id="tasa-cambio-panel">
        <h2>Tipo de Cambio <IMG src="assets/img/currency-exchange.svg" alt="Tasa" class="local-icon" style="width: 20px; height: 20px;"></h2>
        <div class="tasa-info">
            <div class="tasa-valor">
                <strong>1 USD = <?= $tasaBCVFormateada ?> BS</strong>
            </div>
            <div class="tasa-actualizacion">
                <small>Actualizado: <?= date("d/m/Y g:i A", strtotime(str_replace('/', '-', $tasaFechaFormateada))) ?></small>
            </div>
            <div class="tasa-fuente">
                <small>Fuente: Banco Central de Venezuela</small>
            </div>
        </div>
    </div>

    <!-- Panel de Notificaciones -->
    <div class="notificacion-panel" id="notifications-panel">
        <h2>Notificaciones <a href="?pagina=notificacion" class="small">Ver más</a> <span class="notification-count"><?php echo $notificaciones_count; ?></span></h2>
        <div id="notifications-list">
            <?php if (isset($notificaciones_count) && $notificaciones_count > 0): ?>
                <?php foreach ($notificaciones as $notif): 
                    $estado = $notif['estado'] ?? '';
                    $leido = $notif['leido'] ?? '0';
                    $estaLeida = ($leido == '1' || $leido == 1 || $estado == 'leido' || $estado == 'leída');
                    $claseNotificacion = $estaLeida ? 'notificacion-leida' : 'notificacion-no-leida';
                ?>
                    <div class="item-notificacion <?= $claseNotificacion ?>" data-id="<?= $notif['id_notificacion'] ?>">
                        <div class="texto">
                            <h4><?= htmlspecialchars($notif['titulo']) ?></h4>
                            <p><?= htmlspecialchars($notif['mensaje']) ?></p>
                            <?php if ($notif['tipo'] == 'pago' && !empty($notif['detalle_pago'])): ?>
                                <small>Referencia: <?= htmlspecialchars($notif['detalle_pago']['referencia']) ?></small>
                            <?php endif; ?>
                            <small class="fecha-notificacion"><?= date('d/m/Y H:i:s', strtotime($notif['fecha_creacion'] ?? $notif['fecha_hora'])) ?></small>
                        </div>
                        <button class="marcar-leido" data-id="<?= $notif['id_notificacion'] ?>">
                            <IMG src="assets/img/check.svg" alt="Marcar leído" class="local-icon">
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="item-notificacion">
                    <div class="texto">
                        <p>No hay notificaciones recientes</p>
                    </div>
                    <!-- No mostrar botón de marcar leído cuando no hay notificaciones -->
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel de Perfil -->
    <div class="profile-panel" id="profile-panel">
        <h2>Mi Cuenta</h2>
        <div class="profile-info">
            <?php  
            $inicial = substr($_SESSION['name'] ?? 'U', 0, 1);

            // Validar si hay una imagen real en sesión
            if (!empty($_SESSION['foto_perfil'])) { 
            ?>
                <div class="user-avatar">
                    <img src="assets/img/uploads/<?php echo $_SESSION['foto_perfil']; ?>" alt="User Avatar">
                </div>
            <?php 
            } else { 
            ?>
                <div class="user-avatar">
                    <?php echo $inicial; ?>
                </div>
            <?php 
            } 
            ?><div class="profile-details">
                <div class="profile-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Usuario'); ?></div>
                <div class="profile-role"><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol'); ?></div>
            </div>
        </div>
        <div class="profile-options">
            <a href="?pagina=perfil" class="profile-option">
                <IMG src="assets/img/user.svg" alt="Perfil" class="local-icon">
                Mi Perfil
            </a>
            <a href="#" class="profile-option session-out" onclick="confirmarCerrarSesion(); return false;">
                <IMG src="assets/img/log-out.svg" alt="Cerrar Sesión" class="local-icon">
                Cerrar Sesión
            </a>
        </div>
    </div>


    <!-- Bootstrap JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script src="assets/javascript/http-long-poll-client.js"></script>
    <script>
    // Función para confirmar cierre de sesión con SweetAlert
    function confirmarCerrarSesion() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "¿Está seguro que desea cerrar sesión?",
            icon: 'question',
            iconColor: '#d8d508ff',
            showCancelButton: true,
            confirmButtonColor: '#0863b8',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?pagina=cerrar_sesion';
            }
        });
    }
            
    // JavaScript para el menú lateral
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos del menú lateral
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sideMenu = document.getElementById('side-menu');
        
        const closeMenuBtn = document.getElementById('close-menu');
        const overlay = document.getElementById('overlay');
        
        // Elementos de las opciones del menú
        const menuOptions = document.querySelectorAll('.menu-option[data-target]');
        const tasaCambioBtn = document.getElementById('tasa-cambio-btn');
        const tasaCambioPanel = document.getElementById('tasa-cambio-panel');
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsPanel = document.getElementById('notifications-panel');
        const cartBtn = document.getElementById('cart-btn');
        const profileBtn = document.getElementById('profile-btn');
        const profilePanel = document.getElementById('profile-panel');
        
        // Verificar que todos los elementos existan antes de agregar event listeners
        if (hamburgerBtn && sideMenu) {
            // Alternar menú lateral
            hamburgerBtn.addEventListener('click', function() {
                sideMenu.classList.add('active');
                if (overlay) overlay.classList.add('active');
                // Cerrar otros paneles
                closeAllPanels();
            });
        }
        
        if (closeMenuBtn) {
            // Cerrar menú lateral
            closeMenuBtn.addEventListener('click', closeSideMenu);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeSideMenu);
        }
        
        function closeSideMenu() {
            if (sideMenu) sideMenu.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
        
        // Función para cerrar todos los paneles
        function closeAllPanels() {
            if (tasaCambioPanel) tasaCambioPanel.classList.remove('active');
            if (notificationsPanel) notificationsPanel.classList.remove('active');
            if (profilePanel) profilePanel.classList.remove('active');
        }
        
        // Alternar submenús en el menú lateral (solo para opciones con data-target)
        if (menuOptions.length > 0) {
            menuOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const targetId = this.getAttribute('data-target');
                    if (!targetId) return;
                    
                    const subOptions = document.getElementById(targetId);
                    if (!subOptions) return;
                    
                    // Cerrar otros submenús abiertos
                    document.querySelectorAll('.sub-options').forEach(sub => {
                        if (sub.id !== targetId && sub.classList.contains('active')) {
                            sub.classList.remove('active');
                            if (sub.previousElementSibling && sub.previousElementSibling.classList.contains('menu-option')) {
                                sub.previousElementSibling.classList.remove('active');
                            }
                        }
                    });
                    
                    // Alternar el submenú actual
                    subOptions.classList.toggle('active');
                    this.classList.toggle('active');
                });
            });
        }
        
        // Alternar panel de tasa de cambio
        if (tasaCambioBtn && tasaCambioPanel) {
            tasaCambioBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                tasaCambioPanel.classList.toggle('active');
                // Cerrar otros paneles
                if (notificationsPanel) notificationsPanel.classList.remove('active');
                if (profilePanel) profilePanel.classList.remove('active');
            });
            
            // Cerrar panel de tasa de cambio al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (tasaCambioBtn && !tasaCambioBtn.contains(e.target) && 
                    tasaCambioPanel && !tasaCambioPanel.contains(e.target)) {
                    tasaCambioPanel.classList.remove('active');
                }
            });
        }
        
        // Alternar panel de notificaciones
        if (notificationsBtn && notificationsPanel) {
            notificationsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationsPanel.classList.toggle('active');
                // Cerrar otros paneles
                if (tasaCambioPanel) tasaCambioPanel.classList.remove('active');
                if (profilePanel) profilePanel.classList.remove('active');
            });
            
            // Cerrar panel de notificaciones al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (notificationsBtn && !notificationsBtn.contains(e.target) && 
                    notificationsPanel && !notificationsPanel.contains(e.target)) {
                    notificationsPanel.classList.remove('active');
                }
            });
        }
        
        // Redireccionar al carrito
        if (cartBtn) {
            cartBtn.addEventListener('click', function() {
                window.location.href = '?pagina=carrito';
            });
        }
        
        // Alternar panel de perfil
        if (profileBtn && profilePanel) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profilePanel.classList.toggle('active');
                // Cerrar otros paneles
                if (tasaCambioPanel) tasaCambioPanel.classList.remove('active');
                if (notificationsPanel) notificationsPanel.classList.remove('active');
            });
            
            // Cerrar panel de perfil al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (profileBtn && !profileBtn.contains(e.target) && 
                    profilePanel && !profilePanel.contains(e.target)) {
                    profilePanel.classList.remove('active');
                }
            });
        }
        
        // Manejar marcado de notificaciones como leídas (delegación de eventos)
        if (notificationsPanel) {
            notificationsPanel.addEventListener('click', function(e) {
                const button = e.target.closest('.marcar-leido');
                if (!button) return;
                e.stopPropagation();

                const idNotificacion = button.getAttribute('data-id');
                if (!idNotificacion) return;

                console.log('Click marcar-leido. idNotificacion =', idNotificacion, 'Long polling disponible =', !!window.notificacionesWS);

                if (window.notificacionesWS) {
                    window.notificacionesWS.marcarComoLeida(idNotificacion);
                } else {
                    console.error('Long polling de notificaciones no está disponible');
                }
            });
        }
        
        // Inicializar long polling para notificaciones y actualizaciones auxiliares
        if (window.notificacionesWS) {
            // Esperar a que el DOM esté completamente cargado antes de configurar intervalos
            const configurarIntervalos = () => {
                // El long polling entrega notificaciones; solo se sondean datos auxiliares aquí.
                setInterval(() => {
                    if (window.notificacionesWS) {
                        window.notificacionesWS.solicitarCarritoCount();
                    }
                }, 30000);
                
                setInterval(() => {
                    if (window.notificacionesWS) {
                        window.notificacionesWS.solicitarTasaCambio();
                    }
                }, 300000);
                
                // Solicitar datos iniciales después de un pequeño retraso
                setTimeout(() => {
                    if (window.notificacionesWS) {
                        window.notificacionesWS.solicitarTasaCambio();
                        window.notificacionesWS.solicitarCarritoCount();
                    }
                }, 2000);
            };
            
            // Si el DOM ya está cargado, configurar inmediatamente
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', configurarIntervalos);
            } else {
                configurarIntervalos();
            }
        }
    });

    // Estas funciones se mantienen por compatibilidad, pero ya no se usan directamente
    function actualizarTasaCambio() {
        if (window.notificacionesWS) {
            window.notificacionesWS.solicitarTasaCambio();
        }
    }

    function actualizarCarritoCount() {
        if (window.notificacionesWS) {
            window.notificacionesWS.solicitarCarritoCount();
        }
    }

    class NotificacionesLongPoll {
        constructor(usuarioId) {
            this.usuarioId = usuarioId;
            this.client = new CasalaiLongPoll.Client({
                endpoint: '?pagina=long_poll_notificaciones',
                credentials: 'same-origin',
                onMessage: (data) => this.manejarMensaje(data),
                onError: (error) => console.error('Error en long polling:', error.message)
            });
            this.conectar();
        }

        conectar() {
            return this.client.start();
        }

        reconectar() {
            return this.client.restart();
        }

        enviar(mensaje) {
            if (mensaje.tipo === 'obtener_tasa_cambio') {
                return this.client.getExchangeRate().catch((error) => console.error(error.message));
            }
            if (mensaje.tipo === 'obtener_carrito_count') {
                return this.client.getCartCount().catch((error) => console.error(error.message));
            }
            if (mensaje.tipo === 'marcar_leida') {
                return this.marcarComoLeida(mensaje.id_notificacion);
            }
        }

        manejarMensaje(data) {
            // Manejar diferentes tipos de mensajes recibidos
            switch(data.tipo) {
                case 'sync_inicial':
                    this.renderizarListaNotificaciones(data.notificaciones || []);
                    break;
                case 'nueva_notificacion':
                    this.renderizarListaNotificaciones(data.notificaciones || []);
                    break;
                case 'marcar_leida':
                    if (data.ok) {
                        this.actualizarNotificacionUI(data.id_notificacion);
                    } else {
                        console.error('Error al marcar notificación como leída:', data.error || data);
                    }
                    break;
                case 'actualizar_tasa_cambio':
                    if (data.error) {
                        console.error(data.error);
                    } else {
                        this.actualizarTasaCambioUI(data);
                    }
                    break;
                case 'actualizar_carrito_count':
                    if (data.error) {
                        console.error(data.error);
                    } else {
                        this.actualizarCarritoCountUI(data.count);
                    }
                    break;
                // Agregar más casos según sea necesario
            }
        }

        mostrarNotificacion(notificacion) {
            // Método mantenido por compatibilidad; no se usa directamente en la
            // nueva versión basada en sync_inicial / nueva_notificacion.
            console.log('Nueva notificación (no usada directamente):', notificacion);
            this.actualizarContadorNotificaciones();
        }

        renderizarListaNotificaciones(notificaciones) {
            const notificationsList = document.getElementById('notifications-list');
            const countElement = document.querySelector('.notification-badge');
            const titleCountElement = document.querySelector('.notificacion-panel .notification-count');

            if (!notificationsList) {
                return;
            }

            let html = '';

            if (Array.isArray(notificaciones) && notificaciones.length > 0) {
                notificaciones.forEach(notif => {
                    const fecha = notif.fecha_formateada || notif.fecha_hora || '';
                    html += `
                        <div class="item-notificacion notificacion-no-leida" data-id="${notif.id_notificacion}">
                            <div class="texto">
                                <h4>${notif.titulo}</h4>
                                <p>${notif.mensaje}</p>
                                ${notif.referencia ? `<small>Referencia: ${notif.referencia}</small>` : ""}
                                <small>${fecha}</small>
                            </div>
                            <button class="marcar-leido" data-id="${notif.id_notificacion}">
                                <IMG src="assets/img/check.svg" alt="Marcar leído" class="local-icon">
                            </button>
                        </div>
                    `;
                });
            } else {
                html = `
                    <div class="item-notificacion">
                        <div class="texto"><p>No hay notificaciones recientes</p></div>
                    </div>
                `;
            }

            notificationsList.innerHTML = html;

            const newCount = Array.isArray(notificaciones) ? notificaciones.length : 0;

            if (countElement) {
                if (newCount > 0) {
                    countElement.textContent = newCount;
                    countElement.style.display = 'block';
                } else {
                    countElement.textContent = '';
                    countElement.style.display = 'none';
                }
            }

            if (titleCountElement) {
                titleCountElement.textContent = newCount;
            }
        }

        marcarComoLeida(idNotificacion) {
            return this.client.markAsRead(idNotificacion)
                .catch((error) => console.error('No se pudo marcar la notificación como leída:', error.message));
        }

        actualizarNotificacionUI(idNotificacion) {
            // Actualizar la UI para marcar la notificación como leída
            const notificacionItem = document.querySelector(`.item-notificacion[data-id="${idNotificacion}"]`);
            if (!notificacionItem) {
                return;
            }

            // Eliminar la notificación del DOM
            notificacionItem.remove();

            // Actualizar contadores (campana y número en el header del panel)
            const countElement = document.querySelector('.notification-badge');
            const titleCountElement = document.querySelector('.notificacion-panel .notification-count');

            let newCount = 0;
            if (countElement && countElement.textContent) {
                newCount = Math.max(0, parseInt(countElement.textContent) - 1);
                countElement.textContent = newCount;
                if (newCount <= 0) {
                    countElement.style.display = 'none';
                }
            }

            if (titleCountElement) {
                // Si no pudimos leer del badge, usamos el número actual de items
                if (!newCount) {
                    newCount = document.querySelectorAll('.notificacion-panel .item-notificacion').length;
                }
                titleCountElement.textContent = newCount;
            }

            // Si ya no quedan notificaciones, mostrar mensaje vacío en la lista
            const notificationsList = document.getElementById('notifications-list');
            if (notificationsList && notificationsList.querySelectorAll('.item-notificacion').length === 0) {
                notificationsList.innerHTML = `
                    <div class="item-notificacion">
                        <div class="texto">
                            <p>No hay notificaciones recientes</p>
                        </div>
                    </div>
                `;
            }
        }

        actualizarContadorNotificaciones() {
            // Actualizar el contador de notificaciones no leídas
            const contadorCampana = document.querySelector('.notification-badge');
            const contadorPanel = document.querySelector('.notificacion-panel .notification-count');
            let actual = 0;

            if (contadorCampana && contadorCampana.textContent) {
                actual = parseInt(contadorCampana.textContent) || 0;
                contadorCampana.textContent = actual + 1;
                contadorCampana.style.display = 'block';
            }

            if (contadorPanel) {
                const valorActual = parseInt(contadorPanel.textContent) || 0;
                contadorPanel.textContent = valorActual + 1;
            }
        }

        // Nuevos métodos para manejar la tasa de cambio y el contador del carrito
        actualizarTasaCambioUI(data) {
            const tasaPanel = document.getElementById('tasa-cambio-panel');
            if (tasaPanel) {
                let html = `<h2>Tipo de Cambio <i class="bi bi-currency-exchange"></i></h2>`;
                
                if (data.tasa) {
                    html += `
                        <div class="tasa-info">
                            <div class="tasa-valor">
                                <strong>1 USD = ${data.tasa} BS</strong>
                            </div>
                            <div class="tasa-actualizacion">
                                <small>Actualizado: ${data.actualizado}</small>
                            </div>
                            <div class="tasa-fuente">
                                <small>Fuente: Banco Central de Venezuela</small>
                            </div>
                        </div>
                    `;
                }
                
                tasaPanel.innerHTML = html;
            }
        }

        actualizarCarritoCountUI(count) {
            const cartBadge = document.querySelector('.cart-count-badge');
            const cartBtn = document.getElementById('cart-btn');
            
            if (count > 0) {
                if (cartBadge) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'flex';
                } else if (cartBtn) {
                    // Crear badge si no existe
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cart-count-badge';
                    newBadge.textContent = count;
                    cartBtn.appendChild(newBadge);
                }
            } else if (cartBadge) {
                cartBadge.style.display = 'none';
            }
        }

        // Métodos para solicitar actualizaciones
        solicitarTasaCambio() {
            this.enviar({ tipo: 'obtener_tasa_cambio' });
        }

        solicitarCarritoCount() {
            this.enviar({ tipo: 'obtener_carrito_count' });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const usuarioId = '<?php echo isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : ''; ?>';
        
        if (usuarioId && usuarioId !== '0') {
            window.notificacionesWS = new NotificacionesLongPoll(usuarioId);
        }
    });

    </script>
</body>
</html>