<?php

/**
 * ABM de recetas · listado con buscador y baja. El alta y la modificación están en receta-editar.php.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\RecetaRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();

$repo = new RecetaRepositorio(Db::conexion());

if (esPost() && campo('accion') === 'borrar') {
    Sesion::verificarCsrf();
    $receta = $repo->obtener((int) ($_POST['id'] ?? 0));
    if ($receta === null) {
        Sesion::avisar('error', 'Esa receta ya no existe.');
    } else {
        $repo->borrar((int) $receta['id']);
        Sesion::avisar('exito', 'Se borró “' . $receta['titulo'] . '”.');
    }
    redirigir('admin/recetas.php');
}

$busqueda = trim((string) ($_GET['q'] ?? ''));
$recetas = $repo->buscar(['q' => $busqueda]);

$titulo = 'Recetas · Administración';
require parcial('encabezado');
?>
<div class="encabezado-pagina">
  <h1>Recetas</h1>
  <a class="boton" href="<?= e(url('admin/receta-editar.php')) ?>">+ Nueva receta</a>
</div>

<form class="buscador" method="get" role="search">
  <div class="campo">
    <label for="q">Buscar por título o ingrediente</label>
    <input id="q" name="q" type="search" value="<?= e($busqueda) ?>">
  </div>
  <button class="boton boton--secundario" type="submit">Buscar</button>
</form>

<?php if ($recetas === []) : ?>
  <p class="vacio">No hay recetas<?= $busqueda !== '' ? ' que coincidan con “' . e($busqueda) . '”' : '' ?>.</p>
<?php else : ?>
  <div class="tabla-contenedor">
    <table>
      <thead>
        <tr><th>Receta</th><th>Dificultad</th><th>Minutos</th><th>Ingredientes</th><th>Etiquetas</th><th><span class="solo-lectores">Acciones</span></th></tr>
      </thead>
      <tbody>
      <?php foreach ($recetas as $r) : ?>
        <tr>
          <td><a href="<?= e(url('app/receta.php?id=' . $r['id'])) ?>"><?= e($r['titulo']) ?></a></td>
          <td><?= e(RecetaRepositorio::DIFICULTADES[(int) $r['dificultad']]) ?></td>
          <td><?= e($r['minutos']) ?></td>
          <td><?= e($r['total']) ?></td>
          <td><?= e(str_replace('_', ' ', (string) $r['etiquetas'])) ?></td>
          <td>
            <div class="acciones">
              <a class="boton boton--secundario boton--chico" href="<?= e(url('admin/receta-editar.php?id=' . $r['id'])) ?>">Editar</a>
              <form method="post" data-confirmar="¿Borrar la receta “<?= e($r['titulo']) ?>”? No se puede deshacer.">
                <?= Sesion::campoCsrf() ?>
                <input type="hidden" name="accion" value="borrar">
                <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <button class="boton boton--peligro boton--chico" type="submit">Borrar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require parcial('pie'); ?>