<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

use Alacena\Controladores\UsuarioControlador;
use Alacena\Repositorios\UsuarioRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();
$controlador = new UsuarioControlador();
$nombre = '';
$email = '';
$rol = 'general';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sesion::verificarCsrf();
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = trim($_POST['rol'] ?? '');

    $errores = UsuarioRepositorio::validar($nombre, $email, $password);
    if (!in_array($rol, ['general', 'admin'], true)) {
        $errores['rol'] = 'Selecciona un rol valido.';
    }
    $repo = new UsuarioRepositorio(\Alacena\Db::conexion());
    if ($email !== '' && $repo->existeEmail($email)) {
        $errores['email'] = 'Este correo ya está registrado.';
    }
    if (empty($errores)) {
        try {
            $controlador->crear($nombre, $email, $password, $rol);
            header('Location: usuarios.php');
            exit;
        } catch (\PDOException $error) {
            $errores['general'] = 'No se pudo crear el usuario. Revisá los datos e intentá nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear usuario - Alacena</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<main class="contenido">
    <header class="encabezado-pagina">
        <h1>Crear usuario</h1>
        <a class="boton boton--secundario" href="usuarios.php">Volver a usuarios</a>
    </header>
    <?php if (!empty($errores)): ?>
        <ul class="aviso aviso--error" role="alert">
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <section class="tarjeta tarjeta--angosta" aria-labelledby="datos-usuario">
        <h2 id="datos-usuario">Crear nuevo usuario</h2>
        <form method="POST" class="formulario">
            <?= Sesion::campoCsrf(); ?>
            <div class="campo">
                <label for="nombre">Nombre:</label>
                <input id="nombre" type="text" name="nombre" minlength="2" maxlength="80" value="<?= e($nombre) ?>" required>
            </div>
            <div class="campo">
                <label for="email">Correo electrónico:</label>
                <input id="email" type="email" name="email" maxlength="190" value="<?= e($email) ?>" required>
            </div>
            <div class="campo">
                <label for="password">Contraseña:</label>
                <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="ayuda-password" required>
                <small id="ayuda-password">Al menos 8 caracteres, con letras y números.</small>
            </div>
            <div class="campo">
                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="general" <?= $rol === 'general' ? 'selected' : '' ?>>General</option>
                    <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="acciones">
                <button type="submit" class="boton">Crear usuario</button>
                <a class="boton boton--secundario" href="usuarios.php">Cancelar</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
