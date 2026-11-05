<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use Cooki\Repositorios\RecetaRepositorio;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Incremento 2 · P3: la recomendación con puntaje, los grupos, las sustituciones y las porciones,
 * con la despensa de María de seed.sql (papa, huevo, cebolla, leche que vence, zapallito que vence…).
 */
final class RecomendacionTest extends TestCase
{
    private const MARIA = 3;

    protected function setUp(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

    /** @return array<string, array<string, mixed>> por título */
    private function recomendadas(): array
    {
        $lista = (new RecetaRepositorio(Db::conexion()))->recomendadas(self::MARIA);
        return array_column($lista, null, 'titulo');
    }

    public function testAgrupaYOrdenaPorPuntaje(): void
    {
        $lista = (new RecetaRepositorio(Db::conexion()))->recomendadas(self::MARIA);
        $this->assertNotEmpty($lista);
        $puntajes = array_column($lista, 'puntaje');
        $ordenados = $puntajes;
        rsort($ordenados);
        $this->assertSame($ordenados, $puntajes, 'primero las de más puntaje');
        foreach ($lista as $receta) {
            $this->assertContains($receta['grupo'], ['ahora', 'falta1', 'casi']);
        }
        $porTitulo = array_column($lista, null, 'titulo');
        $this->assertSame('ahora', $porTitulo['Tortilla de papas']['grupo']);
        $this->assertArrayNotHasKey('Locro sencillo', $porTitulo, 'no tiene ni la mitad de lo principal');
    }

    public function testPrioridadALoQueVenceYSustituciones(): void
    {
        $recetas = $this->recomendadas();
        $revuelto = $recetas['Revuelto de zapallitos'];
        $this->assertContains('Zapallito', $revuelto['usa_lo_que_vence']);
        $this->assertGreaterThan(
            $recetas['Tortilla de papas']['puntaje'],
            $revuelto['puntaje'],
            'a igual cobertura, gana la que usa lo que vence'
        );
        // No tiene crema, pero tiene leche: es un sustituto
        $this->assertSame('Crema de leche', $recetas['Fideos a la crema con champiñones']['sustituciones'][0]['falta']);
    }

    public function testMiCocinaYElListadoMuestranLaRecomendacion(): void
    {
        $web = new Navegador();
        $web->ingresar('maria@cooki.test', 'Usuario1234');
        [, $html] = $web->get('app/index.php');
        $this->assertStringContainsString('Podés hacerla ahora', $html);
        $this->assertStringContainsString('Usa lo que vence: zapallito', $html);

        [$estado, $html] = $web->get('app/recetas.php?orden=recomendadas');
        $this->assertSame(200, $estado);
        // Ordenadas por puntaje: el revuelto (todo + lo que vence) en la primera página; los fideos a la crema
        // (con un sustituto) más atrás
        $this->assertStringContainsString('Revuelto de zapallitos', $html);
        $this->assertStringNotContainsString('Fideos a la crema con champiñones', $html, 'ordenadas por puntaje');
        $encontrada = false;
        for ($pagina = 2; $pagina <= 6 && !$encontrada; $pagina++) {
            [, $siguiente] = $web->get('app/recetas.php?orden=recomendadas&pagina=' . $pagina);
            $encontrada = str_contains($siguiente, 'Fideos a la crema con champiñones');
        }
        $this->assertTrue($encontrada);
    }

    public function testElDetalleEscalaLasPorcionesYLosPasos(): void
    {
        $id = (int) Db::conexion()->query("SELECT id FROM receta WHERE slug = 'guiso-de-arroz'")->fetchColumn();
        $web = new Navegador();
        $web->ingresar('maria@cooki.test', 'Usuario1234');
        [, $html] = $web->get("app/receta.php?id={$id}");
        $this->assertStringContainsString('sumar arroz (300 g)', $html, 'el marcador {ing:arroz} con la cantidad');

        [, $html] = $web->get("app/receta.php?id={$id}&porciones=8");
        $this->assertStringContainsString('<strong>8</strong> porciones', $html);
        $this->assertStringContainsString('sumar arroz (600 g)', $html);
        $this->assertStringContainsString('Volver a 4', $html);

        [, $html] = $web->get("app/receta.php?id={$id}&porciones=99");
        $this->assertStringContainsString('<strong>12</strong> porciones', $html, 'no pasa del máximo');
    }
}
