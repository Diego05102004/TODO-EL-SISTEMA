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
        error_log("Error al generar JWT en permiso: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'permiso';
$titulo_pagina = 'Gestión de Permisos';

// Filtrar roles excluyendo administrador, superusuario y cliente
$excluidos = ['administrador', 'superusuario', 'cliente'];
$rolesSinSuper = array_filter($roles ?? [], function ($rol) use ($excluidos) {
    return !in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'] ?? '')), $excluidos);
});
$rolesSinSuper = array_values($rolesSinSuper);

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Permisos Section -->
            <div class="permisos-section">
                <div class="section-header">
                    <h2><i class="fas fa-shield-alt"></i> Gestión de Permisos por Rol</h2>
                    <div class="section-actions">
                        <button class="btn-save-all" onclick="guardarTodosPermisos()">
                            <span class="btn-icon"><i class="fas fa-save"></i></span>
                            Guardar Cambios
                        </button>
                    </div>
                </div>

                <!-- Selector de Rol -->
                <div class="role-selector">
                    <label for="selectorRol">
                        <i class="fas fa-user-tag"></i>
                        Seleccionar Rol:
                    </label>
                    <select id="selectorRol" class="role-select">
                        <?php foreach ($rolesSinSuper as $i => $rol): ?>
                            <option value="<?= $rol['id_rol'] ?>" <?= $i===0 ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tablas de Permisos -->
                <div class="permisos-tables-container">
                    <?php 
                    $acciones = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
                    foreach ($rolesSinSuper as $i => $rol): ?>
                        <div class="permiso-tabla-rol <?= $i===0 ? 'active' : '' ?>" id="tabla-rol-<?= $rol['id_rol'] ?>">
                            <h3 class="rol-title">
                                <i class="fas fa-user-shield"></i>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </h3>
                            <div class="table-responsive">
                                <table class="permisos-table">
                                    <thead>
                                        <tr>
                                            <th>Módulo</th>
                                            <?php foreach ($acciones as $accion): ?>
                                                <th><?= ucfirst($accion) ?></th>
                                            <?php endforeach; ?>
                                            <th>Todos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modulos_permiso ?? [] as $modulo): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($modulo['nombre_modulo'] ?? '') ?></td>
                                                <?php foreach ($acciones as $accion): ?>
                                                    <td>
                                                        <input type="checkbox" 
                                                               class="permiso-checkbox"
                                                               data-rol="<?= $rol['id_rol'] ?>"
                                                               data-modulo="<?= $modulo['id_modulo'] ?? '' ?>"
                                                               data-accion="<?= $accion ?>"
                                                               <?= isset($permisosUsuario[$rol['id_rol']][$modulo['id_modulo'] ?? []][$accion]) && $permisosUsuario[$rol['id_rol']][$modulo['id_modulo'] ?? []][$accion] ? 'checked' : '' ?>>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <input type="checkbox" 
                                                           class="seleccionar-todos-modulo"
                                                           data-rol="<?= $rol['id_rol'] ?>"
                                                           data-modulo="<?= $modulo['id_modulo'] ?? '' ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <style>
                /* Estilos de Permisos */
                .permisos-section {
                    margin-top: 30px;
                }

                .role-selector {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                    margin-bottom: 30px;
                }

                .role-selector label {
                    display: block;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 10px;
                }

                .role-selector label i {
                    color: #2196F3;
                    margin-right: 8px;
                }

                .role-select {
                    width: 100%;
                    max-width: 400px;
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .role-select:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .btn-save-all {
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-save-all:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .permisos-tables-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .permiso-tabla-rol {
                    display: none;
                }

                .permiso-tabla-rol.active {
                    display: block;
                }

                .rol-title {
                    font-size: 1.3rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .rol-title i {
                    color: #2196F3;
                }

                .table-responsive {
                    overflow-x: auto;
                }

                .permisos-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 600px;
                }

                .permisos-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .permisos-table th {
                    padding: 12px;
                    text-align: center;
                    font-weight: 600;
                    font-size: 0.85rem;
                }

                .permisos-table td {
                    padding: 12px;
                    border-bottom: 1px solid #e9ecef;
                    text-align: center;
                }

                .permisos-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .permisos-table input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    cursor: pointer;
                    accent-color: #2196F3;
                }

                .permisos-table tbody tr td:first-child {
                    text-align: left;
                    font-weight: 600;
                    color: #2d3748;
                }
            </style>

            <script>
                // Cambiar tabla al seleccionar rol
                document.getElementById('selectorRol').addEventListener('change', function() {
                    const rolId = this.value;
                    
                    // Ocultar todas las tablas
                    document.querySelectorAll('.permiso-tabla-rol').forEach(tabla => {
                        tabla.classList.remove('active');
                    });
                    
                    // Mostrar la tabla seleccionada
                    const tablaSeleccionada = document.getElementById('tabla-rol-' + rolId);
                    if (tablaSeleccionada) {
                        tablaSeleccionada.classList.add('active');
                    }
                });

                // Seleccionar todos los permisos de un módulo
                document.querySelectorAll('.seleccionar-todos-modulo').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const rol = this.dataset.rol;
                        const modulo = this.dataset.modulo;
                        const checked = this.checked;
                        
                        document.querySelectorAll(`.permiso-checkbox[data-rol="${rol}"][data-modulo="${modulo}"]`).forEach(cb => {
                            cb.checked = checked;
                        });
                    });
                });

                function guardarTodosPermisos() {
                    alert('Función para guardar todos los permisos (conectar con backend)');
                }
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>