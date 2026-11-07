<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Importar clase de autenticación JWT
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();

$idRol = $_SESSION['id_rol'] ?? 0; // Rol por defecto en entorno de pruebas
$idModulo = 19;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora del Sistema</title>
    <link rel="stylesheet" href="assets/public/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/public/js/dataTables.bootstrap5.min.css">
    <?php include 'header.php'; ?>
</head>
<body class="fondo" style="background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh;">
<?php include 'NewNavBar.php'; ?>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>Bitácora del Sistema</h3>
        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaBitacora">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha y Hora</th>
                <th>Usuario</th>
                <th>Acción <br> Realizada</th>
                <th>Módulo</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($registros)): ?>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td>
                            <span class="campo-numeros">
                                <?= htmlspecialchars($registro['id_bitacora']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-numeros">
                                <?= date('d/m/Y h:i:s A', strtotime($registro['fecha_hora'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-nombres">
                                <?= htmlspecialchars($registro['username']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-tex-num">
                                <?= htmlspecialchars($registro['accion']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-tex-num">
                                <?= htmlspecialchars($registro['modulo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-nombres">
                                <?= htmlspecialchars($registro['descripcion']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No hay registros en la bitácora.</td>
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
<script src="assets/javascript/bitacora.js"></script>
<button 
    class="btn-ayuda"
    style="top: 115px; right: 100px;"
    title="Visualizar Ayuda">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>

<script src="assets/javascript/jwt_validator.js"></script>
</body>
</html>
<?php } else {
    header("Location: ?pagina=acceso-denegado");
    exit;
} ?>
