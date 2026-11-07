<?php
if (isset($_SESSION['mensaje'])) {
    echo '<div class="alert alert-' . $_SESSION['mensaje']['tipo'] . '">' . $_SESSION['mensaje']['texto'] . '</div>';
    unset($_SESSION['mensaje']);
}

$token = $_GET['token'] ?? '';
?>

<div class="container">
    <div class="form-wrapper">
        <h2>Restablecer Contraseña</h2>
        <form method="post" action="/proyecto-casalai-ca/index.php?pagina=password-recovery&action=reset">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            
            <div class="input-field">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Nueva Contraseña" required>
            </div>
            
            <div class="input-field">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirmar" placeholder="Confirmar Contraseña" required>
            </div>
            
            <button type="submit" class="btn btn-vino">Actualizar Contraseña</button>
        </form>
    </div>
</div>