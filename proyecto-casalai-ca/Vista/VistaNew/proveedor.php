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
        error_log("Error al generar JWT en proveedor: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'proveedor';
$titulo_pagina = 'Gestión de Proveedores';

// Iniciar el buffer de contenido
ob_start();
?>

<?php
// Cálculos previos para summary cards
$total_proveedores = count($proveedores ?? []);
$proveedores_activos = count(array_filter($proveedores ?? [], function($p) { return ($p['estado'] ?? '') === 'habilitado'; }));
$total_recepciones = count($recepcionesPorProveedor ?? []);

// Calcular porcentajes
$porcentaje_total = 100;
$porcentaje_activos = $total_proveedores > 0 ? round(($proveedores_activos / $total_proveedores) * 100) : 0;
?>

            <!-- Summary Cards para Proveedores -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-building"></i></div>
                    <div class="card-content">
                        <h3>Total Proveedores</h3>
                        <p class="card-value"><?php echo $total_proveedores; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_total; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_total; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-star"></i></div>
                    <div class="card-content">
                        <h3>Activos</h3>
                        <p class="card-value"><?php echo $proveedores_activos; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_activos; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_activos; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-inbox"></i></div>
                    <div class="card-content">
                        <h3>Recepciones registradas</h3>
                        <p class="card-value"><?php echo $total_recepciones; ?></p>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="action-buttons">
                <button class="btn-add-provider" data-provider-add hidden onclick="openModal('agregar')">
                    <span class="btn-icon"><i class="fas fa-plus"></i></span>
                    Agregar Proveedor
                </button>
                <button class="btn-export" onclick="exportProviders()">
                    <span class="btn-icon"><i class="fas fa-upload"></i></span>
                    Exportar
                </button>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-bar">
                    <input type="text" id="searchProvider" placeholder="Buscar proveedor..." onkeyup="searchProviders()">
                    <span class="search-icon"><i class="fas fa-search"></i></span>
                </div>
                <div class="filter-options">
                    <select id="statusFilter" onchange="filterByStatus()">
                        <option value="">Todos los estados</option>
                        <option value="habilitado">Habilitados</option>
                        <option value="inhabilitado">Inhabilitados</option>
                    </select>
                </div>
            </div>

            <!-- Grid de Proveedores -->
            <div class="providers-grid">
                <?php if (!empty($proveedores)): ?>
                    <?php foreach ($proveedores as $proveedor): ?>
                        <?php
                        $idProveedor = (int)($proveedor['id_proveedor'] ?? 0);
                        $nombreProveedor = (string)($proveedor['nombre_proveedor'] ?? '');
                        $estadoProveedor = ($proveedor['estado'] ?? '') === 'habilitado' ? 'habilitado' : 'inhabilitado';
                        $claseEstado = $estadoProveedor === 'habilitado' ? 'active' : 'inactive';
                        $escapeProveedor = static function($valor) {
                            $texto = (string)($valor ?? '');
                            $binario = base64_decode($texto, true);
                            if ($binario !== false && strlen($binario) >= 4 && unpack('N', substr($binario, 0, 4))[1] === 256) {
                                $texto = 'Dato cifrado no disponible';
                            }
                            return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
                        };
                        ?>
                        <article class="provider-card" data-provider-card data-status="<?= $escapeProveedor($estadoProveedor) ?>">
                            <div class="provider-header">
                                <div class="provider-avatar" aria-hidden="true">
                                    <span class="avatar-initial"><?= $escapeProveedor(mb_substr($nombreProveedor, 0, 1, 'UTF-8') ?: '?') ?></span>
                                </div>
                                <button type="button" class="provider-status <?= $claseEstado ?>" data-provider-status data-id="<?= $idProveedor ?>"
                                        data-current-status="<?= $escapeProveedor($estadoProveedor) ?>" title="Cambiar estado">
                                    <span class="status-dot"></span>
                                    <?= $estadoProveedor === 'habilitado' ? 'Habilitado' : 'Inhabilitado' ?>
                                </button>
                            </div>
                            <div class="provider-body">
                                <h3><?= $escapeProveedor($nombreProveedor) ?></h3>
                                <p class="provider-contact"><?= $escapeProveedor($proveedor['correo_proveedor'] ?? '') ?: 'Sin correo registrado' ?></p>
                                <p class="provider-phone"><?= $escapeProveedor($proveedor['telefono_1'] ?? '') ?: 'Sin teléfono registrado' ?></p>
                                <div class="provider-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">RIF</span>
                                        <span class="stat-value provider-rif"><?= $escapeProveedor($proveedor['rif_proveedor'] ?? '') ?: 'No registrado' ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Representante</span>
                                        <span class="stat-value provider-representative"><?= $escapeProveedor($proveedor['nombre_representante'] ?? '') ?: 'No registrado' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="provider-actions">
                                <button type="button" class="btn-action btn-view" data-provider-action="consultar" hidden onclick="viewProvider(<?= $idProveedor ?>)" title="Ver detalles" aria-label="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn-action btn-edit" data-provider-action="modificar" hidden onclick="editProvider(<?= $idProveedor ?>)" title="Editar" aria-label="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-action btn-delete" data-provider-action="eliminar" hidden onclick="deleteProvider(<?= $idProveedor ?>)" title="Eliminar" aria-label="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="providers-empty">No hay proveedores registrados.</p>
                <?php endif; ?>
            </div>
            <p id="providerNoResults" class="providers-empty" hidden>No hay proveedores que coincidan con los filtros.</p>
            <p id="providerPermissionMessage" class="providers-empty" <?= empty($permisosUsuario['consultar']) ? '' : 'hidden' ?>>No tienes permiso para consultar proveedores.</p>

            <!-- Modal para Agregar/Editar Proveedor -->
            <div id="providerModal" class="modal">
                <div class="modal-content provider-modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Proveedor</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="providerForm">
                            <input type="hidden" id="providerId" name="id_proveedor">
                            
                            <div class="form-group">
                                <label for="providerName">Nombre del Proveedor*</label>
                                <input type="text" id="providerName" name="nombre_proveedor" maxlength="200" required
                                       placeholder="Nombre del proveedor">
                            </div>
                            
                            <div class="form-group">
                                <label for="providerRif">RIF del Proveedor*</label>
                                <input type="text" id="providerRif" name="rif_proveedor" maxlength="20" required
                                       placeholder="J-12345678-9">
                            </div>
                            
                            <div class="form-group">
                                <label for="providerRepresentative">Nombre del Representante*</label>
                                <input type="text" id="providerRepresentative" name="nombre_representante" maxlength="200" required
                                       placeholder="Nombre del representante">
                            </div>

                            <div class="form-group">
                                <label for="providerRepRif">RIF del Representante*</label>
                                <input type="text" id="providerRepRif" name="rif_representante" maxlength="20" required
                                       placeholder="V-12345678-9">
                            </div>

                            <div class="form-group">
                                <label for="providerEmail">Correo</label>
                                <input type="email" id="providerEmail" name="correo_proveedor" maxlength="255"
                                       placeholder="contacto@empresa.com">
                            </div>

                            <div class="form-group">
                                <label for="providerPhone1">Teléfono principal</label>
                                <input type="tel" id="providerPhone1" name="telefono_1" maxlength="20"
                                       placeholder="0414-1234567">
                            </div>

                            <div class="form-group">
                                <label for="providerPhone2">Teléfono secundario</label>
                                <input type="tel" id="providerPhone2" name="telefono_2" maxlength="20"
                                       placeholder="0212-1234567">
                            </div>

                            <div class="form-group">
                                <label for="providerAddress">Dirección</label>
                                <textarea id="providerAddress" name="direccion_proveedor" maxlength="500" rows="2"
                                          placeholder="Dirección física"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="providerObservation">Observación</label>
                                <textarea id="providerObservation" name="observacion" maxlength="1000" rows="2"
                                          placeholder="Observaciones del proveedor"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" type="button" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" type="button" onclick="saveProvider()">Guardar</button>
                    </div>
                </div>
            </div>

            <div id="providerDetailsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Detalles del proveedor</h2>
                        <span class="close-modal" id="closeProviderDetails" role="button" tabindex="0" aria-label="Cerrar">&times;</span>
                    </div>
                    <div class="modal-body">
                        <dl class="provider-details-grid">
                            <div><dt>Proveedor</dt><dd id="detailProviderName">-</dd></div>
                            <div><dt>RIF</dt><dd id="detailProviderRif">-</dd></div>
                            <div><dt>Representante</dt><dd id="detailProviderRepresentative">-</dd></div>
                            <div><dt>RIF del representante</dt><dd id="detailProviderRepRif">-</dd></div>
                            <div><dt>Correo</dt><dd id="detailProviderEmail">-</dd></div>
                            <div><dt>Teléfono principal</dt><dd id="detailProviderPhone1">-</dd></div>
                            <div><dt>Teléfono secundario</dt><dd id="detailProviderPhone2">-</dd></div>
                            <div><dt>Dirección</dt><dd id="detailProviderAddress">-</dd></div>
                            <div><dt>Observación</dt><dd id="detailProviderObservation">-</dd></div>
                            <div><dt>Estado</dt><dd id="detailProviderStatus">-</dd></div>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" type="button" onclick="closeProviderDetails()">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Proveedores */
                .action-buttons {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 30px;
                }

                .btn-add-provider, .btn-import, .btn-export {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .btn-add-provider:hover, .btn-import:hover, .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                .btn-import {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                }

                .btn-export {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .filters-section {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 20px;
                }

                .search-bar {
                    flex: 1;
                    position: relative;
                }

                .search-bar input {
                    width: 100%;
                    padding: 12px 40px 12px 15px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                }

                .search-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: 1.2rem;
                }

                .filter-options {
                    display: flex;
                    gap: 10px;
                }

                .filter-options select {
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                }

                .providers-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 25px;
                }

                .provider-card {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .provider-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .provider-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    padding: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .provider-avatar {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2196F3;
                }

                .provider-status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    border: 0;
                    cursor: pointer;
                    font-family: inherit;
                }

                .provider-modal-content {
                    display: flex;
                    flex-direction: column;
                    max-height: 90vh;
                }

                .provider-modal-content .modal-body {
                    overflow-y: auto;
                }

                .provider-details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 16px;
                    margin: 0;
                }

                .provider-details-grid div {
                    min-width: 0;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }

                .provider-details-grid dt {
                    color: #667085;
                    font-size: 0.85rem;
                    margin-bottom: 4px;
                }

                .provider-details-grid dd {
                    margin: 0;
                    color: #222;
                    overflow-wrap: anywhere;
                    white-space: pre-wrap;
                }

                .providers-empty {
                    grid-column: 1 / -1;
                    text-align: center;
                    color: #667085;
                    padding: 24px;
                }

                .providers-grid [hidden], [hidden] {
                    display: none !important;
                }

                .provider-status.active {
                    background: rgba(67, 233, 123, 0.2);
                    color: #155724;
                }

                .provider-status.inactive {
                    background: rgba(245, 87, 108, 0.2);
                    color: #721c24;
                }

                .status-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                }

                .provider-status.active .status-dot {
                    background: #28a745;
                }

                .provider-status.inactive .status-dot {
                    background: #dc3545;
                }

                .provider-body {
                    padding: 20px;
                }

                .provider-body h3 {
                    margin: 0 0 10px 0;
                    color: #333;
                    font-size: 1.1rem;
                }

                .provider-contact, .provider-phone {
                    margin: 5px 0;
                    color: #666;
                    font-size: 0.9rem;
                }

                .provider-stats {
                    display: flex;
                    gap: 20px;
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #eee;
                }

                .stat-item {
                    display: flex;
                    flex-direction: column;
                }

                .stat-label {
                    font-size: 0.75rem;
                    color: #666;
                }

                .stat-value {
                    font-weight: 700;
                    color: #2196F3;
                }

                .provider-actions {
                    padding: 15px 20px;
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    border-top: 1px solid #eee;
                }

                .btn-action {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    border: none;
                    cursor: pointer;
                    font-size: 1.2rem;
                    transition: transform 0.2s;
                }

                .btn-action:hover {
                    transform: scale(1.1);
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
                    
                    .providers-grid {
                        grid-template-columns: 1fr;
                    }

                    .provider-details-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script>
                const providerModal = document.getElementById('providerModal');
                const providerDetailsModal = document.getElementById('providerDetailsModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#providerModal .close-modal');
                const closeProviderDetailsBtn = document.getElementById('closeProviderDetails');
                const providerForm = document.getElementById('providerForm');
                const providerDebug = [];
                window.providerDebug = providerDebug;

                function logProviderAction(action, detail = {}) {
                    const entry = { action, detail, timestamp: new Date().toISOString() };
                    providerDebug.push(entry);
                    console.debug('[Proveedores]', entry);
                }

                function escapeProviderHtml(value) {
                    const element = document.createElement('span');
                    element.textContent = String(value ?? '');
                    return element.innerHTML;
                }

                async function postProviderAction(action, values = {}) {
                    const formData = new FormData();
                    formData.append('accion', action);
                    Object.entries(values).forEach(([key, value]) => formData.append(key, value ?? ''));
                    logProviderAction('POST', { action, id_proveedor: values.id_proveedor ?? null });

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: { Accept: 'application/json' }
                    });
                    const result = await response.json();
                    logProviderAction('respuesta', { action, httpStatus: response.status, status: result.status });
                    if (!response.ok) throw new Error(result.message || 'Error de comunicación con el servidor.');
                    return result;
                }

                async function fetchProvider(providerId) {
                    const result = await postProviderAction('obtener_proveedor', { id_proveedor: providerId });
                    if (result.status !== 'success' || !result.proveedor) {
                        throw new Error(result.message || 'No se pudo obtener el proveedor.');
                    }
                    return result.proveedor;
                }

                async function openModal(type, providerId = null) {
                    providerForm.reset();
                    document.getElementById('providerId').value = '';

                    if (type === 'agregar') {
                        modalTitle.textContent = 'Agregar Proveedor';
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Proveedor';
                        try {
                            const proveedor = await fetchProvider(providerId);
                            Object.entries({
                                id_proveedor: proveedor.id_proveedor,
                                nombre_proveedor: proveedor.nombre_proveedor,
                                rif_proveedor: proveedor.rif_proveedor,
                                nombre_representante: proveedor.nombre_representante,
                                rif_representante: proveedor.rif_representante,
                                correo_proveedor: proveedor.correo_proveedor,
                                telefono_1: proveedor.telefono_1,
                                telefono_2: proveedor.telefono_2,
                                direccion_proveedor: proveedor.direccion_proveedor,
                                observacion: proveedor.observacion
                            }).forEach(([field, value]) => {
                                providerForm.elements[field].value = value ?? '';
                            });
                        } catch (error) {
                            await Swal.fire({ icon: 'error', title: 'No se pudo abrir el proveedor', text: error.message });
                            return;
                        }
                    }

                    providerModal.style.display = 'block';
                }

                async function viewProvider(providerId) {
                    try {
                        const proveedor = await fetchProvider(providerId);
                        const fields = {
                            detailProviderName: proveedor.nombre_proveedor,
                            detailProviderRif: proveedor.rif_proveedor,
                            detailProviderRepresentative: proveedor.nombre_representante,
                            detailProviderRepRif: proveedor.rif_representante,
                            detailProviderEmail: proveedor.correo_proveedor,
                            detailProviderPhone1: proveedor.telefono_1,
                            detailProviderPhone2: proveedor.telefono_2,
                            detailProviderAddress: proveedor.direccion_proveedor,
                            detailProviderObservation: proveedor.observacion,
                            detailProviderStatus: proveedor.estado
                        };
                        Object.entries(fields).forEach(([id, value]) => {
                            document.getElementById(id).textContent = providerDetailValue(value) || '-';
                        });
                        providerDetailsModal.style.display = 'block';
                    } catch (error) {
                        await Swal.fire({ icon: 'error', title: 'No se pudo consultar el proveedor', text: error.message });
                    }
                }

                function providerDetailValue(value) {
                    const text = String(value ?? '').trim();
                    if (!text || !/^[A-Za-z0-9+/]+={0,2}$/.test(text) || text.length <= 24) return text;
                    try {
                        const decoded = atob(text);
                        const keyLength = decoded.length >= 4
                            ? ((decoded.charCodeAt(0) << 24) | (decoded.charCodeAt(1) << 16) | (decoded.charCodeAt(2) << 8) | decoded.charCodeAt(3)) >>> 0
                            : 0;
                        return keyLength === 256 ? 'Dato cifrado no disponible' : text;
                    } catch (error) {
                        return text;
                    }
                }

                function closeProviderDetails() {
                    providerDetailsModal.style.display = 'none';
                }

                async function saveProvider() {
                    if (!providerForm.reportValidity()) return;
                    const values = Object.fromEntries(new FormData(providerForm).entries());
                    const isEditing = Boolean(values.id_proveedor);
                    try {
                        const result = await postProviderAction(isEditing ? 'modificar' : 'registrar', values);
                        if (result.status !== 'success') {
                            const errors = result.field_errors || result.errors || {};
                            const messages = Object.values(errors);
                            if (messages.length) {
                                const list = messages.map((message) => `<li>${escapeProviderHtml(message)}</li>`).join('');
                                throw new Error(messages.join('\n'));
                            }
                            throw new Error(result.message || 'No se pudo guardar el proveedor.');
                        }
                        providerModal.style.display = 'none';
                        await Swal.fire({
                            icon: 'success',
                            title: isEditing ? 'Proveedor modificado' : 'Proveedor registrado',
                            text: result.message || 'La operación se completó correctamente.'
                        });
                        window.location.reload();
                    } catch (error) {
                        await Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: error.message });
                    }
                }

                async function deleteProvider(providerId) {
                    const confirmation = await Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar este proveedor?',
                        text: 'No se podrá eliminar si tiene recepciones asociadas.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        confirmButtonColor: '#dc3545'
                    });
                    if (!confirmation.isConfirmed) return;

                    try {
                        const result = await postProviderAction('eliminar', { id_proveedor: providerId });
                        if (result.status !== 'success') throw new Error(result.message || 'No se pudo eliminar el proveedor.');
                        await Swal.fire({ icon: 'success', title: 'Proveedor eliminado', text: result.message || 'El proveedor fue eliminado.' });
                        window.location.reload();
                    } catch (error) {
                        await Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: error.message });
                    }
                }

                async function toggleProviderStatus(button) {
                    const currentStatus = button.dataset.currentStatus;
                    const nextStatus = currentStatus === 'habilitado' ? 'inhabilitado' : 'habilitado';
                    const confirmation = await Swal.fire({
                        icon: 'question',
                        title: '¿Cambiar estado del proveedor?',
                        text: nextStatus === 'habilitado' ? 'El proveedor quedará habilitado.' : 'El proveedor quedará inhabilitado.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirmation.isConfirmed) return;

                    try {
                        const result = await postProviderAction('cambiar_estado', {
                            id_proveedor: button.dataset.id,
                            nuevo_estatus: nextStatus
                        });
                        if (result.status !== 'success') throw new Error(result.message || 'No se pudo cambiar el estado.');
                        await Swal.fire({ icon: 'success', title: 'Estado actualizado', text: result.message || 'El estado se actualizó correctamente.' });
                        window.location.reload();
                    } catch (error) {
                        await Swal.fire({ icon: 'error', title: 'Error al cambiar el estado', text: error.message });
                    }
                }

                function applyProviderFilters() {
                    const query = document.getElementById('searchProvider').value.trim().toLocaleLowerCase();
                    const status = document.getElementById('statusFilter').value;
                    const cards = Array.from(document.querySelectorAll('[data-provider-card]'));
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const matchesText = !query || card.textContent.toLocaleLowerCase().includes(query);
                        const matchesStatus = !status || card.dataset.status === status;
                        const visible = matchesText && matchesStatus;
                        card.hidden = !visible;
                        if (visible) visibleCount += 1;
                    });

                    document.getElementById('providerNoResults').hidden = cards.length === 0 || visibleCount > 0;
                }

                function searchProviders() {
                    applyProviderFilters();
                }

                function filterByStatus() {
                    applyProviderFilters();
                }

                function exportProviders() {
                    const data = [['Proveedor', 'RIF', 'Representante', 'Correo', 'Teléfono', 'Estado']];
                    document.querySelectorAll('[data-provider-card]:not([hidden])').forEach((card) => {
                        data.push([
                            card.querySelector('.provider-body h3')?.textContent.trim() || '',
                            card.querySelector('.provider-rif')?.textContent.trim() || '',
                            card.querySelector('.provider-representative')?.textContent.trim() || '',
                            card.querySelector('.provider-contact')?.textContent.trim() || '',
                            card.querySelector('.provider-phone')?.textContent.trim() || '',
                            card.dataset.status || ''
                        ]);
                    });
                    const csv = data.map((row) => row.map((value) => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\r\n');
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
                    link.href = url;
                    link.download = 'proveedores.csv';
                    link.click();
                    URL.revokeObjectURL(url);
                }

                function can(permission, permissions) {
                    return permissions[permission] === true || permissions[permission] === 1 || permissions[permission] === '1';
                }

                async function refreshProviderPermissions() {
                    try {
                        const permissions = await postProviderAction('permisos_tiempo_real');
                        const canConsult = can('consultar', permissions);
                        document.querySelector('.providers-grid').hidden = !canConsult;
                        document.getElementById('providerPermissionMessage').hidden = canConsult;
                        document.querySelector('[data-provider-add]').hidden = !can('incluir', permissions);
                        document.querySelectorAll('[data-provider-action="consultar"]').forEach((button) => {
                            button.hidden = !canConsult;
                        });
                        document.querySelectorAll('[data-provider-action="modificar"], [data-provider-status]').forEach((button) => {
                            button.hidden = !can('modificar', permissions);
                        });
                        document.querySelectorAll('[data-provider-action="eliminar"]').forEach((button) => {
                            button.hidden = !can('eliminar', permissions);
                        });
                    } catch (error) {
                        logProviderAction('permisos error', { message: error.message });
                        document.querySelectorAll('[data-provider-action], [data-provider-status]').forEach((button) => {
                            button.hidden = true;
                        });
                        document.querySelector('[data-provider-add]').hidden = true;
                    }
                }

                function closeModal() {
                    providerModal.style.display = 'none';
                }

                window.openModal = openModal;
                window.closeModal = closeModal;
                window.saveProvider = saveProvider;
                window.viewProvider = viewProvider;
                window.editProvider = (providerId) => openModal('editar', providerId);
                window.deleteProvider = deleteProvider;
                window.searchProviders = searchProviders;
                window.filterByStatus = filterByStatus;
                window.exportProviders = exportProviders;
                window.closeProviderDetails = closeProviderDetails;

                closeModalBtn.addEventListener('click', closeModal);
                closeProviderDetailsBtn.addEventListener('click', closeProviderDetails);
                closeProviderDetailsBtn.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') closeProviderDetails();
                });
                document.querySelectorAll('[data-provider-status]').forEach((button) => {
                    button.addEventListener('click', () => toggleProviderStatus(button));
                });

                window.addEventListener('click', function(event) {
                    if (event.target === providerModal) {
                        closeModal();
                    }
                    if (event.target === providerDetailsModal) {
                        closeProviderDetails();
                    }
                });

                refreshProviderPermissions();
                window.setInterval(refreshProviderPermissions, 10000);
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>