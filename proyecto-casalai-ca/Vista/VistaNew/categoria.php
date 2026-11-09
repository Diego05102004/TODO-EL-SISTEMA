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
$titulo_pagina = 'Gestión de Categorías';

// Datos reales para las tarjetas
$categoriasArray = $categorias ?? [];
$totalCategorias = count($categoriasArray);
$categoriasPorId = $categoriasArray;
usort($categoriasPorId, function ($a, $b) {
    return ((int)($a['id_categoria'] ?? 0)) <=> ((int)($b['id_categoria'] ?? 0));
});
$primeraCategoria = $categoriasPorId[0] ?? null;
$ultimaCategoria = $categoriasPorId[$totalCategorias - 1] ?? null;

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Categorías -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-tags"></i></div>
                    <div class="card-content">
                        <h3>Total Categorías</h3>
                        <p class="card-value" id="categoria-count-total"><?php echo $totalCategorias; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="100, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">100%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="card-content">
                        <h3>Primera Registrada</h3>
                        <p class="card-value" id="categoria-primera" style="font-size: 1.1rem;"><?php echo $primeraCategoria ? htmlspecialchars($primeraCategoria['nombre_categoria']) : 'N/A'; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalCategorias > 0 ? round(100 / $totalCategorias, 1) : 0; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalCategorias > 0 ? round(100 / $totalCategorias) . '%' : '0%'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-content">
                        <h3>Última Registrada</h3>
                        <p class="card-value" id="categoria-ultima" style="font-size: 1.1rem;"><?php echo $ultimaCategoria ? htmlspecialchars($ultimaCategoria['nombre_categoria']) : 'N/A'; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalCategorias > 0 ? round(100 / $totalCategorias, 1) : 0; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalCategorias > 0 ? round(100 / $totalCategorias) . '%' : '0%'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categorías Section -->
            <div class="categorias-section" id="contenedor-categorias">
                <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>
                <div class="section-header">
                    <h2>Gestión de Categorías</h2>
                    <div class="section-actions">
                        <button class="btn-add-categoria" id="btn-add-categoria">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Nueva Categoría
                        </button>
                        <button class="btn-filter" id="btn-filter">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Buscar
                        </button>
                    </div>
                </div>

                <!-- Tabla de Categorías -->
                <div class="table-container">
                    <table class="categorias-table" id="tablaCategorias" data-datatable data-page-length="15" data-datatable-order="[[0,\"desc\"]]" data-datatable-no-order="2" data-datatable-no-search="2">
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
                                    <tr data-id="<?php echo (int)$categoria['id_categoria']; ?>">
                                        <td><?php echo (int)$categoria['id_categoria']; ?></td>
                                        <td><?php echo htmlspecialchars($categoria['nombre_categoria'] ?? 'N/A'); ?></td>
                                        <td>
                                            <button class="btn-action btn-edit" title="Modificar Categoría"
                                                data-id="<?php echo (int)$categoria['id_categoria']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($categoria['nombre_categoria'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" title="Eliminar Categoría"
                                                data-id="<?php echo (int)$categoria['id_categoria']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($categoria['nombre_categoria'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-tags" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay categorías registradas</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Categoría -->
            <div id="categoriaModal" class="modal">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Nueva Categoría</h2>
                                <p id="modalSubtitle">Complete los datos para registrar una nueva categoría</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="categoriaForm">
                            <input type="hidden" name="accion" id="accionForm" value="registrar">
                            <input type="hidden" name="id_categoria" id="id_categoria">

                            <div class="form-group">
                                <label for="nombre_categoria">
                                    <i class="fas fa-tag"></i>
                                    Nombre de la Categoría*
                                </label>
                                <input type="text" id="nombre_categoria" name="nombre_categoria" required
                                       placeholder="Ej: Electrónica, Ropa, Alimentos" maxlength="20" class="input-with-icon">
                                <i class="fas fa-tags input-icon"></i>
                                <span class="span-error" id="error-nombre-categoria"></span>
                                <span class="span-error" id="error-caracteristicas"></span>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-list"></i>
                                    Características* <small>(máx. 5)</small>
                                </label>
                                <div id="caracteristicasContainer"></div>
                                <button type="button" class="btn-add-caracteristica" id="btn-agregar-caracteristica">
                                    <i class="fas fa-plus-circle"></i> Agregar característica
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" id="btn-save-categoria">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para Buscar Categoría -->
            <div id="searchModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2>Buscar Categoría</h2>
                                <p>Filtre las categorías por nombre</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeSearchModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="searchForm">
                            <div class="form-group">
                                <label for="searchNombre">
                                    <i class="fas fa-tag"></i>
                                    Nombre
                                </label>
                                <input type="text" id="searchNombre" name="searchNombre"
                                       placeholder="Ingrese el nombre de la categoría">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeSearchModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" id="btn-do-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>

            <button class="btn-ayuda" id="btn-ayuda-categoria" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <style>
                /* Estilos específicos de Categorías */
                .categorias-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .categorias-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .categorias-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .categorias-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .categorias-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .categorias-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .btn-add-categoria, .btn-filter {
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

                .btn-add-categoria {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-add-categoria:hover, .btn-filter:hover {
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
                    margin-right: 8px;
                }

                .btn-edit {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-delete {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-action:hover {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }

                /* Características dinámicas */
                .caracteristica-item {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 10px;
                    align-items: center;
                }

                .caracteristica-item input,
                .caracteristica-item select {
                    flex: 1;
                    padding: 10px 12px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.9rem;
                    transition: all 0.3s ease;
                }

                .caracteristica-item input:focus,
                .caracteristica-item select:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .caracteristica-item .max-caracteres {
                    flex: 0 0 140px;
                }

                .caracteristica-item .btn-eliminar-caracteristica {
                    flex: 0 0 auto;
                    width: 35px;
                    height: 35px;
                    border: none;
                    border-radius: 8px;
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: all 0.3s ease;
                }

                .caracteristica-item .btn-eliminar-caracteristica:hover {
                    transform: scale(1.1);
                }

                .btn-add-caracteristica {
                    margin-top: 5px;
                    padding: 10px 18px;
                    border: none;
                    border-radius: 10px;
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                    font-size: 0.85rem;
                    font-weight: 600;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-add-caracteristica:hover:not(:disabled) {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 15px rgba(79, 172, 254, 0.3);
                }

                .btn-add-caracteristica:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .caracteristica-loading {
                    padding: 20px;
                    text-align: center;
                    color: #718096;
                }

                /* Modal styles */
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    animation: fadeIn 0.3s;
                }

                .modal-content {
                    background-color: #fff;
                    margin: 5% auto;
                    padding: 0;
                    border-radius: 10px;
                    width: 90%;
                    max-width: 700px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                    animation: slideDown 0.3s;
                    max-height: 90vh;
                    overflow-y: auto;
                }

                .modal-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
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

                .close-modal {
                    font-size: 28px;
                    font-weight: bold;
                    color: white;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .close-modal:hover {
                    transform: scale(1.1);
                }

                .modal-body {
                    padding: 25px;
                }

                .form-group {
                    margin-bottom: 20px;
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

                .form-group label small {
                    font-weight: 400;
                    color: #718096;
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

                .input-with-icon {
                    position: relative;
                }

                .input-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #2196F3;
                    pointer-events: none;
                }

                .span-error {
                    display: block;
                    color: #dc2626;
                    font-size: 0.82rem;
                    margin-top: 5px;
                    min-height: 1em;
                }

                .modal-footer {
                    padding: 20px 25px;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }

                .btn-cancel, .btn-save {
                    padding: 12px 25px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-cancel {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                }

                .btn-cancel:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
                }

                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-save:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                /* Botón de ayuda flotante */
                .btn-ayuda {
                    position: fixed;
                    top: 115px;
                    right: 25px;
                    width: 50px;
                    height: 50px;
                    border: none;
                    border-radius: 50%;
                    background-color: #5995fd;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                    z-index: 1000;
                }
                .btn-ayuda img {
                    width: 25px;
                    height: 25px;
                    pointer-events: none;
                }
                .btn-ayuda:hover {
                    background-color: #4d84e2;
                    transform: scale(1.1);
                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .categorias-table {
                        font-size: 0.8rem;
                    }

                    .categorias-table th,
                    .categorias-table td {
                        padding: 10px;
                    }

                    .caracteristica-item {
                        flex-wrap: wrap;
                    }

                    .caracteristica-item .max-caracteres {
                        flex: 1 1 100%;
                    }
                }
            </style>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=categoria';
                const MAX_CARACTERISTICAS = 5;
                const REGEX_NOMBRE = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/;
                const REGEX_CARAC_NOMBRE = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/;

                const INPUT_NOMBRE = document.getElementById('nombre_categoria');
                const ERROR_NOMBRE = document.getElementById('error-nombre-categoria');
                const ERROR_CARACS = document.getElementById('error-caracteristicas');
                const CONTENEDOR = document.getElementById('caracteristicasContainer');
                const BTN_AGREGAR = document.getElementById('btn-agregar-caracteristica');

                /* ==================== Utilidades ==================== */
                function esc(text) {
                    const div = document.createElement('div');
                    div.textContent = text == null ? '' : String(text);
                    return div.innerHTML;
                }

                function space(str) {
                    return String(str).replace(/\s{2,}/g, ' ');
                }

                async function postAccion(formData) {
                    const res = await fetch(ENDPOINT, { method: 'POST', body: formData });
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Respuesta no JSON del servidor:', text);
                        return { status: 'error', message: 'Error en la solicitud. Intente nuevamente.' };
                    }
                }

                function obtenerTabla() {
                    const tabla = document.getElementById('tablaCategorias');
                    if (!tabla) return null;
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#tablaCategorias')) {
                        return jQuery('#tablaCategorias').DataTable();
                    }
                    return null;
                }

                /* ==================== Filas de la tabla ==================== */
                function encontrarTr(id) {
                    const dt = obtenerTabla();
                    if (dt) {
                        const nodos = dt.rows().nodes().toArray();
                        for (let i = 0; i < nodos.length; i++) {
                            const nodo = nodos[i];
                            if (nodo && nodo.getAttribute && nodo.getAttribute('data-id') === String(id)) {
                                return nodo;
                            }
                        }
                    }
                    return document.querySelector('#tablaCategorias tbody tr[data-id="' + id + '"]');
                }

                function buildRowData(categoria) {
                    const id = parseInt(categoria.id_categoria, 10);
                    const nombre = esc(categoria.nombre_categoria);
                    return [
                        String(id),
                        nombre,
                        '<button class="btn-action btn-edit" title="Modificar Categoría" data-id="' + id + '" data-nombre="' + nombre + '"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn-action btn-delete" title="Eliminar Categoría" data-id="' + id + '" data-nombre="' + nombre + '"><i class="fas fa-trash"></i></button>'
                    ];
                }

                function agregarFila(categoria) {
                    const dt = obtenerTabla();
                    if (dt) {
                        const row = dt.row.add(buildRowData(categoria));
                        const nodo = row.node();
                        if (nodo) nodo.setAttribute('data-id', categoria.id_categoria);
                        row.draw(false);
                    } else {
                        const tbody = document.querySelector('#tablaCategorias tbody');
                        if (!tbody) return;
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', categoria.id_categoria);
                        buildRowData(categoria).forEach(function (html) {
                            const td = document.createElement('td');
                            td.innerHTML = html;
                            tr.appendChild(td);
                        });
                        tbody.insertBefore(tr, tbody.firstChild);
                    }
                    actualizarContadores();
                }

                function actualizarFila(categoria) {
                    const tr = encontrarTr(categoria.id_categoria);
                    const dt = obtenerTabla();
                    if (dt && tr) {
                        const fila = dt.row(tr);
                        if (fila.length) {
                            fila.data(buildRowData(categoria)).draw(false);
                            actualizarContadores();
                            return;
                        }
                    }
                    if (tr) {
                        const tds = tr.querySelectorAll('td');
                        buildRowData(categoria).forEach(function (html, i) {
                            if (tds[i]) tds[i].innerHTML = html;
                        });
                    }
                    actualizarContadores();
                }

                function eliminarFila(id) {
                    const tr = encontrarTr(id);
                    const dt = obtenerTabla();
                    if (dt && tr) {
                        const fila = dt.row(tr);
                        if (fila.length) { fila.remove().draw(false); actualizarContadores(); return; }
                    }
                    if (tr) { tr.remove(); actualizarContadores(); }
                }

                function actualizarContadores() {
                    const filas = document.querySelectorAll('#tablaCategorias tbody tr[data-id]');
                    const total = filas.length;
                    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                    set('categoria-count-total', total);

                    let primera = null, ultima = null;
                    filas.forEach(function (tr) {
                        const id = parseInt(tr.getAttribute('data-id'), 10);
                        const nombre = tr.querySelector('td:nth-child(2)') ? tr.querySelector('td:nth-child(2)').textContent.trim() : '';
                        if (!primera || id < primera.id) primera = { id: id, nombre: nombre };
                        if (!ultima || id > ultima.id) ultima = { id: id, nombre: nombre };
                    });
                    set('categoria-primera', primera ? primera.nombre : 'N/A');
                    set('categoria-ultima', ultima ? ultima.nombre : 'N/A');

                    document.querySelectorAll('#tablaCategorias .progress-circle .circle').forEach(function (circle, idx) {
                        if (idx === 0) return;
                        const pct = total > 0 ? Math.round(100 / total) : 0;
                        circle.setAttribute('stroke-dasharray', pct + ', 100');
                        const label = circle.parentElement.querySelector('.percentage');
                        if (label) label.textContent = pct + '%';
                    });
                }

                /* ==================== Características dinámicas ==================== */
                function crearItemCaracteristica(datos, puedeEliminar) {
                    const div = document.createElement('div');
                    div.classList.add('caracteristica-item');

                    const nombre = datos && datos.nombre ? esc(datos.nombre) : '';
                    const tipo = datos && datos.tipo ? datos.tipo : '';
                    const max = datos && datos.max ? datos.max : '';

                    div.innerHTML =
                        '<input type="text" class="carac-nombre" placeholder="Nombre (ej: Capacidad RAM)" maxlength="20" value="' + nombre + '">' +
                        '<select class="carac-tipo">' +
                            '<option value="" disabled hidden>Tipo</option>' +
                            '<option value="int"' + (tipo === 'int' ? ' selected' : '') + '>Entero</option>' +
                            '<option value="float"' + (tipo === 'float' ? ' selected' : '') + '>Decimal</option>' +
                            '<option value="string"' + (tipo === 'string' ? ' selected' : '') + '>Texto</option>' +
                        '</select>' +
                        '<input type="number" class="carac-max max-caracteres" placeholder="Máx. caracteres" min="1" max="255" value="' + (tipo === 'string' ? max : '') + '" style="' + (tipo === 'string' ? '' : 'display:none;') + '">' +
                        (puedeEliminar ? '<button type="button" class="btn-eliminar-caracteristica" title="Quitar característica">✖</button>' : '');

                    const selectTipo = div.querySelector('.carac-tipo');
                    const inputMax = div.querySelector('.carac-max');

                    selectTipo.addEventListener('change', function () {
                        if (this.value === 'string') {
                            inputMax.style.display = '';
                            inputMax.required = true;
                        } else {
                            inputMax.style.display = 'none';
                            inputMax.value = '';
                            inputMax.required = false;
                        }
                    });

                    // Solo permitir dígitos en max, máximo 3 caracteres
                    inputMax.addEventListener('keypress', function (e) {
                        if (!/[0-9]/.test(e.key)) e.preventDefault();
                        if (this.value && this.value.length >= 3 && this.selectionStart === this.selectionEnd) e.preventDefault();
                    });
                    inputMax.addEventListener('input', function () {
                        let val = (this.value || '').replace(/\D+/g, '').slice(0, 3);
                        let num = parseInt(val || '0', 10);
                        if (isNaN(num)) num = 0;
                        if (num > 255) num = 255;
                        this.value = num ? String(num) : '';
                    });

                    const btnQuitar = div.querySelector('.btn-eliminar-caracteristica');
                    if (btnQuitar) {
                        btnQuitar.addEventListener('click', function () {
                            div.remove();
                            actualizarBotonAgregar();
                        });
                    }

                    // Validación visual en vivo para nombre de característica
                    const inputNombre = div.querySelector('.carac-nombre');
                    inputNombre.addEventListener('keypress', function (e) {
                        if (!REGEX_CARAC_NOMBRE.test(e.key)) e.preventDefault();
                        this.value = space(this.value);
                    });
                    inputNombre.addEventListener('input', function () {
                        this.value = space(this.value);
                    });

                    return div;
                }

                function totalCaracteristicas() {
                    return CONTENEDOR ? CONTENEDOR.querySelectorAll('.caracteristica-item').length : 0;
                }

                function actualizarBotonAgregar() {
                    if (BTN_AGREGAR) BTN_AGREGAR.disabled = totalCaracteristicas() >= MAX_CARACTERISTICAS;
                }

                function agregarCaracteristica(datos, puedeEliminar) {
                    if (!CONTENEDOR) return;
                    if (totalCaracteristicas() >= MAX_CARACTERISTICAS) return;
                    CONTENEDOR.appendChild(crearItemCaracteristica(datos || {}, puedeEliminar !== false));
                    actualizarBotonAgregar();
                }

                function resetCaracteristicas() {
                    if (!CONTENEDOR) return;
                    CONTENEDOR.innerHTML = '';
                    agregarCaracteristica({}, false);
                }

                function recolectarCaracteristicas() {
                    const items = [];
                    CONTENEDOR.querySelectorAll('.caracteristica-item').forEach(function (item) {
                        const nombre = item.querySelector('.carac-nombre').value.trim();
                        const tipo = item.querySelector('.carac-tipo').value;
                        const maxInput = item.querySelector('.carac-max');
                        items.push({
                            nombre: nombre,
                            tipo: tipo,
                            max: tipo === 'string' ? (parseInt(maxInput.value, 10) || 255) : null
                        });
                    });
                    return items;
                }

                /* ==================== Validaciones ==================== */
                function validarNombreCategoria() {
                    const valor = space(INPUT_NOMBRE.value).trim();
                    INPUT_NOMBRE.value = valor;
                    if (valor === '') {
                        ERROR_NOMBRE.textContent = '*Este campo es obligatorio*';
                        return false;
                    }
                    if (valor.length < 2) {
                        ERROR_NOMBRE.textContent = '*Mínimo 2 caracteres*';
                        return false;
                    }
                    if (valor.length > 20) {
                        ERROR_NOMBRE.textContent = '*Máximo 20 caracteres*';
                        return false;
                    }
                    if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/.test(valor)) {
                        ERROR_NOMBRE.textContent = '*El formato permite letras y números (2-20)*';
                        return false;
                    }
                    ERROR_NOMBRE.textContent = '';
                    return true;
                }

                function validarCaracteristicas() {
                    let ok = true;
                    let primerError = '';
                    const items = CONTENEDOR.querySelectorAll('.caracteristica-item');

                    items.forEach(function (item) {
                        const inputNombre = item.querySelector('.carac-nombre');
                        const selectTipo = item.querySelector('.carac-tipo');
                        const inputMax = item.querySelector('.carac-max');
                        const nombreVal = inputNombre.value.trim();
                        const tipoVal = selectTipo.value;

                        if (!nombreVal) {
                            if (!primerError) primerError = 'El nombre de la característica está vacío.';
                            inputNombre.style.borderColor = '#e53e3e';
                            ok = false;
                            return;
                        }
                        inputNombre.style.borderColor = '';
                        if (nombreVal.length < 2) {
                            if (!primerError) primerError = 'El nombre de la característica debe tener al menos 2 caracteres.';
                            inputNombre.style.borderColor = '#e53e3e';
                            ok = false;
                            return;
                        }
                        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/.test(nombreVal)) {
                            if (!primerError) primerError = 'El nombre de la característica permite letras y números (2-20).';
                            inputNombre.style.borderColor = '#e53e3e';
                            ok = false;
                            return;
                        }
                        if (!tipoVal) {
                            if (!primerError) primerError = 'Debe seleccionar un tipo para cada característica.';
                            selectTipo.style.borderColor = '#e53e3e';
                            ok = false;
                            return;
                        }
                        selectTipo.style.borderColor = '';
                        if (tipoVal === 'string') {
                            const maxVal = parseInt(inputMax.value, 10);
                            if (isNaN(maxVal) || maxVal <= 0) {
                                if (!primerError) primerError = 'El campo "Máx. caracteres" debe ser un número mayor a 0.';
                                inputMax.style.borderColor = '#e53e3e';
                                ok = false;
                                return;
                            }
                            inputMax.style.borderColor = '';
                        }
                    });

                    if (items.length === 0) {
                        ok = false;
                        primerError = 'Debe agregar al menos una característica.';
                    }

                    ERROR_CARACS.textContent = ok ? '' : (primerError || 'Verifique las características.');
                    return ok;
                }

                /* ==================== Modal registrar/modificar ==================== */
                const categoriaModal = document.getElementById('categoriaModal');
                const searchModal = document.getElementById('searchModal');
                let modoActual = 'registrar';

                function openModal(modo) {
                    modoActual = modo;
                    document.getElementById('accionForm').value = modo;
                    document.getElementById('modalTitle').textContent = modo === 'modificar' ? 'Modificar Categoría' : 'Nueva Categoría';
                    document.getElementById('modalSubtitle').textContent = modo === 'modificar'
                        ? 'Actualice el nombre y las características de la categoría'
                        : 'Complete los datos para registrar una nueva categoría';
                    document.getElementById('id_categoria').value = '';
                    INPUT_NOMBRE.value = '';
                    ERROR_NOMBRE.textContent = '';
                    ERROR_CARACS.textContent = '';
                    resetCaracteristicas();
                    categoriaModal.style.display = 'block';
                }

                function closeModal() {
                    categoriaModal.style.display = 'none';
                }

                function openSearchModal() {
                    searchModal.style.display = 'block';
                }

                function closeSearchModal() {
                    searchModal.style.display = 'none';
                }

                async function abrirEdicion(id, nombre) {
                    openModal('modificar');
                    document.getElementById('id_categoria').value = id;
                    INPUT_NOMBRE.value = nombre || '';

                    // Mostrar loading en el contenedor de características
                    CONTENEDOR.innerHTML = '<div class="caracteristica-loading"><i class="fas fa-spinner fa-spin"></i> Cargando características...</div>';

                    const fd = new FormData();
                    fd.append('accion', 'obtener_categoria');
                    fd.append('id_categoria', id);

                    try {
                        const data = await postAccion(fd);
                        CONTENEDOR.innerHTML = '';

                        if (data && data.status !== 'error' && data.id_categoria) {
                            INPUT_NOMBRE.value = data.nombre_categoria || nombre || '';
                            const caracs = Array.isArray(data.caracteristicas) ? data.caracteristicas : [];
                            if (caracs.length > 0) {
                                caracs.forEach(function (carac, index) {
                                    agregarCaracteristica(carac, index > 0);
                                });
                            } else {
                                agregarCaracteristica({}, false);
                            }
                        } else {
                            agregarCaracteristica({}, false);
                            if (data && data.message) {
                                Swal.fire({ icon: 'warning', title: 'Advertencia', text: data.message });
                            }
                        }
                    } catch (e) {
                        CONTENEDOR.innerHTML = '';
                        agregarCaracteristica({}, false);
                        console.error(e);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener la categoría.' });
                    }
                    actualizarBotonAgregar();
                }

                function construirFormData() {
                    const fd = new FormData();
                    const idCategoria = document.getElementById('id_categoria').value;
                    const nombre = INPUT_NOMBRE.value.trim();

                    if (modoActual === 'modificar') {
                        fd.append('accion', 'modificar');
                        fd.append('id_categoria', idCategoria);
                        fd.append('nombre_categoria', nombre);
                        // El backend de modificar espera las características como JSON
                        fd.append('caracteristicas', JSON.stringify(recolectarCaracteristicas()));
                    } else {
                        fd.append('accion', 'registrar');
                        fd.append('nombre_categoria', nombre);
                        // El backend de registrar espera características como array PHP
                        recolectarCaracteristicas().forEach(function (carac, i) {
                            fd.append('caracteristicas[' + i + '][nombre]', carac.nombre);
                            fd.append('caracteristicas[' + i + '][tipo]', carac.tipo);
                            if (carac.tipo === 'string') {
                                fd.append('caracteristicas[' + i + '][max]', carac.max);
                            }
                        });
                    }
                    return fd;
                }

                async function saveCategoria() {
                    const okNombre = validarNombreCategoria();
                    const okCaracs = validarCaracteristicas();
                    if (!okNombre || !okCaracs) {
                        Swal.fire({ icon: 'warning', title: 'Verifique los datos', text: 'Revise los campos marcados en el formulario.' });
                        return;
                    }

                    const btnSave = document.getElementById('btn-save-categoria');
                    btnSave.disabled = true;

                    try {
                        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        const data = await postAccion(construirFormData());
                        Swal.close();

                        if (data && data.status === 'success') {
                            const categoria = data.categoria;
                            if (modoActual === 'modificar' && categoria) {
                                actualizarFila(categoria);
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'La categoría se ha actualizado correctamente' });
                            } else if (categoria) {
                                agregarFila(categoria);
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message || 'Categoría registrada correctamente' });
                            } else {
                                // Fallback: recargar datos si el backend no devolvió la categoría
                                location.reload();
                                return;
                            }
                            closeModal();
                        } else {
                            // Mostrar errores de campo específicos si existen
                            const errores = data && (data.errors || data.field_errors);
                            if (errores) {
                                if (errores.nombre_categoria) ERROR_NOMBRE.textContent = '*' + errores.nombre_categoria + '*';
                                if (errores.id_categoria) ERROR_NOMBRE.textContent = '*' + errores.id_categoria + '*';
                                const msgsCarac = Object.keys(errores)
                                    .filter(function (k) { return k.indexOf('caracteristica') === 0; })
                                    .map(function (k) { return errores[k]; });
                                if (msgsCarac.length) ERROR_CARACS.textContent = '*' + msgsCarac.join(' ') + '*';
                                if (errores.integridad) ERROR_CARACS.textContent = '*' + errores.integridad + '*';
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (data && data.message) || 'No se pudo guardar la categoría'
                            });
                        }
                    } catch (e) {
                        Swal.close();
                        console.error(e);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar la categoría. Intente nuevamente.' });
                    } finally {
                        btnSave.disabled = false;
                    }
                }

                /* ==================== Buscar ==================== */
                function searchCategoria() {
                    const termino = document.getElementById('searchNombre').value.trim();
                    const dt = obtenerTabla();
                    if (dt) {
                        dt.search(termino).draw();
                    } else {
                        const filas = document.querySelectorAll('#tablaCategorias tbody tr[data-id]');
                        filas.forEach(function (tr) {
                            const nombre = tr.querySelector('td:nth-child(2)') ? tr.querySelector('td:nth-child(2)').textContent.toLowerCase() : '';
                            tr.style.display = termino === '' || nombre.includes(termino.toLowerCase()) ? '' : 'none';
                        });
                    }
                    closeSearchModal();
                }

                /* ==================== Eventos ==================== */
                document.getElementById('btn-add-categoria')?.addEventListener('click', function () {
                    openModal('registrar');
                });

                document.getElementById('btn-filter')?.addEventListener('click', openSearchModal);
                document.getElementById('btn-do-search')?.addEventListener('click', searchCategoria);
                document.getElementById('btn-save-categoria')?.addEventListener('click', saveCategoria);
                document.getElementById('btn-agregar-caracteristica')?.addEventListener('click', function () {
                    agregarCaracteristica({}, true);
                });

                INPUT_NOMBRE?.addEventListener('keypress', function (e) {
                    if (!REGEX_NOMBRE.test(e.key)) e.preventDefault();
                });
                INPUT_NOMBRE?.addEventListener('input', function () {
                    this.value = space(this.value);
                    ERROR_NOMBRE.textContent = '';
                });
                INPUT_NOMBRE?.addEventListener('blur', validarNombreCategoria);

                document.getElementById('categoriaForm')?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    saveCategoria();
                });
                document.getElementById('searchForm')?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    searchCategoria();
                });

                // Delegación para editar/eliminar (funciona con DataTables y DOM plano)
                document.querySelector('#tablaCategorias tbody')?.addEventListener('click', async function (e) {
                    const btnEdit = e.target.closest('.btn-edit');
                    const btnDelete = e.target.closest('.btn-delete');

                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        const nombre = btnEdit.getAttribute('data-nombre');
                        abrirEdicion(id, nombre);
                        return;
                    }

                    if (btnDelete) {
                        const id = btnDelete.getAttribute('data-id');
                        const nombre = btnDelete.getAttribute('data-nombre') || '';
                        const result = await Swal.fire({
                            title: '¿Está seguro?',
                            html: 'Se eliminará la categoría <strong>' + esc(nombre) + '</strong> y su tabla de características asociada. ¡No podrás revertir esto!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e53e3e',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Sí, eliminarla',
                            cancelButtonText: 'Cancelar'
                        });
                        if (!result.isConfirmed) return;

                        const fd = new FormData();
                        fd.append('accion', 'eliminar');
                        fd.append('id_categoria', id);

                        try {
                            Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const data = await postAccion(fd);
                            Swal.close();

                            if (data && data.status === 'success') {
                                eliminarFila(id);
                                Swal.fire({ icon: 'success', title: 'Eliminada', text: 'La categoría y su tabla de características han sido eliminadas.' });
                            } else {
                                let mensaje = (data && data.message) || 'Error al eliminar la categoría';
                                // El backend devuelve errores de validación/integridad en 'errors'
                                const errores = data && (data.errors || data.field_errors);
                                if (errores) {
                                    if (errores.integridad) mensaje = errores.integridad;
                                    else {
                                        const extra = Object.values(errores).join(' ');
                                        if (extra) mensaje = extra;
                                    }
                                }
                                Swal.fire({ icon: 'error', title: 'No se puede eliminar', text: mensaje });
                            }
                        } catch (err) {
                            Swal.close();
                            console.error(err);
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al eliminar la categoría' });
                        }
                    }
                });

                categoriaModal?.addEventListener('click', function (e) {
                    if (e.target === categoriaModal) closeModal();
                });
                searchModal?.addEventListener('click', function (e) {
                    if (e.target === searchModal) closeSearchModal();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { closeModal(); closeSearchModal(); }
                });

                /* ==================== Permisos en tiempo real ==================== */
                async function verificarPermisos() {
                    const fd = new FormData();
                    fd.append('accion', 'permisos_tiempo_real');
                    try {
                        const permisos = await postAccion(fd);
                        const contenedor = document.getElementById('contenedor-categorias');
                        const msg = document.getElementById('mensaje-permiso');
                        const btnAdd = document.getElementById('btn-add-categoria');
                        const btnFilter = document.getElementById('btn-filter');
                        if (!permisos || !permisos.consultar) {
                            if (contenedor) contenedor.style.display = 'none';
                            if (msg) { msg.style.display = 'block'; msg.textContent = 'No tiene permiso para consultar los registros.'; }
                            return;
                        }
                        if (contenedor) contenedor.style.display = '';
                        if (msg) msg.style.display = 'none';
                        if (btnAdd) btnAdd.style.display = permisos.incluir ? '' : 'none';
                        if (btnFilter) btnFilter.style.display = '';
                        document.querySelectorAll('#tablaCategorias .btn-edit').forEach(function (b) {
                            b.style.display = permisos.modificar ? '' : 'none';
                        });
                        document.querySelectorAll('#tablaCategorias .btn-delete').forEach(function (b) {
                            b.style.display = permisos.eliminar ? '' : 'none';
                        });
                    } catch (e) {
                        console.error('Error verificando permisos:', e);
                    }
                }

                verificarPermisos();
                setInterval(verificarPermisos, 10000);

                /* ==================== Modal de ayuda ==================== */
                let modalAyudaInstance = null;

                function cargarRecursoAyuda(tipo, url) {
                    return new Promise((resolve, reject) => {
                        if (tipo === 'css' && document.querySelector('link[href*="ayuda/css/modal.css"]')) return resolve();
                        if (tipo === 'js' && document.querySelector('script[src*="ayuda/js/modal.js"]')) return resolve();
                        const el = document.createElement(tipo === 'css' ? 'link' : 'script');
                        if (tipo === 'css') { el.rel = 'stylesheet'; el.href = url; }
                        else { el.src = url; }
                        el.onload = resolve;
                        el.onerror = reject;
                        document.head.appendChild(el);
                    });
                }

                async function abrirModalAyuda(contexto) {
                    try {
                        await cargarRecursoAyuda('css', 'assets/public/ayuda/css/modal.css');

                        if (!document.getElementById('modalAyuda')) {
                            const html = await (await fetch('assets/public/ayuda/categorias.php')).text();
                            document.body.insertAdjacentHTML('beforeend', html);
                        }

                        await cargarRecursoAyuda('js', 'assets/public/ayuda/js/modal.js');

                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
                            document.body.style.overflow = '';
                            if (contexto) {
                                const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    setTimeout(() => modalAyudaInstance.goToSlide(slideIndex), 300);
                                }
                            }
                            modalAyudaInstance.openModal();
                        } else {
                            console.error('La función inicializarModalAyudaUsuario no está disponible');
                        }
                    } catch (err) {
                        console.error('Error al cargar el modal de ayuda:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el contenido de ayuda' });
                    }
                }

                document.getElementById('btn-ayuda-categoria')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    abrirModalAyuda();
                });
            })();
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>
