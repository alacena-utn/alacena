<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use Cooki\Dominio\FotoReceta;
use Cooki\Repositorios\RecetaRepositorio;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Foto de receta (incremento 2 · P3): subir, validar, mostrar, reemplazar y quitar. */
final class FotoRecetaTest extends TestCase
{
    private int $receta = 0;

    protected function setUp(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('Hace falta la extensión GD para generar la imagen de prueba');
        }
        try {
            $db = Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
        $this->receta = (new RecetaRepositorio($db))->guardar(
            ['titulo' => 'Receta con foto ' . uniqid(), 'descripcion' => '', 'porciones_base' => 2,
                'minutos_prep' => 5, 'minutos_coccion' => 0, 'dificultad' => 1],
            [['id' => 1, 'cantidad' => 1.0, 'unidad' => 'u', 'opcional' => false]],
            ['Pelar la papa.'],
            [],
            1
        );
    }

    protected function tearDown(): void
    {
        if ($this->receta) {
            $imagen = (new RecetaRepositorio(Db::conexion()))->cambiarImagen($this->receta, null);
            if (FotoReceta::esFotoNuestra($imagen)) {
                @unlink(dirname(__DIR__, 2) . '/public/' . $imagen);
            }
            (new RecetaRepositorio(Db::conexion()))->borrar($this->receta);
        }
    }

    private static function png(int $ancho, int $alto): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 220, 120, 40));
        ob_start();
        imagepng($imagen);
        return (string) ob_get_clean();
    }

    /** @return array<string, mixed> los campos del editor para la receta de prueba */
    private function campos(): array
    {
        $r = (new RecetaRepositorio(Db::conexion()))->obtener($this->receta);
        return [
            'id' => $this->receta, 'titulo' => $r['titulo'], 'descripcion' => '', 'porciones_base' => 2,
            'minutos_prep' => 5, 'minutos_coccion' => 0, 'dificultad' => 1,
            'ingredientes' => [['id' => 1, 'cantidad' => '1', 'unidad' => 'u', 'peso' => 2]],
            'pasos' => 'Pelar la papa.',
        ];
    }

    private function imagen(): ?string
    {
        return (new RecetaRepositorio(Db::conexion()))->obtener($this->receta)['imagen'];
    }

    public function testSubirMostrarYQuitarLaFoto(): void
    {
        $admin = new Navegador();
        $admin->ingresar('admin@cooki.test', 'Admin1234');
        [, $html] = $admin->get('admin/receta-editar.php?id=' . $this->receta);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);

        $foto = ['foto', 'mi foto.png', self::png(400, 300)];
        [$estado] = $admin->postArchivo('admin/receta-editar.php', $this->campos(), $foto);
        $this->assertSame(302, $estado);
        $primera = $this->imagen();
        $this->assertTrue(FotoReceta::esFotoNuestra($primera), (string) $primera);
        $this->assertFileExists(dirname(__DIR__, 2) . '/public/' . $primera);

        $maria = new Navegador();
        $maria->ingresar('maria@cooki.test', 'Usuario1234');
        [, $html] = $maria->get('app/receta.php?id=' . $this->receta);
        $this->assertStringContainsString('class="foto-receta"', $html);
        $this->assertStringContainsString($primera, $html);

        // Al reemplazarla, el archivo anterior se borra
        $admin->get('admin/receta-editar.php?id=' . $this->receta);
        $admin->postArchivo('admin/receta-editar.php', $this->campos(), ['foto', 'otra.png', self::png(300, 300)]);
        $segunda = $this->imagen();
        $this->assertNotSame($primera, $segunda);
        $this->assertFileDoesNotExist(dirname(__DIR__, 2) . '/public/' . $primera);

        // Quitar la foto
        $admin->get('admin/receta-editar.php?id=' . $this->receta);
        $admin->post('admin/receta-editar.php', $this->campos() + ['quitar_foto' => '1']);
        $this->assertNull($this->imagen());
        $this->assertFileDoesNotExist(dirname(__DIR__, 2) . '/public/' . $segunda);
    }

    public function testRechazaLoQueNoEsUnaImagenOEsMuyChica(): void
    {
        $admin = new Navegador();
        $admin->ingresar('admin@cooki.test', 'Admin1234');
        $admin->get('admin/receta-editar.php?id=' . $this->receta);
        $falsa = ['foto', 'virus.jpg', '<?php echo 1;'];
        [$estado, $html] = $admin->postArchivo('admin/receta-editar.php', $this->campos(), $falsa);
        $this->assertSame(200, $estado);
        $this->assertStringContainsString('tiene que ser JPG, PNG o WebP', $html);
        $chica = ['foto', 'chica.png', self::png(50, 50)];
        [, $html] = $admin->postArchivo('admin/receta-editar.php', $this->campos(), $chica);
        $this->assertStringContainsString('entre 200 y 6000 píxeles', $html);
        $this->assertNull($this->imagen());
    }
}
