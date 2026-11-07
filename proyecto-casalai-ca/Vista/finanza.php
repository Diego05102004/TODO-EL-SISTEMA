<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 16;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Ingresos y Egresos</title>
    <?php include 'header.php'; ?>
</head>

<body  class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
<?php include 'NewNavBar.php'; ?>

<div style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <div class="reporte-parametros" style="margin-bottom: 30px; text-align:center;">
        <div class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
            <div>
                <label for="fechaInicio" class="title-select">Fecha inicio:</label>
                <input type="date" id="fechaInicio" class="selector-reporte" style="width:160px;">

                <label for="fechaFin" class="title-select">Fecha fin:</label>
                <input type="date" id="fechaFin" class="selector-reporte" style="width:160px;">

                <label for="tipoGrafica" class="title-select">Tipo de gráfica:</label>
                <select id="tipoGrafica" class="selector-reporte" style="width:200px;">
                    <option value="bar">Barras</option>
                    <option value="line">Líneas</option>
                    <option value="pie">Pastel</option>
                    <option value="doughnut">Donas</option>
                    <option value="polarArea">Área Polar</option>
                </select>

                <label for="tipoConsulta" class="title-select">Tipo de consulta:</label>
                <select id="tipoConsulta" class="selector-reporte" style="width:180px;">
                    <option value="ambos">Ingresos y Egresos</option>
                    <option value="ingresos">Solo Ingresos</option>
                    <option value="egresos">Solo Egresos</option>
                </select>
            </div>
            <div>
                <button id="generarReporteBtn" class="btn btn-primary">Generar Reporte</button>
                <button id="descargarPDF" class="btn btn-primary">Descargar PDF</button>
            </div>
        </div>
    </div>
    <div class="reporte-container" style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <h3 class="titulo-form">Reporte de Ingresos y Egresos</h3>
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center;">
            <div style="flex:1; min-width:320px; text-align:center;">
                <div class="grafica-container" style="max-width:600px; margin:0 auto 24px auto;">
                    <canvas id="graficoFinanzas" width="600" height="400"></canvas>
                </div>
            </div>
            <div style="flex:2; min-width:320px;">
                <div id="tablaReporte"></div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/finanza.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const ingresos = <?= json_encode($finanzas['ingresos'] ?? []) ?>;
const egresos = <?= json_encode($finanzas['egresos'] ?? []) ?>;

function generarColores(n) {
    return Array.from({length: n}, (_, i) => `hsl(${(360 / n) * i}, 70%, 60%)`);
}

function filtrarPorFechas(datos, inicio, fin) {
    return datos.filter(d => {
        return (!inicio || d.fecha >= inicio) && (!fin || d.fecha <= fin);
    });
}

function agruparPorMes(datos) {
    const agrupado = {};
    datos.forEach(d => {
        const mes = d.fecha.substr(0,7); // YYYY-MM
        if (!agrupado[mes]) agrupado[mes] = 0;
        agrupado[mes] += parseFloat(d.monto);
    });
    return agrupado;
}

function generarReporte() {
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

    // Agrupar por mes (para la gráfica)
    const ingresosPorMes = agruparPorMes(ingresosFiltrados);
    const egresosPorMes = agruparPorMes(egresosFiltrados);

    // Unir todos los meses presentes
    const meses = Array.from(new Set([
        ...Object.keys(ingresosPorMes),
        ...Object.keys(egresosPorMes)
    ])).sort();

    // Datos para la gráfica
    const labels = meses.map(m => {
        const [y, mo] = m.split('-');
        return new Date(y, mo-1).toLocaleString('es-ES', { month: 'short', year: 'numeric' });
    });
    const dataIngresos = meses.map(m => ingresosPorMes[m] || 0);
    const dataEgresos = meses.map(m => egresosPorMes[m] || 0);

    // Gráfica
    const colores = ['rgba(39, 174, 96, 0.7)', 'rgba(231, 76, 60, 0.7)'];
    const borderColores = ['rgba(39, 174, 96, 1)', 'rgba(231, 76, 60, 1)'];
    const canvas = document.getElementById('graficoFinanzas');
    canvas.width = 600;
    canvas.height = 400;
    const ctx = canvas.getContext('2d');
    if (window.reporteFinanzasChart) window.reporteFinanzasChart.destroy();

    // 🔹 Adaptar datasets según tipoConsulta
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

    // Filtrar según tipoConsulta
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
        egresosFiltrados = []; // Para totales
    } else if (tipoConsulta === 'egresos') {
        movimientos = egresosFiltrados.map(eg => ({
            tipo: 'Egreso',
            fecha: eg.fecha,
            monto: parseFloat(eg.monto),
            descripcion: eg.descripcion
        }));
        ingresosFiltrados = []; // Para totales
    }

    movimientos.sort((a, b) => b.fecha.localeCompare(a.fecha)); // Orden descendente por fecha

    // Tabla detallada de movimientos (scrollable)
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
        // Formatear fecha a DD/MM/YYYY
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
    document.getElementById('tablaReporte').innerHTML = `
        <div style="max-height: 420px; overflow-y: auto;">
            ${tablaHtml}
        </div>
    `;
}

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

document.getElementById('tipoConsulta').addEventListener('change', function() {
    actualizarOpcionesTipoConsulta();
    generarReporte();
});

// Inicializar opciones al cargar
document.addEventListener('DOMContentLoaded', function() {
    actualizarOpcionesTipoConsulta();
    generarReporte();
});

// Botón generar
document.getElementById('generarReporteBtn').addEventListener('click', generarReporte);

// Botón descargar PDF
document.getElementById('descargarPDF').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'pt',
        format: 'a4'
    });

    const reporte = document.querySelector('.reporte-container');
    html2canvas(reporte, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pageWidth = doc.internal.pageSize.getWidth();
        const imgWidth = pageWidth - 40;
        const imgHeight = canvas.height * imgWidth / canvas.width;

        doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
        doc.save('Reporte_Finanzas.pdf');
    });
});

// Generar reporte inicial
document.addEventListener('DOMContentLoaded', generarReporte);
</script>
    <button 
        class="btn-grafica"
        title="Visualizar Reportes"
        onclick="window.location.href='?pagina=reporteFinanzas'">
        <img src="assets/img/grafic.png" alt="Reportes" width="30" height="30">
    </button>

    <button 
        class="btn-ayuda"
        style="top: 170px; right: 35px;"
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