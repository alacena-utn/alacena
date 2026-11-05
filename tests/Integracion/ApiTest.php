<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Pruebas de la API por HTTP, de punta a punta (PHP → base de datos → JSON). */
final class ApiTest extends TestCase
{
    private string $email = '';

    protected function tearDown(): void
    {
        if ($this->email !== '') {
            Db::conexion()->prepare('DELETE FROM usuario WHERE email = ?')->execute([$this->email]);
            Db::conexion()->prepare('DELETE FROM intento_ingreso WHERE email = ?')->execute([$this->email]);
        }
    }

    private function conBase(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

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

    public function testRegistroIngresoYSalida(): void
    {
        $this->conBase();
        $this->email = 'api-' . uniqid() . '@cooki.test';
        $api = new ClienteApi();
        ClienteApi::iniciar();

        [$estado, $datos] = $api->pedir('POST', '/usuarios', ['nombre' => 'A', 'email' => 'mal', 'contrasena' => '1']);
        $this->assertSame(422, $estado);
        $this->assertArrayHasKey('email', $datos['campos']);

        $cuenta = ['nombre' => 'Api', 'email' => $this->email, 'contrasena' => 'Clave1234'];
        [$estado, $datos] = $api->pedir('POST', '/usuarios', $cuenta);
        $this->assertSame(201, $estado);
        $this->assertSame($this->email, $datos['usuario']['email']);
        $this->assertSame(409, (new ClienteApi())->pedir('POST', '/usuarios', $cuenta)[0]);

        [$estado, $datos] = $api->pedir('GET', '/yo');
        $this->assertSame(200, $estado);
        $this->assertSame('Api', $datos['usuario']['nombre']);
        $this->assertSame(204, $api->pedir('DELETE', '/sesion')[0]);
        $this->assertSame(401, $api->pedir('GET', '/yo')[0]);

        $this->assertSame(401, $api->pedir('POST', '/sesion', ['email' => $this->email, 'contrasena' => 'otra'])[0]);
        [$estado, $datos] = $api->pedir('POST', '/sesion', ['email' => $this->email, 'contrasena' => 'Clave1234']);
        $this->assertSame(200, $estado);
        $this->assertSame('general', $datos['usuario']['rol']);
    }

    public function testElIngresoPorLaApiTambienSeBloquea(): void
    {
        $this->conBase();
        $this->email = 'api-bloqueo-' . uniqid() . '@cooki.test';
        $api = ClienteApi::iniciar();
        for ($i = 0; $i < 5; $i++) {
            $api->pedir('POST', '/sesion', ['email' => $this->email, 'contrasena' => 'mal']);
        }
        [$estado, $datos] = $api->pedir('POST', '/sesion', ['email' => $this->email, 'contrasena' => 'mal']);
        $this->assertSame(429, $estado);
        $this->assertStringContainsString('Demasiados intentos', $datos['error']);
    }

    public function testRecetasPaginadasYUnaRecetaEscalada(): void
    {
        $this->conBase();
        [$estado, $datos] = ClienteApi::iniciar()->pedir('GET', '/recetas?por_pagina=5&pagina=2');
        $this->assertSame(200, $estado);
        $this->assertCount(5, $datos['recetas']);
        $this->assertSame(2, $datos['paginacion']['pagina']);

        [$estado, $datos] = ClienteApi::iniciar()->pedir('GET', '/recetas/1?porciones=2');
        $this->assertSame(200, $estado);
        $this->assertSame('Tortilla de papas', $datos['titulo']);
        $papa = array_values(array_filter($datos['ingredientes'], fn ($i) => $i['nombre'] === 'Papa'))[0];
        $this->assertEquals(300, $papa['cantidad'], 'La mitad de 600 g');
        $this->assertSame(900, $datos['pasos'][1]['segundos']);
        $this->assertSame(404, ClienteApi::iniciar()->pedir('GET', '/recetas/999999')[0]);
    }

    public function testRecomendacionesDespensaYFavoritasPidenSesion(): void
    {
        $this->conBase();
        $api = new ClienteApi();
        ClienteApi::iniciar();
        $this->assertSame(401, $api->pedir('GET', '/recomendaciones')[0]);
        $this->assertSame(401, $api->pedir('POST', '/favoritos/2')[0]);

        $this->email = 'api-fav-' . uniqid() . '@cooki.test';
        $api->pedir('POST', '/usuarios', ['nombre' => 'Fav', 'email' => $this->email, 'contrasena' => 'Clave1234']);
        [$estado, $datos] = $api->pedir('GET', '/despensa');
        $this->assertSame(200, $estado);
        $this->assertSame([], $datos['despensa']);
        $this->assertSame(200, $api->pedir('GET', '/recomendaciones')[0]);

        [$estado, $datos] = $api->pedir('POST', '/favoritos/2');
        $this->assertSame(200, $estado);
        $this->assertTrue($datos['favorita']);
        $this->assertTrue($api->pedir('GET', '/recetas/2')[1]['favorita']);
        $this->assertFalse($api->pedir('DELETE', '/favoritos/2')[1]['favorita']);
        $this->assertFalse($api->pedir('GET', '/recetas/2')[1]['favorita']);
    }
}
