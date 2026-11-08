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

$notificacionesLista = is_array($notificaciones ?? null) ? array_values($notificaciones) : [];

// Estadísticas reales para las tarjetas
$total_notificaciones = count($notificacionesLista);
$notificaciones_no_leidas = 0;
$notificaciones_importantes = 0;
foreach ($notificacionesLista as $n) {
    if ((int)($n['leido'] ?? 0) !== 1) {
        $notificaciones_no_leidas++;
    }
    $prio = strtolower((string)($n['prioridad'] ?? ''));
    if (in_array($prio, ['alta', 'urgente'], true)) {
        $notificaciones_importantes++;
    }
}
$porcentaje_no_leidas = $total_notificaciones > 0 ? round(($notificaciones_no_leidas / $total_notificaciones) * 100) : 0;
$porcentaje_importantes = $total_notificaciones > 0 ? round(($notificaciones_importantes / $total_notificaciones) * 100) : 0;

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Notificaciones -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-comments"></i></div>
                    <div class="card-content">
                        <h3>Total Notificaciones</h3>
                        <p class="card-value" id="notif-count-total"><?php echo $total_notificaciones; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="100, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">100%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-envelope"></i></div>
                    <div class="card-content">
                        <h3>No Leídas</h3>
                        <p class="card-value" id="notif-count-unread"><?php echo $notificaciones_no_leidas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" id="notif-pct-unread" stroke-dasharray="<?php echo $porcentaje_no_leidas; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage" id="notif-pct-unread-label"><?php echo $porcentaje_no_leidas; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-star"></i></div>
                    <div class="card-content">
                        <h3>Importantes</h3>
                        <p class="card-value" id="notif-count-important"><?php echo $notificaciones_importantes; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" id="notif-pct-important" stroke-dasharray="<?php echo $porcentaje_importantes; ?>, 100" stroke="#ffc107" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage" id="notif-pct-important-label"><?php echo $porcentaje_importantes; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros de Notificaciones -->
            <div class="notifications-filters">
                <div class="section-header">
                    <h2><i class="fas fa-bell"></i> Mis Notificaciones</h2>
                    <div class="section-actions">
                        <button class="btn-mark-read" id="btn-marcar-todas">
                            <span class="btn-icon">✓</span>
                            Marcar todas como leídas
                        </button>
                    </div>
                </div>

                <div class="filter-tabs">
                    <button class="filter-tab active" data-filtro="all">Todas</button>
                    <button class="filter-tab" data-filtro="unread">No leídas</button>
                    <button class="filter-tab" data-filtro="important">Importantes</button>
                    <button class="filter-tab" data-filtro="system">Sistema</button>
                </div>

                <div class="notif-search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="notif-search" class="notif-search" placeholder="Buscar en título o descripción...">
                </div>
            </div>

            <!-- Lista de Notificaciones -->
            <div class="notifications-list" id="notif-page-list">
                <div class="notif-empty" id="notif-empty" style="display:none;">
                    <i class="fas fa-bell-slash"></i>
                    <p>No hay notificaciones para mostrar</p>
                </div>
            </div>

            <button class="btn-ayuda" id="btn-ayuda-notificacion" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <style>
                /* Estilos específicos de Notificaciones */
                .notifications-filters {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 15px;
                }

                .section-header h2 {
                    margin: 0;
                    color: #333;
                    font-size: 1.5rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .section-header h2 i {
                    color: #2196F3;
                }

                .btn-mark-read {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 10px 18px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.2s;
                }

                .btn-mark-read:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(33, 150, 243, 0.35);
                }

                .filter-tabs {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                    flex-wrap: wrap;
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

                .notif-search-wrapper {
                    position: relative;
                    margin-top: 20px;
                    max-width: 420px;
                }

                .notif-search-wrapper i {
                    position: absolute;
                    left: 14px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #718096;
                }

                .notif-search {
                    width: 100%;
                    padding: 11px 15px 11px 40px;
                    border: 2px solid #e9ecef;
                    border-radius: 10px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .notif-search:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .notifications-list {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .notif-empty {
                    background: white;
                    border-radius: 12px;
                    padding: 50px 25px;
                    text-align: center;
                    color: #718096;
                }

                .notif-empty i {
                    font-size: 3rem;
                    color: #cbd5e0;
                    margin-bottom: 15px;
                }

                .notif-empty p {
                    margin: 0;
                    font-weight: 600;
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

                .notification-item.read {
                    opacity: 0.75;
                }

                .notification-item.new-live {
                    animation: notif-enter 0.5s ease-out;
                }

                @keyframes notif-enter {
                    from { opacity: 0; transform: translateY(-15px); }
                    to { opacity: 1; transform: translateY(0); }
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

                .notification-item[data-tipo="pago"] .icon-container i { color: #38a169; }
                .notification-item[data-tipo="despacho"] .icon-container i { color: #3182ce; }
                .notification-item[data-tipo="sistema"] .icon-container i { color: #6c757d; }
                .notification-item[data-tipo="alerta"] .icon-container i { color: #e53e3e; }
                .notification-item[data-tipo="general"] .icon-container i { color: #d69e2e; }

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
                    min-width: 0;
                }

                .notification-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 10px;
                    flex-wrap: wrap;
                }

                .notification-header h4 {
                    margin: 0;
                    color: #333;
                    font-size: 1rem;
                }

                .notification-header .notif-meta {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .prio-badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 20px;
                    font-size: 0.72rem;
                    font-weight: 700;
                    text-transform: capitalize;
                }

                .prio-alta, .prio-urgente {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .prio-media {
                    background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
                    color: white;
                }

                .prio-baja {
                    background: linear-gradient(135deg, #68d391 0%, #38a169 100%);
                    color: white;
                }

                .notification-time {
                    font-size: 0.85rem;
                    color: #666;
                    white-space: nowrap;
                }

                .notification-content p {
                    margin: 0 0 15px 0;
                    color: #555;
                    line-height: 1.5;
                    word-break: break-word;
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

                .btn-notification-action:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                }

                /* Botón de ayuda */
                .btn-ayuda {
                    position: fixed;
                    top: 115px;
                    right: 25px;
                    width: 50px;
                    height: 50px;
                    border: none;
                    border-radius: 50%;
                    background-color: #5995fd;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                    z-index: 1000;
                }
                .btn-ayuda img {
                    width: 25px;
                    height: 25px;
                    pointer-events: none;
                }
                .btn-ayuda:hover {
                    background-color: #4d84e2;
                    transform: scale(1.1);
                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
                }

                @media (max-width: 768px) {
                    .notification-item {
                        flex-direction: column;
                    }
                }
            </style>

            <script>
            (function () {
                'use strict';

                const ENDPOINT_MARCAR = '?pagina=notificacion&accion=marcar_leida';
                const ENDPOINT_MARCAR_TODAS = '?pagina=notificacion&accion=marcar_todas_leidas';

                const ICONOS_POR_TIPO = {
                    pago: 'fa-credit-card',
                    despacho: 'fa-truck',
                    sistema: 'fa-tools',
                    alerta: 'fa-exclamation-triangle',
                    general: 'fa-bell'
                };

                const state = {
                    notificaciones: [],
                    filtro: 'all',
                    busqueda: ''
                };

                const listaEl = document.getElementById('notif-page-list');
                const emptyEl = document.getElementById('notif-empty');
                const searchEl = document.getElementById('notif-search');

                /* ==================== Utilidades ==================== */
                function escaparHtml(texto) {
                    const div = document.createElement('div');
                    div.textContent = texto == null ? '' : String(texto);
                    return div.innerHTML;
                }

                function formatearFecha(fechaString) {
                    if (!fechaString) return '';
                    let fecha = new Date(String(fechaString).replace(' ', 'T'));
                    if (isNaN(fecha.getTime())) return '';
                    const ahora = new Date();
                    const diff = Math.floor((ahora - fecha) / 1000);
                    if (diff < 60) return 'Hace unos segundos';
                    if (diff < 3600) {
                        const m = Math.floor(diff / 60);
                        return 'Hace ' + m + ' minuto' + (m > 1 ? 's' : '');
                    }
                    if (diff < 86400) {
                        const h = Math.floor(diff / 3600);
                        return 'Hace ' + h + ' hora' + (h > 1 ? 's' : '');
                    }
                    if (diff < 604800) {
                        const d = Math.floor(diff / 86400);
                        return 'Hace ' + d + ' día' + (d > 1 ? 's' : '');
                    }
                    return fecha.toLocaleDateString('es-ES', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                }

                function estaLeida(n) {
                    return n && (n.leido === 1 || n.leido === '1' || n.leido === true ||
                                 n.leida === 1 || n.leida === '1' || n.leida === true);
                }

                function esImportante(n) {
                    const p = String(n.prioridad || '').toLowerCase();
                    return p === 'alta' || p === 'urgente';
                }

                function clasePrioridad(n) {
                    const p = String(n.prioridad || 'baja').toLowerCase();
                    return ['alta', 'media', 'baja', 'urgente'].includes(p) ? p : 'baja';
                }

                function iconoTipo(tipo) {
                    return ICONOS_POR_TIPO[String(tipo || '').toLowerCase()] || 'fa-bell';
                }

                function coincideFiltro(n) {
                    switch (state.filtro) {
                        case 'unread': return !estaLeida(n);
                        case 'important': return esImportante(n);
                        case 'system': return String(n.tipo || '').toLowerCase() === 'sistema';
                        default: return true;
                    }
                }

                function coincideBusqueda(n) {
                    if (!state.busqueda) return true;
                    const q = state.busqueda.toLowerCase();
                    return String(n.titulo || '').toLowerCase().includes(q) ||
                           String(n.mensaje || '').toLowerCase().includes(q);
                }

                /* ==================== Renderizado ==================== */
                function htmlNotificacion(n, esNueva) {
                    const leida = estaLeida(n);
                    const importante = esImportante(n);
                    const prio = clasePrioridad(n);
                    const id = n.id_notificacion || n.id;
                    const clases = ['notification-item'];
                    if (leida) clases.push('read'); else clases.push('unread');
                    if (importante) clases.push('important');
                    if (esNueva) clases.push('new-live');

                    return '<div class="' + clases.join(' ') + '" data-id="' + escaparHtml(id) +
                        '" data-tipo="' + escaparHtml(String(n.tipo || '').toLowerCase()) + '">' +
                        '<div class="notification-icon">' +
                            '<span class="icon-container"><i class="fas ' + iconoTipo(n.tipo) + '"></i></span>' +
                            (leida ? '' : '<span class="unread-dot"></span>') +
                        '</div>' +
                        '<div class="notification-content">' +
                            '<div class="notification-header">' +
                                '<h4>' + escaparHtml(n.titulo || 'Notificación') + '</h4>' +
                                '<div class="notif-meta">' +
                                    '<span class="prio-badge prio-' + escaparHtml(prio) + '">' + escaparHtml(prio) + '</span>' +
                                    '<span class="notification-time">' + escaparHtml(formatearFecha(n.fecha_hora || n.fecha_creacion)) + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<p>' + escaparHtml(n.mensaje || '') + '</p>' +
                            '<div class="notification-actions">' +
                                (leida ? '' : '<button class="btn-notification-action btn-mark-one" data-id="' + escaparHtml(id) + '">Marcar leída</button>') +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }

                function renderLista() {
                    const visibles = state.notificaciones.filter(n => coincideFiltro(n) && coincideBusqueda(n));
                    if (!visibles.length) {
                        listaEl.innerHTML = '';
                        emptyEl.style.display = 'block';
                    } else {
                        emptyEl.style.display = 'none';
                        listaEl.innerHTML = visibles.map(n => htmlNotificacion(n, false)).join('');
                    }
                    actualizarContadores();
                }

                function actualizarContadores() {
                    const total = state.notificaciones.length;
                    const noLeidas = state.notificaciones.filter(n => !estaLeida(n)).length;
                    const importantes = state.notificaciones.filter(n => esImportante(n)).length;

                    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                    setVal('notif-count-total', total);
                    setVal('notif-count-unread', noLeidas);
                    setVal('notif-count-important', importantes);

                    const pctU = total > 0 ? Math.round((noLeidas / total) * 100) : 0;
                    const pctI = total > 0 ? Math.round((importantes / total) * 100) : 0;
                    const arcU = document.getElementById('notif-pct-unread');
                    const arcI = document.getElementById('notif-pct-important');
                    const lblU = document.getElementById('notif-pct-unread-label');
                    const lblI = document.getElementById('notif-pct-important-label');
                    if (arcU) arcU.setAttribute('stroke-dasharray', pctU + ', 100');
                    if (arcI) arcI.setAttribute('stroke-dasharray', pctI + ', 100');
                    if (lblU) lblU.textContent = pctU + '%';
                    if (lblI) lblI.textContent = pctI + '%';
                }

                function agregarNotificacion(n, esNueva) {
                    const id = String(n.id_notificacion || n.id || '');
                    if (!id) return;
                    const idx = state.notificaciones.findIndex(x => String(x.id_notificacion || x.id) === id);
                    if (idx >= 0) {
                        // Actualizar datos sin duplicar
                        state.notificaciones[idx] = Object.assign({}, state.notificaciones[idx], n);
                    } else {
                        // Insertar manteniendo orden descendente por id
                        const idNum = parseInt(id, 10) || 0;
                        let pos = state.notificaciones.length;
                        for (let i = 0; i < state.notificaciones.length; i++) {
                            const otroId = parseInt(state.notificaciones[i].id_notificacion || state.notificaciones[i].id || 0, 10) || 0;
                            if (otroId < idNum) { pos = i; break; }
                        }
                        state.notificaciones.splice(pos, 0, n);
                    }
                    renderLista();
                    if (esNueva) {
                        const item = listaEl.querySelector('.notification-item[data-id="' + CSS.escape(id) + '"]');
                        if (item) item.classList.add('new-live');
                    }
                }

                /* ==================== Acciones ==================== */
                function actualizarLocalLeida(id) {
                    const notif = state.notificaciones.find(x => String(x.id_notificacion || x.id) === String(id));
                    if (notif) notif.leido = 1;
                    renderLista();
                }

                async function marcarLeida(id) {
                    const boton = listaEl.querySelector('.btn-mark-one[data-id="' + CSS.escape(String(id)) + '"]');
                    if (boton) boton.disabled = true;

                    let exito = false;
                    try {
                        if (window.notificacionesWS && typeof window.notificacionesWS.marcarComoLeida === 'function') {
                            const res = await window.notificacionesWS.marcarComoLeida(id);
                            // res puede ser undefined si el cliente lo capturó como error
                            exito = !!(res && res.success === true);
                        }
                    } catch (e) {
                        console.warn('Long polling marcar_leida falló, se intenta endpoint clásico:', e);
                    }

                    if (!exito) {
                        try {
                            const body = new URLSearchParams({ id_notificacion: String(id) });
                            const resp = await fetch(ENDPOINT_MARCAR, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin',
                                body: body.toString()
                            });
                            const data = await resp.json();
                            exito = !!(data && data.exito === true);
                        } catch (e2) {
                            console.error('Error al marcar notificación como leída:', e2);
                        }
                    }

                    if (exito) {
                        actualizarLocalLeida(id);
                    } else {
                        if (boton) boton.disabled = false;
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo marcar la notificación como leída' });
                    }
                }

                async function marcarTodasLeidas() {
                    const confirmar = await Swal.fire({
                        title: '¿Marcar todas como leídas?',
                        text: 'Todas las notificaciones no leídas pasarán a estado leído.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2196F3',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, marcar todas',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirmar.isConfirmed) return;

                    Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const resp = await fetch(ENDPOINT_MARCAR_TODAS, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin'
                        });
                        const data = await resp.json();
                        if (data && data.exito === true) {
                            state.notificaciones.forEach(n => { n.leido = 1; });
                            renderLista();
                            await Swal.fire({ icon: 'success', title: 'Listo', text: 'Todas las notificaciones fueron marcadas como leídas', timer: 2000, showConfirmButton: false });
                        } else {
                            await Swal.fire({ icon: 'error', title: 'Error', text: data.mensaje || 'No se pudieron actualizar las notificaciones' });
                        }
                    } catch (e) {
                        console.error('Error al marcar todas como leídas:', e);
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la operación. Intente nuevamente.' });
                    } finally {
                        Swal.close();
                    }
                }

                /* ==================== Eventos ==================== */
                document.querySelectorAll('.filter-tab').forEach(tab => {
                    tab.addEventListener('click', function () {
                        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        state.filtro = this.dataset.filtro;
                        renderLista();
                    });
                });

                if (searchEl) {
                    searchEl.addEventListener('input', function () {
                        state.busqueda = this.value.trim();
                        renderLista();
                    });
                }

                listaEl.addEventListener('click', function (e) {
                    const btn = e.target.closest('.btn-mark-one');
                    if (btn) {
                        e.stopPropagation();
                        marcarLeida(btn.dataset.id);
                    }
                });

                document.getElementById('btn-marcar-todas')?.addEventListener('click', marcarTodasLeidas);

                /* ==================== Datos iniciales ==================== */
                state.notificaciones = <?php echo json_encode($notificacionesLista, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]'; ?>;
                if (!Array.isArray(state.notificaciones)) state.notificaciones = [];
                renderLista();

                /* ==================== Actualización en tiempo real (long polling) ==================== */
                function manejarMensajeLive(data) {
                    if (!data || (data.tipo !== 'nueva_notificacion' && data.tipo !== 'sync_inicial')) return;
                    const nuevas = Array.isArray(data.notificaciones) ? data.notificaciones : [];
                    nuevas.forEach(n => agregarNotificacion(n, true));
                }

                document.addEventListener('DOMContentLoaded', function () {
                    const ws = window.notificacionesWS;
                    if (ws && typeof ws.manejarMensaje === 'function') {
                        const original = ws.manejarMensaje.bind(ws);
                        ws.manejarMensaje = function (data) {
                            original(data);
                            manejarMensajeLive(data);
                        };
                    }
                });

                /* ==================== Modal de ayuda ==================== */
                let modalAyudaInstance = null;

                function cargarRecursoAyuda(tipo, url) {
                    return new Promise((resolve, reject) => {
                        if (tipo === 'css' && document.querySelector('link[href*="ayuda/css/modal.css"]')) return resolve();
                        if (tipo === 'js' && document.querySelector('script[src*="ayuda/js/modal.js"]')) return resolve();
                        const el = document.createElement(tipo === 'css' ? 'link' : 'script');
                        if (tipo === 'css') { el.rel = 'stylesheet'; el.href = url; }
                        else { el.src = url; }
                        el.onload = resolve;
                        el.onerror = reject;
                        document.head.appendChild(el);
                    });
                }

                async function abrirModalAyuda(contexto) {
                    try {
                        await cargarRecursoAyuda('css', 'assets/public/ayuda/css/modal.css');

                        if (!document.getElementById('modalAyuda')) {
                            const html = await (await fetch('assets/public/ayuda/notificacion.php')).text();
                            document.body.insertAdjacentHTML('beforeend', html);
                        }

                        await cargarRecursoAyuda('js', 'assets/public/ayuda/js/modal.js');

                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
                            document.body.style.overflow = '';
                            modalAyudaInstance.openModal();
                        } else {
                            console.error('La función inicializarModalAyudaUsuario no está disponible');
                        }
                    } catch (err) {
                        console.error('Error al cargar el modal de ayuda:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el contenido de ayuda' });
                    }
                }

                document.getElementById('btn-ayuda-notificacion')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda();
                });
            })();
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>
