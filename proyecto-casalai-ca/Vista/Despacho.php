<?php
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
 $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 3;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Gestionar Despachos</title>
    
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

<div class="contenedor-tabla">
    <div class="tabla-header" style="width: 75%;">
        <div class="ghost"></div>

        <h3>Lista de Despachos</h3>

        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>CLIENTE</th>
                <th>TIPO DE COMPRA</th>
                <th>ESTATUS</th>
                <th>ACCIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($despachos)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">No se han despachado productos.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($despachos as $despacho): ?>
                    <tr data-id="<?php echo $despacho['id_despachos']; ?>">
                        <td>
                            <span class="campo-numeros">
                                <?= date('d/m/Y', strtotime($despacho['fecha'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-nombres">
                                <?= htmlspecialchars($despacho['nombre_cliente']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-tex-num">
                                <?= htmlspecialchars($despacho['tipocompra']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-rango">
                                <?php echo htmlspecialchars($despacho['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <ul>
                                <button class="btn-detalle"
                                    title="Ver Detalles"
                                    data-id_despacho="<?= htmlspecialchars($despacho['id_despachos']) ?>"
                                    data-fecha="<?= htmlspecialchars($despacho['fecha']) ?>"
                                    data-cliente="<?= htmlspecialchars($despacho['nombre_cliente']) ?>"
                                    data-cedula="<?= htmlspecialchars($despacho['cedula_cliente']) ?>"
                                    data-tipocompra="<?= htmlspecialchars($despacho['tipocompra']) ?>"
                                    data-productos='<?= json_encode($despacho['productos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    <img src="assets/img/eye.svg">
                                </button>
                                <?php if ($despacho['estado'] !== 'Despachado'): ?>
                                    <button
                                        class="btn-marcar"
                                        title="Marcar como Despachado">
                                        <img src="assets/img/check.svg">
                                    </button>
                                <?php endif; ?>
                                <?php if ($despacho['estado'] !== 'Despachado'): ?>
                                <button class="btn-anular"
                                    title="Anular Despacho"
                                    data-id-despacho="<?= htmlspecialchars($despacho['id_despachos']) ?>">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                                <?php endif; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de Detalles -->
<div id="modalDetallesDespacho" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form">Detalles del Despacho</h5>
                <button type="button" class="close" id="cerrarModalDetallesDespacho">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group fila-dato">
                    <label>Fecha:</label>
                    <p id="detalle-fecha-despacho"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Cliente/Cédula:</label>
                    <p id="detalle-cliente-despacho"></p> <p id="detalle-cedula-despacho"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Tipo de compra:</label>
                    <p id="detalle-tipocompra-despacho"></p>
                </div>
                <hr>
                <h6 class="subtitle">Productos</h6>
                <div class="table-responsive contenedor-tabla">
                    <table class="tablaConsultas" id="tablaDetalleProductosDespacho">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Serial</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetalleProductosDespacho">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SOLO REPORTE DE DESPACHOS -->
<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Despachos</h2>
        <p class="texto-p">Visualice y analice los datos de despachos</p>
    </div>

    <!-- Parámetros del Reporte -->
    <div class="parameters-container">
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
    <div class="report-section" id="reporteCliente" style="display:none">
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
            <button class="btn btn-primary btn-download" onclick="descargarPDFDespachos('reporteCliente', 'Reporte_Despachos_Cliente.pdf')">Descargar PDF</button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenDespachos('graficoCliente', 'Grafico_Despachos_Cliente.png')">Descargar Gráfico</button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 4: Despachos por Tipo de Compra -->
    <div class="report-section" id="reporteTipoCompra" style="display:none">
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
            <button class="btn btn-primary btn-download" onclick="descargarPDFDespachos('reporteTipoCompra', 'Reporte_Despachos_TipoCompra.pdf')">Descargar PDF</button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenDespachos('graficoTipoCompra', 'Grafico_Despachos_TipoCompra.png')">Descargar Gráfico</button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<!-- Scripts locales -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>
<script src="assets/javascript/sweetalert2.all.min.js"></script>
<script src="assets/javascript/despacho.js"></script>

<script>
    // ============================
    // SECCIÓN DE DESPACHOS
    // ============================
    let despachoMes = <?= json_encode($despachoMes ?? []) ?>;
    let despachoEstado = <?= json_encode($despachoEstado ?? []) ?>;
    // Datos crudos de la tabla (con fecha y estado) para poder re-agrupar por estado con filtro de fechas
    let despachosRaw = <?= json_encode($despachos ?? []) ?>;
    let graficoEstado = null;
    let graficoMensualDespachos = null;
    let graficoCliente = null;
    let graficoTipoCompra = null;

    // Filtro por rango de fechas (sin recargar)
    function filtrarPorFechas(datos, inicio, fin) {
        const inicioDate = inicio ? new Date(inicio + 'T00:00:00') : null;
        const finDate = fin ? new Date(fin + 'T23:59:59') : null;

        const mesesES = {
            'enero':0,'febrero':1,'marzo':2,'abril':3,'mayo':4,'junio':5,
            'julio':6,'agosto':7,'septiembre':8,'setiembre':8,'octubre':9,'noviembre':10,'diciembre':11
        };

        return (datos || []).filter(d => {
            let f = null;
            // Caso 1: fecha ISO directa
            if (d.fecha || d.fecha_registro || d.created_at) {
                const cruda = d.fecha || d.fecha_registro || d.created_at;
                f = new Date(cruda);
            }
            // Caso 2: nombre de mes (e.g., "Julio") con posible año
            if (!f || isNaN(f)) {
                // Tomar mes desde campo especifico o desde 'label' si es mes
                let mesStr = (d.mes || d.Mes || d.MES || '').toString().trim();
                if (!mesStr && typeof d.label === 'string') {
                    const maybe = d.label.trim().toLowerCase();
                    if (mesesES.hasOwnProperty(maybe)) mesStr = d.label;
                }
                if (mesStr) {
                    const idx = mesesES[mesStr.toLowerCase()];
                    if (idx !== undefined) {
                        // Priorizar año del rango seleccionado
                        const yearFromRange = finDate ? finDate.getFullYear() : (inicioDate ? inicioDate.getFullYear() : null);
                        const year = parseInt(d.anio || d.año || d.year || yearFromRange || new Date().getFullYear(), 10);
                        f = new Date(year, idx, 1);
                    }
                }
            }
            if (!f || isNaN(f)) return true; // si no podemos determinar fecha, no filtramos
            if (inicioDate && f < inicioDate) return false;
            if (finDate && f > finDate) return false;
            return true;
        });
    }

    function getHeaderLabelDespacho(labelKey){
        const headers = { 'mes':'Mes', 'estado':'Estado', 'cliente':'Cliente', 'nombre_cliente':'Cliente', 'tipocompra':'Tipo de Compra', 'label':'Descripción' };
        return headers[labelKey] || 'Item';
    }

    // ========= Parámetros dinámicos =========
    const mesesMap = {
        1:'Enero',2:'Febrero',3:'Marzo',4:'Abril',5:'Mayo',6:'Junio',7:'Julio',8:'Agosto',9:'Septiembre',10:'Octubre',11:'Noviembre',12:'Diciembre'
    };
    function distinct(raw, key){
        const s = new Map();
        (raw||[]).forEach(r=>{ 
            if(r[key]){
                const val = String(r[key]).trim();
                const norm = val.toLowerCase();
                if(!s.has(norm)) s.set(norm, val);
            }
        });
        return Array.from(s.values());
    }
    function yearsFromRaw(raw){
        const s = new Set();
        (raw||[]).forEach(r=>{ const f = r.fecha || r.fecha_despacho; if(f){ const d=new Date(f); if(!isNaN(d)) s.add(d.getFullYear()); }});
        return Array.from(s).sort();
    }
    function monthsList(){ return Array.from({length:12},(_,i)=>({value:i+1,label:mesesMap[i+1]})); }
    function buildParametrosUI(){
        const cont = document.getElementById('parametrosIndividuales');
        const tipo = document.getElementById('selectReporte').value;
        const estados = distinct(despachosRaw,'estado').sort();
        const clientes = distinct(despachosRaw,'nombre_cliente').sort();
        const tipos = distinct(despachosRaw,'tipocompra').sort();
        const anios = yearsFromRaw(despachosRaw);
        let html = '';
        // Solo mostrar parámetros cuando se selecciona UN reporte en específico
        if(tipo === 'reporteEstado'){
            html += `
            <div class="form-group" style="min-width:220px">
                <label>Estado</label>
                <select id="paramEstado" class="form-select" multiple size="${Math.min(4, Math.max(2, estados.length))}">
                    ${estados.map(e=>`<option value="${e}">${e}</option>`).join('')}
                </select>
            </div>`;
        }
        if(tipo === 'reporteCliente'){
            html += `
            <div class="form-group" style="min-width:220px">
                <label>Cliente</label>
                <select id="paramCliente" class="form-select">
                    <option value="">Todos</option>
                    ${clientes.map(c=>`<option value="${c}">${c}</option>`).join('')}
                </select>
            </div>`;
        }
        if(tipo === 'reporteTipoCompra'){
            html += `
            <div class="form-group" style="min-width:220px">
                <label>Tipo de compra</label>
                <select id="paramTipo" class="form-select">
                    <option value="">Todos</option>
                    ${tipos.map(t=>`<option value="${t}">${t}</option>`).join('')}
                </select>
            </div>`;
        }
        if(tipo === 'reporteMensual'){
            html += `
            <div class="form-group" style="min-width:160px">
                <label>Mes</label>
                <select id="paramMes" class="form-select">
                    <option value="">Todos</option>
                    ${monthsList().map(m=>`<option value="${m.value}">${m.label}</option>`).join('')}
                </select>
            </div>
            <div class="form-group" style="min-width:140px">
                <label>Año</label>
                <select id="paramAnio" class="form-select">
                    <option value="">Todos</option>
                    ${anios.map(a=>`<option value="${a}">${a}</option>`).join('')}
                </select>
            </div>`;
        }
        cont.innerHTML = html;
    }
    function getParametrosSeleccionados(){
        const getMulti = (id)=>{ const el=document.getElementById(id); if(!el) return []; return Array.from(el.selectedOptions).map(o=>o.value).filter(Boolean); };
        const estado = getMulti('paramEstado');
        const cliente = document.getElementById('paramCliente')?.value || '';
        const tipo = document.getElementById('paramTipo')?.value || '';
        const mes = document.getElementById('paramMes')?.value || '';
        const anio = document.getElementById('paramAnio')?.value || '';
        return {estado, cliente, tipo, mes, anio};
    }
    function aplicarParametrosRaw(raw, params){
        return (raw||[]).filter(r=>{
            const estadoVal = r.estado ? String(r.estado).trim().toLowerCase() : '';
            const clienteVal = r.nombre_cliente ? String(r.nombre_cliente).trim() : '';
            const tipoVal = r.tipocompra ? String(r.tipocompra).trim().toLowerCase() : '';
            if(params.estado && params.estado.length){
                const estadosNorm = params.estado.map(e=>String(e).trim().toLowerCase());
                if(!estadosNorm.includes(estadoVal)) return false;
            }
            if(params.cliente){
                if(clienteVal !== String(params.cliente).trim()) return false;
            }
            if(params.tipo){
                if(tipoVal !== String(params.tipo).trim().toLowerCase()) return false;
            }
            if(params.mes || params.anio){
                const f = r.fecha || r.fecha_despacho; if(!f) return false; const d=new Date(f); if(isNaN(d)) return false;
                if(params.anio && d.getFullYear().toString() !== params.anio.toString()) return false;
                if(params.mes && (d.getMonth()+1).toString() !== params.mes.toString()) return false;
            }
            return true;
        });
    }
    function agruparMesDesdeRaw(raw){
        const map = new Map();
        (raw||[]).forEach(r=>{
            const f = r.fecha || r.fecha_despacho; if(!f) return; const d=new Date(f); if(isNaN(d)) return;
            const k = d.getFullYear()+"-"+(d.getMonth()+1);
            map.set(k, (map.get(k)||0)+1);
        });
        // Solo mes como etiqueta, Chart mostrado por mes (no por año)
        const res = [];
        map.forEach((v,k)=>{ const parts=k.split('-'); const m=parseInt(parts[1],10); res.push({ mes: mesesMap[m], total: v, label: mesesMap[m] }); });
        // Merge por etiqueta (misma etiqueta para diferente año) sumando
        const red = {};
        res.forEach(x=>{ red[x.label]=(red[x.label]||0)+x.total; });
        return Object.keys(red).map(lbl=>({ label: lbl, mes: lbl, total: red[lbl] }));
    }

    function agruparClienteDesdeRaw(raw){
        const mapa = {};
        (raw||[]).forEach(r=>{ const c = r.nombre_cliente || 'Sin cliente'; mapa[c] = (mapa[c]||0)+1; });
        return Object.keys(mapa).map(k=>({ nombre_cliente:k, total: mapa[k] }));
    }
    function agruparTipoCompraDesdeRaw(raw){
        const mapa = {};
        (raw||[]).forEach(r=>{ const t = r.tipocompra || 'Sin tipo'; mapa[t] = (mapa[t]||0)+1; });
        return Object.keys(mapa).map(k=>({ tipocompra:k, total: mapa[k] }));
    }

    function generarColores(n) {
        return Array.from({length: n}, (_, i) => {
            const hue = (360 / n) * i;
            return `hsl(${hue}, 70%, 60%)`;
        });
    }

    function renderReporteDespachos(datos, labelKey, valueKey, canvasId, tablaId, titulo, tipoGrafica) {
        const tablaEl = document.getElementById(tablaId);
        const canvasEl = document.getElementById(canvasId);
        const ctx = canvasEl.getContext('2d');
        if (!datos || datos.length === 0) {
            tablaEl.innerHTML = `
                <div class="alert alert-warning text-center">
                    No hay datos disponibles para el período seleccionado
                </div>`;
            // Destruir y limpiar el gráfico si existe
            if (canvasId === 'graficoEstado' && graficoEstado) { graficoEstado.destroy(); graficoEstado = null; }
            if (canvasId === 'graficoMensualDespachos' && graficoMensualDespachos) { graficoMensualDespachos.destroy(); graficoMensualDespachos = null; }
            if (canvasId === 'graficoCliente' && graficoCliente) { graficoCliente.destroy(); graficoCliente = null; }
            if (canvasId === 'graficoTipoCompra' && graficoTipoCompra) { graficoTipoCompra.destroy(); graficoTipoCompra = null; }
            ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
            return;
        }

        // **VERSIÓN ROBUSTA: Maneja múltiples nombres de campos**
        const labels = datos.map(d => {
            return d[labelKey] || d.label || d.estado || d.mes || d.nombre_cliente || d.tipocompra || d.cliente || 'Sin nombre';
        });
        
        const data = datos.map(d => {
            const valor = d[valueKey] || d.value || d.total || d.cantidad || 0;
            return parseInt(valor) || 0;
        });

        const total = data.reduce((a, b) => a + b, 0);
        const colores = generarColores(labels.length);

        const header = getHeaderLabelDespacho(labelKey);
        let tablaHtml = `
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>${header}</th>
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

        const ctx2 = document.getElementById(canvasId).getContext('2d');
        if (canvasId === 'graficoEstado' && graficoEstado) {
            graficoEstado.destroy();
        } else if (canvasId === 'graficoMensualDespachos' && graficoMensualDespachos) {
            graficoMensualDespachos.destroy();
        } else if (canvasId === 'graficoCliente' && graficoCliente) {
            graficoCliente.destroy();
        } else if (canvasId === 'graficoTipoCompra' && graficoTipoCompra) {
            graficoTipoCompra.destroy();
        }
        const newChart = new Chart(ctx2, {
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

    // Agrupar por estado a partir de despachos crudos (con fecha) para poder aplicar filtro por rango
    function agruparEstadoDesdeRaw(rawFiltrado){
        const mapa = {};
        (rawFiltrado || []).forEach(r => {
            const est = (r.estado || r.estatus || '').toString() || 'Sin estado';
            mapa[est] = (mapa[est] || 0) + 1;
        });
        return Object.keys(mapa).map(k => ({ estado: k, total: mapa[k] }));
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
        // Construir parámetros visibles según selección
        buildParametrosUI();
    }

    function generarReportesDespachos(applyFilter = true) {
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        const seleccion = document.getElementById('selectReporte').value;
        const inicio = document.getElementById('fechaInicio').value;
        const fin = document.getElementById('fechaFin').value;
        document.getElementById('loadingSpinner').style.display = 'block';
        setTimeout(() => {
            try {
                let mesFiltrado, estadoFiltrado, clienteFiltrado, tipoFiltrado;
                if (applyFilter) {
                    // Aplicar filtros por rango
                    const params = getParametrosSeleccionados();
                    // Dependiendo del reporte seleccionado, aplicamos parámetros SOLO a ese dataset
                    let rawBase = despachosRaw;
                    if (seleccion === 'reporteEstado' && (params.estado?.length)) {
                        rawBase = aplicarParametrosRaw(rawBase, {estado: params.estado});
                    } else if (seleccion === 'reporteCliente' && params.cliente) {
                        rawBase = aplicarParametrosRaw(rawBase, {cliente: params.cliente});
                    } else if (seleccion === 'reporteTipoCompra' && params.tipo) {
                        rawBase = aplicarParametrosRaw(rawBase, {tipo: params.tipo});
                    } else if (seleccion === 'reporteMensual' && (params.mes || params.anio)) {
                        rawBase = aplicarParametrosRaw(rawBase, {mes: params.mes, anio: params.anio});
                    }
                    rawBase = filtrarPorFechas(rawBase, inicio, fin);
                    // Generar datasets por reporte
                    // Para cada reporte, si no es el seleccionado, usar solo filtro de fechas sin parámetros
                    const baseSoloFecha = filtrarPorFechas(despachosRaw, inicio, fin);
                    mesFiltrado = (seleccion === 'reporteMensual') ? agruparMesDesdeRaw(rawBase) : agruparMesDesdeRaw(baseSoloFecha);
                    const tieneFecha = (despachoEstado || []).some(d => d.fecha || d.mes || d.created_at || d.fecha_registro);
                    if (tieneFecha) {
                        const estadoBaseFecha = filtrarPorFechas(despachoEstado, inicio, fin);
                        estadoFiltrado = (seleccion === 'reporteEstado') ? agruparEstadoDesdeRaw(rawBase) : estadoBaseFecha;
                    } else {
                        estadoFiltrado = (seleccion === 'reporteEstado') ? agruparEstadoDesdeRaw(rawBase) : agruparEstadoDesdeRaw(baseSoloFecha);
                    }
                    clienteFiltrado = (seleccion === 'reporteCliente') ? agruparClienteDesdeRaw(rawBase) : agruparClienteDesdeRaw(baseSoloFecha);
                    tipoFiltrado = (seleccion === 'reporteTipoCompra') ? agruparTipoCompraDesdeRaw(rawBase) : agruparTipoCompraDesdeRaw(baseSoloFecha);
                } else {
                    // Sin filtro: usar datasets completos
                    // Para mantener consistencia, generamos mensual desde raw completo
                    mesFiltrado = agruparMesDesdeRaw(despachosRaw);
                    // Si no hay fechas en el agregado de estado, agrupar desde raw completo
                    const tieneFecha = (despachoEstado || []).some(d => d.fecha || d.mes || d.created_at || d.fecha_registro);
                    estadoFiltrado = tieneFecha ? despachoEstado : agruparEstadoDesdeRaw(despachosRaw);
                    clienteFiltrado = agruparClienteDesdeRaw(despachosRaw);
                    tipoFiltrado = agruparTipoCompraDesdeRaw(despachosRaw);
                }
                if (seleccion === 'todos' || seleccion === 'reporteEstado') {
                    renderReporteDespachos(estadoFiltrado, 'estado', 'total', 'graficoEstado', 'tablaEstado', 'Despachos por Estado', tipoGrafica);
                }
                if (seleccion === 'todos' || seleccion === 'reporteMensual') {
                    renderReporteDespachos(mesFiltrado, 'mes', 'total', 'graficoMensualDespachos', 'tablaMensualDespachos', 'Despachos Mensuales', tipoGrafica);
                }
                if (seleccion === 'todos' || seleccion === 'reporteCliente') {
                    renderReporteDespachos(clienteFiltrado, 'nombre_cliente', 'total', 'graficoCliente', 'tablaCliente', 'Despachos por Cliente', tipoGrafica);
                }
                if (seleccion === 'todos' || seleccion === 'reporteTipoCompra') {
                    renderReporteDespachos(tipoFiltrado, 'tipocompra', 'total', 'graficoTipoCompra', 'tablaTipoCompra', 'Despachos por Tipo de Compra', tipoGrafica);
                }
                document.getElementById('errorMessage').style.display = 'none';
            } catch (error) {
                console.error("Error al generar reportes de despachos:", error);
                document.getElementById('errorMessage').style.display = 'block';
            } finally {
                document.getElementById('loadingSpinner').style.display = 'none';
            }
        }, 100);
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
            alert("Error al generar el PDF. Asegúrese de que todas las librerías estén cargadas correctamente.");
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
        // Mostrar todo SIN filtrar al cargar
        toggleReportesDespachos();
        generarReportesDespachos(false);
        // Aplicar filtro solo al dar clic
        document.getElementById('generarReporteBtn').addEventListener('click', function(){ generarReportesDespachos(true); });
        // Cambiar selector solo muestra/oculta secciones, no regenera
        document.getElementById('selectReporte').addEventListener('change', function() {
            toggleReportesDespachos();
        });
        
        // Agregar manejadores para botones de acción
        const botonesDetalle = document.querySelectorAll('.detalle');
        botonesDetalle.forEach(boton => {
            boton.addEventListener('click', function() {
                const idDespacho = this.dataset.id;
                // Abrir modal y poblar detalles
                const modal = document.getElementById('modalDetalle');
                modal.querySelector('.modal-body').innerHTML = '';
                // Simulación de carga de detalles
                const detalles = `Detalles del despacho ${idDespacho}`;
                modal.querySelector('.modal-body').innerHTML = detalles;
                modal.style.display = 'block';
            });
        });

        $(document).ready(function() {
            $('#tablaConsultas').DataTable({
                language: {
                    "url": "assets/public/js/es-ES.json"
                },
                scrollX: true,
                scrollCollapse: true,
                columnDefs: [
                    {
                        targets: 4, // Columna ESTATUS
                        render: function(data, type) {
                            if (type === 'sort') {
                                return (data === 'Por Despachar' || data === 'Por Entregar') ? 0 : 1;
                            }
                            return data;
                        }
                    }
                ],
                order: [
                    [4, 'asc'], // Primero pendientes
                    [0, 'asc']  // Luego por fecha (antiguas primero)
                ],
                pageLength: 10,
                initComplete: function () {
                    var api = this.api();
                    api.columns.adjust();
                }
            });
        });
    });
</script>
<script>
// Handlers de acciones en tabla (detallar, marcar, anular)
$(function() {
  // Detallar
  $(document).on('click', '.btn-detalle', function() {
    const $btn = $(this);
    $('#detalle-fecha-despacho').text(new Date($btn.data('fecha')).toLocaleDateString());
    $('#detalle-cliente-despacho').text($btn.data('cliente'));
    $('#detalle-cedula-despacho').text($btn.data('cedula'));
    $('#detalle-tipocompra-despacho').text($btn.data('tipocompra'));

    let productos = [];
    const raw = $btn.attr('data-productos');
    try { productos = raw ? JSON.parse(raw) : []; } catch(e) { productos = []; }
    const $tbody = $('#tbodyDetalleProductosDespacho');
    let prodHtml = '';
    (Array.isArray(productos) ? productos : []).forEach(p => {
      prodHtml += `
        <tr>
          <td><span class="campo-numeros">${p.codigo ?? ''}</span></td>
          <td><span class="campo-nombres">${p.producto ?? ''}</span></td>
          <td><span class="campo-nombres">${p.modelo ?? ''}</span></td>
          <td><span class="campo-nombres">${p.marca ?? ''}</span></td>
          <td><span class="campo-numeros">${p.serial ?? ''}</span></td>
          <td><span class="campo-numeros">${p.cantidad ?? 0}</span></td>
        </tr>`;
    });
    $tbody.html(prodHtml);
    document.getElementById('modalDetallesDespacho').classList.add('mostrar');
  });

  // Marcar como Despachado / Por Despachar (toggle)
  $(document).on('click', '.btn-marcar', function() {
    const $row = $(this).closest('tr');
    const id = $row.data('id');
    const estadoActual = $row.find('td:nth-child(4) .campo-rango').text().trim();

    $.post('?pagina=despacho', {
      accion: 'cambiar_estado_despacho',
      id: id,
      estado_actual: estadoActual
    }).done(resp => {
      try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e) {}
      if (resp && resp.status === 'success') {
        $row.find('td:nth-child(4) .campo-rango').text(resp.nuevo_estado);
        if (resp.nuevo_estado === 'Despachado') {
          $row.find('.btn-marcar').remove();
          $row.find('.btn-anular').remove();
        }
        const tabla = $('#tablaConsultas').DataTable();
        tabla.order([[4, 'asc'], [0, 'asc']]).draw();
        Swal.fire('Estado cambiado', 'Nuevo estado: ' + resp.nuevo_estado, 'success');

      } else {
        Swal.fire('Error', (resp && resp.message) || 'No se pudo cambiar el estado', 'error');
      }
    }).fail(() => Swal.fire('Error', 'Error de red al cambiar estado.', 'error'));
  });

  // Anular (con SweetAlert, como OrdenDespacho)
  $(document).on('click', '.btn-anular', function(e) {
    e.preventDefault();
    const $row = $(this).closest('tr');
    const id = $(this).data('idDespacho') || $row.data('id');
    Swal.fire({
      title: '¿Está seguro?',
      text: '¡No podrás revertir esto!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sí, anular!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('?pagina=despacho', { accion: 'anular', id_despachos: id })
          .done(resp => {
            try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e) {}
            if (resp && resp.status === 'success') {
              Swal.fire('Anulado!', 'El despacho ha sido anulado.', 'success');
              const tabla = $('#tablaConsultas').DataTable();
              tabla.row($row).remove().draw();
            } else {
              Swal.fire('Error', (resp && resp.message) || 'No se pudo anular el despacho', 'error');
            }
          })
          .fail(() => Swal.fire('Error', 'Error de red al anular el despacho.', 'error'));
      }
    });
  });

  // Cerrar modal detalles
  $('#cerrarModalDetallesDespacho').on('click', function(){ document.getElementById('modalDetallesDespacho').classList.remove('mostrar'); });
  // Cerrar al hacer clic fuera del contenido
  window.addEventListener('click', (e) => {
    const modal = document.getElementById('modalDetallesDespacho');
    if (e.target === modal) {
      modal.classList.remove('mostrar');
    }
  });
});
</script>
    <button 
        class="btn-grafica"
        title="Visualizar Reportes"
        onclick="window.location.href='?pagina=reporteVentas'">
        <img src="assets/img/grafic.png" alt="Reportes" width="30" height="30">
    </button>
    
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