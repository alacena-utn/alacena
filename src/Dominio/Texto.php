<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Sacar tildes sin depender del idioma del servidor.
 * iconv('UTF-8', 'ASCII//TRANSLIT') depende de la configuración regional: en el hosting
 * (configuración "C") convierte "Azúcar" en "az?car", y los ingredientes con tilde se quedaban
 * sin dibujo. Por eso se reemplazan las letras a mano.
 */
final class Texto
{
    private const SIN_TILDES = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];

    /** "Champiñón" → "champinon": en minúsculas y sin tildes. */
    public static function sinTildes(string $texto): string
    {
        return strtr(mb_strtolower($texto), self::SIN_TILDES);
    }

    /** "Puré de tomate" → "pure-de-tomate". */
    public static function slug(string $texto): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', self::sinTildes($texto)), '-');
    }
}
