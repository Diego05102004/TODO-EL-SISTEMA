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
        error_log('Error al generar JWT en reporteProveedores: ' . $e->getMessage());
    }
}

if (empty($permisosUsuario['consultar'])) {
    header('Location: ?pagina=acceso-denegado');
    exit();
}

$pagina_actual = 'reporteProveedores';
$titulo_pagina = 'Reporte de Proveedores';

$jsonOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$reportesProveedorJson = json_encode($reportesProveedor ?? [], $jsonOptions) ?: '{}';
$datosSuministro = $reportesProveedor['suministro'] ?? [];
$datosRanking = $reportesProveedor['ranking'] ?? [];
$datosComparacion = $reportesProveedor['comparacion'] ?? [];
$totalUnidades = array_sum(array_map(static function ($fila) {
    return (float) ($fila['cantidad'] ?? 0);
}, $datosComparacion));
$montoTotal = array_sum(array_map(static function ($fila) {
    return (float) ($fila['total'] ?? 0);
}, $datosRanking));
$mesesReporte = count(array_unique(array_column($datosComparacion, 'mes')));

ob_start();
?>

<div class="summary-cards">
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-building"></i></div>
        <div class="card-content">
            <h3>Proveedores con suministro</h3>
            <p class="card-value" id="totalProveedoresReporte"><?php echo number_format(count($datosRanking)); ?></p>
        </div>
    </div>
    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-boxes"></i></div>
        <div class="card-content">
            <h3>Unidades recibidas</h3>
            <p class="card-value" id="totalUnidadesReporte"><?php echo number_format($totalUnidades); ?></p>
        </div>
    </div>
    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="card-content">
            <h3>Meses con suministros</h3>
            <p class="card-value" id="totalMesesReporte"><?php echo number_format($mesesReporte); ?></p>
        </div>
    </div>
    <div class="summary-card balance">
        <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="card-content">
            <h3>Gasto total de recepción</h3>
            <p class="card-value" id="montoTotalReporte">$<?php echo number_format($montoTotal, 2); ?></p>
        </div>
    </div>
</div>

<section class="report-card rp-report" id="resultadoReporteProveedores">
    <div class="section-header">
        <h2><i class="fas fa-chart-bar"></i> Reportes de Proveedores</h2>
        <div class="section-actions">
            <button type="button" class="btn-primary-action" id="generarReportesProveedores">
                <i class="fas fa-chart-line"></i> Generar Reporte
            </button>
            <button type="button" class="btn-export" id="descargarTodosReportesProveedores">
                <i class="fas fa-file-pdf"></i> Descargar todos los reportes
            </button>
        </div>
    </div>

    <div class="filters-grid">
        <div class="filter-group">
            <label for="fechaInicioProv">Fecha inicio</label>
            <input type="date" id="fechaInicioProv">
        </div>
        <div class="filter-group">
            <label for="fechaFinProv">Fecha fin</label>
            <input type="date" id="fechaFinProv">
        </div>
        <div class="filter-group">
            <label for="tipoGraficaProveedor">Tipo de gráfica</label>
            <select id="tipoGraficaProveedor">
                <option value="bar">Barras</option>
                <option value="pie">Pastel</option>
                <option value="line">Líneas</option>
                <option value="doughnut">Donas</option>
                <option value="polarArea">Área polar</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="selectReporteProveedor">Tipo de reporte</label>
            <select id="selectReporteProveedor">
                <option value="todos">Todos los reportes</option>
                <option value="suministro">Productos suministrados por proveedor</option>
                <option value="ranking">Ranking de proveedores</option>
                <option value="comparacion">Comparación mensual de suministros</option>
                <option value="dependencia">Dependencia de proveedores</option>
            </select>
        </div>
    </div>

    <div class="rp-status" id="estadoReporteProveedor" role="status" aria-live="polite"></div>
    <div class="rp-loading" id="cargandoReporteProveedor" style="display:none;">Generando reportes...</div>
    <div class="rp-error" id="errorReporteProveedor" style="display:none;"></div>
</section>

<div class="rp-reports-grid">
    <section class="report-subcard rp-section" id="reporteSuministro">
        <div class="report-subcard-header"><h3><i class="fas fa-boxes"></i> Productos Suministrados por Proveedor</h3></div>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-container rp-chart-container"><canvas id="graficoSuministro"></canvas></div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaSuministro" class="table-wrapper"></div>
                <div class="section-actions">
                    <button type="button" class="btn-export" data-pdf-report="suministro"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
                    <button type="button" class="btn-primary-action" data-image-report="suministro"><i class="fas fa-image"></i> Descargar Gráfico</button>
                </div>
            </div>
        </div>
    </section>

    <section class="report-subcard rp-section" id="reporteRanking">
        <div class="report-subcard-header"><h3><i class="fas fa-ranking-star"></i> Ranking de Proveedores</h3></div>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-container rp-chart-container"><canvas id="graficoRanking"></canvas></div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaRanking" class="table-wrapper"></div>
                <div class="section-actions">
                    <button type="button" class="btn-export" data-pdf-report="ranking"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
                    <button type="button" class="btn-primary-action" data-image-report="ranking"><i class="fas fa-image"></i> Descargar Gráfico</button>
                </div>
            </div>
        </div>
    </section>

    <section class="report-subcard rp-section" id="reporteComparacion">
        <div class="report-subcard-header"><h3><i class="fas fa-calendar-alt"></i> Comparación Mensual de Unidades Recibidas</h3></div>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-container rp-chart-container"><canvas id="graficoComparacion"></canvas></div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaComparacion" class="table-wrapper"></div>
                <div class="section-actions">
                    <button type="button" class="btn-export" data-pdf-report="comparacion"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
                    <button type="button" class="btn-primary-action" data-image-report="comparacion"><i class="fas fa-image"></i> Descargar Gráfico</button>
                </div>
            </div>
        </div>
    </section>

    <section class="report-subcard rp-section" id="reporteDependencia">
        <div class="report-subcard-header"><h3><i class="fas fa-chart-pie"></i> Dependencia de Proveedores</h3></div>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-container rp-chart-container"><canvas id="graficoDependencia"></canvas></div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaDependencia" class="table-wrapper"></div>
                <div class="section-actions">
                    <button type="button" class="btn-export" data-pdf-report="dependencia"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
                    <button type="button" class="btn-primary-action" data-image-report="dependencia"><i class="fas fa-image"></i> Descargar Gráfico</button>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.rp-report { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
.rp-report .filter-group { display: flex; flex-direction: column; gap: 8px; }
.rp-report .filter-group label { font-weight: 600; color: #4a5568; }
.rp-report .filter-group select,.rp-report .filter-group input { padding: 11px 12px; border: 1px solid #dfe6ed; border-radius: 8px; background: white; }
.rp-reports-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; margin-top: 20px; }
.rp-section { margin: 0; padding: 22px; background: #fff; border: 1px solid #e6edf5; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
.rp-chart-container { position: relative; height: 390px; }
.rp-report .table-wrapper,.rp-reports-grid .table-wrapper { max-height: 430px; overflow: auto; margin-bottom: 15px; }
.rp-report .table-wrapper table,.rp-reports-grid .table-wrapper table { width: 100%; border-collapse: collapse; }
.rp-report .table-wrapper th,.rp-reports-grid .table-wrapper th { background: #2196F3; color: white; padding: 12px; text-align: left; }
.rp-report .table-wrapper td,.rp-reports-grid .table-wrapper td { padding: 10px 12px; border-bottom: 1px solid #edf2f7; }
.rp-report .section-actions,.rp-reports-grid .section-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.rp-report .btn-primary-action,.rp-report .btn-export,.rp-reports-grid .btn-primary-action,.rp-reports-grid .btn-export { border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; color: white; display: flex; align-items: center; gap: 8px; }
.rp-report .btn-primary-action,.rp-reports-grid .btn-primary-action { background: linear-gradient(135deg,#2196F3,#1976D2); }
.rp-report .btn-export,.rp-reports-grid .btn-export { background: linear-gradient(135deg,#f56565,#e53e3e); }
.rp-report .btn-primary-action:hover,.rp-reports-grid .btn-primary-action:hover,.rp-report .btn-export:hover,.rp-reports-grid .btn-export:hover { filter: brightness(.95); }
.rp-status { min-height: 1.5em; color: #4a5568; margin-top: 14px; }
.rp-loading { text-align: center; padding: 14px; color: #1976D2; }
.rp-error { padding: 12px; color: #9b2c2c; background: #fff5f5; border: 1px solid #feb2b2; border-radius: 8px; }
@media(max-width:768px) {
    .rp-report { padding: 16px; }
    .rp-section { padding: 16px; }
    .rp-chart-container { height: 320px; }
    .rp-report .section-actions,.rp-reports-grid .section-actions { width: 100%; }
    .rp-report .section-actions button,.rp-reports-grid .section-actions button { width: 100%; justify-content: center; }
}
</style>

<script src="assets/public/js/chart.js"></script>
<script src="assets/public/js/html2canvas.min.js"></script>
<script src="assets/public/js/jspdf.umd.min.js"></script>
<script>
(function () {
    'use strict';

    let datosReportes = <?php echo $reportesProveedorJson; ?>;
    const graficos = {};
    const secciones = {
        suministro: { id: 'reporteSuministro', canvas: 'graficoSuministro', tabla: 'tablaSuministro' },
        ranking: { id: 'reporteRanking', canvas: 'graficoRanking', tabla: 'tablaRanking' },
        comparacion: { id: 'reporteComparacion', canvas: 'graficoComparacion', tabla: 'tablaComparacion' },
        dependencia: { id: 'reporteDependencia', canvas: 'graficoDependencia', tabla: 'tablaDependencia' }
    };

    const colores = function (cantidad) {
        return Array.from({length: Math.max(cantidad, 1)}, function (_, indice) {
            return 'hsl(' + Math.round((360 / Math.max(cantidad, 1)) * indice) + ',70%,60%)';
        });
    };

    function numero(valor) {
        const resultado = Number(valor);
        return Number.isFinite(resultado) ? resultado : 0;
    }

    function formatoMonto(valor) {
        return '$' + numero(valor).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function crearCelda(fila, tipo, texto) {
        const celda = document.createElement(tipo);
        celda.textContent = texto == null ? '' : String(texto);
        fila.appendChild(celda);
    }

    function renderTabla(contenedorId, encabezados, filas, totalFila) {
        const contenedor = document.getElementById(contenedorId);
        if (!filas.length) {
            contenedor.textContent = 'No hay datos disponibles para el período seleccionado.';
            return;
        }

        const tabla = document.createElement('table');
        const filaEncabezado = tabla.createTHead().insertRow();
        encabezados.forEach(function (encabezado) {
            crearCelda(filaEncabezado, 'th', encabezado);
        });
        const cuerpo = tabla.createTBody();
        filas.forEach(function (datosFila) {
            const fila = cuerpo.insertRow();
            datosFila.forEach(function (dato) {
                crearCelda(fila, 'td', dato);
            });
        });
        if (totalFila) {
            const filaTotal = tabla.createTFoot().insertRow();
            totalFila.forEach(function (dato) {
                crearCelda(filaTotal, 'th', dato);
            });
        }
        contenedor.replaceChildren(tabla);
    }

    function crearReporte(tipo) {
        const suministro = datosReportes.suministro || [];
        const ranking = datosReportes.ranking || [];
        const comparacion = datosReportes.comparacion || [];
        const dependencia = datosReportes.dependencia || [];

        if (tipo === 'suministro') {
            const total = suministro.reduce(function (suma, fila) { return suma + numero(fila.cantidad); }, 0);
            return {
                titulo: 'Productos Suministrados por Proveedor',
                labels: suministro.map(function (fila) { return fila.nombre_proveedor || 'Sin nombre'; }),
                datasets: [{
                    label: 'Unidades recibidas',
                    data: suministro.map(function (fila) { return numero(fila.cantidad); })
                }],
                encabezados: ['Proveedor', 'Cantidad', 'Porcentaje'],
                filas: suministro.map(function (fila) {
                    const cantidad = numero(fila.cantidad);
                    return [fila.nombre_proveedor || 'Sin nombre', cantidad, (total ? (cantidad / total * 100) : 0).toFixed(2) + '%'];
                }),
                totalFila: ['Total', total, '100%'],
                orientacionHorizontal: true
            };
        }

        if (tipo === 'ranking') {
            const total = ranking.reduce(function (suma, fila) { return suma + numero(fila.total); }, 0);
            return {
                titulo: 'Ranking de Proveedores',
                labels: ranking.map(function (fila) { return fila.nombre_proveedor || 'Sin nombre'; }),
                datasets: [{
                    label: 'Gasto total de recepción',
                    data: ranking.map(function (fila) { return numero(fila.total); })
                }],
                encabezados: ['Proveedor', 'Total de Compras', 'Porcentaje'],
                filas: ranking.map(function (fila) {
                    const monto = numero(fila.total);
                    return [fila.nombre_proveedor || 'Sin nombre', formatoMonto(monto), (total ? (monto / total * 100) : 0).toFixed(2) + '%'];
                }),
                totalFila: ['Total', formatoMonto(total), '100%'],
                orientacionHorizontal: true
            };
        }

        if (tipo === 'comparacion') {
            const meses = Array.from(new Set(comparacion.map(function (fila) { return fila.mes; }))).sort();
            const proveedores = Array.from(new Set(comparacion.map(function (fila) {
                return fila.nombre_proveedor || 'Sin nombre';
            }))).sort();
            const cantidadesPorMesProveedor = new Map();
            comparacion.forEach(function (fila) {
                cantidadesPorMesProveedor.set(
                    fila.mes + '\u0000' + (fila.nombre_proveedor || 'Sin nombre'),
                    numero(fila.cantidad)
                );
            });
            return {
                titulo: 'Comparación Mensual de Unidades Recibidas',
                labels: meses,
                datasets: proveedores.map(function (proveedor) {
                    return {
                        label: proveedor,
                        data: meses.map(function (mes) {
                            return cantidadesPorMesProveedor.get(mes + '\u0000' + proveedor) || 0;
                        })
                    };
                }),
                encabezados: ['Mes', 'Proveedor', 'Unidades Recibidas'],
                filas: comparacion.map(function (fila) {
                    return [fila.mes, fila.nombre_proveedor || 'Sin nombre', numero(fila.cantidad)];
                }),
                orientacionHorizontal: false
            };
        }

        const total = dependencia.reduce(function (suma, fila) { return suma + numero(fila.monto_total_pagado); }, 0);
        return {
            titulo: 'Dependencia de Proveedores',
            labels: dependencia.map(function (fila) { return fila.nombre_proveedor || 'Sin nombre'; }),
            datasets: [{
                label: 'Dependencia del gasto',
                data: dependencia.map(function (fila) { return numero(fila.dependencia_porcentaje); })
            }],
            encabezados: ['Proveedor', 'Monto de Recepción', '% Dependencia'],
            filas: dependencia.map(function (fila) {
                return [
                    fila.nombre_proveedor || 'Sin nombre',
                    formatoMonto(fila.monto_total_pagado),
                    numero(fila.dependencia_porcentaje).toFixed(2) + '%'
                ];
            }),
            totalFila: ['Total', formatoMonto(total), '100%'],
            orientacionHorizontal: true,
            total: total
        };
    }

    function renderReporte(tipo, sinAnimacion) {
        const elementos = secciones[tipo];
        const reporte = crearReporte(tipo);
        renderTabla(elementos.tabla, reporte.encabezados, reporte.filas, reporte.totalFila);

        if (graficos[tipo]) {
            graficos[tipo].destroy();
            delete graficos[tipo];
        }

        const canvas = document.getElementById(elementos.canvas);
        const contenedorGrafico = canvas.closest('.rp-chart-container');
        if (contenedorGrafico) {
            const alto = reporte.orientacionHorizontal
                ? Math.max(360, Math.min(720, reporte.labels.length * 26))
                : 390;
            contenedorGrafico.style.height = alto + 'px';
        }

        if (!reporte.labels.length || !reporte.datasets.length) {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        const tipoGrafica = document.getElementById('tipoGraficaProveedor').value;
        const paleta = colores(Math.max(reporte.labels.length, reporte.datasets.length));
        const datasets = reporte.datasets.map(function (dataset, indice) {
            const color = paleta[indice % paleta.length];
            return {
                label: dataset.label,
                data: dataset.data,
                backgroundColor: reporte.datasets.length > 1 ? color : paleta,
                borderColor: tipoGrafica === 'line' ? color : undefined,
                borderWidth: tipoGrafica === 'line' ? 2 : 1,
                fill: false
            };
        });
        const mostrarLeyenda = reporte.datasets.length > 1 ||
            !['bar', 'line'].includes(tipoGrafica);

        graficos[tipo] = new Chart(canvas.getContext('2d'), {
            type: tipoGrafica,
            data: { labels: reporte.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: sinAnimacion ? false : undefined,
                indexAxis: tipoGrafica === 'bar' && reporte.orientacionHorizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: mostrarLeyenda, position: 'bottom' },
                    title: { display: false }
                },
                scales: ['bar', 'line'].includes(tipoGrafica)
                    ? { x: { beginAtZero: true }, y: { beginAtZero: true } }
                    : {}
            }
        });
    }

    function renderReportesVisibles() {
        const seleccion = document.getElementById('selectReporteProveedor').value;
        Object.keys(secciones).forEach(function (tipo) {
            const visible = seleccion === 'todos' || seleccion === tipo;
            document.getElementById(secciones[tipo].id).style.display = visible ? 'block' : 'none';
            if (visible) {
                renderReporte(tipo);
            }
        });
    }

    function actualizarResumen() {
        const ranking = datosReportes.ranking || [];
        const comparacion = datosReportes.comparacion || [];
        const totalUnidades = comparacion.reduce(function (suma, fila) { return suma + numero(fila.cantidad); }, 0);
        const totalCompras = ranking.reduce(function (suma, fila) { return suma + numero(fila.total); }, 0);
        const meses = new Set(comparacion.map(function (fila) { return fila.mes; }));

        document.getElementById('totalProveedoresReporte').textContent = ranking.length.toLocaleString();
        document.getElementById('totalUnidadesReporte').textContent = totalUnidades.toLocaleString();
        document.getElementById('totalMesesReporte').textContent = meses.size.toLocaleString();
        document.getElementById('montoTotalReporte').textContent = formatoMonto(totalCompras);
    }

    async function generarReportes() {
        const fechaInicio = document.getElementById('fechaInicioProv').value;
        const fechaFin = document.getElementById('fechaFinProv').value;
        const error = document.getElementById('errorReporteProveedor');
        const estado = document.getElementById('estadoReporteProveedor');
        const carga = document.getElementById('cargandoReporteProveedor');

        error.style.display = 'none';
        estado.textContent = '';
        if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
            error.textContent = 'La fecha de inicio no puede ser posterior a la fecha fin.';
            error.style.display = 'block';
            return;
        }

        carga.style.display = 'block';
        try {
            const respuesta = await fetch('?pagina=reporteProveedores', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'reportes_proveedores',
                    fechaInicio: fechaInicio,
                    fechaFin: fechaFin
                }).toString()
            });
            const contenido = await respuesta.json();
            if (!respuesta.ok || contenido.status !== 'success' || !contenido.data) {
                throw new Error(contenido.message || 'No se pudieron cargar los reportes.');
            }
            datosReportes = contenido.data;
            actualizarResumen();
            renderReportesVisibles();
            estado.textContent = 'Reportes generados correctamente.';
        } catch (exception) {
            error.textContent = exception.message || 'No se pudieron cargar los reportes.';
            error.style.display = 'block';
            console.error('Error al generar los reportes de proveedores:', exception);
        } finally {
            carga.style.display = 'none';
        }
    }

    function descargarImagen(tipo) {
        const canvas = document.getElementById(secciones[tipo].canvas);
        if (!graficos[tipo] || !canvas) {
            document.getElementById('estadoReporteProveedor').textContent = 'No hay gráfica para descargar.';
            return;
        }
        const link = document.createElement('a');
        link.download = 'Grafico_Proveedores_' + tipo + '.png';
        link.href = graficos[tipo].toBase64Image('image/png');
        link.click();
    }

    async function agregarReporteAlPDF(pdf, tipo, agregarPagina) {
        const reporte = crearReporte(tipo);
        const chart = graficos[tipo];
        if (agregarPagina) {
            pdf.addPage();
        }

        const margen = 12;
        const anchoPagina = pdf.internal.pageSize.getWidth();
        const altoPagina = pdf.internal.pageSize.getHeight();
        const anchoContenido = anchoPagina - margen * 2;
        let y = margen;

        pdf.setFontSize(15);
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(45, 55, 72);
        pdf.text(reporte.titulo, margen, y, { maxWidth: anchoContenido });
        y += 9;

        if (!reporte.filas.length) {
            pdf.setFontSize(10);
            pdf.text('No hay datos disponibles para el período seleccionado.', margen, y);
            return;
        }

        if (chart) {
            const canvas = document.getElementById(secciones[tipo].canvas);
            const relacion = canvas.width / canvas.height;
            const altoGrafico = Math.min(anchoContenido / relacion, 95);
            const anchoGrafico = altoGrafico * relacion;
            pdf.addImage(
                chart.toBase64Image('image/png'),
                'PNG',
                margen + (anchoContenido - anchoGrafico) / 2,
                y,
                anchoGrafico,
                altoGrafico
            );
            y += altoGrafico + 8;
        }

        if (typeof window.html2canvas !== 'function') {
            throw new Error('No se pudo cargar la librería para renderizar la tabla del PDF.');
        }

        const tabla = document.querySelector('#' + secciones[tipo].tabla + ' table');
        if (!tabla) {
            throw new Error('No se encontró la tabla del reporte ' + tipo + '.');
        }

        const wrapper = tabla.closest('.table-wrapper');
        const maxHeightAnterior = wrapper ? wrapper.style.maxHeight : '';
        const overflowAnterior = wrapper ? wrapper.style.overflowY : '';
        let tablaCanvas;
        try {
            if (wrapper) {
                wrapper.style.maxHeight = 'none';
                wrapper.style.overflowY = 'visible';
            }
            tablaCanvas = await window.html2canvas(tabla, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
        } finally {
            if (wrapper) {
                wrapper.style.maxHeight = maxHeightAnterior;
                wrapper.style.overflowY = overflowAnterior;
            }
        }

        const tablaWidth = anchoPagina - margen * 2;
        const tablaHeight = tablaCanvas.height * tablaWidth / tablaCanvas.width;
        if (y + tablaHeight <= altoPagina - margen) {
            pdf.addImage(tablaCanvas.toDataURL('image/png'), 'PNG', margen, y, tablaWidth, tablaHeight);
            return;
        }

        const ratio = tablaCanvas.width / tablaWidth;
        const altoContenidoPagina = (altoPagina - margen * 2) * ratio;
        let origenY = 0;
        let primeraSeccion = true;
        while (origenY < tablaCanvas.height) {
            if (!primeraSeccion) {
                pdf.addPage();
            }
            const altoSeccion = Math.min(altoContenidoPagina, tablaCanvas.height - origenY);
            const seccionCanvas = document.createElement('canvas');
            seccionCanvas.width = tablaCanvas.width;
            seccionCanvas.height = Math.ceil(altoSeccion);
            seccionCanvas.getContext('2d').drawImage(
                tablaCanvas,
                0, origenY, tablaCanvas.width, seccionCanvas.height,
                0, 0, tablaCanvas.width, seccionCanvas.height
            );
            pdf.addImage(
                seccionCanvas.toDataURL('image/png'),
                'PNG',
                margen,
                primeraSeccion ? y : margen,
                tablaWidth,
                seccionCanvas.height / ratio
            );
            origenY += altoSeccion;
            primeraSeccion = false;
        }
    }

    async function descargarPDF(tipo) {
        const reporte = crearReporte(tipo);
        const estado = document.getElementById('estadoReporteProveedor');
        if (!reporte.filas.length || !graficos[tipo]) {
            estado.textContent = 'No hay datos suficientes para generar este PDF.';
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF) {
            estado.textContent = 'No se pudo cargar la librería para generar PDF.';
            return;
        }

        try {
            const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            await agregarReporteAlPDF(pdf, tipo, false);
            pdf.save('Reporte_Proveedores_' + tipo + '.pdf');
            estado.textContent = 'PDF descargado.';
        } catch (exception) {
            estado.textContent = exception.message || 'No se pudo generar el PDF.';
            console.error('Error al generar el PDF del reporte de proveedores:', exception);
        }
    }

    async function descargarTodosLosReportesPDF() {
        const estado = document.getElementById('estadoReporteProveedor');
        if (!window.jspdf || !window.jspdf.jsPDF) {
            estado.textContent = 'No se pudo cargar la librería para generar PDF.';
            return;
        }

        const tipos = Object.keys(secciones);
        if (tipos.every(function (tipo) { return crearReporte(tipo).filas.length === 0; })) {
            estado.textContent = 'No hay datos suficientes para generar los PDF.';
            return;
        }

        const seleccion = document.getElementById('selectReporteProveedor').value;
        try {
            tipos.forEach(function (tipo) {
                document.getElementById(secciones[tipo].id).style.display = 'block';
            });
            tipos.forEach(function (tipo) { renderReporte(tipo, true); });

            const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            for (let indice = 0; indice < tipos.length; indice++) {
                await agregarReporteAlPDF(pdf, tipos[indice], indice > 0);
            }
            pdf.save('Reportes_Proveedores_Completos.pdf');
            estado.textContent = 'PDF con todos los reportes descargado.';
        } catch (exception) {
            estado.textContent = 'No se pudieron generar todos los reportes en PDF.';
            console.error('Error al descargar todos los reportes de proveedores:', exception);
        } finally {
            tipos.forEach(function (tipo) {
                const visible = seleccion === 'todos' || seleccion === tipo;
                document.getElementById(secciones[tipo].id).style.display = visible ? 'block' : 'none';
            });
        }
    }

    document.getElementById('generarReportesProveedores').addEventListener('click', generarReportes);
    document.getElementById('descargarTodosReportesProveedores').addEventListener('click', descargarTodosLosReportesPDF);
    document.getElementById('selectReporteProveedor').addEventListener('change', renderReportesVisibles);
    document.getElementById('tipoGraficaProveedor').addEventListener('change', renderReportesVisibles);
    document.querySelectorAll('[data-pdf-report]').forEach(function (boton) {
        boton.addEventListener('click', function () { descargarPDF(boton.dataset.pdfReport); });
    });
    document.querySelectorAll('[data-image-report]').forEach(function (boton) {
        boton.addEventListener('click', function () { descargarImagen(boton.dataset.imageReport); });
    });

    actualizarResumen();
    renderReportesVisibles();
})();
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
