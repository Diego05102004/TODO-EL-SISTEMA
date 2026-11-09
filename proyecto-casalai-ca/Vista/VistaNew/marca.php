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
$titulo_pagina = 'Gestión de Marcas';

// Datos reales para las tarjetas
$marcasArray = $marcas ?? [];
$totalMarcas = count($marcasArray);
$marcasPorId = $marcasArray;
usort($marcasPorId, function ($a, $b) {
    return ((int)($a['id_marca'] ?? 0)) <=> ((int)($b['id_marca'] ?? 0));
});
$primeraMarca = $marcasPorId[0] ?? null;
$ultimaMarca = $marcasPorId[$totalMarcas - 1] ?? null;

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Marcas -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-trademark"></i></div>
                    <div class="card-content">
                        <h3>Total Marcas</h3>
                        <p class="card-value" id="marca-count-total"><?php echo $totalMarcas; ?></p>
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
                        <p class="card-value" id="marca-primera" style="font-size: 1.1rem;"><?php echo $primeraMarca ? htmlspecialchars($primeraMarca['nombre_marca']) : 'N/A'; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalMarcas > 0 ? round(100 / $totalMarcas, 1) : 0; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalMarcas > 0 ? round(100 / $totalMarcas) . '%' : '0%'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-content">
                        <h3>Última Registrada</h3>
                        <p class="card-value" id="marca-ultima" style="font-size: 1.1rem;"><?php echo $ultimaMarca ? htmlspecialchars($ultimaMarca['nombre_marca']) : 'N/A'; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $totalMarcas > 0 ? round(100 / $totalMarcas, 1) : 0; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $totalMarcas > 0 ? round(100 / $totalMarcas) . '%' : '0%'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marcas Section -->
            <div class="marcas-section" id="contenedor-marcas">
                <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>
                <div class="section-header">
                    <h2>Gestión de Marcas</h2>
                    <div class="section-actions">
                        <button class="btn-add-marca" id="btn-add-marca">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Nueva Marca
                        </button>
                        <button class="btn-filter" id="btn-filter">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Buscar
                        </button>
                    </div>
                </div>

                <!-- Tabla de Marcas -->
                <div class="table-container">
                    <table class="marcas-table" id="tablaMarcas" data-datatable data-page-length="15" data-datatable-order="[[0,\"desc\"]]" data-datatable-no-order="2" data-datatable-no-search="2">
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
                                        <td><?php echo (int)$marca['id_marca']; ?></td>
                                        <td><?php echo htmlspecialchars($marca['nombre_marca'] ?? 'N/A'); ?></td>
                                        <td>
                                            <button class="btn-action btn-edit" title="Modificar Marca"
                                                data-id="<?php echo (int)$marca['id_marca']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($marca['nombre_marca'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" title="Eliminar Marca"
                                                data-id="<?php echo (int)$marca['id_marca']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($marca['nombre_marca'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-trademark" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay marcas registradas</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Marca -->
            <div id="marcaModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-trademark"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Nueva Marca</h2>
                                <p id="modalSubtitle">Complete los datos para registrar una nueva marca</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="marcaForm">
                            <input type="hidden" name="accion" id="accionForm" value="registrar">
                            <input type="hidden" name="id_marca" id="id_marca">

                            <div class="form-group">
                                <label for="nombre_marca">
                                    <i class="fas fa-tag"></i>
                                    Nombre de la Marca*
                                </label>
                                <input type="text" id="nombre_marca" name="nombre_marca" required
                                       placeholder="Ej: Apple, Samsung, Sony" maxlength="25" class="input-with-icon">
                                <i class="fas fa-trademark input-icon"></i>
                                <span class="span-error" id="error-nombre-marca"></span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" id="btn-save-marca">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para Buscar Marca -->
            <div id="searchModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2>Buscar Marca</h2>
                                <p>Filtre las marcas por nombre</p>
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
                                       placeholder="Ingrese el nombre de la marca">
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

            <button class="btn-ayuda" id="btn-ayuda-marca" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <style>
                /* Estilos específicos de Marcas */
                .marcas-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .marcas-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .marcas-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .marcas-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .marcas-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .marcas-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .btn-add-marca, .btn-filter {
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

                .btn-add-marca {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-add-marca:hover, .btn-filter:hover {
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
                    .marcas-table {
                        font-size: 0.8rem;
                    }

                    .marcas-table th,
                    .marcas-table td {
                        padding: 10px;
                    }
                }
            </style>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=marca';
                const INPUT_NOMBRE = document.getElementById('nombre_marca');
                const ERROR_NOMBRE = document.getElementById('error-nombre-marca');
                const REGEX_NOMBRE = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\-\.\&\']*$/;

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

                function mostrarErrorCampo(mensaje) {
                    if (ERROR_NOMBRE) ERROR_NOMBRE.textContent = mensaje || '';
                }

                function obtenerTabla() {
                    const tabla = document.getElementById('tablaMarcas');
                    if (!tabla) return null;
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#tablaMarcas')) {
                        return jQuery('#tablaMarcas').DataTable();
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
                    return document.querySelector('#tablaMarcas tbody tr[data-id="' + id + '"]');
                }

                function buildRowData(marca) {
                    const id = parseInt(marca.id_marca, 10);
                    const nombre = esc(marca.nombre_marca);
                    return [
                        String(id),
                        nombre,
                        '<button class="btn-action btn-edit" title="Modificar Marca" data-id="' + id + '" data-nombre="' + nombre + '"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn-action btn-delete" title="Eliminar Marca" data-id="' + id + '" data-nombre="' + nombre + '"><i class="fas fa-trash"></i></button>'
                    ];
                }

                function agregarFila(marca) {
                    const dt = obtenerTabla();
                    if (dt) {
                        const row = dt.row.add(buildRowData(marca));
                        const nodo = row.node();
                        if (nodo) nodo.setAttribute('data-id', marca.id_marca);
                        row.draw(false);
                    } else {
                        const tbody = document.querySelector('#tablaMarcas tbody');
                        if (!tbody) return;
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', marca.id_marca);
                        buildRowData(marca).forEach(function (html) {
                            const td = document.createElement('td');
                            td.innerHTML = html;
                            tr.appendChild(td);
                        });
                        tbody.insertBefore(tr, tbody.firstChild);
                    }
                    actualizarContadores();
                }

                function actualizarFila(marca) {
                    const tr = encontrarTr(marca.id_marca);
                    const dt = obtenerTabla();
                    if (dt && tr) {
                        const fila = dt.row(tr);
                        if (fila.length) {
                            fila.data(buildRowData(marca)).draw(false);
                            actualizarContadores();
                            return;
                        }
                    }
                    if (tr) {
                        const tds = tr.querySelectorAll('td');
                        buildRowData(marca).forEach(function (html, i) {
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
                    const filas = document.querySelectorAll('#tablaMarcas tbody tr[data-id]');
                    const total = filas.length;
                    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                    set('marca-count-total', total);

                    let primera = null, ultima = null;
                    filas.forEach(function (tr) {
                        const id = parseInt(tr.getAttribute('data-id'), 10);
                        const nombre = tr.querySelector('td:nth-child(2)') ? tr.querySelector('td:nth-child(2)').textContent.trim() : '';
                        if (!primera || id < primera.id) primera = { id: id, nombre: nombre };
                        if (!ultima || id > ultima.id) ultima = { id: id, nombre: nombre };
                    });
                    set('marca-primera', primera ? primera.nombre : 'N/A');
                    set('marca-ultima', ultima ? ultima.nombre : 'N/A');

                    document.querySelectorAll('#tablaMarcas .progress-circle .circle').forEach(function (circle, idx) {
                        if (idx === 0) return;
                        const pct = total > 0 ? Math.round(100 / total) : 0;
                        circle.setAttribute('stroke-dasharray', pct + ', 100');
                        const label = circle.parentElement.querySelector('.percentage');
                        if (label) label.textContent = pct + '%';
                    });
                }

                /* ==================== Modal registrar/modificar ==================== */
                const marcaModal = document.getElementById('marcaModal');
                const searchModal = document.getElementById('searchModal');

                function openModal(modo, marca) {
                    document.getElementById('accionForm').value = modo === 'modificar' ? 'modificar' : 'registrar';
                    document.getElementById('modalTitle').textContent = modo === 'modificar' ? 'Modificar Marca' : 'Nueva Marca';
                    document.getElementById('modalSubtitle').textContent = modo === 'modificar'
                        ? 'Actualice los datos de la marca'
                        : 'Complete los datos para registrar una nueva marca';
                    document.getElementById('id_marca').value = marca ? marca.id : '';
                    INPUT_NOMBRE.value = marca ? marca.nombre : '';
                    mostrarErrorCampo('');
                    marcaModal.style.display = 'block';
                }

                function closeModal() {
                    marcaModal.style.display = 'none';
                }

                function openSearchModal() {
                    searchModal.style.display = 'block';
                }

                function closeSearchModal() {
                    searchModal.style.display = 'none';
                }

                function validarNombre() {
                    const valor = space(INPUT_NOMBRE.value).trim();
                    INPUT_NOMBRE.value = valor;
                    if (valor === '') {
                        mostrarErrorCampo('*Este campo es obligatorio*');
                        return false;
                    }
                    if (valor.length < 2) {
                        mostrarErrorCampo('*Mínimo 2 caracteres*');
                        return false;
                    }
                    if (valor.length > 25) {
                        mostrarErrorCampo('*Máximo 25 caracteres*');
                        return false;
                    }
                    if (!REGEX_NOMBRE.test(valor)) {
                        mostrarErrorCampo('*El formato permite letras, números, espacios y caracteres especiales comunes*');
                        return false;
                    }
                    mostrarErrorCampo('');
                    return true;
                }

                async function saveMarca() {
                    if (!validarNombre()) {
                        Swal.fire({ icon: 'warning', title: 'Verifique el nombre de la marca', text: 'Revise los datos ingresados.' });
                        return;
                    }

                    const form = document.getElementById('marcaForm');
                    const formData = new FormData(form);
                    const accion = formData.get('accion');
                    const btnSave = document.getElementById('btn-save-marca');
                    btnSave.disabled = true;

                    try {
                        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        const data = await postAccion(formData);
                        Swal.close();

                        if (data && data.status === 'success') {
                            if (accion === 'modificar' && data.marca) {
                                actualizarFila(data.marca);
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'La marca se ha actualizado correctamente' });
                            } else {
                                agregarFila(data.marca);
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message || 'Marca registrada correctamente' });
                            }
                            closeModal();
                        } else {
                            const errores = data && data.field_errors ? Object.values(data.field_errors).join(' ') : '';
                            mostrarErrorCampo(errores || (data && data.message) || 'Error al guardar la marca');
                            Swal.fire({ icon: 'error', title: 'Error', text: (data && data.message) || 'No se pudo guardar la marca' });
                        }
                    } catch (e) {
                        Swal.close();
                        console.error(e);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar marca. Intente nuevamente.' });
                    } finally {
                        btnSave.disabled = false;
                    }
                }

                /* ==================== Buscar ==================== */
                function searchMarca() {
                    const termino = document.getElementById('searchNombre').value.trim();
                    const dt = obtenerTabla();
                    if (dt) {
                        dt.search(termino).draw();
                    } else {
                        const filas = document.querySelectorAll('#tablaMarcas tbody tr[data-id]');
                        filas.forEach(function (tr) {
                            const nombre = tr.querySelector('td:nth-child(2)') ? tr.querySelector('td:nth-child(2)').textContent.toLowerCase() : '';
                            tr.style.display = termino === '' || nombre.includes(termino.toLowerCase()) ? '' : 'none';
                        });
                    }
                    closeSearchModal();
                }

                /* ==================== Eventos ==================== */
                document.getElementById('btn-add-marca')?.addEventListener('click', function () {
                    openModal('registrar');
                });

                document.getElementById('btn-filter')?.addEventListener('click', openSearchModal);
                document.getElementById('btn-do-search')?.addEventListener('click', searchMarca);
                document.getElementById('btn-save-marca')?.addEventListener('click', saveMarca);

                INPUT_NOMBRE?.addEventListener('keypress', function (e) {
                    if (!REGEX_NOMBRE.test(e.key)) e.preventDefault();
                });
                INPUT_NOMBRE?.addEventListener('input', function () {
                    this.value = space(this.value);
                    mostrarErrorCampo('');
                });
                INPUT_NOMBRE?.addEventListener('blur', validarNombre);

                document.getElementById('marcaForm')?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    saveMarca();
                });
                document.getElementById('searchForm')?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    searchMarca();
                });

                // Delegación para editar/eliminar (funciona con DataTables y DOM plano)
                document.querySelector('#tablaMarcas tbody')?.addEventListener('click', async function (e) {
                    const btnEdit = e.target.closest('.btn-edit');
                    const btnDelete = e.target.closest('.btn-delete');

                    if (btnEdit) {
                        const marca = {
                            id: btnEdit.getAttribute('data-id'),
                            nombre: btnEdit.getAttribute('data-nombre')
                        };
                        openModal('modificar', marca);
                        return;
                    }

                    if (btnDelete) {
                        const id = btnDelete.getAttribute('data-id');
                        const nombre = btnDelete.getAttribute('data-nombre') || '';
                        const result = await Swal.fire({
                            title: '¿Está seguro?',
                            html: 'Se eliminará la marca <strong>' + esc(nombre) + '</strong>. ¡No podrás revertir esto!',
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
                        fd.append('id_marca', id);

                        try {
                            Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const data = await postAccion(fd);
                            Swal.close();

                            if (data && data.status === 'success') {
                                eliminarFila(id);
                                Swal.fire({ icon: 'success', title: 'Eliminada', text: 'La marca ha sido eliminada.' });
                            } else {
                                let mensaje = (data && data.message) || 'Error al eliminar la marca';
                                if (data && data.field_errors) {
                                    const extra = Object.values(data.field_errors).join(' ');
                                    if (extra) mensaje = extra;
                                }
                                Swal.fire({ icon: 'error', title: 'No se puede eliminar', text: mensaje });
                            }
                        } catch (err) {
                            Swal.close();
                            console.error(err);
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al eliminar la marca' });
                        }
                    }
                });

                marcaModal?.addEventListener('click', function (e) {
                    if (e.target === marcaModal) closeModal();
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
                        const contenedor = document.getElementById('contenedor-marcas');
                        const msg = document.getElementById('mensaje-permiso');
                        const btnAdd = document.getElementById('btn-add-marca');
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
                        document.querySelectorAll('#tablaMarcas .btn-edit').forEach(function (b) {
                            b.style.display = permisos.modificar ? '' : 'none';
                        });
                        document.querySelectorAll('#tablaMarcas .btn-delete').forEach(function (b) {
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
                            const html = await (await fetch('assets/public/ayuda/marca.php')).text();
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

                document.getElementById('btn-ayuda-marca')?.addEventListener('click', function (e) {
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
