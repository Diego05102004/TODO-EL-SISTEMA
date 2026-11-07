<?php require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if ($_SESSION) { ?>

<?php require_once 'Modelo/despacho.php' ; require_once 'controlador/factura.php' ; ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php include 'header.php'; ?>
        <link rel="stylesheet" href="assets/styles/darckort.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <title>Gestionar Despachos</title>
    </head>

    <body>
        <?php require_once("assets/public/modal.php"); ?>


        <?php include 'NavBar.php'; ?>


        <div class="container"> <!-- todo el contenido ira dentro de esta etiqueta-->
            <form method="post" action="?pagina=descargarFactura" id="f" class="formulario-1">
                <input type="text" name="accion" id="accion" style="display:none" />
                <input type="hidden" name="detalle_factura" id="detalle_factura_input">
                <h3 class="display-4 text-center">Factura</h3>
                
                <div class="instrucciones-modulo" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #1f66df;">
                    <h5 style="color: #1f66df; margin-bottom: 10px;">🧾 Instrucciones de Uso - Módulo de Facturas</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 style="color: #333;">🔹 Proceso de Facturación:</h6>
                            <ol style="font-size: 13px; color: #555; margin-left: 20px;">
                                <li>Haga clic en <strong>"LISTADO DE PRODUCTOS"</strong> para ver productos disponibles</li>
                                <li>Seleccione los productos deseados haciendo clic en ellos</li>
                                <li>Ajuste las cantidades usando los botones <strong>+/-</strong> o escribiendo directamente</li>
                                <li>Verifique el stock disponible (no puede exceder el stock actual)</li>
                                <li>El subtotal se calculará automáticamente</li>
                                <li>Complete los datos del cliente si es necesario</li>
                                <li>Haga clic en <strong>"Procesar Pre-Factura"</strong> cuando esté listo</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 style="color: #333;">🔹 Gestión de Productos:</h6>
                            <ol style="font-size: 13px; color: #555; margin-left: 20px;">
                                <li>Para <strong>agregar productos</strong>: haga clic en la tabla del modal</li>
                                <li>Para <strong>eliminar productos</strong>: presione el botón <strong>"X"</strong> rojo</li>
                                <li>Para <strong>modificar cantidades</strong>: use los botones <strong>+/-</strong></li>
                                <li>Los productos con stock 0 están ocultos automáticamente</li>
                                <li>El sistema validará que no exceda el stock disponible</li>
                            </ol>
                            <h6 style="color: #333; margin-top: 15px;">🔹 Cancelar Factura:</h6>
                            <ol style="font-size: 13px; color: #555; margin-left: 20px;">
                                <li>Use el botón <strong>"Cancelar"</strong> si necesita anular la factura</li>
                                <li>Confirme la cancelación en el mensaje de advertencia</li>
                            </ol>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6 style="color: #333;">🔹 Validaciones y Restricciones:</h6>
                            <ul style="font-size: 13px; color: #555; margin-left: 20px;">
                                <li>• No puede procesar una factura sin productos</li>
                                <li>• Las cantidades no pueden superar el stock disponible</li>
                                <li>• Los precios se calculan automáticamente según el producto</li>
                                <li>• El total se actualiza en tiempo real</li>
                                <li>• Todos los campos obligatorios deben estar completos</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="">

                    <div class="row">
                        <div class="col">
                            <hr />
                        </div>
                    </div>
                    <!-- FILA DE INPUT Y BUSCAR CLIENTE -->
                    <div class="row">

                        <div class="row">
                            <div class="col-md-8 input-group">
                                <input class="form-control" type="text" id="nombre_p" name="nombre_p"
                                    style="display:none" />
                                <input class="form-control" type="text" id="id_producto" name="id_producto"
                                    style="display:none" />
                                <button type="button" class="btn btn-primary" id="listadodeproductos"
                                    name="listadodeproductos">LISTADO DE PRODUCTOS</button>
                                <div class=" row">
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary" id="registrar"
                                            name="registrar">Procesar<br> Pre-Factura</button>
                                        <div class="mt-2" style="font-size: 11px; color: #666; text-align: center;">
                                            <strong>Verifique:</strong> Productos seleccionados → Cantidades → Total
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- FIN DE FILA BUSQUEDA DE PRODUCTOS -->
                        <div class="row">
                            <div class="col">
                                <hr />
                            </div>
                        </div>




                    </div>
                </div>
                <!-- FIN DE FILA INPUT Y BUSCAR CLIENTE -->

                <!-- FILA DE DATOS DEL CLIENTE -->
                <div class="row">
                    <div class="col-md-12" id="datosdelcliente">

                    </div>
                </div>
                <!-- FIN DE FILA DATOS DEL CLIENTE -->

                <div class="row">
                    <div class="col">
                        <hr />
                    </div>
                </div>

                <!-- FILA DE BUSQUEDA DE PRODUCTOS -->

                <!-- FILA DE DETALLES DE LA VENTA -->
                <div class="row">
                    <div class="col-md-12">
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>Eliminar</th>
                                    <th style="display:none">Id</th>
                                    <th>Producto</th>
                                    <th>modelo</th>
                                    <th>Marca</th>
                                    <th>Cantidad Disponible</th>
                                    <th>Cantidad Seleccionada</th>
                                    <th>Precio</th>
                                    <th>Sub-Total</th>
                                </tr>
                            </thead>
                            <tbody id="detalle_factura">
                                <!-- Filas insertadas dinámicamente con JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" style="text-align:right; font-weight:bold;">Total:</td>
                                    <td id="total_subtotal" style="font-weight:bold;">0.00 $</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </form>


        </div> <!-- fin de container -->



        <!-- seccion del modal clientes -->
        <div class="modal fade" tabindex="-1" role="dialog" id="modalclientes">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-header text-light bg-info">
                    <h5 class="modal-title">Listado de clientes</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-content">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="display:none">Id</th>
                                <th>Nombre</th>
                                <th>Rif</th>
                            </tr>
                        </thead>
                        <tbody id="listadoclientes">

                        </tbody>
                    </table>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
        <!--fin de seccion modal-->

        <!-- seccion del modal productos -->
        <div class="modal fade" tabindex="-1" role="dialog" id="modalproductos">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-header text-light bg-info">
                    <h5 class="modal-title">Agregar Productos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-content">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="display:none">Id</th>
                                <th>Nombre Producto</th>
                                <th>modelo</th>
                                <th>Marca</th>
                                <th>Stock Actual</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody id="listadoproductos">

                        </tbody>
                    </table>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>

        </div>
        <!--fin de seccion modal-->
        <script src="assets/javascript/factura.js"></script>




        <?php include 'footer.php'; ?>
        <script src="assets/javascript/validaciones.js"></script>
        
<script src="assets/javascript/jwt_validator.js"></script>
    </body>

    </html>
    <?php
} else {
    header("Location: ?pagina=acceso-denegado"); 
    exit;
}
?>