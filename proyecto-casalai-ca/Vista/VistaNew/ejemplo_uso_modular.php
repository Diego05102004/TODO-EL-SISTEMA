<?php
// EJEMPLO DE USO DEL SISTEMA MODULAR DEL DASHBOARD
// Este archivo muestra cómo crear nuevas vistas usando los componentes reutilizables

// Paso 1: Verificar autenticación (copia esto en todas las vistas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT: " . $e->getMessage());
    }
}

// Paso 2: Definir variables para la vista
$pagina_actual = 'cliente'; // Identificador de la página actual para el menú activo
$titulo_pagina = 'Gestión de Clientes'; // Título que aparecerá en el header

// Paso 3: Iniciar el buffer de contenido
ob_start();
?>

<!-- Aquí va el contenido específico de tu vista -->
<div class="custom-content">
    <h2>Gestión de Clientes</h2>
    <p>Aquí puedes agregar, editar y eliminar clientes.</p>
    
    <!-- Ejemplo de tabla de clientes -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Juan Pérez</td>
                    <td>12345678</td>
                    <td>0414-1234567</td>
                    <td>juan@email.com</td>
                    <td>
                        <button class="btn-edit">Editar</button>
                        <button class="btn-delete">Eliminar</button>
                    </td>
                </tr>
                <!-- Más filas aquí -->
            </tbody>
        </table>
    </div>
</div>

<!-- Estilos específicos de esta vista -->
<style>
    .custom-content {
        padding: 20px;
    }
    
    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .custom-table th, .custom-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .btn-edit, .btn-delete {
        padding: 5px 10px;
        margin-right: 5px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }
    
    .btn-edit {
        background-color: #007bff;
        color: white;
    }
    
    .btn-delete {
        background-color: #dc3545;
        color: white;
    }
</style>

<!-- JavaScript específico de esta vista -->
<script>
    // Aquí va el JavaScript específico de esta vista
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            alert('Función de editar cliente');
        });
    });
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if(confirm('¿Está seguro de eliminar este cliente?')) {
                alert('Función de eliminar cliente');
            }
        });
    });
</script>

<?php
// Paso 4: Capturar el contenido y cerrar el buffer
$contenido_pagina = ob_get_clean();

// Paso 5: Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>