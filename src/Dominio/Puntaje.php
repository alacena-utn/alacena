<?php

declare(strict_types=1);

namespace Alacena\Dominio;

/**
 * Lógica pura del motor de recomendación: sin base de datos ni HTTP.
 * Por eso se puede probar con PHPUnit en milisegundos.
 * La completa P3 (ver informe, sección 10).
 */
final class Puntaje
{
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
}
