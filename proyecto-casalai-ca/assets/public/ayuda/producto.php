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

                        <i class="bi bi-box fs-1"></i>

                    </div>

                    <h3 class="ayuda-titulo">Gestión de Productos</h3>

                </div>

                

                <p class="ayuda-descripcion">Administre el catálogo completo de productos del inventario con información detallada y control de stock.</p>

                

                <div class="ayuda-grid">

                    <div class="ayuda-columna">

                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>

                        <ul class="ayuda-lista">

                            <li>Foto del producto</li>

                            <li>Nombre y descripción</li>

                            <li>Stock Actual/Máximo/Mínimo</li>

                            <li>Número de serial</li>

                            <li>Cláusula de garantía</li>

                            <li>Categoría</li>

                            <li>Precio</li>

                            <li>Estatus</li>

                        </ul>

                    </div>

                    

                    <div class="ayuda-columna">

                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>

                        <ul class="ayuda-lista">

                            <li><strong>Registrar:</strong> Nuevo producto</li>

                            <li><strong>Consultar:</strong> Ver lista completa</li>

                            <li><strong>Detallar:</strong> Ver información completa</li>

                            <li><strong>Modificar:</strong> Actualizar datos</li>

                            <li><strong>Eliminar:</strong> Remover producto</li>

                            <li><strong>Estatus:</strong> Cambiar disponibilidad</li>

                            <li><strong>Reporte:</strong> Generar reportes</li>

                        </ul>

                    </div>

                </div>

            </div>

            

            <!-- Contenido de tarjetas (inicialmente oculto) -->

            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">

                <!-- Tarjeta Registrar Producto -->

                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">

                    <div class="tarjeta-header">

                        <div class="tarjeta-icono">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                            </svg>

                        </div>

                        <h4 class="tarjeta-titulo">Registrar Producto</h4>

                    </div>

                    <div class="tarjeta-contenido">

                        <p>Agregue nuevos productos al catálogo del inventario con toda la información necesaria para su gestión.</p>

                        <h5 class="tarjeta-subtitulo">Pasos para Registrar Nuevo Producto:</h5>

                        <ol>

                            <li><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) en la esquina superior derecha.</li>

                            <li><strong>Paso 2:</strong> Complete todos los campos obligatorios marcados con <strong>*</strong>.</li>

                            <li><strong>Paso 3:</strong> Ingrese el <strong>nombre</strong> del producto.</li>

                            <li><strong>Paso 4:</strong> Seleccione el <strong>modelo/marca</strong> del producto.</li>

                            <li><strong>Paso 5:</strong> Cargue una <strong>imagen</strong> del producto.</li>

                            <li><strong>Paso 6:</strong> Ingrese una <strong>descripción</strong> breve.</li>

                            <li><strong>Paso 7:</strong> Ingrese el <strong>stock</strong> (Actual, Máximo y Mínimo).</li>

                            <li><strong>Paso 8:</strong> Redacte la <strong>cláusula de garantía</strong>.</li>

                            <li><strong>Paso 9:</strong> Seleccione la <strong>categoría</strong> y complete características específicas.</li>

                            <li><strong>Paso 10:</strong> Ingrese el <strong>código serial</strong>.</li>

                            <li><strong>Paso 11:</strong> Ingrese su <strong>precio</strong> de venta.</li>

                            <li><strong>Paso 12:</strong> Haga clic en <strong>"Registrar"</strong> para guardar.</li>

                        </ol>

                        <div class="alert alert-info">

                            <strong>Importante:</strong><br>

                            - La imagen debe estar en formato JPG/PNG<br>

                            - Todos los campos con * son obligatorios<br>

                            - El botón "Limpiar" resetea el formulario

                        </div>

                    </div>

                </div>

                <!-- Tarjeta Detallar Producto -->

                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar">

                    <div class="tarjeta-header">

                        <div class="tarjeta-icono">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                            </svg>

                        </div>

                        <h4 class="tarjeta-titulo">Detallar Producto</h4>

                    </div>

                    <div class="tarjeta-contenido">

                        <p>Visualice toda la información completa de un producto específico del catálogo.</p>

                        <h5 class="tarjeta-subtitulo">Pasos para Detallar Producto:</h5>

                        <ol>

                            <li><strong>Paso 1:</strong> Localice el producto que desea consultar en la tabla.</li>

                            <li><strong>Paso 2:</strong> Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i> en la columna "Acciones".</li>

                            <li><strong>Paso 3:</strong> Se mostrará una ventana con toda la información completa del producto.</li>

                        </ol>

                        <h5 class="tarjeta-subtitulo">Información Visible:</h5>

                        <ul>

                            <li><strong>Datos básicos:</strong> Nombre, descripción, imagen</li>

                            <li><strong>Información de stock:</strong> Actual, máximo y mínimo</li>

                            <li><strong>Detalles técnicos:</strong> Serial, garantía, categoría</li>

                            <li><strong>Información comercial:</strong> Precio, estatus</li>

                            <li><strong>Características específicas:</strong> Según categoría</li>

                        </ul>

                    </div>

                </div>

                <!-- Tarjeta Modificar Producto -->

                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar">

                    <div class="tarjeta-header">

                        <div class="tarjeta-icono">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                            </svg>

                        </div>

                        <h4 class="tarjeta-titulo">Modificar Producto</h4>

                    </div>

                    <div class="tarjeta-contenido">

                        <p>Actualice la información de un producto existente, incluyendo imagen y todos los datos asociados.</p>

                        <h5 class="tarjeta-subtitulo">Pasos para Modificar Producto:</h5>

                        <ol>

                            <li><strong>Paso 1:</strong> Localice el producto que desea modificar en la tabla.</li>

                            <li><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>

                            <li><strong>Paso 3:</strong> Edite los campos necesarios del formulario.</li>

                            <li><strong>Paso 4:</strong> Puede actualizar la imagen si lo desea.</li>

                            <li><strong>Paso 5:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar los cambios.</li>

                        </ol>

                        <h5 class="tarjeta-subtitulo">Campos Modificables:</h5>

                        <ul>

                            <li><strong>Información básica:</strong> Nombre, descripción</li>

                            <li><strong>Stock:</strong> Actual, máximo y mínimo</li>

                            <li><strong>Datos técnicos:</strong> Serial, garantía</li>

                            <li><strong>Categoría:</strong> Cambiar de categoría</li>

                            <li><strong>Precio:</strong> Actualizar precio de venta</li>

                            <li><strong>Imagen:</strong> Reemplazar foto del producto</li>

                        </ul>

                        <div class="alert alert-warning">

                            <strong>Tip:</strong> Los cambios se reflejan inmediatamente en el sistema

                        </div>

                    </div>

                </div>

                <!-- Tarjeta Eliminar Producto -->

                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar">

                    <div class="tarjeta-header">

                        <div class="tarjeta-icono">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                            </svg>

                        </div>

                        <h4 class="tarjeta-titulo">Eliminar Producto</h4>

                    </div>

                    <div class="tarjeta-contenido">

                        <p>Elimine permanentemente un producto del catálogo junto con toda su información asociada.</p>

                        <h5 class="tarjeta-subtitulo">Pasos para Eliminar Producto:</h5>

                        <ol>

                            <li><strong>Paso 1:</strong> Encuentre el producto que desea eliminar en la tabla.</li>

                            <li><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>

                            <li><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>

                            <li><strong>Paso 4:</strong> El producto será eliminado permanentemente.</li>

                        </ol>

                        <h5 class="tarjeta-subtitulo">Datos Eliminados:</h5>

                        <ul>

                            <li><strong>Información básica:</strong> Nombre, descripción</li>

                            <li><strong>Imagen asociada:</strong> Archivo de imagen del producto</li>

                            <li><strong>Stock y datos técnicos:</strong> Serial, garantía</li>

                            <li><strong>Historial:</strong> Todos los registros relacionados</li>

                            <li><strong>Características:</strong> Datos específicos de categoría</li>

                        </ul>

                        <div class="alert alert-danger">

                            <strong>¡Cuidado!</strong><br>

                            Esta acción no se puede deshacer<br>

                            Al eliminar un producto, se eliminarán todos sus datos incluyendo la imagen asociada

                        </div>

                    </div>

                </div>

                <!-- Tarjeta Cambiar Estatus -->

                <div class="tarjeta-ayuda tarjeta-estatus" data-tarjeta="estatus">

                    <div class="tarjeta-header">

                        <div class="tarjeta-icono">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                            </svg>

                        </div>

                        <h4 class="tarjeta-titulo">Cambiar Estatus</h4>

                    </div>

                    <div class="tarjeta-contenido">

                        <p>Alterne la disponibilidad de un producto entre habilitado e inhabilitado para controlar su visibilidad.</p>

                        <h5 class="tarjeta-subtitulo">Pasos para Cambiar Estatus:</h5>

                        <ol>

                            <li><strong>Paso 1:</strong> Localice el producto en la tabla.</li>

                            <li><strong>Paso 2:</strong> Haga clic directamente en el estatus (habilitado/inhabilitado) del producto.</li>

                            <li><strong>Paso 3:</strong> El estatus cambiará automáticamente al estado opuesto.</li>

                        </ol>

                        <h5 class="tarjeta-subtitulo">Tipos de Estatus:</h5>

                        <ul>

                            <li><strong>Habilitado:</strong> Producto visible y disponible para venta</li>

                            <li><strong>Inhabilitado:</strong> Producto oculto y no disponible</li>

                            <li><strong>Cambio instantáneo:</strong> No requiere confirmación</li>

                            <li><strong>Control de inventario:</strong> Gestiona disponibilidad sin eliminar</li>

                        </ul>

                        <div class="alert alert-info">

                            <strong>Instantáneo:</strong> Sin confirmación requerida

                        </div>

                    </div>

                </div>

                <!-- Tarjeta Generar Reporte -->

                <div class="tarjeta-ayuda tarjeta-reporte" data-tarjeta="reporte">

                    <div class="tarjeta-header">

                        <div class="tarjeta-icono">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                            </svg>

                        </div>

                        <h4 class="tarjeta-titulo">Generar Reporte</h4>

                    </div>

                    <div class="tarjeta-contenido">

                        <p>Genere reportes detallados del catálogo de productos con análisis de inventario y estadísticas.</p>

                        <h5 class="tarjeta-subtitulo">Pasos para Generar Reportes:</h5>

                        <ol>

                            <li><strong>Paso 1:</strong> Haga clic en el botón de reportes en la sección de productos.</li>

                            <li><strong>Paso 2:</strong> Seleccione los filtros deseados (categoría, estatus, rango de fechas).</li>

                            <li><strong>Paso 3:</strong> Elija el formato de salida (PDF, Excel).</li>

                            <li><strong>Paso 4:</strong> Haga clic en <strong>"Generar Reporte"</strong>.</li>

                        </ol>

                        <h5 class="tarjeta-subtitulo">Información del Reporte:</h5>

                        <ul>

                            <li><strong>Lista completa:</strong> Todos los productos con filtros aplicados</li>

                            <li><strong>Stock actual:</strong> Niveles de inventario</li>

                            <li><strong>Precios:</strong> Información de costos y ventas</li>

                            <li><strong>Estatus:</strong> Disponibilidad de productos</li>

                            <li><strong>Categorías:</strong> Agrupación por tipo</li>

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

    </div>

</div>