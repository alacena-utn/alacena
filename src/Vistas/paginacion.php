<?php
/**
 * Barra de páginas de un listado. Antes de incluirla, la página define:
 *   $paginacion  un Dominio\Paginacion
 *   $enlace      fn (int $pagina): string → la URL de esa página (conservando la búsqueda y los filtros)
 * Si hay una sola página no muestra nada. Son enlaces comunes: funcionan sin JavaScript.
 */

if ($paginacion->paginas <= 1) {
    return;
}
?>
<nav class="paginacion" aria-label="Páginas">
  <p class="paginacion__texto texto-suave"><?= e($paginacion->texto()) ?></p>
  <ul>
    <?php if ($paginacion->pagina > 1) : ?>
      <li><a class="paginacion__flecha" href="<?= e($enlace($paginacion->pagina - 1)) ?>" rel="prev">← Anterior</a></li>
    <?php endif; ?>
    <?php foreach ($paginacion->numeros() as $numero) : ?>
      <?php if ($numero === null) : ?>
        <li class="paginacion__salto" aria-hidden="true">…</li>
      <?php elseif ($numero === $paginacion->pagina) : ?>
        <li><span class="paginacion__numero" aria-current="page"><span class="solo-lectores">Página </span><?= $numero ?></span></li>
      <?php else : ?>
        <li><a class="paginacion__numero" href="<?= e($enlace($numero)) ?>"><span class="solo-lectores">Página </span><?= $numero ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($paginacion->pagina < $paginacion->paginas) : ?>
      <li><a class="paginacion__flecha" href="<?= e($enlace($paginacion->pagina + 1)) ?>" rel="next">Siguiente →</a></li>
    <?php endif; ?>
  </ul>
</nav>
