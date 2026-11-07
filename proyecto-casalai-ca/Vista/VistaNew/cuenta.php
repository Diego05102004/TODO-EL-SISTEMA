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
        error_log("Error al generar JWT en cuenta: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'cuenta';
$titulo_pagina = 'Gestión de Cuentas Bancarias';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Cuentas Bancarias -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-university"></i></div>
                    <div class="card-content">
                        <h3>Total Cuentas</h3>
                        <p class="card-value"><?php echo count($cuentabancos ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="100, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">100%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="card-content">
                        <h3>Bancos Activos</h3>
                        <p class="card-value"><?php echo count(array_unique(array_column($cuentabancos ?? [], 'nombre_banco'))); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="85, 100" stroke="#28a745" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">85%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-content">
                        <h3>Cuentas en USD</h3>
                        <p class="card-value"><?php echo count(array_filter($cuentabancos ?? [], function($c) { return ($c['tipo_moneda'] ?? '') === 'USD'; })); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="65, 100" stroke="#ffc107" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">65%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cuentas Bancarias Section -->
            <div class="cuentas-section">
                <div class="section-header">
                    <h2>Cuentas Bancarias</h2>
                    <div class="section-actions">
                        <button class="btn-add-cuenta" onclick="openModal('registrar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Cuenta
                        </button>
                    </div>
                </div>

                <!-- Tabla de Cuentas -->
                <div class="table-container">
                    <table class="cuentas-table">
                        <thead>
                            <tr>
                                <th>Banco</th>
                                <th>Número de Cuenta</th>
                                <th>RIF</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Moneda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cuentabancos)): ?>
                                <?php foreach ($cuentabancos as $cuenta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cuenta['nombre_banco'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cuenta['numero_cuenta'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cuenta['rif'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cuenta['telefono'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cuenta['correo'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="currency-badge <?php echo ($cuenta['tipo_moneda'] ?? '') === 'USD' ? 'usd' : 'ves'; ?>">
                                                <?php echo htmlspecialchars($cuenta['tipo_moneda'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-view" onclick="viewCuenta(<?php echo $cuenta['id_cuenta']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-edit" onclick="editCuenta(<?php echo $cuenta['id_cuenta']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteCuenta(<?php echo $cuenta['id_cuenta']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-university" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay cuentas bancarias registradas</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Cuenta -->
            <div id="cuentaModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Agregar Cuenta Bancaria</h2>
                                <p>Complete los datos para registrar una nueva cuenta</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="cuentaForm">
                            <input type="hidden" name="accion" value="registrar">
                            <input type="hidden" id="cuentaId" name="id_cuenta">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre_banco">
                                        <i class="fas fa-building"></i>
                                        Nombre del Banco*
                                    </label>
                                    <input type="text" id="nombre_banco" name="nombre_banco" required 
                                           placeholder="Ej: Banco de Venezuela" maxlength="20">
                                </div>
                                
                                <div class="form-group">
                                    <label for="numero_cuenta">
                                        <i class="fas fa-hashtag"></i>
                                        Número de Cuenta*
                                    </label>
                                    <input type="text" id="numero_cuenta" name="numero_cuenta" required 
                                           placeholder="Ej: 0134-0000-00-0000000000" maxlength="23">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="rif_cuenta">
                                        <i class="fas fa-id-card"></i>
                                        RIF*
                                    </label>
                                    <input type="text" id="rif_cuenta" name="rif_cuenta" required 
                                           placeholder="Ej: V-12345678-9" maxlength="12">
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono_cuenta">
                                        <i class="fas fa-phone"></i>
                                        Teléfono*
                                    </label>
                                    <input type="text" id="telefono_cuenta" name="telefono_cuenta" required 
                                           placeholder="Ej: 0400-000-0000" maxlength="13">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="correo_cuenta">
                                    <i class="fas fa-envelope"></i>
                                    Correo*
                                </label>
                                <input type="email" id="correo_cuenta" name="correo_cuenta" required 
                                       placeholder="Ej: contacto@banco.com" maxlength="50">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipo_moneda">
                                        <i class="fas fa-dollar-sign"></i>
                                        Tipo de Moneda*
                                    </label>
                                    <select id="tipo_moneda" name="tipo_moneda" required>
                                        <option value="">Seleccione moneda</option>
                                        <option value="VES">Bolívares (VES)</option>
                                        <option value="USD">Dólares (USD)</option>
                                        <option value="EUR">Euros (EUR)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tipo_cuenta">
                                        <i class="fas fa-credit-card"></i>
                                        Tipo de Cuenta*
                                    </label>
                                    <select id="tipo_cuenta" name="tipo_cuenta" required>
                                        <option value="">Seleccione tipo</option>
                                        <option value="ahorros">Ahorros</option>
                                        <option value="corriente">Corriente</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" onclick="saveCuenta()">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Cuentas Bancarias */
                .cuentas-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .cuentas-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .cuentas-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .cuentas-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .cuentas-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .cuentas-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .currency-badge {
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .currency-badge.usd {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                    color: white;
                }

                .currency-badge.ves {
                    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                    color: white;
                }

                .btn-add-cuenta {
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
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-add-cuenta:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-action {
                    width: 35px;
                    height: 35px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 8px;
                }

                .btn-view {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-edit {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-delete {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                .btn-action:hover {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }

                /* Modal styles - reusando estilos de otros módulos */
                .modal-header-content {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .modal-icon {
                    width: 50px;
                    height: 50px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .modal-icon i {
                    font-size: 1.5rem;
                    color: white;
                }

                .modal-title-content h2 {
                    font-size: 1.4rem;
                    font-weight: 700;
                    margin: 0 0 5px 0;
                }

                .modal-title-content p {
                    font-size: 0.85rem;
                    opacity: 0.9;
                    margin: 0;
                }

                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin-bottom: 20px;
                }

                @media (max-width: 768px) {
                    .form-row {
                        grid-template-columns: 1fr;
                    }
                }

                .form-group {
                    margin-bottom: 15px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #2d3748;
                }

                .form-group label i {
                    color: #2196F3;
                    margin-right: 8px;
                }

                .form-group input,
                .form-group select {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .form-group input:focus,
                .form-group select:focus {
                    border-color: #2196F3;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
                }

                .btn-cancel, .btn-save {
                    padding: 12px 25px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-cancel {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                }

                .btn-cancel:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
                }

                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-save:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
                }
            </style>

            <script>
                function openModal(type) {
                    if (type === 'registrar') {
                        document.getElementById('modalTitle').textContent = 'Agregar Cuenta Bancaria';
                        document.getElementById('cuentaForm').reset();
                        document.getElementById('cuentaId').value = '';
                    }
                    document.getElementById('cuentaModal').style.display = 'block';
                }

                function closeModal() {
                    document.getElementById('cuentaModal').style.display = 'none';
                }

                function saveCuenta() {
                    const form = document.getElementById('cuentaForm');
                    const formData = new FormData(form);
                    
                    if (!formData.get('nombre_banco')) {
                        alert('Por favor, ingrese el nombre del banco');
                        return;
                    }
                    
                    if (!formData.get('numero_cuenta')) {
                        alert('Por favor, ingrese el número de cuenta');
                        return;
                    }
                    
                    if (!formData.get('rif_cuenta')) {
                        alert('Por favor, ingrese el RIF');
                        return;
                    }
                    
                    if (!formData.get('telefono_cuenta')) {
                        alert('Por favor, ingrese el teléfono');
                        return;
                    }
                    
                    if (!formData.get('correo_cuenta')) {
                        alert('Por favor, ingrese el correo');
                        return;
                    }
                    
                    if (!formData.get('tipo_moneda')) {
                        alert('Por favor, seleccione el tipo de moneda');
                        return;
                    }
                    
                    if (!formData.get('tipo_cuenta')) {
                        alert('Por favor, seleccione el tipo de cuenta');
                        return;
                    }
                    
                    // Enviar al backend
                    fetch('?pagina=cuenta', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            alert('Cuenta registrada correctamente');
                            closeModal();
                            location.reload();
                        } else {
                            alert('Error al registrar cuenta: ' + (data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al registrar cuenta. Por favor, intente nuevamente.');
                    });
                }

                function viewCuenta(id) {
                    alert('Función para ver cuenta ID: ' + id + '\n(conectar con backend)');
                }

                function editCuenta(id) {
                    alert('Función para editar cuenta ID: ' + id + '\n(conectar con backend)');
                }

                function deleteCuenta(id) {
                    if (confirm('¿Está seguro de eliminar esta cuenta?')) {
                        alert('Función para eliminar cuenta ID: ' + id + '\n(conectar con backend)');
                    }
                }

                // Event listeners
                document.querySelector('#cuentaModal .close-modal').addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === document.getElementById('cuentaModal')) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>