<?php require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 8;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Gestionar Proveedores</title>
    
    <style>
        :root {
            --primary-color: #1f66df;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .report-container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            padding: 32px 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .report-header {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light-bg);
            padding-bottom: 15px;
        }
        
        .parameters-container {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
        }
        
        .report-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .report-section:hover {
            box-shadow: var(--box-shadow);
        }
        
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .chart-canvas {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }
        
        .chart-table {
            flex: 2;
            min-width: 400px;
        }
        
        .btn-download {
            display: block;
            margin: 20px auto;
            width: 250px;
        }
        
        .error-message {
            color: #dc3545;
            text-align: center;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .form-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: center;
        }
        
        .form-inline .form-control,
        .form-inline .form-select {
            width: auto;
            min-width: 160px;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            margin: 30px 0;
        }
        
        .stock-negativo {
            color: #dc3545;
            font-weight: bold;
        }
        
        .stock-bajo {
            color: #ffc107;
            font-weight: bold;
        }
        
        .stock-ok {
            color: #198754;
            font-weight: bold;
        }
    </style>
</head>

<body class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<!-- Modal de Registro de Proveedor -->
<div class="modal fade modal-registrar" id="registrarProveedorModal" tabindex="-1" role="dialog" 
aria-labelledby="registrarProveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="incluirproveedor" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Proveedor" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="registrarProveedorModalLabel">Incluir Proveedor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    <div class="grupo-form">
                        <div class="grupo-interno">
                            <label for="nombre_proveedor">Nombre del Proveedor*</label>
                            <input type="text" placeholder="Nombre: Proveedor" class="control-form" id="nombre_proveedor" name="nombre_proveedor" maxlength="50" required>
                            <span class="span-value" id="snombre_proveedor"></span>
                        </div>
                        <div class="grupo-interno">
                            <label for="rif_proveedor">RIF del Proveedor*</label>                     
                            <input type="text" placeholder="RIF: Proveedor" class="control-form" id="rif_proveedor" name="rif_proveedor" maxlength="12" required>
                            <span class="span-value" id="srif_proveedor"></span>
                        </div>
                        </div>
                        <div class="grupo-form">
                        <div class="grupo-interno">
                            <label for="nombre_representante">Nombre del Representante*</label>
                            <input type="text" placeholder="Nombre: Representante" class="control-form" id="nombre_representante" name="nombre_representante" maxlength="50" required>
                            <span class="span-value" id="snombre_representante"></span>
                        </div>
                        <div class="grupo-interno">
                            <label for="rif_representante">RIF del Representante*</label>
                            <input type="text" placeholder="RIF: Representante" class="control-form" id="rif_representante" name="rif_representante" maxlength="12" required>
                            <span class="span-value" id="srif_representante"></span>
                        </div>
                    </div>
                    <div class="envolver-form">
                        <label for="correo_proveedor">Correo*</label>
                        <input type="text" placeholder="ejemplo@gmail.com" class="control-form" id="correo_proveedor" name="correo_proveedor" maxlength="50" required>
                        <span class="span-value" id="scorreo_proveedor"></span>
                    </div>
                    <div class="envolver-form">
                        <label for="direccion_proveedor">Dirección*</label>
                        <textarea placeholder="Estado/Ciudad/Calle o Avenida..." class="form-control" id="direccion_proveedor" name="direccion_proveedor" rows="2" maxlength="100" required></textarea>
                        <span class="span-value" id="sdireccion_proveedor"></span>
                    </div>
                    <div class="grupo-form">
                        <div class="grupo-interno">
                            <label for="telefono_1">Teléfono Principal*</label>
                            <input type="text" placeholder="0200-000-0000" class="control-form" id="telefono_1" name="telefono_1" maxlength="13" required>
                            <span class="span-value" id="stelefono_1"></span>
                        </div>
                        <div class="grupo-interno">
                            <label for="telefono_2">Teléfono Secundario*</label>
                            <input type="text" placeholder="0400-000-0000" class="control-form" id="telefono_2" name="telefono_2" maxlength="13" required>
                            <span class="span-value" id="stelefono_2"></span>
                        </div>
                    </div>
                    <div class="envolver-form">
                        <label for="observacion">Observación*</label>
                        <textarea class="form-control" placeholder="Escriba alguna observación" id="observacion" name="observacion" maxlength="100" rows="2"></textarea>
                        <span class="span-value" id="sobservacion"></span>
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

<!-- Tabla Principal de Proveedores -->
<div class="contenedor-tabla">
    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>LISTA DE PROVEEDORES</h3>
        <div class="filtro-status">
			<label for="filtro-estatus-proveedores">Mostrar:</label>
			<select id="filtro-estatus-proveedores" class="form-select">
				<option value="todos" selected>Todos</option>
				<option value="habilitado">Habilitados</option>
				<option value="inhabilitado">Inhabilitados</option>
			</select>
		</div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>Nombre <br> (Proveedor)</th>
                <th>RIF (Proveedor)</th>
                <th>Nombre <br> (Representante)</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Teléfono <br> Principal</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($proveedores as $proveedor): ?>
            <tr data-id="<?php echo $proveedor['id_proveedor']; ?>">
                <td>
                    <span class="campo-nombres">
                    <?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-tex-num">
                    <?php echo htmlspecialchars($proveedor['rif_proveedor']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-nombres">
                    <?php echo htmlspecialchars($proveedor['nombre_representante']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-tex-num">
                    <?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>
                    </span>
                </td> 
                <td>
                    <span class="campo-nombres">
                    <?php echo htmlspecialchars($proveedor['direccion_proveedor']); ?>
                    </span>
                </td> 
                <td>
                    <span class="campo-numeros">
                    <?php echo htmlspecialchars($proveedor['telefono_1']); ?>
                    </span>
                </td>
                <td class="campo-estado">
                    <span 
                        class="campo-estatus <?php echo ($proveedor['estado'] == 'habilitado') ? 'habilitado' : 'inhabilitado'; ?>" 
                        data-id="<?php echo $proveedor['id_proveedor']; ?>"
                        style="cursor: pointer;"
                        title="Cambiar Estatus">
                        <?php echo htmlspecialchars($proveedor['estado']); ?>
                    </span>
                </td>
                <td>
                    <ul>
                        <button class="btn-detalle"
                            title="Ver Detalles"
                            data-iddtl="<?php echo $proveedor['id_proveedor']; ?>"
                            data-nombreproveedordtl="<?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>"
                            data-rifproveedordtl="<?php echo htmlspecialchars($proveedor['rif_proveedor']); ?>"
                            data-nombrerepresentantedtl="<?php echo htmlspecialchars($proveedor['nombre_representante']); ?>"
                            data-rifrepresentantedtl="<?php echo htmlspecialchars($proveedor['rif_representante']); ?>"
                            data-correodtl="<?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>"
                            data-direcciondtl="<?php echo htmlspecialchars($proveedor['direccion_proveedor']); ?>"
                            data-telefono1dtl="<?php echo htmlspecialchars($proveedor['telefono_1']); ?>"
                            data-telefono2dtl="<?php echo htmlspecialchars($proveedor['telefono_2']); ?>"
                            data-observaciondtl="<?php echo htmlspecialchars($proveedor['observacion']); ?>">
                            <img src="assets/img/eye.svg">
                        </button>
                        <button class="btn-modificar"
                            id="btnModificarProveedor"
                            title="Modificar Proveedor"
                            data-id="<?php echo $proveedor['id_proveedor']; ?>"
                            data-nombre-proveedor="<?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>"
                            data-rif-proveedor="<?php echo htmlspecialchars($proveedor['rif_proveedor']); ?>"
                            data-nombre-representante="<?php echo htmlspecialchars($proveedor['nombre_representante']); ?>"
                            data-rif-representante="<?php echo htmlspecialchars($proveedor['rif_representante']); ?>"
                            data-correo-proveedor="<?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>"
                            data-direccion-proveedor="<?php echo htmlspecialchars($proveedor['direccion_proveedor']); ?>"
                            data-telefono-1="<?php echo htmlspecialchars($proveedor['telefono_1']); ?>"
                            data-telefono-2="<?php echo htmlspecialchars($proveedor['telefono_2']); ?>"
                            data-observacion="<?php echo htmlspecialchars($proveedor['observacion']); ?>">
                            <img src="assets/img/pencil.svg">
                        </button>
                        <button class="btn-eliminar" 
                            title="Eliminar Proveedor"
                            data-id="<?php echo $proveedor['id_proveedor']; ?>">
                            <img src="assets/img/circle-x.svg">
                        </button>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade modal-modificar" id="modificarProveedorModal" tabindex="-1" role="dialog"
aria-labelledby="modificarProveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="FormModificarProveedor" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Proveedor" data-contexto="modificar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="modificarProveedorModalLabel">Modificar Proveedor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="modificar_id_proveedor" name="id_proveedor">
                    <input type="hidden" name="accion" value="modificar">

                    <div class="form-group">
                        <label for="modificar_nombre_proveedor">Nombre del Proveedor</label>
                        <input type="text" class="form-control" id="modificar_nombre_proveedor" name="nombre_proveedor" maxlength="50" required>
                        <span class="span-value-modal" id="smnombre_proveedor"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_rif_proveedor">RIF del Proveedor</label>
                        <input type="text" class="form-control" id="modificar_rif_proveedor" name="rif_proveedor" maxlength="12" required>
                        <span class="span-value-modal" id="smrif_proveedor"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_nombre_representante">Nombre del Representante</label>
                        <input type="text" class="form-control" id="modificar_nombre_representante" name="nombre_representante" maxlength="50" required>
                        <span class="span-value-modal" id="smnombre_representante"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_rif_representante">RIF del Representante</label>
                        <input type="text" class="form-control" id="modificar_rif_representante" name="rif_representante" maxlength="12" required>
                        <span class="span-value-modal" id="smrif_representante"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_correo_proveedor">Correo</label>
                        <input type="email" class="form-control" id="modificar_correo_proveedor" name="correo_proveedor" maxlength="50" required>
                        <span class="span-value-modal" id="smcorreo_proveedor"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_direccion_proveedor">Dirección</label>
                        <textarea class="form-control" id="modificar_direccion_proveedor" name="direccion_proveedor" maxlength="100" rows="2" required></textarea>
                        <span class="span-value-modal" id="smdireccion_proveedor"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_telefono_1">Teléfono Principal</label>
                        <input type="text" class="form-control" id="modificar_telefono_1" name="telefono_1" maxlength="13" required>
                        <span class="span-value-modal" id="smtelefono_1"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_telefono_2">Teléfono Secundario</label>
                        <input type="text" class="form-control" id="modificar_telefono_2" name="telefono_2" maxlength="13" required>
                        <span class="span-value-modal" id="smtelefono_2"></span>
                    </div>

                    <div class="form-group">
                        <label for="modificar_observacion">Observación</label>
                        <textarea class="form-control" id="modificar_observacion" name="observacion" maxlength="100" rows="2"></textarea>
                        <span class="span-value-modal" id="smobservacion"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Modificar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales -->
<div id="modalDetallesProveedor" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form">Detalles del Proveedor</h5>
                <button type="button" class="close" id="cerrarModalDetalles">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group fila-dato">
                    <label>Nombre (Proveedor):</label>
                    <p id="detalle-nombre-proveedor"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>RIF (Proveedor):</label>
                    <p id="detalle-rif-proveedor"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Nombre (Representante):</label>
                    <p id="detalle-nombre-representante"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>RIF (Representante):</label>
                    <p id="detalle-rif-representante"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Correo:</label>
                    <p id="detalle-correo"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Dirección:</label>
                    <p id="detalle-direccion"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Teléfono Principal:</label>
                    <p id="detalle-telefono-1"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Teléfono Secundario:</label>
                    <p id="detalle-telefono-2"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Observación:</label>
                    <p id="detalle-observacion"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Proveedores</h2>
        <p class="texto-p">Analice el desempeño y suministro de proveedores</p>
    </div>

    <!-- Parámetros del Reporte -->
    <div class="parameters-container">
        <div class="form-inline">
            <div class="form-group">
                <label for="fechaInicioProv" class="title-select">Fecha inicio:</label>
                <input type="date" id="fechaInicioProv" class="selector-reporte">
            </div>
            <div class="form-group">
                <label for="fechaFinProv" class="title-select">Fecha fin:</label>
                <input type="date" id="fechaFinProv" class="selector-reporte">
            </div>
            <div class="form-group">
                <label for="tipoGrafica" class="title-select">Tipo de gráfica:</label>
                <select id="tipoGrafica" class="selector-reporte">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Donas</option>
                    <option value="polarArea">Área Polar</option>
                </select>
            </div>
            <div class="form-group">
                <label for="selectReporte" class="title-select">Tipo de Reporte:</label>
                <select id="selectReporte" class="selector-reporte">
                    <option value="todos">Todos los Reportes</option>
                    <option value="reporteSuministro">Productos Suministrados por Proveedor</option>
                    <option value="reporteRanking">Ranking de Proveedores</option>
                    <option value="reporteComparacion">Comparación Mensual de Suministros</option>
                    <option value="reporteDependencia">Dependencia de Proveedores</option>
                </select>
            </div>
            <div id="parametrosIndividualesProv"></div>
            <div class="form-group">
                <button id="generarReporteBtn" class="btn btn-primary">Generar Reporte</button>
            </div>
        </div>
    </div>

    <!-- Spinner de carga -->
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p>Generando reportes...</p>
    </div>

    <!-- Mensaje de error -->
    <div id="errorMessage" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

    <!-- Reporte 1: Suministro por Proveedor -->
    <div class="report-section" id="reporteSuministro">
        <h3 class="titulo-form">Productos Suministrados por Proveedor</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoSuministro" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaSuministro"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFProveedores('reporteSuministro', 'Reporte_Suministro_Proveedores.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenProveedores('graficoSuministro', 'Grafico_Suministro_Proveedores.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 2: Ranking de Proveedores -->
    <div class="report-section" id="reporteRanking">
        <h3 class="titulo-form">Ranking de Proveedores</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoRanking" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaRanking"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFProveedores('reporteRanking', 'Reporte_Ranking_Proveedores.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenProveedores('graficoRanking', 'Grafico_Ranking_Proveedores.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 3: Comparación Mensual -->
    <div class="report-section" id="reporteComparacion">
        <h3 class="titulo-form">Comparación Mensual de Suministros</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoComparacion" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaComparacion"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFProveedores('reporteComparacion', 'Reporte_Comparacion_Proveedores.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenProveedores('graficoComparacion', 'Grafico_Comparacion_Proveedores.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 4: Dependencia de Proveedores -->
    <div class="report-section" id="reporteDependencia">
        <h3 class="titulo-form">Dependencia de Proveedores</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoDependencia" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaDependencia"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-primary btn-download" onclick="descargarPDFProveedores('reporteDependencia', 'Reporte_Dependencia_Proveedores.pdf')">
                Descargar PDF
            </button>
            <button class="btn btn-primary btn-download" onclick="descargarImagenProveedores('graficoDependencia', 'Grafico_Dependencia_Proveedores.png')">
                Descargar Gráfico
            </button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/javascript/proveedor.js"></script>

<script>
    // Datos parametrizados desde PHP
    let reporteSuministro = <?php echo json_encode($reporteSuministroProveedores ?? []); ?>;
    let reporteRanking = <?php echo json_encode($reporteRankingProveedores ?? []); ?>;
    let reporteComparacion = <?php echo json_encode($reporteComparacion ?? []); ?>;
    let reporteDependencia = <?php echo json_encode($reporteDependencia ?? []); ?>;

    // Variables globales de gráficos
    let graficoSuministro = null, graficoRanking = null, graficoComparacion = null, graficoDependencia = null;
    // Últimos parámetros seleccionados (para usar en tablas)
    let ultimoParamsProv = {};

    console.log('Datos cargados:', {
        suministro: reporteSuministro,
        ranking: reporteRanking,
        comparacion: reporteComparacion,
        dependencia: reporteDependencia
    });

    // Función para asegurar que el gráfico esté renderizado
    function asegurarRenderizadoGrafico(canvasId, callback) {
        const canvas = document.getElementById(canvasId);
        const chart = Chart.getChart(canvasId);
        
        if (chart) {
            // Forzar actualización del gráfico
            chart.update('none');
            
            // Pequeño delay para asegurar el renderizado
            setTimeout(() => {
                callback();
            }, 100);
        } else {
            callback();
        }
    }

    // Generador de colores
    function generarColores(n) {
        return Array.from({length: n}, (_, i) => {
            const hue = (360 / n) * i;
            return `hsl(${hue}, 70%, 60%)`;
        });
    }

    // ====== Parámetros dinámicos por reporte (Proveedor/TopN/Mes/Año) ======
    function distinctProv(arr, key) {
        const m = new Map();
        (arr||[]).forEach(d => {
            const v = d && d[key];
            if (v !== undefined && v !== null) {
                const raw = String(v).trim();
                const norm = raw.toLowerCase();
                if (!m.has(norm)) m.set(norm, raw);
            }
        });
        return Array.from(m.values());
    }

    function buildParametrosUIProv() {
        const cont = document.getElementById('parametrosIndividualesProv');
        const tipo = document.getElementById('selectReporte').value;
        if (!cont) return;
        let html = '';

        // Lista unificada de proveedores desde datasets disponibles
        const proveedoresNombres = Array.from(new Set([
            ...distinctProv(reporteSuministro, 'nombre_proveedor'),
            ...distinctProv(reporteRanking, 'nombre_proveedor'),
            ...distinctProv(reporteDependencia, 'nombre_proveedor')
        ])).sort();

        if (tipo === 'reporteSuministro' || tipo === 'reporteRanking' || tipo === 'reporteDependencia') {
            html += `
            <div class="form-group" style="min-width:220px">
                <label>Proveedor</label>
                <select id="paramProveedor" class="form-select">
                    <option value="">Todos</option>
                    ${proveedoresNombres.map(p=>`<option value="${p}">${p}</option>`).join('')}
                </select>
            </div>`;
        }

        if (tipo === 'reporteRanking') {
            html += `
            <div class="form-group" style="min-width:140px">
                <label>Top</label>
                <select id="paramTopN" class="form-select">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="50">Top 50</option>
                    <option value="0">Todos</option>
                </select>
            </div>`;
        }

        if (tipo === 'reporteComparacion') {
            html += `
            <div class="form-group" style="min-width:160px">
                <label>Mes</label>
                <select id="paramMesProv" class="form-select">
                    <option value="">Todos</option>
                    ${['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
                        .map((name,idx)=>`<option value="${idx+1}">${name}</option>`).join('')}
                </select>
            </div>
            <div class="form-group" style="min-width:140px">
                <label>Año</label>
                <input id="paramAnioProv" class="form-control" type="number" placeholder="Ej: 2025" />
            </div>`;
        }

        cont.innerHTML = html;
    }

    function getParametrosSeleccionadosProv() {
        return {
            proveedor: document.getElementById('paramProveedor')?.value || '',
            topN: parseInt(document.getElementById('paramTopN')?.value || '0', 10) || 0,
            mes: document.getElementById('paramMesProv')?.value || '',
            anio: document.getElementById('paramAnioProv')?.value || ''
        };
    }

    function aplicarParametrosProveedor(datos, params) {
        if (!datos) return [];
        const prov = params.proveedor ? String(params.proveedor).trim() : '';
        if (!prov) return datos;
        const norm = prov.toLowerCase();
        return datos.filter(d => String(d.nombre_proveedor || '').trim().toLowerCase() === norm);
    }

    // Filtro por fechas genérico (incluye meses en español)
    function filtrarPorFechas(datos, inicio, fin) {
        const inicioDate = inicio ? new Date(inicio + 'T00:00:00') : null;
        const finDate = fin ? new Date(fin + 'T23:59:59') : null;
        const mesesES = { 'enero':0,'febrero':1,'marzo':2,'abril':3,'mayo':4,'junio':5,'julio':6,'agosto':7,'septiembre':8,'setiembre':8,'octubre':9,'noviembre':10,'diciembre':11 };

        return (datos || []).filter(d => {
            let f = null;
            const cruda = d.fecha || d.fecha_registro || d.created_at || d.fecha_compra || null;
            if (cruda) {
                f = new Date(cruda);
            }
            if (!f || isNaN(f)) {
                const mesStr = (d.mes || d.Mes || d.MES || '').toString().trim();
                if (mesStr) {
                    const idx = mesesES[mesStr.toLowerCase()];
                    if (idx !== undefined) {
                        const year = parseInt(d.anio || d.año || d.year || new Date().getFullYear(), 10);
                        f = new Date(year, idx, 1);
                    }
                }
            }
            if (!f || isNaN(f)) return true;
            if (inicioDate && f < inicioDate) return false;
            if (finDate && f > finDate) return false;
            return true;
        });
    }

    // Función render general mejorada
    function renderReporte(datos, tipoReporte, canvasId, tablaId, titulo, tipoGrafica, esHorizontal = false) {
        console.log(`Renderizando ${tipoReporte} en ${canvasId}`, datos);
        
        const tablaElement = document.getElementById(tablaId);
        const canvasElement = document.getElementById(canvasId);
        
        if (!tablaElement || !canvasElement) {
            console.error(`Elemento no encontrado: ${tablaId} o ${canvasId}`);
            return;
        }

        if (!datos || datos.length === 0) {
            tablaElement.innerHTML = `<div class="alert alert-warning text-center">No hay datos disponibles</div>`;
            // Limpiar canvas
            const ctx = canvasElement.getContext('2d');
            ctx.clearRect(0, 0, canvasElement.width, canvasElement.height);
            return;
        }

        let labels = [];
        let data = [];
        let total = 0;
        let tablaHtml = '';

        try {
            switch(tipoReporte) {
                case 'suministro':
                    labels = datos.map(d => d.nombre_proveedor || 'Sin nombre');
                    data = datos.map(d => parseInt(d.cantidad) || 0);
                    total = data.reduce((a,b) => a+b, 0);
                    tablaHtml = crearTablaSuministro(datos,total);
                    break;
                case 'ranking':
                    // Aceptar distintos campos para total (según consulta backend)
                    datos = datos.map(d => ({
                        ...d,
                        _total: Number(
                            (d.total !== undefined ? d.total :
                            d.monto_total_pagado !== undefined ? d.monto_total_pagado :
                            d.cantidad !== undefined ? d.cantidad :
                            d.value !== undefined ? d.value : 0)
                        ) || 0
                    })).sort((a,b) => b._total - a._total);
                    labels = datos.map(d => d.nombre_proveedor || 'Sin nombre');
                    data = datos.map(d => d._total);
                    total = data.reduce((a,b) => a+b,0);
                    tablaHtml = crearTablaRanking(datos,total);
                    break;
                case 'comparacion':
                    labels = datos.map(p => p.nombre_proveedor || 'Sin nombre');
                    data = datos.map(p => parseFloat(p.precio_promedio) || 0);
                    titulo = `Comparación: ${datos.nombre_producto || 'Producto'}`;
                    tablaHtml = crearTablaComparacion(datos);
                    break;
                case 'dependencia':
                    labels = datos.map(d => d.nombre_proveedor || 'Sin nombre');
                    data = datos.map(d => parseFloat(d.dependencia_porcentaje) || 0);
                    total = 100;
                    tablaHtml = crearTablaDependencia(datos);
                    break;
            }

            tablaElement.innerHTML = tablaHtml;
            const ctx = canvasElement.getContext('2d');

            // Destruir gráfico previo
            switch(canvasId) {
                case 'graficoSuministro':
                    if (graficoSuministro) graficoSuministro.destroy();
                    break;
                case 'graficoRanking':
                    if (graficoRanking) graficoRanking.destroy();
                    break;
                case 'graficoComparacion':
                    if (graficoComparacion) graficoComparacion.destroy();
                    break;
                case 'graficoDependencia':
                    if (graficoDependencia) graficoDependencia.destroy();
                    break;
            }

            const colores = generarColores(labels.length);

            let options = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        display: !['line', 'bar'].includes(tipoGrafica), 
                        position: 'bottom' 
                    },
                    title: { 
                        display: true, 
                        text: titulo,
                        font: { size: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                // Extraer valor numérico robusto (soporta datos como número u objeto {x,y})
                                const raw = context.raw;
                                let value = 0;
                                if (typeof context.parsed === 'number') {
                                    value = context.parsed;
                                } else if (context.parsed && typeof context.parsed === 'object') {
                                    value = Number((context.parsed.x ?? context.parsed.y ?? raw ?? 0));
                                } else {
                                    value = Number(raw ?? 0);
                                }
                                // Sumar total de forma segura
                                const nums = (context.dataset.data || []).map(d => {
                                    if (typeof d === 'number') return d;
                                    if (d && typeof d === 'object') return Number(d.x ?? d.y ?? 0);
                                    const n = Number(d ?? 0); return isNaN(n) ? 0 : n;
                                });
                                const total = nums.reduce((a,b)=> a + (isNaN(b)?0:b), 0);
                                const showPct = total > 0;
                                const pct = showPct ? ` (${((value/total)*100).toFixed(1)}%)` : '';
                                const valFmt = isNaN(value) ? 0 : value;
                                return `${valFmt.toLocaleString(undefined,{maximumFractionDigits:2})}${pct}`;
                            }
                        }
                    }
                }
            };
            
            if (esHorizontal) options.indexAxis = 'y';

            // Crear nuevo gráfico
            const nuevoGrafico = new Chart(ctx, {
                type: tipoGrafica,
                data: { 
                    labels: labels, 
                    datasets: [{ 
                        label: titulo, 
                        data: data, 
                        backgroundColor: colores,
                        borderColor: tipoGrafica === 'line' ? colores : undefined,
                        borderWidth: tipoGrafica === 'line' ? 2 : 1
                    }] 
                },
                options: options
            });

            // Asignar a la variable global correcta
            switch(canvasId) {
                case 'graficoSuministro': graficoSuministro = nuevoGrafico; break;
                case 'graficoRanking': graficoRanking = nuevoGrafico; break;
                case 'graficoComparacion': graficoComparacion = nuevoGrafico; break;
                case 'graficoDependencia': graficoDependencia = nuevoGrafico; break;
            }

        } catch (error) {
            console.error(`Error renderizando ${tipoReporte}:`, error);
            tablaElement.innerHTML = `<div class="alert alert-danger text-center">Error al generar el reporte</div>`;
        }
    }

    // Funciones de tablas
    function crearTablaSuministro(datos,total) {
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Proveedor</th><th>Cantidad</th><th>%</th></tr></thead><tbody>`;
        datos.forEach(d => {
            let pct = total > 0 ? ((parseInt(d.cantidad)||0)/total*100).toFixed(2) : 0;
            html += `<tr><td>${d.nombre_proveedor || 'N/A'}</td><td>${d.cantidad || 0}</td><td>${pct}%</td></tr>`;
        });
        html += `<tfoot class="table-active"><tr><th>Total</th><th>${total}</th><th>100%</th></tr></tfoot>`;
        return html+`</tbody></table></div>`;
    }

    function crearTablaRanking(datos,total) {
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Proveedor</th><th>Total Compras</th><th>%</th></tr></thead><tbody>`;
        datos.forEach(d => {
            const valor = Number(d._total !== undefined ? d._total : (d.total||d.monto_total_pagado||d.cantidad||d.value||0)) || 0;
            let pct = total > 0 ? ((valor)/total*100).toFixed(2) : 0;
            html += `<tr><td>${d.nombre_proveedor || 'N/A'}</td><td>${valor.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}</td><td>${pct}%</td></tr>`;
        });
        html += `<tfoot class="table-active"><tr><th>Total</th><th>${total.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}</th><th>100%</th></tr></tfoot>`;
        return html+`</tbody></table></div>`;
    }

    function crearTablaComparacion(datos) {
        if (!datos || datos.length === 0) return '<div class="alert alert-info">No hay datos de comparación</div>';
        const nombresMes = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        // Preferir el mes seleccionado en parámetros. Si no existe, usar el rango de fechas como backup.
        let mesFallback = 'N/A';
        const selMes = ultimoParamsProv?.mes ? parseInt(ultimoParamsProv.mes,10) : null;
        if (selMes && selMes >=1 && selMes <=12) {
            mesFallback = nombresMes[selMes-1];
        } else {
            const fi = document.getElementById('fechaInicioProv')?.value || '';
            const ff = document.getElementById('fechaFinProv')?.value || '';
            if (fi && ff) {
                const d1 = new Date(fi+'T00:00:00');
                const d2 = new Date(ff+'T00:00:00');
                if (!isNaN(d1) && !isNaN(d2) && d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth()) {
                    mesFallback = nombresMes[d1.getMonth()];
                }
            }
        }

        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Mes</th><th>Producto</th><th>Proveedor</th><th>Precio Promedio</th><th>Registros</th></tr></thead><tbody>`;
        datos.forEach(p => {
            // Derivar mes desde distintos posibles campos
            let mesVal = p.mes_num || p.mes || null;
            const cruda = p.fecha || p.fecha_compra || p.fecha_registro || p.created_at || null;
            if (!mesVal && cruda) {
                const d = new Date(cruda);
                if (!isNaN(d)) {
                    mesVal = (d.getMonth()+1);
                }
            }
            if (typeof mesVal === 'string') {
                const idx = nombresMes.findIndex(n => n.toLowerCase() === mesVal.toLowerCase());
                mesVal = idx >= 0 ? (idx+1) : mesVal;
            }
            const mesNombre = (typeof mesVal === 'number') ? nombresMes[(mesVal-1)] : (mesFallback);

            html += `<tr>
                <td>${mesNombre}</td>
                <td>${p.nombre_producto || 'Producto'}</td>
                <td>${p.nombre_proveedor || 'N/A'}</td>
                <td>$${(parseFloat(p.precio_promedio)||0).toFixed(2)}</td>
                <td>${p.cantidad || 0}</td>
            </tr>`;
        });
        return html+`</tbody></table></div>`;
    }

    function crearTablaDependencia(datos) {
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Proveedor</th><th>Monto</th><th>% Dependencia</th></tr></thead><tbody>`;
        datos.forEach(d=>{
            html+=`<tr><td>${d.nombre_proveedor || 'N/A'}</td><td>$${d.monto_total_pagado || '0.00'}</td><td>${d.dependencia_porcentaje || '0.00'}%</td></tr>`;
        });
        return html+`</tbody></table></div>`;
    }

    // Selector de reportes
    function toggleReportes() {
        const seleccion = document.getElementById('selectReporte').value;
        const reportes = ['reporteSuministro','reporteRanking','reporteComparacion','reporteDependencia'];
        
        reportes.forEach(r => {
            const elemento = document.getElementById(r);
            if (elemento) {
                elemento.style.display = (seleccion === 'todos' || seleccion === r) ? 'block' : 'none';
                // Ocultar también los divisores
                const divisores = document.querySelectorAll('.divider');
                divisores.forEach(divisor => {
                    const prevSection = divisor.previousElementSibling;
                    if (prevSection && prevSection.id && prevSection.id === r) {
                        divisor.style.display = (seleccion === 'todos' || seleccion === r) ? 'block' : 'none';
                    }
                });
            }
        });
    }

    // Función para mostrar/ocultar loading
    function toggleLoading(mostrar) {
        document.getElementById('loadingSpinner').style.display = mostrar ? 'block' : 'none';
    }

    // Helper: aplicar mes/año a dataset que pueda tener fecha o campos mes/anio
    function aplicarMesAnio(datos, params){
        if(!datos) return [];
        const m = params.mes ? parseInt(params.mes,10) : null; // 1-12
        let y = params.anio ? parseInt(params.anio,10) : null;
        const currentYear = new Date().getFullYear();
        if(!(y > 0) || y > currentYear) { y = null; } // aplicar año solo si es válido y no mayor al actual
        if(!m && !y) return datos;
        return datos.filter(d=>{
            let date = null;
            const cruda = d.fecha || d.fecha_compra || d.fecha_registro || d.created_at || null;
            if(cruda){ const tmp = new Date(cruda); if(!isNaN(tmp)) date = tmp; }
            let mesCampo = d.mes_num || d.mes || null; // por si llega mes ya calculado
            let anioCampo = d.anio || d.año || d.year || null;
            if(typeof mesCampo === 'string'){ const idx = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'].indexOf(mesCampo.toLowerCase()); if(idx>=0) mesCampo = idx+1; }
            const mes = date ? (date.getMonth()+1) : (mesCampo? parseInt(mesCampo,10): null);
            const anio = date ? date.getFullYear() : (anioCampo? parseInt(anioCampo,10): null);
            if(m && mes !== m) return false;
            if(y && anio !== y) return false;
            return true;
        });
    }

    // Generar reportes mejorado
    function generarReportes() {
        console.log('Generando reportes...');
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        const inicio = document.getElementById('fechaInicioProv')?.value;
        const fin = document.getElementById('fechaFinProv')?.value;
        const seleccion = document.getElementById('selectReporte').value;
        
        toggleLoading(true);
        // Mostrar/ocultar reportes, y construir parámetros según selección
        toggleReportes();
        
        // Pequeño delay para asegurar que el DOM se actualizó
        setTimeout(() => {
            try {
                const suministroFiltrado = filtrarPorFechas(reporteSuministro, inicio, fin);
                const rankingFiltrado = filtrarPorFechas(reporteRanking, inicio, fin);
                const comparacionFiltrado = filtrarPorFechas(reporteComparacion, inicio, fin);
                const dependenciaFiltrado = filtrarPorFechas(reporteDependencia, inicio, fin);
                // Aplicar parámetros SOLO cuando se seleccione un reporte específico
                const params = getParametrosSeleccionadosProv();
                ultimoParamsProv = params; // guardar para que las tablas puedan mostrar el mes seleccionado
                let suministroFinal = suministroFiltrado;
                let rankingFinal = rankingFiltrado;
                let comparacionFinal = comparacionFiltrado;
                let dependenciaFinal = dependenciaFiltrado;

                if (seleccion === 'reporteSuministro') {
                    suministroFinal = aplicarParametrosProveedor(suministroFiltrado, params);
                } else if (seleccion === 'reporteRanking') {
                    rankingFinal = aplicarParametrosProveedor(rankingFiltrado, params);
                    if (params.topN > 0) rankingFinal = rankingFinal.slice(0, params.topN);
                } else if (seleccion === 'reporteDependencia') {
                    dependenciaFinal = aplicarParametrosProveedor(dependenciaFiltrado, params);
                } else if (seleccion === 'reporteComparacion') {
                    // Aplicar parámetros específicos de mes/año si se definieron
                    comparacionFinal = aplicarMesAnio(comparacionFiltrado, params);
                }

                if (document.getElementById('reporteSuministro').style.display === 'block') {
                    renderReporte(suministroFinal, 'suministro', 'graficoSuministro', 'tablaSuministro', 'Suministro por Proveedor', tipoGrafica, true);
                }
                if (document.getElementById('reporteRanking').style.display === 'block') {
                    renderReporte(rankingFinal, 'ranking', 'graficoRanking', 'tablaRanking', 'Ranking de Proveedores', tipoGrafica);
                }
                if (document.getElementById('reporteComparacion').style.display === 'block') {
                    renderReporte(comparacionFinal, 'comparacion', 'graficoComparacion', 'tablaComparacion', 'Comparación Mensual', tipoGrafica);
                }
                if (document.getElementById('reporteDependencia').style.display === 'block') {
                    renderReporte(dependenciaFinal, 'dependencia', 'graficoDependencia', 'tablaDependencia', 'Dependencia de Proveedores', 'doughnut');
                }
                
                document.getElementById('errorMessage').style.display = 'none';
            } catch (error) {
                console.error("Error al generar reportes:", error);
                document.getElementById('errorMessage').style.display = 'block';
            } finally {
                toggleLoading(false);
            }
        }, 100);
    }

    // Descargar PDF para PROVEEDORES con gráfica arriba y tabla abajo
    function descargarPDFProveedores(contenedorId, nombreArchivo) {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const reporte = document.getElementById(contenedorId);
            const titulo = reporte.querySelector('h3').textContent;
            
            // Agregar título al PDF
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(titulo, 20, 20);
            
            // Obtener el canvas del gráfico
            let canvasId;
            if (contenedorId === 'reporteSuministro') {
                canvasId = 'graficoSuministro';
            } else if (contenedorId === 'reporteRanking') {
                canvasId = 'graficoRanking';
            } else if (contenedorId === 'reporteComparacion') {
                canvasId = 'graficoComparacion';
            } else if (contenedorId === 'reporteDependencia') {
                canvasId = 'graficoDependencia';
            }
            
            asegurarRenderizadoGrafico(canvasId, () => {
                const canvas = document.getElementById(canvasId);
                if (canvas) {
                    // Convertir canvas a imagen
                    const imgData = canvas.toDataURL('image/png');
                    
                    // Dimensiones para la imagen en el PDF
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = pageWidth - 40;
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    
                    // Agregar imagen del gráfico al PDF
                    doc.addImage(imgData, 'PNG', 20, 30, imgWidth, imgHeight);
                    
                    // Posición Y después del gráfico
                    let currentY = 30 + imgHeight + 10;
                    
                    // Verificar si hay espacio suficiente para la tabla
                    if (currentY + 100 > doc.internal.pageSize.getHeight()) {
                        doc.addPage();
                        currentY = 20;
                    }
                    
                    // Agregar tabla como imagen
                    const tablaElement = reporte.querySelector('.chart-table');
                    if (tablaElement) {
                        html2canvas(tablaElement, {
                            scale: 2,
                            useCORS: true,
                            backgroundColor: '#ffffff'
                        }).then(tablaCanvas => {
                            const tablaImgData = tablaCanvas.toDataURL('image/png');
                            const tablaImgWidth = pageWidth - 40;
                            const tablaImgHeight = (tablaCanvas.height * tablaImgWidth) / tablaCanvas.width;
                            
                            // Verificar si necesita nueva página
                            if (currentY + tablaImgHeight > doc.internal.pageSize.getHeight()) {
                                doc.addPage();
                                currentY = 20;
                            }
                            
                            doc.addImage(tablaImgData, 'PNG', 20, currentY, tablaImgWidth, tablaImgHeight);
                            doc.save(nombreArchivo);
                        });
                    } else {
                        doc.save(nombreArchivo);
                    }
                } else {
                    alert('No se pudo encontrar el gráfico para generar el PDF.');
                }
            });
            
        } catch (error) {
            console.error("Error al generar PDF de proveedores:", error);
            alert("Error al generar el PDF. Asegúrese de que todas las librerías estén cargadas correctamente.");
        }
    }

    // Descargar imagen del gráfico para PROVEEDORES
    function descargarImagenProveedores(canvasId, nombreArchivo) {
        try {
            asegurarRenderizadoGrafico(canvasId, () => {
                const canvas = document.getElementById(canvasId);
                const link = document.createElement('a');
                link.download = nombreArchivo;
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        } catch (error) {
            console.error("Error al descargar imagen de proveedores:", error);
            alert("Error al descargar la imagen del gráfico.");
        }
    }

    // Inicialización mejorada
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, inicializando UI de reportes...');
        // No establecer fechas por defecto para evitar filtrar; se muestran todos los datos inicialmente

        // Inicializar UI sin generar reportes
        toggleReportes();
        buildParametrosUIProv();

        // Agregar event listeners
        const generarBtn = document.getElementById('generarReporteBtn');
        const selectReporte = document.getElementById('selectReporte');
        if (generarBtn) {
            generarBtn.addEventListener('click', generarReportes);
        }
        if (selectReporte) {
            selectReporte.addEventListener('change', function(){
                toggleReportes();
                buildParametrosUIProv();
            });
        }
        // Generar todos los reportes sin filtros al cargar
        generarReportes();
    });
</script>

    <button 
        class="btn-grafica"
        title="Visualizar Reportes"
        onclick="window.location.href='?pagina=reporteProveedores'">
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