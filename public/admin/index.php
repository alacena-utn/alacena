<?php

/**
 * Panel de administración: resumen y accesos a cada ABM. Solo administradores.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\ResumenRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();

$totales = (new ResumenRepositorio(Db::conexion()))->totales();

$secciones = [
    ['recetas.php', 'Recetas', $totales['recetas'] . ' cargadas', 'Alta, baja y modificación del recetario.'],
    ['ingredientes.php', 'Ingredientes', $totales['ingredientes'] . ' en el catálogo', 'Los ingredientes que se pueden usar en recetas y despensas.'],
    ['consultas.php', 'Consultas', $totales['consultas_nuevas'] . ' sin responder', 'Mensajes que llegaron desde el formulario de contacto.'],
    ['usuarios.php', 'Usuarios', $totales['usuarios'] . ' generales · ' . $totales['administradores'] . ' administradores', 'Cuentas, roles y contraseñas.'],
];

$titulo = 'Administración';
require parcial('encabezado');
?>
<h1>Administración</h1>
<div class="grilla">
  <?php foreach ($secciones as [$ruta, $nombre, $dato, $descripcion]) : ?>
    <article class="tarjeta">
      <h2><?= e($nombre) ?></h2>
      <p><strong><?= e($dato) ?></strong></p>
      <p class="texto-suave"><?= e($descripcion) ?></p>
      <a class="boton boton--secundario" href="<?= e(url('admin/' . $ruta)) ?>">Gestionar <?= e(mb_strtolower($nombre)) ?></a>
    </article>
  <?php endforeach; ?>
</div>
<?php require parcial('pie'); ?>
