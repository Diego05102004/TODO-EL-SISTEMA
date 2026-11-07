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
                        <i class="bi bi-arrow-down-up fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Ingresos y Egresos</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre los Ingresos y Egresos de la empresa.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Ingresos y Egresos</li>
                            <li>Fecha</li>
                            <li>RIF</li>
                            <li>Monto</li>
                            <li>Descripción (Productos Involucrados)</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Reporte:</strong> Generar gráfica</li>
                            <li><strong>Descargar:</strong> Obtener reporte en formato PDF</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                
                <!-- Tarjeta Reporte -->
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
                        <p>Permite generar reportes estadísticos de los movimientos financieros con múltiples opciones de visualización.</p>
                        <ul>
                            <li>Ingrese las fechas de inicio y fin del período a analizar</li>
                            <li>Elija el tipo de gráfica: Barras, Pastel, Líneas, Rosca o Área Polar</li>
                            <li>Seleccione el tipo de reporte: Ingresos y Egresos, Solo Ingresos o Solo Egresos</li>
                            <li>Haga clic en "Generar Reporte" para visualizar los datos</li>
                            <li>El sistema mostrará el reporte en formato visual y descargará automáticamente el PDF</li>
                            <li>Disponibles 5 tipos de gráficas para mejor análisis</li>
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