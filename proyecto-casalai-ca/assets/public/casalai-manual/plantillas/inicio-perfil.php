<?php require_once __DIR__ . "/../utils.php"; ?>

<h2 class="text-primary" id="seccion-<?= $nombre_plural ?>">
    Gestión de Perfil
</h2>

<section>
    <p>En el logo de usuario en <strong>la esquina superior derecha</strong>, al hacer clic se desplegara un menu lateral con la opción <strong>Mi <?= $nombre_singular ?></strong> que lo
     llevará a la <strong>Sección de Gestion de Perfil</strong>.
    </p>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Barra lateral</th>
                <th>Sección de Gestión de Perfil</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><?= renderImagen($id, "barra.png") ?></td>
                <td><?= renderImagen($id, "perfil.png") ?></td>
            </tr>
        </tbody>
    </table>

    <p>En esta seccion en el <strong><?= $nombre_singular ?></strong> se podra gestionar:</p>

    <ul>
        <?php foreach ($gestionable as $item): ?>
            <li><?= $item ?></li>
        <?php endforeach ?>
    </ul>
</section>