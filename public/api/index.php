<?php

/**
 * Punto de entrada único de la API: todas las peticiones a /api/... llegan acá
 * (ver public/.htaccess). Acá solo se registran las rutas; la lógica va en src/.
 */

declare(strict_types=1);

use Cooki\Config;
use Cooki\Controladores\IngredientesControlador;
use Cooki\Controladores\SaludControlador;
use Cooki\Http\ErrorHttp;
use Cooki\Http\Respuesta;
use Cooki\Http\Router;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$router = new Router();

$router->get('/salud', fn () => SaludControlador::ver());
$router->get('/ingredientes', fn () => IngredientesControlador::buscar());
// P1: $router->post('/sesion', ...)  P2: $router->get('/despensa', ...)  P3: $router->get('/recomendaciones', ...)

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // Las operaciones que modifican datos solo aceptan JSON: un formulario de otro sitio
    // no puede enviar JSON sin permiso, así que esto también protege contra CSRF.
    $modifica = in_array($metodo, ['POST', 'PATCH', 'PUT', 'DELETE'], true);
    if ($modifica && !str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        throw new ErrorHttp('Las peticiones deben enviarse como JSON', 415);
    }
    $router->despachar($metodo, Router::rutaDesdeUri($_SERVER['REQUEST_URI'] ?? '/'));
} catch (ErrorHttp $e) {
    Respuesta::error($e->getMessage(), $e->getCode());
} catch (Throwable $e) {
    error_log((string) $e);
    $detalle = Config::esProduccion() ? 'Error interno' : $e->getMessage();
    Respuesta::error($detalle, 500);
}
