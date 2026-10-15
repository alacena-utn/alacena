<?php

declare(strict_types=1);

namespace Alacena\Tests\Dominio;

use Alacena\Dominio\Puntaje;
use PHPUnit\Framework\TestCase;

/** Prueba de ejemplo: copiar este estilo (un caso por método, nombre que explica el caso). */
final class PuntajeTest extends TestCase
{
    public function testCoberturaEs100SiTengoTodo(): void
    {
        $this->assertSame(100, Puntaje::cobertura([1, 2, 3], [1, 2, 3, 9]));
    }

    public function testCoberturaEs75SiTengoTresDeCuatro(): void
    {
        $this->assertSame(75, Puntaje::cobertura([1, 2, 3, 4], [1, 2, 3]));
    }

    public function testRecetaSinIngredientesDaCero(): void
    {
        $this->assertSame(0, Puntaje::cobertura([], [1, 2]));
    }
}
