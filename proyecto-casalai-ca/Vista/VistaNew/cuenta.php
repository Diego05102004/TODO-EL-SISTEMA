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
        error_log("Error al generar JWT en cuenta: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'cuenta';
$titulo_pagina = 'Gestión de Cuentas Bancarias';

// Filtrar cuentas válidas (excluir IDs 0 y 1, como la vista anterior)
$cuentasValidas = array_values(array_filter($cuentabancos ?? [], function($c) {
    return !in_array((int)($c['id_cuenta'] ?? 0), [0, 1]);
}));

$totalCuentas = count($cuentasValidas);
$cuentasHabilitadas = count(array_filter($cuentasValidas, function($c) {
    return ($c['estado'] ?? '') === 'habilitado';
}));
$cuentasUsd = count(array_filter($cuentasValidas, function($c) {
    $metodos = $c['metodos'] ?? '';
    return strpos($metodos, 'Zelle') !== false;
}));

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Cuentas Bancarias -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-university"></i></div>
                    <div class="card-content">
                        <h3>Total Cuentas</h3>
                        <p class="card-value" id="cuenta-count-total"><?php echo $totalCuentas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="100, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalCuentas > 0 ? round(($cuentasHabilitadas / $totalCuentas) * 100) : 0; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-content">
                        <h3>Cuentas Habilitadas</h3>
                        <p class="card-value" id="cuenta-count-habilitadas"><?php echo $cuentasHabilitadas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalCuentas > 0 ? round(($cuentasHabilitadas / $totalCuentas) * 100) : 0; ?>, 100" stroke="#28a745" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalCuentas > 0 ? round(($cuentasHabilitadas / $totalCuentas) * 100) : 0; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Cuentas en USD</h3>
                        <p class="card-value" id="cuenta-count-usd"><?php echo $cuentasUsd; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalCuentas > 0 ? round(($cuentasUsd / $totalCuentas) * 100) : 0; ?>, 100" stroke="#ffc107" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalCuentas > 0 ? round(($cuentasUsd / $totalCuentas) * 100) : 0; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cuentas Bancarias Section -->
            <div class="cuentas-section" id="contenedor-cuentas">
                <div class="section-header">
                    <h2>Cuentas Bancarias</h2>
                    <div class="section-actions">
                        <select id="filtro-estatus-cuentas" class="form-select filtro-estatus">
                            <option value="todos" selected>Todos los estatus</option>
                            <option value="habilitado">Habilitadas</option>
                            <option value="inhabilitado">Inhabilitadas</option>
                        </select>
                        <button class="btn-add-cuenta" id="btn-add-cuenta">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Cuenta
                        </button>
                    </div>
                </div>

                <div id="mensaje-permiso" style="display:none; color:#d30000; text-align:center; margin:20px 0; font-weight:600;"></div>

                <!-- Tabla de Cuentas -->
                <div class="table-container">
                    <table class="cuentas-table" id="tablaCuentas" data-datatable data-page-length="15" data-datatable-order="[[0,\"desc\"]]" data-datatable-no-order="9" data-datatable-no-search="9">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Banco</th>
                                <th>Número de Cuenta</th>
                                <th>RIF</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Métodos de Pago</th>
                                <th>Moneda</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuentasValidas as $cuenta): ?>
                                <?php echo buildCuentaRow($cuenta); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Cuenta -->
            <div id="cuentaModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Agregar Cuenta Bancaria</h2>
                                <p id="modalSubtitle">Complete los datos para registrar una nueva cuenta</p>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <button type="button" class="btn-ayuda-modal" id="btn-ayuda-modal-cuenta" title="Ayuda" data-contexto="registrar">
                                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                            </button>
                            <span class="close-modal" onclick="closeCuentaModal()">&times;</span>
                        </div>
                    </div>
                    <div class="modal-body">
                        <form id="cuentaForm" novalidate>
                            <input type="hidden" id="cuentaAccion" name="accion" value="registrar">
                            <input type="hidden" id="cuentaId" name="id_cuenta">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre_banco">
                                        <i class="fas fa-building"></i>
                                        Nombre del Banco*
                                    </label>
                                    <input type="text" id="nombre_banco" name="nombre_banco" required
                                           placeholder="Ej: Banco de Venezuela" maxlength="20">
                                    <span class="span-value error-message" id="snombre_banco"></span>
                                </div>

                                <div class="form-group">
                                    <label for="numero_cuenta">
                                        <i class="fas fa-hashtag"></i>
                                        Número de Cuenta*
                                    </label>
                                    <input type="text" id="numero_cuenta" name="numero_cuenta" required
                                           placeholder="0100-0000-00-0000000000" maxlength="23">
                                    <span class="span-value error-message" id="snumero_cuenta"></span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="rif_cuenta">
                                        <i class="fas fa-id-card"></i>
                                        RIF*
                                    </label>
                                    <input type="text" id="rif_cuenta" name="rif_cuenta" required
                                           placeholder="V-12345678-9" maxlength="12">
                                    <span class="span-value error-message" id="srif_cuenta"></span>
                                </div>

                                <div class="form-group">
                                    <label for="telefono_cuenta">
                                        <i class="fas fa-phone"></i>
                                        Teléfono*
                                    </label>
                                    <input type="text" id="telefono_cuenta" name="telefono_cuenta" required
                                           placeholder="0400-000-0000" maxlength="13">
                                    <span class="span-value error-message" id="stelefono_cuenta"></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="correo_cuenta">
                                    <i class="fas fa-envelope"></i>
                                    Correo*
                                </label>
                                <input type="email" id="correo_cuenta" name="correo_cuenta" required
                                       placeholder="Ej: contacto@banco.com" maxlength="50">
                                <span class="span-value error-message" id="scorreo_cuenta"></span>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipo_moneda">
                                        <i class="fas fa-dollar-sign"></i>
                                        Tipo de Moneda*
                                    </label>
                                    <select id="tipo_moneda" name="tipo_moneda" required>
                                        <option value="">Seleccione moneda</option>
                                        <option value="bs">Bolívares (Bs)</option>
                                        <option value="usd">Dólares (USD)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="label-checkbox">
                                    <i class="fas fa-credit-card"></i>
                                    Métodos de Pago Aceptados*
                                </label>
                                <div class="metodos-bs" id="metodosBs">
                                    <div class="metodo-check">
                                        <input type="checkbox" class="btn-check" value="Pago Movil" id="pagoMovil" name="metodos_pago[]" autocomplete="off">
                                        <label class="btn-metodo" for="pagoMovil">Pago Móvil</label>
                                    </div>
                                    <div class="metodo-check">
                                        <input type="checkbox" class="btn-check" value="Transferencia" id="transferencia" name="metodos_pago[]" autocomplete="off">
                                        <label class="btn-metodo" for="transferencia">Transferencia</label>
                                    </div>
                                </div>
                                <div class="metodos-usd" id="metodosUsd" style="display:none;">
                                    <div class="metodo-check">
                                        <input type="checkbox" class="btn-check" value="Zelle" id="zelle" name="metodos_pago[]" autocomplete="off">
                                        <label class="btn-metodo" for="zelle">Zelle</label>
                                    </div>
                                </div>
                                <span class="span-value error-message" id="smetodos_pago"></span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeCuentaModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" id="btn-save-cuenta">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalles de Cuenta -->
            <div id="detalleModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2>Detalles de la Cuenta</h2>
                                <p>Información completa de la cuenta bancaria</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeDetalleModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="detalle-grid">
                            <div class="detalle-item"><label>ID</label><p id="detalle-id"></p></div>
                            <div class="detalle-item"><label>Nombre del Banco</label><p id="detalle-nombre"></p></div>
                            <div class="detalle-item"><label>Número de Cuenta</label><p id="detalle-numero"></p></div>
                            <div class="detalle-item"><label>RIF</label><p id="detalle-rif"></p></div>
                            <div class="detalle-item"><label>Teléfono</label><p id="detalle-telefono"></p></div>
                            <div class="detalle-item"><label>Correo</label><p id="detalle-correo"></p></div>
                            <div class="detalle-item"><label>Métodos de Pago</label><p id="detalle-metodos"></p></div>
                            <div class="detalle-item"><label>Moneda</label><p id="detalle-moneda"></p></div>
                            <div class="detalle-item"><label>Estatus</label><p id="detalle-estado"></p></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeDetalleModal()">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Cuentas Bancarias */
                .cuentas-section {
                    margin-top: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                    gap: 15px;
                }

                .section-header h2 {
                    margin: 0;
                    color: #2d3748;
                    font-size: 1.4rem;
                }

                .section-actions {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .filtro-estatus {
                    padding: 10px 14px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.9rem;
                    background: white;
                    cursor: pointer;
                }

                .filtro-estatus:focus {
                    border-color: #2196F3;
                    outline: none;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                    overflow-x: auto;
                }

                .cuentas-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .cuentas-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .cuentas-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.85rem;
                    white-space: nowrap;
                }

                .cuentas-table td {
                    padding: 12px 15px;
                    border-bottom: 1px solid #e9ecef;
                    font-size: 0.9rem;
                    color: #4a5568;
                }

                .cuentas-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .metodos-lista {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                }

                .metodo-tag {
                    background: rgba(33, 150, 243, 0.12);
                    color: #1976D2;
                    padding: 3px 9px;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    white-space: nowrap;
                }

                .moneda-badge {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.78rem;
                    font-weight: 700;
                    display: inline-block;
                }

                .moneda-badge.usd {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                    color: white;
                }

                .moneda-badge.bs {
                    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                    color: white;
                }

                .estatus-badge {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.78rem;
                    font-weight: 700;
                    cursor: pointer;
                    display: inline-block;
                    transition: all 0.2s;
                    user-select: none;
                }

                .estatus-badge.habilitado {
                    background: rgba(40, 167, 69, 0.15);
                    color: #28a745;
                    border: 1px solid #28a745;
                }

                .estatus-badge.inhabilitado {
                    background: rgba(220, 53, 69, 0.12);
                    color: #dc3545;
                    border: 1px solid #dc3545;
                }

                .estatus-badge:hover {
                    transform: scale(1.05);
                }

                .btn-add-cuenta {
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

                .btn-add-cuenta:hover {
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
                    margin-right: 6px;
                }

                .btn-view {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-edit {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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

                /* Métodos de pago (checkboxes tipo botón) */
                .label-checkbox {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 10px;
                }

                .label-checkbox i {
                    color: #2196F3;
                }

                .metodos-bs, .metodos-usd {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .metodo-check {
                    position: relative;
                }

                .btn-check {
                    position: absolute;
                    opacity: 0;
                    pointer-events: none;
                }

                .btn-metodo {
                    display: inline-block;
                    padding: 9px 18px;
                    border: 2px solid #2196F3;
                    border-radius: 8px;
                    color: #2196F3;
                    font-weight: 600;
                    font-size: 0.88rem;
                    cursor: pointer;
                    transition: all 0.2s;
                    background: white;
                    user-select: none;
                }

                .btn-check:checked + .btn-metodo {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border-color: #1976D2;
                }

                .btn-metodo:hover {
                    background: rgba(33, 150, 243, 0.08);
                }

                .btn-check:checked + .btn-metodo:hover {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }

                /* Modal styles */
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
                    overflow-y: auto;
                }

                .modal-content {
                    background-color: #fff;
                    margin: 4% auto;
                    border-radius: 12px;
                    width: 92%;
                    max-width: 640px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                    animation: slideDown 0.3s;
                    overflow: hidden;
                }

                .modal-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 20px 25px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

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
                    font-size: 1.3rem;
                    font-weight: 700;
                    margin: 0 0 4px 0;
                }

                .modal-title-content p {
                    font-size: 0.82rem;
                    opacity: 0.9;
                    margin: 0;
                }

                .btn-ayuda-modal {
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    border-radius: 8px;
                    padding: 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background 0.2s;
                }

                .btn-ayuda-modal:hover {
                    background: rgba(255, 255, 255, 0.35);
                }

                .close-modal {
                    font-size: 30px;
                    font-weight: bold;
                    cursor: pointer;
                    color: white;
                    line-height: 1;
                }

                .close-modal:hover { color: #ddd; }

                .modal-body { padding: 25px; }

                .modal-footer {
                    padding: 18px 25px;
                    text-align: right;
                    border-top: 1px solid #dee2e6;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }

                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 18px;
                }

                .form-group {
                    margin-bottom: 16px;
                }

                .form-group label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #2d3748;
                    font-size: 0.9rem;
                }

                .form-group label i {
                    color: #2196F3;
                }

                .form-group input,
                .form-group select {
                    width: 100%;
                    padding: 11px 14px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.92rem;
                    transition: all 0.3s ease;
                    box-sizing: border-box;
                    background: white;
                }

                .form-group input:focus,
                .form-group select:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .span-value.error-message {
                    display: block;
                    margin-top: 6px;
                    font-size: 0.8rem;
                    color: #d30000;
                    font-style: italic;
                    min-height: 1em;
                }

                .detalle-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 16px;
                }

                .detalle-item label {
                    display: block;
                    font-weight: 600;
                    color: #718096;
                    font-size: 0.78rem;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 4px;
                }

                .detalle-item p {
                    margin: 0;
                    color: #2d3748;
                    font-size: 0.95rem;
                    font-weight: 600;
                    word-break: break-word;
                }

                .btn-cancel, .btn-save {
                    padding: 11px 22px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.92rem;
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

                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-cancel:hover, .btn-save:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideDown {
                    from { transform: translateY(-40px); opacity: 0; }
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
                    .form-row {
                        grid-template-columns: 1fr;
                    }

                    .detalle-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <button class="btn-ayuda" id="btn-ayuda-cuenta" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=cuenta';
                const ESTATUS_COL = 8;

                /* ==================== Utilidades ==================== */
                function esc(str) {
                    const div = document.createElement('div');
                    div.textContent = str == null ? '' : String(str);
                    return div.innerHTML;
                }

                function space(str) {
                    return (str || '').toString().replace(/\s{2,}/g, ' ');
                }

                function setError(spanId, mensaje) {
                    const el = document.getElementById(spanId);
                    if (el) el.textContent = mensaje || '';
                }

                function deducirMoneda(metodos) {
                    const m = (metodos || '').toLowerCase();
                    return (m.includes('zelle')) ? 'usd' : 'bs';
                }

                function monedaLabel(metodos) {
                    return deducirMoneda(metodos) === 'usd' ? 'USD' : 'Bs';
                }

                async function postCuenta(formData) {
                    const resp = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: formData
                    });
                    let data = null;
                    try { data = await resp.json(); } catch (e) { data = null; }
                    if (!resp.ok || !data) throw new Error('Respuesta inválida del servidor');
                    return data;
                }

                function mostrarErroresValidacion(errors) {
                    if (!errors || typeof errors !== 'object') return;
                    const mapa = {
                        nombre_banco: 'snombre_banco',
                        numero_cuenta: 'snumero_cuenta',
                        rif_cuenta: 'srif_cuenta',
                        telefono_cuenta: 'stelefono_cuenta',
                        correo_cuenta: 'scorreo_cuenta',
                        metodos_pago: 'smetodos_pago'
                    };
                    Object.keys(errors).forEach(function (k) {
                        const spanId = mapa[k];
                        if (spanId) setError(spanId, errors[k]);
                    });
                }

                async function enviarAccion(formData, opciones) {
                    opciones = opciones || {};
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    try {
                        const data = await postCuenta(formData);
                        Swal.close();
                        if (data.status === 'success') {
                            await Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.message || opciones.successMsg || 'Operación completada',
                                timer: 1600,
                                showConfirmButton: false
                            });
                            if (opciones.onSuccess) opciones.onSuccess(data);
                        } else {
                            mostrarErroresValidacion(data.errors);
                            await Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo completar la operación'
                            });
                        }
                    } catch (err) {
                        console.error('Error POST cuenta:', err);
                        Swal.close();
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la operación. Intente nuevamente.' });
                    }
                }

                /* ==================== Renderizado de filas ==================== */
                function buildMetodosHtml(metodos) {
                    const lista = (metodos || '').split(',').map(s => s.trim()).filter(Boolean);
                    if (!lista.length) return '<span style="color:#a0aec0;">-</span>';
                    return '<div class="metodos-lista">' + lista.map(m =>
                        '<span class="metodo-tag">' + esc(m) + '</span>'
                    ).join('') + '</div>';
                }

                function buildAccionesHtml(cuenta) {
                    return '<button class="btn-action btn-view" data-id="' + cuenta.id_cuenta + '" title="Ver Detalles"><i class="fas fa-eye"></i></button>' +
                           '<button class="btn-action btn-edit" data-id="' + cuenta.id_cuenta + '" title="Modificar Cuenta"><i class="fas fa-edit"></i></button>' +
                           '<button class="btn-action btn-delete" data-id="' + cuenta.id_cuenta + '" title="Eliminar Cuenta"><i class="fas fa-trash"></i></button>';
                }

                function buildRowData(cuenta) {
                    const estado = cuenta.estado || 'habilitado';
                    const moneda = monedaLabel(cuenta.metodos);
                    return [
                        '<span style="font-weight:700;color:#2196F3;">' + esc(cuenta.id_cuenta) + '</span>',
                        esc(cuenta.nombre_banco || '-'),
                        '<span style="font-family:monospace;">' + esc(cuenta.numero_cuenta || '-') + '</span>',
                        esc(cuenta.rif_cuenta || '-'),
                        esc(cuenta.telefono_cuenta || '-'),
                        esc(cuenta.correo_cuenta || '-'),
                        buildMetodosHtml(cuenta.metodos),
                        '<span class="moneda-badge ' + deducirMoneda(cuenta.metodos) + '">' + moneda + '</span>',
                        '<span class="estatus-badge ' + estado + '" data-id="' + cuenta.id_cuenta + '" title="Clic para cambiar estatus">' + estado + '</span>',
                        buildAccionesHtml(cuenta)
                    ];
                }

                function obtenerTabla() {
                    const tabla = document.getElementById('tablaCuentas');
                    if (!tabla) return null;
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#tablaCuentas')) {
                        return jQuery('#tablaCuentas').DataTable();
                    }
                    return null;
                }

                function agregarFila(cuenta) {
                    const dt = obtenerTabla();
                    if (dt) {
                        dt.row.add(buildRowData(cuenta)).draw(false);
                    } else {
                        const tbody = document.querySelector('#tablaCuentas tbody');
                        if (!tbody) return;
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', cuenta.id_cuenta);
                        buildRowData(cuenta).forEach(function (html, i) {
                            const td = document.createElement('td');
                            td.innerHTML = html;
                            tr.appendChild(td);
                        });
                        tbody.insertBefore(tr, tbody.firstChild);
                    }
                    actualizarContadores();
                }

                function actualizarFila(cuenta) {
                    const dt = obtenerTabla();
                    if (dt) {
                        const fila = dt.row('tr[data-id="' + cuenta.id_cuenta + '"]');
                        if (fila.length) {
                            fila.data(buildRowData(cuenta)).draw(false);
                            return;
                        }
                    }
                    // Respaldo DOM
                    const tr = document.querySelector('#tablaCuentas tbody tr[data-id="' + cuenta.id_cuenta + '"]');
                    if (tr) {
                        const tds = tr.querySelectorAll('td');
                        buildRowData(cuenta).forEach(function (html, i) {
                            if (tds[i]) tds[i].innerHTML = html;
                        });
                    }
                    actualizarContadores();
                }

                function eliminarFila(id) {
                    const dt = obtenerTabla();
                    if (dt) {
                        const fila = dt.row('tr[data-id="' + id + '"]');
                        if (fila.length) { fila.remove().draw(false); actualizarContadores(); return; }
                    }
                    const tr = document.querySelector('#tablaCuentas tbody tr[data-id="' + id + '"]');
                    if (tr) { tr.remove(); actualizarContadores(); }
                }

                function actualizarContadores() {
                    const filas = document.querySelectorAll('#tablaCuentas tbody tr');
                    let total = 0, hab = 0, usd = 0;
                    filas.forEach(function (tr) {
                        const badge = tr.querySelector('.estatus-badge');
                        const moneda = tr.querySelector('.moneda-badge');
                        if (!badge) return;
                        total++;
                        if (badge.textContent.trim() === 'habilitado') hab++;
                        if (moneda && moneda.classList.contains('usd')) usd++;
                    });
                    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                    set('cuenta-count-total', total);
                    set('cuenta-count-habilitadas', hab);
                    set('cuenta-count-usd', usd);
                }

                /* ==================== Modal registrar/modificar ==================== */
                const cuentaModal = document.getElementById('cuentaModal');
                const cuentaForm = document.getElementById('cuentaForm');
                const tipoMoneda = document.getElementById('tipo_moneda');
                const metodosBs = document.getElementById('metodosBs');
                const metodosUsd = document.getElementById('metodosUsd');

                function aplicarMoneda() {
                    const esUsd = tipoMoneda.value === 'usd';
                    metodosBs.style.display = esUsd ? 'none' : 'flex';
                    metodosUsd.style.display = esUsd ? 'flex' : 'none';
                    if (esUsd) {
                        // Desmarcar métodos Bs y viceversa
                        document.querySelectorAll('#metodosBs input').forEach(i => i.checked = false);
                    } else if (tipoMoneda.value === 'bs') {
                        document.querySelectorAll('#metodosUsd input').forEach(i => i.checked = false);
                    } else {
                        document.querySelectorAll('#cuentaForm input[type="checkbox"]').forEach(i => i.checked = false);
                    }
                }

                function resetCuentaForm() {
                    cuentaForm.reset();
                    document.getElementById('cuentaAccion').value = 'registrar';
                    document.getElementById('cuentaId').value = '';
                    document.getElementById('modalTitle').textContent = 'Agregar Cuenta Bancaria';
                    document.getElementById('modalSubtitle').textContent = 'Complete los datos para registrar una nueva cuenta';
                    document.getElementById('btn-ayuda-modal-cuenta').dataset.contexto = 'registrar';
                    ['snombre_banco', 'snumero_cuenta', 'srif_cuenta', 'stelefono_cuenta', 'scorreo_cuenta', 'smetodos_pago'].forEach(id => setError(id, ''));
                    aplicarMoneda();
                }

                function openCuentaModal() {
                    cuentaModal.style.display = 'block';
                }

                function closeCuentaModal() {
                    cuentaModal.style.display = 'none';
                }

                window.closeCuentaModal = closeCuentaModal;

                document.getElementById('btn-add-cuenta')?.addEventListener('click', function () {
                    resetCuentaForm();
                    openCuentaModal();
                });

                tipoMoneda?.addEventListener('change', aplicarMoneda);

                /* ---- Validaciones en vivo ---- */
                function addLiveValidation(inputId, spanId, regex, mensaje, formatter) {
                    const input = document.getElementById(inputId);
                    if (!input) return;
                    if (formatter) {
                        input.addEventListener('input', function () { this.value = formatter(this.value); });
                    }
                    input.addEventListener('blur', function () {
                        const ok = regex.test(this.value.trim());
                        setError(spanId, ok ? '' : mensaje);
                    });
                }

                addLiveValidation('nombre_banco', 'snombre_banco', /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\-\.\&']{3,20}$/, '*Solo letras, de 3 a 20 caracteres*',
                    function (v) { return space(v).replace(/[^a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\-\.\&']/g, ''); });

                addLiveValidation('numero_cuenta', 'snumero_cuenta', /^\d{4}-\d{4}-\d{2}-\d{10}$/, '*Formato válido: 0100-0000-00-0000000000*',
                    function (v) {
                        let d = v.replace(/\D/g, '');
                        if (d.length > 4 && d.length <= 8) d = d.slice(0,4) + '-' + d.slice(4);
                        else if (d.length > 8 && d.length <= 10) d = d.slice(0,4) + '-' + d.slice(4,8) + '-' + d.slice(8,10);
                        else if (d.length > 10) d = d.slice(0,4) + '-' + d.slice(4,8) + '-' + d.slice(8,10) + '-' + d.slice(10,20);
                        return d;
                    });

                addLiveValidation('rif_cuenta', 'srif_cuenta', /^[VEJPG]-\d{8}-\d$/, '*Formato válido: (VEJPG)-12345678-9*',
                    function (v) {
                        let val = v.toUpperCase().replace(/[^A-Z0-9]/g, '');
                        let res = '';
                        if (val.length > 0) {
                            const letra = val.charAt(0);
                            if ('VEJPG'.includes(letra)) res = letra;
                            const nums = val.substring(1).replace(/\D/g, '');
                            if (nums.length > 0) {
                                res += '-' + nums.substring(0, 8);
                                if (nums.length > 8) res += '-' + nums.substring(8, 9);
                            }
                        }
                        return res;
                    });

                addLiveValidation('telefono_cuenta', 'stelefono_cuenta', /^\d{4}-\d{3}-\d{4}$/, '*Formato válido: 0400-000-0000*',
                    function (v) {
                        let d = v.replace(/\D/g, '');
                        if (d.length > 4 && d.length <= 7) d = d.slice(0,4) + '-' + d.slice(4);
                        else if (d.length > 7) d = d.slice(0,4) + '-' + d.slice(4,7) + '-' + d.slice(7,11);
                        return d;
                    });

                const CORREO_REGEX = /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/;
                addLiveValidation('correo_cuenta', 'scorreo_cuenta', CORREO_REGEX, '*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*');

                /* ---- Envío del formulario (registrar/modificar) ---- */
                document.getElementById('btn-save-cuenta')?.addEventListener('click', async function () {
                    const accion = document.getElementById('cuentaAccion').value;

                    // Validación cliente
                    const checks = [
                        ['nombre_banco', 'snombre_banco', /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\-\.\&']{3,20}$/, '*Solo letras, de 3 a 20 caracteres*'],
                        ['numero_cuenta', 'snumero_cuenta', /^\d{4}-\d{4}-\d{2}-\d{10}$/, '*Formato válido: 0100-0000-00-0000000000*'],
                        ['rif_cuenta', 'srif_cuenta', /^[VEJPG]-\d{8}-\d$/, '*Formato válido: (VEJPG)-12345678-9*'],
                        ['telefono_cuenta', 'stelefono_cuenta', /^\d{4}-\d{3}-\d{4}$/, '*Formato válido: 0400-000-0000*'],
                        ['correo_cuenta', 'scorreo_cuenta', CORREO_REGEX, '*Debe ser un correo @gmail.com, @outlook.com, @yahoo.com o @icloud.com*']
                    ];
                    let valido = true;
                    checks.forEach(function (c) {
                        const input = document.getElementById(c[0]);
                        const ok = input && c[2].test(input.value.trim());
                        setError(c[1], ok ? '' : c[3]);
                        if (!ok) valido = false;
                    });

                    if (!tipoMoneda.value) {
                        setError('smetodos_pago', '*Debe seleccionar el tipo de moneda*');
                        valido = false;
                    }

                    const metodosMarcados = cuentaForm.querySelectorAll('input[name="metodos_pago[]"]:checked');
                    const monedaActual = tipoMoneda.value;
                    const metodoVisible = monedaActual === 'usd' ? '#metodosUsd' : '#metodosBs';
                    const visibles = cuentaForm.querySelectorAll(metodoVisible + ' input[name="metodos_pago[]"]:checked');
                    if (visibles.length === 0) {
                        setError('smetodos_pago', '*Debe seleccionar al menos un método de pago*');
                        valido = false;
                    }

                    if (!valido) {
                        await Swal.fire({ icon: 'error', title: 'Verifique los datos', text: 'Hay campos con formato inválido o incompletos' });
                        return;
                    }

                    // Desmarcar checkboxes del grupo oculto para no enviarlos
                    cuentaForm.querySelectorAll('input[name="metodos_pago[]"]').forEach(function (cb) {
                        if (!cb.closest(metodoVisible)) cb.checked = false;
                    });

                    const fd = new FormData(cuentaForm);
                    fd.set('accion', accion);

                    await enviarAccion(fd, {
                        successMsg: accion === 'modificar' ? 'Cuenta modificada correctamente' : 'Cuenta registrada correctamente',
                        onSuccess: function (data) {
                            closeCuentaModal();
                            if (accion === 'modificar' && data.cuenta) {
                                actualizarFila(data.cuenta);
                            } else if (data.cuenta) {
                                agregarFila(data.cuenta);
                            } else {
                                location.reload();
                            }
                        }
                    });
                });

                /* ==================== Detalles ==================== */
                const detalleModal = document.getElementById('detalleModal');

                function closeDetalleModal() {
                    detalleModal.style.display = 'none';
                }
                window.closeDetalleModal = closeDetalleModal;

                document.querySelector('#tablaCuentas tbody')?.addEventListener('click', function (e) {
                    const btnVer = e.target.closest('.btn-view');
                    const btnEditar = e.target.closest('.btn-edit');
                    const btnEliminar = e.target.closest('.btn-delete');
                    const badgeEstatus = e.target.closest('.estatus-badge');
                    if (!btnVer && !btnEditar && !btnEliminar && !badgeEstatus) return;

                    const tr = e.target.closest('tr[data-id]');
                    const id = tr ? tr.getAttribute('data-id') : null;
                    if (!id) return;

                    if (badgeEstatus) {
                        cambiarEstatus(id, badgeEstatus);
                    } else if (btnVer) {
                        verDetalle(id);
                    } else if (btnEditar) {
                        editarCuenta(id);
                    } else if (btnEliminar) {
                        eliminarCuenta(id);
                    }
                });

                async function obtenerCuenta(id) {
                    const fd = new FormData();
                    fd.append('accion', 'obtener_cuenta');
                    fd.append('id_cuenta', id);
                    const data = await postCuenta(fd);
                    return data;
                }

                async function verDetalle(id) {
                    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const cuenta = await obtenerCuenta(id);
                        Swal.close();
                        if (!cuenta || cuenta.status === 'error') {
                            await Swal.fire({ icon: 'error', title: 'Error', text: (cuenta && cuenta.message) || 'No se pudo obtener la cuenta' });
                            return;
                        }
                        document.getElementById('detalle-id').textContent = cuenta.id_cuenta || id;
                        document.getElementById('detalle-nombre').textContent = cuenta.nombre_banco || '-';
                        document.getElementById('detalle-numero').textContent = cuenta.numero_cuenta || '-';
                        document.getElementById('detalle-rif').textContent = cuenta.rif_cuenta || '-';
                        document.getElementById('detalle-telefono').textContent = cuenta.telefono_cuenta || '-';
                        document.getElementById('detalle-correo').textContent = cuenta.correo_cuenta || '-';
                        document.getElementById('detalle-metodos').textContent = cuenta.metodos || '-';
                        document.getElementById('detalle-moneda').textContent = monedaLabel(cuenta.metodos);
                        document.getElementById('detalle-estado').textContent = cuenta.estado || '-';
                        detalleModal.style.display = 'block';
                    } catch (err) {
                        Swal.close();
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener la cuenta' });
                    }
                }

                async function editarCuenta(id) {
                    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const cuenta = await obtenerCuenta(id);
                        Swal.close();
                        if (!cuenta || cuenta.status === 'error') {
                            await Swal.fire({ icon: 'error', title: 'Error', text: (cuenta && cuenta.message) || 'No se pudo obtener la cuenta' });
                            return;
                        }
                        resetCuentaForm();
                        document.getElementById('cuentaAccion').value = 'modificar';
                        document.getElementById('cuentaId').value = cuenta.id_cuenta;
                        document.getElementById('modalTitle').textContent = 'Modificar Cuenta Bancaria';
                        document.getElementById('modalSubtitle').textContent = 'Actualice los datos de la cuenta';
                        document.getElementById('btn-ayuda-modal-cuenta').dataset.contexto = 'modificar';

                        document.getElementById('nombre_banco').value = cuenta.nombre_banco || '';
                        document.getElementById('numero_cuenta').value = cuenta.numero_cuenta || '';
                        document.getElementById('rif_cuenta').value = cuenta.rif_cuenta || '';
                        document.getElementById('telefono_cuenta').value = cuenta.telefono_cuenta || '';
                        document.getElementById('correo_cuenta').value = cuenta.correo_cuenta || '';

                        const moneda = deducirMoneda(cuenta.metodos);
                        tipoMoneda.value = moneda;
                        aplicarMoneda();

                        const metodos = (cuenta.metodos || '').split(',').map(s => s.trim());
                        document.querySelectorAll('#cuentaForm input[name="metodos_pago[]"]').forEach(function (cb) {
                            cb.checked = metodos.includes(cb.value);
                        });

                        openCuentaModal();
                    } catch (err) {
                        Swal.close();
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener la cuenta' });
                    }
                }

                async function eliminarCuenta(id) {
                    const result = await Swal.fire({
                        title: '¿Está seguro?',
                        text: '¡No podrá revertir esto!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminarla',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!result.isConfirmed) return;

                    const fd = new FormData();
                    fd.append('accion', 'eliminar');
                    fd.append('id_cuenta', id);

                    Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const data = await postCuenta(fd);
                        Swal.close();
                        if (data.status === 'success') {
                            await Swal.fire({ icon: 'success', title: '¡Eliminada!', text: 'La cuenta ha sido eliminada.', timer: 1600, showConfirmButton: false });
                            eliminarFila(id);
                        } else {
                            let html = '<p style="text-align:left;">' + esc(data.message || 'No se pudo eliminar la cuenta') + '</p>';
                            if (data.total_pagos > 0) {
                                html += '<p style="text-align:left;margin-top:10px;"><strong>Total de pagos registrados: ' + data.total_pagos + '</strong></p>';
                                if (Array.isArray(data.pagos) && data.pagos.length) {
                                    html += '<div style="max-height:180px;overflow-y:auto;text-align:left;margin-top:8px;font-size:0.85rem;">'
                                        + '<table style="width:100%;border-collapse:collapse;">'
                                        + '<tr style="background:#f1f5f9;"><th style="padding:6px;border:1px solid #e2e8f0;">Cliente</th><th style="padding:6px;border:1px solid #e2e8f0;">Cédula</th><th style="padding:6px;border:1px solid #e2e8f0;">Monto</th><th style="padding:6px;border:1px solid #e2e8f0;">Fecha</th><th style="padding:6px;border:1px solid #e2e8f0;">Factura</th></tr>';
                                    data.pagos.forEach(function (p) {
                                        const cliente = esc((p.nombre || '') + ' ' + (p.apellido || ''));
                                        html += '<tr><td style="padding:6px;border:1px solid #e2e8f0;">' + cliente + '</td>'
                                            + '<td style="padding:6px;border:1px solid #e2e8f0;">' + esc(p.cedula || '-') + '</td>'
                                            + '<td style="padding:6px;border:1px solid #e2e8f0;">' + esc(p.monto || '-') + '</td>'
                                            + '<td style="padding:6px;border:1px solid #e2e8f0;">' + esc(p.fecha || p.fecha_pago || '-') + '</td>'
                                            + '<td style="padding:6px;border:1px solid #e2e8f0;">' + esc(p.numero_factura || '-') + '</td></tr>';
                                    });
                                    html += '</table></div>';
                                    if (data.total_pagos > data.pagos.length) {
                                        html += '<p style="font-size:0.8rem;color:#718096;margin-top:6px;">Mostrando los últimos ' + data.pagos.length + ' de ' + data.total_pagos + ' pagos</p>';
                                    }
                                }
                            }
                            mostrarErroresValidacion(data.errors);
                            await Swal.fire({
                                icon: 'error',
                                title: 'No se puede eliminar',
                                html: html
                            });
                        }
                    } catch (err) {
                        Swal.close();
                        await Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la operación. Intente nuevamente.' });
                    }
                }

                async function cambiarEstatus(id, badge) {
                    const estadoActual = badge.textContent.trim();
                    const nuevoEstado = estadoActual === 'habilitado' ? 'inhabilitado' : 'habilitado';

                    const result = await Swal.fire({
                        title: 'Cambiar estatus',
                        text: '¿Desea ' + (nuevoEstado === 'habilitado' ? 'habilitar' : 'inhabilitar') + ' esta cuenta?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2196F3',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!result.isConfirmed) return;

                    const fd = new FormData();
                    fd.append('accion', 'cambiar_estado');
                    fd.append('id_cuenta', id);
                    fd.append('estado', nuevoEstado);

                    await enviarAccion(fd, {
                        successMsg: 'Estatus actualizado a ' + nuevoEstado,
                        onSuccess: function () {
                            badge.textContent = nuevoEstado;
                            badge.classList.remove('habilitado', 'inhabilitado');
                            badge.classList.add(nuevoEstado);
                            actualizarContadores();
                        }
                    });
                }

                /* ==================== Filtro de estatus ==================== */
                const filtroEstatus = document.getElementById('filtro-estatus-cuentas');
                filtroEstatus?.addEventListener('change', function () {
                    const dt = obtenerTabla();
                    if (dt) {
                        if (this.value === 'todos') {
                            dt.column(ESTATUS_COL).search('').draw();
                        } else {
                            dt.column(ESTATUS_COL).search('^' + this.value + '$', true, false).draw();
                        }
                    } else {
                        document.querySelectorAll('#tablaCuentas tbody tr').forEach(function (tr) {
                            const badge = tr.querySelector('.estatus-badge');
                            const texto = badge ? badge.textContent.trim() : '';
                            tr.style.display = (this.value === 'todos' || texto === this.value) ? '' : 'none';
                        }.bind(this));
                    }
                });

                /* ==================== Cierre de modales ==================== */
                cuentaModal?.addEventListener('click', function (e) {
                    if (e.target === cuentaModal) closeCuentaModal();
                });
                detalleModal?.addEventListener('click', function (e) {
                    if (e.target === detalleModal) closeDetalleModal();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { closeCuentaModal(); closeDetalleModal(); }
                });

                /* ==================== Permisos en tiempo real ==================== */
                async function verificarPermisos() {
                    const fd = new FormData();
                    fd.append('accion', 'permisos_tiempo_real');
                    try {
                        const permisos = await postCuenta(fd);
                        const msg = document.getElementById('mensaje-permiso');
                        if (!permisos || !permisos.consultar) {
                            document.getElementById('contenedor-cuentas').style.display = 'none';
                            if (msg) { msg.style.display = 'block'; msg.textContent = 'No tiene permiso para consultar los registros.'; }
                            return;
                        }
                        document.getElementById('contenedor-cuentas').style.display = '';
                        if (msg) msg.style.display = 'none';
                        const btnAdd = document.getElementById('btn-add-cuenta');
                        if (btnAdd) btnAdd.style.display = permisos.incluir ? '' : 'none';
                        document.querySelectorAll('.btn-edit').forEach(b => b.style.display = permisos.modificar ? '' : 'none');
                        document.querySelectorAll('.btn-delete').forEach(b => b.style.display = permisos.eliminar ? '' : 'none');
                    } catch (e) {
                        console.error('Error verificando permisos:', e);
                    }
                }

                verificarPermisos();
                setInterval(verificarPermisos, 10000);

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
                            const html = await (await fetch('assets/public/ayuda/cuenta.php')).text();
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

                document.getElementById('btn-ayuda-cuenta')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda();
                });

                document.getElementById('btn-ayuda-modal-cuenta')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda(this.dataset.contexto || null);
                });
            })();
            </script>

<?php
// NOTA: buildCuentaRow se define en PHP para el render inicial del tbody
function buildCuentaRow($cuenta) {
    static $primera = true;
    $id = (int)$cuenta['id_cuenta'];
    $estado = $cuenta['estado'] ?? 'habilitado';
    $metodos = $cuenta['metodos'] ?? '';
    $esUsd = (strpos($metodos, 'Zelle') !== false);
    $moneda = $esUsd ? 'USD' : 'Bs';
    $listaMetodos = array_filter(array_map('trim', explode(',', $metodos)));
    $metodosHtml = '';
    if ($listaMetodos) {
        $metodosHtml = '<div class="metodos-lista">';
        foreach ($listaMetodos as $m) {
            $metodosHtml .= '<span class="metodo-tag">' . htmlspecialchars($m) . '</span>';
        }
        $metodosHtml .= '</div>';
    } else {
        $metodosHtml = '<span style="color:#a0aec0;">-</span>';
    }
    ob_start();
    ?>
    <tr data-id="<?php echo $id; ?>">
        <td><span style="font-weight:700;color:#2196F3;"><?php echo $id; ?></span></td>
        <td><?php echo htmlspecialchars($cuenta['nombre_banco'] ?? '-'); ?></td>
        <td><span style="font-family:monospace;"><?php echo htmlspecialchars($cuenta['numero_cuenta'] ?? '-'); ?></span></td>
        <td><?php echo htmlspecialchars($cuenta['rif_cuenta'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($cuenta['telefono_cuenta'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($cuenta['correo_cuenta'] ?? '-'); ?></td>
        <td><?php echo $metodosHtml; ?></td>
        <td><span class="moneda-badge <?php echo $esUsd ? 'usd' : 'bs'; ?>"><?php echo $moneda; ?></span></td>
        <td><span class="estatus-badge <?php echo htmlspecialchars($estado); ?>" data-id="<?php echo $id; ?>" title="Clic para cambiar estatus"><?php echo htmlspecialchars($estado); ?></span></td>
        <td>
            <button class="btn-action btn-view" data-id="<?php echo $id; ?>" title="Ver Detalles"><i class="fas fa-eye"></i></button>
            <button class="btn-action btn-edit" data-id="<?php echo $id; ?>" title="Modificar Cuenta"><i class="fas fa-edit"></i></button>
            <button class="btn-action btn-delete" data-id="<?php echo $id; ?>" title="Eliminar Cuenta"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>
