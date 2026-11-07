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
        error_log("Error al generar JWT en finanza: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'finanza';
$titulo_pagina = 'Gestión de Ingresos y Egresos';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Filtros de Reporte -->
            <div class="filters-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Reporte de Ingresos y Egresos</h2>
                    <div class="section-actions">
                        <button class="btn-export" onclick="descargarPDF()">
                            <span class="btn-icon"><i class="fas fa-file-pdf"></i></span>
                            Descargar PDF
                        </button>
                    </div>
                </div>
                
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="fechaInicio">
                            <i class="fas fa-calendar-alt"></i>
                            Fecha Inicio
                        </label>
                        <input type="date" id="fechaInicio" class="filter-input">
                    </div>
                    
                    <div class="filter-group">
                        <label for="fechaFin">
                            <i class="fas fa-calendar-alt"></i>
                            Fecha Fin
                        </label>
                        <input type="date" id="fechaFin" class="filter-input">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tipoGrafica">
                            <i class="fas fa-chart-bar"></i>
                            Tipo de Gráfica
                        </label>
                        <select id="tipoGrafica" class="filter-input">
                            <option value="bar">Barras</option>
                            <option value="line">Líneas</option>
                            <option value="pie">Pastel</option>
                            <option value="doughnut">Donas</option>
                            <option value="polarArea">Área Polar</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="tipoConsulta">
                            <i class="fas fa-filter"></i>
                            Tipo de Consulta
                        </label>
                        <select id="tipoConsulta" class="filter-input">
                            <option value="ambos">Ingresos y Egresos</option>
                            <option value="ingresos">Solo Ingresos</option>
                            <option value="egresos">Solo Egresos</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="display: flex; align-items: end;">
                        <button class="btn-generate" onclick="generarReporte()">
                            <i class="fas fa-sync-alt"></i> Generar Reporte
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-content">
                        <h3>Total Ingresos</h3>
                        <p class="card-value" id="totalIngresos">$0.00</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="75, 100" stroke="#28a745" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">75%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="card-content">
                        <h3>Total Egresos</h3>
                        <p class="card-value" id="totalEgresos">$0.00</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="25, 100" stroke="#dc3545" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">25%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card balance">
                    <div class="card-icon"><i class="fas fa-balance-scale"></i></div>
                    <div class="card-content">
                        <h3>Balance</h3>
                        <p class="card-value" id="balance">$0.00</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="50, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">50%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfica y Tabla -->
            <div class="report-layout">
                <div class="chart-section">
                    <h3><i class="fas fa-chart-pie"></i> Gráfica Financiera</h3>
                    <div class="chart-container">
                        <canvas id="graficoFinanzas"></canvas>
                    </div>
                </div>
                
                <div class="table-section">
                    <h3><i class="fas fa-table"></i> Detalle de Movimientos</h3>
                    <div id="tablaReporte" class="report-table">
                        <p class="no-data">Genere un reporte para ver los datos</p>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Finanzas */
                .filters-section {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                    margin-bottom: 30px;
                }

                .filters-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }

                .filter-group {
                    display: flex;
                    flex-direction: column;
                }

                .filter-group label {
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 8px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .filter-group label i {
                    color: #2196F3;
                }

                .filter-input {
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .filter-input:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .btn-generate {
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-generate:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-export {
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(240, 147, 251, 0.3);
                }

                .report-layout {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-top: 30px;
                }

                @media (max-width: 992px) {
                    .report-layout {
                        grid-template-columns: 1fr;
                    }
                }

                .chart-section, .table-section {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .chart-section h3, .table-section h3 {
                    font-size: 1.2rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .chart-section h3 i, .table-section h3 i {
                    color: #2196F3;
                }

                .chart-container {
                    max-width: 600px;
                    margin: 0 auto;
                }

                .report-table {
                    max-height: 400px;
                    overflow-y: auto;
                }

                .no-data {
                    text-align: center;
                    color: #718096;
                    padding: 40px;
                    font-style: italic;
                }

                /* Summary cards específicos de finanzas */
                .summary-card.income .card-icon {
                    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
                }

                .summary-card.expenses .card-icon {
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                }

                .summary-card.balance .card-icon {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }
            </style>

            <script src="assets/public/js/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script>
                const ingresos = <?= json_encode($finanzas['ingresos'] ?? []) ?>;
                const egresos = <?= json_encode($finanzas['egresos'] ?? []) ?>;
                let graficoFinanzas = null;

                function generarColores(n) {
                    return Array.from({length: n}, (_, i) => `hsl(${(360 / n) * i}, 70%, 60%)`);
                }

                function filtrarPorFechas(datos, inicio, fin) {
                    return datos.filter(d => {
                        return (!inicio || d.fecha >= inicio) && (!fin || d.fecha <= fin);
                    });
                }

                function agruparPorMes(datos) {
                    const agrupado = {};
                    datos.forEach(d => {
                        const mes = d.fecha.substr(0,7);
                        if (!agrupado[mes]) agrupado[mes] = 0;
                        agrupado[mes] += parseFloat(d.monto);
                    });
                    return agrupado;
                }

                function generarReporte() {
                    const fechaInicio = document.getElementById('fechaInicio').value;
                    const fechaFin = document.getElementById('fechaFin').value;
                    const tipoGrafica = document.getElementById('tipoGrafica').value;
                    const tipoConsulta = document.getElementById('tipoConsulta').value;

                    let datosFiltradosIngresos = filtrarPorFechas(ingresos, fechaInicio, fechaFin);
                    let datosFiltradosEgresos = filtrarPorFechas(egresos, fechaInicio, fechaFin);

                    const ingresosAgrupados = agruparPorMes(datosFiltradosIngresos);
                    const egresosAgrupados = agruparPorMes(datosFiltradosEgresos);

                    const meses = [...new Set([...Object.keys(ingresosAgrupados), ...Object.keys(egresosAgrupados)])].sort();
                    const datosIngresos = meses.map(m => ingresosAgrupados[m] || 0);
                    const datosEgresos = meses.map(m => egresosAgrupados[m] || 0);

                    const totalIngresos = datosIngresos.reduce((a, b) => a + b, 0);
                    const totalEgresos = datosEgresos.reduce((a, b) => a + b, 0);
                    const balance = totalIngresos - totalEgresos;

                    document.getElementById('totalIngresos').textContent = '$' + totalIngresos.toFixed(2);
                    document.getElementById('totalEgresos').textContent = '$' + totalEgresos.toFixed(2);
                    document.getElementById('balance').textContent = '$' + balance.toFixed(2);

                    if (graficoFinanzas) {
                        graficoFinanzas.destroy();
                    }

                    const ctx = document.getElementById('graficoFinanzas').getContext('2d');
                    const colores = generarColores(meses.length);

                    let datosGrafica, etiquetasGrafica;

                    if (tipoConsulta === 'ingresos') {
                        datosGrafica = datosIngresos;
                        etiquetasGrafica = meses;
                    } else if (tipoConsulta === 'egresos') {
                        datosGrafica = datosEgresos;
                        etiquetasGrafica = meses;
                    } else {
                        datosGrafica = datosIngresos.map((val, i) => val - datosEgresos[i]);
                        etiquetasGrafica = meses;
                    }

                    graficoFinanzas = new Chart(ctx, {
                        type: tipoGrafica,
                        data: {
                            labels: etiquetasGrafica,
                            datasets: [{
                                label: tipoConsulta === 'ambos' ? 'Balance' : (tipoConsulta === 'ingresos' ? 'Ingresos' : 'Egresos'),
                                data: datosGrafica,
                                backgroundColor: colores,
                                borderColor: colores.map(c => c.replace('60%', '50%')),
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                }
                            }
                        }
                    });

                    generarTabla(datosFiltradosIngresos, datosFiltradosEgresos, tipoConsulta);
                }

                function generarTabla(ingresos, egresos, tipoConsulta) {
                    const tablaContainer = document.getElementById('tablaReporte');
                    let html = '<table class="table"><thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Monto</th></tr></thead><tbody>';

                    let datos = [];
                    if (tipoConsulta === 'ingresos' || tipoConsulta === 'ambos') {
                        ingresos.forEach(i => {
                            datos.push({...i, tipo: 'Ingreso'});
                        });
                    }
                    if (tipoConsulta === 'egresos' || tipoConsulta === 'ambos') {
                        egresos.forEach(e => {
                            datos.push({...e, tipo: 'Egreso'});
                        });
                    }

                    datos.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

                    datos.forEach(d => {
                        const clase = d.tipo === 'Ingreso' ? 'text-success' : 'text-danger';
                        html += `<tr><td>${d.fecha}</td><td><span class="${clase}">${d.tipo}</span></td><td>${d.descripcion || '-'}</td><td>$${parseFloat(d.monto).toFixed(2)}</td></tr>`;
                    });

                    html += '</tbody></table>';
                    tablaContainer.innerHTML = html;
                }

                function descargarPDF() {
                    const element = document.querySelector('.filters-section');
                    html2canvas(element).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const pdf = new jspdf.jsPDF();
                        pdf.addImage(imgData, 'PNG', 10, 10, 190, 100);
                        pdf.save('reporte_finanzas.pdf');
                    });
                }

                // Generar reporte inicial
                document.addEventListener('DOMContentLoaded', function() {
                    generarReporte();
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>