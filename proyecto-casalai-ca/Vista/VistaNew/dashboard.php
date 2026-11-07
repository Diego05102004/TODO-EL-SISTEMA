<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión (sistema tradicional)
if (!isset($_SESSION['name'])) {
    // Redirigir al usuario a la página de inicio de sesión
    header('Location: ../..');
    exit();
}

// Verificar y/o generar token JWT (sistema JWT)
require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

// Si el usuario tiene sesión pero no tiene JWT, generar uno
if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en dashboard: " . $e->getMessage());
        // Continuar aunque falle la generación del JWT
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'dashboard';
$titulo_pagina = 'Panel Principal';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Ventas Totales</h3>
                        <p class="card-value">$25,024</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="75, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">75%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-chart-down"></i></div>
                    <div class="card-content">
                        <h3>Gastos Totales</h3>
                        <p class="card-value">$14,160</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="45, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">45%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Ingresos Totales</h3>
                        <p class="card-value">$10,864</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="60, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">60%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders Table -->
                <div class="recent-orders">
                    <h2>Pedidos Recientes</h2>
                    <div class="table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Nombre del Producto</th>
                                    <th>Número de Pedido</th>
                                    <th>Pago</th>
                                    <th>Estado</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Laptop Dell XPS 15</td>
                                    <td>#ORD-001</td>
                                    <td>$1,299</td>
                                    <td><span class="status completed">Completado</span></td>
                                    <td><button class="btn-view">Ver</button></td>
                                </tr>
                                <tr>
                                    <td>iPhone 15 Pro Max</td>
                                    <td>#ORD-002</td>
                                    <td>$1,199</td>
                                    <td><span class="status pending">Pendiente</span></td>
                                    <td><button class="btn-view">Ver</button></td>
                                </tr>
                                <tr>
                                    <td>Samsung Galaxy S24</td>
                                    <td>#ORD-003</td>
                                    <td>$999</td>
                                    <td><span class="status completed">Completado</span></td>
                                    <td><button class="btn-view">Ver</button></td>
                                </tr>
                                <tr>
                                    <td>MacBook Air M3</td>
                                    <td>#ORD-004</td>
                                    <td>$1,099</td>
                                    <td><span class="status processing">Procesando</span></td>
                                    <td><button class="btn-view">Ver</button></td>
                                </tr>
                                <tr>
                                    <td>iPad Pro 12.9</td>
                                    <td>#ORD-005</td>
                                    <td>$1,199</td>
                                    <td><span class="status completed">Completado</span></td>
                                    <td><button class="btn-view">Ver</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="right-sidebar">
                    <!-- Recent Updates -->
                    <div class="recent-updates">
                        <h2>Actualizaciones Recientes</h2>
                        <div class="updates-list">
                            <div class="update-item">
                                <div class="update-icon"><i class="fas fa-box"></i></div>
                                <div class="update-content">
                                    <p>Nuevo pedido recibido</p>
                                    <span class="update-time">hace 2 min</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon"><i class="fas fa-credit-card"></i></div>
                                <div class="update-content">
                                    <p>Pago procesado</p>
                                    <span class="update-time">hace 15 min</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon"><i class="fas fa-truck"></i></div>
                                <div class="update-content">
                                    <p>Pedido enviado</p>
                                    <span class="update-time">hace 1 hora</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon"><i class="fas fa-user"></i></div>
                                <div class="update-content">
                                    <p>Nuevo cliente registrado</p>
                                    <span class="update-time">hace 3 horas</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon"><i class="fas fa-check"></i></div>
                                <div class="update-content">
                                    <p>Pedido entregado</p>
                                    <span class="update-time">hace 5 horas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Analytics -->
                    <div class="sales-analytics">
                        <h2>Análisis de Ventas</h2>
                        <div class="analytics-cards">
                            <div class="analytics-card">
                                <div class="analytics-icon"><i class="fas fa-globe"></i></div>
                                <div class="analytics-content">
                                    <h3>Pedidos Online</h3>
                                    <p class="analytics-value">1,234</p>
                                    <span class="analytics-change positive">+12.5%</span>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon"><i class="fas fa-store"></i></div>
                                <div class="analytics-content">
                                    <h3>Pedidos Presenciales</h3>
                                    <p class="analytics-value">567</p>
                                    <span class="analytics-change negative">-3.2%</span>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon"><i class="fas fa-users"></i></div>
                                <div class="analytics-content">
                                    <h3>Nuevos Clientes</h3>
                                    <p class="analytics-value">89</p>
                                    <span class="analytics-change positive">+8.7%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Product Button -->
                    <button class="btn-add-product" onclick="window.location.href='?pagina=producto'">
                        <span class="btn-icon"><i class="fas fa-plus"></i></span>
                        Agregar Producto
                    </button>
                </div>
            </div>

            <!-- Modal de Detalles del Pedido -->
            <div id="orderModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Detalles del Pedido</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="detail-label">Producto:</span>
                                <span class="detail-value" id="modalProductName">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Número de Pedido:</span>
                                <span class="detail-value" id="modalOrderNumber">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Precio:</span>
                                <span class="detail-value" id="modalPrice">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value" id="modalStatus">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Fecha:</span>
                                <span class="detail-value" id="modalDate">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Cliente:</span>
                                <span class="detail-value" id="modalCustomer">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Dirección de envío:</span>
                                <span class="detail-value" id="modalAddress">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-close-modal">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos del Modal */
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
                    padding: 0;
                    border-radius: 10px;
                    width: 90%;
                    max-width: 500px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                    animation: slideDown 0.3s;
                }

                .modal-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .modal-header h2 {
                    margin: 0;
                    font-size: 1.5rem;
                }

                .close-modal {
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    color: white;
                    transition: color 0.3s;
                }

                .close-modal:hover {
                    color: #ddd;
                }

                .modal-body {
                    padding: 25px;
                }

                .order-details {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px;
                    background-color: #f8f9fa;
                    border-radius: 8px;
                    border-left: 4px solid #2196F3;
                }

                .detail-label {
                    font-weight: 600;
                    color: #333;
                    font-size: 0.95rem;
                }

                .detail-value {
                    color: #555;
                    font-size: 0.95rem;
                    text-align: right;
                }

                .modal-footer {
                    padding: 20px;
                    text-align: right;
                    border-top: 1px solid #dee2e6;
                }

                .btn-close-modal {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 1rem;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .btn-close-modal:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideDown {
                    from { transform: translateY(-50px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            </style>

            <script>
                // View button functionality - Modal
                const modal = document.getElementById('orderModal');
                const closeModal = document.querySelector('.close-modal');
                const btnCloseModal = document.querySelector('.btn-close-modal');

                document.querySelectorAll('.btn-view').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const productName = row.cells[0].textContent;
                        const orderNumber = row.cells[1].textContent;
                        const price = row.cells[2].textContent;
                        const statusElement = row.cells[3].querySelector('.status');
                        const status = statusElement ? statusElement.textContent : 'Desconocido';

                        // Datos simulados para el modal (en producción vendrían de la base de datos)
                        const mockData = {
                            '#ORD-001': {
                                date: '2024-01-15',
                                customer: 'Juan Pérez',
                                address: 'Av. Principal #123, Caracas'
                            },
                            '#ORD-002': {
                                date: '2024-01-16',
                                customer: 'María González',
                                address: 'Calle 45 #67, Valencia'
                            },
                            '#ORD-003': {
                                date: '2024-01-17',
                                customer: 'Carlos Rodríguez',
                                address: 'Urb. Los Pinos #89, Maracaibo'
                            },
                            '#ORD-004': {
                                date: '2024-01-18',
                                customer: 'Ana Martínez',
                                address: 'Av. Bolívar #234, Barquisimeto'
                            },
                            '#ORD-005': {
                                date: '2024-01-19',
                                customer: 'Pedro Sánchez',
                                address: 'Calle Real #567, Mérida'
                            }
                        };

                        const orderData = mockData[orderNumber] || {
                            date: 'Fecha no disponible',
                            customer: 'Cliente no disponible',
                            address: 'Dirección no disponible'
                        };

                        // Llenar el modal con los datos
                        document.getElementById('modalProductName').textContent = productName;
                        document.getElementById('modalOrderNumber').textContent = orderNumber;
                        document.getElementById('modalPrice').textContent = price;
                        document.getElementById('modalStatus').textContent = status;
                        document.getElementById('modalDate').textContent = orderData.date;
                        document.getElementById('modalCustomer').textContent = orderData.customer;
                        document.getElementById('modalAddress').textContent = orderData.address;

                        // Mostrar el modal
                        modal.style.display = 'block';
                    });
                });

                // Cerrar modal con el botón X
                closeModal.addEventListener('click', function() {
                    modal.style.display = 'none';
                });

                // Cerrar modal con el botón "Cerrar"
                btnCloseModal.addEventListener('click', function() {
                    modal.style.display = 'none';
                });

                // Cerrar modal al hacer clic fuera del contenido
                window.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>