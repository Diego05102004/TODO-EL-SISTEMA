<?php
if (isset($_SESSION['mensaje'])) {
    echo '<div class="alert alert-' . $_SESSION['mensaje']['tipo'] . '">' . $_SESSION['mensaje']['texto'] . '</div>';
    unset($_SESSION['mensaje']);
}
?>

<div class="container">
    <div class="form-wrapper">
        <h2>Recuperar Contraseña</h2>
        <form method="post" action="/proyecto-casalai-ca/index.php?pagina=password-recovery&action=request">
            <div class="input-field">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Correo Electrónico" required>
            </div>
            
            <button type="submit" class="btn btn-vino">Enviar Enlace</button>
        </form>
    </div>
</div>