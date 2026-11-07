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
                            <path d="M9 2l2 2h4l2-2v2H9V2zm0 4v2h6V6H9zm0 4v2h6v-2H9zm0 4v2h6v-2H9zm0 4v2h6v-2H9zm0 4v2h6v-2H9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 9l2-2 2 2-2M5 15l2 2 2-2M19 9l-2-2-2 2M19 15l-2 2-2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Reportes de Despacho</h3>
                </div>
                
                <p class="ayuda-descripcion">Genere reportes detallados de despachos con análisis de estatus, tendencias mensuales y comportamiento por cliente.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Tipos de Reportes Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Reporte Por Estatus</li>
                            <li>Reporte Por Despachos Mensuales</li>
                            <li>Reporte Por Cliente</li>
                            <li>Reporte Por Tipo de Compra</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>5 tipos de gráficas disponibles</li>
                            <li>Múltiples tipos de reportes</li>
                            <li>Descarga automática en formato PDF</li>
                            <li>Análisis por períodos específicos</li>
                            <li>Filtrado por diferentes criterios</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Gestión de Reportes de Despacho -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 2l2 2h4l2-2v2H9V2zm0 4v2h6V6H9zm0 4v2h6v-2H9zm0 4v2h6v-2H9zm0 4v2h6v-2H9zm0 4v2h6v-2H9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 9l2-2 2 2-2M5 15l2 2 2-2M19 9l-2-2-2 2M19 15l-2 2-2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Reportes de Despacho</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes detallados de despachos con análisis de estatus, tendencias mensuales y comportamiento por cliente.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                            <li><strong>Paso 2:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                            <li><strong>Paso 3:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                            <li><strong>Paso 4:</strong> Elije el tipo de reporte: (Todos los reportes, Por Estatus, Mensuales, Por Cliente o Por Tipo de Compra).</li>
                            <li><strong>Paso 5:</strong> Haga clic en <strong>"Generar Reporte"</strong> para visualizar.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Reportes Disponibles:</h5>
                        <ul>
                            <li><strong>Reporte Por Estatus:</strong> Muestra los despachos agrupados según su estado actual (pendientes, completados, cancelados, etc.)</li>
                            <li><strong>Reporte Por Despachos Mensuales:</strong> Compara la evolución de los despachos mes a mes para identificar tendencias y patrones estacionales</li>
                            <li><strong>Reporte Por Cliente:</strong> Analiza el comportamiento de despachos por cada cliente específico, identificando los más activos</li>
                            <li><strong>Reporte Por Tipo de Compra:</strong> Agrupa los despachos según el tipo de compra o método de adquisición utilizado</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Gráficas Disponibles:</h5>
                        <ul>
                            <li><strong>Barras:</strong> Ideal para comparar cantidades entre diferentes estatus o períodos</li>
                            <li><strong>Pastel:</strong> Perfecto para mostrar proporciones de despachos por categoría</li>
                            <li><strong>Líneas:</strong> Excelente para mostrar tendencias de despachos a lo largo del tiempo</li>
                            <li><strong>Rosca:</strong> Variación del gráfico de pastel con centro hueco, ideal para resaltar categorías principales</li>
                            <li><strong>Área Polar:</strong> Representación circular de datos cuantitativos, útil para análisis multidimensional</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Características Adicionales:</h5>
                        <ul>
                            <li><strong>Gráficas:</strong> 5 tipos disponibles para diferentes necesidades de visualización</li>
                            <li><strong>Reportes:</strong> Múltiples tipos para análisis específicos de despachos</li>
                            <li><strong>Reporte PDF:</strong> Descarga automática en formato PDF para compartir o archivar</li>
                            <li><strong>Análisis Temporal:</strong> Permite filtrar por fechas específicas para análisis de períodos concretos</li>
                            <li><strong>Filtrado Dinámico:</strong> Posibilidad de combinar múltiples criterios de filtrado</li>
                            <li><strong>Visualización Interactiva:</strong> Gráficos dinámicos con información detallada al pasar el cursor</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Beneficios del Reporte:</h5>
                        <ul>
                            <li><strong>Control de Operaciones:</strong> Permite monitorear el estado de todos los despachos en tiempo real</li>
                            <li><strong>Análisis de Eficiencia:</strong> Identifica cuellos de botella y áreas de mejora en el proceso de despacho</li>
                            <li><strong>Toma de Decisiones:</strong> Proporciona datos concretos para decisiones logísticas estratégicas</li>
                            <li><strong>Satisfacción del Cliente:</strong> Facilita el seguimiento de entregas para mejorar el servicio</li>
                            <li><strong>Optimización de Recursos:</strong> Ayuda a planificar mejor la utilización de recursos humanos y materiales</li>
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