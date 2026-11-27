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
        error_log("Error al generar JWT en categoria: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'categoria';
$titulo_pagina = 'Gestionar Categoría';

// Iniciar el buffer de contenido
ob_start();
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script defer src="assets/javascript/categoria.js?v=<?php echo filemtime(__DIR__ . '/../../assets/javascript/categoria.js'); ?>"></script>

<!-- Categorías Section -->
<div class="table-section" id="contenedor-categorias">
    <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>

    <div class="section-header">
        <h2>Listar Categoría</h2>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button class="btn-table-superior btn-incluir" title="Incluir Categoría" onclick="openModal('agregar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <!-- Tabla de Categorías -->
    <table id="tablaCategorias" class="table-info" data-datatable data-page-length="10" data-datatable-order='[[0, "desc"]]' data-datatable-no-order="2" data-datatable-no-search="2">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categorias)): ?>
                <?php foreach ($categorias as $categoria): ?>
                    <tr data-id="<?php echo (int)$categoria['id_categoria']; ?>"
                        data-nombre-categoria="<?php echo htmlspecialchars($categoria['nombre_categoria'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <td>
                            <span class="campo-texto">
                                <?php echo (int)$categoria['id_categoria']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-texto">
                                <?php echo htmlspecialchars($categoria['nombre_categoria'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button type="button" class="btn-action btn-modificar"
                                title="Modificar Categoría"
                                data-user-action="edit">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button type="button" class="btn-action btn-eliminar"
                                title="Eliminar Categoría"
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

<!-- Modal para Registrar/Editar Categoría -->
<div id="categoriaModal" class="modal modal-container">
    <div class="modal-narrow">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Incluir Categoría</h2>
                    <p id="modalSubtitle">Complete los datos para incluir una nueva categoría</p>
                </div>
                <button type="button" class="btn-help blue" title="Ayuda para registrar categorías" data-contexto="registrar">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form id="categoriaForm" novalidate>
                <input type="hidden" name="accion" id="accionForm" value="registrar">
                <input type="hidden" name="id_categoria" id="id_categoria">

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información</h3>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_categoria">Nombre de la Categoría*</label>
                            <input type="text" id="nombre_categoria" name="nombre_categoria" placeholder="Ingrese el nombre de la categoría" maxlength="20" class="input-textnum">
                            <span class="span-value" id="snombre_categoria"></span>
                        </div>
                    </div>

                    <div class="form-row caracteristicas-form-row">
                        <div class="form-group caracteristicas-form-group">
                            <label for="caracteristicas">Características*</label>
                            <div id="caracteristicasContainer"></div>
                            <span class="span-value" id="scaracteristicas"></span>
                            <button type="button" class="btn-add-caracteristica" id="btn-agregar-caracteristica">
                                <i class="fas fa-plus-circle"></i> Agregar característica
                            </button>
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

<div id="deleteCategoriaModal" class="modal modal-container" aria-hidden="true">
    <div class="modal-narrow">
        <div class="background-red">
            <div class="modal-header-content">
                <div class="modal-icon background-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Eliminar Categoría</h2>
                    <p>Confirmación de eliminación permanente</p>
                </div>
                <button type="button" class="btn-help red" title="Ayuda para eliminar categorías" data-contexto="eliminar">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div class="content-eliminacion">
                <div class="quest-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de eliminar esta categoría?</h3>
                <p>Esta acción es permanente y no se puede deshacer.</p>

                <div class="info-eliminacion">
                    <div class="info-row">
                        <label>Categoría:</label>
                        <span id="deleteCategoriaName"></span>
                    </div>
                </div>

                <div class="modal-footer-content">
                    <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal('deleteCategoriaModal')">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-confirmar btn-danger" id="confirmDeleteCategoria">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-add-caracteristica,
    .btn-remove-caracteristica {
        padding: 10px 18px;
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-add-caracteristica {
        background: linear-gradient(135deg, #a8e7f7 0%, #059cf3 100%);
    }

    .btn-remove-caracteristica {
        background: linear-gradient(135deg, #ff9a9e 0%, #f56c6c 100%);
    }

    .btn-add-caracteristica:hover:not(:disabled),
    .btn-remove-caracteristica:hover:not(:disabled) {
        transform: translateY(-2px);
    }

    .btn-add-caracteristica:hover:not(:disabled) {
        box-shadow: 0 4px 12px rgba(44, 75, 250, 0.479);
    }

    .btn-remove-caracteristica:hover:not(:disabled) {
        box-shadow: 0 4px 12px rgba(236, 78, 78, 0.479);
    }

    .btn-add-caracteristica:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    #categoriaModal .caracteristicas-form-row {
        justify-content: flex-start;
        align-items: flex-start;
        margin: 20px 0;
    }

    #categoriaModal .caracteristicas-form-group {
        width: 100%;
        height: auto;
        min-width: 0;
    }

    #categoriaModal #caracteristicasContainer {
        width: 100%;
    }

    #categoriaModal .caracteristica-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        align-items: start;
        gap: 10px;
        width: 100%;
        margin-bottom: 10px;
    }

    #categoriaModal .caracteristica-campo {
        width: 100%;
        height: auto;
        min-width: 0;
        margin: 0;
    }

    #categoriaModal .carac-nombre,
    #categoriaModal .carac-tipo,
    #categoriaModal .carac-max {
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        height: 35px;
    }

    #categoriaModal .caracteristica-max {
        margin-top: 8px;
    }

    #categoriaModal .btn-remove-caracteristica {
        grid-column: 3;
        align-self: start;
        white-space: nowrap;
    }

    @media (max-width: 600px) {
        #categoriaModal .caracteristica-item {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        #categoriaModal .caracteristica-nombre {
            grid-column: 1 / -1;
        }

        #categoriaModal .caracteristica-tipo {
            grid-column: 1;
        }

        #categoriaModal .btn-remove-caracteristica {
            grid-column: 2;
        }
    }
</style>


<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>
