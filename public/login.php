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
    // Mi cocina ya saluda con "Hola, …"; el panel de administración no, así que ahí va el aviso
    if ($usuario['rol'] === Sesion::ROL_ADMIN) {
        Sesion::avisar('exito', 'Hola, ' . explode(' ', (string) $usuario['nombre'])[0] . '.');
    }
    redirigir($usuario['rol'] === Sesion::ROL_ADMIN ? 'admin/index.php' : 'app/index.php');
}

$titulo = 'Ingresar';
require parcial('encabezado');
?>
<div class="con-stickers">
<?php
$stickers = ['tomate', 'leche', 'huevo', 'limon', 'queso-cremoso', 'palta', 'zanahoria', 'frutilla', 'manzana', 'naranja'];
$zona = 'costados';
require parcial('stickers');
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
<div class="stickers-abajo">
<?php
$stickers = ['pan', 'cafe', 'uva', 'kiwi', 'miel'];
$zona = 'abajo';
require parcial('stickers');
?>
</div>
</div>
<?php require parcial('pie'); ?>
