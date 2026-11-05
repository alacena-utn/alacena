<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Los pasos de una receta como texto, un paso por renglón (así se editan en el ABM de recetas).
 * Un paso con temporizador termina en "[⏱ 15 min]" (o "[15 min]"): el modo cocina usa ese tiempo.
 */
final class Pasos
{
    private const TIMER = '/\s*\[\s*(?:⏱\s*)?(\d{1,3})\s*min\s*\]\s*$/u';

    /**
     * @param string $texto un paso por renglón
     * @return list<array{texto: string, segundos: ?int}>
     */
    public static function desdeTexto(string $texto): array
    {
        $pasos = [];
        foreach (preg_split('/\R/u', $texto) ?: [] as $renglon) {
            $renglon = trim($renglon);
            $segundos = null;
            if (preg_match(self::TIMER, $renglon, $m) === 1) {
                $segundos = max(1, min(180, (int) $m[1])) * 60;
                $renglon = trim((string) preg_replace(self::TIMER, '', $renglon));
            }
            if ($renglon !== '') {
                $pasos[] = ['texto' => $renglon, 'segundos' => $segundos];
            }
        }
        return $pasos;
    }

    /**
     * @param list<array{instruccion: string, segundos_timer: ?int}> $pasos como vienen de la base
     */
    public static function aTexto(array $pasos): string
    {
        $renglones = [];
        foreach ($pasos as $paso) {
            $segundos = (int) ($paso['segundos_timer'] ?? 0);
            $renglones[] = $paso['instruccion']
                . ($segundos > 0 ? ' [⏱ ' . max(1, (int) round($segundos / 60)) . ' min]' : '');
        }
        return implode("\n", $renglones);
    }
}
