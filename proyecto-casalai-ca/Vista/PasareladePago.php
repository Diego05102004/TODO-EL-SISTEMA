<?php 
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();if ($_SESSION) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Pagos</title>
    <?php include 'header.php'; ?>
    <style>
        /* ESTILOS COMPACTOS Y MEJORADOS */
        .contenedor-principal {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 0.5rem;
            padding-bottom: 2rem; /* Espacio extra para separar del footer */
            max-width: 1400px;
            margin: 0 auto;
            margin-top: 2rem;
        }

        /* Forzar disposición horizontal en pantallas medianas/grandes */
        @media (min-width: 769px) {
            .contenedor-pagos-horizontal {
                flex-direction: row;
            }
        }
        
        .seccion-datos-bancarios {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .contenedor-paneles-bancarios {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .panel-datos-bancarios {
            padding: 0.75rem;
            border-radius: 6px;
            flex: 1;
            min-width: 280px;
            max-width: 320px;
            font-size: 0.9rem;
        }
        
        .seccion-formularios-pago {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            margin-bottom: 1rem; /* Separación del footer */
        }
        
        /* BLOQUES DE PAGO COMPACTOS */
        .bloque-pago {
            border: 1px solid #ddd;
            padding: 0.75rem;
            margin: 0.5rem;
            border-radius: 6px;
            background: #fafafa;
            position: relative;
            min-width: 280px;
            flex: 1;
            max-width: 320px;
        }
        
        .btn-quitar-pago {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            cursor: pointer;
            font-size: 0.8rem;
            line-height: 1;
        }
        .campos-pago {
            display: none;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed #ccc;
        }
        
        /* FORMULARIOS COMPACTOS */
        .envolver-form {
            margin-bottom: 0.75rem;
        }
        
        .envolver-form label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: block;
            color: #495057;
        }
        
        .control-form {
            padding: 0.4rem 0.6rem;
            font-size: 0.875rem;
            height: auto;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
        }
        
        .btn-pago {
            display: block;
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border-radius: 6px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: #fff;
            border: none;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-pago:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, var(--primary-hover), var(--primary-color));
        }
        
        /* CONTENEDOR HORIZONTAL COMPACTO */
        .contenedor-pagos-horizontal {
            display: flex;
            justify-content: center;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 0.75rem;
            padding: 0.5rem 0;
            min-height: 150px;
            scrollbar-width: thin;
        }
        
        .contenedor-pagos-horizontal::-webkit-scrollbar {
            height: 6px;
        }
        
        .contenedor-pagos-horizontal::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }
        
        /* RESULTADOS COMPACTOS */
        #resumen-pagos {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .error-validacion {
            color: #dc3545;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .paso-activo {
            border-left: 3px solid #28a745 !important;
            background-color: #f0fff4 !important;
        }
        
        .preview-comprobante {
            margin-top: 0.5rem;
            text-align: center;
            display: none;
        }
        
        .preview-comprobante img {
            max-width: 120px;
            max-height: 120px;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 2px;
        }
        
        /* ESTILOS PARA PANTALLAS PEQUEÑAS */
        @media (max-width: 768px) {
            .contenedor-principal {
                padding: 0.25rem;
            }
            
            .seccion-datos-bancarios,
            .seccion-formularios-pago {
                padding: 0.75rem;
            }
            
            .contenedor-paneles-bancarios,
            .contenedor-pagos-horizontal {
                flex-direction: column;
            }
            
            .panel-datos-bancarios,
            .bloque-pago {
                min-width: 100%;
                max-width: 100%;
            }
            
            .contenedor-pagos-horizontal {
                min-height: auto;
            }
        }
        
        /* MEJORAS VISUALES */
        .texto-ayuda {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .estado-correcto {
            color: #28a745;
            font-weight: 600;
        }
        
        .estado-error {
            color: #dc3545;
            font-weight: 600;
        }
        
        /* ESTILOS PARA CONVERSIÓN DE DÓLARES */
        .bolivares-conversion {
            font-weight: bold;
            color: #0d6efd;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
        }
    </style>
</head>
<body class="fondo" style="background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <?php include 'NewNavBar.php'; ?>
    <script>
// Solución robusta para prevenir retroceso en la pasarela
(function() {
    // 1. Reemplazar el historial actual
    if (window.history && window.history.pushState) {
        window.history.pushState(null, null, document.URL);
        
        // 2. Controlar el evento de retroceso
        window.onpopstate = function() {
            // Forzar redirección a gestionarfactura
            window.history.pushState(null, null, '?pagina=gestionarfactura');
            window.location.href = '?pagina=gestionarfactura';
            return false;
        };
    }

    // 3. Prevenir retroceso con teclado
    document.onkeydown = function(e) {
        e = e || window.event;
        
        // Detectar combinaciones de teclas para retroceder
        if (e.keyCode == 8 || // Backspace
            (e.altKey && e.keyCode == 37) || // Alt + Flecha Izquierda
            e.keyCode == 116 || // F5
            (e.ctrlKey && e.keyCode == 82) || // Ctrl + R
            (e.metaKey && e.keyCode == 82) // Cmd + R (Mac)
        ) {
            // Permitir backspace solo en campos de texto
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return true;
            }
            
            // Prevenir la acción por defecto
            e.preventDefault();
            e.stopPropagation();
            
            // Redirigir
            window.history.pushState(null, null, '?pagina=gestionarfactura');
            window.location.href = '?pagina=gestionarfactura';
            return false;
        }
    };
    
    // 4. Controlar clic derecho y menú contextual
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    
    // 5. Prevenir arrastrar imágenes
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // 6. Forzar el estado inicial
    window.onload = function() {
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, null, document.URL);
        }
    };
})();
</script>
<!-- CONTENEDOR PRINCIPAL COMPACTO -->
<div class="main-content">
    <div class="contenedor-principal">
            
        <div class="row">
            <div class="col-md-3">
                <!-- SECCIÓN DE DATOS BANCARIOS COMPACTA -->
                <div class="seccion-datos-bancarios">
                    <h3 class="title-select">Datos Bancarios para Pagos</h3>
                    <div class="contenedor-paneles-bancarios" id="paneles-datos-bancarios">
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <!-- SECCIÓN DE FORMULARIOS DE PAGO COMPACTA -->
                <div class="seccion-formularios-pago">
                    <form id="formularioPago" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="accion" value="ingresar">
                        <h3 class="titulo-form">REGISTRAR PAGOS ONLINE</h3>

                        <div class="col-md-12 mx-auto">
                            <input type="hidden" name="id_factura" value="<?php echo $idFactura; ?>">
                            
                            <!-- Contenedor horizontal compacto para múltiples pagos -->
                            <div class="contenedor-pagos-horizontal" id="pagos-container">
                                <!-- Los bloques de pago se agregarán aquí dinámicamente -->
                            </div>
                        </div>

                        <div class="col-md-7 mx-auto">
                            <!-- Monto total a pagar -->
                            <div class="envolver-form">
                                <label for="monto_total">Monto Total a Pagar</label>
                                <input type="text" class="control-form" id="monto_total" name="monto_total" 
                                    value="<?php echo number_format($monto, 2); ?> Bs" 
                                    readonly style="font-weight: 600; background: #e8f5e8; font-size: 0.9rem;">
                            </div>
                        </div>
                        
                        <!-- Resumen de pagos compacto -->
                        <div id="resumen-pagos" style="display: none;">
                            <h4 style="font-size: 1rem; color: #495057; margin-bottom: 0.75rem;">Resumen de Pagos</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <div style="background: white; padding: 0.75rem; border-radius: 4px; border: 1px solid #dee2e6;">
                                    <div style="font-size: 0.8rem; color: #6c757d;">Total Registrado</div>
                                    <div id="total-registrado" style="font-size: 1rem; font-weight: 600; color: #28a745;">0.00 Bs</div>
                                </div>
                                <div style="background: white; padding: 0.75rem; border-radius: 4px; border: 1px solid #dee2e6;">
                                    <div style="font-size: 0.8rem; color: #6c757d;">Restante por Pagar</div>
                                    <div id="restante-pagar" style="font-size: 1rem; font-weight: 600; color: #dc3545;">
                                        <?php echo number_format($monto, 2); ?> Bs
                                    </div>
                                </div>
                            </div>
                            <p id="mensaje-validacion" class="error-validacion" style="text-align: center; margin: 0.75rem 0 0 0;"></p>
                        </div>

                        <div class="row" style="justify-content: center; align-items: center;">
                            <div class="col-md-4">
                                <!-- Botón compacto para agregar más métodos de pago -->
                                <div style="text-align: center; margin-top: 1rem;">
                                    <button class="btn-pago" type="button" id="agregar-pago">
                                        Agregar Método de Pago
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- Botón de registro compacto -->
                                <div style="text-align: center; margin-top: 1rem;">
                                    <button class="btn-pago" type="submit" id="btn-registrar" disabled>
                                        Registrar Pagos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <!-- FOOTER -->
    <?php include 'footer.php'; ?>
    
    <!-- Scripts -->
    <script src="assets/public/bootstrap/js/sidebar.js"></script>
    <script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/public/js/jquery-3.7.1.min.js"></script>
    <script src="assets/javascript/sweetalert2.all.min.js"></script>
    <script src="assets/javascript/pago.js"></script>

    <script>
    $(document).ready(function() {
        // Asegurar número JS válido con punto decimal
        const montoTotal = parseFloat('<?= number_format($monto, 2, '.', '') ?>');
        const tasaDolar = <?php echo $data['monitors']['bcv']['price']; ?>;
        let contadorPagos = 0;
        const MAX_REF_DIGITS = 12; // tope de dígitos para referencia Pago Móvil/Transferencia
        // Formateador global: 1234.56 -> 1,234.56 (comma thousands, dot decimals)
        function formatearNumero(n) {
            const num = Number(n) || 0;
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        // Parseador robusto: acepta coma de miles y punto decimal; también soporta 1.234,56 -> 1234.56
        function parsearNumeroFormateado(texto) {
            if (texto === null || texto === undefined) return 0;
            let s = String(texto).trim();
            if (s === '') return 0;
            
            // Eliminar caracteres no numéricos excepto punto, coma y signo menos
            s = s.replace(/[^0-9.,\-]/g, '');
            
            // Si no hay separadores decimales, convertir directamente
            if (!s.includes('.') && !s.includes(',')) {
                const n = parseFloat(s);
                return isNaN(n) ? 0 : n;
            }
            
            // Detectar el separador decimal correcto
            const lastDot = s.lastIndexOf('.');
            const lastComma = s.lastIndexOf(',');
            
            // Si hay ambos separadores, asumir formato europeo (1.234,56)
            if (lastDot >= 0 && lastComma >= 0) {
                if (lastComma > lastDot) {
                    // Formato europeo: 1.234,56 -> 1234.56
                    const integerPart = s.slice(0, lastComma).replace(/[.,]/g, '');
                    const decimalPart = s.slice(lastComma + 1).replace(/[^0-9]/g, '');
                    const composed = integerPart + '.' + decimalPart;
                    const n = parseFloat(composed);
                    return isNaN(n) ? 0 : n;
                } else {
                    // Formato americano: 1,234.56 -> 1234.56
                    const integerPart = s.slice(0, lastDot).replace(/,/g, '');
                    const decimalPart = s.slice(lastDot + 1).replace(/[^0-9]/g, '');
                    const composed = integerPart + '.' + decimalPart;
                    const n = parseFloat(composed);
                    return isNaN(n) ? 0 : n;
                }
            } else {
                // Solo un separador decimal
                const decIdx = Math.max(lastDot, lastComma);
                const integerPart = s.slice(0, decIdx).replace(/[^0-9\-]/g, '');
                let decimalPart = s.slice(decIdx + 1).replace(/[^0-9]/g, '');
                
                // Limitar decimales a máximo 2 para evitar problemas
                if (decimalPart.length > 2) {
                    decimalPart = decimalPart.substring(0, 2);
                }
                
                const composed = integerPart + '.' + decimalPart;
                const n = parseFloat(composed);
                return isNaN(n) ? 0 : n;
            }
        }
        
        // Datos de las cuentas disponibles
        const cuentas = <?php echo json_encode($listadocuentas); ?>;
        
        // Inicializar con un método de pago
        agregarBloquePago();
        
        // Función para agregar un nuevo bloque de pago compacto
        function agregarBloquePago() {
            const id = contadorPagos++;
            const bloquePago = `
                <div class="bloque-pago" id="bloque-pago-${id}">
                    <button type="button" class="btn-quitar-pago" ${id === 0 ? 'style="display:none;"' : ''}>×</button>
                    <h4 style="color: #495057; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        Método ${id + 1}
                    </h4>
                    
                    <!-- PRIMER PASO: Seleccionar tipo de pago -->
                    <div class="envolver-form paso-activo" id="paso-tipo-${id}">
                        <label for="tipo-${id}" class="title-select">Método de Pago *</label>
                        <select id="tipo-${id}" name="pagos[${id}][tipo]" class="control-form" required>
                            <option value="" disabled selected>Seleccione método</option>
                            <option value="Pago Movil">Pago Móvil</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Zelle">Zelle</option>
                        </select>
                        <span class="error-validacion" id="error-tipo-${id}"></span>
                    </div>
                    
                    <!-- SEGUNDO PASO: Seleccionar cuenta -->
                    <div class="envolver-form" id="contenedor-cuenta-${id}" style="display:none;">
                        <label for="cuenta-${id}">Cuenta Bancaria *</label>
                        <select id="cuenta-${id}" name="pagos[${id}][cuenta]" class="control-form" required>
                            <option value="" disabled selected>Seleccione cuenta</option>
                        </select>
                        <span class="error-validacion" id="error-cuenta-${id}"></span>
                    </div>
                    
                    <!-- TERCER PASO: Campos específicos del pago -->
                    <div class="campos-pago" id="campos-pago-${id}">
                        <!-- Campos específicos se mostrarán según el tipo de pago -->
                    </div>
                </div>
            `;
            
            $('#pagos-container').append(bloquePago);
            configurarEventosBloque(id);
            renumerarMetodosPago();
        }
        
        // Configurar eventos para un bloque de pago
        function configurarEventosBloque(id) {
            $(`#tipo-${id}`).on('change', function() {
                const tipoPago = $(this).val();
                $(`#error-tipo-${id}`).text('');
                
                if (tipoPago) {
                    cargarCuentasPorMetodo(id, tipoPago);
                    $(`#contenedor-cuenta-${id}`).show();
                    $(`#paso-tipo-${id}`).removeClass('paso-activo');
                    $(`#contenedor-cuenta-${id}`).addClass('paso-activo');
                    $(`#campos-pago-${id}`).hide();
                    $(`#panel-datos-${id}`).hide();
                    
                    // Actualizar campos según el tipo de pago
                    actualizarCamposPago(id, tipoPago);
                } else {
                    $(`#contenedor-cuenta-${id}`).hide();
                    $(`#campos-pago-${id}`).hide();
                    $(`#panel-datos-${id}`).hide();
                }
            });
            
            $(`#cuenta-${id}`).on('change', function() {
                const cuentaId = $(this).val();
                $(`#error-cuenta-${id}`).text('');
                
                if (cuentaId) {
                    mostrarDatosCuenta(id, cuentaId);
                    $(`#contenedor-cuenta-${id}`).removeClass('paso-activo');
                    $(`#campos-pago-${id}`).addClass('paso-activo').show();
                    $(`#panel-datos-${id}`).show();
                } else {
                    $(`#campos-pago-${id}`).hide();
                    $(`#panel-datos-${id}`).hide();
                }
            });
            
            $(`#bloque-pago-${id} .btn-quitar-pago`).on('click', function() {
                const selectId = `cuenta-${id}`;
                // Remover protección al eliminar el bloque
                selectProtection.removerProteccion(selectId);
                
                $(this).closest('.bloque-pago').remove();
                $(`#panel-datos-${id}`).remove();
                actualizarResumenPagos();
                renumerarMetodosPago();
            });
        }
        
        // Cargar cuentas según el método de pago seleccionado
        function cargarCuentasPorMetodo(id, tipoPago) {
            const selectId = `cuenta-${id}`;
            const $cuentaSelect = $(`#${selectId}`);
            
            // Limpiar select temporalmente
            $cuentaSelect.empty().append('<option value="" disabled selected>Seleccione una cuenta</option>');
            
            // Filtrar cuentas según el método de pago
            const cuentasFiltradas = cuentas.filter(cuenta => 
                cuenta.metodos && cuenta.metodos.includes(tipoPago)
            ).map(cuenta => ({
                value: cuenta.id_cuenta,
                texto: `${cuenta.nombre_banco} - ${cuenta.numero_cuenta}`
            }));

            // Usar el sistema de protección para actualizar el select de manera segura
            selectProtection.actualizarSelectSeguro(selectId, cuentasFiltradas);
            
            // Mostrar el contenedor de cuenta
            $(`#contenedor-cuenta-${id}`).show();
        }
        
        // Mostrar datos de la cuenta en el panel superior
        function mostrarDatosCuenta(id, cuentaId) {
            const cuenta = cuentas.find(c => c.id_cuenta == cuentaId);
            if (cuenta) {
                $(`#panel-datos-${id}`).remove();
                
                const panelHtml = `
                    <div class="panel-datos-bancarios" id="panel-datos-${id}">
                        <h4 style="margin-bottom: 0.5rem; font-size: 1rem;">
                            Datos para Pago ${id + 1}
                        </h4>
                        <div>
                            <p style="margin: 0.2rem 0; font-size: 0.8rem;"><strong>Banco:</strong> ${cuenta.nombre_banco}</p>
                            <p style="margin: 0.2rem 0; font-size: 0.8rem;"><strong>Número:</strong> ${cuenta.numero_cuenta}</p>
                            <p style="margin: 0.2rem 0; font-size: 0.8rem;"><strong>RIF:</strong> ${cuenta.rif_cuenta}</p>
                            <p style="margin: 0.2rem 0; font-size: 0.8rem;"><strong>Teléfono:</strong> ${cuenta.telefono_cuenta}</p>
                            <p style="margin: 0.2rem 0; font-size: 0.8rem;"><strong>Correo:</strong> ${cuenta.correo_cuenta}</p>
                            <p style="margin: 0.2rem 0; font-size: 0.8rem;"><strong>Tipo:</strong> ${cuenta.tipo_cuenta || 'No especificado'}</p>
                            <hr style="margin: 0.5rem 0;">
                            <p style="color: #28a745; font-weight: 600; font-size: 0.75rem; margin: 0;">
                                Utilice estos datos para realizar su pago
                            </p>
                        </div>
                    </div>
                `;
                
                $('#paneles-datos-bancarios').append(panelHtml);
            }
        }
        
        // Actualizar campos según el tipo de pago seleccionado
        function actualizarCamposPago(id, tipoPago) {
            const $camposPago = $(`#campos-pago-${id}`);
            let campos = '';
            
            if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia') {
                campos = `
                    <div class="envolver-form">
                        <label for="referencia-${id}">Número de referencia *</label>
                        <input type="text" id="referencia-${id}" name="pagos[${id}][referencia]" 
                               class="control-form" placeholder="Ej: 123456789" required 
                               maxlength="${MAX_REF_DIGITS}" inputmode="numeric" pattern="[0-9]*">
                        <span class="error-validacion" id="error-referencia-${id}"></span>
                    </div>
                    <div class="envolver-form">
                        <label for="comprobante-${id}">Comprobante (imagen) *</label>
                        <input type="file" id="comprobante-${id}" name="pagos[${id}][comprobante]" 
                               class="control-form" accept="image/*" required>
                        <span class="error-validacion" id="error-comprobante-${id}"></span>
                        <div class="preview-comprobante" id="preview-${id}">
                            <img src="" alt="Vista/ previa del comprobante" id="img-preview-${id}">
                            <p style="font-size: 0.75rem; margin: 0.25rem 0;">Vista/ previa del comprobante</p>
                        </div>
                    </div>
                    <div class="envolver-form">
                        <label for="monto-${id}">Monto (Bs) *</label>
                        <input type="text" id="monto-${id}" name="pagos[${id}][monto]" class="control-form monto-pago" 
                               inputmode="decimal" pattern="[0-9.,]*" placeholder="0.00" required>
                        <span class="error-validacion" id="error-monto-${id}"></span>
                    </div>
                `;
            } 
            else if (tipoPago === 'Zelle') {
                campos = `
                    <div class="envolver-form">
                        <label for="propietario-${id}">Propietario del Zelle *</label>
                        <input type="text" id="propietario-${id}" name="pagos[${id}][propietario]" 
                               class="control-form" placeholder="Nombre del propietario" required>
                        <span class="error-validacion" id="error-propietario-${id}"></span>
                    </div>
                    <div class="envolver-form">
                        <label for="referencia-${id}">Referencia Zelle *</label>
                        <input type="text" id="referencia-${id}" name="pagos[${id}][referencia]" 
                               class="control-form" placeholder="Referencia de la transacción" required>
                        <span class="error-validacion" id="error-referencia-${id}"></span>
                    </div>
                    <div class="envolver-form">
                        <label for="comprobante-${id}">Comprobante (imagen) *</label>
                        <input type="file" id="comprobante-${id}" name="pagos[${id}][comprobante]" 
                               class="control-form" accept="image/*" required>
                        <span class="error-validacion" id="error-comprobante-${id}"></span>
                        <div class="preview-comprobante" id="preview-${id}">
                            <img src="" alt="Vista/ previa del comprobante" id="img-preview-${id}">
                            <p style="font-size: 0.75rem; margin: 0.25rem 0;">Vista/ previa del comprobante</p>
                        </div>
                    </div>
                    <div class="envolver-form">
                        <label for="monto-dolar-${id}">Monto ($) *</label>
                        <input type="text" id="monto-dolar-${id}" name="pagos[${id}][monto_dolar]" 
                               class="control-form monto-dolar" inputmode="decimal" pattern="[0-9.,]*" 
                               placeholder="0.00" required onkeydown="return validarTeclaMonto(event)">
                        <span class="bolivares-conversion" id="conversion-${id}"></span>
                        <input type="hidden" name="pagos[${id}][monto]" id="monto-bs-${id}">
                        <span class="error-validacion" id="error-monto-${id}"></span>
                    </div>
                `;
            }
            
            $camposPago.html(campos).show();
            configurarEventosCampos(id, tipoPago);
            actualizarResumenPagos();
        }
        
        // Configurar eventos para los campos del pago
        function configurarEventosCampos(id, tipoPago) {
            // Helpers de formato/parseo (en-US visual)
            // formatearNumero / parsearNumeroFormateado ya definidos arriba

            // Preview de comprobante
            $(`#comprobante-${id}`).on('change', function(e) {
                validarComprobante(id);
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $(`#img-preview-${id}`).attr('src', e.target.result);
                        $(`#preview-${id}`).show();
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Validación de monto
            if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia') {
                // valor inicial
                $(`#monto-${id}`).val('0.00');
                $(`#monto-${id}`).on('input', function(e) {
                    const input = this;
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const originalValue = input.value;
                    
                    // Forzar formato automáticamente en cada pulsación
                    let v = input.value.replace(/[^0-9.,]/g, '');
                    const montoNum = parsearNumeroFormateado(v);
                    const formattedValue = formatearNumero(montoNum);
                    
                    // Solo actualizar si el valor cambió
                    if (originalValue !== formattedValue) {
                        input.value = formattedValue;
                        
                        // Mantener posición del cursor de forma inteligente
                        let newCursorPos = start;
                        
                        // Si el formato agregó comas, ajustar posición
                        const originalCommas = (originalValue.match(/,/g) || []).length;
                        const newCommas = (formattedValue.match(/,/g) || []).length;
                        newCursorPos += (newCommas - originalCommas);
                        
                        // Limitar posición del cursor para no pasar de los decimales
                        const decimalPos = formattedValue.lastIndexOf('.');
                        if (decimalPos !== -1 && newCursorPos > decimalPos) {
                            newCursorPos = decimalPos;
                        }
                        
                        // Restaurar posición del cursor
                        setTimeout(() => {
                            input.setSelectionRange(newCursorPos, newCursorPos);
                        }, 0);
                    }
                    
                    validarMonto(id, montoNum, 'BS');
                    actualizarResumenPagos();
                });
            }
            
            // Validación de monto en dólares (Zelle)
            if (tipoPago === 'Zelle') {
                $(`#monto-dolar-${id}`).on('input', function(e) {
                    const input = this;
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const originalValue = input.value;
                    
                    // Forzar formato automáticamente
                    let v = input.value.replace(/[^0-9.,]/g, '');
                    const montoDolar = parsearNumeroFormateado(v);
                    const formattedValue = formatearNumero(montoDolar);
                    
                    // Solo actualizar si el valor cambió
                    if (originalValue !== formattedValue) {
                        input.value = formattedValue;
                        
                        // Mantener posición del cursor de forma inteligente
                        let newCursorPos = start;
                        
                        // Si el formato agregó comas, ajustar posición
                        const originalCommas = (originalValue.match(/,/g) || []).length;
                        const newCommas = (formattedValue.match(/,/g) || []).length;
                        newCursorPos += (newCommas - originalCommas);
                        
                        // Limitar posición del cursor para no pasar de los decimales
                        const decimalPos = formattedValue.lastIndexOf('.');
                        if (decimalPos !== -1 && newCursorPos > decimalPos) {
                            newCursorPos = decimalPos;
                        }
                        
                        // Restaurar posición del cursor
                        setTimeout(() => {
                            input.setSelectionRange(newCursorPos, newCursorPos);
                        }, 0);
                    }
                    
                    validarMonto(id, montoDolar, 'USD');
                    // Conversión a bolívares
                    if (!isNaN(montoDolar) && montoDolar > 0) {
                        const montoBs = (montoDolar * tasaDolar);
                        $(`#conversion-${id}`).text(`Equivalente: ${formatearNumero(montoBs)} Bs`);
                        $(`#monto-bs-${id}`).val(montoBs.toFixed(2));
                    } else {
                        $(`#conversion-${id}`).text('');
                        $(`#monto-bs-${id}`).val('');
                    }
                    actualizarResumenPagos();
                });
            }
            
            // Validación de referencia (solo números para algunos métodos)
            if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia') {
                $(`#referencia-${id}`).on('input', function() {
                    const valor = $(this).val();
                    if (!/^\d+$/.test(valor)) {
                        $(this).val(valor.replace(/[^\d]/g, ''));
                    }
                    // Limitar a MAX_REF_DIGITS
                    if (this.value.length > MAX_REF_DIGITS) {
                        this.value = this.value.slice(0, MAX_REF_DIGITS);
                    }
                    validarReferencia(id);
                });
            }
            
            // Validación de propietario (Zelle)
            if (tipoPago === 'Zelle') {
                $(`#propietario-${id}`).on('input', function() {
                    validarPropietario(id);
                });
                
                $(`#referencia-${id}`).on('input', function() {
                    validarReferenciaZelle(id);
                });
            }
        }
        
        // ===== FUNCIONES DE VALIDACIÓN =====
        
        function validarTeclaMonto(e) {
            if (e.key === '+' || e.key === '-' || e.keyCode === 187 || e.keyCode === 189) {
                return false;
            }
            return true;
        }
        
        function validarMonto(id, monto, moneda) {
            let montoNum = 0;
            if (typeof monto === 'number') {
                montoNum = monto;
            } else if (moneda === 'BS') {
                // Aceptar 1,234.56 o 1.234,56 o 1234.56
                montoNum = parsearNumeroFormateado(monto);
            } else {
                montoNum = parsearNumeroFormateado(monto);
            }
            const $error = $(`#error-monto-${id}`);
            
            if (montoNum <= 0) {
                $error.text('El monto debe ser mayor a 0');
                return false;
            } else if (moneda === 'BS' && montoNum > montoTotal) {
                $error.text('El monto no puede ser mayor al total a pagar');
                return false;
            } else {
                $error.text('');
                return true;
            }
        }
        
        function validarReferencia(id) {
            const referencia = $(`#referencia-${id}`).val();
            const $error = $(`#error-referencia-${id}`);
            
            if (!referencia) {
                $error.text('La referencia es obligatoria');
                return false;
            } else if (!/^\d+$/.test(referencia)) {
                $error.text('La referencia debe contener solo números');
                return false;
            } else if (referencia.length < 6) {
                $error.text('La referencia debe tener al menos 6 dígitos');
                return false;
            } else if (referencia.length > MAX_REF_DIGITS) {
                $error.text(`La referencia no debe exceder ${MAX_REF_DIGITS} dígitos`);
                return false;
            } else {
                $error.text('');
                return true;
            }
        }
        
        function validarReferenciaZelle(id) {
            const referencia = $(`#referencia-${id}`).val();
            const $error = $(`#error-referencia-${id}`);
            
            if (!referencia) {
                $error.text('La referencia Zelle es obligatoria');
                return false;
            } else if (referencia.length < 3) {
                $error.text('La referencia debe tener al menos 3 caracteres');
                return false;
            } else {
                $error.text('');
                return true;
            }
        }
        
        function validarPropietario(id) {
            const propietario = $(`#propietario-${id}`).val();
            const $error = $(`#error-propietario-${id}`);
            
            if (!propietario) {
                $error.text('El propietario del Zelle es obligatorio');
                return false;
            } else if (propietario.length < 3) {
                $error.text('El nombre debe tener al menos 3 caracteres');
                return false;
            } else {
                $error.text('');
                return true;
            }
        }
        
        function validarComprobante(id) {
            const archivo = $(`#comprobante-${id}`)[0].files[0];
            const $error = $(`#error-comprobante-${id}`);
            
            if (!archivo) {
                $error.text('El comprobante es obligatorio');
                return false;
            } else if (!archivo.type.match('image.*')) {
                $error.text('El archivo debe ser una imagen (JPEG, PNG, etc.)');
                return false;
            } else if (archivo.size > 5 * 1024 * 1024) {
                $error.text('La imagen no debe pesar más de 5MB');
                return false;
            } else {
                $error.text('');
                return true;
            }
        }
        
        // Validar formulario completo antes de enviar
        function validarFormularioCompleto() {
            let esValido = true;
            
            $('.bloque-pago').each(function() {
                const id = $(this).attr('id').split('-').pop();
                const tipoPago = $(`#tipo-${id}`).val();
                
                // Validar tipo de pago
                if (!tipoPago) {
                    $(`#error-tipo-${id}`).text('Seleccione un método de pago');
                    esValido = false;
                }
                
                // Validar cuenta si aplica
                if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia' || tipoPago === 'Zelle') {
                    const cuenta = $(`#cuenta-${id}`).val();
                    if (!cuenta) {
                        $(`#error-cuenta-${id}`).text('Seleccione una cuenta');
                        esValido = false;
                    }
                }
                
                // Validar campos específicos
                if (tipoPago === 'Zelle') {
                    if (!validarPropietario(id)) esValido = false;
                    if (!validarReferenciaZelle(id)) esValido = false;
                } else if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia') {
                    if (!validarReferencia(id)) esValido = false;
                }
                
                if (!validarComprobante(id)) esValido = false;
                
                // Validar monto
                let monto = 0;
                if (tipoPago === 'Zelle') {
                    monto = $(`#monto-dolar-${id}`).val();
                    if (!validarMonto(id, monto, 'USD')) esValido = false;
                } else {
                    monto = $(`#monto-${id}`).val();
                    if (!validarMonto(id, monto, 'BS')) esValido = false;
                }
            });
            
            // Validar que la suma coincida con el total
            const totalRegistrado = calcularTotalRegistrado();
            if (Math.abs(totalRegistrado - montoTotal) > 0.01) {
                $('#mensaje-validacion').text(`La suma de los montos (${formatearNumero(totalRegistrado)} Bs) no coincide con el total (${formatearNumero(montoTotal)} Bs)`);
                esValido = false;
            }
            
            return esValido;
        }
        
        // Calcular total registrado
        function calcularTotalRegistrado() {
            let total = 0;
            
            $('.monto-pago').each(function() {
                const val = $(this).val();
                const monto = parsearNumeroFormateado(val);
                total += monto;
            });
            
            // Incluir montos de Zelle convertidos
            $('.monto-dolar').each(function() {
                const montoDolar = parsearNumeroFormateado($(this).val());
                total += (montoDolar * tasaDolar);
            });
            
            return total;
        }
        
        // Actualizar el resumen de pagos
        function actualizarResumenPagos() {
            const totalRegistrado = calcularTotalRegistrado();
            const restante = montoTotal - totalRegistrado;
            
            $('#total-registrado').text(formatearNumero(totalRegistrado) + ' Bs');
            $('#restante-pagar').text(formatearNumero(restante) + ' Bs');
            
            if (totalRegistrado > 0) {
                $('#resumen-pagos').show();
            } else {
                $('#resumen-pagos').hide();
            }
            
            const $mensajeValidacion = $('#mensaje-validacion');
            if (Math.abs(restante) < 0.01) {
                $mensajeValidacion.text('Montos coinciden correctamente').removeClass('error-validacion').addClass('estado-correcto');
                $('#btn-registrar').prop('disabled', false);
            } else if (restante > 0) {
                $mensajeValidacion.text(`Faltan ${restante.toFixed(2)} Bs por registrar`).addClass('error-validacion');
                $('#btn-registrar').prop('disabled', true);
            } else {
                $mensajeValidacion.text(`Ha registrado ${(-restante).toFixed(2)} Bs de más`).addClass('error-validacion');
                $('#btn-registrar').prop('disabled', true);
            }
        }
        
        // Renumerar métodos de pago
        function renumerarMetodosPago() {
            $('.bloque-pago').each(function(index) {
                $(this).find('h4').text(`Método ${index + 1}`);
            });
            
            $('.panel-datos-bancarios').each(function(index) {
                $(this).find('h4').text(`Datos para Pago ${index + 1}`);
            });
        }
        
        // Evento para agregar otro método de pago
        $('#agregar-pago').on('click', function() {
            if ($('.bloque-pago').length >= 5) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Límite alcanzado',
                    text: 'Solo puedes agregar hasta 5 métodos de pago',
                    confirmButtonColor: '#007bff'
                });
                return;
            }
            agregarBloquePago();
        });
        
    });
    </script>
    <script>
// Sistema de protección mejorado para selects
const selectProtection = {
    // Almacena los selects protegidos
    protectedSelects: new Map(),
    
    // Proteger un select individual
    protegerSelect: function(selectId) {
        const select = document.getElementById(selectId);
        if (!select || this.protectedSelects.has(selectId)) return;

        console.log(`🔒 Aplicando protección al select: ${selectId}`);
        
        // Guardar estado original
        const estadoOriginal = {
            options: Array.from(select.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                selected: opt.selected
            })),
            selectedValue: select.value
        };
        
        this.protectedSelects.set(selectId, {
            estadoOriginal,
            intervalo: null,
            ultimaActualizacion: Date.now()
        });

        // Iniciar monitoreo
        this.iniciarMonitoreo(selectId);
    },
    
    // Iniciar monitoreo del select
    iniciarMonitoreo: function(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;

        const proteccion = this.protectedSelects.get(selectId);
        if (!proteccion) return;

        // Limpiar intervalo anterior si existe
        if (proteccion.intervalo) {
            clearInterval(proteccion.intervalo);
        }

        // Configurar nuevo intervalo
        proteccion.intervalo = setInterval(() => {
            this.verificarIntegridad(selectId);
        }, 800);
    },
    
    // Verificar integridad del select
    verificarIntegridad: function(selectId) {
        const select = document.getElementById(selectId);
        const proteccion = this.protectedSelects.get(selectId);
        
        if (!select || !proteccion) return;

        const opcionesActuales = Array.from(select.options);
        const opcionesOriginales = proteccion.estadoOriginal.options;

        // Verificar si hay cambios no autorizados
        const cambiosDetectados = 
            opcionesActuales.length !== opcionesOriginales.length ||
            opcionesActuales.some((opt, i) => {
                const original = opcionesOriginales[i];
                return !original || 
                       opt.value !== original.value || 
                       opt.textContent !== original.text;
            });

        if (cambiosDetectados && !select._cambioAutorizado) {
            console.warn(`⚠ Select ${selectId} fue manipulado. Restaurando...`);
            this.restaurarSelect(selectId);
        }
        
        // Resetear flag de cambio autorizado después de la verificación
        if (select._cambioAutorizado) {
            select._cambioAutorizado = false;
        }
    },
    
    // Restaurar select a su estado original
    restaurarSelect: function(selectId) {
        const select = document.getElementById(selectId);
        const proteccion = this.protectedSelects.get(selectId);
        
        if (!select || !proteccion) return;

        // Guardar valor actualmente seleccionado si existe en las opciones originales
        const valorActual = select.value;
        const existeEnOriginal = proteccion.estadoOriginal.options.some(opt => opt.value === valorActual);
        
        // Restaurar opciones
        select.innerHTML = '';
        proteccion.estadoOriginal.options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            if (opt.selected || (existeEnOriginal && opt.value === valorActual)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        proteccion.ultimaActualizacion = Date.now();
    },
    
    // Actualizar select de manera segura (para cambios programáticos)
    actualizarSelectSeguro: function(selectId, nuevasOpciones) {
        const select = document.getElementById(selectId);
        if (!select) return;

        console.log(`🔄 Actualización segura del select: ${selectId}`, nuevasOpciones);
        
        // Marcar cambio como autorizado
        select._cambioAutorizado = true;
        
        // Guardar valor seleccionado actual
        const valorAnterior = select.value;
        
        // Limpiar y reconstruir opciones
        select.innerHTML = '';
        
        // Agregar opción por defecto
        const opcionDefault = document.createElement('option');
        opcionDefault.value = '';
        opcionDefault.textContent = 'Seleccione una cuenta';
        opcionDefault.disabled = true;
        opcionDefault.selected = true;
        select.appendChild(opcionDefault);
        
        // Agregar nuevas opciones
        nuevasOpciones.forEach(opcion => {
            const option = document.createElement('option');
            option.value = opcion.value;
            option.textContent = opcion.texto;
            select.appendChild(option);
        });
        
        // Intentar restaurar selección anterior si existe
        if (valorAnterior && nuevasOpciones.some(op => op.value === valorAnterior)) {
            select.value = valorAnterior;
        }
        
        // Actualizar estado de protección
        this.actualizarEstadoProteccion(selectId, nuevasOpciones);
    },
    
    // Actualizar estado de protección después de cambio autorizado
    actualizarEstadoProteccion: function(selectId, nuevasOpciones) {
        const proteccion = this.protectedSelects.get(selectId);
        if (!proteccion) {
            // Si no existe protección, crear una nueva
            this.protegerSelect(selectId);
            return;
        }
        
        // Actualizar estado original con las nuevas opciones
        proteccion.estadoOriginal = {
            options: nuevasOpciones.map(opcion => ({
                value: opcion.value,
                text: opcion.texto,
                selected: false
            })),
            selectedValue: ''
        };
        
        proteccion.ultimaActualizacion = Date.now();
        
        // Reiniciar monitoreo
        this.iniciarMonitoreo(selectId);
    },
    
    // Remover protección de un select
    removerProteccion: function(selectId) {
        const proteccion = this.protectedSelects.get(selectId);
        if (proteccion && proteccion.intervalo) {
            clearInterval(proteccion.intervalo);
        }
        this.protectedSelects.delete(selectId);
        
        const select = document.getElementById(selectId);
        if (select) {
            delete select._cambioAutorizado;
        }
        
        console.log(`🔓 Removiendo protección del select: ${selectId}`);
    },
    
    // Limpiar todas las protecciones
    limpiarTodasProtecciones: function() {
        this.protectedSelects.forEach((proteccion, selectId) => {
            if (proteccion.intervalo) {
                clearInterval(proteccion.intervalo);
            }
        });
        this.protectedSelects.clear();
    }
};

// Aplicar protección inicial a los selects de tipo de pago
$(document).ready(function() {
    // Proteger los selects de tipo de pago inmediatamente
    $('select[id^="tipo-"]').each(function() {
        selectProtection.protegerSelect(this.id);
    });
    
    // Limpiar protecciones al recargar la página
    $(window).on('beforeunload', function() {
        selectProtection.limpiarTodasProtecciones();
    });
});
</script>
<button 
    class="btn-ayuda"
    style="top: 120px;"
    title="Visualizar Ayuda"
    onclick="window.location.href='?pagina=ayuda'">
    <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
</button>

<script src="assets/javascript/jwt_validator.js"></script>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>