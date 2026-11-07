<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión (sistema tradicional)
if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

// Verificar y/o generar token JWT (sistema JWT)
require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en notificacion: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'notificacion';
$titulo_pagina = 'Notificaciones';

// Iniciar el buffer de contenido
ob_start();
?>

<?php
// Cálculos previos para summary cards
$total_notificaciones = count($notificaciones ?? []);
$notificaciones_no_leidas = count(array_filter($notificaciones ?? [], function($n) { return ($n['leida'] ?? 0) === 0; }));
$notificaciones_leidas = count(array_filter($notificaciones ?? [], function($n) { return ($n['leida'] ?? 0) === 1; }));

// Calcular porcentajes
$porcentaje_total = 100;
$porcentaje_no_leidas = $total_notificaciones > 0 ? round(($notificaciones_no_leidas / $total_notificaciones) * 100) : 0;
$porcentaje_leidas = $total_notificaciones > 0 ? round(($notificaciones_leidas / $total_notificaciones) * 100) : 0;
?>

            <!-- Summary Cards para Notificaciones -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-comments"></i></div>
                    <div class="card-content">
                        <h3>Total Notificaciones</h3>
                        <p class="card-value"><?php echo $total_notificaciones; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_total; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_total; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-envelope"></i></div>
                    <div class="card-content">
                        <h3>No Leídas</h3>
                        <p class="card-value"><?php echo $notificaciones_no_leidas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_no_leidas; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_no_leidas; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-check-double"></i></div>
                    <div class="card-content">
                        <h3>Leídas</h3>
                        <p class="card-value"><?php echo $notificaciones_leidas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_leidas; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_leidas; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-bell"></i></div>
                    <div class="card-content">
                        <h3>No Leídas</h3>
                        <p class="card-value">12</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="25, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">25%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-star"></i></div>
                    <div class="card-content">
                        <h3>Importantes</h3>
                        <p class="card-value">5</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="11, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">11%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros de Notificaciones -->
            <div class="notifications-filters">
                <div class="section-header">
                    <h2>Mis Notificaciones</h2>
                    <div class="section-actions">
                        <button class="btn-mark-read" onclick="markAllAsRead()">
                            <span class="btn-icon">✓</span>
                            Marcar todas como leídas
                        </button>
                        <button class="btn-clear-notifications" onclick="clearNotifications()">
                            <span class="btn-icon"><i class="fas fa-trash"></i></span>
                            Limpiar notificaciones
                        </button>
                    </div>
                </div>

                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterNotifications('all')">Todas</button>
                    <button class="filter-tab" onclick="filterNotifications('unread')">No leídas</button>
                    <button class="filter-tab" onclick="filterNotifications('important')">Importantes</button>
                    <button class="filter-tab" onclick="filterNotifications('system')">Sistema</button>
                </div>
            </div>

            <!-- Lista de Notificaciones -->
            <div class="notifications-list">
                <!-- Notificación Importante -->
                <div class="notification-item important unread">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="unread-dot"></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Alerta de Stock Bajo</h4>
                            <span class="notification-time">hace 5 min</span>
                        </div>
                        <p>El producto "iPhone 15 Pro Max" tiene menos de 10 unidades en stock. Se recomienda reabastecer.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('stock', 1)">Ver Producto</button>
                            <button class="btn-notification-action" onclick="dismissNotification(1)">Descartar</button>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Pedido -->
                <div class="notification-item unread">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-box"></i></span>
                        <span class="unread-dot"></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Nuevo Pedido #ORD-006</h4>
                            <span class="notification-time">hace 15 min</span>
                        </div>
                        <p>El cliente Juan Pérez ha realizado un nuevo pedido por $2,450. El pedido está pendiente de procesamiento.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('order', 2)">Ver Pedido</button>
                            <button class="btn-notification-action" onclick="dismissNotification(2)">Descartar</button>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Pago -->
                <div class="notification-item unread">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-credit-card"></i></span>
                        <span class="unread-dot"></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Pago Recibido</h4>
                            <span class="notification-time">hace 30 min</span>
                        </div>
                        <p>Se ha recibido un pago de $1,299 del cliente María González mediante transferencia bancaria.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('payment', 3)">Ver Detalles</button>
                            <button class="btn-notification-action" onclick="dismissNotification(3)">Descartar</button>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Sistema -->
                <div class="notification-item system">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-tools"></i></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Actualización del Sistema</h4>
                            <span class="notification-time">hace 1 hora</span>
                        </div>
                        <p>El sistema ha sido actualizado a la versión 2.5.0 con nuevas funcionalidades de reportes y mejoras de rendimiento.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('system', 4)">Ver Novedades</button>
                            <button class="btn-notification-action" onclick="dismissNotification(4)">Descartar</button>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Cliente -->
                <div class="notification-item">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-user"></i></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Nuevo Cliente Registrado</h4>
                            <span class="notification-time">hace 2 horas</span>
                        </div>
                        <p>El cliente Carlos Rodríguez se ha registrado en el sistema. Su información está disponible en el módulo de clientes.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('client', 5)">Ver Cliente</button>
                            <button class="btn-notification-action" onclick="dismissNotification(5)">Descartar</button>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Envío -->
                <div class="notification-item">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-truck"></i></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Pedido Enviado</h4>
                            <span class="notification-time">hace 3 horas</span>
                        </div>
                        <p>El pedido #ORD-004 ha sido enviado al cliente Ana Martínez. Se espera entrega en 2-3 días hábiles.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('shipping', 6)">Ver Pedido</button>
                            <button class="btn-notification-action" onclick="dismissNotification(6)">Descartar</button>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Reporte -->
                <div class="notification-item system">
                    <div class="notification-icon">
                        <span class="icon-container"><i class="fas fa-chart-bar"></i></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4>Reporte Generado</h4>
                            <span class="notification-time">hace 5 horas</span>
                        </div>
                        <p>El reporte mensual de ventas ha sido generado automáticamente. Está disponible en el módulo de reportes.</p>
                        <div class="notification-actions">
                            <button class="btn-notification-action" onclick="handleNotification('report', 7)">Ver Reporte</button>
                            <button class="btn-notification-action" onclick="dismissNotification(7)">Descartar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Notificaciones -->
            <div class="notification-settings">
                <h2>Configuración de Notificaciones</h2>
                <div class="settings-grid">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Notificaciones de Pedidos</h4>
                            <p>Recibir alertas cuando se creen nuevos pedidos</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Alertas de Stock</h4>
                            <p>Notificar cuando el stock esté bajo</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Notificaciones de Pagos</h4>
                            <p>Alertas sobre pagos recibidos</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Actualizaciones del Sistema</h4>
                            <p>Información sobre mantenimiento y actualizaciones</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Notificaciones por Email</h4>
                            <p>Enviar resumen diario por correo electrónico</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Notificaciones */
                .notifications-filters {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .filter-tabs {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                }

                .filter-tab {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border: none;
                    padding: 10px 20px;
                    border-radius: 20px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: all 0.2s;
                }

                .filter-tab:hover {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .filter-tab.active {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .notifications-list {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .notification-item {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    display: flex;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .notification-item:hover {
                    transform: translateX(5px);
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
                }

                .notification-item.unread {
                    border-left: 4px solid #2196F3;
                }

                .notification-item.important {
                    border-left: 4px solid #f5576c;
                }

                .notification-item.system {
                    border-left: 4px solid #6c757d;
                }

                .notification-icon {
                    position: relative;
                    flex-shrink: 0;
                }

                .icon-container {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                }

                .icon-container i {
                    color: #2196F3;
                }

                .unread-dot {
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    width: 10px;
                    height: 10px;
                    background: #f5576c;
                    border-radius: 50%;
                    border: 2px solid white;
                }

                .notification-content {
                    flex: 1;
                }

                .notification-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .notification-header h4 {
                    margin: 0;
                    color: #333;
                    font-size: 1rem;
                }

                .notification-time {
                    font-size: 0.85rem;
                    color: #666;
                }

                .notification-content p {
                    margin: 0 0 15px 0;
                    color: #555;
                    line-height: 1.5;
                }

                .notification-actions {
                    display: flex;
                    gap: 10px;
                }

                .btn-notification-action {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    transition: transform 0.2s;
                }

                .btn-notification-action:hover {
                    transform: scale(1.05);
                }

                .notification-settings {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .notification-settings h2 {
                    color: #333;
                    margin: 0 0 25px 0;
                    font-size: 1.5rem;
                }

                .settings-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                }

                .setting-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 8px;
                }

                .setting-info h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .setting-info p {
                    margin: 0;
                    font-size: 0.85rem;
                    color: #666;
                }

                .toggle-switch {
                    position: relative;
                    display: inline-block;
                    width: 50px;
                    height: 26px;
                }

                .toggle-switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .toggle-slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: 0.4s;
                    border-radius: 26px;
                }

                .toggle-slider:before {
                    position: absolute;
                    content: "";
                    height: 20px;
                    width: 20px;
                    left: 3px;
                    bottom: 3px;
                    background-color: white;
                    transition: 0.4s;
                    border-radius: 50%;
                }

                .toggle-switch input:checked + .toggle-slider {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }

                .toggle-switch input:checked + .toggle-slider:before {
                    transform: translateX(24px);
                }

                @media (max-width: 768px) {
                    .notification-item {
                        flex-direction: column;
                    }
                    
                    .settings-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script>
                function filterNotifications(type) {
                    // Remover clase active de todos los tabs
                    document.querySelectorAll('.filter-tab').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    
                    // Agregar clase active al tab seleccionado
                    event.target.classList.add('active');
                    
                    // Aquí implementarías la lógica de filtrado
                    alert('Filtrar notificaciones: ' + type);
                }

                function markAllAsRead() {
                    // Remover todos los puntos de no leído
                    document.querySelectorAll('.unread-dot').forEach(dot => {
                        dot.style.display = 'none';
                    });
                    
                    // Remover clase unread de los items
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    alert('Todas las notificaciones marcadas como leídas');
                }

                function clearNotifications() {
                    if (confirm('¿Está seguro de limpiar todas las notificaciones?')) {
                        alert('Función de limpiar notificaciones (conectar con backend)');
                    }
                }

                function handleNotification(type, id) {
                    const actions = {
                        'stock': 'Ver producto',
                        'order': 'Ver pedido',
                        'payment': 'Ver detalles de pago',
                        'system': 'Ver novedades',
                        'client': 'Ver cliente',
                        'shipping': 'Ver pedido',
                        'report': 'Ver reporte'
                    };
                    
                    alert('Acción: ' + actions[type] + ' (ID: ' + id + ')');
                }

                function dismissNotification(id) {
                    // Aquí implementarías la lógica para descartar la notificación
                    const notification = event.target.closest('.notification-item');
                    notification.style.opacity = '0.5';
                    notification.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 300);
                }
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>