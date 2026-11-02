<?php

/**
 * Cerrar la sesión. Solo por POST (con el token CSRF): así otra página no puede sacarte con un enlace.
 * El botón "Salir" del menú de la cuenta pregunta antes "¿Seguro?" (sitio.js).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Sesion;

if (!esPost()) {
    redirigir(Sesion::estaLogueado() ? 'app/index.php' : 'index.php');
}
Sesion::verificarCsrf();
Sesion::salir();
Sesion::avisar('info', 'Cerraste la sesión.');
redirigir('index.php');
