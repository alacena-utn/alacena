<?php

declare(strict_types=1);

namespace Alacena;

final class Correo
{
    public static function enviar(
        string $para,
        string $asunto,
        string $cuerpo,
        string $responderA = ''
    ): bool {
        $remitente = Config::get('MAIL_REMITENTE', 'no-responder@alacena.test');

        $encabezados = [
            'From' => 'Alacena <' . $remitente . '>',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Mailer' => 'PHP/' . PHP_VERSION,
        ];

        if ($responderA !== '' && filter_var($responderA, FILTER_VALIDATE_EMAIL)) {
            $encabezados['Reply-To'] = $responderA;
        }

        $asuntoCodificado = '=?UTF-8?B?'
            . base64_encode(str_replace(["\r", "\n"], ' ', $asunto))
            . '?=';

        return @mail($para, $asuntoCodificado, $cuerpo, $encabezados);
    }
}
