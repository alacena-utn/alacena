<?php

/**
 * Página principal (pública). P2 la completa en la tarea P2-b.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$titulo = 'Inicio';
require parcial('encabezado');
?>
<h1>Alacena</h1>
<p>Tu despensa y qué cocinar con lo que tenés.</p>
<p class="acciones">
  <a class="boton" href="<?= e(url('registro.php')) ?>">Crear cuenta</a>
  <a class="boton boton--secundario" href="<?= e(url('login.php')) ?>">Ingresar</a>
</p>
<?php require parcial('pie'); ?>
