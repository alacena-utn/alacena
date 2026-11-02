<?php
/**
 * Stickers de ingredientes para que la puerta no quede vacía (Ingresar, Crear cuenta, error, inicio).
 * Son decorativos: los lectores de pantalla no los leen. En pantallas angostas no se muestran.
 * Se mecen solos, cada uno a su tiempo, y al pasar el mouse se agrandan y se menean.
 *
 * Uso: se definen $stickers (los dibujos) y $zona, y se incluye el parcial.
 *   $zona = 'costados' → hasta 10, a los dos lados de una hoja angosta (de arriba hasta abajo de todo).
 *                        Va dentro de un contenedor "con-stickers", antes de la hoja.
 *   $zona = 'abajo'    → hasta 5, desparramados debajo de la hoja (no en fila: cada uno a otra altura).
 *                        Va dentro del mismo contenedor, después de la hoja.
 *   $zona = 'porque'   → hasta 6, a los costados de "¿Por qué Alacena?" en el inicio.
 */

// Lugar de cada sticker: [izquierda en %, arriba en %, giro en grados, retraso del vaivén en s]
$lugaresPorZona = [
    'costados' => [
        [2, 3, -12, 0], [86, 5, 10, .5], [12, 21, 9, .7], [93, 24, -9, 1.7], [2, 41, -6, 1.4],
        [85, 44, 7, .9], [11, 61, 11, .3], [92, 64, -11, .2], [3, 81, -9, 1.1], [86, 84, 8, 1.3],
    ],
    'abajo' => [[27, 18, -8, .4], [38, 58, 10, 1.2], [49, 8, -5, .8], [60, 54, 12, 0], [70, 20, -10, 1.5]],
    'porque' => [[1, 0, -10, .3], [91, 4, 9, .6], [7, 38, 8, 1], [95, 40, -8, 1.3], [1, 74, -6, 1.6], [90, 76, 7, 0]],
];
$zona = $zona ?? 'costados';
$lugares = $lugaresPorZona[$zona];
// Si hay menos dibujos que lugares, se reparten salteando lugares (para que ocupen todo el alto)
$paso = max(1, intdiv(count($lugares), max(1, count($stickers))));
?>
<div class="stickers-deco stickers-deco--<?= e($zona) ?>" aria-hidden="true">
  <?php foreach (array_slice($stickers, 0, count($lugares)) as $n => $dibujo) : ?>
    <?php [$x, $y, $giro, $retraso] = $lugares[min($n * $paso, count($lugares) - 1)]; ?>
    <img class="stickers-deco__dibujo" src="<?= e(url('img/ingredientes/' . $dibujo . '.svg')) ?>" alt="" width="110" height="110"
         loading="lazy" style="--x: <?= $x ?>%; --y: <?= $y ?>%; --r: <?= $giro ?>deg; --d: <?= $retraso ?>s">
  <?php endforeach; ?>
</div>
