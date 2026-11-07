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
        error_log("Error al generar JWT en cliente: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'cliente';
$titulo_pagina = 'Gestión de Clientes';

// Iniciar el buffer de contenido
ob_start();

if (!function_exists('desencriptarCampoCliente')) {
    function desencriptarCampoCliente($valor) {
        if (!is_string($valor) || $valor === '') {
            return $valor;
        }

        if (strlen($valor) <= 24 || !preg_match('/^[A-Za-z0-9+\/=]+$/', $valor)) {
            return $valor;
        }

        try {
            $encryption = new Usuario\ProyectoCasalaiCa\Config\Encryption();
            $descifrado = $encryption->decrypt($valor);
            return is_string($descifrado) && $descifrado !== '' && $descifrado !== $valor ? $descifrado : $valor;
        } catch (Throwable $e) {
            error_log('[VISTA_CLIENTE] Error desencriptando campo: ' . $e->getMessage());
            return $valor;
        }
    }
}

if (is_array($clientes ?? null)) {
    foreach ($clientes as $indice => $cliente) {
        if (!is_array($cliente)) {
            continue;
        }

        foreach (['nombre', 'direccion', 'telefono', 'correo'] as $campo) {
            if (isset($cliente[$campo])) {
                $clientes[$indice][$campo] = desencriptarCampoCliente($cliente[$campo]);
            }
        }
    }
}
?>

<?php
// 1. Cálculos previos de totales y porcentajes
$total_clientes = count($clientes);

$clientes_habilitados = count(array_filter($clientes, function($c) {
    return isset($c['activo']) && (int)$c['activo'] === 1;
}));

$clientes_deshabilitados = count(array_filter($clientes, function($c) {
    return isset($c['activo']) && (int)$c['activo'] === 0;
}));

// Evitar división por cero si no hay clientes registrados
$porcentaje_habilitados = $total_clientes > 0 ? round(($clientes_habilitados / $total_clientes) * 100) : 0;
$porcentaje_deshabilitados = $total_clientes > 0 ? round(($clientes_deshabilitados / $total_clientes) * 100) : 0;
?>

<!-- Summary Cards para Clientes -->
<div class="summary-cards">
    <!-- Total Clientes -->
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-users"></i></div>
        <div class="card-content">
            <h3>Total Clientes</h3>
            <p class="card-value"><?php echo $total_clientes; ?></p>
        </div>
    </div>

    <!-- Clientes Habilitados -->
    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-chart-line"></i></div>
        <div class="card-content">
            <h3>Clientes Habilitados</h3>
            <p class="card-value"><?php echo $clientes_habilitados; ?></p>
            <div class="progress-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <!-- stroke-dasharray ahora es dinámico -->
                    <path class="circle" stroke-dasharray="<?php echo $porcentaje_habilitados; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="percentage"><?php echo $porcentaje_habilitados; ?>%</span>
            </div>
        </div>
    </div>

    <!-- Clientes Deshabilitados -->
    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-chart-line fa-flip-vertical"></i></div>
        <div class="card-content">
            <h3>Clientes Deshabilitados</h3>
            <p class="card-value"><?php echo $clientes_deshabilitados; ?></p>
            <div class="progress-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <!-- stroke-dasharray ahora es dinámico -->
                    <path class="circle" stroke-dasharray="<?php echo $porcentaje_deshabilitados; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="percentage"><?php echo $porcentaje_deshabilitados; ?>%</span>
            </div>
        </div>
    </div>
</div>


            <!-- Clientes Table Section -->
            <div class="clients-section">
                <div class="section-header">
                    <h2>Lista de Clientes</h2>
                    <div class="section-actions">
                        <button class="btn-add-client" onclick="openModal('registrar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Cliente
                        </button>
                        <button class="btn-export" onclick="exportData()">
                            <span class="btn-icon"><i class="fas fa-download"></i></span>
                            Exportar
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="clients-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente) { ?>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar"><?php echo htmlspecialchars(mb_substr($cliente['nombre'] ?? '', 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="client-details">
                                            <span class="client-name"><?php echo htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="client-email"><?php echo htmlspecialchars($cliente['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($cliente['cedula'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="status <?php echo (int)($cliente['activo'] ?? 0) === 1 ? 'active' : 'inactive'; ?>" 
                                      onclick="toggleClientStatus(<?php echo (int)$cliente['id_clientes']; ?>, <?php echo (int)($cliente['activo'] ?? 0); ?>)"
                                      title="Click para cambiar estado">
                                      <?php echo (int)($cliente['activo'] ?? 0) === 1 ? 'Habilitado' : 'Deshabilitado'; ?>
                                </span></td>
                                <td>
                                    <button class="btn-action btn-edit" title="Editar" onclick="openModal('editar', <?php echo (int)$cliente['id_clientes']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar" onclick="deleteClient(<?php echo (int)$cliente['id_clientes']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn-action btn-view" title="Ver detalles" onclick="viewClient(<?php echo (int)$cliente['id_clientes']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Clientes Section -->
            <div class="top-clients-section">
                <h2>Top Clientes por Compras</h2>
                <div class="top-clients-grid">
                    <?php if (!empty($reporteComprasClientes)) { ?>
                    <?php foreach (array_slice($reporteComprasClientes, 0, 3) as $indice => $clienteTop) { ?>
                    <div class="top-client-card">
                        <div class="client-rank"><i class="fas fa-trophy"></i></div>
                        <div class="client-info-top">
                            <h3><?php echo htmlspecialchars($clienteTop['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo (int)($clienteTop['cantidad'] ?? 0); ?> productos comprados</p>
                        </div>
                    </div>
                    <?php } ?>
                    <?php } else { ?>
                    <p>No hay compras de clientes para mostrar.</p>
                    <?php } ?>
                </div>
            </div>

            <!-- Reporte estadístico de compras por cliente -->
            <div class="reporte-container" style="max-width:900px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                <h3 style="text-align:center; color:#1f66df; margin-bottom:16px;">Top 10 Clientes por Productos Comprados</h3>
                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:16px;">
                    <div style="flex:1 1 320px; min-width:0;">
                        <canvas id="graficoComprasClientes" style="width:100%; height:260px;"></canvas>
                    </div>
                    <div style="flex:1 1 320px; min-width:0;">
                        <div style="max-width:100%; overflow-x:auto;">
                        <table class="table table-bordered table-striped" style="margin:0 auto 32px auto; width:100%; min-width:480px;">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Cantidad de <br> Productos Comprados</th>
                                    <th>Porcentaje (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalComprasClientes = isset($totalComprasClientes) ? $totalComprasClientes : array_sum(array_column($reporteComprasClientes ?? [], 'cantidad'));
                                foreach ($reporteComprasClientes ?? [] as $cliente): 
                                    $porcentaje = $totalComprasClientes > 0 ? round(($cliente['cantidad'] / $totalComprasClientes) * 100, 2) : 0;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cliente['nombre'] ?? '') ?></td>
                                        <td><?= $cliente['cantidad'] ?? 0 ?></td>
                                        <td><?= $porcentaje ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th><?= $totalComprasClientes ?></th>
                                    <th>100%</th>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                        <div style="text-align:center; margin-top:20px;">
                            <button id="descargarPDFClientes" class="btn btn-success" style="padding:10px 24px; font-size:16px; border-radius:6px; background:#27ae60; color:#fff; border:none; cursor:pointer;">
                                Descargar Reporte de Compras
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Cliente -->
            <div id="clientModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Cliente</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="clientForm">
                            <input type="hidden" id="clientId" name="id_clientes">
                            <input type="hidden" id="clientStatus" name="activo">

                            <div class="form-group">
                                <label for="clientName">Nombre Completo*</label>
                                <input type="text" id="clientName" name="nombre" required
                                       placeholder="Nombres y apellidos" maxlength="100">
                                <small class="field-error" data-error-for="nombre" aria-live="polite"></small>
                            </div>

                            <div class="form-group">
                                <label for="clientCedula">Cédula*</label>
                                <input type="text" id="clientCedula" name="cedula" required
                                       placeholder="12.345.678" maxlength="10">
                                    <small class="field-error" data-error-for="cedula" aria-live="polite"></small>
                            </div>

                            <div class="form-group">
                                <label for="clientPhone">Teléfono*</label>
                                <input type="text" id="clientPhone" name="telefono" required
                                       placeholder="0414-123-4567" maxlength="13">
                                    <small class="field-error" data-error-for="telefono" aria-live="polite"></small>
                            </div>

                            <div class="form-group">
                                <label for="clientEmail">Correo Electrónico*</label>
                                <input type="email" id="clientEmail" name="correo" required
                                       placeholder="ejemplo@email.com" maxlength="50">
                                    <small class="field-error" data-error-for="correo" aria-live="polite"></small>
                            </div>

                            <div class="form-group">
                                <label for="clientAddress">Dirección*</label>
                                <textarea id="clientAddress" name="direccion" required
                                          placeholder="Estado/Ciudad/Calle o Avenida..." rows="3" maxlength="100"></textarea>
                                <small class="field-error" data-error-for="direccion" aria-live="polite"></small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveClient()">Guardar</button>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalles del Cliente -->
            <div id="clientDetailsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Detalles del Cliente</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="client-details-content">
                            <div class="detail-row">
                                <span class="detail-label">Nombre:</span>
                                <span class="detail-value" id="detailName">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Cédula:</span>
                                <span class="detail-value" id="detailCedula">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Teléfono:</span>
                                <span class="detail-value" id="detailPhone">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Correo:</span>
                                <span class="detail-value" id="detailEmail">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Dirección:</span>
                                <span class="detail-value" id="detailAddress">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value" id="detailStatus">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-close" onclick="closeDetailsModal()">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Clientes */
                .clients-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .section-header h2 {
                    color: #333;
                    margin: 0;
                    font-size: 1.5rem;
                }

                .section-actions {
                    display: flex;
                    gap: 10px;
                }

                .btn-add-client, .btn-export {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .btn-add-client:hover, .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                .btn-export {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                }

                .clients-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .clients-table th {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .clients-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .clients-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .clients-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .clients-table tr:hover {
                    background-color: #f8f9fa;
                }

                .client-info {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .client-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1.1rem;
                }

                .client-details {
                    display: flex;
                    flex-direction: column;
                }

                .client-name {
                    font-weight: 600;
                    color: #333;
                }

                .client-email {
                    font-size: 0.85rem;
                    color: #666;
                }

                .status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s;
                    user-select: none;
                }

                .status:hover {
                    transform: scale(1.05);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                }

                .status.active {
                    background-color: #d4edda;
                    color: #155724;
                }

                .status.inactive {
                    background-color: #f8d7da;
                    color: #721c24;
                }

                .btn-action {
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 5px;
                    transition: transform 0.2s;
                    margin-right: 5px;
                }

                .btn-action:hover {
                    transform: scale(1.2);
                }

                .btn-edit:hover {
                    color: #2196F3;
                }

                .btn-delete:hover {
                    color: #dc3545;
                }

                .btn-view:hover {
                    color: #28a745;
                }

                .top-clients-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .top-clients-section h2 {
                    color: #333;
                    margin: 0 0 20px 0;
                    font-size: 1.5rem;
                }

                .top-clients-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .top-client-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .top-client-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .client-rank {
                    font-size: 2.5rem;
                }

                .client-rank i {
                    color: #ffd700;
                }

                .client-info-top h3 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1.1rem;
                }

                .client-info-top p {
                    margin: 0 0 8px 0;
                    color: #666;
                    font-size: 0.9rem;
                }

                .total-spent {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 4px 10px;
                    border-radius: 12px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #333;
                }

                .form-group input, .form-group textarea {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                    transition: border-color 0.3s;
                }

                .form-group input:focus, .form-group textarea:focus {
                    outline: none;
                    border-color: #2196F3;
                }

                #clientModal.modal, #clientDetailsModal.modal {
                    overflow-y: auto;
                    padding: 16px 0;
                }

                #clientModal .modal-content, #clientDetailsModal .modal-content {
                    display: flex;
                    flex-direction: column;
                    max-height: calc(100vh - 32px);
                    margin: 0 auto;
                }

                #clientModal .modal-body, #clientDetailsModal .modal-body {
                    min-height: 0;
                    overflow-y: auto;
                    flex: 1 1 auto;
                }

                #clientModal .modal-header, #clientModal .modal-footer,
                #clientDetailsModal .modal-header, #clientDetailsModal .modal-footer {
                    flex: 0 0 auto;
                }

                .field-error {
                    display: block;
                    min-height: 0;
                    color: #b42318;
                    font-size: 0.85rem;
                    margin-top: 5px;
                }

                .field-error:empty {
                    display: none;
                }

                .btn-cancel, .btn-close {
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                    margin-right: 10px;
                }

                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                }

                .client-details-content {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .reporte-container {
                    max-width: 900px;
                    margin: 40px auto;
                    background: #fff;
                    padding: 32px 24px;
                    border-radius: 12px;
                    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
                }

                .reporte-container table {
                    margin: 0 auto 32px auto;
                    width: 100%;
                    min-width: 480px;
                }
            </style>

            <!-- Cargar librerías externas -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script>
                // Funciones para modales
                const clientModal = document.getElementById('clientModal');
                const detailsModal = document.getElementById('clientDetailsModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#clientModal .close-modal');
                const closeDetailsBtn = document.querySelector('#clientDetailsModal .close-modal');
                const clientForm = document.getElementById('clientForm');
                const clientFields = Array.from(clientForm.querySelectorAll('[name]'));

                function clearClientValidation() {
                    document.querySelectorAll('#clientForm .field-error').forEach((field) => {
                        field.textContent = '';
                    });
                }

                function validateClientForm() {
                    const values = Object.fromEntries(new FormData(clientForm).entries());
                    values.nombre = (values.nombre || '').replace(/\s{2,}/g, ' ').trim();
                    values.direccion = (values.direccion || '').replace(/\s{2,}/g, ' ').trim();
                    values.cedula = (values.cedula || '').trim();
                    values.telefono = (values.telefono || '').trim();
                    values.correo = (values.correo || '').trim();
                    clientForm.elements.nombre.value = values.nombre;
                    clientForm.elements.direccion.value = values.direccion;
                    clientForm.elements.cedula.value = values.cedula;
                    clientForm.elements.telefono.value = values.telefono;
                    clientForm.elements.correo.value = values.correo;

                    const rules = [
                        { field: 'nombre', pattern: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,100}$/, message: 'Ingrese un nombre de 2 a 100 caracteres, solo con letras y espacios.' },
                        { field: 'cedula', pattern: /^(?:\d{1,2}\.\d{3}\.\d{3})$/, message: 'Use el formato de cédula 1.234.567 o 12.345.678.' },
                        { field: 'telefono', pattern: /^\d{4}-\d{3}-\d{4}$/, message: 'Use el formato telefónico 0400-000-0000.' },
                        { field: 'direccion', pattern: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s]{4,100}$/, message: 'La dirección debe tener de 4 a 100 caracteres y solo admite letras, números, comas y guiones.' },
                        { field: 'correo', pattern: /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/, message: 'Use un correo de Gmail, Outlook, Yahoo o iCloud.' }
                    ];
                    const errors = {};
                    clearClientValidation();

                    rules.forEach(({ field, pattern, message }) => {
                        if (!pattern.test(values[field])) {
                            errors[field] = message;
                            document.querySelector(`[data-error-for="${field}"]`).textContent = message;
                        }
                    });

                    return { values, errors };
                }

                async function showClientError(title, message) {
                    return Swal.fire({ icon: 'error', title, text: message, confirmButtonText: 'Entendido' });
                }

                clientForm.elements.cedula.addEventListener('input', function() {
                    const digits = this.value.replace(/\D/g, '').slice(0, 8);
                    if (digits.length === 7) this.value = `${digits.slice(0, 1)}.${digits.slice(1, 4)}.${digits.slice(4)}`;
                    else if (digits.length === 8) this.value = `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
                    else this.value = digits;
                });

                clientForm.elements.telefono.addEventListener('input', function() {
                    const digits = this.value.replace(/\D/g, '').slice(0, 11);
                    if (digits.length > 7) this.value = `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
                    else if (digits.length > 4) this.value = `${digits.slice(0, 4)}-${digits.slice(4)}`;
                    else this.value = digits;
                });

                clientFields.forEach((field) => field.addEventListener('input', () => {
                    const error = document.querySelector(`[data-error-for="${field.name}"]`);
                    if (error) error.textContent = '';
                }));

                async function openModal(type, clientId = null) {
                    if (type === 'registrar') {
                        modalTitle.textContent = 'Agregar Cliente';
                        clientForm.reset();
                        document.getElementById('clientId').value = '';
                        document.getElementById('clientStatus').value = '1'; // Por defecto activo al registrar
                        clearClientValidation();
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Cliente';
                        const cliente = await fetchClient(clientId);
                        if (!cliente) return;
                        clientForm.reset();
                        clearClientValidation();
                        document.getElementById('clientId').value = cliente.id_clientes;
                        document.getElementById('clientName').value = cliente.nombre || '';
                        document.getElementById('clientCedula').value = cliente.cedula || '';
                        document.getElementById('clientPhone').value = cliente.telefono || '';
                        document.getElementById('clientEmail').value = cliente.correo || '';
                        document.getElementById('clientAddress').value = cliente.direccion || '';
                        document.getElementById('clientStatus').value = cliente.activo || '1'; // Mantener estado actual
                    }
                    clientModal.style.display = 'block';
                }

                async function postClientAction(action, values = {}) {
                    const formData = new FormData();
                    formData.append('accion', action);
                    Object.entries(values).forEach(([key, value]) => formData.append(key, value));
                    const response = await fetch(window.location.href, { method: 'POST', body: formData });
                    return response.json();
                }

                async function fetchClient(clientId) {
                    try {
                        const result = await postClientAction('obtener_clientes', { id_clientes: clientId });
                        if (result.status === 'error') throw new Error(result.message || 'No se pudo obtener el cliente.');
                        return result;
                    } catch (error) {
                        await showClientError('No se pudo consultar el cliente', error.message || 'Intente nuevamente.');
                        return null;
                    }
                }

                function closeModal() {
                    clientModal.style.display = 'none';
                }

                async function viewClient(clientId) {
                    const cliente = await fetchClient(clientId);
                    if (!cliente) return;
                    document.getElementById('detailName').textContent = cliente.nombre || '-';
                    document.getElementById('detailCedula').textContent = cliente.cedula || '-';
                    document.getElementById('detailPhone').textContent = cliente.telefono || '-';
                    document.getElementById('detailEmail').textContent = cliente.correo || '-';
                    document.getElementById('detailAddress').textContent = cliente.direccion || '-';
                    document.getElementById('detailStatus').textContent = Number(cliente.activo) === 1 ? 'Habilitado' : 'Deshabilitado';
                    detailsModal.style.display = 'block';
                }

                function closeDetailsModal() {
                    detailsModal.style.display = 'none';
                }

                async function saveClient() {
                    const { values, errors } = validateClientForm();
                    if (Object.keys(errors).length) {
                        const firstInvalidField = clientForm.elements[Object.keys(errors)[0]];
                        await Swal.fire({
                            icon: 'error',
                            title: 'Revisa los datos del cliente',
                            html: `<ul style="text-align:left;margin:0;padding-left:20px">${Object.values(errors).map((message) => `<li>${message}</li>`).join('')}</ul>`,
                            confirmButtonText: 'Entendido'
                        });
                        firstInvalidField.focus();
                        return;
                    }
                    const isEditing = Boolean(values.id_clientes);
                    try {
                        const result = await postClientAction(isEditing ? 'modificar' : 'registrar', values);
                        if (result.status !== 'success') {
                            throw new Error((result.errors ? Object.values(result.errors).join('\n') : '') || result.message || 'No se pudo guardar el cliente.');
                        }
                        closeModal();
                        // Mostrar SweetAlert de éxito antes de recargar
                        await Swal.fire({
                            icon: 'success',
                            title: isEditing ? 'Cliente modificado' : 'Cliente registrado',
                            text: result.message || 'La operación se completó correctamente',
                            confirmButtonText: 'Entendido'
                        });
                        window.location.reload();
                    } catch (error) {
                        await showClientError('No se pudo guardar el cliente', error.message || 'Intente nuevamente.');
                    }
                }

                async function deleteClient(clientId) {
                    const confirmation = await Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar este cliente?',
                        text: 'Esta acción no se puede deshacer.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        confirmButtonColor: '#dc3545'
                    });
                    if (!confirmation.isConfirmed) return;

                    try {
                        const result = await postClientAction('eliminar', { id_clientes: clientId });
                        if (result.status !== 'success') {
                            throw new Error((result.errors ? Object.values(result.errors).join('\n') : '') || result.message || 'No se pudo eliminar el cliente.');
                        }
                        await Swal.fire({ icon: 'success', title: 'Cliente eliminado', text: result.message || 'El cliente se eliminó correctamente.' });
                        window.location.reload();
                    } catch (error) {
                        await showClientError('No se pudo eliminar el cliente', error.message || 'Intente nuevamente.');
                    }
                }

                function exportData() {
                    const rows = Array.from(document.querySelectorAll('.clients-table tbody tr'));
                    const data = [['Nombre', 'Cédula', 'Teléfono', 'Correo', 'Estado']];
                    rows.forEach((row) => {
                        const cells = row.cells;
                        data.push([
                            cells[0].querySelector('.client-name')?.textContent.trim() || '',
                            cells[1].textContent.trim(),
                            cells[2].textContent.trim(),
                            cells[3].textContent.trim(),
                            cells[4].textContent.trim()
                        ]);
                    });
                    const csv = data.map((row) => row.map((value) => `"${value.replace(/"/g, '""')}"`).join(',')).join('\r\n');
                    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'clientes.csv';
                    link.click();
                    URL.revokeObjectURL(url);
                }

                // Función para cambiar el estado del cliente
                async function toggleClientStatus(clientId, currentStatus) {
                    const confirmation = await Swal.fire({
                        icon: 'question',
                        title: '¿Cambiar estado del cliente?',
                        text: currentStatus === 1 ? 'El cliente será deshabilitado' : 'El cliente será habilitado',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        confirmButtonColor: '#2196F3'
                    });

                    if (!confirmation.isConfirmed) return;

                    try {
                        const formData = new FormData();
                        formData.append('accion', 'cambiar_estado');
                        formData.append('id_clientes', clientId);
                        formData.append('estado_actual', currentStatus);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Estado cambiado',
                                text: result.message,
                                confirmButtonText: 'Entendido'
                            });
                            window.location.reload();
                        } else {
                            throw new Error(result.message || 'No se pudo cambiar el estado');
                        }
                    } catch (error) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo cambiar el estado del cliente',
                            confirmButtonText: 'Entendido'
                        });
                    }
                }

                // Event listeners para cerrar modales
                closeModalBtn.addEventListener('click', closeModal);
                closeDetailsBtn.addEventListener('click', closeDetailsModal);

                window.addEventListener('click', function(event) {
                    if (event.target === clientModal) {
                        closeModal();
                    }
                    if (event.target === detailsModal) {
                        closeDetailsModal();
                    }
                });

                // Inicializar gráfico de compras de clientes
                <?php if (!empty($reporteComprasClientes)): ?>
                const labelsClientes = <?= json_encode(array_column($reporteComprasClientes, 'nombre')) ?>;
                const dataClientes = <?= json_encode(array_column($reporteComprasClientes, 'cantidad')) ?>;
                const ctxClientes = document.getElementById('graficoComprasClientes');
                if (ctxClientes) {
                    new Chart(ctxClientes.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: labelsClientes,
                            datasets: [{
                                label: 'Productos comprados',
                                data: dataClientes,
                                backgroundColor: 'rgba(39, 174, 96, 0.7)',
                                borderColor: 'rgba(39, 174, 96, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            plugins: {
                                legend: { display: false },
                                title: { display: true, text: 'Top 10 Clientes por Productos Comprados' }
                            },
                            scales: {
                                x: { beginAtZero: true }
                            }
                        }
                    });
                }

                // Función para descargar PDF del reporte
                document.getElementById('descargarPDFClientes').addEventListener('click', function () {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF({
                        orientation: 'landscape',
                        unit: 'pt',
                        format: 'a4'
                    });

                    const reporte = document.querySelector('.reporte-container');
                    html2canvas(reporte).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const pageWidth = doc.internal.pageSize.getWidth();
                        const imgWidth = pageWidth - 40;
                        const imgHeight = canvas.height * imgWidth / canvas.width;

                        doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                        doc.save('Reporte_Compras_Clientes.pdf');
                    });
                });
                <?php endif; ?>
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>