<?php

/**
 * Mi despensa: ABM de los ingredientes que tiene el usuario.
 *   despensa.php               → formulario de alta + puerta con imanes
 *   despensa.php?editar=ID     → formulario de modificación
 *   despensa.php?ubicacion=... → filtra por alacena, heladera o freezer
 *   POST accion=guardar|borrar
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\DespensaRepositorio;
use Alacena\Repositorios\IngredienteRepositorio;
use Alacena\Sesion;

Sesion::requiereLogin();

$db = Db::conexion();
$repo = new DespensaRepositorio($db);
$catalogo = (new IngredienteRepositorio($db))->todos();
$usuarioId = Sesion::idUsuario();
$errores = [];
$vacio = ['id' => 0, 'ingrediente_id' => 0, 'nivel' => 2, 'fecha_vencimiento' => '', 'ubicacion' => 'alacena'];
$item = $vacio;

if (esPost()) {
    Sesion::verificarCsrf();
    $id = (int) ($_POST['id'] ?? 0);

    if (campo('accion') === 'borrar') {
        if ($repo->borrar($usuarioId, $id)) {
            Sesion::avisar('exito', 'Se quitó de tu despensa.');
        } else {
            Sesion::avisar('error', 'Ese ingrediente no está en tu despensa.');
        }
        redirigir('app/despensa.php');
    }

    $item = [
        'id' => $id,
        'ingrediente_id' => (int) ($_POST['ingrediente_id'] ?? 0),
        'nivel' => (int) ($_POST['nivel'] ?? 2),
        'fecha_vencimiento' => campo('fecha_vencimiento'),
        'ubicacion' => campo('ubicacion'),
    ];
    if (!in_array($item['ingrediente_id'], array_map('intval', array_column($catalogo, 'id')), true)) {
        $errores['ingrediente_id'] = 'Elegí un ingrediente de la lista.';
    }
    if (!isset(DespensaRepositorio::NIVELES[$item['nivel']])) {
        $errores['nivel'] = 'Elegí cuánto tenés.';
    }
    if (!isset(DespensaRepositorio::UBICACIONES[$item['ubicacion']])) {
        $errores['ubicacion'] = 'Elegí dónde lo guardás.';
    }
    $fecha = DateTime::createFromFormat('!Y-m-d', $item['fecha_vencimiento']);
    if ($item['fecha_vencimiento'] !== '' && ($fecha === false || $fecha->format('Y-m-d') !== $item['fecha_vencimiento'])) {
        $errores['fecha_vencimiento'] = 'La fecha no es válida.';
    }
    if ($id > 0 && $repo->obtener($usuarioId, $id) === null) {
        redirigir('error.php?motivo=no-encontrado');
    }

    if ($errores === []) {
        $datos = $item;
        $datos['fecha_vencimiento'] = $item['fecha_vencimiento'] !== '' ? $item['fecha_vencimiento'] : null;
        if ($repo->guardar($usuarioId, $datos, $id)) {
            Sesion::avisar('exito', $id ? 'Se guardaron los cambios.' : 'Se agregó a tu despensa.');
            redirigir('app/despensa.php');
        }
        $errores['ingrediente_id'] = 'Ese ingrediente ya está en esa ubicación: editalo en la lista.';
    }
} elseif (isset($_GET['editar'])) {
    $item = $repo->obtener($usuarioId, (int) $_GET['editar']);
    if ($item === null) {
        redirigir('error.php?motivo=no-encontrado');
    }
}

$ubicacion = (string) ($_GET['ubicacion'] ?? '');
if (!isset(DespensaRepositorio::UBICACIONES[$ubicacion])) {
    $ubicacion = '';
}
$items = $repo->listar($usuarioId, '', $ubicacion);
$error = fn(string $campo) => isset($errores[$campo]) ? '<p class="error-campo">' . e($errores[$campo]) . '</p>' : '';

/**
 * Dibujo del ingrediente en public/img/ingredientes/.
 * Prueba con el nombre completo ("queso-cremoso") y después con la primera palabra ("queso"),
 * salvo nombres como "Crema de leche" o "Atún en lata", que no son la primera palabra.
 * Si un ingrediente nuevo todavía no tiene dibujo, muestra un frasco genérico (comida.svg).
 */
$dibujo = function (string $nombre): string {
    $sinTildes = strtr(mb_strtolower($nombre), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    $completo = trim((string) preg_replace('/[^a-z0-9]+/', '-', $sinTildes), '-');
    $candidatos = [$completo];
    if (!preg_match('/ (de|en) /', $sinTildes)) {
        $candidatos[] = strtok($completo, '-');
    }
    foreach ($candidatos as $archivo) {
        if (is_file(dirname(__DIR__) . '/img/ingredientes/' . $archivo . '.svg')) {
            return 'img/ingredientes/' . $archivo . '.svg';
        }
    }
    return 'img/ingredientes/comida.svg';
};

$titulo = 'Mi despensa';
require parcial('encabezado');
?>
<h1>Mi despensa</h1>

<section class="tarjeta" id="formulario">
    <h2><?= $item['id'] ? 'Editar ingrediente' : 'Agregar ingrediente' ?></h2>
    <form class="formulario formulario--ancho" method="post" action="<?= e(url('app/despensa.php')) ?>">
        <?= Sesion::campoCsrf() ?>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
        <div class="fila-campos">
            <div class="campo">
                <label for="ingrediente_id">Ingrediente</label>
                <select id="ingrediente_id" name="ingrediente_id" required>
                    <option value="">Elegí…</option>
                    <?php $grupo = null; ?>
                    <?php foreach ($catalogo as $ing) : ?>
                        <?php if ($ing['categoria'] !== $grupo) : ?>
                            <?= $grupo !== null ? '</optgroup>' : '' ?><optgroup label="<?= e($ing['categoria']) ?>">
                                <?php $grupo = $ing['categoria']; ?>
                            <?php endif; ?>
                            <option value="<?= e($ing['id']) ?>" <?= (int) $item['ingrediente_id'] === (int) $ing['id'] ? ' selected' : '' ?>><?= e($ing['nombre']) ?></option>
                        <?php endforeach; ?>
                            </optgroup>
                </select>
                <?= $error('ingrediente_id') ?>
            </div>
            <div class="campo">
                <label for="nivel">¿Cuánto tenés?</label>
                <select id="nivel" name="nivel">
                    <?php foreach (DespensaRepositorio::NIVELES as $valor => $texto) : ?>
                        <option value="<?= $valor ?>" <?= (int) $item['nivel'] === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $error('nivel') ?>
            </div>
            <div class="campo">
                <label for="ubicacion">¿Dónde está?</label>
                <select id="ubicacion" name="ubicacion">
                    <?php foreach (DespensaRepositorio::UBICACIONES as $valor => $texto) : ?>
                        <option value="<?= e($valor) ?>" <?= $item['ubicacion'] === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $error('ubicacion') ?>
            </div>
            <div class="campo">
                <label for="fecha_vencimiento">Vence el (opcional)</label>
                <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" value="<?= e((string) $item['fecha_vencimiento']) ?>">
                <?= $error('fecha_vencimiento') ?>
            </div>
        </div>
        <div class="acciones">
            <button class="boton" type="submit"><?= $item['id'] ? 'Guardar cambios' : 'Agregar' ?></button>
            <?php if ($item['id']) : ?>
                <a class="boton boton--secundario" href="<?= e(url('app/despensa.php')) ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<nav class="pestanas" aria-label="Filtrar por ubicación">
    <a href="<?= e(url('app/despensa.php')) ?>" <?= $ubicacion === '' ? ' aria-current="page"' : '' ?>>Todo</a>
    <?php foreach (DespensaRepositorio::UBICACIONES as $valor => $texto) : ?>
        <a href="<?= e(url('app/despensa.php?ubicacion=' . $valor)) ?>" <?= $ubicacion === $valor ? ' aria-current="page"' : '' ?>><?= e($texto) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($items === []) : ?>
    <p class="vacio">
        <?= $ubicacion === '' ? 'Tu despensa está vacía. Agregá lo que tenés con el formulario de arriba.' : 'No hay nada en esta ubicación.' ?>
    </p>
<?php else : ?>
    <div class="campo filtro-rapido">
        <label for="filtro">Buscar en mi despensa</label>
        <input id="filtro" type="search" placeholder="Escribí para filtrar" autocomplete="off">
    </div>

    <ul id="lista-despensa" class="puerta-despensa">
        <?php foreach ($items as $fila) : ?>
            <?php
            $dias = $fila['dias'] === null ? null : (int) $fila['dias'];
            [$textoVence, $estilo] = DespensaRepositorio::estadoVencimiento($dias);
            $nivel = (int) $fila['nivel'];
            $venceYa = $nivel > 0 && $dias !== null && $dias <= 1;
            ?>
            <li class="item-despensa<?= $nivel === 0 ? ' item-despensa--agotado' : '' ?>" data-nombre="<?= e(mb_strtolower($fila['nombre'])) ?>">
                <div class="item-despensa__dibujo">
                    <img src="<?= e(url($dibujo($fila['nombre']))) ?>" alt="" width="96" height="96">

                </div>
                <div class="item-despensa__datos">
                    <h3 class="item-despensa__nombre"><?= e($fila['nombre']) ?></h3>
                    <p class="item-despensa__estado">
                        <span class="nivel nivel--<?= $nivel ?>"><?= e(DespensaRepositorio::NIVELES[$nivel]) ?></span>
                        <span class="etiqueta<?= $estilo ? ' etiqueta--' . $estilo : '' ?>"><?= e($textoVence) ?></span>
                    </p>
                    <p class="item-despensa__lugar">
                        <?= e($fila['categoria']) ?> · <?= e(DespensaRepositorio::UBICACIONES[$fila['ubicacion']]) ?>
                        <?php if ($fila['fecha_vencimiento']) : ?> · <?= e(date('d/m', strtotime($fila['fecha_vencimiento']))) ?><?php endif; ?>
                    </p>
                </div>
                <div class="acciones item-despensa__acciones">
                    <a class="boton boton--secundario boton--chico" href="<?= e(url('app/despensa.php?editar=' . $fila['id'] . '#formulario')) ?>">Editar<span class="solo-lectores"> <?= e($fila['nombre']) ?></span></a>
                    <form method="post" data-confirmar="¿Quitar <?= e($fila['nombre']) ?> de tu despensa?">
                        <?= Sesion::campoCsrf() ?>
                        <input type="hidden" name="accion" value="borrar">
                        <input type="hidden" name="id" value="<?= e($fila['id']) ?>">
                        <button class="boton boton--peligro boton--chico" type="submit">Quitar<span class="solo-lectores"> <?= e($fila['nombre']) ?></span></button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="vacio" id="sin-resultados" hidden>Nada coincide con lo que escribiste.</p>
<?php endif; ?>
<script src="<?= e(url('js/despensa.js')) ?>" defer></script>
<?php require parcial('pie'); ?>