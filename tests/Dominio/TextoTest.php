<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Texto;
use PHPUnit\Framework\TestCase;

/** Sacar tildes igual en cualquier servidor (en el hosting la configuración regional es "C"). */
final class TextoTest extends TestCase
{
    public function testSacaLasTildesAunqueElServidorUseLaConfiguracionC(): void
    {
        $antes = setlocale(LC_CTYPE, '0');
        setlocale(LC_CTYPE, 'C');
        try {
            $this->assertSame('azucar', Texto::sinTildes('Azúcar'));
            $this->assertSame('champinon', Texto::sinTildes('Champiñón'));
            $this->assertSame('pure-de-tomate', Texto::slug('Puré de tomate'));
            $this->assertSame('atun-en-lata', Texto::slug('Atún en lata'));
            $this->assertSame('pinguino', Texto::slug('PINGÜINO'));
        } finally {
            setlocale(LC_CTYPE, (string) $antes);
        }
    }

    public function testElSlugDejaSoloLetrasNumerosYGuiones(): void
    {
        $this->assertSame('arroz-con-pollo-2', Texto::slug('  Arroz con pollo (2)! '));
        $this->assertSame('', Texto::slug('¡¿?!'));
    }
}
