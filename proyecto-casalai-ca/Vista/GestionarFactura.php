<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 13;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>
<!DOCTYPE html>
<html lang="es">
<?php include 'header.php'; ?>
<title>Gestionar Pedidos</title>

    <style>
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
    <body  class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
<div class="main-content">  
<?php require_once("assets/public/modal.php"); ?>

<!--<div class="container">  todo el contenido ira dentro de esta etiqueta-->

<div class="contenedor-tabla">
    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>Pedidos Realizados</h3>
        <div class="ghost"></div>
    </div>

    <!-- seccion del modal productos -->
    <div id="modalproductos" class="container-lg p-4 bg-light shadow rounded" style="max-width: 90%; margin: auto;">
        <div style="display: flex; flex-direction: column;">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-striped table-hover table-bordered w-100">
                    <tbody id="listado">
                        <!-- filas aquí -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


</div>

    <?php include 'footer.php'; ?>
        <!-- Bootstrap JS -->
    <script src="assets/javascript/factura.js"></script>
    <script src='assets/public/bootstrap/js/bootstrap.bundle.min.js'></script>
    <script src='assets/public/bootstrap/css/bootstrap.min.css'></script>
    <script src="assets/javascript/validaciones.js"></script>

    <button 
        class="btn-ayuda"
        style="top: 100px;"
        title="Visualizar Ayuda"
        >
        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
    </button>
    
<script src="assets/javascript/jwt_validator.js"></script>
</body>
</html>
<?php } else {
    header("Location: ?pagina=acceso-denegado");
    exit();
} ?>