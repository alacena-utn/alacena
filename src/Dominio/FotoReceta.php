<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Foto de una receta (la sube un administrador desde el editor).
 *
 * Nunca se confía en lo que dice el navegador: el tipo se mira en el contenido del archivo (finfo) y se comprueba
 * que sea una imagen de verdad (getimagesize). El nombre del archivo lo arma el sistema, con una parte al azar:
 * el nombre original no se usa nunca (podría traer "../" o terminar en .php).
 */
final class FotoReceta
{
    public const MAXIMO_BYTES = 2 * 1024 * 1024;
    public const TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    /** Carpeta (dentro de public/) donde se guardan las fotos. */
    public const CARPETA = 'img/recetas';

    /**
     * Revisa un archivo subido ($_FILES['foto']).
     * @param array{error?: int, size?: int, tmp_name?: string} $archivo
     * @return array{ok: bool, error: string, extension: string}
     *         ok=false y error='' significa que no se eligió ningún archivo (no es un error)
     */
    public static function validar(array $archivo): array
    {
        $codigo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($codigo === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => '', 'extension' => ''];
        }
        $muyGrande = in_array($codigo, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || (int) ($archivo['size'] ?? 0) > self::MAXIMO_BYTES;
        if ($muyGrande) {
            return self::error('La foto pesa más de 2 MB. Achicala o elegí otra.');
        }
        $ruta = (string) ($archivo['tmp_name'] ?? '');
        if ($codigo !== UPLOAD_ERR_OK || $ruta === '' || !is_file($ruta)) {
            return self::error('No se pudo subir la foto. Probá de nuevo.');
        }
        $tipo = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        if (!isset(self::TIPOS[$tipo])) {
            return self::error('La foto tiene que ser JPG, PNG o WebP.');
        }
        $medidas = @getimagesize($ruta);
        if ($medidas === false) {
            return self::error('El archivo no es una imagen válida.');
        }
        if ($medidas[0] < 200 || $medidas[1] < 200 || $medidas[0] > 6000 || $medidas[1] > 6000) {
            return self::error('La foto tiene que medir entre 200 y 6000 píxeles de lado.');
        }
        return ['ok' => true, 'error' => '', 'extension' => self::TIPOS[$tipo]];
    }

    /** Nombre nuevo para la foto de una receta, por ejemplo "img/recetas/receta-6-3fa2c19b.jpg". */
    public static function nombre(int $recetaId, string $extension): string
    {
        return self::CARPETA . '/receta-' . $recetaId . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    }

    /** true si la ruta guardada en la base es una foto de esta carpeta (antes de borrar un archivo). */
    public static function esFotoNuestra(?string $ruta): bool
    {
        return $ruta !== null && preg_match('#^img/recetas/receta-\d+-[a-f0-9]{8}\.(jpg|png|webp)$#', $ruta) === 1;
    }

    /** @return array{ok: false, error: string, extension: string} */
    private static function error(string $mensaje): array
    {
        return ['ok' => false, 'error' => $mensaje, 'extension' => ''];
    }
}
