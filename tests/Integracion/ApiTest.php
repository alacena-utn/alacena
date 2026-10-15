<?php

declare(strict_types=1);

namespace Alacena\Tests\Integracion;

use PHPUnit\Framework\TestCase;

/** Pruebas de la API por HTTP, de punta a punta (PHP → base de datos → JSON). */
final class ApiTest extends TestCase
{
    public function testSaludRespondeOk(): void
    {
        [$estado, $datos] = ClienteApi::iniciar()->pedir('GET', '/salud');
        $this->assertSame(200, $estado);
        $this->assertSame('ok', $datos['api']);
    }

    public function testRutaInexistenteDevuelve404(): void
    {
        [$estado] = ClienteApi::iniciar()->pedir('GET', '/no-existe');
        $this->assertSame(404, $estado);
    }

    public function testBuscarConUnaLetraDevuelve400(): void
    {
        [$estado, $datos] = ClienteApi::iniciar()->pedir('GET', '/ingredientes?q=a');
        $this->assertSame(400, $estado);
        $this->assertArrayHasKey('error', $datos);
    }
}
