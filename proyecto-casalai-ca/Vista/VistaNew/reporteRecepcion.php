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
        error_log("Error al generar JWT en reporteRecepcion: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'reporteRecepcion';
$titulo_pagina = 'Reportes de Recepción';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Reporte de Recepciones Section -->
            <div class="reporte-section">
                <div class="section-header">
                    <h2>Reportes de Recepción</h2>
                    <button class="btn-back" onclick="window.location.href='?pagina=recepcion'">
                        <span class="btn-icon"><i class="fas fa-arrow-left"></i></span>
                        Volver a Recepciones
                    </button>
                </div>

                <!-- Filtros de Reporte -->
                <div class="reporte-filters">
                    <div class="filter-card">
                        <h3><i class="fas fa-filter"></i> Filtros del Reporte</h3>
                        <form id="reporteForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipoReporte">Tipo de Reporte*</label>
                                    <select id="tipoReporte" name="tipoReporte" required>
                                        <option value="">Seleccione un tipo</option>
                                        <option value="todos">Todos los Reportes</option>
                                        <option value="proveedor">Recepciones por Proveedor</option>
                                        <option value="productos">Productos más Recibidos</option>
                                        <option value="mensual">Recepciones Mensuales</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="tipoGrafica">Tipo de Gráfica*</label>
                                    <select id="tipoGrafica" name="tipoGrafica" required>
                                        <option value="">Seleccione un tipo</option>
                                        <option value="bar">Barras</option>
                                        <option value="pie">Pastel</option>
                                        <option value="line">Líneas</option>
                                        <option value="doughnut">Rosca</option>
                                        <option value="polarArea">Área Polar</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fechaInicio">Fecha Desde*</label>
                                    <input type="date" id="fechaInicio" name="fechaInicio" required>
                                </div>

                                <div class="form-group">
                                    <label for="fechaFin">Fecha Hasta*</label>
                                    <input type="date" id="fechaFin" name="fechaFin" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-generate">
                                    <span class="btn-icon"><i class="fas fa-chart-bar"></i></span>
                                    Generar Reporte
                                </button>
                                <button type="button" class="btn-pdf" onclick="generarPDF()">
                                    <span class="btn-icon"><i class="fas fa-file-pdf"></i></span>
                                    Descargar PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Área de Gráficas -->
                <div class="graficas-container">
                    <div class="grafica-card">
                        <div class="grafica-header">
                            <h3>Recepciones por Proveedor</h3>
                            <div class="grafica-actions">
                                <button class="btn-grafica-action" onclick="exportarGrafica('proveedor')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-grafica-action" onclick="expandirGrafica('proveedor')">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grafica-content">
                            <canvas id="graficaProveedor"></canvas>
                        </div>
                    </div>

                    <div class="grafica-card">
                        <div class="grafica-header">
                            <h3>Productos más Recibidos</h3>
                            <div class="grafica-actions">
                                <button class="btn-grafica-action" onclick="exportarGrafica('productos')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-grafica-action" onclick="expandirGrafica('productos')">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grafica-content">
                            <canvas id="graficaProductos"></canvas>
                        </div>
                    </div>

                    <div class="grafica-card full-width">
                        <div class="grafica-header">
                            <h3>Tendencia de Recepciones Mensuales</h3>
                            <div class="grafica-actions">
                                <button class="btn-grafica-action" onclick="exportarGrafica('mensual')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-grafica-action" onclick="expandirGrafica('mensual')">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grafica-content">
                            <canvas id="graficaMensual"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Datos -->
                <div class="resumen-container">
                    <div class="resumen-card">
                        <div class="resumen-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="resumen-content">
                            <h4>Total Recepciones</h4>
                            <p class="resumen-value">156</p>
                            <span class="resumen-trend positive">+12% vs mes anterior</span>
                        </div>
                    </div>

                    <div class="resumen-card">
                        <div class="resumen-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="resumen-content">
                            <h4>Productos Recibidos</h4>
                            <p class="resumen-value">1,234</p>
                            <span class="resumen-trend positive">+8% vs mes anterior</span>
                        </div>
                    </div>

                    <div class="resumen-card">
                        <div class="resumen-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="resumen-content">
                            <h4>Valor Total</h4>
                            <p class="resumen-value">$45,678</p>
                            <span class="resumen-trend positive">+15% vs mes anterior</span>
                        </div>
                    </div>

                    <div class="resumen-card">
                        <div class="resumen-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="resumen-content">
                            <h4>Proveedores Activos</h4>
                            <p class="resumen-value">12</p>
                            <span class="resumen-trend neutral">Sin cambios</span>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Reporte de Recepción */
                .reporte-section {
                    margin-top: 30px;
                }

                .reporte-filters {
                    margin-top: 20px;
                }

                .filter-card {
                    background: white;
                    border-radius: 16px;
                    padding: 30px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .filter-card h3 {
                    font-size: 1.3rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0 0 25px 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .filter-card h3 i {
                    color: #2196F3;
                }

                .form-actions {
                    display: flex;
                    gap: 15px;
                    margin-top: 25px;
                }

                .btn-generate, .btn-pdf {
                    flex: 1;
                    padding: 14px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                }

                .btn-generate {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-generate:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-pdf {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-pdf:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(245, 101, 101, 0.3);
                }

                .graficas-container {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 25px;
                    margin-top: 30px;
                }

                .grafica-card {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .grafica-card.full-width {
                    grid-column: 1 / -1;
                }

                .grafica-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid rgba(33, 150, 243, 0.1);
                }

                .grafica-header h3 {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0;
                }

                .grafica-actions {
                    display: flex;
                    gap: 8px;
                }

                .btn-grafica-action {
                    width: 35px;
                    height: 35px;
                    border: none;
                    border-radius: 8px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .btn-grafica-action:hover {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
                }

                .grafica-content {
                    min-height: 300px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .resumen-container {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 20px;
                    margin-top: 30px;
                }

                .resumen-card {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .resumen-icon {
                    width: 50px;
                    height: 50px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .resumen-icon i {
                    font-size: 1.5rem;
                    color: white;
                }

                .resumen-content h4 {
                    font-size: 0.85rem;
                    font-weight: 600;
                    color: #718096;
                    margin: 0 0 5px 0;
                }

                .resumen-value {
                    font-size: 1.5rem;
                    font-weight: 800;
                    color: #2d3748;
                    margin: 0 0 5px 0;
                }

                .resumen-trend {
                    font-size: 0.75rem;
                    font-weight: 600;
                }

                .resumen-trend.positive {
                    color: #48bb78;
                }

                .resumen-trend.negative {
                    color: #f56565;
                }

                .resumen-trend.neutral {
                    color: #718096;
                }

                .btn-back {
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
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .graficas-container {
                        grid-template-columns: 1fr;
                    }

                    .resumen-container {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .form-actions {
                        flex-direction: column;
                    }
                }
            </style>

            <script>
                function generarPDF() {
                    alert('Función para generar PDF del reporte (conectar con backend)');
                }

                function exportarGrafica(tipo) {
                    alert('Función para exportar gráfica: ' + tipo);
                }

                function expandirGrafica(tipo) {
                    alert('Función para expandir gráfica: ' + tipo);
                }

                // Set default dates (last 30 days)
                document.addEventListener('DOMContentLoaded', function() {
                    const fechaInicio = document.getElementById('fechaInicio');
                    const fechaFin = document.getElementById('fechaFin');
                    
                    if (fechaInicio && fechaFin) {
                        const today = new Date();
                        const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                        
                        fechaFin.value = today.toISOString().split('T')[0];
                        fechaInicio.value = thirtyDaysAgo.toISOString().split('T')[0];
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>