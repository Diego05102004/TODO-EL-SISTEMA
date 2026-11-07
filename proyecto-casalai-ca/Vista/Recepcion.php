<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 2;
if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Gestionar Recepcion</title>
</head>
<style>
    #modalp .tablaConsultas tr.agregado {
        background: #d1e7dd !important;
        color: #155724;
        font-weight: bold;
        cursor: not-allowed;
    }
    #modalp .tablaConsultas tr:hover:not(.agregado) {
        background: #f1f3f5;
        cursor: pointer;
    }
    .tr-seleccionado {
        background-color: #d4edda !important;
        border-left: 4px solid #28a745 !important;
        transition: all 0.3s ease;
    }
    .tr-seleccionado:hover {
        background-color: #c3e6cb !important;
    }
    .agregado {
        background-color: #f8d7da !important;
        opacity: 0.6;
    }
    
    /* ESTILOS GUARDIÁN IA */
    .guardian-ia-section {
        border: 2px dashed #667eea;
        padding: 15px;
        border-radius: 8px;
        background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);
        margin-bottom: 20px;
    }
    .guardian-ia-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .guardian-ia-header h6 {
        margin: 0;
        font-weight: 600;
    }
    .guardian-ia-header p {
        margin: 5px 0 0 0;
        opacity: 0.9;
        font-size: 0.9em;
    }
    .btn-analizar-ia {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-analizar-ia:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    .btn-analizar-ia:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .preview-factura-img {
        max-width: 100%;
        max-height: 200px;
        border: 2px solid #667eea;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .panel-resultados-ia {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .spinner-ia {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .verificacion-exitosa {
        border-left: 4px solid #28a745 !important;
        background: #d4edda !important;
    }
    .verificacion-bloqueada {
        border-left: 4px solid #dc3545 !important;
        background: #f8d7da !important;
    }
    .verificacion-advertencia {
        border-left: 4px solid #ffc107 !important;
        background: #fff3cd !important;
    }
</style>

<body class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
<?php include 'NewNavBar.php'; ?>

<div class="modal fade modal-registrar" id="registrarRecepcionModal" tabindex="-1" role="dialog" aria-labelledby="registrarRecepcionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">

            <form id="ingresarRecepcion" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Recepción" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="registrarRecepcionModalLabel">Incluir Recepción</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    <input type="hidden" id="ia_verificada" name="ia_verificada" value="false">
                    
                    <!-- SECCIÓN IA: FOTO DE FACTURA Y VERIFICACIÓN -->
                    <div class="alert alert-info mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                        <h6 class="mb-2"><i class="fas fa-robot"></i> <strong>Guardián IA - Verificación Asistida</strong></h6>
                        <p class="mb-2 small">Suba una foto de la factura física para que el sistema verifique la coherencia de los datos antes del registro.</p>
                    </div>
                    
                    <div class="grupo-form mb-3" style="border: 2px dashed #667eea; padding: 15px; border-radius: 8px; background: #f8f9ff;">
                        <div class="grupo-interno" style="flex: 1;">
                            <label for="foto-factura-ia"><i class="fas fa-camera"></i> Foto de la Factura del Proveedor</label>
                            <input type="file" class="control-form" id="foto-factura-ia" name="foto_factura" accept="image/*,.pdf" />
                            <small class="text-muted"> La imagen se analizará automáticamente con IA al subirla. Formatos: JPG, PNG, PDF. Máx: 5MB</small>
                        </div>
                    </div>
                    
                    <!-- Preview de imagen -->
                    <div id="preview-factura-container" class="mb-3 d-none">
                        <label>Vista previa:</label>
                        <img id="preview-factura-img" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;" />
                    </div>
                    
                    <!-- Panel de resultados del IA -->
                    <div id="panel-resultados-ia" class="d-none mb-3"></div>
                    
                    <!-- Alertas del sistema IA -->
                    <div id="alertas-recepcion-ia"></div>
                    
                    <hr style="border-top: 2px solid #667eea; margin: 20px 0;">
                    
                    <div class="grupo-form">
                        <div class="grupo-interno">
                            <label for="correlativo">N° de Factura del Proveedor</label>
                            <input type="text" placeholder="N° de Factura" class="control-form" maxlength="6" id="correlativo" name="correlativo" />
                            <span class="span-value" id="scorrelativo"></span>
                        </div>
                        <div class="grupo-interno">
                            <label for="proveedor">Proveedor</label>
                            <select class="form-select" name="proveedor" id="proveedor">
                                <option value="" hidden>Seleccione el Proveedor</option>
                                <?php
                                foreach ($proveedores as $proveedor) {
                                    echo "<option value='" . $proveedor['id_proveedor'] . "'>" . $proveedor['nombre_proveedor'] . "</option>";
                                } ?>
                            </select>
                            <span class="span-value" id="sproveedor"></span>
                        </div>
                    </div>
                    <div class="envolver-form">
                        <input class="" type="text" id="codigoproducto" name="codigoproducto" style="display:none"/>
                        <input class="" type="text" id="idproducto" name="idproducto" style="display:none"/>
                        <button type="button" class="btn-listado btn-primary" id="listado" name="listado">Lista de Productos</button>
                    </div>
                    <div class="row">
                        <div class="col"><hr /></div>
                    </div>
                    <div class="table-responsive card shadow">
                        <table class="tabla" id="tablarecepcion">
                            <thead>
                                <tr>
                                    <th>Acción</th>
                                    <th style="display:none">Cl</th>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>modelo</th>
                                    <th>Marca</th>
                                    <th>Serial</th>
                                    <th>Costo por C/U</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="recepcion1"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="boton-form btn-primary" type="submit">Registrar</button>
                    <button class="boton-reset btn-primary" type="reset">Limpiar</button>
                </div>
            </form>

            <div class="modal fade" tabindex="-1" role="dialog" id="modalp">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="titulo-form">Listado de productos</h5>
                            <button type="button" class="close-2" data-bs-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="tablaConsultas">
                                <thead class="text-center">
                                    <tr>
                                        <th style="display:none">Id</th>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>modelo</th>
                                        <th>Marca</th>
                                        <th>Serial</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center" id="listadop"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="contenedor-tabla">
    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>Lista de Recepciones</h3>
        <div class="ghost"></div>
    </div>
    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>N° DE FACTURA <br> DEL PROVEEDOR</th>
                <th>PROVEEDOR</th>
                <th>COSTO DE INVERSIÓN</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recepciones)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No se han registrado recepciones.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recepciones as $recepcion): ?>
                    <tr data-id="<?= htmlspecialchars($recepcion['correlativo']) ?>">
                        <td>
                            <span class="campo-numeros">
                                <?= date('d/m/Y', strtotime($recepcion['fecha'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-numeros">
                                <?= htmlspecialchars($recepcion['correlativo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-nombres">
                                <?= htmlspecialchars($recepcion['nombre_proveedor']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-numeros">
                                <?= number_format($recepcion['costo_inversion'], 2, ',', '.') ?>
                            </span>
                        </td>
                        <td>
                            <ul>
                                <button class="btn-detalle"
                                    title="Ver Detalles"
                                    data-id_recepcion="<?= htmlspecialchars($recepcion['id_recepcion']) ?>"
                                    data-fecha="<?= htmlspecialchars($recepcion['fecha']) ?>"
                                    data-correlativo="<?= htmlspecialchars($recepcion['correlativo']) ?>"
                                    data-proveedor="<?= htmlspecialchars($recepcion['nombre_proveedor']) ?>"
                                    data-costo_inversion="<?= htmlspecialchars($recepcion['costo_inversion']) ?>">
                                    <img src="assets/img/eye.svg">
                                </button>
                                <?php if ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario'): ?>
                                <button class="btn-anular"
                                    title="Anular Recepción"
                                    data-correlativo="<?= htmlspecialchars($recepcion['correlativo']) ?>">
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

<div id="modalDetallesRecepcion" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form">Detalles de la Recepción</h5>
                <button type="button" class="close" id="cerrarModalDetallesRecepcion">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group fila-dato">
                    <label>Fecha:</label>
                    <p id="detalle-fecha"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>N° de Factura:</label>
                    <p id="detalle-correlativo"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Proveedor:</label>
                    <p id="detalle-proveedor"></p>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="tablaConsultas" id="tablaDetalleProductosRecepcion">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Serial</th>
                                <th>Cantidad</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetalleProductosRecepcion"></tbody>
                    </table>
                </div>
                <div style="text-align:right; margin-top:16px;">
                    <label style="font-weight:bold;">Costo Total de la Inversión:</label>
                    <span id="detalle-costo-inversion" style="font-size:18px; color:#1f66df;"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Recepción</h2>
        <p class="texto-p">Seleccione y genere los reportes que desea visualizar</p>
    </div>
    <div class="report-selector">
        <label for="selectReporteRecepcion" class="title-select">Seleccionar Reporte:</label><br>
        <select id="selectReporteRecepcion" class="selector-reporte">
            <option value="todos">Todos los Reportes</option>
            <option value="proveedores">Recepciones por Proveedor</option>
            <option value="productos">Productos más Recibidos</option>
            <option value="mensual">Recepciones Mensuales</option>
        </select>
    </div>
    <div class="parameters-container">
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
    <div id="errorMessageRecepcion" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

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
            <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteProveedores','Reporte_Recepciones_Proveedores.pdf')">Descargar PDF</button>
            <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoProveedores','Grafico_Recepciones_Proveedores.png')">Descargar Gráfico</button>
        </div>
    </div>
<div class="divider"></div>
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
            <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteProductos','Reporte_Recepciones_Productos.pdf')">Descargar PDF</button>
            <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoProductos','Grafico_Recepciones_Productos.png')">Descargar Gráfico</button>
        </div>
    </div>
<div class="divider"></div>
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
            <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteMensualRecepcion','Reporte_Recepciones_Mensual.pdf')">Descargar PDF</button>
            <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoMensualRecepcion','Grafico_Recepciones_Mensual.png')">Descargar Gráfico</button>
        </div>
    </div>
</div>

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
        // Destruir gráfico previo si no hay datos
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
                tablaEl.innerHTML = `<div class="alert alert-warning text-center">📭 No hay datos disponibles para el período seleccionado</div>`;
            }
            const canvas = document.getElementById(canvasId);
            if (canvas) { const ctx = canvas.getContext('2d'); ctx && ctx.clearRect(0,0,canvas.width,canvas.height); }
            return;
        }

        const labels = datos.map(d => (d[labelKey] ?? d.label ?? 'Sin nombre'));
        const data = datos.map(d => Number(d[valueKey] ?? d.value ?? 0) || 0);
        const total = data.reduce((a, b) => a + b, 0);
        const colores = generarColores(labels.length);
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
                    <tbody>`;

        labels.forEach((nombre, i) => {
            const pct = total > 0 ? ((data[i] / total) * 100).toFixed(2) : 0;
            tablaHtml += `<tr><td>${nombre}</td><td>${data[i].toLocaleString()}</td><td>${pct}%</td></tr>`;
        });

        tablaHtml += `</tbody>
                    <tfoot class="table-active">
                        <tr><th>Total</th><th>${total.toLocaleString()}</th><th>100%</th></tr>
                    </tfoot>
                </table>
            </div>`;

        document.getElementById(tablaId).innerHTML = tablaHtml;
        const ctx = document.getElementById(canvasId).getContext('2d');

        if (canvasId === 'graficoProveedores' && graficoProveedores) graficoProveedores.destroy();
        else if (canvasId === 'graficoProductos' && graficoProductos) graficoProductos.destroy();
        else if (canvasId === 'graficoMensualRecepcion' && graficoMensualRecepcion) graficoMensualRecepcion.destroy();

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
                    legend: { display: tipoGrafica !== 'line', position: 'bottom' },
                    title: { display: true, text: titulo, font: { size: 16, weight: 'bold' } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const raw = context.raw;
                                let value = 0;
                                if (typeof context.parsed === 'number') value = context.parsed;
                                else if (context.parsed && typeof context.parsed === 'object') value = Number((context.parsed.x ?? context.parsed.y ?? raw ?? 0));
                                else value = Number(raw ?? 0);
                                const nums = (context.dataset.data || []).map(d => {
                                    if (typeof d === 'number') return d;
                                    if (d && typeof d === 'object') return Number(d.x ?? d.y ?? 0);
                                    const n = Number(d ?? 0); return isNaN(n) ? 0 : n;
                                });
                                const total = nums.reduce((a,b)=> a + (isNaN(b)?0:b), 0);
                                const pct = total > 0 ? ` (${((value/total)*100).toFixed(1)}%)` : '';
                                return `${value.toLocaleString(undefined,{maximumFractionDigits:2})}${pct}`;
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
        const recepcionesProveedorFiltrado = filtrarPorFechas(recepcionesProveedor, inicio, fin);
        const productosRecibidosFiltrado  = filtrarPorFechas(productosRecibidos, inicio, fin);
        const recepcionMensualFiltrado    = recepcionMensual.slice();
        try {
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
            }
            if (seleccion === 'mensual') mensFinal = aplicarMesAnioRecep(mensFinal, params);
            let provAgg = agruparPorLabel(provFinal).sort((a,b)=> b.value - a.value);
            let prodAgg = agruparPorLabel(prodFinal).sort((a,b)=> b.value - a.value);
            if (seleccion === 'productos' && params.topN>0) prodAgg = prodAgg.slice(0, params.topN);
            if (document.getElementById('reporteProveedores').style.display === 'block'){
                renderReporteRecepcion(provAgg, "label", "value", "graficoProveedores", "tablaProveedores", "Recepciones por Proveedor", tipoGrafica);
            }
            if (document.getElementById('reporteProductos').style.display === 'block'){
                renderReporteRecepcion(prodAgg, "label", "value", "graficoProductos", "tablaProductos", "Productos más Recibidos", tipoGrafica);
            }
            if (document.getElementById('reporteMensualRecepcion').style.display === 'block'){
                renderReporteRecepcion(mensFinal.map(r=>({label: r.label, value: Number(r.value||0)})), "label", "value", "graficoMensualRecepcion", "tablaMensualRecepcion", "Recepciones Mensuales", tipoGrafica);
            }
            document.getElementById('errorMessageRecepcion').style.display = 'none';
        } catch (error) {
            console.error("Error al generar reportes de recepción:", error);
            document.getElementById('errorMessageRecepcion').style.display = 'block';
        }
    }

    function descargarPDFRecepcion(contenedorId, nombreArchivo) {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            const reporte = document.getElementById(contenedorId);
            const titulo = reporte.querySelector('h3')?.textContent || "Reporte de Recepción";
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(titulo, 20, 20);
            let canvasId = contenedorId === 'reporteProveedores' ? 'graficoProveedores' : (contenedorId === 'reporteProductos' ? 'graficoProductos' : 'graficoMensualRecepcion');
            const canvas = document.getElementById(canvasId);
            if (canvas) {
                const imgData = canvas.toDataURL('image/png');
                const pageWidth = doc.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 40;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                doc.addImage(imgData, 'PNG', 20, 30, imgWidth, imgHeight);
                let currentY = 30 + imgHeight + 10;
                const tablaElement = reporte.querySelector('.chart-table');
                if (tablaElement) {
                    html2canvas(tablaElement, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(tablaCanvas => {
                        const tablaImgData = tablaCanvas.toDataURL('image/png');
                        const tablaImgWidth = pageWidth - 40;
                        const tablaImgHeight = (tablaCanvas.height * tablaImgWidth) / tablaCanvas.width;
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
            alert("Error al generar el PDF.");
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
        toggleReportesRecepcion();
        buildParametrosUIRecep();
        document.getElementById('generarReporteRecepcionBtn').addEventListener('click', generarReportesRecepcion);
        document.getElementById('selectReporteRecepcion').addEventListener('change', toggleReportesRecepcion);
        generarReportesRecepcion();
    });
</script>

<script>
const proveedoresDisponibles = <?= json_encode($proveedores) ?>;
const productosDisponibles = <?= json_encode($productos) ?>;

function crearBloqueProducto(productosDisponibles) {
    return `
        <div class="row mb-2 grupo-producto">
            <div class="col-md-5">
                <label>Producto</label>
                <select class="form-control" name="productos[]">
                    ${productosDisponibles.map(prod => `<option value="${prod.id_producto}">${prod.nombre_producto}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <label>Cantidad</label>
                <input type="number" class="form-control" name="cantidades[]" value="1" min="1">
            </div>
            <div class="col-md-2">
                <label>Costo</label>
                <input type="number" class="form-control" name="costos[]" value="0" min="0" step="0.01">
                <input type="hidden" name="iddetalles[]" value="">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-eliminar-producto">Eliminar Producto</button>
            </div>
        </div>`;
}

$(document).on('click', '#btnAgregarProducto', function () {
    $('#contenedorDetalles').append(crearBloqueProducto(productosDisponibles));
});

$(document).on('click', '.btn-eliminar-producto', function () {
    $(this).closest('.grupo-producto').remove();
});

function eliminarBackdrop() {
    const backdrops = document.getElementsByClassName('modal-backdrop');
    while (backdrops.length > 0) {
        backdrops[0].parentNode.removeChild(backdrops[0]);
    }
}

function eliminarBackdropSeguro() {
    setTimeout(() => {
        const modalesAbiertos = document.querySelectorAll('.modal.show');
        if (modalesAbiertos.length === 0) {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.parentNode.removeChild(backdrop));
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        }
    }, 50);
}

$(document).on('click', '.modal .close', function() {
    $(this).closest('.modal').modal('hide');
    eliminarBackdropSeguro();
});
</script>

<!-- INTEGRACIÓN GUARDIÁN IA - FASE 1: VERIFICACIÓN ASISTIDA -->
<script src="microservicio/javascript/asistente_recepcion.js"></script>
<script>
$(document).ready(function() {
    // Estado de verificación IA
    let iaVerificada = false;
    let iaFacturaId = null;
    
    // Configuración del Asistente IA
    const asistenteIA = new AsistenteRecepcionIA({
        apiUrl: 'http://localhost:8000',
        authToken: 'V2ULrTMDqWierxYc7i71JKpewJozqORhdI_JbmbsTVo',
        debug: true,
        selectores: {
            inputImagen: '#foto-factura-ia',
            previewImagen: '#preview-factura-img',
            panelResultados: '#panel-resultados-ia',
            alertasContainer: '#alertas-recepcion-ia',
            formulario: '#ingresarRecepcion'
        },
        callbacks: {
            onExtraccionExitosa: function(resultado) {
                console.log('✅ Extracción exitosa:', resultado);
                iaFacturaId = resultado.factura_id;
                
                // Auto-llenar campos si hay datos extraídos
                if (resultado.numero_factura && !$('#correlativo').val()) {
                    $('#correlativo').val(resultado.numero_factura);
                }
                
                // Notificar éxito de extracción con SweetAlert
                Swal.fire({
                    icon: 'info',
                    title: '🤖 Factura Analizada',
                    html: `<b>N° Factura detectado:</b> ${resultado.numero_factura || 'No detectado'}<br>
                           <b>Proveedor:</b> ${resultado.nombre_proveedor || 'No detectado'}<br>
                           <b>Confianza:</b> ${(resultado.confianza_promedio * 100).toFixed(1)}%<br><br>
                           Complete los datos del formulario y presione <b>"Verificar y Registrar"</b> para validar.`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#667eea'
                });
            },
            onError: function(error) {
                console.error('❌ Error IA:', error);
                iaVerificada = false;
                actualizarEstadoBotonRegistrar();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error del Guardián IA',
                    text: 'No se pudo analizar la factura. Verifique que el microservicio esté activo.',
                    confirmButtonColor: '#dc3545'
                });
            }
        }
    });

    // Función para verificar si el formulario está completo
    function formularioCompleto() {
        const correlativo = $('#correlativo').val().trim();
        const proveedor = $('#proveedor').val();
        const productos = $('#tablarecepcion tbody tr').length;
        
        return correlativo !== '' && proveedor !== '' && productos > 0;
    }

    // Función para actualizar estado del botón registrar
    function actualizarEstadoBotonRegistrar() {
        const formularioOk = formularioCompleto();
        const imagenCargada = $('#foto-factura-ia')[0].files.length > 0;
        
        // Si hay imagen, requiere verificación IA exitosa
        // Si no hay imagen, solo requiere formulario completo
        const puedeRegistrar = formularioOk && (!imagenCargada || iaVerificada);
        
        const $btnRegistrar = $('#ingresarRecepcion button[type="submit"]');
        $btnRegistrar.prop('disabled', !puedeRegistrar);
        
        // Cambiar texto según estado
        if (imagenCargada && !iaVerificada && formularioOk) {
            $btnRegistrar.html('<i class="fas fa-shield-alt"></i> Verificar con IA');
        } else if (puedeRegistrar) {
            $btnRegistrar.html('<i class="fas fa-check"></i> Registrar Recepción');
        }
    }

    // Monitorear cambios en el formulario
    $('#ingresarRecepcion').on('input change', 'input, select', function() {
        actualizarEstadoBotonRegistrar();
    });
    
    // Observar cambios en la tabla de productos
    const observer = new MutationObserver(function() {
        actualizarEstadoBotonRegistrar();
    });
    observer.observe(document.getElementById('recepcion1'), { childList: true, subtree: true });

    // ANÁLISIS AUTOMÁTICO + COMPARACIÓN INMEDIATA al subir imagen
    $('#foto-factura-ia').on('change', async function() {
        const archivo = this.files[0];
        
        if (!archivo) {
            $('#preview-factura-container').addClass('d-none');
            iaVerificada = false;
            iaFacturaId = null;
            actualizarEstadoBotonRegistrar();
            return;
        }
        
        // Validar tamaño (5MB máximo)
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'warning',
                title: 'Archivo muy grande',
                text: 'El archivo debe ser menor a 5MB.',
                confirmButtonColor: '#ffc107'
            });
            this.value = '';
            iaVerificada = false;
            actualizarEstadoBotonRegistrar();
            return;
        }
        
        // Mostrar preview
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#preview-factura-img').attr('src', e.target.result);
            $('#preview-factura-container').removeClass('d-none');
        };
        reader.readAsDataURL(archivo);
        
        // PASO 1: Extracción con loading
        Swal.fire({
            title: '🤖 Guardián IA Analizando...',
            html: 'Extrayendo texto de la factura con OCR + PLN',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Ejecutar análisis de extracción
        const resultadoExtraer = await asistenteIA.extraerDesdeImagen(archivo);
        
        if (!resultadoExtraer.exito) {
            Swal.close();
            iaVerificada = false;
            iaFacturaId = null;
            actualizarEstadoBotonRegistrar();
            return;
        }
        
        iaFacturaId = resultadoExtraer.data.factura_id;
        $('#panel-resultados-ia').removeClass('d-none');
        
        // PASO 2: Si hay datos en el formulario, comparar inmediatamente
        const hayDatosFormulario = $('#correlativo').val().trim() !== '' || 
                                   $('#proveedor').val() !== '' ||
                                   $('#tablarecepcion tbody tr').length > 0;
        
        if (hayDatosFormulario) {
            Swal.update({
                title: '🔍 Comparando datos...',
                html: 'Verificando coherencia entre factura y formulario'
            });
            
            // Recopilar datos actuales del formulario
            const productos = [];
            $('#tablarecepcion tbody tr').each(function() {
                const $row = $(this);
                productos.push({
                    nombre: $row.find('td:eq(3)').text().trim(),
                    modelo: $row.find('td:eq(4)').text().trim(),
                    marca: $row.find('td:eq(5)').text().trim(),
                    serial: $row.find('td:eq(6)').text().trim(),
                    costo: parseFloat($row.find('td:eq(7) input').val()) || 0,
                    cantidad: parseInt($row.find('td:eq(8) input').val()) || 1
                });
            });
            
            const datosFormulario = {
                numero_factura: $('#correlativo').val(),
                nombre_proveedor: $('#proveedor option:selected').text(),
                productos: productos
            };
            
            // Comparar con IA
            const resultadoVerificar = await asistenteIA.verificarCoherencia(iaFacturaId, datosFormulario);
            
            Swal.close();
            
            if (resultadoVerificar.exito) {
                // ✅ Todo coincide - mostrar éxito y permitir registro
                iaVerificada = true;
                
                Swal.fire({
                    icon: 'success',
                    title: '✅ Verificación Exitosa',
                    html: `<b>Factura analizada:</b> ${resultadoExtraer.data.numero_factura || 'No detectado'}<br>
                           <b>Proveedor detectado:</b> ${resultadoExtraer.data.nombre_proveedor || 'No detectado'}<br>
                           <b>Confianza:</b> ${(resultadoExtraer.data.confianza_promedio * 100).toFixed(1)}%<br><br>
                           <span style="color:green;">✓ Los datos coinciden con la factura</span>`,
                    confirmButtonText: 'Perfecto, continuar',
                    confirmButtonColor: '#28a745',
                    timer: 5000,
                    timerProgressBar: true
                });
            } else {
                // ❌ Hay discrepancias - mostrar inmediatamente
                const hayCriticas = resultadoVerificar.discrepancias.some(d => d.severidad === 'CRITICA');
                
                // Construir mensaje de discrepancias
                let mensajeHtml = '<div style="text-align:left; max-height:300px; overflow-y:auto;">';
                resultadoVerificar.discrepancias.forEach(d => {
                    const icono = d.severidad === 'CRITICA' ? '🔴' : 
                                 d.severidad === 'ALTA' ? '🟠' : 
                                 d.severidad === 'MEDIA' ? '🟡' : '🟢';
                    mensajeHtml += `<div style="margin:8px 0; padding:10px; background:#f8f9fa; border-radius:4px; border-left:4px solid ${d.severidad === 'CRITICA' ? '#dc3545' : d.severidad === 'ALTA' ? '#ffc107' : '#17a2b8'};">
                        <b>${icono} ${d.campo}</b> <span style="font-size:0.85em; padding:2px 8px; border-radius:4px; background:${d.severidad === 'CRITICA' ? '#dc3545' : d.severidad === 'ALTA' ? '#ffc107' : '#17a2b8'}; color:${d.severidad === 'CRITICA' ? 'white' : 'black'};">${d.severidad}</span><br>
                        <small style="color:#666;">📄 Factura: <b>${d.valor_factura}</b></small><br>
                        <small style="color:#666;">📝 Formulario: <b>${d.valor_formulario}</b></small>
                    </div>`;
                });
                mensajeHtml += '</div>';
                
                if (hayCriticas) {
                    // Bloquear - discrepancias críticas
                    iaVerificada = false;
                    
                    await Swal.fire({
                        icon: 'error',
                        title: '🔴 Discrepancias Críticas Detectadas',
                        html: `<div style="margin-bottom:15px;">Se encontraron diferencias importantes entre la factura y los datos ingresados:</div>${mensajeHtml}<div style="margin-top:15px; padding:10px; background:#f8d7da; border-radius:4px; color:#721c24;"><b>⚠️ Debe corregir estos datos antes de poder registrar la recepción.</b></div>`,
                        confirmButtonText: 'Entendido, corregiré',
                        confirmButtonColor: '#dc3545',
                        width: '650px'
                    });
                } else {
                    // Advertencia - permitir continuar con confirmación
                    const confirmacion = await Swal.fire({
                        icon: 'warning',
                        title: '⚠️ Diferencias Encontradas',
                        html: `<div style="margin-bottom:15px;">La factura difiere de los datos ingresados:</div>${mensajeHtml}<div style="margin-top:15px;">¿Desea continuar de todos modos?</div>`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Corregir primero',
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#28a745',
                        width: '650px'
                    });
                    
                    iaVerificada = confirmacion.isConfirmed;
                }
            }
        } else {
            // No hay datos para comparar aún
            Swal.close();
            
            Swal.fire({
                icon: 'info',
                title: '📄 Factura Analizada',
                html: `<b>N° Factura detectado:</b> ${resultadoExtraer.data.numero_factura || 'No detectado'}<br>
                       <b>Proveedor:</b> ${resultadoExtraer.data.nombre_proveedor || 'No detectado'}<br>
                       <b>Confianza:</b> ${(resultadoExtraer.data.confianza_promedio * 100).toFixed(1)}%<br><br>
                       Complete el formulario y la comparación automática se realizará al registrar.`,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#667eea'
            });
        }
        
        actualizarEstadoBotonRegistrar();
    });

    // SUBMIT del formulario con verificación IA completa
    $('#ingresarRecepcion').on('submit', async function(e) {
        e.preventDefault();
        
        const archivo = $('#foto-factura-ia')[0].files[0];
        
        // Validar formulario completo
        if (!formularioCompleto()) {
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Complete todos los campos requeridos.',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }
        
        // Si hay imagen pero no se analizó, analizar automáticamente primero
        if (archivo && !iaFacturaId) {
            Swal.fire({
                title: '🤖 Analizando factura...',
                html: 'Procesando imagen automáticamente',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Analizar automáticamente
            const resultado = await asistenteIA.extraerDesdeImagen(archivo);
            
            if (!resultado.exito) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de análisis',
                    text: 'No se pudo analizar la factura. Intente nuevamente.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }
            
            iaFacturaId = resultado.data.factura_id;
            $('#panel-resultados-ia').removeClass('d-none');
            Swal.close();
        }
        
        // Si no hay imagen, permitir registro directo con confirmación
        if (!archivo) {
            const confirmacion = await Swal.fire({
                icon: 'question',
                title: '¿Continuar sin factura?',
                text: 'No ha subido una imagen de la factura para verificación. ¿Desea continuar de todos modos?',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545'
            });
            
            if (!confirmacion.isConfirmed) {
                return false;
            }
            
            this.submit();
            return true;
        }
        
        // VERIFICACIÓN COMPLETA CON IA (con imagen)
        Swal.fire({
            title: '🔍 Verificando con Guardián IA...',
            html: 'Comparando datos del formulario con la factura',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Recopilar datos del formulario
        const productos = [];
        $('#tablarecepcion tbody tr').each(function() {
            const $row = $(this);
            productos.push({
                nombre: $row.find('td:eq(3)').text().trim(),
                modelo: $row.find('td:eq(4)').text().trim(),
                marca: $row.find('td:eq(5)').text().trim(),
                serial: $row.find('td:eq(6)').text().trim(),
                costo: parseFloat($row.find('td:eq(7) input').val()) || 0,
                cantidad: parseInt($row.find('td:eq(8) input').val()) || 1
            });
        });
        
        const datosFormulario = {
            numero_factura: $('#correlativo').val(),
            nombre_proveedor: $('#proveedor option:selected').text(),
            productos: productos
        };
        
        // Verificar coherencia
        const resultado = await asistenteIA.verificarCoherencia(iaFacturaId, datosFormulario);
        
        if (resultado.exito) {
            // Sin discrepancias - mostrar éxito y registrar
            iaVerificada = true;
            $('#ia_verificada').val('true'); // Actualizar campo oculto
            
            await Swal.fire({
                icon: 'success',
                title: '✅ Verificación Exitosa',
                html: 'Los datos coinciden con la factura.<br>Registrando recepción...',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            
            this.submit();
        } else {
            // Hay discrepancias
            const hayCriticas = resultado.discrepancias.some(d => d.severidad === 'CRITICA');
            
            // Construir mensaje de discrepancias
            let mensajeHtml = '<div style="text-align:left; max-height:300px; overflow-y:auto;">';
            resultado.discrepancias.forEach(d => {
                const icono = d.severidad === 'CRITICA' ? '🔴' : 
                             d.severidad === 'ALTA' ? '🟠' : 
                             d.severidad === 'MEDIA' ? '🟡' : '🟢';
                mensajeHtml += `<div style="margin:8px 0; padding:8px; background:#f8f9fa; border-radius:4px;">
                    <b>${icono} ${d.campo}</b> <span class="badge bg-${d.severidad === 'CRITICA' ? 'danger' : d.severidad === 'ALTA' ? 'warning' : 'info'}">${d.severidad}</span><br>
                    <small>Factura: ${d.valor_factura}</small><br>
                    <small>Formulario: ${d.valor_formulario}</small>
                </div>`;
            });
            mensajeHtml += '</div>';
            
            if (hayCriticas) {
                // Bloquear registro
                Swal.fire({
                    icon: 'error',
                    title: '🔴 REGISTRO BLOQUEADO',
                    html: `<b>Discrepancias críticas encontradas:</b><br>${mensajeHtml}<br>
                           <b>Corrija los datos antes de continuar.</b>`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#dc3545',
                    width: '600px'
                });
                iaVerificada = false;
            } else {
                // Advertencia pero permitir continuar
                const confirmacion = await Swal.fire({
                    icon: 'warning',
                    title: '⚠️ Diferencias Detectadas',
                    html: `${mensajeHtml}<br>¿Desea continuar de todos modos?`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Corregir',
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#28a745',
                    width: '600px'
                });
                
                if (confirmacion.isConfirmed) {
                    iaVerificada = true;
                    this.submit();
                }
            }
            
            actualizarEstadoBotonRegistrar();
        }
    });

    // Limpiar al cerrar modal
    $('#registrarRecepcionModal').on('hidden.bs.modal', function() {
        asistenteIA.limpiarCache();
        iaVerificada = false;
        iaFacturaId = null;
        $('#preview-factura-container').addClass('d-none');
        $('#panel-resultados-ia').addClass('d-none').html('').removeClass('verificacion-exitosa verificacion-bloqueada verificacion-advertencia');
        $('#alertas-recepcion-ia').html('');
        $('#foto-factura-ia').val('');
        $('#ingresarRecepcion button[type="submit"]').html('Registrar').prop('disabled', false);
    });
    
    // Estado inicial del botón
    actualizarEstadoBotonRegistrar();
});
</script>

<?php include 'footer.php'; ?>
<script src="assets/javascript/recepcion.js"></script>
<script src="assets/public/js/chart.js"></script>
<script src="assets/public/js/html2canvas.min.js"></script>
<script src="assets/public/js/jspdf.umd.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>

    <button class="btn-grafica" title="Visualizar Reportes" onclick="window.location.href='?pagina=reporteInventario'">
        <img src="assets/img/grafic.png" alt="Reportes" width="30" height="30">
    </button>
    <button class="btn-ayuda" title="Visualizar Ayuda">
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