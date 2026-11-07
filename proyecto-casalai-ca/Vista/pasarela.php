<?php
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
 $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 12;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>


<title>Gestión de Pagos Realizados</title>
<?php include 'header.php'; ?>
</head>
<body class="fondo" style="height:100vh; background-image:url(assets/img/fondo.jpg); background-size:cover; background-position:center; background-repeat:no-repeat;">
<?php include 'NewNavBar.php'; ?>

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
      <span class="text-success">Pago procesado. No es posible cambiar estatus.</span>
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

<?php include 'footer.php'; ?>
<script src="assets/public/bootstrap/js/sidebar.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>
<script src="assets/javascript/sweetalert2.all.min.js"></script>
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
</body>
</html>

<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}