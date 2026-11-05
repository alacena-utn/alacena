<?php

declare(strict_types=1);

namespace Cooki\Tests\Dominio;

use Cooki\Dominio\Puntaje;
use PHPUnit\Framework\TestCase;

/** Prueba de ejemplo: copiar este estilo (un caso por método, nombre que explica el caso). */
final class PuntajeTest extends TestCase
{
    public function testCoberturaEs100SiTengoTodo(): void
    {
        $this->assertSame(100, Puntaje::cobertura([1, 2, 3], [1, 2, 3, 9]));
    }

    public function testCoberturaEs75SiTengoTresDeCuatro(): void
    {
        $this->assertSame(75, Puntaje::cobertura([1, 2, 3, 4], [1, 2, 3]));
    }

    public function testRecetaSinIngredientesDaCero(): void
    {
        $this->assertSame(0, Puntaje::cobertura([], [1, 2]));
    }

    /** Arma un ingrediente para calcular(): [nombre, peso, disponible, vence pronto, sustituto] */
    private static function ing(
        string $nombre,
        int $peso,
        float $disponible,
        bool $vence = false,
        ?string $sust = null
    ): array {
        return ['nombre' => $nombre, 'peso' => $peso, 'opcional' => false, 'disponible' => $disponible,
            'vence_pronto' => $vence, 'sustituto' => $sust];
    }

    public function testConTodoEsPodesHacerlaAhoraYLoQueVenceSuma(): void
    {
        $r = Puntaje::calcular([self::ing('Papa', 3, 1, true), self::ing('Huevo', 3, 1, true), self::ing('Sal', 1, 1)]);
        $this->assertSame('ahora', $r['grupo']);
        $this->assertSame(100, $r['cobertura']);
        $this->assertSame(110, $r['puntaje'], '100 de cobertura + 5 por cada uno que vence');
        $this->assertSame(['Papa', 'Huevo'], $r['usa_lo_que_vence']);
    }

    public function testElBonusPorVencimientoTieneTope(): void
    {
        $ingredientes = array_map(fn ($n) => self::ing($n, 2, 1, true), ['a', 'b', 'c', 'd', 'e']);
        $this->assertSame(115, Puntaje::calcular($ingredientes)['puntaje']);
    }

    public function testFaltaUnoSecundarioEsTeFaltaUno(): void
    {
        $r = Puntaje::calcular([self::ing('Fideos', 3, 1), self::ing('Manteca', 2, 0), self::ing('Sal', 1, 1)]);
        $this->assertSame('falta1', $r['grupo']);
        $this->assertSame(['Manteca'], $r['faltantes']);
        $this->assertSame(67, $r['cobertura'], '(3 + 1) / 6');
    }

    public function testFaltaUnPrincipalEsCasiYPenaliza(): void
    {
        $r = Puntaje::calcular([self::ing('Pollo', 3, 0), self::ing('Arroz', 3, 1), self::ing('Cebolla', 2, 1)]);
        $this->assertSame('casi', $r['grupo']);
        $this->assertSame(1, $r['faltan_principales']);
        $this->assertSame(63 - 6, $r['puntaje']);
    }

    public function testElSustitutoCuentaLaMitadYSeAvisa(): void
    {
        $r = Puntaje::calcular([self::ing('Fideos', 3, 1), self::ing('Crema de leche', 2, 0.5, false, 'Leche')]);
        $this->assertSame('ahora', $r['grupo'], 'Con el sustituto se puede hacer');
        $this->assertSame([['falta' => 'Crema de leche', 'usar' => 'Leche']], $r['sustituciones']);
        $this->assertSame(80, $r['cobertura']);
    }

    public function testSinLaMitadDeLosPrincipalesNoSeMuestra(): void
    {
        $r = Puntaje::calcular([self::ing('Lenteja', 3, 0), self::ing('Pollo', 3, 0), self::ing('Sal', 1, 1)]);
        $this->assertNull($r['grupo']);
    }

    public function testLosOpcionalesNoCuentanYCocinarlaHacePocoResta(): void
    {
        $opcional = ['nombre' => 'Perejil', 'peso' => 1, 'opcional' => true, 'disponible' => 0.0];
        $r = Puntaje::calcular([self::ing('Polenta', 3, 1), $opcional], true);
        $this->assertSame('ahora', $r['grupo']);
        $this->assertSame(92, $r['puntaje']);
    }

    public function testElOrdenDesempataPorTiempoYDificultad(): void
    {
        $lista = [
            ['puntaje' => 90, 'minutos' => 40, 'dificultad' => 1, 'titulo' => 'B'],
            ['puntaje' => 90, 'minutos' => 20, 'dificultad' => 2, 'titulo' => 'C'],
            ['puntaje' => 95, 'minutos' => 60, 'dificultad' => 3, 'titulo' => 'A'],
        ];
        usort($lista, [Puntaje::class, 'comparar']);
        $this->assertSame(['A', 'C', 'B'], array_column($lista, 'titulo'));
    }
}
