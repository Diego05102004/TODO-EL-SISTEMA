<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en reporteUsuario: " . $e->getMessage());
    }
}

$idRol = $_SESSION['id_rol'] ?? null;
$idModulo = 1;
if (!isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) ||
    $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] !== true) {
    header('Location: ?pagina=acceso-denegado');
    exit;
}

$pagina_actual = 'reporteUsuario';
$titulo_pagina = 'Reporte de Usuarios';

$jsonOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$reporteRolesJson = json_encode($reporteRoles ?? [], $jsonOptions) ?: '[]';
$usuariosTodosJson = json_encode($usuariosTodos ?? [], $jsonOptions) ?: '[]';

ob_start();
?>

<section class="report-card" id="resultadoReporteUsuarios">
    <div class="section-header">
        <h2><i class="fas fa-chart-pie"></i> Reporte de Usuarios</h2>
        <div class="section-actions">
            <button type="button" class="btn-primary-action" id="generarReporteUsuarios">
                <span class="btn-icon"><i class="fas fa-chart-bar"></i></span>
                Generar Reporte
            </button>
            <button type="button" class="btn-export" id="descargarPDFUsuarios">
                <span class="btn-icon"><i class="fas fa-file-pdf"></i></span>
                Descargar PDF
            </button>
        </div>
    </div>

    <div class="filters-grid">
        <div class="filter-group">
            <label for="tipoReporteUsuarios">Tipo de reporte</label>
            <select id="tipoReporteUsuarios">
                <option value="roles" selected>Usuarios por Rol</option>
                <option value="estatus">Usuarios por Estatus</option>
                <option value="dominio">Usuarios por Dominio de Correo</option>
                <option value="inicial_nombre">Usuarios por Inicial de Nombre</option>
                <option value="inicial_apellido">Usuarios por Inicial de Apellido</option>
                <option value="area_telefono">Usuarios por Prefijo Telefónico</option>
            </select>
        </div>
        <div id="parametrosUsuarios" class="filters-grid dynamic-filter"></div>
    </div>

    <div id="contenidoPDFUsuarios">
        <h3 id="tituloReporteUsuarios" class="report-title">Usuarios por Rol</h3>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header"><h3 id="tituloGraficoUsuarios">Distribución</h3></div>
                <div class="chart-container">
                    <canvas id="graficoUsuarios"></canvas>
                </div>
            </div>
            <div class="chart-card table-chart-card">
                <div class="chart-header"><h3>Datos del reporte</h3></div>
                <div id="tablaUsuarios" class="table-wrapper"></div>
            </div>
        </div>
    </div>
</section>

<style>
.report-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.dynamic-filter {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    margin: 0;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.filter-group label {
    font-weight: 600;
    color: #4a5568;
}
.filter-group select {
    padding: 11px 12px;
    border: 1px solid #dfe6ed;
    border-radius: 8px;
    background: white;
}
.report-title {
    color: #2d3748;
    margin: 10px 0 20px;
}
.table-chart-card {
    min-width: 0;
}
.table-wrapper {
    max-height: 430px;
    overflow-y: auto;
}
.table-wrapper table {
    width: 100%;
    border-collapse: collapse;
}
.table-wrapper th {
    background: #2196F3;
    color: white;
    padding: 12px;
    text-align: left;
}
.table-wrapper td, .table-wrapper tfoot th {
    padding: 10px 12px;
    border-bottom: 1px solid #edf2f7;
}
.table-wrapper tr:hover td {
    background: #f8fafc;
}
.btn-primary-action, .btn-export {
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-primary-action { background: linear-gradient(135deg, #2196F3, #1976D2); }
.btn-export { background: linear-gradient(135deg, #f56565, #e53e3e); }
.btn-ayuda {
    position: fixed;
    top: 115px;
    right: 25px;
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 50%;
    background: #5995fd;
    box-shadow: 0 4px 12px rgba(0,0,0,.3);
    cursor: pointer;
    z-index: 1000;
}
.btn-ayuda img { width: 25px; height: 25px; }
@media (max-width: 768px) {
    .filters-grid { grid-template-columns: 1fr; }
    .section-actions { flex-direction: column; width: 100%; }
}
</style>

<button class="btn-ayuda" id="btn-ayuda-reporteUsuario" title="Visualizar Ayuda">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const reporteRoles = <?php echo $reporteRolesJson; ?>;
    const usuarios = <?php echo $usuariosTodosJson; ?>;
    let grafico = null;

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function colores(cantidad) {
        return Array.from({ length: Math.max(cantidad, 1) }, function (_, i) {
            return 'hsl(' + Math.round((360 / Math.max(cantidad, 1)) * i) + ',70%,60%)';
        });
    }

    function dominioCorreo(correo) {
        if (!correo) return 'Sin dominio';
        const indice = String(correo).indexOf('@');
        return indice > 0 ? String(correo).slice(indice + 1).toLowerCase() : 'Sin dominio';
    }

    function prefijoTelefono(telefono) {
        const digitos = String(telefono || '').replace(/\D/g, '');
        if (!digitos) return 'Sin prefijo';
        if (digitos.length >= 4 && digitos.startsWith('58')) {
            const local = digitos.slice(2);
            return '0' + local.slice(0, 3);
        }
        if (digitos.length >= 10 && /^[124]/.test(digitos) && !digitos.startsWith('0')) {
            return '0' + digitos.slice(0, 3);
        }
        return digitos.slice(0, 4);
    }

    function agrupar(datos, campo) {
        const mapa = new Map();
        (datos || []).forEach(function (item) {
            const etiqueta = String(item[campo] ?? '').trim() || 'Sin dato';
            mapa.set(etiqueta, (mapa.get(etiqueta) || 0) + 1);
        });
        return Array.from(mapa.entries())
            .map(function (entry) { return { label: entry[0], value: entry[1] }; })
            .sort(function (a, b) { return b.value - a.value; });
    }

    function buildFiltros() {
        const tipo = document.getElementById('tipoReporteUsuarios').value;
        const contenedor = document.getElementById('parametrosUsuarios');
        let html = '';

        if (tipo === 'roles') {
            const roles = Array.from(new Set(reporteRoles.map(function (rol) { return rol.nombre_rol; }).filter(Boolean))).sort();
            html += '<div class="filter-group"><label for="paramRol">Rol</label><select id="paramRol"><option value="">Todos</option>' +
                roles.map(function (rol) { return '<option value="' + esc(rol) + '">' + esc(rol) + '</option>'; }).join('') +
                '</select></div>';
        } else if (tipo === 'estatus') {
            html += '<div class="filter-group"><label for="paramEstatus">Estatus</label><select id="paramEstatus">' +
                '<option value="">Todos</option><option value="habilitado">Habilitado</option><option value="deshabilitado">Inhabilitado</option></select></div>';
        } else if (tipo === 'dominio') {
            const dominios = Array.from(new Set(usuarios.map(function (usuario) { return dominioCorreo(usuario.correo); }))).sort();
            html += '<div class="filter-group"><label for="paramDominio">Dominio</label><select id="paramDominio"><option value="">Todos</option>' +
                dominios.map(function (dominio) { return '<option value="' + esc(dominio) + '">' + esc(dominio) + '</option>'; }).join('') +
                '</select></div>' +
                '<div class="filter-group"><label for="paramTopN">Cantidad</label><select id="paramTopN">' +
                '<option value="0">Todos</option><option value="5">Top 5</option><option value="10">Top 10</option><option value="20">Top 20</option></select></div>';
        } else if (tipo === 'inicial_nombre' || tipo === 'inicial_apellido') {
            const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
            html += '<div class="filter-group"><label for="paramInicial">Inicial</label><select id="paramInicial"><option value="">Todas</option>' +
                letras.map(function (letra) { return '<option value="' + letra + '">' + letra + '</option>'; }).join('') +
                '</select></div>';
        } else if (tipo === 'area_telefono') {
            const prefijos = Array.from(new Set(usuarios.map(function (usuario) {
                return prefijoTelefono(usuario.telefono);
            }).filter(Boolean))).sort();
            html += '<div class="filter-group"><label for="paramArea">Prefijo</label><select id="paramArea"><option value="">Todos</option>' +
                prefijos.map(function (prefijo) { return '<option value="' + esc(prefijo) + '">' + esc(prefijo) + '</option>'; }).join('') +
                '</select></div>';
        }
        contenedor.innerHTML = html;
    }

    function renderReporte(datos, titulo, detalles) {
        document.getElementById('tituloReporteUsuarios').textContent = titulo;
        document.getElementById('tituloGraficoUsuarios').textContent = titulo;
        const tabla = document.getElementById('tablaUsuarios');
        const canvas = document.getElementById('graficoUsuarios');

        if (grafico) {
            grafico.destroy();
            grafico = null;
        }

        if (!datos || !datos.length) {
            tabla.innerHTML = '<div style="text-align:center;color:#718096;padding:30px;">No hay datos para mostrar</div>';
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        let html = '<table><thead><tr><th>Descripción</th><th>Cantidad</th></tr></thead><tbody>';
        datos.forEach(function (dato) {
            html += '<tr><td>' + esc(dato.label) + '</td><td>' + dato.value + '</td></tr>';
        });
        html += '</tbody><tfoot><tr><th>Total</th><th>' +
            datos.reduce(function (total, dato) { return total + dato.value; }, 0) +
            '</th></tr></tfoot></table>';

        if (detalles && detalles.length) {
            html += '<table style="margin-top:20px;"><thead><tr><th>#</th><th>Nombres</th><th>Apellidos</th><th>Usuario</th><th>Rol</th><th>Estatus</th></tr></thead><tbody>';
            detalles.forEach(function (usuario, indice) {
                html += '<tr><td>' + (indice + 1) + '</td><td>' + esc(usuario.nombres || '') +
                    '</td><td>' + esc(usuario.apellidos || '') +
                    '</td><td>' + esc(usuario.username || '') +
                    '</td><td>' + esc(usuario.nombre_rol || '') +
                    '</td><td>' + esc(usuario.estatus || '') + '</td></tr>';
            });
            html += '</tbody></table>';
        }
        tabla.innerHTML = html;

        grafico = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: datos.map(function (dato) { return dato.label; }),
                datasets: [{
                    label: titulo,
                    data: datos.map(function (dato) { return dato.value; }),
                    backgroundColor: colores(datos.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    function generarReporte() {
        const tipo = document.getElementById('tipoReporteUsuarios').value;
        const rol = document.getElementById('paramRol') ? document.getElementById('paramRol').value : '';
        const estatus = document.getElementById('paramEstatus') ? document.getElementById('paramEstatus').value : '';
        const dominio = document.getElementById('paramDominio') ? document.getElementById('paramDominio').value : '';
        const topN = document.getElementById('paramTopN') ? parseInt(document.getElementById('paramTopN').value, 10) || 0 : 0;
        const inicial = document.getElementById('paramInicial') ? document.getElementById('paramInicial').value : '';
        const area = document.getElementById('paramArea') ? document.getElementById('paramArea').value : '';

        if (tipo === 'roles') {
            let datos = reporteRoles.map(function (item) {
                return { label: item.nombre_rol || 'Sin dato', value: Number(item.cantidad || 0) };
            });
            if (rol) datos = datos.filter(function (dato) { return dato.label.toLowerCase() === rol.toLowerCase(); });
            renderReporte(datos, 'Usuarios por Rol');
        } else if (tipo === 'estatus') {
            let base = usuarios;
            if (estatus) {
                base = base.filter(function (usuario) {
                    const estadoUsuario = String(usuario.estatus || '').toLowerCase();
                    return estadoUsuario === estatus ||
                        (estatus === 'deshabilitado' && (estadoUsuario === 'inhabilitado' || estadoUsuario === 'deshabilitado'));
                });
            }
            renderReporte(agrupar(base, 'estatus'), 'Usuarios por Estatus');
        } else if (tipo === 'dominio') {
            let datos = agrupar(usuarios.map(function (usuario) { return { dominio: dominioCorreo(usuario.correo) }; }), 'dominio');
            if (dominio) datos = datos.filter(function (dato) { return dato.label.toLowerCase() === dominio.toLowerCase(); });
            if (topN > 0) datos = datos.slice(0, topN);
            renderReporte(datos, 'Usuarios por Dominio de Correo');
        } else if (tipo === 'inicial_nombre' || tipo === 'inicial_apellido') {
            const campo = tipo === 'inicial_nombre' ? 'nombres' : 'apellidos';
            let base = usuarios.map(function (usuario) {
                return {
                    ini: String(usuario[campo] || '').trim().charAt(0).toUpperCase() || '?',
                    usuario: usuario
                };
            });
            if (inicial) base = base.filter(function (item) { return item.ini === inicial; });
            const datos = base.reduce(function (mapa, item) {
                mapa[item.ini] = (mapa[item.ini] || 0) + 1;
                return mapa;
            }, {});
            renderReporte(
                Object.keys(datos).map(function (label) { return { label: label, value: datos[label] }; }),
                tipo === 'inicial_nombre' ? 'Usuarios por Inicial de Nombre' : 'Usuarios por Inicial de Apellido',
                inicial ? base.map(function (item) { return item.usuario; }) : []
            );
        } else if (tipo === 'area_telefono') {
            let datos = agrupar(usuarios.map(function (usuario) {
                return { area: prefijoTelefono(usuario.telefono) };
            }), 'area');
            if (area) datos = datos.filter(function (dato) { return dato.label === area; });
            renderReporte(datos, 'Usuarios por Prefijo Telefónico');
        }
    }

    function descargarPDF() {
        if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Las librerías de PDF no están cargadas' });
            return;
        }
        const elemento = document.getElementById('contenidoPDFUsuarios');
        const tabla = document.getElementById('tablaUsuarios');
        const alturaAnterior = tabla.style.maxHeight;
        const desbordamientoAnterior = tabla.style.overflowY;
        tabla.style.maxHeight = 'none';
        tabla.style.overflowY = 'visible';
        Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        html2canvas(elemento, { scale: 2, useCORS: true }).then(function (canvas) {
            tabla.style.maxHeight = alturaAnterior;
            tabla.style.overflowY = desbordamientoAnterior;
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('l', 'pt', 'a4');
            const width = pdf.internal.pageSize.getWidth();
            const height = canvas.height * (width - 40) / canvas.width;
            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 20, 20, width - 40, height);
            pdf.save('Reporte_Usuarios.pdf');
            Swal.close();
        }).catch(function (error) {
            tabla.style.maxHeight = alturaAnterior;
            tabla.style.overflowY = desbordamientoAnterior;
            console.error('Error generando PDF:', error);
            Swal.close();
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el PDF' });
        });
    }

    async function abrirModalAyuda() {
        try {
            if (!document.querySelector('link[href*="ayuda/css/modal.css"]')) {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'assets/public/ayuda/css/modal.css';
                document.head.appendChild(css);
            }
            if (!document.getElementById('modalAyuda')) {
                const html = await (await fetch('assets/public/ayuda/reporteUsuario.php')).text();
                document.body.insertAdjacentHTML('beforeend', html);
            }
            if (!document.querySelector('script[src*="ayuda/js/modal.js"]')) {
                await new Promise(function (resolve, reject) {
                    const script = document.createElement('script');
                    script.src = 'assets/public/ayuda/js/modal.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.body.appendChild(script);
                });
            }
            if (typeof inicializarModalAyudaUsuario === 'function') {
                inicializarModalAyudaUsuario().openModal();
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la ayuda' });
        }
    }

    document.getElementById('tipoReporteUsuarios').addEventListener('change', function () {
        buildFiltros();
        generarReporte();
    });
    document.getElementById('generarReporteUsuarios').addEventListener('click', generarReporte);
    document.getElementById('descargarPDFUsuarios').addEventListener('click', descargarPDF);
    document.getElementById('btn-ayuda-reporteUsuario').addEventListener('click', abrirModalAyuda);

    buildFiltros();
    generarReporte();
})();
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
?>
