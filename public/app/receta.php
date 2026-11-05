<?php

/**
 * Detalle de una receta: ingredientes (marcando cuáles tiene el usuario y con qué reemplazar lo que falta)
 * y pasos. Se puede escalar a otra cantidad de porciones:
 *   receta.php?id=6&porciones=2 → cantidades y pasos ({ing:...}) recalculados (Dominio\Porciones)
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Cooki\Db;
use Cooki\Dominio\Porciones;
use Cooki\Repositorios\RecetaRepositorio;
use Cooki\Sesion;

Sesion::requiereLogin();

$receta = (new RecetaRepositorio(Db::conexion()))->obtener((int) ($_GET['id'] ?? 0), Sesion::idUsuario());
if ($receta === null) {
    redirigir('error.php?motivo=no-encontrado');
}

// Porciones elegidas (por defecto, las de la receta) y factor para escalar
$base = (int) $receta['porciones_base'];
$porciones = max(Porciones::MINIMO, min(Porciones::MAXIMO, (int) ($_GET['porciones'] ?? $base)));
$factor = Porciones::factor($base, $porciones);
$cantidad = fn (array $ing): string => Porciones::texto(
    $ing['cantidad'] === null ? null : (float) $ing['cantidad'],
    $ing['unidad'],
    $factor
);
$porSlug = [];
foreach ($receta['ingredientes'] as $ing) {
    $porSlug[Porciones::slug($ing['nombre'])] = [
        'nombre' => $ing['nombre'],
        'cantidad' => $ing['cantidad'] === null ? null : (float) $ing['cantidad'],
        'unidad' => $ing['unidad'],
    ];
}
$enlacePorciones = fn (int $n): string => url('app/receta.php?id=' . $receta['id'] . '&porciones=' . $n) . '#ingredientes';

$titulo = $receta['titulo'];
require parcial('encabezado');
?>
<?= enlaceVolver('app/recetas.php', 'Volver a Recetas', [['app/recetas.php', 'Recetas']]) ?>
<article>
  <h1><?= e($receta['titulo']) ?></h1>
  <p class="texto-suave"><?= e($receta['descripcion']) ?></p>
  <p class="acciones">
    <span class="etiqueta">Preparación: <?= e($receta['minutos_prep']) ?> min</span>
    <span class="etiqueta">Cocción: <?= e($receta['minutos_coccion']) ?> min</span>
    <span class="etiqueta"><?= e(RecetaRepositorio::DIFICULTADES[(int) $receta['dificultad']]) ?></span>
    <?php foreach ($receta['etiquetas'] as $etiqueta) : ?>
      <span class="etiqueta"><?= e(str_replace('_', ' ', $etiqueta['nombre'])) ?></span>
    <?php endforeach; ?>
  </p>

  <div class="detalle-receta">
    <section class="tarjeta" id="ingredientes">
      <h2>Ingredientes</h2>
      <?php /* Porciones: − y + son enlaces (andan sin JavaScript); las cantidades y los pasos se recalculan */ ?>
      <p class="porciones" aria-label="Porciones">
        <a class="porciones__boton<?= $porciones <= Porciones::MINIMO ? ' porciones__boton--tope' : '' ?>"
           href="<?= e($enlacePorciones(max(Porciones::MINIMO, $porciones - 1))) ?>" aria-label="Una porción menos">−</a>
        <span class="porciones__numero"><strong><?= $porciones ?></strong> porci<?= $porciones === 1 ? 'ón' : 'ones' ?></span>
        <a class="porciones__boton<?= $porciones >= Porciones::MAXIMO ? ' porciones__boton--tope' : '' ?>"
           href="<?= e($enlacePorciones(min(Porciones::MAXIMO, $porciones + 1))) ?>" aria-label="Una porción más">+</a>
        <?php if ($porciones !== $base) : ?>
          <a class="porciones__volver" href="<?= e($enlacePorciones($base)) ?>">Volver a <?= $base ?></a>
        <?php endif; ?>
      </p>
      <ul class="lista-ingredientes">
        <?php foreach ($receta['ingredientes'] as $ing) : ?>
          <?php $tiene = $ing['en_despensa'] || $ing['es_basico']; ?>
          <li class="<?= $tiene ? 'tengo' : 'falta' ?>">
            <span aria-hidden="true"><?= $tiene ? '✓' : '✗' ?></span>
            <span class="solo-lectores"><?= $tiene ? 'Lo tenés:' : 'Te falta:' ?></span>
            <img class="icono-chico" src="<?= e(iconoIngrediente($ing['nombre'])) ?>" alt="" width="28" height="28">
            <strong><?= e($ing['nombre']) ?></strong>
            <?= e($cantidad($ing)) ?>
            <?php if ($ing['nota']) : ?><span class="texto-suave">(<?= e($ing['nota']) ?>)</span><?php endif; ?>
            <?php if ($ing['es_opcional']) : ?><span class="etiqueta">opcional</span><?php endif; ?>
            <?php if (!$tiene && $ing['sustituto'] !== null) : ?>
              <span class="cambio-ingrediente">⇄ Podés usar <?= e(mb_strtolower($ing['sustituto'])) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="texto-suave"><small>✓ = está en tu despensa (o es un básico como sal o aceite).</small></p>
    </section>

    <section class="tarjeta">
      <h2>Paso a paso</h2>
      <?php /* Cada paso se puede tildar para saber por dónde vas. receta.js lo recuerda en este navegador. */ ?>
      <ol class="lista-pasos" data-receta="<?= e($receta['id']) ?>">
        <?php foreach ($receta['pasos'] as $paso) : ?>
          <li class="paso">
            <label>
              <input class="paso__check" type="checkbox">
              <span class="paso__texto"><?= e(Porciones::pasoConCantidades($paso['instruccion'], $porSlug, $factor)) ?></span>
            </label>
          </li>
        <?php endforeach; ?>
      </ol>
      <p class="pasos-acciones" hidden>
        <button class="boton boton--secundario boton--chico" type="button" id="reiniciar-pasos">Empezar de nuevo</button>
      </p>
    </section>
  </div>
  <?php if ($receta['autor']) : ?>
    <p class="texto-suave"><small>Cargada por <?= e($receta['autor']) ?>.</small></p>
  <?php endif; ?>
</article>
<script src="<?= e(recurso('js/receta.js')) ?>" defer></script>
<?php require parcial('pie'); ?>
