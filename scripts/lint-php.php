<?php

/**
 * Revisa la sintaxis de todos los .php del proyecto (equivale a "php -l" en cada archivo).
 * Uso: php scripts/lint-php.php
 */

$raiz = dirname(__DIR__);
$errores = 0;
$cantidad = 0;
$iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
foreach ($iterador as $archivo) {
    $ruta = str_replace('\\', '/', $archivo->getPathname());   // en Windows las rutas usan \\
    if ($archivo->getExtension() !== 'php' || preg_match('#/(vendor|\.git)/#', $ruta)) {
        continue;
    }
    $cantidad++;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($ruta) . ' 2>&1', $salida, $codigo);
    if ($codigo !== 0) {
        $errores++;
        echo implode(PHP_EOL, $salida), PHP_EOL;
    }
    $salida = [];
}
echo "Sintaxis revisada en {$cantidad} archivos: " . ($errores === 0 ? 'sin errores' : "{$errores} con errores") . PHP_EOL;
exit($errores === 0 ? 0 : 1);
