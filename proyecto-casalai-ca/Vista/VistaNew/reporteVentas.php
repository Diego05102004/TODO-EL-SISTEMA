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
        error_log("Error al generar JWT en reporteVentas: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'reporteVentas';
$titulo_pagina = 'Reporte de Ventas';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Reporte de Ventas -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Ventas Totales</h3>
                        <p class="card-value">$125,890</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="88, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">88%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Crecimiento</h3>
                        <p class="card-value">+23.5%</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="76, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">76%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-bullseye"></i></div>
                    <div class="card-content">
                        <h3>Objetivo</h3>
                        <p class="card-value">92%</p>
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

            <!-- Filtros de Reporte -->
            <div class="filters-section">
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
                        <label for="clientFilter">Cliente</label>
                        <select id="clientFilter" name="cliente">
                            <option value="">Todos los clientes</option>
                            <option value="1">Juan Pérez</option>
                            <option value="2">María González</option>
                            <option value="3">Carlos Rodríguez</option>
                            <option value="4">Ana Martínez</option>
                            <option value="5">Pedro Sánchez</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="statusFilter">Estado del Pedido</label>
                        <select id="statusFilter" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="completed">Completado</option>
                            <option value="processing">Procesando</option>
                            <option value="pending">Pendiente</option>
                            <option value="shipped">Enviado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Gráficos de Ventas -->
            <div class="charts-section">
                <h2>Análisis de Ventas</h2>
                <div class="charts-grid">
                    <!-- Gráfico de Ventas Mensuales -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Ventas Mensuales</h3>
                            <select class="chart-period" onchange="updateChart('monthly', this.value)">
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlySalesChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico de Ventas por Categoría -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Ventas por Categoría</h3>
                            <div class="chart-legend">
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #2196F3;"></span>
                                    Smartphones
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #1976D2;"></span>
                                    Laptops
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #f093fb;"></span>
                                    Audio
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="categorySalesChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico de Tendencia de Ventas -->
                    <div class="chart-card full-width">
                        <div class="chart-header">
                            <h3>Tendencia de Ventas (Últimos 6 meses)</h3>
                            <div class="chart-stats">
                                <span class="stat-item">
                                    <span class="stat-label">Promedio:</span>
                                    <span class="stat-value">$18,234</span>
                                </span>
                                <span class="stat-item">
                                    <span class="stat-label">Máximo:</span>
                                    <span class="stat-value">$24,567</span>
                                </span>
                                <span class="stat-item">
                                    <span class="stat-label">Mínimo:</span>
                                    <span class="stat-value">$12,890</span>
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Detalles de Ventas -->
            <div class="sales-details-section">
                <div class="section-header">
                    <h2>Detalles de Ventas</h2>
                    <div class="section-actions">
                        <button class="btn-export-excel" onclick="exportToExcel()">
                            <span class="btn-icon"><i class="fas fa-file"></i></span>
                            Exportar Excel
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Método Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2024-01-15</td>
                                <td>#ORD-001</td>
                                <td>Juan Pérez</td>
                                <td>iPhone 15 Pro Max, AirPods Pro 2</td>
                                <td>$1,448</td>
                                <td><span class="status completed">Completado</span></td>
                                <td>Tarjeta</td>
                            </tr>
                            <tr>
                                <td>2024-01-16</td>
                                <td>#ORD-002</td>
                                <td>María González</td>
                                <td>MacBook Air M3</td>
                                <td>$1,099</td>
                                <td><span class="status processing">Procesando</span></td>
                                <td>Transferencia</td>
                            </tr>
                            <tr>
                                <td>2024-01-17</td>
                                <td>#ORD-003</td>
                                <td>Carlos Rodríguez</td>
                                <td>Apple Watch Ultra</td>
                                <td>$799</td>
                                <td><span class="status completed">Completado</span></td>
                                <td>Efectivo</td>
                            </tr>
                            <tr>
                                <td>2024-01-18</td>
                                <td>#ORD-004</td>
                                <td>Ana Martínez</td>
                                <td>Canon EOS R5, MacBook Air M3</td>
                                <td>$4,998</td>
                                <td><span class="status shipped">Enviado</span></td>
                                <td>Tarjeta</td>
                            </tr>
                            <tr>
                                <td>2024-01-19</td>
                                <td>#ORD-005</td>
                                <td>Pedro Sánchez</td>
                                <td>PlayStation 5, AirPods Pro 2</td>
                                <td>$748</td>
                                <td><span class="status completed">Completado</span></td>
                                <td>PayPal</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen Ejecutivo -->
            <div class="executive-summary-section">
                <h2>Resumen Ejecutivo</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon positive"><i class="fas fa-chart-line"></i></div>
                        <div class="summary-content">
                            <h4>Ventas vs Objetivo</h4>
                            <p class="summary-value">+15.3%</p>
                            <p class="summary-desc">Por encima del objetivo mensual</p>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon positive"><i class="fas fa-dollar-sign"></i></div>
                        <div class="summary-content">
                            <h4>Ticket Promedio</h4>
                            <p class="summary-value">$892</p>
                            <p class="summary-desc">Aumento del 8.7% vs mes anterior</p>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon neutral"><i class="fas fa-clock"></i></div>
                        <div class="summary-content">
                            <h4>Tiempo de Entrega</h4>
                            <p class="summary-value">2.3 días</p>
                            <p class="summary-desc">Mejora del 12% vs mes anterior</p>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon positive"><i class="fas fa-bullseye"></i></div>
                        <div class="summary-content">
                            <h4>Tasa de Conversión</h4>
                            <p class="summary-value">94.5%</p>
                            <p class="summary-desc">Tasa de completación de pedidos</p>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Reporte de Ventas */
                .filters-section {
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

                .btn-apply-filter, .btn-reset-filter, .btn-download-report, .btn-export-excel {
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

                .btn-apply-filter:hover, .btn-download-report:hover, .btn-export-excel:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                .btn-reset-filter {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .btn-download-report {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                }

                .btn-export-excel {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .charts-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .charts-section h2 {
                    color: #333;
                    margin: 0 0 25px 0;
                    font-size: 1.5rem;
                }

                .charts-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                    gap: 25px;
                }

                .chart-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .chart-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .chart-card.full-width {
                    grid-column: 1 / -1;
                }

                .chart-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }

                .chart-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 1.1rem;
                }

                .chart-period {
                    padding: 5px 10px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    background: white;
                }

                .chart-legend {
                    display: flex;
                    gap: 15px;
                }

                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    font-size: 0.85rem;
                    color: #666;
                }

                .legend-color {
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                }

                .chart-stats {
                    display: flex;
                    gap: 20px;
                }

                .stat-item {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-end;
                }

                .stat-label {
                    font-size: 0.75rem;
                    color: #666;
                }

                .stat-value {
                    font-weight: 700;
                    color: #2196F3;
                }

                .chart-container {
                    height: 300px;
                    position: relative;
                }

                .sales-details-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .sales-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .sales-table th {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .sales-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .sales-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .sales-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .sales-table tr:hover {
                    background-color: #f8f9fa;
                }

                .executive-summary-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .executive-summary-section h2 {
                    color: #333;
                    margin: 0 0 25px 0;
                    font-size: 1.5rem;
                }

                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .summary-item {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .summary-item:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .summary-icon {
                    font-size: 2.5rem;
                }

                .summary-icon i {
                    color: #43e97b;
                }

                .summary-icon.neutral i {
                    color: #f093fb;
                }

                .summary-content h4 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .summary-value {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2196F3;
                    margin: 0 0 5px 0;
                }

                .summary-desc {
                    font-size: 0.85rem;
                    color: #666;
                    margin: 0;
                }

                @media (max-width: 768px) {
                    .charts-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .summary-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .filters-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Gráfico de Ventas Mensuales
                const monthlyCtx = document.getElementById('monthlySalesChart').getContext('2d');
                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                        datasets: [{
                            label: 'Ventas 2024',
                            data: [12000, 15000, 18000, 14000, 20000, 22000, 19000, 24000, 21000, 18000, 25000, 28000],
                            backgroundColor: 'rgba(102, 126, 234, 0.7)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 1
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
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Gráfico de Ventas por Categoría
                const categoryCtx = document.getElementById('categorySalesChart').getContext('2d');
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Smartphones', 'Laptops', 'Audio', 'Smartwatches', 'Cámaras', 'Gaming'],
                        datasets: [{
                            data: [35, 25, 15, 10, 8, 7],
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

                // Gráfico de Tendencia
                const trendCtx = document.getElementById('trendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: ['Ago', 'Sep', 'Oct', 'Nov', 'Dic', 'Ene'],
                        datasets: [{
                            label: 'Ventas',
                            data: [15000, 18000, 16500, 22000, 21000, 24500],
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
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Funciones de filtros
                function applyFilters() {
                    alert('Función de aplicar filtros (conectar con backend)');
                }

                function resetFilters() {
                    document.getElementById('startDate').value = '';
                    document.getElementById('endDate').value = '';
                    document.getElementById('categoryFilter').value = '';
                    document.getElementById('clientFilter').value = '';
                    document.getElementById('statusFilter').value = '';
                }

                function downloadReport() {
                    alert('Función de descargar PDF (conectar con backend)');
                }

                function exportToExcel() {
                    alert('Función de exportar Excel (conectar con backend)');
                }

                function updateChart(type, period) {
                    alert('Función de actualizar gráfico ' + type + ' para período ' + period);
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