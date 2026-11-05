<?php

/**
 * ABM de recetas · alta (receta-editar.php) y modificación (receta-editar.php?id=5).
 * Una receta tiene datos generales, una lista de ingredientes, pasos y etiquetas.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Cooki\Db;
use Cooki\Dominio\Pasos;
use Cooki\Dominio\Puntaje;
use Cooki\Repositorios\IngredienteRepositorio;
use Cooki\Repositorios\RecetaRepositorio;
use Cooki\Sesion;

Sesion::requiereAdmin();

$db = Db::conexion();
$repo = new RecetaRepositorio($db);
$catalogo = (new IngredienteRepositorio($db))->todos();
$idsValidos = array_column($catalogo, 'id');
$etiquetas = $repo->etiquetas();
$errores = [];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$receta = [
    'titulo' => '', 'descripcion' => '', 'porciones_base' => 4, 'minutos_prep' => 15,
    'minutos_coccion' => 0, 'dificultad' => 1,
];
$ingredientes = [['id' => 0, 'cantidad' => null, 'unidad' => '', 'peso' => 2, 'opcional' => false]];
$pasos = [];
$etiquetasElegidas = [];

if ($id > 0 && !esPost()) {
    $guardada = $repo->obtener($id);
    if ($guardada === null) {
        redirigir('error.php?motivo=no-encontrado');
    }
    $receta = $guardada;
    $ingredientes = array_map(fn ($i) => [
        'id' => (int) $i['ingrediente_id'],
        'cantidad' => $i['cantidad'] === null ? null : (float) $i['cantidad'],
        'unidad' => (string) $i['unidad'],
        'peso' => (int) $i['peso'],
        'opcional' => (bool) $i['es_opcional'],
        'nota' => (string) ($i['nota'] ?? ''),
    ], $guardada['ingredientes']);
    $pasos = Pasos::desdeTexto(Pasos::aTexto($guardada['pasos']));
    $etiquetasElegidas = array_map('intval', array_column($guardada['etiquetas'], 'id'));
}

if (esPost()) {
    Sesion::verificarCsrf();
    $receta = [
        'titulo' => campo('titulo'),
        'descripcion' => campo('descripcion'),
        'porciones_base' => (int) ($_POST['porciones_base'] ?? 0),
        'minutos_prep' => (int) ($_POST['minutos_prep'] ?? -1),
        'minutos_coccion' => (int) ($_POST['minutos_coccion'] ?? -1),
        'dificultad' => (int) ($_POST['dificultad'] ?? 0),
    ];
    // Las filas de ingredientes llegan como ingredientes[0][id], ingredientes[0][cantidad]...
    $ingredientes = [];
    foreach ((array) ($_POST['ingredientes'] ?? []) as $fila) {
        $idIngrediente = (int) ($fila['id'] ?? 0);
        if ($idIngrediente === 0) {
            continue;   // fila vacía
        }
        $cantidad = str_replace(',', '.', trim((string) ($fila['cantidad'] ?? '')));
        $ingredientes[] = [
            'id' => $idIngrediente,
            'cantidad' => $cantidad === '' ? null : (float) $cantidad,
            'unidad' => mb_substr(trim((string) ($fila['unidad'] ?? '')), 0, 10),
            // Peso en la receta (para el puntaje): 3 principal, 2 secundario, 1 accesorio
            'peso' => isset(Puntaje::PESOS[(int) ($fila['peso'] ?? 2)]) ? (int) $fila['peso'] : 2,
            'opcional' => !empty($fila['opcional']),
            'nota' => mb_substr(trim((string) ($fila['nota'] ?? '')), 0, 100),
        ];
    }
    // Un paso por renglón; "[⏱ 15 min]" al final le pone temporizador (lo usa el modo cocina)
    $pasos = Pasos::desdeTexto(campo('pasos'));
    $etiquetasElegidas = array_values(array_intersect(
        array_map('intval', (array) ($_POST['etiquetas'] ?? [])),
        array_map('intval', array_column($etiquetas, 'id'))
    ));

    // Validaciones
    if (mb_strlen($receta['titulo']) < 3 || mb_strlen($receta['titulo']) > 150) {
        $errores['titulo'] = 'El título debe tener entre 3 y 150 caracteres.';
    }
    if (mb_strlen($receta['descripcion']) > 500) {
        $errores['descripcion'] = 'La descripción puede tener hasta 500 caracteres.';
    }
    if ($receta['porciones_base'] < 1 || $receta['porciones_base'] > 20) {
        $errores['porciones_base'] = 'Entre 1 y 20 porciones.';
    }
    if ($receta['minutos_prep'] < 1 || $receta['minutos_prep'] > 600 || $receta['minutos_coccion'] < 0 || $receta['minutos_coccion'] > 600) {
        $errores['minutos'] = 'Los minutos deben estar entre 0 y 600 (la preparación, al menos 1).';
    }
    if (!isset(RecetaRepositorio::DIFICULTADES[$receta['dificultad']])) {
        $errores['dificultad'] = 'Elegí una dificultad.';
    }
    $ids = array_column($ingredientes, 'id');
    if ($ingredientes === []) {
        $errores['ingredientes'] = 'Agregá al menos un ingrediente.';
    } elseif (array_diff($ids, $idsValidos) !== []) {
        $errores['ingredientes'] = 'Hay un ingrediente que no existe en el catálogo.';
    } elseif (count($ids) !== count(array_unique($ids))) {
        $errores['ingredientes'] = 'Hay un ingrediente repetido.';
    } else {
        foreach ($ingredientes as $ing) {
            if ($ing['cantidad'] !== null && ($ing['cantidad'] <= 0 || $ing['cantidad'] > 100000)) {
                $errores['ingredientes'] = 'Las cantidades tienen que ser mayores que cero.';
            } elseif ($ing['cantidad'] === null && !in_array($ing['unidad'], ['', 'pizca'], true)) {
                // Sin cantidad, el ingrediente va "a gusto": la unidad sola (por ejemplo "g") no dice cuánto
                $errores['ingredientes'] = 'Hay un ingrediente con unidad pero sin cantidad. Si va a gusto, dejá vacías la cantidad y la unidad.';
            }
        }
    }
    if ($pasos === []) {
        $errores['pasos'] = 'Escribí al menos un paso.';
    }

    if ($errores === []) {
        $repo->guardar($receta, $ingredientes, $pasos, $etiquetasElegidas, Sesion::idUsuario(), $id);
        Sesion::avisar('exito', 'Se guardó “' . $receta['titulo'] . '”.');
        redirigir('admin/recetas.php?q=' . urlencode($receta['titulo']));
    }
    if ($ingredientes === []) {
        $ingredientes = [['id' => 0, 'cantidad' => null, 'unidad' => '', 'peso' => 2, 'opcional' => false]];
    }
}

/** <option> de ingredientes agrupados por categoría, con el elegido marcado. */
$opciones = function (int $elegido) use ($catalogo): string {
    $html = '<option value="0">Elegí…</option>';
    $grupo = null;
    foreach ($catalogo as $ing) {
        if ($ing['categoria'] !== $grupo) {
            $html .= ($grupo !== null ? '</optgroup>' : '') . '<optgroup label="' . e($ing['categoria']) . '">';
            $grupo = $ing['categoria'];
        }
        $html .= '<option value="' . e($ing['id']) . '" data-unidad="' . e($ing['unidad_default']) . '"'
            . ' data-dibujo="' . e(iconoIngrediente($ing['nombre'])) . '"'
            . ((int) $ing['id'] === $elegido ? ' selected' : '') . '>' . e($ing['nombre']) . '</option>';
    }
    return $html . '</optgroup>';
};
$error = fn (string $campo) => isset($errores[$campo]) ? '<p class="error-campo">' . e($errores[$campo]) . '</p>' : '';

$titulo = $id ? 'Editar receta' : 'Nueva receta';
require parcial('encabezado');
?>
<?= enlaceVolver('admin/recetas.php', 'Volver a Recetas', $id ? [['admin/recetas.php', 'Recetas'], ['app/receta.php?id=' . $id, 'Ver cómo se ve']] : [['admin/recetas.php', 'Recetas']]) ?>
<h1><?= e($titulo) ?></h1>
<?php if ($errores !== []) : ?>
  <p class="aviso aviso--error" role="alert">Revisá los campos marcados.</p>
<?php endif; ?>

<form class="formulario formulario--ancho" method="post" id="form-receta">
  <?= Sesion::campoCsrf() ?>
  <input type="hidden" name="id" value="<?= e($id) ?>">

  <section class="tarjeta">
    <h2>Datos generales</h2>
    <div class="campo">
      <label for="titulo">Título</label>
      <input id="titulo" name="titulo" type="text" required maxlength="150" value="<?= e($receta['titulo']) ?>">
      <?= $error('titulo') ?>
    </div>
    <div class="campo">
      <label for="descripcion">Descripción breve</label>
      <textarea id="descripcion" name="descripcion" maxlength="500" rows="2"><?= e($receta['descripcion']) ?></textarea>
      <?= $error('descripcion') ?>
    </div>
    <div class="fila-campos">
      <div class="campo">
        <label for="porciones_base">Porciones</label>
        <input id="porciones_base" name="porciones_base" type="number" min="1" max="20" required value="<?= e($receta['porciones_base']) ?>">
        <?= $error('porciones_base') ?>
      </div>
      <div class="campo">
        <label for="minutos_prep">Minutos de preparación</label>
        <input id="minutos_prep" name="minutos_prep" type="number" min="1" max="600" required value="<?= e($receta['minutos_prep']) ?>">
      </div>
      <div class="campo">
        <label for="minutos_coccion">Minutos de cocción</label>
        <input id="minutos_coccion" name="minutos_coccion" type="number" min="0" max="600" required value="<?= e($receta['minutos_coccion']) ?>">
      </div>
      <div class="campo">
        <label for="dificultad">Dificultad</label>
        <select id="dificultad" name="dificultad">
          <?php foreach (RecetaRepositorio::DIFICULTADES as $valor => $texto) : ?>
            <option value="<?= $valor ?>"<?= (int) $receta['dificultad'] === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?= $error('minutos') ?>
    <fieldset class="campo">
      <legend>Etiquetas</legend>
      <div class="casillas">
        <?php foreach ($etiquetas as $etiqueta) : ?>
          <label><input type="checkbox" name="etiquetas[]" value="<?= e($etiqueta['id']) ?>"<?= in_array((int) $etiqueta['id'], $etiquetasElegidas, true) ? ' checked' : '' ?>> <?= e(str_replace('_', ' ', $etiqueta['nombre'])) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
  </section>

  <section class="tarjeta">
    <h2>Ingredientes</h2>
    <p class="texto-suave"><small>Si un ingrediente no lleva cantidad (la sal, el perejil para servir), dejá vacías la cantidad y la unidad: en la receta dice «a gusto».</small></p>
    <?= $error('ingredientes') ?>
    <div id="filas-ingredientes" data-frasco="<?= e(url('img/ingredientes/frasco.svg')) ?>">
      <?php foreach ($ingredientes as $i => $ing) : ?>
        <div class="fila-ingrediente">
          <?php $nombreIng = (string) (array_column($catalogo, 'nombre', 'id')[(int) $ing['id']] ?? ''); ?>
          <img class="fila-ingrediente__dibujo" src="<?= e($nombreIng !== '' ? iconoIngrediente($nombreIng) : url('img/ingredientes/frasco.svg')) ?>" alt="" width="44" height="44">
          <div class="campo">
            <label for="ing-<?= $i ?>-id">Ingrediente</label>
            <select id="ing-<?= $i ?>-id" name="ingredientes[<?= $i ?>][id]" data-campo="id"><?= $opciones((int) $ing['id']) ?></select>
          </div>
          <div class="campo">
            <label for="ing-<?= $i ?>-cantidad">Cantidad</label>
            <input id="ing-<?= $i ?>-cantidad" name="ingredientes[<?= $i ?>][cantidad]" data-campo="cantidad" type="text" inputmode="decimal" placeholder="a gusto" value="<?= e($ing['cantidad'] === null ? '' : rtrim(rtrim(number_format($ing['cantidad'], 2, '.', ''), '0'), '.')) ?>">
          </div>
          <div class="campo">
            <label for="ing-<?= $i ?>-unidad">Unidad</label>
            <input id="ing-<?= $i ?>-unidad" name="ingredientes[<?= $i ?>][unidad]" data-campo="unidad" type="text" maxlength="10" list="unidades" value="<?= e($ing['unidad']) ?>">
          </div>
          <div class="campo">
            <label for="ing-<?= $i ?>-peso">Importancia</label>
            <select id="ing-<?= $i ?>-peso" name="ingredientes[<?= $i ?>][peso]" data-campo="peso">
              <?php foreach (Puntaje::PESOS as $valor => $texto) : ?>
                <option value="<?= $valor ?>"<?= (int) ($ing['peso'] ?? 2) === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <input type="hidden" name="ingredientes[<?= $i ?>][nota]" data-campo="nota" value="<?= e($ing['nota'] ?? '') ?>">
          <label class="fila-ingrediente__opcional"><input type="checkbox" name="ingredientes[<?= $i ?>][opcional]" data-campo="opcional" value="1"<?= $ing['opcional'] ? ' checked' : '' ?>> Opcional</label>
          <button class="boton boton--peligro boton--chico" type="button" data-quitar>Quitar</button>
        </div>
      <?php endforeach; ?>
    </div>
    <datalist id="unidades">
      <option value="g"><option value="kg"><option value="ml"><option value="l"><option value="u">
      <option value="cda"><option value="cdita"><option value="taza"><option value="pizca">
    </datalist>
    <button class="boton boton--secundario" type="button" id="agregar-ingrediente">+ Agregar ingrediente</button>
    <p class="texto-suave"><small>¿Falta un ingrediente en la lista? Cargalo primero en <a href="<?= e(url('admin/ingredientes.php')) ?>">Ingredientes</a>.</small></p>
  </section>

  <section class="tarjeta">
    <h2>Pasos</h2>
    <div class="campo">
      <label for="pasos">Un paso por renglón</label>
      <textarea id="pasos" name="pasos" rows="8" required aria-describedby="pasos-ayuda"><?= e(implode("\n", array_map(
          fn ($p) => $p['texto'] . ($p['segundos'] ? ' [⏱ ' . intdiv($p['segundos'], 60) . ' min]' : ''),
          $pasos
      ))) ?></textarea>
      <p class="texto-suave" id="pasos-ayuda"><small>Para que un paso tenga temporizador, terminalo con <code>[⏱ 15 min]</code>. Para que la cantidad cambie con las porciones, escribí el ingrediente así: <code>{ing:papa}</code>.</small></p>
      <?= $error('pasos') ?>
    </div>
  </section>

  <div class="acciones">
    <button class="boton" type="submit">Guardar receta</button>
    <a class="boton boton--secundario" href="<?= e(url('admin/recetas.php')) ?>">Cancelar</a>
  </div>
</form>
<script src="<?= e(recurso('js/receta-form.js')) ?>" defer></script>
<?php require parcial('pie'); ?>
