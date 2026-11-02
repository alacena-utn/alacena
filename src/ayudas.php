<?php

/**
 * Funciones de ayuda para las páginas. Se cargan solas desde src/bootstrap.php.
 */

declare(strict_types=1);

/**
 * Escapa un valor para mostrarlo dentro del HTML. Evita la inyección de código (XSS).
 * Regla: todo dato que venga de la base o del usuario se imprime con e().
 */
function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Arma la dirección de una página del sitio, por ejemplo url('admin/recetas.php').
 * Funciona igual en Docker, con `composer servidor` y en XAMPP dentro de una subcarpeta.
 */
function url(string $ruta = ''): string
{
    static $base = null;
    if ($base === null) {
        $publico = str_replace('\\', '/', (string) realpath(dirname(__DIR__) . '/public'));
        $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $relativo = str_starts_with($script, $publico . '/') ? substr($script, strlen($publico) + 1) : '';
        $nombre = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
        $base = ($relativo !== '' && str_ends_with($nombre, $relativo))
            ? substr($nombre, 0, -strlen($relativo))
            : '/';
    }
    return $base . ltrim($ruta, '/');
}

/** Redirige a otra página del sitio y corta la ejecución. */
function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

/** Ruta de una parte común de las páginas (encabezado, pie). Uso: require parcial('encabezado'); */
function parcial(string $nombre): string
{
    return __DIR__ . '/Vistas/' . $nombre . '.php';
}

/** true si la página se pidió enviando un formulario (POST). */
function esPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lee un campo de texto de un formulario, sin espacios al principio ni al final. */
function campo(string $nombre): string
{
    return trim((string) ($_POST[$nombre] ?? ''));
}

/**
 * Ícono (sticker) de un ingrediente para el estilo Heladera: public/img/ingredientes/<nombre>.svg.
 * Primero busca el nombre completo sin tildes ("Cebolla de verdeo" → cebolla-de-verdeo.svg);
 * si no está, la primera palabra ("Pollo asado" → pollo.svg, "Cebolla morada" → cebolla.svg).
 * Si no hay dibujo, devuelve el frasco genérico. Los ingredientes propios (los que un usuario agregó
 * desde su despensa) siempre llevan el frasco: es el dibujo que no usa ningún producto de la lista.
 */
function iconoIngrediente(string $nombre, bool $propio = false): string
{
    if ($propio) {
        return url('img/ingredientes/frasco.svg');
    }
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($nombre)) ?: $nombre;
    $palabras = preg_split('/[^a-z]+/', strtolower($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ([implode('-', $palabras), $palabras[0] ?? ''] as $candidato) {
        $archivo = 'img/ingredientes/' . $candidato . '.svg';
        if ($candidato !== '' && is_file(dirname(__DIR__) . '/public/' . $archivo)) {
            return url($archivo);
        }
    }
    return url('img/ingredientes/frasco.svg');
}

/** Enlace "← Volver a …" (flecha a mano) que va arriba de las páginas de administración. */
function enlaceVolver(string $ruta, string $texto): string
{
    return '<p><a class="volver" href="' . e(url($ruta)) . '"><span class="volver__flecha" aria-hidden="true">←</span> '
        . e($texto) . '</a></p>';
}

/**
 * URL de un archivo de public/ (CSS o JS) con su fecha de modificación al final (?v=…).
 * Así el navegador baja la versión nueva cada vez que el archivo cambia, en lugar de usar la guardada.
 */
function recurso(string $ruta): string
{
    $archivo = dirname(__DIR__) . '/public/' . $ruta;
    return url($ruta) . (is_file($archivo) ? '?v=' . filemtime($archivo) : '');
}
