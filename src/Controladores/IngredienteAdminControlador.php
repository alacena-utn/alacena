<?php

declare(strict_types=1);

namespace Alacena\Controladores;

use Alacena\Repositorios\IngredienteAdminRepositorio;

/** Validaciones exclusivas del formulario administrativo de ingredientes (P5-B). */
final class IngredienteAdminControlador
{
    public function __construct(private IngredienteAdminRepositorio $repo)
    {
    }

    public static function datos(array $entrada): array
    {
        $datos = [];
        foreach (
            ['nombre', 'categoria_id', 'unidad_default', 'icono', 'gramos_por_unidad', 'vida_util_dias'] as $campo
        ) {
            $datos[$campo] = is_string($entrada[$campo] ?? '') ? trim($entrada[$campo] ?? '') : '';
        }
        $datos['gramos_por_unidad'] = str_replace(',', '.', $datos['gramos_por_unidad']);
        $datos['es_basico'] = ($entrada['es_basico'] ?? '') === '1' ? 1 : 0;
        $datos['perecedero'] = ($entrada['perecedero'] ?? '') === '1' ? 1 : 0;
        return $datos;
    }

    public static function validar(array $datos, array $categorias): array
    {
        $errores = [];
        if (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 80) {
            $errores['nombre'] = 'El nombre debe tener entre 2 y 80 caracteres.';
        }
        $categoria = filter_var($datos['categoria_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($categoria === false || !in_array($categoria, array_map('intval', array_column($categorias, 'id')), true)) {
            $errores['categoria_id'] = 'Seleccioná una categoría existente.';
        }
        if ($datos['unidad_default'] === '' || mb_strlen($datos['unidad_default']) > 10) {
            $errores['unidad_default'] =
                'La unidad es obligatoria y admite hasta 10 caracteres (por ejemplo: g, ml o u).';
        }
        if (mb_strlen($datos['icono']) > 60) {
            $errores['icono'] = 'La clave del ícono admite hasta 60 caracteres.';
        }
        $gramos = $datos['gramos_por_unidad'];
        if ($gramos !== '' && (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $gramos) || (float) $gramos <= 0)) {
            $errores['gramos_por_unidad'] =
                'Ingresá un peso mayor que cero, hasta 999999,99 y con hasta dos decimales.';
        }
        $dias = $datos['vida_util_dias'];
        if (
            $dias !== ''
            && filter_var($dias, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 32767]]) === false
        ) {
            $errores['vida_util_dias'] = 'La vida útil debe ser un número entero entre 1 y 32767 días.';
        }
        return $errores;
    }

    public function guardar(int $id, array $datos, array $categorias): array
    {
        $errores = self::validar($datos, $categorias);
        if (!isset($errores['nombre']) && $this->repo->existeNombre($datos['nombre'], $id)) {
            $errores['nombre'] = 'Ya existe un ingrediente con ese nombre.';
        }
        if ($errores === []) {
            $this->repo->guardar($id, $datos);
        }
        return $errores;
    }
}
