<?php // Importar clase de autenticación JWT
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario') { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Backups</title>
    <?php include 'header.php'; ?>
    <style>
        .tabla-backups .btn-descargar { color: #0d6efd; }
        .tabla-backups .btn-restaurar { color: #ffc107; }
        .tabla-backups .btn-eliminar { color: #dc3545; }
        .dataTables_wrapper .dataTables_processing {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            margin-left: -50%;
            margin-top: -25px;
            padding-top: 20px;
            text-align: center;
            font-size: 1.2em;
        }
    </style>
</head>

<?php include 'NewNavBar.php'; ?>

<body class="fondo" style="height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="contenedor-tabla">
    <h3 class="tabla-titulo-2" style="margin-bottom: 20px;">Gestión de Backups</h3>
    <div class="grupo-form" style="margin-bottom: 20px; display: flex; justify-content: center; gap: 15px;">
        <button id="btn-backup-principal" class="btn-generar-p">
            <img src="assets/img/save.svg" alt=""> Generar Backup Principal
        </button>
        <button id="btn-backup-seguridad" class="btn-generar-s">
            <img src="assets/img/save.svg" alt=""> Generar Backup Seguridad
        </button>
    </div>

    <table class="tablaConsultas" id="tablaConsultas" style="width:100%">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Tipo</th>
                <th>Tamaño</th>
                <th>Fecha de Modificación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($backups)): ?>
                <?php foreach ($backups as $backup): ?>
                <tr>
                    <td>
                        <span class="campo-nombres">
                            <?= htmlspecialchars($backup['nombre'] ?? '') ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-rango <?= (isset($backup['tipo']) && $backup['tipo'] === 'Seguridad')?>">
                            <?= htmlspecialchars($backup['tipo'] ?? '') ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-numeros">
                            <?= htmlspecialchars($backup['tamano'] ?? '') ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-numeros">
                            <?= htmlspecialchars($backup['fecha_modificacion'] ?? '') ?>
                        </span>
                    </td>
                    <td>
                        <ul>
                            <button class="btn-restaurar"
                                data-archivo="<?= htmlspecialchars($backup['nombre'] ?? ''); ?>"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Restaurar">
                                <img src="assets/img/rotate-ccw.svg" alt="Restaurar">
                            </button>
                            <button class="btn-descargar"
                                data-archivo="<?= htmlspecialchars($backup['nombre'] ?? ''); ?>"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Descargar">
                                <img src="assets/img/download.svg" alt="Descargar">
                            </button>
                            <button class="btn-eliminar"
                                data-archivo="<?= htmlspecialchars($backup['nombre'] ?? ''); ?>"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Eliminar">
                                <img src="assets/img/circle-x.svg" alt="Eliminar">
                            </button>
                        </ul>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        No hay backups disponibles.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#tablaConsultas').DataTable({
            language: {
                url: 'assets/public/js/es-ES.json'
            },
            responsive: true,
            scrollX: true,
            scrollCollapse: true,
            columnDefs: [
                { orderable: false, targets: 4 }, // Disable sorting on Actions column
                { width: '15%', targets: 4 } // Set width for Actions column
            ],
            initComplete: function() {
                var api = this.api();
                api.columns.adjust();
                // Reinitialize tooltips after table is initialized
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    });
</script>
<script src="assets/javascript/backup.js"></script>
<button 
    class="btn-ayuda"
    style="top: 125px; right: 100px;"
    title="Visualizar Ayuda"
    >
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