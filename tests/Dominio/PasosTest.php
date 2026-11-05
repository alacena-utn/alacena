<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Pasos;
use PHPUnit\Framework\TestCase;

/** Pasos de receta como texto, con temporizador "[⏱ 15 min]" (el ABM de recetas los edita así). */
final class PasosTest extends TestCase
{
    public function testLeeUnPasoPorRenglonConTemporizador(): void
    {
        $pasos = Pasos::desdeTexto("Pelar las papas.\n\n  Hervirlas [⏱ 20 min]\r\nServir [5 min]");
        $this->assertSame([
            ['texto' => 'Pelar las papas.', 'segundos' => null],
            ['texto' => 'Hervirlas', 'segundos' => 1200],
            ['texto' => 'Servir', 'segundos' => 300],
        ], $pasos);
    }

    public function testIdaYVueltaNoPierdeLosTiempos(): void
    {
        $texto = Pasos::aTexto([
            ['instruccion' => 'Hervir', 'segundos_timer' => 600],
            ['instruccion' => 'Servir', 'segundos_timer' => null],
        ]);
        $this->assertSame("Hervir [⏱ 10 min]\nServir", $texto);
        $this->assertSame(600, Pasos::desdeTexto($texto)[0]['segundos']);
    }
}
