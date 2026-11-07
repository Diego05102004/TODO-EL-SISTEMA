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
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Reportes de Recepción</h3>
                </div>
                
                <p class="ayuda-descripcion">Genere reportes detallados de recepciones de productos con múltiples opciones de análisis y visualización.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Tipos de Reportes Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Todos los Reportes</li>
                            <li>Recepciones por Proveedor</li>
                            <li>Productos más Recibidos</li>
                            <li>Recepciones Mensuales</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>5 tipos de gráficas disponibles</li>
                            <li>Múltiples tipos de reportes</li>
                            <li>Descarga automática en formato PDF</li>
                            <li>Filtrado por fechas específicas</li>
                            <li>Visualización interactiva</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Gestión de Reportes de Recepción -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Reportes de Recepción</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes detallados de recepciones de productos con diferentes criterios de análisis y visualización gráfica.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                            <li><strong>Paso 2:</strong> Elije el tipo de reporte: (Todos los Reportes, Recepciones por Proveedor, Productos más Recibidos, Recepciones Mensuales).</li>
                            <li><strong>Paso 3:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                            <li><strong>Paso 4:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                            <li><strong>Paso 5:</strong> Haga clic en <strong>"Generar Reporte"</strong> para visualizar.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Reportes Disponibles:</h5>
                        <ul>
                            <li><strong>Todos los Reportes:</strong> Muestra un resumen completo de todas las recepciones en el período seleccionado</li>
                            <li><strong>Recepciones por Proveedor:</strong> Agrupa las recepciones según el proveedor que suministró los productos</li>
                            <li><strong>Productos más Recibidos:</strong> Identifica los productos con mayor frecuencia de recepción</li>
                            <li><strong>Recepciones Mensuales:</strong> Muestra la tendencia de recepciones agrupadas por mes</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Gráficas Disponibles:</h5>
                        <ul>
                            <li><strong>Barras:</strong> Ideal para comparar cantidades entre diferentes categorías</li>
                            <li><strong>Pastel:</strong> Perfecto para mostrar proporciones y porcentajes</li>
                            <li><strong>Líneas:</strong> Excelente para mostrar tendencias a lo largo del tiempo</li>
                            <li><strong>Rosca:</strong> Variación del gráfico de pastel con centro hueco</li>
                            <li><strong>Área Polar:</strong> Representación circular de datos cuantitativos</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Características Adicionales:</h5>
                        <ul>
                            <li><strong>Gráficas:</strong> 5 tipos disponibles para diferentes necesidades de visualización</li>
                            <li><strong>Reportes:</strong> Múltiples tipos para análisis específicos</li>
                            <li><strong>Reporte PDF:</strong> Descarga automática en formato PDF para compartir o archivar</li>
                            <li><strong>Filtrado por fechas:</strong> Permite acotar el análisis a períodos específicos</li>
                            <li><strong>Visualización interactiva:</strong> Gráficos dinámicos con información detallada al pasar el cursor</li>
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