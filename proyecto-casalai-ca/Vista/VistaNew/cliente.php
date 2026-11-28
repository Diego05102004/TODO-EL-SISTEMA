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
        error_log("Error al generar JWT en cliente: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'cliente';
$titulo_pagina = 'Gestionar Clientes';

// Iniciar el buffer de contenido
ob_start();

if (!function_exists('desencriptarCampoCliente')) {
    function desencriptarCampoCliente($valor) {
        if (!is_string($valor) || $valor === '') {
            return $valor;
        }

        if (strlen($valor) <= 24 || !preg_match('/^[A-Za-z0-9+\/=]+$/', $valor)) {
            return $valor;
        }

        try {
            $encryption = new Usuario\ProyectoCasalaiCa\Config\Encryption();
            $descifrado = $encryption->decrypt($valor);
            return is_string($descifrado) && $descifrado !== '' && $descifrado !== $valor ? $descifrado : $valor;
        } catch (Throwable $e) {
            error_log('[VISTA_CLIENTE] Error desencriptando campo: ' . $e->getMessage());
            return $valor;
        }
    }
}

if (is_array($clientes ?? null)) {
    foreach ($clientes as $indice => $cliente) {
        if (!is_array($cliente)) {
            continue;
        }

        foreach (['nombre', 'direccion', 'telefono', 'correo'] as $campo) {
            if (isset($cliente[$campo])) {
                $clientes[$indice][$campo] = desencriptarCampoCliente($cliente[$campo]);
            }
        }
    }
}
?>

<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/javascript/datatable.js"></script>
<script defer src="assets/javascript/cliente.js?v=<?php echo filemtime(__DIR__ . '/../../assets/javascript/cliente.js'); ?>"></script>

<!-- Clientes Table Section -->
<div class="table-section">
    <div id="mensaje-permiso" style="display:none; color:#dc2626; text-align:center; margin:20px 0; font-weight:600;"></div>
    <div class="section-header">
        <h2>Listar Clientes</h2>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button type="button" class="btn-table-superior btn-incluir" title="Incluir Clientes" onclick="openModal('registrar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <table id="tablaClientes" class="table-info" data-datatable data-page-length="10" data-datatable-order='[[0, "asc"]]' data-datatable-no-order="5" data-datatable-no-search="5">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $cliente) { ?>
                <tr data-id="<?php echo (int)($cliente['id_clientes'] ?? 0); ?>"
                    data-nombre="<?php echo htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-cedula="<?php echo htmlspecialchars($cliente['cedula'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars($cliente['cedula'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars($cliente['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-texto">
                            <?php echo htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button type="button" class="btn-action btn-modificar"
                            title="Modificar Cliente"
                            data-user-action="edit">
                            <img src="assets/img/pencil.svg">
                        </button>
                        <button type="button" class="btn-action btn-eliminar"
                            title="Eliminar Cliente"
                            data-user-action="delete">
                            <img src="assets/img/circle-x.svg">
                        </button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal para Registrar/Editar Cliente -->
<div id="clientModal" class="modal modal-container">
    <div class="modal-broad">
        <div class="background-blue">
            <div class="modal-header-content">
                <div class="modal-icon background-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="modal-title-content">
                    <h2 id="modalTitle">Incluir Clientes</h2>
                    <p>Complete los datos para incluir un nuevo cliente</p>
                </div>
                <button type="button" class="btn-help blue" title="Ayuda para Incluir Clientes" data-contexto="registrar">
                    <img src="assets/img/info-ayuda.svg">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form id="clientForm" enctype="multipart/form-data" novalidate>
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información</h3>
                    </div>
                    <input type="hidden" id="clientId" name="id_clientes">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clientName">Nombre Completo*</label>
                            <input type="text" id="clientName" name="nombre" placeholder="Nombres y Apellidos" class="input-textnum" maxlength="100">
                            <span class="span-value" id="snombre"></span>
                        </div>

                        <div class="form-group">
                            <label for="clientCedula">Cédula*</label>
                            <input type="text" id="clientCedula" name="cedula" placeholder="12.345.678" class="input-textnum" maxlength="10">
                            <span class="span-value" id="scedula"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="clientPhone">Teléfono*</label>
                            <input type="text" id="clientPhone" name="telefono" placeholder="0414-123-4567" class="input-textnum" maxlength="13">
                            <span class="span-value" id="stelefono"></span>
                        </div>

                        <div class="form-group">
                            <label for="clientEmail">Correo Electrónico*</label>
                            <input type="email" id="clientEmail" name="correo" placeholder="persona@gmail.com" class="input-textnum" maxlength="50">
                            <span class="span-value" id="scorreo"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="clientAddress">Dirección*</label>
                            <textarea id="clientAddress" name="direccion" placeholder="Estado/Ciudad/Calle o Avenida..." class="input-textnum" rows="4" maxlength="100"></textarea>
                            <span class="span-value" id="sdireccion"></span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn-confirmar btn-save">
                            <i class="fas fa-check"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteClientModal" class="modal modal-container" aria-hidden="true">
    <div class="modal-narrow">
        <div class="background-red">
            <div class="modal-header-content">
                <div class="modal-icon background-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Eliminar Cliente</h2>
                    <p>Confirmación de eliminación permanente</p>
                </div>
                <button type="button" class="btn-help red" title="Ayuda para Eliminar Cliente" data-contexto="eliminar">
                    <img src="assets/img/info-ayuda.svg" alt="Ayuda">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div class="content-eliminacion">
                <div class="quest-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de eliminar este cliente?</h3>
                <p>Esta acción es permanente y no se puede deshacer.</p>

                <div class="info-eliminacion">
                    <div class="info-row">
                        <label>Cédula:</label>
                        <span id="deleteClientCedula"></span>
                    </div>
                    <div class="info-row">
                        <label>Cliente:</label>
                        <span id="deleteClientName"></span>
                    </div>
                </div>

                <div class="modal-footer-content">
                    <button type="button" class="btn-confirmar btn-cancel" onclick="closeModal('deleteClientModal')">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-confirmar btn-danger" id="confirmDeleteClient">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>