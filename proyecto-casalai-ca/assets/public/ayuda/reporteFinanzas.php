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
                            <path d="M12 2v20M17 5H9l-3 3 3-3h11M17 17H9l-3 3 3-3h11M7 12l3-3 3 3M17 12l-3 3-3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 5H9l-3 3 3-3h11M17 17H9l-3 3 3-3h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Reportes de Cuenta Bancaria e Ingresos y Egresos</h3>
                </div>
                
                <p class="ayuda-descripcion">Genere reportes financieros detallados con análisis de ingresos, egresos y cuentas bancarias.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Tipos de Reportes Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Ingresos y Egresos</li>
                            <li>Solo Ingresos</li>
                            <li>Solo Egresos</li>
                            <li>Reportes de Cuentas Bancarias</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>5 tipos de gráficas disponibles</li>
                            <li>Múltiples tipos de reportes</li>
                            <li>Descarga automática en formato PDF</li>
                            <li>Análisis por períodos específicos</li>
                            <li>Información completa de transacciones</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Reporte de Cuenta Bancaria -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2v20M17 5H9l-3 3 3-3h11M17 17H9l-3 3 3-3h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 12l3-3 3 3M17 12l-3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3 7h18M3 17h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Reporte de Cuenta Bancaria</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Genere reportes detallados de cuentas bancarias con análisis de movimientos, saldos y transacciones financieras.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes de Cuenta Bancaria:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                            <li><strong>Paso 2:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                            <li><strong>Paso 3:</strong> Elije el campo de agrupación: (Cliente, Banco, Método o Estatus).</li>
                            <li><strong>Paso 4:</strong> Haga clic en <strong>"Generar Reporte"</strong> para visualizar.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Información Analizada:</h5>
                        <ul>
                            <li><strong>Fecha:</strong> Rango de tiempo específico para el análisis bancario</li>
                            <li><strong>Cliente:</strong> Nombre del cliente asociado a la transacción</li>
                            <li><strong>Cédula:</strong> Identificación fiscal del cliente</li>
                            <li><strong>Banco:</strong> Entidad bancaria donde se realiza la operación</li>
                            <li><strong>Método:</strong> Forma de pago o transferencia utilizada</li>
                            <li><strong>Monto:</strong> Cantidad monetaria de la transacción</li>
                            <li><strong>Estatus:</strong> Estado actual de la transacción</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Campos de Agrupación:</h5>
                        <ul>
                            <li><strong>Por Cliente:</strong> Agrupa transacciones por nombre del cliente</li>
                            <li><strong>Por Banco:</strong> Agrupa transacciones por entidad bancaria</li>
                            <li><strong>Por Método:</strong> Agrupa transacciones por forma de pago</li>
                            <li><strong>Por Estatus:</strong> Agrupa transacciones por estado actual</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Reporte de Ingresos y Egresos -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2v20M17 5H9l-3 3 3-3h11M17 17H9l-3 3 3-3h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 12l3-3 3 3M17 12l-3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3 7h18M3 17h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Reporte de Ingresos y Egresos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Genere reportes financieros detallados con análisis de flujo de dinero, ingresos y egresos de la empresa.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes de Ingresos y Egresos:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Ingrese las fechas: (Inicio y Fin).</li>
                            <li><strong>Paso 2:</strong> Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                            <li><strong>Paso 3:</strong> Elije el tipo de reporte: (Ingresos y Egresos, Solo Ingresos o Solo Egresos).</li>
                            <li><strong>Paso 4:</strong> Haga clic en <strong>"Generar Reporte"</strong> para visualizar.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Tipos de Reportes Disponibles:</h5>
                        <ul>
                            <li><strong>Ingresos y Egresos:</strong> Muestra el flujo completo de dinero entrando y saliendo de la empresa, permitiendo ver el balance neto</li>
                            <li><strong>Solo Ingresos:</strong> Analiza únicamente las fuentes de ingresos, identificando las áreas más rentables del negocio</li>
                            <li><strong>Solo Egresos:</strong> Enfoca en los gastos y salidas de dinero, ayudando a identificar áreas de costo y optimización</li>
                        </ul>
                        
                        <h5 class="tarjeta-subtitulo">Información Financiera Analizada:</h5>
                        <ul>
                            <li><strong>Fechas del Período:</strong> Rango de tiempo específico para el análisis financiero</li>
                            <li><strong>RIF de Contribuyentes:</strong> Identificación fiscal de las partes involucradas en las transacciones</li>
                            <li><strong>Montos Totales:</strong> Suma completa de ingresos y egresos por categoría y período</li>
                            <li><strong>Balance Neto:</strong> Diferencia entre ingresos totales y egresos totales</li>
                            <li><strong>Tendencias Temporales:</strong> Evolución de los flujos de dinero a lo largo del tiempo</li>
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
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>