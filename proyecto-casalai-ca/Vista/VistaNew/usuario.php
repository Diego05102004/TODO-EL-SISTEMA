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
$porcentaje_activos = $total_usuarios > 0 ? round(($usuarios_activos / $total_usuarios) * 100) : 0;
$porcentaje_inactivos = $total_usuarios > 0 ? round(($usuarios_inactivos / $total_usuarios) * 100) : 0;
$porcentaje_admin = $total_usuarios > 0 ? round(($administradores / $total_usuarios) * 100) : 0;

$rolesUsuarios = [];
foreach ($usuarios ?? [] as $usuario) {
    $nombreRol = trim((string)($usuario['nombre_rol'] ?? ''));
    if ($nombreRol !== '') {
        $rolesUsuarios[] = $nombreRol;
    }
}
$rolesUsuarios = array_values(array_unique($rolesUsuarios, SORT_FLAG_CASE | SORT_STRING));
sort($rolesUsuarios, SORT_NATURAL | SORT_FLAG_CASE);
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script defer src="assets/javascript/usuario.js?v=<?php echo filemtime(__DIR__ . '/../../assets/javascript/usuario.js'); ?>"></script>
<link rel="stylesheet" href="assets/styles/cards.css?v=<?php echo filemtime(__DIR__ . '/../../assets/styles/cards.css'); ?>">

<!-- Summary Cards para Usuarios -->
<div class="summary-cards">
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-users"></i></div>
        <div class="card-content">
            <h3>Total Usuarios</h3>
            <div class="card-data">
                <p class="card-value" id="totalUsersCount"><?php echo $total_usuarios; ?></p>
            </div>
        </div>
    </div>

    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-user-check"></i></div>
        <div class="card-content">
            <h3>Usuarios Habilitados</h3>
            <div class="card-data">
                <p class="card-value" id="activeUsersCount"><?php echo number_format($totalHabilitados); ?></p>
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
                <p class="card-value" id="inactiveUsersCount"><?php echo number_format($totalInhabilitados); ?></p>
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
                    <?php foreach ($rolesUsuarios as $nombreRol): ?>
                        <option value="<?php echo htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-options">
                <select id="filtro-estatus" class="select-filter">
                    <option value="">Todos los estados</option>
                    <option value="active">Habilitado</option>
                    <option value="inactive">Inhabilitado</option>
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
                                data-user-action="status"
                                title="Click para cambiar estado"
                                <?php endif; ?>
                                <?php if (strtolower($usuario['nombre_rol'] ?? '') === 'superusuario'): ?>
                                style="cursor: not-allowed; opacity: 0.6;"
                                <?php endif; ?>>
                            <?php echo htmlspecialchars(ucfirst(strtolower($usuario['estatus'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <?php if (strtolower($usuario['nombre_rol'] ?? '') !== 'superusuario'): ?>
                            <button class="btn-action btn-detallar" 
                                title="Detallar Usuario"
                                data-user-action="details">
                                <img src="assets/img/eye.svg">
                            </button>
                            <button class="btn-action btn-modificar"
                                title="Modificar Usuario"
                                data-user-action="edit">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button class="btn-action btn-eliminar" 
                                title="Eliminar Usuario"
                                data-user-action="delete">
                                <img src="assets/img/circle-x.svg">
                            </button>
                        <?php else: ?>
                            <span style="color: #999; font-size: 0.9rem;">NO DISPONIBLE</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Agregar/Editar Usuario -->
<div id="userModal" class="modal modal-container">
    <div class="modal-broad">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Incluir Usuarios</h2>
                    <p>Complete los datos para incluir un nuevo usuario</p>
                </div>
                <button type="button" class="btn-help blue" title="Ayuda para Incluir Usuarios" data-contexto="registrar">
                    <img src="assets/img/info-ayuda.svg">
                </button>
            </div>
        </div>
        
        <div class="modal-body">
            <form id="userForm" enctype="multipart/form-data" novalidate>
                <div class="form-section">
                    <div class="section-title">
                            <i class="fas fa-info-circle"></i>
                            <h3>Información</h3>
                        </div>
                    <input type="hidden" id="userId" name="id_usuario">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_usuario">Nombre de Usuario*</label>
                            <input type="text" class="input-textnum" id="nombre_usuario" name="nombre_usuario" placeholder="Ingrese un nombre de usuario" maxlength="20">
                            <span class="span-value" id="snombre_usuario"></span>
                        </div>

                        <div class="form-group">
                            <label for="nombre">Nombre(s)*</label>
                            <input type="text" class="input-textnum" id="nombre" name="nombre" placeholder="Ingrese su nombre" maxlength="50">
                            <span class="span-value" id="snombre"></span>
                        </div>

                        <div class="form-group">
                            <label for="apellido_usuario">Apellido(s)*</label>
                            <input type="text" class="input-textnum" id="apellido_usuario" name="apellido_usuario" placeholder="Ingrese su apellido" maxlength="50">
                            <span class="span-value" id="sapellido_usuario"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cedula">Cédula*</label>
                            <input type="text" class="input-textnum" id="cedula" name="cedula" placeholder="12.345.678" maxlength="10">
                            <span class="span-value" id="scedula"></span>
                        </div>

                        <div class="form-group">
                            <label for="telefono_usuario">Teléfono*</label>
                            <input type="text" class="input-textnum" id="telefono_usuario" name="telefono_usuario" placeholder="0414-123-4567" maxlength="13">
                            <span class="span-value" id="stelefono_usuario"></span>
                        </div>

                        <div class="form-group">
                            <label for="correo_usuario">Correo Electrónico*</label>
                            <input type="email" class="input-textnum" id="correo_usuario" name="correo_usuario" placeholder="persona@gmail.com" maxlength="50">
                            <span class="span-value" id="scorreo_usuario"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="rango">Rol del Usuario*</label>
                            <select id="rango" name="rango" class="input-select">
                                <option value="" data-role-placeholder>Seleccione el rol</option>
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
                            <span class="span-value" id="srango"></span>
                        </div>

                        <div class="form-group" id="passwordGroup">
                            <label for="clave_usuario">Contraseña*</label>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="password" class="input-textnum" id="clave_usuario" name="clave_usuario" placeholder="Crea una contraseña" maxlength="15">
                                <button type="button" class="toggle-password" data-target="#clave_usuario" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                </button>
                            </div>
                            <span class="span-value" id="sclave_usuario"></span>
                        </div>

                        <div class="form-group" id="confirmPasswordGroup">
                            <label for="clave_confirmar">Confirmar Contraseña*</label>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="password" class="input-textnum" id="clave_confirmar" name="clave_confirmar" placeholder="Ingrese nuevamente la contraseña" maxlength="15">
                                <button type="button" class="toggle-password" data-target="#clave_confirmar" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                </button>
                            </div>
                            <span class="span-value" id="sclave_confirmar"></span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn-confirmar btn-save">
                            <i class="fas fa-check"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="userDetailsModal" class="modal modal-container">
    <div class="modal-narrow">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Detallar Usuario</h2>
                    <p>Información completa del usuario seleccionado</p>
                </div>
                <span class="close-modal" onclick="closeModal('userDetailsModal')">&times;</span>
            </div>
        </div>

        <div class="modal-body body-data">
            <div class="detallado-column">
                <div class="detalle-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información General</h3>
                    </div>
                    <div class="detallado-row">
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Usuario:</strong>
                            <span id="detailUsername"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Cédula:</strong>
                            <span id="detailCedula"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Nombre:</strong>
                            <span id="detailFirstName"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Apellido:</strong>
                            <span id="detailLastName"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Teléfono:</strong>
                            <span id="detailPhone"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Correo electrónico:</strong>
                            <span id="detailEmail"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Rol:</strong>
                            <span id="detailRole"></span>
                        </div>
                        <div class="detalle-item detalle-column">
                            <strong class="title-data">Estado:</strong>
                            <span id="detailStatus"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="deleteUserModal" class="modal modal-container" aria-hidden="true">
    <div class="modal-narrow">
        <div class="background-red">
            <div class="modal-header-content">
                <div class="modal-icon background-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Eliminar Usuario</h2>
                    <p>Confirmación de eliminación permanente</p>
                </div>
                <button type="button" class="btn-help red" title="Ayuda para eliminar usuarios" data-contexto="eliminar">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div class="content-eliminacion">
                <div class="quest-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de eliminar este usuario?</h3>
                <p>Esta acción es permanente y no se puede deshacer.</p>

                <div class="info-eliminacion">
                    <div class="info-row">
                        <label>Usuario:</label>
                        <span id="deleteUserName"></span>
                    </div>
                </div>

                <div class="modal-footer-content">
                    <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal('deleteUserModal')">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-confirmar btn-danger" id="confirmDeleteUser">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="userStatusModal" class="modal modal-container" aria-hidden="true">
    <div class="modal-narrow">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Cambiar Estado</h2>
                    <p>Confirmación de cambio de estado</p>
                </div>
                <button type="button" class="btn-help blue" title="Ayuda para cambiar el estado" data-contexto="estado">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div class="content-status">
                <div class="status-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>¿Desea cambiar el estado de este usuario?</h3>
                <p>El cambio de estado afectará el acceso del usuario al sistema.</p>

                <div class="info-status">
                    <div class="info-row">
                        <label>Usuario:</label>
                        <span id="statusUserName"></span>
                    </div>
                    <div class="info-row">
                        <label>Nuevo estado:</label>
                        <span id="statusUserNext"></span>
                    </div>
                </div>

                <div class="modal-footer-content">
                    <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal('userStatusModal')">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-confirmar btn-cambiar" id="confirmUserStatus">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>