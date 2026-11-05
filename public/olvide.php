<?php

/**
 * Olvidé mi contraseña: se escribe el email y, si hay una cuenta, llega un correo con un enlace
 * (restablecer.php?token=...) que vale 1 hora y una sola vez.
 * La respuesta es siempre la misma, exista o no la cuenta: así nadie puede averiguar qué emails están registrados.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Cooki\Config;
use Cooki\Correo;
use Cooki\Db;
use Cooki\Repositorios\RecuperacionRepositorio;
use Cooki\Repositorios\UsuarioRepositorio;
use Cooki\Sesion;

if (Sesion::estaLogueado()) {
    redirigir('app/perfil.php');
}

if (esPost()) {
    Sesion::verificarCsrf();
    $email = mb_strtolower(campo('email'));
    $usuario = filter_var($email, FILTER_VALIDATE_EMAIL)
        ? (new UsuarioRepositorio(Db::conexion()))->buscarPorEmail($email)
        : null;
    $pedidos = new RecuperacionRepositorio(Db::conexion());
    if ($usuario !== null && $pedidos->pedidosRecientes((int) $usuario['id']) < RecuperacionRepositorio::PEDIDOS_POR_HORA) {
        $token = $pedidos->crear((int) $usuario['id']);
        $enlace = urlAbsoluta('restablecer.php?token=' . $token);
        Correo::enviar(
            (string) $usuario['email'],
            'Cooki · Cambiar tu contraseña',
            'Hola, ' . explode(' ', (string) $usuario['nombre'])[0] . ".\n\n"
                . "Recibimos un pedido para cambiar la contraseña de tu cuenta de Cooki.\n"
                . "Para elegir una nueva, abrí este enlace (vale por 1 hora y una sola vez):\n\n"
                . $enlace . "\n\n"
                . "Si no lo pediste vos, ignorá este correo: tu contraseña sigue siendo la misma.\n\n"
                . '— El equipo de Cooki'
        );
    }
    redirigir('olvide.php?enviado=1');
}

$enviado = isset($_GET['enviado']);
$titulo = 'Recuperar la contraseña';
require parcial('encabezado');
?>
<div class="con-stickers">
<?php
$stickers = ['limon', 'ajo', 'miel', 'pera', 'zanahoria', 'kiwi', 'tomate', 'banana', 'cereza', 'palta'];
$zona = 'costados';
require parcial('stickers');
?>
<section class="tarjeta tarjeta--angosta">
  <h1>¿Olvidaste tu contraseña?</h1>
  <?php if ($enviado) : ?>
    <p role="status"><strong>Listo.</strong> Si hay una cuenta con ese email, te mandamos un enlace para elegir una contraseña nueva.
      Vale por 1 hora. Revisá también la carpeta de spam.</p>
    <?php if (!Config::esProduccion()) : ?>
      <p class="texto-suave"><small>En el entorno de prueba (Docker), los correos llegan a Mailpit: http://localhost:8025</small></p>
    <?php endif; ?>
    <p><a href="<?= e(url('login.php')) ?>">Volver a ingresar</a></p>
  <?php else : ?>
    <p>Escribí el email de tu cuenta y te mandamos un enlace para elegir una nueva.</p>
    <form class="formulario" method="post">
      <?= Sesion::campoCsrf() ?>
      <div class="campo">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email">
      </div>
      <button class="boton" type="submit">Mandarme el enlace</button>
    </form>
    <p class="texto-suave"><a href="<?= e(url('login.php')) ?>">Volver a ingresar</a></p>
  <?php endif; ?>
</section>
</div>
<?php require parcial('pie'); ?>
