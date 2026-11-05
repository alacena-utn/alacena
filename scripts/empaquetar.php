<?php

/**
 * Arma cooki-hosting.zip con lo que hay que subir al hosting (docs/publicar.md).
 * Deja afuera lo que solo sirve para desarrollar: pruebas, Docker, Composer, documentación y scripts.
 * Uso: php scripts/empaquetar.php
 */

$raiz = dirname(__DIR__);
$destino = $raiz . '/cooki-hosting.zip';
if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Falta la extensión zip de PHP. Alternativa: comprimir a mano public/, src/, database/ y .htaccess\n");
    exit(1);
}
$incluir = ['public', 'src', 'database', '.htaccess', '.env.example', 'README.md'];
$excluir = '#^public/img/recetas/(?!\.gitkeep$|\.htaccess$)#';   // las fotos subidas en tu compu no van

$zip = new ZipArchive();
if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "No se pudo crear {$destino}\n");
    exit(1);
}
$cantidad = 0;
foreach ($incluir as $elemento) {
    $ruta = $raiz . '/' . $elemento;
    $archivos = is_dir($ruta)
        ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS))
        : [new SplFileInfo($ruta)];
    foreach ($archivos as $archivo) {
        $relativa = ltrim(str_replace('\\', '/', substr($archivo->getPathname(), strlen($raiz))), '/');
        if (!$archivo->isFile() || preg_match($excluir, $relativa)) {
            continue;
        }
        $zip->addFile($archivo->getPathname(), 'cooki/' . $relativa);
        $cantidad++;
    }
}
$zip->close();
echo "Listo: cooki-hosting.zip con {$cantidad} archivos. Seguí con docs/publicar.md." . PHP_EOL;
