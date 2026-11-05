<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Mi cuenta (incremento 2 · P1): datos, contraseña y baja. Cada prueba crea su propia cuenta
 * con el registro, así no cambia los usuarios de seed.sql.
 */
final class PerfilTest extends TestCase
{
    private string $email = '';

    protected function setUp(): void
    {
        try {
            Db::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('Sin base de datos disponible: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        foreach ([$this->email, 'otro-' . $this->email] as $email) {
            Db::conexion()->prepare('DELETE FROM usuario WHERE email = ?')->execute([$email]);
        }
    }

    private function cuentaNueva(): Navegador
    {
        $this->email = 'perfil-' . uniqid() . '@cooki.test';
        $web = new Navegador();
        $web->get('registro.php');
        $web->post('registro.php', [
            'nombre' => 'Perfil Prueba', 'email' => $this->email, 'contrasena' => 'Clave1234', 'repetir' => 'Clave1234',
        ]);
        return $web;
    }

    public function testSinCuentaNoSeEntra(): void
    {
        [, , $destino] = (new Navegador())->get('app/perfil.php');
        $this->assertStringNotContainsString('perfil.php', $destino);
        $this->assertNotSame('', $destino);
    }

    public function testCambiarNombreYEmail(): void
    {
        $web = $this->cuentaNueva();
        [$estado, $html] = $web->get('app/perfil.php');
        $this->assertSame(200, $estado);
        $this->assertMatchesRegularExpression(
            '#class="cuenta__enlace[^"]*"[^>]*>Mi cuenta</a>#',
            $html,
            'enlace en el menú de la cuenta'
        );

        // Un email que ya usa otra cuenta no se acepta
        [, $html] = $web->post('app/perfil.php', [
            'accion' => 'datos', 'nombre' => 'X Y', 'email' => 'juan@cooki.test',
        ]);
        $this->assertStringContainsString('Ya hay otra cuenta con ese email.', $html);

        $web->get('app/perfil.php');
        [, , $destino] = $web->post('app/perfil.php', [
            'accion' => 'datos', 'nombre' => 'Nombre Nuevo', 'email' => 'OTRO-' . $this->email,
        ]);
        $this->assertStringEndsWith('app/perfil.php', $destino);
        [, $html] = $web->get('app/perfil.php');
        $this->assertStringContainsString('Nombre Nuevo', $html, 'el menú muestra el nombre nuevo');
        $this->assertStringContainsString('otro-' . $this->email, $html, 'el email se guarda en minúscula');
    }

    public function testCambiarLaContrasenaPideLaActual(): void
    {
        $web = $this->cuentaNueva();
        $web->get('app/perfil.php');
        [, $html] = $web->post('app/perfil.php', [
            'accion' => 'contrasena', 'actual' => 'Equivocada1', 'nueva' => 'Nueva12345', 'repetir' => 'Nueva12345',
        ]);
        $this->assertStringContainsString('La contraseña actual no es correcta.', $html);

        [, , $destino] = $web->post('app/perfil.php', [
            'accion' => 'contrasena', 'actual' => 'Clave1234', 'nueva' => 'Nueva12345', 'repetir' => 'Nueva12345',
        ]);
        $this->assertStringEndsWith('app/perfil.php', $destino);
        $this->assertStringEndsWith('app/index.php', (new Navegador())->ingresar($this->email, 'Nueva12345'));
        $this->assertStringNotContainsString('app/index.php', (new Navegador())->ingresar($this->email, 'Clave1234'));
    }

    public function testBorrarLaCuentaPideContrasenaYConfirmacion(): void
    {
        $web = $this->cuentaNueva();
        $web->get('app/perfil.php');
        [, $html] = $web->post('app/perfil.php', ['accion' => 'baja', 'contrasena_baja' => 'Clave1234']);
        $this->assertStringContainsString('Marcá la casilla para confirmar.', $html);

        [, , $destino] = $web->post('app/perfil.php', [
            'accion' => 'baja', 'contrasena_baja' => 'Clave1234', 'confirmo' => '1',
        ]);
        $this->assertStringEndsWith('index.php', $destino);
        $consulta = Db::conexion()->prepare('SELECT COUNT(*) FROM usuario WHERE email = ?');
        $consulta->execute([$this->email]);
        $this->assertSame(0, (int) $consulta->fetchColumn());
        [, , $destino] = $web->get('app/perfil.php');
        $this->assertStringNotContainsString('perfil.php', $destino, 'la sesión se cerró');
    }
}
