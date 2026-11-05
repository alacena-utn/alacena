<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Cuánto queda de un ingrediente de la despensa después de cocinar (modo cocina, al terminar).
 *
 * - Si la despensa tiene la cantidad exacta y la receta usa una unidad comparable
 *   (g/kg, ml/l o unidades; y gramos con unidades si se sabe cuánto pesa una, como la papa), se resta.
 *   Si no queda nada, pasa a "Agotado" (nivel 0).
 * - Si no se puede comparar (la despensa no tiene cantidad, o la receta dice "1 taza" y la despensa "500 g"),
 *   baja un nivel: Mucho → Normal → Poco → Agotado. La cantidad exacta se borra, porque ya no es cierta.
 *
 * Es una clase sin base de datos: la usa CocinaRepositorio y se prueba sola (DescuentoTest).
 */
final class Descuento
{
    /** Unidades que se pueden pasar a una unidad base: [base, factor]. */
    private const CONVERSIONES = [
        'g' => ['g', 1.0], 'kg' => ['g', 1000.0],
        'ml' => ['ml', 1.0], 'l' => ['ml', 1000.0],
        'u' => ['u', 1.0],
    ];

    /**
     * @param array{nivel: int|string, cantidad: float|string|null, unidad: ?string} $item fila de despensa_item
     * @param float|null $usado cantidad que usó la receta (ya escalada a las porciones); null si no dice
     * @param float|null $gramosPorUnidad cuánto pesa una unidad del ingrediente (ingrediente.gramos_por_unidad)
     * @return array{nivel: int, cantidad: ?float, unidad: ?string, como: string}
     *         como = 'cantidad' (se restó) o 'nivel' (bajó un nivel)
     */
    public static function aplicar(
        array $item,
        ?float $usado,
        ?string $unidadReceta,
        ?float $gramosPorUnidad = null
    ): array {
        $nivel = (int) $item['nivel'];
        $cantidad = $item['cantidad'] === null ? null : (float) $item['cantidad'];
        $unidad = $item['unidad'] ?? null;

        $tabla = self::CONVERSIONES;
        // Si se sabe cuánto pesa una unidad, "2 papas" y "600 g de papa" se pueden comparar
        if ($gramosPorUnidad !== null && $gramosPorUnidad > 0 && ($unidad === 'u' xor $unidadReceta === 'u')) {
            $tabla['u'] = ['g', $gramosPorUnidad];
        }
        $enBaseDespensa = self::aBase($cantidad, $unidad, $tabla);
        $enBaseReceta = self::aBase($usado, $unidadReceta, $tabla);
        if ($enBaseDespensa !== null && $enBaseReceta !== null && $enBaseDespensa[1] === $enBaseReceta[1]) {
            $resta = $enBaseDespensa[0] - $enBaseReceta[0];
            if ($resta <= 0.0001) {
                return ['nivel' => 0, 'cantidad' => null, 'unidad' => null, 'como' => 'cantidad'];
            }
            // La cantidad que queda se expresa en la unidad que ya tenía la despensa
            $factor = $tabla[(string) $unidad][1];
            return [
                'nivel' => max(1, $nivel),
                'cantidad' => round($resta / $factor, 2),
                'unidad' => $unidad,
                'como' => 'cantidad',
            ];
        }
        return ['nivel' => max(0, $nivel - 1), 'cantidad' => null, 'unidad' => null, 'como' => 'nivel'];
    }

    /**
     * @param array<string, array{0: string, 1: float}> $tabla
     * @return array{0: float, 1: string}|null [valor en la unidad base, unidad base]
     */
    private static function aBase(?float $valor, ?string $unidad, array $tabla): ?array
    {
        if ($valor === null || $unidad === null || !isset($tabla[$unidad])) {
            return null;
        }
        [$base, $factor] = $tabla[$unidad];
        return [$valor * $factor, $base];
    }
}
