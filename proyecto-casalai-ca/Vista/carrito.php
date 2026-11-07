<?php
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
 $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 11;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/public/bootstrap/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'header.php'; ?>
<style>
.tabladeConsultas {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.tabladeConsultas thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.tabladeConsultas thead th {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 12px;
    border: none;
    text-align: center;
}

.tabladeConsultas thead th:first-child {
    text-align: left;
    padding-left: 20px;
}

.tabladeConsultas thead th:last-child {
    padding-right: 20px;
}

.tabladeConsultas tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f5f9;
}

.tabladeConsultas tbody tr:hover {
    background-color: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tabladeConsultas tbody td {
    padding: 16px 12px;
    text-align: center;
    color: #475569;
    font-size: 0.95rem;
    border: none;
}

.tabladeConsultas tbody td:first-child {
    text-align: left;
    padding-left: 20px;
    font-weight: 500;
    color: #1e293b;
}

.tabladeConsultas tbody td:last-child {
    padding-right: 20px;
}

/* Estilo para la fila del total */
.tabladeConsultas tbody tr.table-active {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-weight: 700;
}

.tabladeConsultas tbody tr.table-active td {
    color: #1e293b;
    font-size: 1rem;
    border-top: 2px solid #cbd5e1;
}

.tabladeConsultas tbody tr.table-active td:nth-child(4) {
    color: #059669;
    font-size: 1.1rem;
}

/* Estilo para cuando no hay productos */
.tabladeConsultas tbody tr:last-child td {
    padding: 40px 20px;
    color: #64748b;
    font-style: italic;
}

.tabladeConsultas tbody tr:last-child td i {
    color: #94a3b8;
    margin-bottom: 8px;
}

/* Estilo para los botones de cantidad */
.cantidad-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    max-width: 140px;
    margin: 0 auto;
}

.btn-decrement, .btn-increment {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #6b7280;
}

.btn-decrement:hover, .btn-increment:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    transform: scale(1.05);
}
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 4px;
            margin-right: 15px;
            border: 1px solid #dee2e6;
        }

        .combo-images {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .combo-product-image {
            width: 52px;
            height: 52px;
            object-fit: contain;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

.cantidad-wrapper .form-control.cantidad {
    width: 60px;
    text-align: center;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 6px;
    font-weight: 600;
    background: #f9fafb;
}

/* Estilo para el botón eliminar */
.btn-eliminar {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
}

.btn-eliminar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

/* Estilo para los subtotales */
.subtotal {
    font-weight: 600;
    color: #059669;
}

/* Responsive */
@media (max-width: 768px) {
    .tabladeConsultas {
        font-size: 0.85rem;
    }
    
    .tabladeConsultas thead th,
    .tabladeConsultas tbody td {
        padding: 12px 8px;
    }
    
    .tabladeConsultas thead th:first-child,
    .tabladeConsultas tbody td:first-child {
        padding-left: 12px;
    }
    
    .tabladeConsultas thead th:last-child,
    .tabladeConsultas tbody td:last-child {
        padding-right: 12px;
    }
    
    .cantidad-wrapper {
        max-width: 120px;
    }
}
</style>
</head>
<body  class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>
        
        <h3>PRODUCTOS EN EL CARRITO</h3>

        <div class="ghost"></div>
    </div>

    <?php
    $gruposCarritoVista = [];
    foreach ($carritos ?? [] as $itemCarrito) {
        $esCombo = !empty($itemCarrito['id_combo']);
        $claveGrupo = $esCombo
            ? 'combo-' . (int)$itemCarrito['id_combo']
            : 'producto-' . (int)$itemCarrito['id_carrito_detalle'];
        if (!isset($gruposCarritoVista[$claveGrupo])) {
            $gruposCarritoVista[$claveGrupo] = [
                'es_combo' => $esCombo,
                'nombre_combo' => $itemCarrito['nombre_combo'] ?? 'Combo',
                'items' => [],
                'subtotal' => 0.0
            ];
        }
        $gruposCarritoVista[$claveGrupo]['items'][] = $itemCarrito;
        $gruposCarritoVista[$claveGrupo]['subtotal'] += (float)($itemCarrito['subtotal'] ?? 0);
    }
    ?>

    <table class="tabladeConsultas tabla" id="tabla-carrito">
        <thead>
            <tr>
                <th>Imagen y Serial</th>
                <th>Nombre del Producto</th>
                <th>Cantidad / Combos</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($gruposCarritoVista)): ?>
                <?php 
                $total = 0;
                foreach ($gruposCarritoVista as $grupoCarrito):
                    $total += $grupoCarrito['subtotal'];
                    $carrito = $grupoCarrito['items'][0];
                    $cantidadCombo = max(1, (int)($carrito['cantidad_combo'] ?? 1));
                    $cantidadVisible = $grupoCarrito['es_combo']
                        ? (int)$carrito['cantidad'] / $cantidadCombo
                        : (int)$carrito['cantidad'];
                    $cantidadVisible = max(1, (int)$cantidadVisible);
                ?>
                    <tr>
                        <td>
                            <?php if (!$grupoCarrito['es_combo']): ?>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($carrito['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($carrito['imagen']) ?>" class="product-image"
                                        alt="<?= htmlspecialchars($carrito['nombre']) ?>"
                                        onerror="this.src='assets/img/placeholder-product.png'">
                                <?php else: ?>
                                    <div class="product-image img-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <div class="combo-images" aria-label="Imágenes de los productos del combo">
                                    <?php foreach ($grupoCarrito['items'] as $componente): ?>
                                        <?php if (!empty($componente['imagen'])): ?>
                                            <img src="<?= htmlspecialchars($componente['imagen']) ?>"
                                                class="combo-product-image"
                                                alt="<?= htmlspecialchars($componente['nombre'] ?? 'Producto del combo') ?>"
                                                title="<?= htmlspecialchars($componente['nombre'] ?? 'Producto del combo') ?>"
                                                onerror="this.src='assets/img/placeholder-product.png'">
                                        <?php else: ?>
                                            <div class="combo-product-image img-placeholder"
                                                title="<?= htmlspecialchars($componente['nombre'] ?? 'Producto del combo') ?>">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($grupoCarrito['nombre_combo']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($grupoCarrito['es_combo']): ?>
                                <ul class="mb-0">
                                    <?php foreach ($grupoCarrito['items'] as $componente): ?>
                                        <li>
                                            <?php echo htmlspecialchars($componente['nombre'] . ' Modelo ' . $componente['nombre_modelo'] . ' de la Marca ' . $componente['nombre_marca']); ?>
                                            × <?php echo (int)$componente['cantidad_combo']; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?php echo htmlspecialchars($carrito['nombre'].' Modelo '.$carrito['nombre_modelo'].' de la Marca '.$carrito['nombre_marca']); ?>
                                <span class="badge bg-secondary">Unidad individual</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="cantidad-wrapper">
                                <button type="button" class="btn-decrement" data-id-carrito-detalle="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"><i class="bi bi-dash"></i></button>
                                <input type="number"
                                    class="form-control cantidad"
                                    value="<?php echo $cantidadVisible; ?>"
                                    min="1"
                                    readonly
                                    data-id-carrito-detalle="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"
                                    data-id-producto="<?php echo htmlspecialchars($carrito['id_producto']); ?>"
                                    data-step="1"
                                    data-units-per-combo="<?php echo $grupoCarrito['es_combo'] ? $cantidadCombo : 1; ?>">
                                <?php if ($grupoCarrito['es_combo']): ?><small>combos</small><?php endif; ?>
                                <button type="button" class="btn-increment" data-id-carrito-detalle="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"><i class="bi bi-plus"></i></button>
                            </div>
                        </td>
                        
                        <td>
                            <?php if ($grupoCarrito['es_combo']): ?>
                                <ul class="mb-0 list-unstyled">
                                    <?php foreach ($grupoCarrito['items'] as $componente): ?>
                                        <li><?php echo number_format($componente['precio']*$data['monitors']['bcv']['price'], 2); ?> BS c/u</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?php echo number_format($carrito['precio']*$data['monitors']['bcv']['price'], 2); ?> BS
                            <?php endif; ?>
                        </td>
                        <td class="subtotal"><?php echo number_format($grupoCarrito['subtotal']*$data['monitors']['bcv']['price'], 2); ?> BS</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                    data-id="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"
                                    data-es-combo="<?php echo !empty($carrito['id_combo']) ? '1' : '0'; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-active">
                    <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                    <td class="fw-bold" id="total-carrito"><?php echo number_format($total*$data['monitors']['bcv']['price'], 2); ?> BS</td>
                    <td></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="bi bi-cart-x" style="font-size: 2rem;"></i>
                        <p class="mt-2">No hay productos en el carrito.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
            
    <!-- Botones de acción -->
    <div class="d-flex justify-content-between mt-3">
        <?php if (!empty($carritos)): ?>
            <button type="button" class="btn btn-danger" id="eliminar-todo-carrito">
                <i class="bi bi-trash"></i> Vaciar Carrito
            </button>
            <button type="button" class="btn btn-success" id="registrar-compra">
                <i class="bi bi-check-circle"></i> Prefacturar
            </button>
        <?php endif; ?>
    </div>
</div>

    <script src="assets/public/bootstrap/js/sidebar.js"></script>
    <script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/public/js/jquery-3.7.1.min.js"></script>
    <script src="assets/public/js/jquery.dataTables.min.js"></script>
    <script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/public/js/datatable.js"></script>
    <script src="assets/javascript/sweetalert2.all.min.js"></script>
    <script src="assets/javascript/carrito.js"></script>

    <script>
        $(document).on('click', '.btn-increment', function () {
            let input = $(this).siblings('input.cantidad');
            let val = parseInt(input.val()) || 1;
            let step = parseInt(input.data('step')) || 1;
            input.val(val + step).trigger('change');
        });

        $(document).on('click', '.btn-decrement', function () {
            let input = $(this).siblings('input.cantidad');
            let val = parseInt(input.val()) || 1;
            let step = parseInt(input.data('step')) || 1;
            if (val > step) {
                input.val(val - step).trigger('change');
            }
        });
    </script>

<?php include 'footer.php'; ?>
<button 
    class="btn-ayuda"
    style="top: 100px; right: 100px;"
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