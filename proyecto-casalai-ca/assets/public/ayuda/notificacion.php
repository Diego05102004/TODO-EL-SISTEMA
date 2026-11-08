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
                        <i class="bi bi-bell fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Centro de Notificaciones</h3>
                </div>
                
                <p class="ayuda-descripcion">Consulta y gestiona las notificaciones que el sistema genera sobre pagos, despachos, alertas y eventos del sistema.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información disponible:</h4>
                        <ul class="ayuda-lista">
                            <li>Título y descripción de la notificación</li>
                            <li>Tipo de evento (pago, despacho, sistema, alerta)</li>
                            <li>Prioridad de la notificación</li>
                            <li>Fecha y hora del evento</li>
                            <li>Estado de lectura</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>Actualización en tiempo real mediante long polling</li>
                            <li>Marcar notificaciones individuales o todas como leídas</li>
                            <li>Filtros por estado, importancia, tipo y búsqueda</li>
                            <li>Punto indicador en las notificaciones no leídas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
