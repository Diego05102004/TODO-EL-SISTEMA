<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 14;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Gestionar Orden de Despacho</title>
</head>

<body class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<!-- Modal para registrar orden de despacho -->
<div class="modal fade modal-registrar" id="registrarOrdenModal" tabindex="-1" role="dialog"
aria-labelledby="registrarOrdenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="ingresarOrdenDespacho" method="POST" novalidate>
                <div class="modal-header">
                    <h5 class="titulo-form" id="registrarOrdenModalLabel">Incluir Orden de Despacho</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="ingresar">
                    <div class="envolver-form">
                        <label for="correlativo">Correlativo</label>
                        <input type="text" class="control-form" id="correlativo" name="correlativo" placeholder="0123456789" maxlength="10">
                        <span class="span-value" id="scorrelativo"></span>
                    </div>

                    <div class="envolver-form">
                        <label for="factura">Orden de compra</label>
                        <select name="factura" id="factura" class="form-select">
                            <option value="" disabled selected>Seleccionar orden de compra</option>
                            <?php foreach ($facturas as $factura): ?>
                                <option value="<?php echo htmlspecialchars($factura['id_factura']); ?>">
                                    <?php echo htmlspecialchars('Orden de compra #'.$factura['id_factura'].' Cliente: '.$factura['nombre'].' Fecha '.$factura['fecha']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="span-value" id="sfactura"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="boton-form" type="submit">Registrar</button>
                    <button class="boton-reset" type="reset">Limpiar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>

        <h3>Lista de Orden <br> de Despacho</h3>
        
        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas" style="width:100%">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>N° de Orden <br> de despacho</th>
                <th>Código de orden <br> de compra</th>
                <th>Cliente</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($ordendespacho as $orden): ?>
            <tr data-id="<?php echo $orden['id_orden_despachos']; ?>">
                <td data-order="<?= $orden['fecha_despacho'] ?>">
                    <span class="campo-numeros">
                        <?= date('d/m/Y', strtotime($orden['fecha_despacho'])) ?>
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        <?php echo htmlspecialchars($orden['id_orden_despachos']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        <?php echo htmlspecialchars($orden['id_factura']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-nombres">
                        <?= htmlspecialchars($orden['cliente']) ?>
                    </span>
                </td>
                <td>
                    <span class="campo-rango">
                        <?php echo htmlspecialchars($orden['estado']); ?>
                    </span>
                </td>
                <td>
                    <ul>
                        <button
                            class="btn-detalle"
                            title="Ver Detalles"
                            data-id="<?= htmlspecialchars($orden['id_factura']) ?>"
                            data-productos='<?= json_encode($orden['productos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                            data-cliente="<?= htmlspecialchars($orden['cliente']) ?>"
                            data-cedula="<?= htmlspecialchars($orden['cedula']) ?>"
                            data-fecha="<?= htmlspecialchars($orden['fecha_despacho']) ?>">
                            <img src="assets/img/eye.svg">
                        </button>
                        <?php if ($orden['estado'] !== 'Entregada'): ?>
                            <button
                                class="btn-marcar"
                                title="Marcar como Entregada">
                                <img src="assets/img/check.svg">
                            </button>
                        <?php endif; ?>
                        <button class="btn-descargar" 
                                title="Descargar Orden de Despacho" 
                                onclick="descargarOrdenDespacho(<?php echo $orden['id_orden_despachos']; ?>, event)">
                            <img src="assets/img/download.svg">
                        </button>
                        <?php if ($orden['estado'] !== 'Entregada'): ?>
                            <?php if ($_SESSION['id_rol'] !== 1): ?>
                                <button class="btn-anular"
                                    title="Anular Orden de Despacho"
                                    data-id-orden="<?= htmlspecialchars($orden['id_orden_despachos']) ?>">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalDetallesOrden" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form" id="modalDetallesLabel">Detalles de la Orden</h5>
                <button type="button" class="close" id="cerrarModalDetallesOrden">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group fila-dato">
                    <label>Fecha de la compra:</label>
                    <p id="detalleFecha"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Cliente/Cédula:</label>
                    <p id="detalleCliente"></p> <p id="detalleCedula"></p>
                </div>
                
                <h6 class="subtitle">Productos</h6>
                <div class="table-responsive">
                    <table class="tablaConsultas">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Serial</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="detalleProductos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/public/bootstrap/js/sidebar.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>
<script src="assets/javascript/sweetalert2.all.min.js"></script>
<script src="assets/javascript/validaciones.js"></script>
<script>
window.facturasDisponibles = <?php
echo json_encode(array_map(function($factura) {
    return [
        'id_factura' => $factura['id_factura'],
        'factura' => 'Orden de compra #'.$factura['id_factura'].' Cliente: '.$factura['nombre'].' Fecha '.$factura['fecha']
    ];
}, $facturas));
?>;
</script>

    <!-- Nuestro archivo JS -->
    <script src="assets/javascript/ordendespacho.js"></script>
    
    <!-- Inicialización de componentes -->
    <script>
    $(document).ready(function() {
        // Inicialización del botón de incluir orden
        $('#btnIncluirOrden').on('click', function() {
            $('#registrarOrdenModal').modal('show');
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
?>