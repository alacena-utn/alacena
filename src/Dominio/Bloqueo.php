<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Bloqueo por intentos de ingreso: 5 contraseñas mal en 15 minutos para el mismo email bloquean ese email
 * durante 15 minutos. Mientras está bloqueado no se prueban contraseñas (ni siquiera la correcta), así que
 * nadie puede adivinarla probando miles de combinaciones.
 *
 * Clase sin base de datos: recibe los momentos (timestamps) de los intentos fallidos y la hora actual.
 */
final class Bloqueo
{
    public const INTENTOS = 5;
    public const MINUTOS = 15;

    /**
     * Minutos que faltan para poder volver a intentar (0 = no está bloqueado).
     * @param list<int> $fallos momentos de los intentos fallidos (cualquier orden)
     */
    public static function minutosRestantes(array $fallos, int $ahora): int
    {
        $ventana = self::MINUTOS * 60;
        $recientes = array_filter($fallos, fn (int $momento) => $momento > $ahora - $ventana);
        if (count($recientes) < self::INTENTOS) {
            return 0;
        }
        $hasta = max($recientes) + $ventana;
        return max(1, (int) ceil(($hasta - $ahora) / 60));
    }

    /** Cuántos intentos le quedan antes del bloqueo (para avisar "te queda 1 intento"). */
    public static function intentosRestantes(array $fallos, int $ahora): int
    {
        $recientes = array_filter($fallos, fn (int $momento) => $momento > $ahora - self::MINUTOS * 60);
        return max(0, self::INTENTOS - count($recientes));
    }
}
