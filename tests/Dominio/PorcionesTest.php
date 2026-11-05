<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Porciones;
use PHPUnit\Framework\TestCase;

/** Escalar porciones: multiplicar, redondear como en la cocina y convertir unidades. */
final class PorcionesTest extends TestCase
{
    public function testElFactorRespetaElMinimoYElMaximo(): void
    {
        $this->assertSame(0.5, Porciones::factor(4, 2));
        $this->assertSame(3.0, Porciones::factor(4, 12));
        $this->assertSame(3.0, Porciones::factor(4, 99), 'Más de 12 porciones no');
        $this->assertSame(0.25, Porciones::factor(4, 0), 'Menos de 1 porción no');
    }

    public function testGramosYMililitrosSeRedondeanYPasanAKilosYLitros(): void
    {
        $this->assertSame('600 g', Porciones::texto(600, 'g'));
        $this->assertSame('1,2 kg', Porciones::texto(600, 'g', 2));
        $this->assertSame('150 ml', Porciones::texto(200, 'ml', 0.75));
        $this->assertSame('1,5 l', Porciones::texto(500, 'ml', 3));
        $this->assertSame('35 g', Porciones::texto(33, 'g'));
    }

    public function testUnidadesYCucharadasVanEnFracciones(): void
    {
        $this->assertSame('3 unidades', Porciones::texto(5, 'u', 0.6));
        $this->assertSame('1 ½ cda', Porciones::texto(1, 'cda', 1.5));
        $this->assertSame('½ unidad', Porciones::texto(1, 'u', 0.5));
        $this->assertSame('¼ unidad', Porciones::texto(1, 'u', 0.1), 'Nunca da cero');
        $this->assertSame('una pizca', Porciones::texto(1, 'pizca', 2), 'La pizca no se multiplica');
    }

    public function testSinCantidadQuedaAGusto(): void
    {
        $this->assertSame('a gusto', Porciones::texto(null, ''));
        $this->assertSame('a gusto', Porciones::texto(null, null, 2));
        $this->assertSame('una pizca', Porciones::texto(null, 'pizca'));
        $this->assertSame('Salar con sal (a gusto).', Porciones::pasoConCantidades(
            'Salar con {ing:sal}.',
            ['sal' => ['nombre' => 'Sal', 'cantidad' => null, 'unidad' => '']]
        ));
    }

    public function testLosMarcadoresDeLosPasosMuestranLaCantidadEscalada(): void
    {
        $ingredientes = [
            'papa' => ['nombre' => 'Papa', 'cantidad' => 600.0, 'unidad' => 'g'],
            'huevo' => ['nombre' => 'Huevo', 'cantidad' => 5.0, 'unidad' => 'u'],
        ];
        $this->assertSame(
            'Cortá la papa (1,2 kg) y batí el huevo (10).',
            Porciones::pasoConCantidades('Cortá la {ing:papa} y batí el {ing:huevo}.', $ingredientes, 2)
        );
        $this->assertSame('Agregá el limon', Porciones::pasoConCantidades('Agregá el {ing:limon}', $ingredientes));
    }

    public function testElSlugEsElDeLosDibujos(): void
    {
        $this->assertSame('cebolla-de-verdeo', Porciones::slug('Cebolla de verdeo'));
        $this->assertSame('champinon', Porciones::slug('Champiñón'));
    }
}
