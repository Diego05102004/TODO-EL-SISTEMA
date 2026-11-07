<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Producto</title>
    <?php $exclude_buttons_css = true; include 'header.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .product-hero { display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; }
        .product-image { width: 100%; max-height: 420px; object-fit: contain; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; }
        .product-title { font-size: 1.6rem; font-weight: 700; margin-bottom: 6px; }
        .product-brand { color: #6b7280; margin-bottom: 12px; }
        .price-block { display: flex; gap: 16px; align-items: baseline; margin: 10px 0 16px; }
        .price-usd { font-size: 1.4rem; font-weight: 700; color: #111827; }
        .price-bs { font-size: 1rem; color: #374151; }
        .badge-stock { padding: 6px 10px; border-radius: 999px; font-size: .85rem; }
        .qty-control { display: inline-flex; align-items: center; gap: 8px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px; background: #fff; }
        .qty-control input { width: 56px; text-align: center; border: none; outline: none; }
        .qty-control button { border: none; background: #f3f4f6; width: 28px; height: 28px; border-radius: 6px; }
        .features-list { list-style: none; padding: 0; margin: 0; }
        .features-list li { padding: 10px 0; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; gap: 12px; }
        .features-list .label { color: #6b7280; }
        .surface-card { background: #ffffff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 18px 20px; }
        /* Breadcrumb propio: recuadro blanco + burbuja azul para sección actual */
        .bc-wrap { background:#ffffff; border:1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); padding: .5rem .75rem; margin: .75rem 0 1rem; }
        .bc-list { display:flex; align-items:center; flex-wrap: wrap; gap: .5rem; }
        .bc-item { font-weight: 700; color: var(--text-primary); }
        .bc-item a { color: inherit; text-decoration: none; }
        .bc-sep { color: var(--text-secondary); opacity:.7; font-weight: 700; }
        .bc-current { display:inline-flex; align-items:center; background: var(--primary-color); color:#ffffff; border-radius: 999px; padding: .25rem .6rem; font-weight: 700; }
        @media (max-width: 992px) { .product-hero { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="fondo" style="background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['nombre_rol']) && !empty($_SESSION['nombre_rol'])) { include 'NewNavBar.php'; } else { include 'NavBar.php'; }
?>

<div class="container py-4">
    <div class="bc-wrap">
        <div class="bc-list">
            <span class="bc-item"><a href="?pagina=catalogo">Catálogo</a></span>
            <?php if (!empty($producto['nombre_categoria'])): ?>
                <span class="bc-sep">/</span>
                <span class="bc-item"><a href="?pagina=catalogo&amp;cat=<?= urlencode($producto['nombre_categoria']) ?>"><?= htmlspecialchars($producto['nombre_categoria']) ?></a></span>
            <?php endif; ?>
            <span class="bc-sep">/</span>
            <span class="bc-current"><?= htmlspecialchars($producto['nombre_producto'] ?? 'Detalle') ?></span>
        </div>
    </div>
    <?php if (!$producto): ?>
        <div class="alert alert-danger mt-4">Producto no encontrado.</div>
    <?php else: ?>
        <div class="surface-card">
        <div class="product-hero">
            <div>
                <?php 
                $img = $producto['imagen'] ?? '';
                $imgSrc = (!empty($img)) ? htmlspecialchars($img) : 'assets/img/placeholder-product.png';
                ?>
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($producto['nombre_producto']) ?>" class="product-image" onerror="this.src='assets/img/placeholder-product.png'">
            </div>
            <div>
                <h1 class="product-title"><?= htmlspecialchars($producto['nombre_producto']) ?></h1>
                <div class="product-brand">Marca: <?= htmlspecialchars($producto['nombre_marca']) ?> • Modelo: <?= htmlspecialchars($producto['nombre_modelo']) ?></div>
                <div class="mb-2 text-muted">Serial: <?= htmlspecialchars($producto['serial']) ?></div>
                <div class="mb-3">Categoría: <span class="badge bg-secondary"><?= htmlspecialchars($producto['nombre_categoria']) ?></span></div>
                <p><?= htmlspecialchars($producto['descripcion_producto'] ?? '') ?></p>

                <?php 
                $tasa = floatval($data['monitors']['bcv']['price'] ?? 0);
                $precio = floatval($producto['precio'] ?? 0);
                $precioBs = $tasa > 0 ? $precio * $tasa : 0;
                $stock = intval($producto['stock'] ?? 0);
                ?>
                <div class="price-block">
                    <div class="price-usd">$<?= number_format($precio, 2) ?></div>
                    <div class="price-bs"><?= number_format($precioBs, 2) ?> BS</div>
                </div>
                <div class="mb-3">
                    <?php if ($stock > 10): ?>
                        <span class="badge bg-success badge-stock">En stock (<?= $stock ?>)</span>
                    <?php elseif ($stock > 0): ?>
                        <span class="badge bg-warning text-dark badge-stock">Stock bajo (<?= $stock ?>)</span>
                    <?php else: ?>
                        <span class="badge bg-danger badge-stock">Agotado</span>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="qty-control">
                        <button type="button" class="qty-decrease" aria-label="Disminuir">-</button>
                        <input type="number" id="detalleCantidad" value="1" min="1" max="<?= max(1, $stock) ?>">
                        <button type="button" class="qty-increase" aria-label="Aumentar">+</button>
                    </div>
                    <button id="btnAddToCart" class="btn btn-primary" <?= $stock <= 0 ? 'disabled' : '' ?> data-id-producto="<?= intval($producto['id_producto']) ?>">
                        <i class="bi bi-cart-plus"></i> Agregar al carrito
                    </button>
                </div>

                <div class="small text-muted">Garantía: <?= htmlspecialchars($producto['clausula_garantia'] ?? 'N/A') ?></div>
            </div>
        </div>

        <div class="mt-4">
            <h2 class="tabla-titulo-2">Características</h2>
            <?php $caracs = $producto['caracteristicas'] ?? []; ?>
            <?php if (!empty($caracs)): ?>
                <ul class="features-list">
                    <?php foreach ($caracs as $k => $v): ?>
                        <li><span class="label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $k))) ?></span><span class="value"><?= htmlspecialchars($v) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-muted">Este producto no tiene características registradas para su categoría.</div>
            <?php endif; ?>
        </div>

        <!-- Relacionados -->
        <?php if (!empty($relacionados)): ?>
        <div class="mt-5">
            <h2 class="tabla-titulo-2">Productos relacionados</h2>
            <div class="row g-3">
                <?php foreach ($relacionados as $rel): 
                    $relImg = $rel['imagen'] ?? '';
                    $relSrc = (!empty($relImg)) ? htmlspecialchars($relImg) : 'assets/img/placeholder-product.png';
                    $relBs  = isset($data['monitors']['bcv']['price']) && isset($rel['precio']) ? ($rel['precio'] * $data['monitors']['bcv']['price']) : 0;
                    $relStock = intval($rel['stock'] ?? 0);
                    ?>
                <div class="col-6 col-md-3">
                    <div class="card h-100 rel-card">
                        <a href="?pagina=detalle_producto&id=<?= (int)$rel['id_producto'] ?>" class="text-decoration-none">
                            <img src="<?= $relSrc ?>" class="card-img-top" alt="<?= htmlspecialchars($rel['nombre_producto']) ?>" onerror="this.src='assets/img/placeholder-product.png'">
                            <div class="card-body">
                                <div class="fw-semibold text-truncate" title="<?= htmlspecialchars($rel['nombre_producto']) ?>"><?= htmlspecialchars($rel['nombre_producto']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($rel['nombre_marca'] ?? '') ?></div>
                                <div class="mt-1 fw-bold"><?= number_format($relBs, 2) ?> BS</div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                            <a href="?pagina=detalle_producto&id=<?= (int)$rel['id_producto'] ?>" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye"></i> Ver</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/sweetalert2.all.min.js"></script>
<script>
function solicitarLogin() {
    Swal.fire({
        icon: 'info',
        title: 'Inicia sesión',
        text: 'Para agregar productos al carrito, por favor inicia sesión.',
        showCancelButton: true,
        confirmButtonText: 'Iniciar sesión',
        cancelButtonText: 'Cancelar'
    }).then(res => { if (res.isConfirmed) window.location.href='?pagina=login'; });
}
</script>
<script src="assets/javascript/detalle_producto.js"></script>
<?php include 'footer.php'; ?>

<button 
    class="btn-ayuda"
    style="top: 100px; right: 100px;"
    title="Visualizar Ayuda"
    onclick="window.location.href='?pagina=ayuda'">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>
</body>
</html>