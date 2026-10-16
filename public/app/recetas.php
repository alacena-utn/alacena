<?php

/**
 * Catálogo de recetas con buscador (contenido principal para el usuario general).
 * Busca por título, descripción o ingrediente, y filtra por dificultad, etiqueta
 * y "solo lo que puedo hacer con mi despensa".
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\RecetaRepositorio;
use Alacena\Sesion;

Sesion::requiereLogin();

$repo = new RecetaRepositorio(Db::conexion());
$filtros = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'dificultad' => (int) ($_GET['dificultad'] ?? 0),
    'etiqueta' => (int) ($_GET['etiqueta'] ?? 0),
    'puedo' => isset($_GET['puedo']),
];
$recetas = $repo->buscar($filtros, Sesion::idUsuario());
$etiquetas = $repo->etiquetas();
$hayFiltros = $filtros['q'] !== '' || $filtros['dificultad'] || $filtros['etiqueta'] || $filtros['puedo'];

$titulo = 'Recetas';
require parcial('encabezado');
?>
<h1>Recetas</h1>

<form class="tarjeta buscador" method="get" role="search">
  <div class="campo">
    <label for="q">Buscar receta o ingrediente</label>
    <input id="q" name="q" type="search" placeholder="Ej.: tarta, pollo, zapallo" value="<?= e($filtros['q']) ?>">
  </div>
  <div class="campo">
    <label for="dificultad">Dificultad</label>
    <select id="dificultad" name="dificultad">
      <option value="0">Todas</option>
      <?php foreach (RecetaRepositorio::DIFICULTADES as $valor => $texto) : ?>
        <option value="<?= $valor ?>"<?= $filtros['dificultad'] === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="etiqueta">Tipo</label>
    <select id="etiqueta" name="etiqueta">
      <option value="0">Todas</option>
      <?php foreach ($etiquetas as $etiqueta) : ?>
        <option value="<?= e($etiqueta['id']) ?>"<?= $filtros['etiqueta'] === (int) $etiqueta['id'] ? ' selected' : '' ?>><?= e(str_replace('_', ' ', $etiqueta['nombre'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo casillas">
    <label><input type="checkbox" name="puedo" value="1"<?= $filtros['puedo'] ? ' checked' : '' ?>> Solo las que puedo hacer ya</label>
  </div>
  <button class="boton" type="submit">Buscar</button>
  <?php if ($hayFiltros) : ?>
    <a class="boton boton--secundario" href="<?= e(url('app/recetas.php')) ?>">Limpiar</a>
  <?php endif; ?>
</form>

<p class="texto-suave" role="status"><?= count($recetas) ?> receta<?= count($recetas) === 1 ? '' : 's' ?><?= $hayFiltros ? ' encontradas' : '' ?></p>

<?php if ($recetas === []) : ?>
  <p class="vacio">No encontramos recetas con esos filtros. Probá con otra palabra o sacá algún filtro.</p>
<?php else : ?>
  <div class="grilla">
    <?php foreach ($recetas as $receta) : ?>
      <?php require parcial('tarjeta-receta'); ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require parcial('pie'); ?>