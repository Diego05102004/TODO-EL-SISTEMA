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
                        <p class="card-value" id="rol-count-total"><?php echo count($roles ?? []); ?></p>
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
                        <p class="card-value" id="rol-count-activos"><?php echo count($roles ?? []); ?></p>
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
                        <p class="card-value" id="rol-count-editables"><?php echo count(array_filter($roles ?? [], function($r) { 
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
                        <button class="btn-add-rol js-btn-add-rol" onclick="openModal('registrar')">
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
                                    <tr data-id="<?php echo (int)($rol['id_rol'] ?? 0); ?>"
                                        data-nombre="<?php echo htmlspecialchars($rol['nombre_rol'] ?? '', ENT_QUOTES); ?>">
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
                                                <button class="btn-action btn-permisos js-btn-permisos" data-id="<?php echo (int)$rol['id_rol']; ?>" title="Gestionar Permisos">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button class="btn-action btn-edit js-btn-edit" data-id="<?php echo (int)$rol['id_rol']; ?>" title="Editar Rol">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action btn-delete js-btn-delete" data-id="<?php echo (int)$rol['id_rol']; ?>" title="Eliminar Rol">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-action btn-permisos js-btn-permisos" data-id="<?php echo (int)$rol['id_rol']; ?>" title="Gestionar Permisos">
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
                        <button type="button" class="btn-ayuda-modal" id="btnAyudaModalRol" title="Ayuda" data-contexto="registrar">
                            <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                        </button>
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
                                       placeholder="Ej: Gerente" maxlength="25">
                                <span class="rol-field-error" id="rolNombreError"></span>
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

                .rol-field-error {
                    display: block;
                    margin-top: 6px;
                    font-size: 0.82rem;
                    color: #d30000;
                    font-style: italic;
                    min-height: 1em;
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
                .btn-ayuda-modal {
                    position: absolute;
                    top: 18px;
                    left: 24px;
                    background: #5995fd;
                    border: none;
                    border-radius: 50%;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    z-index: 1002;
                }
                .btn-ayuda-modal:hover {
                    background: #4d84e2;
                    transform: scale(1.1);
                }
                .btn-ayuda-modal img { pointer-events: none; }
                #rolModal .modal-header { position: relative; }
            </style>

            <button class="btn-ayuda" id="btn-ayuda-rol" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=rol';
                const tbody = document.querySelector('.roles-table tbody');
                const inputNombre = document.getElementById('nombre_rol');
                const spanError = document.getElementById('rolNombreError');
                const btnAyudaModal = document.getElementById('btnAyudaModalRol');
                const ROLES_PROTEGIDOS = ['superusuario', 'administrador', 'cliente'];

                /* ==================== Utilidades ==================== */
                function escaparHtml(texto) {
                    const div = document.createElement('div');
                    div.textContent = texto == null ? '' : String(texto);
                    return div.innerHTML;
                }

                function normalizarNombre(valor) {
                    return String(valor || '').replace(/\s{2,}/g, ' ').trim();
                }

                function esProtegido(nombre) {
                    return ROLES_PROTEGIDOS.includes(String(nombre || '').toLowerCase());
                }

                async function postAccion(datos) {
                    const body = new URLSearchParams();
                    Object.keys(datos).forEach(k => body.append(k, datos[k]));
                    const response = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        credentials: 'same-origin',
                        body: body.toString()
                    });
                    let data = null;
                    try { data = await response.json(); } catch (e) { data = null; }
                    if (!response.ok || !data) {
                        throw new Error('El servidor devolvió una respuesta no válida.');
                    }
                    return data;
                }

                function mensajeErrorBackend(data, fallback) {
                    if (data && data.errors && typeof data.errors === 'object') {
                        const msgs = Object.values(data.errors).filter(Boolean);
                        if (msgs.length) return msgs.join(' ');
                    }
                    return (data && (data.message || data.error)) || fallback;
                }

                function filaRolHtml(rol) {
                    const protegido = esProtegido(rol.nombre_rol);
                    const botones = protegido
                        ? '<button class="btn-action btn-permisos js-btn-permisos" data-id="' + rol.id_rol + '" title="Gestionar Permisos"><i class="fas fa-key"></i></button>' +
                          '<span class="btn-action btn-disabled" title="Rol protegido"><i class="fas fa-lock"></i></span>'
                        : '<button class="btn-action btn-permisos js-btn-permisos" data-id="' + rol.id_rol + '" title="Gestionar Permisos"><i class="fas fa-key"></i></button>' +
                          '<button class="btn-action btn-edit js-btn-edit" data-id="' + rol.id_rol + '" title="Editar Rol"><i class="fas fa-edit"></i></button>' +
                          '<button class="btn-action btn-delete js-btn-delete" data-id="' + rol.id_rol + '" title="Eliminar Rol"><i class="fas fa-trash"></i></button>';

                    return '<tr data-id="' + rol.id_rol + '" data-nombre="' + escaparHtml(rol.nombre_rol) + '">' +
                        '<td>' + escaparHtml(rol.id_rol) + '</td>' +
                        '<td>' + escaparHtml(rol.nombre_rol) + '</td>' +
                        '<td><span class="status-badge ' + (protegido ? 'protected' : 'editable') + '">' +
                            (protegido ? 'Protegido' : 'Editable') + '</span></td>' +
                        '<td>' + botones + '</td>' +
                    '</tr>';
                }

                function hayFilasVacias() {
                    return tbody && tbody.querySelector('tr td[colspan]');
                }

                function agregarFila(rol) {
                    if (!tbody) return;
                    const vacia = hayFilasVacias();
                    if (vacia) vacia.remove();
                    tbody.insertAdjacentHTML('beforeend', filaRolHtml(rol));
                    actualizarContadores();
                    aplicarPermisos();
                }

                function actualizarFila(rol) {
                    const fila = tbody && tbody.querySelector('tr[data-id="' + rol.id_rol + '"]');
                    if (!fila) return;
                    const tmp = document.createElement('tbody');
                    tmp.innerHTML = filaRolHtml(rol);
                    const nueva = tmp.querySelector('tr');
                    fila.replaceWith(nueva);
                    actualizarContadores();
                    aplicarPermisos();
                }

                function eliminarFila(id) {
                    const fila = tbody && tbody.querySelector('tr[data-id="' + id + '"]');
                    if (!fila) return;
                    fila.remove();
                    if (tbody && !tbody.querySelector('tr[data-id]')) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;">' +
                            '<i class="fas fa-users-cog" style="font-size:3rem;color:#2196F3;margin-bottom:15px;"></i>' +
                            '<p style="color:#718096;margin:0;">No hay roles registrados</p></td></tr>';
                    }
                    actualizarContadores();
                    aplicarPermisos();
                }

                function actualizarContadores() {
                    const filas = tbody ? Array.from(tbody.querySelectorAll('tr[data-id]')) : [];
                    const total = document.getElementById('rol-count-total');
                    const activos = document.getElementById('rol-count-activos');
                    const editables = document.getElementById('rol-count-editables');
                    if (total) total.textContent = filas.length;
                    if (activos) activos.textContent = filas.length;
                    if (editables) editables.textContent = filas.filter(f => f.querySelector('.status-badge.editable')).length;
                }

                /* ==================== Validación del nombre ==================== */
                function validarNombre() {
                    const valor = normalizarNombre(inputNombre.value);
                    inputNombre.value = valor;
                    if (valor === '') {
                        spanError.textContent = '*Este campo es obligatorio*';
                        return false;
                    }
                    if (valor.length < 2) {
                        spanError.textContent = '*Mínimo 2 caracteres*';
                        return false;
                    }
                    if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]+$/.test(valor)) {
                        spanError.textContent = '*El formato solo permite letras*';
                        return false;
                    }
                    spanError.textContent = '';
                    return true;
                }

                if (inputNombre) {
                    inputNombre.addEventListener('input', function () {
                        this.value = this.value.replace(/\s{2,}/g, ' ');
                    });
                    inputNombre.addEventListener('blur', validarNombre);
                }

                /* ==================== Modal ==================== */
                window.openModal = function (type) {
                    if (type === 'registrar') {
                        document.getElementById('modalTitle').textContent = 'Agregar Rol';
                        document.getElementById('rolForm').reset();
                        document.getElementById('rolId').value = '';
                        document.getElementById('rolForm').querySelector('input[name="accion"]').value = 'registrar';
                        if (spanError) spanError.textContent = '';
                        if (btnAyudaModal) btnAyudaModal.dataset.contexto = 'registrar';
                    }
                    document.getElementById('rolModal').style.display = 'block';
                };

                window.closeModal = function () {
                    document.getElementById('rolModal').style.display = 'none';
                };

                document.querySelector('#rolModal .close-modal')?.addEventListener('click', closeModal);
                window.addEventListener('click', function (event) {
                    if (event.target === document.getElementById('rolModal')) closeModal();
                });

                /* ==================== Guardar (registrar / modificar) ==================== */
                window.saveRol = async function () {
                    if (!validarNombre()) {
                        await Swal.fire({ icon: 'error', title: 'Verifique el nombre del rol', text: spanError.textContent || 'Datos inválidos' });
                        return;
                    }

                    const form = document.getElementById('rolForm');
                    const formData = new FormData(form);
                    const esModificar = formData.get('accion') === 'modificar';
                    const idRol = formData.get('id_rol');

                    Swal.fire({ title: 'Guardando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const data = await postAccion(Object.fromEntries(formData.entries()));

                        if (data.status === 'success') {
                            const rol = data.rol || { id_rol: idRol, nombre_rol: normalizarNombre(inputNombre.value) };
                            if (esModificar) {
                                actualizarFila(rol);
                            } else {
                                agregarFila(rol);
                            }
                            closeModal();
                            await Swal.fire({
                                icon: 'success',
                                title: esModificar ? 'Modificado' : 'Registrado',
                                text: esModificar ? 'El rol se ha modificado correctamente' : (data.message || 'Rol registrado correctamente')
                            });
                        } else {
                            await Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: mensajeErrorBackend(data, esModificar ? 'No se pudo modificar el rol' : 'No se pudo registrar el rol')
                            });
                        }
                    } catch (err) {
                        console.error('Error al guardar rol:', err);
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la operación. Intente nuevamente.' });
                    } finally {
                        Swal.close();
                    }
                };

                /* ==================== Editar ==================== */
                async function editRol(id) {
                    Swal.fire({ title: 'Cargando rol...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const data = await postAccion({ accion: 'obtener_rol', id_rol: id });
                        Swal.close();
                        if (data && data.id_rol) {
                            document.getElementById('modalTitle').textContent = 'Modificar Rol';
                            document.getElementById('rolId').value = data.id_rol;
                            document.getElementById('nombre_rol').value = data.nombre_rol || '';
                            document.getElementById('rolForm').querySelector('input[name="accion"]').value = 'modificar';
                            if (spanError) spanError.textContent = '';
                            if (btnAyudaModal) btnAyudaModal.dataset.contexto = 'modificar';
                            document.getElementById('rolModal').style.display = 'block';
                        } else {
                            await Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Rol no encontrado' });
                        }
                    } catch (err) {
                        Swal.close();
                        console.error('Error al obtener rol:', err);
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el rol' });
                    }
                }

                /* ==================== Eliminar ==================== */
                async function deleteRol(id, nombre) {
                    const confirm = await Swal.fire({
                        title: '¿Está seguro?',
                        text: '¿Desea eliminar el rol "' + (nombre || id) + '"? ¡No podrá revertir esto!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirm.isConfirmed) return;

                    Swal.fire({ title: 'Eliminando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const data = await postAccion({ accion: 'eliminar', id_rol: id });
                        if (data.status === 'success') {
                            eliminarFila(id);
                            await Swal.fire({ icon: 'success', title: 'Eliminado', text: 'El rol ha sido eliminado correctamente' });
                        } else {
                            await Swal.fire({ icon: 'error', title: 'Error', text: mensajeErrorBackend(data, 'Error al eliminar el rol') });
                        }
                    } catch (err) {
                        console.error('Error al eliminar rol:', err);
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la operación. Intente nuevamente.' });
                    } finally {
                        Swal.close();
                    }
                }

                /* ==================== Delegación de botones ==================== */
                document.addEventListener('click', function (e) {
                    const btnEdit = e.target.closest('.js-btn-edit');
                    const btnDelete = e.target.closest('.js-btn-delete');
                    const btnPermisos = e.target.closest('.js-btn-permisos');
                    if (!btnEdit && !btnDelete && !btnPermisos) return;

                    const id = btnEdit?.dataset.id || btnDelete?.dataset.id || btnPermisos?.dataset.id;
                    const fila = btnEdit?.closest('tr') || btnDelete?.closest('tr') || btnPermisos?.closest('tr');
                    const nombre = fila ? fila.dataset.nombre : '';

                    if (btnEdit) editRol(id);
                    else if (btnDelete) deleteRol(id, nombre);
                    else if (btnPermisos) window.location.href = '?pagina=permiso';
                });

                /* ==================== Permisos en tiempo real ==================== */
                let permisosCache = null;

                function aplicarPermisos() {
                    if (!permisosCache) return;
                    const seccion = document.querySelector('.roles-section');
                    const tablaWrap = document.querySelector('.table-container');

                    if (!permisosCache.consultar) {
                        if (tablaWrap) tablaWrap.style.display = 'none';
                        if (seccion && !document.getElementById('mensaje-permiso-rol')) {
                            const div = document.createElement('div');
                            div.id = 'mensaje-permiso-rol';
                            div.style.cssText = 'color:#e53e3e;text-align:center;margin:20px 0;font-weight:600;';
                            div.textContent = 'No tiene permiso para consultar los registros.';
                            seccion.appendChild(div);
                        }
                    } else {
                        if (tablaWrap) tablaWrap.style.display = '';
                        const msg = document.getElementById('mensaje-permiso-rol');
                        if (msg) msg.remove();
                    }

                    document.querySelectorAll('.js-btn-add-rol').forEach(b => {
                        b.style.display = permisosCache.incluir ? '' : 'none';
                    });
                    document.querySelectorAll('.js-btn-edit').forEach(b => {
                        b.style.display = permisosCache.modificar ? '' : 'none';
                    });
                    document.querySelectorAll('.js-btn-delete').forEach(b => {
                        b.style.display = permisosCache.eliminar ? '' : 'none';
                    });
                }

                async function verificarPermisosEnTiempoReal() {
                    try {
                        const data = await postAccion({ accion: 'permisos_tiempo_real' });
                        permisosCache = data || {};
                        aplicarPermisos();
                    } catch (err) {
                        console.error('Error verificando permisos:', err);
                    }
                }

                verificarPermisosEnTiempoReal();
                setInterval(verificarPermisosEnTiempoReal, 10000);

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
                            const html = await (await fetch('assets/public/ayuda/rolypermisos.php')).text();
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

                document.getElementById('btn-ayuda-rol')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda();
                });

                document.addEventListener('click', function (e) {
                    const btn = e.target.closest('.btn-ayuda-modal');
                    if (!btn) return;
                    e.preventDefault();
                    abrirModalAyuda(btn.dataset.contexto || null);
                });
            })();
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>