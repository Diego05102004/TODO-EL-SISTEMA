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
        error_log("Error al generar JWT en recepcion: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'recepcion';
$titulo_pagina = 'Gestionar de Recepción';

// Iniciar el buffer de contenido
ob_start();
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script>
    const rolUsuarioActual = "<?php echo isset($_SESSION['nombre_rol']) ? $_SESSION['nombre_rol'] : ''; ?>";
</script>
<script src="microservicio/javascript/asistente_recepcion.js"></script>
<script src="assets/javascript/recepcion.js"></script>
<link rel="stylesheet" href="assets/styles/modal-recepcion-ia.css">

<!-- Recepciones Section -->
<div class="table-section">
    <div class="section-header">
        <h2>Lista de Recepciones</h2>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button class="btn-table-superior btn-incluir" title="Incluir Recepción" onclick="openModal('registrar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <!-- Tabla de Recepciones -->
    <table class="table-info" data-datatable data-page-length="10" data-datatable-order='[[0, "desc"]]' data-datatable-no-order="4" data-datatable-no-search="4">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Número de Factura</th>
                <th>Proveedor</th>
                <th>Monto Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recepciones)): ?>
                <?php foreach ($recepciones as $recepcion): ?>
                    <tr>
                        <td data-order="<?php echo htmlspecialchars((string)($recepcion['fecha'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo date('d/m/Y', strtotime($recepcion['fecha'] ?? 'now')); ?>
                        </td>
                        <td><?php echo htmlspecialchars($recepcion['correlativo'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($recepcion['nombre_proveedor'] ?? 'N/A'); ?></td>
                        <td>
                            $<?php echo number_format($recepcion['total_factura'] ?? $recepcion['costo_inversion'] ?? 0, 2, ',', '.'); ?>
                            <?php if (($recepcion['total_factura'] ?? null) === null): ?>
                                <small title="El IVA de esta recepción no fué registrada">IVA no registrado</small>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <button class="btn-action btn-detallar" 
                                title="Ver Detalles"
                                data-correlativo="<?php echo htmlspecialchars((string)($recepcion['correlativo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-proveedor="<?php echo htmlspecialchars((string)($recepcion['nombre_proveedor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-fecha="<?php echo htmlspecialchars((string)($recepcion['fecha'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                onclick="viewRecepcion(this)">
                                <img src="assets/img/eye.svg">
                            </button>
                            <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === 'Administrador'): ?>
                                <button class="btn-action btn-anular" 
                                    title="Anular Recepción"
                                    onclick="anularRecepcion('<?php echo htmlspecialchars($recepcion['correlativo'] ?? ''); ?>')">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Registrar Recepción -->
<div id="recepcionModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Nueva Recepción</h2>
                    <p>Complete los datos para registrar una nueva recepción de productos</p>
                </div>
                <button type="button" class="btn-ayuda-modal btn-ayuda" title="Ayuda para Incluir Recepción" data-contexto="registrar">
                    <img src="assets/img/info-ayuda.svg">
                </button>
            </div>
            <span class="close-modal" onclick="closeRecepcionModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="recepcionForm" enctype="multipart/form-data">
                <!-- Información General -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información General</h3>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fotoFacturaRecepcion">
                                <i class="fas fa-robot"></i>
                                Factura para análisis asistido
                            </label>
                            <input type="file" id="fotoFacturaRecepcion" name="foto_factura" accept="image/*,.pdf">
                            <small>Adjunte una imagen o PDF (máximo 5 MB). <br>
                                El análisis rellenará los datos detectados.</small>
                        </div>
                        <div class="form-group">
                            <label for="correlativo">
                                <i class="fas fa-file-invoice"></i>
                                Número de Factura*
                            </label>
                            <input type="text" placeholder="Ej: 012345" class="input-textnum" id="correlativo" name="correlativo" maxlength="6">
                            <span class="span-value" id="scorrelativo"></span>
                        </div>
                        <div class="form-group">
                            <label for="proveedor">
                                <i class="fas fa-building"></i>
                                Proveedor*
                            </label>
                            <select id="proveedor" name="proveedor" class="input-select">
                                <option value="">Seleccione un proveedor</option>
                                <?php if (!empty($proveedores)): ?>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?php echo $proveedor['id_proveedor'] ?? ''; ?>">
                                            <?php echo htmlspecialchars($proveedor['nombre_proveedor'] ?? 'Sin nombre'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No hay proveedores disponibles</option>
                                <?php endif; ?>
                            </select><br>
                            <span class="span-value" id="sproveedor"></span>
                        </div>
                    </div>
                    <div id="previewFacturaRecepcion" class="recepcion-ia-preview" hidden>
                        <img id="previewImagenRecepcion" alt="Vista previa de la factura" hidden>
                        <iframe id="previewPdfRecepcion" title="Vista previa de la factura PDF" hidden></iframe>
                    </div>
                    <div id="estadoIARecepcion" class="recepcion-ia-status" role="status" aria-live="polite" hidden></div>
                    <div id="resultadoIARecepcion" class="recepcion-ia-result" hidden></div>
                </div>
                
                <!-- Productos de la Recepción -->
                <div class="form-section productos-section">
                    <div class="section-title">
                        <i class="fas fa-boxes"></i>
                        <h3>Productos de la Recepción</h3>
                        <span class="product-count">0 productos</span>

                        <button type="button" class="btn-add-row" title="Agregar Producto" onclick="addProductoRow()">
                        <img src="assets/img/plus.svg">
                    </button>
                    </div>
                    <div id="productosList">
                        <div class="producto-row">
                            <div class="form-group">
                                <label>Producto*</label>
                                <select name="producto[]" class="producto-select input-select" onchange="seleccionarProducto(this)">
                                    <option value="">Seleccione un producto</option>
                                    <?php if (!empty($productos)): ?>
                                        <?php foreach ($productos as $producto): ?>
                                            <option value="<?php echo htmlspecialchars((string)($producto['id_producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-codigo="<?php echo htmlspecialchars((string)($producto['id_producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-marca="<?php echo htmlspecialchars($producto['nombre_marca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-modelo="<?php echo htmlspecialchars($producto['nombre_modelo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-serial="<?php echo htmlspecialchars($producto['serial'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($producto['nombre_producto'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No hay productos disponibles</option>
                                    <?php endif; ?>
                                </select>
                                <span class="span-value sproducto-msg"></span>
                            </div>
                            <div class="form-group">
                                <label>Código</label>
                                <input type="text" class="producto-codigo input-textnum" readonly>
                            </div>
                            <div class="form-group">
                                <label>Marca</label>
                                <input type="text" class="producto-marca input-textnum" readonly>
                            </div>
                            <div class="form-group">
                                <label>Modelo</label>
                                <input type="text" class="producto-modelo input-textnum" readonly>
                            </div>
                            <div class="form-group">
                                <label>Serial</label>
                                <input type="text" class="producto-serial input-textnum" readonly>
                            </div>
                            <div class="form-group">
                                <label>Costo Unitario*</label>
                                <input type="text" name="costo[]" placeholder="0.00" class="input-with-icon input-textnum costo-input" maxlength="12" oninput="limitarDigitos(this, 9, true); limitarDosDecimales(this); calcularSubtotal(this);">
                                <span class="span-value scosto-msg"></span>
                            </div>
                            <div class="form-group">
                                <label>Cantidad*</label>
                                <input type="text" name="cantidad[]" placeholder="0" class="input-with-icon input-textnum cantidad-input" maxlength="9" oninput="limitarDigitos(this, 9); calcularSubtotal(this)">
                                <span class="span-value scantidad-msg"></span>
                            </div>
                            <div class="form-group subtotal-group">
                                <label>Subtotal</label>
                                <input type="text" class="subtotal-display" readonly value="$0.00">
                            </div>
                            <button type="button" class="btn-remove-row" onclick="removeProductoRow(this)" title="Remover Producto">
                                <img src="assets/img/circle-x.svg">
                            </button>
                        </div>
                    </div>
                    <div class="productos-footer">
                        <div class="total-section recepcion-resumen-factura">
                            <div class="resumen-factura-item">
                                <span class="total-label">Subtotal:</span>
                                <strong class="total-value" id="subtotalRecepcion">$0.00</strong>
                            </div>
                            <div class="resumen-factura-item resumen-iva-tasa">
                                <label for="porcentajeIvaRecepcion">IVA (%)*</label>
                                <input type="text" id="porcentajeIvaRecepcion" placeholder="16.00" name="porcentaje_iva" class="input-with-icon input-textnum costo-input" maxlength="5" oninput="limitarDosDecimales(this); calcularSubtotal(this);">
                                <span class="span-value" id="sporcentajeIva"></span>
                            </div>
                            <div class="resumen-factura-item">
                                <span class="total-label">Monto IVA:</span>
                                <strong class="total-value" id="montoIvaRecepcion">$0.00</strong>
                            </div>
                            <div class="resumen-factura-item resumen-factura-total">
                                <span class="total-label">Total factura:</span>
                                <strong class="total-value" id="totalValue">$0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btm-confirmar btn-cancel" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btm-confirmar btn-save" onclick="saveRecepcion()">
                        <i class="fas fa-check"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles de Recepción -->
<div id="viewModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Detalles de Recepción</h2>
                    <p>Información completa de la recepción seleccionada</p>
                </div>
            </div>
            <span class="close-modal" onclick="closeViewModal()">&times;</span>
        </div>
        <div class="modal-body-detallar">
            <div class="recepcion-detallar">
                <!-- Información General -->
                <div class="detalle-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información General</h3>
                    </div>
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <label>Número de Factura:</label>
                            <span id="viewCorrelativo"></span>
                        </div>
                        <div class="detalle-item">
                            <label>Proveedor:</label>
                            <span id="viewProveedor"></span>
                        </div>
                        <div class="detalle-item">
                            <label>Fecha:</label>
                            <span id="viewFecha"></span>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="detalle-section">
                    <div class="section-title">
                        <i class="fas fa-boxes"></i>
                        <h3>Productos Recibidos</h3>
                    </div>
                    <div class="detalle-table" style="overflow-x: auto;">
                        <table style="width: 100%; white-space: nowrap;">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Código</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Serial</th>
                                    <th>Cantidad</th>
                                    <th>Costo Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="viewProductos">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="monto"><strong>Subtotal:</strong></td>
                                    <td><span id="viewSubtotal">$0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="monto"><strong>IVA (<span id="viewIvaPorcentaje">0%</span>):</strong></td>
                                    <td><span id="viewMontoIva">No registrado</span></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="monto"><strong>Total factura:</strong></td>
                                    <td><span id="viewTotal">$0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Anular Recepción -->
<div id="anularModal" class="modal">
    <div class="modal-content">
        <div class="modal-header warning">
            <div class="modal-header-content">
                <div class="modal-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Anular Recepción</h2>
                    <p>Confirmación de anulación de recepción</p>
                </div>
            </div>
            <span class="close-modal" onclick="closeAnularModal()">&times;</span>
        </div>
        <div class="modal-body-warning">
            <div class="anular-content">
                <div class="anular-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de anular esta recepción?</h3>
                <p>Esta acción no se puede deshacer y afectará el inventario del sistema.</p>
                
                <div class="anular-info">
                    <div class="info-row">
                        <label>Número de Factura:</label>
                        <span id="anularCorrelativo"></span>
                    </div>
                </div>
                <div class="modal-footer-content">
                    <button class="btm-confirmar btn-cancel" onclick="closeAnularModal()">
                        Cancelar
                    </button>
                    <button class="btm-confirmar btn-danger" onclick="confirmarAnulacion()">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>