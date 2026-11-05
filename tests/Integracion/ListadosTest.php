<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Paginación de los listados (incremento 2 · P3 + P5). */
final class ListadosTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

    public function testElCatalogoMuestraDeADoceYConservaLosFiltros(): void
    {
        $total = (int) Db::conexion()->query('SELECT COUNT(*) FROM receta')->fetchColumn();
        if ($total <= 12) {
            $this->markTestSkipped('Hacen falta más de 12 recetas (cargá database/recetas.sql)');
        }
        $web = new Navegador();
        $web->ingresar('maria@cooki.test', 'Usuario1234');
        [, $html] = $web->get('app/recetas.php');
        $this->assertSame(12, substr_count($html, 'class="tarjeta tarjeta-receta"'));
        $this->assertStringContainsString('aria-label="Páginas"', $html);
        $this->assertStringContainsString('Mostrando 1 a 12 de ' . $total, $html);

        $ultima = (int) ceil($total / 12);
        [, $html] = $web->get('app/recetas.php?pagina=' . $ultima);
        $this->assertSame($total - 12 * ($ultima - 1), substr_count($html, 'class="tarjeta tarjeta-receta"'));
        [, $html] = $web->get('app/recetas.php?pagina=9999');
        $actual = 'aria-current="page"><span class="solo-lectores">Página </span>' . $ultima;
        $this->assertStringContainsString($actual, $html);

        // Con un filtro, los enlaces de las páginas lo conservan
        [, $html] = $web->get('app/recetas.php?orden=rapidas');
        $this->assertStringContainsString('orden=rapidas&amp;pagina=2', $html);
        // Con pocos resultados no hay barra de páginas
        [, $html] = $web->get('app/recetas.php?q=tortilla');
        $this->assertStringNotContainsString('aria-label="Páginas"', $html);
    }

    public function testLosListadosDeAdministracionTambienSePaginan(): void
    {
        $admin = new Navegador();
        $admin->ingresar('admin@cooki.test', 'Admin1234');
        [, $html] = $admin->get('admin/ingredientes.php');
        $this->assertStringContainsString('Mostrando 1 a 30 de', $html);
        [$estado, $html] = $admin->get('admin/ingredientes.php?pagina=2');
        $this->assertSame(200, $estado);
        $this->assertStringContainsString('Mostrando 31 a', $html);
        [, $html] = $admin->get('admin/usuarios.php');
        $this->assertStringNotContainsString('aria-label="Páginas"', $html, 'Hay menos de 20 usuarios: no hace falta');
    }
}
