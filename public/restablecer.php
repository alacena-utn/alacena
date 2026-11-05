<?php

/**
 * Elegir una contraseña nueva desde el enlace del correo (restablecer.php?token=...).
 * El enlace vale 1 hora y una sola vez. Al cambiarla se anulan los otros enlaces pendientes
 * y se borran los intentos fallidos de ingreso (si estaba bloqueado, se desbloquea).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Cooki\Db;
use Cooki\Repositorios\IngresoRepositorio;
use Cooki\Repositorios\RecuperacionRepositorio;
use Cooki\Repositorios\UsuarioRepositorio;
use Cooki\Sesion;

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$pedidos = new RecuperacionRepositorio(Db::conexion());
$pedido = $pedidos->vigente($token);
$errores = [];

if (esPost() && $pedido !== null) {
    Sesion::verificarCsrf();
    $contrasena = (string) ($_POST['contrasena'] ?? '');
    $error = UsuarioRepositorio::errorContrasena($contrasena);
    $errores = $error === null ? [] : ['contrasena' => $error];
    if ($contrasena !== (string) ($_POST['repetir'] ?? '')) {
        $errores['repetir'] = 'Las contraseñas no coinciden.';
    }
    if ($errores === []) {
        (new UsuarioRepositorio(Db::conexion()))->cambiarContrasena((int) $pedido['usuario_id'], $contrasena);
        $pedidos->usar((int) $pedido['usuario_id']);
        (new IngresoRepositorio(Db::conexion()))->limpiar((string) $pedido['email']);
        Sesion::avisar('exito', 'Listo, cambiamos tu contraseña. Ya podés ingresar con la nueva.');
        redirigir('login.php');
    }
}

$titulo = 'Contraseña nueva';
require parcial('encabezado');
?>
<div class="con-stickers">
<?php
$stickers = ['limon', 'ajo', 'miel', 'pera', 'zanahoria', 'kiwi', 'tomate', 'banana', 'cereza', 'palta'];
$zona = 'costados';
require parcial('stickers');
?>
<section class="tarjeta tarjeta--angosta">
  <?php if ($pedido === null) : ?>
    <h1>El enlace no sirve</h1>
    <p>Venció (dura 1 hora) o ya se usó. Pedí otro y usalo enseguida.</p>
    <p class="acciones"><a class="boton" href="<?= e(url('olvide.php')) ?>">Pedir otro enlace</a></p>
  <?php else : ?>
    <h1>Elegí tu contraseña nueva</h1>
    <p class="texto-suave">Para la cuenta <?= e($pedido['email']) ?>.</p>
    <form class="formulario" method="post" novalidate>
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="campo">
        <label for="contrasena">Contraseña nueva</label>
        <input id="contrasena" name="contrasena" type="password" required minlength="8" autocomplete="new-password"
               aria-describedby="ayuda-contrasena">
        <small id="ayuda-contrasena">Al menos 8 caracteres, con letras y números.</small>
        <?php if (isset($errores['contrasena'])) : ?><p class="error-campo"><?= e($errores['contrasena']) ?></p><?php endif; ?>
      </div>
      <div class="campo">
        <label for="repetir">Repetí la contraseña</label>
        <input id="repetir" name="repetir" type="password" required minlength="8" autocomplete="new-password">
        <?php if (isset($errores['repetir'])) : ?><p class="error-campo"><?= e($errores['repetir']) ?></p><?php endif; ?>
      </div>
      <button class="boton" type="submit">Guardar la contraseña</button>
    </form>
  <?php endif; ?>
</section>
</div>
<?php require parcial('pie'); ?>
