<?php
/**
 * Parte de arriba de todas las páginas: <head>, menú de navegación y avisos.
 * Antes de incluirlo, la página define $titulo. Uso:
 *   $titulo = 'Mi despensa';
 *   require parcial('encabezado');
 */

use Alacena\Sesion;

$usuario = Sesion::usuario();
$avisos = Sesion::avisos();
$actual = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$carpeta = basename(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

/** Devuelve aria-current si el enlace es la página actual (el CSS lo resalta). */
$marcar = function (string $ruta) use ($actual, $carpeta): string {
    $partes = explode('/', $ruta);
    $archivo = end($partes);
    $dir = count($partes) > 1 ? $partes[0] : '';
    $mismaCarpeta = $dir === '' ? !in_array($carpeta, ['app', 'admin'], true) : $carpeta === $dir;
    return $mismaCarpeta && $archivo === $actual ? ' aria-current="page"' : '';
};

$menu = [['index.php', 'Inicio']];
if ($usuario !== null) {
    $menu = [['app/index.php', 'Mi cocina'], ['app/despensa.php', 'Mi despensa'], ['app/recetas.php', 'Recetas']];
    if ($usuario['rol'] === Sesion::ROL_ADMIN) {
        $menu[] = ['admin/index.php', 'Administración'];
    }
}
$menu[] = ['contacto.php', 'Contacto'];
?>
<!doctype html>
<html lang="es-AR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Alacena') ?> · Alacena</title>
  <meta name="description" content="Alacena: tu despensa y qué cocinar con lo que tenés">
    <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Figtree:wght@400;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
  <script src="<?= e(url('js/sitio.js')) ?>" defer></script>
</head>
<body>
<header class="barra">
  <div class="barra__interior">
    <a class="marca" href="<?= e(url($usuario ? 'app/index.php' : 'index.php')) ?>">Alacena</a>
    <button class="barra__boton" type="button" aria-expanded="false" aria-controls="menu-principal">Menú</button>
    <nav id="menu-principal" class="menu" aria-label="Principal">
      <ul>
        <?php foreach ($menu as [$ruta, $texto]) : ?>
          <li><a href="<?= e(url($ruta)) ?>"<?= $marcar($ruta) ?>><?= e($texto) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="menu__cuenta">
        <?php if ($usuario !== null) : ?>
          <span class="menu__usuario"><?= e($usuario['nombre']) ?><?= $usuario['rol'] === Sesion::ROL_ADMIN ? ' · admin' : '' ?></span>
          <a class="boton boton--secundario" href="<?= e(url('salir.php')) ?>">Salir</a>
        <?php else : ?>
          <a class="boton boton--secundario" href="<?= e(url('login.php')) ?>">Ingresar</a>
          <a class="boton" href="<?= e(url('registro.php')) ?>">Crear cuenta</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>
<main class="contenido" id="contenido">
<?php foreach ($avisos as $aviso) : ?>
  <p class="aviso aviso--<?= e($aviso['tipo']) ?>" role="<?= $aviso['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= e($aviso['mensaje']) ?></p>
<?php endforeach; ?>
