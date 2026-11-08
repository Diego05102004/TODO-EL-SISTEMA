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

// Verificar permisos: solo Administrador y SuperUsuario pueden acceder
if (!in_array($_SESSION['nombre_rol'] ?? '', ['Administrador', 'SuperUsuario'])) {
    header('Location: ?pagina=dashboard');
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
        error_log("Error al generar JWT en backup: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'backup';
$titulo_pagina = 'Gestión de Backups';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Backups -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-database"></i></div>
                    <div class="card-content">
                        <h3>Total Backups</h3>
                        <p class="card-value" id="backup-count-total"><?php echo count($backups ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="100, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">100%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="card-content">
                        <h3>Backups Seguridad</h3>
                        <p class="card-value" id="backup-count-seguridad"><?php echo count(array_filter($backups ?? [], function($b) { return ($b['tipo'] ?? '') === 'Seguridad'; })); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="50, 100" stroke="#28a745" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">50%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-server"></i></div>
                    <div class="card-content">
                        <h3>Backups Principal</h3>
                        <p class="card-value" id="backup-count-principal"><?php echo count(array_filter($backups ?? [], function($b) { return ($b['tipo'] ?? '') === 'Principal'; })); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="50, 100" stroke="#ffc107" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">50%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backups Section -->
            <div class="backups-section">
                <div class="section-header">
                    <h2><i class="fas fa-database"></i> Gestión de Backups</h2>
                    <div class="section-actions">
                        <button class="btn-backup-primary" onclick="generarBackup('principal')">
                            <span class="btn-icon"><i class="fas fa-save"></i></span>
                            Backup Principal
                        </button>
                        <button class="btn-backup-security" onclick="generarBackup('seguridad')">
                            <span class="btn-icon"><i class="fas fa-shield-alt"></i></span>
                            Backup Seguridad
                        </button>
                    </div>
                </div>

                <!-- Tabla de Backups -->
                <div class="table-container">
                    <table class="backups-table">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th>Fecha de Modificación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($backups)): ?>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($backup['nombre'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="backup-type-badge <?php echo ($backup['tipo'] ?? '') === 'Seguridad' ? 'security' : 'primary'; ?>">
                                                <?php echo htmlspecialchars($backup['tipo'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($backup['tamano'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($backup['fecha_modificacion'] ?? 'N/A'); ?></td>
                                        <td>
                                            <button class="btn-action btn-restore" data-archivo="<?php echo htmlspecialchars($backup['nombre'] ?? '', ENT_QUOTES); ?>" title="Restaurar">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button class="btn-action btn-download" data-archivo="<?php echo htmlspecialchars($backup['nombre'] ?? '', ENT_QUOTES); ?>" title="Descargar">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn-action btn-delete" data-archivo="<?php echo htmlspecialchars($backup['nombre'] ?? '', ENT_QUOTES); ?>" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-database" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay backups registrados</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <style>
                /* Estilos de Backups */
                .backups-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .backups-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .backups-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .backups-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .backups-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .backups-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .backup-type-badge {
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .backup-type-badge.security {
                    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
                    color: white;
                }

                .backup-type-badge.primary {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                    color: white;
                }

                .btn-backup-primary, .btn-backup-security {
                    padding: 12px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                    color: white;
                }

                .btn-backup-primary {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                }

                .btn-backup-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
                }

                .btn-backup-security {
                    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
                }

                .btn-backup-security:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
                }

                .btn-action {
                    width: 35px;
                    height: 35px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 8px;
                }

                .btn-restore {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                    color: white;
                }

                .btn-download {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-delete {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-action:hover {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }
            </style>

            <style>
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

            <button class="btn-ayuda" id="btn-ayuda-backup" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <script>
            (function () {
                'use strict';

                const BACKUP_CONTROLLER = 'Modelo/Controlador/backup.php';
                const tabla = document.querySelector('.backups-table tbody');

                function escaparHtml(texto) {
                    const div = document.createElement('div');
                    div.textContent = texto == null ? '' : String(texto);
                    return div.innerHTML;
                }

                function formatearTamano(bytes) {
                    const valor = Number(bytes);
                    if (!valor || valor <= 0) return 'N/A';
                    const unidades = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.min(Math.floor(Math.log(valor) / Math.log(1024)), unidades.length - 1);
                    return parseFloat((valor / Math.pow(1024, i)).toFixed(2)) + ' ' + unidades[i];
                }

                function formatearFecha(fecha) {
                    if (!fecha) return 'N/A';
                    const d = new Date(String(fecha).replace(' ', 'T'));
                    if (isNaN(d.getTime())) return String(fecha);
                    const pad = n => String(n).padStart(2, '0');
                    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() +
                           ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
                }

                async function fetchJson(url) {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    const text = await response.text();
                    const trimmed = text ? text.trim() : '';
                    if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
                        console.error('Respuesta no JSON del backend:', text);
                        throw new Error('El servidor devolvió una salida no válida.');
                    }
                    const data = JSON.parse(trimmed);
                    if (!response.ok || data.success === false) {
                        const err = new Error(data.error || data.message || 'Error del servidor');
                        err.data = data;
                        throw err;
                    }
                    return data;
                }

                function filaBackup(b) {
                    const nombre = b.nombre || '';
                    const tipo = b.tipo || 'Principal';
                    const esSeguridad = tipo === 'Seguridad';
                    const tamano = b.tamano != null ? formatearTamano(b.tamano) : (b.tamano_text || 'N/A');
                    const fecha = formatearFecha(b.fecha_modificacion || b.fecha || '');

                    return '<tr>' +
                        '<td>' + escaparHtml(nombre) + '</td>' +
                        '<td><span class="backup-type-badge ' + (esSeguridad ? 'security' : 'primary') + '">' +
                            escaparHtml(tipo) + '</span></td>' +
                        '<td>' + escaparHtml(tamano) + '</td>' +
                        '<td>' + escaparHtml(fecha) + '</td>' +
                        '<td>' +
                            '<button class="btn-action btn-restore" data-archivo="' + escaparHtml(nombre) + '" title="Restaurar"><i class="fas fa-sync-alt"></i></button>' +
                            '<button class="btn-action btn-download" data-archivo="' + escaparHtml(nombre) + '" title="Descargar"><i class="fas fa-download"></i></button>' +
                            '<button class="btn-action btn-delete" data-archivo="' + escaparHtml(nombre) + '" title="Eliminar"><i class="fas fa-trash"></i></button>' +
                        '</td>' +
                    '</tr>';
                }

                function actualizarContadores(lista) {
                    const total = document.getElementById('backup-count-total');
                    const seg = document.getElementById('backup-count-seguridad');
                    const pri = document.getElementById('backup-count-principal');
                    if (total) total.textContent = lista.length;
                    if (seg) seg.textContent = lista.filter(b => (b.tipo || '') === 'Seguridad').length;
                    if (pri) pri.textContent = lista.filter(b => (b.tipo || '') === 'Principal').length;
                }

                async function cargarRespaldos() {
                    if (!tabla) return;
                    tabla.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;">Cargando respaldos...</td></tr>';
                    try {
                        const data = await fetchJson(BACKUP_CONTROLLER + '?accion=listar');
                        const lista = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
                        if (!lista.length) {
                            tabla.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;">' +
                                '<i class="fas fa-database" style="font-size:3rem;color:#2196F3;margin-bottom:15px;"></i>' +
                                '<p style="color:#718096;margin:0;">No hay backups registrados</p></td></tr>';
                        } else {
                            tabla.innerHTML = lista.map(filaBackup).join('');
                        }
                        actualizarContadores(lista);
                    } catch (err) {
                        console.error('Error al cargar respaldos:', err);
                        tabla.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:#e53e3e;">' +
                            'No se pudieron cargar los respaldos</td></tr>';
                        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudieron cargar los respaldos' });
                    }
                }

                async function generarBackup(tipo) {
                    const esPrincipal = tipo === 'principal';
                    const tipoBackend = esPrincipal ? 'P' : 'S';
                    const tipoTexto = esPrincipal ? 'principal' : 'seguridad';

                    const confirm = await Swal.fire({
                        title: 'Generar respaldo de ' + tipoTexto,
                        text: '¿Está seguro de generar un respaldo ' + tipoTexto + '?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, generar'
                    });
                    if (!confirm.isConfirmed) return;

                    Swal.fire({ title: 'Generando respaldo', text: 'Por favor espere...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const data = await fetchJson(BACKUP_CONTROLLER + '?accion=generar&tipo=' + tipoBackend);
                        await Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message || 'Respaldo generado correctamente' });
                        await cargarRespaldos();
                    } catch (err) {
                        console.error('Error al generar respaldo:', err);
                        await Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Error al generar respaldo' });
                    } finally {
                        Swal.close();
                    }
                }

                async function restaurarBackup(archivo) {
                    const confirm = await Swal.fire({
                        title: '¿Estás seguro?',
                        text: '¿Deseas restaurar el respaldo ' + archivo + '? Esta acción sobrescribirá los datos actuales.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, restaurar'
                    });
                    if (!confirm.isConfirmed) return;

                    Swal.fire({ title: 'Restaurando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const data = await fetchJson(BACKUP_CONTROLLER + '?accion=restaurar&archivo=' + encodeURIComponent(archivo));
                        await Swal.fire({ icon: 'success', title: 'Restauración completada', text: data.message || 'La base de datos fue restaurada correctamente' });
                        await cargarRespaldos();
                    } catch (err) {
                        console.error('Error al restaurar:', err);
                        await Swal.fire({ icon: 'error', title: 'Error al restaurar', text: err.message || 'Error al restaurar el respaldo' });
                    } finally {
                        Swal.close();
                    }
                }

                async function eliminarBackup(archivo) {
                    const confirm = await Swal.fire({
                        title: '¿Estás seguro?',
                        text: '¿Deseas eliminar el respaldo ' + archivo + '? Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        confirmButtonColor: '#d33'
                    });
                    if (!confirm.isConfirmed) return;

                    Swal.fire({ title: 'Eliminando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const data = await fetchJson(BACKUP_CONTROLLER + '?accion=eliminar&archivo=' + encodeURIComponent(archivo));
                        await Swal.fire({ icon: 'success', title: 'Respaldo eliminado', text: data.message || 'El respaldo fue eliminado correctamente' });
                        await cargarRespaldos();
                    } catch (err) {
                        console.error('Error al eliminar:', err);
                        await Swal.fire({ icon: 'error', title: 'Error al eliminar', text: err.message || 'Error al eliminar el respaldo' });
                    } finally {
                        Swal.close();
                    }
                }

                function descargarBackup(archivo) {
                    window.location.href = BACKUP_CONTROLLER + '?accion=descargar&archivo=' + encodeURIComponent(archivo);
                }

                // Delegación de eventos para los botones de acción
                document.addEventListener('click', function (e) {
                    const btnRestore = e.target.closest('.btn-restore');
                    const btnDownload = e.target.closest('.btn-download');
                    const btnDelete = e.target.closest('.btn-delete');
                    if (!btnRestore && !btnDownload && !btnDelete) return;

                    const archivo = (btnRestore || btnDownload || btnDelete).dataset.archivo;
                    if (!archivo) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Archivo inválido' });
                        return;
                    }
                    if (btnRestore) restaurarBackup(archivo);
                    else if (btnDownload) descargarBackup(archivo);
                    else eliminarBackup(archivo);
                });

                // Exponer generarBackup para los botones del encabezado (onclick inline)
                window.generarBackup = generarBackup;

                // ==================== Modal de ayuda ====================
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

                async function abrirModalAyuda() {
                    try {
                        await cargarRecursoAyuda('css', 'assets/public/ayuda/css/modal.css');

                        if (!document.getElementById('modalAyuda')) {
                            const html = await (await fetch('assets/public/ayuda/backup.php')).text();
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

                document.getElementById('btn-ayuda-backup')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda();
                });

                // Carga inicial de la tabla
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', cargarRespaldos);
                } else {
                    cargarRespaldos();
                }
            })();
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>