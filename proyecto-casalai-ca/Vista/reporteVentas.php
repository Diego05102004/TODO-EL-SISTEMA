<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if ($_SESSION) {?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Reportes de Despachos</title>
    <style>
        :root {
            --primary-color: #1f66df;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .report-container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            padding: 32px 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .report-header {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light-bg);
            padding-bottom: 15px;
        }
        
        .parameters-container {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
        }
        
        .report-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .report-section:hover {
            box-shadow: var(--box-shadow);
        }
        
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .chart-canvas {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }
        
        .chart-table {
            flex: 2;
            min-width: 400px;
        }
        
        /* Estilo para PDF - gráfica arriba, tabla abajo */
        .pdf-layout {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .pdf-chart {
            width: 100%;
            text-align: center;
        }
        
        .pdf-table {
            width: 100%;
        }
        
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 10px 5px;
            padding: 10px 20px;
        }
        
        .download-buttons {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .error-message {
            color: #dc3545;
            text-align: center;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .report-selector {
            margin-bottom: 20px;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            margin: 30px 0;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .form-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: center;
        }
        
        .form-inline .form-control,
        .form-inline .form-select {
            width: auto;
            min-width: 160px;
        }
    </style>
</head>

<body class="fondo" style="height: auto; min-height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<!-- SOLO REPORTE DE DESPACHOS -->
<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Despachos</h2>
        <p class="texto-p">Visualice y analice los datos de despachos</p>
    </div>

    <!-- Parámetros del Reporte -->
    <div class="parameters-container">
        <h5 class="titulo-form">Parámetros del Reporte</h5>
        <form id="formParametros" class="form-inline">
            <div class="form-group">
                <label for="fechaInicio" class="title-select">Fecha inicio:</label>
                <input type="date" id="fechaInicio" class="selector-reporte" name="fechaInicio">
            </div>
            <div class="form-group">
                <label for="fechaFin" class="title-select">Fecha fin:</label>
                <input type="date" id="fechaFin" class="selector-reporte" name="fechaFin">
            </div>
            <div class="form-group">
                <label for="tipoGrafica" class="title-select">Tipo de gráfica:</label>
                <select id="tipoGrafica" class="selector-reporte" name="tipoGrafica">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Donas</option>
                    <option value="polarArea">Área Polar</option>
                </select>
            </div>
            <div class="form-group">
                <label for="selectReporte" class="title-select">Tipo de Reporte:</label>
                <select id="selectReporte" class="selector-reporte" name="selectReporte">
                    <option value="todos">Todos los Reportes</option>
                    <option value="reporteEstado">Despachos por Estado</option>
                    <option value="reporteMensual">Despachos Mensuales</option>
                    <option value="reporteCliente">Despachos por Cliente</option>
                    <option value="reporteTipoCompra">Despachos por Tipo de Compra</option>
                </select>
            </div>
            <div id="parametrosIndividuales"></div>
            <div class="form-group">
                <button id="generarReporteBtn" class="btn btn-primary" type="button">Generar Reporte</button>
            </div>
        </form>
    </div>

    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p>Generando reportes...</p>
    </div>

    <div id="errorMessage" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

    <!-- Reporte 1: Despachos por Estado -->
    <div class="report-section" id="reporteEstado">
        <h3 class="titulo-form">Despachos por Estado</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoEstado" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaEstado"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFDespachos('reporteEstado', 'Reporte_Despachos_Estado.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenDespachos('graficoEstado', 'Grafico_Despachos_Estado.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 2: Despachos Mensuales -->
    <div class="report-section" id="reporteMensual">
        <h3 class="titulo-form">Despachos Mensuales</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoMensualDespachos" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaMensualDespachos"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFDespachos('reporteMensual', 'Reporte_Despachos_Mensual.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenDespachos('graficoMensualDespachos', 'Grafico_Despachos_Mensual.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 3: Despachos por Cliente -->
    <div class="report-section" id="reporteCliente">
        <h3 class="titulo-form">Despachos por Cliente</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoCliente" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaCliente"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFDespachos('reporteCliente', 'Reporte_Despachos_Cliente.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenDespachos('graficoCliente', 'Grafico_Despachos_Cliente.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 4: Despachos por Tipo de Compra -->
    <div class="report-section" id="reporteTipoCompra">
        <h3 class="titulo-form">Despachos por Tipo de Compra</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoTipoCompra" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaTipoCompra"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFDespachos('reporteTipoCompra', 'Reporte_Despachos_Tipo_Compra.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenDespachos('graficoTipoCompra', 'Grafico_Despachos_Tipo_Compra.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    // ============================
    // SECCIÓN DE DESPACHOS
    // ============================
    let despachoMes = <?= json_encode($despachoMes ?? []) ?>;
    let despachoEstado = <?= json_encode($despachoEstado ?? []) ?>;
    let despachoCliente = <?= json_encode($despachoCliente ?? []) ?>;
    let despachoTipoCompra = <?= json_encode($despachoTipoCompra ?? []) ?>;
    let graficoEstado = null;
    let graficoMensualDespachos = null;
    let graficoCliente = null;
    let graficoTipoCompra = null;

    function generarColores(n) {
        return Array.from({length: n}, (_, i) => {
            const hue = (360 / n) * i;
            return `hsl(${hue}, 70%, 60%)`;
        });
    }

function renderReporteDespachos(datos, labelKey, valueKey, canvasId, tablaId, titulo, tipoGrafica) {
    if (!datos || datos.length === 0) {
        document.getElementById(tablaId).innerHTML = `
            <div class="alert alert-warning text-center">
                📭 No hay datos disponibles para el período seleccionado
            </div>`;
        return;
    }

    // **VERSIÓN ROBUSTA: Maneja múltiples nombres de campos**
    const labels = datos.map(d => {
        return d[labelKey] || d.label || d.estado || d.mes || d.cliente || 'Sin nombre';
    });
    
    const data = datos.map(d => {
        const valor = d[valueKey] || d.value || d.total || d.cantidad || 0;
        return parseInt(valor) || 0;
    });

    const total = data.reduce((a, b) => a + b, 0);
    const colores = generarColores(labels.length);

        let headerText = 'Estado';
        if (labelKey === 'mes') headerText = 'Mes';
        else if (labelKey === 'cliente') headerText = 'Cliente';
        else if (labelKey === 'tipo_compra') headerText = 'Tipo de Compra';
        
        let tablaHtml = `
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>${headerText}</th>
                            <th>Cantidad</th>
                            <th>Porcentaje (%)</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        labels.forEach((nombre, i) => {
            const pct = total > 0 ? ((data[i] / total) * 100).toFixed(2) : 0;
            tablaHtml += `
                <tr>
                    <td>${nombre}</td>
                    <td>${data[i].toLocaleString()}</td>
                    <td>${pct}%</td>
                </tr>
            `;
        });
        tablaHtml += `
                    </tbody>
                    <tfoot class="table-active">
                        <tr>
                            <th>Total</th>
                            <th>${total.toLocaleString()}</th>
                            <th>100%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        document.getElementById(tablaId).innerHTML = tablaHtml;

        const ctx = document.getElementById(canvasId).getContext('2d');
        if (canvasId === 'graficoEstado' && graficoEstado) {
            graficoEstado.destroy();
        } else if (canvasId === 'graficoMensualDespachos' && graficoMensualDespachos) {
            graficoMensualDespachos.destroy();
        } else if (canvasId === 'graficoCliente' && graficoCliente) {
            graficoCliente.destroy();
        } else if (canvasId === 'graficoTipoCompra' && graficoTipoCompra) {
            graficoTipoCompra.destroy();
        }
        const newChart = new Chart(ctx, {
            type: tipoGrafica,
            data: {
                labels: labels,
                datasets: [{
                    label: titulo,
                    data: data,
                    backgroundColor: colores,
                    borderColor: tipoGrafica === 'line' ? colores.map(c => c.replace('hsl', 'hsla').replace(')', ', 1)')) : colores,
                    borderWidth: tipoGrafica === 'line' ? 3 : 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: tipoGrafica !== 'line',
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: titulo,
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        if (canvasId === 'graficoEstado') {
            graficoEstado = newChart;
        } else if (canvasId === 'graficoMensualDespachos') {
            graficoMensualDespachos = newChart;
        } else if (canvasId === 'graficoCliente') {
            graficoCliente = newChart;
        } else if (canvasId === 'graficoTipoCompra') {
            graficoTipoCompra = newChart;
        }
    }

    function toggleReportesDespachos() {
        const seleccion = document.getElementById('selectReporte').value;
        const reportes = ['reporteEstado', 'reporteMensual', 'reporteCliente', 'reporteTipoCompra'];
        if (seleccion === 'todos') {
            reportes.forEach(reporte => {
                document.getElementById(reporte).style.display = 'block';
            });
        } else {
            reportes.forEach(reporte => {
                if (reporte === seleccion) {
                    document.getElementById(reporte).style.display = 'block';
                } else {
                    document.getElementById(reporte).style.display = 'none';
                }
            });
        }
    }

    function generarReportesDespachos() {
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        const seleccion = document.getElementById('selectReporte').value;
        document.getElementById('loadingSpinner').style.display = 'block';
        setTimeout(() => {
            try {
                toggleReportesDespachos();
                if (seleccion === 'todos' || seleccion === 'reporteEstado') {
                    renderReporteDespachos(despachoEstado, 'estado', 'total', 'graficoEstado', 'tablaEstado', 'Despachos por Estado', tipoGrafica);
                }
                if (seleccion === 'todos' || seleccion === 'reporteMensual') {
                    renderReporteDespachos(despachoMes, 'mes', 'total', 'graficoMensualDespachos', 'tablaMensualDespachos', 'Despachos Mensuales', tipoGrafica);
                }
                if (seleccion === 'todos' || seleccion === 'reporteCliente') {
                    renderReporteDespachos(despachoCliente, 'cliente', 'total', 'graficoCliente', 'tablaCliente', 'Despachos por Cliente', tipoGrafica);
                }
                if (seleccion === 'todos' || seleccion === 'reporteTipoCompra') {
                    renderReporteDespachos(despachoTipoCompra, 'tipo_compra', 'total', 'graficoTipoCompra', 'tablaTipoCompra', 'Despachos por Tipo de Compra', tipoGrafica);
                }
                document.getElementById('errorMessage').style.display = 'none';
            } catch (error) {
                console.error("Error al generar reportes de despachos:", error);
                document.getElementById('errorMessage').style.display = 'block';
            } finally {
                document.getElementById('loadingSpinner').style.display = 'none';
            }
        }, 500);
    }

    // Descargar PDF para DESPACHOS con gráfica arriba y tabla abajo
    function descargarPDFDespachos(contenedorId, nombreArchivo) {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const reporte = document.getElementById(contenedorId);
            const titulo = reporte.querySelector('h3').textContent;
            
            // Agregar título al PDF
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(titulo, 20, 20);
            
            // Obtener el canvas del gráfico
            let canvasId;
            if (contenedorId === 'reporteEstado') {
                canvasId = 'graficoEstado';
            } else if (contenedorId === 'reporteMensual') {
                canvasId = 'graficoMensualDespachos';
            } else if (contenedorId === 'reporteCliente') {
                canvasId = 'graficoCliente';
            } else if (contenedorId === 'reporteTipoCompra') {
                canvasId = 'graficoTipoCompra';
            }
            
            const canvas = document.getElementById(canvasId);
            if (canvas) {
                // Convertir canvas a imagen
                const imgData = canvas.toDataURL('image/png');
                
                // Dimensiones para la imagen en el PDF
                const pageWidth = doc.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 40; // 20mm de margen a cada lado
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                // Agregar imagen del gráfico al PDF
                doc.addImage(imgData, 'PNG', 20, 30, imgWidth, imgHeight);
                
                // Posición Y después del gráfico
                let currentY = 30 + imgHeight + 10;
                
                // Verificar si hay espacio suficiente para la tabla
                if (currentY + 100 > doc.internal.pageSize.getHeight()) {
                    doc.addPage();
                    currentY = 20;
                }
                
                // Agregar tabla como imagen
                const tablaElement = reporte.querySelector('.chart-table');
                if (tablaElement) {
                    html2canvas(tablaElement, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff'
                    }).then(tablaCanvas => {
                        const tablaImgData = tablaCanvas.toDataURL('image/png');
                        const tablaImgWidth = pageWidth - 40;
                        const tablaImgHeight = (tablaCanvas.height * tablaImgWidth) / tablaCanvas.width;
                        
                        // Verificar si necesita nueva página
                        if (currentY + tablaImgHeight > doc.internal.pageSize.getHeight()) {
                            doc.addPage();
                            currentY = 20;
                        }
                        
                        doc.addImage(tablaImgData, 'PNG', 20, currentY, tablaImgWidth, tablaImgHeight);
                        doc.save(nombreArchivo);
                    });
                } else {
                    doc.save(nombreArchivo);
                }
            } else {
                alert('No se pudo encontrar el gráfico para generar el PDF.');
            }
            
        } catch (error) {
            console.error("Error al generar PDF de despachos:", error);
            alert("❌ Error al generar el PDF. Asegúrese de que todas las librerías estén cargadas correctamente.");
        }
    }

    // Descargar imagen del gráfico para DESPACHOS
    function descargarImagenDespachos(canvasId, nombreArchivo) {
        try {
            const canvas = document.getElementById(canvasId);
            const link = document.createElement('a');
            link.download = nombreArchivo;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (error) {
            console.error("Error al descargar imagen de despachos:", error);
            alert("Error al descargar la imagen del gráfico.");
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hoy = new Date();
        const hace30Dias = new Date();
        hace30Dias.setDate(hoy.getDate() - 30);
        document.getElementById('fechaInicio').valueAsDate = hace30Dias;
        document.getElementById('fechaFin').valueAsDate = hoy;
        generarReportesDespachos();
        document.getElementById('generarReporteBtn').addEventListener('click', generarReportesDespachos);
        document.getElementById('selectReporte').addEventListener('change', function() {
            generarReportesDespachos();
        });
    });
</script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>

<script>
    // Esperar a que jQuery esté disponible
    $(document).ready(function() {
        console.log('jQuery cargado, inicializando modal de ayuda...');
        
        let modalAyudaInstance = null;

        // Función para cargar y mostrar el modal de ayuda con contexto específico
        function cargarYMostrarModalAyuda(contexto = null) {
            console.log('cargarYMostrarModalAyuda llamado con contexto:', contexto);
            
            // Cargar CSS si no está cargado
            if (!$('link[href*="ayuda/css/modal.css"]').length) {
                console.log('Cargando CSS del modal...');
                $('<link>')
                    .attr({
                        'rel': 'stylesheet',
                        'type': 'text/css',
                        'href': 'assets/public/ayuda/css/modal.css'
                    })
                    .appendTo('head');
            }

            // Cargar HTML del modal
            $.get('assets/public/ayuda/reporteDespacho.php')
                .done(function(html) {
                    console.log('HTML del modal cargado');
                    
                    // Solo agregar modal si no existe
                    if (!$('#modalAyuda').length) {
                        $('body').append(html);
                        console.log('Modal agregado al DOM');
                    }

                    // Cargar JS del modal si no está cargado
                    if (!$('script[src*="ayuda/js/modal.js"]').length) {
                        console.log('Cargando JavaScript del modal...');
                        $.getScript('assets/public/ayuda/js/modal.js')
                            .done(function() {
                                console.log('JavaScript del modal cargado');
                                inicializarModalConContexto(contexto);
                            })
                            .fail(function() {
                                console.error('Error al cargar el JavaScript del modal de ayuda');
                            });
                    } else {
                        console.log('JavaScript del modal ya estaba cargado');
                        inicializarModalConContexto(contexto);
                    }
                })
                .fail(function() {
                    console.error('Error al cargar el HTML del modal');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el contenido de ayuda'
                    });
                });
        }

        function inicializarModalConContexto(contexto) {
            // Inicializar modal
            if (typeof inicializarModalAyudaUsuario === 'function') {
                modalAyudaInstance = inicializarModalAyudaUsuario();
                console.log('Modal inicializado:', modalAyudaInstance);
                console.log('Mapeo de contextos disponible:', modalAyudaInstance.mapeoContextos);

                // Abrir modal con contexto si se proporciona
                if (contexto) {
                    setTimeout(() => {
                        const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                        console.log('Contexto:', contexto, '-> Slide:', slideIndex);
                        if (slideIndex !== undefined) {
                            modalAyudaInstance.goToSlide(slideIndex);
                        }
                    }, 300);
                }

                // Abrir modal
                modalAyudaInstance.openModal();
            } else {
                console.error('La función inicializarModalAyudaUsuario no está disponible');
            }
        }

        // Botón de ayuda principal
        $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
            e.preventDefault();
            console.log('Clic en botón de ayuda detectado');
            cargarYMostrarModalAyuda(); // Sin contexto específico
        });

        // Botón de ayuda dentro de modales
        $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
            e.preventDefault();
            const contexto = $(this).data('contexto');
            console.log('Clic en botón de ayuda modal con contexto:', contexto);
            cargarYMostrarModalAyuda(contexto);
        });
    });
</script>

<script src="assets/javascript/jwt_validator.js"></script>
<button 
    class="btn-ayuda"
    title="Visualizar Ayuda">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>