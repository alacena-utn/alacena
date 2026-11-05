<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use Cooki\Dominio\ImportadorRecetas;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Las recetas de database/recetas/*.json validan contra el catálogo real
 * y database/recetas.sql está al día (si no: php scripts/importar-recetas.php --sql).
 */
final class RecetarioTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

    public function testTodasLasRecetasSonValidasYElSqlEstaAlDia(): void
    {
        $db = Db::conexion();
        $importador = new ImportadorRecetas(
            $db->query('SELECT id, nombre, categoria_id FROM ingrediente WHERE usuario_id IS NULL')->fetchAll(),
            $db->query('SELECT texto, ingrediente_id FROM sinonimo')->fetchAll(PDO::FETCH_KEY_PAIR),
            $db->query('SELECT nombre, id FROM etiqueta')->fetchAll(PDO::FETCH_KEY_PAIR)
        );
        $archivos = glob(dirname(__DIR__, 2) . '/database/recetas/*.json') ?: [];
        sort($archivos);
        $this->assertGreaterThanOrEqual(48, count($archivos));
        $recetas = [];
        foreach ($archivos as $archivo) {
            $resultado = $importador->validar(json_decode((string) file_get_contents($archivo), true));
            $this->assertSame([], $resultado['errores'], basename($archivo));
            $recetas[] = $resultado['receta'];
        }
        $this->assertSame(
            ImportadorRecetas::archivoSql($recetas),
            file_get_contents(dirname(__DIR__, 2) . '/database/recetas.sql'),
            'database/recetas.sql quedó viejo: correr php scripts/importar-recetas.php --sql'
        );
    }

    public function testElRecetarioEstaCargado(): void
    {
        $db = Db::conexion();
        $this->assertGreaterThanOrEqual(60, (int) $db->query('SELECT COUNT(*) FROM receta')->fetchColumn());
        // Cada receta tiene al menos un ingrediente principal (si no, el puntaje no la puede agrupar bien)
        $sinPrincipal = $db->query(
            'SELECT r.titulo FROM receta r WHERE NOT EXISTS
               (SELECT 1 FROM receta_ingrediente ri WHERE ri.receta_id = r.id AND ri.peso = 3)'
        )->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame([], $sinPrincipal);
    }
}
