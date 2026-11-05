<?php

/**
 * Mi cuenta (incremento 2 · P1). Tres formularios en la misma página:
 *   POST accion=datos       → cambiar nombre y email
 *   POST accion=contrasena  → cambiar la contraseña (pide la actual)
 *   POST accion=baja        → borrar la cuenta (pide la contraseña y una confirmación)
 * Al borrar la cuenta se borra también su despensa, sus ingredientes propios y sus preferencias
 * (ON DELETE CASCADE). Sus consultas de contacto quedan, sin el usuario.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Cooki\Db;
use Cooki\Repositorios\UsuarioRepositorio;
use Cooki\Sesion;

Sesion::requiereLogin();
$repo = new UsuarioRepositorio(Db::conexion());
$id = Sesion::idUsuario();
$cuenta = $repo->buscarPorId($id);
if ($cuenta === null) {
    Sesion::salir();
    redirigir('login.php');
}
$errores = [];
$datoNombre = (string) $cuenta['nombre'];
$datoEmail = (string) $cuenta['email'];
$accion = esPost() ? campo('accion') : '';

if ($accion === 'datos') {
    Sesion::verificarCsrf();
    $datoNombre = campo('nombre');
    $datoEmail = mb_strtolower(campo('email'));
    $errores = UsuarioRepositorio::validar($datoNombre, $datoEmail, '', false);
    if (!isset($errores['email']) && $repo->existeEmail($datoEmail, $id)) {
        $errores['email'] = 'Ya hay otra cuenta con ese email.';
    }
    if ($errores === []) {
        $repo->actualizarDatos($id, $datoNombre, $datoEmail);
        Sesion::actualizarDatos($datoNombre, $datoEmail);
        Sesion::avisar('exito', 'Se guardaron tus datos.');
        redirigir('app/perfil.php');
    }
} elseif ($accion === 'contrasena') {
    Sesion::verificarCsrf();
    $nueva = (string) ($_POST['nueva'] ?? '');
    if (!$repo->contrasenaCorrecta($id, (string) ($_POST['actual'] ?? ''))) {
        $errores['actual'] = 'La contraseña actual no es correcta.';
    }
    $errores += array_intersect_key(UsuarioRepositorio::validar($datoNombre, $datoEmail, $nueva), ['contrasena' => 1]);
    if ($nueva !== (string) ($_POST['repetir'] ?? '')) {
        $errores['repetir'] = 'Las contraseñas no coinciden.';
    }
    if ($errores === []) {
        $repo->cambiarContrasena($id, $nueva);
        Sesion::avisar('exito', 'Listo: tu contraseña cambió.');
        redirigir('app/perfil.php');
    }
} elseif ($accion === 'baja') {
    Sesion::verificarCsrf();
    if (!$repo->contrasenaCorrecta($id, (string) ($_POST['contrasena_baja'] ?? ''))) {
        $errores['contrasena_baja'] = 'La contraseña no es correcta.';
    }
    if (empty($_POST['confirmo'])) {
        $errores['confirmo'] = 'Marcá la casilla para confirmar.';
    }
    if ($cuenta['rol'] === Sesion::ROL_ADMIN && $repo->contarAdministradores() <= 1) {
        $errores['confirmo'] = 'Sos la única cuenta de administración: antes creá otra desde Administración → Usuarios.';
    }
    if ($errores === []) {
        $repo->borrar($id);
        Sesion::salir();
        Sesion::avisar('exito', 'Tu cuenta se borró. ¡Gracias por usar Cooki!');
        redirigir('index.php');
    }
}

$error = fn (string $campo) => isset($errores[$campo]) ? '<p class="error-campo">' . e($errores[$campo]) . '</p>' : '';
$titulo = 'Mi cuenta';
require parcial('encabezado');
?>
<?= enlaceVolver('app/index.php', 'Volver a Mi cocina', [['app/mis-preferencias.php', 'Mis preferencias']]) ?>
<?php /* Los espacios vacíos (al lado del título y abajo de cada hoja) se llenan con dibujos sueltos */ ?>
<div class="titulo-con-sueltos">
  <h1>Mi cuenta</h1>
  <?php $sueltos = [[34, 2, 62, -10], [64, -8, 72, 6], [92, 0, 62, 9]]; ?>
  <?php require parcial('dibujos-sueltos'); ?>
</div>
<div class="perfil">
  <div class="perfil__columna">
  <section class="tarjeta" aria-labelledby="titulo-datos">
    <h2 id="titulo-datos">Mis datos</h2>
    <form class="formulario" method="post">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="datos">
      <div class="campo">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" required minlength="2" maxlength="80" autocomplete="name" value="<?= e($datoNombre) ?>">
        <?= $accion === 'datos' ? $error('nombre') : '' ?>
      </div>
      <div class="campo">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e($datoEmail) ?>">
        <?= $accion === 'datos' ? $error('email') : '' ?>
      </div>
      <p class="texto-suave"><small>Cuenta creada el <?= e(date('d/m/Y', strtotime((string) $cuenta['creado_en']))) ?><?= $cuenta['rol'] === Sesion::ROL_ADMIN ? ' · administración' : '' ?>.</small></p>
      <button class="boton" type="submit">Guardar mis datos</button>
    </form>
  </section>
    <div class="perfil__relleno">
      <?php $sueltos = [[20, 20, 96, -10], [82, 58, 104, 8]]; ?>
      <?php require parcial('dibujos-sueltos'); ?>
    </div>
  </div>

  <div class="perfil__columna">
  <section class="tarjeta" aria-labelledby="titulo-contrasena">
    <h2 id="titulo-contrasena">Cambiar la contraseña</h2>
    <form class="formulario" method="post">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="contrasena">
      <div class="campo">
        <label for="actual">Contraseña actual</label>
        <input id="actual" name="actual" type="password" required autocomplete="current-password">
        <?= $error('actual') ?>
      </div>
      <div class="campo">
        <label for="nueva">Contraseña nueva</label>
        <input id="nueva" name="nueva" type="password" required minlength="8" autocomplete="new-password" aria-describedby="ayuda-nueva">
        <small id="ayuda-nueva">Al menos 8 caracteres, con letras y números.</small>
        <?= $error('contrasena') ?>
      </div>
      <div class="campo">
        <label for="repetir">Repetí la contraseña nueva</label>
        <input id="repetir" name="repetir" type="password" required autocomplete="new-password">
        <?= $error('repetir') ?>
      </div>
      <button class="boton" type="submit">Cambiar la contraseña</button>
    </form>
  </section>
    <div class="perfil__relleno">
      <?php $sueltos = [[62, 16, 98, 12]]; ?>
      <?php require parcial('dibujos-sueltos'); ?>
    </div>
  </div>

  <div class="perfil__columna">
  <section class="tarjeta perfil__baja" aria-labelledby="titulo-baja">
    <h2 id="titulo-baja">Borrar mi cuenta</h2>
    <p>Se borran tu despensa, tus ingredientes propios y tus preferencias. <strong>No se puede deshacer.</strong></p>
    <form class="formulario" method="post">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="baja">
      <div class="campo">
        <label for="contrasena_baja">Tu contraseña</label>
        <input id="contrasena_baja" name="contrasena_baja" type="password" required autocomplete="current-password">
        <?= $error('contrasena_baja') ?>
      </div>
      <label class="casilla"><input type="checkbox" name="confirmo" value="1"> Entiendo que mi cuenta se borra para siempre</label>
      <?= $error('confirmo') ?>
      <button class="boton boton--peligro" type="submit">Borrar mi cuenta</button>
    </form>
  </section>
    <div class="perfil__relleno">
      <?php $sueltos = [[24, 50, 104, -8], [80, 0, 88, 14]]; ?>
      <?php require parcial('dibujos-sueltos'); ?>
    </div>
  </div>
</div>
<?php require parcial('pie'); ?>
