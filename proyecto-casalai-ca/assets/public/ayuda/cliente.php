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
                        <i class="bi bi-people fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Clientes</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre la información de los clientes para mantener un registro actualizado de contactos y datos personales.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Nombre completo</li>
                            <li>Número de cédula</li>
                            <li>Dirección</li>
                            <li>Teléfono</li>
                            <li>Correo electrónico</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nuevo cliente</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Modificar:</strong> Actualizar datos</li>
                            <li><strong>Eliminar:</strong> Remover cliente</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Registrar -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Registrar Cliente</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite registrar un nuevo cliente en el sistema con todos sus datos de contacto.</p>
                        <ul>
                            <li>Haga clic en el botón "+" (color verde) para nuevo cliente</li>
                            <li>Ingrese el nombre completo (solo letras)</li>
                            <li>Ingrese la cédula con formato: 1.234.567 o 12.345.678</li>
                            <li>Ingrese la dirección (mínimo 4 caracteres)</li>
                            <li>Ingrese el teléfono con formato: 0400-000-0000</li>
                            <li>Ingrese el correo electrónico (dominios permitidos: gmail, outlook, yahoo, icloud)</li>
                            <li>Haga clic en "Registrar" para confirmar</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Importante:</strong> La cédula debe ser única en el sistema
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Modificar -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Modificar Cliente</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite actualizar los datos de un cliente existente.</p>
                        <ul>
                            <li>Localice el cliente en la tabla</li>
                            <li>Haga clic en el ícono del lápiz en "Acciones"</li>
                            <li>Edite los campos necesarios</li>
                            <li>Verifique que los datos cumplan los formatos requeridos</li>
                            <li>Haga clic en "Modificar" para confirmar cambios</li>
                        </ul>
                        <div class="alert alert-warning">
                            <strong>Tip:</strong> Los cambios se reflejan inmediatamente en la tabla
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Eliminar -->
                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Eliminar Cliente</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite remover un cliente del sistema.</p>
                        <ul>
                            <li>Encuentre el cliente que desea eliminar</li>
                            <li>Haga clic en el ícono de la X en "Acciones"</li>
                            <li>Confirme la eliminación en el mensaje de advertencia</li>
                        </ul>
                        <div class="alert alert-danger">
                            <strong>¡Cuidado!</strong> Esta acción no se puede deshacer
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Formatos -->
                <div class="tarjeta-ayuda tarjeta-formatos" data-tarjeta="formatos">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Formatos Requeridos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Los campos deben cumplir con los siguientes formatos específicos:</p>
                        <div class="formato-item">
                            <strong>Nombre:</strong>
                            <ul>
                                <li>Solo letras y espacios</li>
                                <li>Mínimo 2 caracteres, máximo 100</li>
                                <li>Ejemplo: Juan Pérez</li>
                            </ul>
                        </div>
                        <div class="formato-item">
                            <strong>Cédula:</strong>
                            <ul>
                                <li>Formato: 1.234.567 o 12.345.678</li>
                                <li>Solo números y puntos</li>
                                <li>Ejemplo: 12.345.678</li>
                            </ul>
                        </div>
                        <div class="formato-item">
                            <strong>Teléfono:</strong>
                            <ul>
                                <li>Formato: 0400-000-0000</li>
                                <li>Solo números y guiones</li>
                                <li>Ejemplo: 0412-123-4567</li>
                            </ul>
                        </div>
                        <div class="formato-item">
                            <strong>Correo:</strong>
                            <ul>
                                <li>Formato: usuario@dominio.com</li>
                                <li>Dominios permitidos: gmail.com, outlook.com, yahoo.com, icloud.com</li>
                                <li>Ejemplo: cliente@gmail.com</li>
                            </ul>
                        </div>
                        <div class="alert alert-info">
                            <strong>Tip:</strong> Los campos con formato incorrecto mostrarán mensajes de error específicos
                        </div>
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
