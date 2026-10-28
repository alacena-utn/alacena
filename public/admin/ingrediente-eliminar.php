<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\IngredienteAdminRepositorio;
use Alacena\Sesion;

Sesion::requiereAdmin();
if (!esPost()) {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}
Sesion::verificarCsrf();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(400);
    exit('ID de ingrediente inválido.');
}
try {
    $repo = new IngredienteAdminRepositorio(Db::conexion());
    $repo->borrar($id);
    Sesion::avisar('exito', 'Ingrediente eliminado.');
} catch (\DomainException $error) {
    Sesion::avisar('error', $error->getMessage());
} catch (\PDOException $error) {
    Sesion::avisar('error', 'No se pudo eliminar el ingrediente. Intentá nuevamente más tarde.');
}
header('Location: ingredientes.php');
exit;
