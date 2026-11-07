<?php
// Archivo: dashboard_base.php - Estructura base del dashboard reutilizable
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina ?? 'Dashboard'; ?> - CasaLai</title>
    <link rel="icon" type="image/png" href="assets/img/LOGO.png">
    <link rel="stylesheet" href="Vista/VistaNew/VistaNew.css">
    <link rel="stylesheet" href="assets/styles/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/public/datatables-custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php require_once __DIR__ . '/dashboard_header.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <?php echo $contenido_pagina ?? ''; ?>
        </div>
    </main>

    <script src="assets/public/js/data-table.js"></script>
    <script src="assets/public/js/sweetalert2.js"></script>
    <script>
        function confirmarCerrarSesion() {
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                window.location.href = '?pagina=cerrar';
            }
        }

        // Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('dateFilter');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
        });
    </script>

    <!-- Estilos comunes para modales -->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
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

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #ddd;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px;
            text-align: right;
            border-top: 1px solid #dee2e6;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</body>

</html>