<?php

/**
 * ABM de usuarios (solo administradores).
 *   usuarios.php            → listado con buscador
 *   usuarios.php?nuevo      → formulario de alta
 *   usuarios.php?editar=ID  → formulario de modificación
 *   POST accion=guardar     → alta o modificación
 *   POST accion=borrar      → baja
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Cooki\Db;
use Cooki\Dominio\Paginacion;
use Cooki\Repositorios\UsuarioRepositorio;
use Cooki\Sesion;

Sesion::requiereAdmin();

$repo = new UsuarioRepositorio(Db::conexion());
$roles = [Sesion::ROL_GENERAL => 'General', Sesion::ROL_ADMIN => 'Administrador'];
$errores = [];
$form = null;   // datos del formulario cuando se está creando o editando

if (esPost()) {
    Sesion::verificarCsrf();
    $accion = campo('accion');
    $id = (int) ($_POST['id'] ?? 0);

    if ($accion === 'borrar') {
        $usuario = $repo->buscarPorId($id);
        if ($usuario === null) {
            Sesion::avisar('error', 'Ese usuario no existe.');
        } elseif ($id === Sesion::idUsuario()) {
            Sesion::avisar('error', 'No podés borrar tu propia cuenta mientras estás usándola.');
        } elseif ($usuario['rol'] === Sesion::ROL_ADMIN && $repo->contarAdministradores() <= 1) {
            Sesion::avisar('error', 'Tiene que quedar al menos un administrador.');
        } else {
            $repo->borrar($id);
            Sesion::avisar('exito', 'Se borró la cuenta de ' . $usuario['nombre'] . '.');
        }
        redirigir('admin/usuarios.php');
    }

    if ($accion === 'guardar') {
        $form = [
            'id' => $id,
            'nombre' => campo('nombre'),
            'email' => mb_strtolower(campo('email')),
            'rol' => array_key_exists(campo('rol'), $roles) ? campo('rol') : Sesion::ROL_GENERAL,
        ];
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        // Al editar, la contraseña es opcional: si queda vacía no se cambia.
        $errores = UsuarioRepositorio::validar($form['nombre'], $form['email'], $contrasena, $id === 0);
        if (!isset($errores['email']) && $repo->existeEmail($form['email'], $id)) {
            $errores['email'] = 'Ya hay otra cuenta con ese email.';
        }
        $actual = $id > 0 ? $repo->buscarPorId($id) : null;
        if ($id > 0 && $actual === null) {
            redirigir('error.php?motivo=no-encontrado');
        }
        $quitaUltimoAdmin = $actual !== null && $actual['rol'] === Sesion::ROL_ADMIN
            && $form['rol'] !== Sesion::ROL_ADMIN && $repo->contarAdministradores() <= 1;
        if ($quitaUltimoAdmin) {
            $errores['rol'] = 'Es el único administrador: no se le puede quitar el rol.';
        }

        if ($errores === []) {
            if ($id === 0) {
                $repo->crear($form['nombre'], $form['email'], $contrasena, $form['rol']);
                Sesion::avisar('exito', 'Se creó la cuenta de ' . $form['nombre'] . '.');
            } else {
                $repo->actualizar($id, $form['nombre'], $form['email'], $form['rol'], $contrasena);
                Sesion::avisar('exito', 'Se guardaron los cambios de ' . $form['nombre'] . '.');
            }
            redirigir('admin/usuarios.php');
        }
    }
} elseif (isset($_GET['nuevo'])) {
    $form = ['id' => 0, 'nombre' => '', 'email' => '', 'rol' => Sesion::ROL_GENERAL];
} elseif (isset($_GET['editar'])) {
    $form = $repo->buscarPorId((int) $_GET['editar']);
    if ($form === null) {
        redirigir('error.php?motivo=no-encontrado');
    }
}

$busqueda = trim((string) ($_GET['q'] ?? ''));
$usuarios = $form === null ? $repo->listar($busqueda) : [];
$paginacion = new Paginacion(count($usuarios), 20, (int) ($_GET['pagina'] ?? 1));
$enlace = fn (int $pagina): string => enlacePagina('admin/usuarios.php', $pagina);

$titulo = 'Usuarios';
require parcial('encabezado');
?>
<?php if ($form !== null) : ?>
  <?= enlaceVolver('admin/usuarios.php', 'Volver a Usuarios', [], true) ?>
  <section class="tarjeta tarjeta--angosta">
    <h1><?= $form['id'] ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
    <form class="formulario" method="post">
      <?= Sesion::campoCsrf() ?>
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= e($form['id']) ?>">
      <div class="campo">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" required maxlength="80" value="<?= e($form['nombre']) ?>">
        <?php if (isset($errores['nombre'])) : ?><p class="error-campo"><?= e($errores['nombre']) ?></p><?php endif; ?>
      </div>
      <div class="campo">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" value="<?= e($form['email']) ?>">
        <?php if (isset($errores['email'])) : ?><p class="error-campo"><?= e($errores['email']) ?></p><?php endif; ?>
      </div>
      <div class="campo">
        <label for="rol">Rol</label>
        <select id="rol" name="rol">
          <?php foreach ($roles as $valor => $texto) : ?>
            <option value="<?= e($valor) ?>"<?= $form['rol'] === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['rol'])) : ?><p class="error-campo"><?= e($errores['rol']) ?></p><?php endif; ?>
      </div>
      <div class="campo">
        <label for="contrasena"><?= $form['id'] ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
        <input id="contrasena" name="contrasena" type="password" autocomplete="new-password"<?= $form['id'] ? '' : ' required' ?>>
        <small><?= $form['id'] ? 'Dejala vacía para no cambiarla. ' : '' ?>Al menos 8 caracteres, con letras y números.</small>
        <?php if (isset($errores['contrasena'])) : ?><p class="error-campo"><?= e($errores['contrasena']) ?></p><?php endif; ?>
      </div>
      <div class="acciones">
        <button class="boton" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="<?= e(url('admin/usuarios.php')) ?>">Cancelar</a>
      </div>
    </form>
  </section>
<?php else : ?>
  <?= enlaceVolver('admin/index.php', 'Volver al panel', [['admin/index.php', 'Panel']]) ?>
  <div class="encabezado-pagina">
    <h1>Usuarios</h1>
    <a class="boton" href="<?= e(url('admin/usuarios.php?nuevo')) ?>">+ Nuevo usuario</a>
  </div>
  <form class="buscador" method="get" role="search">
    <div class="campo">
      <label for="q">Buscar por nombre o email</label>
      <input id="q" name="q" type="search" value="<?= e($busqueda) ?>">
    </div>
    <button class="boton boton--secundario" type="submit">Buscar</button>
  </form>
  <?php if ($usuarios === []) : ?>
    <p class="vacio">No hay usuarios que coincidan con “<?= e($busqueda) ?>”.</p>
  <?php else : ?>
    <div class="tabla-contenedor">
      <table>
        <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Alta</th><th><span class="solo-lectores">Acciones</span></th></tr></thead>
        <tbody>
        <?php foreach ($paginacion->cortar($usuarios) as $u) : ?>
          <tr>
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="etiqueta<?= $u['rol'] === Sesion::ROL_ADMIN ? ' etiqueta--alerta' : '' ?>"><?= e($roles[$u['rol']]) ?></span></td>
            <td><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
            <td>
              <div class="acciones">
                <a class="boton boton--secundario boton--chico" href="<?= e(url('admin/usuarios.php?editar=' . $u['id'])) ?>">Editar</a>
                <form method="post" data-confirmar="¿Borrar la cuenta de <?= e($u['nombre']) ?>? Se borra también su despensa.">
                  <?= Sesion::campoCsrf() ?>
                  <input type="hidden" name="accion" value="borrar">
                  <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                  <button class="boton boton--peligro boton--chico" type="submit">Borrar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require parcial('paginacion'); ?>
  <?php endif; ?>
<?php endif; ?>
<?php require parcial('pie'); ?>
