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
                        <i class="bi bi-cash fs-1"></i>
                    </div>
                    <h3 class="ayuda-titulo">Venta Presencial</h3>
                </div>
                
                <p class="ayuda-descripcion">Registre ventas presenciales de productos directamente en el punto de venta.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Cliente seleccionado</li>
                            <li>Productos vendidos</li>
                            <li>Cantidades por producto</li>
                            <li>Métodos de pago</li>
                            <li>Referencias de pago</li>
                            <li>Montos parciales</li>
                            <li>Total de la venta</li>
                            <li>Cambio a devolver</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Proceso de venta:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Buscar cliente:</strong> Seleccione cliente existente</li>
                            <li><strong>Agregar productos:</strong> Busque y añada productos</li>
                            <li><strong>Registrar pagos:</strong> Configure métodos de pago</li>
                            <li><strong>Confirmar venta:</strong> Verifique y procese</li>
                            <li><strong>Generar factura:</strong> Se crea automáticamente</li>
                            <li><strong>Actualizar inventario:</strong> Stock se reduce</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Registrar -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Registrar Venta Presencial</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite registrar una venta presencial completa con múltiples productos y métodos de pago.</p>
                        <ul>
                            <li>Haga clic en el botón (+) Nueva Venta</li>
                            <li>Busque y seleccione un cliente existente</li>
                            <li>Agregue productos buscándolos por código o nombre</li>
                            <li>Configure las cantidades para cada producto</li>
                            <li>Seleccione los métodos de pago a utilizar</li>
                            <li>Ingrese las referencias y montos correspondientes</li>
                            <li>Verifique el total y presione "Procesar Venta"</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Información importante:</strong><br>
                            - Cliente: Puede buscar por nombre o cédula<br>
                            - Productos: Use el buscador o escanee códigos<br>
                            - Cantidades: Verifique stock disponible<br>
                            - Pagos múltiples: Puede combinar métodos<br>
                            - Referencias: Obligatorias para transferencias<br>
                            - Cambio: Se calcula automáticamente
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Cliente -->
                <div class="tarjeta-ayuda tarjeta-cliente" data-tarjeta="cliente" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Clientes</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Seleccione un cliente existente o registre uno nuevo directamente desde la venta.</p>
                        <ul>
                            <li>En el campo "Cliente", comience a escribir el nombre o cédula</li>
                            <li>El sistema mostrará clientes coincidentes</li>
                            <li>Seleccione el cliente deseado de la lista</li>
                            <li>Si no existe, haga clic en "Nuevo" para registrarlo</li>
                            <li>Complete el formulario del nuevo cliente</li>
                            <li>El cliente se seleccionará automáticamente</li>
                        </ul>
                        <div class="alert alert-warning">
                            <strong>Nota:</strong> Todo cliente debe estar registrado en el sistema antes de completar la venta.
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Productos -->
                <div class="tarjeta-ayuda tarjeta-productos" data-tarjeta="productos" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Gestión de Productos</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Agregue productos a la venta verificando disponibilidad y precios.</p>
                        <ul>
                            <li>Use el buscador de productos por código o nombre</li>
                            <li>Seleccione el producto de la lista desplegable</li>
                            <li>Verifique el precio y stock disponible</li>
                            <li>Ingrese la cantidad deseada</li>
                            <li>El subtotal se calcula automáticamente</li>
                            <li>Puede agregar múltiples productos</li>
                            <li>Use el botón eliminar para quitar productos</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Controles:</strong><br>
                            - Stock: Se muestra disponibilidad en tiempo real<br>
                            - Precios: Se cargan automáticamente del sistema<br>
                            - Subtotal: Cantidad × Precio unitario<br>
                            - Total: Suma de todos los subtotales
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Pagos -->
                <div class="tarjeta-ayuda tarjeta-pagos" data-tarjeta="pagos" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Métodos de Pago</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Configure uno o múltiples métodos de pago para completar la venta.</p>
                        <ul>
                            <li>Seleccione el método de pago deseado</li>
                            <li>Efectivo: No requiere referencia</li>
                            <li>Pago Móvil: Ingrese número y referencia</li>
                            <li>Transferencia: Seleccione cuenta y referencia</li>
                            <li>Zelle: Ingrese cuenta y referencia</li>
                            <li>Ingrese el monto para cada método</li>
                            <li>Puede combinar varios métodos</li>
                            <li>El sistema calculará el cambio si aplica</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>Métodos disponibles:</strong><br>
                            - Efectivo (Bs y $)<br>
                            - Pago Móvil<br>
                            - Transferencia bancaria<br>
                            - Zelle<br><br>
                            <strong>Referencias obligatorias:</strong><br>
                            - Pago Móvil: 8 dígitos<br>
                            - Transferencia: Número de referencia<br>
                            - Zelle: Email o teléfono asociado
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Proceso -->
                <div class="tarjeta-ayuda tarjeta-proceso" data-tarjeta="proceso" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Proceso Final</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Verificación y procesamiento final de la venta presencial.</p>
                        <ul>
                            <li>Revise el resumen de la venta</li>
                            <li>Verifique cliente, productos y totales</li>
                            <li>Confirme los métodos de pago configurados</li>
                            <li>Verifique el cambio a devolver (si aplica)</li>
                            <li>Presione "Procesar Venta" para confirmar</li>
                            <li>El sistema generará factura automáticamente</li>
                            <li>El inventario se actualizará</li>
                            <li>Se mostrará confirmación con detalles</li>
                        </ul>
                        <div class="alert alert-success">
                            <strong>Resultados del proceso:</strong><br>
                            ✅ Factura generada<br>
                            ✅ Inventario actualizado<br>
                            ✅ Pagos registrados<br>
                            ✅ Registro contable creado<br>
                            ✅ Comprobante disponible
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
