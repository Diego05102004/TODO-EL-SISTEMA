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
            <!-- Contenido principal (Cliente) -->
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 3h18v4H3V3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 9h18v12H3V9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 13h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Catálogo de Productos</h3>
                </div>

                <p class="ayuda-descripcion">Explora productos y combos disponibles, y agrega artículos al carrito para completar tu compra.</p>

                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">¿Qué puedes hacer?</h4>
                        <ul class="ayuda-lista">
                            <li>Buscar productos</li>
                            <li>Filtrar por marca</li>
                            <li>Ver detalles de un producto</li>
                            <li>Agregar productos al carrito</li>
                            <li>Explorar combos promocionales</li>
                        </ul>
                    </div>

                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Agregar:</strong> Añadir un producto al carrito</li>
                            <li><strong>Detallar:</strong> Ver información del producto</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tarjetas (Cliente) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 6h15l-1.5 9h-13z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="9" cy="20" r="1" stroke="currentColor" stroke-width="2"/>
                                <circle cx="18" cy="20" r="1" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 6l-2-3H1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Agregar al carrito</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Agrega productos al carrito desde el catálogo.</p>
                        <ul>
                            <li>Navega por el catálogo y encuentra el producto deseado</li>
                            <li>Haz clic en el botón <strong>"Agregar"</strong></li>
                            <li>El contador del carrito se actualizará automáticamente</li>
                        </ul>
                    </div>
                </div>

                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar producto</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Consulta la información completa de un producto.</p>
                        <ul>
                            <li>Haz clic sobre el producto (imagen o nombre)</li>
                            <li>Verás descripción, información y productos relacionados</li>
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
                </div>

                <button class="nav-btn nav-btn-next" id="btnNavNext">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <?php else: ?>
            <!-- Contenido principal (Staff) -->
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-4-4H6a2 2 0 0 0-2 2v14z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 3v4a2 2 0 0 0 2 2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión del Catálogo</h3>
                </div>

                <p class="ayuda-descripcion">Gestiona productos y combos promocionales disponibles para los clientes.</p>

                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Productos (Inventario):</h4>
                        <ul class="ayuda-lista">
                            <li>Registrar producto</li>
                            <li>Detallar producto</li>
                            <li>Modificar producto</li>
                            <li>Eliminar producto</li>
                            <li>Cambiar estatus</li>
                            <li>Generar reportes</li>
                        </ul>
                    </div>

                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Combos promocionales:</h4>
                        <ul class="ayuda-lista">
                            <li>Registrar combo</li>
                            <li>Modificar combo</li>
                            <li>Eliminar combo</li>
                            <li>Cambiar estatus</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tarjetas (Staff) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Registrar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Registra un nuevo producto o combo en el sistema.</p>
                        <ul>
                            <li>Productos: haga clic en el botón <strong>"+"</strong> (verde) para crear un nuevo producto</li>
                            <li>Combos: haga clic en <strong>"+ Nuevo Combo"</strong> (azul) para crear un combo promocional</li>
                            <li>Complete los campos requeridos y confirme la acción</li>
                        </ul>
                    </div>
                </div>

                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Consulta la información completa de un producto.</p>
                        <ul>
                            <li>Haga clic en el ícono del <strong>ojo</strong> en la tabla de productos</li>
                            <li>Revise información, stock, categoría, precio y detalles</li>
                        </ul>
                    </div>
                </div>

                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Modificar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Actualiza los datos de un producto o modifica la composición de un combo.</p>
                        <ul>
                            <li>Productos: haga clic en el ícono del <strong>lápiz</strong> en la tabla</li>
                            <li>Combos: use la opción de editar, ajuste productos/cantidades y confirme</li>
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
                        <h4 class="tarjeta-titulo">Eliminar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Elimina un producto o desactiva un combo promocional.</p>
                        <ul>
                            <li>Ubique el registro y haga clic en el ícono de la <strong>X</strong> en "Acciones"</li>
                            <li>Confirme la acción en el mensaje de advertencia</li>
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
                        <h4 class="tarjeta-titulo">Estatus</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Cambia el estatus de productos o combos (habilitado/inhabilitado).</p>
                        <ul>
                            <li>Productos: haga clic sobre el estatus y se actualizará automáticamente</li>
                            <li>Combos: use la acción de cambio de estatus y confirme</li>
                        </ul>
                    </div>
                </div>

                <div class="tarjeta-ayuda tarjeta-reporte" data-tarjeta="reporte" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 19h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 17V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 17V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 17V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Reporte</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Genera reportes y estadísticas relacionadas a productos.</p>
                        <ul>
                            <li>Haga clic en el botón de <strong>gráfica</strong> en la esquina superior derecha</li>
                            <li>Configure filtros y el tipo de reporte</li>
                            <li>Presione <strong>"Generar Reporte"</strong> para visualizar</li>
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
                    <span class="nav-dot" data-slide="4"></span>
                    <span class="nav-dot" data-slide="5"></span>
                    <span class="nav-dot" data-slide="6"></span>
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
