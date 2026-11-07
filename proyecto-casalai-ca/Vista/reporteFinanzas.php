<?php require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if ($_SESSION) {?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reportes de Finanzas</title>
        <?php include 'header.php'; ?>
        <style>
            .error {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }

            .error-text {
                color: #dc3545 !important;
                font-size: 0.875em;
                margin-top: 0.25rem;
            }

            .error-container {
                border: 1px solid #dc3545 !important;
                border-radius: 0.375rem;
                padding: 0.5rem;
            }

            .span-value.error-text {
                display: block;
                margin-top: 0.25rem;
            }
        </style>
    </head>

    <?php include 'NewNavBar.php'; ?>

    <body class="fondo"
        style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

        <div style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">

            <div class="reporte-parametros" style="text-align:center;">
                <h3 class="titulo-form">Reporte de Cuentas Bancarias</h3>
                <div class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                    <label for="fechaInicioCuentas" class="title-select">Fecha inicio:</label>
                    <input type="date" id="fechaInicioCuentas" class="selector-reporte">
                    <label for="fechaFinCuentas" class="title-select">Fecha fin:</label>
                    <input type="date" id="fechaFinCuentas" class="selector-reporte">
                    <label for="agruparPor" class="title-select">Agrupar por:</label>
                    <select id="agruparPor" class="selector-reporte">
                        <option value="metodos">Método de Pago</option>
                        <option value="nombre_banco">Banco</option>
                        <option value="nombre">Cliente</option>
                        <option value="estatus">Estatus</option>
                    </select>
                    <label for="tipoGraficaCuentas" class="title-select">Tipo de gráfica:</label>
                    <select id="tipoGraficaCuentas" class="selector-reporte">
                        <option value="bar">Barras</option>
                        <option value="line">Líneas</option>
                        <option value="pie">Pastel</option>
                        <option value="doughnut">Donas</option>
                        <option value="polarArea">Área Polar</option>
                    </select>
                    <div>
                        <button id="generarReporteCuentasBtn" class="btn btn-primary">Generar Reporte</button>
                        <button id="descargarPDFCuentas" class="btn btn-primary">Descargar PDF</button>
                    </div>
                </div>
            </div>

            <div class="reporte-container" style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center;">
                    <div style="flex:1; min-width:320px; text-align:center;">
                        <div class="grafica-container" style="max-width:600px; margin:0 auto 24px auto;">
                            <canvas id="graficoCuentas" width="600" height="400"></canvas>
                        </div>
                    </div>
                    <div style="flex:2; min-width:320px;">
                        <div id="tablaReporteCuentas"></div>
                    </div>
                </div>
            </div>

            <div class="reporte-parametros" style="margin-bottom: 30px; text-align:center;">
                <h3 class="titulo-form">Reporte de Ingresos y Egresos</h3>
                <div class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                    <label for="fechaInicio" class="title-select">Fecha inicio:</label>
                    <input type="date" id="fechaInicio" class="selector-reporte">

                    <label for="fechaFin" class="title-select">Fecha fin:</label>
                    <input type="date" id="fechaFin" class="selector-reporte">

                    <label for="tipoGrafica" class="title-select">Tipo de gráfica:</label>
                    <select id="tipoGrafica" class="selector-reporte">
                        <option value="bar">Barras</option>
                        <option value="line">Líneas</option>
                        <option value="pie">Pastel</option>
                        <option value="doughnut">Donas</option>
                        <option value="polarArea">Área Polar</option>
                    </select>

                    <label for="tipoConsulta" class="title-select">Tipo de consulta:</label>
                    <select id="tipoConsulta" class="selector-reporte">
                        <option value="ambos">Ingresos y Egresos</option>
                        <option value="ingresos">Solo Ingresos</option>
                        <option value="egresos">Solo Egresos</option>
                    </select>
                    <div>
                        <button id="generarReporteFinanzasBtn" class="btn btn-primary">Generar Reporte</button>
                        <button id="descargarPDFFinanzas" class="btn btn-primary">Descargar PDF</button>
                    </div>
                </div>
            </div>

            <div class="reporte-container"
                style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                
                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center;">
                    <div style="flex:1; min-width:320px; text-align:center;">
                        <div class="grafica-container" style="max-width:600px; margin:0 auto 24px auto;">
                            <canvas id="graficoFinanzas" width="600" height="400"></canvas>
                        </div>
                    </div>
                    <div style="flex:2; min-width:320px;">
                        <div id="tablaReporteFinanzas"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'footer.php'; ?>
        <script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets/public/js/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // Datos para el primer reporte (Finanzas)
            const ingresos = <?= json_encode($finanzas['ingresos'] ?? []) ?>;
            const egresos = <?= json_encode($finanzas['egresos'] ?? []) ?>;

            // Datos para el segundo reporte (Cuentas)
            const cuentasReportes = <?= json_encode($cuentasReportes ?? []); ?>;

            // Utilidad: Filtrar por fechas (YYYY-MM-DD)
            function filtrarPorFechas(datos, inicio, fin) {
                return datos.filter(d => {
                    if (!d.fecha) return false;
                    if (inicio && d.fecha < inicio) return false;
                    if (fin && d.fecha > fin) return false;
                    return true;
                });
            }

            // Agrupar por mes para finanzas
            function agruparPorMes(datos) {
                const agrupado = {};
                datos.forEach(d => {
                    if (!d.fecha) return;
                    const mes = d.fecha.substr(0, 7); // YYYY-MM
                    if (!agrupado[mes]) agrupado[mes] = 0;
                    agrupado[mes] += parseFloat(d.monto);
                });
                return agrupado;
            }

            // Agrupar por campo para cuentas
            function agruparPorCampo(datos, campo) {
                const agrupado = {};
                datos.forEach(d => {
                    const key = d[campo] || "Desconocido";
                    if (!agrupado[key]) agrupado[key] = 0;
                    agrupado[key] += parseFloat(d.monto);
                });
                return agrupado;
            }

            // Actualiza las opciones del select tipoConsulta
            function actualizarOpcionesTipoConsulta() {
                const select = document.getElementById('tipoConsulta');
                const actual = select.value;
                const opciones = [
                    { value: 'ambos', text: 'Ingresos y Egresos' },
                    { value: 'ingresos', text: 'Solo Ingresos' },
                    { value: 'egresos', text: 'Solo Egresos' }
                ];
                select.innerHTML = '';
                opciones.forEach(op => {
                    if (op.value !== actual) {
                        select.innerHTML += `<option value="${op.value}">${op.text}</option>`;
                    }
                });
                // Mantener la opción actual seleccionada
                select.innerHTML = `<option value="${actual}" selected>${opciones.find(o=>o.value===actual).text}</option>` + select.innerHTML;
            }

            // Reporte de Finanzas
            function generarReporteFinanzas() {
                const fechaInicio = document.getElementById('fechaInicio').value;
                const fechaFin = document.getElementById('fechaFin').value;
                const tipoGrafica = document.getElementById('tipoGrafica').value;
                const tipoConsulta = document.getElementById('tipoConsulta').value;

                // Validación de fechas
                if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                    Swal.fire('Error', 'La fecha inicial no puede ser mayor que la fecha final', 'error');
                    return;
                }

                // Filtrar datos
                let ingresosFiltrados = filtrarPorFechas(ingresos, fechaInicio, fechaFin);
                let egresosFiltrados = filtrarPorFechas(egresos, fechaInicio, fechaFin);

                // Agrupar por mes para gráfica
                const ingresosPorMes = agruparPorMes(ingresosFiltrados);
                const egresosPorMes = agruparPorMes(egresosFiltrados);

                // Unir todos los meses presentes
                const meses = Array.from(new Set([
                    ...Object.keys(ingresosPorMes),
                    ...Object.keys(egresosPorMes)
                ])).sort();

                // Etiquetas para gráfica
                const labels = meses.map(m => {
                    const [y, mo] = m.split('-');
                    return `${mo}/${y}`;
                });
                const dataIngresos = meses.map(m => ingresosPorMes[m] || 0);
                const dataEgresos = meses.map(m => egresosPorMes[m] || 0);

                // Datasets según tipoConsulta
                const colores = ['rgba(39, 174, 96, 0.7)', 'rgba(231, 76, 60, 0.7)'];
                const borderColores = ['rgba(39, 174, 96, 1)', 'rgba(231, 76, 60, 1)'];
                let datasets = [];
                if (tipoConsulta === 'ambos') {
                    datasets = [
                        {
                            label: 'Egresos',
                            data: dataEgresos.length ? dataEgresos : [0],
                            backgroundColor: tipoGrafica === 'line' ? borderColores[1] : colores[1],
                            borderColor: borderColores[1],
                            borderWidth: 2,
                            fill: tipoGrafica === 'line' ? false : true
                        },
                        {
                            label: 'Ingresos',
                            data: dataIngresos.length ? dataIngresos : [0],
                            backgroundColor: tipoGrafica === 'line' ? borderColores[0] : colores[0],
                            borderColor: borderColores[0],
                            borderWidth: 2,
                            fill: tipoGrafica === 'line' ? false : true
                        }
                    ];
                } else if (tipoConsulta === 'ingresos') {
                    datasets = [
                        {
                            label: 'Ingresos',
                            data: dataIngresos.length ? dataIngresos : [0],
                            backgroundColor: tipoGrafica === 'line' ? borderColores[0] : colores[0],
                            borderColor: borderColores[0],
                            borderWidth: 2,
                            fill: tipoGrafica === 'line' ? false : true
                        }
                    ];
                } else if (tipoConsulta === 'egresos') {
                    datasets = [
                        {
                            label: 'Egresos',
                            data: dataEgresos.length ? dataEgresos : [0],
                            backgroundColor: tipoGrafica === 'line' ? borderColores[1] : colores[1],
                            borderColor: borderColores[1],
                            borderWidth: 2,
                            fill: tipoGrafica === 'line' ? false : true
                        }
                    ];
                }

                // Renderizar gráfica
                const canvas = document.getElementById('graficoFinanzas');
                const ctx = canvas.getContext('2d');
                if (window.reporteFinanzasChart) window.reporteFinanzasChart.destroy();
                window.reporteFinanzasChart = new Chart(ctx, {
                    type: tipoGrafica,
                    data: {
                        labels: labels.length ? labels : ['Sin datos'],
                        datasets: datasets
                    },
                    options: {
                        plugins: {
                            legend: { display: true, position: 'top' },
                            title: { display: true, text: 'Ingresos vs Egresos por Mes' }
                        },
                        scales: {
                            x: { beginAtZero: true },
                            y: { beginAtZero: true }
                        }
                    }
                });

                // Filtrar movimientos para tabla
                let movimientos = [];
                if (tipoConsulta === 'ambos') {
                    movimientos = [
                        ...ingresosFiltrados.map(ing => ({
                            tipo: 'Ingreso',
                            fecha: ing.fecha,
                            monto: parseFloat(ing.monto),
                            descripcion: ing.descripcion
                        })),
                        ...egresosFiltrados.map(eg => ({
                            tipo: 'Egreso',
                            fecha: eg.fecha,
                            monto: parseFloat(eg.monto),
                            descripcion: eg.descripcion
                        }))
                    ];
                } else if (tipoConsulta === 'ingresos') {
                    movimientos = ingresosFiltrados.map(ing => ({
                        tipo: 'Ingreso',
                        fecha: ing.fecha,
                        monto: parseFloat(ing.monto),
                        descripcion: ing.descripcion
                    }));
                    egresosFiltrados = [];
                } else if (tipoConsulta === 'egresos') {
                    movimientos = egresosFiltrados.map(eg => ({
                        tipo: 'Egreso',
                        fecha: eg.fecha,
                        monto: parseFloat(eg.monto),
                        descripcion: eg.descripcion
                    }));
                    ingresosFiltrados = [];
                }

                movimientos.sort((a, b) => b.fecha.localeCompare(a.fecha)); // Más reciente primero

                // Tabla de movimientos
                let tablaHtml = `
                    <table class="table table-bordered table-striped" style="margin:0 auto 32px auto; width:100%;">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                movimientos.forEach(mov => {
                    // Formato DD/MM/YYYY
                    let fechaFormateada = mov.fecha;
                    if (fechaFormateada && fechaFormateada.length === 10) {
                        const [y, m, d] = fechaFormateada.split('-');
                        fechaFormateada = `${d}/${m}/${y}`;
                    }
                    tablaHtml += `<tr>
                        <td style="color:${mov.tipo === 'Ingreso' ? 'green' : 'red'};">${mov.tipo}</td>
                        <td>${fechaFormateada}</td>
                        <td>${mov.monto.toFixed(2)}</td>
                        <td>${mov.descripcion}</td>
                    </tr>`;
                });
                tablaHtml += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Totales</th>
                                <th style="color:green;">${ingresosFiltrados.reduce((a,b)=>a+parseFloat(b.monto),0).toFixed(2)}</th>
                                <th style="color:red;">${egresosFiltrados.reduce((a,b)=>a+parseFloat(b.monto),0).toFixed(2)}</th>
                            </tr>
                        </tfoot>
                    </table>
                `;
                document.getElementById('tablaReporteFinanzas').innerHTML = tablaHtml;
            }

            // Reporte de Cuentas
            function generarReporteCuentas() {
                const fechaInicio = document.getElementById('fechaInicioCuentas').value;
                const fechaFin = document.getElementById('fechaFinCuentas').value;
                const campo = document.getElementById('agruparPor').value;
                const tipoGrafica = document.getElementById('tipoGraficaCuentas').value;

                // Validación de fechas
                if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                    Swal.fire('Error', 'La fecha inicial no puede ser mayor que la fecha final', 'error');
                    return;
                }

                // Filtrar
                const datosFiltrados = filtrarPorFechas(cuentasReportes, fechaInicio, fechaFin);

                // Agrupar
                const agrupado = agruparPorCampo(datosFiltrados, campo);
                const labels = Object.keys(agrupado);
                const valores = Object.values(agrupado);

                // Colores
                const colores = labels.map((_, i) => `hsl(${(360 / (labels.length || 1)) * i},70%,60%)`);
                const borderColores = colores.map(color => color.replace('60%)', '40%)'));

                // Gráfica
                const ctx = document.getElementById("graficoCuentas").getContext("2d");
                if (window.reporteCuentasChart) window.reporteCuentasChart.destroy();

                window.reporteCuentasChart = new Chart(ctx, {
                    type: tipoGrafica,
                    data: {
                        labels: labels.length ? labels : ["Sin datos"],
                        datasets: [{
                            label: `Montos agrupados por ${campo}`,
                            data: valores.length ? valores : [0],
                            backgroundColor: colores,
                            borderColor: borderColores,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true, position: "top" },
                            title: { display: true, text: "Análisis Financiero por " + campo }
                        }
                    }
                });

                // Tabla detallada
                let tablaHtml = `
                    <table class="table table-bordered table-striped" style="margin:0 auto; width:100%;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Cédula</th>
                                <th>Banco</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                datosFiltrados.forEach(d => {
                    // Formato DD/MM/YYYY
                    let fechaFormateada = d.fecha;
                    if (fechaFormateada && fechaFormateada.length === 10) {
                        const [y, m, day] = fechaFormateada.split('-');
                        fechaFormateada = `${day}/${m}/${y}`;
                    }
                    tablaHtml += `
                        <tr>
                            <td>${fechaFormateada}</td>
                            <td>${d.nombre || '-'}</td>
                            <td>${d.cedula || '-'}</td>
                            <td>${d.nombre_banco || "-"}</td>
                            <td>${d.metodos || '-'}</td>
                            <td>${parseFloat(d.monto).toFixed(2)}</td>
                            <td>${d.estatus || '-'}</td>
                        </tr>
                    `;
                });

                tablaHtml += "</tbody></table>";
                document.getElementById("tablaReporteCuentas").innerHTML = tablaHtml;
            }

            // Event Listeners para Finanzas
            document.getElementById('tipoConsulta').addEventListener('change', function() {
                actualizarOpcionesTipoConsulta();
                generarReporteFinanzas();
            });
            document.getElementById('fechaInicio').addEventListener('change', generarReporteFinanzas);
            document.getElementById('fechaFin').addEventListener('change', generarReporteFinanzas);
            document.getElementById('tipoGrafica').addEventListener('change', generarReporteFinanzas);
            document.getElementById('generarReporteFinanzasBtn').addEventListener('click', generarReporteFinanzas);

            document.getElementById('descargarPDFFinanzas').addEventListener('click', function () {
                if (typeof jspdf === 'undefined') {
                    Swal.fire('Error', 'La librería jsPDF no está cargada', 'error');
                    return;
                }
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'pt',
                    format: 'a4'
                });
                const reporte = document.querySelector('#tablaReporteFinanzas').closest('.reporte-container');
                html2canvas(reporte, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = pageWidth - 40;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                    doc.save('Reporte_Finanzas.pdf');
                });
            });

            // Event Listeners para Cuentas
            document.getElementById('fechaInicioCuentas').addEventListener('change', generarReporteCuentas);
            document.getElementById('fechaFinCuentas').addEventListener('change', generarReporteCuentas);
            document.getElementById('agruparPor').addEventListener('change', generarReporteCuentas);
            document.getElementById('tipoGraficaCuentas').addEventListener('change', generarReporteCuentas);
            document.getElementById('generarReporteCuentasBtn').addEventListener('click', generarReporteCuentas);

            document.getElementById('descargarPDFCuentas').addEventListener('click', function () {
                if (typeof jspdf === 'undefined') {
                    Swal.fire('Error', 'La librería jsPDF no está cargada', 'error');
                    return;
                }
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'pt',
                    format: 'a4'
                });
                const reporte = document.querySelector('#tablaReporteCuentas').closest('.reporte-container');
                html2canvas(reporte, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = pageWidth - 40;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                    doc.save('Reporte_Cuentas.pdf');
                });
            });

            // Inicialización al cargar
            document.addEventListener('DOMContentLoaded', function() {
                actualizarOpcionesTipoConsulta();
                generarReporteFinanzas();
                generarReporteCuentas();
            });
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
            $.get('assets/public/ayuda/reporteFinanzas.php')
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