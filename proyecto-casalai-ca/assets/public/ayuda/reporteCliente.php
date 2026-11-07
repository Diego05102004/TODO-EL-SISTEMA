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
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Reportes de Cliente</h3>
                </div>
                
                <p class="ayuda-descripcion">Genere reportes detallados de clientes con análisis de compras y estadísticas de los mejores clientes.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Tipos de Reportes Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Top 10 Clientes por Productos Comprados</li>
                            <li>Análisis de Compras por Cliente</li>
                            <li>Estadísticas de Clientes Activos</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>Estadísticas de Top 10 clientes</li>
                            <li>Gráficos de barras detallados</li>
                            <li>Tablas con información completa</li>
                            <li>Descarga automática en formato PDF</li>
                            <li>Análisis de comportamiento de compra</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Gestión de Reportes de Cliente -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Reportes de Cliente</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes detallados de clientes con análisis de compras, productos adquiridos y estadísticas de comportamiento.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                            <li><strong>Paso 2:</strong> Consulte la sección <strong>"Top 10 Clientes por Productos Comprados"</strong>.</li>
                            <li><strong>Paso 3:</strong> Visualice el gráfico de barras y la tabla detallada.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Características del Reporte:</h5>
                        <ul>
                            <li><strong>Top 10 Clientes:</strong> Muestra los 10 clientes con mayor volumen de compras de productos</li>
                            <li><strong>Gráfico de Barras:</strong> Visualización clara y comparativa del rendimiento de cada cliente</li>
                            <li><strong>Tabla Detallada:</strong> Información completa con datos específicos de cada cliente</li>
                            <li><strong>Estadísticas:</strong> Análisis cuantitativo del comportamiento de compra</li>
                            <li><strong>Ordenamiento:</strong> Clientes ordenados de mayor a menor según productos comprados</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Información Analizada:</h5>
                        <ul>
                            <li><strong>Identificación del Cliente:</strong> Nombre y datos básicos del cliente</li>
                            <li><strong>Cantidad de Productos:</strong> Número total de productos comprados por cada cliente</li>
                            <li><strong>Frecuencia de Compra:</strong> Regularidad de las transacciones del cliente</li>
                            <li><strong>Valor de Compras:</strong> Monto total invertido por cada cliente</li>
                            <li><strong>Tipo de Productos:</strong> Categorías de productos preferidas por cada cliente</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Características Adicionales:</h5>
                        <ul>
                            <li><strong>Estadísticas:</strong> Top 10 clientes con análisis detallado</li>
                            <li><strong>Reporte PDF:</strong> Descarga automática en formato PDF para compartir o archivar</li>
                            <li><strong>Visualización Interactiva:</strong> Gráficos dinámicos con información detallada</li>
                            <li><strong>Análisis Comparativo:</strong> Permite comparar el rendimiento entre diferentes clientes</li>
                            <li><strong>Filtrado de Datos:</strong> Posibilidad de filtrar por períodos específicos</li>
                            <li><strong>Exportación de Datos:</strong> Opciones para exportar la información en diferentes formatos</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Beneficios del Reporte:</h5>
                        <ul>
                            <li><strong>Identificación de Clientes Clave:</strong> Reconoce a los clientes más importantes para el negocio</li>
                            <li><strong>Estrategias de Marketing:</strong> Facilita la creación de campañas dirigidas a clientes específicos</li>
                            <li><strong>Mejora del Servicio:</strong> Permite personalizar la atención según el perfil de cada cliente</li>
                            <li><strong>Análisis de Tendencias:</strong> Identifica patrones de compra y preferencias del mercado</li>
                            <li><strong>Toma de Decisiones:</strong> Proporciona datos concretos para decisiones comerciales estratégicas</li>
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