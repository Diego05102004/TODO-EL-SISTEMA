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
                        <i class="bi bi-person fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Perfil</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre su información personal, correo electrónico y contraseña de acceso al sistema.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Funciones Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Editar Información Personal</li>
                            <li>Editar Correo Electrónico</li>
                            <li>Editar Contraseña</li>
                            <li>Ver Datos del Perfil</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>Acceso desde menú superior</li>
                            <li>Actualización de datos en tiempo real</li>
                            <li>Validación de contraseñas</li>
                            <li>Confirmación de cambios</li>
                            <li>Seguridad de datos personales</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Editar Información Personal -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Editar Información Personal</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Actualice sus datos personales como nombre, apellido, teléfono y dirección desde la sección de perfil.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Editar Información Personal:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> En la sección de perfil, haga clic en el botón de editar información personal.</li>
                            <li><strong>Paso 2:</strong> Actualice sus datos personales como nombre, apellido, teléfono y dirección.</li>
                            <li><strong>Paso 3:</strong> Haga clic en <strong>"Guardar Cambios"</strong> para confirmar la actualización.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Información Personal Editable:</h5>
                        <ul>
                            <li><strong>Nombre Completo:</strong> Nombres y apellidos del usuario</li>
                            <li><strong>Teléfono:</strong> Número de contacto principal</li>
                            <li><strong>Dirección:</strong> Ubicación física del usuario</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Editar Correo -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Editar Correo</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Actualice y cambie su correo electrónico desde la sección de perfil.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Editar Correo Electrónico:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> En la sección de perfil, haga clic en el botón de editar correo.</li>
                            <li><strong>Paso 2:</strong> Ingrese su nueva dirección de correo electrónico.</li>
                            <li><strong>Paso 3:</strong> Para seguridad, ingrese su contraseña actual.</li>
                            <li><strong>Paso 4:</strong> Haga clic en <strong>"Guardar Cambios"</strong> para confirmar la actualización.</li>
                        </ol>
                    </div>
                </div>

                <!-- Tarjeta Editar Contraseña -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="16" r="1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Editar Contraseña</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Actualice y cambie su contraseña de acceso al sistema desde la sección de perfil.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Editar Contraseña:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> En la sección de perfil, haga clic en el botón de cambiar contraseña.</li>
                            <li><strong>Paso 2:</strong> Ingrese su contraseña actual.</li>
                            <li><strong>Paso 3:</strong> Ingrese la nueva contraseña.</li>
                            <li><strong>Paso 4:</strong> Confirme la nueva contraseña ingresándola nuevamente.</li>
                            <li><strong>Paso 5:</strong> Haga clic en <strong>"Guardar Cambios"</strong> para confirmar el cambio.</li>
                        </ol>
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
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>