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
    <title>Gestionar Clientes</title>
    <?php include 'header.php'; ?>
</head>
<body  class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<div class="modal fade modal-registrar" id="registrarClienteModal" tabindex="-1" role="dialog" 
aria-labelledby="registrarClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="ingresarclientes" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Registrar Cliente" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="registrarClienteModalLabel">Incluir Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    <div class="envolver-form">
                        <label for="nombre">Nombre Completo*</label>
                        <input class="control-form" placeholder="Nombres y apellidos" maxlength="100" type="text" id="nombre" name="nombre" required>
                        <span class="span-value" id="snombre"></span>
                    </div>

                    <div class="envolver-form">
                        <label for="cedula_o_rif">Cédula*</label>
                        <input class="control-form" placeholder="1.234.567 o 12.345.678" maxlength="10" type="text" id="cedula" name="cedula" required>
                        <span class="span-value" id="scedula"></span>
                    </div>

                    <div class="envolver-form">
                        <label for="telefono">Numero de Teléfono*</label>
                        <input class="control-form" placeholder="0400-000-0000" maxlength="13" type="text" id="telefono" name="telefono" required>
                        <span class="span-value" id="stelefono"></span>
                    </div>
                    
                    <div class="envolver-form">
                        <label for="Direccion">Dirección*</label>
                        <textarea placeholder="Estado/Ciudad/Calle o Avenida..." class="form-control" maxlength="100" id="direccion" name="direccion" rows="2"></textarea>
                        <span class="span-value" id="sdireccion"></span>
                    </div>
                    
                    <div class="envolver-form">
                        <label for="correo">Correo Electrónico*</label>
                        <input class="control-form" placeholder="ejemplo@gmail.com" type="text" id="correo" name="correo" maxlength="50" required>
                        <span class="span-value" id="scorreo"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="boton-form btn-primary" type="submit">Registrar</button>
                    <button class="boton-reset btn-primary" type="reset">Limpiar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>

        <h3>Lista de Clientes</h3>

        <div class="ghost"></div>
    </div>


    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <?php if ($cliente['activo'] == 1) { ?>
                <tr data-id="<?php echo $cliente['id_clientes']; ?>">
                    <td>
                        <span class="campo-nombres">
                            <?php echo htmlspecialchars($cliente['nombre']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-numeros">
                            <?php echo htmlspecialchars($cliente['cedula']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-nombres">
                            <?php echo htmlspecialchars($cliente['direccion']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-numeros">
                            <?php echo htmlspecialchars($cliente['telefono']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-tex-num">
                            <?php echo htmlspecialchars($cliente['correo']); ?>
                        </span>
                    </td>
                    <td>
                        <ul>
                            <button class="btn-modificar"
                                id="btnModificarCliente"
                                title="Modificar Cliente"
                                data-id="<?php echo htmlspecialchars($cliente['id_clientes']); ?>"
                                data-nombre="<?php echo htmlspecialchars($cliente['nombre']); ?>"
                                data-cedula="<?php echo htmlspecialchars($cliente['cedula']); ?>"
                                data-direccion="<?php echo htmlspecialchars($cliente['direccion']); ?>"
                                data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                data-correo="<?php echo htmlspecialchars($cliente['correo']); ?>">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button class="btn-eliminar"
                                title="Eliminar Cliente"
                                data-id="<?php echo $cliente['id_clientes']; ?>">
                                <img src="assets/img/circle-x.svg">
                            </button>
                        </ul>
                    </td>
                </tr>
                <?php } ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

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
<div class="modal fade modal-modificar" id="modificar_clientes_modal" tabindex="-1" role="dialog" 
aria-labelledby="modificar_clientes_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="modificarclientes" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Cliente" data-contexto="modificar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="modificar_clientes_modal_label">Modificar Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modificar_id_clientes" name="id_clientes">
                    <div class="form-group">
                        <label for="modificarnombre">Nombre completo</label>
                        <input type="text" class="form-control" maxlength="100" id="modificarnombre" name="nombre" required>
                        <span class="span-value-modal" id="smodificarnombre"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificarcedula">Cédula</label>
                        <input type="text" class="form-control" id="modificarcedula" name="cedula" maxlength="10" required>
                        <span class="span-value-modal" id="smodificarcedula"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificartelefono">Teléfono</label>
                        <input type="text" class="form-control" id="modificartelefono" name="telefono" maxlength="13" required>
                        <span class="span-value-modal" id="smodificartelefono"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificardireccion">Dirección</label>
                        <textarea class="form-control" maxlength="100" id="modificardireccion" name="direccion" rows="2"></textarea>
                        <span class="span-value-modal" id="smodificardireccion"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificarcorreo">Correo electrónico</label>
                        <input type="text" class="form-control" id="modificarcorreo" name="correo" maxlength="50" required>
                        <span class="span-value-modal" id="smodificarcorreo"></span>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Modificar</button>
                </div>
            </form>
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
<button 
    class="btn-grafica"
    title="Visualizar Reportes"
    onclick="window.location.href='?pagina=reporteCliente'">
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