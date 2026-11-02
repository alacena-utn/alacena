<?php

/**
 * ABM de ingredientes (solo administradores).
 *   ingredientes.php            → listado con buscador y filtro por categoría
 *   ingredientes.php?nuevo      → formulario de alta
 *   ingredientes.php?editar=ID  → formulario de modificación
 *   ingredientes.php?propios    → solo los que agregaron los usuarios desde su despensa
 *   POST accion=guardar|borrar|general (general = pasar un ingrediente propio a la lista general)
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\IngredienteRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();

$repo = new IngredienteRepositorio(Db::conexion());
$categorias = $repo->categorias();
$unidades = ['g' => 'gramos (g)', 'ml' => 'mililitros (ml)', 'u' => 'unidades (u)'];
$errores = [];
$form = null;

if (esPost()) {
    Sesion::verificarCsrf();
    $id = (int) ($_POST['id'] ?? 0);

    if (campo('accion') === 'borrar') {
        $ingrediente = $repo->obtener($id);
        if ($ingrediente === null) {
            Sesion::avisar('error', 'Ese ingrediente ya no existe.');
        } elseif ($repo->borrar($id)) {
            Sesion::avisar('exito', 'Se borró “' . $ingrediente['nombre'] . '”.');
        } else {
            Sesion::avisar('error', 'No se puede borrar “' . $ingrediente['nombre'] . '”: lo usa alguna receta o despensa.');
        }
        redirigir('admin/ingredientes.php');
    }

    if (campo('accion') === 'general') {
        $ingrediente = $repo->obtener($id);
        if ($ingrediente !== null && $repo->pasarALaListaGeneral($id)) {
            Sesion::avisar('exito', '“' . $ingrediente['nombre'] . '” ya está en la lista general: lo ven todos y se puede usar en recetas.');
        } else {
            Sesion::avisar('error', 'No se pudo: ya hay uno con ese nombre en la lista general (o ya estaba).');
        }
        redirigir('admin/ingredientes.php?propios');
    }

    $vida = trim((string) ($_POST['vida_util_dias'] ?? ''));
    $form = [
        'id' => $id,
        'nombre' => campo('nombre'),
        'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
        'unidad_default' => campo('unidad_default'),
        'es_basico' => isset($_POST['es_basico']),
        'perecedero' => isset($_POST['perecedero']),
        'vida_util_dias' => $vida === '' ? null : (int) $vida,
    ];
    if (mb_strlen($form['nombre']) < 2 || mb_strlen($form['nombre']) > 80) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 80 caracteres.';
    } elseif ($repo->existeNombre($form['nombre'], $id, (int) ($repo->obtener($id)['usuario_id'] ?? 0))) {
        $errores['nombre'] = 'Ya existe un ingrediente con ese nombre.';
    }
    if (!in_array($form['categoria_id'], array_map('intval', array_column($categorias, 'id')), true)) {
        $errores['categoria_id'] = 'Elegí una categoría.';
    }
    if (!isset($unidades[$form['unidad_default']])) {
        $errores['unidad_default'] = 'Elegí una unidad.';
    }
    if ($form['vida_util_dias'] !== null && ($form['vida_util_dias'] < 1 || $form['vida_util_dias'] > 3650)) {
        $errores['vida_util_dias'] = 'Entre 1 y 3650 días, o vacío si no aplica.';
    }
    if ($id > 0 && $repo->obtener($id) === null) {
        redirigir('error.php?motivo=no-encontrado');
    }

    if ($errores === []) {
        $repo->guardar($form, $id);
        Sesion::avisar('exito', 'Se guardó “' . $form['nombre'] . '”.');
        redirigir('admin/ingredientes.php?q=' . urlencode($form['nombre']));
    }
} elseif (isset($_GET['nuevo'])) {
    $form = ['id' => 0, 'nombre' => '', 'categoria_id' => 0, 'unidad_default' => 'g',
        'es_basico' => false, 'perecedero' => true, 'vida_util_dias' => null];
} elseif (isset($_GET['editar'])) {
    $form = $repo->obtener((int) $_GET['editar']);
    if ($form === null) {
        redirigir('error.php?motivo=no-encontrado');
    }
}

$busqueda = trim((string) ($_GET['q'] ?? ''));
$categoria = (int) ($_GET['categoria'] ?? 0);
$soloPropios = isset($_GET['propios']);
$ingredientes = $form === null ? $repo->listar($busqueda, $categoria, $soloPropios) : [];
$error = fn (string $campo) => isset($errores[$campo]) ? '<p class="error-campo">' . e($errores[$campo]) . '</p>' : '';

$titulo = 'Ingredientes · Administración';
// Dibujos disponibles (para la vista previa del formulario)
$dibujos = array_map(fn (string $a) => basename($a, '.svg'), glob(dirname(__DIR__) . '/img/ingredientes/*.svg') ?: []);

require parcial('encabezado');
?>
<?php if ($form !== null) : ?>
  <?= enlaceVolver('admin/ingredientes.php', 'Volver a ingredientes') ?>
  <section class="tarjeta tarjeta--angosta">
    <h1><?= $form['id'] ? 'Editar ingrediente' : 'Nuevo ingrediente' ?></h1>
    <form class="formulario" method="post">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= e($form['id']) ?>">
      <div class="campo-con-dibujo">
        <div class="campo">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" type="text" required maxlength="80" value="<?= e($form['nombre']) ?>">
          <?= $error('nombre') ?>
        </div>
        <?php /* Vista previa del dibujo: admin-ingrediente.js la actualiza mientras se escribe el nombre */ ?>
        <?php $tieneDibujo = !str_ends_with(iconoIngrediente((string) $form['nombre'], ($form['usuario_id'] ?? null) !== null), '/frasco.svg'); ?>
        <figure class="vista-dibujo" data-carpeta="<?= e(url('img/ingredientes/')) ?>"
                data-dibujos="<?= e(json_encode($dibujos, JSON_UNESCAPED_SLASHES)) ?>">
          <img src="<?= e(iconoIngrediente((string) $form['nombre'], ($form['usuario_id'] ?? null) !== null)) ?>" alt="" width="72" height="72">
          <figcaption><?= $tieneDibujo || $form['nombre'] === '' ? 'Dibujo' : 'Sin dibujo: frasco' ?></figcaption>
        </figure>
      </div>
      <div class="fila-campos">
        <div class="campo">
          <label for="categoria_id">Categoría</label>
          <select id="categoria_id" name="categoria_id" required>
            <option value="">Elegí…</option>
            <?php foreach ($categorias as $cat) : ?>
              <option value="<?= e($cat['id']) ?>"<?= (int) $form['categoria_id'] === (int) $cat['id'] ? ' selected' : '' ?>><?= e($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <?= $error('categoria_id') ?>
        </div>
        <div class="campo">
          <label for="unidad_default">Unidad habitual</label>
          <select id="unidad_default" name="unidad_default">
            <?php foreach ($unidades as $valor => $texto) : ?>
              <option value="<?= e($valor) ?>"<?= $form['unidad_default'] === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
          </select>
          <?= $error('unidad_default') ?>
        </div>
        <div class="campo">
          <label for="vida_util_dias">Dura (días)</label>
          <input id="vida_util_dias" name="vida_util_dias" type="number" min="1" max="3650" value="<?= e($form['vida_util_dias']) ?>">
          <?= $error('vida_util_dias') ?>
        </div>
      </div>
      <div class="casillas">
        <label><input type="checkbox" name="perecedero" value="1"<?= $form['perecedero'] ? ' checked' : '' ?>> Se echa a perder</label>
        <label><input type="checkbox" name="es_basico" value="1"<?= $form['es_basico'] ? ' checked' : '' ?>> Es un básico (sal, aceite…): se considera que siempre hay</label>
      </div>
      <div class="acciones">
        <button class="boton" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="<?= e(url('admin/ingredientes.php')) ?>">Cancelar</a>
      </div>
    </form>
  </section>
<?php else : ?>
  <?= enlaceVolver('admin/index.php', 'Volver al panel') ?>
  <div class="encabezado-pagina">
    <h1>Ingredientes</h1>
    <a class="boton" href="<?= e(url('admin/ingredientes.php?nuevo')) ?>">+ Nuevo ingrediente</a>
  </div>
  <form class="buscador" method="get" role="search">
    <div class="campo">
      <label for="q">Buscar (también por sinónimo)</label>
      <input id="q" name="q" type="search" placeholder="Ej.: zapallo, pimiento" value="<?= e($busqueda) ?>">
    </div>
    <div class="campo">
      <label for="categoria">Categoría</label>
      <select id="categoria" name="categoria">
        <option value="0">Todas</option>
        <?php foreach ($categorias as $cat) : ?>
          <option value="<?= e($cat['id']) ?>"<?= $categoria === (int) $cat['id'] ? ' selected' : '' ?>><?= e($cat['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="casillas">
      <label><input type="checkbox" name="propios"<?= $soloPropios ? ' checked' : '' ?>> Solo los que agregaron los usuarios</label>
    </div>
    <button class="boton boton--secundario" type="submit">Buscar</button>
  </form>
  <?php if ($ingredientes === []) : ?>
    <p class="vacio">No hay ingredientes con esos filtros.</p>
  <?php else : ?>
    <div class="tabla-contenedor">
      <table>
        <thead><tr><th>Nombre</th><th>Categoría</th><th>Unidad</th><th>Se usa en</th><th><span class="solo-lectores">Acciones</span></th></tr></thead>
        <tbody>
        <?php foreach ($ingredientes as $ing) : ?>
          <tr>
            <td class="con-dibujo">
              <img class="icono-chico" src="<?= e(iconoIngrediente($ing['nombre'], $ing['usuario_id'] !== null)) ?>" alt="" width="36" height="36">
              <span><?= e($ing['nombre']) ?><?= $ing['es_basico'] ? ' <span class="etiqueta">básico</span>' : '' ?>
                <?php if ($ing['usuario_id'] !== null) : ?>
                  <span class="etiqueta etiqueta--alerta" title="Lo agregó desde su despensa: solo lo ve esa persona">
                    propio de <?= e(explode(' ', (string) $ing['duenio'])[0]) ?>
                  </span>
                <?php endif; ?>
              </span>
            </td>
            <td><?= e($ing['categoria']) ?></td>
            <td><?= e($ing['unidad_default']) ?></td>
            <td><?= e($ing['en_recetas']) ?> recetas · <?= e($ing['en_despensas']) ?> despensas</td>
            <td>
              <div class="acciones">
                <a class="boton boton--secundario boton--chico" href="<?= e(url('admin/ingredientes.php?editar=' . $ing['id'])) ?>">Editar</a>
                <?php if ($ing['usuario_id'] !== null) : ?>
                  <form method="post">
                    <?= Sesion::campoCsrf() ?>
                    <input type="hidden" name="accion" value="general">
                    <input type="hidden" name="id" value="<?= e($ing['id']) ?>">
                    <button class="boton boton--chico" type="submit" title="Lo ven todos y se puede usar en recetas">A la lista general</button>
                  </form>
                <?php endif; ?>
                <form method="post" data-confirmar="¿Borrar “<?= e($ing['nombre']) ?>”?" data-confirmar-dibujo="<?= e(iconoIngrediente($ing['nombre'], $ing['usuario_id'] !== null)) ?>">
                  <?= Sesion::campoCsrf() ?>
                  <input type="hidden" name="accion" value="borrar">
                  <input type="hidden" name="id" value="<?= e($ing['id']) ?>">
                  <button class="boton boton--peligro boton--chico" type="submit"<?= ($ing['en_recetas'] + $ing['en_despensas']) > 0 ? ' disabled title="Está en uso"' : '' ?>>Borrar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>
<script src="<?= e(recurso('js/admin-ingrediente.js')) ?>" defer></script>
<?php require parcial('pie'); ?>
