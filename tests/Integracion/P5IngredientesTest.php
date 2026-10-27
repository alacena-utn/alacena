<?php

declare(strict_types=1);

namespace Alacena\Tests\Integracion;

use Alacena\Controladores\IngredienteAdminControlador;
use Alacena\Repositorios\IngredienteAdminRepositorio;
use Alacena\Repositorios\IngredienteRepositorio;
use DomainException;
use PDO;
use PHPUnit\Framework\TestCase;

/** P5-B: prueba SQL en memoria sin tocar los datos reales de MySQL. */
final class P5IngredientesTest extends TestCase
{
    private PDO $db;
    private IngredienteAdminRepositorio $repo;
    private IngredienteAdminControlador $controlador;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('Estas pruebas aisladas requieren pdo_sqlite.');
        }
        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Equivalente SQLite de los campos y relaciones utilizados de database/schema.sql.
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->exec('CREATE TABLE categoria (id INTEGER PRIMARY KEY, nombre TEXT, orden INTEGER)');
        $this->db->exec("INSERT INTO categoria VALUES (1, 'Verduras', 1)");
        $this->db->exec('CREATE TABLE ingrediente (
            id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL UNIQUE,
            categoria_id INTEGER NOT NULL REFERENCES categoria(id), unidad_default TEXT NOT NULL,
            icono TEXT, gramos_por_unidad DECIMAL(8,2), es_basico INTEGER NOT NULL,
            perecedero INTEGER NOT NULL, vida_util_dias INTEGER)');
        $this->db->exec('CREATE TABLE sinonimo (id INTEGER PRIMARY KEY, texto TEXT,
            ingrediente_id INTEGER REFERENCES ingrediente(id) ON DELETE CASCADE)');
        foreach (['receta_ingrediente', 'despensa_item', 'usuario_basico', 'restriccion_usuario'] as $tabla) {
            $this->db->exec("CREATE TABLE {$tabla} (ingrediente_id INTEGER REFERENCES ingrediente(id))");
        }
        $this->db->exec('CREATE TABLE sustitucion (
            ingrediente_id INTEGER REFERENCES ingrediente(id), reemplazo_id INTEGER REFERENCES ingrediente(id))');
        $this->repo = new IngredienteAdminRepositorio($this->db);
        $this->controlador = new IngredienteAdminControlador($this->repo);
    }

    private function datos(array $cambios = []): array
    {
        return IngredienteAdminControlador::datos(array_replace([
            'nombre' => 'Zanahoria', 'categoria_id' => '1', 'unidad_default' => 'g',
            'perecedero' => '1',
        ], $cambios));
    }

    public function testCrearEditarListarYBuscarConSinonimos(): void
    {
        $datos = $this->datos(['gramos_por_unidad' => '125,50', 'vida_util_dias' => '12']);
        self::assertSame([], $this->controlador->guardar(0, $datos, $this->repo->categorias()));
        $id = (int) $this->db->lastInsertId();
        self::assertEquals(125.50, $this->repo->obtener($id)['gramos_por_unidad']);
        $lectura = new IngredienteRepositorio($this->db);
        self::assertCount(1, $lectura->todos());
        $this->db->prepare('INSERT INTO sinonimo (ingrediente_id, texto) VALUES (?, ?)')->execute([$id, 'Carrot']);
        self::assertCount(1, $lectura->buscar('Carrot'));
        self::assertSame(
            [],
            $this->controlador->guardar($id, $this->datos(['nombre' => 'Zanahoria fresca']), $this->repo->categorias())
        );
        self::assertSame('Zanahoria fresca', $this->repo->obtener($id)['nombre']);
        self::assertNull($this->repo->obtener($id)['gramos_por_unidad']);
        self::assertNull($this->repo->obtener($id)['vida_util_dias']);
        self::assertCount(0, $lectura->buscar('No existe'));
    }

    public function testDuplicadoNoInsertaNiImpideGuardarElMismoNombre(): void
    {
        $categorias = $this->repo->categorias();
        self::assertSame([], $this->controlador->guardar(0, $this->datos(), $categorias));
        $id = (int) $this->db->lastInsertId();
        self::assertArrayHasKey('nombre', $this->controlador->guardar(0, $this->datos(), $categorias));
        self::assertSame([], $this->controlador->guardar($id, $this->datos(), $categorias));
        self::assertSame(1, (int) $this->db->query('SELECT COUNT(*) FROM ingrediente')->fetchColumn());
    }

    public function testValidacionesImpidenDatosFueraDelEsquema(): void
    {
        foreach (
            [
            ['nombre', ''], ['nombre', str_repeat('x', 81)], ['categoria_id', '999'],
            ['categoria_id', '1 OR 1=1'], ['unidad_default', ''], ['unidad_default', str_repeat('x', 11)],
            ['icono', str_repeat('x', 61)], ['gramos_por_unidad', '-1'], ['gramos_por_unidad', '0'],
            ['gramos_por_unidad', '1000000'], ['gramos_por_unidad', '1.234'],
            ['vida_util_dias', '0'], ['vida_util_dias', '32768'], ['vida_util_dias', '1.5'],
            ] as [$campo, $valor]
        ) {
            self::assertArrayHasKey(
                $campo,
                $this->controlador->guardar(0, $this->datos([$campo => $valor]), $this->repo->categorias())
            );
        }
        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM ingrediente')->fetchColumn());
    }

    public function testLimitesValidosYDatosOpcionales(): void
    {
        $datos = $this->datos(['gramos_por_unidad' => '999999.99', 'vida_util_dias' => '32767', 'es_basico' => '1']);
        self::assertSame([], IngredienteAdminControlador::validar($datos, $this->repo->categorias()));
        self::assertSame(1, $datos['es_basico']);
        self::assertSame(0, $this->datos(['perecedero' => ''])['perecedero']);
        self::assertArrayHasKey('categoria_id', IngredienteAdminControlador::validar($datos, []));
    }

    public function testTodasLasRelacionesImpidenLaBaja(): void
    {
        $id = $this->repo->guardar(0, $this->datos());
        foreach (
            [
            ['receta_ingrediente', 'ingrediente_id'], ['despensa_item', 'ingrediente_id'],
            ['usuario_basico', 'ingrediente_id'], ['restriccion_usuario', 'ingrediente_id'],
            ['sustitucion', 'ingrediente_id'], ['sustitucion', 'reemplazo_id'],
            ] as [$tabla, $columna]
        ) {
            $this->db->prepare("INSERT INTO {$tabla} ({$columna}) VALUES (?)")->execute([$id]);
            try {
                $this->repo->borrar($id);
                self::fail('Se permitió borrar un ingrediente en uso.');
            } catch (DomainException $error) {
                self::assertStringContainsString('en uso', $error->getMessage());
            }
            self::assertNotNull($this->repo->obtener($id));
            $this->db->exec("DELETE FROM {$tabla}");
        }
    }

    public function testBajaSinReferenciasEliminaSoloIngredienteYSusSinonimos(): void
    {
        $id = $this->repo->guardar(0, $this->datos());
        $otro = $this->repo->guardar(0, $this->datos(['nombre' => 'Papa']));
        $this->db->prepare('INSERT INTO sinonimo (ingrediente_id, texto) VALUES (?, ?)')->execute([$id, 'Carrot']);
        $this->repo->borrar($id);
        self::assertNull($this->repo->obtener($id));
        self::assertNotNull($this->repo->obtener($otro));
        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM sinonimo')->fetchColumn());
    }

    public function testBajaDeIngredienteInexistente(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ya no existe');
        $this->repo->borrar(999);
    }

    public function testEdicionDeIngredienteInexistente(): void
    {
        $this->expectException(DomainException::class);
        $this->repo->guardar(999, $this->datos());
    }
}
