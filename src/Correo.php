<?php

declare(strict_types=1);

namespace Alacena;

/**
 * Envío de correos con la función mail() de PHP.
 * - Con Docker, los correos llegan a Mailpit (http://localhost:8025): nadie los recibe de verdad.
 * - Con XAMPP, mail() necesita un servidor de correo configurado; si no lo hay, devuelve false
 *   y la consulta igual queda guardada en la base de datos.
 */
final class Correo
{
    public static function enviar(string $para, string $asunto, string $cuerpo, string $responderA = ''): bool
    {
        $remitente = Config::get('MAIL_REMITENTE', 'no-responder@alacena.test');
        $encabezados = [
            'From' => 'Alacena <' . $remitente . '>',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Mailer' => 'PHP/' . PHP_VERSION,
        ];
        // Solo se agrega Reply-To si el email es válido: un salto de línea ahí permitiría
        // inyectar encabezados y usar el formulario para mandar spam.
        if ($responderA !== '' && filter_var($responderA, FILTER_VALIDATE_EMAIL)) {
            $encabezados['Reply-To'] = $responderA;
        }
        $asuntoCodificado = '=?UTF-8?B?' . base64_encode(str_replace(["\r", "\n"], ' ', $asunto)) . '?=';

        // La @ evita que el aviso de PHP se muestre en la página si no hay servidor de correo
        return @mail($para, $asuntoCodificado, $cuerpo, $encabezados);
    }
}
