<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\IngredienteRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();
$busqueda = is_string($_GET['q'] ?? '') ? trim($_GET['q'] ?? '') : '';
$ingredientes = [];
$error = '';
$avisos = Sesion::avisos();
try {
    $repo = new IngredienteRepositorio(Db::conexion());
    $ingredientes = $busqueda === '' ? $repo->todos() : $repo->buscar($busqueda, PHP_INT_MAX);
} catch (\PDOException $excepcion) {
    $error = 'No se pudieron cargar los ingredientes. Intentá nuevamente más tarde.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de ingredientes - Alacena</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&amp;family=Figtree:wght@400;600;700;800&amp;family=Space+Mono:wght@700&amp;display=swap">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<main class="contenido">
    <header class="encabezado-pagina">
        <h1>Gestión de ingredientes</h1>
        <nav class="acciones" aria-label="Administración">
            <a class="boton" href="ingrediente-editar.php">Crear ingrediente</a>
            <a class="boton boton--secundario" href="../index.php">Inicio</a>
            <a class="boton boton--secundario" href="usuarios.php">Usuarios</a>
        </nav>
    </header>
    <?php foreach ($avisos as $aviso): ?>
        <p class="aviso aviso--<?= $aviso['tipo'] === 'exito' ? 'exito' : 'error' ?>" role="<?= $aviso['tipo'] === 'exito' ? 'status' : 'alert' ?>"><?= e($aviso['mensaje']) ?></p>
    <?php endforeach; ?>
    <?php if ($error !== ''): ?>
        <p class="aviso aviso--error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <section class="tarjeta" aria-labelledby="ingredientes-registrados">
        <h2 id="ingredientes-registrados">Ingredientes registrados</h2>
        <form method="get" class="buscador" role="search">
            <div class="campo">
                <label for="buscarIngrediente">Buscar por nombre o sinónimo</label>
                <input type="search" id="buscarIngrediente" name="q" value="<?= e($busqueda) ?>">
            </div>
            <div class="acciones">
                <button class="boton" type="submit">Buscar</button>
                <a class="boton boton--secundario" href="ingredientes.php">Ver todos</a>
            </div>
        </form>
        <?php if ($error === '' && $ingredientes === []): ?>
            <p class="aviso aviso--info">No se encontraron ingredientes.</p>
        <?php elseif ($ingredientes !== []): ?>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th scope="col">Nombre</th><th scope="col">Categoría</th><th scope="col">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($ingredientes as $ingrediente): ?>
                        <tr>
                            <td><?= e($ingrediente['nombre']) ?></td>
                            <td><?= e($ingrediente['categoria']) ?></td>
                            <td>
                                <div class="acciones">
                                    <a class="boton boton--secundario boton--chico" href="ingrediente-editar.php?id=<?= (int) $ingrediente['id'] ?>">Editar</a>
                                    <form method="post" action="ingrediente-eliminar.php">
                                        <?= Sesion::campoCsrf() ?>
                                        <input type="hidden" name="id" value="<?= (int) $ingrediente['id'] ?>">
                                        <button class="boton boton--peligro boton--chico" type="submit" onclick="return confirm('¿Eliminar este ingrediente y sus sinónimos? Solo se eliminará si no está en uso.');">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
