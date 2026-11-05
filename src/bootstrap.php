<?php

/**
 * Arranque común de la aplicación: autoload, configuración y funciones de ayuda.
 * Cada página empieza con:  require dirname(__DIR__) . '/src/bootstrap.php';
 * No depende de Composer para funcionar en producción (hosting sin consola).
 */

declare(strict_types=1);

spl_autoload_register(function (string $clase): void {
    $prefijo = 'Cooki\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $relativa = str_replace('\\', '/', substr($clase, strlen($prefijo)));
    $archivo = __DIR__ . '/' . $relativa . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

require_once __DIR__ . '/ayudas.php';

\Cooki\Config::cargar(dirname(__DIR__) . '/.env');

date_default_timezone_set('America/Argentina/Buenos_Aires');

// En producción (APP_ENV=produccion) los errores no se muestran en la página: quedan en el log del servidor,
// y quien navega ve la página de error común. En desarrollo se ven, para poder corregirlos.
if (\Cooki\Config::esProduccion()) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    set_exception_handler(function (Throwable $e): void {
        error_log((string) $e);
        if (!headers_sent()) {
            header('Location: ' . url('error.php?motivo=interno'));
            return;
        }
        echo '<p>Ocurrió un error inesperado. Probá de nuevo en un rato.</p>';
    });
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    // En el hosting con certificado: FORZAR_HTTPS=1 manda todo a https://
    if (\Cooki\Config::get('FORZAR_HTTPS') === '1' && !esHttps()) {
        $host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    // Encabezados de seguridad: el navegador no adivina tipos de archivo, el sitio no se puede meter
    // dentro de otro (clickjacking), los enlaces no le pasan la dirección a otros sitios (protege el token
    // de "Olvidé mi contraseña") y ninguna página pide cámara, micrófono ni ubicación.
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
