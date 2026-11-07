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
        error_log("Error al generar JWT en despacho: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'despacho';
$titulo_pagina = 'Gestión de Despachos';

// Iniciar el buffer de contenido
ob_start();
?>

<?php
// Cálculos previos para summary cards
$total_despachos = isset($totalDespachos) ? $totalDespachos : count($despachos ?? []);
$despachos_pendientes = count(array_filter($despachos ?? [], function($d) { return ($d['estado'] ?? '') === 'Por Despachar'; }));
$despachos_completados = count(array_filter($despachos ?? [], function($d) { return ($d['estado'] ?? '') === 'Despachado'; }));

// Calcular porcentajes
$porcentaje_total = 100;
$porcentaje_pendientes = $total_despachos > 0 ? round(($despachos_pendientes / $total_despachos) * 100) : 0;
$porcentaje_completados = $total_despachos > 0 ? round(($despachos_completados / $total_despachos) * 100) : 0;
?>

            <!-- Summary Cards para Despachos -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-truck"></i></div>
                    <div class="card-content">
                        <h3>Total Despachos</h3>
                        <p class="card-value"><?php echo $total_despachos; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_total; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_total; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-content">
                        <h3>Pendientes</h3>
                        <p class="card-value"><?php echo $despachos_pendientes; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_pendientes; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_pendientes; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-content">
                        <h3>Completados</h3>
                        <p class="card-value"><?php echo $despachos_completados; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_completados; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_completados; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-content">
                        <h3>Pendientes</h3>
                        <p class="card-value">18</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="8, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">8%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-content">
                        <h3>Completados</h3>
                        <p class="card-value">216</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="92, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">92%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="action-buttons">
                <button class="btn-add-dispatch" onclick="openModal('agregar')">
                    <span class="btn-icon"><i class="fas fa-plus"></i></span>
                    Crear Despacho
                </button>
                <button class="btn-process-pending" onclick="processPending()">
                    <span class="btn-icon"><i class="fas fa-bolt"></i></span>
                    Procesar Pendientes
                </button>
                <button class="btn-export" onclick="exportDispatches()">
                    <span class="btn-icon"><i class="fas fa-upload"></i></span>
                    Exportar
                </button>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-bar">
                    <input type="text" id="searchDispatch" placeholder="Buscar despacho..." onkeyup="searchDispatches()">
                    <span class="search-icon"><i class="fas fa-search"></i></span>
                </div>
                <div class="filter-options">
                    <select id="statusFilter" onchange="filterByStatus()">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendientes</option>
                        <option value="processing">En Proceso</option>
                        <option value="shipped">Enviados</option>
                        <option value="delivered">Entregados</option>
                    </select>
                    <select id="dateFilter" onchange="filterByDate()">
                        <option value="">Todas las fechas</option>
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mes</option>
                    </select>
                </div>
            </div>

            <!-- Tabla de Despachos -->
            <div class="dispatches-table-section">
                <div class="table-container">
                    <table class="dispatches-table">
                        <thead>
                            <tr>
                                <th>ID Despacho</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Dirección</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Transportista</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="dispatch-id">#DSP-001</span></td>
                                <td>#ORD-006</td>
                                <td>Juan Pérez</td>
                                <td>Av. Principal #123, Caracas</td>
                                <td><span class="status processing">En Proceso</span></td>
                                <td>2024-01-19</td>
                                <td>Zoom Delivery</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewDispatch(1)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editDispatch(1)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-ship" onclick="shipDispatch(1)"><i class="fas fa-truck"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="dispatch-id">#DSP-002</span></td>
                                <td>#ORD-005</td>
                                <td>María González</td>
                                <td>Calle 45 #67, Valencia</td>
                                <td><span class="status shipped">Enviado</span></td>
                                <td>2024-01-18</td>
                                <td>MRW</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewDispatch(2)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editDispatch(2)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-deliver" onclick="deliverDispatch(2)"><i class="fas fa-check"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="dispatch-id">#DSP-003</span></td>
                                <td>#ORD-004</td>
                                <td>Carlos Rodríguez</td>
                                <td>Av. Libertador #89, Maracaibo</td>
                                <td><span class="status delivered">Entregado</span></td>
                                <td>2024-01-17</td>
                                <td>Zoom Delivery</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewDispatch(3)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-print" onclick="printDispatch(3)"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="dispatch-id">#DSP-004</span></td>
                                <td>#ORD-003</td>
                                <td>Ana Martínez</td>
                                <td>Urbanización Los Pinos #12, Mérida</td>
                                <td><span class="status pending">Pendiente</span></td>
                                <td>2024-01-19</td>
                                <td>Por asignar</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewDispatch(4)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editDispatch(4)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-ship" onclick="shipDispatch(4)"><i class="fas fa-truck"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="dispatch-id">#DSP-005</span></td>
                                <td>#ORD-002</td>
                                <td>Pedro Sánchez</td>
                                <td>Calle 23 #45, Barquisimeto</td>
                                <td><span class="status processing">En Proceso</span></td>
                                <td>2024-01-19</td>
                                <td>Zoom Delivery</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewDispatch(5)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editDispatch(5)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-ship" onclick="shipDispatch(5)"><i class="fas fa-truck"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Panel de Estadísticas de Despacho -->
            <div class="dispatch-stats-section">
                <h2>Estadísticas de Despacho</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-content">
                            <h4>Tiempo Promedio</h4>
                            <p class="stat-value">2.3 días</p>
                            <span class="stat-change positive">-12% vs mes anterior</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-box"></i></div>
                        <div class="stat-content">
                            <h4>Paquetes por Día</h4>
                            <p class="stat-value">18.5</p>
                            <span class="stat-change positive">+8% vs mes anterior</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h4>Costo Promedio</h4>
                            <p class="stat-value">$8.50</p>
                            <span class="stat-change negative">+5% vs mes anterior</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
                        <div class="stat-content">
                            <h4>Tasa de Entrega</h4>
                            <p class="stat-value">94.5%</p>
                            <span class="stat-change positive">+2% vs mes anterior</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Crear/Editar Despacho -->
            <div id="dispatchModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Crear Despacho</h2>
                                <p>Complete los datos para registrar un nuevo despacho</p>
                            </div>
                        </div>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="dispatchForm">
                            <input type="hidden" id="dispatchId" name="id">
                            
                            <div class="form-group">
                                <label for="orderId">
                                    <i class="fas fa-file-invoice"></i>
                                    Pedido*
                                </label>
                                <select id="orderId" name="pedido_id" required>
                                    <option value="">Seleccione pedido</option>
                                    <?php if (!empty($pedidosList)): ?>
                                        <?php foreach ($pedidosList as $pedido): ?>
                                            <option value="<?php echo $pedido['id_factura']; ?>">
                                                #<?php echo htmlspecialchars($pedido['id_factura']); ?> - 
                                                <?php echo htmlspecialchars($pedido['nombre_cliente'] ?? 'N/A'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="carrier">
                                    <i class="fas fa-shipping-fast"></i>
                                    Transportista*
                                </label>
                                <select id="carrier" name="transportista" required>
                                    <option value="">Seleccione transportista</option>
                                    <option value="zoom">Zoom Delivery</option>
                                    <option value="mrw">MRW</option>
                                    <option value="dhl">DHL</option>
                                    <option value="fedex">FedEx</option>
                                    <option value="domesa">Domesa</option>
                                    <option value="tealca">Tealca</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Dirección de Entrega*
                                </label>
                                <input type="text" id="address" name="direccion" required 
                                       placeholder="Dirección completa">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i>
                                    Teléfono de Contacto*
                                </label>
                                <input type="tel" id="phone" name="telefono" required 
                                       placeholder="+58 412-123-4567">
                            </div>
                            
                            <div class="form-group">
                                <label for="instructions">
                                    <i class="fas fa-comment-alt"></i>
                                    Instrucciones Especiales
                                </label>
                                <textarea id="instructions" name="instrucciones" rows="3"
                                          placeholder="Instrucciones para el delivery"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">
                                    <i class="fas fa-flag"></i>
                                    Prioridad
                                </label>
                                <select id="priority" name="prioridad">
                                    <option value="normal">Normal</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" onclick="saveDispatch()">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Despachos */
                .action-buttons {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 30px;
                }

                .btn-add-dispatch, .btn-process-pending, .btn-export {
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

                .btn-add-dispatch:hover, .btn-process-pending:hover, .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                .btn-process-pending {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .btn-export {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                /* Estilos del Modal */
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
                    margin-bottom: 20px;
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
                
                .form-group input,
                .form-group select,
                .form-group textarea {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }
                
                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
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

                .dispatches-table-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .dispatches-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .dispatches-table th {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .dispatches-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .dispatches-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .dispatches-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .dispatches-table tr:hover {
                    background-color: #f8f9fa;
                }

                .dispatch-id {
                    font-weight: 700;
                    color: #2196F3;
                }

                .status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .status.pending {
                    background-color: #fff3cd;
                    color: #856404;
                }

                .status.processing {
                    background-color: #cce5ff;
                    color: #004085;
                }

                .status.shipped {
                    background-color: #d1ecf1;
                    color: #0c5460;
                }

                .status.delivered {
                    background-color: #d4edda;
                    color: #155724;
                }

                .btn-action {
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    border: none;
                    cursor: pointer;
                    font-size: 1rem;
                    transition: transform 0.2s;
                    margin-right: 5px;
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

                .btn-ship {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .btn-deliver {
                    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
                }

                .btn-print {
                    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
                }

                .dispatch-stats-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .dispatch-stats-section h2 {
                    color: #333;
                    margin: 0 0 25px 0;
                    font-size: 1.5rem;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .stat-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .stat-icon {
                    font-size: 2.5rem;
                }

                .stat-content h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .stat-value {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2196F3;
                    margin: 0 0 5px 0;
                }

                .stat-change {
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .stat-change.positive {
                    color: #28a745;
                }

                .stat-change.negative {
                    color: #dc3545;
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
                    
                    .dispatches-table {
                        font-size: 0.85rem;
                    }
                    
                    .dispatches-table th, .dispatches-table td {
                        padding: 10px;
                    }
                    
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script>
                const dispatchModal = document.getElementById('dispatchModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#dispatchModal .close-modal');

                function openModal(type, dispatchId = null) {
                    if (type === 'agregar') {
                        modalTitle.textContent = 'Crear Despacho';
                        document.getElementById('dispatchForm').reset();
                        document.getElementById('dispatchId').value = '';
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Despacho';
                        document.getElementById('dispatchId').value = dispatchId;
                        // Simulación de datos
                        document.getElementById('orderId').value = 'ORD-006';
                        document.getElementById('carrier').value = 'zoom';
                        document.getElementById('address').value = 'Av. Principal #123, Caracas';
                        document.getElementById('phone').value = '+58 412-123-4567';
                        document.getElementById('instructions').value = 'Entregar en portón principal';
                        document.getElementById('priority').value = 'normal';
                    }
                    dispatchModal.style.display = 'block';
                }

                function closeModal() {
                    dispatchModal.style.display = 'none';
                }

                function viewDispatch(dispatchId) {
                    alert('Función de ver detalles de despacho (conectar con backend)');
                }

                function editDispatch(dispatchId) {
                    openModal('editar', dispatchId);
                }

                function shipDispatch(dispatchId) {
                    if (confirm('¿Está seguro de enviar este despacho?')) {
                        alert('Función de enviar despacho (conectar con backend)');
                    }
                }

                function deliverDispatch(dispatchId) {
                    if (confirm('¿Está seguro de marcar como entregado?')) {
                        alert('Función de marcar como entregado (conectar con backend)');
                    }
                }

                function printDispatch(dispatchId) {
                    alert('Función de imprimir despacho (conectar con backend)');
                }

                function saveDispatch() {
                    alert('Función de guardar despacho (conectar con backend)');
                    closeModal();
                }

                function searchDispatches() {
                    const searchTerm = document.getElementById('searchDispatch').value;
                    alert('Función de búsqueda: ' + searchTerm + ' (conectar con backend)');
                }

                function filterByStatus() {
                    const status = document.getElementById('statusFilter').value;
                    alert('Filtrar por estado: ' + status + ' (conectar con backend)');
                }

                function filterByDate() {
                    const date = document.getElementById('dateFilter').value;
                    alert('Filtrar por fecha: ' + date + ' (conectar con backend)');
                }

                function processPending() {
                    alert('Función para procesar despachos pendientes (conectar con backend)');
                }

                function exportDispatches() {
                    alert('Función de exportar despachos (conectar con backend)');
                }

                // Event listeners para cerrar modal
                closeModalBtn.addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === dispatchModal) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>