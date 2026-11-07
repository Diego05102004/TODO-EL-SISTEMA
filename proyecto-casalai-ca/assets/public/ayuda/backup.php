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
                        <i class="bi bi-database fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Backup</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre las bases de datos para respaldar y restaurar la información.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Nombre de archivo</li>
                            <li>Base de datos (Principal/Seguridad)</li>
                            <li>Tamaño de archivo</li>
                            <li>Fecha</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Generar:</strong> Nuevo respaldo</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Restaurar:</strong> Recuperar base de datos</li>
                            <li><strong>Eliminar:</strong> Remover respaldo</li>
                            <li><strong>Descargar:</strong> Obtener archivo de respaldo</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Generar -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Generar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar un nuevo respaldo de la base de datos (Principal o Seguridad).</p>
                        <ul>
                            <li>Haga clic en el botón "Generar Backup" (Principal/Seguridad)</li>
                            <li>Confirme la generación del respaldo en el mensaje interrogante</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Restaurar -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="modificar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="22 4 22 10 16 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Restaurar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite restaurar la base de datos a partir de un respaldo existente.</p>
                        <ul>
                            <li>Localice el respaldo en la tabla</li>
                            <li>Haga clic en el botón "Restaurar" (amarillo)</li>
                            <li>Confirme la restauración en el mensaje de advertencia</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Importante:</strong> Esta operación debe ser realizada con responsabilidad. Una restauración de una base de datos incorrecta sin realizar previamente un respaldo de las bases de datos actuales puede causar una grave perdida de información en el sistema.
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Descargar -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="reporte" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Descargar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite descargar el respaldo de la base de datos en formato SQL.</p>
                        <ul>
                            <li>Haga clic en el ícono de descarga en la columna "Acciones"</li>
                            <li>Se descargará el archivo del respaldo en formato SQL</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Eliminar -->
                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Eliminar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite eliminar un respaldo de la base de datos.</p>
                        <ul>
                            <li>Encuentre el respaldo que desea eliminar</li>
                            <li>Haga clic en el ícono de la X en "Acciones"</li>
                            <li>Confirme la eliminación en el mensaje de advertencia</li>
                        </ul>
                        <div class="alert alert-danger">
                            <strong>¡Cuidado!</strong> Esta acción no se puede deshacer
                        </div>
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
                <span class="nav-dot" data-slide="2"></span>
                <span class="nav-dot" data-slide="3"></span>
                <span class="nav-dot" data-slide="4"></span>
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>
