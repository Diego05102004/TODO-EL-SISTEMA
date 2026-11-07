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
    <title>Reportes de Inventario</title>
</head>

<body class="fondo" style="height: auto; min-height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<!-- SOLO REPORTE DE RECEPCIÓN -->
<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Recepción</h2>
        <p class="texto-p">Seleccione y genere los reportes que desea visualizar</p>
    </div>

    <!-- Selector de Reportes -->
    <div class="report-selector">
        <label for="selectReporteRecepcion" class="title-select"><strong>Seleccionar Reporte:</strong></label>
        <br>
        <select id="selectReporteRecepcion" class="selector-reporte">
            <option value="todos">Todos los Reportes</option>
            <option value="proveedores">Recepciones por Proveedor</option>
            <option value="productos">Productos más Recibidos</option>
            <option value="mensual">Recepciones Mensuales</option>
        </select>
    </div>

    <!-- Parámetros -->
    <div class="parameters-container">
        <h2 class="titulo-form">Parámetros del Reporte</h2>
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label for="fechaInicioRecepcion" class="title-select">Fecha inicio:</label>
                <input type="date" id="fechaInicioRecepcion" class="selector-reporte">
            </div>
            <div class="col-md-3">
                <label for="fechaFinRecepcion" class="title-select">Fecha fin:</label><br>
                <input type="date" id="fechaFinRecepcion" class="selector-reporte">
            </div>
            <div class="col-md-3">
                <label for="tipoGraficaRecepcion" class="title-select">Tipo de gráfica:</label>
                <select id="tipoGraficaRecepcion" class="selector-reporte">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Donas</option>
                    <option value="polarArea">Área Polar</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button id="generarReporteRecepcionBtn" class="btn btn-primary w-100">Generar Reporte</button>
            </div>
        </div>
        <div class="row g-3 align-items-center mt-2" id="parametrosIndividualesRecep"></div>
    </div>

    <!-- Mensaje de error -->
    <div id="errorMessageRecepcion" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

    <!-- Reporte 1: Cantidad de recepciones por proveedor -->
    <div class="report-section" id="reporteProveedores">
        <h2 class="titulo-form">Recepciones por Proveedor</h2>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoProveedores" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaProveedores"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteProveedores','Reporte_Recepciones_Proveedores.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoProveedores','Grafico_Recepciones_Proveedores.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 2: Productos más recibidos -->
    <div class="report-section" id="reporteProductos">
        <h2 class="titulo-form">Productos más Recibidos</h2>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoProductos" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaProductos"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteProductos','Reporte_Recepciones_Productos.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoProductos','Grafico_Recepciones_Productos.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 3: Recepciones mensuales -->
    <div class="report-section" id="reporteMensualRecepcion">
        <h2 class="titulo-form">Recepciones Mensuales</h2>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoMensualRecepcion" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaMensualRecepcion"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteMensualRecepcion','Reporte_Recepciones_Mensual.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoMensualRecepcion','Grafico_Recepciones_Mensual.png')">
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
    // SECCIÓN DE RECEPCIÓN
    // ============================
    const recepcionesProveedor = <?php echo json_encode($RecepcionesProveedor) ?>;
    const productosRecibidos = <?php echo json_encode($ProductorRecibidos) ?>;
    const recepcionMensual = <?php echo json_encode($RecepcionMensual)?>;
    let graficoProveedores = null;
    let graficoProductos = null;
    let graficoMensualRecepcion = null;

    function filtrarPorFechas(datos, inicio, fin) {
        const inicioDate = inicio ? new Date(inicio + 'T00:00:00') : null;
        const finDate = fin ? new Date(fin + 'T23:59:59') : null;

        return datos.filter(d => {
            const fechaDato = d.fecha || d.fecha_recepcion || d.created_at || null;

            if (!fechaDato) return true;

            const fechaConvertida = new Date(fechaDato);

            if (isNaN(fechaConvertida)) return true;
            if (inicioDate && fechaConvertida < inicioDate) return false;
            if (finDate && fechaConvertida > finDate) return false;

            return true;
        });
    }
    
    function generarColores(n) {
        return Array.from({length: n}, (_, i) => {
            const hue = (360 / n) * i;
            return `hsl(${hue}, 70%, 60%)`;
        });
    }

    // Agrupar por etiqueta sumando value
    function agruparPorLabel(rows) {
        const map = new Map();
        (rows || []).forEach(r => {
            const key = String(r.label ?? '').trim() || 'Sin nombre';
            const val = Number(r.value ?? 0) || 0;
            map.set(key, (map.get(key) || 0) + val);
        });
        return Array.from(map.entries()).map(([label, value]) => ({ label, value }));
    }

function renderReporteRecepcion(datos, labelKey, valueKey, canvasId, tablaId, titulo, tipoGrafica) {
    // Destruir gráfico previo si no hay datos, para no dejar gráfico viejo
    if (!datos || datos.length === 0) {
        if (canvasId === 'graficoProveedores' && graficoProveedores) {
            graficoProveedores.destroy(); graficoProveedores = null;
        } else if (canvasId === 'graficoProductos' && graficoProductos) {
            graficoProductos.destroy(); graficoProductos = null;
        } else if (canvasId === 'graficoMensualRecepcion' && graficoMensualRecepcion) {
            graficoMensualRecepcion.destroy(); graficoMensualRecepcion = null;
        }
        const tablaEl = document.getElementById(tablaId);
        if (tablaEl) {
            tablaEl.innerHTML = `
                <div class="alert alert-warning text-center">
                    📭 No hay datos disponibles para el período seleccionado
                </div>`;
        }
        // limpiar canvas
        const canvas = document.getElementById(canvasId);
        if (canvas) { const ctx = canvas.getContext('2d'); ctx && ctx.clearRect(0,0,canvas.width,canvas.height); }
        return;
    }

    // **CORRECCIÓN: Mapear los campos correctamente**
    const labels = datos.map(d => (d[labelKey] ?? d.label ?? 'Sin nombre'));
    
    const data = datos.map(d => Number(d[valueKey] ?? d.value ?? 0) || 0);

    const total = data.reduce((a, b) => a + b, 0);
    const colores = generarColores(labels.length);

    // **CORRECCIÓN: Header dinámico para la tabla**
    const headerLabel = getHeaderLabelRecepcion(labelKey);
    
    let tablaHtml = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>${headerLabel}</th>
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
    
    // Destruir gráfico anterior si existe
    if (canvasId === 'graficoProveedores' && graficoProveedores) {
        graficoProveedores.destroy();
    } else if (canvasId === 'graficoProductos' && graficoProductos) {
        graficoProductos.destroy();
    } else if (canvasId === 'graficoMensualRecepcion' && graficoMensualRecepcion) {
        graficoMensualRecepcion.destroy();
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
                            // Robust numeric extraction for tooltips
                            const raw = context.raw;
                            let value = 0;
                            if (typeof context.parsed === 'number') {
                                value = context.parsed;
                            } else if (context.parsed && typeof context.parsed === 'object') {
                                value = Number((context.parsed.x ?? context.parsed.y ?? raw ?? 0));
                            } else {
                                value = Number(raw ?? 0);
                            }
                            const nums = (context.dataset.data || []).map(d => {
                                if (typeof d === 'number') return d;
                                if (d && typeof d === 'object') return Number(d.x ?? d.y ?? 0);
                                const n = Number(d ?? 0); return isNaN(n) ? 0 : n;
                            });
                            const total = nums.reduce((a,b)=> a + (isNaN(b)?0:b), 0);
                            const pct = total > 0 ? ` (${((value/total)*100).toFixed(1)}%)` : '';
                            const valFmt = isNaN(value) ? 0 : value;
                            return `${valFmt.toLocaleString(undefined,{maximumFractionDigits:2})}${pct}`;
                        }
                    }
                }
            }
        }
    });
    
    // Asignar el nuevo gráfico a la variable correspondiente
    if (canvasId === 'graficoProveedores') {
        graficoProveedores = newChart;
    } else if (canvasId === 'graficoProductos') {
        graficoProductos = newChart;
    } else if (canvasId === 'graficoMensualRecepcion') {
        graficoMensualRecepcion = newChart;
    }
}

// **FUNCIÓN AUXILIAR PARA HEADERS DE TABLA**
function getHeaderLabelRecepcion(labelKey) {

    const headers = {
        'label': 'Descripción',
        'mes': 'Mes'
    };
    return headers[labelKey] || 'Item';
}
    function toggleReportesRecepcion() {
        const seleccion = document.getElementById('selectReporteRecepcion').value;
        if (seleccion === 'todos') {
            document.getElementById('reporteProveedores').style.display = 'block';
            document.getElementById('reporteProductos').style.display = 'block';
            document.getElementById('reporteMensualRecepcion').style.display = 'block';
        } else if (seleccion === 'proveedores') {
            document.getElementById('reporteProveedores').style.display = 'block';
            document.getElementById('reporteProductos').style.display = 'none';
            if (graficoProductos) { graficoProductos.destroy(); graficoProductos = null; }
            document.getElementById('reporteMensualRecepcion').style.display = 'none';
            if (graficoMensualRecepcion) { graficoMensualRecepcion.destroy(); graficoMensualRecepcion = null; }
        } else if (seleccion === 'productos') {
            document.getElementById('reporteProveedores').style.display = 'none';
            if (graficoProveedores) { graficoProveedores.destroy(); graficoProveedores = null; }
            document.getElementById('reporteProductos').style.display = 'block';
            document.getElementById('reporteMensualRecepcion').style.display = 'none';
            if (graficoMensualRecepcion) { graficoMensualRecepcion.destroy(); graficoMensualRecepcion = null; }
        } else if (seleccion === 'mensual') {
            document.getElementById('reporteProveedores').style.display = 'none';
            if (graficoProveedores) { graficoProveedores.destroy(); graficoProveedores = null; }
            document.getElementById('reporteProductos').style.display = 'none';
            if (graficoProductos) { graficoProductos.destroy(); graficoProductos = null; }
            document.getElementById('reporteMensualRecepcion').style.display = 'block';
        }
        buildParametrosUIRecep();
    }

    // Distinct helper
    function distinctRecep(arr, key){
        const s = new Map();
        (arr||[]).forEach(r=>{ if(r && r[key]!==undefined && r[key]!==null){ const raw=String(r[key]).trim(); const norm=raw.toLowerCase(); if(!s.has(norm)) s.set(norm, raw); }});
        return Array.from(s.values());
    }

    function buildParametrosUIRecep(){
        const cont = document.getElementById('parametrosIndividualesRecep');
        if(!cont) return;
        const tipo = document.getElementById('selectReporteRecepcion').value;
        let html = '';
        if (tipo === 'proveedores'){
            const provs = Array.from(new Set(distinctRecep(recepcionesProveedor,'label'))).sort();
            html += `
            <div class="col-md-3">
                <label>Proveedor</label>
                <select id="paramProvRecep" class="form-select">
                    <option value="">Todos</option>
                    ${provs.map(p=>`<option value="${p}">${p}</option>`).join('')}
                </select>
            </div>`;
        } else if (tipo === 'productos'){
            const prods = Array.from(new Set(distinctRecep(productosRecibidos,'label'))).sort();
            const provs = Array.from(new Set(distinctRecep(productosRecibidos,'proveedor'))).sort();
            html += `
            <div class="col-md-4">
                <label>Producto</label>
                <select id="paramProdRecep" class="form-select">
                    <option value="">Todos</option>
                    ${prods.map(p=>`<option value="${p}">${p}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <label>Proveedor</label>
                <select id="paramProvProdRecep" class="form-select">
                    <option value="">Todos</option>
                    ${provs.map(p=>`<option value="${p}">${p}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <label>Top</label>
                <select id="paramTopNRecep" class="form-select">
                    <option value="0">Todos</option>
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="50">Top 50</option>
                </select>
            </div>`;
        } else if (tipo === 'mensual'){
            const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            html += `
            <div class="col-md-3">
                <label>Mes</label>
                <select id="paramMesRecep" class="form-select">
                    <option value="">Todos</option>
                    ${meses.map((m,i)=>`<option value="${i+1}">${m}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <label>Año</label>
                <input id="paramAnioRecep" type="number" class="form-control" placeholder="Ej: 2025" />
            </div>`;
        }
        cont.innerHTML = html;
    }

    function getParametrosSeleccionadosRecep(){
        return {
            proveedor: document.getElementById('paramProvRecep')?.value || '',
            producto: document.getElementById('paramProdRecep')?.value || '',
            proveedorProducto: document.getElementById('paramProvProdRecep')?.value || '',
            topN: parseInt(document.getElementById('paramTopNRecep')?.value || '0',10) || 0,
            mes: document.getElementById('paramMesRecep')?.value || '',
            anio: document.getElementById('paramAnioRecep')?.value || ''
        };
    }

    function aplicarMesAnioRecep(datos, params){
        if(!datos) return [];
        const m = params.mes ? parseInt(params.mes,10) : null;
        let y = params.anio ? parseInt(params.anio,10) : null;
        const currentYear = new Date().getFullYear();
        if(!(y>0) || y>currentYear) y = null;
        if(!m && !y) return datos;
        const mesesES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return (datos||[]).filter(d=>{
            let date=null;
            const cruda=d.fecha||d.fecha_recepcion||d.created_at||null;
            if(cruda){ const tmp=new Date(cruda); if(!isNaN(tmp)) date=tmp; }
            let mesCampo=d.mes_num||d.mes||null; let anioCampo=d.anio||d.año||d.year||null;
            if(typeof mesCampo==='string'){ const idx=mesesES.indexOf(mesCampo.toLowerCase()); if(idx>=0) mesCampo=idx+1; }
            const mes = date ? (date.getMonth()+1) : (mesCampo? parseInt(mesCampo,10): null);
            const anio = date ? date.getFullYear() : (anioCampo? parseInt(anioCampo,10): null);
            if(m && mes!==m) return false; if(y && anio!==y) return false; return true;
        });
    }

    function generarReportesRecepcion() {
        const tipoGrafica = document.getElementById('tipoGraficaRecepcion').value;
        const inicio = document.getElementById('fechaInicioRecepcion').value;
        const fin = document.getElementById('fechaFinRecepcion').value;
        const seleccion = document.getElementById('selectReporteRecepcion').value;

        // Filtrar por fecha solo donde aplica (datasets con campo fecha)
        const recepcionesProveedorFiltrado = filtrarPorFechas(recepcionesProveedor, inicio, fin);
        const productosRecibidosFiltrado  = filtrarPorFechas(productosRecibidos, inicio, fin);
        const recepcionMensualFiltrado    = recepcionMensual.slice();
        try {
            // Aplicar parámetros dinámicos solo cuando no es 'todos'
            const params = getParametrosSeleccionadosRecep();
            let provFinal = recepcionesProveedorFiltrado;
            let prodFinal = productosRecibidosFiltrado;
            let mensFinal = recepcionMensualFiltrado;

            if (seleccion === 'proveedores' && params.proveedor){
                const norm = params.proveedor.trim().toLowerCase();
                provFinal = provFinal.filter(r=> String(r.label||'').trim().toLowerCase()===norm);
            }
            if (seleccion === 'productos'){
                if (params.producto){
                    const normP = params.producto.trim().toLowerCase();
                    prodFinal = prodFinal.filter(r=> String(r.label||'').trim().toLowerCase()===normP);
                }
                if (params.proveedorProducto){
                    const normProv = params.proveedorProducto.trim().toLowerCase();
                    prodFinal = prodFinal.filter(r=> String(r.proveedor||'').trim().toLowerCase()===normProv);
                }
                // Después de agrupar se aplicará TopN
            }
            if (seleccion === 'mensual'){
                mensFinal = aplicarMesAnioRecep(mensFinal, params);
            }

            // Agregar por label para proveedores y productos
            let provAgg = agruparPorLabel(provFinal).sort((a,b)=> b.value - a.value);
            let prodAgg = agruparPorLabel(prodFinal).sort((a,b)=> b.value - a.value);
            if (seleccion === 'productos' && params.topN>0) {
                prodAgg = prodAgg.slice(0, params.topN);
            }

            if (document.getElementById('reporteProveedores').style.display === 'block'){
                renderReporteRecepcion(provAgg, "label", "value", "graficoProveedores", "tablaProveedores", "Recepciones por Proveedor", tipoGrafica);
            }
            if (document.getElementById('reporteProductos').style.display === 'block'){
                renderReporteRecepcion(prodAgg, "label", "value", "graficoProductos", "tablaProductos", "Productos más Recibidos", tipoGrafica);
            }
            if (document.getElementById('reporteMensualRecepcion').style.display === 'block'){
                // mensFinal ya viene agregado del backend; usar label/value
                renderReporteRecepcion(mensFinal.map(r=>({label: r.label, value: Number(r.value||0)})), "label", "value", "graficoMensualRecepcion", "tablaMensualRecepcion", "Recepciones Mensuales", tipoGrafica);
            }
            document.getElementById('errorMessageRecepcion').style.display = 'none';
        } catch (error) {
            console.error("Error al generar reportes de recepción:", error);
            document.getElementById('errorMessageRecepcion').style.display = 'block';
        }
    }

    // Descargar PDF para RECEPCIÓN con gráfica arriba y tabla abajo
    function descargarPDFRecepcion(contenedorId, nombreArchivo) {
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
            if (contenedorId === 'reporteProveedores') {
                canvasId = 'graficoProveedores';
            } else if (contenedorId === 'reporteProductos') {
                canvasId = 'graficoProductos';
            } else if (contenedorId === 'reporteMensualRecepcion') {
                canvasId = 'graficoMensualRecepcion';
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
            console.error("Error al generar PDF de recepción:", error);
            alert("Error al generar el PDF. Asegúrese de que todas las librerías estén cargadas correctamente.");
        }
    }

    function descargarImagenRecepcion(canvasId, nombreArchivo) {
        try {
            const canvas = document.getElementById(canvasId);
            const link = document.createElement('a');
            link.download = nombreArchivo;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (error) {
            console.error("Error al descargar imagen de recepción:", error);
            alert("Error al descargar la imagen del gráfico.");
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // No establecer fechas por defecto para evitar filtrar; mostrar todos los datos al cargar
        toggleReportesRecepcion();
        buildParametrosUIRecep();
        document.getElementById('generarReporteRecepcionBtn').addEventListener('click', generarReportesRecepcion);
        document.getElementById('selectReporteRecepcion').addEventListener('change', toggleReportesRecepcion);
        // Render inicial con todos los datos (sin filtros)
        generarReportesRecepcion();
    });
</script>

<script>
const proveedoresDisponibles = <?= json_encode($proveedores) ?>;
</script>
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
            $.get('assets/public/ayuda/reporteRecepcion.php')
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

<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>

<button 
    class="btn-ayuda"
    title="Visualizar Ayuda">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>

<script src="assets/javascript/jwt_validator.js"></script>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>