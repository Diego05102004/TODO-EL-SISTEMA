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
                            <path d="M16 21v-2a4 4 0 0 0 4-4V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V7a1 1 0 0 0 1-1h2a1 1 0 0 0 1 1v7a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Reportes de Proveedor</h3>
                </div>
                
                <p class="ayuda-descripcion">Genere reportes detallados de proveedores con análisis de suministros, rankings y dependencias.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Tipos de Reportes Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Productos Suministrados por Proveedor</li>
                            <li>Ranking de Proveedores</li>
                            <li>Comparación Mensual de Suministros</li>
                            <li>Dependencia de Proveedores</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>5 tipos de gráficas disponibles</li>
                            <li>Múltiples tipos de reportes</li>
                            <li>Descarga en PDF o Gráfica</li>
                            <li>Filtrado por estatus de proveedores</li>
                            <li>Análisis de dependencias</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Gestión de Reportes de Proveedor -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 21v-2a4 4 0 0 0 4-4V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V7a1 1 0 0 0 1-1h2a1 1 0 0 0 1 1v7a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Reportes de Proveedor</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes detallados de proveedores con análisis de suministros, rankings y relaciones de dependencia.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                            <li><strong>Paso 2:</strong> Use el filtro de estatus para <strong>mostrar</strong>: Todos/Habilitados/Inhabilitados.</li>
                            <li><strong>Paso 3:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                            <li><strong>Paso 4:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                            <li><strong>Paso 5:</strong> Elije el tipo de reporte: (Todos los Reportes, Suministro, Rancking, Comparación Mensual o Dependencia).</li>
                            <li><strong>Paso 6:</strong> Haga clic en <strong>"Generar Reporte"</strong> para visualizar.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Reportes Disponibles:</h5>
                        <ul>
                            <li><strong>Productos Suministrados por Proveedor:</strong> Muestra la cantidad y tipo de productos que cada proveedor ha suministrado al sistema</li>
                            <li><strong>Ranking de Proveedores:</strong> Ordena los proveedores según su volumen de suministros o actividad, identificando los más importantes</li>
                            <li><strong>Comparación Mensual de Suministros:</strong> Compara la evolución de los suministros mes a mes para identificar tendencias y patrones estacionales</li>
                            <li><strong>Dependencia de Proveedores:</strong> Analiza las relaciones entre proveedores para identificar posibles conflictos de interés o dependencias críticas en la cadena de suministro</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Gráficas Disponibles:</h5>
                        <ul>
                            <li><strong>Barras:</strong> Ideal para comparar cantidades entre diferentes proveedores o períodos</li>
                            <li><strong>Pastel:</strong> Perfecto para mostrar proporciones de participación de cada proveedor</li>
                            <li><strong>Líneas:</strong> Excelente para mostrar tendencias de suministros a lo largo del tiempo</li>
                            <li><strong>Rosca:</strong> Variación del gráfico de pastel con centro hueco, ideal para resaltar proveedores principales</li>
                            <li><strong>Área Polar:</strong> Representación circular de datos cuantitativos, útil para análisis multidimensional</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Características Adicionales:</h5>
                        <ul>
                            <li><strong>Gráficas:</strong> 5 tipos disponibles para diferentes necesidades de visualización</li>
                            <li><strong>Reportes:</strong> Múltiples tipos para análisis específicos de proveedores</li>
                            <li><strong>Descarga:</strong> PDF o Gráfica para flexibilidad en la presentación de resultados</li>
                            <li><strong>Filtrado por estatus:</strong> Permite mostrar solo proveedores habilitados, inhabilitados o todos</li>
                            <li><strong>Análisis de dependencias:</strong> Identifica relaciones críticas entre proveedores para gestión de riesgos</li>
                            <li><strong>Análisis temporal:</strong> Comparaciones mensuales para identificar patrones y tendencias estacionales</li>
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