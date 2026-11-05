<?php
/**
 * Dibujos sueltos: stickers de ingredientes elegidos al azar para llenar los espacios vacíos
 * (Contacto, Recetas, Mi cuenta, Mis preferencias). Cambian cada vez que se carga la página.
 * Son decorativos: los lectores de pantalla no los leen. En pantallas angostas no se muestran.
 *
 * Uso: se define $sueltos (un lugar por dibujo) y se incluye el parcial dentro de un contenedor
 * con position: relative (los lugares son relativos a ese contenedor).
 *   $sueltos = [[izquierda %, arriba %, tamaño px, giro grados], ...];
 *   $sueltosClase = 'sueltos--esquinas';   // opcional, una clase más para el contenedor
 *   require parcial('dibujos-sueltos');
 */

/** @var list<array{0: float|int, 1: float|int, 2: int, 3: int}> $sueltos */
// Los dibujos se mezclan una sola vez por página y se van usando: así no se repiten entre una zona y otra
if (!isset($GLOBALS['dibujosSueltosSinUsar'])) {
    $GLOBALS['dibujosSueltosSinUsar'] = array_values(array_filter(
        array_map(fn (string $archivo) => basename($archivo, '.svg'), glob(dirname(__DIR__, 2) . '/public/img/ingredientes/*.svg') ?: []),
        fn (string $nombre) => $nombre !== 'frasco'
    ));
    shuffle($GLOBALS['dibujosSueltosSinUsar']);
}
$dibujosSueltos = array_splice($GLOBALS['dibujosSueltosSinUsar'], 0, count($sueltos));
?>
<div class="sueltos<?= isset($sueltosClase) ? ' ' . e($sueltosClase) : '' ?>" aria-hidden="true">
  <?php foreach ($sueltos as $n => [$x, $y, $tamano, $giro]) : ?>
    <?php if (!isset($dibujosSueltos[$n])) {
        break;
    } ?>
    <img class="sueltos__dibujo" src="<?= e(url('img/ingredientes/' . $dibujosSueltos[$n] . '.svg')) ?>" alt=""
         width="<?= (int) $tamano ?>" height="<?= (int) $tamano ?>" loading="lazy"
         style="--x: <?= (float) $x ?>%; --y: <?= (float) $y ?>%; --t: <?= (int) $tamano ?>px; --r: <?= (int) $giro ?>deg; --d: <?= ($n % 5) * 0.35 ?>s">
  <?php endforeach; ?>
</div>
<?php unset($sueltos, $sueltosClase, $dibujosSueltos);
