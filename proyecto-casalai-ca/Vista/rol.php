<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
$idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 18;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) {?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Roles</title>
    <?php include 'header.php'; ?>
</head>
<body class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'NewNavBar.php'; ?>

<div class="modal fade modal-registrar" id="registrarRolModal" tabindex="-1" role="dialog" aria-labelledby="registrarRolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="registrarRol" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Rol" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="registrarRolModalLabel">Incluir Rol</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    <div class="envolver-form">
                        <label for="nombre_rol">Nombre del Rol</label>
                        <input type="text" placeholder="Nombre" class="control-form" id="nombre_rol" name="nombre_rol" maxlength="15" required>
                        <span class="span-value" id="snombre_rol"></span>
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

    <div class="tabla-header" style="width: 75%;">
        <div class="ghost"></div>

        <h3>Lista de Roles</h3>

        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $rol): ?>
                <tr data-id="<?php echo $rol['id_rol']; ?>">
                    <td>
                        <span class="campo-numeros">
                            <?php echo htmlspecialchars($rol['id_rol']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-nombres">
                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                        </span>
                    </td>
                    <td>
                        <ul>
                            <?php if (strtolower($rol['nombre_rol']) !== 'superusuario' && strtolower($rol['nombre_rol']) !== 'administrador' && strtolower($rol['nombre_rol']) !== 'cliente'): ?>
                                <button class="btn-modificar"
                                    id="btnModificarRol"
                                    title="Modificar Rol"
                                    data-id="<?php echo $rol['id_rol']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($rol['nombre_rol']); ?>">
                                    <img src="assets/img/pencil.svg">
                                </button>
                                <a href="?pagina=permiso">
                                    <button class="btn-permisos"
                                        title="Gestionar Permisos">
                                        <img src="assets/img/comprobacion-de-lista.svg">
                                    </button>
                                </a>
                                <button class="btn-eliminar"
                                    title="Eliminar Rol"
                                    data-id="<?php echo $rol['id_rol']; ?>">
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

<div class="modal fade modal-modificar" id="modificarRolModal" tabindex="-1" role="dialog" aria-labelledby="modificarRolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="modificarRol" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Rol" data-contexto="modificar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="modificarRolModalLabel">Modificar Rol</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modificar_id_rol" name="id_rol">
                    <div class="form-group">
                        <label for="modificar_nombre_rol">Nombre del Rol</label>
                        <input type="text" class="form-control" id="modificar_nombre_rol" name="nombre_rol" maxlength="15" required>
                        <span class="span-value-modal" id="smnombre_rol"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Modificar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/javascript/rol.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/bootstrap/js/sidebar.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>

<script src="assets/javascript/jwt_validator.js"></script>
    <button 
        class="btn-ayuda"
        style="top: 125px; right: 105px;"
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