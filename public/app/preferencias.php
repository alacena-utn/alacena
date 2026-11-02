<?php

/**
 * Guarda las preferencias de pantalla (menú de la cuenta, o "Ajustes" sin cuenta):
 *   POST color=verde|crema|celeste|rosa|noche        → color de la heladera
 *   POST disposicion=normal|panoramica               → disposición en la computadora
 *   POST volumen=0..100                              → volumen de los sonidos
 *   POST volver=/ruta/de/la/pagina                   → a dónde vuelve (solo rutas de este sitio)
 * Sin cuenta solo se puede elegir el color, y se guarda en una cookie (Sesion::recordarColor).
 * Con JavaScript se llama en segundo plano (el cambio se ve al instante sin recargar);
 * sin JavaScript, el formulario se envía y vuelve a la misma página.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Alacena\Db;
use Alacena\Repositorios\UsuarioRepositorio;
use Alacena\Sesion;

$inicio = Sesion::estaLogueado() ? 'app/index.php' : 'index.php';
if (!esPost()) {
    redirigir($inicio);
}
Sesion::verificarCsrf();

$guardado = false;
if (Sesion::estaLogueado()) {
    $repo = new UsuarioRepositorio(Db::conexion());
    foreach (['color', 'disposicion', 'volumen'] as $clave) {
        if (isset($_POST[$clave]) && $repo->guardarPreferencia(Sesion::idUsuario(), $clave, campo($clave))) {
            Sesion::cambiarPreferencia($clave, campo($clave));
            $guardado = true;
        }
    }
} elseif (isset($_POST['color'])) {
    $guardado = Sesion::recordarColor(campo('color'));
}

// Pedido desde JavaScript: alcanza con responder si se guardó
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
    http_response_code($guardado ? 204 : 400);
    exit;
}

// Volver a la página desde la que se eligió (solo rutas de este mismo sitio)
$volver = campo('volver');
if (!str_starts_with($volver, '/') || str_starts_with($volver, '//') || str_contains($volver, '\\')) {
    redirigir($inicio);
}
header('Location: ' . $volver);
exit;
