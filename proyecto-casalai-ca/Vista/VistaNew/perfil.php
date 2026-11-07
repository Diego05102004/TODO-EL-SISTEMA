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

// Obtener datos del usuario desde la sesión
$usuario = [
    'nombres' => $_SESSION['name'] ?? 'Usuario',
    'apellidos' => $_SESSION['apellidos'] ?? '',
    'username' => $_SESSION['username'] ?? 'usuario',
    'nombre_rol' => $_SESSION['nombre_rol'] ?? 'Usuario',
    'cedula' => $_SESSION['cedula'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'telefono' => $_SESSION['telefono'] ?? '',
    'foto_perfil' => $_SESSION['foto_perfil'] ?? ''
];

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Perfil Header -->
            <div class="profile-header-section">
                <div class="profile-avatar-large">
                    <div class="avatar-circle">
                        <?php if (!empty($usuario['foto_perfil'])): ?>
                            <img src="assets/img/uploads/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil" class="avatar-img">
                        <?php else: ?>
                            <span class="avatar-initial"><?php echo substr($usuario['nombres'], 0, 1); ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="btn-change-avatar" onclick="changeAvatar()">
                        <span class="camera-icon"><i class="fas fa-camera"></i></span>
                    </button>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($usuario['username']); ?></p>
                    <span class="profile-role"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></span>
                </div>
            </div>

            <!-- Secciones del Perfil -->
            <div class="profile-sections">
                <!-- Información Personal -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Información Personal</h2>
                        <button class="btn-edit-section" onclick="editSection('personal')">
                            <span class="edit-icon"><i class="fas fa-edit"></i></span>
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Nombre Completo</label>
                                <p><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Usuario</label>
                                <p><?php echo htmlspecialchars($usuario['username']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Cédula</label>
                                <p><?php echo htmlspecialchars($usuario['cedula']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($usuario['email']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Teléfono</label>
                                <p><?php echo htmlspecialchars($usuario['telefono']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Rol</label>
                                <p><?php echo htmlspecialchars($usuario['nombre_rol']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Seguridad</h2>
                        <button class="btn-edit-section" onclick="editSection('security')">
                            <span class="edit-icon"><i class="fas fa-edit"></i></span>
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="security-items">
                            <div class="security-item">
                                <div class="security-icon"><i class="fas fa-lock"></i></div>
                                <div class="security-info">
                                    <h4>Contraseña</h4>
                                    <p>Última actualización: hace 30 días</p>
                                </div>
                                <button class="btn-change-password" onclick="changePassword()">Cambiar</button>
                            </div>
                            <div class="security-item">
                                <div class="security-icon"><i class="fas fa-mobile"></i></div>
                                <div class="security-info">
                                    <h4>Autenticación de Dos Factores</h4>
                                    <p>Estado: Desactivado</p>
                                </div>
                                <button class="btn-activate-2fa" onclick="activate2FA()">Activar</button>
                            </div>
                            <div class="security-item">
                                <div class="security-icon"><i class="fas fa-desktop"></i></div>
                                <div class="security-info">
                                    <h4>Sesiones Activas</h4>
                                    <p>1 sesión activa</p>
                                </div>
                                <button class="btn-view-sessions" onclick="viewSessions()">Ver</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferencias -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Preferencias</h2>
                        <button class="btn-edit-section" onclick="editSection('preferences')">
                            <span class="edit-icon"><i class="fas fa-edit"></i></span>
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="preferences-grid">
                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Notificaciones por Email</h4>
                                    <p>Recibir alertas por correo electrónico</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Notificaciones Push</h4>
                                    <p>Recibir notificaciones en tiempo real</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Modo Oscuro</h4>
                                    <p>Interfaz con tema oscuro</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Idioma</h4>
                                    <p>Idioma de la interfaz</p>
                                </div>
                                <select class="preference-select">
                                    <option value="es" selected>Español</option>
                                    <option value="en">English</option>
                                    <option value="pt">Português</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Actividad Reciente</h2>
                        <button class="btn-view-all" onclick="viewAllActivity()">Ver todo</button>
                    </div>
                    <div class="card-content">
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fas fa-box"></i></div>
                                <div class="activity-info">
                                    <h4>Pedido #ORD-006 creado</h4>
                                    <p>Hace 15 minutos</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fas fa-user"></i></div>
                                <div class="activity-info">
                                    <h4>Perfil actualizado</h4>
                                    <p>Hace 2 horas</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fas fa-lock"></i></div>
                                <div class="activity-info">
                                    <h4>Cambio de contraseña</h4>
                                    <p>Hace 30 días</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fas fa-chart-bar"></i></div>
                                <div class="activity-info">
                                    <h4>Reporte generado</h4>
                                    <p>Hace 2 días</p>
                                </div>
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
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="passwordForm">
                            <div class="form-group">
                                <label for="currentPassword">Contraseña Actual*</label>
                                <input type="password" id="currentPassword" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="newPassword">Nueva Contraseña*</label>
                                <input type="password" id="newPassword" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirmar Contraseña*</label>
                                <input type="password" id="confirmPassword" name="confirm_password" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closePasswordModal()">Cancelar</button>
                        <button class="btn-save" onclick="savePassword()">Cambiar</button>
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

                .btn-edit-section, .btn-view-all {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: transform 0.2s;
                }

                .btn-edit-section:hover, .btn-view-all:hover {
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
                }

                .btn-change-password, .btn-activate-2fa, .btn-view-sessions {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    transition: transform 0.2s;
                }

                .btn-change-password:hover, .btn-activate-2fa:hover, .btn-view-sessions:hover {
                    transform: scale(1.05);
                }

                .preferences-grid {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .preference-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 8px;
                }

                .preference-info h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .preference-info p {
                    margin: 0;
                    color: #666;
                    font-size: 0.85rem;
                }

                .preference-select {
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    background: white;
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

                .activity-list {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .activity-item {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding: 15px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 8px;
                }

                .activity-icon {
                    font-size: 2rem;
                }

                .activity-icon i {
                    color: #2196F3;
                }

                .activity-info h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .activity-info p {
                    margin: 0;
                    color: #666;
                    font-size: 0.85rem;
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
                }
            </style>

            <script>
                function changeAvatar() {
                    alert('Función para cambiar foto de perfil (conectar con backend)');
                }

                function editSection(section) {
                    alert('Función para editar sección: ' + section + ' (conectar con backend)');
                }

                function changePassword() {
                    document.getElementById('passwordModal').style.display = 'block';
                }

                function closePasswordModal() {
                    document.getElementById('passwordModal').style.display = 'none';
                }

                function savePassword() {
                    const currentPassword = document.getElementById('currentPassword').value;
                    const newPassword = document.getElementById('newPassword').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;

                    if (newPassword !== confirmPassword) {
                        alert('Las contraseñas no coinciden');
                        return;
                    }

                    if (newPassword.length < 6) {
                        alert('La contraseña debe tener al menos 6 caracteres');
                        return;
                    }

                    alert('Función para cambiar contraseña (conectar con backend)');
                    closePasswordModal();
                }

                function activate2FA() {
                    alert('Función para activar autenticación de dos factores (conectar con backend)');
                }

                function viewSessions() {
                    alert('Función para ver sesiones activas (conectar con backend)');
                }

                function viewAllActivity() {
                    alert('Función para ver toda la actividad (conectar con backend)');
                }

                // Event listeners para cerrar modal
                document.querySelector('#passwordModal .close-modal').addEventListener('click', closePasswordModal);

                window.addEventListener('click', function(event) {
                    if (event.target === document.getElementById('passwordModal')) {
                        closePasswordModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>