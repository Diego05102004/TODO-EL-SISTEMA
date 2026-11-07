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
                        <i class="bi bi-box-arrow-right fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Despacho de Productos</h3>
                </div>
                
                <p class="ayuda-descripcion">Gestione la salida de productos del inventario hacia los clientes.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Fecha de despacho</li>
                            <li>Cliente</li>
                            <li>Tipo de compra</li>
                            <li>Productos</li>
                            <li>Cantidad despachada</li>
                            <li>Precio unitario</li>
                            <li>Total del despacho</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Proceso de despacho:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Ver información completa</li>
                            <li><strong>Anular:</strong> Remover despacho</li>
                            <li><strong>Reportes:</strong> Gráficas parametrizadas</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Detallar -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite ver la información completa del despacho.</p>
                        <ul>
                            <li>Haga clic en el ícono del ojo en la columna "Acciones"</li>
                            <li>Revise los datos del despacho y los productos</li>
                            <li>Cierre la ventana cuando termine</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Cambiar Estatus -->
                <div class="tarjeta-ayuda tarjeta-estatus" data-tarjeta="estatus" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Cambiar Estatus</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite marcar un despacho como despachado de forma inmediata.</p>
                        <ul>
                            <li>Ubique el despacho en la tabla</li>
                            <li>Haga clic en el botón de check (verde) en "Acciones"</li>
                            <li>El estatus cambiará automáticamente</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Instantáneo:</strong> Sin confirmación
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Anular -->
                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Anular</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite anular un despacho.</p>
                        <ul>
                            <li>Encuentre el despacho que desea anular</li>
                            <li>Haga clic en el ícono de la X en "Acciones"</li>
                            <li>Confirme la anulación en el mensaje de advertencia</li>
                        </ul>
                        <div class="alert alert-danger">
                            <strong>¡Cuidado!</strong> Esta acción no se puede deshacer
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Reportes -->
                <div class="tarjeta-ayuda tarjeta-reporte" data-tarjeta="reporte" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 14l3-3 4 4 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Reportes</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes de despachos mediante gráficas parametrizadas.</p>
                        <ul>
                            <li>Haga clic en el botón de la gráfica (azul) en la esquina superior derecha</li>
                            <li>Seleccione fechas, tipo de gráfica y tipo de reporte</li>
                            <li>Haga clic en "Generar Reporte" para visualizar</li>
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
