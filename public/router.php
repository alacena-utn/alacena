<?php

/**
 * Solo para el servidor de desarrollo de PHP (sin Apache):
 *   php -S localhost:8080 -t public public/router.php
 * Sirve los archivos estáticos tal cual y manda /api/... a api/index.php.
 */

$camino = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (str_starts_with($camino, '/api/') || $camino === '/api') {
    require __DIR__ . '/api/index.php';
    return true;
}
return false;
