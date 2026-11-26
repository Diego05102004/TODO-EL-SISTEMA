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
        error_log("Error al generar JWT en marca: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'marca';
$titulo_pagina = 'Gestionar Marcas';

// Datos reales para las tarjetas
$marcasArray = $marcas ?? [];
$marcasPorId = $marcasArray;
usort($marcasPorId, function ($a, $b) {
    return ((int)($a['id_marca'] ?? 0)) <=> ((int)($b['id_marca'] ?? 0));
});


// Iniciar el buffer de contenido
ob_start();
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script defer src="assets/javascript/marca.js?v=<?php echo filemtime(__DIR__ . '/../../assets/javascript/marca.js'); ?>"></script>

<!-- Marcas Section -->
<div class="table-section" id="contenedor-marcas">
    <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>

    <div class="section-header">
        <h2>Listar Marcas</h2>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button class="btn-table-superior btn-incluir" title="Incluir Marcas" onclick="openModal('agregar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <!-- Tabla de Marcas -->
    <table id="tablaMarcas" class="table-info" data-datatable data-page-length="10" data-datatable-order='[[0, "desc"]]' data-datatable-no-order="2" data-datatable-no-search="2">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($marcas)): ?>
                <?php foreach ($marcas as $marca): ?>
                    <tr data-id="<?php echo (int)$marca['id_marca']; ?>">
                        <td>
                            <span class="campo-texto">
                                <?php echo (int)$marca['id_marca']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-texto">
                                <?php echo htmlspecialchars($marca['nombre_marca'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button class="btn-action btn-modificar"
                                title="Modificar Marcas"
                                data-user-action="edit">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button class="btn-action btn-eliminar" 
                                title="Eliminar Marcas"
                                data-user-action="delete">
                                <img src="assets/img/circle-x.svg">
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Registrar/Editar Marca -->
<div id="marcaModal" class="modal modal-container">
    <div class="modal-narrow">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-trademark"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Incluir Marcas</h2>
                    <p id="modalSubtitle">Complete los datos para incluir una nueva marca</p>
                </div>
                <button type="button" class="btn-help blue" title="Ayuda para Incluir Marcas" data-contexto="registrar">
                    <img src="assets/img/info-ayuda.svg">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form id="marcaForm" novalidate>
                <input type="hidden" name="accion" id="accionForm" value="registrar">
                <input type="hidden" name="id_marca" id="id_marca">

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información</h3>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_marca">Nombre*</label>
                            <input type="text" id="nombre_marca" name="nombre_marca" placeholder="Ingrese el nombre de la marca" maxlength="25" class="input-textnum">
                            <span class="span-value" id="snombre_marca"></span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn-confirmar btn-save">
                            <i class="fas fa-check"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteMarcaModal" class="modal modal-container" aria-hidden="true">
    <div class="modal-narrow">
        <div class="background-red">
            <div class="modal-header-content">
                <div class="modal-icon background-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Eliminar Marcas</h2>
                    <p>Confirmación de eliminación permanente</p>
                </div>
                <button type="button" class="btn-help red" title="Ayuda para Eliminar Marcas" data-contexto="eliminar">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div class="content-eliminacion">
                <div class="quest-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de eliminar esta marca?</h3>
                <p>Esta acción es permanente y no se puede deshacer.</p>

                <div class="info-eliminacion">
                    <div class="info-row">
                        <label>Marca:</label>
                        <span id="deleteMarcaName"></span>
                    </div>
                </div>

                <div class="modal-footer-content">
                    <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal('deleteMarcaModal')">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-confirmar btn-danger" id="confirmDeleteMarca">
                        <i class="fas fa-trash"></i> Eliminar
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
