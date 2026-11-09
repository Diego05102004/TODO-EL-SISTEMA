<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión (sistema tradicional)
if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

// Verificar y/o generar token JWT (sistema JWT)
require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en producto: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'producto';
$titulo_pagina = 'Gestión de Productos';
$permisosProducto = is_array($permisosUsuario ?? null) ? $permisosUsuario : [];
$puedeConsultarProductos = !empty($permisosProducto['consultar']);
$puedeIncluirProductos = !empty($permisosProducto['incluir']) || !empty($permisosProducto['ingresar']);
$puedeModificarProductos = !empty($permisosProducto['modificar']);
$puedeEliminarProductos = !empty($permisosProducto['eliminar']);

if (!$puedeConsultarProductos) {
    header('Location: ?pagina=acceso-denegado');
    exit();
}

// Iniciar el buffer de contenido
ob_start();
?>

<?php
// Cálculos previos para summary cards
$productos = is_array($productos ?? null) ? $productos : [];
foreach ($productos as &$producto) {
    $producto['stock_actual'] = (int)($producto['stock_actual'] ?? $producto['stock'] ?? 0);
    $producto['stock_minimo'] = (int)($producto['stock_minimo'] ?? 0);
}
unset($producto);

$total_productos = count($productos ?? []);
$stock_bajo = count(array_filter($productos, function($p) { return $p['stock_actual'] <= $p['stock_minimo']; }));
$stock_estable = count(array_filter($productos, function($p) { return $p['stock_actual'] > $p['stock_minimo'] && $p['stock_actual'] < $p['stock_maximo']; }));
$stock_excedido = count(array_filter($productos, function($p) { return $p['stock_actual'] >= $p['stock_maximo']; }));


// Calcular porcentajes
$porcentaje_total = 100; // Siempre 100% para el total
$porcentaje_stock_bajo = $total_productos > 0 ? round(($stock_bajo / $total_productos) * 100) : 0;
$porcentaje_stock_estable = $total_productos > 0 ? round(($stock_estable / $total_productos) * 100) : 0;
$porcentaje_stock_excedido = $total_productos > 0 ? round(($stock_excedido / $total_productos) * 100) : 0;
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script src="assets/public/js/chart.js"></script>
<script src="assets/javascript/producto.js"></script>

<!-- Summary Cards para Productos -->
<div class="summary-cards">
    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-chart-down"></i></div>
        <div class="card-content">
            <h3>Stock Bajo</h3>
            <p class="card-value"><?php echo $stock_bajo; ?></p>
            <div class="progress-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="circle" stroke-dasharray="<?php echo $porcentaje_stock_bajo; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="percentage"><?php echo $porcentaje_stock_bajo; ?>%</span>
            </div>
        </div>
    </div>

    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-chart-down"></i></div>
        <div class="card-content">
            <h3>Stock Estable</h3>
            <p class="card-value"><?php echo $stock_estable; ?></p>
            <div class="progress-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="circle" stroke-dasharray="<?php echo $porcentaje_stock_estable; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="percentage"><?php echo $porcentaje_stock_estable; ?>%</span>
            </div>
        </div>
    </div>

    <div class="summary-card expenses">
        <div class="card-icon"><i class="fas fa-chart-down"></i></div>
        <div class="card-content">
            <h3>Stock Excedido</h3>
            <p class="card-value"><?php echo $stock_excedido; ?></p>
            <div class="progress-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="circle" stroke-dasharray="<?php echo $porcentaje_stock_excedido; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="percentage"><?php echo $porcentaje_stock_excedido; ?>%</span>
            </div>
        </div>
    </div>
</div>

            <section class="analytics-section" aria-labelledby="analyticsTitle">
                <div class="analytics-header">
                    <div>
                        <span class="analytics-eyebrow"><i class="fas fa-brain" aria-hidden="true"></i> Analítica local</span>
                        <h2 id="analyticsTitle">Asistente inteligente de inventario</h2>
                        <p>Explora demanda real de facturas pagadas, patrones de compra y señales de reposición.</p>
                    </div>
                    <span class="analytics-source">Fuente: facturas pagadas</span>
                </div>

                <div class="analytics-filters">
                    <div class="analytics-filter">
                        <label for="analyticsProduct">Producto</label>
                        <select id="analyticsProduct">
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo (int)$producto['id_producto']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre_producto'] ?? 'Producto', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <span class="analytics-or">o</span>
                    <div class="analytics-filter">
                        <label for="analyticsCategory">Categoría</label>
                        <select id="analyticsCategory">
                            <option value="">Seleccionar categoría</option>
                            <?php foreach (($categorias ?? []) as $categoria): ?>
                                <option value="<?php echo (int)$categoria['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre_categoria'] ?? 'Categoría', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="analytics-filter">
                        <label for="analyticsFrequency">Frecuencia</label>
                        <select id="analyticsFrequency">
                            <option value="diaria">Diaria</option>
                            <option value="semanal">Semanal</option>
                            <option value="mensual">Mensual</option>
                        </select>
                    </div>
                    <div class="analytics-filter">
                        <label for="analyticsHorizon">Horizonte</label>
                        <select id="analyticsHorizon">
                            <option value="15">15 días</option>
                            <option value="30" selected>30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                        </select>
                    </div>
                    <button type="button" class="analytics-button" id="runAnalytics">
                        <i class="fas fa-chart-line" aria-hidden="true"></i> Analizar
                    </button>
                    <?php if ($puedeModificarProductos): ?>
                        <button type="button" class="analytics-button analytics-train" id="trainAnalytics">
                            <i class="fas fa-rotate" aria-hidden="true"></i> Entrenar modelo local
                        </button>
                    <?php endif; ?>
                </div>

                <p class="analytics-status" id="analyticsStatus" role="status" aria-live="polite">
                    El análisis general del inventario se ejecuta automáticamente al entrar. Puedes filtrar por producto o categoría y pulsar «Analizar».
                </p>

                <div class="analytics-kpis" id="analyticsKpis" hidden>
                    <article class="analytics-kpi"><span>Venta proyectada</span><strong data-metric="venta_proyectada">—</strong><small>unidades en el horizonte</small></article>
                    <article class="analytics-kpi"><span>Días hasta agotamiento</span><strong data-metric="dias_estimados_agotamiento">—</strong><small>estimación con stock actual</small></article>
                    <article class="analytics-kpi"><span>Punto de reorden</span><strong data-metric="punto_reorden">—</strong><small>stock mínimo configurado</small></article>
                    <article class="analytics-kpi"><span>Compra sugerida</span><strong data-metric="cantidad_sugerida_compra">—</strong><small>no descuenta órdenes abiertas</small></article>
                    <article class="analytics-kpi"><span>Rotación proyectada</span><strong data-metric="rotacion_proyectada">—</strong><small>veces en el horizonte</small></article>
                    <!-- <article class="analytics-kpi"><span>Explicaciones</span><strong data-metric="explicaciones">—</strong><small>del modelo</small></article> -->
                </div>

                <div class="analytics-chart-card">
                    <div class="analytics-chart-heading">
                        <h3>Ventas históricas y demanda estimada</h3>
                        <span>La banda representa el intervalo estimado del modelo.</span>
                    </div>
                    <div class="analytics-chart-frame">
                        <canvas id="analyticsChart" aria-label="Gráfico de ventas y demanda estimada" role="img"></canvas>
                    </div>
                
                </div>

                <div class="analytics-actions">
                    <div class="analytics-chart-heading">
                        <h3>Acciones recomendadas</h3>
                        <span>Las valoraciones ajustan la prioridad futura por tipo de recomendación.</span>
                    </div>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Producto / acción</th>
                                    <th>Señal</th>
                                    <th>Cantidad sugerida</th>
                                    <th>Confianza</th>
                                    <th>Calculado</th>
                                    <th>Valoración</th>
                                </tr>
                            </thead>
                            <tbody id="analyticsRecommendations">
                                <tr><td colspan="6">Aún no hay recomendaciones cargadas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Productos Grid Section -->
            <div class="products-section">
                <div class="section-header">
                    <h2>Inventario de Productos</h2>
                    <div class="section-actions">
                        <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                            <img src="assets/img/info-ayuda.svg">
                        </button>
                        <?php if ($puedeIncluirProductos): ?>
                        <button type="button" class="btn-table-superior btn-incluir" title="Incluir Producto" data-product-action="register" <?php echo empty($categoriasDinamicas) ? 'disabled' : ''; ?>>
                            <img src="assets/img/plus.svg">
                        </button>
                        <?php endif; ?>
                        <button class="btn-table-superior btn-filter" title="Filtrar Productos" data-product-action="filter">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="products-grid">
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $producto): ?>
                            <?php $estadoProducto = $producto['estado'] ?? 'habilitado'; ?>
                            <div class="product-card <?php echo $estadoProducto !== 'habilitado' ? 'producto-inhabilitado' : ''; ?>" data-product-id="<?php echo (int)$producto['id_producto']; ?>" data-estado="<?php echo htmlspecialchars($estadoProducto); ?>">
                                <div class="product-image">
                                    <?php if (!empty($producto['imagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" class="product-img">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    <?php endif; ?>
                                    <?php 
                                    $stock_actual = $producto['stock_actual'] ?? 0;
                                    $stock_minimo = $producto['stock_minimo'] ?? 0;
                                    if ($stock_actual <= $stock_minimo): ?>
                                        <span class="product-badge low-stock">Stock Bajo</span>
                                    <?php elseif ($stock_actual < 10): ?>
                                        <span class="product-badge critical">Crítico</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
                                    <p class="product-category"><?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'Sin categoría'); ?></p>
                                    <p class="product-brand"><?php echo htmlspecialchars($producto['nombre_marca'] ?? 'Sin marca'); ?></p>
                                    <div class="product-price">$<?php echo number_format($producto['precio'] ?? 0, 2); ?></div>
                                    <?php if ($puedeModificarProductos): ?>
                                        <button type="button" class="product-estatus <?php echo htmlspecialchars($estadoProducto); ?>"
                                            data-product-action="toggle-estatus"
                                            data-product-id="<?php echo (int)$producto['id_producto']; ?>"
                                            data-estado="<?php echo htmlspecialchars($estadoProducto); ?>"
                                            title="<?php echo $estadoProducto === 'habilitado' ? 'Clic para inhabilitar el producto' : 'Clic para habilitar el producto'; ?>">
                                            <i class="fas fa-power-off"></i> <?php echo $estadoProducto === 'habilitado' ? 'Habilitado' : 'Inhabilitado'; ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="product-estatus <?php echo htmlspecialchars($estadoProducto); ?>">
                                            <?php echo $estadoProducto === 'habilitado' ? 'Habilitado' : 'Inhabilitado'; ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="product-stock">
                                        <span class="stock-label">Stock:</span>
                                        <span class="stock-value <?php 
                                            if ($stock_actual <= $stock_minimo) echo 'low';
                                            elseif ($stock_actual < 10) echo 'critical';
                                            elseif ($stock_actual > 30) echo 'high';
                                            else echo 'medium';
                                        ?>">
                                            <?php echo $stock_actual; ?> unidades
                                        </span>
                                    </div>
                                    <div class="product-actions">
                                        <?php if ($puedeModificarProductos): ?>
                                        <button type="button" class="btn-action btn-edit" data-product-action="edit" data-product-id="<?php echo (int)$producto['id_producto']; ?>" title="Modificar Producto" aria-label="Modificar Producto"><i class="fas fa-edit"></i></button>
                                        <?php endif; ?>
                                        <?php if ($puedeEliminarProductos): ?>
                                        <button type="button" class="btn-action btn-delete" data-product-action="delete" data-product-id="<?php echo (int)$producto['id_producto']; ?>" title="Eliminar Producto" aria-label="Eliminar Producto"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                        <button type="button" class="btn-action btn-view" data-product-action="view" data-product-id="<?php echo (int)$producto['id_producto']; ?>" title="Ver Producto" aria-label="Ver Producto"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products">
                            <i class="fas fa-box-open"></i>
                            <p>No hay productos registrados en el sistema</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

<!-- Modal para Registrar/Editar Producto -->
<div id="productModal" class="modal" data-categories="<?php echo htmlspecialchars(json_encode($categoriasDinamicas ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>" data-products="<?php echo htmlspecialchars(json_encode($productos ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>" data-can-modify="<?php echo $puedeModificarProductos ? 'true' : 'false'; ?>" data-can-delete="<?php echo $puedeEliminarProductos ? 'true' : 'false'; ?>">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Nuevo Producto</h2>
                </div>
                <button type="button" class="btn-ayuda-modal btn-ayuda" title="Ayuda para Incluir Producto" data-contexto="producto">
                    <img src="assets/img/info-ayuda.svg">
                </button>
            </div>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="id_producto">
                <input type="hidden" name="accion" id="productAction" value="ingresar">
                <input type="hidden" id="productCategoryTable" name="tabla_categoria">

                <div class="form-section">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_producto">Nombre del producto*</label>
                            <input type="text" placeholder="Ej: Impresora" maxlength="20" class="input-textnum"
                            id="nombre_producto" name="nombre_producto" required>
                            <span class="span-value" id="snombre_producto"></span>
                        </div>

                        <div class="form-group">
                            <label for="modelo">Modelo/Marca*</label>
                            <select class="input-select" id="modelo" name="modelo" required>
                            <option value="">Seleccione un modelo</option>
                            <?php foreach ($modelos as $modelo): ?>
                                <option value="<?= (int)$modelo['id_modelo']; ?>">
                                <?= $modelo['nombre_modelo'] . ' (' . $modelo['tbl_marcas'] . ')' ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                            <span class="span-value" id="smodelo"></span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label for="productImage">Imagen del producto (JPG, PNG, etc)*</label>
                        <input type="file" name="imagen" id="productImage" accept="image/*" required>
                        <span class="span-value" id="simagen"></span>
                        <div id="productImagePreviewFrame" class="product-image-preview">
                            <img id="productImagePreview" alt="Vista previa de la imagen seleccionada" hidden>
                            <span id="productImagePreviewPlaceholder">Seleccione una imagen para previsualizarla</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label for="descripcion_producto">Descripción del producto*</label>
                        <textarea maxlength="50" class="text-long" id="descripcion_producto" name="descripcion_producto"
                            rows="2" placeholder="Ej: Multifuncional a color con WiFi"></textarea>
                        <span class="span-value" id="sdescripcion_producto"></span>
                    </div>

                    <div class="form-row" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                        <div class="form-group">
                            <label for="Stock_Actual">Stock Actual*</label>
                            <input type="number" class="input-textnum" id="Stock_Actual" name="Stock_Actual" min="1" required
                            placeholder="Ej: 10" maxlength="3">
                            <span class="span-value" id="sStock_Actual"></span>
                        </div>

                        <div class="form-group">
                            <label for="Stock_Maximo">Stock Máximo*</label>
                            <input type="number" class="input-textnum" id="Stock_Maximo" name="Stock_Maximo" min="1" required
                            placeholder="Ej: 100" maxlength="3">
                            <span class="span-value" id="sStock_Maximo"></span>
                        </div>

                        <div class="form-group">
                            <label for="Stock_Minimo">Stock Mínimo*</label>
                            <input type="number" class="input-textnum" id="Stock_Minimo" name="Stock_Minimo" min="1" required
                            placeholder="Ej: 5" maxlength="3">
                            <span class="span-value" id="sStock_Minimo"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="Clausula_garantia">Cláusula de garantía*</label>
                        <textarea class="text-long" maxlength="50" id="Clausula_garantia" name="Clausula_garantia" rows="2"
                            placeholder="Ej: Garantía válida por 6 meses en defectos de fábrica."></textarea>
                        <span class="span-value" id="sClausula_garantia"></span>
                    </div>

                    <div class="form-group">
                        <label for="productCategory">Categoría*</label>
                        <select class="input-select" id="productCategory" name="Categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categoriasDinamicas as $cat): ?>
                            
                            <option value="<?= $cat['tabla'] ?>" data-tabla="<?= $cat['tabla'] ?>">
                                <?= htmlspecialchars($cat['nombre_categoria']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="span-value" id="sCategoria"></span>
                    </div>
                    <div id="productCategoryFields"></div>
                    
                    <div class="form-row" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                        <div class="form-group">
                            <label for="Seriales">Código Serial*</label>
                            <input type="text" class="input-textnum" id="Seriales" name="Seriales" maxlength="10" placeholder="Ej: EPSON1234" required>
                            <span class="span-value" id="sSeriales"></span>
                        </div>

                        <div class="form-group">
                            <label for="Precio">Precio ($)*</label>
                            <input type="number" class="input-textnum" id="Precio" name="Precio" min="0.01" step="0.01" placeholder="Ej: 100.50" required>
                            <span class="span-value" id="sPrecio"></span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btm-confirmar btn-clear" data-product-action="clear">
                <i class="fas fa-trash-alt"></i> Limpiar
            </button>
            <button type="submit" class="btm-confirmar btn-save" form="productForm">
                <i class="fas fa-check"></i> Registrar
            </button>
        </div>
    </div>
</div>

            <div id="productDetailsModal" class="modal" aria-hidden="true">
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <h2 id="detailProductTitle">Detalles del Producto</h2>
                        <button type="button" class="close-modal" aria-label="Cerrar detalles">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="product-details-layout">
                            <div class="product-details-image-frame">
                                <img id="detailProductImage" alt="Imagen completa del producto" hidden>
                                <div id="detailProductImagePlaceholder" class="image-placeholder">
                                    <i class="fas fa-box-open" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="product-details-grid">
                                <div class="product-detail"><span>ID</span><strong id="detailProductId">-</strong></div>
                                <div class="product-detail"><span>Nombre</span><strong id="detailProductName">-</strong></div>
                                <div class="product-detail"><span>Categoría</span><strong id="detailProductCategory">-</strong></div>
                                <div class="product-detail"><span>Modelo</span><strong id="detailProductModel">-</strong></div>
                                <div class="product-detail"><span>Marca</span><strong id="detailProductBrand">-</strong></div>
                                <div class="product-detail"><span>Precio</span><strong id="detailProductPrice">-</strong></div>
                                <div class="product-detail"><span>Stock actual</span><strong id="detailProductStock">-</strong></div>
                                <div class="product-detail"><span>Stock mínimo</span><strong id="detailProductMinStock">-</strong></div>
                                <div class="product-detail"><span>Stock máximo</span><strong id="detailProductMaxStock">-</strong></div>
                                <div class="product-detail"><span>Serial</span><strong id="detailProductSerial">-</strong></div>
                                <div class="product-detail"><span>Estado</span><strong id="detailProductStatus">-</strong></div>
                                <div class="product-detail product-detail-wide"><span>Descripción</span><strong id="detailProductDescription">-</strong></div>
                                <div class="product-detail product-detail-wide"><span>Garantía</span><strong id="detailProductWarranty">-</strong></div>
                                <div id="detailProductCharacteristics" class="product-details-grid product-detail-wide"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" id="closeProductDetails">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Productos */
                .analytics-section {
                    margin: 28px 0 36px;
                    padding: 24px;
                    border: 1px solid #dbeafe;
                    border-radius: 18px;
                    background: #fff;
                    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
                }

                .analytics-header, .analytics-chart-heading {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 16px;
                }

                .analytics-header h2, .analytics-chart-heading h3 {
                    margin: 6px 0;
                    color: #172554;
                }

                .analytics-header p, .analytics-chart-heading span {
                    margin: 0;
                    color: #64748b;
                    font-size: 0.9rem;
                }

                .analytics-eyebrow {
                    color: #2563eb;
                    font-size: 0.78rem;
                    font-weight: 700;
                    letter-spacing: 0.06em;
                    text-transform: uppercase;
                }

                .analytics-source {
                    padding: 7px 11px;
                    border-radius: 20px;
                    background: #eff6ff;
                    color: #1d4ed8;
                    font-size: 0.8rem;
                    font-weight: 600;
                    white-space: nowrap;
                }

                .analytics-filters {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: flex-end;
                    gap: 12px;
                    margin-top: 20px;
                }

                .analytics-filter {
                    display: grid;
                    flex: 1 1 145px;
                    gap: 6px;
                    min-width: 135px;
                }

                .analytics-filter label {
                    color: #334155;
                    font-size: 0.82rem;
                    font-weight: 600;
                }

                .analytics-filter select {
                    min-height: 42px;
                    padding: 8px 10px;
                    border: 1px solid #cbd5e1;
                    border-radius: 8px;
                    background: #fff;
                    color: #1e293b;
                    font: inherit;
                }

                .analytics-or {
                    padding-bottom: 12px;
                    color: #64748b;
                    font-size: 0.82rem;
                }

                .analytics-button {
                    min-height: 42px;
                    padding: 9px 14px;
                    border: 0;
                    border-radius: 8px;
                    background: #2563eb;
                    color: #fff;
                    font: inherit;
                    font-weight: 600;
                    cursor: pointer;
                }

                .analytics-button:hover:not(:disabled) {
                    background: #1d4ed8;
                }

                .analytics-button:disabled {
                    cursor: wait;
                    opacity: 0.65;
                }

                .analytics-train {
                    background: #0f766e;
                }

                .analytics-train:hover:not(:disabled) {
                    background: #115e59;
                }

                .analytics-status {
                    min-height: 22px;
                    margin: 16px 0;
                    color: #475569;
                    font-size: 0.9rem;
                }

                .analytics-status.error {
                    color: #b91c1c;
                }

                .analytics-status.success {
                    color: #047857;
                }

                .analytics-kpis {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 12px;
                    margin: 18px 0;
                }

                .analytics-kpi {
                    display: grid;
                    gap: 7px;
                    padding: 15px;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    background: #f8fafc;
                }

                .analytics-kpi span {
                    color: #475569;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .analytics-kpi strong {
                    color: #1d4ed8;
                    font-size: 1.35rem;
                }

                .analytics-kpi small {
                    color: #64748b;
                    line-height: 1.35;
                }

                .analytics-chart-card, .analytics-actions {
                    margin-top: 20px;
                    padding: 18px;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    background: #fff;
                }

                .analytics-chart-frame {
                    position: relative;
                    height: 320px;
                    margin-top: 16px;
                }

                .analytics-table-wrap {
                    overflow-x: auto;
                    margin-top: 12px;
                }

                .analytics-table {
                    width: 100%;
                    border-collapse: collapse;
                    text-align: left;
                }

                .analytics-table th, .analytics-table td {
                    padding: 11px 10px;
                    border-bottom: 1px solid #e2e8f0;
                    color: #334155;
                    font-size: 0.84rem;
                    vertical-align: middle;
                }

                .analytics-table th {
                    background: #f8fafc;
                    color: #475569;
                    font-weight: 700;
                    white-space: nowrap;
                }

                .analytics-rating {
                    display: flex;
                    gap: 2px;
                    white-space: nowrap;
                }

                .analytics-rating button {
                    padding: 3px;
                    border: 0;
                    background: transparent;
                    color: #94a3b8;
                    cursor: pointer;
                    font-size: 1rem;
                }

                .analytics-rating button:hover, .analytics-rating button.selected {
                    color: #f59e0b;
                }

                .analytics-rating button:disabled {
                    cursor: default;
                }

                .products-section {
                    margin-top: 30px;
                }

                .products-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 25px;
                    margin-top: 20px;
                }

                .product-card {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    transition: all 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                    min-height: 380px;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .product-card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 12px 30px rgba(33, 150, 243, 0.2);
                    border-color: rgba(33, 150, 243, 0.3);
                }

                .product-image {
                    position: relative;
                    width: 100%;
                    height: 200px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }

                .product-image img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    object-position: center;
                }

                .image-placeholder {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }

                .image-placeholder i {
                    font-size: 4rem;
                    color: rgba(255, 255, 255, 0.8);
                }

                .product-badge {
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    padding: 6px 14px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: white;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                }

                .product-badge.bestseller {
                    background: linear-gradient(135deg, #00B4DB 0%, #0083B0 100%);
                }

                .product-badge.new {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .product-badge.sale {
                    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
                }

                .product-badge.critical {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                }

                .product-badge.low-stock {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .product-info {
                    padding: 20px;
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                }

                .product-info h3 {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0 0 8px 0;
                    line-height: 1.4;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    min-height: 31px;
                }

                .product-category {
                    font-size: 0.85rem;
                    color: #2196F3;
                    font-weight: 600;
                    margin: 0 0 4px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .product-brand {
                    font-size: 0.8rem;
                    color: #718096;
                    margin: 0 0 12px 0;
                }

                .product-price {
                    font-size: 1.4rem;
                    font-weight: 800;
                    color: #2d3748;
                    margin: 0 0 12px 0;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }

                .product-stock {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 15px;
                    padding: 8px 12px;
                    background: rgba(33, 150, 243, 0.05);
                    border-radius: 8px;
                }

                .stock-label {
                    font-size: 0.8rem;
                    color: #718096;
                    font-weight: 600;
                }

                .stock-value {
                    font-size: 0.85rem;
                    font-weight: 700;
                    color: #2d3748;
                }

                .stock-value.high {
                    color: #48bb78;
                }

                .stock-value.medium {
                    color: #ed8936;
                }

                .stock-value.low {
                    color: #ecc94b;
                }

                .stock-value.critical {
                    color: #f56565;
                }

                .product-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: auto;
                }

                .btn-action {
                    flex: 1;
                    padding: 10px 8px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.9rem;
                }

                .btn-action:hover {
                    transform: scale(1.05);
                }

                .btn-action.btn-edit {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-action.btn-delete {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                    color: white;
                }

                .btn-action.btn-view {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-action i {
                    font-size: 1rem;
                }

                /* Estado del producto */
                .product-estatus {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    width: fit-content;
                    margin: 8px 0 4px;
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.78rem;
                    font-weight: 600;
                }

                .product-estatus.habilitado {
                    background: #dcfce7;
                    color: #15803d;
                }

                .product-estatus.inhabilitado {
                    background: #fee2e2;
                    color: #b91c1c;
                }

                button.product-estatus {
                    border: none;
                    cursor: pointer;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }

                button.product-estatus:hover {
                    transform: scale(1.05);
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
                }

                .producto-inhabilitado {
                    opacity: 0.6;
                    filter: grayscale(0.4);
                }

                .producto-inhabilitado:hover {
                    opacity: 0.9;
                }

                /* Mensajes de vacío */
                .no-products {
                    text-align: center;
                    padding: 60px 40px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 16px;
                    grid-column: 1 / -1;
                }

                .no-products i {
                    font-size: 4rem;
                    color: #2196F3;
                    margin-bottom: 20px;
                }

                .no-products p {
                    font-size: 1.2rem;
                    color: #666;
                    margin: 0;
                    font-weight: 500;
                }

                /* Botones de sección */
                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .section-header h2 {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0;
                }

                .section-actions {
                    display: flex;
                    gap: 12px;
                }
/*
                .btn-filter {
                    padding: 12px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-filter:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }*/

                #productModal.modal, #productDetailsModal.modal {
                    overflow-y: auto;
                    padding: 16px 0;
                }

                #productModal .modal-content, #productDetailsModal .modal-content {
                    display: flex;
                    flex-direction: column;
                    width: min(860px, calc(100vw - 32px));
                    max-width: 860px;
                    max-height: calc(100vh - 32px);
                    margin: 0 auto;
                }

                #productModal .modal-body, #productDetailsModal .modal-body {
                    min-height: 0;
                    overflow-y: auto;
                    flex: 1 1 auto;
                }

                #productModal .modal-header, #productModal .modal-footer,
                #productDetailsModal .modal-header, #productDetailsModal .modal-footer {
                    flex: 0 0 auto;
                }

                #productModal .form-row {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 16px;
                }

                #productModal .stock-fields {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                #productModal .form-group {
                    min-width: 0;
                    margin-bottom: 18px;
                }

                #productModal textarea.input-textnum {
                    height: auto;
                    min-height: 72px;
                    resize: vertical;
                }

                #productModal .modal-header, #productDetailsModal .modal-header {
                    background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
                    color: #fff;
                }

                #productModal .modal-footer, #productDetailsModal .modal-footer {
                    border-top: 1px solid #e5e7eb;
                }

                #productModal .close-modal, #productDetailsModal .close-modal {
                    padding: 0;
                    border: 0;
                    background: transparent;
                    color: #fff;
                }

                #productModal .btn-save, #productModal .btn-cancel,
                #productDetailsModal .btn-cancel {
                    min-height: 42px;
                    padding: 10px 24px;
                    border: 0;
                    border-radius: 8px;
                    color: #fff;
                    font: inherit;
                    font-weight: 600;
                    cursor: pointer;
                }

                #productModal .btn-save {
                    background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
                }

                #productModal .btn-cancel, #productDetailsModal .btn-cancel {
                    background: #6c757d;
                }

                .product-details-layout {
                    display: grid;
                    grid-template-columns: minmax(220px, 0.85fr) minmax(0, 1.5fr);
                    gap: 24px;
                    align-items: start;
                }

                .product-details-image-frame {
                    display: grid;
                    height: min(60vh, 620px);
                    min-height: 320px;
                    place-items: center;
                    overflow: auto;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    background: #f8fafc;
                    padding: 12px;
                }

                #detailProductImage {
                    display: block;
                    width: auto;
                    height: auto;
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }

                .product-image-preview {
                    display: grid;
                    width: min(100%, 420px);
                    height: 190px;
                    margin-top: 12px;
                    padding: 10px;
                    place-items: center;
                    overflow: hidden;
                    border: 1px dashed #b8c4d1;
                    border-radius: 8px;
                    background: #f8fafc;
                    color: #64748b;
                    text-align: center;
                }

                #productImagePreview {
                    display: block;
                    width: auto;
                    height: auto;
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }

                #productImagePreview[hidden], #detailProductImage[hidden] {
                    display: none;
                }

                .product-details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 12px;
                }

                .product-detail {
                    display: flex;
                    min-width: 0;
                    flex-direction: column;
                    gap: 5px;
                    padding: 12px;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    background: #f8fafc;
                }

                .product-detail span {
                    color: #64748b;
                    font-size: 0.82rem;
                    font-weight: 600;
                }

                .product-detail strong {
                    overflow-wrap: anywhere;
                    color: #1f2937;
                    font-weight: 600;
                    white-space: pre-wrap;
                }

                .product-detail-wide {
                    grid-column: 1 / -1;
                }

                #detailProductCharacteristics:empty {
                    display: none;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .analytics-section {
                        padding: 16px;
                    }

                    .analytics-header, .analytics-chart-heading {
                        flex-direction: column;
                    }

                    .analytics-or {
                        display: none;
                    }

                    .analytics-chart-frame {
                        height: 250px;
                    }

                    .products-grid {
                        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                        gap: 20px;
                    }

                    .section-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                    }

                    #productModal .form-row, #productModal .stock-fields,
                    .product-details-layout {
                        grid-template-columns: 1fr;
                    }

                    .product-details-image-frame {
                        height: min(45vh, 420px);
                        min-height: 240px;
                    }
                }

                @media (max-width: 480px) {
                    .products-grid {
                        grid-template-columns: 1fr;
                    }

                    .product-card {
                        min-height: 350px;
                    }
                }
            </style>



<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>