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
        error_log("Error al generar JWT en reporteFinanzas: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'reporteFinanzas';
$titulo_pagina = 'Reporte de Finanzas';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Finanzas -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Ingresos Totales</h3>
                        <p class="card-value">$156,890</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="92, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">92%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-chart-down"></i></div>
                    <div class="card-content">
                        <h3>Gastos Totales</h3>
                        <p class="card-value">$45,234</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="28, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">28%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Beneficio Neto</h3>
                        <p class="card-value">$111,656</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="71, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">71%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cuentas Bancarias -->
            <div class="accounts-section">
                <div class="section-header">
                    <h2>Cuentas Bancarias</h2>
                    <div class="section-actions">
                        <button class="btn-add-account" onclick="openModal('agregar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Cuenta
                        </button>
                    </div>
                </div>

                <div class="accounts-grid">
                    <div class="account-card">
                        <div class="account-header">
                            <div class="bank-icon"><i class="fas fa-university"></i></div>
                            <div class="account-info">
                                <h3>Banco Mercantil</h3>
                                <p>Cuenta Corriente</p>
                            </div>
                        </div>
                        <div class="account-balance">
                            <span class="balance-label">Saldo Actual</span>
                            <span class="balance-value">$45,234.56</span>
                        </div>
                        <div class="account-actions">
                            <button class="btn-action btn-view" onclick="viewAccount(1)"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-edit" onclick="editAccount(1)"><i class="fas fa-edit"></i></button>
                        </div>
                    </div>

                    <div class="account-card">
                        <div class="account-header">
                            <div class="bank-icon"><i class="fas fa-university"></i></div>
                            <div class="account-info">
                                <h3>Banesco</h3>
                                <p>Cuenta de Ahorros</p>
                            </div>
                        </div>
                        <div class="account-balance">
                            <span class="balance-label">Saldo Actual</span>
                            <span class="balance-value">$28,890.12</span>
                        </div>
                        <div class="account-actions">
                            <button class="btn-action btn-view" onclick="viewAccount(2)"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-edit" onclick="editAccount(2)"><i class="fas fa-edit"></i></button>
                        </div>
                    </div>

                    <div class="account-card">
                        <div class="account-header">
                            <div class="bank-icon"><i class="fas fa-university"></i></div>
                            <div class="account-info">
                                <h3>Banco de Venezuela</h3>
                                <p>Cuenta Corriente</p>
                            </div>
                        </div>
                        <div class="account-balance">
                            <span class="balance-label">Saldo Actual</span>
                            <span class="balance-value">$67,456.78</span>
                        </div>
                        <div class="account-actions">
                            <button class="btn-action btn-view" onclick="viewAccount(3)"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-edit" onclick="editAccount(3)"><i class="fas fa-edit"></i></button>
                        </div>
                    </div>

                    <div class="account-card">
                        <div class="account-header">
                            <div class="bank-icon"><i class="fas fa-university"></i></div>
                            <div class="account-info">
                                <h3>Banco Provincial</h3>
                                <p>Cuenta de Inversión</p>
                            </div>
                        </div>
                        <div class="account-balance">
                            <span class="balance-label">Saldo Actual</span>
                            <span class="balance-value">$15,309.54</span>
                        </div>
                        <div class="account-actions">
                            <button class="btn-action btn-view" onclick="viewAccount(4)"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-edit" onclick="editAccount(4)"><i class="fas fa-edit"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transacciones Recientes -->
            <div class="transactions-section">
                <div class="section-header">
                    <h2>Transacciones Recientes</h2>
                    <div class="section-actions">
                        <button class="btn-filter" onclick="openFilterModal()">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Filtrar
                        </button>
                        <button class="btn-export" onclick="exportTransactions()">
                            <span class="btn-icon"><i class="fas fa-download"></i></span>
                            Exportar
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Cuenta</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2024-01-19</td>
                                <td>Pago de Cliente #ORD-005</td>
                                <td>Banco Mercantil</td>
                                <td>Ventas</td>
                                <td><span class="transaction-type income">Ingreso</span></td>
                                <td class="amount positive">+$1,448.00</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewTransaction(1)"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2024-01-18</td>
                                <td>Pago a Proveedor TechCorp</td>
                                <td>Banesco</td>
                                <td>Compras</td>
                                <td><span class="transaction-type expense">Gasto</span></td>
                                <td class="amount negative">-$2,345.00</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewTransaction(2)"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2024-01-17</td>
                                <td>Nómina Mensual</td>
                                <td>Banco de Venezuela</td>
                                <td>Nómina</td>
                                <td><span class="transaction-type expense">Gasto</span></td>
                                <td class="amount negative">-$8,500.00</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewTransaction(3)"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2024-01-16</td>
                                <td>Venta Servicios #INV-003</td>
                                <td>Banco Provincial</td>
                                <td>Servicios</td>
                                <td><span class="transaction-type income">Ingreso</span></td>
                                <td class="amount positive">+$3,200.00</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewTransaction(4)"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2024-01-15</td>
                                <td>Pago de Servicios</td>
                                <td>Banco Mercantil</td>
                                <td>Servicios</td>
                                <td><span class="transaction-type expense">Gasto</span></td>
                                <td class="amount negative">-$450.00</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewTransaction(5)"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Análisis Financiero -->
            <div class="financial-analysis-section">
                <h2>Análisis Financiero</h2>
                <div class="analysis-grid">
                    <div class="analysis-card">
                        <div class="analysis-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="analysis-content">
                            <h3>Flujo de Caja</h3>
                            <p class="analysis-value">$+12,456</p>
                            <span class="analysis-change positive">Positivo</span>
                        </div>
                    </div>
                    <div class="analysis-card">
                        <div class="analysis-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="analysis-content">
                            <h3>Deuda Total</h3>
                            <p class="analysis-value">$23,456</p>
                            <span class="analysis-change negative">-5.2%</span>
                        </div>
                    </div>
                    <div class="analysis-card">
                        <div class="analysis-icon"><i class="fas fa-bullseye"></i></div>
                        <div class="analysis-content">
                            <h3>Margen de Beneficio</h3>
                            <p class="analysis-value">71.2%</p>
                            <span class="analysis-change positive">+3.4%</span>
                        </div>
                    </div>
                    <div class="analysis-card">
                        <div class="analysis-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="analysis-content">
                            <h3>ROI Mensual</h3>
                            <p class="analysis-value">15.8%</p>
                            <span class="analysis-change positive">+2.1%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Agregar/Editar Cuenta -->
            <div id="accountModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Cuenta Bancaria</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="accountForm">
                            <input type="hidden" id="accountId" name="id">
                            
                            <div class="form-group">
                                <label for="bankName">Banco*</label>
                                <select id="bankName" name="banco" required>
                                    <option value="">Seleccione un banco</option>
                                    <option value="mercantil">Banco Mercantil</option>
                                    <option value="banesco">Banesco</option>
                                    <option value="venezuela">Banco de Venezuela</option>
                                    <option value="provincial">Banco Provincial</option>
                                    <option value="occidental">Banco Occidental</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="accountType">Tipo de Cuenta*</label>
                                <select id="accountType" name="tipo_cuenta" required>
                                    <option value="">Seleccione tipo</option>
                                    <option value="corriente">Cuenta Corriente</option>
                                    <option value="ahorros">Cuenta de Ahorros</option>
                                    <option value="inversion">Cuenta de Inversión</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="accountNumber">Número de Cuenta*</label>
                                <input type="text" id="accountNumber" name="numero_cuenta" required 
                                       placeholder="0134-5678-90-12345678" maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label for="initialBalance">Saldo Inicial*</label>
                                <input type="number" id="initialBalance" name="saldo_inicial" required 
                                       placeholder="0.00" step="0.01" min="0">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveAccount()">Guardar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Finanzas */
                .accounts-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .accounts-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 25px;
                }

                .account-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    transition: transform 0.3s, box-shadow 0.3s;
                }

                .account-card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                }

                .account-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .bank-icon {
                    font-size: 2.5rem;
                }

                .account-info h3 {
                    margin: 0 0 5px 0;
                    font-size: 1.1rem;
                }

                .account-info p {
                    margin: 0;
                    font-size: 0.85rem;
                    opacity: 0.9;
                }

                .account-balance {
                    padding: 20px;
                    text-align: center;
                }

                .balance-label {
                    display: block;
                    font-size: 0.85rem;
                    color: #666;
                    margin-bottom: 8px;
                }

                .balance-value {
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: #2196F3;
                }

                .account-actions {
                    padding: 15px 20px;
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    border-top: 1px solid #ddd;
                }

                .transactions-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .transactions-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .transactions-table th {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .transactions-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .transactions-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .transactions-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .transactions-table tr:hover {
                    background-color: #f8f9fa;
                }

                .transaction-type {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .transaction-type.income {
                    background-color: #d4edda;
                    color: #155724;
                }

                .transaction-type.expense {
                    background-color: #f8d7da;
                    color: #721c24;
                }

                .amount {
                    font-weight: 700;
                    font-size: 1.1rem;
                }

                .amount.positive {
                    color: #28a745;
                }

                .amount.negative {
                    color: #dc3545;
                }

                .financial-analysis-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .financial-analysis-section h2 {
                    color: #333;
                    margin: 0 0 25px 0;
                    font-size: 1.5rem;
                }

                .analysis-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .analysis-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .analysis-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .analysis-icon {
                    font-size: 2.5rem;
                }

                .analysis-icon i {
                    color: #2196F3;
                }

                .analysis-content h3 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .analysis-value {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2196F3;
                    margin: 0 0 5px 0;
                }

                .analysis-change {
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .analysis-change.positive {
                    color: #28a745;
                }

                .analysis-change.negative {
                    color: #dc3545;
                }

                @media (max-width: 768px) {
                    .accounts-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .analysis-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script>
                // Funciones para modales
                const accountModal = document.getElementById('accountModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#accountModal .close-modal');

                function openModal(type, accountId = null) {
                    if (type === 'agregar') {
                        modalTitle.textContent = 'Agregar Cuenta Bancaria';
                        document.getElementById('accountForm').reset();
                        document.getElementById('accountId').value = '';
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Cuenta Bancaria';
                        document.getElementById('accountId').value = accountId;
                        // Simulación de datos
                        document.getElementById('bankName').value = 'mercantil';
                        document.getElementById('accountType').value = 'corriente';
                        document.getElementById('accountNumber').value = '0134-5678-90-12345678';
                        document.getElementById('initialBalance').value = '45234.56';
                    }
                    accountModal.style.display = 'block';
                }

                function closeModal() {
                    accountModal.style.display = 'none';
                }

                function viewAccount(accountId) {
                    alert('Función de ver detalles de cuenta (conectar con backend)');
                }

                function editAccount(accountId) {
                    openModal('editar', accountId);
                }

                function saveAccount() {
                    alert('Función de guardar cuenta (conectar con backend)');
                    closeModal();
                }

                function viewTransaction(transactionId) {
                    alert('Función de ver detalles de transacción (conectar con backend)');
                }

                function openFilterModal() {
                    alert('Función de filtros de transacciones (conectar con backend)');
                }

                function exportTransactions() {
                    alert('Función de exportar transacciones (conectar con backend)');
                }

                // Event listeners para cerrar modal
                closeModalBtn.addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === accountModal) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>