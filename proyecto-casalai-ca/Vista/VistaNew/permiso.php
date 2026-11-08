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
        error_log("Error al generar JWT en permiso: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'permiso';
$titulo_pagina = 'Gestión de Permisos';

// Filtrar roles excluyendo administrador, superusuario y cliente
$excluidos = ['administrador', 'superusuario', 'cliente'];
$rolesSinSuper = array_filter($roles ?? [], function ($rol) use ($excluidos) {
    return !in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'] ?? '')), $excluidos);
});
$rolesSinSuper = array_values($rolesSinSuper);

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Permisos Section -->
            <div class="permisos-section">
                <div class="section-header">
                    <h2><i class="fas fa-shield-alt"></i> Gestión de Permisos por Rol</h2>
                    <div class="section-actions">
                        <button class="btn-save-all" onclick="guardarTodosPermisos()">
                            <span class="btn-icon"><i class="fas fa-save"></i></span>
                            Guardar Cambios
                        </button>
                    </div>
                </div>

                <!-- Selector de Rol -->
                <div class="role-selector">
                    <label for="selectorRol">
                        <i class="fas fa-user-tag"></i>
                        Seleccionar Rol:
                    </label>
                    <select id="selectorRol" class="role-select">
                        <?php foreach ($rolesSinSuper as $i => $rol): ?>
                            <option value="<?= $rol['id_rol'] ?>" <?= $i===0 ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tablas de Permisos -->
                <div class="permisos-tables-container">
                    <?php 
                    $acciones = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
                    foreach ($rolesSinSuper as $i => $rol): ?>
                        <div class="permiso-tabla-rol <?= $i===0 ? 'active' : '' ?>" id="tabla-rol-<?= $rol['id_rol'] ?>">
                            <h3 class="rol-title">
                                <i class="fas fa-user-shield"></i>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </h3>
                            <div class="table-responsive">
                                <table class="permisos-table">
                                    <thead>
                                        <tr>
                                            <th>Módulo</th>
                                            <?php foreach ($acciones as $accion): ?>
                                                <th><?= ucfirst($accion) ?></th>
                                            <?php endforeach; ?>
                                            <th>Todos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modulos_permiso ?? [] as $modulo): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($modulo['nombre_modulo'] ?? '') ?></td>
                                                <?php foreach ($acciones as $accion): ?>
                                                    <td>
                                                        <input type="checkbox" 
                                                               class="permiso-checkbox"
                                                               data-rol="<?= $rol['id_rol'] ?>"
                                                               data-modulo="<?= $modulo['id_modulo'] ?? '' ?>"
                                                               data-accion="<?= $accion ?>"
                                                               <?= isset($permisosUsuario[$rol['id_rol']][$modulo['id_modulo'] ?? []][$accion]) && $permisosUsuario[$rol['id_rol']][$modulo['id_modulo'] ?? []][$accion] ? 'checked' : '' ?>>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <input type="checkbox" 
                                                           class="seleccionar-todos-modulo"
                                                           data-rol="<?= $rol['id_rol'] ?>"
                                                           data-modulo="<?= $modulo['id_modulo'] ?? '' ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <style>
                /* Estilos de Permisos */
                .permisos-section {
                    margin-top: 30px;
                }

                .role-selector {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                    margin-bottom: 30px;
                }

                .role-selector label {
                    display: block;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 10px;
                }

                .role-selector label i {
                    color: #2196F3;
                    margin-right: 8px;
                }

                .role-select {
                    width: 100%;
                    max-width: 400px;
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .role-select:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .btn-save-all {
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
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

                .btn-save-all:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .permisos-tables-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .permiso-tabla-rol {
                    display: none;
                }

                .permiso-tabla-rol.active {
                    display: block;
                }

                .rol-title {
                    font-size: 1.3rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .rol-title i {
                    color: #2196F3;
                }

                .table-responsive {
                    overflow-x: auto;
                }

                .permisos-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 600px;
                }

                .permisos-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .permisos-table th {
                    padding: 12px;
                    text-align: center;
                    font-weight: 600;
                    font-size: 0.85rem;
                }

                .permisos-table td {
                    padding: 12px;
                    border-bottom: 1px solid #e9ecef;
                    text-align: center;
                }

                .permisos-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .permisos-table input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    cursor: pointer;
                    accent-color: #2196F3;
                }

                .permisos-table tbody tr td:first-child {
                    text-align: left;
                    font-weight: 600;
                    color: #2d3748;
                }
            </style>

            <style>
                /* Botón de ayuda */
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
            </style>

            <button class="btn-ayuda" id="btn-ayuda-permisos" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
            </button>

            <script>
            (function () {
                'use strict';

                const ENDPOINT = '?pagina=permiso';
                const selectorRol = document.getElementById('selectorRol');

                /* ==================== Selector de rol ==================== */
                if (selectorRol) {
                    selectorRol.addEventListener('change', function () {
                        document.querySelectorAll('.permiso-tabla-rol').forEach(tabla => {
                            tabla.classList.remove('active');
                        });
                        const tablaSeleccionada = document.getElementById('tabla-rol-' + this.value);
                        if (tablaSeleccionada) {
                            tablaSeleccionada.classList.add('active');
                        }
                    });
                }

                /* ==================== Regla: sin "Ingresar" no hay otras acciones ==================== */
                function checksDeFila(fila) {
                    return Array.from(fila.querySelectorAll('.permiso-checkbox'));
                }

                function checkIngresar(fila) {
                    return fila.querySelector('.permiso-checkbox[data-accion="ingresar"]');
                }

                function checksOtros(fila) {
                    return checksDeFila(fila).filter(cb => cb.dataset.accion !== 'ingresar');
                }

                function checkTodos(fila) {
                    return fila.querySelector('.seleccionar-todos-modulo');
                }

                function sincronizarTodos(fila) {
                    const todos = checkTodos(fila);
                    if (!todos) return;
                    const otros = checksOtros(fila);
                    todos.checked = otros.length > 0 && otros.every(cb => cb.checked);
                    todos.indeterminate = !todos.checked && otros.some(cb => cb.checked);
                }

                function aplicarReglaIngresar(fila) {
                    const chkIngresar = checkIngresar(fila);
                    const todos = checkTodos(fila);
                    if (!chkIngresar) return;

                    const habilitado = chkIngresar.checked;
                    checksOtros(fila).forEach(cb => {
                        if (!habilitado) cb.checked = false;
                        cb.disabled = !habilitado;
                    });
                    if (todos) todos.disabled = !habilitado;
                    sincronizarTodos(fila);
                }

                function inicializarReglas() {
                    document.querySelectorAll('.permisos-table tbody tr').forEach(aplicarReglaIngresar);
                }

                document.addEventListener('change', function (e) {
                    const cb = e.target;
                    if (!(cb instanceof HTMLInputElement) || cb.type !== 'checkbox') return;

                    if (cb.classList.contains('permiso-checkbox')) {
                        const fila = cb.closest('tr');
                        if (!fila) return;
                        if (cb.dataset.accion === 'ingresar') {
                            aplicarReglaIngresar(fila);
                        } else {
                            sincronizarTodos(fila);
                        }
                        return;
                    }

                    if (cb.classList.contains('seleccionar-todos-modulo')) {
                        const fila = cb.closest('tr');
                        if (!fila) return;
                        checksOtros(fila).forEach(otro => { otro.checked = cb.checked; });
                        sincronizarTodos(fila);
                    }
                });

                inicializarReglas();

                /* ==================== Protección del selector de rol ====================
                   Impide alterar el valor u opciones del select desde DevTools.
                   La protección real está en el backend (filtra roles no editables);
                   esto evita manipulaciones casuales del DOM. */
                function protegerSelectorRol() {
                    if (!selectorRol) return;

                    const opcionesOriginales = Array.from(selectorRol.options).map(o => ({
                        value: o.value,
                        text: o.textContent
                    }));
                    const valoresValidos = new Set(opcionesOriginales.map(o => o.value));
                    let ultimoValorValido = selectorRol.value;

                    function restaurarOpcionesSiAlteradas() {
                        const ops = Array.from(selectorRol.options);
                        const alterado = ops.length !== opcionesOriginales.length ||
                            ops.some((o, i) => !opcionesOriginales[i] ||
                                o.value !== opcionesOriginales[i].value ||
                                o.textContent !== opcionesOriginales[i].text);
                        if (alterado) {
                            selectorRol.innerHTML = '';
                            opcionesOriginales.forEach(d => {
                                const op = document.createElement('option');
                                op.value = d.value;
                                op.textContent = d.text;
                                selectorRol.appendChild(op);
                            });
                        }
                    }

                    function verificar() {
                        restaurarOpcionesSiAlteradas();
                        if (!valoresValidos.has(selectorRol.value)) {
                            selectorRol.value = ultimoValorValido;
                        } else {
                            ultimoValorValido = selectorRol.value;
                        }
                    }

                    selectorRol.addEventListener('change', function () {
                        if (valoresValidos.has(this.value)) {
                            ultimoValorValido = this.value;
                        } else {
                            this.value = ultimoValorValido;
                        }
                    });

                    setInterval(verificar, 1000);
                }
                protegerSelectorRol();

                /* ==================== Guardar permisos (AJAX) ==================== */
                async function guardarTodosPermisos() {
                    const params = new URLSearchParams();
                    params.append('guardarPermisos', '1');

                    let total = 0;
                    document.querySelectorAll('.permiso-checkbox').forEach(cb => {
                        // Solo se envían los marcados y habilitados (sin "ingresar" el resto va deshabilitado)
                        if (cb.checked && !cb.disabled) {
                            params.append(
                                'permisos[' + cb.dataset.rol + '][' + cb.dataset.modulo + '][' + cb.dataset.accion + ']',
                                'on'
                            );
                            total++;
                        }
                    });

                    const confirm = await Swal.fire({
                        title: 'Guardar permisos',
                        text: 'Se actualizarán los permisos de ' + total + ' casillas marcadas. ¿Desea continuar?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, guardar'
                    });
                    if (!confirm.isConfirmed) return;

                    Swal.fire({ title: 'Guardando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    try {
                        const response = await fetch(ENDPOINT, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            credentials: 'same-origin',
                            body: params.toString()
                        });

                        let data = null;
                        try { data = await response.json(); } catch (e) { data = null; }

                        if (!response.ok || !data || data.success !== true) {
                            let detalle = (data && data.error) || 'Error del servidor';
                            if (data && data.errores) {
                                detalle = Object.values(data.errores).join(' ');
                            }
                            throw new Error(detalle);
                        }

                        await Swal.fire({
                            icon: 'success',
                            title: 'Permisos guardados',
                            text: data.message || 'Los permisos se actualizaron correctamente'
                        });
                    } catch (err) {
                        console.error('Error al guardar permisos:', err);
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message || 'No se pudieron guardar los permisos'
                        });
                    } finally {
                        Swal.close();
                    }
                }
                window.guardarTodosPermisos = guardarTodosPermisos;

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

                async function abrirModalAyuda() {
                    try {
                        await cargarRecursoAyuda('css', 'assets/public/ayuda/css/modal.css');

                        if (!document.getElementById('modalAyuda')) {
                            const html = await (await fetch('assets/public/ayuda/rolypermisos.php')).text();
                            document.body.insertAdjacentHTML('beforeend', html);
                        }

                        await cargarRecursoAyuda('js', 'assets/public/ayuda/js/modal.js');

                        if (typeof inicializarModalAyudaUsuario === 'function') {
                            modalAyudaInstance = inicializarModalAyudaUsuario();
                            document.body.style.overflow = '';
                            modalAyudaInstance.openModal();
                            const slideIndex = modalAyudaInstance.mapeoContextos['estatus'];
                            if (slideIndex !== undefined) {
                                setTimeout(() => modalAyudaInstance.goToSlide(slideIndex), 300);
                            }
                        } else {
                            console.error('La función inicializarModalAyudaUsuario no está disponible');
                        }
                    } catch (err) {
                        console.error('Error al cargar el modal de ayuda:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el contenido de ayuda' });
                    }
                }

                document.getElementById('btn-ayuda-permisos')?.addEventListener('click', function (e) {
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