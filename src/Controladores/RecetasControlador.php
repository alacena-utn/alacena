<?php

declare(strict_types=1);

namespace Cooki\Controladores;

use Cooki\Db;
use Cooki\Dominio\Paginacion;
use Cooki\Dominio\Porciones;
use Cooki\Http\ErrorHttp;
use Cooki\Http\Respuesta;
use Cooki\Repositorios\DespensaRepositorio;
use Cooki\Repositorios\PreferenciasRepositorio;
use Cooki\Repositorios\RecetaRepositorio;
use Cooki\Sesion;

/**
 * Recetas, recomendaciones, despensa y favoritas por la API (incremento 2).
 *   GET    /api/recetas?q=&pagina=&por_pagina=   catálogo paginado (con sesión, trae "tengo" y "favorita")
 *   GET    /api/recetas/{id}?porciones=N         una receta con cantidades y pasos para N porciones
 *   GET    /api/recomendaciones                  lo que puede cocinar el usuario, por puntaje (pide sesión)
 *   GET    /api/despensa                         la despensa del usuario (pide sesión)
 *   POST   /api/favoritos/{id}                   marca una receta como favorita (pide sesión)
 *   DELETE /api/favoritos/{id}                   la desmarca (pide sesión)
 */
final class RecetasControlador
{
    public static function listar(): void
    {
        $repo = new RecetaRepositorio(Db::conexion());
        $todas = $repo->buscar(['q' => trim((string) ($_GET['q'] ?? ''))], Sesion::idUsuario());
        $porPagina = max(1, min(50, (int) ($_GET['por_pagina'] ?? 12)));
        $paginas = new Paginacion(count($todas), $porPagina, (int) ($_GET['pagina'] ?? 1));
        Respuesta::json([
            'recetas' => array_map(self::resumen(...), $paginas->cortar($todas)),
            'paginacion' => [
                'pagina' => $paginas->pagina, 'paginas' => $paginas->paginas,
                'por_pagina' => $porPagina, 'total' => $paginas->total,
            ],
        ]);
    }

    /** @param array{id: string} $parametros */
    public static function ver(array $parametros): void
    {
        $receta = (new RecetaRepositorio(Db::conexion()))->obtener((int) $parametros['id'], Sesion::idUsuario());
        if ($receta === null) {
            throw new ErrorHttp('No existe esa receta', 404);
        }
        $base = (int) $receta['porciones_base'];
        $porciones = max(Porciones::MINIMO, min(Porciones::MAXIMO, (int) ($_GET['porciones'] ?? $base)));
        $factor = Porciones::factor($base, $porciones);
        $porSlug = [];
        $ingredientes = [];
        foreach ($receta['ingredientes'] as $ing) {
            $cantidad = $ing['cantidad'] === null ? null : (float) $ing['cantidad'];
            $porSlug[Porciones::slug($ing['nombre'])] = [
                'nombre' => $ing['nombre'], 'cantidad' => $cantidad, 'unidad' => $ing['unidad'],
            ];
            $ingredientes[] = [
                'id' => (int) $ing['ingrediente_id'],
                'nombre' => $ing['nombre'],
                'cantidad' => $cantidad === null ? null : round($cantidad * $factor, 2),
                'unidad' => $ing['unidad'],
                'texto' => Porciones::texto($cantidad, $ing['unidad'], $factor),
                'opcional' => (bool) $ing['es_opcional'],
                'tengo' => (bool) ($ing['en_despensa'] || $ing['es_basico']),
                'sustituto' => $ing['sustituto'],
            ];
        }
        Respuesta::json([
            'id' => (int) $receta['id'],
            'titulo' => $receta['titulo'],
            'descripcion' => $receta['descripcion'],
            'porciones' => $porciones,
            'porciones_base' => $base,
            'minutos' => (int) $receta['minutos_prep'] + (int) $receta['minutos_coccion'],
            'dificultad' => RecetaRepositorio::DIFICULTADES[(int) $receta['dificultad']] ?? '',
            'imagen' => $receta['imagen'] ? urlAbsoluta($receta['imagen']) : null,
            'etiquetas' => array_column($receta['etiquetas'], 'nombre'),
            'favorita' => (bool) $receta['favorita'],
            'ingredientes' => $ingredientes,
            'pasos' => array_map(fn (array $p) => [
                'numero' => (int) $p['numero'],
                'texto' => Porciones::pasoConCantidades($p['instruccion'], $porSlug, $factor),
                'segundos' => $p['segundos_timer'] === null ? null : (int) $p['segundos_timer'],
            ], $receta['pasos']),
        ]);
    }

    public static function recomendaciones(): void
    {
        $usuario = SesionControlador::requiereSesion();
        $limite = max(1, min(60, (int) ($_GET['limite'] ?? 20)));
        $lista = (new RecetaRepositorio(Db::conexion()))->recomendadas($usuario, $limite);
        Respuesta::json(['recomendaciones' => array_map(fn (array $r) => self::resumen($r) + [
            'puntaje' => $r['puntaje'],
            'grupo' => $r['grupo'],
            'faltantes' => $r['faltantes'],
            'sustituciones' => $r['sustituciones'],
            'usa_lo_que_vence' => $r['usa_lo_que_vence'],
        ], $lista)]);
    }

    public static function despensa(): void
    {
        $usuario = SesionControlador::requiereSesion();
        $items = (new DespensaRepositorio(Db::conexion()))->listar($usuario);
        Respuesta::json(['despensa' => array_map(fn (array $i) => [
            'id' => (int) $i['id'],
            'ingrediente_id' => (int) $i['ingrediente_id'],
            'nombre' => $i['nombre'],
            'nivel' => DespensaRepositorio::NIVELES[(int) $i['nivel']],
            'cantidad' => $i['cantidad'] === null ? null : (float) $i['cantidad'],
            'unidad' => $i['cantidad'] === null ? null : $i['unidad'],
            'vence' => $i['fecha_vencimiento'],
            'dias' => $i['dias'] === null ? null : (int) $i['dias'],
            'ubicacion' => $i['ubicacion'],
        ], $items)]);
    }

    /** @param array{id: string} $parametros */
    public static function marcarFavorita(array $parametros): void
    {
        self::cambiarFavorita((int) $parametros['id'], true);
    }

    /** @param array{id: string} $parametros */
    public static function desmarcarFavorita(array $parametros): void
    {
        self::cambiarFavorita((int) $parametros['id'], false);
    }

    private static function cambiarFavorita(int $receta, bool $marcar): void
    {
        $usuario = SesionControlador::requiereSesion();
        $repo = new RecetaRepositorio(Db::conexion());
        $actual = $repo->obtener($receta, $usuario);
        if ($actual === null) {
            throw new ErrorHttp('No existe esa receta', 404);
        }
        if ((bool) $actual['favorita'] !== $marcar) {
            (new PreferenciasRepositorio(Db::conexion()))->alternarFavorita($usuario, $receta);
        }
        Respuesta::json(['receta' => $receta, 'favorita' => $marcar]);
    }

    /**
     * @param array<string, mixed> $r fila de RecetaRepositorio::buscar()
     * @return array<string, mixed>
     */
    private static function resumen(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'titulo' => $r['titulo'],
            'descripcion' => $r['descripcion'],
            'minutos' => (int) $r['minutos'],
            'dificultad' => RecetaRepositorio::DIFICULTADES[(int) $r['dificultad']] ?? '',
            'porciones' => (int) $r['porciones_base'],
            'etiquetas' => array_values(array_filter(explode(', ', (string) $r['etiquetas']))),
            'ingredientes' => (int) $r['total'],
            'tengo' => (int) $r['tengo'],
            'favorita' => (bool) $r['favorita'],
        ];
    }
}
