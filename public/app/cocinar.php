<?php

/**
 * Modo cocina: la receta paso a paso, con letra grande, temporizadores y lo que ya hiciste guardado en tu cuenta.
 *   POST accion=iniciar   receta, porciones   → empieza (o sigue) y abre cocinar.php?sesion=N
 *   GET  sesion=N                             → la receta que estás cocinando
 *   POST accion=paso      sesion, paso, hecho → marca o desmarca un paso (cocinar.js lo hace sin recargar)
 *   POST accion=terminar  sesion, descontar[] → termina y descuenta de la despensa lo que quedó tildado
 *   POST accion=abandonar sesion              → deja de cocinar sin descontar nada
 * El progreso vive en la base (sesion_cocina y paso_completado): sobrevive a recargar la página.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Cooki\Db;
use Cooki\Dominio\Porciones;
use Cooki\Repositorios\CocinaRepositorio;
use Cooki\Repositorios\DespensaRepositorio;
use Cooki\Repositorios\RecetaRepositorio;
use Cooki\Sesion;

Sesion::requiereLogin();

$db = Db::conexion();
$cocina = new CocinaRepositorio($db);
$idUsuario = Sesion::idUsuario();

if (esPost()) {
    Sesion::verificarCsrf();
    $accion = campo('accion');
    $sesionId = (int) ($_POST['sesion'] ?? 0);

    if ($accion === 'iniciar') {
        $recetaId = (int) ($_POST['receta'] ?? 0);
        $receta = (new RecetaRepositorio($db))->obtener($recetaId);
        if ($receta === null) {
            redirigir('error.php?motivo=no-encontrado');
        }
        $porciones = max(Porciones::MINIMO, min(Porciones::MAXIMO, (int) ($_POST['porciones'] ?? $receta['porciones_base'])));
        redirigir('app/cocinar.php?sesion=' . $cocina->iniciar($idUsuario, $recetaId, $porciones));
    }

    if ($accion === 'paso') {
        $ok = $cocina->marcarPaso($idUsuario, $sesionId, (int) ($_POST['paso'] ?? 0), ($_POST['hecho'] ?? '') === '1');
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            http_response_code($ok ? 204 : 400);
            exit;
        }
        redirigir('app/cocinar.php?sesion=' . $sesionId . '#paso-' . (int) ($_POST['paso'] ?? 0));
    }

    if ($accion === 'terminar') {
        $descontar = array_map('intval', (array) ($_POST['descontar'] ?? []));
        $descontados = $cocina->terminar($idUsuario, $sesionId, $descontar);
        if ($descontados === null) {
            Sesion::avisar('info', 'Esa receta ya estaba terminada: no se descontó nada de nuevo.');
        } elseif ($descontados === []) {
            Sesion::avisar('exito', '¡Buen provecho! No se descontó nada de tu despensa.');
        } else {
            Sesion::avisar('exito', '¡Buen provecho! Actualizamos tu despensa: '
                . mb_strtolower(implode(', ', $descontados)) . '.');
        }
        redirigir('app/index.php');
    }

    if ($accion === 'abandonar') {
        $sesion = $cocina->obtener($idUsuario, $sesionId);
        $cocina->abandonar($idUsuario, $sesionId);
        redirigir($sesion ? 'app/receta.php?id=' . $sesion['receta_id'] : 'app/index.php');
    }
    redirigir('app/index.php');
}

$sesion = $cocina->obtener($idUsuario, (int) ($_GET['sesion'] ?? 0));
if ($sesion === null) {
    redirigir('error.php?motivo=no-encontrado');
}
if ($sesion['estado'] !== 'en_curso') {
    // Ya terminada o abandonada: se vuelve a la receta, desde donde se puede empezar otra vez
    redirigir('app/receta.php?id=' . $sesion['receta_id']);
}
$receta = (new RecetaRepositorio($db))->obtener((int) $sesion['receta_id'], $idUsuario);
if ($receta === null) {
    redirigir('error.php?motivo=no-encontrado');
}

$porciones = (int) $sesion['porciones'];
$factor = Porciones::factor((int) $receta['porciones_base'], $porciones);
$porSlug = [];
foreach ($receta['ingredientes'] as $ing) {
    $porSlug[Porciones::slug($ing['nombre'])] = [
        'nombre' => $ing['nombre'],
        'cantidad' => $ing['cantidad'] === null ? null : (float) $ing['cantidad'],
        'unidad' => $ing['unidad'],
    ];
}
$pasos = $receta['pasos'];
$hechos = array_fill_keys($sesion['hechos'], true);
$actual = null;
foreach ($pasos as $paso) {
    if (!isset($hechos[(int) $paso['id']])) {
        $actual = (int) $paso['id'];
        break;
    }
}
$cuantosHechos = count(array_filter($pasos, fn ($p) => isset($hechos[(int) $p['id']])));
$paraDescontar = $cocina->paraDescontar($idUsuario, (int) $sesion['id']);
$nivelTexto = fn (array $estado): string => $estado['cantidad'] !== null
    ? DespensaRepositorio::textoCantidad($estado['cantidad'], $estado['unidad'])
    : DespensaRepositorio::NIVELES[(int) $estado['nivel']];
$minutos = fn (int $segundos): string => sprintf('%d:%02d', intdiv($segundos, 60), $segundos % 60);

$titulo = 'Cocinando: ' . $receta['titulo'];
require parcial('encabezado');
?>
<?php $enlaceReceta = 'app/receta.php?id=' . $receta['id'] . '&porciones=' . $porciones; ?>
<?= enlaceVolver($enlaceReceta, 'Volver a la receta', [[$enlaceReceta, 'La receta']]) ?>
<div class="cocinar" data-sesion="<?= e($sesion['id']) ?>" data-csrf="<?= e($_SESSION['csrf']) ?>"
     data-accion="<?= e(url('app/cocinar.php')) ?>">
  <header class="cocinar__cabeza">
    <h1><?= e($receta['titulo']) ?></h1>
    <p class="cocinar__datos">
      <span class="etiqueta"><?= $porciones ?> porci<?= $porciones === 1 ? 'ón' : 'ones' ?></span>
      <span class="cocinar__cuenta" aria-live="polite"><strong data-hechos><?= $cuantosHechos ?></strong> de <?= count($pasos) ?> pasos</span>
    </p>
    <div class="cocinar__barra" aria-hidden="true"><span style="width: <?= count($pasos) ? round(100 * $cuantosHechos / count($pasos)) : 0 ?>%"></span></div>
  </header>

  <details class="cocinar__ingredientes">
    <summary>Ingredientes para <?= $porciones ?> porci<?= $porciones === 1 ? 'ón' : 'ones' ?></summary>
    <ul>
      <?php foreach ($receta['ingredientes'] as $ing) : ?>
        <li><img class="icono-chico" src="<?= e(iconoIngrediente($ing['nombre'])) ?>" alt="" width="30" height="30">
          <strong><?= e($ing['nombre']) ?></strong>
          <?= e(Porciones::texto($ing['cantidad'] === null ? null : (float) $ing['cantidad'], $ing['unidad'], $factor)) ?>
          <?php if ($ing['es_opcional']) : ?><span class="texto-suave">(opcional)</span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </details>

  <ol class="cocinar__pasos">
    <?php foreach ($pasos as $i => $paso) : ?>
      <?php
      $id = (int) $paso['id'];
      $hecho = isset($hechos[$id]);
      $segundos = (int) ($paso['segundos_timer'] ?? 0);
      ?>
      <li class="tarjeta paso-cocina<?= $hecho ? ' paso-cocina--hecho' : '' ?><?= $id === $actual ? ' paso-cocina--actual' : '' ?>"
          id="paso-<?= $id ?>" data-paso="<?= $id ?>">
        <p class="paso-cocina__numero">Paso <?= $i + 1 ?></p>
        <p class="paso-cocina__texto"><?= e(Porciones::pasoConCantidades($paso['instruccion'], $porSlug, $factor)) ?></p>
        <div class="paso-cocina__acciones">
          <?php if ($segundos > 0) : ?>
            <?php /* Temporizador: lo maneja cocina-modo.js; sin JavaScript queda el tiempo como dato */ ?>
            <div class="temporizador" data-segundos="<?= $segundos ?>" data-clave="<?= e($sesion['id'] . '-' . $id) ?>">
              <span class="temporizador__reloj" role="timer" aria-live="off"><?= $minutos($segundos) ?></span>
              <button class="boton boton--secundario boton--chico temporizador__boton" type="button" hidden>▶ Empezar</button>
              <button class="boton boton--secundario boton--chico temporizador__cancelar" type="button" hidden>Cancelar</button>
            </div>
          <?php endif; ?>
          <form method="post" class="paso-cocina__form">
            <?= Sesion::campoCsrf() ?>
            <input type="hidden" name="accion" value="paso">
            <input type="hidden" name="sesion" value="<?= e($sesion['id']) ?>">
            <input type="hidden" name="paso" value="<?= $id ?>">
            <input type="hidden" name="hecho" value="<?= $hecho ? '0' : '1' ?>">
            <button class="boton paso-cocina__listo" type="submit" aria-pressed="<?= $hecho ? 'true' : 'false' ?>">
              <span class="paso-cocina__tilde" aria-hidden="true">✓</span>
              <span class="paso-cocina__etiqueta"><?= $hecho ? 'Hecho' : 'Listo' ?></span>
            </button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>

  <p class="cocinar__aviso-tiempo solo-lectores" aria-live="assertive"></p>

  <section class="tarjeta cocinar__fin" id="terminar">
    <h2>¿Terminaste?</h2>
    <form method="post">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="terminar">
      <input type="hidden" name="sesion" value="<?= e($sesion['id']) ?>">
      <?php if ($paraDescontar === []) : ?>
        <p>No hay nada para descontar de tu despensa: no tenías cargados estos ingredientes (o son básicos).</p>
      <?php else : ?>
        <p>Vamos a actualizar tu despensa así. Destildá lo que no usaste.</p>
        <ul class="descontar">
          <?php foreach ($paraDescontar as $fila) : ?>
            <li>
              <label>
                <input type="checkbox" name="descontar[]" value="<?= e($fila['ingrediente_id']) ?>" checked>
                <img class="icono-chico" src="<?= e(iconoIngrediente($fila['nombre'])) ?>" alt="" width="28" height="28">
                <strong><?= e($fila['nombre']) ?>:</strong>
                <?= e($nivelTexto($fila['item'])) ?> → <?= e($nivelTexto($fila['despues'])) ?>
              </label>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p class="acciones">
        <button class="boton" type="submit">Terminé<?= $paraDescontar === [] ? '' : ': actualizar mi despensa' ?></button>
      </p>
    </form>
    <form method="post" class="cocinar__abandonar">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="abandonar">
      <input type="hidden" name="sesion" value="<?= e($sesion['id']) ?>">
      <button class="boton boton--secundario boton--chico" type="submit">Dejar de cocinar sin descontar</button>
    </form>
  </section>
</div>
<script src="<?= e(recurso('js/cocina-modo.js')) ?>" defer></script>
<?php require parcial('pie'); ?>
