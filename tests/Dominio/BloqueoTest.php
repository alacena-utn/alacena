<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Bloqueo;
use PHPUnit\Framework\TestCase;

/** Bloqueo por intentos de ingreso: 5 errores en 15 minutos → 15 minutos sin poder intentar. */
final class BloqueoTest extends TestCase
{
    private const AHORA = 1_800_000_000;

    public function testConMenosDeCincoErroresNoSeBloquea(): void
    {
        $fallos = [self::AHORA - 60, self::AHORA - 50, self::AHORA - 40, self::AHORA - 30];
        $this->assertSame(0, Bloqueo::minutosRestantes($fallos, self::AHORA));
        $this->assertSame(1, Bloqueo::intentosRestantes($fallos, self::AHORA));
    }

    public function testElQuintoErrorBloqueaQuinceMinutos(): void
    {
        $fallos = array_fill(0, 5, self::AHORA);
        $this->assertSame(15, Bloqueo::minutosRestantes($fallos, self::AHORA));
        $this->assertSame(5, Bloqueo::minutosRestantes($fallos, self::AHORA + 10 * 60));
        $this->assertSame(0, Bloqueo::minutosRestantes($fallos, self::AHORA + 15 * 60));
    }

    public function testLosErroresViejosNoCuentan(): void
    {
        $fallos = [self::AHORA - 3600, self::AHORA - 3000, self::AHORA - 120, self::AHORA - 60, self::AHORA - 30];
        $this->assertSame(0, Bloqueo::minutosRestantes($fallos, self::AHORA));
    }
}
