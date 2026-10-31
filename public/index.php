<?php

/**
 * Página principal (pública): la pregunta, qué es Alacena, acceso al registro y al ingreso,
 * una vitrina con todos los dibujos de ingredientes en movimiento y cómo funciona.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Sesion;

$titulo = 'Tu despensa y qué cocinar con lo que tenés';

// Todos los dibujos de ingredientes (menos el frasco genérico), en un orden mezclado pero fijo
$dibujos = array_map(
    fn (string $archivo) => basename($archivo, '.svg'),
    glob(dirname(__DIR__) . '/public/img/ingredientes/*.svg') ?: []
);
$dibujos = array_values(array_diff($dibujos, ['frasco']));
usort($dibujos, fn (string $a, string $b) => crc32($a) <=> crc32($b));
$dibujoInicial = $dibujos[array_rand($dibujos)];
// En la vitrina, 4 dibujos al azar llevan el globo "¡ya!" (cambian en cada carga)
$conYa = (array) array_rand(array_flip($dibujos), 4);
// La vitrina tiene 3 filas: si la cantidad no es múltiplo de 3, se repiten dibujos del medio
// para completar la última columna y que ninguno quede solo con huecos alrededor.
$faltan = (3 - count($dibujos) % 3) % 3;
$vitrina = [];
foreach (array_merge($dibujos, array_slice($dibujos, intdiv(count($dibujos), 2), $faltan)) as $i => $dibujo) {
    // Desorden fijo para cada dibujo: corrimiento, giro y tamaño (igual en las dos copias de la lista).
    // Las columnas van alternadas, una más arriba y otra más abajo, para que no se vean filas.
    $azar = crc32($dibujo . $i);
    $vitrina[] = [$dibujo, sprintf(
        '--x: %dpx; --y: %dpx; --r: %ddeg; --t: %.2f',
        $azar % 49 - 24,
        (intdiv($i, 3) % 2 ? 22 : -22) + ($azar >> 5) % 29 - 14,
        ($azar >> 11) % 17 - 8,
        .82 + (($azar >> 16) % 27) / 100
    )];
}

require parcial('encabezado');
?>
<section class="portada">
  <div class="portada__texto">
    <h1 class="portada__titulo">
      ¿Qué puedo cocinar?
      <?php /* El dibujo baila siempre; al pasar el mouse se acerca y al tocarlo cambia por otro (inicio.js) */ ?>
      <button class="portada__dibujo" type="button" title="Tocalo para cambiarlo"
              data-dibujos="<?= e(json_encode($dibujos, JSON_UNESCAPED_SLASHES)) ?>" data-carpeta="<?= e(url('img/ingredientes/')) ?>">
        <img src="<?= e(url('img/ingredientes/' . $dibujoInicial . '.svg')) ?>" alt="" width="84" height="84">
        <span class="solo-lectores">Cambiar el dibujo</span>
      </button>
    </h1>
    <p class="portada__bajada"><strong>Alacena es la puerta de tu heladera:</strong> anotá lo que tenés en la heladera y la alacena, y te decimos qué podés cocinar hoy. Sin ir al súper, sin tirar comida.</p>
    <p class="acciones">
      <?php if (Sesion::estaLogueado()) : ?>
        <a class="boton" href="<?= e(url('app/index.php')) ?>">Ir a mi cocina</a>
      <?php else : ?>
        <a class="boton" href="<?= e(url('registro.php')) ?>">Crear cuenta gratis</a>
        <a class="boton boton--secundario" href="<?= e(url('login.php')) ?>">Ya tengo cuenta</a>
      <?php endif; ?>
    </p>
    <p class="portada__nota">→ y usá primero lo que vence</p>
  </div>

  <?php /* Vitrina: los dibujos se desplazan solos dentro del recuadro (la lista va dos veces para que el giro no corte) */ ?>
  <div class="vitrina" aria-hidden="true">
    <div class="vitrina__cinta">
      <?php for ($vuelta = 0; $vuelta < 2; $vuelta++) : ?>
        <ul class="vitrina__tira">
          <?php foreach ($vitrina as [$dibujo, $desorden]) : ?>
            <li class="vitrina__dibujo" style="<?= $desorden ?>">
              <img src="<?= e(url('img/ingredientes/' . $dibujo . '.svg')) ?>" alt="" width="84" height="84" loading="lazy">
              <?php if (in_array($dibujo, $conYa, true)) : ?><i class="ya">¡ya!</i><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section aria-labelledby="como-funciona">
  <h2 id="como-funciona" class="titulo-mano">Cómo funciona</h2>
  <ol class="pasos-portada">
    <li class="postit postit--amarillo">
      <span class="postit__chinche" aria-hidden="true"></span>
      <h3>1. Cargá tu despensa</h3>
      <p>Elegí los ingredientes que tenés, cuánto te queda y cuándo vencen.</p>
    </li>
    <li class="postit postit--rosa">
      <span class="postit__chinche" aria-hidden="true"></span>
      <h3>2. Mirá qué cocinar</h3>
      <p>Te mostramos las recetas para las que ya tenés todo, o casi todo.</p>
    </li>
    <li class="postit postit--celeste">
      <span class="postit__chinche" aria-hidden="true"></span>
      <h3>3. Aprovechá lo que vence</h3>
      <p>Te avisamos qué conviene usar primero para no tirar comida.</p>
    </li>
  </ol>
</section>

<?php /* ¿Por qué Alacena?: con stickers a los costados, que se mecen como en Ingresar */ ?>
<div class="con-stickers con-stickers--porque">
<?php
$stickers = ['mandarina', 'chocolate', 'ricota', 'langostinos', 'espinaca', 'merluza'];
$zona = 'porque';
require parcial('stickers');
?>
<section class="tarjeta portada__porque">
  <h2>¿Por qué Alacena?</h2>
  <p>En Argentina se pierden o desperdician alrededor de 16 millones de toneladas de alimentos por año, y buena parte se tira en casa porque se vence o porque nos olvidamos de que estaba. Alacena es un proyecto de estudiantes de la UTN Facultad Regional Haedo para ayudar a decidir qué cocinar con lo que ya hay.</p>
  <p><a href="<?= e(url('contacto.php')) ?>">Escribinos</a> si tenés sugerencias.</p>
</section>
</div>
<script src="<?= e(recurso('js/inicio.js')) ?>" defer></script>
<?php require parcial('pie'); ?>
