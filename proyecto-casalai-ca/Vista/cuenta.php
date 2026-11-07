<?php require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 15;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) { ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestionar Cuentas Bancarias</title>
        <?php include 'header.php'; ?>
        <style>
.error {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.error-text {
    color: #dc3545 !important;
    font-size: 0.875em;
    margin-top: 0.25rem;
}

.error-container {
    border: 1px solid #dc3545 !important;
    border-radius: 0.375rem;
    padding: 0.5rem;
}

.span-value.error-text {
    display: block;
    margin-top: 0.25rem;
}
</style>
    </head>

    <?php include 'NewNavBar.php'; ?>

    <body class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

        <div class="modal fade modal-registrar" id="registrarCuentaModal" tabindex="-1" role="dialog"
            aria-labelledby="registrarCuentaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <form id="registrarCuenta" method="POST" novalidate>
                        <div class="modal-header">
                            <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Cuenta" data-contexto="registrar">
                                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                            </button>
                            <h5 class="titulo-form" id="registrarCuentaModalLabel">Incluir Cuenta</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="registrar">
                            <div class="grupo-form">
                                <div class="grupo-interno">
                                    <label for="nombre_banco">Nombre del Banco*</label>
                                    <input type="text" placeholder="Nombre" class="control-form" id="nombre_banco"
                                        name="nombre_banco" maxlength="20" required>
                                    <span class="span-value" id="snombre_banco"></span>
                                </div>
                                <div class="grupo-interno">
                                    <label for="numero_cuenta">Número de Cuenta*</label>
                                    <input type="text" placeholder="N° de cuenta" class="control-form" id="numero_cuenta"
                                        name="numero_cuenta" maxlength="23" required>
                                    <span class="span-value" id="snumero_cuenta"></span>
                                </div>
                            </div>
                            <div class="grupo-form">
                                <div class="grupo-interno">
                                    <label for="rif_cuenta">RIF*</label>
                                    <input type="text" placeholder="V-12345678-9" class="control-form" id="rif_cuenta"
                                        name="rif_cuenta" maxlength="12" required>
                                    <span class="span-value" id="srif_cuenta"></span>
                                </div>
                                <div class="grupo-interno">
                                    <label for="telefono_cuenta">Teléfono*</label>
                                    <input type="text" placeholder="0400-000-0000" class="control-form" id="telefono_cuenta"
                                        name="telefono_cuenta" maxlength="13" required>
                                    <span class="span-value" id="stelefono_cuenta"></span>
                                </div>
                            </div>
                            <div class="envolver-form">
                                <label for="correo_cuenta">Correo*</label>
                                <input type="email" placeholder="ejemplo@gmail.com" class="control-form" id="correo_cuenta"
                                    name="correo_cuenta" maxlength="50" required>
                                <span class="span-value" id="scorreo_cuenta"></span>
                            </div>
                            <div class="grupo-form">
                                <div class="grupo-interno">
                                    <label for="tipo_moneda">Tipo de Moneda*</label>
                                    <select class="form-select" id="tipo_moneda" name="tipo_moneda" required>
                                        <option value="" hidden>Seleccione el tipo de moneda</option>
                                        <option value="bs">Bolívares</option>
                                        <option value="usd">Dólares</option>
                                    </select>
                                </div>
                                <div class="grupo-interno">
                                    <label class="label-checkbox" id="titulo-metodos-pago">Métodos de Pago Aceptados*</label>
                                    <div class="btn-group metodos-bs" role="group" aria-label="Métodos en Bs">
                                        <input type="checkbox" class="btn-check" value="Pago Movil" id="pagoMovil" name="metodos_pago[]" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="pagoMovil">Pago Móvil</label>

                                        <input type="checkbox" class="btn-check" id="transferencia" value="Transferencia" name="metodos_pago[]" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="transferencia">Transferencia</label>
                                    </div>
                                    <div class="btn-group metodos-usd d-none" role="group" aria-label="Métodos en USD">
                                        <input type="checkbox" class="btn-check" value="Zelle" id="zelle" name="metodos_pago[]" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="zelle">Zelle</label>
                                    </div>
                                </div>
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

        <!-- El resto del código permanece igual (tabla, modal de detalles, etc.) -->
        
        <div class="contenedor-tabla">

            <div class="tabla-header">
                <div class="ghost"></div>

                <h3>Lista de Cuentas <br> Bancarias</h3>

                <div class="filtro-status">
                    <label for="filtro-estatus-cuentas">Mostrar:</label>
                    <select id="filtro-estatus-cuentas" class="form-select">
                        <option value="todos" selected>Todos</option>
                        <option value="habilitado">Habilitados</option>
                        <option value="inhabilitado">Inhabilitados</option>
                    </select>
                </div>
            </div>
            
            <table class="tablaConsultas" id="tablaConsultas">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Banco</th>
                        <th>Número de Cuenta</th>
                        <th>Teléfono</th>
                        <th>Métodos Permitidos</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentabancos as $cuenta): ?>
                        <?php if ($cuenta['id_cuenta'] != 1 && $cuenta['id_cuenta'] != 0){?>
                        <tr data-id="<?php echo $cuenta['id_cuenta']; ?>">
                            <td>
                                <span class="campo-numeros">
                                    <?php echo htmlspecialchars($cuenta['id_cuenta']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-nombres">
                                    <?php echo htmlspecialchars($cuenta['nombre_banco']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-numeros">
                                    <?php echo htmlspecialchars($cuenta['numero_cuenta']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-numeros">
                                    <?php echo htmlspecialchars($cuenta['telefono_cuenta']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-nombres">
                                    <?php echo str_replace(',', '<br>', htmlspecialchars($cuenta['metodos'])); ?>
                                </span>
                            </td>
                            <td>
                                <span
                                    class="campo-estatus <?php echo ($cuenta['estado'] == 'habilitado') ? 'habilitado' : 'inhabilitado'; ?>"
                                    data-id="<?php echo $cuenta['id_cuenta']; ?>"
                                    style="cursor: pointer;"
                                    title="Cambiar Estatus">
                                    <?php echo htmlspecialchars($cuenta['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <ul>
                                    <button class="btn-detalle"
                                        title="Ver Detalles"
                                        data-iddtl="<?php echo $cuenta['id_cuenta']; ?>"
                                        data-nombredtl="<?php echo htmlspecialchars($cuenta['nombre_banco']); ?>"
                                        data-numerodtl="<?php echo htmlspecialchars($cuenta['numero_cuenta']); ?>"
                                        data-rifdtl="<?php echo htmlspecialchars($cuenta['rif_cuenta']); ?>"
                                        data-telefonodtl="<?php echo htmlspecialchars($cuenta['telefono_cuenta']); ?>"
                                        data-correodtl="<?php echo htmlspecialchars($cuenta['correo_cuenta']); ?>"
                                        data-metodosdtl="<?php echo htmlspecialchars($cuenta['metodos']); ?>"
                                        data-estatusdtl="<?php echo htmlspecialchars($cuenta['estado']); ?>">
                                        <img src="assets/img/eye.svg">
                                    </button>
                                    <button class="btn-modificar" 
                                        id="btnModificarCuenta"
                                        title="Modificar Cuenta"
                                        data-id="<?php echo $cuenta['id_cuenta']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($cuenta['nombre_banco']); ?>"
                                        data-numero="<?php echo htmlspecialchars($cuenta['numero_cuenta']); ?>"
                                        data-rif="<?php echo htmlspecialchars($cuenta['rif_cuenta']); ?>"
                                        data-telefono="<?php echo htmlspecialchars($cuenta['telefono_cuenta']); ?>"
                                        data-correo="<?php echo htmlspecialchars($cuenta['correo_cuenta']); ?>"
                                        data-metodos="<?php echo htmlspecialchars($cuenta['metodos']); ?>">
                                        <img src="assets/img/pencil.svg">
                                    </button>
                                    <button class="btn-eliminar"
                                        title="Eliminar Cuenta"
                                        data-id="<?php echo $cuenta['id_cuenta']; ?>">
                                        <img src="assets/img/circle-x.svg">
                                    </button>
                                </ul>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="modalDetallesCuenta" class="modal-detalles" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="titulo-form">Detalles de la Cuenta</h5>
                        <button type="button" class="close" id="cerrarModalDetalles">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group fila-dato">
                            <label>ID:</label>
                            <p id="detalle-id"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>Nombre del Banco:</label>
                            <p id="detalle-nombre"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>Número de Cuenta:</label>
                            <p id="detalle-numero"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>RIF:</label>
                            <p id="detalle-rif"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>Teléfono:</label>
                            <p id="detalle-telefono"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>Correo:</label>
                            <p id="detalle-correo"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>Métodos de Pago:</label>
                            <p id="detalle-metodo"></p>
                        </div>
                        <div class="form-group fila-dato">
                            <label>Estatus:</label>
                            <p id="detalle-estatus"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade modal-modificar" id="modificarCuentaModal" tabindex="-1" role="dialog"
            aria-labelledby="modificarCuentaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <form id="modificarCuenta" method="POST" novalidate>
                        <div class="modal-header">
                            <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Cuenta" data-contexto="modificar">
                                <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                            </button>
                            <h5 class="titulo-form" id="modificarCuentaModalLabel">Modificar Cuenta Bancaria</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="modificar_id_cuenta" name="id_cuenta">
                            <input type="hidden" name="accion" value="modificar">
                            <div class="form-group">
                                <label for="modificar_nombre_banco">Nombre del Banco</label>
                                <input type="text" class="form-control" id="modificar_nombre_banco" name="nombre_banco"
                                    maxlength="20" required>
                                <span class="span-value-modal" id="smnombre_banco"></span>
                            </div>
                            <div class="form-group">
                                <label for="modificar_numero_cuenta">Número de Cuenta</label>
                                <input type="text" class="form-control" id="modificar_numero_cuenta" name="numero_cuenta"
                                    maxlength="23" required>
                                <span class="span-value-modal" id="smnumero_cuenta"></span>
                            </div>
                            <div class="form-group">
                                <label for="modificar_rif_cuenta">RIF</label>
                                <input type="text" class="form-control" id="modificar_rif_cuenta" name="rif_cuenta"
                                    maxlength="12" required>
                                <span class="span-value-modal" id="smrif_cuenta"></span>
                            </div>
                            <div class="form-group">
                                <label for="modificar_telefono_cuenta">Teléfono</label>
                                <input type="text" class="form-control" id="modificar_telefono_cuenta"
                                    name="telefono_cuenta" maxlength="13" required>
                                <span class="span-value-modal" id="smtelefono_cuenta"></span>
                            </div>
                            <div class="form-group">
                                <label for="modificar_correo_cuenta">Correo</label>
                                <input type="email" class="form-control" id="modificar_correo_cuenta" name="correo_cuenta"
                                    maxlength="50" required>
                                <span class="span-value-modal" id="smcorreo_cuenta"></span>
                            </div>
                            <div class="form-group">
                                <label for="tipo_moneda_modificar">Tipo de Moneda</label>
                                <select class="form-select" id="tipo_moneda_modificar" name="tipo_moneda" required>
                                    <option value="bs" selected>Bolívares</option>
                                    <option value="usd">Dólares</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="label-checkbox" id="titulo-metodos-pago">Métodos de Pago Aceptados</label>
                                <div class="btn-group metodos-bs_modificar" role="group" aria-label="Métodos en Bs">
                                    <input type="checkbox" class="btn-check" value="Pago Movil" id="pagoMovil_modificar" name="metodos_pago[]" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="pagoMovil_modificar">Pago Móvil</label>

                                    <input type="checkbox" class="btn-check" id="transferencia_modificar" value="Transferencia" name="metodos_pago[]" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="transferencia_modificar">Transferencia</label>
                                </div>
                                <div class="btn-group metodos-usd_modificar d-none" role="group" aria-label="Métodos en USD">
                                    <input type="checkbox" class="btn-check" value="Zelle" id="zelle_modificar" name="metodos_pago[]" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="zelle_modificar">Zelle</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Modificar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="reporte-parametros">
                <div class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                    <label for="fechaInicioCuentas" class="title-select">Fecha inicio:</label>
                    <input type="date" id="fechaInicioCuentas" class="selector-reporte">
                    <label for="fechaFinCuentas" class="title-select">Fecha fin:</label>
                    <input type="date" id="fechaFinCuentas" class="selector-reporte">
                    <label for="agruparPor" class="title-select">Agrupar por:</label>
                    <select id="agruparPor" class="selector-reporte">
                        <option value="metodos">Método de Pago</option>
                        <option value="nombre_banco">Banco</option>
                        <option value="nombre">Cliente</option>
                        <option value="estatus">Estatus</option>
                    </select>
                    <label for="tipoGraficaCuentas" class="title-select">Tipo de gráfica:</label>
                    <select id="tipoGraficaCuentas" class="selector-reporte">
                        <option value="bar">Barras</option>
                        <option value="line">Líneas</option>
                        <option value="pie">Pastel</option>
                        <option value="doughnut">Donas</option>
                        <option value="polarArea">Área Polar</option>
                    </select>
                    <div>
                        <button id="generarReporteCuentasBtn" class="btn btn-primary">Generar Reporte</button>
                        <button id="descargarPDFCuentas" class="btn btn-primary">Descargar PDF</button>
                    </div>
                </div>
            </div>

            <div class="reporte-container" style="max-width:1200px; margin:40px auto; background:#fff; padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                <h3 class="titulo-form">Reporte de Cuentas Bancarias</h3>
                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center;">
                    <div style="flex:1; min-width:320px; text-align:center;">
                        <div class="grafica-container" style="max-width:600px; margin:0 auto 24px auto;">
                            <canvas id="graficoCuentas" width="600" height="400"></canvas>
                        </div>
                    </div>
                    <div style="flex:2; min-width:320px;">
                        <div id="tablaReporteCuentas"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'footer.php'; ?>
        <script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets/public/js/jquery-3.7.1.min.js"></script>
        <script src="assets/javascript/cuenta.js"></script>
        <script src="assets/public/js/jquery.dataTables.min.js"></script>
        <script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
        <script src="assets/public/js/datatable.js"></script>

        <script>
            const cuentasReportes = <?= json_encode($cuentasReportes ?? []); ?>;

            function filtrarPorFechas(datos, inicio, fin) {
                return datos.filter(d => {
                    if (!d.fecha) return false;
                    if (inicio && d.fecha < inicio) return false;
                    if (fin && d.fecha > fin) return false;
                    return true;
                });
            }

            function agruparPorCampo(datos, campo) {
                const agrupado = {};
                datos.forEach(d => {
                    const key = d[campo] || "Desconocido";
                    if (!agrupado[key]) agrupado[key] = 0;
                    agrupado[key] += parseFloat(d.monto);
                });
                return agrupado;
            }

            function generarReporteCuentas() {
                // Verifica que cuentasReportes esté definida y sea un array
                if (typeof cuentasReportes === 'undefined' || !Array.isArray(cuentasReportes)) {
                    document.getElementById("tablaReporteCuentas").innerHTML = "<div style='color:red;text-align:center;'>No hay datos para mostrar</div>";
                    if (window.reporteCuentasChart) window.reporteCuentasChart.destroy();
                    return;
                }

                const fechaInicio = document.getElementById('fechaInicioCuentas').value;
                const fechaFin = document.getElementById('fechaFinCuentas').value;
                const campo = document.getElementById('agruparPor').value;
                const tipoGrafica = document.getElementById('tipoGraficaCuentas').value;

                // Validación de fechas
                if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                    Swal.fire('Error', 'La fecha inicial no puede ser mayor que la fecha final', 'error');
                    return;
                }

                // Filtrar
                let datosFiltrados = filtrarPorFechas(cuentasReportes, fechaInicio, fechaFin);

                // Ordenar por fecha descendente (más reciente primero)
                datosFiltrados.sort((a, b) => b.fecha.localeCompare(a.fecha));
                
                // Agrupar
                const agrupado = agruparPorCampo(datosFiltrados, campo);
                const labels = Object.keys(agrupado);
                const valores = Object.values(agrupado);

                // Colores
                const colores = labels.map((_, i) => `hsl(${(360 / (labels.length || 1)) * i},70%,60%)`);
                const borderColores = colores.map(color => color.replace('60%)', '40%)'));

                // Gráfica
                const canvas = document.getElementById("graficoCuentas");
                if (!canvas) return; // Si no existe el canvas, no hacer nada
                const ctx = canvas.getContext("2d");
                if (window.reporteCuentasChart) window.reporteCuentasChart.destroy();

                window.reporteCuentasChart = new Chart(ctx, {
                    type: tipoGrafica,
                    data: {
                        labels: labels.length ? labels : ["Sin datos"],
                        datasets: [{
                            label: `Montos agrupados por ${campo}`,
                            data: valores.length ? valores : [0],
                            backgroundColor: colores,
                            borderColor: borderColores,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true, position: "top" },
                            title: { display: true, text: "Análisis Financiero por " + campo }
                        }
                    }
                });

                // Tabla detallada
                let tablaHtml = `
                    <table class="table table-bordered table-striped" style="margin:0 auto; width:100%;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Cédula</th>
                                <th>Banco</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                if (datosFiltrados.length === 0) {
                    tablaHtml += `<tr><td colspan="7" style="text-align:center;color:#888;">No hay datos para mostrar</td></tr>`;
                } else {
                    datosFiltrados.forEach(d => {
                        // Formato DD/MM/YYYY
                        let fechaFormateada = d.fecha;
                        if (fechaFormateada && fechaFormateada.length === 10) {
                            const [y, m, day] = fechaFormateada.split('-');
                            fechaFormateada = `${day}/${m}/${y}`;
                        }
                        tablaHtml += `
                            <tr>
                                <td>${fechaFormateada}</td>
                                <td>${d.nombre || '-'}</td>
                                <td>${d.cedula || '-'}</td>
                                <td>${d.nombre_banco || "-"}</td>
                                <td>${d.metodos || '-'}</td>
                                <td>${parseFloat(d.monto).toFixed(2)}</td>
                                <td>${d.estatus || '-'}</td>
                            </tr>
                        `;
                    });
                }

                tablaHtml += "</tbody></table>";
                document.getElementById("tablaReporteCuentas").innerHTML = tablaHtml;
            }

            // Event Listeners para Cuentas
            document.getElementById('generarReporteCuentasBtn').addEventListener('click', generarReporteCuentas);
            document.getElementById('fechaInicioCuentas').addEventListener('change', generarReporteCuentas);
            document.getElementById('fechaFinCuentas').addEventListener('change', generarReporteCuentas);
            document.getElementById('agruparPor').addEventListener('change', generarReporteCuentas);
            document.getElementById('tipoGraficaCuentas').addEventListener('change', generarReporteCuentas);

            document.getElementById('descargarPDFCuentas').addEventListener('click', function () {
                if (typeof jspdf === 'undefined') {
                    Swal.fire('Error', 'La librería jsPDF no está cargada', 'error');
                    return;
                }
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'pt',
                    format: 'a4'
                });
                const reporte = document.querySelector('#tablaReporteCuentas').closest('.reporte-container');
                html2canvas(reporte, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = pageWidth - 40;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                    doc.save('Reporte_Cuentas.pdf');
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                generarReporteCuentas();
            });
        </script>

        <button 
            class="btn-grafica"
            title="Visualizar Reportes"
            onclick="window.location.href='?pagina=reporteFinanzas'">
            <img src="assets/img/grafic.png" alt="Reportes" width="30" height="30">
        </button>
        
        <button 
            class="btn-ayuda"
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