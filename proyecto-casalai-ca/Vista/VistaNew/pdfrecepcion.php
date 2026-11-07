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
        error_log("Error al generar JWT en pdfrecepcion: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'pdfrecepcion';
$titulo_pagina = 'Generar PDF de Recepción';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- PDF Generation Section -->
            <div class="pdf-section">
                <div class="section-header">
                    <h2>Generar PDF de Recepción</h2>
                    <button class="btn-back" onclick="window.location.href='?pagina=recepcion'">
                        <span class="btn-icon"><i class="fas fa-arrow-left"></i></span>
                        Volver a Recepciones
                    </button>
                </div>

                <div class="pdf-container">
                    <div class="pdf-card">
                        <div class="pdf-header">
                            <div class="pdf-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h3>Generar Documento PDF</h3>
                            <p>Complete los datos para generar el reporte de recepción</p>
                        </div>

                        <form id="pdfForm" method="post" action="" target="_blank">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fecha_recepcion">Fecha de Recepción*</label>
                                    <input type="date" id="fecha_recepcion" name="fecha_recepcion" required>
                                </div>

                                <div class="form-group">
                                    <label for="correlativo">Correlativo*</label>
                                    <input type="text" id="correlativo" name="correlativo" required 
                                           placeholder="Ej: REC-001">
                                </div>

                                <div class="form-group">
                                    <label for="id_proveedor">ID del Proveedor*</label>
                                    <input type="text" id="id_proveedor" name="id_proveedor" required 
                                           placeholder="Ej: 1">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="tipo_reporte">Tipo de Reporte</label>
                                <select id="tipo_reporte" name="tipo_reporte">
                                    <option value="recepcion">Recepción Completa</option>
                                    <option value="resumen">Resumen de Recepción</option>
                                    <option value="productos">Solo Productos</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="incluir_firma" name="incluir_firma">
                                    <span>Incluir firma del receptor</span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="incluir_observaciones" name="incluir_observaciones" checked>
                                    <span>Incluir observaciones</span>
                                </label>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-generate" name="generar">
                                    <span class="btn-icon"><i class="fas fa-file-pdf"></i></span>
                                    Generar PDF
                                </button>
                                <button type="button" class="btn-clear" onclick="limpiarFormulario()">
                                    <span class="btn-icon"><i class="fas fa-eraser"></i></span>
                                    Limpiar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="pdf-info">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h4>Información</h4>
                            <p>El PDF se generará en una nueva pestaña del navegador. Asegúrese de tener habilitados los pop-ups.</p>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h4>Consejo</h4>
                            <p>Verifique que el correlativo y el ID del proveedor sean correctos antes de generar el documento.</p>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <h4>Historial</h4>
                            <p>Los PDFs generados se guardan automáticamente en el historial de descargas del navegador.</p>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de PDF */
                .pdf-section {
                    margin-top: 30px;
                }

                .pdf-container {
                    display: grid;
                    grid-template-columns: 2fr 1fr;
                    gap: 30px;
                    margin-top: 20px;
                }

                .pdf-card {
                    background: white;
                    border-radius: 16px;
                    padding: 30px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .pdf-header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid rgba(33, 150, 243, 0.1);
                }

                .pdf-icon {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 20px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .pdf-icon i {
                    font-size: 2.5rem;
                    color: white;
                }

                .pdf-header h3 {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0 0 10px 0;
                }

                .pdf-header p {
                    font-size: 0.95rem;
                    color: #718096;
                    margin: 0;
                }

                .checkbox-label {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    color: #2d3748;
                }

                .checkbox-label input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    cursor: pointer;
                }

                .form-actions {
                    display: flex;
                    gap: 15px;
                    margin-top: 25px;
                }

                .btn-generate, .btn-clear {
                    flex: 1;
                    padding: 14px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                }

                .btn-generate {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-generate:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-clear {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                }

                .btn-clear:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(240, 147, 251, 0.3);
                }

                .pdf-info {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }

                .info-card {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border-left: 4px solid #2196F3;
                }

                .info-icon {
                    width: 40px;
                    height: 40px;
                    margin-bottom: 15px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .info-icon i {
                    font-size: 1.2rem;
                    color: white;
                }

                .info-card h4 {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0 0 10px 0;
                }

                .info-card p {
                    font-size: 0.85rem;
                    color: #718096;
                    margin: 0;
                    line-height: 1.5;
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
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .pdf-container {
                        grid-template-columns: 1fr;
                    }

                    .form-actions {
                        flex-direction: column;
                    }
                }
            </style>

            <script>
                function limpiarFormulario() {
                    document.getElementById('pdfForm').reset();
                }

                // Set today's date as default
                document.addEventListener('DOMContentLoaded', function() {
                    const fechaInput = document.getElementById('fecha_recepcion');
                    if (fechaInput) {
                        const today = new Date().toISOString().split('T')[0];
                        fechaInput.value = today;
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>