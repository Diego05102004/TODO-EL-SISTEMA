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
        error_log('Error al generar JWT en reporteCliente: ' . $e->getMessage());
    }
}

$idRol = $_SESSION['id_rol'] ?? null;
$idModulo = 9;
if (!isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) ||
    $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] !== true) {
    header('Location: ?pagina=acceso-denegado');
    exit;
}

$pagina_actual = 'reporteCliente';
$titulo_pagina = 'Reporte de Clientes';
$datosComprasClientes = is_array($reporteComprasClientes ?? null) ? $reporteComprasClientes : [];
$totalComprasClientes = array_sum(array_map(static function ($fila) {
    return (int) ($fila['cantidad'] ?? 0);
}, $datosComprasClientes));
$clienteLider = $datosComprasClientes[0] ?? null;
$jsonOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$comprasClientesJson = json_encode($datosComprasClientes, $jsonOptions) ?: '[]';

ob_start();
?>

<div class="summary-cards">
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-users"></i></div>
        <div class="card-content">
            <h3>Clientes en el Top 10</h3>
            <p class="card-value"><?php echo number_format(count($datosComprasClientes)); ?></p>
        </div>
    </div>
    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-cart-shopping"></i></div>
        <div class="card-content">
            <h3>Productos comprados</h3>
            <p class="card-value"><?php echo number_format($totalComprasClientes); ?></p>
        </div>
    </div>
    <div class="summary-card balance">
        <div class="card-icon"><i class="fas fa-trophy"></i></div>
        <div class="card-content">
            <h3>Cliente con más compras</h3>
            <p class="card-value"><?php echo htmlspecialchars($clienteLider['nombre'] ?? 'Sin datos', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
</div>

<section class="report-card rc-report" id="resultadoReporteClientes">
    <div class="section-header">
        <h2><i class="fas fa-chart-bar"></i> Top 10 Clientes por Productos Comprados</h2>
        <div class="section-actions">
            <button type="button" class="btn-export" id="descargarPDFClientes">
                <i class="fas fa-file-pdf"></i> Descargar PDF
            </button>
        </div>
    </div>

    <div id="contenidoPDFCliente">
        <h3 class="report-title">Top 10 Clientes por Productos Comprados</h3>
        <div class="charts-grid rc-charts-grid">
            <div class="chart-card">
                <div class="chart-header"><h3>Productos comprados por cliente</h3></div>
                <div class="chart-container rc-chart-container">
                    <canvas id="graficoComprasClientes"></canvas>
                </div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaComprasClientes" class="table-wrapper"></div>
            </div>
        </div>
    </div>

    <p class="rc-status" id="estadoReporteClientes" role="status" aria-live="polite"></p>
</section>

<style>
.rc-report { background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
.rc-report .section-actions { display: flex; justify-content: flex-end; gap: 10px; }
.rc-report .btn-export { border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; color: #fff; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#f56565,#e53e3e); }
.rc-report .btn-export:hover { filter: brightness(.95); }
.rc-report .report-title { margin: 14px 0 20px; color: #2d3748; }
.rc-charts-grid { grid-template-columns: minmax(0,1.1fr) minmax(0,.9fr); }
.rc-report .chart-card { min-width: 0; }
.rc-chart-container { position: relative; height: 390px; }
.rc-report .table-wrapper { max-height: 430px; overflow: auto; }
.rc-report .table-wrapper table { width: 100%; border-collapse: collapse; }
.rc-report .table-wrapper th { background: #2196F3; color: #fff; padding: 12px; text-align: left; }
.rc-report .table-wrapper td,.rc-report .table-wrapper tfoot th { padding: 10px 12px; border-bottom: 1px solid #edf2f7; }
.rc-report .table-wrapper tr:hover td { background: #f8fafc; }
.rc-status { min-height: 1.5em; margin: 14px 0 0; color: #4a5568; }
@media(max-width:768px) {
    .rc-report { padding: 16px; }
    .rc-charts-grid { grid-template-columns: 1fr; }
    .rc-chart-container { height: 320px; }
    .rc-report .section-actions { width: 100%; }
    .rc-report .section-actions button { width: 100%; justify-content: center; }
}
</style>

<script src="assets/public/js/chart.js"></script>
<script src="assets/public/js/html2canvas.min.js"></script>
<script src="assets/public/js/jspdf.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const datosCompras = <?php echo $comprasClientesJson; ?>;
    const tabla = document.getElementById('tablaComprasClientes');
    const canvas = document.getElementById('graficoComprasClientes');
    const estado = document.getElementById('estadoReporteClientes');
    let grafico = null;

    function numero(valor) {
        const resultado = Number(valor);
        return Number.isFinite(resultado) ? resultado : 0;
    }

    function crearCelda(fila, etiqueta, valor) {
        const celda = document.createElement(etiqueta);
        celda.textContent = valor == null ? '' : String(valor);
        fila.appendChild(celda);
    }

    function renderTabla() {
        const total = datosCompras.reduce(function (suma, cliente) {
            return suma + numero(cliente.cantidad);
        }, 0);
        const elementoTabla = document.createElement('table');
        const encabezado = elementoTabla.createTHead().insertRow();
        ['Cliente', 'Cantidad de Productos Comprados', 'Porcentaje (%)'].forEach(function (texto) {
            crearCelda(encabezado, 'th', texto);
        });

        const cuerpo = elementoTabla.createTBody();
        if (!datosCompras.length) {
            const filaVacia = cuerpo.insertRow();
            const celdaVacia = filaVacia.insertCell();
            celdaVacia.colSpan = 3;
            celdaVacia.textContent = 'No hay compras de clientes activos para mostrar.';
        } else {
            datosCompras.forEach(function (cliente) {
                const cantidad = numero(cliente.cantidad);
                const porcentaje = total ? (cantidad / total * 100).toFixed(2) + '%' : '0.00%';
                const fila = cuerpo.insertRow();
                crearCelda(fila, 'td', cliente.nombre || 'Sin nombre');
                crearCelda(fila, 'td', cantidad.toLocaleString('es-ES'));
                crearCelda(fila, 'td', porcentaje);
            });
        }

        const pie = elementoTabla.createTFoot().insertRow();
        crearCelda(pie, 'th', 'Total');
        crearCelda(pie, 'th', total.toLocaleString('es-ES'));
        crearCelda(pie, 'th', datosCompras.length ? '100%' : '0%');
        tabla.replaceChildren(elementoTabla);
    }

    function renderGrafico() {
        if (grafico) {
            grafico.destroy();
            grafico = null;
        }

        if (!datosCompras.length) {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        if (typeof Chart === 'undefined') {
            estado.textContent = 'No se pudo cargar la librería para mostrar la gráfica.';
            return;
        }

        grafico = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: datosCompras.map(function (cliente) { return cliente.nombre || 'Sin nombre'; }),
                datasets: [{
                    label: 'Productos comprados',
                    data: datosCompras.map(function (cliente) { return numero(cliente.cantidad); }),
                    backgroundColor: 'rgba(39, 174, 96, 0.7)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    title: { display: false }
                },
                scales: { x: { beginAtZero: true } }
            }
        });
    }

    async function descargarPDF() {
        if (typeof window.html2canvas !== 'function' || !window.jspdf || !window.jspdf.jsPDF) {
            estado.textContent = 'No se pudieron cargar las librerías para generar el PDF.';
            return;
        }

        const elemento = document.getElementById('contenidoPDFCliente');
        estado.textContent = 'Generando PDF...';
        try {
            const imagen = await window.html2canvas(elemento, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
            const pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const margen = 10;
            const anchoPagina = pdf.internal.pageSize.getWidth();
            const altoPagina = pdf.internal.pageSize.getHeight();
            const factor = Math.min(
                (anchoPagina - margen * 2) / imagen.width,
                (altoPagina - margen * 2) / imagen.height
            );
            const anchoImagen = imagen.width * factor;
            const altoImagen = imagen.height * factor;
            pdf.addImage(
                imagen.toDataURL('image/png'),
                'PNG',
                (anchoPagina - anchoImagen) / 2,
                margen,
                anchoImagen,
                altoImagen
            );
            pdf.save('Reporte_Compras_Clientes.pdf');
            estado.textContent = 'PDF descargado.';
        } catch (error) {
            estado.textContent = 'No se pudo generar el PDF del reporte de clientes.';
            console.error('Error al generar el PDF de clientes:', error);
        }
    }

    renderTabla();
    renderGrafico();
    document.getElementById('descargarPDFClientes').addEventListener('click', descargarPDF);
})();
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
