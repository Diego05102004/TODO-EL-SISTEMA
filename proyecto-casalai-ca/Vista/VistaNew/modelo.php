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
$titulo_pagina = 'Gestión de Modelos';

// Datos reales para las tarjetas
$modelosArray = $modelos ?? [];
$marcasArray = $marcas ?? [];
$totalModelos = count($modelosArray);
$totalMarcas = count($marcasArray);
$modelosPorId = $modelosArray;
usort($modelosPorId, function ($a, $b) {
    return ((int)($a['id_modelo'] ?? 0)) <=> ((int)($b['id_modelo'] ?? 0));
});
$ultimoModelo = $modelosPorId[$totalModelos - 1] ?? null;
$promedioPorMarca = $totalMarcas > 0 ? round($totalModelos / $totalMarcas, 1) : 0;

// Mapa id_marca => nombre_marca para resolver el nombre al registrar
$marcasMap = [];
foreach ($marcasArray as $m) {
    $marcasMap[(int)$m['id_marca']] = $m['nombre_marca'] ?? '';
}

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Modelos -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-cube"></i></div>
                    <div class="card-content">
                        <h3>Total Modelos</h3>
                        <p class="card-value" id="modelo-count-total"><?php echo $totalModelos; ?></p>
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
                    <div class="card-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="card-content">
                        <h3>Marcas</h3>
                        <p class="card-value" id="modelo-count-marcas"><?php echo $totalMarcas; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalMarcas > 0 ? round(100 / max($totalMarcas, 1), 1) : 0; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalMarcas > 0 ? round(100 / $totalMarcas) . '%' : '0%'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Último Registrado</h3>
                        <p class="card-value" id="modelo-ultimo" style="font-size: 1.1rem;"><?php echo $ultimoModelo ? htmlspecialchars($ultimoModelo['nombre_modelo'] ?? 'N/A') : 'N/A'; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalModelos > 0 ? round(100 / $totalModelos, 1) : 0; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalModelos > 0 ? round(100 / $totalModelos) . '%' : '0%'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modelos Section -->
            <div class="modelos-section" id="contenedor-modelos">
                <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>
                <div class="section-header">
                    <h2>Gestión de Modelos</h2>
                    <div class="section-actions">
                        <button class="btn-add-modelo" id="btn-add-modelo">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Nuevo Modelo
                        </button>
                        <button class="btn-filter" id="btn-filter">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Buscar
                        </button>
                    </div>
                </div>

                <!-- Tabla de Modelos -->
                <div class="table-container">
                    <table class="modelos-table" id="tablaModelos" data-datatable data-page-length="15" data-datatable-order="[[0,\"desc\"]]" data-datatable-no-order="3" data-datatable-no-search="3">
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
                                    <tr data-id="<?php echo (int)$modelo['id_modelo']; ?>">
                                        <td><?php echo (int)$modelo['id_modelo']; ?></td>
                                        <td><?php echo htmlspecialchars($modelo['nombre_marca'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($modelo['nombre_modelo'] ?? 'N/A'); ?></td>
                                        <td>
                                            <button class="btn-action btn-edit" title="Modificar Modelo"
                                                data-id="<?php echo (int)$modelo['id_modelo']; ?>"
                                                data-marca="<?php echo (int)($modelo['id_marca'] ?? 0); ?>"
                                                data-marca-nombre="<?php echo htmlspecialchars($modelo['nombre_marca'] ?? '', ENT_QUOTES); ?>"
                                                data-nombre="<?php echo htmlspecialchars($modelo['nombre_modelo'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" title="Eliminar Modelo"
                                                data-id="<?php echo (int)$modelo['id_modelo']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($modelo['nombre_modelo'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-cube" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay modelos registrados</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Modelo -->
            <div id="modeloModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-cube"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Nuevo Modelo</h2>
                                <p id="modalSubtitle">Complete los datos para registrar un nuevo modelo</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="modeloForm">
                            <input type="hidden" name="accion" id="accionForm" value="registrar">
                            <input type="hidden" name="id_modelo" id="id_modelo">

                            <div class="form-group">
                                <label for="id_marca">
                                    <i class="fas fa-trademark"></i>
                                    Marca*
                                </label>
                                <select id="id_marca" name="id_marca" required>
                                    <option value="">Seleccione una marca</option>
                                    <?php if (!empty($marcas)): ?>
                                        <?php foreach ($marcas as $marca): ?>
                                            <option value="<?php echo (int)$marca['id_marca']; ?>">
                                                <?php echo htmlspecialchars($marca['nombre_marca']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <span class="span-error" id="error-id-marca"></span>
                            </div>

                            <div class="form-group">
                                <label for="nombre_modelo">
                                    <i class="fas fa-tag"></i>
                                    Nombre del Modelo*
                                </label>
                                <input type="text" id="nombre_modelo" name="nombre_modelo" required
                                       placeholder="Ej: iPhone 15, Galaxy S24" maxlength="25" class="input-with-icon">
                                <i class="fas fa-cube input-icon"></i>
                                <span class="span-error" id="error-nombre-modelo"></span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" id="btn-save-modelo">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para Buscar Modelo -->
            <div id="searchModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2>Buscar Modelo</h2>
                                <p>Filtre los modelos por nombre o marca</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeSearchModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="searchForm">
                            <div class="form-group">
                                <label for="searchNombre">
                                    <i class="fas fa-tag"></i>
                                    Nombre del Modelo
                                </label>
                                <input type="text" id="searchNombre" name="searchNombre"
                                       placeholder="Ingrese el nombre del modelo">
                            </div>

                            <div class="form-group">
                                <label for="searchMarca">
                                    <i class="fas fa-trademark"></i>
                                    Marca
                                </label>
                                <select id="searchMarca" name="searchMarca">
                                    <option value="">Todas</option>
                                    <?php if (!empty($marcas)): ?>
                                        <?php foreach ($marcas as $marca): ?>
                                            <option value="<?php echo htmlspecialchars($marca['nombre_marca']); ?>">
                                                <?php echo htmlspecialchars($marca['nombre_marca']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
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

            <button class="btn-ayuda" id="btn-ayuda-modelo" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <style>
                /* Estilos específicos de Modelos */
                .modelos-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .modelos-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .modelos-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .modelos-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .modelos-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .modelos-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .btn-add-modelo, .btn-filter {
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

                .btn-add-modelo {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-add-modelo:hover, .btn-filter:hover {
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
                    max-width: 500px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                    animation: slideDown 0.3s;
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

                /* Responsive */
                @media (max-width: 768px) {
                    .modelos-table {
                        font-size: 0.8rem;
                    }

                    .modelos-table th,
                    .modelos-table td {
                        padding: 10px;
                    }
                }
            </style>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=modelo';
                const INPUT_NOMBRE = document.getElementById('nombre_modelo');
                const SELECT_MARCA = document.getElementById('id_marca');
                const ERROR_NOMBRE = document.getElementById('error-nombre-modelo');
                const ERROR_MARCA = document.getElementById('error-id-marca');
                const REGEX_NOMBRE = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\-\.\&\']*$/;
                const MARCAS_MAP = <?php echo json_encode($marcasMap, JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}'; ?>;

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

                function mostrarError(campo, mensaje) {
                    const el = campo === 'marca' ? ERROR_MARCA : ERROR_NOMBRE;
                    if (el) el.textContent = mensaje || '';
                }

                function obtenerTabla() {
                    const tabla = document.getElementById('tablaModelos');
                    if (!tabla) return null;
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#tablaModelos')) {
                        return jQuery('#tablaModelos').DataTable();
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
                    return document.querySelector('#tablaModelos tbody tr[data-id="' + id + '"]');
                }

                function nombreMarcaPorId(idMarca) {
                    return MARCAS_MAP[String(parseInt(idMarca, 10))] || '';
                }

                function buildRowData(modelo) {
                    const id = parseInt(modelo.id_modelo, 10);
                    const marcaNombre = esc(modelo.nombre_marca || nombreMarcaPorId(modelo.id_marca) || 'N/A');
                    const nombre = esc(modelo.nombre_modelo);
                    const marcaId = parseInt(modelo.id_marca, 10) || 0;
                    return [
                        String(id),
                        marcaNombre,
                        nombre,
                        '<button class="btn-action btn-edit" title="Modificar Modelo" data-id="' + id + '" data-marca="' + marcaId + '" data-marca-nombre="' + marcaNombre + '" data-nombre="' + nombre + '"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn-action btn-delete" title="Eliminar Modelo" data-id="' + id + '" data-nombre="' + nombre + '"><i class="fas fa-trash"></i></button>'
                    ];
                }

                function agregarFila(modelo) {
                    const dt = obtenerTabla();
                    if (dt) {
                        const row = dt.row.add(buildRowData(modelo));
                        const nodo = row.node();
                        if (nodo) nodo.setAttribute('data-id', modelo.id_modelo);
                        row.draw(false);
                    } else {
                        const tbody = document.querySelector('#tablaModelos tbody');
                        if (!tbody) return;
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', modelo.id_modelo);
                        buildRowData(modelo).forEach(function (html) {
                            const td = document.createElement('td');
                            td.innerHTML = html;
                            tr.appendChild(td);
                        });
                        tbody.insertBefore(tr, tbody.firstChild);
                    }
                    actualizarContadores();
                }

                function actualizarFila(modelo) {
                    const tr = encontrarTr(modelo.id_modelo);
                    const dt = obtenerTabla();
                    if (dt && tr) {
                        const fila = dt.row(tr);
                        if (fila.length) {
                            fila.data(buildRowData(modelo)).draw(false);
                            actualizarContadores();
                            return;
                        }
                    }
                    if (tr) {
                        const tds = tr.querySelectorAll('td');
                        buildRowData(modelo).forEach(function (html, i) {
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
                    const filas = document.querySelectorAll('#tablaModelos tbody tr[data-id]');
                    const total = filas.length;
                    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                    set('modelo-count-total', total);

                    // Marcas únicas presentes en la tabla
                    const marcasUnicas = new Set();
                    let ultimo = null;
                    filas.forEach(function (tr) {
                        const id = parseInt(tr.getAttribute('data-id'), 10);
                        const marcaNombre = tr.querySelector('td:nth-child(2)') ? tr.querySelector('td:nth-child(2)').textContent.trim() : '';
                        const nombre = tr.querySelector('td:nth-child(3)') ? tr.querySelector('td:nth-child(3)').textContent.trim() : '';
                        if (marcaNombre) marcasUnicas.add(marcaNombre);
                        if (!ultimo || id > ultimo.id) ultimo = { id: id, nombre: nombre };
                    });
                    set('modelo-count-marcas', marcasUnicas.size);
                    set('modelo-ultimo', ultimo ? ultimo.nombre : 'N/A');

                    const totalMarcasCard = parseInt(document.getElementById('modelo-count-marcas')?.textContent || '0', 10);
                    document.querySelectorAll('#tablaModelos .progress-circle .circle').forEach(function (circle, idx) {
                        if (idx === 0) return;
                        const base = idx === 1 ? totalMarcasCard : total;
                        const pct = base > 0 ? Math.round(100 / base) : 0;
                        circle.setAttribute('stroke-dasharray', pct + ', 100');
                        const label = circle.parentElement.querySelector('.percentage');
                        if (label) label.textContent = pct + '%';
                    });
                }

                /* ==================== Modal registrar/modificar ==================== */
                const modeloModal = document.getElementById('modeloModal');
                const searchModal = document.getElementById('searchModal');

                function openModal(modo, modelo) {
                    document.getElementById('accionForm').value = modo === 'modificar' ? 'modificar' : 'registrar';
                    document.getElementById('modalTitle').textContent = modo === 'modificar' ? 'Modificar Modelo' : 'Nuevo Modelo';
                    document.getElementById('modalSubtitle').textContent = modo === 'modificar'
                        ? 'Actualice los datos del modelo'
                        : 'Complete los datos para registrar un nuevo modelo';
                    document.getElementById('id_modelo').value = modelo ? modelo.id : '';
                    SELECT_MARCA.value = modelo ? modelo.marca : '';
                    INPUT_NOMBRE.value = modelo ? modelo.nombre : '';
                    mostrarError('marca', '');
                    mostrarError('nombre', '');
                    modeloModal.style.display = 'block';
                }

                function closeModal() {
                    modeloModal.style.display = 'none';
                }

                function openSearchModal() {
                    searchModal.style.display = 'block';
                }

                function closeSearchModal() {
                    searchModal.style.display = 'none';
                }

                function validarFormulario() {
                    let valido = true;
                    const marca = SELECT_MARCA.value;
                    const nombre = space(INPUT_NOMBRE.value).trim();
                    INPUT_NOMBRE.value = nombre;

                    if (!marca) {
                        mostrarError('marca', '*Debe seleccionar una marca*');
                        valido = false;
                    } else {
                        mostrarError('marca', '');
                    }

                    if (nombre === '') {
                        mostrarError('nombre', '*Este campo es obligatorio*');
                        valido = false;
                    } else if (nombre.length < 2) {
                        mostrarError('nombre', '*Mínimo 2 caracteres*');
                        valido = false;
                    } else if (nombre.length > 25) {
                        mostrarError('nombre', '*Máximo 25 caracteres*');
                        valido = false;
                    } else if (!REGEX_NOMBRE.test(nombre)) {
                        mostrarError('nombre', '*El formato permite letras, números, espacios y caracteres especiales comunes*');
                        valido = false;
                    } else {
                        mostrarError('nombre', '');
                    }

                    return valido;
                }

                async function saveModelo() {
                    if (!validarFormulario()) {
                        Swal.fire({ icon: 'warning', title: 'Verifique los datos', text: 'Revise los campos del formulario.' });
                        return;
                    }

                    const form = document.getElementById('modeloForm');
                    const formData = new FormData(form);
                    const accion = formData.get('accion');
                    const btnSave = document.getElementById('btn-save-modelo');
                    btnSave.disabled = true;

                    try {
                        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        const data = await postAccion(formData);
                        Swal.close();

                        if (data && data.status === 'success') {
                            if (accion === 'modificar' && data.modelo) {
                                actualizarFila(data.modelo);
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message || 'Modelo modificado correctamente' });
                            } else {
                                agregarFila(data.modelo);
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message || 'Modelo registrado correctamente' });
                            }
                            closeModal();
                        } else {
                            const errores = data && data.field_errors ? Object.values(data.field_errors).join(' ') : '';
                            mostrarError('nombre', errores || (data && data.message) || 'Error al guardar el modelo');
                            Swal.fire({ icon: 'error', title: 'Error', text: (data && data.message) || 'No se pudo guardar el modelo' });
                        }
                    } catch (e) {
                        Swal.close();
                        console.error(e);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar modelo. Intente nuevamente.' });
                    } finally {
                        btnSave.disabled = false;
                    }
                }

                /* ==================== Buscar ==================== */
                function searchModelo() {
                    const terminoNombre = document.getElementById('searchNombre').value.trim();
                    const terminoMarca = document.getElementById('searchMarca').value;
                    const dt = obtenerTabla();
                    if (dt) {
                        // Columna 1 = Marca, columna 2 = Modelo
                        dt.column(1).search(terminoMarca).column(2).search(terminoNombre).draw();
                    } else {
                        const filas = document.querySelectorAll('#tablaModelos tbody tr[data-id]');
                        filas.forEach(function (tr) {
                            const marca = tr.querySelector('td:nth-child(2)') ? tr.querySelector('td:nth-child(2)').textContent.toLowerCase() : '';
                            const nombre = tr.querySelector('td:nth-child(3)') ? tr.querySelector('td:nth-child(3)').textContent.toLowerCase() : '';
                            const okMarca = terminoMarca === '' || marca === terminoMarca.toLowerCase();
                            const okNombre = terminoNombre === '' || nombre.includes(terminoNombre.toLowerCase());
                            tr.style.display = (okMarca && okNombre) ? '' : 'none';
                        });
                    }
                    closeSearchModal();
                }

                /* ==================== Eventos ==================== */
                document.getElementById('btn-add-modelo')?.addEventListener('click', function () {
                    openModal('registrar');
                });

                document.getElementById('btn-filter')?.addEventListener('click', openSearchModal);
                document.getElementById('btn-do-search')?.addEventListener('click', searchModelo);
                document.getElementById('btn-save-modelo')?.addEventListener('click', saveModelo);

                INPUT_NOMBRE?.addEventListener('keypress', function (e) {
                    if (!REGEX_NOMBRE.test(e.key)) e.preventDefault();
                });
                INPUT_NOMBRE?.addEventListener('input', function () {
                    this.value = space(this.value);
                    mostrarError('nombre', '');
                });
                INPUT_NOMBRE?.addEventListener('blur', function () { validarFormulario(); });
                SELECT_MARCA?.addEventListener('change', function () { mostrarError('marca', ''); });

                document.getElementById('modeloForm')?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    saveModelo();
                });
                document.getElementById('searchForm')?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    searchModelo();
                });

                // Delegación para editar/eliminar (funciona con DataTables y DOM plano)
                document.querySelector('#tablaModelos tbody')?.addEventListener('click', async function (e) {
                    const btnEdit = e.target.closest('.btn-edit');
                    const btnDelete = e.target.closest('.btn-delete');

                    if (btnEdit) {
                        const modelo = {
                            id: btnEdit.getAttribute('data-id'),
                            marca: btnEdit.getAttribute('data-marca'),
                            nombre: btnEdit.getAttribute('data-nombre')
                        };
                        openModal('modificar', modelo);
                        return;
                    }

                    if (btnDelete) {
                        const id = btnDelete.getAttribute('data-id');
                        const nombre = btnDelete.getAttribute('data-nombre') || '';
                        const result = await Swal.fire({
                            title: '¿Está seguro?',
                            html: 'Se eliminará el modelo <strong>' + esc(nombre) + '</strong>. ¡No podrás revertir esto!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e53e3e',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Sí, eliminarlo',
                            cancelButtonText: 'Cancelar'
                        });
                        if (!result.isConfirmed) return;

                        const fd = new FormData();
                        fd.append('accion', 'eliminar');
                        fd.append('id_modelo', id);

                        try {
                            Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const data = await postAccion(fd);
                            Swal.close();

                            if (data && data.status === 'success') {
                                eliminarFila(id);
                                Swal.fire({ icon: 'success', title: 'Eliminado', text: 'El modelo ha sido eliminado.' });
                            } else {
                                let mensaje = (data && data.message) || 'Error al eliminar el modelo';
                                if (data && data.field_errors) {
                                    const extra = Object.values(data.field_errors).join(' ');
                                    if (extra) mensaje = extra;
                                }
                                Swal.fire({ icon: 'error', title: 'No se puede eliminar', text: mensaje });
                            }
                        } catch (err) {
                            Swal.close();
                            console.error(err);
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al eliminar el modelo' });
                        }
                    }
                });

                modeloModal?.addEventListener('click', function (e) {
                    if (e.target === modeloModal) closeModal();
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
                        const contenedor = document.getElementById('contenedor-modelos');
                        const msg = document.getElementById('mensaje-permiso');
                        const btnAdd = document.getElementById('btn-add-modelo');
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
                        document.querySelectorAll('#tablaModelos .btn-edit').forEach(function (b) {
                            b.style.display = permisos.modificar ? '' : 'none';
                        });
                        document.querySelectorAll('#tablaModelos .btn-delete').forEach(function (b) {
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
                            const html = await (await fetch('assets/public/ayuda/modelo.php')).text();
                            document.body.insertAdjacentHTML('beforeend', html);
                        }

                        await cargarRecursoAyuda('js', 'assets/public/ayuda/js/modal.js');

                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
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

                document.getElementById('btn-ayuda-modelo')?.addEventListener('click', function (e) {
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
