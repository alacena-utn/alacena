<?php

/**
 * Arranque común de la aplicación: autoload, configuración y funciones de ayuda.
 * Cada página empieza con:  require dirname(__DIR__) . '/src/bootstrap.php';
 * No depende de Composer para funcionar en producción (hosting sin consola).
 */

declare(strict_types=1);

spl_autoload_register(function (string $clase): void {
    $prefijo = 'Alacena\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $relativa = str_replace('\\', '/', substr($clase, strlen($prefijo)));
    $archivo = __DIR__ . '/' . $relativa . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

require_once __DIR__ . '/ayudas.php';

\Alacena\Config::cargar(dirname(__DIR__) . '/.env');

date_default_timezone_set('America/Argentina/Buenos_Aires');
