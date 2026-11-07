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
                            <path d="M9 17V7m0 10a2 2 0 01-2 2 2 2 0 01-2-2m4-10a2 2 0 012-2 2 2 0 012 2m0 10a2 2 0 002 2 2 2 0 002-2M15 7a2 2 0 012-2 2 2 0 012 2m-2-2v10m0 0a2 2 0 002 2 2 2 0 002-2V7a2 2 0 00-2-2 2 2 0 00-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Reportes de Usuario</h3>
                </div>
                
                <p class="ayuda-descripcion">Genere reportes detallados de usuarios con múltiples opciones de filtrado y análisis.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Tipos de Reportes Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Usuarios por Rol</li>
                            <li>Usuarios por Estatus</li>
                            <li>Usuarios por Dominio de Correo</li>
                            <li>Usuarios por Inicial de Nombre</li>
                            <li>Usuarios por Inicial de Apellido</li>
                            <li>Usuarios por Prefijo Telefónico</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>Múltiples tipos de reportes disponibles</li>
                            <li>Descarga automática en formato PDF</li>
                            <li>Filtros específicos para cada tipo de reporte</li>
                            <li>Vista previa antes de generar</li>
                            <li>Interfaz intuitiva con gráficos</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Gestión de Reportes de Usuario -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 17V7m0 10a2 2 0 01-2 2 2 2 0 01-2-2m4-10a2 2 0 012-2 2 2 0 012 2m0 10a2 2 0 002 2 2 2 0 002-2M15 7a2 2 0 012-2 2 2 0 012 2m-2-2v10m0 0a2 2 0 002 2 2 2 0 002-2V7a2 2 0 00-2-2 2 2 0 00-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Reportes de Usuario</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes detallados de usuarios con diferentes criterios de filtrado y análisis.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                            <li><strong>Paso 2:</strong> Elije el tipo de reporte: Usuarios por (Rol, Estatus, Dominio de Correo, Inicial de Nombre, Inicial de Apellido o Prefijo Telefónico).</li>
                            <li><strong>Paso 3:</strong> Elige el rol de los usuarios (si aplica).</li>
                            <li><strong>Paso 4:</strong> Haga clic en <strong>"Generar Reporte"</strong> para visualizar y descargar.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Reportes Disponibles:</h5>
                        <ul>
                            <li><strong>Usuarios por Rol:</strong> Agrupa usuarios según su rol asignado en el sistema</li>
                            <li><strong>Usuarios por Estatus:</strong> Muestra usuarios clasificados por su estado (activo, inactivo, etc.)</li>
                            <li><strong>Usuarios por Dominio de Correo:</strong> Filtra usuarios según el dominio de su correo electrónico</li>
                            <li><strong>Usuarios por Inicial de Nombre:</strong> Agrupa usuarios por la primera letra de su nombre</li>
                            <li><strong>Usuarios por Inicial de Apellido:</strong> Organiza usuarios por la primera letra de su apellido</li>
                            <li><strong>Usuarios por Prefijo Telefónico:</strong> Clasifica usuarios según el prefijo de su número telefónico</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Características Adicionales:</h5>
                        <ul>
                            <li><strong>Múltiples tipos:</strong> Ofrece 6 diferentes criterios de reporte</li>
                            <li><strong>Reporte PDF:</strong> Descarga automática en formato PDF para compartir o archivar</li>
                            <li><strong>Vista previa:</strong> Permite visualizar el reporte antes de descargar</li>
                            <li><strong>Filtros dinámicos:</strong> Los filtros se ajustan según el tipo de reporte seleccionado</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-ayuda-navigation">
            <button class="nav-btn nav-btn-prev" id="btnNavPrev" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <div class="nav-indicators">
                <span class="nav-dot nav-dot-active" data-slide="0"></span>
                <span class="nav-dot" data-slide="1"></span>
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>