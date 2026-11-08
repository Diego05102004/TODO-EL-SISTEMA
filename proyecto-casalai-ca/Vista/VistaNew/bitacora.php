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
        error_log("Error al generar JWT en bitacora: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'bitacora';
$titulo_pagina = 'Bitácora del Sistema';

// Permiso de consulta del módulo (misma regla que la vista anterior)
$puedeConsultar = !empty($permisosModuloBitacora['consultar'] ?? false);

// Estadísticas para las tarjetas resumen
$registrosBitacora = is_array($registros ?? null) ? $registros : [];
$totalRegistros = count($registrosBitacora);
$hoyStr = date('Y-m-d');
$registrosHoy = 0;
$registrosAlta = 0;
foreach ($registrosBitacora as $r) {
    if (isset($r['fecha_hora']) && strpos((string)$r['fecha_hora'], $hoyStr) === 0) {
        $registrosHoy++;
    }
    if (isset($r['prioridad']) && strtolower((string)$r['prioridad']) === 'alta') {
        $registrosAlta++;
    }
}

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Bitácora -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="card-content">
                        <h3>Total Registros</h3>
                        <p class="card-value" id="bitacora-count-total"><?php echo $totalRegistros; ?></p>
                    </div>
                    <div class="card-percentage">
                        <svg viewBox="0 0 36 36" class="circular-chart green">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="circle" stroke-dasharray="100, 100" stroke="#4caf50" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="percentage">100%</span>
                    </div>
                </div>
                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="card-content">
                        <h3>Registros Hoy</h3>
                        <p class="card-value" id="bitacora-count-hoy"><?php echo $registrosHoy; ?></p>
                    </div>
                    <div class="card-percentage">
                        <svg viewBox="0 0 36 36" class="circular-chart orange">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="circle" stroke-dasharray="75, 100" stroke="#ff9800" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="percentage">75%</span>
                    </div>
                </div>
                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="card-content">
                        <h3>Prioridad Alta</h3>
                        <p class="card-value" id="bitacora-count-alta"><?php echo $registrosAlta; ?></p>
                    </div>
                    <div class="card-percentage">
                        <svg viewBox="0 0 36 36" class="circular-chart yellow">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="circle" stroke-dasharray="50, 100" stroke="#ffc107" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="percentage">50%</span>
                    </div>
                </div>
            </div>

            <!-- Bitácora Section -->
            <div class="bitacora-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Bitácora del Sistema</h2>
                    <div class="section-actions">
                        <span class="live-indicator" id="bitacora-live-status">
                            <i class="fas fa-circle"></i> En vivo
                        </span>
                        <span class="last-update" id="bitacora-last-update"></span>
                    </div>
                </div>

                <?php if (!$puedeConsultar): ?>
                    <div class="table-container" style="text-align:center; padding: 50px;">
                        <i class="fas fa-lock" style="font-size:3rem;color:#e53e3e;margin-bottom:15px;"></i>
                        <p style="color:#718096;margin:0;font-weight:600;">No tiene permiso para consultar la bitácora.</p>
                    </div>
                <?php else: ?>
                <!-- Tabla de Bitácora -->
                <div class="table-container">
                    <table class="bitacora-table" id="tablaBitacora"
                           data-datatable data-page-length="25" data-datatable-order='[[0,"desc"]]'>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción Realizada</th>
                                <th>Módulo</th>
                                <th>Prioridad</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrosBitacora as $registro): ?>
                                <?php
                                $prio = strtolower((string)($registro['prioridad'] ?? 'baja'));
                                $prioClass = in_array($prio, ['alta', 'media', 'baja'], true) ? $prio : 'baja';
                                ?>
                                <tr data-id="<?php echo (int)($registro['id_bitacora'] ?? 0); ?>">
                                    <td><span class="campo-numeros"><?php echo htmlspecialchars($registro['id_bitacora'] ?? ''); ?></span></td>
                                    <td><span class="campo-numeros"><?php echo htmlspecialchars($registro['fecha_hora'] ?? ''); ?></span></td>
                                    <td><span class="campo-nombres"><?php echo htmlspecialchars($registro['username'] ?? 'N/A'); ?></span></td>
                                    <td><span class="campo-tex-num"><?php echo htmlspecialchars($registro['accion'] ?? ''); ?></span></td>
                                    <td><span class="campo-tex-num"><?php echo htmlspecialchars($registro['modulo'] ?? ($registro['nombre_modulo'] ?? '')); ?></span></td>
                                    <td><span class="prio-badge prio-<?php echo $prioClass; ?>"><?php echo htmlspecialchars(ucfirst($prioClass)); ?></span></td>
                                    <td><span class="campo-nombres"><?php echo htmlspecialchars($registro['descripcion'] ?? ''); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <button class="btn-ayuda" id="btn-ayuda-bitacora" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <style>
                /* Estilos de Bitácora */
                .bitacora-section {
                    margin-top: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                    gap: 15px;
                }

                .section-header h2 {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .section-header h2 i {
                    color: #2196F3;
                }

                .section-actions {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .live-indicator {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 6px 14px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 600;
                    background: rgba(76, 175, 80, 0.12);
                    color: #2e7d32;
                }

                .live-indicator i {
                    font-size: 0.55rem;
                    animation: bitacora-pulse 1.6s ease-in-out infinite;
                }

                .live-indicator.offline {
                    background: rgba(229, 62, 62, 0.12);
                    color: #c62828;
                }

                .live-indicator.offline i {
                    animation: none;
                }

                @keyframes bitacora-pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.25; }
                }

                .last-update {
                    font-size: 0.8rem;
                    color: #718096;
                }

                .bitacora-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .bitacora-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .bitacora-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                    white-space: nowrap;
                }

                .bitacora-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                    vertical-align: top;
                }

                .bitacora-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .campo-numeros { font-weight: 600; color: #2d3748; }
                .campo-nombres { color: #4a5568; }
                .campo-tex-num { color: #2d3748; font-weight: 500; }

                .prio-badge {
                    display: inline-block;
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.78rem;
                    font-weight: 700;
                    text-transform: capitalize;
                }

                .prio-alta {
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

                .table-container.flash {
                    animation: bitacora-flash 1.2s ease-out;
                }

                @keyframes bitacora-flash {
                    0% { box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.5); }
                    100% { box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); }
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
            </style>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=bitacora&ajax=registros&limite=100';
                const INTERVALO_MS = 15000;
                const MAX_FILAS = 600;

                const tabla = document.getElementById('tablaBitacora');
                const puedeConsultar = <?php echo $puedeConsultar ? 'true' : 'false'; ?>;
                if (!puedeConsultar || !tabla) return;

                const tbody = tabla.querySelector('tbody');
                const contenedor = tabla.closest('.table-container');
                const elTotal = document.getElementById('bitacora-count-total');
                const elHoy = document.getElementById('bitacora-count-hoy');
                const elAlta = document.getElementById('bitacora-count-alta');
                const elEstado = document.getElementById('bitacora-live-status');
                const elUpdate = document.getElementById('bitacora-last-update');

                const idsVistos = new Set();
                tbody.querySelectorAll('tr[data-id]').forEach(function (tr) {
                    idsVistos.add(tr.dataset.id);
                });

                function escaparHtml(texto) {
                    const div = document.createElement('div');
                    div.textContent = texto == null ? '' : String(texto);
                    return div.innerHTML;
                }

                function formatearFecha(fecha) {
                    if (!fecha) return '';
                    const s = String(fecha).replace('T', ' ');
                    const m = s.match(/(\d{4})-(\d{2})-(\d{2})[ ](\d{2}):(\d{2}):(\d{2})/);
                    if (!m) return escaparHtml(s);
                    let h = parseInt(m[4], 10);
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12 || 12;
                    return m[3] + '/' + m[2] + '/' + m[1] + ' ' + h + ':' + m[5] + ':' + m[6] + ' ' + ampm;
                }

                function clasePrioridad(prioridad) {
                    const p = String(prioridad || 'baja').toLowerCase();
                    return ['alta', 'media', 'baja'].includes(p) ? p : 'baja';
                }

                function celdasRegistro(r) {
                    const prio = clasePrioridad(r.prioridad);
                    return [
                        '<span class="campo-numeros">' + escaparHtml(r.id_bitacora) + '</span>',
                        '<span class="campo-numeros">' + escaparHtml(r.fecha_hora || '') + '</span>',
                        '<span class="campo-nombres">' + escaparHtml(r.username || 'N/A') + '</span>',
                        '<span class="campo-tex-num">' + escaparHtml(r.accion || '') + '</span>',
                        '<span class="campo-tex-num">' + escaparHtml(r.modulo || r.nombre_modulo || '') + '</span>',
                        '<span class="prio-badge prio-' + prio + '">' + escaparHtml(prio.charAt(0).toUpperCase() + prio.slice(1)) + '</span>',
                        '<span class="campo-nombres">' + escaparHtml(r.descripcion || '') + '</span>'
                    ];
                }

                function obtenerDataTable() {
                    const $ = window.jQuery;
                    if (!$ || !$.fn || !$.fn.DataTable) return null;
                    if (!$.fn.DataTable.isDataTable(tabla)) return null;
                    return $(tabla).DataTable();
                }

                function actualizarContadores(nuevos) {
                    if (!nuevos.length) return;
                    const hoyStr = new Date().toISOString().slice(0, 10);
                    let hoy = 0, alta = 0;
                    nuevos.forEach(function (r) {
                        if (r.fecha_hora && String(r.fecha_hora).indexOf(hoyStr) === 0) hoy++;
                        if (clasePrioridad(r.prioridad) === 'alta') alta++;
                    });
                    if (elTotal) elTotal.textContent = parseInt(elTotal.textContent || '0', 10) + nuevos.length;
                    if (elHoy) elHoy.textContent = parseInt(elHoy.textContent || '0', 10) + hoy;
                    if (elAlta) elAlta.textContent = parseInt(elAlta.textContent || '0', 10) + alta;
                }

                function marcarActualizacion() {
                    if (elUpdate) {
                        elUpdate.textContent = 'Actualizado ' + new Date().toLocaleTimeString();
                    }
                }

                function marcarOnline(online) {
                    if (!elEstado) return;
                    elEstado.classList.toggle('offline', !online);
                    elEstado.innerHTML = online
                        ? '<i class="fas fa-circle"></i> En vivo'
                        : '<i class="fas fa-circle"></i> Sin conexión';
                }

                async function sondearBitacora() {
                    try {
                        const response = await fetch(ENDPOINT, {
                            method: 'GET',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin'
                        });
                        const data = await response.json();
                        if (!response.ok || !data || data.status !== 'success') {
                            marcarOnline(false);
                            return;
                        }
                        marcarOnline(true);

                        const lista = Array.isArray(data.registros) ? data.registros : [];
                        const nuevos = lista.filter(function (r) {
                            return r && r.id_bitacora && !idsVistos.has(String(r.id_bitacora));
                        });
                        console.debug('[bitacora] registros recibidos:', lista.length, '| nuevos:', nuevos.length);
                        if (!nuevos.length) {
                            marcarActualizacion();
                            return;
                        }

                        nuevos.forEach(function (r) { idsVistos.add(String(r.id_bitacora)); });

                        const dt = obtenerDataTable();
                        if (dt) {
                            try {
                                nuevos.slice().reverse().forEach(function (r) {
                                    dt.row.add(celdasRegistro(r));
                                });
                                dt.draw(false);

                            // Mantener acotado el volumen de filas
                            while (dt.rows().count() > MAX_FILAS) {
                                const indices = dt.rows({ order: 'applied' }).indexes();
                                if (!indices.length) break;
                                dt.row(indices[indices.length - 1]).remove();
                            }
                            dt.draw(false);
                            } catch (dtErr) {
                                console.error('[bitacora] Error actualizando DataTables:', dtErr);
                            }
                        } else {
                            // Respaldo sin DataTables: inserción DOM directa
                            nuevos.slice().reverse().forEach(function (r) {
                                const prio = clasePrioridad(r.prioridad);
                                const tr = document.createElement('tr');
                                tr.dataset.id = r.id_bitacora;
                                tr.innerHTML =
                                    '<td><span class="campo-numeros">' + escaparHtml(r.id_bitacora) + '</span></td>' +
                                    '<td><span class="campo-numeros">' + escaparHtml(r.fecha_hora || '') + '</span></td>' +
                                    '<td><span class="campo-nombres">' + escaparHtml(r.username || 'N/A') + '</span></td>' +
                                    '<td><span class="campo-tex-num">' + escaparHtml(r.accion || '') + '</span></td>' +
                                    '<td><span class="campo-tex-num">' + escaparHtml(r.modulo || r.nombre_modulo || '') + '</span></td>' +
                                    '<td><span class="prio-badge prio-' + prio + '">' + escaparHtml(prio.charAt(0).toUpperCase() + prio.slice(1)) + '</span></td>' +
                                    '<td><span class="campo-nombres">' + escaparHtml(r.descripcion || '') + '</span></td>';
                                tbody.insertBefore(tr, tbody.firstChild);
                            });
                        }

                        actualizarContadores(nuevos);
                        marcarActualizacion();
                        if (contenedor) {
                            contenedor.classList.remove('flash');
                            void contenedor.offsetWidth;
                            contenedor.classList.add('flash');
                        }
                    } catch (err) {
                        console.error('Error al sondear la bitácora:', err);
                        marcarOnline(false);
                    }
                }

                // Esperar a que DataTables termine de inicializarse (carga perezosa)
                const inicio = Date.now();
                const intentoInicial = setInterval(function () {
                    if (obtenerDataTable() || Date.now() - inicio > 20000) {
                        clearInterval(intentoInicial);
                        marcarActualizacion();
                        sondearBitacora();
                        setInterval(sondearBitacora, INTERVALO_MS);
                    }
                }, 500);

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
                            const html = await (await fetch('assets/public/ayuda/bitacora.php')).text();
                            document.body.insertAdjacentHTML('beforeend', html);
                        }

                        await cargarRecursoAyuda('js', 'assets/public/ayuda/js/modal.js');

                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
                            document.body.style.overflow = '';
                            if (contexto) {
                                const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    setTimeout(() => modalAyudaInstance.goToSlide(slideIndex), 300);
                                }
                            }
                            modalAyudaInstance.openModal();
                        } else {
                            console.error('La función inicializarModalAyudaUsuario no está disponible');
                        }
                    } catch (err) {
                        console.error('Error al cargar el modal de ayuda:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el contenido de ayuda' });
                    }
                }

                document.getElementById('btn-ayuda-bitacora')?.addEventListener('click', function (e) {
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
