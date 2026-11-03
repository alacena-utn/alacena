<?php

/**
 * Consultas recibidas por el formulario de contacto (solo administradores):
 * listado con buscador y filtro, responder (se guarda, se manda por correo y el usuario con cuenta
 * la ve en Contacto → Mis consultas), marcar como respondida o nueva, y borrar.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Cooki\Correo;
use Cooki\Db;
use Cooki\Repositorios\ContactoRepositorio;
use Cooki\Sesion;

Sesion::requiereAdmin();

$repo = new ContactoRepositorio(Db::conexion());

if (esPost()) {
    Sesion::verificarCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $accion = campo('accion');
    if ($accion === 'responder') {
        $consulta = $repo->obtener($id);
        $respuesta = trim((string) ($_POST['respuesta'] ?? ''));
        if ($consulta === null) {
            Sesion::avisar('error', 'La consulta ya no existe.');
        } elseif (mb_strlen($respuesta) < 2 || mb_strlen($respuesta) > 4000) {
            Sesion::avisar('error', 'Escribí la respuesta (hasta 4000 caracteres).');
            redirigir('admin/consultas.php?responder=' . $id . '#consulta-' . $id);
        } else {
            $cuerpo = "Hola, {$consulta['nombre']}:\n\n" . $respuesta . "\n\n"
                . "— El equipo de Cooki\n\n"
                . "Tu consulta (" . date('d/m/Y', strtotime($consulta['creado_en'])) . "): {$consulta['asunto']}\n"
                . '> ' . str_replace("\n", "\n> ", $consulta['mensaje']);
            $enviada = Correo::enviar($consulta['email'], 'Re: ' . $consulta['asunto'], $cuerpo);
            $repo->responder($id, $respuesta, Sesion::idUsuario(), $enviada);
            Sesion::avisar(
                $enviada ? 'exito' : 'info',
                $enviada
                    ? 'Se envió la respuesta a ' . $consulta['email'] . '.'
                    : 'La respuesta quedó guardada, pero el correo no se pudo enviar.'
                        . ($consulta['usuario_id'] ? ' Igual la va a ver en Contacto → Mis consultas.' : '')
            );
        }
        redirigir('admin/consultas.php#consulta-' . $id);
    } elseif ($accion === 'borrar') {
        $ok = $repo->borrar($id);
        Sesion::avisar($ok ? 'exito' : 'error', $ok ? 'Se borró la consulta.' : 'La consulta ya no existe.');
    } elseif (isset(ContactoRepositorio::ESTADOS[$accion])) {
        $repo->cambiarEstado($id, $accion);
        Sesion::avisar('exito', 'La consulta quedó como “' . ContactoRepositorio::ESTADOS[$accion] . '”.');
    }
    redirigir('admin/consultas.php');
}

$busqueda = trim((string) ($_GET['q'] ?? ''));
$estado = (string) ($_GET['estado'] ?? '');
if (!isset(ContactoRepositorio::ESTADOS[$estado])) {
    $estado = '';
}
$consultas = $repo->listar($busqueda, $estado);
$abierta = (int) ($_GET['responder'] ?? 0);   // si la respuesta vino vacía, ese formulario queda abierto

$titulo = 'Consultas · Administración';
require parcial('encabezado');
?>
<?= enlaceVolver('admin/index.php', 'Volver al panel') ?>
<h1>Consultas recibidas</h1>

<form class="buscador" method="get" role="search">
  <div class="campo">
    <label for="q">Buscar</label>
    <input id="q" name="q" type="search" placeholder="Nombre, email, asunto o texto" value="<?= e($busqueda) ?>">
  </div>
  <div class="campo">
    <label for="estado">Estado</label>
    <select id="estado" name="estado">
      <option value="">Todas</option>
      <?php foreach (ContactoRepositorio::ESTADOS as $valor => $texto) : ?>
        <option value="<?= e($valor) ?>"<?= $estado === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="boton boton--secundario" type="submit">Buscar</button>
</form>

<?php if ($consultas === []) : ?>
  <p class="vacio">No hay consultas<?= $busqueda !== '' || $estado !== '' ? ' con esos filtros' : '' ?>.</p>
<?php endif; ?>

<?php foreach ($consultas as $c) : ?>
  <article class="tarjeta consulta<?= $c['estado'] === 'nueva' ? ' consulta--nueva' : '' ?>" id="consulta-<?= e($c['id']) ?>">
    <header class="encabezado-pagina">
      <div>
        <h2><?= e($c['asunto']) ?></h2>
        <p class="texto-suave">
          <?= e($c['nombre']) ?> · <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
          · <?= e(date('d/m/Y H:i', strtotime($c['creado_en']))) ?>
          <?= $c['usuario'] ? ' · usuario registrado' : '' ?>
        </p>
      </div>
      <span class="etiqueta<?= $c['estado'] === 'nueva' ? ' etiqueta--alerta' : ' etiqueta--exito' ?>"><?= e(ContactoRepositorio::ESTADOS[$c['estado']]) ?></span>
    </header>
    <p class="consulta__mensaje"><?= nl2br(e($c['mensaje'])) ?></p>
    <?php if (!$c['correo_enviado']) : ?>
      <p class="texto-suave"><small>El aviso por correo no se pudo enviar; la consulta quedó guardada igual.</small></p>
    <?php endif; ?>
    <?php if ($c['respuesta'] !== null) : ?>
      <?php /* La respuesta ya enviada: una notita pegada abajo de la consulta */ ?>
      <div class="consulta__respuesta">
        <p class="consulta__quien">
          Respondió <?= e(explode(' ', (string) ($c['respondio'] ?? 'la administración'))[0]) ?>
          · <?= e(date('d/m/Y H:i', strtotime($c['respondida_en']))) ?>
          <?= $c['respuesta_enviada'] ? '' : ' · <span class="etiqueta etiqueta--alerta">el correo no salió</span>' ?>
        </p>
        <p><?= nl2br(e($c['respuesta'])) ?></p>
      </div>
    <?php endif; ?>
    <?php /* Responder: un <details> se abre sin JavaScript */ ?>
    <details class="responder"<?= $abierta === (int) $c['id'] ? ' open' : '' ?>>
      <summary class="boton boton--chico"><?= $c['respuesta'] !== null ? 'Responder de nuevo' : 'Responder' ?></summary>
      <form class="responder__form" method="post">
        <?= Sesion::campoCsrf() ?>
        <input type="hidden" name="id" value="<?= e($c['id']) ?>">
        <label for="respuesta-<?= e($c['id']) ?>">Respuesta para <?= e($c['nombre']) ?></label>
        <textarea id="respuesta-<?= e($c['id']) ?>" name="respuesta" rows="5" required minlength="2" maxlength="4000"
                  placeholder="Hola, <?= e(explode(' ', $c['nombre'])[0]) ?>: …"></textarea>
        <p class="texto-suave"><small>Le llega por correo a <?= e($c['email']) ?><?= $c['usuario'] ? ' y la ve en Contacto → Mis consultas' : '' ?>. La consulta queda como respondida.</small></p>
        <button class="boton" name="accion" value="responder">Enviar respuesta</button>
      </form>
    </details>
    <div class="acciones">
      <form method="post">
        <?= Sesion::campoCsrf() ?>
        <input type="hidden" name="id" value="<?= e($c['id']) ?>">
        <?php if ($c['estado'] === 'nueva') : ?>
          <button class="boton boton--secundario boton--chico" name="accion" value="respondida">Marcar como respondida</button>
        <?php else : ?>
          <button class="boton boton--secundario boton--chico" name="accion" value="nueva">Marcar como nueva</button>
        <?php endif; ?>
      </form>
      <form method="post" data-confirmar="¿Borrar la consulta de <?= e($c['nombre']) ?>?">
        <?= Sesion::campoCsrf() ?>
        <input type="hidden" name="id" value="<?= e($c['id']) ?>">
        <button class="boton boton--peligro boton--chico" name="accion" value="borrar">Borrar</button>
      </form>
    </div>
  </article>
<?php endforeach; ?>
<?php require parcial('pie'); ?>
