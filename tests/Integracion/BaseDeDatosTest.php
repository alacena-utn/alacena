<?php

declare(strict_types=1);

namespace Alacena\Tests\Integracion;

use Alacena\Db;
use Alacena\Repositorios\IngredienteRepositorio;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas contra una base real con schema.sql + seed.sql cargados.
 * Si no hay base disponible, se saltean (no fallan) para no trabar a quien no la tenga levantada.
 */
final class BaseDeDatosTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

    public function testBuscarIgnoraTildes(): void
    {
        $repo = new IngredienteRepositorio(Db::conexion());
        $nombres = array_column($repo->buscar('morron'), 'nombre');
        $this->assertContains('Morrón', $nombres);
    }

    public function testBuscarEncuentraPorSinonimo(): void
    {
        $repo = new IngredienteRepositorio(Db::conexion());
        $nombres = array_column($repo->buscar('aguacate'), 'nombre');
        $this->assertContains('Palta', $nombres);
    }

    public function testHayAdministradoresYUsuariosGenerales(): void
    {
        $roles = Db::conexion()->query('SELECT DISTINCT rol FROM usuario')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains('admin', $roles);
        $this->assertContains('general', $roles);
    }

    public function testLasContrasenasEstanEncriptadas(): void
    {
        $hashes = Db::conexion()->query('SELECT hash_contrasena FROM usuario')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertNotEmpty($hashes, 'La tabla usuario está vacía: ¿se cargó seed.sql?');
        foreach ($hashes as $hash) {
            $this->assertNotSame('unknown', password_get_info($hash)['algoName'], 'Contraseña sin hash');
        }
    }

    public function testHayFrutasVerdurasYOtrosIngredientesNuevos(): void
    {
        $db = Db::conexion();
        $this->assertGreaterThanOrEqual(76, (int) $db->query('SELECT COUNT(*) FROM ingrediente')->fetchColumn());
        $this->assertSame('Pollo', $db->query('SELECT nombre FROM ingrediente WHERE id = 14')->fetchColumn());
        $nombres = array_column((new IngredienteRepositorio($db))->buscar('piña'), 'nombre');
        $this->assertContains('Ananá', $nombres, 'Se encuentra por sinónimo');
    }

    public function testCadaIngredienteTieneSuPropioDibujo(): void
    {
        // Solo la lista general: los ingredientes propios de cada usuario llevan el frasco a propósito
        $nombres = Db::conexion()
            ->query('SELECT nombre FROM ingrediente WHERE usuario_id IS NULL')->fetchAll(\PDO::FETCH_COLUMN);
        $dibujos = [];
        foreach ($nombres as $nombre) {
            $dibujo = basename(iconoIngrediente($nombre));
            $this->assertNotSame('frasco.svg', $dibujo, "{$nombre} no tiene dibujo propio");
            $dibujos[$dibujo][] = $nombre;
        }
        $repetidos = array_filter($dibujos, fn ($lista) => count($lista) > 1);
        $this->assertSame([], $repetidos, 'Hay ingredientes que comparten dibujo');
    }
}
