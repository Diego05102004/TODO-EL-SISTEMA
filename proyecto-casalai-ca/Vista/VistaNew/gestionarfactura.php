<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

$pagina_actual = 'gestionarfactura';
$titulo_pagina = 'Gestión de Pedidos';

$rolActual = $_SESSION['id_rol'] ?? null;
$idModulo = 13;
$permiteConsultar = !empty($permisosUsuario[$rolActual][$idModulo]['consultar']) && $permisosUsuario[$rolActual][$idModulo]['consultar'] === true;

if (!$permiteConsultar) {
    header('Location: ?pagina=acceso-denegado');
    exit();
}

$facturas = is_array($facturas ?? null) ? $facturas : [];
$totalFacturas = count($facturas);
$pendientes = 0;
$pagadas = 0;
$canceladas = 0;

foreach ($facturas as $factura) {
    $estado = strtolower(trim((string)($factura['estatus'] ?? '')));

    if (strpos($estado, 'cancel') !== false || strpos($estado, 'anul') !== false) {
        $canceladas++;
    } elseif ($estado === '' || strpos($estado, 'borrador') !== false || strpos($estado, 'proceso') !== false || strpos($estado, 'incompleto') !== false) {
        $pendientes++;
    } else {
        $pagadas++;
    }
}

$porcentajePendientes = $totalFacturas > 0 ? round(($pendientes / $totalFacturas) * 100) : 0;
$porcentajePagadas = $totalFacturas > 0 ? round(($pagadas / $totalFacturas) * 100) : 0;
$porcentajeCanceladas = $totalFacturas > 0 ? round(($canceladas / $totalFacturas) * 100) : 0;

ob_start();
?>

<div class="orders-section">
    <div class="section-header">
        <h2>Gestión de Pedidos</h2>
        <div class="section-actions">
            <button type="button" class="btn-add-order" onclick="openCreateOrderModal()">
                <span class="btn-icon"><i class="fas fa-plus"></i></span>
                Crear Pedido
            </button>
            <button type="button" class="btn-filter" onclick="openFilterModal()">
                <span class="btn-icon"><i class="fas fa-search"></i></span>
                Filtrar
            </button>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card sales" data-summary-card="total">
            <div class="card-icon"><i class="fas fa-box"></i></div>
            <div class="card-content">
                <h3>Total Pedidos</h3>
                <p class="card-value"><?php echo $totalFacturas; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="100, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $totalFacturas > 0 ? 100 : 0; ?>%</span>
                </div>
            </div>
        </div>

        <div class="summary-card expenses" data-summary-card="pending">
            <div class="card-icon"><i class="fas fa-clock"></i></div>
            <div class="card-content">
                <h3>Pendientes</h3>
                <p class="card-value"><?php echo $pendientes; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentajePendientes; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentajePendientes; ?>%</span>
                </div>
            </div>
        </div>

        <div class="summary-card income" data-summary-card="completed">
            <div class="card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="card-content">
                <h3>Completados</h3>
                <p class="card-value"><?php echo $pagadas; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentajePagadas; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentajePagadas; ?>%</span>
                </div>
            </div>
        </div>

        <div class="summary-card cancelled-orders" data-summary-card="cancelled">
            <div class="card-icon"><i class="fas fa-ban"></i></div>
            <div class="card-content">
                <h3>Cancelados</h3>
                <p class="card-value"><?php echo $canceladas; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentajeCanceladas; ?>, 100" stroke="#64748b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentajeCanceladas; ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="orders-table-container">
        <div id="listado" aria-live="polite">
            <p class="orders-loading">Cargando pedidos...</p>
        </div>
    </div>
</div>

<div id="orderDetailsModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Detalles del Pedido</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="order-details-content">
                <div class="order-info-section">
                    <h3>Información del Pedido</h3>
                    <div class="detail-row"><span class="detail-label">Número:</span><span class="detail-value" id="detailNumber">-</span></div>
                    <div class="detail-row"><span class="detail-label">Cliente:</span><span class="detail-value" id="detailClient">-</span></div>
                    <div class="detail-row"><span class="detail-label">Fecha:</span><span class="detail-value" id="detailDate">-</span></div>
                    <div class="detail-row"><span class="detail-label">Estado:</span><span class="detail-value" id="detailStatus">-</span></div>
                    <div class="detail-row"><span class="detail-label">Total:</span><span class="detail-value" id="detailTotal">-</span></div>
                </div>
                <div class="order-products-section">
                    <h3>Productos del Pedido</h3>
                    <div class="products-list" id="detailProducts"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-close" onclick="closeDetailsModal()">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para Crear Pedido -->
<div id="createOrderModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Crear Nuevo Pedido</h2>
                    <p>Complete los datos para registrar una venta presencial</p>
                </div>
            </div>
            <span class="close-modal" onclick="closeCreateOrderModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="createOrderForm">
                <input type="hidden" name="accion" value="registrar">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente">
                            <i class="fas fa-user"></i>
                            Cliente*
                        </label>
                        <select id="cliente" name="cliente" required>
                            <option value="">Seleccione un cliente</option>
                            <?php if (!empty($clientes)): ?>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id_cliente']; ?>">
                                        <?php echo htmlspecialchars($cliente['nombre_cliente'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha">
                            <i class="fas fa-calendar"></i>
                            Fecha*
                        </label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="descuento">
                            <i class="fas fa-percent"></i>
                            Descuento (%)
                        </label>
                        <input type="number" id="descuento" name="descuento" value="0" min="0" max="100" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="estatus">
                            <i class="fas fa-toggle-on"></i>
                            Estatus*
                        </label>
                        <select id="estatus" name="estatus" required>
                            <option value="borrador">Borrador</option>
                            <option value="proceso">En Proceso</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>
                </div>

                <div class="products-section">
                    <h3><i class="fas fa-box"></i> Productos del Pedido</h3>
                    
                    <div class="product-selection">
                        <div class="form-group">
                            <label for="producto">
                                <i class="fas fa-tag"></i>
                                Producto
                            </label>
                            <select id="producto" name="producto">
                                <option value="">Seleccione un producto</option>
                                <?php if (!empty($productos)): ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?php echo $producto['id_producto']; ?>">
                                            <?php echo htmlspecialchars($producto['nombre_producto'] ?? ''); ?> - 
                                            $<?php echo number_format($producto['precio'] ?? 0, 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cantidad">
                                <i class="fas fa-hashtag"></i>
                                Cantidad
                            </label>
                            <input type="number" id="cantidad" name="cantidad" min="1" value="1">
                        </div>
                        
                        <button type="button" class="btn-add-product" onclick="addProduct()">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                </div>

                <!-- Lista de productos agregados -->
                <div class="products-list" id="productsList">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Los productos se agregarán aquí dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: 700;">Total:</td>
                                <td colspan="2" id="totalAmount" style="font-weight: 700; color: #2196F3;">$0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-close" onclick="closeCreateOrderModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn-save" onclick="submitOrder()">
                <i class="fas fa-check"></i> Registrar Pedido
            </button>
        </div>
    </div>
</div>

<style>
    .orders-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }
    .section-header h2 {
        margin: 0;
        color: #1f2937;
    }
    .section-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-add-order, .btn-filter, .btn-close {
        border: none;
        border-radius: 8px;
        padding: 11px 16px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-add-order {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
    }
    .btn-filter {
        background: #f3f4f6;
        color: #1f2937;
    }
    .btn-action {
        border: none;
        border-radius: 8px;
        padding: 8px 10px;
        margin-right: 6px;
        cursor: pointer;
        color: white;
    }
    .btn-view { background: #2196F3; }
    .btn-delete { background: #dc3545; }
    .orders-table-container {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }
    .orders-table th {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        text-align: left;
        padding: 14px 12px;
        font-size: 0.9rem;
    }
    .orders-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #374151;
    }
    .orders-table tr:hover {
        background: #f9fafb;
    }
    .client-info-mini {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .client-avatar-mini {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .status {
        display: inline-block;
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status.completed { background: #d1fae5; color: #065f46; }
    .status.processing { background: #fef3c7; color: #92400e; }
    .status.pending { background: #fee2e2; color: #991b1b; }
    .status.cancelled { background: #e5e7eb; color: #374151; }
    .cancelled-orders .card-icon { background: linear-gradient(135deg, #64748b, #475569); }
    
    /* Modal de Creación de Pedidos */
    .modal-large {
        max-width: 900px;
    }
    
    .modal-header-content {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .modal-icon {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-icon i {
        font-size: 1.5rem;
        color: white;
    }
    
    .modal-title-content h2 {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0 0 5px 0;
    }
    
    .modal-title-content p {
        font-size: 0.85rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
    }
    
    .form-group label i {
        color: #2196F3;
        margin-right: 8px;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        border-color: #2196F3;
        outline: none;
        box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }
    
    .products-section {
        margin-top: 25px;
        padding-top: 25px;
        border-top: 2px solid #e9ecef;
    }
    
    .products-section h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .products-section h3 i {
        color: #2196F3;
    }
    
    .product-selection {
        display: grid;
        grid-template-columns: 2fr 1fr auto;
        gap: 15px;
        align-items: end;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .product-selection {
            grid-template-columns: 1fr;
        }
    }
    
    .btn-add-product {
        padding: 12px 20px;
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-add-product:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
    }
    
    .products-list {
        margin-top: 20px;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .products-table thead {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
    }
    
    .products-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .products-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .products-table tfoot td {
        padding: 15px 12px;
        background: #f8f9fa;
        font-weight: 700;
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
    
    #listado .accordion { width: 100%; }
    #listado .accordion-item { border-bottom: 1px solid #e5e7eb; }
    #listado .accordion-header { margin: 0; }
    #listado .accordion-button {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px;
        border: 0;
        background: #f3f4f6;
        color: #1f2937;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }
    #listado .accordion-button::after {
        width: 8px;
        height: 8px;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        content: '';
        transform: rotate(45deg);
        transition: transform 0.2s ease;
    }
    #listado .accordion-button[aria-expanded="true"]::after { transform: rotate(225deg); }
    #listado .accordion-button.bg-success { background: #198754; color: #fff; }
    #listado .accordion-button.bg-success.text-white { color: #fff; }
    #listado .accordion-button.bg-warning { background: #ffc107; color: #212529; }
    #listado .accordion-collapse { display: none; }
    #listado .accordion-collapse.show { display: block; }
    #listado .accordion-body { padding: 16px; }
    #listado .table-responsive { overflow-x: auto; }
    #listado table { width: 100%; border-collapse: collapse; }
    #listado th, #listado td { padding: 10px 12px; border: 1px solid #e5e7eb; text-align: left; }
    #listado .alert { margin: 12px 0; padding: 12px 14px; border-radius: 6px; }
    #listado .alert-warning { background: #fff3cd; color: #664d03; }
    #listado .alert-info { background: #cff4fc; color: #055160; }
    #listado .alert-success { background: #d1e7dd; color: #0f5132; }
    #listado .alert-danger { background: #f8d7da; color: #842029; }
    #listado .alert-secondary { background: #e2e3e5; color: #41464b; }
    #listado .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; }
    #listado .badge.bg-success { background: #198754; color: #fff; }
    #listado .badge.bg-warning { background: #ffc107; color: #212529; }
    #listado .btn { display: inline-block; margin: 4px 4px 4px 0; padding: 8px 12px; border: 0; border-radius: 6px; color: #fff; cursor: pointer; }
    #listado .btn-success { background: #198754; }
    #listado .btn-danger { background: #dc3545; }
    #listado .btn-primary { background: #0d6efd; }
    .orders-loading { margin: 0; padding: 30px; color: #666; text-align: center; }
    .summary-cards {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        margin-bottom: 20px;
    }
    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        gap: 14px;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .card-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
        background: linear-gradient(135deg, #4f46e5, #3b82f6);
    }
    .summary-card.expenses .card-icon { background: linear-gradient(135deg, #f59e0b, #ef4444); }
    .summary-card.income .card-icon { background: linear-gradient(135deg, #10b981, #34d399); }
    .card-content h3 {
        margin: 0 0 6px;
        font-size: 0.88rem;
        color: #6b7280;
    }
    .card-value {
        margin: 0;
        font-size: 1.7rem;
        font-weight: 800;
        color: #111827;
    }
    .progress-circle {
        margin-top: 10px;
        width: 52px;
        height: 52px;
        position: relative;
    }
    .circular-chart {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    .circle-bg {
        fill: none;
        stroke: #e5e7eb;
        stroke-width: 3;
    }
    .circle {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        transition: stroke-dasharray 0.3s ease;
    }
    .percentage {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        color: #374151;
    }
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 2000;
    }
    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 720px;
        width: 90%;
        margin: 6% auto;
        box-shadow: 0 10px 35px rgba(0,0,0,0.25);
    }
    .modal-header {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 12px 12px 0 0;
    }
    .modal-header h2 {
        margin: 0;
    }
    .close-modal {
        font-size: 28px;
        cursor: pointer;
    }
    .modal-body {
        padding: 20px;
    }
    .modal-footer {
        padding: 15px 20px 20px;
        text-align: right;
    }
    .order-details-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .order-info-section, .order-products-section {
        background: #f9fafb;
        border-radius: 10px;
        padding: 16px;
    }
    .order-info-section h3, .order-products-section h3 {
        margin-top: 0;
        color: #111827;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        border-bottom: 1px solid #e5e7eb;
        padding: 8px 0;
    }
    .detail-value {
        color: #111827;
        font-weight: 600;
        text-align: right;
    }
    .products-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .product-item {
        background: white;
        border-radius: 8px;
        padding: 10px 12px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #e5e7eb;
    }
    .btn-close {
        background: #111827;
        color: white;
    }
    @media (max-width: 768px) {
        .order-details-content { grid-template-columns: 1fr; }
        .section-header { flex-direction: column; align-items: flex-start; }
    }
</style>

<script src="assets/public/js/jquery.min.js"></script>
<script src="assets/javascript/factura.js"></script>
<script>
    function closeDetailsModal() {
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            const closeButton = modal.querySelector('.close-modal');
            if (closeButton) {
                closeButton.addEventListener('click', closeDetailsModal);
            }
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeDetailsModal();
                }
            });
        }
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('#listado .accordion-button[data-bs-target]');
        if (!button) {
            return;
        }

        const target = document.querySelector(button.dataset.bsTarget);
        if (!target) {
            return;
        }

        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        button.classList.toggle('collapsed', expanded);
        target.classList.toggle('show', !expanded);
    });

    // Funciones para el modal de creación de pedidos
    let orderProducts = [];

    function openCreateOrderModal() {
        document.getElementById('createOrderModal').style.display = 'block';
        // Establecer fecha actual por defecto
        document.getElementById('fecha').valueAsDate = new Date();
    }

    function closeCreateOrderModal() {
        document.getElementById('createOrderModal').style.display = 'none';
        document.getElementById('createOrderForm').reset();
        orderProducts = [];
        updateProductsTable();
    }

    function addProduct() {
        const productoSelect = document.getElementById('producto');
        const cantidadInput = document.getElementById('cantidad');
        
        const productoId = productoSelect.value;
        const productoNombre = productoSelect.options[productoSelect.selectedIndex].text;
        const cantidad = parseInt(cantidadInput.value);
        
        if (!productoId || cantidad <= 0) {
            alert('Por favor, seleccione un producto y una cantidad válida');
            return;
        }
        
        // Extraer precio del texto del producto
        const precioTexto = productoNombre.split('-')[1].trim().replace('$', '');
        const precio = parseFloat(precioTexto);
        const nombre = productoNombre.split('-')[0].trim();
        
        // Verificar si el producto ya está en la lista
        const existingIndex = orderProducts.findIndex(p => p.id === productoId);
        if (existingIndex >= 0) {
            orderProducts[existingIndex].cantidad += cantidad;
            orderProducts[existingIndex].subtotal = orderProducts[existingIndex].cantidad * orderProducts[existingIndex].precio;
        } else {
            orderProducts.push({
                id: productoId,
                nombre: nombre,
                precio: precio,
                cantidad: cantidad,
                subtotal: precio * cantidad
            });
        }
        
        updateProductsTable();
        productoSelect.value = '';
        cantidadInput.value = 1;
    }

    function removeProduct(index) {
        orderProducts.splice(index, 1);
        updateProductsTable();
    }

    function updateProductsTable() {
        const tbody = document.getElementById('productsTableBody');
        tbody.innerHTML = '';
        
        let total = 0;
        
        orderProducts.forEach((product, index) => {
            total += product.subtotal;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${product.nombre}</td>
                <td>$${product.precio.toFixed(2)}</td>
                <td>${product.cantidad}</td>
                <td>$${product.subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn-action btn-delete" onclick="removeProduct(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
    }

    function submitOrder() {
        const form = document.getElementById('createOrderForm');
        const cliente = form.querySelector('#cliente').value;
        const fecha = form.querySelector('#fecha').value;
        const estatus = form.querySelector('#estatus').value;
        const descuento = form.querySelector('#descuento').value;
        
        if (!cliente || !fecha || !estatus) {
            alert('Por favor, complete todos los campos obligatorios');
            return;
        }
        
        if (orderProducts.length === 0) {
            alert('Por favor, agregue al menos un producto al pedido');
            return;
        }
        
        const formData = new FormData();
        formData.append('accion', 'registrar');
        formData.append('cliente', cliente);
        formData.append('fecha', fecha);
        formData.append('estatus', estatus);
        formData.append('descuento', descuento);
        
        orderProducts.forEach((product, index) => {
            formData.append(`id_producto[${index}]`, product.id);
            formData.append(`cantidad[${index}]`, product.cantidad);
        });
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.status === 'success') {
                alert('Pedido registrado correctamente');
                closeCreateOrderModal();
                location.reload();
            } else {
                alert('Error al registrar pedido: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al registrar pedido. Por favor, intente nuevamente.');
        });
    }

    // Event listeners para el modal de creación
    document.addEventListener('DOMContentLoaded', function () {
        const createModal = document.getElementById('createOrderModal');
        if (createModal) {
            const closeButton = createModal.querySelector('.close-modal');
            if (closeButton) {
                closeButton.addEventListener('click', closeCreateOrderModal);
            }
            createModal.addEventListener('click', function (event) {
                if (event.target === createModal) {
                    closeCreateOrderModal();
                }
            });
        }
    });
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
?>