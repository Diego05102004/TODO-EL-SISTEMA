<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bloqueo</title>
  <?php include 'header.php'; ?>

</head>
<body  class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;" class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
  

<div class="container">
    <div class="alert alert-danger mt-5">
        <h1>Acceso Denegado</h1>
        <p>No tienes permisos para acceder a esta sección.</p>
        <p>Si crees que esto es un error, por favor contacta al administrador.</p>
        <?php if($_SESSION['nombre_rol'] == 'Cliente'){ ?>
        <a href="?pagina=catalogo" class="btn btn-primary">Volver al inicio</a>
        <?php } else { ?>
          <a href="?pagina=Dashboard" class="btn btn-primary">Volver al inicio</a>
                 <?php } ?>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
