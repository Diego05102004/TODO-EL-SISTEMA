<?php
// Modal de Ayuda - Módulo de Permisos
// Basado en la estructura del manual de usuarios y siguiendo el patrón de proveedor.php
?>
<div class="modal-ayuda-overlay" id="modalAyuda">
    <div class="modal-ayuda-container">
        <div class="modal-ayuda-header">
            <h2 class="modal-ayuda-title">AYUDA</h2>
            <button class="modal-ayuda-close" id="cerrarModalAyuda">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <div class="modal-ayuda-content">
            <!-- Contenido principal -->
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Permisos</h3>
                </div>
                
                <p class="ayuda-descripcion">Configure los permisos del sistema para controlar el acceso de los usuarios a los diferentes módulos y acciones.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Roles del sistema</li>
                            <li>Permisos por módulo</li>
                            <li>Acciones permitidas (ingresar, consultar, incluir, modificar, eliminar, generar reporte)</li>
                            <li>Asignación de permisos a roles específicos</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Consultar:</strong> Ver lista de roles y sus permisos</li>
                            <li><strong>Modificar:</strong> Actualizar permisos de un rol</li>
                            <li><strong>Gestionar Permisos:</strong> Asignar permisos específicos a roles</li>
                        </ul>
                    </div>
                </div>
                
                <div class="ayuda-avisos">
                    <div class="ayuda-aviso ayuda-aviso-info">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <strong>Nota importante:</strong> Solo los usuarios con permisos de administrador pueden gestionar los permisos del sistema.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de ayuda específicas -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas">
                <!-- Tarjeta: Gestionar Permisos -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestionar Permisos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Configure qué acciones puede realizar cada rol en los diferentes módulos del sistema.</p>
                        <ul>
                            <li>Haga clic en el botón <strong>"Gestionar Permisos"</strong> (color verde) del rol a configurar</li>
                            <li>Seleccione que <strong>acciones</strong> podrá realizar el usuario en cada <strong>módulo</strong></li>
                            <li>Las acciones disponibles son: ingresar, consultar, incluir, modificar, eliminar y generar reporte</li>
                            <li>Haga clic en <strong>"Guardar Permisos"</strong> para confirmar los cambios</li>
                        </ul>
                        <div class="tarjeta-alerta">
                            <strong>⚠️ Precaución:</strong> Los cambios en permisos afectan inmediatamente el acceso de los usuarios al sistema.
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta: Consultar Roles y Permisos -->
                <div class="tarjeta-ayuda tarjeta-consultar" data-tarjeta="consultar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Consultar Roles y Permisos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Visualice todos los roles del sistema y los permisos asignados a cada uno.</p>
                        <ul>
                            <li>La tabla muestra todos los roles configurados en el sistema</li>
                            <li>Puede ver el nombre del rol y su estatus actual</li>
                            <li>En la columna <strong>"Acciones"</strong> puede gestionar los permisos de cada rol</li>
                            <li>Use el botón <strong>"Gestionar Permisos"</strong> para ver y modificar los permisos específicos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navegación -->
        <div class="modal-ayuda-navigation">
            <button class="nav-btn nav-btn-prev" id="btnNavPrev" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="nav-indicators">
                <!-- Punto 0: Slide principal (Gestión de Permisos) -->
                <span class="nav-dot nav-dot-active" data-slide="0"></span>
                <!-- Punto 1: Slide Gestionar Permisos -->
                <span class="nav-dot" data-slide="1"></span>
                <!-- Punto 2: Slide Consultar Roles -->
                <span class="nav-dot" data-slide="2"></span>
            </div>

            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Script de inicialización del modal -->
<script>
// Inicializar el modal de ayuda cuando se cargue este archivo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof inicializarModalAyudaUsuario === 'function') {
        window.modalAyudaInstance = inicializarModalAyudaUsuario();
    }
});
</script>
