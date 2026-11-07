<?php
require_once 'Config/Config.php';

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PasswordRecoveryModel extends BD {
    private $conex;

    public function __construct() {
        parent::__construct('S'); // Usar base de datos de seguridad
        $this->conex = $this->getConexion();
    }

    /**
     * Verifica si un correo electrónico existe en la tabla de usuarios.
     */
    public function verificarCorreo($correo) {
        try {
            $stmt = $this->conex->prepare("SELECT id_usuario, cedula, correo FROM tbl_usuarios WHERE correo = ? AND estatus = 'habilitado'");
            $stmt->bindValue(1, $correo);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Guarda un token de recuperación de contraseña y su fecha de expiración para un usuario.
     */
    public function guardarToken($id_usuario, $token, $expira) {
        try {
            $stmt = $this->conex->prepare(
                "UPDATE tbl_usuarios SET reset_token = ?, reset_token_expira = ? WHERE id_usuario = ?"
            );
            $stmt->bindValue(1, $token);
            $stmt->bindValue(2, $expira);
            $stmt->bindValue(3, $id_usuario);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Busca un usuario por su token de recuperación, asegurándose de que no haya expirado.
     */
    public function buscarUsuarioPorToken($token) {
        try {
            $stmt = $this->conex->prepare(
                "SELECT id_usuario, cedula FROM tbl_usuarios WHERE reset_token = ? AND reset_token_expira > NOW()"
            );
            $stmt->bindValue(1, $token);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la contraseña de un usuario y limpia el token de recuperación.
     */
    public function actualizarClave($id_usuario, $nuevaClave) {
        try {
            $nuevaClaveHasheada = password_hash($nuevaClave, PASSWORD_DEFAULT);
            $stmt = $this->conex->prepare(
                "UPDATE tbl_usuarios SET password = ?, reset_token = NULL, reset_token_expira = NULL WHERE id_usuario = ?"
            );
            $stmt->bindValue(1, $nuevaClaveHasheada);
            $stmt->bindValue(2, $id_usuario);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Envía el correo de recuperación de contraseña usando PHPMailer.
     */
    public function enviarCorreoRecuperacion($destinatario, $token) {
        // Cargar la configuración de correo
        $config = require __DIR__ . '/../config/mail_config.php';

        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = $config['smtp_auth'];
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port       = $config['port'];

            // Remitente y destinatario
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($destinatario);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Recuperacion de Contrasena - CasaLai';

            $enlace = "http://{$_SERVER['HTTP_HOST']}/proyecto-casalai-ca/index.php?pagina=password-recovery&action=show_reset_form&token=$token";

            $mail->Body    = "Hola,<br><br>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:<br><a href='{$enlace}'>Restablecer Contraseña</a><br><br>Si no solicitaste esto, puedes ignorar este correo.";
            $mail->AltBody = "Para restablecer tu contraseña, copia y pega el siguiente enlace en tu navegador: {$enlace}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
?>