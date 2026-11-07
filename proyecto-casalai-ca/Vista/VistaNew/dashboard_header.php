<?php
// Archivo: dashboard_header.php - Header del dashboard reutilizable
$id_usuario_header = (int) ($_SESSION['id_usuario'] ?? 0);

$dolarService = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService();
$registroDolar = $dolarService->obtenerRegistroDelDia();
$tasaBCV = isset($registroDolar['precio'])
    ? (float) $registroDolar['precio']
    : $dolarService->obtenerPrecioDelDia();
$tasaBCVFormateada = number_format($tasaBCV, 2);
$tasaFecha = $registroDolar['fecha'] ?? date('Y-m-d H:i:s');
$tasaFechaFormateada = date('d/m/Y H:i', strtotime($tasaFecha));

$notificaciones = [];
$notificaciones_count = 0;

if ($id_usuario_header > 0) {
    $bd_seguridad = new \Usuario\ProyectoCasalaiCa\Config\BD('S');
    try {
        $pdo_seguridad = $bd_seguridad->getConexion();
        $query = "SELECT * FROM tbl_notificaciones
                  WHERE id_usuario = :id_usuario AND leido = 0
                  ORDER BY fecha_hora DESC LIMIT 5";
        $stmt = $pdo_seguridad->prepare($query);
        $stmt->bindValue(':id_usuario', $id_usuario_header, PDO::PARAM_INT);
        $stmt->execute();
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } finally {
        $bd_seguridad->cerrar();
    }

    $bd_casalai = null;
    $stmt_pago = null;
    foreach ($notificaciones as &$notificacion) {
        if ($notificacion['tipo'] !== 'pago' || empty($notificacion['id_referencia'])) {
            continue;
        }

        if ($bd_casalai === null) {
            $bd_casalai = new \Usuario\ProyectoCasalaiCa\Config\BD('C');
            $stmt_pago = $bd_casalai->getConexion()->prepare(
                'SELECT referencia FROM tbl_detalles_pago WHERE id_detalles = ?'
            );
        }

        $stmt_pago->execute([$notificacion['id_referencia']]);
        $notificacion['detalle_pago'] = $stmt_pago->fetch(PDO::FETCH_ASSOC);
    }
    unset($notificacion);
    if ($bd_casalai !== null) {
        $bd_casalai->cerrar();
    }

    $notificaciones_count = count($notificaciones);
}
?>
<!-- Header -->

<link rel="stylesheet" href="assets/styles/header.css">

<header class="main-header">
    <div class="header-left">
        <h1><?php echo $titulo_pagina ?? 'Panel Principal'; ?></h1>
    </div>
    <div class="header-right">
        <!-- Botón de tasa de cambio -->
        <button class="btn-icon" id="tasa-cambio-btn">
            <img src="assets/img/currency-exchange.svg" alt="Tasa de Cambio" class="local-icon">
        </button>

        <!-- Botón de carrito -->
        <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === 'Cliente'): ?>
            <button class="btn-icon" id="cart-btn">
                <img src="assets/img/shopping-cart2.svg" alt="Carrito" class="local-icon">
                <?php if (isset($carrito_count) && $carrito_count > 0): ?>
                    <span class="cart-count-badge"><?php echo $carrito_count; ?></span>
                <?php endif; ?>
            </button>
        <?php endif; ?>

        <!-- Botón de notificaciones -->
        <button class="btn-icon" id="notifications-btn">
            <img src="assets/img/bell.svg" alt="Notificaciones" class="local-icon">
            <?php if (isset($notificaciones_count) && $notificaciones_count > 0): ?>
                <span class="notification-badge"><?php echo $notificaciones_count; ?></span>
            <?php endif; ?>
        </button>

        <!-- Botón de ayuda -->
        <button class="btn-icon">
            <a href="assets/public/casalai-manual/index-new.php" target="_blank">
                <img src="assets/img/info.svg" alt="Ayuda" class="local-icon">
            </a>
        </button>

        <!-- Botón de perfil -->
        <button class="user-profile" id="profile-btn">
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
</header>

<div class="tasa-cambio-panel header-popover" id="tasa-cambio-panel">
    <h2>
        Tipo de Cambio
        <img src="assets/img/currency-exchange.svg" alt="" class="local-icon">
    </h2>
    <div class="tasa-info">
        <div class="tasa-valor">
            <strong>1 USD = <?php echo htmlspecialchars($tasaBCVFormateada); ?> BS</strong>
        </div>
        <div class="tasa-actualizacion">
            <small>Actualizado: <?php echo htmlspecialchars($tasaFechaFormateada); ?></small>
        </div>
        <div class="tasa-fuente">
            <small>Fuente: Banco Central de Venezuela</small>
        </div>
    </div>
</div>

<div class="notificacion-panel header-popover" id="notifications-panel">
    <h2>
        Notificaciones
        <a href="?pagina=notificacion" class="small">Ver más</a>
        <span class="notification-count"><?php echo $notificaciones_count; ?></span>
    </h2>
    <div id="notifications-list">
        <?php if ($notificaciones_count > 0): ?>
            <?php foreach ($notificaciones as $notificacion): ?>
                <?php
                $fecha_notificacion = $notificacion['fecha_creacion'] ?? $notificacion['fecha_hora'] ?? '';
                $timestamp_notificacion = $fecha_notificacion !== '' ? strtotime($fecha_notificacion) : false;
                ?>
                <div class="item-notificacion notificacion-no-leida"
                    data-id="<?php echo (int) $notificacion['id_notificacion']; ?>">
                    <div class="texto">
                        <h4><?php echo htmlspecialchars($notificacion['titulo'] ?? ''); ?></h4>
                        <p><?php echo htmlspecialchars($notificacion['mensaje'] ?? ''); ?></p>
                        <?php if (!empty($notificacion['detalle_pago']['referencia'])): ?>
                            <small>Referencia: <?php echo htmlspecialchars($notificacion['detalle_pago']['referencia']); ?></small>
                        <?php endif; ?>
                        <?php if ($timestamp_notificacion !== false): ?>
                            <small class="fecha-notificacion">
                                <?php echo date('d/m/Y H:i:s', $timestamp_notificacion); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="marcar-leido"
                        data-id="<?php echo (int) $notificacion['id_notificacion']; ?>"
                        aria-label="Marcar notificación como leída"
                        title="Marcar como leída">
                        <img src="assets/img/check.svg" alt="" class="local-icon">
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="item-notificacion">
                <div class="texto">
                    <p>No hay notificaciones recientes</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Panel de Perfil -->
<div class="profile-panel header-popover" id="profile-panel">
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
            <img src="assets/img/user.svg" alt="Perfil" class="local-icon icon-profile">
            Mi Perfil
        </a>
        <a href="#" class="profile-option session-out" onclick="confirmarCerrarSesion(); return false;">
            <img src="assets/img/log-out.svg" alt="Cerrar Sesión" class="local-icon">
            Cerrar Sesión
        </a>
    </div>
</div>

<link rel="stylesheet" href="assets/styles/tablas_section_styles.css">
<link rel="stylesheet" href="assets/styles/modal-registrar.css">
<link rel="stylesheet" href="assets/styles/modal-detallar.css">
<link rel="stylesheet" href="assets/styles/modal-eliminar-anular.css">

<script src="assets/javascript/http-long-poll-client.js"></script>
<script>
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

<!--<script>
document.addEventListener('DOMContentLoaded', function () {
    const panels = [
        [document.getElementById('tasa-cambio-btn'), document.getElementById('tasa-cambio-panel')],
        [document.getElementById('notifications-btn'), document.getElementById('notifications-panel')],
        [document.getElementById('user-profile'), document.getElementById('profile-panel')]
    ];

    panels.forEach(function ([button, panel]) {
        if (!button || !panel) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const shouldOpen = !panel.classList.contains('active');
            panels.forEach(function (entry) {
                if (entry[1]) {
                    entry[1].classList.remove('active');
                }
            });
            if (shouldOpen) {
                panel.classList.add('active');
            }
        });

        panel.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', function () {
        panels.forEach(function (entry) {
            if (entry[1]) {
                entry[1].classList.remove('active');
            }
        });
    });
});
</script>-->