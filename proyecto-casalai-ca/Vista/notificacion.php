<?php
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
// Verificar si hay un error en la carga de notificaciones
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Historial de Notificaciones</title>
    <?php include 'header.php'; ?>
    <link rel="stylesheet" href="assets/styles/notificacion.css">
</head>

<body class="fondo" style="min-height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<div class="container my-4" style="max-width: 800px;">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 style="color: #1f66df; font-weight: bold; text-align: center; margin: 0;">Mis Notificaciones</h4>
                </div>
                <div class="card-body p-0">
                    <?php if ($error): ?>
                        <div class="alert alert-danger m-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div id="lista-notificaciones" class="list-group list-group-flush">
                        <!-- Las notificaciones se cargarán aquí dinámicamente -->
                        <div class="text-center p-4" id="cargando">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando notificaciones...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/javascript/notificacion.js"></script>

<script src="assets/javascript/jwt_validator.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>