<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Lógica pura del motor de recomendación: sin base de datos ni HTTP.
 * Por eso se puede probar con PHPUnit en milisegundos. Sigue el informe del proyecto (sección 10):
 *
 *   disponibilidad de cada ingrediente: 1 (lo tiene o es básico) · 0,5 (tiene un sustituto) · 0 (no lo tiene)
 *   cobertura = Σ (peso × disponibilidad) / Σ peso      (los opcionales no cuentan)
 *   puntaje   = 100 × cobertura
 *             + 5 por cada ingrediente que vence en 3 días o menos (hasta +15)
 *             − 6 por cada ingrediente principal que falta
 *             − 8 si la cocinó en los últimos 7 días
 *
 * Y la agrupa como la piensa el usuario: "Podés hacerla ahora", "Te falta 1" o "Casi" (faltan 2 o 3).
 */
final class Puntaje
{
    public const PESOS = [3 => 'Principal', 2 => 'Secundario', 1 => 'Accesorio'];
    public const GRUPOS = ['ahora' => 'Podés hacerla ahora', 'falta1' => 'Te falta 1', 'casi' => 'Casi'];

    /**
     * Porcentaje (0–100) de ingredientes requeridos que el usuario tiene.
     *
     * @param list<int> $requeridos  ids de ingredientes de la receta
     * @param list<int> $disponibles ids de ingredientes en la despensa
     */
    public static function cobertura(array $requeridos, array $disponibles): int
    {
        if ($requeridos === []) {
            return 0;
        }
        $tengo = count(array_intersect($requeridos, $disponibles));
        return (int) round($tengo / count($requeridos) * 100);
    }

    /**
     * Calcula el puntaje de una receta para un usuario.
     *
     * @param list<array{nombre: string, peso: int, opcional: bool, disponible: float,
     *                   vence_pronto?: bool, sustituto?: ?string}> $ingredientes
     * @return array{puntaje: int, cobertura: int, faltantes: list<string>, faltan_principales: int,
     *               sustituciones: list<array{falta: string, usar: string}>, usa_lo_que_vence: list<string>,
     *               grupo: ?string}
     */
    public static function calcular(array $ingredientes, bool $cocinadaHacePoco = false): array
    {
        $pesoTotal = 0;
        $pesoCubierto = 0.0;
        $pesoPrincipales = 0;
        $principalesCubiertos = 0.0;
        $faltantes = [];
        $faltanPrincipales = 0;
        $faltaUnPrincipal = false;
        $sustituciones = [];
        $vence = [];

        foreach ($ingredientes as $ing) {
            if ($ing['opcional']) {
                continue;
            }
            $peso = max(1, min(3, (int) $ing['peso']));
            $disponible = max(0.0, min(1.0, (float) $ing['disponible']));
            $pesoTotal += $peso;
            $pesoCubierto += $peso * $disponible;
            if ($peso === 3) {
                $pesoPrincipales += $peso;
                $principalesCubiertos += $peso * $disponible;
            }
            if ($disponible <= 0.0) {
                $faltantes[] = $ing['nombre'];
                if ($peso === 3) {
                    $faltanPrincipales++;
                    $faltaUnPrincipal = true;
                }
            } elseif ($disponible < 1.0 && !empty($ing['sustituto'])) {
                $sustituciones[] = ['falta' => $ing['nombre'], 'usar' => (string) $ing['sustituto']];
            }
            if (!empty($ing['vence_pronto']) && $disponible >= 1.0) {
                $vence[] = $ing['nombre'];
            }
        }

        $cobertura = $pesoTotal > 0 ? $pesoCubierto / $pesoTotal : 0.0;
        $puntaje = (int) round(100 * $cobertura)
            + min(15, 5 * count($vence))
            - 6 * $faltanPrincipales
            - ($cocinadaHacePoco ? 8 : 0);

        return [
            'puntaje' => $puntaje,
            'cobertura' => (int) round(100 * $cobertura),
            'faltantes' => $faltantes,
            'faltan_principales' => $faltanPrincipales,
            'sustituciones' => $sustituciones,
            'usa_lo_que_vence' => $vence,
            'grupo' => self::grupo(
                count($faltantes),
                $faltaUnPrincipal,
                $pesoPrincipales > 0 ? $principalesCubiertos / $pesoPrincipales : 1.0
            ),
        ];
    }

    /**
     * Grupo de la receta: "ahora" (no falta nada), "falta1" (falta uno que no es principal),
     * "casi" (faltan 2 o 3, o falta un principal) o null (no se muestra).
     * Si tiene menos de la mitad de los principales, tampoco se muestra.
     */
    public static function grupo(int $faltantes, bool $faltaUnPrincipal, float $coberturaPrincipales): ?string
    {
        if ($coberturaPrincipales < 0.5) {
            return null;
        }
        return match (true) {
            $faltantes === 0 => 'ahora',
            $faltantes === 1 && !$faltaUnPrincipal => 'falta1',
            $faltantes <= 3 => 'casi',
            default => null,
        };
    }

    /**
     * Orden de las recomendaciones: más puntaje primero; si empatan, la más rápida y después la más fácil.
     *
     * @param array{puntaje: int, minutos: int, dificultad: int, titulo: string} $a
     * @param array{puntaje: int, minutos: int, dificultad: int, titulo: string} $b
     */
    public static function comparar(array $a, array $b): int
    {
        return [$b['puntaje'], $a['minutos'], $a['dificultad'], $a['titulo']]
            <=> [$a['puntaje'], $b['minutos'], $b['dificultad'], $b['titulo']];
    }
}
