<?php

/**
 * Importador de recetas (P3). Lee database/recetas/*.json (formato en docs/guia-recetas.md).
 *
 *   php scripts/importar-recetas.php            valida y carga (o actualiza) las recetas en la base
 *   php scripts/importar-recetas.php --validar  solo valida: no toca nada
 *   php scripts/importar-recetas.php --sql      valida y escribe database/recetas.sql
 *   php scripts/importar-recetas.php archivo.json ...   lo mismo, pero con esos archivos
 *
 * Con Docker: docker compose exec web php scripts/importar-recetas.php
 * Valida contra el catálogo de ingredientes de la base, así que la base tiene que estar cargada.
 * Si una receta tiene errores, no se carga ninguna (y el script termina con código 1).
 */

declare(strict_types=1);

use Cooki\Db;
use Cooki\Dominio\ImportadorRecetas;
use Cooki\Repositorios\RecetaRepositorio;

require dirname(__DIR__) . '/src/bootstrap.php';

$opciones = array_values(array_filter(array_slice($argv, 1), fn ($a) => str_starts_with($a, '--')));
$archivos = array_values(array_filter(array_slice($argv, 1), fn ($a) => !str_starts_with($a, '--')));
$soloValidar = in_array('--validar', $opciones, true);
$comoSql = in_array('--sql', $opciones, true);
$raiz = dirname(__DIR__);
if ($archivos === []) {
    $archivos = glob($raiz . '/database/recetas/*.json') ?: [];
    sort($archivos);
}
if ($archivos === []) {
    fwrite(STDERR, "No hay recetas en database/recetas/.\n");
    exit(1);
}

$db = Db::conexion();
$importador = new ImportadorRecetas(
    $db->query('SELECT id, nombre, categoria_id FROM ingrediente WHERE usuario_id IS NULL')->fetchAll(),
    $db->query('SELECT s.texto, s.ingrediente_id FROM sinonimo s JOIN ingrediente i ON i.id = s.ingrediente_id
                WHERE i.usuario_id IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR),
    $db->query('SELECT nombre, id FROM etiqueta')->fetchAll(PDO::FETCH_KEY_PAIR)
);

$recetas = [];
$conErrores = 0;
$slugs = [];
foreach ($archivos as $archivo) {
    $nombre = basename($archivo);
    $json = json_decode((string) @file_get_contents($archivo), true);
    $resultado = $importador->validar($json);
    $slug = $resultado['receta']['datos']['slug'] ?? null;
    if ($slug !== null && isset($slugs[$slug])) {
        $resultado['errores'][] = "Tiene el mismo título que {$slugs[$slug]}.";
    }
    if ($resultado['errores'] !== []) {
        $conErrores++;
        fwrite(STDERR, "✗ {$nombre}\n");
        foreach ($resultado['errores'] as $error) {
            fwrite(STDERR, "    · {$error}\n");
        }
        continue;
    }
    $slugs[$slug] = $nombre;
    $recetas[$nombre] = $resultado['receta'];
}

if ($conErrores > 0) {
    fwrite(STDERR, "\n{$conErrores} receta(s) con errores. No se cargó nada.\n");
    exit(1);
}
echo count($recetas) . " recetas válidas.\n";
if ($soloValidar) {
    exit(0);
}

if ($comoSql) {
    file_put_contents($raiz . '/database/recetas.sql', ImportadorRecetas::archivoSql($recetas));
    echo "Se escribió database/recetas.sql.\n";
    exit(0);
}

$repo = new RecetaRepositorio($db);
$autor = (int) $db->query("SELECT id FROM usuario WHERE rol = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
$buscar = $db->prepare('SELECT id FROM receta WHERE slug = :slug');
$nuevas = 0;
foreach ($recetas as $receta) {
    $buscar->execute(['slug' => $receta['datos']['slug']]);
    $id = (int) $buscar->fetchColumn();
    $nuevas += $id === 0 ? 1 : 0;
    $repo->guardar($receta['datos'], $receta['ingredientes'], $receta['pasos'], $receta['etiquetas'], $autor, $id);
}
echo "Listo: {$nuevas} nuevas y " . (count($recetas) - $nuevas) . " actualizadas.\n";
