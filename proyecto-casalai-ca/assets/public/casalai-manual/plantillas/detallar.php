<?php require_once "utils.php"; ?>

<h3 class="text-primary-emphasis" id="detallar-<?= $nombre_singular ?>">
    Ver Detalles de <?= $nombre_singular ?>
</h3>

<p>En la lista de <strong><?= $nombre_plural ?></strong> encontrará el botón <strong>Ver Detalles</strong> representado por un ícono de ojo <i class="bi bi-eye"></i>:</p>

<p><?= renderImagen($id, "detallar-boton.png") ?></p>

<p>Al hacer clic en este botón, se abrirá una ventana modal mostrando la información detallada del <?= $nombre_singular ?> seleccionado:</p>

<p><?= renderImagen($id, "detallar-modal.png") ?></p>

<p>En esta vista podrá ver toda la información relacionada con el <?= $nombre_singular ?>, incluyendo:</p>

<ul>
    <li>Información básica del registro de <?= $nombre_singular ?></li>
    <li>Información adicional y detallada del registro de <?= $nombre_singular ?></li>
</ul>

<?php if (isset($detallar_extra)): ?>
    <div class="alert alert-info">
        <?= $detallar_extra ?>
    </div>
<?php endif; ?>

<p>Para cerrar la vista de detalles, puede:</p>
<ul>
    <li>Hacer clic en la <strong>X</strong> en la esquina superior derecha</li>
    
</ul>