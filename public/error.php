<?php

/**
 * Página de error: a acá se redirige cuando falla el ingreso o cuando alguien entra
 * a una página que no le corresponde. El motivo llega por la URL (error.php?motivo=login).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Sesion;

$mensajes = [
    'login' => ['No pudimos ingresar', 'El email o la contraseña no son correctos. Revisalos y probá de nuevo.'],
    'sin-sesion' => ['Tenés que ingresar', 'Esa página es solo para usuarios registrados. Ingresá con tu cuenta para verla.'],
    'sin-permiso' => ['Acceso restringido', 'Esa sección es solo para administradores.'],
    'formulario-vencido' => ['El formulario venció', 'Por seguridad no pudimos procesar el envío. Volvé a la página e intentá otra vez.'],
    'no-encontrado' => ['No encontramos lo que buscabas', 'Puede que se haya borrado o que el enlace esté mal.'],
];
$motivo = (string) ($_GET['motivo'] ?? '');
[$tituloError, $detalle] = $mensajes[$motivo] ?? ['Algo salió mal', 'Ocurrió un error inesperado.'];

http_response_code(match ($motivo) {
    'login', 'sin-sesion' => 401,
    'sin-permiso', 'formulario-vencido' => 403,
    'no-encontrado' => 404,
    default => 400,
});

$titulo = $tituloError;
require parcial('encabezado');
?>
<section class="tarjeta tarjeta--angosta" role="alert">
  <h1><?= e($tituloError) ?></h1>
  <p><?= e($detalle) ?></p>
  <p class="acciones">
    <?php if (Sesion::estaLogueado()) : ?>
      <a class="boton" href="<?= e(url(Sesion::esAdmin() ? 'admin/index.php' : 'app/index.php')) ?>">Ir a mi inicio</a>
    <?php else : ?>
      <a class="boton" href="<?= e(url('login.php')) ?>">Ir a ingresar</a>
      <a class="boton boton--secundario" href="<?= e(url('index.php')) ?>">Volver al inicio</a>
    <?php endif; ?>
  </p>
</section>
<?php require parcial('pie'); ?>
