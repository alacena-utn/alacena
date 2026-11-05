<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use Cooki\Repositorios\RecuperacionRepositorio;
use Cooki\Repositorios\UsuarioRepositorio;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Recuperar la contraseña por correo y bloqueo por intentos (incremento 2 · P1). */
final class RecuperarTest extends TestCase
{
    private int $usuario = 0;
    private string $email = '';

    protected function setUp(): void
    {
        try {
            $db = Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
        $this->email = 'recuperar-' . uniqid() . '@cooki.test';
        $this->usuario = (new UsuarioRepositorio($db))->crear('Recupera', $this->email, 'Vieja1234');
    }

    protected function tearDown(): void
    {
        $db = Db::conexion();
        $db->prepare('DELETE FROM usuario WHERE id = ?')->execute([$this->usuario]);
        $db->prepare('DELETE FROM intento_ingreso WHERE email IN (?, ?)')
            ->execute([$this->email, 'no-existe-' . $this->email]);
    }

    private function pedidos(): int
    {
        $consulta = Db::conexion()->prepare('SELECT COUNT(*) FROM recuperacion WHERE usuario_id = ?');
        $consulta->execute([$this->usuario]);
        return (int) $consulta->fetchColumn();
    }

    public function testPedirElEnlaceYCambiarLaContrasena(): void
    {
        $web = new Navegador();
        [, $html] = $web->get('login.php');
        $this->assertStringContainsString('olvide.php', $html);
        $web->get('olvide.php');
        [$estado, , $destino] = $web->post('olvide.php', ['email' => mb_strtoupper($this->email)]);
        $this->assertSame(302, $estado);
        $this->assertStringEndsWith('olvide.php?enviado=1', $destino);
        $this->assertSame(1, $this->pedidos());

        // El token solo viaja en el correo: para la prueba se crea otro con el repositorio
        $token = (new RecuperacionRepositorio(Db::conexion()))->crear($this->usuario);
        [, $html] = $web->get('restablecer.php?token=' . $token);
        $this->assertStringContainsString('Elegí tu contraseña nueva', $html);
        [, $html] = $web->post('restablecer.php', ['token' => $token, 'contrasena' => 'corta', 'repetir' => 'corta']);
        $this->assertStringContainsString('al menos 8 caracteres', $html);
        [, , $destino] = $web->post('restablecer.php', [
            'token' => $token, 'contrasena' => 'Nueva1234', 'repetir' => 'Nueva1234',
        ]);
        $this->assertStringEndsWith('login.php', $destino);

        $this->assertStringEndsWith('app/index.php', (new Navegador())->ingresar($this->email, 'Nueva1234'));
        $this->assertStringContainsString('motivo=login', (new Navegador())->ingresar($this->email, 'Vieja1234'));
        // El enlace ya se usó (y los otros pedidos pendientes quedaron anulados)
        [, $html] = (new Navegador())->get('restablecer.php?token=' . $token);
        $this->assertStringContainsString('El enlace no sirve', $html);
        [, $html] = (new Navegador())->get('restablecer.php?token=' . str_repeat('a', 64));
        $this->assertStringContainsString('El enlace no sirve', $html);
    }

    public function testLaRespuestaEsIgualSiLaCuentaNoExisteYHayUnLimitePorHora(): void
    {
        $web = new Navegador();
        $web->get('olvide.php');
        [, , $destino] = $web->post('olvide.php', ['email' => 'no-existe-' . $this->email]);
        $this->assertStringEndsWith('olvide.php?enviado=1', $destino);
        for ($i = 0; $i < 5; $i++) {
            $web->get('olvide.php');
            $web->post('olvide.php', ['email' => $this->email]);
        }
        $this->assertSame(RecuperacionRepositorio::PEDIDOS_POR_HORA, $this->pedidos());
    }

    public function testCincoErroresBloqueanElEmailYRecuperarLoDesbloquea(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->assertStringContainsString('motivo=login', (new Navegador())->ingresar($this->email, 'mal' . $i));
        }
        $quinto = (new Navegador())->ingresar($this->email, 'mal5');
        $this->assertStringContainsString('motivo=bloqueado&minutos=15', $quinto);
        // Bloqueado: ni con la contraseña correcta
        $this->assertStringContainsString('motivo=bloqueado', (new Navegador())->ingresar($this->email, 'Vieja1234'));
        [$estado, $html] = (new Navegador())->get('error.php?motivo=bloqueado&minutos=15');
        $this->assertSame(429, $estado);
        $this->assertStringContainsString('Podés volver a intentar en 15 minutos', $html);
        // Otro email no se ve afectado
        $this->assertStringEndsWith('app/index.php', (new Navegador())->ingresar('juan@cooki.test', 'Usuario1234'));

        $web = new Navegador();
        $token = (new RecuperacionRepositorio(Db::conexion()))->crear($this->usuario);
        $web->get('restablecer.php?token=' . $token);
        $web->post('restablecer.php', ['token' => $token, 'contrasena' => 'Otra12345', 'repetir' => 'Otra12345']);
        $this->assertStringEndsWith('app/index.php', (new Navegador())->ingresar($this->email, 'Otra12345'));
    }
}
