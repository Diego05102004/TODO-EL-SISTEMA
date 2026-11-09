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
$titulo_pagina = 'Reportes de Finanzas';

// Totales reales para las summary cards
$totalIngresos = array_sum(array_map(function($r) { return (float)($r['monto'] ?? 0); }, $finanzas['ingresos'] ?? []));
$totalEgresos = array_sum(array_map(function($r) { return (float)($r['monto'] ?? 0); }, $finanzas['egresos'] ?? []));
$balanceNeto = $totalIngresos - $totalEgresos;
$totalPagosCuentas = count($cuentasReportes ?? []);

$jsonOpts = JSON_INVALID_UTF8_SUBSTITUTE;
$ingresosJson = json_encode($finanzas['ingresos'] ?? [], $jsonOpts) ?: '[]';
$egresosJson = json_encode($finanzas['egresos'] ?? [], $jsonOpts) ?: '[]';
$cuentasJson = json_encode($cuentasReportes ?? [], $jsonOpts) ?: '[]';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-content">
                        <h3>Total Ingresos</h3>
                        <p class="card-value" id="card-total-ingresos">$<?php echo number_format($totalIngresos, 2); ?></p>
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
                        <p class="card-value" id="card-total-egresos">$<?php echo number_format($totalEgresos, 2); ?></p>
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
                        <h3>Balance Neto</h3>
                        <p class="card-value" id="card-balance-neto"><?php echo ($balanceNeto >= 0 ? '$' : '-$') . number_format(abs($balanceNeto), 2); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="50, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">50%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-university"></i></div>
                    <div class="card-content">
                        <h3>Pagos por Cuentas</h3>
                        <p class="card-value"><?php echo $totalPagosCuentas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="60, 100" stroke="#6f42c1" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">60%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ REPORTE DE CUENTAS BANCARIAS ============ -->
            <div class="report-card">
                <div class="section-header">
                    <h2><i class="fas fa-university"></i> Reporte de Cuentas Bancarias</h2>
                    <div class="section-actions">
                        <button class="btn-export" id="descargarPDFCuentas">
                            <span class="btn-icon"><i class="fas fa-file-pdf"></i></span>
                            Descargar PDF
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="fechaInicioCuentas"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
                        <input type="date" id="fechaInicioCuentas" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="fechaFinCuentas"><i class="fas fa-calendar-alt"></i> Fecha fin</label>
                        <input type="date" id="fechaFinCuentas" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="agruparPor"><i class="fas fa-layer-group"></i> Agrupar por</label>
                        <select id="agruparPor" class="filter-input">
                            <option value="metodos">Método de Pago</option>
                            <option value="nombre_banco">Banco</option>
                            <option value="nombre">Cliente</option>
                            <option value="estatus">Estatus</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="tipoGraficaCuentas"><i class="fas fa-chart-bar"></i> Tipo de gráfica</label>
                        <select id="tipoGraficaCuentas" class="filter-input">
                            <option value="bar">Barras</option>
                            <option value="line">Líneas</option>
                            <option value="pie">Pastel</option>
                            <option value="doughnut">Donas</option>
                            <option value="polarArea">Área Polar</option>
                        </select>
                    </div>
                    <div class="filter-group" style="display:flex; align-items:end;">
                        <button class="btn-generate" id="generarReporteCuentasBtn">
                            <i class="fas fa-sync-alt"></i> Generar Reporte
                        </button>
                    </div>
                </div>

                <div class="report-result" id="resultadoCuentas">
                    <div class="chart-wrapper">
                        <canvas id="graficoCuentas" height="90"></canvas>
                    </div>
                    <div class="table-wrapper" id="tablaReporteCuentas">
                        <p class="no-data">Genere un reporte para ver los datos</p>
                    </div>
                </div>
            </div>

            <!-- ============ REPORTE DE INGRESOS Y EGRESOS ============ -->
            <div class="report-card">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Reporte de Ingresos y Egresos</h2>
                    <div class="section-actions">
                        <button class="btn-export" id="descargarPDFFinanzas">
                            <span class="btn-icon"><i class="fas fa-file-pdf"></i></span>
                            Descargar PDF
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="fechaInicio"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
                        <input type="date" id="fechaInicio" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="fechaFin"><i class="fas fa-calendar-alt"></i> Fecha fin</label>
                        <input type="date" id="fechaFin" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="tipoGrafica"><i class="fas fa-chart-bar"></i> Tipo de gráfica</label>
                        <select id="tipoGrafica" class="filter-input">
                            <option value="bar">Barras</option>
                            <option value="line">Líneas</option>
                            <option value="pie">Pastel</option>
                            <option value="doughnut">Donas</option>
                            <option value="polarArea">Área Polar</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="tipoConsulta"><i class="fas fa-filter"></i> Tipo de consulta</label>
                        <select id="tipoConsulta" class="filter-input">
                            <option value="ambos">Ingresos y Egresos</option>
                            <option value="ingresos">Solo Ingresos</option>
                            <option value="egresos">Solo Egresos</option>
                        </select>
                    </div>
                    <div class="filter-group" style="display:flex; align-items:end;">
                        <button class="btn-generate" id="generarReporteFinanzasBtn">
                            <i class="fas fa-sync-alt"></i> Generar Reporte
                        </button>
                    </div>
                </div>

                <div class="report-result" id="resultadoFinanzas">
                    <div class="chart-wrapper">
                        <canvas id="graficoFinanzas" height="90"></canvas>
                    </div>
                    <div class="table-wrapper" id="tablaReporteFinanzas">
                        <p class="no-data">Genere un reporte para ver los datos</p>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Reportes */
                .report-card {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                    margin-top: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 15px;
                    margin-bottom: 20px;
                }

                .section-header h2 {
                    margin: 0;
                    color: #2d3748;
                    font-size: 1.3rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .section-header h2 i {
                    color: #2196F3;
                }

                .section-actions {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .filters-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
                    gap: 18px;
                    margin-bottom: 25px;
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
                    font-size: 0.88rem;
                }

                .filter-group label i {
                    color: #2196F3;
                }

                .filter-input {
                    padding: 11px 14px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.92rem;
                    transition: all 0.3s ease;
                    background: white;
                    box-sizing: border-box;
                }

                .filter-input:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .btn-generate {
                    padding: 12px 18px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 0.9rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                    height: fit-content;
                }

                .btn-generate:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-export {
                    padding: 10px 18px;
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 0.88rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(240, 147, 251, 0.3);
                }

                .report-result {
                    width: 100%;
                }

                .chart-wrapper {
                    width: 100%;
                    height: 380px;
                    position: relative;
                    margin-bottom: 25px;
                }

                .table-wrapper {
                    width: 100%;
                    overflow-x: auto;
                    max-height: 420px;
                    overflow-y: auto;
                }

                .no-data {
                    text-align: center;
                    color: #718096;
                    padding: 40px;
                    font-style: italic;
                }

                /* Tablas de reportes con bordes */
                .table-wrapper table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 0.88rem;
                }

                .table-wrapper thead th {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 12px 14px;
                    text-align: left;
                    font-weight: 600;
                    border: 1px solid #1976D2;
                    position: sticky;
                    top: 0;
                    z-index: 2;
                    white-space: nowrap;
                }

                .table-wrapper tbody td {
                    padding: 10px 14px;
                    border: 1px solid #dee2e6;
                    color: #4a5568;
                }

                .table-wrapper tbody tr:nth-child(even) {
                    background: #f8fafc;
                }

                .table-wrapper tbody tr:hover {
                    background: rgba(33, 150, 243, 0.06);
                }

                .table-wrapper tfoot td,
                .table-wrapper tfoot th {
                    padding: 10px 14px;
                    border: 1px solid #dee2e6;
                    font-weight: 700;
                    background: #eef4ff;
                }

                /* Colores de tipo */
                .tipo-ingreso { color: #28a745; font-weight: 600; }
                .tipo-egreso { color: #dc3545; font-weight: 600; }

                /* Resumen de cards por color */
                .summary-card.income .card-icon {
                    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
                }

                .summary-card.expenses .card-icon {
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                }

                .summary-card.balance .card-icon {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
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
            </style>

            <button class="btn-ayuda" id="btn-ayuda-reporte" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script>
            (function () {
                'use strict';

                const ingresos = <?php echo $ingresosJson; ?>;
                const egresos = <?php echo $egresosJson; ?>;
                const cuentasReportes = <?php echo $cuentasJson; ?>;

                let chartCuentas = null;
                let chartFinanzas = null;

                /* ==================== Utilidades ==================== */
                function esc(str) {
                    const div = document.createElement('div');
                    div.textContent = str == null ? '' : String(str);
                    return div.innerHTML;
                }

                function filtrarPorFechas(datos, inicio, fin) {
                    return (datos || []).filter(function (d) {
                        if (!d.fecha) return false;
                        if (inicio && d.fecha < inicio) return false;
                        if (fin && d.fecha > fin) return false;
                        return true;
                    });
                }

                function formatFecha(fecha) {
                    if (!fecha) return '-';
                    if (fecha.length === 10) {
                        const p = fecha.split('-');
                        return p[2] + '/' + p[1] + '/' + p[0];
                    }
                    return fecha;
                }

                function validarFechas(ini, fin) {
                    if (ini && fin && ini > fin) {
                        Swal.fire({ icon: 'error', title: 'Fechas inválidas', text: 'La fecha inicial no puede ser mayor que la fecha final' });
                        return false;
                    }
                    return true;
                }

                /* ==================== Reporte de Cuentas ==================== */
                function agruparPorCampo(datos, campo) {
                    const agrupado = {};
                    datos.forEach(function (d) {
                        const key = d[campo] || 'Desconocido';
                        if (!agrupado[key]) agrupado[key] = 0;
                        agrupado[key] += parseFloat(d.monto) || 0;
                    });
                    return agrupado;
                }

                function generarReporteCuentas() {
                    const fechaInicio = document.getElementById('fechaInicioCuentas').value;
                    const fechaFin = document.getElementById('fechaFinCuentas').value;
                    const campo = document.getElementById('agruparPor').value;
                    const tipoGrafica = document.getElementById('tipoGraficaCuentas').value;

                    if (!validarFechas(fechaInicio, fechaFin)) return;

                    const datosFiltrados = filtrarPorFechas(cuentasReportes, fechaInicio, fechaFin);
                    const agrupado = agruparPorCampo(datosFiltrados, campo);
                    const labels = Object.keys(agrupado);
                    const valores = Object.values(agrupado);

                    const colores = labels.map(function (_, i) {
                        return 'hsl(' + ((360 / (labels.length || 1)) * i) + ',70%,60%)';
                    });

                    const canvas = document.getElementById('graficoCuentas');
                    const ctx = canvas.getContext('2d');
                    if (chartCuentas) chartCuentas.destroy();

                    chartCuentas = new Chart(ctx, {
                        type: tipoGrafica,
                        data: {
                            labels: labels.length ? labels : ['Sin datos'],
                            datasets: [{
                                label: 'Montos agrupados por ' + campo,
                                data: valores.length ? valores : [0],
                                backgroundColor: colores,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: true, position: 'top' },
                                title: { display: true, text: 'Análisis por ' + campo }
                            }
                        }
                    });

                    let tablaHtml = '<table><thead><tr>'
                        + '<th>Fecha</th><th>Cliente</th><th>Cédula</th><th>Banco</th><th>Método</th><th>Monto</th><th>Estatus</th>'
                        + '</tr></thead><tbody>';

                    if (datosFiltrados.length === 0) {
                        tablaHtml += '<tr><td colspan="7" style="text-align:center;color:#718096;padding:30px;">No hay datos para mostrar</td></tr>';
                    } else {
                        datosFiltrados.forEach(function (d) {
                            tablaHtml += '<tr>'
                                + '<td>' + esc(formatFecha(d.fecha)) + '</td>'
                                + '<td>' + esc(d.nombre || d.cliente || '-') + '</td>'
                                + '<td>' + esc(d.cedula || '-') + '</td>'
                                + '<td>' + esc(d.nombre_banco || '-') + '</td>'
                                + '<td>' + esc(d.metodos || '-') + '</td>'
                                + '<td>$' + (parseFloat(d.monto) || 0).toFixed(2) + '</td>'
                                + '<td>' + esc(d.estatus || '-') + '</td>'
                                + '</tr>';
                        });
                    }
                    tablaHtml += '</tbody></table>';
                    document.getElementById('tablaReporteCuentas').innerHTML = tablaHtml;
                }

                /* ==================== Reporte de Finanzas ==================== */
                function agruparPorMes(datos) {
                    const agrupado = {};
                    datos.forEach(function (d) {
                        if (!d.fecha) return;
                        const mes = d.fecha.substr(0, 7);
                        if (!agrupado[mes]) agrupado[mes] = 0;
                        agrupado[mes] += parseFloat(d.monto) || 0;
                    });
                    return agrupado;
                }

                function generarReporteFinanzas() {
                    const fechaInicio = document.getElementById('fechaInicio').value;
                    const fechaFin = document.getElementById('fechaFin').value;
                    const tipoGrafica = document.getElementById('tipoGrafica').value;
                    const tipoConsulta = document.getElementById('tipoConsulta').value;

                    if (!validarFechas(fechaInicio, fechaFin)) return;

                    let ingresosFiltrados = filtrarPorFechas(ingresos, fechaInicio, fechaFin);
                    let egresosFiltrados = filtrarPorFechas(egresos, fechaInicio, fechaFin);

                    const ingresosPorMes = agruparPorMes(ingresosFiltrados);
                    const egresosPorMes = agruparPorMes(egresosFiltrados);

                    const meses = Array.from(new Set([
                        ...Object.keys(ingresosPorMes),
                        ...Object.keys(egresosPorMes)
                    ])).sort();

                    const labels = meses.map(function (m) {
                        const p = m.split('-');
                        return p[1] + '/' + p[0];
                    });
                    const dataIngresos = meses.map(function (m) { return ingresosPorMes[m] || 0; });
                    const dataEgresos = meses.map(function (m) { return egresosPorMes[m] || 0; });

                    const totalIng = ingresosFiltrados.reduce(function (a, b) { return a + (parseFloat(b.monto) || 0); }, 0);
                    const totalEgr = egresosFiltrados.reduce(function (a, b) { return a + (parseFloat(b.monto) || 0); }, 0);

                    // Actualizar cards con los totales filtrados
                    document.getElementById('card-total-ingresos').textContent = '$' + totalIng.toFixed(2);
                    document.getElementById('card-total-egresos').textContent = '$' + totalEgr.toFixed(2);
                    const balance = totalIng - totalEgr;
                    document.getElementById('card-balance-neto').textContent = (balance >= 0 ? '$' : '-$') + Math.abs(balance).toFixed(2);

                    const colorIng = 'rgba(40, 167, 69, 0.7)';
                    const colorEgr = 'rgba(220, 53, 69, 0.7)';
                    let datasets = [];

                    if (tipoConsulta === 'ambos' || tipoConsulta === 'ingresos') {
                        datasets.push({
                            label: 'Ingresos',
                            data: tipoConsulta === 'ambos' ? dataIngresos : dataIngresos,
                            backgroundColor: colorIng,
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 2
                        });
                    }
                    if (tipoConsulta === 'ambos' || tipoConsulta === 'egresos') {
                        datasets.push({
                            label: 'Egresos',
                            data: dataEgresos,
                            backgroundColor: colorEgr,
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 2
                        });
                    }

                    const canvas = document.getElementById('graficoFinanzas');
                    const ctx = canvas.getContext('2d');
                    if (chartFinanzas) chartFinanzas.destroy();

                    chartFinanzas = new Chart(ctx, {
                        type: tipoGrafica,
                        data: {
                            labels: labels.length ? labels : ['Sin datos'],
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: true, position: 'top' },
                                title: { display: true, text: 'Ingresos vs Egresos por Mes' }
                            }
                        }
                    });

                    // Tabla de movimientos
                    let movimientos = [];
                    if (tipoConsulta === 'ambos' || tipoConsulta === 'ingresos') {
                        ingresosFiltrados.forEach(function (ing) {
                            movimientos.push({ tipo: 'Ingreso', fecha: ing.fecha, monto: parseFloat(ing.monto) || 0, descripcion: ing.descripcion || '-' });
                        });
                    }
                    if (tipoConsulta === 'ambos' || tipoConsulta === 'egresos') {
                        egresosFiltrados.forEach(function (eg) {
                            movimientos.push({ tipo: 'Egreso', fecha: eg.fecha, monto: parseFloat(eg.monto) || 0, descripcion: eg.descripcion || '-' });
                        });
                    }
                    movimientos.sort(function (a, b) { return (b.fecha || '').localeCompare(a.fecha || ''); });

                    let tablaHtml = '<table><thead><tr>'
                        + '<th>Tipo</th><th>Fecha</th><th>Descripción</th><th>Monto</th>'
                        + '</tr></thead><tbody>';

                    if (movimientos.length === 0) {
                        tablaHtml += '<tr><td colspan="4" style="text-align:center;color:#718096;padding:30px;">No hay datos para mostrar</td></tr>';
                    } else {
                        movimientos.forEach(function (m) {
                            const esIngreso = m.tipo === 'Ingreso';
                            tablaHtml += '<tr>'
                                + '<td class="' + (esIngreso ? 'tipo-ingreso' : 'tipo-egreso') + '">' + m.tipo + '</td>'
                                + '<td>' + esc(formatFecha(m.fecha)) + '</td>'
                                + '<td>' + esc(m.descripcion) + '</td>'
                                + '<td>$' + m.monto.toFixed(2) + '</td>'
                                + '</tr>';
                        });
                    }
                    tablaHtml += '<tfoot><tr>'
                        + '<td colspan="3" style="text-align:right;">Totales</td>'
                        + '<td><span class="tipo-ingreso">$' + totalIng.toFixed(2) + '</span> &nbsp;/&nbsp; <span class="tipo-egreso">$' + totalEgr.toFixed(2) + '</span></td>'
                        + '</tr></tfoot></table>';

                    document.getElementById('tablaReporteFinanzas').innerHTML = tablaHtml;
                }

                /* ==================== Descarga PDF ==================== */
                function descargarPDF(elemento, nombreArchivo) {
                    if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Las librerías de PDF no están cargadas' });
                        return;
                    }
                    Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    const wrappers = elemento.querySelectorAll('.table-wrapper');
                    const prev = [];
                    wrappers.forEach(function (w) {
                        prev.push({ el: w, mh: w.style.maxHeight, ov: w.style.overflowY });
                        w.style.maxHeight = 'none';
                        w.style.overflowY = 'visible';
                    });
                    html2canvas(elemento, { scale: 2, useCORS: true }).then(function (canvas) {
                        wrappers.forEach(function (w, i) {
                            w.style.maxHeight = prev[i].mh;
                            w.style.overflowY = prev[i].ov;
                        });
                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF('l', 'pt', 'a4');
                        const imgData = canvas.toDataURL('image/png');
                        const pageWidth = pdf.internal.pageSize.getWidth();
                        const pageHeight = pdf.internal.pageSize.getHeight();
                        const imgWidth = pageWidth - 40;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        let heightLeft = imgHeight;
                        let position = 10;
                        pdf.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight - 20;
                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight + 10;
                            pdf.addPage();
                            pdf.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
                            heightLeft -= pageHeight;
                        }
                        pdf.save(nombreArchivo);
                        Swal.close();
                    }).catch(function (err) {
                        wrappers.forEach(function (w, i) {
                            w.style.maxHeight = prev[i].mh;
                            w.style.overflowY = prev[i].ov;
                        });
                        console.error('Error generando PDF:', err);
                        Swal.close();
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el PDF' });
                    });
                }

                document.getElementById('descargarPDFCuentas').addEventListener('click', function () {
                    descargarPDF(document.getElementById('resultadoCuentas'), 'Reporte_Cuentas.pdf');
                });

                document.getElementById('descargarPDFFinanzas').addEventListener('click', function () {
                    descargarPDF(document.getElementById('resultadoFinanzas'), 'Reporte_Finanzas.pdf');
                });

                /* ==================== Eventos ==================== */
                ['fechaInicioCuentas', 'fechaFinCuentas', 'agruparPor', 'tipoGraficaCuentas'].forEach(function (id) {
                    document.getElementById(id).addEventListener('change', generarReporteCuentas);
                });
                document.getElementById('generarReporteCuentasBtn').addEventListener('click', generarReporteCuentas);

                ['fechaInicio', 'fechaFin', 'tipoGrafica', 'tipoConsulta'].forEach(function (id) {
                    document.getElementById(id).addEventListener('change', generarReporteFinanzas);
                });
                document.getElementById('generarReporteFinanzasBtn').addEventListener('click', generarReporteFinanzas);

                /* ==================== Modal de ayuda ==================== */
                let modalAyudaInstance = null;

                function cargarRecursoAyuda(tipo, url) {
                    return new Promise(function (resolve, reject) {
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
                            const html = await (await fetch('assets/public/ayuda/reporteFinanzas.php')).text();
                            document.body.insertAdjacentHTML('beforeend', html);
                        }
                        await cargarRecursoAyuda('js', 'assets/public/ayuda/js/modal.js');
                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
                            document.body.style.overflow = '';
                            if (contexto && modalAyudaInstance.mapeoContextos[contexto] !== undefined) {
                                setTimeout(function () { modalAyudaInstance.goToSlide(modalAyudaInstance.mapeoContextos[contexto]); }, 300);
                            }
                            modalAyudaInstance.openModal();
                        }
                    } catch (err) {
                        console.error('Error al cargar el modal de ayuda:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el contenido de ayuda' });
                    }
                }

                document.getElementById('btn-ayuda-reporte')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda();
                });

                /* ==================== Init ==================== */
                function inicializarReportes() {
                    try {
                        generarReporteCuentas();
                    } catch (e) {
                        console.error('[reporteFinanzas] Error generando reporte de cuentas:', e);
                    }
                    try {
                        generarReporteFinanzas();
                    } catch (e) {
                        console.error('[reporteFinanzas] Error generando reporte de finanzas:', e);
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', inicializarReportes);
                } else {
                    inicializarReportes();
                }
            })();
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>
