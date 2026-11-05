<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Paginacion;
use PHPUnit\Framework\TestCase;

final class PaginacionTest extends TestCase
{
    public function testCalculaPaginasYCorta(): void
    {
        $p = new Paginacion(62, 12, 2);
        $this->assertSame(6, $p->paginas);
        $this->assertSame(12, $p->desde());
        $this->assertSame(range(13, 24), $p->cortar(range(1, 62)));
        $this->assertSame('Mostrando 13 a 24 de 62', $p->texto());
    }

    public function testUnaPaginaQueNoExisteVaALaMasCercana(): void
    {
        $this->assertSame(6, (new Paginacion(62, 12, 999))->pagina);
        $this->assertSame(1, (new Paginacion(62, 12, -3))->pagina);
        $this->assertSame(1, (new Paginacion(0, 12, 4))->paginas);
        $this->assertSame([2], (new Paginacion(2, 12, 1))->cortar([2]));
        $this->assertSame('Sin resultados', (new Paginacion(0, 12, 1))->texto());
    }

    public function testNumerosConSaltos(): void
    {
        $this->assertSame([1, null, 4, 5, 6, null, 10], (new Paginacion(100, 10, 5))->numeros());
        $this->assertSame([1, 2, null, 10], (new Paginacion(100, 10, 1))->numeros());
        $this->assertSame([1, 2, 3], (new Paginacion(30, 10, 2))->numeros());
    }
}
