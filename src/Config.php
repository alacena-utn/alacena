<?php

declare(strict_types=1);

namespace Alacena;

/**
 * Lee la configuración desde variables de entorno o desde el archivo .env.
 * Las variables de entorno reales (Docker, hosting) tienen prioridad sobre el archivo.
 */
final class Config
{
    /** @var array<string, string> */
    private static array $valores = [];

    public static function cargar(string $rutaEnv): void
    {
        if (!is_file($rutaEnv)) {
            return;
        }
        foreach (file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            self::$valores[$clave] = trim($valor, "\"'");
        }
    }

    public static function get(string $clave, string $porDefecto = ''): string
    {
        $env = getenv($clave);
        if ($env !== false && $env !== '') {
            return $env;
        }
        return self::$valores[$clave] ?? $porDefecto;
    }

    public static function esProduccion(): bool
    {
        return self::get('APP_ENV', 'desarrollo') === 'produccion';
    }
}
