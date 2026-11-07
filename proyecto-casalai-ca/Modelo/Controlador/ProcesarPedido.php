<?php
require_once 'Modelo/proveedor.php';
require_once 'Modelo/producto.php';
require_once 'Modelo/bitacora.php';
require_once 'Config/Config.php';

require_once __DIR__ . '/../config/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../config/PHPMailer/Exception.php';
require_once __DIR__ . '/../config/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('MODULO_PEDIDOS')) { define('MODULO_PEDIDOS', 0); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['accion'] ?? '') == 'realizar_pedido') {
    try {
        // Obtener datos del formulario
        $id_producto = $_POST['id_producto'];
        $id_proveedor = $_POST['id_proveedor'];
        $cantidad = $_POST['cantidad'];

        // Inicializar modelos
        $productoModel = new Producto();
        $proveedorModel = new Proveedores();
        $bitacoraModel = new Bitacora();

        // Obtener información del producto
        $producto = $productoModel->obtenerProductoPorId($id_producto);

        // Obtener información del proveedor
        $proveedor = $proveedorModel->obtenerProveedorPorId($id_proveedor);

        if (!$producto || !$proveedor) {
            throw new Exception("No se pudo obtener la información del producto o proveedor");
        }

        // Configurar y enviar correo
        $mail = new PHPMailer(true);

        // Configuración del servidor SMTP (ajusta según tu configuración)
        $mail->isSMTP();
        $mail->Host = 'smtp.tudominio.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tu_correo@tudominio.com';
        $mail->Password = 'tu_contraseña';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Remitente y destinatario
        $mail->setFrom('Darckortgame@gmail.com', 'CASA LAI');
        $mail->addAddress($proveedor['correo'], $proveedor['nombre']);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Nuevo Pedido de Producto - ' . $producto['nombre_producto'];

        $mail->Body = "
            <h1>Solicitud de Pedido</h1>
            <p>Estimado proveedor <strong>{$proveedor['nombre']}</strong>,</p>
            <p>Le informamos que hemos realizado un pedido con los siguientes detalles:</p>

            <table border='1' cellpadding='5' cellspacing='0'>
                <tr>
                    <th>Producto</th>
                    <th>modelo</th>
                    <th>Cantidad Solicitada</th>
                </tr>
                <tr>
                    <td>{$producto['nombre_producto']}</td>
                    <td>{$producto['nombre_modelo']}</td>
                    <td>{$cantidad}</td>
                </tr>
            </table>

            <p>Por favor confirme la recepción de este pedido y la fecha estimada de entrega.</p>
            <p>Atentamente,</p>
            <p>El equipo de Inventario</p>
        ";

        $mail->send();

        // Registrar bitácora si no se está ejecutando en modo de prueba
        if (!defined('SKIP_SIDE_EFFECTS')) {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'] ?? 0,
                MODULO_PEDIDOS,
                'INCLUIR',
                'Pedido realizado del producto ' . ($producto['nombre_producto'] ?? '') . ' al proveedor ' . ($proveedor['nombre'] ?? '') . ' (cantidad: ' . ($cantidad ?? 0) . ')',
                'media'
            );
        }

        // Redirigir con mensaje de éxito
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $_SESSION['mensaje'] = "Pedido realizado correctamente y notificación enviada al proveedor.";
        header('Location: ../Vista/proveedor.php');
    } catch (Exception $e) {
        // En caso de error
        session_start();
        $_SESSION['error'] = "Error al procesar el pedido: " . $e->getMessage();
        header('Location: ../Vista/proveedor.php');
    }
    exit();
}
?>