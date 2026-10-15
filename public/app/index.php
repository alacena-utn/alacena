<?php

/**
 * Mi cocina (provisoria). P2 la completa en la tarea P2-E.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Sesion;

Sesion::requiereLogin();

$titulo = 'Mi cocina';
require parcial('encabezado');
?>
<h1>Hola, <?= e(Sesion::usuario()['nombre']) ?></h1>
<p>Ingresaste correctamente. Esta página se completa en la tarea P2-E.</p>
<?php require parcial('pie'); ?>
