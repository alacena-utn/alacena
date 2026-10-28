<?php

declare(strict_types=1);

namespace Alacena\Repositorios;

use DomainException;
use PDO;
use PDOException;

/** Operaciones exclusivas de P5-B; no altera el repositorio compartido. */
final class IngredienteAdminRepositorio
{
    public function __construct(private PDO $db)
    {
    }

    public function categorias(): array
    {
        return $this->db->query('SELECT id, nombre FROM categoria ORDER BY orden, nombre')->fetchAll();
    }

    public function obtener(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM ingrediente WHERE id = :id');
        $consulta->execute(['id' => $id]);
        return $consulta->fetch() ?: null;
    }

    public function existeNombre(string $nombre, int $exceptoId = 0): bool
    {
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM ingrediente WHERE nombre = :nombre AND id <> :id');
        $consulta->execute(['nombre' => $nombre, 'id' => $exceptoId]);
        return (int) $consulta->fetchColumn() > 0;
    }

    /** Los datos llegan normalizados y validados desde IngredienteAdminControlador. */
    public function guardar(int $id, array $datos): int
    {
        $parametros = [
            'nombre' => $datos['nombre'],
            'categoria_id' => (int) $datos['categoria_id'],
            'unidad_default' => $datos['unidad_default'],
            'icono' => $datos['icono'] === '' ? null : $datos['icono'],
            'gramos_por_unidad' => $datos['gramos_por_unidad'] === '' ? null : $datos['gramos_por_unidad'],
            'es_basico' => (int) $datos['es_basico'],
            'perecedero' => (int) $datos['perecedero'],
            'vida_util_dias' => $datos['vida_util_dias'] === '' ? null : (int) $datos['vida_util_dias'],
        ];
        if ($id > 0) {
            if ($this->obtener($id) === null) {
                throw new DomainException('El ingrediente ya no existe.');
            }
            $sql = 'UPDATE ingrediente SET nombre = :nombre, categoria_id = :categoria_id,
                    unidad_default = :unidad_default, icono = :icono, gramos_por_unidad = :gramos_por_unidad,
                    es_basico = :es_basico, perecedero = :perecedero, vida_util_dias = :vida_util_dias
                    WHERE id = :id';
            $parametros['id'] = $id;
        } else {
            $sql = 'INSERT INTO ingrediente
                    (nombre, categoria_id, unidad_default, icono, gramos_por_unidad,
                     es_basico, perecedero, vida_util_dias)
                    VALUES (:nombre, :categoria_id, :unidad_default, :icono, :gramos_por_unidad,
                            :es_basico, :perecedero, :vida_util_dias)';
        }
        $this->db->prepare($sql)->execute($parametros);
        return $id > 0 ? $id : (int) $this->db->lastInsertId();
    }

    public function borrar(int $id): void
    {
        // No se borran recetas, despensas ni preferencias para permitir una baja.
        $relaciones = [
            ['receta_ingrediente', 'ingrediente_id'],
            ['despensa_item', 'ingrediente_id'],
            ['usuario_basico', 'ingrediente_id'],
            ['restriccion_usuario', 'ingrediente_id'],
            ['sustitucion', 'ingrediente_id'],
            ['sustitucion', 'reemplazo_id'],
        ];
        foreach ($relaciones as [$tabla, $columna]) {
            // Tabla y columna pertenecen a esta lista fija, nunca al formulario.
            $consulta = $this->db->prepare("SELECT COUNT(*) FROM {$tabla} WHERE {$columna} = :id");
            $consulta->execute(['id' => $id]);
            if ((int) $consulta->fetchColumn() > 0) {
                throw new DomainException(
                    'No se puede eliminar: el ingrediente está en uso en recetas, despensas, '
                    . 'preferencias o sustituciones.'
                );
            }
        }
        try {
            $consulta = $this->db->prepare('DELETE FROM ingrediente WHERE id = :id');
            $consulta->execute(['id' => $id]);
        } catch (PDOException $error) {
            // Las claves foráneas también protegen ante una referencia creada simultáneamente.
            if ((int) ($error->errorInfo[1] ?? 0) === 1451) {
                throw new DomainException('No se puede eliminar: el ingrediente está en uso.', 0, $error);
            }
            throw $error;
        }
        if ($consulta->rowCount() === 0) {
            throw new DomainException('El ingrediente ya no existe.');
        }
        // MySQL elimina únicamente sus sinónimos mediante ON DELETE CASCADE.
    }
}
