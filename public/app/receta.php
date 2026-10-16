<?php

/**
 * Detalle de una receta: ingredientes (marcando cuáles tiene el usuario) y pasos.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\RecetaRepositorio;
use Alacena\Sesion;

Sesion::requiereLogin();

$receta = (new RecetaRepositorio(Db::conexion()))->obtener((int) ($_GET['id'] ?? 0), Sesion::idUsuario());
if ($receta === null) {
    redirigir('error.php?motivo=no-encontrado');
}

/** Cantidad legible: 600.00 → "600", 1.50 → "1,5" */
$cantidad = function (?string $valor, ?string $unidad): string {
    if ($valor === null) {
        return '';
    }
    $numero = rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
    return trim($numero . ' ' . ($unidad === 'u' ? '' : (string) $unidad));
};

$titulo = $receta['titulo'];
require parcial('encabezado');
?>
<p><a href="<?= e(url('app/recetas.php')) ?>">← Volver a recetas</a></p>
<article>
    <h1><?= e($receta['titulo']) ?></h1>
    <p class="texto-suave"><?= e($receta['descripcion']) ?></p>
    <p class="acciones">
        <span class="etiqueta">Preparación: <?= e($receta['minutos_prep']) ?> min</span>
        <span class="etiqueta">Cocción: <?= e($receta['minutos_coccion']) ?> min</span>
        <span class="etiqueta"><?= e(RecetaRepositorio::DIFICULTADES[(int) $receta['dificultad']]) ?></span>
        <span class="etiqueta"><?= e($receta['porciones_base']) ?> porciones</span>
        <?php foreach ($receta['etiquetas'] as $etiqueta) : ?>
            <span class="etiqueta"><?= e(str_replace('_', ' ', $etiqueta['nombre'])) ?></span>
        <?php endforeach; ?>
    </p>

  <div class="detalle-receta">
    <section class="tarjeta">
      <h2>Ingredientes</h2>
      <ul class="lista-ingredientes">
        <?php foreach ($receta['ingredientes'] as $ing) : ?>
          <?php $tiene = $ing['en_despensa'] || $ing['es_basico']; ?>
          <li class="<?= $tiene ? 'tengo' : 'falta' ?>">
            <span aria-hidden="true"><?= $tiene ? '✓' : '✗' ?></span>
            <span class="solo-lectores"><?= $tiene ? 'Lo tenés:' : 'Te falta:' ?></span>
            <strong><?= e($ing['nombre']) ?></strong>
            <?= e($cantidad($ing['cantidad'], $ing['unidad'])) ?>
            <?php if ($ing['nota']) : ?><span class="texto-suave">(<?= e($ing['nota']) ?>)</span><?php endif; ?>
            <?php if ($ing['es_opcional']) : ?><span class="etiqueta">opcional</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="texto-suave"><small>✓ = está en tu despensa (o es un básico como sal o aceite).</small></p>
    </section>

    <section class="tarjeta">
      <h2>Paso a paso</h2>
      <ol class="lista-pasos">
        <?php foreach ($receta['pasos'] as $paso) : ?>
          <li><?= e($paso['instruccion']) ?></li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
  <?php if ($receta['autor']) : ?>
    <p class="texto-suave"><small>Cargada por <?= e($receta['autor']) ?>.</small></p>
  <?php endif; ?>
</article>
<?php require parcial('pie'); ?>

