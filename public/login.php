<?php

/**
 * Ingreso. Si los datos son correctos guarda al usuario en la sesión y lo manda a su página
 * según el rol; si no, redirige a la página de error.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\UsuarioRepositorio;
use Alacena\Sesion;

if (Sesion::estaLogueado()) {
    redirigir(Sesion::esAdmin() ? 'admin/index.php' : 'app/index.php');
}

if (esPost()) {
    Sesion::verificarCsrf();
    $email = mb_strtolower(campo('email'));
    $contrasena = (string) ($_POST['contrasena'] ?? '');

    $repo = new UsuarioRepositorio(Db::conexion());
    $usuario = $repo->verificarCredenciales($email, $contrasena);

    if ($usuario === null) {
        // Mismo mensaje si el email no existe o si la contraseña está mal:
        // así nadie puede averiguar qué emails están registrados.
        redirigir('error.php?motivo=login');
    }

    Sesion::ingresar($usuario);
    Sesion::avisar('exito', 'Hola, ' . $usuario['nombre'] . '.');
    redirigir($usuario['rol'] === Sesion::ROL_ADMIN ? 'admin/index.php' : 'app/index.php');
}

$titulo = 'Ingresar';
require parcial('encabezado');
?>
<section class="tarjeta tarjeta--angosta">
  <h1>Ingresar</h1>
  <form class="formulario" method="post">
    <?= Sesion::campoCsrf() ?>
    <div class="campo">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="username">
    </div>
    <div class="campo">
      <label for="contrasena">Contraseña</label>
      <input id="contrasena" name="contrasena" type="password" required autocomplete="current-password">
    </div>
    <button class="boton" type="submit">Ingresar</button>
  </form>
  <p class="texto-suave">¿No tenés cuenta? <a href="<?= e(url('registro.php')) ?>">Creá una gratis</a>.</p>
</section>
<?php require parcial('pie'); ?>
