<?php

declare(strict_types=1);

namespace Alacena\Tests\Integracion;

use Alacena\Db;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de punta a punta de las páginas del parcial: se piden como un navegador
 * y se comprueba qué muestran y a dónde redirigen. Usan los usuarios de seed.sql.
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

    public function testPaginaPrincipalTieneNombreMenuRegistroEIngreso(): void
    {
        [$estado, $html] = (new Navegador())->get('index.php');
        $this->assertSame(200, $estado);
        $this->assertStringContainsString('¿Qué puedo cocinar?', $html);
        $this->assertStringContainsString('Alacena es la puerta de tu heladera:', $html);
        $this->assertStringContainsString('fideos.svg', $html, 'El logo y la pestaña usan el dibujo de los fideos');
        $this->assertStringContainsString('aria-label="Principal"', $html);
        $this->assertStringContainsString('registro.php', $html);
        $this->assertStringContainsString('login.php', $html);
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

    public function testTodasLasPaginasDeAdministracionResponden(): void
    {
        $web = new Navegador();
        $this->assertStringEndsWith('admin/index.php', $web->ingresar('admin@alacena.test', 'Admin1234'));
        $paginas = ['admin/index.php', 'admin/recetas.php', 'admin/receta-editar.php', 'admin/ingredientes.php',
            'admin/ingredientes.php?nuevo', 'admin/usuarios.php', 'admin/usuarios.php?nuevo', 'admin/consultas.php'];
        foreach ($paginas as $pagina) {
            $this->assertSame(200, $web->get($pagina)[0], "{$pagina} tiene que responder 200 (sin composer install)");
        }
        [, $html] = $web->get('admin/consultas.php');
        $this->assertStringContainsString('css/estilos.css', $html, 'Consultas usa el encabezado común');
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
        [$estado, $html] = $web->get('app/index.php');
        $this->assertSame(200, $estado);
        $this->assertStringContainsString('Hola, María', $html);
        [, , $destino] = $web->get('admin/index.php');
        $this->assertStringEndsWith('error.php?motivo=sin-permiso', $destino);
    }

    public function testAdministradorVaAlPanel(): void
    {
        $web = new Navegador();
        $this->assertStringEndsWith('admin/index.php', $web->ingresar('admin@alacena.test', 'Admin1234'));
        [$estado, $html] = $web->get('admin/index.php');
        $this->assertSame(200, $estado);
        $this->assertStringContainsString('admin/recetas.php', $html);
    }

    public function testFormularioSinTokenSeRechaza(): void
    {
        $campos = ['csrf' => 'falso', 'email' => 'a@b.c', 'contrasena' => 'x'];
        [, , $destino] = (new Navegador())->post('login.php', $campos);
        $this->assertStringEndsWith('error.php?motivo=formulario-vencido', $destino);
    }

    public function testRegistroCreaUnUsuarioGeneralConContrasenaEncriptada(): void
    {
        $email = 'prueba-' . uniqid() . '@alacena.test';
        $web = new Navegador();
        $web->get('registro.php');
        [, , $destino] = $web->post('registro.php', [
            'nombre' => 'Prueba', 'email' => $email, 'contrasena' => 'Clave1234', 'repetir' => 'Clave1234',
        ]);
        $this->assertStringEndsWith('app/index.php', $destino);

        $consulta = Db::conexion()->prepare('SELECT rol, hash_contrasena FROM usuario WHERE email = ?');
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();
        Db::conexion()->prepare('DELETE FROM usuario WHERE email = ?')->execute([$email]);

        $this->assertSame('general', $usuario['rol']);
        $this->assertTrue(password_verify('Clave1234', $usuario['hash_contrasena']));
    }

    public function testRegistroConContrasenaDebilMuestraElError(): void
    {
        $web = new Navegador();
        $web->get('registro.php');
        [$estado, $html] = $web->post('registro.php', [
            'nombre' => 'Prueba', 'email' => 'debil@alacena.test', 'contrasena' => '123', 'repetir' => '123',
        ]);
        $this->assertSame(200, $estado);
        $this->assertStringContainsString('al menos 8 caracteres', $html);
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
        $this->assertStringEndsWith('contacto.php', $destino);

        $consulta = Db::conexion()->prepare('SELECT COUNT(*) FROM contacto WHERE asunto = ?');
        $consulta->execute([$asunto]);
        $this->assertSame(1, (int) $consulta->fetchColumn());
        Db::conexion()->prepare('DELETE FROM contacto WHERE asunto = ?')->execute([$asunto]);
    }

    public function testBuscadorDeRecetasEncuentraPorSinonimoDeIngrediente(): void
    {
        $web = new Navegador();
        $web->ingresar('maria@alacena.test', 'Usuario1234');
        [, $html] = $web->get('app/recetas.php?q=pimiento');
        $this->assertStringContainsString('Arroz con pollo', $html);
        $this->assertStringNotContainsString('Panqueques', $html);
    }

    public function testNadiePuedeEditarLaDespensaDeOtro(): void
    {
        $sql = 'SELECT id FROM despensa_item WHERE usuario_id = 4 LIMIT 1';
        $idDeJuan = (int) Db::conexion()->query($sql)->fetchColumn();
        $web = new Navegador();
        $web->ingresar('maria@alacena.test', 'Usuario1234');
        [, , $destino] = $web->get('app/despensa.php?editar=' . $idDeJuan);
        $this->assertStringEndsWith('error.php?motivo=no-encontrado', $destino);
    }

    public function testElColorYLaDisposicionSeGuardanEnLaCuenta(): void
    {
        $web = new Navegador();
        $web->ingresar('maria@alacena.test', 'Usuario1234');
        $web->get('app/index.php');
        [, , $destino] = $web->post('app/preferencias.php', ['color' => 'noche', 'volver' => '/app/recetas.php']);
        $this->assertStringEndsWith('/app/recetas.php', $destino);
        $web->post('app/preferencias.php', ['disposicion' => 'panoramica', 'volver' => '/app/index.php']);
        [, $html] = $web->get('app/index.php');
        $this->assertStringContainsString('data-color="noche"', $html);
        $this->assertStringContainsString('data-disposicion="panoramica"', $html);

        [, , $destino] = $web->post('app/preferencias.php', ['color' => 'fucsia', 'volver' => '//otro-sitio.com']);
        $this->assertStringEndsWith('app/index.php', $destino, 'No redirige a otro sitio');
        $web->post('app/preferencias.php', ['disposicion' => 'gigante', 'volver' => '/app/index.php']);
        $fila = Db::conexion()->query('SELECT color, disposicion FROM usuario WHERE id = 3')->fetch();
        Db::conexion()->exec("UPDATE usuario SET color = 'verde', disposicion = 'normal' WHERE id = 3");
        $this->assertSame('noche', $fila['color'], 'Un color que no está en la lista no se guarda');
        $this->assertSame('panoramica', $fila['disposicion'], 'Una disposición que no está en la lista no se guarda');
    }

    public function testElVolumenSeGuardaEnLaCuenta(): void
    {
        $web = new Navegador();
        $web->ingresar('maria@alacena.test', 'Usuario1234');
        [, $html] = $web->get('app/index.php');
        $this->assertStringContainsString('data-volumen="50"', $html, 'Arranca en 50');
        $web->post('app/preferencias.php', ['volumen' => '80', 'volver' => '/app/index.php']);
        $web->post('app/preferencias.php', ['volumen' => '150', 'volver' => '/app/index.php']);
        [, $html] = $web->get('app/index.php');
        $volumen = Db::conexion()->query('SELECT volumen FROM usuario WHERE id = 3')->fetchColumn();
        Db::conexion()->exec('UPDATE usuario SET volumen = 50 WHERE id = 3');
        $this->assertSame(80, (int) $volumen, 'Un volumen fuera de 0 a 100 no se guarda');
        $this->assertStringContainsString('data-volumen="80"', $html);
    }

    /** El HTML del resultado del selector (sin el resto de la página, que también tiene recetas). */
    public function testLaBarraSinCuentaEsTodaDeImanesConAjustes(): void
    {
        [, $html] = (new Navegador())->get('index.php');
        $inicio = (int) strpos($html, '<ul>');
        $menu = substr($html, $inicio, (int) strpos($html, '</ul>') - $inicio);
        $this->assertStringContainsString('login.php">Ingresar</a></li>', $menu, 'Ingresar es un imán más del menú');
        $this->assertStringContainsString('registro.php">Crear cuenta</a></li>', $menu);
        $this->assertStringContainsString('cuenta--ajustes', $html);
        $this->assertStringContainsString('class="marca__letra"', $html, 'Letras separadas para la ola');
        $this->assertMatchesRegularExpression('#js/sitio\.js\?v=\d+#', $html, 'El JS lleva versión');
        [, $login] = (new Navegador())->get('login.php');
        $this->assertSame(15, substr_count($login, 'stickers-deco__dibujo'), 'Ingresar: 10 a los costados y 5 abajo');
    }

    public function testSinCuentaElColorSeGuardaEnUnaCookieYPasaALaCuentaNueva(): void
    {
        $web = new Navegador();
        $web->agregarCookie('alacena_color', 'rosa');
        [, $html] = $web->get('index.php');
        $this->assertStringContainsString('data-color="rosa"', $html);

        $trucho = new Navegador();
        $trucho->agregarCookie('alacena_color', '"><script>');
        [, $html] = $trucho->get('index.php');
        $this->assertStringContainsString('data-color="verde"', $html, 'Un valor inválido no se usa');

        [, , $destino] = $web->post('app/preferencias.php', ['color' => 'celeste', 'volver' => '/contacto.php']);
        $this->assertSame('/contacto.php', $destino, 'Sin cuenta también se puede elegir el color');

        $email = 'color' . uniqid() . '@alacena.test';
        $web->get('registro.php');
        $web->post('registro.php', [
            'nombre' => 'Con Color', 'email' => $email, 'contrasena' => 'Clave1234', 'repetir' => 'Clave1234',
        ]);
        $consulta = Db::conexion()->prepare('SELECT color FROM usuario WHERE email = ?');
        $consulta->execute([$email]);
        $color = $consulta->fetchColumn();
        Db::conexion()->prepare('DELETE FROM usuario WHERE email = ?')->execute([$email]);
        $this->assertSame('rosa', $color, 'El color elegido sin cuenta pasa a la cuenta nueva');
    }

    public function testAlIngresarNoSeRepiteElSaludo(): void
    {
        $web = new Navegador();
        $web->ingresar('maria@alacena.test', 'Usuario1234');
        [, $html] = $web->get('app/index.php');
        $this->assertSame(1, substr_count($html, 'Hola, María'), 'Solo el título saluda');
    }

    public function testSalirPreguntaYSoloCierraLaSesionPorPost(): void
    {
        $web = new Navegador();
        $web->ingresar('maria@alacena.test', 'Usuario1234');
        [, $html] = $web->get('app/index.php');
        $this->assertStringContainsString('¿Seguro que querés salir?', $html);
        [, , $destino] = $web->get('salir.php');
        $this->assertStringEndsWith('app/index.php', $destino, 'Un enlace a salir.php no cierra la sesión');
        $this->assertSame(200, $web->get('app/despensa.php')[0]);
        $web->get('app/index.php');
        [, , $destino] = $web->post('salir.php', []);
        $this->assertStringEndsWith('index.php', $destino);
        $this->assertSame(302, $web->get('app/despensa.php')[0], 'Después de salir ya no hay sesión');
    }

    private function seccionSelector(string $html): string
    {
        $inicio = (int) strpos($html, 'id="resultado-selector"');
        return substr($html, $inicio, (int) strpos($html, 'Podés cocinar', $inicio) - $inicio);
    }
}
