<?php
/**
 * Tarjeta de una receta para los listados. Espera la variable $receta
 * (una fila de RecetaRepositorio::buscar() o ::sugeridas()).
 */

use Alacena\Repositorios\RecetaRepositorio;

$faltan = (int) $receta['total'] - (int) $receta['tengo'];
?>
<article class="tarjeta tarjeta-receta">
  <h2><a href="<?= e(url('app/receta.php?id=' . $receta['id'])) ?>"><?= e($receta['titulo']) ?></a></h2>
  <p class="texto-suave"><?= e($receta['descripcion']) ?></p>
  <p class="tarjeta-receta__datos">
    <span><?= e($receta['minutos']) ?> min</span> ·
    <span><?= e(RecetaRepositorio::DIFICULTADES[(int) $receta['dificultad']] ?? '') ?></span> ·
    <span><?= e($receta['porciones_base']) ?> porciones</span>
  </p>
  <p>
    <?php if ($faltan <= 0) : ?>
      <span class="etiqueta etiqueta--exito">Tenés todo</span>
    <?php else : ?>
      <span class="etiqueta<?= $faltan <= 2 ? ' etiqueta--alerta' : '' ?>">Tenés <?= e($receta['tengo']) ?> de <?= e($receta['total']) ?></span>
    <?php endif; ?>
    <?php foreach (array_filter(explode(', ', (string) $receta['etiquetas'])) as $etiqueta) : ?>
      <span class="etiqueta"><?= e(str_replace('_', ' ', $etiqueta)) ?></span>
    <?php endforeach; ?>
  </p>
</article>