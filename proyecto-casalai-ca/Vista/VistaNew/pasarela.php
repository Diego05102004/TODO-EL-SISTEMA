<?php
// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
 $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 12;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>
<?php
$pagina_actual = 'pasarela';
$titulo_pagina = 'Gestion de Pagos';
ob_start();
?>

<link rel="stylesheet" href="assets/styles/tabla_consulta.css">
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script>
// Puente jQuery -> Bootstrap 5: Bootstrap 5 ya no registra plugins jQuery ($.fn.modal)
if (window.jQuery && window.bootstrap && !jQuery.fn.modal) {
    jQuery.fn.modal = function (command) {
        return this.each(function () {
            var modal = bootstrap.Modal.getOrCreateInstance(this);
            if (command === 'show') modal.show();
            else if (command === 'hide') modal.hide();
            else if (command === 'toggle') modal.toggle();
        });
    };
}
</script>
<section class="table-section pasarela-section">
    <div class="section-header">
        <div>
            <h2>Pagos realizados</h2>
            <p>Consulta comprobantes y actualiza el estado de cada pago.</p>
        </div>
    </div>
    <div class="contenedor-tabla">
    
    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>LISTA DE PAGOS REALIZADOS</h3>
        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>ID Factura</th>
                <th>Cuenta</th>
                <th>Tipo de Pago</th>
                <th>Referencia</th>
                <th>Fecha</th>
                <th>Estatus</th>
                <th>Comprobante</th>
                <?php if ($_SESSION['nombre_rol'] != 'Cliente') {
                ?><th>Acciones</th><?php } ?>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($datos as $dato): ?>
            <tr data-id="<?= htmlspecialchars($dato['id_detalles']); ?>">
                <td>
                    <span class="campo-numeros">
                        <?= htmlspecialchars($dato['id_factura']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-nombres">
                        <?= htmlspecialchars($dato['nombre_cuenta']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-nombres">
                        <?= htmlspecialchars($dato['tipo']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        <?= htmlspecialchars($dato['referencia']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        <?= date('d/m/Y', strtotime($dato['fecha'])) ?>
                    </span>
                </td>
                <td>
                    <span class="campo-rango"
                          data-estatus="<?= htmlspecialchars($dato['estatus']); ?>" 
                          style="cursor:pointer;">
                        <?= htmlspecialchars($dato['estatus']); ?>
                    </span>
                </td>
                <td>
    <?php if (!empty($dato['comprobante'])): 
        $ruta_comprobante = 'comprobantes/' . $dato['comprobante'];
        $ruta_alternativa = $dato['comprobante'];
        
        if (file_exists($ruta_comprobante)): ?>
            <img src="<?= htmlspecialchars($ruta_comprobante); ?>" 
                 alt="Comprobante" 
                 class="img-comprobante"
                 style="max-width:80px;max-height:80px;border-radius:6px;border:1px solid #ccc;padding:3px;object-fit:contain;background:#fff;cursor:zoom-in;"
                 data-src="<?= htmlspecialchars($ruta_comprobante); ?>"
                 data-factura="<?= htmlspecialchars($dato['id_factura']); ?>"
                 data-referencia="<?= htmlspecialchars($dato['referencia']); ?>">
        <?php elseif (file_exists($ruta_alternativa)): ?>
            <img src="<?= htmlspecialchars($ruta_alternativa); ?>" 
                 alt="Comprobante" 
                 class="img-comprobante"
                 style="max-width:80px;max-height:80px;border-radius:6px;border:1px solid #ccc;padding:3px;object-fit:contain;background:#fff;cursor:zoom-in;"
                 data-src="<?= htmlspecialchars($ruta_alternativa); ?>"
                 data-factura="<?= htmlspecialchars($dato['id_factura']); ?>"
                 data-referencia="<?= htmlspecialchars($dato['referencia']); ?>">
        <?php else: ?>
            <img src="assets/img/no-disponible.png" alt="No disponible" style="max-width:80px;max-height:80px;border-radius:6px;border:1px solid #ccc;padding:3px;object-fit:contain;background:#fff;">
        <?php endif; ?>
    <?php else: ?>
        <img src="assets/img/no-disponible.png" alt="No disponible" style="max-width:80px;max-height:80px;border-radius:6px;border:1px solid #ccc;padding:3px;object-fit:contain;background:#fff;">
    <?php endif; ?>
</td>
<?php if ($_SESSION['nombre_rol'] != 'Cliente') {
                ?>
<td>
  <?php if ($_SESSION['nombre_rol'] == 'Administrador'|| $_SESSION['nombre_rol'] == 'SuperUsuario' || $_SESSION['nombre_rol'] == 'Almacenista'): ?>
    <?php if (isset($dato['estatus']) && $dato['estatus'] === 'Pago Procesado'): ?>
      <span class="text-success">Pago procesado. <br> No es posible cambiar estatus.</span>
    <?php else: ?>
      <button class="btn btn-primary modificarEstado" 
              data-id="<?= htmlspecialchars($dato['id_detalles']); ?>"
              data-factura="<?= htmlspecialchars($dato['id_factura']); ?>"
              data-estatus="Pago No Encontrado"
              data-observaciones="Pago no verificado aún">
        Cambiar Estatus
      </button>
    <?php endif; ?>
  <?php else: ?>
    <span class="text-muted">Sin permisos</span>
  <?php endif; ?>
</td>
<?php } ?>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
</div>
</section>

<!-- Modal para modificar estatus -->
<div class="modal fade" id="modificarEstadoModal" tabindex="-1" aria-labelledby="modificarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="modificarEstadoLabel">Modificar Estatus del Pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      
      <form id="formModificarEstado" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="estadoIdPago" name="id_detalles">
          <input type="hidden" name="id_factura" id="modificarIdFactura">
          <input type="hidden" name="accion" value="modificar_estado">
          
          <div class="mb-3">
            <label for="estatus" class="form-label">Estatus</label>
            <select class="form-select" id="estatus" name="estatus" required>
              <option value="" disabled selected>Seleccione una opción</option>
              <option value="Pago Procesado">Pago Procesado</option>
              <option value="Pago No Encontrado">Pago No Encontrado</option>
              <option value="Pago Incompleto">Pago Incompleto</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- Modal para mostrar comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1" aria-labelledby="modalComprobanteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalComprobanteLabel">Comprobante de Pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <small class="text-muted" id="infoComprobante">
            Factura: <span id="facturaNumero"></span> | Referencia: <span id="referenciaNumero"></span>
          </small>
        </div>
        <img id="imagenComprobante" src="" alt="Comprobante" class="img-fluid" style="max-height: 70vh; object-fit: contain;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <a href="#" id="descargarComprobante" class="btn btn-primary" download>
          <i class="bi bi-download"></i> Descargar
        </a>
      </div>
    </div>
  </div>
</div>



<script src="assets/public/js/datatable.js"></script>
<script src="assets/javascript/validaciones.js"></script>
<script src="assets/javascript/pasarela.js"></script>

<script>
function estatusAClase(estatus) {
    return estatus.toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .replace(/\s+/g, '-')
        .replace(/[^a-z\-]/g, '');
}

function aplicarClasesEstatus() {
    document.querySelectorAll('.campo-rango').forEach(el => {
        const estatus = el.dataset.estatus;
        const clase = estatusAClase(estatus);
        if (estatus && clase) el.classList.add(clase);
    });
}

// Función para mostrar el comprobante en el modal
function mostrarComprobanteModal(src, factura, referencia) {
    const modal = new bootstrap.Modal(document.getElementById('modalComprobante'));
    const imagen = document.getElementById('imagenComprobante');
    const facturaSpan = document.getElementById('facturaNumero');
    const referenciaSpan = document.getElementById('referenciaNumero');
    const descargarBtn = document.getElementById('descargarComprobante');
    
    // Configurar la imagen y información
    imagen.src = src;
    facturaSpan.textContent = factura;
    referenciaSpan.textContent = referencia;
    descargarBtn.href = src;
    
    // Mostrar el modal
    modal.show();
}

// Event listener para los comprobantes
document.addEventListener('DOMContentLoaded', function() {
    aplicarClasesEstatus();
    
    // Agregar event listener a todas las imágenes de comprobante
    document.querySelectorAll('.img-comprobante').forEach(img => {
        img.addEventListener('click', function(e) {
            e.preventDefault();
            const src = this.getAttribute('data-src');
            const factura = this.getAttribute('data-factura');
            const referencia = this.getAttribute('data-referencia');
            
            if (src && src !== 'assets/img/no-disponible.png') {
                mostrarComprobanteModal(src, factura, referencia);
            }
        });
    });
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalComprobante'));
            if (modal) {
                modal.hide();
            }
        }
    });
});

$(document).ready(function() {
    $('#tablaConsultas').DataTable({
        language: { url: 'assets/public/js/es-ES.json' },
        scrollX: true,
        scrollCollapse: true,
        order: [[0, 'desc']],
        initComplete: function () {
            var api = this.api();
            api.columns.adjust();
        }
    });
});

</script>

<style>
/* ===== Soporte local de modales Bootstrap 5 (sin bootstrap.min.css global) ===== */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    width: 100vw;
    height: 100vh;
    background-color: #000;
    opacity: 0;
    transition: opacity .15s linear;
}
.modal-backdrop.show { opacity: .5; }

#modificarEstadoModal,
#modalComprobante {
    z-index: 1055 !important;
    overflow-x: hidden;
    overflow-y: auto;
    outline: 0;
}

#modificarEstadoModal .modal-dialog,
#modalComprobante .modal-dialog {
    position: relative;
    width: auto;
    max-width: 500px;
    margin: 1.75rem auto;
    pointer-events: none;
}
#modalComprobante .modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 3.5rem);
}
#modificarEstadoModal.fade .modal-dialog,
#modalComprobante.fade .modal-dialog {
    transition: transform .3s ease-out;
    transform: translate(0, -50px);
}
#modificarEstadoModal.show .modal-dialog,
#modalComprobante.show .modal-dialog {
    transform: none;
}

#modificarEstadoModal .modal-content,
#modalComprobante .modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    margin: 0;
    pointer-events: auto;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, .2);
    border-radius: .3rem;
    outline: 0;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
}

#modificarEstadoModal .modal-header,
#modalComprobante .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: calc(.3rem - 1px);
    border-top-right-radius: calc(.3rem - 1px);
}
#modificarEstadoModal .modal-header { color: #212529; background-color: #fff; }

#modificarEstadoModal .modal-title,
#modalComprobante .modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 500;
    line-height: 1.5;
}

.btn-close {
    box-sizing: content-box;
    width: 1em;
    height: 1em;
    padding: .25em;
    color: #000;
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    border: 0;
    border-radius: .375rem;
    opacity: .5;
    cursor: pointer;
}
.btn-close:hover { color: #000; opacity: .75; }

#modificarEstadoModal .modal-body,
#modalComprobante .modal-body {
    position: relative;
    flex: 1 1 auto;
    padding: 1rem;
}
#modificarEstadoModal .modal-footer,
#modalComprobante .modal-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    padding: 1rem;
    border-top: 1px solid #dee2e6;
}

/* ===== Utilidades y controles de formulario usados en los modales ===== */
.mb-3 { margin-bottom: 1rem; }
.text-center { text-align: center !important; }
.text-muted { color: #6c757d !important; }
.text-success { color: #198754 !important; }
.img-fluid { max-width: 100%; height: auto; }

.form-label {
    margin-bottom: .5rem;
    font-size: 1rem;
}
.form-select {
    display: block;
    width: 100%;
    padding: .375rem 2.25rem .375rem .75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right .75rem center;
    background-size: 16px 12px;
    border: 1px solid #ced4da;
    border-radius: .375rem;
    appearance: none;
    -webkit-appearance: none;
}
.form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25);
}

.btn {
    display: inline-block;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    border: 1px solid transparent;
    padding: .375rem .75rem;
    font-size: 1rem;
    border-radius: .375rem;
    transition: color .15s, background-color .15s, border-color .15s, box-shadow .15s;
}
.btn:focus,
.btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25);
}
.btn-primary {
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.btn-primary:hover {
    color: #fff;
    background-color: #0b5ed7;
    border-color: #0a58ca;
}
.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}
.btn-secondary:hover {
    color: #fff;
    background-color: #5c636a;
    border-color: #565e64;
}

.img-comprobante {
    transition: all 0.3s ease;
}

.img-comprobante:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    border-color: #007bff !important;
}

#modalComprobante .modal-content {
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

#modalComprobante .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
}

#modalComprobante .modal-header .btn-close {
    filter: invert(1);
}

#imagenComprobante {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: 1px solid #dee2e6;
}

#infoComprobante {
    font-size: 0.9rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    #modalComprobante .modal-dialog {
        margin: 10px;
    }
    
    #imagenComprobante {
        max-height: 50vh;
    }
}
</style>
<button 
    class="btn-ayuda"
    style="top: 115px;"
    title="Visualizar Ayuda">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>

<script src="assets/javascript/jwt_validator.js"></script>


<style>
    body { font-size: 1rem; }
    .main-content { min-width: 0; }
    .pasarela-section .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 0 0 20px;
        padding: 22px 26px;
        background: #fff;
        border: 1px solid #e8edf4;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    }
    .pasarela-section .section-header h2 {
        margin: 0;
        color: #2d3748;
        font-size: 1.45rem;
        font-weight: 700;
    }
    .pasarela-section .section-header p {
        margin: 6px 0 0;
        color: #718096;
    }
    .pasarela-section .contenedor-tabla {
        display: block;
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 24px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e8edf4;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    }
    .pasarela-section .tabla-header { display: none; }
    #tablaConsultas { width: 100% !important; }
    #tablaConsultas thead th {
        background: #f7fafc;
        color: #4a5568;
        font-weight: 700;
        white-space: nowrap;
    }
    #tablaConsultas tbody tr:hover { background: #f7fafc; }
    #tablaConsultas .img-comprobante {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }
    #tablaConsultas .campo-rango {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 700;
        white-space: nowrap;
    }
    #tablaConsultas .pago-procesado { color: #17663a; background: #e6f6ec; }
    #tablaConsultas .pago-incompleto { color: #875b00; background: #fff4d6; }
    #tablaConsultas .pago-no-encontrado { color: #a12c36; background: #fde9eb; }
    #tablaConsultas .modificarEstado {
        border: 0;
        border-radius: 9px;
        background: #2196f3;
        font-weight: 600;
    }
    #modificarEstadoModal .modal-dialog { max-width: 540px; }
    #modificarEstadoModal .modal-content,
    #modalComprobante .modal-content {
        width: 100%;
        max-width: none;
        padding: 0;
        overflow: hidden;
        border: 0;
        border-radius: 16px;
    }
    #modificarEstadoModal .modal-header,
    #modalComprobante .modal-header { padding: 18px 22px; }
    #modificarEstadoModal .modal-body,
    #modalComprobante .modal-body { padding: 22px; }
    #modificarEstadoModal .modal-footer,
    #modalComprobante .modal-footer { padding: 16px 22px; }
    #modalComprobante .modal-dialog { max-width: 900px; }
    @media (max-width: 767px) {
        .pasarela-section .section-header { padding: 18px; }
        .pasarela-section .contenedor-tabla { padding: 14px; }
        #tablaConsultas .img-comprobante { width: 52px; height: 52px; }
    }
</style>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>
