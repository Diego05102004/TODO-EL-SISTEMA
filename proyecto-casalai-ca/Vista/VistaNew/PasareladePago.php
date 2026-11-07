<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión (sistema tradicional)
if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

// Verificar y/o generar token JWT (sistema JWT)
require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en PasareladePago: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'PasareladePago';
$titulo_pagina = 'Pasarela de Pagos';
$cuentasActivas = array_values(array_filter(
    is_array($listadocuentas ?? null) ? $listadocuentas : [],
    static fn($cuenta) => strtolower(trim((string)($cuenta['estado'] ?? ''))) === 'habilitado'
));
$cuentasJson = json_encode(
    $cuentasActivas,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Pasarela de Pagos -->
            <div class="pasarela-section">
                <div class="section-header">
                    <h2><i class="fas fa-credit-card"></i> Registrar Pagos Online</h2>
                    <div class="section-actions">
                        <button class="btn-back" onclick="window.location.href='?pagina=gestionarfactura'">
                            <span class="btn-icon"><i class="fas fa-arrow-left"></i></span>
                            Volver a Pedidos
                        </button>
                    </div>
                </div>

                <!-- Alerta de Borrador -->
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Borrador:</strong> El pago aún no ha sido enviado para validación.
                </div>

                <!-- Formulario de Pagos -->
                <div class="pagos-container">
                    <div class="pagos-layout">
                        <!-- Datos Bancarios -->
                        <div class="datos-bancarios-section">
                            <h3 class="section-title"><i class="fas fa-university"></i> Datos Bancarios</h3>
                            <div class="cuentas-list" id="cuentas-list" aria-live="polite">
                                <p class="no-cuentas">Seleccione un método y una cuenta para consultar sus datos de pago.</p>
                            </div>
                        </div>

                        <!-- Formulario de Pagos -->
                        <div class="form-pagos-section">
                            <form id="formularioPago" method="POST" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="accion" value="ingresar">
                                <input type="hidden" name="id_factura" value="<?php echo $idFactura ?? ''; ?>">
                                
                                <h3 class="section-title"><i class="fas fa-file-invoice-dollar"></i> Información del Pago</h3>
                                
                                <!-- Monto Total -->
                                <div class="monto-section">
                                    <label for="monto_total">Monto Total a Pagar</label>
                                    <input type="text" class="monto-input" id="monto_total" name="monto_total" 
                                           value="<?php echo number_format($monto ?? 0, 2); ?> Bs" readonly>
                                </div>

                                <!-- Contenedor de Pagos -->
                                <div class="pagos-horizontal" id="pagos-container">
                                    <!-- Los bloques de pago se agregarán aquí dinámicamente -->
                                </div>

                                <!-- Resumen de Pagos -->
                                <div id="resumen-pagos" style="display: none;">
                                    <h4 class="resumen-title">Resumen de Pagos</h4>
                                    <div class="resumen-grid">
                                        <div class="resumen-card">
                                            <div class="resumen-label">Total Registrado</div>
                                            <div id="total-registrado" class="resumen-value success">0.00 Bs</div>
                                        </div>
                                        <div class="resumen-card">
                                            <div class="resumen-label">Restante por Pagar</div>
                                            <div id="restante-pagar" class="resumen-value danger">
                                                <?php echo number_format($monto ?? 0, 2); ?> Bs
                                            </div>
                                        </div>
                                    </div>
                                    <p id="mensaje-validacion" class="error-message"></p>
                                </div>

                                <!-- Botones -->
                                <div class="buttons-section">
                                    <button type="button" class="btn-pago btn-secondary" id="agregar-pago">
                                        <i class="fas fa-plus"></i> Agregar Método de Pago
                                    </button>
                                    <button type="submit" class="btn-pago btn-primary" id="btn-registrar" disabled>
                                        <i class="fas fa-check"></i> Registrar Pagos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Pasarela de Pagos */
                .pasarela-section {
                    margin-top: 30px;
                }

                .pagos-container {
                    background: white;
                    border-radius: 16px;
                    padding: 30px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .pagos-layout {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 30px;
                }

                @media (max-width: 992px) {
                    .pagos-layout {
                        grid-template-columns: 1fr;
                    }
                }

                .section-title {
                    font-size: 1.2rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .section-title i {
                    color: #2196F3;
                }

                .alert {
                    padding: 15px 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .alert-warning {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
                    border: 1px solid #ffc107;
                    color: #856404;
                }

                .alert i {
                    font-size: 1.2rem;
                }

                /* Cuentas Bancarias */
                .cuentas-list {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 15px;
                }

                .cuenta-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border: 1px solid #dee2e6;
                    border-radius: 12px;
                    padding: 15px;
                    transition: all 0.3s ease;
                }

                .cuenta-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .cuenta-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .cuenta-banco {
                    font-weight: 700;
                    color: #2d3748;
                }

                .cuenta-tipo {
                    font-size: 0.8rem;
                    background: #2196F3;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 20px;
                }

                .cuenta-info {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .cuenta-numero,
                .cuenta-rif {
                    font-size: 0.9rem;
                    color: #495057;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .no-cuentas {
                    text-align: center;
                    color: #718096;
                    padding: 20px;
                    font-style: italic;
                }

                /* Monto */
                .monto-section {
                    margin-bottom: 20px;
                }

                .monto-section label {
                    display: block;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 8px;
                }

                .monto-input {
                    width: 100%;
                    padding: 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 10px;
                    font-size: 1.2rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
                    color: #155724;
                    text-align: center;
                }

                /* Contenedor de Pagos */
                .pagos-horizontal {
                    display: flex;
                    gap: 15px;
                    overflow-x: auto;
                    padding: 10px 0;
                    min-height: 150px;
                }

                .bloque-pago {
                    flex: 1;
                    min-width: 300px;
                    background: white;
                    border: 2px solid #2196F3;
                    border-radius: 12px;
                    padding: 20px;
                    position: relative;
                }

                .btn-quitar-pago {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: #dc3545;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 28px;
                    height: 28px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    line-height: 1;
                }

                .btn-quitar-pago:hover {
                    background: #c82333;
                }

                .campos-pago {
                    display: none;
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 2px dashed #dee2e6;
                }

                .campos-pago.show {
                    display: block;
                }

                .envolver-form {
                    margin-bottom: 15px;
                }

                .envolver-form label {
                    display: block;
                    font-weight: 600;
                    color: #495057;
                    margin-bottom: 5px;
                    font-size: 0.9rem;
                }

                .control-form {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ced4da;
                    border-radius: 6px;
                    font-size: 0.9rem;
                }

                /* Resumen */
                .resumen-title {
                    font-size: 1rem;
                    color: #495057;
                    margin-bottom: 15px;
                }

                .resumen-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }

                .resumen-card {
                    background: white;
                    padding: 15px;
                    border-radius: 10px;
                    border: 1px solid #dee2e6;
                }

                .resumen-label {
                    font-size: 0.85rem;
                    color: #6c757d;
                }

                .resumen-value {
                    font-size: 1.2rem;
                    font-weight: 700;
                }

                .resumen-value.success {
                    color: #28a745;
                }

                .resumen-value.danger {
                    color: #dc3545;
                }

                .error-message {
                    text-align: center;
                    color: #dc3545;
                    font-size: 0.9rem;
                    margin: 10px 0 0 0;
                }

                /* Botones */
                .buttons-section {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    margin-top: 20px;
                }

                .btn-pago {
                    padding: 12px 25px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-pago:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .btn-primary {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-primary:hover:not(:disabled) {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-secondary {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-secondary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
                }

                .btn-back {
                    padding: 12px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                    color: white;
                }

                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .pagos-container {
                        padding: 20px;
                    }

                    .pagos-horizontal {
                        flex-direction: column;
                    }

                    .bloque-pago {
                        min-width: 100%;
                    }

                    .buttons-section {
                        flex-direction: column;
                    }

                    .btn-pago {
                        width: 100%;
                        justify-content: center;
                    }
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = document.getElementById('formularioPago');
                    const pagosContainer = document.getElementById('pagos-container');
                    const cuentasContainer = document.getElementById('cuentas-list');
                    const resumen = document.getElementById('resumen-pagos');
                    const mensajeValidacion = document.getElementById('mensaje-validacion');
                    const botonRegistrar = document.getElementById('btn-registrar');
                    const montoTotal = Number(<?php echo json_encode((float)($monto ?? 0)); ?>);
                    const tasaDolar = Number(<?php echo json_encode((float)($data['monitors']['bcv']['price'] ?? 0)); ?>);
                    const cuentas = <?php echo $cuentasJson; ?>;
                    let siguientePago = 0;

                    const formatearMonto = (monto) => monto.toLocaleString('es-VE', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    const crearCampo = (labelText, input) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'envolver-form';
                        const label = document.createElement('label');
                        label.htmlFor = input.id;
                        label.textContent = labelText;
                        wrapper.append(label, input);
                        return wrapper;
                    };

                    const crearInput = (id, name, type, attributes = {}) => {
                        const input = document.createElement('input');
                        input.id = id;
                        input.name = name;
                        input.type = type;
                        input.className = 'control-form';
                        Object.entries(attributes).forEach(([key, value]) => input.setAttribute(key, value));
                        return input;
                    };

                    const metodosDeCuenta = (cuenta) => String(cuenta.metodos || '')
                        .split(',')
                        .map((metodo) => metodo.trim().toLowerCase());

                    const cuentasPorMetodo = (metodo) => cuentas.filter((cuenta) =>
                        metodosDeCuenta(cuenta).includes(metodo.toLowerCase())
                    );

                    const mostrarInformacionCuenta = (panel, metodo, cuenta) => {
                        const tarjeta = document.createElement('div');
                        tarjeta.className = 'cuenta-card';
                        tarjeta.dataset.cuenta = cuenta.id_cuenta;
                        const encabezado = document.createElement('div');
                        encabezado.className = 'cuenta-header';
                        const banco = document.createElement('span');
                        banco.className = 'cuenta-banco';
                        banco.textContent = metodo === 'Zelle' ? 'Datos para Zelle' : (cuenta.nombre_banco || 'Banco');
                        encabezado.appendChild(banco);
                        tarjeta.appendChild(encabezado);

                        const info = document.createElement('div');
                        info.className = 'cuenta-info';
                        const datos = metodo === 'Pago Movil'
                            ? [['Banco', cuenta.nombre_banco], ['Teléfono', cuenta.telefono_cuenta], ['RIF', cuenta.rif_cuenta]]
                            : metodo === 'Transferencia'
                                ? [['Banco', cuenta.nombre_banco], ['Número de cuenta', cuenta.numero_cuenta], ['RIF', cuenta.rif_cuenta]]
                                : [['Correo electrónico', cuenta.correo_cuenta]];

                        datos.forEach(([etiqueta, valor]) => {
                            const linea = document.createElement('div');
                            linea.className = 'cuenta-numero';
                            const etiquetaElemento = document.createElement('strong');
                            etiquetaElemento.textContent = `${etiqueta}: `;
                            linea.append(etiquetaElemento, document.createTextNode(valor || 'No disponible'));
                            info.appendChild(linea);
                        });
                        tarjeta.appendChild(info);
                        panel.replaceChildren(tarjeta);
                    };

                    const actualizarResumen = () => {
                        let total = 0;
                        pagosContainer.querySelectorAll('.bloque-pago').forEach((bloque) => {
                            total += Number(bloque.querySelector('.monto-bs')?.value || 0);
                        });
                        const restante = montoTotal - total;
                        document.getElementById('total-registrado').textContent = `${formatearMonto(total)} Bs`;
                        const restanteElemento = document.getElementById('restante-pagar');
                        restanteElemento.textContent = `${formatearMonto(restante)} Bs`;
                        restanteElemento.classList.toggle('success', Math.abs(restante) <= 0.01);
                        restanteElemento.classList.toggle('danger', Math.abs(restante) > 0.01);
                        resumen.style.display = pagosContainer.children.length ? 'block' : 'none';
                        botonRegistrar.disabled = Math.abs(restante) > 0.01 || total <= 0;
                        mensajeValidacion.textContent = Math.abs(restante) <= 0.01
                            ? 'Los montos coinciden con el total a pagar.'
                            : restante > 0
                                ? `Faltan ${formatearMonto(restante)} Bs por registrar.`
                                : `Hay ${formatearMonto(-restante)} Bs de más.`;
                    };

                    const actualizarMontoZelle = (bloque) => {
                        const montoUsd = Number(bloque.querySelector('.monto-usd').value || 0);
                        const montoBs = montoUsd * tasaDolar;
                        bloque.querySelector('.monto-bs').value = Number.isFinite(montoBs) ? montoBs.toFixed(2) : '';
                        bloque.querySelector('.conversion-zelle').textContent = montoUsd > 0 && tasaDolar > 0
                            ? `Equivalente: ${formatearMonto(montoBs)} Bs (tasa: ${formatearMonto(tasaDolar)} Bs/USD)`
                            : '';
                        actualizarResumen();
                    };

                    const mostrarCamposPago = (bloque, id, metodo) => {
                        const campos = bloque.querySelector('.campos-pago');
                        campos.replaceChildren();

                        const referencia = crearInput(`referencia-${id}`, `pagos[${id}][referencia]`, 'text', {
                            maxlength: metodo === 'Zelle' ? '30' : '12',
                            placeholder: metodo === 'Zelle' ? 'ID o referencia de la transacción' : 'Número de referencia',
                            required: '',
                            autocomplete: 'off'
                        });
                        campos.appendChild(crearCampo(metodo === 'Zelle' ? 'ID/referencia de transacción *' : 'Número de referencia *', referencia));

                        if (metodo === 'Zelle') {
                            const titular = crearInput(`titular-${id}`, `pagos[${id}][observaciones]`, 'text', {
                                maxlength: '200',
                                placeholder: 'Nombre del titular de la cuenta Zelle',
                                required: '',
                                autocomplete: 'name'
                            });
                            campos.appendChild(crearCampo('Nombre del titular de la cuenta Zelle *', titular));
                        }

                        const comprobante = crearInput(`comprobante-${id}`, `pagos[${id}][comprobante]`, 'file', {
                            accept: 'image/*,.pdf',
                            required: ''
                        });
                        campos.appendChild(crearCampo('Comprobante del pago *', comprobante));

                        const montoBs = crearInput(`monto-bs-${id}`, `pagos[${id}][monto]`, metodo === 'Zelle' ? 'hidden' : 'number', {
                            step: '0.01',
                            min: '0.01',
                            required: ''
                        });
                        montoBs.classList.add('monto-bs');
                        if (metodo === 'Zelle') {
                            const montoUsd = crearInput(`monto-usd-${id}`, `pagos[${id}][monto_dolar]`, 'number', {
                                step: '0.01',
                                min: '0.01',
                                required: '',
                                placeholder: '0.00'
                            });
                            montoUsd.classList.add('monto-usd');
                            const wrapperUsd = crearCampo('Monto (USD) *', montoUsd);
                            const conversion = document.createElement('small');
                            conversion.className = 'conversion-zelle';
                            conversion.setAttribute('aria-live', 'polite');
                            wrapperUsd.appendChild(conversion);
                            campos.append(wrapperUsd, montoBs);
                            montoUsd.addEventListener('input', () => actualizarMontoZelle(bloque));
                        } else {
                            campos.appendChild(crearCampo('Monto (Bs) *', montoBs));
                            montoBs.addEventListener('input', actualizarResumen);
                        }
                        campos.classList.add('show');
                        actualizarResumen();
                    };

                    const agregarPago = () => {
                        if (pagosContainer.children.length >= 5) {
                            window.alert('Solo puedes agregar hasta 5 métodos de pago.');
                            return;
                        }

                        const id = siguientePago++;
                        const bloque = document.createElement('div');
                        bloque.className = 'bloque-pago';
                        bloque.id = `pago-${id}`;
                        bloque.innerHTML = `
                            <button type="button" class="btn-quitar-pago" aria-label="Quitar método de pago">&times;</button>
                            <h4 style="margin: 0 0 15px 0; color: #2196F3;">Método ${pagosContainer.children.length + 1}</h4>
                            <div class="envolver-form">
                                <label for="tipo-${id}">Método de pago *</label>
                                <select class="control-form metodo-pago" id="tipo-${id}" name="pagos[${id}][tipo]" required>
                                    <option value="">Seleccione método</option>
                                    <option value="Pago Movil">Pago Móvil</option>
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Zelle">Zelle</option>
                                </select>
                            </div>
                            <div class="envolver-form campo-cuenta" hidden>
                                <label for="cuenta-${id}">Cuenta de destino *</label>
                                <select class="control-form cuenta-pago" id="cuenta-${id}" name="pagos[${id}][cuenta]" required>
                                    <option value="">Seleccione una cuenta</option>
                                </select>
                            </div>
                            <div class="campos-pago"></div>`;
                        pagosContainer.appendChild(bloque);
                        const panel = document.createElement('div');
                        panel.id = `datos-cuenta-${id}`;
                        cuentasContainer.querySelector('.no-cuentas')?.remove();
                        cuentasContainer.appendChild(panel);

                        const tipo = bloque.querySelector('.metodo-pago');
                        const cuentaSelect = bloque.querySelector('.cuenta-pago');
                        tipo.addEventListener('change', () => {
                            const metodo = tipo.value;
                            cuentaSelect.replaceChildren(new Option('Seleccione una cuenta', ''));
                            panel.replaceChildren();
                            const campos = bloque.querySelector('.campos-pago');
                            campos.replaceChildren();
                            campos.classList.remove('show');
                            cuentasPorMetodo(metodo).forEach((cuenta) => {
                                cuentaSelect.add(new Option(
                                    `${cuenta.nombre_banco || 'Cuenta'}${metodo === 'Transferencia' && cuenta.numero_cuenta ? ` - ${cuenta.numero_cuenta}` : ''}`,
                                    cuenta.id_cuenta
                                ));
                            });
                            bloque.querySelector('.campo-cuenta').hidden = false;
                            if (cuentaSelect.options.length === 1) {
                                cuentaSelect.options[0].textContent = 'No hay cuentas activas para este método';
                            }
                            actualizarResumen();
                        });

                        cuentaSelect.addEventListener('change', () => {
                            const cuenta = cuentas.find((item) => String(item.id_cuenta) === cuentaSelect.value);
                            if (!cuenta) {
                                panel.replaceChildren();
                                return;
                            }
                            mostrarInformacionCuenta(panel, tipo.value, cuenta);
                            mostrarCamposPago(bloque, id, tipo.value);
                        });

                        bloque.querySelector('.btn-quitar-pago').addEventListener('click', () => {
                            bloque.remove();
                            panel.remove();
                            pagosContainer.querySelectorAll('.bloque-pago h4').forEach((titulo, index) => {
                                titulo.textContent = `Método ${index + 1}`;
                            });
                            actualizarResumen();
                            if (!pagosContainer.children.length) {
                                const aviso = document.createElement('p');
                                aviso.className = 'no-cuentas';
                                aviso.textContent = 'Seleccione un método y una cuenta para consultar sus datos de pago.';
                                cuentasContainer.appendChild(aviso);
                            }
                        });
                    };

                    document.getElementById('agregar-pago').addEventListener('click', agregarPago);
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        const bloques = [...pagosContainer.querySelectorAll('.bloque-pago')];
                        const errores = [];

                        if (!bloques.length) errores.push('Agregue al menos un método de pago.');
                        bloques.forEach((bloque, index) => {
                            const metodo = bloque.querySelector('.metodo-pago').value;
                            if (!metodo) errores.push(`Seleccione el método del pago ${index + 1}.`);
                            if (!bloque.querySelector('.cuenta-pago').value) errores.push(`Seleccione una cuenta activa para el pago ${index + 1}.`);
                            if (!bloque.querySelector('[name$="[referencia]"]')?.value.trim()) errores.push(`Ingrese la referencia del pago ${index + 1}.`);
                            const montoBs = bloque.querySelector('.monto-bs');
                            if (!montoBs || !montoBs.value || Number(montoBs.value) <= 0) errores.push(`Ingrese un monto válido para el pago ${index + 1}.`);
                            const comprobante = bloque.querySelector('[name$="[comprobante]"]');
                            if (!comprobante?.files.length) errores.push(`Adjunte el comprobante del pago ${index + 1}.`);
                            if (metodo === 'Zelle' && !bloque.querySelector('[name$="[observaciones]"]')?.value.trim()) {
                                errores.push(`Ingrese el nombre del titular de Zelle para el pago ${index + 1}.`);
                            }
                        });

                        const total = bloques.reduce((suma, bloque) => suma + Number(bloque.querySelector('.monto-bs')?.value || 0), 0);
                        if (Math.abs(total - montoTotal) > 0.01) errores.push('La suma de los pagos debe coincidir con el total a pagar.');
                        if (bloques.some((bloque) => bloque.querySelector('.metodo-pago').value === 'Zelle') && tasaDolar <= 0) {
                            errores.push('No se pudo obtener una tasa BCV válida para calcular el pago Zelle.');
                        }
                        if (errores.length) {
                            mensajeValidacion.textContent = errores.join(' ');
                            return;
                        }

                        botonRegistrar.disabled = true;
                        try {
                            const respuesta = await fetch(window.location.href, { method: 'POST', body: new FormData(form) });
                            const resultado = await respuesta.json();
                            if (!respuesta.ok || resultado.status !== 'success') {
                                const detalles = Array.isArray(resultado.errors) ? ` ${resultado.errors.join(' ')}` : '';
                                throw new Error((resultado.message || 'No se pudieron registrar los pagos.') + detalles);
                            }
                            if (window.Swal) {
                                await Swal.fire({ icon: 'success', title: 'Pago registrado', text: resultado.message });
                            } else {
                                window.alert(resultado.message);
                            }
                            window.location.href = '?pagina=gestionarfactura';
                        } catch (error) {
                            mensajeValidacion.textContent = error.message || 'Error al registrar los pagos.';
                            botonRegistrar.disabled = false;
                        }
                    });

                    agregarPago();
                    actualizarResumen();
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>