<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/styles/catalogo.css">
    <?php $exclude_buttons_css = true; include 'header.php'; ?>
</head>

<body class="fondo" style="background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Determinar qué navbar incluir basado en la sesión
    if (isset($_SESSION['nombre_rol']) && !empty($_SESSION['nombre_rol'])) {
        include 'NewNavBar.php';
    } else {
        include 'NavBar.php';
    }

    $esCliente = isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] == 'Cliente';

    $id_rol = $_SESSION['id_rol'] ?? 0;
    $nombre_rol = $_SESSION['nombre_rol'] ?? '';

    $permisosObj = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos();
    ?>
    <br>

    <div class="main-content">
        <section class="catalogo-container mt-4">


        <!-- Pestañas de navegación -->
        <div class="catalogo-tabs">
            <!-- Campo oculto para tasa de cambio (usado por assets/javascript/catalogo.js) -->
            <input type="hidden" id="tasa" value="<?= htmlspecialchars($data['monitors']['bcv']['price'] ?? 0) ?>">
            <ul class="nav nav-tabs" id="catalogoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="productos-tab">
                        <i class="bi bi-box-seam"></i> Productos Individuales
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="combos-tab">
                        <i class="bi bi-collection"></i> Combos Promocionales
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenido de Productos -->
        <div id="productos-content">
            <!-- Filtros y búsqueda -->
            <div class="filtros-container">
                <div class="filters-inline">
                    <div>
                        <label for="filtroMarca">Filtrar por marca</label>
                        <select id="filtroMarca">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= htmlspecialchars($marca) ?>">
                                    <?= htmlspecialchars($marca) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-box" style="flex:1;">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchProduct" placeholder="Buscar producto...">
                        <span class="combo-validation-error text-danger small" id="error_searchProduct" role="alert" aria-live="polite"></span>
                    </div>
                </div>
            </div>

            <!-- Grid de Productos -->
            <div class="productos-grid">
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto): 
                        $precioBs = isset($data['monitors']['bcv']['price']) && isset($producto['precio']) ? 
                                   $producto['precio'] * $data['monitors']['bcv']['price'] : 0;
                        $stock = $producto['stock'] ?? 0;
                    ?>
                        <div class="producto-card" data-id="<?= $producto['id_producto'] ?>">
                            <!-- Badge de stock -->
                            <?php if ($stock > 10): ?>
                                <div class="producto-badge">En stock</div>
                            <?php elseif ($stock > 0): ?>
                                <div class="producto-badge stock-bajo">Stock bajo</div>
                            <?php else: ?>
                                <div class="producto-badge sin-stock">Agotado</div>
                            <?php endif; ?>

                            <!-- Imagen del producto -->
                            <a href="?pagina=detalle_producto&id=<?= (int)$producto['id_producto'] ?>" class="producto-imagen-container" style="display:block;">
                                <?php if (!empty($producto['imagen'])): ?>
                                    <IMG src="<?= htmlspecialchars($producto['imagen']) ?>" 
                                         class="producto-imagen"
                                         alt="<?= htmlspecialchars($producto['imagen']) ?>"
                                         onerror="this.src='assets/img/placeholder-product.png'">
                                <?php else: ?>
                                    <div class="producto-imagen-container IMG-placeholder">
                                        <i class="bi bi-image" style="font-size: 3rem; color: #6b7280;"></i>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Contenido de la card -->
                           <div class="producto-content">
                                <h3 class="producto-nombre"><a href="?pagina=detalle_producto&id=<?= (int)$producto['id_producto'] ?>" style="text-decoration:none; color:inherit;">
                                    <?= htmlspecialchars($producto['nombre_producto']) ?>
                                </a></h3>
                                <div class="producto-serial"><?= htmlspecialchars($producto['serial']) ?></div>
                                <p class="producto-descripcion"><?= htmlspecialchars($producto['descripcion_producto']) ?></p>
                                <div class="producto-marca"><?= htmlspecialchars($producto['marca']) ?></div>
                                
                                <div class="producto-precio-container">
                                    <div class="producto-precio"><?= number_format($precioBs, 2) ?> BS</div>
                                    <button type="button" 
                                        class="btn-agregar-carrito <?= $stock <= 0 ? 'disabled' : '' ?>"
                                        data-id-producto="<?= htmlspecialchars($producto['id_producto']) ?>"
                                        data-stock="<?= $stock ?>"
                                        <?= $stock <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-cart-plus"></i>
                                        <span class="btn-text"><?= $stock <= 0 ? 'Agotado' : 'Agregar' ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-exclamation-circle"></i>
                        <h4>No hay productos disponibles</h4>
                        <p>En este momento no tenemos productos en nuestro catálogo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenido de Combos -->
        <div id="combos-content" style="display: none;">
            <?php if ($esAdmin): ?>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" id="nuevo_combo">
                        <i class="bi bi-plus-circle"></i> Nuevo Combo
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($combos)): ?>
                <div class="combos-grid">
                    <?php foreach ($combos as $combo):
                        if (!$esAdmin && !$combo['activo']) continue;

                        // Usar detalles precargados del método optimizado
                        $detalles = $combo['detalles'] ?? [];
                        $precioTotal = $combo['precio_total'] ?? 0;
                        $todosDisponibles = true;

                        foreach ($detalles as $detalle) {
                            $todosDisponibles = $todosDisponibles && ($detalle['stock'] >= $detalle['cantidad']);
                        }

                        $descuentoCombo = (float)($combo['descuento'] ?? 0);
                        $precioOriginalBs = (float)($combo['precio_original'] ?? $precioTotal) * $data['monitors']['bcv']['price'];
                        $precioComboBs = $precioTotal * $data['monitors']['bcv']['price'];
                        $ahorroBs = max(0, $precioOriginalBs - $precioComboBs);
                    ?>
                        <div class="combo-card <?= !$combo['activo'] ? 'disabled-combo' : '' ?>">
                            <div class="combo-header">
                                <h4 class="combo-nombre"><?= htmlspecialchars($combo['nombre_combo']) ?></h4>
                                <?php if (!$combo['activo']): ?>
                                    <span class="badge bg-secondary">Inhabilitado</span>
                                <?php endif; ?>
                                <p class="combo-descripcion"><?= htmlspecialchars($combo['descripcion']) ?></p>
                            </div>

                            <!-- Imágenes del combo -->
                            <div class="combo-imagenes-grid">
                                <?php
                                $imagenesMostradas = 0;
                                foreach ($detalles as $detalle):
                                    if ($imagenesMostradas >= 4) break;
                                    if (!empty($detalle['imagen'])):
                                        $imagenesMostradas++;
                                ?>
                                        <IMG src="<?= htmlspecialchars($detalle['imagen']) ?>" 
                                             class="combo-imagen <?= $imagenesMostradas == 1 ? 'principal' : '' ?>"
                                             alt="<?= htmlspecialchars($detalle['imagen']) ?>"
                                             onerror="this.src='assets/img/placeholder-product.png'">
                                <?php
                                    endif;
                                endforeach;

                                // Placeholders para imágenes faltantes (solo si hay menos de 4 productos)
                                $totalProductos = count($detalles);
                                $maxImagenes = min($totalProductos, 4);
                                while ($imagenesMostradas < $maxImagenes) {
                                    echo '<div class="combo-imagen ' . ($imagenesMostradas == 0 ? 'principal' : '') . ' IMG-placeholder">';
                                    echo '<i class="bi bi-image"></i>';
                                    echo '</div>';
                                    $imagenesMostradas++;
                                }
                                ?>
                            </div>

                            <div class="combo-content">
                                <!-- Lista de productos -->
                                <div class="combo-productos-list">
                                    <?php foreach ($detalles as $detalle):
                                        $disponible = $detalle['stock'] >= $detalle['cantidad'];
                                    ?>
                                        <div class="combo-producto-item">
                                            <div>
                                                <?= htmlspecialchars($detalle['nombre_producto']) ?>
                                                <?php if (!$disponible): ?>
                                                    <i class="bi bi-exclamation-triangle-fill text-danger ms-1" 
                                                       title="Stock insuficiente"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="text-muted small">
                                                    <?= number_format($detalle['precio'] * $data['monitors']['bcv']['price'], 2) ?> BS
                                                </span> ×
                                                <span class="badge bg-<?= $disponible ? 'primary' : 'danger' ?> rounded-pill">
                                                    <?= $detalle['cantidad'] ?>
                                                </span>
                                                <span class="ms-2 fw-semibold">
                                                    <?= number_format($detalle['precio_unitario'] * $data['monitors']['bcv']['price'], 2) ?> BS c/u con descuento
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Precios y ahorro -->
                                <div class="combo-precios">
                                    <div>
                                        <div class="precio-original"><?= number_format($precioOriginalBs, 2) ?> BS</div>
                                        <div class="precio-combo"><?= number_format($precioComboBs, 2) ?> BS</div>
                                    </div>
                                    <div class="ahorro-combo">Descuento <?= number_format($descuentoCombo, 2) ?>% · Ahorras <?= number_format($ahorroBs, 2) ?> BS</div>
                                </div>

                                <?php if($esCliente): ?>
                                    <!-- Botón agregar combo -->
                                    <button class="btn-agregar-carrito btn-agregar-combo w-100 <?= !$todosDisponibles || !$combo['activo'] ? 'disabled' : '' ?>"
                                            data-id-combo="<?= $combo['id_combo'] ?>"
                                            <?= !$todosDisponibles || !$combo['activo'] ? 'disabled' : '' ?>>
                                        <i class="bi bi-cart-plus"></i>
                                        <?= !$combo['activo'] ? 'Combo no disponible' : ($todosDisponibles ? 'Agregar Combo' : 'Productos no disponibles') ?>
                                    </button>
                                <?php endif; ?>

                                <!-- Acciones de admin -->
                                <?php if ($esAdmin): ?>
                                    <div class="d-flex justify-content-between mt-3">
                                        <button class="btn btn-sm btn-outline-primary btn-editar-combo"
                                                data-id-combo="<?= $combo['id_combo'] ?>">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-eliminar-combo"
                                                data-id-combo="<?= $combo['id_combo'] ?>">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                        <button class="btn btn-sm <?= $combo['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-cambiar-estado"
                                                data-id-combo="<?= $combo['id_combo'] ?>"
                                                data-nombre-combo="<?= htmlspecialchars($combo['nombre_combo']) ?>"
                                                data-estado-actual="<?= $combo['activo'] ? 1 : 0 ?>">
                                            <i class="bi <?= $combo['activo'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                            <?= $combo['activo'] ? 'Inhabilitar' : 'Habilitar' ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-info-circle"></i>
                    <h4>No hay combos disponibles</h4>
                    <p>En este momento no tenemos combos promocionales disponibles.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal para gestión de combos -->
        <div class="modal fade" id="comboModal" tabindex="-1" aria-labelledby="comboModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Combo" data-contexto="registrar" onclick="cargarYMostrarModalAyuda(this.getAttribute('data-contexto'))">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                        <h5 class="titulo-form" id="comboModalLabel">Crear Nuevo Combo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="comboForm">
                            <input type="hidden" id="id_combo" name="id_combo" value="">
                            <div class="envolver-form">
                                <label for="nombre_combo" style="color: #1976D2; font-weight: 500; font-size: 1rem; margin-bottom: 4px;">Nombre del Combo*</label>
                                <input type="text" class="control-form" id="nombre_combo" name="nombre_combo" maxlength="100" required>
                                <span class="combo-validation-error text-danger small" id="error_nombre_combo"></span>
                            </div>
                            <div class="envolver-form">
                                <label for="descripcion" style="color: #1976D2; font-weight: 500; font-size: 1rem; margin-bottom: 4px;">Descripción*</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="500" required></textarea>
                                <span class="combo-validation-error text-danger small" id="error_descripcion"></span>
                            </div>
                            <div class="envolver-form">
                                <label for="descuento" style="color: #1976D2; font-weight: 500; font-size: 1rem; margin-bottom: 4px;">Descuento del combo (%)</label>
                                <input type="text" inputmode="decimal" class="form-control" id="descuento" name="descuento"
                                       maxlength="6" value="0">
                                <span class="combo-validation-error text-danger small" id="error_descuento"></span>
                                <small class="text-muted">El descuento se aplica únicamente a las unidades que pertenecen a este combo.</small>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="titulo-form">Productos del Combo</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <select class="form-select" id="producto_combo" required>
                                                <option value="">Seleccionar producto</option>
                                                <?php foreach ($productos as $producto): 
                                                    $precioBs = isset($data['monitors']['bcv']['price']) ? 
                                                               $producto['precio'] * $data['monitors']['bcv']['price'] : 0;
                                                ?>
                                                    <option value="<?= $producto['id_producto'] ?>" 
                                                            data-stock="<?= $producto['stock'] ?? 0 ?>"
                                                            data-precio="<?= $precioBs ?>">
                                                        <?= htmlspecialchars($producto['nombre_producto']) ?> 
                                                        (Stock: <?= $producto['stock'] ?? 0 ?>) - 
                                                        <?= number_format($precioBs, 2) ?> BS
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="combo-validation-error text-danger small" id="error_producto_combo"></span>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control" id="cantidad_producto" min="1" max="999" step="1" value="1" required>
                                            <span class="combo-validation-error text-danger small" id="error_cantidad_producto"></span>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary" style="width: 200px;" id="agregar_producto">
                                                <i class="bi bi-plus-circle"></i> Agregar Producto
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table id="productos_combo_table" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Total con descuento</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Los productos se insertarán aquí dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="boton-reset btn-primary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="boton-form btn-primary" style="width: 160px;" id="guardar_combo">
                            Nuevo Combo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para confirmar cambio de estado -->
        <div class="modal fade" id="cambioEstadoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="titulo-form">Cambiar Estatus del Combo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea <span id="accionEstado">habilitar</span> este combo?</p>
                        <input type="hidden" id="combo_id_estado" value="">
                        <input type="hidden" id="nuevo_estado" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmarCambioEstado">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--footer-->

    <script src="assets/public/js/jquery-3.7.1.min.js"></script>
    <script src="assets/javascript/sweetalert2.all.min.js"></script>
    <script src="assets/javascript/catalogo.js"></script>
    <script src="assets/public/js/jquery.dataTables.min.js"></script>
    <script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/javascript/datatable.js"></script>
<script>
/**
 * Protege uno o varios selects contra modificaciones en DevTools.
 * @param {string[]} selectIds - Array con los IDs de los selects a proteger.
 * @param {number} interval  - Tiempo de verificación (ms), default 1000ms.
 */
function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    // Esperar a que el DOM cargue
    document.addEventListener("DOMContentLoaded", () => {
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;

            // Guardar las opciones originales
            originales[id] = [...select.options].map(opt => ({
                value: opt.value,
                text: opt.textContent.trim()
            }));
        });
    });

    // Monitorear cambios
    setInterval(() => {
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;

            const opsActuales = [...select.options].map(opt => ({
                value: opt.value,
                text: opt.textContent.trim()
            }));

            const opsOriginales = originales[id];
            if (!opsOriginales) return;

            const alterado =
                opsActuales.length !== opsOriginales.length ||
                opsActuales.some((o, i) =>
                    o.value !== opsOriginales[i].value ||
                    o.text !== opsOriginales[i].text
                );

            if (alterado) {
                // Restaurar opciones originales
                select.innerHTML = "";
                opsOriginales.forEach(optData => {
                    const opt = document.createElement("option");
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    select.appendChild(opt);
                });

                console.warn(`⚠ Opciones del <select id="${id}"> fueron alteradas. Restauradas automáticamente.`);
            }
        });
    }, interval);
}

protegerSelects(["producto_combo"]);
</script>

    <script>
        function MensajeInicio() {
            Swal.fire({
                icon: 'info',
                title: '¡Hola!',
                text: 'Para agregar productos al carrito, por favor inicia sesión.',
                confirmButtonText: 'Iniciar Sesión',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                background: '#f8f9fa',
                backdrop: `
                    rgba(0,0,0,0.4)
                    url("/Public/assets/img/cart.gif")
                    center top
                    no-repeat
                `
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?pagina=login';
                }
            });
        }
    </script>
<?php include 'footer.php'; ?>
<?php if($_SESSION): ?>
    <button 
        class="btn-ayuda"
        style="top: 120px;"
        title="Visualizar Ayuda"
        onclick="cargarYMostrarModalAyuda()">
        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
    </button>
    <?php endif;?>
</body>
</html>