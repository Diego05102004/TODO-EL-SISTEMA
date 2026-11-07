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
                        <p class="card-value"><?php echo count($backups ?? []); ?></p>
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
                        <p class="card-value"><?php echo count(array_filter($backups ?? [], function($b) { return ($b['tipo'] ?? '') === 'Seguridad'; })); ?></p>
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
                        <p class="card-value"><?php echo count(array_filter($backups ?? [], function($b) { return ($b['tipo'] ?? '') === 'Principal'; })); ?></p>
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
                                            <button class="btn-action btn-restore" onclick="restaurarBackup('<?php echo htmlspecialchars($backup['nombre'] ?? ''); ?>')">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button class="btn-action btn-download" onclick="descargarBackup('<?php echo htmlspecialchars($backup['nombre'] ?? ''); ?>')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="eliminarBackup('<?php echo htmlspecialchars($backup['nombre'] ?? ''); ?>')">
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

            <script>
                function generarBackup(tipo) {
                    const mensaje = tipo === 'principal' 
                        ? '¿Está seguro de generar un backup principal?' 
                        : '¿Está seguro de generar un backup de seguridad?';
                    
                    if (confirm(mensaje)) {
                        alert('Función para generar backup ' + tipo + ' (conectar con backend)');
                    }
                }

                function restaurarBackup(archivo) {
                    if (confirm('¿Está seguro de restaurar el backup: ' + archivo + '?')) {
                        alert('Función para restaurar backup: ' + archivo + '\n(conectar con backend)');
                    }
                }

                function descargarBackup(archivo) {
                    alert('Función para descargar backup: ' + archivo + '\n(conectar con backend)');
                }

                function eliminarBackup(archivo) {
                    if (confirm('¿Está seguro de eliminar el backup: ' + archivo + '?\nEsta acción no se puede deshacer.')) {
                        alert('Función para eliminar backup: ' + archivo + '\n(conectar con backend)');
                    }
                }
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>