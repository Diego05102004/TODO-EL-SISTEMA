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

if (empty($permisosUsuario['consultar'])) {
    header('Location: ?pagina=acceso-denegado');
    exit();
}

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log('Error al generar JWT en reporteProductos: ' . $e->getMessage());
    }
}

$pagina_actual = 'reporteProductos';
$titulo_pagina = 'Reporte de Productos';

$jsonOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$masVendidosJson = json_encode($masVendidos ?? [], $jsonOptions) ?: '[]';
$stockProductosJson = json_encode($stockProductos ?? [], $jsonOptions) ?: '[]';
$rotacionJson = json_encode($rotacion ?? [], $jsonOptions) ?: '[]';
$categoriasJson = json_encode($categorias ?? [], $jsonOptions) ?: '[]';
$reporteCategoriasJson = json_encode($reporteCategorias ?? [], $jsonOptions) ?: '[]';

$totalProductos = count($stockProductos ?? []);
$productosStockBajo = count(array_filter($stockProductos ?? [], static function ($producto) {
    return ($producto['categoria_stock'] ?? '') === 'Bajo Stock';
}));
$totalCategorias = count($categorias ?? []);

ob_start();
?>

<div class="summary-cards">
    <div class="summary-card sales">
        <div class="card-icon"><i class="fas fa-boxes"></i></div>
        <div class="card-content">
            <h3>Productos</h3>
            <p class="card-value"><?php echo number_format($totalProductos); ?></p>
        </div>
    </div>
    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="card-content">
            <h3>Stock Bajo</h3>
            <p class="card-value"><?php echo number_format($productosStockBajo); ?></p>
        </div>
    </div>
    <div class="summary-card income">
        <div class="card-icon"><i class="fas fa-tags"></i></div>
        <div class="card-content">
            <h3>Categorías</h3>
            <p class="card-value"><?php echo number_format($totalCategorias); ?></p>
        </div>
    </div>
</div>

<section class="report-card rp-report" id="resultadoReporteProductos">
    <div class="section-header">
        <h2><i class="fas fa-chart-column"></i> Reportes de Productos</h2>
        <div class="section-actions">
            <button type="button" class="btn-primary-action" id="generarReporteProducto">
                <i class="fas fa-chart-bar"></i> Generar Reporte
            </button>
            <button type="button" class="btn-export" id="descargarPDFProductos">
                <i class="fas fa-file-pdf"></i> Descargar PDF
            </button>
        </div>
    </div>

    <div class="filters-grid">
        <div class="filter-group">
            <label for="tipoReporteProducto">Tipo de reporte</label>
            <select id="tipoReporteProducto">
                <option value="mas_vendidos">Top Productos Más Vendidos</option>
                <option value="stock">Stock Alto vs Bajo</option>
                <option value="rotacion">Rotación de Productos</option>
                <option value="categorias">Productos por Categoría</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="tipoGraficaProducto">Tipo de gráfica</label>
            <select id="tipoGraficaProducto">
                <option value="bar">Barras</option>
                <option value="pie">Pastel</option>
                <option value="line">Líneas</option>
                <option value="doughnut">Rosca</option>
                <option value="polarArea">Área polar</option>
            </select>
        </div>
        <div id="parametrosExtra" class="filters-grid dynamic-filter"></div>
    </div>

    <p class="rp-status" id="estadoReporteProducto" role="status" aria-live="polite"></p>

    <section class="report-subcard rp-result" id="reporteProductoActual">
        <div class="report-subcard-header">
            <h3><i class="fas fa-chart-pie"></i> <span id="tituloReporteProducto">Top Productos Más Vendidos</span></h3>
        </div>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-container rp-chart-container">
                    <canvas id="graficoReporteProducto"></canvas>
                </div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaReporteProducto" class="table-wrapper"></div>
            </div>
        </div>
    </section>
</section>

<style>
.rp-report { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
.rp-report .filter-group { display: flex; flex-direction: column; gap: 8px; }
.rp-report .filter-group label { font-weight: 600; color: #4a5568; }
.rp-report .filter-group select,.rp-report .filter-group input { padding: 11px 12px; border: 1px solid #dfe6ed; border-radius: 8px; background: white; }
.rp-report .dynamic-filter { margin: 0; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); }
.rp-report .section-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.rp-report .btn-primary-action,.rp-report .btn-export { border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; color: white; display: flex; align-items: center; gap: 8px; }
.rp-report .btn-primary-action { background: linear-gradient(135deg,#2196F3,#1976D2); }
.rp-report .btn-export { background: linear-gradient(135deg,#f56565,#e53e3e); }
.rp-status { min-height: 1.5em; color: #4a5568; }
.rp-result { margin-top: 20px; }
.rp-chart-container { position: relative; height: 390px; }
.rp-report .table-wrapper { max-height: 520px; overflow: auto; }
.rp-report .table-wrapper table { width: 100%; border-collapse: collapse; }
.rp-report .table-wrapper th { background: #2196F3; color: white; padding: 12px; text-align: left; }
.rp-report .table-wrapper td { padding: 10px 12px; border-bottom: 1px solid #edf2f7; }
@media(max-width:768px) {
    .rp-report { padding: 16px; }
    .rp-chart-container { height: 320px; }
    .rp-report .section-actions { width: 100%; }
    .rp-report .section-actions button { width: 100%; justify-content: center; }
}
</style>

<script src="assets/public/js/chart.js"></script>
<script src="assets/public/js/jspdf.umd.min.js"></script>
<script>
(function () {
    'use strict';

    let masVendidos = <?php echo $masVendidosJson; ?>;
    const stockProductos = <?php echo $stockProductosJson; ?>;
    const rotacion = <?php echo $rotacionJson; ?>;
    const categorias = <?php echo $categoriasJson; ?>;
    const reporteCategorias = <?php echo $reporteCategoriasJson; ?>;
    const graficos = {};
    let reporteActual = null;

    const selectorReporte = document.getElementById('tipoReporteProducto');
    const selectorGrafica = document.getElementById('tipoGraficaProducto');
    const parametrosExtra = document.getElementById('parametrosExtra');
    const estado = document.getElementById('estadoReporteProducto');
    const tabla = document.getElementById('tablaReporteProducto');
    const canvas = document.getElementById('graficoReporteProducto');

    function crearCelda(fila, etiqueta, valor) {
        const celda = document.createElement(etiqueta);
        celda.textContent = valor == null ? '' : String(valor);
        fila.appendChild(celda);
        return celda;
    }

    function renderTabla(encabezados, filas) {
        const elementoTabla = document.createElement('table');
        const head = elementoTabla.createTHead();
        const filaHead = head.insertRow();
        encabezados.forEach(function (encabezado) {
            crearCelda(filaHead, 'th', encabezado);
        });

        const body = elementoTabla.createTBody();
        filas.forEach(function (datosFila) {
            const fila = body.insertRow();
            datosFila.forEach(function (dato) {
                crearCelda(fila, 'td', dato);
            });
        });
        tabla.replaceChildren(elementoTabla);
    }

    function mostrarParametros(tipo) {
        parametrosExtra.replaceChildren();

        if (tipo === 'mas_vendidos') {
            const grupo = document.createElement('div');
            grupo.className = 'filter-group';
            const etiqueta = document.createElement('label');
            etiqueta.htmlFor = 'topN';
            etiqueta.textContent = 'Top N (máximo 50)';
            const entrada = document.createElement('input');
            entrada.type = 'number';
            entrada.id = 'topN';
            entrada.min = '1';
            entrada.max = '50';
            entrada.value = '10';
            grupo.append(etiqueta, entrada);
            parametrosExtra.appendChild(grupo);
        } else if (tipo === 'stock') {
            const grupo = document.createElement('div');
            grupo.className = 'filter-group';
            const etiqueta = document.createElement('label');
            etiqueta.htmlFor = 'categoriaStock';
            etiqueta.textContent = 'Categoría';
            const selector = document.createElement('select');
            selector.id = 'categoriaStock';
            const todas = document.createElement('option');
            todas.value = 'todas';
            todas.textContent = 'Todas';
            selector.appendChild(todas);
            categorias.forEach(function (categoria) {
                const opcion = document.createElement('option');
                opcion.value = categoria.id_categoria;
                opcion.textContent = categoria.nombre_categoria;
                selector.appendChild(opcion);
            });
            selector.addEventListener('change', renderReporteSeleccionado);
            grupo.append(etiqueta, selector);
            parametrosExtra.appendChild(grupo);
        }
    }

    function crearReporte(tipo) {
        if (tipo === 'mas_vendidos') {
            const topN = parseInt(document.getElementById('topN')?.value || '10', 10);
            const datos = masVendidos.slice(0, topN);
            return {
                titulo: 'Top Productos Más Vendidos',
                etiqueta: 'Unidades Vendidas',
                labels: datos.map(function (fila) { return fila.nombre_producto; }),
                values: datos.map(function (fila) { return Number(fila.total_vendido) || 0; }),
                encabezados: ['Producto', 'Unidades Vendidas'],
                filas: datos.map(function (fila) { return [fila.nombre_producto, Number(fila.total_vendido) || 0]; }),
                orientacionHorizontal: true
            };
        }

        if (tipo === 'stock') {
            const categoriaSeleccionada = document.getElementById('categoriaStock')?.value || 'todas';
            const datos = categoriaSeleccionada === 'todas'
                ? stockProductos
                : stockProductos.filter(function (fila) {
                    return String(fila.id_categoria) === String(categoriaSeleccionada);
                });
            const bajo = datos.filter(function (fila) { return fila.categoria_stock === 'Bajo Stock'; }).length;
            const alto = datos.length - bajo;
            return {
                titulo: 'Stock Alto vs Bajo',
                etiqueta: 'Productos',
                labels: ['Alto Stock', 'Bajo Stock'],
                values: [alto, bajo],
                encabezados: ['Producto', 'Stock Actual', 'Stock Mínimo', 'Estatus', 'Categoría'],
                filas: datos.map(function (fila) {
                    return [fila.nombre_producto, fila.stock, fila.stock_minimo, fila.categoria_stock, fila.nombre_categoria];
                })
            };
        }

        if (tipo === 'rotacion') {
            return {
                titulo: 'Rotación de Productos (días promedio)',
                etiqueta: 'Días promedio en inventario',
                labels: rotacion.map(function (fila) { return fila.nombre_producto; }),
                values: rotacion.map(function (fila) {
                    return fila.dias_promedio == null ? null : Number(fila.dias_promedio);
                }),
                encabezados: ['Producto', 'Días en Inventario'],
                filas: rotacion.map(function (fila) {
                    const dias = fila.dias_promedio == null ? 'Sin datos' : Number(fila.dias_promedio).toFixed(2);
                    return [fila.nombre_producto, dias];
                }),
                orientacionHorizontal: true
            };
        }

        return {
            titulo: 'Productos por Categoría',
            etiqueta: 'Productos',
            labels: reporteCategorias.map(function (fila) { return fila.nombre_categoria; }),
            values: reporteCategorias.map(function (fila) { return Number(fila.cantidad) || 0; }),
            encabezados: ['Categoría', 'Cantidad', 'Porcentaje'],
            filas: reporteCategorias.map(function (fila) {
                return [fila.nombre_categoria, Number(fila.cantidad) || 0, (Number(fila.porcentaje) || 0) + '%'];
            })
        };
    }

    function renderReporteSeleccionado() {
        const tipo = selectorReporte.value;
        const tipoGrafica = selectorGrafica.value;
        const reporte = crearReporte(tipo);
        reporteActual = reporte;
        document.getElementById('tituloReporteProducto').textContent = reporte.titulo;
        renderTabla(reporte.encabezados, reporte.filas);

        if (graficos.productos) {
            graficos.productos.destroy();
        }

        const chartContainer = canvas.closest('.rp-chart-container');
        if (chartContainer) {
            const alto = reporte.orientacionHorizontal
                ? Math.max(390, Math.min(780, reporte.labels.length * 24))
                : 390;
            chartContainer.style.height = alto + 'px';
        }

        if (!reporte.labels.length) {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            estado.textContent = 'No hay datos disponibles para este reporte.';
            return;
        }

        estado.textContent = '';
        const paleta = reporte.labels.map(function (_, indice) {
            return 'hsl(' + Math.round((360 / Math.max(reporte.labels.length, 1)) * indice) + ',70%,60%)';
        });
        graficos.productos = new Chart(canvas.getContext('2d'), {
            type: tipoGrafica,
            data: {
                labels: reporte.labels,
                datasets: [{
                    label: reporte.etiqueta,
                    data: reporte.values,
                    backgroundColor: paleta,
                    borderWidth: tipoGrafica === 'line' ? 3 : 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: tipoGrafica === 'bar' && reporte.orientacionHorizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: ['pie', 'doughnut', 'polarArea'].includes(tipoGrafica), position: 'bottom' }
                },
                scales: ['bar', 'line'].includes(tipoGrafica)
                    ? { x: { beginAtZero: true }, y: { beginAtZero: true } }
                    : {}
            }
        });
    }

    async function generarReporte() {
        if (selectorReporte.value !== 'mas_vendidos') {
            renderReporteSeleccionado();
            return;
        }

        const campoTopN = document.getElementById('topN');
        const topN = Number(campoTopN?.value);
        if (!Number.isInteger(topN) || topN < 1 || topN > 50) {
            estado.textContent = 'Indica un Top N entre 1 y 50.';
            return;
        }

        estado.textContent = 'Cargando reporte...';
        try {
            const respuesta = await fetch('?pagina=reporteProductos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ accion: 'reportes_productos', topN: String(topN) }).toString()
            });
            const contenido = await respuesta.json();
            if (!respuesta.ok || contenido.status !== 'success' || !Array.isArray(contenido.data)) {
                throw new Error(contenido.message || 'No se pudo cargar el reporte.');
            }
            masVendidos = contenido.data;
            renderReporteSeleccionado();
            estado.textContent = 'Reporte generado correctamente.';
        } catch (error) {
            estado.textContent = error.message || 'No se pudo cargar el reporte.';
            console.error('Error al generar el reporte de productos:', error);
        }
    }

    function descargarPDF() {
        if (!reporteActual || !reporteActual.filas.length) {
            estado.textContent = 'No hay datos para exportar.';
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF) {
            estado.textContent = 'No se pudo cargar la librería para generar PDF.';
            return;
        }

        const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const margen = 12;
        const anchoPagina = pdf.internal.pageSize.getWidth();
        const altoPagina = pdf.internal.pageSize.getHeight();
        const anchoContenido = anchoPagina - margen * 2;
        let y = margen;

        pdf.setFontSize(15);
        pdf.setTextColor(45, 55, 72);
        pdf.text(reporteActual.titulo, margen, y);
        y += 9;

        if (graficos.productos) {
            const imageRatio = canvas.width / canvas.height;
            const altoGrafico = Math.min(anchoContenido / imageRatio, 95);
            const anchoGrafico = altoGrafico * imageRatio;
            pdf.addImage(
                graficos.productos.toBase64Image('image/png'),
                'PNG',
                margen + (anchoContenido - anchoGrafico) / 2,
                y,
                anchoGrafico,
                altoGrafico
            );
            y += altoGrafico + 8;
        }

        const encabezados = reporteActual.encabezados;
        const cantidadColumnas = encabezados.length;
        const proporciones = cantidadColumnas === 2
            ? [0.68, 0.32]
            : cantidadColumnas === 3
                ? [0.5, 0.25, 0.25]
                : [0.38, 0.155, 0.155, 0.155, 0.155];
        const anchos = encabezados.map(function (_, indice) {
            return anchoContenido * (proporciones[indice] || 1 / cantidadColumnas);
        });
        const altoLinea = 4;

        function escribirEncabezadoTabla() {
            pdf.setFontSize(8);
            pdf.setFont('helvetica', 'bold');
            pdf.setFillColor(33, 150, 243);
            pdf.setTextColor(255, 255, 255);
            let x = margen;
            encabezados.forEach(function (encabezado, indice) {
                pdf.rect(x, y, anchos[indice], 8, 'F');
                pdf.text(String(encabezado), x + 2, y + 5.3, { maxWidth: anchos[indice] - 4 });
                x += anchos[indice];
            });
            y += 8;
        }

        function nuevaPaginaConEncabezado() {
            pdf.addPage();
            y = margen;
            escribirEncabezadoTabla();
        }

        escribirEncabezadoTabla();
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        pdf.setTextColor(45, 55, 72);

        reporteActual.filas.forEach(function (fila) {
            const textos = fila.map(function (valor, indice) {
                return pdf.splitTextToSize(String(valor ?? ''), anchos[indice] - 4);
            });
            const altoFila = Math.max(8, ...textos.map(function (lineas) {
                return lineas.length * altoLinea + 2;
            }));

            if (y + altoFila > altoPagina - margen) {
                nuevaPaginaConEncabezado();
            }

            let x = margen;
            textos.forEach(function (lineas, indice) {
                pdf.setDrawColor(220, 226, 232);
                pdf.rect(x, y, anchos[indice], altoFila);
                pdf.text(lineas, x + 2, y + 5);
                x += anchos[indice];
            });
            y += altoFila;
        });

        pdf.save('Reporte_Productos.pdf');
        estado.textContent = 'PDF descargado.';
    }

    selectorReporte.addEventListener('change', function () {
        mostrarParametros(selectorReporte.value);
        renderReporteSeleccionado();
    });
    selectorGrafica.addEventListener('change', renderReporteSeleccionado);
    document.getElementById('generarReporteProducto').addEventListener('click', generarReporte);
    document.getElementById('descargarPDFProductos').addEventListener('click', descargarPDF);

    mostrarParametros(selectorReporte.value);
    renderReporteSeleccionado();
})();
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
