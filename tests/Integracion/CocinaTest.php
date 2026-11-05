<?php

declare(strict_types=1);

namespace Cooki\Tests\Integracion;

use Cooki\Db;
use Cooki\Repositorios\UsuarioRepositorio;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Modo cocina (incremento 2 · P4): empezar, marcar pasos (quedan guardados), terminar y descontar
 * de la despensa una sola vez. Usa una cuenta nueva con una despensa armada para la tortilla de papas.
 */
final class CocinaTest extends TestCase
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
        $email = 'cocina-' . uniqid() . '@cooki.test';
        $this->usuario = (new UsuarioRepositorio($db))->crear('Cocinera', $email, 'Usuario1234');
        // Papa 1 kg (se resta 600 g → 0,4 kg), huevos "mucho" sin cantidad (baja a "normal"), cebolla 1 u (se agota)
        $db->prepare(
            "INSERT INTO despensa_item (usuario_id, ingrediente_id, nivel, cantidad, unidad, ubicacion) VALUES
             (:u1, 1, 2, 1, 'kg', 'alacena'), (:u2, 15, 3, NULL, NULL, 'heladera'), (:u3, 2, 2, 1, 'u', 'alacena')"
        )->execute(['u1' => $this->usuario, 'u2' => $this->usuario, 'u3' => $this->usuario]);
        $this->email = $email;
    }

    protected function tearDown(): void
    {
        if ($this->usuario) {
            Db::conexion()->prepare('DELETE FROM usuario WHERE id = ?')->execute([$this->usuario]);
        }
    }

    private function despensa(): array
    {
        $consulta = Db::conexion()->prepare(
            'SELECT ingrediente_id, nivel, cantidad, unidad FROM despensa_item
             WHERE usuario_id = ? ORDER BY ingrediente_id'
        );
        $consulta->execute([$this->usuario]);
        return $consulta->fetchAll(\PDO::FETCH_UNIQUE);
    }

    public function testCocinarPasoAPasoYDescontarUnaSolaVez(): void
    {
        $web = new Navegador();
        $web->ingresar($this->email, 'Usuario1234');
        [, $html] = $web->get('app/receta.php?id=1');
        $this->assertStringContainsString('Cocinar paso a paso', $html);

        [$estado, , $destino] = $web->post('app/cocinar.php', ['accion' => 'iniciar', 'receta' => 1, 'porciones' => 4]);
        $this->assertSame(302, $estado);
        $this->assertMatchesRegularExpression('#app/cocinar\.php\?sesion=(\d+)$#', $destino);
        preg_match('#sesion=(\d+)#', $destino, $m);
        $sesion = (int) $m[1];

        [, $html] = $web->get('app/cocinar.php?sesion=' . $sesion);
        $this->assertStringContainsString('<strong data-hechos>0</strong> de 5 pasos', $html);
        $this->assertStringContainsString('data-segundos="900"', $html, 'El paso 2 tiene temporizador de 15 minutos');

        $web->post('app/cocinar.php', ['accion' => 'paso', 'sesion' => $sesion, 'paso' => 1, 'hecho' => '1']);
        // El progreso queda en la cuenta: otra visita (u otro dispositivo) lo ve igual
        [, $html] = $web->get('app/cocinar.php?sesion=' . $sesion);
        $this->assertStringContainsString('<strong data-hechos>1</strong> de 5 pasos', $html);
        [, $cocina] = $web->get('app/index.php');
        $this->assertStringContainsString('Estás cocinando <strong>Tortilla de papas</strong>', $cocina);

        // Antes de terminar muestra cómo queda la despensa (la sal y el aceite son básicos: no se descuentan)
        [, $html] = $web->get('app/cocinar.php?sesion=' . $sesion);
        $this->assertStringContainsString('Papa:</strong>', $html);
        $this->assertStringNotContainsString('Sal:</strong>', $html);

        $web->post('app/cocinar.php', ['accion' => 'terminar', 'sesion' => $sesion, 'descontar' => [1, 15, 2]]);
        $despensa = $this->despensa();
        $this->assertSame('0.40', $despensa[1]['cantidad']);
        $this->assertSame(2, (int) $despensa[15]['nivel']);
        $this->assertSame(0, (int) $despensa[2]['nivel'], 'La cebolla se usó entera: queda agotada');

        // Reenviar el formulario no descuenta otra vez
        $web->post('app/cocinar.php', ['accion' => 'terminar', 'sesion' => $sesion, 'descontar' => [1, 15, 2]]);
        $this->assertSame($despensa, $this->despensa());
    }

    public function testNoSePuedeMarcarLaSesionDeOtro(): void
    {
        $web = new Navegador();
        $web->ingresar($this->email, 'Usuario1234');
        [, , $destino] = $web->post('app/cocinar.php', ['accion' => 'iniciar', 'receta' => 1, 'porciones' => 2]);
        preg_match('#sesion=(\d+)#', $destino, $m);

        $otra = new Navegador();
        $otra->ingresar('juan@cooki.test', 'Usuario1234');
        [$estado, , $aDonde] = $otra->get('app/cocinar.php?sesion=' . $m[1]);
        $this->assertStringContainsString('error.php?motivo=no-encontrado', $aDonde);
        $otra->post('app/cocinar.php', ['accion' => 'paso', 'sesion' => $m[1], 'paso' => 1, 'hecho' => '1']);
        $hechos = Db::conexion()->query('SELECT COUNT(*) FROM paso_completado WHERE sesion_id = ' . (int) $m[1]);
        $this->assertSame(0, (int) $hechos->fetchColumn());
        $this->assertSame(302, $estado);
    }
}
