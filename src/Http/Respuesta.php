<?php

declare(strict_types=1);

namespace Alacena\Http;

/** Ayudas para responder JSON de forma uniforme. */
final class Respuesta
{
    /** @param array<mixed>|object $datos */
    public static function json(array|object $datos, int $estado = 200): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $mensaje, int $estado): void
    {
        self::json(['error' => $mensaje], $estado);
    }

    /** @return array<mixed> Cuerpo JSON de la petición, o [] si no hay. */
    public static function cuerpo(): array
    {
        $datos = json_decode((string) file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }
}
