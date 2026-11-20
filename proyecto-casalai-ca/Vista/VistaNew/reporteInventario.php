<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

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

$pagina_actual = 'reporteInventario';
$titulo_pagina = 'Reporte de Inventario';

$jsonOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$proveedoresJson = json_encode($RecepcionesProveedor ?? [], $jsonOptions) ?: '[]';
$productosJson = json_encode($ProductosRecibidos ?? [], $jsonOptions) ?: '[]';
$mensualJson = json_encode($RecepcionMensual ?? [], $jsonOptions) ?: '[]';

$totalRecepciones = array_sum(array_map(
    static function ($fila) { return (float) ($fila['value'] ?? 0); },
    $RecepcionesProveedor ?? []
));
$totalProductos = array_sum(array_map(
    static function ($fila) { return (float) ($fila['value'] ?? 0); },
    $ProductosRecibidos ?? []
));
$totalMeses = array_sum(array_map(
    static function ($fila) { return (float) ($fila['value'] ?? 0); },
    $RecepcionMensual ?? []
));
$cantidadProveedores = count($RecepcionesProveedor ?? []);

ob_start();
?>

<div class="summary-cards">
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-inbox"></i></div>
        <div class="card-content">
            <h3>Recepciones</h3>
            <p class="card-value"><?php echo number_format($totalRecepciones); ?></p>
        </div>
    </div>
    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-boxes"></i></div>
        <div class="card-content">
            <h3>Productos Recibidos</h3>
            <p class="card-value"><?php echo number_format($totalProductos); ?></p>
        </div>
    </div>
    <div class="summary-card balance">
        <div class="card-icon"><i class="fas fa-building"></i></div>
        <div class="card-content">
            <h3>Proveedores</h3>
            <p class="card-value"><?php echo number_format($cantidadProveedores); ?></p>
        </div>
    </div>
    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="card-content">
            <h3>Recepciones del Año</h3>
            <p class="card-value"><?php echo number_format($totalMeses); ?></p>
        </div>
    </div>
</div>

<section class="report-card" id="resultadoInventario">
    <div class="section-header">
        <h2><i class="fas fa-chart-pie"></i> Reportes de Recepción</h2>
        <div class="section-actions">
            <button type="button" class="btn-primary-action" id="generarReporteRecepcion">
                <i class="fas fa-chart-bar"></i> Generar Reporte
            </button>
            <button type="button" class="btn-export" id="descargarTodosReportes">
                <i class="fas fa-file-pdf"></i> Descargar Todos los Reportes
            </button>
        </div>
    </div>

    <div class="filters-grid">
        <div class="filter-group">
            <label for="tipoReporteRecepcion">Tipo de reporte</label>
            <select id="tipoReporteRecepcion">
                <option value="todos">Todos los Reportes</option>
                <option value="proveedores">Recepciones por Proveedor</option>
                <option value="productos">Productos más Recibidos</option>
                <option value="mensual">Recepciones Mensuales</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="fechaInicioRecepcion">Fecha inicio</label>
            <input type="date" id="fechaInicioRecepcion">
        </div>
        <div class="filter-group">
            <label for="fechaFinRecepcion">Fecha fin</label>
            <input type="date" id="fechaFinRecepcion">
        </div>
        <div class="filter-group">
            <label for="tipoGraficaRecepcion">Tipo de gráfica</label>
            <select id="tipoGraficaRecepcion">
                <option value="bar">Barras</option>
                <option value="pie">Pastel</option>
                <option value="line">Líneas</option>
                <option value="doughnut">Donas</option>
                <option value="polarArea">Área Polar</option>
            </select>
        </div>
    </div>
    <div id="parametrosRecepcion" class="filters-grid dynamic-filter"></div>

    <div id="mensajeErrorRecepcion" class="no-data" style="display:none;">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

    <section class="report-subcard reporte-seccion" id="reporteProveedores">
        <div class="report-subcard-header">
            <h3><i class="fas fa-building"></i> Recepciones por Proveedor</h3>
        </div>
        <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header"><h3>Recepciones por Proveedor</h3></div>
            <div class="chart-container"><canvas id="graficoProveedores"></canvas></div>
        </div>
        <div class="chart-card table-chart-card">
            <div class="chart-header"><h3>Datos del reporte</h3></div>
            <div id="tablaProveedores" class="table-wrapper"></div>
            <div class="section-actions">
                <button type="button" class="btn-export" onclick="descargarPDFRecepcion('reporteProveedores','Reporte_Recepciones_Proveedores.pdf')">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                <button type="button" class="btn-primary-action" onclick="descargarImagenRecepcion('graficoProveedores','Grafico_Recepciones_Proveedores.png')">
                    <i class="fas fa-image"></i> Descargar Gráfico
                </button>
            </div>
        </div>
        </div>
    </section>

    <section class="report-subcard reporte-seccion" id="reporteProductos">
        <div class="report-subcard-header">
            <h3><i class="fas fa-boxes"></i> Productos más Recibidos</h3>
        </div>
        <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header"><h3>Productos más Recibidos</h3></div>
            <div class="chart-container"><canvas id="graficoProductos"></canvas></div>
        </div>
        <div class="chart-card table-chart-card">
            <div class="chart-header"><h3>Datos del reporte</h3></div>
            <div id="tablaProductos" class="table-wrapper"></div>
            <div class="section-actions">
                <button type="button" class="btn-export" onclick="descargarPDFRecepcion('reporteProductos','Reporte_Recepciones_Productos.pdf')">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                <button type="button" class="btn-primary-action" onclick="descargarImagenRecepcion('graficoProductos','Grafico_Recepciones_Productos.png')">
                    <i class="fas fa-image"></i> Descargar Gráfico
                </button>
            </div>
        </div>
        </div>
    </section>

    <section class="report-subcard reporte-seccion" id="reporteMensual">
        <div class="report-subcard-header">
            <h3><i class="fas fa-calendar-alt"></i> Recepciones Mensuales</h3>
        </div>
        <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header"><h3>Recepciones Mensuales</h3></div>
            <div class="chart-container"><canvas id="graficoMensual"></canvas></div>
        </div>
        <div class="chart-card table-chart-card">
            <div class="chart-header"><h3>Datos del reporte</h3></div>
            <div id="tablaMensual" class="table-wrapper"></div>
            <div class="section-actions">
                <button type="button" class="btn-export" onclick="descargarPDFRecepcion('reporteMensual','Reporte_Recepciones_Mensual.pdf')">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                <button type="button" class="btn-primary-action" onclick="descargarImagenRecepcion('graficoMensual','Grafico_Recepciones_Mensual.png')">
                    <i class="fas fa-image"></i> Descargar Gráfico
                </button>
            </div>
        </div>
        </div>
    </section>
</section>

<style>
.report-card { background:white; border-radius:16px; padding:25px; box-shadow:0 4px 15px rgba(0,0,0,.08); }
.filters-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin:20px 0; }
.dynamic-filter { margin:0; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
.filter-group { display:flex; flex-direction:column; gap:8px; }
.filter-group label { font-weight:600; color:#4a5568; }
.filter-group select,.filter-group input { padding:11px 12px; border:1px solid #dfe6ed; border-radius:8px; background:white; }
.reporte-seccion { margin-top:28px; }
.report-subcard { background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px; box-shadow:0 2px 10px rgba(0,0,0,.05); }
.report-subcard-header { display:flex; align-items:center; margin-bottom:14px; padding:12px 16px; background:linear-gradient(135deg,#2196F3,#1976D2); border-radius:10px; }
.report-subcard-header h3 { margin:0; color:white; font-size:1.05rem; display:flex; align-items:center; gap:9px; }
.table-chart-card { min-width:0; }
.table-wrapper { max-height:430px; overflow-y:auto; margin-bottom:15px; }
.table-wrapper table { width:100%; border-collapse:collapse; }
.table-wrapper th { background:#2196F3; color:white; padding:12px; text-align:left; }
.table-wrapper td,.table-wrapper tfoot th { padding:10px 12px; border-bottom:1px solid #edf2f7; }
.table-wrapper tr:hover td { background:#f8fafc; }
.no-data { text-align:center; color:#718096; padding:30px; }
.btn-primary-action,.btn-export { border:none; padding:10px 16px; border-radius:8px; cursor:pointer; color:white; display:flex; align-items:center; gap:8px; }
.btn-primary-action { background:linear-gradient(135deg,#2196F3,#1976D2); }
.btn-export { background:linear-gradient(135deg,#f56565,#e53e3e); }
.section-actions { display:flex; flex-wrap:wrap; gap:10px; }
@media(max-width:768px){ .filters-grid{grid-template-columns:1fr}.section-actions{width:100%}.section-actions button{width:100%;justify-content:center} }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    'use strict';

    let recepcionProveedores = <?php echo $proveedoresJson; ?>;
    let recepcionProductos = <?php echo $productosJson; ?>;
    let recepcionMensual = <?php echo $mensualJson; ?>;
    const graficos = {};

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function colores(cantidad) {
        return Array.from({length: Math.max(cantidad, 1)}, function (_, i) {
            return 'hsl(' + Math.round((360 / Math.max(cantidad, 1)) * i) + ',70%,60%)';
        });
    }

    function normalizar(datos, etiqueta) {
        const mapa = new Map();
        (datos || []).forEach(function (fila) {
            const key = String(fila.label ?? fila[etiqueta] ?? '').trim() || 'Sin dato';
            const value = Number(fila.value ?? fila.cantidad ?? 0) || 0;
            mapa.set(key, (mapa.get(key) || 0) + value);
        });
        return Array.from(mapa.entries())
            .map(function (entry) { return {label: entry[0], value: entry[1]}; })
            .sort(function (a, b) { return b.value - a.value; });
    }

    function renderReporte(datos, canvasId, tablaId, titulo, tipoGrafica) {
        if (graficos[canvasId]) {
            graficos[canvasId].destroy();
        }

        const tabla = document.getElementById(tablaId);
        const canvas = document.getElementById(canvasId);
        const chartContainer = canvas.closest('.chart-container');
        if (chartContainer) {
            chartContainer.style.height = canvasId === 'graficoProductos' && datos && datos.length
                ? Math.max(280, Math.min(560, datos.length * 28)) + 'px'
                : '';
        }

        if (!datos || !datos.length) {
            tabla.innerHTML = '<div class="no-data">No hay datos disponibles para el período seleccionado</div>';
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        const total = datos.reduce(function (sum, fila) { return sum + fila.value; }, 0);
        let html = '<table><thead><tr><th>Descripción</th><th>Cantidad</th><th>Porcentaje</th></tr></thead><tbody>';
        datos.forEach(function (fila) {
            const porcentaje = total ? (fila.value / total * 100).toFixed(2) : '0.00';
            html += '<tr><td>' + esc(fila.label) + '</td><td>' + fila.value.toLocaleString() +
                '</td><td>' + porcentaje + '%</td></tr>';
        });
        html += '</tbody><tfoot><tr><th>Total</th><th>' + total.toLocaleString() +
            '</th><th>100%</th></tr></tfoot></table>';
        tabla.innerHTML = html;

        graficos[canvasId] = new Chart(canvas.getContext('2d'), {
            type: tipoGrafica,
            data: {
                labels: datos.map(function (fila) { return fila.label; }),
                datasets: [{
                    label: titulo,
                    data: datos.map(function (fila) { return fila.value; }),
                    backgroundColor: colores(datos.length),
                    borderWidth: tipoGrafica === 'line' ? 3 : 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: tipoGrafica !== 'line', position: 'bottom' },
                    title: { display: false }
                },
                scales: tipoGrafica === 'line' || tipoGrafica === 'bar'
                    ? { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    : {}
            }
        });
    }

    function distinct(datos, campo) {
        const mapa = new Map();
        (datos || []).forEach(function (fila) {
            const valor = String(fila[campo] ?? '').trim();
            if (valor) mapa.set(valor.toLowerCase(), valor);
        });
        return Array.from(mapa.values()).sort();
    }

    function buildFiltros() {
        const tipo = document.getElementById('tipoReporteRecepcion').value;
        const contenedor = document.getElementById('parametrosRecepcion');
        let html = '';

        if (tipo === 'proveedores') {
            html += '<div class="filter-group"><label for="paramProveedor">Proveedor</label><select id="paramProveedor"><option value="">Todos</option>' +
                distinct(recepcionProveedores, 'label').map(function (valor) {
                    return '<option value="' + esc(valor) + '">' + esc(valor) + '</option>';
                }).join('') + '</select></div>';
        } else if (tipo === 'productos') {
            html += '<div class="filter-group"><label for="paramProducto">Producto</label><select id="paramProducto"><option value="">Todos</option>' +
                distinct(recepcionProductos, 'label').map(function (valor) {
                    return '<option value="' + esc(valor) + '">' + esc(valor) + '</option>';
                }).join('') + '</select></div>' +
                '<div class="filter-group"><label for="paramProveedorProducto">Proveedor</label><select id="paramProveedorProducto"><option value="">Todos</option>' +
                distinct(recepcionProductos, 'proveedor').map(function (valor) {
                    return '<option value="' + esc(valor) + '">' + esc(valor) + '</option>';
                }).join('') + '</select></div>' +
                '<div class="filter-group"><label for="paramTopN">Top</label><select id="paramTopN"><option value="0">Todos</option><option value="10">Top 10</option><option value="20">Top 20</option><option value="50">Top 50</option></select></div>';
        } else if (tipo === 'mensual') {
            html += '<div class="filter-group"><label for="paramMes">Mes</label><select id="paramMes"><option value="">Todos</option>';
            ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'].forEach(function (mes, indice) {
                html += '<option value="' + (indice + 1) + '">' + mes + '</option>';
            });
            html += '</select></div><div class="filter-group"><label for="paramAnio">Año</label><input type="number" id="paramAnio" placeholder="Ej: 2026"></div>';
        }
        contenedor.innerHTML = html;
    }

    function toggleSecciones() {
        const tipo = document.getElementById('tipoReporteRecepcion').value;
        document.getElementById('reporteProveedores').style.display =
            tipo === 'todos' || tipo === 'proveedores' ? 'block' : 'none';
        document.getElementById('reporteProductos').style.display =
            tipo === 'todos' || tipo === 'productos' ? 'block' : 'none';
        document.getElementById('reporteMensual').style.display =
            tipo === 'todos' || tipo === 'mensual' ? 'block' : 'none';
        buildFiltros();
    }

    function generarLocales(datos) {
        const tipo = document.getElementById('tipoReporteRecepcion').value;
        const tipoGrafica = document.getElementById('tipoGraficaRecepcion').value;

        let proveedores = normalizar(recepcionProveedores, 'label');
        let productos = normalizar(recepcionProductos, 'label');
        let mensual = normalizar(recepcionMensual, 'label');

        if (tipo === 'proveedores') {
            const proveedor = document.getElementById('paramProveedor')?.value || '';
            if (proveedor) proveedores = proveedores.filter(function (fila) {
                return fila.label.toLowerCase() === proveedor.toLowerCase();
            });
        }
        if (tipo === 'productos') {
            const producto = document.getElementById('paramProducto')?.value || '';
            const proveedor = document.getElementById('paramProveedorProducto')?.value || '';
            const topN = parseInt(document.getElementById('paramTopN')?.value || '0', 10) || 0;
            if (producto) productos = normalizar(recepcionProductos.filter(function (fila) {
                return String(fila.label || '').toLowerCase() === producto.toLowerCase();
            }), 'label');
            if (proveedor) productos = normalizar(recepcionProductos.filter(function (fila) {
                return String(fila.proveedor || '').toLowerCase() === proveedor.toLowerCase();
            }), 'label');
            if (topN) productos = productos.slice(0, topN);
        }
        if (tipo === 'mensual') {
            const mes = parseInt(document.getElementById('paramMes')?.value || '0', 10);
            const anio = document.getElementById('paramAnio')?.value || '';
            if (mes) mensual = mensual.filter(function (fila) {
                const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                return fila.label === meses[mes - 1];
            });
            if (anio) {
                mensual = mensual.filter(function (fila) {
                    return !fila.anio || String(fila.anio) === String(anio);
                });
            }
        }

        renderReporte(proveedores, 'graficoProveedores', 'tablaProveedores', 'Recepciones por Proveedor', tipoGrafica);
        renderReporte(productos, 'graficoProductos', 'tablaProductos', 'Productos más Recibidos', tipoGrafica);
        renderReporte(mensual, 'graficoMensual', 'tablaMensual', 'Recepciones Mensuales', tipoGrafica);
    }

    async function generarDesdeBackend() {
        const params = new URLSearchParams();
        params.append('accion', 'reportes_inventario');
        params.append('tipoReporte', document.getElementById('tipoReporteRecepcion').value);
        params.append('fechaInicio', document.getElementById('fechaInicioRecepcion').value);
        params.append('fechaFin', document.getElementById('fechaFinRecepcion').value);
        params.append('proveedor', document.getElementById('paramProveedor')?.value || '');
        params.append('producto', document.getElementById('paramProducto')?.value || '');
        params.append('anio', document.getElementById('paramAnio')?.value || '');

        document.getElementById('mensajeErrorRecepcion').style.display = 'none';

        try {
            const respuesta = await fetch('?pagina=reporteInventario', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString()
            });
            const contenido = await respuesta.json();
            if (!respuesta.ok || contenido.status !== 'success') throw new Error(contenido.message || 'Error');

            recepcionProveedores = contenido.data.proveedores || [];
            recepcionProductos = contenido.data.productos || [];
            recepcionMensual = contenido.data.mensual || [];
            generarLocales();
        } catch (error) {
            document.getElementById('mensajeErrorRecepcion').style.display = 'block';
            generarLocales();
        }
    }

    function descargarImagenRecepcion(canvasId, nombreArchivo) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const link = document.createElement('a');
        link.download = nombreArchivo;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    async function capturarGrafico(canvasId) {
        const canvas = document.getElementById(canvasId);
        const seccion = canvas.closest('.reporte-seccion');
        const displayAnterior = seccion.style.display;

        if (getComputedStyle(seccion).display === 'none') {
            seccion.style.display = 'block';
        }

        try {
            const grafico = graficos[canvasId];
            if (!grafico) throw new Error('El gráfico aún no se ha generado');

            grafico.resize();
            grafico.update('none');
            await new Promise(function (resolve) {
                requestAnimationFrame(function () {
                    requestAnimationFrame(resolve);
                });
            });

            return {
                data: grafico.toBase64Image('image/png'),
                width: canvas.width,
                height: canvas.height
            };
        } finally {
            seccion.style.display = displayAnterior;
        }
    }

    async function descargarTodosLosReportes() {
        if (typeof window.jspdf === 'undefined') {
            Swal.fire({icon:'error', title:'Error', text:'La librería PDF no está cargada'});
            return;
        }

        const secciones = ['reporteProveedores', 'reporteProductos', 'reporteMensual'];
        const disponibles = secciones.filter(function (id) {
            const contenedor = document.getElementById(id);
            return contenedor && contenedor.querySelector('canvas') &&
                contenedor.querySelector('table');
        });

        if (!disponibles.length) {
            Swal.fire({icon:'warning', title:'Sin datos', text:'Genere los reportes antes de descargarlos'});
            return;
        }

        Swal.fire({title:'Generando reportes...', allowOutsideClick:false, didOpen:function () { Swal.showLoading(); }});

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
        const margenX = 12;
        const margenSuperior = 12;
        const margenInferior = 12;
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const imageWidth = pageWidth - margenX * 2;
        const rowHeight = 8;
        const widths = [imageWidth * 0.52, imageWidth * 0.23, imageWidth * 0.25];

        for (let seccionIndex = 0; seccionIndex < disponibles.length; seccionIndex++) {
            const id = disponibles[seccionIndex];
            if (seccionIndex > 0) pdf.addPage();

            const contenedor = document.getElementById(id);
            const canvas = contenedor.querySelector('canvas');
            const tabla = contenedor.querySelector('table');
            let y = margenSuperior;

            pdf.setFontSize(16);
            pdf.setTextColor(45,55,72);
            pdf.text(contenedor.querySelector('.report-subcard-header h3').textContent.trim(), margenX, y);
            y += 8;

            const graficoImagen = await capturarGrafico(canvas.id);
            const chartHeight = Math.min(graficoImagen.height * imageWidth / graficoImagen.width, 95);
            pdf.addImage(graficoImagen.data, 'PNG', margenX, y, imageWidth, chartHeight);
            y += chartHeight + 8;

            const wrapper = tabla.closest('.table-wrapper');
            const maxHeightAnterior = wrapper ? wrapper.style.maxHeight : '';
            const overflowAnterior = wrapper ? wrapper.style.overflowY : '';
            if (wrapper) {
                wrapper.style.maxHeight = 'none';
                wrapper.style.overflowY = 'visible';
            }

            const tablaCanvas = await html2canvas(tabla, {scale:2, useCORS:true, backgroundColor:'#ffffff'});
            if (wrapper) {
                wrapper.style.maxHeight = maxHeightAnterior;
                wrapper.style.overflowY = overflowAnterior;
            }

            const tablaWidth = pageWidth - margenX * 2;
            const tablaHeight = tablaCanvas.height * tablaWidth / tablaCanvas.width;
            if (y + tablaHeight <= pageHeight - margenInferior) {
                pdf.addImage(tablaCanvas.toDataURL('image/png'), 'PNG', margenX, y, tablaWidth, tablaHeight);
            } else {
                const ratio = tablaCanvas.width / tablaWidth;
                const pageContentHeight = (pageHeight - margenSuperior - margenInferior) * ratio;
                let sourceY = 0;
                let firstTableSlice = true;
                while (sourceY < tablaCanvas.height) {
                    if (!firstTableSlice) pdf.addPage();
                    const remaining = tablaCanvas.height - sourceY;
                    const sliceHeight = Math.min(pageContentHeight, remaining);
                    const slice = document.createElement('canvas');
                    slice.width = tablaCanvas.width;
                    slice.height = Math.ceil(sliceHeight);
                    slice.getContext('2d').drawImage(
                        tablaCanvas,
                        0, sourceY, tablaCanvas.width, slice.height,
                        0, 0, tablaCanvas.width, slice.height
                    );
                    pdf.addImage(
                        slice.toDataURL('image/png'),
                        'PNG',
                        margenX,
                        margenSuperior,
                        tablaWidth,
                        slice.height / ratio
                    );
                    sourceY += sliceHeight;
                    firstTableSlice = false;
                }
            }
        }

        pdf.save('Reporte_Recepciones_Completo.pdf');
        Swal.close();
    }

    async function descargarPDFRecepcion(contenedorId, nombreArchivo) {
        if (typeof window.jspdf === 'undefined') {
            Swal.fire({icon:'error', title:'Error', text:'La librería PDF no está cargada'});
            return;
        }

        const contenedor = document.getElementById(contenedorId);
        const canvas = contenedor.querySelector('canvas');
        const tabla = contenedor.querySelector('table');
        if (!canvas || !tabla) {
            Swal.fire({icon:'warning', title:'Sin datos', text:'Genere el reporte antes de descargar'});
            return;
        }

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
        const margenX = 12;
        const margenSuperior = 12;
        const margenInferior = 12;
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        let y = margenSuperior;

        pdf.setFontSize(16);
        pdf.setTextColor(45,55,72);
        pdf.text(contenedor.querySelector('.chart-header h3').textContent, margenX, y);
        y += 8;

        const imageWidth = pageWidth - margenX * 2;
        const graficoImagen = await capturarGrafico(canvas.id);
        const imageHeight = graficoImagen.height * imageWidth / graficoImagen.width;
        if (y + imageHeight > pageHeight - margenInferior) {
            pdf.addPage();
            y = margenSuperior;
        }
        pdf.addImage(graficoImagen.data, 'PNG', margenX, y, imageWidth, Math.min(imageHeight, 95));
        y += Math.min(imageHeight, 95) + 8;

        if (typeof html2canvas === 'undefined') {
            Swal.fire({icon:'error', title:'Error', text:'La librería de tabla no está cargada'});
            return;
        }

        const wrapper = tabla.closest('.table-wrapper');
        const maxHeightAnterior = wrapper ? wrapper.style.maxHeight : '';
        const overflowAnterior = wrapper ? wrapper.style.overflowY : '';
        if (wrapper) {
            wrapper.style.maxHeight = 'none';
            wrapper.style.overflowY = 'visible';
        }

        Swal.fire({title:'Generando tabla...', allowOutsideClick:false, didOpen:function () { Swal.showLoading(); }});
        html2canvas(tabla, {scale:2, useCORS:true, backgroundColor:'#ffffff'}).then(function (tablaCanvas) {
            if (wrapper) {
                wrapper.style.maxHeight = maxHeightAnterior;
                wrapper.style.overflowY = overflowAnterior;
            }

            const tablaWidth = pageWidth - margenX * 2;
            const tablaHeight = tablaCanvas.height * tablaWidth / tablaCanvas.width;
            if (y + tablaHeight <= pageHeight - margenInferior) {
                pdf.addImage(tablaCanvas.toDataURL('image/png'), 'PNG', margenX, y, tablaWidth, tablaHeight);
            } else {
                const ratio = tablaCanvas.width / tablaWidth;
                const pageContentHeight = (pageHeight - margenSuperior - margenInferior) * ratio;
                let sourceY = 0;
                let first = true;
                while (sourceY < tablaCanvas.height) {
                    if (!first) pdf.addPage();
                    const remaining = tablaCanvas.height - sourceY;
                    const sliceHeight = Math.min(pageContentHeight, remaining);
                    const slice = document.createElement('canvas');
                    slice.width = tablaCanvas.width;
                    slice.height = Math.ceil(sliceHeight);
                    slice.getContext('2d').drawImage(
                        tablaCanvas,
                        0, sourceY, tablaCanvas.width, slice.height,
                        0, 0, tablaCanvas.width, slice.height
                    );
                    const sliceImg = slice.toDataURL('image/png');
                    const slicePdfHeight = slice.height / ratio;
                    pdf.addImage(sliceImg, 'PNG', margenX, margenSuperior, tablaWidth, slicePdfHeight);
                    sourceY += sliceHeight;
                    first = false;
                }
            }

            pdf.save(nombreArchivo);
            Swal.close();
        }).catch(function (error) {
            if (wrapper) {
                wrapper.style.maxHeight = maxHeightAnterior;
                wrapper.style.overflowY = overflowAnterior;
            }
            Swal.close();
            Swal.fire({icon:'error', title:'Error', text:'No se pudo generar la tabla del PDF'});
            console.error(error);
        });
    }

    window.descargarImagenRecepcion = descargarImagenRecepcion;
    window.descargarPDFRecepcion = descargarPDFRecepcion;
    window.descargarTodosLosReportes = descargarTodosLosReportes;

    document.getElementById('tipoReporteRecepcion').addEventListener('change', toggleSecciones);
    document.getElementById('generarReporteRecepcion').addEventListener('click', generarDesdeBackend);
    document.getElementById('descargarTodosReportes').addEventListener('click', descargarTodosLosReportes);
    toggleSecciones();
    generarLocales();
})();
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
?>
