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
        foreach ($hashes as $hash) {
            $this->assertNotSame('unknown', password_get_info($hash)['algoName'], 'Contraseña sin hash');
        }
    }
}
