<?php

declare(strict_types=1);

namespace Alacena\Controladores;

use Alacena\Db;
use Alacena\Http\Respuesta;
use Alacena\Repositorios\IngredienteRepositorio;

/** GET /api/ingredientes?q=texto — buscador con sinónimos (ejemplo completo de punta a punta). */
final class IngredientesControlador
{
    public static function buscar(): void
    {
        $texto = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($texto) < 2) {
            Respuesta::error('Escribí al menos 2 letras', 400);
            return;
        }
        $repo = new IngredienteRepositorio(Db::conexion());
        Respuesta::json(['resultados' => $repo->buscar($texto)]);
    }
}
