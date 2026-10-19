<?php

/**
 * Mi cocina: la página a la que llega el usuario al ingresar.
 * Muestra lo que está por vencer (como imanes en la puerta) y las recetas que puede hacer con su despensa.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\DespensaRepositorio;
use Alacena\Repositorios\RecetaRepositorio;
use Alacena\Sesion;

Sesion::requiereLogin();

$db = Db::conexion();
$usuario = Sesion::usuario();
$porVencer = (new DespensaRepositorio($db))->porVencer($usuario['id'], 7);
$sugeridas = (new RecetaRepositorio($db))->sugeridas($usuario['id'], 6);

$titulo = 'Mi cocina';
require parcial('encabezado');
?>
<h1>Hola, <?= e(explode(' ', $usuario['nombre'])[0]) ?></h1>

<section aria-labelledby="usalo-pronto">
  <h2 id="usalo-pronto" class="titulo-mano">Usalo pronto</h2>
  <?php if ($porVencer === []) : ?>
    <p class="vacio">Nada vence en los próximos 7 días. <a href="<?= e(url('app/despensa.php')) ?>">Ver mi despensa</a></p>
  <?php else : ?>
    <ul class="puerta-cocina">
      <?php foreach ($porVencer as $item) : ?>
        <?php
          $dias = (int) $item['dias'];
          [$texto, $estilo] = DespensaRepositorio::estadoVencimiento($dias);
        ?>
        <li class="iman">
          <img src="<?= e(url(DespensaRepositorio::dibujo($item['nombre']))) ?>" alt="" width="96" height="96">
          <?php if ($dias <= 1) : ?>
            <span class="iman__ya" aria-hidden="true">¡ya!</span>
          <?php endif; ?>
          <span class="iman__cinta"><?= e($item['nombre']) ?></span>
          <span class="etiqueta etiqueta--<?= e($estilo) ?>"><?= e($texto) ?></span>
          <a class="iman__receta" href="<?= e(url('app/recetas.php?q=' . urlencode($item['nombre']))) ?>">Recetas con <?= e(mb_strtolower($item['nombre'])) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<section aria-labelledby="podes-cocinar">
  <div class="encabezado-pagina">
    <h2 id="podes-cocinar" class="titulo-mano">Podés cocinar</h2>
    <a href="<?= e(url('app/recetas.php')) ?>">Ver todas las recetas →</a>
  </div>
  <?php if ($sugeridas === []) : ?>
    <p class="vacio">Todavía no hay sugerencias. <a href="<?= e(url('app/despensa.php')) ?>">Cargá lo que tenés en tu despensa</a> y te mostramos qué cocinar.</p>
  <?php else : ?>
    <div class="grilla">
      <?php foreach ($sugeridas as $receta) : ?>
        <?php require parcial('tarjeta-receta'); ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require parcial('pie'); ?>
