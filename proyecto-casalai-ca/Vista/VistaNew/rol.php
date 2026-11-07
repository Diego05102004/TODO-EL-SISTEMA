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
        error_log("Error al generar JWT en rol: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'rol';
$titulo_pagina = 'Gestión de Roles';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Roles -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-users-cog"></i></div>
                    <div class="card-content">
                        <h3>Total Roles</h3>
                        <p class="card-value"><?php echo count($roles ?? []); ?></p>
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
                    <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="card-content">
                        <h3>Roles Activos</h3>
                        <p class="card-value"><?php echo count($roles ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="100, 100" stroke="#28a745" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">100%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-user-tag"></i></div>
                    <div class="card-content">
                        <h3>Roles Editables</h3>
                        <p class="card-value"><?php echo count(array_filter($roles ?? [], function($r) { 
                            return !in_array(strtolower($r['nombre_rol'] ?? ''), ['superusuario', 'administrador', 'cliente']); 
                        })); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="75, 100" stroke="#ffc107" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">75%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roles Section -->
            <div class="roles-section">
                <div class="section-header">
                    <h2>Gestión de Roles</h2>
                    <div class="section-actions">
                        <button class="btn-add-rol" onclick="openModal('registrar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Rol
                        </button>
                    </div>
                </div>

                <!-- Tabla de Roles -->
                <div class="table-container">
                    <table class="roles-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $rol): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rol['id_rol'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($rol['nombre_rol'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $nombreRol = strtolower($rol['nombre_rol'] ?? '');
                                            $esProtegido = in_array($nombreRol, ['superusuario', 'administrador', 'cliente']);
                                            ?>
                                            <span class="status-badge <?php echo $esProtegido ? 'protected' : 'editable'; ?>">
                                                <?php echo $esProtegido ? 'Protegido' : 'Editable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$esProtegido): ?>
                                                <button class="btn-action btn-permisos" onclick="gestionarPermisos(<?php echo $rol['id_rol']; ?>)">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button class="btn-action btn-edit" onclick="editRol(<?php echo $rol['id_rol']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action btn-delete" onclick="deleteRol(<?php echo $rol['id_rol']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-action btn-permisos" onclick="gestionarPermisos(<?php echo $rol['id_rol']; ?>)">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <span class="btn-action btn-disabled" title="Rol protegido">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-users-cog" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay roles registrados</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Rol -->
            <div id="rolModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Agregar Rol</h2>
                                <p>Complete los datos para registrar un nuevo rol</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="rolForm">
                            <input type="hidden" name="accion" value="registrar">
                            <input type="hidden" id="rolId" name="id_rol">
                            
                            <div class="form-group">
                                <label for="nombre_rol">
                                    <i class="fas fa-tag"></i>
                                    Nombre del Rol*
                                </label>
                                <input type="text" id="nombre_rol" name="nombre_rol" required 
                                       placeholder="Ej: Gerente" maxlength="15">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" onclick="saveRol()">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Roles */
                .roles-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .roles-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .roles-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .roles-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .roles-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .roles-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .status-badge {
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .status-badge.protected {
                    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                    color: white;
                }

                .status-badge.editable {
                    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
                    color: white;
                }

                .btn-add-rol {
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
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-add-rol:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
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

                .btn-permisos {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-edit {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-delete {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-disabled {
                    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                    color: white;
                    cursor: not-allowed;
                    opacity: 0.7;
                }

                .btn-action:hover:not(.btn-disabled) {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }

                /* Modal styles - reusando estilos de otros módulos */
                .modal-header-content {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .modal-icon {
                    width: 50px;
                    height: 50px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .modal-icon i {
                    font-size: 1.5rem;
                    color: white;
                }

                .modal-title-content h2 {
                    font-size: 1.4rem;
                    font-weight: 700;
                    margin: 0 0 5px 0;
                }

                .modal-title-content p {
                    font-size: 0.85rem;
                    opacity: 0.9;
                    margin: 0;
                }

                .form-group {
                    margin-bottom: 15px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #2d3748;
                }

                .form-group label i {
                    color: #2196F3;
                    margin-right: 8px;
                }

                .form-group input {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .form-group input:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .btn-cancel, .btn-save {
                    padding: 12px 25px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-cancel {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                }

                .btn-cancel:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
                }

                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-save:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
                }
            </style>

            <script>
                function openModal(type) {
                    if (type === 'registrar') {
                        document.getElementById('modalTitle').textContent = 'Agregar Rol';
                        document.getElementById('rolForm').reset();
                        document.getElementById('rolId').value = '';
                    }
                    document.getElementById('rolModal').style.display = 'block';
                }

                function closeModal() {
                    document.getElementById('rolModal').style.display = 'none';
                }

                function saveRol() {
                    const form = document.getElementById('rolForm');
                    const formData = new FormData(form);
                    
                    if (!formData.get('nombre_rol')) {
                        alert('Por favor, ingrese el nombre del rol');
                        return;
                    }
                    
                    // Enviar al backend
                    fetch('?pagina=rol', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            alert('Rol registrado correctamente');
                            closeModal();
                            location.reload();
                        } else {
                            alert('Error al registrar rol: ' + (data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al registrar rol. Por favor, intente nuevamente.');
                    });
                }

                function gestionarPermisos(id) {
                    window.location.href = '?pagina=permiso';
                }

                function editRol(id) {
                    alert('Función para editar rol ID: ' + id + '\n(conectar con backend)');
                }

                function deleteRol(id) {
                    if (confirm('¿Está seguro de eliminar este rol?')) {
                        alert('Función para eliminar rol ID: ' + id + '\n(conectar con backend)');
                    }
                }

                // Event listeners
                document.querySelector('#rolModal .close-modal').addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === document.getElementById('rolModal')) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>