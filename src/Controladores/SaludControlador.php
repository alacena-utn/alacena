<?php

declare(strict_types=1);

namespace Alacena\Controladores;

use Alacena\Db;
use Alacena\Http\Respuesta;
use Throwable;

/** GET /api/salud — indica si la API y la base de datos responden. */
final class SaludControlador
{
    public static function ver(): void
    {
        try {
            Db::conexion()->query('SELECT 1');
            $base = 'ok';
        } catch (Throwable) {
            $base = 'sin conexión';
        }
        Respuesta::json(['api' => 'ok', 'base_de_datos' => $base, 'php' => PHP_VERSION]);
    }
}
