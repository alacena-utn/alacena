<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Escalar una receta a otra cantidad de porciones (lógica pura, con pruebas en tests/Dominio).
 *   - Las cantidades se multiplican por porciones / porciones_base.
 *   - Se redondean como en una cocina: unidades, cucharadas y tazas de a ½ o ¼; gramos y mililitros
 *     de a 5 (o de a 10 si pasan de 200).
 *   - 1000 g o más pasan a kg y 1000 ml o más, a l. La pizca no se multiplica.
 *   - En los pasos, {ing:papa} se cambia por "papa (600 g)" con la cantidad ya escalada.
 */
final class Porciones
{
    public const MINIMO = 1;
    public const MAXIMO = 12;

    /** Unidades que se cuentan "a mano" y se redondean a fracciones (½, ¼, ¾). */
    private const FRACCIONABLES = ['u', 'cda', 'cdita', 'taza', 'diente', 'lata', 'paquete', 'atado'];

    public static function factor(int $base, int $porciones): float
    {
        return max(self::MINIMO, min(self::MAXIMO, $porciones)) / max(1, $base);
    }

    /** Cantidad escalada y lista para mostrar: "1,2 kg", "1 ½ cda", "3 unidades", "a gusto". */
    public static function texto(?float $cantidad, ?string $unidad, float $factor = 1.0): string
    {
        $unidad = trim((string) $unidad);
        if ($cantidad === null) {
            return $unidad === 'pizca' ? 'una pizca' : 'a gusto';   // sin cantidad: sal, perejil para servir…
        }
        if ($unidad === 'pizca') {
            return 'una pizca';   // es "a gusto": no se multiplica
        }
        $valor = $cantidad * $factor;
        if (in_array($unidad, ['g', 'ml'], true)) {
            if ($valor >= 1000) {
                return self::decimal(round($valor / 1000, 2)) . ' ' . ($unidad === 'g' ? 'kg' : 'l');
            }
            $paso = $valor > 200 ? 10 : ($valor >= 20 ? 5 : 1);
            return (string) max($paso, (int) (round($valor / $paso) * $paso)) . ' ' . $unidad;
        }
        if ($unidad === '' || $unidad === 'u') {
            $numero = self::fraccion($valor);
            return $numero . ($numero === '1' || $numero === '½' || $numero === '¼' ? ' unidad' : ' unidades');
        }
        if (in_array($unidad, self::FRACCIONABLES, true)) {
            return self::fraccion($valor) . ' ' . $unidad;
        }
        return self::decimal(round($valor, 2)) . ' ' . $unidad;
    }

    /**
     * Cambia los marcadores {ing:slug} de un paso por "nombre (cantidad)".
     * @param array<string, array{nombre: string, cantidad: ?float, unidad: ?string}> $porSlug
     */
    public static function pasoConCantidades(string $texto, array $porSlug, float $factor = 1.0): string
    {
        return (string) preg_replace_callback('/\{ing:([a-z0-9-]+)\}/', function (array $m) use ($porSlug, $factor) {
            $ing = $porSlug[$m[1]] ?? null;
            if ($ing === null) {
                return str_replace('-', ' ', $m[1]);
            }
            $cantidad = self::texto($ing['cantidad'], $ing['unidad'], $factor);
            $cantidad = (string) preg_replace('/ unidad(es)?$/', '', $cantidad);
            return mb_strtolower($ing['nombre']) . ($cantidad !== '' ? ' (' . $cantidad . ')' : '');
        }, $texto);
    }

    /** "Cebolla de verdeo" → "cebolla-de-verdeo" (el mismo nombre que usan los dibujos y los marcadores). */
    public static function slug(string $texto): string
    {
        return Texto::slug($texto);
    }

    /** 1.5 → "1 ½", 0.25 → "¼", 2 → "2" (redondeado al cuarto más cercano; nunca 0). */
    private static function fraccion(float $valor): string
    {
        $cuartos = max(1, (int) round($valor * 4));
        $entero = intdiv($cuartos, 4);
        $resto = ['', '¼', '½', '¾'][$cuartos % 4];
        if ($entero === 0) {
            return $resto;
        }
        return $resto === '' ? (string) $entero : $entero . ' ' . $resto;
    }

    /** 1.25 → "1,25", 2.0 → "2" */
    private static function decimal(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    }
}
