<?php require_once "utils.php"; ?>

<section id="mi-cuenta" class="section-card">
    <h2 class="section-title">
        <i class="bi bi-person-circle me-2"></i>Mi Cuenta
    </h2>
    
    <div class="row">
        <div class="col-md-8">
            <p>En la <strong>parte superior derecha</strong> de la barra de navegación, al hacer clic en su nombre de usuario o foto de perfil, se desplegará un menú con la opción <strong>Mi Perfil</strong>.</p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Acceso al Perfil</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-center">
                                <?= renderImagen("dashboard", "perfil2.png") ?>
                                <p class="text-muted small mt-2">Menú desplegable del perfil de usuario</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column h-100 justify-content-center">
                                <p>Desde aquí puede acceder a:</p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="bi bi-person me-2 text-primary"></i>Ver perfil</li>
                                    <li class="mb-2"><i class="bi bi-gear me-2 text-primary"></i>Configuración</li>
                                    <li><i class="bi bi-box-arrow-right me-2 text-primary"></i>Cerrar sesión</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h4>Gestión del Perfil</h4>
            <p>En la sección de perfil podrá:</p>
            
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Información Personal</h5>
                            <p class="card-text">Actualice sus datos personales como nombre, apellido, teléfono y dirección.</p>
                            <?= renderImagen("perfil", "perfil-informacion-personal.png") ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Configuración de Cuenta</h5>
                            <p class="card-text">Actualice su correo electrónico y contraseña de acceso al sistema.</p>
                            <?= renderImagen("perfil", "perfil-cuenta.png") ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="note">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Nota:</strong> Para actualizar la contraseña es necesario confirmar la contraseña actual como medida de seguridad.
            </div>
            
            <div class="warning mt-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Importante:</strong> Asegúrese de que su correo electrónico esté actualizado para recibir notificaciones importantes del sistema.
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Consejos de Seguridad</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-shield-lock text-success me-2"></i>
                            Utilice contraseñas seguras
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-envelope-check text-primary me-2"></i>
                            Mantenga su correo actualizado
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-bell-fill text-warning me-2"></i>
                            Revise las notificaciones
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-box-arrow-right text-danger me-2"></i>
                            Cierre sesión al terminar
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Cambio de Contraseña</h5>
                    <p class="card-text">Para cambiar su contraseña, complete el siguiente formulario:</p>
                    <?= renderImagen("perfil", "perfil-password.png") ?>
                    <div class="mt-3">
                        <ol class="small">
                            <li class="mb-2">Ingrese su contraseña actual</li>
                            <li class="mb-2">Escriba la nueva contraseña</li>
                            <li class="mb-2">Confirme la nueva contraseña</li>
                            <li>Haga clic en "Guardar Cambios"</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        Recuerde que todos los cambios realizados en su perfil se guardarán automáticamente al hacer clic en el botón "Guardar Cambios".
    </div>
</section>
