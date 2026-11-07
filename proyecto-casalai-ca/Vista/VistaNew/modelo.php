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
        error_log("Error al generar JWT en modelo: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'modelo';
$titulo_pagina = 'Gestión de Modelos';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Modelos -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-cube"></i></div>
                    <div class="card-content">
                        <h3>Total Modelos</h3>
                        <p class="card-value"><?php echo count($modelos ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="78, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">78%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="card-content">
                        <h3>Marcas</h3>
                        <p class="card-value"><?php echo count($marcas ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="65, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">65%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Crecimiento</h3>
                        <p class="card-value">+8%</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="55, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">55%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modelos Section -->
            <div class="modelos-section">
                <div class="section-header">
                    <h2>Gestión de Modelos</h2>
                    <div class="section-actions">
                        <button class="btn-add-modelo" onclick="openModal('registrar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Nuevo Modelo
                        </button>
                        <button class="btn-filter" onclick="openFilterModal()">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Buscar
                        </button>
                    </div>
                </div>

                <!-- Tabla de Modelos -->
                <div class="table-container">
                    <table class="modelos-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($modelos)): ?>
                                <?php foreach ($modelos as $modelo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($modelo['id_modelo'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($modelo['nombre_marca'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($modelo['nombre_modelo'] ?? 'N/A'); ?></td>
                                        <td>
                                            <button class="btn-action btn-edit" onclick="editModelo(<?php echo $modelo['id_modelo']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteModelo(<?php echo $modelo['id_modelo']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-cube" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                                        <p style="color: #718096; margin: 0;">No hay modelos registrados</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Modelo -->
            <div id="modeloModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-cube"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Nuevo Modelo</h2>
                                <p>Complete los datos para registrar un nuevo modelo</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="modeloForm">
                            <input type="hidden" name="accion" value="registrar">
                            <input type="hidden" name="id_modelo" id="id_modelo">
                            
                            <div class="form-group">
                                <label for="id_marca">
                                    <i class="fas fa-trademark"></i>
                                    Marca*
                                </label>
                                <select id="id_marca" name="id_marca" required>
                                    <option value="">Seleccione una marca</option>
                                    <?php if (!empty($marcas)): ?>
                                        <?php foreach ($marcas as $marca): ?>
                                            <option value="<?php echo $marca['id_marca']; ?>">
                                                <?php echo htmlspecialchars($marca['nombre_marca']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre_modelo">
                                    <i class="fas fa-tag"></i>
                                    Nombre del Modelo*
                                </label>
                                <input type="text" id="nombre_modelo" name="nombre_modelo" required 
                                       placeholder="Ej: iPhone 15, Galaxy S24" maxlength="25" class="input-with-icon">
                                <i class="fas fa-cube input-icon"></i>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" onclick="saveModelo()">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para Buscar Modelo -->
            <div id="searchModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2>Buscar Modelo</h2>
                                <p>Filtre los modelos por nombre o marca</p>
                            </div>
                        </div>
                        <span class="close-modal" onclick="closeSearchModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="searchForm">
                            <div class="form-group">
                                <label for="searchNombre">
                                    <i class="fas fa-tag"></i>
                                    Nombre del Modelo
                                </label>
                                <input type="text" id="searchNombre" name="searchNombre" 
                                       placeholder="Ingrese el nombre del modelo">
                            </div>
                            
                            <div class="form-group">
                                <label for="searchMarca">
                                    <i class="fas fa-trademark"></i>
                                    Marca
                                </label>
                                <select id="searchMarca" name="searchMarca">
                                    <option value="">Todas</option>
                                    <?php if (!empty($marcas)): ?>
                                        <?php foreach ($marcas as $marca): ?>
                                            <option value="<?php echo $marca['id_marca']; ?>">
                                                <?php echo htmlspecialchars($marca['nombre_marca']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeSearchModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-save" onclick="searchModelo()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Modelos */
                .modelos-section {
                    margin-top: 30px;
                }

                .table-container {
                    background: white;
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .modelos-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .modelos-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .modelos-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }

                .modelos-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                }

                .modelos-table tbody tr:hover {
                    background: rgba(33, 150, 243, 0.05);
                }

                .btn-add-modelo, .btn-filter {
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
                }

                .btn-add-modelo {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-add-modelo:hover, .btn-filter:hover {
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

                /* Modal styles */
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    animation: fadeIn 0.3s;
                }

                .modal-content {
                    background-color: #fff;
                    margin: 5% auto;
                    padding: 0;
                    border-radius: 10px;
                    width: 90%;
                    max-width: 500px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                    animation: slideDown 0.3s;
                }

                .modal-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

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

                .close-modal {
                    font-size: 28px;
                    font-weight: bold;
                    color: white;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .close-modal:hover {
                    transform: scale(1.1);
                }

                .modal-body {
                    padding: 25px;
                }

                .form-group {
                    margin-bottom: 20px;
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

                .input-with-icon {
                    position: relative;
                }

                .input-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #2196F3;
                    pointer-events: none;
                }

                .modal-footer {
                    padding: 20px 25px;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
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

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .modelos-table {
                        font-size: 0.8rem;
                    }

                    .modelos-table th,
                    .modelos-table td {
                        padding: 10px;
                    }
                }
            </style>

            <script>
                function openModal(type) {
                    if (type === 'registrar') {
                        document.getElementById('modalTitle').textContent = 'Nuevo Modelo';
                        document.getElementById('modeloForm').reset();
                        document.getElementById('id_modelo').value = '';
                    }
                    document.getElementById('modeloModal').style.display = 'block';
                }

                function closeModal() {
                    document.getElementById('modeloModal').style.display = 'none';
                }

                function openFilterModal() {
                    document.getElementById('searchModal').style.display = 'block';
                }

                function closeSearchModal() {
                    document.getElementById('searchModal').style.display = 'none';
                }

                function saveModelo() {
                    const form = document.getElementById('modeloForm');
                    const formData = new FormData(form);
                    
                    if (!formData.get('id_marca')) {
                        alert('Por favor, seleccione una marca');
                        return;
                    }
                    
                    if (!formData.get('nombre_modelo').trim()) {
                        alert('Por favor, ingrese el nombre del modelo');
                        return;
                    }
                    
                    // Enviar al backend
                    fetch('?pagina=modelo', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            alert('Modelo guardado correctamente');
                            closeModal();
                            location.reload();
                        } else {
                            alert('Error al guardar modelo: ' + (data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al guardar modelo. Por favor, intente nuevamente.');
                    });
                }

                function editModelo(id) {
                    alert('Función para editar modelo ID: ' + id + '\n(conectar con backend)');
                }

                function deleteModelo(id) {
                    if (confirm('¿Está seguro de eliminar este modelo?')) {
                        alert('Función para eliminar modelo ID: ' + id + '\n(conectar con backend)');
                    }
                }

                function searchModelo() {
                    alert('Función para buscar modelos (conectar con backend)');
                    closeSearchModal();
                }

                // Event listeners
                document.querySelector('#modeloModal .close-modal').addEventListener('click', closeModal);
                document.querySelector('#searchModal .close-modal').addEventListener('click', closeSearchModal);

                window.addEventListener('click', function(event) {
                    if (event.target === document.getElementById('modeloModal')) {
                        closeModal();
                    }
                    if (event.target === document.getElementById('searchModal')) {
                        closeSearchModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>