<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 9;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Clientes</title>
    <?php include 'header.php'; ?>
</head>
<body  class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>




<!-- Reporte estadístico de compras por cliente -->
<div class="reporte-container" style="max-width:900px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <h3 style="text-align:center; color:#1f66df; margin-bottom:16px;">Top 10 Clientes por Productos Comprados</h3>
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:16px;">
        <div style="flex:1 1 320px; min-width:0;">
            <canvas id="graficoComprasClientes" style="width:100%; height:260px;"></canvas>
        </div>
        <div style="flex:1 1 320px; min-width:0;">
            <div style="max-width:100%; overflow-x:auto;">
            <table class="table table-bordered table-striped" style="margin:0 auto 32px auto; width:100%; min-width:480px;">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Cantidad de <br> Productos Comprados</th>
                        <th>Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reporteComprasClientes as $cliente): 
                        $porcentaje = $totalComprasClientes > 0 ? round(($cliente['cantidad'] / $totalComprasClientes) * 100, 2) : 0;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                            <td><?= $cliente['cantidad'] ?></td>
                            <td><?= $porcentaje ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><?= $totalComprasClientes ?></th>
                        <th>100%</th>
                    </tr>
                </tfoot>
            </table>
            </div>
            <div style="text-align:center; margin-top:20px;">
                <button id="descargarPDFClientes" class="btn btn-success" style="padding:10px 24px; font-size:16px; border-radius:6px; background:#27ae60; color:#fff; border:none; cursor:pointer;">
                    Descargar Reporte de Compras
                </button>
            </div>
        </div>
    </div>
</div>

    <?php include 'footer.php'; ?>


</div>

<script src="assets/javascript/js/jquery.min.js"></script>
<script src="assets/javascript/js/jquery-3.5.1.min.js"></script>
<script src="assets/public/js/popper.min.js"></script>
<script src="assets/javascript/js/bootstrap.min.js"></script>

<script src="assets/javascript/cliente.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>
<script>
$(document).ready(function() {
    $('#tablaConsultas').DataTable({
        language: {
            url: 'assets/public/js/es-ES.json'
        },
        columnDefs: [
            { orderable: false, targets: 5 } // Deshabilitar ordenamiento para columna de acciones
        ]
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const labelsClientes = <?= json_encode(array_column($reporteComprasClientes, 'nombre')) ?>;
const dataClientes = <?= json_encode(array_column($reporteComprasClientes, 'cantidad')) ?>;
const ctxClientes = document.getElementById('graficoComprasClientes').getContext('2d');
new Chart(ctxClientes, {
    type: 'bar',
    data: {
        labels: labelsClientes,
        datasets: [{
            label: 'Productos comprados',
            data: dataClientes,
            backgroundColor: 'rgba(39, 174, 96, 0.7)',
            borderColor: 'rgba(39, 174, 96, 1)',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Top 10 Clientes por Productos Comprados' }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

document.getElementById('descargarPDFClientes').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'pt',
        format: 'a4'
    });

    const reporte = document.querySelector('.reporte-container');
    html2canvas(reporte).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pageWidth = doc.internal.pageSize.getWidth();
        const imgWidth = pageWidth - 40;
        const imgHeight = canvas.height * imgWidth / canvas.width;

        doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
        doc.save('Reporte_Compras_Clientes.pdf');
    });
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
            $.get('assets/public/ayuda/reporteCliente.php')
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
    style="top: 120px;"
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