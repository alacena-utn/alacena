<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Controladores\UsuarioControlador;
use Alacena\Sesion;

Sesion::requiereAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

Sesion::verificarCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('ID de usuario inválido.');
}

$controlador = new UsuarioControlador();

try {
    $controlador->borrar($id);

    header('Location: usuarios.php');
    exit;
} catch (\Throwable $error) {
    http_response_code(400);
    exit('No se pudo eliminar el usuario: ' .
        htmlspecialchars($error->getMessage()));
}
