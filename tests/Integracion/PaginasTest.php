<?php

declare(strict_types=1);

namespace Alacena\Tests\Integracion;

use Alacena\Db;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de punta a punta de las páginas: se piden como un navegador y se comprueba
 * qué muestran y a dónde redirigen. Usan los usuarios de seed.sql.
 * Corren contra un servidor de PHP real (Linux distingue mayúsculas en los nombres de archivo,
 * igual que Docker: así se detecta un enlace a "contacto.php" si el archivo se llama distinto).
 */
final class PaginasTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

    public function testLasPaginasPublicasResponden(): void
    {
        $web = new Navegador();
        foreach (['index.php', 'login.php', 'registro.php', 'contacto.php'] as $pagina) {
            $this->assertSame(200, $web->get($pagina)[0], "{$pagina} tiene que responder 200");
        }
    }

    public function testLasTipografiasNoDependenDeGoogle(): void
    {
        [, $html] = (new Navegador())->get('index.php');
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertFileExists(dirname(__DIR__, 2) . '/public/fonts/figtree-latin-400-normal.woff2');
    }

    public function testPaginaPrivadaSinSesionRedirigeAlError(): void
    {
        [$estado, , $destino] = (new Navegador())->get('app/despensa.php');
        $this->assertSame(302, $estado);
        $this->assertStringEndsWith('error.php?motivo=sin-sesion', $destino);
    }

    public function testIngresoIncorrectoRedirigeALaPaginaDeError(): void
    {
        $destino = (new Navegador())->ingresar('maria@alacena.test', 'otra-cosa');
        $this->assertStringEndsWith('error.php?motivo=login', $destino);
    }

    public function testUsuarioGeneralVaASuCocinaYNoPuedeEntrarAAdministracion(): void
    {
        $web = new Navegador();
        $this->assertStringEndsWith('app/index.php', $web->ingresar('maria@alacena.test', 'Usuario1234'));
        foreach (['app/index.php', 'app/despensa.php', 'app/recetas.php'] as $pagina) {
            $this->assertSame(200, $web->get($pagina)[0], "{$pagina} tiene que responder 200");
        }
        [, , $destino] = $web->get('admin/index.php');
        $this->assertStringEndsWith('error.php?motivo=sin-permiso', $destino);
    }

    public function testTodasLasPaginasDeAdministracionResponden(): void
    {
        $web = new Navegador();
        $this->assertStringEndsWith('admin/index.php', $web->ingresar('admin@alacena.test', 'Admin1234'));
        $paginas = ['admin/index.php', 'admin/recetas.php', 'admin/receta-editar.php', 'admin/ingredientes.php',
            'admin/ingrediente-editar.php', 'admin/usuarios.php', 'admin/usuario-crear.php', 'admin/consultas.php'];
        foreach ($paginas as $pagina) {
            $this->assertSame(200, $web->get($pagina)[0], "{$pagina} tiene que responder 200 (sin composer install)");
        }
        [, $html] = $web->get('admin/consultas.php');
        $this->assertStringContainsString('css/estilos.css', $html, 'Consultas usa el encabezado común');
    }

    public function testFormularioSinTokenSeRechaza(): void
    {
        [, , $destino] = (new Navegador())->post('contacto.php', ['csrf' => 'trucho', 'nombre' => 'X']);
        $this->assertStringEndsWith('error.php?motivo=formulario-vencido', $destino);
    }

    public function testRegistroCreaUnUsuarioGeneralConContrasenaEncriptada(): void
    {
        $email = 'prueba' . uniqid() . '@alacena.test';
        $web = new Navegador();
        $web->get('registro.php');
        [, , $destino] = $web->post('registro.php', [
            'nombre' => 'Prueba', 'email' => $email, 'contrasena' => 'Clave1234', 'repetir' => 'Clave1234',
        ]);
        $consulta = Db::conexion()->prepare('SELECT rol, hash_contrasena FROM usuario WHERE email = ?');
        $consulta->execute([$email]);
        $fila = $consulta->fetch();
        Db::conexion()->prepare('DELETE FROM usuario WHERE email = ?')->execute([$email]);

        $this->assertStringEndsWith('app/index.php', $destino);
        $this->assertSame('general', $fila['rol']);
        $this->assertTrue(password_verify('Clave1234', $fila['hash_contrasena']));
    }

    public function testContactoGuardaLaConsulta(): void
    {
        $asunto = 'Prueba automática ' . uniqid();
        $web = new Navegador();
        $web->get('contacto.php');
        [, , $destino] = $web->post('contacto.php', [
            'nombre' => 'Tester', 'email' => 'tester@alacena.test', 'asunto' => $asunto,
            'mensaje' => 'Mensaje de prueba de más de diez caracteres.',
        ]);
        $consulta = Db::conexion()->prepare('SELECT COUNT(*) FROM contacto WHERE asunto = ?');
        $consulta->execute([$asunto]);
        $guardadas = (int) $consulta->fetchColumn();
        Db::conexion()->prepare('DELETE FROM contacto WHERE asunto = ?')->execute([$asunto]);

        $this->assertStringEndsWith('contacto.php', $destino);
        $this->assertSame(1, $guardadas);
        $this->assertSame(200, $web->get(basename($destino))[0], 'Vuelve a una página que existe');
    }

    public function testNadiePuedeEditarLaDespensaDeOtro(): void
    {
        $web = new Navegador();
        $web->ingresar('juan@alacena.test', 'Usuario1234');
        [, , $destino] = $web->get('app/despensa.php?editar=1');
        $this->assertStringEndsWith('error.php?motivo=no-encontrado', $destino);
    }
}
