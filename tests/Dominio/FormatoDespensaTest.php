<?php

declare(strict_types=1);

namespace Alacena\Tests\Dominio;

use Alacena\Repositorios\DespensaRepositorio;
use Alacena\Repositorios\RecetaRepositorio;
use PHPUnit\Framework\TestCase;

/** Funciones de formato que no necesitan la base de datos. */
final class FormatoDespensaTest extends TestCase
{
    public function testTextoDeLaCantidad(): void
    {
        $this->assertSame('6 unidades', DespensaRepositorio::textoCantidad('6.00', 'u'));
        $this->assertSame('1 unidad', DespensaRepositorio::textoCantidad(1, 'u'));
        $this->assertSame('500 g', DespensaRepositorio::textoCantidad('500.00', 'g'));
        $this->assertSame('1.000 ml', DespensaRepositorio::textoCantidad('1000.00', 'ml'));
        $this->assertSame('1,5 kg', DespensaRepositorio::textoCantidad(1.5, 'kg'));
        $this->assertSame('', DespensaRepositorio::textoCantidad(null, 'g'));
    }

    public function testIngredientesDeLaTarjeta(): void
    {
        $lista = RecetaRepositorio::ingredientesDeTarjeta("1Huevo\n0Tapa de tarta");
        $this->assertSame([
            ['nombre' => 'Huevo', 'tengo' => true],
            ['nombre' => 'Tapa de tarta', 'tengo' => false],
        ], $lista);
        $this->assertSame([], RecetaRepositorio::ingredientesDeTarjeta(null));
    }

    public function testResumenDeLaDespensa(): void
    {
        $items = [
            ['nivel' => 2, 'dias' => null],   // sin fecha → bien
            ['nivel' => 2, 'dias' => 10],     // bien
            ['nivel' => 1, 'dias' => 0],      // vence hoy → por vencer
            ['nivel' => 2, 'dias' => 3],      // por vencer
            ['nivel' => 2, 'dias' => -1],     // vencido
            ['nivel' => 0, 'dias' => -5],     // agotado (gana a vencido)
        ];
        $this->assertSame(
            ['bien' => 2, 'por_vencer' => 2, 'vencidos' => 1, 'agotados' => 1],
            DespensaRepositorio::resumen($items)
        );
    }
}
