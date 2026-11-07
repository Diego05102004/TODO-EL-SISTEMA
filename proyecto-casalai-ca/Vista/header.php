<link rel="icon" type="image/png" href="assets/img/LOGO.png">

<!-- jQuery (solo una versión) -->
<script src="assets/javascript/js/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 CSS -->
<link href="assets/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="assets/styles/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="assets/public/datatables-custom.css">

<!-- Estilos personalizados -->
<link rel="stylesheet" href="assets/styles/new_menu.css">
<link rel="stylesheet" href="assets/styles/formulario.css">
<link rel="stylesheet" href="assets/styles/tabla_consulta.css">
<link rel="stylesheet" href="assets/styles/modalmodificar.css">
<link rel="stylesheet" href="assets/styles/modaldetalles.css">
<link rel="stylesheet" href="assets/styles/fondo.css">
<link rel="stylesheet" href="assets/styles/perfil.css">
<link rel="stylesheet" href="assets/styles/grafica.css">
<link rel="stylesheet" href="assets/styles/reportes.css">
<link rel="stylesheet" href="assets/styles/global.css">
<link rel="stylesheet" href="assets/styles/catalogo.css">

<!-- Bootstrap JS Bundle with Popper -->
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="assets/public/js/sweetalert2.js"></script>
<link rel="stylesheet" href="assets/styles/sweetalert2.min.css">

<!-- Chart.js -->
<script src="assets/javascript/js/chart(v4.5.1).js"></script>

<!-- DataTables JS -->
<script type="text/javascript" src="assets/javascript/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="assets/javascript/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/javascript/js/sweetalert2@11.js"></script>
<!-- DataTables Buttons -->
<script type="text/javascript" src="assets/javascript/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="assets/javascript/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="assets/javascript/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="assets/javascript/js/buttons.print.min.js"></script>

<!-- PDFMake (requerido para botón PDF) -->
<script type="text/javascript" src="assets/javascript/js/pdfmake.min.js"></script>
<script type="text/javascript" src="assets/javascript/js/vfs_fonts.js"></script>

<!-- JSZip (requerido para botón Excel) -->
<script type="text/javascript" src="assets/javascript/js/jszip.min.js"></script>

<!-- Script de inicialización automática de WebSocket -->
<script>
// Asegurar que el WebSocket se inicialice automáticamente después del login
document.addEventListener('DOMContentLoaded', function() {
    const usuarioId = '<?php echo isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : ''; ?>';
    
    // Si hay un usuario logueado, inicializar el WebSocket
    if (usuarioId && usuarioId !== '0') {
        console.log('Usuario detectado, inicializando WebSocket para:', usuarioId);
        
        // Esperar a que la clase NotificacionesWebSocket esté disponible
        const initWebSocket = () => {
            if (typeof NotificacionesWebSocket !== 'undefined') {
                // Si ya existe, reconectar
                if (typeof window.notificacionesWS !== 'undefined') {
                    console.log('WebSocket ya existe, reconectando...');
                    window.notificacionesWS.reconectar();
                } else {
                    // Crear nueva instancia
                    console.log('Creando nueva instancia de WebSocket...');
                    window.notificacionesWS = new NotificacionesWebSocket(usuarioId);
                    console.log('WebSocket inicializado automáticamente');
                }
                
                // Verificar que la conexión se establezca correctamente
                setTimeout(() => {
                    if (window.notificacionesWS.socket && window.notificacionesWS.socket.readyState === WebSocket.OPEN) {
                        console.log('Conexión WebSocket establecida correctamente');
                    } else {
                        console.warn('WebSocket no pudo conectar, intentando reconexión...');
                        if (window.notificacionesWS.reconectar) {
                            window.notificacionesWS.reconectar();
                        }
                    }
                }, 3000);
            } else {
                // Si la clase aún no está cargada, reintentar en 100ms
                setTimeout(initWebSocket, 100);
            }
        };
        
        initWebSocket();
    }
    
    // Verificación periódica del servidor WebSocket (cada 30 segundos)
    setInterval(() => {
        if (usuarioId && usuarioId !== '0') {
            fetch('?pagina=verificar_websocket_status', {
                method: 'GET',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.websocket_running && window.notificacionesWS && window.notificacionesWS.reconectar) {
                    console.log('Servidor WebSocket no detectado, intentando reconexión...');
                    window.notificacionesWS.reconectar();
                }
            })
            .catch(error => {
                console.log('Error verificando estado WebSocket:', error.message);
                // Si hay un error 404, el endpoint no está disponible
                if (error.message.includes('404')) {
                    console.warn('Endpoint de verificación no disponible, deshabilitando verificación periódica');
                    // Detener la verificación periódica
                    clearInterval(this);
                }
            });
        }
    }, 30000);
});
</script>

<!-- Validador de JWT en tiempo real con sistema de extensión de sesión -->
 <?php if(!empty($_SESSION) && isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] !== 0) { ?>
<script src="assets/javascript/jwt_validator.js"></script>
<?php } ?>