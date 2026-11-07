<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$esCliente = isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === 'Cliente';
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
            <?php if ($esCliente): ?>
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 2h9l5 5v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 2v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 13h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Mis Pedidos</h3>
                </div>

                <p class="ayuda-descripcion">Consulta tus pedidos y realiza pagos o cancelaciones según corresponda.</p>

                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Pedidos realizados</li>
                            <li>Estado de pagos</li>
                            <li>Información de pedidos</li>
                        </ul>
                    </div>

                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Pagar:</strong> Realizar pago</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Cancelar:</strong> Retractar solicitud</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Pagar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Registra un pago para un pedido seleccionado.</p>
                        <ul>
                            <li>Selecciona el <strong>pedido a pagar</strong></li>
                            <li>Haz clic en <strong>"Pagar"</strong> (verde)</li>
                            <li>Selecciona método de pago (Pago Móvil/Transferencia o Zelle)</li>
                            <li>Ingresa la referencia, monto y adjunta comprobante</li>
                            <li>Presiona <strong>"Registrar Pago"</strong></li>
                        </ul>
                    </div>
                </div>

                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Cancelar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite cancelar un pedido antes de ser procesado.</p>
                        <ul>
                            <li>Selecciona el pedido a cancelar</li>
                            <li>Haz clic en <strong>"Cancelar"</strong> (rojo)</li>
                            <li>Confirma la cancelación en el mensaje de advertencia</li>
                        </ul>
                    </div>
                </div>

                <!-- Nueva tarjeta: Descargar Pedido (solo visible cuando el pago está procesado) -->
                <div class="tarjeta-ayuda tarjeta-descargar" data-tarjeta="descargar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Descargar Pedido</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Descarga tu pedido en PDF cuando el pago esté procesado.</p>
                        <ul>
                            <li>Verifica que el estatus del pago sea <strong>"Pago Procesado"</strong></li>
                            <li>Haz clic en el ícono de <strong>descarga</strong> en la columna "Acciones"</li>
                            <li>Se generará y descargará automáticamente el PDF del pedido</li>
                        </ul>
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

                </div>

                <button class="nav-btn nav-btn-next" id="btnNavNext">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <?php else: ?>
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-4-4H6a2 2 0 0 0-2 2v14z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 3v4a2 2 0 0 0 2 2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Pedidos</h3>
                </div>

                <p class="ayuda-descripcion">Visualiza y gestiona los pedidos de todos los clientes, incluyendo su estatus y seguimiento.</p>

                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información presentada:</h4>
                        <ul class="ayuda-lista">
                            <li>Pedidos por cliente</li>
                            <li>Estatus del pedido y del pago</li>
                            <li>Fechas, totales y detalles</li>
                        </ul>
                    </div>

                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones comunes:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Ver información del pedido</li>
                            <li><strong>Actualizar:</strong> Procesar/cambiar estatus según políticas</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar Pedido</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Consulta los datos completos de un pedido (cliente, productos, totales, estatus y pagos asociados).</p>
                        <ul>
                            <li>Ubica el pedido en la tabla</li>
                            <li>Usa la acción de detalle/visualización disponible</li>
                        </ul>
                    </div>
                </div>

                <div class="tarjeta-ayuda tarjeta-estatus" data-tarjeta="estatus" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 10H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="7" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                                <circle cx="17" cy="14" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Seguimiento / Estatus</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Actualiza o valida el estatus del pedido/pago según el flujo de trabajo del sistema.</p>
                        <ul>
                            <li>Revisa estatus actual del pedido</li>
                            <li>Aplica cambios disponibles según tu rol</li>
                        </ul>
                    </div>
                </div>
                                <div class="tarjeta-ayuda tarjeta-descargar" data-tarjeta="descargar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Descargar Pedidos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Puedes descargar los pedidos de los clientes en PDF cuando el pago esté procesado.</p>
                        <ul>
                            <li>Verifica que el estatus del pago sea <strong>"Pago Procesado"</strong></li>
                            <li>Haz clic en el ícono de <strong>descarga</strong> en la columna "Acciones"</li>
                            <li>Se generará y descargará automáticamente el PDF del pedido</li>
                        </ul>
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
                    <!-- Punto 0: Slide principal (Mis Pedidos) -->
                    <span class="nav-dot nav-dot-active" data-slide="0"></span>
                    <!-- Punto 1: Slide Pagar -->
                    <span class="nav-dot" data-slide="1"></span>
                    <!-- Punto 2: Slide Cancelar -->
                    <span class="nav-dot" data-slide="2"></span>
                    <!-- Punto 3: Slide Descargar (nueva tarjeta) -->
                    <span class="nav-dot" data-slide="3"></span>
                </div>

                <button class="nav-btn nav-btn-next" id="btnNavNext">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
