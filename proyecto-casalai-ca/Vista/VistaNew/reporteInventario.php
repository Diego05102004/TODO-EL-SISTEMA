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
        error_log("Error al generar JWT en reporteInventario: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'reporteInventario';
$titulo_pagina = 'Reporte de Inventario';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Inventario -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-box"></i></div>
                    <div class="card-content">
                        <h3>Total Productos</h3>
                        <p class="card-value">1,234</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="85, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">85%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="card-content">
                        <h3>Stock Bajo</h3>
                        <p class="card-value">23</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="12, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">12%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-sync"></i></div>
                    <div class="card-content">
                        <h3>Movimientos</h3>
                        <p class="card-value">456</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="37, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">37%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros de Inventario -->
            <div class="inventory-filters">
                <div class="section-header">
                    <h2>Filtros del Reporte</h2>
                    <div class="section-actions">
                        <button class="btn-apply-filter" onclick="applyFilters()">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Aplicar Filtros
                        </button>
                        <button class="btn-reset-filter" onclick="resetFilters()">
                            <span class="btn-icon"><i class="fas fa-sync"></i></span>
                            Restablecer
                        </button>
                        <button class="btn-download-report" onclick="downloadReport()">
                            <span class="btn-icon"><i class="fas fa-download"></i></span>
                            Descargar PDF
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="dateRange">Rango de Fechas</label>
                        <div class="date-range">
                            <input type="date" id="startDate" name="startDate">
                            <span>a</span>
                            <input type="date" id="endDate" name="endDate">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="categoryFilter">Categoría</label>
                        <select id="categoryFilter" name="categoria">
                            <option value="">Todas las categorías</option>
                            <option value="smartphones">Smartphones</option>
                            <option value="laptops">Laptops</option>
                            <option value="audio">Audio</option>
                            <option value="smartwatches">Smartwatches</option>
                            <option value="camaras">Cámaras</option>
                            <option value="gaming">Gaming</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="statusFilter">Estado del Stock</label>
                        <select id="statusFilter" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="normal">Normal</option>
                            <option value="low">Stock Bajo</option>
                            <option value="critical">Crítico</option>
                            <option value="overstock">Sobre-stock</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="locationFilter">Ubicación</label>
                        <select id="locationFilter" name="ubicacion">
                            <option value="">Todas las ubicaciones</option>
                            <option value="almacen">Almacén Principal</option>
                            <option value="tienda">Tienda</option>
                            <option value="sucursal">Sucursal</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Gráficos de Inventario -->
            <div class="charts-section">
                <h2>Análisis de Inventario</h2>
                <div class="charts-grid">
                    <!-- Gráfico de Stock por Categoría -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Stock por Categoría</h3>
                            <div class="chart-legend">
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #2196F3;"></span>
                                    Smartphones
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #1976D2;"></span>
                                    Laptops
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoryStockChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico de Movimientos -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Movimientos de Inventario</h3>
                            <div class="chart-stats">
                                <span class="stat-item">
                                    <span class="stat-label">Entradas:</span>
                                    <span class="stat-value">234</span>
                                </span>
                                <span class="stat-item">
                                    <span class="stat-label">Salidas:</span>
                                    <span class="stat-value">189</span>
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="movementsChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico de Tendencia -->
                    <div class="chart-card full-width">
                        <div class="chart-header">
                            <h3>Tendencia de Stock (Últimos 6 meses)</h3>
                            <div class="chart-stats">
                                <span class="stat-item">
                                    <span class="stat-label">Promedio:</span>
                                    <span class="stat-value">1,234</span>
                                </span>
                                <span class="stat-item">
                                    <span class="stat-label">Máximo:</span>
                                    <span class="stat-value">1,456</span>
                                </span>
                                <span class="stat-item">
                                    <span class="stat-label">Mínimo:</span>
                                    <span class="stat-value">987</span>
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de Stock -->
            <div class="stock-alerts-section">
                <div class="section-header">
                    <h2>Alertas de Stock</h2>
                    <div class="section-actions">
                        <button class="btn-view-all" onclick="viewAllAlerts()">Ver todas</button>
                    </div>
                </div>

                <div class="alerts-grid">
                    <div class="alert-card critical">
                        <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="alert-content">
                            <h4>Stock Crítico</h4>
                            <p>5 productos necesitan atención inmediata</p>
                        </div>
                        <button class="btn-alert-action" onclick="handleAlert('critical')">Ver</button>
                    </div>
                    <div class="alert-card low">
                        <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="alert-content">
                            <h4>Stock Bajo</h4>
                            <p>18 productos están por debajo del mínimo</p>
                        </div>
                        <button class="btn-alert-action" onclick="handleAlert('low')">Ver</button>
                    </div>
                    <div class="alert-card overstock">
                        <div class="alert-icon"><i class="fas fa-box"></i></div>
                        <div class="alert-content">
                            <h4>Sobre-stock</h4>
                            <p>7 productos tienen exceso de inventario</p>
                        </div>
                        <button class="btn-alert-action" onclick="handleAlert('overstock')">Ver</button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Detalles de Inventario -->
            <div class="inventory-details-section">
                <div class="section-header">
                    <h2>Detalles de Inventario</h2>
                    <div class="section-actions">
                        <button class="btn-export-excel" onclick="exportToExcel()">
                            <span class="btn-icon"><i class="fas fa-file"></i></span>
                            Exportar Excel
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Último Movimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>iPhone 15 Pro Max</td>
                                <td>Smartphones</td>
                                <td>5</td>
                                <td>10</td>
                                <td>Almacén Principal</td>
                                <td><span class="status critical">Crítico</span></td>
                                <td>2024-01-19</td>
                            </tr>
                            <tr>
                                <td>MacBook Air M3</td>
                                <td>Laptops</td>
                                <td>8</td>
                                <td>5</td>
                                <td>Tienda</td>
                                <td><span class="status low">Bajo</span></td>
                                <td>2024-01-18</td>
                            </tr>
                            <tr>
                                <td>Apple Watch Ultra</td>
                                <td>Smartwatches</td>
                                <td>25</td>
                                <td>10</td>
                                <td>Almacén Principal</td>
                                <td><span class="status normal">Normal</span></td>
                                <td>2024-01-17</td>
                            </tr>
                            <tr>
                                <td>Canon EOS R5</td>
                                <td>Cámaras</td>
                                <td>3</td>
                                <td>5</td>
                                <td>Sucursal</td>
                                <td><span class="status critical">Crítico</span></td>
                                <td>2024-01-16</td>
                            </tr>
                            <tr>
                                <td>PlayStation 5</td>
                                <td>Gaming</td>
                                <td>15</td>
                                <td>10</td>
                                <td>Almacén Principal</td>
                                <td><span class="status normal">Normal</span></td>
                                <td>2024-01-15</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <style>
                /* Estilos específicos de Inventario */
                .inventory-filters {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .filters-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .filter-group {
                    display: flex;
                    flex-direction: column;
                }

                .filter-group label {
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 8px;
                }

                .filter-group select, .filter-group input {
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                }

                .date-range {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .date-range span {
                    color: #666;
                    font-weight: 500;
                }

                .stock-alerts-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .alerts-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .alert-card {
                    padding: 20px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .alert-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .alert-card.critical {
                    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
                    border-left: 4px solid #dc3545;
                }

                .alert-card.low {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
                    border-left: 4px solid #ffc107;
                }

                .alert-card.overstock {
                    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
                    border-left: 4px solid #17a2b8;
                }

                .alert-icon {
                    font-size: 2rem;
                }

                .alert-icon i {
                    color: #2196F3;
                }

                .alert-content {
                    flex: 1;
                }

                .alert-content h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .alert-content p {
                    margin: 0;
                    color: #666;
                    font-size: 0.85rem;
                }

                .btn-alert-action {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    transition: transform 0.2s;
                }

                .btn-alert-action:hover {
                    transform: scale(1.05);
                }

                .inventory-details-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .inventory-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .inventory-table th {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .inventory-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .inventory-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .inventory-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .inventory-table tr:hover {
                    background-color: #f8f9fa;
                }

                .status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .status.critical {
                    background-color: #f8d7da;
                    color: #721c24;
                }

                .status.low {
                    background-color: #fff3cd;
                    color: #856404;
                }

                .status.normal {
                    background-color: #d4edda;
                    color: #155724;
                }

                @media (max-width: 768px) {
                    .alerts-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .filters-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Gráfico de Stock por Categoría
                const categoryCtx = document.getElementById('categoryStockChart').getContext('2d');
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Smartphones', 'Laptops', 'Audio', 'Smartwatches', 'Cámaras', 'Gaming'],
                        datasets: [{
                            data: [25, 20, 15, 18, 12, 10],
                            backgroundColor: [
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(118, 75, 162, 0.8)',
                                'rgba(240, 147, 251, 0.8)',
                                'rgba(245, 87, 108, 0.8)',
                                'rgba(67, 233, 123, 0.8)',
                                'rgba(56, 242, 215, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });

                // Gráfico de Movimientos
                const movementsCtx = document.getElementById('movementsChart').getContext('2d');
                new Chart(movementsCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Entradas', 'Salidas'],
                        datasets: [{
                            label: 'Movimientos',
                            data: [234, 189],
                            backgroundColor: [
                                'rgba(67, 233, 123, 0.8)',
                                'rgba(245, 87, 108, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Gráfico de Tendencia
                const trendCtx = document.getElementById('trendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: ['Ago', 'Sep', 'Oct', 'Nov', 'Dic', 'Ene'],
                        datasets: [{
                            label: 'Stock',
                            data: [1100, 1250, 1180, 1320, 1400, 1234],
                            borderColor: 'rgba(102, 126, 234, 1)',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                function applyFilters() {
                    alert('Función de aplicar filtros (conectar con backend)');
                }

                function resetFilters() {
                    document.getElementById('startDate').value = '';
                    document.getElementById('endDate').value = '';
                    document.getElementById('categoryFilter').value = '';
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('locationFilter').value = '';
                }

                function downloadReport() {
                    alert('Función de descargar PDF (conectar con backend)');
                }

                function exportToExcel() {
                    alert('Función de exportar Excel (conectar con backend)');
                }

                function viewAllAlerts() {
                    alert('Función de ver todas las alertas (conectar con backend)');
                }

                function handleAlert(type) {
                    alert('Función para manejar alertas: ' + type + ' (conectar con backend)');
                }

                // Establecer fechas por defecto (mes actual)
                document.addEventListener('DOMContentLoaded', function() {
                    const today = new Date();
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    
                    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
                    document.getElementById('endDate').value = lastDay.toISOString().split('T')[0];
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>