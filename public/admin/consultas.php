<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\ContactoRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();

$repo = new ContactoRepositorio(Db::conexion());

if (esPost()) {
    Sesion::verificarCsrf();

    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $accion = campo('accion');

    if ($id === false || $id === null || $id < 1) {
        Sesion::avisar('error', 'El identificador de la consulta no es válido.');
        redirigir('admin/consultas.php');
    }

    if ($accion === 'borrar') {
        $ok = $repo->borrar($id);

        Sesion::avisar(
            $ok ? 'exito' : 'error',
            $ok ? 'Se borró la consulta.' : 'La consulta ya no existe.'
        );
    } elseif (isset(ContactoRepositorio::ESTADOS[$accion])) {
        $ok = $repo->cambiarEstado($id, $accion);

        Sesion::avisar(
            $ok ? 'exito' : 'error',
            $ok
                ? 'Se actualizó el estado de la consulta.'
                : 'No se pudo actualizar la consulta.'
        );
    } else {
        Sesion::avisar('error', 'La acción solicitada no es válida.');
    }

    redirigir('admin/consultas.php');
}

$buscar = trim((string) ($_GET['buscar'] ?? ''));
$estado = trim((string) ($_GET['estado'] ?? ''));

if (!isset(ContactoRepositorio::ESTADOS[$estado])) {
    $estado = '';
}

$consultas = $repo->listar($buscar, $estado);

$titulo = 'Consultas recibidas';

parcial('encabezado', ['titulo' => $titulo]);
?>

<main class="contenedor">
    <section class="seccion-admin">
        <h1>Consultas recibidas</h1>

        <p>
            Desde esta sección podés revisar los mensajes enviados
            mediante el formulario de contacto.
        </p>

        <form method="get" action="">
            <div class="campo">
                <label for="buscar">Buscar consultas</label>
                <input
                    type="search"
                    id="buscar"
                    name="buscar"
                    value="<?= e($buscar) ?>"
                    placeholder="Nombre, correo, asunto o mensaje"
                >
            </div>

            <div class="campo">
                <label for="estado">Filtrar por estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <?php foreach (ContactoRepositorio::ESTADOS as $clave => $nombre): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= $estado === $clave ? 'selected' : '' ?>
                        >
                            <?= e($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Buscar</button>
            <a href="<?= e(url('admin/consultas.php')) ?>">Limpiar filtros</a>
        </form>

        <p>
            Resultados encontrados: <?= count($consultas) ?>
        </p>

        <?php if ($consultas === []): ?>
            <p>No se encontraron consultas con esos criterios.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Asunto</th>
                            <th>Mensaje</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultas as $consulta): ?>
                            <tr>
                                <td>
                                    <?= e((string) ($consulta['creado_en'] ?? '')) ?>
                                </td>
                                <td>
                                    <?= e((string) ($consulta['nombre'] ?? '')) ?>
                                </td>
                                <td>
                                    <a href="mailto:<?= e((string) ($consulta['email'] ?? '')) ?>">
                                        <?= e((string) ($consulta['email'] ?? '')) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= e((string) ($consulta['asunto'] ?? '')) ?>
                                </td>
                                <td>
                                    <?= nl2br(e((string) ($consulta['mensaje'] ?? ''))) ?>
                                </td>
                                <td>
                                    <?= e(
                                        ContactoRepositorio::ESTADOS[
                                            (string) ($consulta['estado'] ?? '')
                                        ] ?? 'Sin estado'
                                    ) ?>
                                </td>
                                <td>
                                    <?php foreach (ContactoRepositorio::ESTADOS as $clave => $nombre): ?>
                                        <?php if (($consulta['estado'] ?? '') !== $clave): ?>
                                            <form
                                                method="post"
                                                action="<?= e(url('admin/consultas.php')) ?>"
                                            >
                                                <?= Sesion::campoCsrf() ?>
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $consulta['id'] ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="<?= e($clave) ?>"
                                                >
                                                <button type="submit">
                                                    Marcar como <?= e($nombre) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <form
                                        method="post"
                                        action="<?= e(url('admin/consultas.php')) ?>"
                                    >
                                        <?= Sesion::campoCsrf() ?>
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $consulta['id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="borrar"
                                        >
                                        <button type="submit">Borrar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php parcial('pie'); ?>