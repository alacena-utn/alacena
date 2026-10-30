<?php
declare(strict_types=1);

require_once dirname(__DIR__,2) . '/src/bootstrap.php';

use Alacena\Controladores\UsuarioControlador;
use Alacena\Sesion;

// Solo pueden ingresar administradores.
Sesion::requiereAdmin();

$controlador = new UsuarioControlador();

// Obtenemos el ID del usuario que queremos editar.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('ID de usuario inválido.');
}

// Buscamos los datos del usuario.
$usuario = $controlador->buscarPorId($id);

if ($usuario === null) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sesion::verificarCsrf();

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = trim($_POST['rol'] ?? '');

    $errores = \Alacena\Repositorios\UsuarioRepositorio::validar($nombre, $email, $password, false);

    if ($nombre === '' || mb_strlen($nombre) < 2) {
        $errores['nombre'] = 'Ingresá un nombre válido.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Ingresá un correo válido.';
    }

    if (!in_array($rol, ['general', 'admin'], true)) {
        $errores['rol'] = 'Seleccioná un rol válido.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    $repo = new \Alacena\Repositorios\UsuarioRepositorio(
    \Alacena\Db::conexion()
);

if ($email !== '' && $repo->existeEmail($email, $id)) {
    $errores['email'] = 'Este correo ya está registrado.';
}

    if (empty($errores)) {
        try {
        $controlador->actualizar($id, $nombre, $email, $rol, $password);

        header('Location: usuarios.php');
        exit;
        } catch (\RuntimeException $error) {
            $errores['general'] = $error instanceof \PDOException
                ? 'No se pudieron guardar los cambios. Revisá los datos e intentá nuevamente.'
                : $error->getMessage();
        }
    }
    $usuario = array_replace($usuario, ['nombre' => $nombre, 'email' => $email, 'rol' => $rol]);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar usuario - Alacena</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<main class="contenido">
    <header class="encabezado-pagina"><h1>Editar usuario</h1></header>
    <?php if ($errores !== []): ?>
        <ul class="aviso aviso--error" role="alert">
        <?php foreach ($errores as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <section class="tarjeta tarjeta--angosta">
    <h2>Datos del usuario</h2>

    <form method="POST" class="formulario">
        <?= Sesion::campoCsrf() ?>

        <div class="campo">
        <label for="nombre">Nombre:</label>
        <input id="nombre" type="text" name="nombre" minlength="2" maxlength="80"
               value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

        </div>
        <div class="campo">
        <label for="email">Correo electrónico:</label>
        <input id="email" type="email" name="email" maxlength="190"
               value="<?= htmlspecialchars($usuario['email']) ?>" required>

        </div>
        <div class="campo">
        <label for="password">Contraseña nueva (opcional):</label>
        <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="ayuda-password">
        <small id="ayuda-password">Dejá el campo vacío para conservar la contraseña. La nueva debe tener al menos 8 caracteres, con letras y números.</small>
        </div>
        <div class="campo">

        <label for="rol">Rol:</label>
        <select id="rol" name="rol" required>
            <option value="general" <?= $usuario['rol'] === 'general' ? 'selected' : '' ?>>General</option>
            <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>
        </div>

        <button class="boton" type="submit">Guardar cambios</button>
    </form>
    <p><a class="boton boton--secundario" href="usuarios.php">Volver a la lista de usuarios</a></p>
    </section>
</main>
</body>
</html>
