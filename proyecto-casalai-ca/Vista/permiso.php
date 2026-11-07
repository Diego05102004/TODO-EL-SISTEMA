<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$idRol = $_SESSION['id_rol'] ?? 0; // rol por defecto en pruebas si no hay sesión
$idModulo = 17;
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Permisos</title>
    <?php include 'header.php'; ?>
    <style>
        .tabla-permisos-rol { display: none; }
        .tabla-permisos-rol.active { display: block; }
        .btn-seleccionar-todos {
            background: #27ae60; color: #fff; border: none; padding: 3px 8px;
            border-radius: 4px; cursor: pointer; font-size: 0.8rem;
        }
        .btn-seleccionar-todos:disabled { background: #ccc; cursor: not-allowed; }
        body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.main-content {
    flex: 1;
    padding-bottom: 60px; /* Espacio para el footer si es necesario */
}
</style>
</head>

<body class="fondo" style="height:100vh; background-image:url(assets/img/fondo.jpg); background-size:cover;">
<?php include 'NewNavBar.php'; ?>

<div class="main-content">

<div style="display:flex; flex-direction:column; align-items:center; min-height:70vh; margin-bottom: 20px; margin-top: 20px;">
<form method="post" action=""
    style="background:rgba(255,255,255,0.97); padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); margin:30px 0; width:100%; max-width:1100px;">

    <div class="row justify-content-center">
        <div class="col-md-5">
            <h2 class="tabla-titulo-2">Gestión de Permisos por Rol</h2>
        </div>

        <div class="col-md-2">
            <!-- Selector de roles -->
            <div style="text-align:center; margin-bottom:18px;">
                <label for="selectorRol" class="title-select">Seleccionar Rol:</label>
                <br>
                <select id="selectorRol" class="selector-reporte">
                    <?php
                    $excluidos = ['administrador','superusuario', 'cliente'];
                    $rolesSinSuper = array_filter($roles, function ($rol) use ($excluidos) {
                        return !in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'])), $excluidos);
                    });
                    $rolesSinSuper = array_values($rolesSinSuper);

                    foreach ($rolesSinSuper as $i => $rol): ?>
                        <option value="<?= $rol['id_rol'] ?>" <?= $i===0 ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rol['nombre_rol']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Tablas visibles -->
    <div style="max-height:60vh; overflow-y:auto;">
        <?php 
        $acciones = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
        foreach ($rolesSinSuper as $i => $rol): ?>
            <div class="tabla-permisos-rol <?= $i===0 ? 'active' : '' ?>" id="tabla-rol-<?= $rol['id_rol'] ?>">
                <h4 style="text-align:center; color:#1f66df;"><?= htmlspecialchars($rol['nombre_rol']) ?></h4>
                <table cellpadding="6" style="margin:0 auto; min-width:450px; text-align:center; border:1px solid #ddd;">
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
                        <?php foreach ($modulos_permiso as $modulo): ?>
                            <tr>
                                <td><?= htmlspecialchars($modulo['nombre_modulo']) ?></td>
                                <?php foreach ($acciones as $accion): ?>
                                    <td>
                                        <input type="checkbox" 
                                            class="permiso-<?= $accion ?>" 
                                            data-modulo="<?= $modulo['id_modulo'] ?>"
                                            name="permisos[<?= $rol['id_rol'] ?>][<?= $modulo['id_modulo'] ?>][<?= $accion ?>]"
                                            <?= isset($permisosActuales[$rol['id_rol']][$modulo['id_modulo']][$accion]) ? 'checked' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <button type="button" 
                                        class="btn-seleccionar-todos btn-incluir"
                                        title="Marcar todos"
                                        data-modulo="<?= $modulo['id_modulo'] ?>">
                                        <img src="assets/img/mouse-pointer-click.svg">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 🔹 Tablas ocultas para roles excluidos -->
    <div style="display:none;">
        <?php
        $rolesOcultos = array_filter($roles, function ($rol) use ($excluidos) {
            return in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'])), $excluidos);
        });

        foreach ($rolesOcultos as $rol): ?>
            <?php foreach ($modulos_permiso as $modulo): ?>
                <?php foreach ($acciones as $accion): ?>
                    <input type="checkbox"
                        name="permisos[<?= $rol['id_rol'] ?>][<?= $modulo['id_modulo'] ?>][<?= $accion ?>]"
                        <?= isset($permisosActuales[$rol['id_rol']][$modulo['id_modulo']][$accion]) ? 'checked' : '' ?>>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:15px;">
        <button type="submit" name="guardarPermisos" class="btn btn-primary">Guardar Permisos</button>
    </div>
</form>
</div>
                </div>
                
<?php include 'footer.php'; ?>

<script>
// Cambio de tabla por rol
document.getElementById('selectorRol').addEventListener('change', function () {
    document.querySelectorAll('.tabla-permisos-rol').forEach(tabla => tabla.classList.remove('active'));
    document.getElementById('tabla-rol-' + this.value).classList.add('active');
});

// Bloqueo por "Ingresar"
function actualizarPermisosIngresar() {
    document.querySelectorAll('.permiso-ingresar').forEach(chk => {
        const fila = chk.closest('tr');
        const otrosPermisos = fila.querySelectorAll(`input[type=checkbox]:not(.permiso-ingresar)`);
        const btnTodos = fila.querySelector('.btn-seleccionar-todos');

        if (!chk.checked) {
            otrosPermisos.forEach(cb => { cb.checked = false; cb.disabled = true; });
            btnTodos.disabled = true;
        } else {
            otrosPermisos.forEach(cb => cb.disabled = false);
            btnTodos.disabled = false;
        }
    });
}

actualizarPermisosIngresar();

document.querySelectorAll('.permiso-ingresar').forEach(chk => {
    chk.addEventListener('change', actualizarPermisosIngresar);
});

document.querySelectorAll('.btn-seleccionar-todos').forEach(btn => {
    btn.addEventListener('click', function () {
        const fila = this.closest('tr');
        fila.querySelectorAll(`input[type=checkbox]:not(.permiso-ingresar)`).forEach(cb => cb.checked = true);
    });
});

function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    // Guardar opciones originales de cada select
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;

        originales[id] = Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    });

    // Monitorear periódicamente cambios en las opciones
    setInterval(() => {
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;

            const opsActuales = Array.from(select.options);
            const opsOriginales = originales[id];
            if (!opsOriginales) return;

            const alterado =
                opsActuales.length !== opsOriginales.length ||
                opsActuales.some((o, i) =>
                    !opsOriginales[i] ||
                    o.value !== opsOriginales[i].value ||
                    o.textContent !== opsOriginales[i].text
                );

            if (alterado) {
                select.innerHTML = "";
                opsOriginales.forEach(optData => {
                    const opt = document.createElement("option");
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    select.appendChild(opt);
                });

                console.warn(`⚠ Opciones del <select id="${id}"> fueron alteradas. Restauradas automáticamente.`);
            }
        });
    }, interval);
}

protegerSelects(['selectorRol']);
</script>

<script>
    // Esperar a que jQuery esté disponible
    $(document).ready(function() {
        console.log('jQuery cargado, inicializando modal de ayuda...');
        
        let modalAyudaInstance = null;

        // Función para cargar y mostrar el modal de ayuda con contexto específico
        function cargarYMostrarModalAyuda(contexto = null) {
            console.log('cargarYMostrarModalAyuda llamado con contexto:', contexto);
            
            // Cargar CSS si no está cargado
            if (!$('link[href*="ayuda/css/modal.css"]').length) {
                console.log('Cargando CSS del modal...');
                $('<link>')
                    .attr({
                        'rel': 'stylesheet',
                        'type': 'text/css',
                        'href': 'assets/public/ayuda/css/modal.css'
                    })
                    .appendTo('head');
            }

            // Cargar HTML del modal
            $.get('assets/public/ayuda/rolypermisos.php')
                .done(function(html) {
                    console.log('HTML del modal cargado');
                    
                    // Solo agregar modal si no existe
                    if (!$('#modalAyuda').length) {
                        $('body').append(html);
                        console.log('Modal agregado al DOM');
                    }

                    // Cargar JS del modal si no está cargado
                    if (!$('script[src*="ayuda/js/modal.js"]').length) {
                        console.log('Cargando JavaScript del modal...');
                        $.getScript('assets/public/ayuda/js/modal.js')
                            .done(function() {
                                console.log('JavaScript del modal cargado');
                                inicializarModalConContexto(contexto);
                            })
                            .fail(function() {
                                console.error('Error al cargar el JavaScript del modal de ayuda');
                            });
                    } else {
                        console.log('JavaScript del modal ya estaba cargado');
                        inicializarModalConContexto(contexto);
                    }
                })
                .fail(function() {
                    console.error('Error al cargar el HTML del modal');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el contenido de ayuda'
                    });
                });
        }

        function inicializarModalConContexto(contexto) {
            // Inicializar modal
            if (typeof inicializarModalAyudaUsuario === 'function') {
                modalAyudaInstance = inicializarModalAyudaUsuario();
                console.log('Modal inicializado:', modalAyudaInstance);
                console.log('Mapeo de contextos disponible:', modalAyudaInstance.mapeoContextos);

                // Abrir modal con contexto si se proporciona
                if (contexto) {
                    setTimeout(() => {
                        const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                        console.log('Contexto:', contexto, '-> Slide:', slideIndex);
                        if (slideIndex !== undefined) {
                            modalAyudaInstance.goToSlide(slideIndex);
                        }
                    }, 300);
                }

                // Abrir modal
                modalAyudaInstance.openModal();
            } else {
                console.error('La función inicializarModalAyudaUsuario no está disponible');
            }
        }

        // Botón de ayuda principal - abrir directamente en gestión de permisos
        $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
            e.preventDefault();
            console.log('Clic en botón de ayuda principal detectado');
            cargarYMostrarModalAyuda('estatus'); // Abrir directamente en "Gestionar Permisos"
        });

        // Botón de ayuda dentro de modales
        $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
            e.preventDefault();
            const contexto = $(this).data('contexto');
            console.log('Clic en botón de ayuda modal con contexto:', contexto);
            cargarYMostrarModalAyuda(contexto);
        });
    });
</script>

    <button 
        class="btn-ayuda"
        style="top: 125px; right: 105px;"
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
?>
