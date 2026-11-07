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
                        <i class="bi bi-truck fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Recepción de Productos</h3>
                </div>
                
                <p class="ayuda-descripcion">Registre la entrada de nuevos productos al inventario desde proveedores.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>N° de factura de compra</li>
                            <li>Proveedor del producto</li>
                            <li>Productos recibidos</li>
                            <li>Cantidad y costo unitario</li>
                            <li>Fecha de recepción</li>
                            <li>Descripción de la recepción</li>
                            <li>Estado de la recepción</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nueva recepción</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Ver información completa</li>
                            <li><strong>Anular:</strong> Remover recepción</li>
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
                        <p>Permite registrar una nueva recepción de productos en el sistema.</p>
                        <ul>
                            <li>Haga clic en el botón "Nueva Recepción"</li>
                            <li>Complete el formulario con los datos de la recepción</li>
                            <li>Seleccione los productos y cantidades recibidas</li>
                            <li>Confirme la operación para guardar</li>
                        </ul>
                        <div class="alert alert-info"><br>
                            <strong>Importante:</strong><br>
                            - N° de Factura: (único en el sistema) <br>
                            - Costo: Valor unitario requerido
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Detallar -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite ver la información completa de una recepción específica.</p>
                        <ul>
                            <li>Haga clic en el ícono del ojo en la columna "Acciones"</li>
                            <li>Se mostrarán todos los detalles de la recepción</li>
                            <li>Incluye productos, cantidades y costos</li>
                            <li>Puede cerrar la vista detallada cuando termine</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tarjeta Anular -->
                <div class="tarjeta-ayuda tarjeta-anular" data-tarjeta="anular">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Anular</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite anular una recepción existente del sistema.</p>
                        <ul>
                            <li>Localice la recepción que desea anular</li>
                            <li>Haga clic en el ícono X en la columna "Acciones"</li>
                            <li>Confirme la anulación en el mensaje de advertencia</li>
                            <li>La recepción será marcada como anulada</li>
                        </ul>
                        <div class="alert alert-warning"><br>
                            <strong>Importante:</strong> Esta acción no se puede deshacer
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Reporte -->
                <div class="tarjeta-ayuda tarjeta-reporte" data-tarjeta="reporte">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Reporte</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes estadísticos de las recepciones.</p>
                        <ul>
                            <li>Haga clic en el botón de gráficas en la parte superior</li>
                            <li>Seleccione el tipo de reporte que desea generar</li>
                            <li>Configure el rango de fechas</li>
                            <li>Elija el tipo de gráfica a visualizar</li>
                            <li>Genere y visualice el reporte</li>
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
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>