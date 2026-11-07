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
                        <i class="bi bi-building fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Proveedores</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre la información de todos los proveedores de productos y servicios.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Nombre del proveedor y su representante</li>
                            <li>RIF del proveedor y su representante</li>
                            <li>Correo electrónico</li>
                            <li>Dirección</li>
                            <li>N° de teléfono (Principal y Secundario)</li>
                            <li>Observaciones</li>
                            <li>Estatus</li>
                            <li>Suministro de los proveedores</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nuevo proveedor</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Ver información completa del proveedor</li>
                            <li><strong>Modificar:</strong> Actualizar datos</li>
                            <li><strong>Eliminar:</strong> Remover proveedor</li>
                            <li><strong>Estatus:</strong> Actualizar estatus (habilitado/inhabilitado)</li>
                            <li><strong>Reporte:</strong> Generar reportes</li>
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
                        <h4 class="tarjeta-titulo">Registrar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite agregar un nuevo proveedor al sistema. Debe completar todos los campos obligatorios como nombre, RIF, correo, dirección y teléfonos.</p>
                        <ul>
                            <li>Haga clic en el botón (+) Incluir</li>
                            <li>Complete el formulario con los datos del proveedor</li>
                            <li>Verifique que todos los campos sean correctos</li>
                            <li>Presione "Guardar" para registrar</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Importante:</strong><br>
                            - RIF: (VEJPG)-12345678-9 <br>
                            -  Teléfono: 0400-000-0000
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Detallar -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite visualizar toda la información completa de un proveedor específico.</p>
                        <ul>
                            <li>Ubique el proveedor en la lista</li>
                            <li>Haga clic en el ícono del ojo (👁)</li>
                            <li>Revise todos los datos del proveedor</li>
                            <li>Puede cerrar la vista cuando termine</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tarjeta Modificar -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Modificar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite actualizar los datos de un proveedor existente en el sistema.</p>
                        <ul>
                            <li>Encuentre el proveedor a modificar</li>
                            <li>Haga clic en el ícono del lápiz (✏️)</li>
                            <li>Edite los campos necesarios</li>
                            <li>Guarde los cambios realizados</li>
                        </ul>
                        <div class="alert alert-warning">
                            <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                        </div>
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
                        <p>Permite remover permanentemente un proveedor del sistema.</p>
                        <ul>
                            <li>Localice el proveedor a eliminar</li>
                            <li>Haga clic en el ícono de eliminar (🗑️)</li>
                            <li>Confirme la acción en la ventana emergente</li>
                            <li>El proveedor será eliminado del sistema</li>
                        </ul>
                        <div class="alert alert-danger">
                            <strong>¡Cuidado!</strong> Esta acción no se puede deshacer
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Cambiar Estatus -->
                <div class="tarjeta-ayuda tarjeta-estatus" data-tarjeta="estatus" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="11" width="18" height="10" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Cambiar Estatus</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite habilitar o inhabilitar un proveedor sin eliminarlo del sistema.</p>
                        <ul>
                            <li>Encuentre el proveedor en la lista</li>
                            <li>Haga clic sobre su estatus actual</li>
                            <li>El sistema alternará entre habilitado/inhabilitado</li>
                            <li>El proveedor mantendrá sus datos pero cambiará su estado</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Instantáneo:</strong> Sin confirmación
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Generar Reporte -->
                <div class="tarjeta-ayuda tarjeta-reporte" data-tarjeta="reporte" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
                                <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2"/>
                                <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/>
                                <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/>
                                <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Generar Reporte</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes de los proveedores registrados en el sistema.</p>
                        <ul>
                            <li>Utilice los filtros para seleccionar los datos</li>
                            <li>Puede filtrar por estatus o fecha</li>
                            <li>Haga clic en "Generar Reporte"</li>
                            <li>El sistema descargará el archivo en formato PDF</li>
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
                <span class="nav-dot" data-slide="2"></span>
                <span class="nav-dot" data-slide="3"></span>
                <span class="nav-dot" data-slide="4"></span>
                <span class="nav-dot" data-slide="5"></span>
                <span class="nav-dot" data-slide="6"></span>
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>