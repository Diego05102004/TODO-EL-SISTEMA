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
        error_log("Error al generar JWT en carrito: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'carrito';
$titulo_pagina = 'Carrito de Compras';

// Calcular precio del dólar
$precioDolar = (float)($data['monitors']['bcv']['price'] ?? 35.50);
$subtotalCarritoUsd = array_sum(array_map(
    static fn($carrito) => (float)($carrito['subtotal'] ?? 0),
    is_array($carritos ?? null) ? $carritos : []
));
$subtotalCarritoBs = $subtotalCarritoUsd * $precioDolar;
$gruposCarrito = [];
foreach (is_array($carritos ?? null) ? $carritos : [] as $carrito) {
    $esCombo = !empty($carrito['id_combo']);
    $grupoId = $esCombo
        ? 'combo-' . (int)$carrito['id_combo']
        : 'producto-' . (int)$carrito['id_carrito_detalle'];
    if (!isset($gruposCarrito[$grupoId])) {
        $gruposCarrito[$grupoId] = [
            'es_combo' => $esCombo,
            'combo' => $carrito['nombre_combo'] ?? 'Combo',
            'items' => [],
            'subtotal' => 0.0
        ];
    }
    $gruposCarrito[$grupoId]['items'][] = $carrito;
    $gruposCarrito[$grupoId]['subtotal'] += (float)($carrito['subtotal'] ?? 0);
}

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Carrito -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="card-content">
                        <h3>Productos</h3>
                        <p class="card-value"><?php echo count($gruposCarrito); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="78, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">78%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-calculator"></i></div>
                    <div class="card-content">
                        <h3>Subtotal</h3>
                        <p class="card-value"><?php echo number_format($subtotalCarritoBs, 2); ?> Bs.</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="65, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">65%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Total</h3>
                        <p class="card-value"><?php echo number_format($subtotalCarritoBs, 2); ?> Bs.</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="55, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">55%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carrito Section -->
            <div class="carrito-section">
                <div class="section-header">
                    <h2>Carrito de Compras</h2>
                    <div class="section-actions">
                        <button class="btn-clear" onclick="clearCart()">
                            <span class="btn-icon"><i class="fas fa-trash"></i></span>
                            Vaciar Carrito
                        </button>
                        <button class="btn-checkout" onclick="checkout()">
                            <span class="btn-icon"><i class="fas fa-credit-card"></i></span>
                            Procesar Compra
                        </button>
                    </div>
                </div>

                <!-- Tabla del Carrito -->
                <div class="table-container">
                    <table class="carrito-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio Unitario</th>
                                <th>Cantidad / Combos</th>
                                <th>Subtotal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($gruposCarrito)): ?>
                                <?php 
                                $total = 0;
                                foreach ($gruposCarrito as $grupo):
                                    $total += $grupo['subtotal'];
                                    $carrito = $grupo['items'][0];
                                    $cantidadCombo = max(1, (int)($carrito['cantidad_combo'] ?? 1));
                                    $cantidadVisible = $grupo['es_combo']
                                        ? (int)$carrito['cantidad'] / $cantidadCombo
                                        : (int)$carrito['cantidad'];
                                    $cantidadVisible = max(1, (int)$cantidadVisible);
                                ?>
                                    <tr>
                                        <td>
                                            <?php if (!$grupo['es_combo']): ?>
                                            <div class="product-info">
                                                <?php if (!empty($carrito['imagen'])): ?>
                                                    <img src="<?php echo htmlspecialchars($carrito['imagen']); ?>" alt="<?php echo htmlspecialchars($carrito['nombre']); ?>" class="product-img-mini">
                                                <?php else: ?>
                                                    <div class="image-placeholder-mini">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="product-name"><?php echo htmlspecialchars($carrito['nombre'] ?? 'N/A'); ?></span>
                                                    <span class="product-category">
                                                        <?php echo htmlspecialchars('Modelo ' . ($carrito['nombre_modelo'] ?? '') . ' - Marca ' . ($carrito['nombre_marca'] ?? '')); ?>
                                                        <br><span>Unidad individual</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                                <div class="combo-images" aria-label="Imágenes de los productos del combo">
                                                    <?php foreach ($grupo['items'] as $componente): ?>
                                                        <?php if (!empty($componente['imagen'])): ?>
                                                            <img
                                                                src="<?php echo htmlspecialchars($componente['imagen']); ?>"
                                                                alt="<?php echo htmlspecialchars($componente['nombre'] ?? 'Producto del combo'); ?>"
                                                                title="<?php echo htmlspecialchars($componente['nombre'] ?? 'Producto del combo'); ?>"
                                                                class="product-img-mini">
                                                        <?php else: ?>
                                                            <div class="image-placeholder-mini" title="<?php echo htmlspecialchars($componente['nombre'] ?? 'Producto del combo'); ?>">
                                                                <i class="fas fa-box"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                                <strong><?php echo htmlspecialchars($grupo['combo']); ?></strong>
                                                <ul class="combo-items">
                                                    <?php foreach ($grupo['items'] as $componente): ?>
                                                        <li>
                                                            <?php echo htmlspecialchars($componente['nombre'] ?? 'Producto'); ?>
                                                            × <?php echo (int)$componente['cantidad_combo']; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($grupo['es_combo']): ?>
                                                <ul class="combo-items combo-prices">
                                                    <?php foreach ($grupo['items'] as $componente): ?>
                                                        <li><?php echo number_format((float)$componente['precio'] * $precioDolar, 2); ?> Bs. c/u</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <?php echo number_format((float)$carrito['precio'] * $precioDolar, 2); ?> Bs.
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="quantity-control">
                                                <button class="qty-btn" onclick="updateQuantity(<?php echo $carrito['id_carrito_detalle']; ?>, -1, false, <?php echo $grupo['es_combo'] ? 'true' : 'false'; ?>, <?php echo $grupo['es_combo'] ? $cantidadCombo : 1; ?>)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="qty-input" value="<?php echo $cantidadVisible; ?>" min="1" readonly>
                                                <?php if ($grupo['es_combo']): ?><small>combos</small><?php endif; ?>
                                                <button class="qty-btn" onclick="updateQuantity(<?php echo $carrito['id_carrito_detalle']; ?>, 1, false, <?php echo $grupo['es_combo'] ? 'true' : 'false'; ?>, <?php echo $grupo['es_combo'] ? $cantidadCombo : 1; ?>)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($grupo['subtotal'] * $precioDolar, 2); ?> Bs.</td>
                                        <td>
                                            <button class="btn-action btn-delete" onclick="removeFromCart(<?php echo $carrito['id_carrito_detalle']; ?>, <?php echo $grupo['es_combo'] ? 'true' : 'false'; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3" style="text-align: right; font-weight: 700; font-size: 1.1rem;">Total:</td>
                                    <td colspan="2" style="font-weight: 700; font-size: 1.3rem; color: #2196F3;">
                                        <?php echo number_format($total * $precioDolar, 2); ?> Bs.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 60px 20px;">
                                        <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #e0e0e0; margin-bottom: 20px;"></i>
                                        <h3 style="color: #718096; margin: 0 0 10px 0;">Carrito Vacío</h3>
                                        <p style="color: #a0aec0; margin: 0;">No hay productos en tu carrito</p>
                                        <a href="?pagina=catalogo" class="btn-back-catalog">
                                            <i class="fas fa-store"></i> Ir al Catálogo
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <style>
                /* Estilos específicos de Carrito */
                .carrito-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .carrito-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .carrito-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .carrito-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .carrito-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .combo-items {
                    margin: 6px 0 0;
                    padding-left: 18px;
                }

                .combo-images {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    margin-bottom: 8px;
                }

                .combo-items li {
                    margin: 3px 0;
                }

                .combo-prices {
                    list-style: none;
                    padding-left: 0;
                }

                .carrito-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .total-row {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    font-weight: 700;
                }

                .product-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .product-img-mini {
                    width: 50px;
                    height: 50px;
                    border-radius: 8px;
                    object-fit: cover;
                }

                .image-placeholder-mini {
                    width: 50px;
                    height: 50px;
                    border-radius: 8px;
                    background: #e0e0e0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #a0aec0;
                }

                .product-name {
                    display: block;
                    font-weight: 600;
                    color: #2d3748;
                }

                .product-category {
                    display: block;
                    font-size: 0.85rem;
                    color: #718096;
                }

                .quantity-control {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .qty-btn {
                    width: 32px;
                    height: 32px;
                    border: none;
                    border-radius: 6px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .qty-btn:hover {
                    transform: scale(1.1);
                    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
                }

                .qty-input {
                    width: 60px;
                    padding: 8px;
                    border: 2px solid #e9ecef;
                    border-radius: 6px;
                    text-align: center;
                    font-weight: 600;
                }

                .qty-input:focus {
                    border-color: #2196F3;
                    outline: none;
                }

                .btn-clear, .btn-checkout {
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

                .btn-clear {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-checkout {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-clear:hover, .btn-checkout:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-action {
                    width: 35px;
                    height: 35px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .btn-delete {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-delete:hover {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }

                .btn-back-catalog {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 12px 25px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    margin-top: 20px;
                    transition: all 0.3s ease;
                }

                .btn-back-catalog:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .carrito-table {
                        font-size: 0.8rem;
                    }

                    .carrito-table th,
                    .carrito-table td {
                        padding: 10px;
                    }

                    .product-img-mini,
                    .image-placeholder-mini {
                        width: 40px;
                        height: 40px;
                    }
                }
            </style>

            <script>
                function updateQuantity(productId, change, isDirect = false, isCombo = false, componentQuantity = 1) {
                    if (isDirect) {
                        const qty = parseInt(change);
                        if (qty < 1) {
                            alert('La cantidad mínima es 1');
                            location.reload();
                            return;
                        }
                        updateCartBackend(productId, qty);
                    } else {
                        const input = event.target.closest('.quantity-control').querySelector('.qty-input');
                        const currentQty = parseInt(input.value);
                        const newQty = currentQty + change;
                        
                        if (newQty < 1) {
                            removeFromCart(productId, isCombo);
                            return;
                        }
                        
                        input.value = newQty;
                        updateCartBackend(productId, newQty * componentQuantity);
                    }
                }

                function updateCartBackend(detailId, quantity) {
                    const formData = new FormData();
                    formData.append('accion', 'actualizar_cantidad');
                    formData.append('id_carrito_detalle', detailId);
                    formData.append('cantidad', quantity);
                    
                    fetch('?pagina=carrito', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            location.reload();
                        } else {
                            alert('Error al actualizar carrito: ' + (data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al actualizar carrito. Por favor, intente nuevamente.');
                    });
                }

                function removeFromCart(productId, isCombo = false) {
                    const mensaje = isCombo
                        ? '¿Está seguro de eliminar este combo completo y todos sus productos del carrito?'
                        : '¿Está seguro de eliminar este producto del carrito?';
                    if (confirm(mensaje)) {
                        const formData = new FormData();
                        formData.append('accion', 'eliminar_del_carrito');
                        formData.append('id_carrito_detalle', productId);
                        
                        fetch('?pagina=carrito', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.status === 'success') {
                                location.reload();
                            } else {
                                alert('Error al eliminar producto: ' + (data.message || 'Error desconocido'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al eliminar producto. Por favor, intente nuevamente.');
                        });
                    }
                }

                function clearCart() {
                    if (confirm('¿Está seguro de vaciar el carrito?')) {
                        const formData = new FormData();
                        formData.append('accion', 'eliminar_todo_carrito');
                        
                        fetch('?pagina=carrito', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.status === 'success') {
                                location.reload();
                            } else {
                                alert('Error al vaciar carrito: ' + (data.message || 'Error desconocido'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al vaciar carrito. Por favor, intente nuevamente.');
                        });
                    }
                }

                async function checkout() {
                    const carritoCount = <?php echo count($gruposCarrito); ?>;
                    if (carritoCount === 0) {
                        alert('El carrito está vacío. Agregue productos antes de procesar la compra.');
                        return;
                    }
                    
                    if (confirm('¿Desea registrar el pedido con los precios vigentes?')) {
                        const formData = new FormData();
                        formData.append('accion', 'registrar_compra');
                        try {
                            const response = await fetch('?pagina=carrito', {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();
                            if (data && data.status === 'success') {
                                window.location.href = '?pagina=gestionarfactura';
                            } else {
                                alert('Error al registrar el pedido: ' + (data.message || 'Error desconocido'));
                            }
                        } catch (error) {
                            console.error('Error al registrar el pedido:', error);
                            alert('No fue posible registrar el pedido. Intente nuevamente.');
                        }
                    }
                }
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>