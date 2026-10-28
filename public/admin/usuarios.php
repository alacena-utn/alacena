<?php
declare(strict_types=1);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap.php';

use Alacena\Controladores\UsuarioControlador;
use Alacena\Sesion;

Sesion::requiereAdmin();
$controlador = new UsuarioControlador();
$usuarios = $controlador->index();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de usuarios - Alacena</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&amp;family=Figtree:wght@400;600;700;800&amp;family=Space+Mono:wght@700&amp;display=swap">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<main class="contenido">
    <header class="encabezado-pagina">
        <h1>Gestión de usuarios</h1>
        <nav class="acciones" aria-label="Administración">
            <a class="boton" href="usuario-crear.php">Crear usuario</a>
            <a class="boton boton--secundario" href="../index.php">Inicio</a>
            <a class="boton boton--secundario" href="ingredientes.php">Ingredientes</a>
        </nav>
    </header>
<section class="tarjeta" aria-labelledby="usuarios-registrados">
    <h2 id="usuarios-registrados">Usuarios registrados</h2>
    <div class="campo filtro-rapido">
    <label for="buscarUsuario">Buscar usuario</label>
    <input type="search" id="buscarUsuario" placeholder="Buscar usuario o correo">
</div>
<div class="tabla-contenedor">
    <table id="tablaUsuarios">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Correo electrónico</th>
                <th scope="col">Rol</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?=
                        htmlspecialchars($usuario['nombre']) ?></td>
                    <td><?=
                        htmlspecialchars($usuario['email']) ?></td>
                     <td><?=
                        htmlspecialchars($usuario['rol']) ?></td>
                      <td>
    <div class="acciones">
    <a class="boton boton--secundario boton--chico" href="usuario-editar.php?id=<?= (int) $usuario['id'] ?>">
        Editar
    </a>

    <form method="POST" action="usuario-eliminar.php">
        <?= Sesion::campoCsrf() ?>

        <input type="hidden" name="id"
               value="<?= (int) $usuario['id'] ?>">

        <button type="submit" class="boton boton--peligro boton--chico"
                onclick="return confirm('¿Seguro que querés eliminar este usuario?');">
            Eliminar
        </button>
    </form>
    </div>
</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            </div>
    <?php if ($usuarios === []): ?>
        <p class="aviso aviso--info">Todavía no hay usuarios registrados.</p>
    <?php endif; ?>
    </section>
    </main>

    <script src="../js/usuarios.js"></script>


</body>

</html>
