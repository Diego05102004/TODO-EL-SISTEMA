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
        error_log("Error al generar JWT en modelo: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'modelo';
$titulo_pagina = 'Gestionar Modelos';

// Iniciar el buffer de contenido
ob_start();
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script defer src="assets/javascript/modelo.js?v=<?php echo filemtime(__DIR__ . '/../../assets/javascript/modelo.js'); ?>"></script>


<!-- Modelos Section -->
<div class="table-section" id="contenedor-modelos">
    <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>

    <div class="section-header">
        <h2>Listar Modelos</h2>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button class="btn-table-superior btn-incluir" title="Incluir Modelos" onclick="openModal('agregar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <table id="tablaModelos" class="table-info" data-datatable data-page-length="10" data-datatable-order='[[0, "desc"]]' data-datatable-no-order="3" data-datatable-no-search="3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($modelos)): ?>
                <?php foreach ($modelos as $modelo): ?>
                    <tr data-id="<?php echo (int)$modelo['id_modelo']; ?>"
                        data-id-marca="<?php echo (int)($modelo['id_marca'] ?? 0); ?>"
                        data-nombre-modelo="<?php echo htmlspecialchars($modelo['nombre_modelo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <td>
                            <span class="campo-texto">
                                <?php echo (int)$modelo['id_modelo']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-texto">
                                <?php echo htmlspecialchars($modelo['nombre_marca'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-texto">
                                <?php echo htmlspecialchars($modelo['nombre_modelo'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button type="button" class="btn-action btn-modificar"
                                title="Modificar Modelos"
                                data-user-action="edit">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button type="button" class="btn-action btn-eliminar"
                                title="Eliminar Modelos"
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

<!-- Modal para Registrar/Editar Modelo -->
<div id="modeloModal" class="modal modal-container">
    <div class="modal-narrow">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Incluir Modelo</h2>
                    <p id="modalSubtitle">Complete los datos para incluir un nuevo modelo</p>
                </div>
                <button type="button" class="btn-help blue" title="Ayuda para Incluir Modelos" data-contexto="registrar">
                    <img src="assets/img/info-ayuda.svg">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form id="modeloForm" novalidate>
                <input type="hidden" name="accion" id="accionForm" value="registrar">
                <input type="hidden" name="id_modelo" id="id_modelo">

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información</h3>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_marca">Marca del Modelo*</label>
                            <select id="id_marca" name="id_marca" class="input-select">
                                <option value="">Seleccione una marca</option>
                                <?php if (!empty($marcas)): ?>
                                    <?php foreach ($marcas as $marca): ?>
                                        <option value="<?php echo (int)$marca['id_marca']; ?>">
                                            <?php echo htmlspecialchars($marca['nombre_marca']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <span class="span-value" id="smarca"></span>
                        </div>

                        <div class="form-group">
                            <label for="nombre_modelo">Nombre del Modelo*</label>
                            <input type="text" id="nombre_modelo" name="nombre_modelo" placeholder="Ingrese el nombre del modelo" maxlength="25" class="input-textnum">
                            <span class="span-value" id="snombre_modelo"></span>
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

<div id="deleteModeloModal" class="modal modal-container" aria-hidden="true">
    <div class="modal-narrow">
        <div class="background-red">
            <div class="modal-header-content">
                <div class="modal-icon background-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Eliminar Modelos</h2>
                    <p>Confirmación de eliminación permanente</p>
                </div>
                <button type="button" class="btn-help red" title="Ayuda para Eliminar Modelos" data-contexto="eliminar">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div class="content-eliminacion">
                <div class="quest-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de eliminar este modelo?</h3>
                <p>Esta acción es permanente y no se puede deshacer.</p>

                <div class="info-eliminacion">
                    <div class="info-row">
                        <label>Marca/Modelo:</label>
                        <span id="deleteModeloName"></span>
                    </div>
                </div>

                <div class="modal-footer-content">
                    <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal('deleteModeloModal')">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-confirmar btn-danger" id="confirmDeleteModelo">
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
