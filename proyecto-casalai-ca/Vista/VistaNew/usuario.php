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
        error_log("Error al generar JWT en usuario: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'usuario';
$titulo_pagina = 'Gestión de Usuarios';

// Iniciar el buffer de contenido
ob_start();

// Cálculos previos para summary cards
$total_usuarios = count($usuarios ?? []);
$usuarios_activos = count($usuariosHabilitados ?? []);
$usuarios_inactivos = count($usuariosDeshabilitados ?? []);

// Calcular administradores (usuarios con rol de administrador)
$administradores = count(array_filter($usuarios ?? [], function($u) {
    return (isset($u['nombre_rol']) && stripos($u['nombre_rol'], 'admin') !== false) ||
           (isset($u['id_rol']) && (int)$u['id_rol'] === 1);
}));

$totalHabilitados = is_array($usuariosHabilitados ?? null) ? count($usuariosHabilitados) : 0;
$totalInhabilitados = is_array($usuariosDeshabilitados ?? null) ? count($usuariosDeshabilitados) : 0;

// Calcular porcentajes
$porcentaje_total = 100;
$porcentaje_activos = $total_usuarios > 0 ? round(($usuarios_activos / $total_usuarios) * 100) : 0;
$porcentaje_inactivos = $total_usuarios > 0 ? round(($usuarios_inactivos / $total_usuarios) * 100) : 0;
$porcentaje_admin = $total_usuarios > 0 ? round(($administradores / $total_usuarios) * 100) : 0;
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<link rel="stylesheet" href="assets/styles/cards.css?v=<?php echo filemtime(__DIR__ . '/../../assets/styles/cards.css'); ?>">

<!-- Summary Cards para Usuarios -->
<div class="summary-cards">
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-users"></i></div>
        <div class="card-content">
            <h3>Total Usuarios</h3>
            <div class="card-data">
                <p class="card-value"><?php echo $total_usuarios; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentaje_total; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentaje_total; ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-user-check"></i></div>
        <div class="card-content">
            <h3>Usuarios Habilitados</h3>
            <div class="card-data">
                <p class="card-value"><?php echo number_format($totalHabilitados); ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentaje_activos; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentaje_activos; ?>%</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-user-slash"></i></div>
        <div class="card-content">
            <h3>Usuarios Inhabilitados</h3>
            <div class="card-data">
                <p class="card-value"><?php echo number_format($totalInhabilitados); ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentaje_inactivos; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentaje_inactivos; ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Usuarios Section -->
<div class="table-section">
    <div class="section-header">
        <h2>Lista de Usuarios</h2>
        <div class="filters-section">
            <div class="filter-options">
                <select id="roleFilter" class="select-filter" onchange="filterByRole()">
                    <option value="">Todos los roles</option>
                    <option value="admin">Administrador</option>
                    <option value="gerente">Gerente</option>
                    <option value="vendedor">Vendedor</option>
                    <option value="almacen">Almacenista</option>
                </select>
            </div>
            <div class="filter-options">
                <select id="statusFilter" class="select-filter" onchange="filterByStatus()">
                    <option value="">Todos los estados</option>
                    <option value="active">Habilitados</option>
                    <option value="inactive">Inhabilitados</option>
                </select>
            </div>
        </div>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button class="btn-table-superior btn-incluir" title="Incluir Usuarios" onclick="openModal('agregar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <table class="table-info" data-datatable data-page-length="10" data-datatable-order='[[0, "desc"]]' data-datatable-no-order="4" data-datatable-no-search="4">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Nombre Completo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $usuario): ?>
                <tr data-id="<?php echo (int)($usuario['id_usuario'] ?? 0); ?>">
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars($usuario['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="role-badge <?php echo strtolower(str_replace(' ', '', $usuario['nombre_rol'] ?? '')); ?>">
                            <?php echo htmlspecialchars($usuario['nombre_rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status <?php echo (strtolower($usuario['estatus'] ?? '') === 'habilitado') ? 'active' : 'inactive'; ?>"
                                <?php if (strtolower($usuario['nombre_rol'] ?? '') !== 'superusuario'): ?>
                                onclick="toggleUserStatus(<?php echo (int)($usuario['id_usuario'] ?? 0); ?>, '<?php echo htmlspecialchars($usuario['estatus'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')"
                                title="Click para cambiar estado"
                                <?php endif; ?>
                                <?php if (strtolower($usuario['nombre_rol'] ?? '') === 'superusuario'): ?>
                                style="cursor: not-allowed; opacity: 0.6;"
                                <?php endif; ?>>
                            <?php echo htmlspecialchars($usuario['estatus'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <?php if (strtolower($usuario['nombre_rol'] ?? '') !== 'superusuario'): ?>
                            <button class="btn-action btn-detallar" 
                                title="Detallar Usuario"
                                onclick="viewUser(<?php echo (int)($usuario['id_usuario'] ?? 0); ?>)">
                                <img src="assets/img/eye.svg">
                            </button>
                            <button class="btn-action btn-modificar"
                                title="Modificar Usuario"
                                onclick="openModal('editar', <?php echo (int)($usuario['id_usuario'] ?? 0); ?>)">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button class="btn-action btn-eliminar" 
                                title="Eliminar Usuario"
                                onclick="deleteUser(<?php echo (int)($usuario['id_usuario'] ?? 0); ?>)">
                                <img src="assets/img/circle-x.svg">
                            </button>
                        <?php else: ?>
                            <span style="color: #999; font-size: 0.9rem;">NO DISPONIBLE</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        No hay usuarios registrados
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

            <!-- Modal para Agregar/Editar Usuario -->
            <div id="userModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Usuario</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="userId" name="id_usuario">

                            <div class="form-group">
                                <label for="nombre_usuario">Nombre de Usuario*</label>
                                <input type="text" id="nombre_usuario" name="nombre_usuario" required
                                       placeholder="Ej: jperrez" maxlength="20">
                                <small class="field-error" data-error-for="nombre_usuario"></small>
                            </div>

                            <div class="form-group">
                                <label for="nombre">Nombre*</label>
                                <input type="text" id="nombre" name="nombre" required
                                       placeholder="Ej: Juan" maxlength="50">
                                <small class="field-error" data-error-for="nombre"></small>
                            </div>

                            <div class="form-group">
                                <label for="apellido_usuario">Apellido*</label>
                                <input type="text" id="apellido_usuario" name="apellido_usuario" required
                                       placeholder="Ej: Pérez" maxlength="50">
                                <small class="field-error" data-error-for="apellido_usuario"></small>
                            </div>

                            <div class="form-group">
                                <label for="correo_usuario">Email*</label>
                                <input type="email" id="correo_usuario" name="correo_usuario" required
                                       placeholder="juan.perez@casalai.com" maxlength="50">
                                <small class="field-error" data-error-for="correo_usuario"></small>
                            </div>

                            <div class="form-group">
                                <label for="cedula">Cédula*</label>
                                <input type="text" id="cedula" name="cedula" required
                                       placeholder="12.345.678" maxlength="10">
                                <small class="field-error" data-error-for="cedula"></small>
                            </div>

                            <div class="form-group">
                                <label for="telefono_usuario">Teléfono*</label>
                                <input type="text" id="telefono_usuario" name="telefono_usuario" required
                                       placeholder="0414-123-4567" maxlength="13">
                                <small class="field-error" data-error-for="telefono_usuario"></small>
                            </div>

                            <div class="form-group">
                                <label for="rango">Rol*</label>
                                <select id="rango" name="rango" required>
                                    <option value="">Seleccione rol</option>
                                    <?php
                                    if (!empty($selecionarRol)) {
                                        foreach ($selecionarRol as $rol) {
                                            if($rol['nombre_rol'] != 'SuperUsuario' && $rol['nombre_rol'] != 'Cliente') {
                                                echo '<option value="' . $rol['id_rol'] . '">' . htmlspecialchars($rol['nombre_rol']) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <small class="field-error" data-error-for="rango"></small>
                            </div>

                            <div class="form-group" id="passwordGroup">
                                <label for="clave_usuario">Contraseña*</label>
                                <input type="password" id="clave_usuario" name="clave_usuario" required
                                       placeholder="Mínimo 6 caracteres" maxlength="15">
                                <small class="field-error" data-error-for="clave_usuario"></small>
                            </div>

                            <div class="form-group" id="confirmPasswordGroup">
                                <label for="clave_confirmar">Confirmar Contraseña*</label>
                                <input type="password" id="clave_confirmar" name="clave_confirmar" required
                                       placeholder="Repetir contraseña" maxlength="15">
                                <small class="field-error" data-error-for="clave_confirmar"></small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveUser()">Guardar</button>
                    </div>
                </div>
            </div>

            <div id="userDetailsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Detalles del usuario</h2>
                        <span class="close-modal" id="closeUserDetails">&times;</span>
                    </div>
                    <div class="modal-body">
                        <dl class="user-details-grid">
                            <div><dt>Usuario</dt><dd id="detailUsername">-</dd></div>
                            <div><dt>Nombre</dt><dd id="detailFirstName">-</dd></div>
                            <div><dt>Apellido</dt><dd id="detailLastName">-</dd></div>
                            <div><dt>Correo</dt><dd id="detailEmail">-</dd></div>
                            <div><dt>Cédula</dt><dd id="detailCedula">-</dd></div>
                            <div><dt>Teléfono</dt><dd id="detailPhone">-</dd></div>
                            <div><dt>Rol</dt><dd id="detailRole">-</dd></div>
                            <div><dt>Estado</dt><dd id="detailStatus">-</dd></div>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" type="button" onclick="closeUserDetailsModal()">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                .user-details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 16px;
                    margin: 0;
                }

                .user-details-grid div {
                    min-width: 0;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }

                .user-details-grid dt {
                    color: #667085;
                    font-size: 0.85rem;
                    margin-bottom: 4px;
                }

                .user-details-grid dd {
                    margin: 0;
                    color: #222;
                    overflow-wrap: anywhere;
                }

                .btn-view {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }

                .btn-edit {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .btn-delete {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                }

                @media (max-width: 768px) {
                    .filters-section {
                        flex-direction: column;
                    }
                    
                    .filter-options {
                        flex-direction: column;
                        width: 100%;
                    }
                    
                    .filter-options select {
                        width: 100%;
                    }
                    
                    .users-table {
                        font-size: 0.85rem;
                    }

                    .user-details-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .users-table th, .users-table td {
                        padding: 10px;
                    }
                }
            </style>

            <script>
                const userModal = document.getElementById('userModal');
                const userDetailsModal = document.getElementById('userDetailsModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#userModal .close-modal');
                const closeUserDetailsBtn = document.getElementById('closeUserDetails');
                const userForm = document.getElementById('userForm');
                const userFields = Array.from(userForm.querySelectorAll('[name]'));

                function clearUserValidation() {
                    document.querySelectorAll('#userForm .field-error').forEach((field) => {
                        field.textContent = '';
                    });
                }

                function validateUserForm() {
                    const values = Object.fromEntries(new FormData(userForm).entries());
                    values.nombre = (values.nombre || '').replace(/\s{2,}/g, ' ').trim();
                    values.apellido_usuario = (values.apellido_usuario || '').replace(/\s{2,}/g, ' ').trim();
                    values.cedula = (values.cedula || '').trim();
                    values.telefono_usuario = (values.telefono_usuario || '').trim();
                    values.correo_usuario = (values.correo_usuario || '').trim();
                    userForm.elements.nombre.value = values.nombre;
                    userForm.elements.apellido_usuario.value = values.apellido_usuario;
                    userForm.elements.cedula.value = values.cedula;
                    userForm.elements.telefono_usuario.value = values.telefono_usuario;
                    userForm.elements.correo_usuario.value = values.correo_usuario;

                    const isEditing = Boolean(values.id_usuario);
                    const rules = [
                        { field: 'nombre', pattern: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/, message: 'Ingrese un nombre de 2 a 50 caracteres, solo con letras y espacios.' },
                        { field: 'apellido_usuario', pattern: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/, message: 'Ingrese un apellido de 2 a 50 caracteres, solo con letras y espacios.' },
                        { field: 'cedula', pattern: /^(?:\d{1,2}\.\d{3}\.\d{3})$/, message: 'Use el formato de cédula 1.234.567 o 12.345.678.' },
                        { field: 'telefono_usuario', pattern: /^\d{4}-\d{3}-\d{4}$/, message: 'Use el formato telefónico 0400-000-0000.' },
                        { field: 'correo_usuario', pattern: /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/, message: 'Use un correo de Gmail, Outlook, Yahoo o iCloud.' },
                        { field: 'nombre_usuario', pattern: /^[a-zA-Z0-9_]{3,20}$/, message: 'El nombre de usuario debe tener entre 3 y 20 caracteres alfanuméricos.' }
                    ];

                    // Solo validar contraseña si es nuevo usuario
                    if (!isEditing) {
                        rules.push(
                            { field: 'clave_usuario', pattern: /^.{6,15}$/, message: 'La contraseña debe tener entre 6 y 15 caracteres.' },
                            { field: 'clave_confirmar', pattern: /^.{6,15}$/, message: 'La confirmación debe tener entre 6 y 15 caracteres.' }
                        );
                    }

                    const errors = {};
                    clearUserValidation();

                    rules.forEach(({ field, pattern, message }) => {
                        if (values[field] && !pattern.test(values[field])) {
                            errors[field] = message;
                            const errorElement = document.querySelector(`[data-error-for="${field}"]`);
                            if (errorElement) errorElement.textContent = message;
                        }
                    });

                    // Validar que las contraseñas coincidan
                    if (!isEditing && values.clave_usuario !== values.clave_confirmar) {
                        errors['clave_confirmar'] = 'Las contraseñas no coinciden';
                        const errorElement = document.querySelector('[data-error-for="clave_confirmar"]');
                        if (errorElement) errorElement.textContent = 'Las contraseñas no coinciden';
                    }

                    return { values, errors };
                }

                const userDebug = { logs: [] };
                window.userDebug = userDebug;

                function debugUserLog(label, payload) {
                    const entry = { label, payload, timestamp: new Date().toISOString() };
                    userDebug.logs.push(entry);
                    console.debug('[Usuario debug]', label, payload);
                }

                async function showUserError(title, message) {
                    return Swal.fire({ icon: 'error', title, text: message, confirmButtonText: 'Entendido' });
                }

                userForm.elements.cedula.addEventListener('input', function() {
                    const digits = this.value.replace(/\D/g, '').slice(0, 8);
                    if (digits.length === 7) this.value = `${digits.slice(0, 1)}.${digits.slice(1, 4)}.${digits.slice(4)}`;
                    else if (digits.length === 8) this.value = `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
                    else this.value = digits;
                });

                userForm.elements.telefono_usuario.addEventListener('input', function() {
                    const digits = this.value.replace(/\D/g, '').slice(0, 11);
                    if (digits.length > 7) this.value = `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
                    else if (digits.length > 4) this.value = `${digits.slice(0, 4)}-${digits.slice(4)}`;
                    else this.value = digits;
                });

                userFields.forEach((field) => field.addEventListener('input', () => {
                    const error = document.querySelector(`[data-error-for="${field.name}"]`);
                    if (error) error.textContent = '';
                }));

                async function openModal(type, userId = null) {
                    if (type === 'agregar') {
                        modalTitle.textContent = 'Agregar Usuario';
                        userForm.reset();
                        document.getElementById('userId').value = '';
                        document.getElementById('passwordGroup').style.display = 'block';
                        document.getElementById('confirmPasswordGroup').style.display = 'block';
                        clearUserValidation();
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Usuario';
                        const usuario = await fetchUser(userId);
                        if (!usuario) return;
                        userForm.reset();
                        clearUserValidation();
                        document.getElementById('userId').value = usuario.id_usuario;
                        document.getElementById('nombre_usuario').value = usuario.username || '';
                        document.getElementById('nombre').value = usuario.nombres || '';
                        document.getElementById('apellido_usuario').value = usuario.apellidos || '';
                        document.getElementById('correo_usuario').value = usuario.correo || '';
                        document.getElementById('cedula').value = usuario.cedula || '';
                        document.getElementById('telefono_usuario').value = usuario.telefono || '';
                        document.getElementById('rango').value = usuario.id_rol || '';
                        // Ocultar campos de contraseña al editar
                        document.getElementById('passwordGroup').style.display = 'none';
                        document.getElementById('confirmPasswordGroup').style.display = 'none';
                    }
                    userModal.style.display = 'block';
                }

                async function postUserAction(action, values = {}) {
                    const formData = new FormData();
                    formData.append('accion', action);
                    Object.entries(values).forEach(([key, value]) => formData.append(key, value));
                    debugUserLog('POST usuario', { action, values });
                    const response = await fetch(window.location.href, { method: 'POST', body: formData });
                    const result = await response.json();
                    debugUserLog('Respuesta usuario', { action, status: response.status, result });
                    return result;
                }

                async function fetchUser(userId) {
                    try {
                        const result = await postUserAction('obtener_usuario', { id_usuario: userId });
                        if (result.status === 'error') throw new Error(result.message || 'No se pudo obtener el usuario.');
                        return result;
                    } catch (error) {
                        await showUserError('No se pudo consultar el usuario', error.message || 'Intente nuevamente.');
                        return null;
                    }
                }

                function userDetailValue(value) {
                    const text = String(value ?? '').trim();
                    if (!text) return '-';

                    if (/^[A-Za-z0-9+/]+={0,2}$/.test(text) && text.length > 24) {
                        try {
                            const decoded = atob(text);
                            const keyLength = decoded.length >= 4
                                ? ((decoded.charCodeAt(0) << 24) | (decoded.charCodeAt(1) << 16) | (decoded.charCodeAt(2) << 8) | decoded.charCodeAt(3)) >>> 0
                                : 0;
                            if (keyLength === 256) return 'Dato cifrado no disponible';
                        } catch (error) {
                            return text;
                        }
                    }

                    return text;
                }

                async function viewUser(userId) {
                    const usuario = await fetchUser(userId);
                    if (!usuario) return;

                    document.getElementById('detailUsername').textContent = userDetailValue(usuario.username);
                    document.getElementById('detailFirstName').textContent = userDetailValue(usuario.nombres);
                    document.getElementById('detailLastName').textContent = userDetailValue(usuario.apellidos);
                    document.getElementById('detailEmail').textContent = userDetailValue(usuario.correo);
                    document.getElementById('detailCedula').textContent = userDetailValue(usuario.cedula);
                    document.getElementById('detailPhone').textContent = userDetailValue(usuario.telefono);
                    document.getElementById('detailRole').textContent = userDetailValue(usuario.nombre_rol);
                    document.getElementById('detailStatus').textContent = userDetailValue(usuario.estatus);
                    userDetailsModal.style.display = 'block';
                }

                window.viewUser = viewUser;

                function closeUserDetailsModal() {
                    userDetailsModal.style.display = 'none';
                }

                function closeModal() {
                    userModal.style.display = 'none';
                }

                async function saveUser() {
                    const { values, errors } = validateUserForm();
                    if (Object.keys(errors).length) {
                        const firstInvalidField = userForm.elements[Object.keys(errors)[0]];
                        await Swal.fire({
                            icon: 'error',
                            title: 'Revisa los datos del usuario',
                            html: `<ul style="text-align:left;margin:0;padding-left:20px">${Object.values(errors).map((message) => `<li>${message}</li>`).join('')}</ul>`,
                            confirmButtonText: 'Entendido'
                        });
                        firstInvalidField.focus();
                        return;
                    }
                    const isEditing = Boolean(values.id_usuario);
                    try {
                        const result = await postUserAction(isEditing ? 'modificar' : 'registrar', values);
                        if (result.status !== 'success') {
                            throw new Error((result.errors ? Object.values(result.errors).join('\n') : '') || result.message || 'No se pudo guardar el usuario.');
                        }
                        closeModal();
                        await Swal.fire({
                            icon: 'success',
                            title: isEditing ? 'Usuario modificado' : 'Usuario registrado',
                            text: result.message || 'La operación se completó correctamente',
                            confirmButtonText: 'Entendido'
                        });
                        window.location.reload();
                    } catch (error) {
                        await showUserError('No se pudo guardar el usuario', error.message || 'Intente nuevamente.');
                    }
                }

                async function deleteUser(userId) {
                    const confirmation = await Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar este usuario?',
                        text: 'Esta acción no se puede deshacer.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        confirmButtonColor: '#dc3545'
                    });
                    if (!confirmation.isConfirmed) return;

                    try {
                        const result = await postUserAction('eliminar', { id_usuario: userId });
                        if (result.status !== 'success') {
                            throw new Error((result.errors ? Object.values(result.errors).join('\n') : '') || result.message || 'No se pudo eliminar el usuario.');
                        }
                        await Swal.fire({ icon: 'success', title: 'Usuario eliminado', text: result.message || 'El usuario se eliminó correctamente.' });
                        window.location.reload();
                    } catch (error) {
                        await showUserError('No se pudo eliminar el usuario', error.message || 'Intente nuevamente.');
                    }
                }

                // Función para cambiar el estado del usuario
                async function toggleUserStatus(userId, currentStatus) {
                    const nextStatus = currentStatus === 'habilitado' ? 'inhabilitado' : 'habilitado';
                    const confirmation = await Swal.fire({
                        icon: 'question',
                        title: '¿Cambiar estado del usuario?',
                        text: currentStatus === 'habilitado' ? 'El usuario será deshabilitado' : 'El usuario será habilitado',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        confirmButtonColor: '#2196F3'
                    });

                    if (!confirmation.isConfirmed) return;

                    try {
                        const formData = new FormData();
                        formData.append('accion', 'cambiar_estatus');
                        formData.append('id_usuario', userId);
                        formData.append('nuevo_estatus', nextStatus);

                        debugUserLog('toggleUserStatus', { userId, currentStatus, nextStatus });

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        debugUserLog('toggleUserStatus respuesta', { userId, status: response.status, result });

                        if (result.status === 'success') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Estado cambiado',
                                text: result.message || 'El estado del usuario se actualizó correctamente.',
                                confirmButtonText: 'Entendido'
                            });
                            window.location.reload();
                        } else {
                            throw new Error(result.message || 'No se pudo cambiar el estado');
                        }
                    } catch (error) {
                        debugUserLog('toggleUserStatus error', { userId, error: error.message });
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo cambiar el estado del usuario',
                            confirmButtonText: 'Entendido'
                        });
                    }
                }

                function applyUserTableFilters() {
                    const searchInput = document.getElementById('searchUser');
                    const roleFilter = document.getElementById('roleFilter');
                    const statusFilter = document.getElementById('statusFilter');

                    const query = (searchInput?.value || '').trim().toLowerCase();
                    const roleValue = (roleFilter?.value || '').trim().toLowerCase();
                    const statusValue = (statusFilter?.value || '').trim().toLowerCase();

                    const rows = document.querySelectorAll('.users-table tbody tr[data-id]');
                    rows.forEach((row) => {
                        const text = (row.textContent || '').toLowerCase();
                        const roleText = (row.querySelector('.role-badge')?.textContent || '').trim().toLowerCase();
                        const statusText = (row.querySelector('.status')?.textContent || '').trim().toLowerCase();

                        const matchesSearch = !query || text.includes(query);
                        const matchesRole = !roleValue || roleText.includes(roleValue);
                        const matchesStatus = !statusValue || (
                            statusValue === 'active' ? statusText.includes('habilitado') :
                            statusValue === 'inactive' ? statusText.includes('inhabilitado') :
                            true
                        );

                        row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
                    });
                }

                function searchUsers() {
                    applyUserTableFilters();
                }

                function filterByRole() {
                    applyUserTableFilters();
                }

                function filterByStatus() {
                    applyUserTableFilters();
                }

                function importUsers() {
                    debugUserLog('importUsers', { message: 'Función de importación pendiente por implementar' });
                    Swal.fire({
                        icon: 'info',
                        title: 'Importación pendiente',
                        text: 'La importación de usuarios aún no está implementada.',
                        confirmButtonText: 'Entendido'
                    });
                }

                function exportUsers() {
                    debugUserLog('exportUsers inicio', { rows: document.querySelectorAll('.users-table tbody tr[data-id]').length });
                    const rows = Array.from(document.querySelectorAll('.users-table tbody tr[data-id]'));
                    const visibleRows = rows.filter((row) => row.style.display !== 'none');
                    const data = [['Usuario', 'Nombre', 'Email', 'Rol', 'Estado']];
                    visibleRows.forEach((row) => {
                        const cells = row.cells;
                        data.push([
                            cells[0].querySelector('.username')?.textContent.trim() || '',
                            cells[1].textContent.trim(),
                            cells[2].textContent.trim(),
                            cells[3].textContent.trim(),
                            cells[4].textContent.trim()
                        ]);
                    });
                    const csv = data.map((row) => row.map((value) => `"${String(value ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
                    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'usuarios.csv';
                    link.click();
                    URL.revokeObjectURL(url);
                    debugUserLog('exportUsers fin', { total: visibleRows.length });
                }

                // Event listeners para cerrar modal
                closeModalBtn.addEventListener('click', closeModal);
                closeUserDetailsBtn.addEventListener('click', closeUserDetailsModal);

                window.addEventListener('click', function(event) {
                    if (event.target === userModal) {
                        closeModal();
                    }
                    if (event.target === userDetailsModal) {
                        closeUserDetailsModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>