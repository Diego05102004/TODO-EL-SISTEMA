<?php
// Modal de Ayuda - Módulo de Pagos
// Basado en la estructura del manual de usuarios con distinción por rol
?>
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
            <!-- Contenido principal - CLIENTES -->
            <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] == 'Cliente'): ?>
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Mis Pagos</h3>
                </div>
                
                <p class="ayuda-descripcion">Consulte sus pagos realizados para observar si fueron validados o en qué estatus se encuentran.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información presentada:</h4>
                        <ul class="ayuda-lista">
                            <li>Factura</li>
                            <li>Cuenta</li>
                            <li>Tipo de pago</li>
                            <li>Referencia</li>
                            <li>Fecha</li>
                            <li>Estatus</li>
                            <li>Comprobante</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Estatus posibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Pago Procesado:</strong> Pago validado. Ya puede ir a la tienda a retirar su pedido</li>
                            <li><strong>Pago No Encontrado:</strong> Pago invalido. Pago no realizado o número de referencia incorrecto</li>
                            <li><strong>Pago Incompleto:</strong> Pago validado, pero no cubre el monto total de la compra</li>
                        </ul>
                    </div>
                </div>
                
                <div class="ayuda-avisos">
                    <div class="ayuda-aviso ayuda-aviso-info">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <strong>Nota importante:</strong> Una vez que su pago esté en estatus "Pago Procesado", puede proceder a retirar su pedido en la tienda.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de ayuda específicas para CLIENTES -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas">
                <!-- Tarjeta: Ver Comprobante -->
                <div class="tarjeta-ayuda tarjeta-consultar" data-tarjeta="consultar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Ver Comprobante de Pago</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Visualice el comprobante de pago que ha adjuntado para cada transacción.</p>
                        <ul>
                            <li>En la columna <strong>"Comprobante"</strong> haga clic en el ícono de la imagen</li>
                            <li>Se abrirá una ventana modal con la imagen del comprobante</li>
                            <li>Puede verificar que el comprobante sea correcto y esté legible</li>
                            <li>Si el comprobante es incorrecto, contacte con soporte para actualizarlo</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido principal - OTROS USUARIOS -->
            <?php else: ?>
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Pagos</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre los pagos realizados por los clientes, actualizando sus estatus según corresponda.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Estatus de los pagos</li>
                            <li>Comprobantes adjuntos</li>
                            <li>Información de facturas asociadas</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Consultar:</strong> Ver lista completa de pagos</li>
                            <li><strong>Modificar Estatus:</strong> Actualizar estatus (Procesado/No Encontrado/Incompleto)</li>
                            <li><strong>Ver Comprobante:</strong> Visualizar comprobantes adjuntos</li>
                        </ul>
                    </div>
                </div>
                
                <div class="ayuda-avisos">
                    <div class="ayuda-aviso ayuda-aviso-warning">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="12" y1="9" x2="12" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="12" y1="17" x2="12.01" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <strong>Importante:</strong> Los cambios en el estatus de los pagos afectan directamente la disponibilidad de los pedidos para los clientes.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de ayuda específicas para OTROS USUARIOS -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas">
                <!-- Tarjeta: Cambiar Estatus -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Cambiar Estatus del Pago</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Actualice el estatus de los pagos según la verificación realizada.</p>
                        <ul>
                            <li>Haga clic en el botón <strong>"Cambiar Estatus"</strong> (color azul) del pago</li>
                            <li>Seleccione el <strong>estatus</strong> a asignar:</li>
                            <ul>
                                <li><strong>Pago Procesado:</strong> Pago validado correctamente</li>
                                <li><strong>Pago No Encontrado:</strong> Pago no realizado o referencia incorrecta</li>
                                <li><strong>Pago Incompleto:</strong> Pago no cubre el monto total</li>
                            </ul>
                            <li>Haga clic en <strong>"Guardar Cambios"</strong> para confirmar</li>
                        </ul>
                        <div class="tarjeta-alerta">
                            <strong>Precaución:</strong> Verifique bien el comprobante antes de cambiar el estatus a "Pago Procesado".
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta: Ver Comprobante -->
                <div class="tarjeta-ayuda tarjeta-consultar" data-tarjeta="consultar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Ver Comprobante de Pago</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Verifique los comprobantes adjuntos por los clientes para validar los pagos.</p>
                        <ul>
                            <li>En la columna <strong>"Comprobante"</strong> haga clic en el ícono de la imagen</li>
                            <li>Se abrirá una ventana modal con el comprobante en alta resolución</li>
                            <li>Verifique que los datos coincidan con la información del pago</li>
                            <li>Confirme que el monto y la referencia sean correctos</li>
                            <li>Use esta información para decidir el estatus apropiado</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Navegación -->
        <div class="modal-ayuda-navigation">
            <button class="nav-btn nav-btn-prev" id="btnNavPrev" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="nav-indicators">
                <!-- Punto 0: Slide principal -->
                <span class="nav-dot nav-dot-active" data-slide="0"></span>
                <!-- Punto 1: Slide secundario (Ver Comprobante para clientes, Cambiar Estatus para otros) -->
                <span class="nav-dot" data-slide="1"></span>
                <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] != 'Cliente'): ?>
                <!-- Punto 2: Slide Ver Comprobante (solo para otros usuarios) -->
                <span class="nav-dot" data-slide="2"></span>
                <?php endif; ?>
            </div>

            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Script de inicialización del modal -->
<script>
// Inicializar el modal de ayuda cuando se cargue este archivo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof inicializarModalAyudaUsuario === 'function') {
        window.modalAyudaInstance = inicializarModalAyudaUsuario();
    }
});
</script>
