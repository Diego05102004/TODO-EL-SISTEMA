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

            <?php if (!empty($errorFacturasReporte)): ?>
                <p class="report-error" role="alert">No fue posible cargar los datos de facturación. Revisa el registro del servidor e inténtalo de nuevo.</p>
            <?php endif; ?>
            <?php if (!empty($errorDespachosReporte)): ?>
                <p class="report-error" role="alert">No fue posible cargar uno o más reportes de despachos. Revisa el registro del servidor.</p>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Ingresos de facturas pagadas</h3>
                        <p class="card-value" id="revenueTotal">Bs. 0,00</p>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="card-content">
                        <h3>Facturas pagadas</h3>
                        <p class="card-value" id="paidInvoicesCount">0</p>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-receipt"></i></div>
                    <div class="card-content">
                        <h3>Ticket promedio pagado</h3>
                        <p class="card-value" id="averageInvoiceTotal">Bs. 0,00</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon"><i class="fas fa-truck"></i></div>
                    <div class="card-content">
                        <h3>Despachos activos</h3>
                        <p class="card-value" id="activeDispatches"><?= (int)($totalDespachos ?? 0) ?></p>
                    </div>
                </div>
            </div>

            <div class="filters-section">
                <div class="section-header">
                    <h2>Filtros de facturas</h2>
                    <div class="section-actions">
                        <button class="btn-apply-filter" onclick="applyFilters()">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Aplicar filtros
                        </button>
                        <button class="btn-reset-filter" onclick="resetFilters()">
                            <span class="btn-icon"><i class="fas fa-sync"></i></span>
                            Restablecer
                        </button>
                        <button class="btn-download-report" onclick="downloadReport()">
                            <span class="btn-icon"><i class="fas fa-download"></i></span>
                            Imprimir / guardar PDF
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="startDate">Rango de fechas</label>
                        <div class="date-range">
                            <input type="date" id="startDate" name="startDate">
                            <span>a</span>
                            <input type="date" id="endDate" name="endDate">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="clientFilter">Cliente</label>
                        <select id="clientFilter" name="cliente">
                            <option value="">Todos los clientes</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="statusFilter">Estado de factura</label>
                        <select id="statusFilter" name="estado">
                            <option value="">Todos los estados</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="charts-section">
                <h2>Análisis de facturación</h2>
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Ingresos mensuales pagados</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlySalesChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Unidades por producto facturado</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="productSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="charts-section">
                <h2>Reportes de despachos</h2>
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Productos despachados por mes</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="dispatchMonthChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Órdenes por estado</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="dispatchStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Despachos por cliente</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="dispatchClientChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Despachos por tipo de compra</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="dispatchTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sales-details-section">
                <div class="section-header">
                    <h2>Facturas</h2>
                    <div class="section-actions">
                        <button class="btn-export-excel" onclick="exportToExcel()">
                            <span class="btn-icon"><i class="fas fa-file-csv"></i></span>
                            Exportar CSV para Excel
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Factura</th>
                                <th>Cliente</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody"></tbody>
                    </table>
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

                .report-error {
                    padding: 14px 18px;
                    margin-bottom: 20px;
                    border-left: 4px solid #c62828;
                    border-radius: 6px;
                    background: #ffebee;
                    color: #8e0000;
                }

                .sales-details-section .table-container {
                    overflow-x: auto;
                }

                .sales-table .empty-state {
                    padding: 24px;
                    text-align: center;
                    color: #666;
                }

                .status-badge {
                    display: inline-block;
                    padding: 5px 10px;
                    border-radius: 999px;
                    background: #e8f1fb;
                    color: #174f84;
                    white-space: nowrap;
                }

                @media print {
                    .filters-section,
                    .section-actions {
                        display: none !important;
                    }

                    .charts-section,
                    .sales-details-section,
                    .summary-card {
                        break-inside: avoid;
                        box-shadow: none;
                    }
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
                const reporteVentasData = {
                    facturas: <?= json_encode($facturasReporte ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    detalles: <?= json_encode($detallesFacturasReporte ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    despachos: {
                        estado: <?= json_encode($despachoEstado ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                        mes: <?= json_encode($despachoMes ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                        cliente: <?= json_encode($despachoCliente ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                        tipoCompra: <?= json_encode($despachoTipoCompra ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
                    }
                };

                const estadosPagados = new Set(['pagada', 'pagada presencialmente']);
                const formatoMoneda = new Intl.NumberFormat('es-VE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                const graficosVentas = {};

                function normalizarTexto(valor) {
                    return String(valor || '').trim().toLocaleLowerCase();
                }

                function esFacturaPagada(factura) {
                    return estadosPagados.has(normalizarTexto(factura.estatus));
                }

                function formatearMoneda(valor) {
                    const monto = Number(valor);
                    return 'Bs. ' + formatoMoneda.format(Number.isFinite(monto) ? monto : 0);
                }

                function obtenerFacturasFiltradas() {
                    const fechaInicio = document.getElementById('startDate').value;
                    const fechaFin = document.getElementById('endDate').value;
                    const cliente = document.getElementById('clientFilter').value;
                    const estado = document.getElementById('statusFilter').value;

                    return reporteVentasData.facturas.filter(function(factura) {
                        const fecha = String(factura.fecha || '').slice(0, 10);
                        return (!fechaInicio || fecha >= fechaInicio) &&
                            (!fechaFin || fecha <= fechaFin) &&
                            (!cliente || String(factura.cliente || '') === cliente) &&
                            (!estado || String(factura.estatus || '') === estado);
                    });
                }

                function formatearFecha(fecha) {
                    const fechaTexto = String(fecha || '').slice(0, 10);
                    const fechaObjeto = new Date(fechaTexto + 'T12:00:00');
                    return Number.isNaN(fechaObjeto.getTime())
                        ? fechaTexto
                        : new Intl.DateTimeFormat('es-VE').format(fechaObjeto);
                }

                function obtenerProductosFactura(idFactura) {
                    const detalles = reporteVentasData.detalles[idFactura] || [];
                    const productos = detalles.map(function(detalle) {
                        return detalle.nombre_combo || detalle.nombre_producto || 'Producto';
                    });
                    return Array.from(new Set(productos)).join(', ') || 'Sin detalle';
                }

                function actualizarTabla(facturas) {
                    const cuerpo = document.getElementById('salesTableBody');
                    cuerpo.replaceChildren();

                    if (facturas.length === 0) {
                        const fila = document.createElement('tr');
                        const celda = document.createElement('td');
                        celda.colSpan = 6;
                        celda.className = 'empty-state';
                        celda.textContent = 'No hay facturas que coincidan con los filtros seleccionados.';
                        fila.appendChild(celda);
                        cuerpo.appendChild(fila);
                        return;
                    }

                    facturas.forEach(function(factura) {
                        const fila = document.createElement('tr');
                        const valores = [
                            formatearFecha(factura.fecha),
                            '#' + String(factura.id_factura || ''),
                            factura.cliente || 'Cliente no registrado',
                            obtenerProductosFactura(factura.id_factura),
                            formatearMoneda(factura.total_final),
                            factura.estatus || 'Sin estado'
                        ];

                        valores.forEach(function(valor, indice) {
                            const celda = document.createElement('td');
                            if (indice === 5) {
                                const estado = document.createElement('span');
                                estado.className = 'status-badge';
                                estado.textContent = valor;
                                celda.appendChild(estado);
                            } else {
                                celda.textContent = valor;
                            }
                            fila.appendChild(celda);
                        });
                        cuerpo.appendChild(fila);
                    });
                }

                function agruparVentasMensuales(facturas) {
                    const montos = {};
                    facturas.filter(esFacturaPagada).forEach(function(factura) {
                        const mes = String(factura.fecha || '').slice(0, 7);
                        if (mes) {
                            montos[mes] = (montos[mes] || 0) + Number(factura.total_final || 0);
                        }
                    });

                    const meses = Object.keys(montos).sort();
                    return {
                        labels: meses.map(function(mes) {
                            return new Intl.DateTimeFormat('es-VE', { month: 'short', year: 'numeric' })
                                .format(new Date(mes + '-15T12:00:00'));
                        }),
                        values: meses.map(function(mes) { return montos[mes]; })
                    };
                }

                function agruparProductosFacturados(facturas) {
                    const cantidades = {};
                    facturas.filter(esFacturaPagada).forEach(function(factura) {
                        const detalles = reporteVentasData.detalles[factura.id_factura] || [];
                        detalles.forEach(function(detalle) {
                            const nombre = detalle.nombre_combo || detalle.nombre_producto || 'Producto';
                            cantidades[nombre] = (cantidades[nombre] || 0) + Number(detalle.cantidad || 0);
                        });
                    });

                    return Object.entries(cantidades)
                        .sort(function(a, b) { return b[1] - a[1]; })
                        .slice(0, 10);
                }

                function normalizarDatosDespacho(datos, etiqueta) {
                    return Array.isArray(datos)
                        ? datos.filter(function(fila) { return fila && !fila.error; })
                            .map(function(fila) {
                                return {
                                    label: String(fila[etiqueta] || fila.label || 'Sin dato'),
                                    value: Number(fila.value || fila.total || 0)
                                };
                            })
                        : [];
                }

                function crearGrafico(id, tipo, datos, opciones) {
                    const elemento = document.getElementById(id);
                    if (!elemento) {
                        return;
                    }
                    if (graficosVentas[id]) {
                        graficosVentas[id].destroy();
                    }
                    graficosVentas[id] = new Chart(elemento.getContext('2d'), {
                        type: tipo,
                        data: datos,
                        options: Object.assign({
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: tipo === 'doughnut' } }
                        }, opciones || {})
                    });
                }

                function actualizarGraficosFacturas(facturas) {
                    const mensual = agruparVentasMensuales(facturas);
                    crearGrafico('monthlySalesChart', 'bar', {
                        labels: mensual.labels,
                        datasets: [{
                            label: 'Ingresos pagados',
                            data: mensual.values,
                            backgroundColor: 'rgba(33, 150, 243, 0.72)',
                            borderColor: '#1976D2',
                            borderWidth: 1
                        }]
                    }, {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: function(valor) { return formatearMoneda(valor); } }
                            }
                        }
                    });

                    const productos = agruparProductosFacturados(facturas);
                    crearGrafico('productSalesChart', 'bar', {
                        labels: productos.map(function(producto) { return producto[0]; }),
                        datasets: [{
                            label: 'Unidades facturadas',
                            data: productos.map(function(producto) { return producto[1]; }),
                            backgroundColor: 'rgba(118, 75, 162, 0.72)',
                            borderColor: '#764ba2',
                            borderWidth: 1
                        }]
                    }, {
                        indexAxis: 'y',
                        scales: { x: { beginAtZero: true } },
                        plugins: { legend: { display: false } }
                    });
                }

                function actualizarGraficosDespachos() {
                    const despachos = reporteVentasData.despachos;
                    const mensual = normalizarDatosDespacho(despachos.mes, 'mes');
                    const estado = normalizarDatosDespacho(despachos.estado, 'label');
                    const cliente = normalizarDatosDespacho(despachos.cliente, 'cliente');
                    const tipo = normalizarDatosDespacho(despachos.tipoCompra, 'tipo_compra');

                    crearGrafico('dispatchMonthChart', 'bar', {
                        labels: mensual.map(function(fila) { return fila.label; }),
                        datasets: [{
                            label: 'Unidades despachadas',
                            data: mensual.map(function(fila) { return fila.value; }),
                            backgroundColor: 'rgba(17, 153, 142, 0.72)',
                            borderColor: '#11998e',
                            borderWidth: 1
                        }]
                    });
                    crearGrafico('dispatchStatusChart', 'doughnut', {
                        labels: estado.map(function(fila) { return fila.label; }),
                        datasets: [{
                            data: estado.map(function(fila) { return fila.value; }),
                            backgroundColor: ['#2196F3', '#764ba2', '#43e97b', '#f093fb', '#f5576c']
                        }]
                    });
                    crearGrafico('dispatchClientChart', 'bar', {
                        labels: cliente.map(function(fila) { return fila.label; }),
                        datasets: [{
                            label: 'Despachos',
                            data: cliente.map(function(fila) { return fila.value; }),
                            backgroundColor: 'rgba(102, 126, 234, 0.72)'
                        }]
                    }, { indexAxis: 'y', scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } });
                    crearGrafico('dispatchTypeChart', 'doughnut', {
                        labels: tipo.map(function(fila) { return fila.label; }),
                        datasets: [{
                            data: tipo.map(function(fila) { return fila.value; }),
                            backgroundColor: ['#43e97b', '#2196F3', '#f093fb', '#f5576c', '#764ba2']
                        }]
                    });
                }

                function actualizarReporte() {
                    const facturas = obtenerFacturasFiltradas();
                    const pagadas = facturas.filter(esFacturaPagada);
                    const ingresos = pagadas.reduce(function(total, factura) {
                        return total + Number(factura.total_final || 0);
                    }, 0);

                    document.getElementById('revenueTotal').textContent = formatearMoneda(ingresos);
                    document.getElementById('paidInvoicesCount').textContent = String(pagadas.length);
                    document.getElementById('averageInvoiceTotal').textContent = formatearMoneda(
                        pagadas.length ? ingresos / pagadas.length : 0
                    );
                    actualizarTabla(facturas);
                    actualizarGraficosFacturas(facturas);
                }

                function applyFilters() {
                    const fechaInicio = document.getElementById('startDate').value;
                    const fechaFin = document.getElementById('endDate').value;
                    if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                        alert('La fecha inicial no puede ser posterior a la fecha final.');
                        return;
                    }
                    actualizarReporte();
                }

                function resetFilters() {
                    document.getElementById('startDate').value = '';
                    document.getElementById('endDate').value = '';
                    document.getElementById('clientFilter').value = '';
                    document.getElementById('statusFilter').value = '';
                    actualizarReporte();
                }

                function downloadReport() {
                    window.print();
                }

                function escaparCsv(valor) {
                    return '"' + String(valor == null ? '' : valor).replace(/"/g, '""') + '"';
                }

                function exportToExcel() {
                    const filas = obtenerFacturasFiltradas();
                    const datos = [
                        ['Fecha', 'Factura', 'Cliente', 'Productos', 'Total final', 'Estado'],
                        ...filas.map(function(factura) {
                            return [
                                formatearFecha(factura.fecha),
                                factura.id_factura,
                                factura.cliente || 'Cliente no registrado',
                                obtenerProductosFactura(factura.id_factura),
                                factura.total_final,
                                factura.estatus || 'Sin estado'
                            ];
                        })
                    ];
                    const csv = '\uFEFF' + datos.map(function(fila) {
                        return fila.map(escaparCsv).join(',');
                    }).join('\r\n');
                    const enlace = document.createElement('a');
                    enlace.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
                    enlace.download = 'reporte_ventas.csv';
                    enlace.click();
                    setTimeout(function() { URL.revokeObjectURL(enlace.href); }, 1000);
                }

                function inicializarFiltros() {
                    const clientes = Array.from(new Set(reporteVentasData.facturas
                        .map(function(factura) { return factura.cliente; })
                        .filter(Boolean))).sort();
                    const estados = Array.from(new Set(reporteVentasData.facturas
                        .map(function(factura) { return factura.estatus; })
                        .filter(Boolean))).sort();

                    clientes.forEach(function(cliente) {
                        const opcion = document.createElement('option');
                        opcion.value = cliente;
                        opcion.textContent = cliente;
                        document.getElementById('clientFilter').appendChild(opcion);
                    });
                    estados.forEach(function(estado) {
                        const opcion = document.createElement('option');
                        opcion.value = estado;
                        opcion.textContent = estado;
                        document.getElementById('statusFilter').appendChild(opcion);
                    });
                }

                document.addEventListener('DOMContentLoaded', function() {
                    inicializarFiltros();
                    actualizarReporte();
                    actualizarGraficosDespachos();
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>