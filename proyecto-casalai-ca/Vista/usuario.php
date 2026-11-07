<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 1;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestionar Usuarios</title>
        <?php include 'header.php'; ?>
    </head>

    <body class="fondo"
        style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

        <?php include 'NewNavBar.php'; ?>

        <div class="modal fade modal-registrar" id="registrarUsuarioModal" tabindex="-1" role="dialog"
            aria-labelledby="registrarUsuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <form id="incluirusuario" method="POST" novalidate>
                        <div class="modal-header">
                            <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Usuario" data-contexto="registrar">
                                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                            </button>
                            <h5 class="titulo-form" id="registrarUsuarioModalLabel">Incluir Usuario</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="registrar">
                            <div class="grupo-form">
                                <div class="grupo-interno">
                                    <label for="nombre">Nombre(s)*</label>
                                    <input type="text" placeholder="Nombre" class="control-form" id="nombre" name="nombre" maxlength="50" required>
                                    <span class="span-value" id="snombre"></span>
                                </div>
                                <div class="grupo-interno">
                                    <label for="apellido_usuario">Apellido(s)*</label>
                                    <input type="text" placeholder="Apellido" class="control-form" id="apellido_usuario" name="apellido_usuario" maxlength="50" required>
                                    <span class="span-value" id="sapellido"></span>
                                </div>
                            </div>
                            <div class="grupo-form">
                                <div class="grupo-interno">
                                    <label for="cedula">Cédula*</label>
                                    <input class="control-form" placeholder="1.234.567 o 12.345.678" maxlength="10" type="text" id="cedula" name="cedula" required>
                                    <span class="span-value" id="scedula"></span>                                
                                </div>
                                <div class="grupo-interno">
                                    <label for="telefono_usuario">Número de Teléfono*</label>
                                    <input type="text" placeholder="0400-000-0000" class="control-form" id="telefono_usuario" name="telefono_usuario" maxlength="13" required>
                                    <span class="span-value" id="stelefono_usuario"></span>
                                </div>
                            </div>
                            <div class="envolver-form">
                                <label for="nombre">Nombre de Usuario*</label>
                                <input type="text" placeholder="Usuario" class="control-form" id="nombre_usuario"
                                    name="nombre_usuario" maxlength="20" required>
                                <span class="span-value" id="snombre_usuario"></span>
                            </div>
                            <div class="envolver-form">
                                <label for="correo_usuario">Correo Electrónico*</label>
                                <input type="text" placeholder="ejemplo@gmail.com" class="control-form" id="correo_usuario"
                                    name="correo_usuario" maxlength="50" required>
                                <span class="span-value" id="scorreo_usuario"></span>
                            </div>
                            <div class="envolver-form">
                                <label for="rango">Rol de Usuario*</label>
                                <select class="form-select" id="rango" name="rango">
                                    <option value="" hidden>Seleccione el rol del usuario</option>
                                    <?php
                                    foreach ($selecionarRol as $rol) {
                                        if($rol['nombre_rol'] != 'SuperUsuario' && $rol['nombre_rol'] != 'Cliente') {
                                            echo '<option value="' . $rol['id_rol'] . '">' . htmlspecialchars($rol['nombre_rol']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="envolver-form">
                                <label for="clave_usuario">Contraseña*</label>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="password" placeholder="Crea una contraseña" class="control-form"
                                        id="clave_usuario" name="clave_usuario" maxlength="15" required>
                                    <button type="button" class="toggle-password" data-target="#clave_usuario" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                    </button>
                                </div>
                                <span class="span-value" id="sclave_usuario"></span>
                            </div>
                            <div class="envolver-form">
                                <label for="clave_confirmar">Confirmar Contraseña*</label>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="password" placeholder="Ingrese nuevamente la contraseña" class="control-form"
                                        id="clave_confirmar" name="clave_confirmar" maxlength="15" required>
                                    <button type="button" class="toggle-password" data-target="#clave_confirmar" title="Mostrar/Ocultar" style="background:transparent;border:none;cursor:pointer;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                    </button>
                                </div>
                                <span class="span-value" id="sclave_confirmar"></span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="boton-form btn-primary" type="submit">Registrar</button>
                            <button class="boton-reset btn-primary" type="reset">Limpiar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>
        
        <h3>LISTA DE USUARIOS</h3>
        
        <div class="filtro-status">
            <label for="filtro-estatus">Mostrar:</label>
            <select id="filtro-estatus" class="form-select">
                <option value="todos" selected>Todos</option>
                <option value="habilitado">Habilitados</option>
                <option value="inhabilitado">Inhabilitados</option>
            </select>
        </div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas" style="width:100%">
        <thead>
            <tr>
                <th>Nombre y Apellido</th>
                <th>Correo</th>
                <th>Usuario</th>
                <th>Telefono</th>
                <th>Rol</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr data-id="<?php echo $usuario['id_usuario']; ?>">
                    <td>
                        <span class="campo-nombres">
                            <?php echo htmlspecialchars($usuario['nombres'] ?? ''); ?>
                            <?php echo htmlspecialchars($usuario['apellidos'] ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-tex-num">
                            <?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-nombres">
                            <?php echo htmlspecialchars($usuario['username'] ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-numeros">
                            <?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-rango">
                            <?php echo htmlspecialchars($usuario['nombre_rol'] ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span
                            <?php if (strtolower($usuario['nombre_rol']) !== 'superusuario'): ?>
                            class="campo-estatus <?php echo ($usuario['estatus'] == 'habilitado') ? 'habilitado' : 'inhabilitado'; ?>"
                            data-id="<?php echo $usuario['id_usuario']; ?>"
                            style="cursor: pointer;"
                            title="Cambiar Estatus">
                            <?php echo htmlspecialchars($usuario['estatus'] ?? ''); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <ul>
                            <?php if (strtolower($usuario['nombre_rol']) !== 'superusuario'): ?>
                                <button class="btn-modificar"
                                    title="Modificar Usuario"
                                    data-id="<?php echo $usuario['id_usuario']; ?>"
                                    data-username="<?php echo htmlspecialchars($usuario['username'] ?? ''); ?>"
                                    data-nombres="<?php echo htmlspecialchars($usuario['nombres'] ?? ''); ?>"
                                    data-apellidos="<?php echo htmlspecialchars($usuario['apellidos'] ?? ''); ?>"
                                    data-cedula="<?php echo htmlspecialchars($usuario['cedula'] ?? ''); ?>"
                                    data-correo="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>"
                                    data-telefono="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                    data-clave="<?php echo htmlspecialchars($usuario['password'] ?? ''); ?>"
                                    data-rango="<?php echo htmlspecialchars($usuario['id_rol']); ?>">
                                    <img src="assets/img/pencil.svg">
                                </button>
                            <?php endif; ?>
                            <?php if (strtolower($usuario['nombre_rol']) !== 'superusuario'): ?>
                                <button class="btn-eliminar"
                                    title="Eliminar Usuario"
                                    data-id="<?php echo $usuario['id_usuario']; ?>">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            <?php endif; ?>
                        </ul>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade modal-modificar" id="modificar_usuario_modal" tabindex="-1" role="dialog"
    aria-labelledby="modificar_usuario_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="modificarusuario" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Usuario" data-contexto="modificar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="modificar_usuario_modal_label">Modificar Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modificar_id_usuario" name="id_usuario">
                    <div class="form-group">
                        <label for="modificarnombre">Nombres del Usuario</label>
                        <input type="text" class="form-control" id="modificarnombre" name="nombre" maxlength="30" required>
                        <span class="span-value-modal" id="smodificarnombre"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificarapellido_usuario">Apellidos del Usuario</label>
                        <input type="text" class="form-control" id="modificarapellido_usuario" name="apellido_usuario" maxlength="30" required>
                        <span class="span-value-modal" id="smodificarapellido_usuario"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificarcedula">Cédula</label>
                        <input type="text" class="form-control" id="modificarcedula" name="cedula" maxlength="10" required>
                        <span class="span-value-modal" id="smodificarcedula"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificarnombre_usuario">Usuario</label>
                        <input type="text" class="form-control" id="modificarnombre_usuario" name="nombre_usuario" maxlength="20" required>
                        <span class="span-value-modal" id="smodificarnombre_usuario"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificartelefono_usuario">Telefono</label>
                        <input type="text" class="form-control" id="modificartelefono_usuario" name="telefono_usuario" maxlength="13" required>
                        <span class="span-value-modal" id="smodificartelefono_usuario"></span>
                    </div>
                    <div class="form-group">
                        <label for="modificarcorreo_usuario">Correo</label>
                        <input type="text" class="form-control" id="modificarcorreo_usuario" name="correo_usuario" maxlength="50" required>
                        <span class="span-value-modal" id="smodificarcorreo_usuario"></span>
                    </div>
                    <div class="form-group">
                        <label for="rango">Rol de Usuario</label>
                        <select class="form-select" id="modificar_rango" name="rango">
                            <option value="" hidden>Seleccione el tipo de usuario a crear</option>
                            <?php
                                foreach ($selecionarRol as $rol) {
                                    if($rol['nombre_rol'] != 'SuperUsuario') {
                                        echo '<option value="' . $rol['id_rol'] . '">' . htmlspecialchars($rol['nombre_rol']) . '</option>';
                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Modificar</button>
                </div>
            </form>
        </div>
    </div>
</div>

     <div class="report-container" style="max-width: 1100px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <h2 class="titulo-form" style="text-align:center">Reportes de Usuarios</h2>
        <div class="parameters-container" style="margin: 16px 0; background:#f8f9fa; border-radius: 10px; padding: 16px;">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="selectReporteUsuarios" class="title-select">Reporte</label>
                    <select id="selectReporteUsuarios" class="selector-reporte">
                        <option value="roles" selected>Usuarios por Rol</option>
                        <option value="estatus">Usuarios por Estatus</option>
                        <option value="dominio">Usuarios por Dominio de Correo</option>
                        <option value="inicial_nombre">Usuarios por Inicial de Nombre</option>
                        <option value="inicial_apellido">Usuarios por Inicial de Apellido</option>
                        <option value="area_telefono">Usuarios por Prefijo Telefónico</option>
                    </select>
                </div>
                <div id="parametrosUsuarios" class="col-md-8 row g-3"></div>
            </div>
        </div>

        <div id="reporteUsuarios" class="report-section" style="padding: 16px; border:1px solid #e0e0e0; border-radius: 10px;">
            <h3 id="tituloReporteUsuarios" class="titulo-form" style="font-size:20px;">Usuarios por Rol</h3>
            <div class="chart-container" style="display:flex; gap:20px; flex-wrap:wrap; align-items:center; justify-content:center;">
                <div class="chart-canvas" style="flex:1; min-width:300px; text-align:center;">
                    <canvas id="graficoUsuarios" width="380" height="280"></canvas>
                </div>
                <div class="chart-table" style="flex:2; min-width:380px;">
                    <div id="tablaUsuarios"></div>
                </div>
            </div>
            <div style="text-align:center;">
                <button id="btnGenerarUsuarios" class="btn btn-primary">Generar Reporte</button>
                <button id="descargarPDFUsuarios" class="btn btn-primary">Descargar PDF</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
    const reporteRolesPHP = <?= json_encode($reporteRoles ?? []) ?>;
    const usuariosTodos = <?= json_encode($usuariosTodos ?? []) ?>;

    let graficoUsuarios = null;

    function generarColores(n){
        return Array.from({length: Math.max(n,1)}, (_,i)=>`hsl(${Math.round((360/Math.max(n,1))*i)},70%,60%)`);
    }

    function agrupar(rows, key){
        const m = new Map();
        (rows||[]).forEach(r=>{
            const k = String(r[key] ?? '').trim() || 'Sin dato';
            m.set(k, (m.get(k)||0) + 1);
        });
        return Array.from(m.entries()).map(([label,value])=>({label,value})).sort((a,b)=>b.value-a.value);
    }

    function dominioCorreo(correo){
        if(!correo) return 'Sin dominio';
        const i = String(correo).indexOf('@');
        return i>0 ? String(correo).slice(i+1).toLowerCase() : 'Sin dominio';
    }

    function getParametrosUsuarios(){
        return {
            rol: document.getElementById('paramRol')?.value || '',
            estatus: document.getElementById('paramEstatus')?.value || '',
            dominio: document.getElementById('paramDominio')?.value || '',
            inicial: document.getElementById('paramInicial')?.value || '',
            area: document.getElementById('paramArea')?.value || '',
            topN: parseInt(document.getElementById('paramTopN')?.value||'0',10) || 0
        };
    }

    function buildParametrosUsuarios(){
        const tipo = document.getElementById('selectReporteUsuarios').value;
        const cont = document.getElementById('parametrosUsuarios');
        let html = '';
        if (tipo==='roles'){
            const roles = Array.from(new Set((reporteRolesPHP||[]).map(r=>r.nombre_rol))).sort();
            html += `<div class="col-md-4 title-select"><label>Rol</label><select id="paramRol" class="selector-reporte"><option value="">Todos</option>${roles.map(r=>`<option value="${r}">${r}</option>`).join('')}</select></div>`;
        } else if (tipo==='estatus'){
            html += `<div class="col-md-3 title-select"><label>Estatus</label><select id="paramEstatus" class="selector-reporte"><option value="">Todos</option><option value="habilitado">Habilitado</option><option value="deshabilitado">Inhabilitado</option></select></div>`;
        } else if (tipo==='dominio'){
            const dominios = Array.from(new Set((usuariosTodos||[]).map(u=>dominioCorreo(u.correo)))).sort();
            html += `<div class="col-md-4 title-select"><label>Dominio</label><select id="paramDominio" class="selector-reporte"><option value="">Todos</option>${dominios.map(d=>`<option value="${d}">${d}</option>`).join('')}</select></div>`;
            html += `<div class="col-md-2 title-select"><label>Top</label><select id="paramTopN" class="selector-reporte"><option value="0">Todos</option><option value="5">Top 5</option><option value="10">Top 10</option><option value="20">Top 20</option></select></div>`;
        } else if (tipo==='inicial_nombre' || tipo==='inicial_apellido'){
            const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
            html += `<div class="col-md-3 title-select"><label>Inicial</label><select id="paramInicial" class="selector-reporte"><option value="">Todas</option>${letras.map(l=>`<option value="${l}">${l}</option>`).join('')}</select></div>`;
        } else if (tipo==='area_telefono'){
            const areas = Array.from(new Set((usuariosTodos||[]).map(u=>String(u.telefono||'').slice(0,4)).filter(v=>v))).sort();
            html += `<div class="col-md-3 title-select"><label>Prefijo</label><select id="paramArea" class="selector-reporte"><option value="">Todos</option>${areas.map(a=>`<option value="${a}">${a}</option>`).join('')}</select></div>`;
        }
        cont.innerHTML = html;
    }

    function renderUsuarios(datos, titulo, detalles){
        const canvas = document.getElementById('graficoUsuarios');
        const tabla = document.getElementById('tablaUsuarios');
        document.getElementById('tituloReporteUsuarios').textContent = titulo;
        if (!datos || datos.length===0){
            if (graficoUsuarios){ graficoUsuarios.destroy(); graficoUsuarios=null; }
            tabla.innerHTML = `<div class="alert alert-warning text-center">No hay datos</div>`;
            const ctx = canvas.getContext('2d'); ctx.clearRect(0,0,canvas.width,canvas.height);
            return;
        }
        const labels = datos.map(d=>d.label);
        const values = datos.map(d=>d.value);
        const colores = generarColores(labels.length);
        let tablaHtml = `<div class=\"table-responsive\"><table class=\"table table-bordered table-striped table-hover\"><thead><tr><th>Descripción</th><th>Cantidad</th></tr></thead><tbody>`;
        datos.forEach(d=>{ tablaHtml += `<tr><td>${d.label}</td><td>${d.value}</td></tr>`; });
        tablaHtml += `</tbody><tfoot><tr><th>Total</th><th>${values.reduce((a,b)=>a+b,0)}</th></tr></tfoot></table></div>`;

        // Si se proveen detalles (lista de usuarios), agregamos una tabla adicional
        if (detalles && detalles.length){
            tablaHtml += `<div class=\"mt-3\"></div>`;
            tablaHtml += `<div class=\"table-responsive\"><table class=\"table table-bordered table-striped table-hover\"><thead><tr><th>#</th><th>Nombres</th><th>Apellidos</th><th>Usuario</th><th>Rol</th><th>Estatus</th></tr></thead><tbody>`;
            detalles.forEach((u,idx)=>{
                const est = (u.estatus||'').toString();
                tablaHtml += `<tr><td>${idx+1}</td><td>${u.nombres||''}</td><td>${u.apellidos||''}</td><td>${u.username||''}</td><td>${u.nombre_rol||''}</td><td>${est}</td></tr>`;
            });
            tablaHtml += `</tbody></table></div>`;
        }
        tabla.innerHTML = tablaHtml;

        if (graficoUsuarios){ graficoUsuarios.destroy(); }
        graficoUsuarios = new Chart(canvas.getContext('2d'),{
            type:'bar',
            data:{ labels, datasets:[{ label: titulo, data: values, backgroundColor: colores }] },
            options:{ plugins:{ legend:{ display:true, position:'bottom' } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
        });
    }

    function generarUsuarios(){
        const tipo = document.getElementById('selectReporteUsuarios').value;
        const p = getParametrosUsuarios();
        if (tipo==='roles'){
            let rows = (reporteRolesPHP||[]).map(r=>({label: r.nombre_rol, value: Number(r.cantidad||0)}));
            if (p.rol){ const n=p.rol.toLowerCase(); rows = rows.filter(x=>x.label.toLowerCase()===n); }
            renderUsuarios(rows, 'Usuarios por Rol');
        } else if (tipo==='estatus'){
            let base = usuariosTodos || [];
            if (p.estatus){ base = base.filter(u=>String(u.estatus||'').toLowerCase()===p.estatus); }
            renderUsuarios(agrupar(base,'estatus'), 'Usuarios por Estatus');
        } else if (tipo==='dominio'){
            let base = (usuariosTodos||[]).map(u=>({ dominio: dominioCorreo(u.correo) }));
            let rows = agrupar(base.map(x=>({dominio:x.dominio})), 'dominio');
            if (p.dominio){ const n=p.dominio.toLowerCase(); rows = rows.filter(x=>x.label.toLowerCase()===n); }
            if (p.topN>0){ rows = rows.slice(0,p.topN); }
            renderUsuarios(rows, 'Usuarios por Dominio de Correo');
        } else if (tipo==='inicial_nombre'){
            const users = usuariosTodos || [];
            let base = users.map(u=>({ ini: String(u.nombres||'').trim().charAt(0).toUpperCase()||'?', u }));
            let detalles = [];
            if (p.inicial){
                base = base.filter(x=>x.ini===p.inicial.toUpperCase());
                detalles = base.map(x=>x.u);
            }
            const rows = agrupar(base.map(x=>({ini:x.ini})), 'ini').map(x=>({label:x.label, value:x.value}));
            renderUsuarios(rows, 'Usuarios por Inicial de Nombre', detalles);
        } else if (tipo==='inicial_apellido'){
            const users = usuariosTodos || [];
            let base = users.map(u=>({ ini: String(u.apellidos||'').trim().charAt(0).toUpperCase()||'?', u }));
            let detalles = [];
            if (p.inicial){
                base = base.filter(x=>x.ini===p.inicial.toUpperCase());
                detalles = base.map(x=>x.u);
            }
            const rows = agrupar(base.map(x=>({ini:x.ini})), 'ini').map(x=>({label:x.label, value:x.value}));
            renderUsuarios(rows, 'Usuarios por Inicial de Apellido', detalles);
        } else if (tipo==='area_telefono'){
            let base = (usuariosTodos||[]).map(u=>({ area: String(u.telefono||'').slice(0,4)||'N/A' }));
            if (p.area){ base = base.filter(x=>x.area===p.area); }
            const rows = agrupar(base.map(x=>({area:x.area})), 'area').map(x=>({label:x.label, value:x.value}));
            renderUsuarios(rows, 'Usuarios por Prefijo Telefónico');
        }
    }

    document.getElementById('selectReporteUsuarios').addEventListener('change', ()=>{ buildParametrosUsuarios(); });
    document.getElementById('btnGenerarUsuarios').addEventListener('click', (e)=>{ e.preventDefault(); generarUsuarios(); });
    document.getElementById('descargarPDFUsuarios').addEventListener('click', function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        const cont = document.querySelector('.report-container');
        html2canvas(cont).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pageWidth = doc.internal.pageSize.getWidth();
            const imgWidth = pageWidth - 40;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
            doc.save('Reporte_Usuarios.pdf');
        });
    });

    buildParametrosUsuarios();
    generarUsuarios();
    </script>
        <!-- Modal de eliminación -->
        <?php include 'footer.php'; ?>
        <!-- jQuery ya se carga en header.php; evitar recargarlo aquí para no perder handlers -->
        <!-- DataTables -->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
        
<script src="assets/javascript/jwt_validator.js"></script>
        <!-- Cargar lógica del módulo al final, con todas las dependencias listas -->
        <script src="assets/javascript/usuario.js"></script>
        <script src="assets/public/bootstrap/js/sidebar.js"></script>

        <button 
            class="btn-grafica"
            title="Visualizar Reportes"
            onclick="window.location.href='?pagina=reporteUsuario'">
            <img src="assets/img/grafic.png" alt="Reportes" width="30" height="30">
        </button>
        <button 
            class="btn-ayuda"
            title="Visualizar Ayuda">
            <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
        </button>
    </body>

    </html>
    <?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>