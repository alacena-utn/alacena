<?php

declare(strict_types=1);

// Usa el autoload de Composer si existe; si no, el propio del proyecto.
$composer = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
}
require dirname(__DIR__) . '/src/bootstrap.php';

// Clases de ayuda de las pruebas (Alacena\Tests\...) aunque no esté el autoload de Composer
spl_autoload_register(function (string $clase): void {
    $prefijo = 'Alacena\\Tests\\';
    if (str_starts_with($clase, $prefijo)) {
        $archivo = __DIR__ . '/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
        if (is_file($archivo)) {
            require $archivo;
        }
    }
});
