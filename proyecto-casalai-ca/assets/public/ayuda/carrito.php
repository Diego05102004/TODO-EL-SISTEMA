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
                            <path d="M9 2l2 2h4l2-2v2H9v2zm0 4v2h6V6H9zm0 4v2h6v6H9zm0 4v2h6v6H9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 7H7l-3 3 3 3h11M17 17H7l-3 3 3 3h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 9h18M3 15h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 2v20M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión del Carrito de Compras</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre los productos que desea comprar antes de finalizar su pedido con operaciones de ajuste, eliminación y vaciado.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones Disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li>Ajustar Cantidad de Productos</li>
                            <li>Eliminar Productos del Carrito</li>
                            <li>Vaciar Todo el Carrito</li>
                            <li>Ver Totales Actualizados</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Características:</h4>
                        <ul class="ayuda-lista">
                            <li>Actualización en tiempo real</li>
                            <li>Control de inventario</li>
                            <li>Cálculo automático de totales</li>
                            <li>Confirmación de operaciones</li>
                            <li>Interfaz intuitiva</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                
                <!-- Tarjeta Ajustar Cantidad de Productos -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 1v6M12 17v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 3h-8M8 21h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Ajustar Cantidad de Productos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Modifique las cantidades de los productos en su carrito aumentando o disminuyendo las unidades según sus necesidades.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Ajustar Cantidades:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Ubique el producto que desea modificar.</li>
                            <li><strong>Paso 2:</strong> Use los botones <strong>+</strong> y <strong>-</strong> para aumentar o disminuir la cantidad.</li>
                            <li><strong>Paso 3:</strong> El total se actualizará automáticamente.</li>
                            <li><strong>Paso 4:</strong> Puede ingresar directamente la cantidad deseada.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Controles de Cantidad:</h5>
                        <ul>
                            <li><strong>Botón +:</strong> Aumenta la cantidad en una unidad</li>
                            <li><strong>Botón -:</strong> Disminuye la cantidad en una unidad</li>
                            <li><strong>Campo directo:</strong> Permite ingresar la cantidad exacta deseada</li>
                            <li><strong>Actualización automática:</strong> Los totales se recalculan instantáneamente</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Eliminar Productos del Carrito -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2H5a2 2 0 0 0-2V8a2 2 0 0 0-2h14a2 2 0 0 1 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 11h10M7 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Eliminar Productos del Carrito</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Elimine productos individuales de su carrito de compras cuando ya no los necesite o desee modificar su selección.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Eliminar Productos:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Encuentre el producto que desea eliminar.</li>
                            <li><strong>Paso 2:</strong> Haga clic en el ícono de <strong>basura</strong> 🗑️ junto al producto.</li>
                            <li><strong>Paso 3:</strong> Confirme la eliminación en el mensaje emergente.</li>
                            <li><strong>Paso 4:</strong> El producto será removido y el total actualizado.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Características de la Eliminación:</h5>
                        <ul>
                            <li><strong>Confirmación requerida:</strong> Sistema pide confirmación antes de eliminar</li>
                            <li><strong>Actualización automática:</strong> Totales se recalculan inmediatamente</li>
                            <li><strong>Eliminación individual:</strong> Se elimina un producto a la vez</li>
                            <li><strong>Reversibilidad:</strong> Puede volver a agregar el producto si lo necesita</li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta Vaciar Todo el Carrito -->
                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2H5a2 2 0 0 0-2V8a2 2 0 0 0-2h14a2 2 0 0 1 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 11h10M7 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Vaciar Todo el Carrito</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Elimine todo el contenido de su carrito de compras para comenzar una nueva selección de productos desde cero.</p>
                        
                        <h5 class="tarjeta-subtitulo">Pasos para Vaciar el Carrito:</h5>
                        <ol>
                            <li><strong>Paso 1:</strong> Haga clic en el botón <strong>"Vaciar Carrito"</strong>.</li>
                            <li><strong>Paso 2:</strong> Confirme que desea eliminar todos los productos.</li>
                            <li><strong>Paso 3:</strong> El carrito quedará completamente vacío.</li>
                            <li><strong>Paso 4:</strong> Podrá comenzar a agregar nuevos productos.</li>
                        </ol>
                        
                        <h5 class="tarjeta-subtitulo">Ventajas de Vaciar el Carrito:</h5>
                        <ul>
                            <li><strong>Limpieza completa:</strong> Elimina todos los productos de una sola vez</li>
                            <li><strong>Nuevo inicio:</strong> Permite comenzar con una selección limpia</li>
                            <li><strong>Reinicio rápido:</strong> Facilita cambiar completamente de selección</li>
                            <li><strong>Confirmación segura:</strong> Evita eliminaciones accidentales</li>
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
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>