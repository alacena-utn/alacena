<?php
/**
 * Tarjeta de una receta para los listados. Espera la variable $receta
 * (una fila de RecetaRepositorio::buscar() o ::sugeridas()).
 * Toda la tarjeta es clickeable: el enlace del título se estira con CSS (.tarjeta-receta h2 a::before).
 * Muestra los dibujos de hasta 5 ingredientes (y "+N" con el resto); los que faltan van en gris y tachados.
 */

use Cooki\Repositorios\RecetaRepositorio;

$faltan = (int) $receta['total'] - (int) $receta['tengo'];
$dibujos = RecetaRepositorio::ingredientesDeTarjeta($receta['ingredientes'] ?? null);
$maximo = 5;
?>
<article class="tarjeta tarjeta-receta">
  <h2><a href="<?= e(url('app/receta.php?id=' . $receta['id'])) ?>"><?= e($receta['titulo']) ?></a></h2>
  <p class="texto-suave"><?= e($receta['descripcion']) ?></p>
  <?php if ($dibujos !== []) : ?>
    <ul class="mini-ingredientes" aria-label="Ingredientes">
      <?php foreach (array_slice($dibujos, 0, $maximo) as $ing) : ?>
        <li class="<?= $ing['tengo'] ? 'tengo' : 'falta' ?>">
          <img src="<?= e(iconoIngrediente($ing['nombre'])) ?>" width="40" height="40"
               alt="<?= e($ing['nombre'] . ($ing['tengo'] ? '' : ' (te falta)')) ?>" title="<?= e($ing['nombre']) ?>">
        </li>
      <?php endforeach; ?>
      <?php if (count($dibujos) > $maximo) : ?>
        <li class="mini-ingredientes__mas">+<?= count($dibujos) - $maximo ?></li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>
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
