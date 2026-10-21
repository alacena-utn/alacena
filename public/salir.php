<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Sesion;

Sesion::salir();
Sesion::avisar('info', 'Cerraste la sesión.');
redirigir('index.php');
