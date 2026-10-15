<?php

declare(strict_types=1);

namespace Alacena\Http;

use RuntimeException;

/**
 * Error con código HTTP. Lanzarlo desde cualquier parte corta la petición y
 * public/api/index.php responde { "error": mensaje } con ese código.
 * Ejemplo: throw new ErrorHttp('Tenés que iniciar sesión', 401);
 */
final class ErrorHttp extends RuntimeException
{
}
