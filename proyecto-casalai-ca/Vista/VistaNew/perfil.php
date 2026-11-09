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
        error_log("Error al generar JWT en perfil: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'perfil';
$titulo_pagina = 'Perfil de Usuario';

// El controlador ya provee $usuario desde la BD (descifrado).
// Fallback a sesión por seguridad.
$u = is_array($usuario ?? null) ? $usuario : [];
$perfil = [
    'nombres'      => $u['nombres'] ?? ($_SESSION['name'] ?? 'Usuario'),
    'apellidos'    => $u['apellidos'] ?? ($_SESSION['apellidos'] ?? ''),
    'username'     => $u['username'] ?? ($_SESSION['username'] ?? 'usuario'),
    'nombre_rol'   => $u['nombre_rol'] ?? ($_SESSION['nombre_rol'] ?? 'Usuario'),
    'cedula'       => $u['cedula'] ?? ($_SESSION['cedula'] ?? ''),
    'correo'       => $u['correo'] ?? ($_SESSION['correo'] ?? ($_SESSION['email'] ?? '')),
    'telefono'     => $u['telefono'] ?? ($_SESSION['telefono'] ?? ''),
    'foto_perfil'  => $u['foto_perfil'] ?? ($_SESSION['foto_perfil'] ?? '')
];

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Perfil Header -->
            <div class="profile-header-section">
                <div class="profile-avatar-large">
                    <div class="avatar-circle" id="avatarCircle">
                        <?php if (!empty($perfil['foto_perfil'])): ?>
                            <img src="assets/img/uploads/<?php echo htmlspecialchars($perfil['foto_perfil']); ?>" alt="Foto de perfil" class="avatar-img" id="avatarImg">
                        <?php else: ?>
                            <span class="avatar-initial" id="avatarInitial"><?php echo htmlspecialchars(substr($perfil['nombres'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="btn-change-avatar" id="btn-change-avatar" title="Cambiar foto de perfil">
                        <span class="camera-icon"><i class="fas fa-camera"></i></span>
                    </button>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars(trim($perfil['nombres'] . ' ' . $perfil['apellidos'])); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($perfil['username']); ?></p>
                    <span class="profile-role"><?php echo htmlspecialchars($perfil['nombre_rol']); ?></span>
                </div>
            </div>

            <!-- Secciones del Perfil -->
            <div class="profile-sections">
                <!-- Información Personal -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Información Personal</h2>
                        <button class="btn-edit-section" id="btn-edit-personal" title="Editar información personal">
                            <span class="edit-icon"><i class="fas fa-edit"></i></span>
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="info-grid" id="personal-info-display">
                            <div class="info-item">
                                <label>Nombre Completo</label>
                                <p id="display-nombre"><?php echo htmlspecialchars(trim($perfil['nombres'] . ' ' . $perfil['apellidos'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Usuario</label>
                                <p id="display-username"><?php echo htmlspecialchars($perfil['username']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Cédula</label>
                                <p><?php echo htmlspecialchars($perfil['cedula']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Teléfono</label>
                                <p id="display-telefono"><?php echo htmlspecialchars($perfil['telefono']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Rol</label>
                                <p><?php echo htmlspecialchars($perfil['nombre_rol']); ?></p>
                            </div>
                        </div>

                        <form id="form-personal" class="perfil-form" style="display:none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombres">Nombres*</label>
                                    <input type="text" id="nombres" name="nombres" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($perfil['nombres']); ?>">
                                    <span id="snombres" class="span-value error-message"></span>
                                </div>
                                <div class="form-group">
                                    <label for="apellidos">Apellidos*</label>
                                    <input type="text" id="apellidos" name="apellidos" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($perfil['apellidos']); ?>">
                                    <span id="sapellidos" class="span-value error-message"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" class="form-control" maxlength="13" placeholder="0400-000-0000" value="<?php echo htmlspecialchars($perfil['telefono']); ?>">
                                <span id="stelefono" class="span-value error-message"></span>
                            </div>
                            <div class="action-buttons">
                                <button type="submit" class="btn-save-form"><i class="fas fa-check"></i> Guardar</button>
                                <button type="button" class="btn-cancel-form" id="btn-cancel-personal"><i class="fas fa-times"></i> Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Seguridad</h2>
                    </div>
                    <div class="card-content">
                        <div class="security-items">
                            <div class="security-item">
                                <div class="security-icon"><i class="fas fa-envelope"></i></div>
                                <div class="security-info">
                                    <h4>Correo Electrónico</h4>
                                    <p id="display-correo"><?php echo htmlspecialchars($perfil['correo']); ?></p>
                                </div>
                                <button class="btn-change-password" id="btn-change-email">Actualizar</button>
                            </div>

                            <!-- Formulario correo (oculto) -->
                            <form id="form-email" class="perfil-form" style="display:none; width:100%;">
                                <div class="form-group">
                                    <label for="current_email">Correo Actual</label>
                                    <input type="email" id="current_email" class="form-control" value="<?php echo htmlspecialchars($perfil['correo']); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="new_email">Nuevo Correo*</label>
                                    <input type="email" id="new_email" name="new_email" class="form-control" maxlength="100" placeholder="correo@gmail.com">
                                    <span id="snew_email" class="span-value error-message"></span>
                                </div>
                                <div class="form-group">
                                    <label for="password_email">Contraseña Actual*</label>
                                    <input type="password" id="password_email" name="password" class="form-control" placeholder="Confirma tu contraseña">
                                </div>
                                <div class="action-buttons">
                                    <button type="submit" class="btn-save-form"><i class="fas fa-check"></i> Actualizar Correo</button>
                                    <button type="button" class="btn-cancel-form" id="btn-cancel-email"><i class="fas fa-times"></i> Cancelar</button>
                                </div>
                            </form>

                            <div class="security-item">
                                <div class="security-icon"><i class="fas fa-lock"></i></div>
                                <div class="security-info">
                                    <h4>Contraseña</h4>
                                    <p>Cambia tu contraseña de acceso</p>
                                </div>
                                <button class="btn-change-password" id="btn-change-password">Cambiar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Cambiar Contraseña -->
            <div id="passwordModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Cambiar Contraseña</h2>
                        <span class="close-modal" id="closePasswordModal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="passwordForm">
                            <div class="form-group">
                                <label for="currentPassword">Contraseña Actual*</label>
                                <input type="password" id="currentPassword" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="newPassword">Nueva Contraseña*</label>
                                <input type="password" id="newPassword" name="new_password" class="form-control" required>
                                <span id="snew_password" class="span-value error-message"></span>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirmar Contraseña*</label>
                                <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
                                <span id="sconfirm_password" class="span-value error-message"></span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" id="btn-cancel-password-modal">Cancelar</button>
                        <button class="btn-save" id="btn-save-password">Cambiar</button>
                    </div>
                </div>
            </div>

            <!-- Modal para Cambiar Foto de Perfil -->
            <div id="avatarModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Cambiar Foto de Perfil</h2>
                        <span class="close-modal" id="closeAvatarModal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="form-avatar">
                            <div class="avatar-preview-modal" id="avatarPreview">
                                <?php if (!empty($perfil['foto_perfil'])): ?>
                                    <img src="assets/img/uploads/<?php echo htmlspecialchars($perfil['foto_perfil']); ?>" alt="Vista previa" class="avatar-preview-img" id="avatarPreviewImg">
                                <?php else: ?>
                                    <span id="avatarPreviewInitial"><?php echo htmlspecialchars(substr($perfil['nombres'], 0, 1)); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="foto_perfil">Seleccionar Imagen</label>
                                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="form-control">
                                <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB</small>
                                <span id="sfoto_perfil" class="span-value error-message"></span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" id="btn-cancel-avatar">Cancelar</button>
                        <button class="btn-save" id="btn-save-avatar">Guardar Foto</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Perfil */
                .profile-header-section {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    border-radius: 12px;
                    padding: 30px;
                    display: flex;
                    align-items: center;
                    gap: 30px;
                    margin-bottom: 30px;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }

                .profile-avatar-large {
                    position: relative;
                }

                .avatar-circle {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    background: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                }

                .avatar-img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .avatar-initial {
                    font-size: 3rem;
                    font-weight: 700;
                    color: #2196F3;
                }

                .btn-change-avatar {
                    position: absolute;
                    bottom: 5px;
                    right: 5px;
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    background: white;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                    transition: transform 0.2s;
                }

                .btn-change-avatar:hover {
                    transform: scale(1.1);
                }

                .camera-icon {
                    font-size: 1.2rem;
                }

                .camera-icon i {
                    color: #2196F3;
                }

                .profile-info {
                    color: white;
                }

                .profile-name {
                    margin: 0 0 10px 0;
                    font-size: 2rem;
                    font-weight: 700;
                }

                .profile-username {
                    margin: 0 0 5px 0;
                    font-size: 1.2rem;
                    opacity: 0.9;
                }

                .profile-role {
                    display: inline-block;
                    background: rgba(255, 255, 255, 0.2);
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 0.9rem;
                }

                .profile-sections {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                    gap: 25px;
                }

                .profile-card {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                }

                .card-header {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    padding: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px solid #ddd;
                }

                .card-header h2 {
                    margin: 0;
                    color: #333;
                    font-size: 1.2rem;
                }

                .btn-edit-section {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 8px 14px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: transform 0.2s;
                }

                .btn-edit-section:hover {
                    transform: scale(1.05);
                }

                .edit-icon i {
                    color: white;
                }

                .card-content {
                    padding: 20px;
                }

                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                }

                .info-item label {
                    display: block;
                    font-weight: 600;
                    color: #666;
                    margin-bottom: 5px;
                    font-size: 0.85rem;
                }

                .info-item p {
                    margin: 0;
                    color: #333;
                    font-size: 1rem;
                }

                .security-items {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .security-item {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding: 15px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 8px;
                }

                .security-icon {
                    font-size: 2rem;
                }

                .security-icon i {
                    color: #2196F3;
                }

                .security-info {
                    flex: 1;
                }

                .security-info h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .security-info p {
                    margin: 0;
                    color: #666;
                    font-size: 0.85rem;
                    word-break: break-word;
                }

                .btn-change-password {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    transition: transform 0.2s;
                    white-space: nowrap;
                }

                .btn-change-password:hover {
                    transform: scale(1.05);
                }

                /* Formularios inline */
                .perfil-form {
                    margin-top: 15px;
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

                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }

                .form-control {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                    box-sizing: border-box;
                }

                .form-control:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .span-value.error-message {
                    display: block;
                    margin-top: 6px;
                    font-size: 0.82rem;
                    color: #d30000;
                    font-style: italic;
                    min-height: 1em;
                }

                .action-buttons {
                    display: flex;
                    gap: 10px;
                    margin-top: 10px;
                }

                .btn-save-form, .btn-cancel-form, .btn-cancel, .btn-save {
                    padding: 10px 18px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-save-form, .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-save-form:hover, .btn-save:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
                }

                .btn-cancel-form, .btn-cancel {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                }

                .btn-cancel-form:hover, .btn-cancel:hover {
                    transform: translateY(-2px);
                }

                .text-muted {
                    color: #718096;
                    font-size: 0.82rem;
                }

                /* Modales */
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 2000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    animation: fadeIn 0.3s;
                }

                .modal-content {
                    background-color: #fff;
                    margin: 5% auto;
                    border-radius: 10px;
                    width: 90%;
                    max-width: 480px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                    animation: slideDown 0.3s;
                    overflow: hidden;
                }

                .modal-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .modal-header h2 {
                    margin: 0;
                    font-size: 1.3rem;
                }

                .close-modal {
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    color: white;
                    line-height: 1;
                }

                .close-modal:hover { color: #ddd; }

                .modal-body { padding: 25px; }
                .modal-footer {
                    padding: 20px;
                    text-align: right;
                    border-top: 1px solid #dee2e6;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }

                .avatar-preview-modal {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    margin: 0 auto 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    font-size: 3rem;
                    font-weight: 700;
                    color: #2196F3;
                }

                .avatar-preview-img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideDown {
                    from { transform: translateY(-50px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
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
                    .profile-header-section {
                        flex-direction: column;
                        text-align: center;
                    }

                    .profile-sections {
                        grid-template-columns: 1fr;
                    }

                    .info-grid {
                        grid-template-columns: 1fr;
                    }

                    .form-row {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <button class="btn-ayuda" id="btn-ayuda-perfil" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=perfil';

                /* ==================== Utilidades ==================== */
                function space(str) {
                    return (str || '').toString().replace(/\s{2,}/g, ' ');
                }

                function setError(spanId, mensaje) {
                    const el = document.getElementById(spanId);
                    if (el) el.textContent = mensaje || '';
                }

                async function postPerfil(formData) {
                    const resp = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: formData
                    });
                    let data = null;
                    try { data = await resp.json(); } catch (e) { data = null; }
                    if (!resp.ok || !data) {
                        throw new Error('Respuesta inválida del servidor');
                    }
                    return data;
                }

                function mostrarErroresValidacion(errors) {
                    if (!errors || typeof errors !== 'object') return;
                    const mapa = {
                        username: 'susername',
                        nombre: 'snombres',
                        apellido: 'sapellidos',
                        nombres: 'snombres',
                        apellidos: 'sapellidos',
                        telefono: 'stelefono',
                        correo: 'snew_email',
                        clave: 'snew_password'
                    };
                    Object.keys(errors).forEach(function (k) {
                        const spanId = mapa[k];
                        if (spanId) setError(spanId, errors[k]);
                    });
                }

                function pedirContrasena(titulo, texto) {
                    return Swal.fire({
                        title: titulo,
                        text: texto,
                        input: 'password',
                        inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
                        showCancelButton: true,
                        confirmButtonText: 'Confirmar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#2196F3',
                        cancelButtonColor: '#6c757d',
                        inputValidator: (value) => {
                            if (!value) return 'La contraseña es requerida';
                        }
                    });
                }

                async function enviar(tipo, formData, opciones) {
                    opciones = opciones || {};
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    try {
                        const data = await postPerfil(formData);
                        Swal.close();
                        if (data.status === 'success') {
                            await Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.message || 'Operación completada',
                                timer: 1800,
                                showConfirmButton: false
                            });
                            if (opciones.onSuccess) opciones.onSuccess(data);
                            if (opciones.reload !== false) {
                                setTimeout(() => location.reload(), 300);
                            }
                        } else if (data.status === 'info') {
                            await Swal.fire({ icon: 'info', title: 'Información', text: data.message || '' });
                        } else {
                            mostrarErroresValidacion(data.errors);
                            await Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo completar la operación'
                            });
                        }
                    } catch (err) {
                        console.error('Error POST perfil:', err);
                        Swal.close();
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la operación. Intente nuevamente.' });
                    }
                }

                /* ==================== Validaciones en vivo ==================== */
                function soloLetrasConTildes(valor) {
                    return /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/.test(valor);
                }

                function validarCampo(input, regex, spanId, mensaje) {
                    const ok = regex.test(input.value);
                    setError(spanId, ok ? '' : mensaje);
                    return ok;
                }

                const usernameInput = document.getElementById('username');
                const nombresInput = document.getElementById('nombres');
                const apellidosInput = document.getElementById('apellidos');
                const telefonoInput = document.getElementById('telefono');
                const newEmailInput = document.getElementById('new_email');
                const newPassInput = document.getElementById('newPassword');
                const confirmPassInput = document.getElementById('confirmPassword');

                if (usernameInput) {
                    usernameInput.addEventListener('input', function () {
                        this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
                    });
                    usernameInput.addEventListener('blur', function () {
                        validarCampo(this, /^[a-zA-Z0-9_]{4,20}$/, 'susername', '*El usuario debe tener entre 4 y 20 caracteres alfanuméricos*');
                    });
                }

                [nombresInput, apellidosInput].forEach(function (input) {
                    if (!input) return;
                    const spanId = input.id === 'nombres' ? 'snombres' : 'sapellidos';
                    input.addEventListener('input', function () {
                        this.value = space(this.value).replace(/[^a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]/g, '');
                    });
                    input.addEventListener('blur', function () {
                        validarCampo(this, /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/, spanId, '*Solo letras, de 2 a 50 caracteres*');
                    });
                });

                if (telefonoInput) {
                    telefonoInput.addEventListener('input', function () {
                        let valor = this.value.replace(/\D/g, '');
                        if (valor.length > 4 && valor.length <= 7) {
                            valor = valor.slice(0, 4) + '-' + valor.slice(4);
                        } else if (valor.length > 7) {
                            valor = valor.slice(0, 4) + '-' + valor.slice(4, 7) + '-' + valor.slice(7, 11);
                        }
                        this.value = valor;
                    });
                    telefonoInput.addEventListener('blur', function () {
                        const vacio = this.value.trim() === '';
                        validarCampo(this, vacio || /^\d{4}-\d{3}-\d{4}$/, 'stelefono', '*Formato válido: 0400-000-0000*');
                    });
                }

                const EMAIL_REGEX = /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/;
                if (newEmailInput) {
                    newEmailInput.addEventListener('blur', function () {
                        validarCampo(this, EMAIL_REGEX, 'snew_email', '*Debe ser un correo @gmail.com, @outlook.com, @yahoo.com o @icloud.com*');
                    });
                }

                const PASS_REGEX = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{\}\[\]|:;"'<>.,?/\\])[A-Za-z0-9ñÑà-ü!@#$%^&*()_+\-=\{\}\[\]|:;"'<>.,?/\\]{6,15}$/;
                if (newPassInput) {
                    newPassInput.addEventListener('blur', function () {
                        validarCampo(this, PASS_REGEX, 'snew_password', '*6-15 caracteres, con al menos 1 mayúscula, 1 número y 1 caracter especial*');
                    });
                }
                if (confirmPassInput) {
                    confirmPassInput.addEventListener('blur', function () {
                        const ok = this.value === '' || this.value === newPassInput.value;
                        setError('sconfirm_password', ok ? '' : '*Las contraseñas no coinciden*');
                    });
                }

                /* ==================== Sección información personal ==================== */
                const btnEditPersonal = document.getElementById('btn-edit-personal');
                const formPersonal = document.getElementById('form-personal');
                const displayPersonal = document.getElementById('personal-info-display');

                if (btnEditPersonal && formPersonal && displayPersonal) {
                    btnEditPersonal.addEventListener('click', function () {
                        displayPersonal.style.display = 'none';
                        formPersonal.style.display = 'block';
                        btnEditPersonal.style.display = 'none';
                    });
                    document.getElementById('btn-cancel-personal').addEventListener('click', function () {
                        formPersonal.style.display = 'none';
                        displayPersonal.style.display = '';
                        btnEditPersonal.style.display = '';
                        ['susername', 'snombres', 'sapellidos', 'stelefono'].forEach(id => setError(id, ''));
                    });
                }

                if (formPersonal) {
                    formPersonal.addEventListener('submit', async function (e) {
                        e.preventDefault();
                        // Validación previa
                        const checks = [
                            validarCampo(nombresInput, /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/, 'snombres', '*Solo letras, de 2 a 50 caracteres*'),
                            validarCampo(apellidosInput, /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/, 'sapellidos', '*Solo letras, de 2 a 50 caracteres*')
                        ];
                        if (telefonoInput.value.trim() !== '' && !/^\d{4}-\d{3}-\d{4}$/.test(telefonoInput.value)) {
                            setError('stelefono', '*Formato válido: 0400-000-0000*');
                            checks.push(false);
                        }
                        if (checks.includes(false)) {
                            await Swal.fire({ icon: 'error', title: 'Verifique los datos', text: 'Hay campos con formato inválido' });
                            return;
                        }

                        const pass = await pedirContrasena('Confirmar cambios', 'Ingresa tu contraseña actual para confirmar los cambios:');
                        if (!pass.isConfirmed) return;

                        const fd = new FormData();
                        fd.append('tipo', 'personal');
                        fd.append('nombres', space(nombresInput.value.trim()));
                        fd.append('apellidos', space(apellidosInput.value.trim()));
                        fd.append('telefono', telefonoInput.value.trim());
                        fd.append('clave_actual', pass.value);

                        await enviar('personal', fd);
                    });
                }

                /* ==================== Correo ==================== */
                const btnChangeEmail = document.getElementById('btn-change-email');
                const formEmail = document.getElementById('form-email');
                const securityItems = document.querySelectorAll('.security-item');

                if (btnChangeEmail && formEmail) {
                    btnChangeEmail.addEventListener('click', function () {
                        // Ocultar el primer security-item (correo) y mostrar el form
                        securityItems[0].style.display = 'none';
                        formEmail.style.display = 'block';
                    });
                    document.getElementById('btn-cancel-email').addEventListener('click', function () {
                        formEmail.style.display = 'none';
                        securityItems[0].style.display = '';
                        formEmail.reset();
                        setError('snew_email', '');
                    });
                }

                if (formEmail) {
                    formEmail.addEventListener('submit', async function (e) {
                        e.preventDefault();
                        if (!EMAIL_REGEX.test(newEmailInput.value.trim())) {
                            setError('snew_email', '*Debe ser un correo @gmail.com, @outlook.com, @yahoo.com o @icloud.com*');
                            await Swal.fire({ icon: 'error', title: 'Correo inválido', text: 'Ingrese un correo válido' });
                            return;
                        }
                        const pass = document.getElementById('password_email').value;
                        if (!pass) {
                            await Swal.fire({ icon: 'error', title: 'Error', text: 'La contraseña es obligatoria' });
                            return;
                        }
                        const fd = new FormData();
                        fd.append('tipo', 'email');
                        fd.append('new_email', newEmailInput.value.trim());
                        fd.append('password', pass);
                        await enviar('email', fd);
                    });
                }

                /* ==================== Contraseña ==================== */
                const passwordModal = document.getElementById('passwordModal');
                const btnChangePassword = document.getElementById('btn-change-password');

                function openPasswordModal() {
                    passwordModal.style.display = 'block';
                    document.getElementById('passwordForm').reset();
                    setError('snew_password', '');
                    setError('sconfirm_password', '');
                }

                function closePasswordModal() {
                    passwordModal.style.display = 'none';
                }

                if (btnChangePassword) btnChangePassword.addEventListener('click', openPasswordModal);
                document.getElementById('closePasswordModal')?.addEventListener('click', closePasswordModal);
                document.getElementById('btn-cancel-password-modal')?.addEventListener('click', closePasswordModal);
                window.addEventListener('click', function (event) {
                    if (event.target === passwordModal) closePasswordModal();
                });

                document.getElementById('btn-save-password')?.addEventListener('click', async function () {
                    const current = document.getElementById('currentPassword').value;
                    const nueva = document.getElementById('newPassword').value;
                    const confirma = document.getElementById('confirmPassword').value;

                    if (!current || !nueva || !confirma) {
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'Todos los campos son obligatorios' });
                        return;
                    }
                    if (nueva !== confirma) {
                        setError('sconfirm_password', '*Las contraseñas no coinciden*');
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'Las contraseñas nuevas no coinciden' });
                        return;
                    }
                    if (!PASS_REGEX.test(nueva)) {
                        setError('snew_password', '*6-15 caracteres, con al menos 1 mayúscula, 1 número y 1 caracter especial*');
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'La contraseña no cumple los requisitos de seguridad' });
                        return;
                    }
                    if (nueva === current) {
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'La nueva contraseña no puede ser igual a la actual' });
                        return;
                    }

                    const fd = new FormData();
                    fd.append('tipo', 'password');
                    fd.append('current_password', current);
                    fd.append('new_password', nueva);
                    fd.append('confirm_password', confirma);

                    await enviar('password', fd, {
                        reload: false,
                        onSuccess: function () { closePasswordModal(); }
                    });
                });

                /* ==================== Foto de perfil ==================== */
                const avatarModal = document.getElementById('avatarModal');
                const fileInput = document.getElementById('foto_perfil');
                const avatarPreview = document.getElementById('avatarPreview');
                const avatarCircle = document.getElementById('avatarCircle');

                function closeAvatarModal() {
                    avatarModal.style.display = 'none';
                    if (fileInput) fileInput.value = '';
                    setError('sfoto_perfil', '');
                    // Restaurar preview original
                    avatarPreview.innerHTML = '<?php echo !empty($perfil['foto_perfil']) ? '<img src="assets/img/uploads/' . htmlspecialchars($perfil['foto_perfil'], ENT_QUOTES) . '" alt="Vista previa" class="avatar-preview-img">' : htmlspecialchars(substr($perfil['nombres'], 0, 1), ENT_QUOTES); ?>';
                }

                function openAvatarModal() {
                    avatarModal.style.display = 'block';
                    avatarPreview.innerHTML = '<?php echo !empty($perfil['foto_perfil']) ? '<img src="assets/img/uploads/' . htmlspecialchars($perfil['foto_perfil'], ENT_QUOTES) . '" alt="Vista previa" class="avatar-preview-img">' : htmlspecialchars(substr($perfil['nombres'], 0, 1), ENT_QUOTES); ?>';
                }

                document.getElementById('btn-change-avatar')?.addEventListener('click', openAvatarModal);
                document.getElementById('closeAvatarModal')?.addEventListener('click', closeAvatarModal);
                document.getElementById('btn-cancel-avatar')?.addEventListener('click', closeAvatarModal);
                window.addEventListener('click', function (event) {
                    if (event.target === avatarModal) closeAvatarModal();
                });

                if (fileInput) {
                    fileInput.addEventListener('change', function (e) {
                        const file = e.target.files[0];
                        setError('sfoto_perfil', '');
                        if (!file) return;
                        if (file.size > 2 * 1024 * 1024) {
                            setError('sfoto_perfil', '*La imagen debe ser menor a 2MB*');
                            this.value = '';
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function (ev) {
                            avatarPreview.innerHTML = '<img src="' + ev.target.result + '" alt="Vista previa" class="avatar-preview-img">';
                        };
                        reader.readAsDataURL(file);
                    });
                }

                document.getElementById('btn-save-avatar')?.addEventListener('click', async function () {
                    if (!fileInput || !fileInput.files[0]) {
                        setError('sfoto_perfil', '*Debe seleccionar una imagen*');
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'Debes seleccionar una imagen' });
                        return;
                    }
                    const pass = await pedirContrasena('Confirmar cambio de foto', 'Ingresa tu contraseña actual para cambiar la foto de perfil:');
                    if (!pass.isConfirmed) return;

                    const fd = new FormData();
                    fd.append('tipo', 'avatar');
                    fd.append('foto_perfil', fileInput.files[0]);
                    fd.append('clave_actual', pass.value);

                    await enviar('avatar', fd, {
                        reload: false,
                        onSuccess: function () {
                            closeAvatarModal();
                            // Recargar para reflejar la nueva foto en header y avatar
                            setTimeout(() => location.reload(), 400);
                        }
                    });
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
                            const html = await (await fetch('assets/public/ayuda/perfil.php')).text();
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

                document.getElementById('btn-ayuda-perfil')?.addEventListener('click', function (e) {
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
