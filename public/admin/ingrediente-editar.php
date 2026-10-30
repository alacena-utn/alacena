<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Controladores\IngredienteAdminControlador;
use Alacena\Db;
use Alacena\Repositorios\IngredienteAdminRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();
if (esPost()) {
    Sesion::verificarCsrf();
}
$id = 0;
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(400);
        exit('ID de ingrediente inválido.');
    }
}
$datos = ['nombre' => '', 'categoria_id' => '', 'unidad_default' => 'u', 'icono' => '',
    'gramos_por_unidad' => '', 'vida_util_dias' => '', 'es_basico' => 0, 'perecedero' => 1];
$errores = [];
$categorias = [];
$disponible = false;
try {
    $repo = new IngredienteAdminRepositorio(Db::conexion());
    if ($id > 0) {
        $ingrediente = $repo->obtener($id);
        if ($ingrediente === null) {
            http_response_code(404);
            exit('Ingrediente no encontrado.');
        }
        foreach ($datos as $campo => $valor) {
            $datos[$campo] = $ingrediente[$campo] ?? '';
        }
    }
    $categorias = $repo->categorias();
    $disponible = $categorias !== [];
    if (!$disponible) {
        $errores['general'] = 'No hay categorías disponibles. Consultá al equipo antes de cargar ingredientes.';
    }
    if (esPost()) {
        $datos = IngredienteAdminControlador::datos($_POST);
        $controlador = new IngredienteAdminControlador($repo);
        $errores = array_replace($errores, $controlador->guardar($id, $datos, $categorias));
        if ($errores === []) {
            Sesion::avisar('exito', $id > 0 ? 'Ingrediente actualizado.' : 'Ingrediente creado.');
            header('Location: ingredientes.php');
            exit;
        }
    }
} catch (\DomainException $error) {
    $errores['general'] = $error->getMessage();
} catch (\PDOException $error) {
    $errores['general'] = match ((int) ($error->errorInfo[1] ?? 0)) {
        1062 => 'Ya existe un ingrediente con ese nombre.',
        1452 => 'La categoría seleccionada ya no está disponible. Volvé a abrir el formulario.',
        default => 'No se pudo completar la operación. Comprobá la conexión e intentá nuevamente.',
    };
}
$titulo = $id > 0 ? 'Editar ingrediente' : 'Crear ingrediente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - Alacena</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<main class="contenido">
    <header class="encabezado-pagina">
        <h1><?= e($titulo) ?></h1>
        <a class="boton boton--secundario" href="ingredientes.php">Volver a ingredientes</a>
    </header>
    <?php if ($errores !== []): ?>
        <ul class="aviso aviso--error" role="alert">
            <?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <section class="tarjeta tarjeta--angosta" aria-labelledby="datos-ingrediente">
        <h2 id="datos-ingrediente">Datos del ingrediente</h2>
        <form method="post" class="formulario">
            <?= Sesion::campoCsrf() ?>
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" type="text" minlength="2" maxlength="80" value="<?= e($datos['nombre']) ?>" required>
            </div>
            <div class="fila-campos">
                <div class="campo">
                    <label for="categoria_id">Categoría</label>
                    <select id="categoria_id" name="categoria_id" required>
                        <option value="">Seleccioná una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id'] ?>" <?= (string) $datos['categoria_id'] === (string) $categoria['id'] ? 'selected' : '' ?>><?= e($categoria['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="unidad_default">Unidad predeterminada</label>
                    <input id="unidad_default" name="unidad_default" type="text" maxlength="10" value="<?= e($datos['unidad_default']) ?>" placeholder="g, ml o u" required>
                </div>
            </div>
            <div class="fila-campos">
                <div class="campo">
                    <label for="gramos_por_unidad">Gramos por unidad (opcional)</label>
                    <input id="gramos_por_unidad" name="gramos_por_unidad" type="number" min="0.01" max="999999.99" step="0.01" value="<?= e($datos['gramos_por_unidad']) ?>">
                </div>
                <div class="campo">
                    <label for="vida_util_dias">Vida útil en días (opcional)</label>
                    <input id="vida_util_dias" name="vida_util_dias" type="number" min="1" max="32767" step="1" value="<?= e($datos['vida_util_dias']) ?>">
                </div>
            </div>
            <div class="campo">
                <label for="icono">Clave del ícono (opcional)</label>
                <input id="icono" name="icono" type="text" maxlength="60" value="<?= e($datos['icono']) ?>">
            </div>
            <div class="casillas">
                <label><input type="checkbox" name="es_basico" value="1" <?= $datos['es_basico'] ? 'checked' : '' ?>> Es un ingrediente básico</label>
                <label><input type="checkbox" name="perecedero" value="1" <?= $datos['perecedero'] ? 'checked' : '' ?>> Es perecedero</label>
            </div>
            <div class="acciones">
                <button class="boton" type="submit" <?= !$disponible ? 'disabled' : '' ?>>Guardar ingrediente</button>
                <a class="boton boton--secundario" href="ingredientes.php">Cancelar</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
