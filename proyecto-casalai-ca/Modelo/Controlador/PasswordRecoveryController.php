<?php
require_once 'Modelo/PasswordRecoveryModel.php';

class PasswordRecoveryController {
    
    private $model;
    
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new PasswordRecoveryModel();
    }
    
    public function mostrarFormularioRecuperacion() {
        include 'Vista/password_recovery/form.php';
    }

    public function procesarSolicitud() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
            header('Location: /proyecto-casalai-ca/index.php?pagina=password-recovery');
            exit;
        }

        $correo = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        if (!$correo) {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Correo electrónico no válido.'];
            header('Location: /proyecto-casalai-ca/index.php?pagina=password-recovery');
            exit;
        }

        $usuario = $this->model->verificarCorreo($correo);
        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->model->guardarToken($usuario['id_usuario'], $token, $expira);

            // Enviar el correo de recuperación
            if ($this->model->enviarCorreoRecuperacion($correo, $token) === true) {
                $_SESSION['mensaje'] = [
                    'tipo' => 'success',
                    'texto' => 'Si tu correo electrónico está en nuestros registros, recibirás un enlace para restablecer tu contraseña.'
                ];
            } else {
                // Error al enviar el correo
                $_SESSION['mensaje'] = [
                    'tipo' => 'error',
                    'texto' => 'No se pudo enviar el correo de recuperación. Por favor, inténtalo de nuevo más tarde.'
                ];
            }
        } else {
            // Mensaje genérico para no revelar si el correo existe
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Si tu correo electrónico está en nuestros registros, recibirás un enlace para restablecer tu contraseña.'];
        }

        header('Location: /password-recovery');
        exit;
    }

    public function mostrarFormularioReseteo() {
        $token = $_GET['token'] ?? null;
        if (!$token) {
            die('Token no proporcionado.');
        }

        $usuario = $this->model->buscarUsuarioPorToken($token);
        if (!$usuario) {
            die('Token inválido o expirado.');
        }

        include 'Vista/password_recovery/reset_form.php';
    }

    public function procesarReseteo() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['token'], $_POST['password'], $_POST['confirmar'])) {
            die('Solicitud no válida.');
        }

        $token = $_POST['token'];
        $clave = $_POST['password'];
        $confirmar_clave = $_POST['confirmar'];

        if ($clave !== $confirmar_clave) {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Las contraseñas no coinciden.'];
            header('Location: /proyecto-casalai-ca/index.php?pagina=password-recovery&action=show_reset_form&token=' . urlencode($token));
header('Location: /proyecto-casalai-ca/index.php?pagina=login');
            exit;
        }

        $usuario = $this->model->buscarUsuarioPorToken($token);
        if (!$usuario) {
            die('Token inválido o expirado.');
        }

        if ($this->model->actualizarClave($usuario['id_usuario'], $clave)) {
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Tu contraseña ha sido actualizada exitosamente. Ya puedes iniciar sesión.'];
            header('Location: /login');
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se pudo actualizar la contraseña. Inténtalo de nuevo.'];
            header('Location: /password-recovery?action=show_reset_form&token=' . $token);
        }
        exit;
    }
}
?>