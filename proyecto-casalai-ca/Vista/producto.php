<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 6;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

  <!DOCTYPE html>
  <html lang="es">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestionar Productos</title>
    <?php include 'header.php'; ?>
    <style>
      .foto-producto {
        max-width: 80px;
        max-height: 80px;
        border-radius: 6px;
        border: 1px solid #ccc;
        padding: 5px;
        object-fit: contain;
        background: #fff;
      }
      #reporteMasVendidos, #reporteStock, #reporteRotacion {
          min-height: 420px;
          max-width: 100%;
      }
      #reporteMasVendidos, #reporteStock, #reporteRotacion {
          min-height: 420px;
          max-width: 200%;
      }
      canvas {
          max-width: 100% !important;
          height: 400px !important;
          max-height: 400px !important;
          display: block;
      }
    </style>
  </head>

  <body class="fondo"
    style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <?php include 'NewNavBar.php'; ?>

    <div class="modal fade modal-registrar" id="registrarProductoModal" tabindex="-1" role="dialog"
      aria-labelledby="registrarProductoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <?php if (!$mostrarFormulario): ?>
            <div class="alert alert-warning mt-4">
              Debe registrar una categoría antes de crear un producto.
            </div>
          <?php else: ?>
            <!-- Formulario de registro de producto -->
            <form id="incluirProductoForm" method="POST" enctype="multipart/form-data" novalidate>
              <div class="modal-header">
                <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Producto" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                <h5 class="titulo-form" id="registrarProductoModalLabel">Incluir Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="accion" value="ingresar">
                <div class="grupo-form">
                  <div class="grupo-interno">
                    <label for="nombre_producto">Nombre del producto*</label>
                    <input type="text" placeholder="Ej: Impresora" maxlength="20" class="control-form"
                      id="nombre_producto" name="nombre_producto" required>
                    <span class="span-value" id="snombre_producto"></span>
                  </div>
                  <div class="grupo-interno">
                    <label for="modelo">Modelo/Marca*</label>
                    <select class="form-select" id="modelo" name="modelo" required>
                      <option value="">Seleccione un modelo</option>
                      <?php foreach ($modelos as $modelo): ?>
                        <option value="<?= $modelo['tbl_modelos']; ?>">
                          <?= $modelo['nombre_modelo'] . ' (' . $modelo['tbl_marcas'] . ')' ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <span class="span-value" id="smodelo"></span>
                  </div>
                </div>
                <div class="envolver-form">
                  <label for="imagen">Imagen del producto (JPG, PNG, etc)*</label>
                  <input type="file" class="form-control" name="imagen" id="imagen" accept="image/*" required>
                  <span class="span-value" id="simagen"></span>
                  <div id="previewImagen" style="margin-top: 10px;">
                    <img id="imagenPreview" src="#" alt="vista previa"
                      style="display: none; max-height: 150px; border-radius: 8px; border: 1px solid #ccc; padding: 5px;">
                  </div>
                </div>
                <div class="envolver-form">
                  <label for="descripcion_producto">Descripción del producto*</label>
                  <textarea maxlength="50" class="form-control" id="descripcion_producto" name="descripcion_producto"
                    rows="2" placeholder="Ej: Multifuncional a color con WiFi"></textarea>
                  <span class="span-value" id="sdescripcion_producto"></span>
                </div>
                <div class="envolver-form" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                  <div style="flex: 1;">
                    <label for="Stock_Actual">Stock Actual*</label>
                    <input type="number" class="control-form" id="Stock_Actual" name="Stock_Actual" min="1" required
                      placeholder="Ej: 10" maxlength="3">
                    <span class="span-value" id="sStock_Actual"></span>
                  </div>
                  <div style="flex: 1;">
                    <label for="Stock_Maximo">Stock Máximo*</label>
                    <input type="number" class="control-form" id="Stock_Maximo" name="Stock_Maximo" min="1" required
                      placeholder="Ej: 100" maxlength="3">
                    <span class="span-value" id="sStock_Maximo"></span>
                  </div>
                  <div style="flex: 1;">
                    <label for="Stock_Minimo">Stock Mínimo*</label>
                    <input type="number" class="control-form" id="Stock_Minimo" name="Stock_Minimo" min="1" required
                      placeholder="Ej: 5" maxlength="3">
                    <span class="span-value" id="sStock_Minimo"></span>
                  </div>
                </div>
                <div class="envolver-form">
                  <label for="Clausula_garantia">Cláusula de garantía*</label>
                  <textarea class="form-control" maxlength="50" id="Clausula_garantia" name="Clausula_garantia" rows="2"
                    placeholder="Ej: Garantía válida por 6 meses en defectos de fábrica."></textarea>
                  <span class="span-value" id="sClausula_garantia"></span>
                </div>
                <div class="envolver-form">
                  <label for="Categoria">Categoría*</label>
                  <select class="form-select" id="Categoria" name="Categoria" required>
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categoriasDinamicas as $cat): ?>
                      
                      <option value="<?= $cat['tabla'] ?>" data-tabla="<?= $cat['tabla'] ?>">
                        <?= htmlspecialchars($cat['nombre_categoria']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <span class="span-value" id="sCategoria"></span>
                </div>
                <input type="hidden" id="tabla_categoria" name="tabla_categoria">
                <div id="caracteristicasCategoria"></div>
                <div class="grupo-form">
                  <div class="grupo-interno">
                    <label for="Seriales">Código Serial*</label>
                    <input type="text" class="control-form" id="Seriales" name="Seriales" maxlength="10"
                      placeholder="Ej: EPSON1234" required>
                    <span class="span-value" id="sSeriales"></span>
                  </div>
                  <div class="grupo-interno">
                    <label for="Precio">Precio ($)*</label>
                    <input class="control-form" id="Precio" name="Precio" min="1" step="1" placeholder="Ej: 100" required>
                    <span class="span-value" id="sPrecio"></span>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button class="boton-form btn-primary" type="submit">Registrar</button>
                <button class="boton-reset btn-primary" type="reset">Limpiar</button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="contenedor-tabla">

      <div class="tabla-header">
        <div class="ghost"></div>
      
        <h3>Listado de Productos</h3>


        <div class="filtro-status">
            <label for="filtro-estatus-productos">Mostrar:</label>
            <select id="filtro-estatus-productos" class="form-select">
                <option value="todos" selected>Todos</option>
                <option value="habilitado">Habilitados</option>
                <option value="inhabilitado">Inhabilitados</option>
            </select>
        </div>
      </div>

      <table class="tablaConsultas" id="tablaConsultas">
        <thead>
          <tr>
            <th>ID</th>
            <th>Imagen</th>
            <th>Producto</th>
            <th>Modelo</th>
            <th>Marca</th>
            <th>Stock <br> Actual</th>
            <th>Serial</th>
            <th>Precio</th>
            <th>Estatus</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

              <?php foreach ($productos as $producto): ?>
              <?php $ruta_imagen = $producto['imagen'] ?>
   
              
            <tr data-id="<?php echo $producto['id_producto']; ?>">
              <td>
                <span class="campo-numeros">
                  <?php echo htmlspecialchars($producto['id_producto']); ?>
                </span>
              </td>
              <td>
                <?php
               
                  echo '<img src="' . htmlspecialchars($producto['imagen']) . '" alt="Foto del producto" class="foto-producto">';
                
                ?>
              </td>
              <td>
                <span class="campo-nombres">
                  <?php echo htmlspecialchars($producto['nombre_producto']); ?>
                </span>
              </td>
              <td>
                <span class="campo-nombres">
                  <?php echo htmlspecialchars($producto['nombre_modelo']); ?>
                </span>
              </td>
              <td>
                <span class="campo-nombres">
                  <?php echo htmlspecialchars($producto['nombre_marca']); ?>
                </span>
              </td>
              <td>
                <span class="campo-numeros">
                  <?php echo htmlspecialchars($producto['stock']); ?>
                </span>
              </td>
              <td>
                <span class="campo-numeros">
                  <?php echo htmlspecialchars($producto['serial']); ?>
                </span>
              </td>
              <td>
                <span class="precio"><?php echo htmlspecialchars($producto['precio']); ?></span>
                <span class="moneda">USD</span>
              </td>
              <td>
                <span
                  class="campo-estatus <?php echo ($producto['estado'] == 'habilitado') ? 'habilitado' : 'inhabilitado'; ?>"
                  onclick="cambiarEstatus(<?php echo $producto['id_producto']; ?>, '<?php echo $producto['estado']; ?>')"
                  style="cursor: pointer;"
                  title="Cambiar Estatus">
                  <?php echo htmlspecialchars($producto['estado']); ?>
                </span>
              </td>
              <td>
                <ul>
                    <?php
                    $caracteristicas = $producto['caracteristicas'] ?? [];
                    $atributosExtra = '';
                    foreach ($caracteristicas as $clave => $valor) {
                        if ($clave === 'id' || $clave === 'id_producto') continue; // omitir claves técnicas
                        // camelCase para JS
                        $claveCamel = preg_replace_callback('/_([a-z])/', function ($m) { return strtoupper($m[1]); }, strtolower($clave));
                        $atributosExtra .= ' data-' . $claveCamel . '="' . htmlspecialchars($valor) . '"';
                    }
                    ?>
                    <button class="btn-detalle"
                        title="Detallar"
                        data-imagendtl="<?= isset($ruta_imagen) ? htmlspecialchars($ruta_imagen) : ''; ?>"
                        data-iddtl="<?= isset($producto['id_producto']) ? htmlspecialchars($producto['id_producto']) : ''; ?>"
                        data-nombredtl="<?= isset($producto['nombre_producto']) ? htmlspecialchars($producto['nombre_producto']) : ''; ?>"
                        data-modelodtl="<?= isset($producto['nombre_modelo']) ? htmlspecialchars($producto['nombre_modelo']) : ''; ?>"
                        data-marcadtl="<?= isset($producto['nombre_marca']) ? htmlspecialchars($producto['nombre_marca']) : ''; ?>"
                        data-descripciondtl="<?= isset($producto['descripcion_producto']) ? htmlspecialchars($producto['descripcion_producto']) : ''; ?>"
                        data-stockactualdtl="<?= htmlspecialchars($producto['stock']); ?>"
                        data-stockmaximodtl="<?= htmlspecialchars($producto['stock_maximo']); ?>"
                        data-stockminimodtl="<?= htmlspecialchars($producto['stock_minimo']); ?>"
                        data-serialdtl="<?= htmlspecialchars($producto['serial']); ?>"
                        data-clausuladtl="<?= htmlspecialchars($producto['clausula_garantia']); ?>"
                        data-categoriadtl="<?= htmlspecialchars($producto['nombre_categoria']); ?>"
                        data-preciodtl="<?= htmlspecialchars($producto['precio']); ?>"
                        data-estatusdtl="<?php echo htmlspecialchars($producto['estado']); ?>">
                        <img src="assets/img/eye.svg">
                    </button>
                    <button class="btn-modificar"
                        title="Modificar Producto"
                        data-id="<?= htmlspecialchars($producto['id_producto']); ?>"
                        data-nombre="<?= htmlspecialchars($producto['nombre_producto']); ?>"
                        data-descripcion="<?= htmlspecialchars($producto['descripcion_producto']); ?>"
                        data-modelo="<?= htmlspecialchars($producto['nombre_modelo']); ?>"
                        data-marca="<?= htmlspecialchars($producto['nombre_marca']); ?>"
                        data-modeloid="<?= htmlspecialchars($producto['id_modelo']); ?>"
                        data-stockactual="<?= htmlspecialchars($producto['stock']); ?>"
                        data-stockmaximo="<?= htmlspecialchars($producto['stock_maximo']); ?>"
                        data-stockminimo="<?= htmlspecialchars($producto['stock_minimo']); ?>"
                        data-seriales="<?= htmlspecialchars($producto['serial']); ?>"
                        data-clausula="<?= htmlspecialchars($producto['clausula_garantia']); ?>"
                        data-categoria="<?= htmlspecialchars('cat_' . strtolower(str_replace(' ', '_', $producto['nombre_categoria']))); ?>"
                        data-tabla_categoria="<?= htmlspecialchars('cat_' . strtolower(str_replace(' ', '_', $producto['nombre_categoria']))); ?>"
                        data-precio="<?= htmlspecialchars($producto['precio']); ?>"
                        data-imagen="<?= htmlspecialchars($ruta_imagen); ?>"
                        <?= $atributosExtra; ?>>
                        <img src="assets/img/pencil.svg">
                    </button>
                    <button class="btn-eliminar eliminar"
                        title="Eliminar Producto"
                        data-id="<?php echo $producto['id_producto']; ?>">
                        <img src="assets/img/circle-x.svg">
                    </button>
                </ul>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="modalDetallesProducto" class="modal-detalles" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="titulo-form">Detalles del Producto</h5>
                    <button type="button" class="close" id="cerrarModalDetalles">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group fila-dato">
                        <label>Foto del Producto:</label>
                        <p id="detalle-imagen"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>ID:</label>
                        <p id="detalle-id"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Producto:</label>
                        <p id="detalle-nombre"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Modelo:</label>
                        <p id="detalle-modelo"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Marca:</label>
                        <p id="detalle-marca"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Descripción:</label>
                        <p id="detalle-descripcion"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Stock Actual:</label>
                        <p id="detalle-stockactual"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Stock Máximo:</label>
                        <p id="detalle-stockmaximo"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Stock Mínimo:</label>
                        <p id="detalle-stockminimo"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Serial:</label>
                        <p id="detalle-serial"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Clausula de Garantia:</label>
                        <p id="detalle-clausula"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Categoria:</label>
                        <p id="detalle-categoria"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Precio:</label>
                        <p id="detalle-precio"></p>
                    </div>
                    <div class="form-group fila-dato">
                        <label>Estatus:</label>
                        <p id="detalle-estatus"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modificación -->
    <div class="modal fade modal-modificar" id="modificarProductoModal" tabindex="-1" role="dialog"
      aria-labelledby="modificarProductoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <form id="modificarProductoForm" method="POST" enctype="multipart/form-data" novalidate>
            <div class="modal-header">
              <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Producto" data-contexto="modificar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
              <h5 class="titulo-form" id="modificarProductoModalLabel">Modificar Producto</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="accion" value="modificar">
              <input type="hidden" id="modificarIdProducto" name="id_producto">
              <input type="hidden" id="modificar_tabla_categoria" name="tabla_categoria">

              <div class="form-group">
                <label for="modificarNombreProducto">Nombre del producto*</label>
                <input type="text" class="form-control" id="modificarNombreProducto" name="nombre_producto" maxlength="20" required>
                <span class="span-value" id="smodificarNombreProducto"></span>
              </div>

              <div class="form-group">
                <label>Imagen actual</label><br>
                <img id="modificarImagenPreview" src="#" alt="Imagen actual"
                  style="max-height: 120px; border-radius: 8px; border: 1px solid #ccc; padding: 5px; margin-bottom: 10px;">
              </div>

              <div class="form-group">
                <label for="modificarImagen">Cambiar imagen</label>
                <input type="file" class="form-control" id="modificarImagen" name="imagen" accept="image/*">
                <span class="span-value" id="smodificarImagen"></span>
              </div>

              <div class="form-group">
                <label for="modificarDescripcionProducto">Descripción del producto*</label>
                <textarea class="form-control" id="modificarDescripcionProducto" name="descripcion_producto" rows="2" maxlength="50" required></textarea>
                <span class="span-value" id="smodificarDescripcionProducto"></span>
              </div>

              <div class="form-group">
                  <label for="modificarModelo">Modelo*</label>
                  <select class="form-select" id="modificarModelo" name="modelo" required>
                    <option value="">Seleccionar modelo</option>
                    <?php foreach ($modelos as $modelo): ?>
                      <option value="<?= $modelo['tbl_modelos'] ?>"><?= $modelo['nombre_modelo'] ?></option>
                    <?php endforeach; ?>
                  </select>
                  <span class="span-value" id="smodificarModelo"></span>
              </div>

              <div class="form-row" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="form-group col-md-4" style="flex: 1;">
                  <label for="modificarStockActual">Stock Actual</label>
                  <input type="number" min="0" class="form-control" id="modificarStockActual" name="Stock_Actual"
                    maxlength="3" required>
                </div>
                <div class="form-group col-md-4" style="flex: 1;">
                  <label for="modificarStockMaximo">Stock Máximo</label>
                  <input type="number" min="0" class="form-control" id="modificarStockMaximo" name="Stock_Maximo"
                    maxlength="3" required>
                </div>
                <div class="form-group col-md-4" style="flex: 1;">
                  <label for="modificarStockMinimo">Stock Mínimo</label>
                  <input type="number" min="0" class="form-control" id="modificarStockMinimo" name="Stock_Minimo"
                    maxlength="3" required>
                </div>
              </div>

              <div class="form-group">
                <label for="modificarClausulaGarantia">Cláusula de Garantía*</label>
                <textarea class="form-control" id="modificarClausulaGarantia" name="Clausula_garantia" rows="2" maxlength="50" required></textarea>
                <span class="span-value" id="smodificarClausulaGarantia"></span>
              </div>

              <div class="form-row" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="form-group col-md-5" style="flex: 1;">
                  <label for="modificarSeriales">Código Serial</label>
                  <input type="text" maxlength="10" class="form-control" id="modificarSeriales" name="Seriales" required>
                </div>
                <div class="form-group col-md-4" style="flex: 1;">
                  <label for="modificarPrecio">Precio ($)</label>
                  <input min="0" class="form-control" id="modificarPrecio" name="Precio" required>
                </div>
              </div>

              <div class="form-group">
                  <label for="modificarCategoria">Categoría*</label>
                  <select class="form-select" id="modificarCategoria" name="Categoria" required>
                    <option value="">Seleccionar Categoría</option>
                    <?php foreach ($categoriasDinamicas as $cat): ?>
                      <option value="<?= $cat['tabla'] ?>" data-tabla="<?= $cat['tabla'] ?>">
                        <?= htmlspecialchars($cat['nombre_categoria']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <span class="span-value" id="smodificarCategoria"></span>
              </div>

              <div id="caracteristicasCategoriaModificar"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="container mt-4">
      <!-- Formulario de parámetros para el reporte -->
      <div class="reporte-container" style="max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.1);">
        
        <div class="reporte-parametros" style="margin-bottom: 30px; text-align:center;">
            <div class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                <label for="tipoReporte" class="title-select">Tipo de reporte:</label>
                <select id="tipoReporte" class="selector-reporte">
                    <option value="mas_vendidos">Top Productos Más Vendidos</option>
                    <option value="stock">Stock Alto vs Bajo</option>
                    <option value="rotacion">Rotación de Productos</option>
                </select>

                <label for="tipoGrafica" class="title-select">Tipo de gráfica:</label>
                <select id="tipoGrafica" class="selector-reporte">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Rosca</option>
                    <option value="polarArea">Área Polar</option>
                </select>

                <!-- Parámetros dinámicos -->
                <div id="parametrosExtra" class="title-select" style="display:flex; gap:10px; flex-wrap: wrap; align-items:center;">
                    <!-- Aquí se insertan dinámicamente los inputs según el reporte -->
                </div>

                <button id="generarReporteBtn" class="btn btn-primary">Generar Reporte</button>
                <button id="descargarPDF" class="btn btn-primary">Descargar PDF</button>
            </div>
        </div>

        <!-- Contenedor del Reporte -->
        <h3 id="tituloReporte" style="text-align: center; margin-bottom: 20px; color:#1f66df;">Reporte de Productos</h3>
        
        <!-- Secciones de reportes -->
        <div id="reporteMasVendidos">
            <canvas id="chartMasVendidos" height="400"></canvas>
            <div id="reporteMasVendidosDetalle"></div>
        </div>
        
        <div id="reporteStock" style="display:none;">
            <h5>Stock Alto vs Bajo</h5>
            <canvas id="chartStock" height="400"></canvas>
  <div id="reporteStockDetalle"></div>
        </div>
        
        <div id="reporteRotacion" style="display:none;">
            <h5>Rotación de Productos (días promedio)</h5>
            <canvas id="chartRotacion" height="400"></canvas>
            <div id="reporteRotacionDetalle"></div> 
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/javascript/sweetalert2.all.min.js"></script>
<script src="assets/javascript/validaciones.js"></script>
<script src="assets/public/js/chart.js"></script>
<script src="assets/public/js/html2canvas.min.js"></script>
<script src="assets/public/js/jspdf.umd.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>
<script src="assets/javascript/producto.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
    // Datos desde PHP en arrays
    const masVendidos = <?php echo json_encode($masVendidos); ?>;
    const stockProductos = <?php echo json_encode($stockProductos); ?>;
    const rotacion = <?php echo json_encode($rotacion); ?>;
    const categorias = <?php echo json_encode($categorias); ?>; // <- array de categorías desde PHP

    // Variables para gráficos
    let chartVendidos = null;
    let chartStock = null;
    let chartRotacion = null;

    // --- Función para mostrar parámetros dinámicos ---
    function mostrarParametrosExtra(tipoReporte) {
        const contenedor = document.getElementById('parametrosExtra');
        contenedor.innerHTML = ""; // limpiar

        if (tipoReporte === "mas_vendidos") {
            contenedor.innerHTML = `
                <label for="topN">Top N:</label>
                <input type="number" id="topN" value="10" min="1" class="selector-reporte">
            `;
        } else if (tipoReporte === "stock") {
            let options = categorias.map(cat => `<option value="${cat.id_categoria}">${cat.nombre_categoria}</option>`).join('');
            contenedor.innerHTML = `
                <label for="categoriaStock">Categoría:</label>
                <select id="categoriaStock" class="form-select" style="width:200px;">
                    <option value="todas">Todas</option>
                    ${options}
                </select>
            `;
        }
    }

    // Función para mostrar un reporte específico
    function mostrarReporte(tipoReporte, tipoGrafica) {
        document.getElementById('reporteMasVendidos').style.display = 'none';
        document.getElementById('reporteStock').style.display = 'none';
        document.getElementById('reporteRotacion').style.display = 'none';
        
        if (tipoReporte === 'mas_vendidos') {
            document.getElementById('reporteMasVendidos').style.display = 'block';
            document.getElementById('tituloReporte').textContent = 'Top Productos Más Vendidos';
        } else if (tipoReporte === 'stock') {
            document.getElementById('reporteStock').style.display = 'block';
            document.getElementById('tituloReporte').textContent = 'Stock Alto vs Bajo';
        } else if (tipoReporte === 'rotacion') {
            document.getElementById('reporteRotacion').style.display = 'block';
            document.getElementById('tituloReporte').textContent = 'Rotación de Productos (días promedio)';
        }

        generarGrafico(tipoReporte, tipoGrafica);
    }

    // Función para generar gráficos
// Función para generar tabla debajo del gráfico
function generarTablaDetalle(containerId, headers, rows) {
    const container = document.getElementById(containerId + "Detalle");
    let html = `
        <div class="table-responsive mt-3">
            <table class="table table-bordered table-striped">
                <thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead>
                <tbody>
    `;

    rows.forEach(r => {
        html += "<tr>";
        r.forEach(val => {
            html += `<td>${val}</td>`;
        });
        html += "</tr>";
    });

    html += "</tbody></table></div>";
    container.innerHTML = html;
}

// Función para generar gráficos + tablas
function generarGrafico(tipoReporte, tipoGrafica) {
    switch(tipoReporte) {
        case 'mas_vendidos':
            if (chartVendidos) chartVendidos.destroy();

            const topN = parseInt(document.getElementById("topN")?.value || 10);
            const dataTop = masVendidos.slice(0, topN);

            const labelsVendidos = dataTop.map(item => item.nombre_producto);
            const valuesVendidos = dataTop.map(item => item.total_vendido);
            
            chartVendidos = new Chart(document.getElementById("chartMasVendidos"), {
                type: tipoGrafica,
                data: {
                    labels: labelsVendidos,
                    datasets: [{
                        label: "Unidades Vendidas",
                        data: valuesVendidos,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 🔹 Tabla debajo
            generarTablaDetalle("reporteMasVendidos",
                ["Producto", "Unidades Vendidas"],
                dataTop.map(item => [item.nombre_producto, item.total_vendido])
            );
            break;

        case 'stock':
            if (chartStock) chartStock.destroy();

            const categoriaSeleccionada = document.getElementById("categoriaStock")?.value || "todas";
            let dataStock = stockProductos;

            if (categoriaSeleccionada !== "todas") {
                dataStock = stockProductos.filter(item => item.id_categoria == categoriaSeleccionada);
            }

            const altoStock = dataStock.filter(item => item.categoria_stock === "Alto Stock").length;
            const bajoStock = dataStock.filter(item => item.categoria_stock === "Bajo Stock").length;
            
            chartStock = new Chart(document.getElementById("chartStock"), {
                type: tipoGrafica,
                data: {
                    labels: ["Alto Stock", "Bajo Stock"],
                    datasets: [{
                        data: [altoStock, bajoStock],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderWidth: 1
                        
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 🔹 Tabla debajo con categoría incluida
            generarTablaDetalle("reporteStock",
                ["Producto", "Stock Actual", "Stock Mínimo", "Estatus", "Categoría"],
                dataStock.map(item => {
                    const categoria = categorias.find(c => c.id_categoria == item.id_categoria);
                    return [
                        item.nombre_producto,
                        item.stock,
                        item.stock_minimo,
                        item.categoria_stock,
                        categoria ? categoria.nombre_categoria : "Sin categoría"
                    ];
                })
            );
            break;

        case 'rotacion':
            if (chartRotacion) chartRotacion.destroy();

            const labelsRotacion = rotacion.map(item => item.nombre_producto);
            const valuesRotacion = rotacion.map(item => item.dias_promedio);
            
            chartRotacion = new Chart(document.getElementById("chartRotacion"), {
                type: tipoGrafica,
                data: {
                    labels: labelsRotacion,
                    datasets: [{
                        label: "Días promedio en inventario",
                        data: valuesRotacion,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 🔹 Tabla debajo
            generarTablaDetalle("reporteRotacion",
                ["Producto", "Días en Inventario"],
                rotacion.map(item => [item.nombre_producto, item.dias_promedio])
            );
            break;
    }
}


    // Event Listeners
    document.getElementById('generarReporteBtn').addEventListener('click', function() {
        const tipoReporte = document.getElementById('tipoReporte').value;
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        mostrarReporte(tipoReporte, tipoGrafica);
    });

    document.getElementById('tipoReporte').addEventListener('change', function() {
        mostrarParametrosExtra(this.value);
    });

    // Inicializar con el primer reporte
    window.onload = function() {
        const tipoReporte = document.getElementById('tipoReporte').value;
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        mostrarParametrosExtra(tipoReporte);
        mostrarReporte(tipoReporte, tipoGrafica);
    };

    // Descargar PDF (placeholder)
    document.getElementById('descargarPDF').addEventListener('click', function() {
        alert('Función de descarga de PDF será implementada próximamente');
    });
</script>


    <script>
      const categoriasDinamicas = <?php echo json_encode($categoriasDinamicas); ?>;

      $('#Categoria').on('change', function () {
        const tabla = $(this).find(':selected').data('tabla');
        $('#tabla_categoria').val(tabla)
        const categoria = categoriasDinamicas.find(cat => cat.tabla === tabla);
        const contenedor = $('#caracteristicasCategoria');
        contenedor.empty();

        if (categoria) {
          categoria.caracteristicas.forEach(carac => {
            let input = '';
           if (carac.tipo === 'int' || carac.tipo === 'float') {
    if (carac.tipo === 'int') {
        input = `<input type="number" min="1" step="1" class="form-control" name="carac[${carac.nombre}]" placeholder="${carac.nombre}" required>`;
    } else {
        input = `<input type="number" min="0.01" step="0.01" class="form-control" name="carac[${carac.nombre}]" placeholder="${carac.nombre}" required>`;
    }
} else {
    input = `<input type="text" class="form-control" name="carac[${carac.nombre}]" maxlength="${carac.max}" placeholder="${carac.nombre}" oninput="soloTextoPermitido(event)" required>`;
}
            contenedor.append(`<div class="mb-2"><label>${carac.nombre}</label>${input}</div>`);
          });
        }
      });

      $('#modificarCategoria').on('change', function () {
    const tabla = $(this).val();
    $('#modificar_tabla_categoria').val(tabla);
    const categoria = categoriasDinamicas.find(cat => cat.tabla === tabla);
    const contenedor = $('#caracteristicasCategoriaModificar');
    contenedor.empty();

    if (categoria) {
        categoria.caracteristicas.forEach(carac => {
            let input = '';
            if (carac.tipo === 'int' || carac.tipo === 'float') {
                input = `<input type="number" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" placeholder="${carac.nombre}" ${carac.tipo === 'int' ? 'step="1"' : 'step="0.01"'} required>`;
            } else {
                input = `<input type="text" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" maxlength="${carac.max}" placeholder="${carac.nombre}" required>`;
            }
            contenedor.append(`<div class="mb-2 col-md-6"><label>${carac.nombre}</label>${input}</div>`);
        });
    }
});
    </script>
    
    <script>

$(document).ready(function () {
    // Genera los campos dinámicos al cambiar la categoría en el modal de modificar
$('#modificarCategoria').on('change', function () {
    const tabla = $(this).val();
    $('#modificar_tabla_categoria').val(tabla);
    const categoria = categoriasDinamicas.find(cat => cat.tabla === tabla);
    const contenedor = $('#caracteristicasCategoriaModificar');
    contenedor.empty();

    if (categoria) {
categoria.caracteristicas.forEach(carac => {
    let input = '';
if (carac.tipo === 'int' || carac.tipo === 'float') {
    if (carac.tipo === 'int') {
        input = `<input type="number" min="1" step="1" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" placeholder="${carac.nombre}" required>`;
    } else {
        input = `<input type="number" min="0.01" step="0.01" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" placeholder="${carac.nombre}" required>`;
    }
} else {
    input = `<input type="text" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" maxlength="${carac.max}" placeholder="${carac.nombre}" required>`;
}
    contenedor.append(`<div class="mb-2 col-md-6"><label>${carac.nombre}</label>${input}</div>`);
});
    }
});
});

    </script>
    <button 
        class="btn-grafica"
        title="Visualizar Reportes"
        onclick="window.location.href='?pagina=reporteProductos'">
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