<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Descuento;
use PHPUnit\Framework\TestCase;

/** Cuánto queda en la despensa después de cocinar (modo cocina). */
final class DescuentoTest extends TestCase
{
    public function testConCantidadEnLaMismaUnidadSeResta(): void
    {
        $r = Descuento::aplicar(['nivel' => 2, 'cantidad' => 6, 'unidad' => 'u'], 5.0, 'u');
        $this->assertSame(['nivel' => 2, 'cantidad' => 1.0, 'unidad' => 'u', 'como' => 'cantidad'], $r);
    }

    public function testConvierteKilosYGramos(): void
    {
        $r = Descuento::aplicar(['nivel' => 3, 'cantidad' => '1.00', 'unidad' => 'kg'], 600.0, 'g');
        $this->assertSame(0.4, $r['cantidad']);
        $this->assertSame('kg', $r['unidad']);
        $r = Descuento::aplicar(['nivel' => 2, 'cantidad' => 500, 'unidad' => 'ml'], 0.25, 'l');
        $this->assertSame(250.0, $r['cantidad']);
    }

    public function testSiNoAlcanzaQuedaAgotado(): void
    {
        $r = Descuento::aplicar(['nivel' => 1, 'cantidad' => 2, 'unidad' => 'u'], 5.0, 'u');
        $this->assertSame(0, $r['nivel']);
        $this->assertNull($r['cantidad']);
    }

    public function testSinCantidadBajaUnNivel(): void
    {
        $sinCantidad = ['cantidad' => null, 'unidad' => null];
        $this->assertSame(1, Descuento::aplicar(['nivel' => 2] + $sinCantidad, 5.0, 'u')['nivel']);
        $this->assertSame(0, Descuento::aplicar(['nivel' => 1] + $sinCantidad, null, null)['nivel']);
    }

    public function testUnidadesQueNoSeComparanBajanUnNivel(): void
    {
        $r = Descuento::aplicar(['nivel' => 3, 'cantidad' => 500, 'unidad' => 'g'], 2.0, 'taza');
        $this->assertSame(['nivel' => 2, 'cantidad' => null, 'unidad' => null, 'como' => 'nivel'], $r);
    }

    public function testGramosYUnidadesSeComparanSiSeSabeCuantoPesaUna(): void
    {
        // 4 papas de 200 g; la receta usa 600 g → queda 1 papa
        $r = Descuento::aplicar(['nivel' => 2, 'cantidad' => 4, 'unidad' => 'u'], 600.0, 'g', 200.0);
        $this->assertSame(1.0, $r['cantidad']);
        $this->assertSame('u', $r['unidad']);
        // Sin el peso no se puede: baja un nivel
        $sinPeso = Descuento::aplicar(['nivel' => 2, 'cantidad' => 4, 'unidad' => 'u'], 600.0, 'g');
        $this->assertSame('nivel', $sinPeso['como']);
    }
}
