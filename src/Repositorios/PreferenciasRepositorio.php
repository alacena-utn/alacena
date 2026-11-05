<?php

declare(strict_types=1);

namespace Cooki\Repositorios;

use PDO;

/**
 * Preferencias de cocina de cada usuario:
 *   - "Siempre tengo" (usuario_basico): cuentan como disponibles en todas las recetas y no se descuentan al cocinar.
 *   - "No puedo comer" (restriccion_usuario): alergias o lo que no come; esas recetas no se recomiendan.
 *   - Favoritas (favorito): las recetas marcadas con ♥.
 */
final class PreferenciasRepositorio
{
    /** Tabla de cada lista. Es una lista fija: el nombre de la tabla nunca viene del usuario. */
    public const LISTAS = ['basicos' => 'usuario_basico', 'restricciones' => 'restriccion_usuario'];

    public function __construct(private PDO $db)
    {
    }

    /**
     * Ingredientes de una lista ('basicos' o 'restricciones'), ordenados por nombre.
     * @return list<array{id: int, nombre: string}>
     */
    public function ingredientes(int $usuarioId, string $lista): array
    {
        $tabla = self::LISTAS[$lista];
        $consulta = $this->db->prepare(
            "SELECT i.id, i.nombre FROM {$tabla} t JOIN ingrediente i ON i.id = t.ingrediente_id
             WHERE t.usuario_id = :usuario ORDER BY i.nombre"
        );
        $consulta->execute(['usuario' => $usuarioId]);
        return $consulta->fetchAll();
    }

    /** Agrega un ingrediente a la lista. Si ya estaba, no hace nada. */
    public function agregar(int $usuarioId, string $lista, int $ingredienteId): void
    {
        $tabla = self::LISTAS[$lista];
        $this->db->prepare(
            "INSERT IGNORE INTO {$tabla} (usuario_id, ingrediente_id) VALUES (:usuario, :ingrediente)"
        )->execute(['usuario' => $usuarioId, 'ingrediente' => $ingredienteId]);
    }

    public function quitar(int $usuarioId, string $lista, int $ingredienteId): void
    {
        $tabla = self::LISTAS[$lista];
        $this->db->prepare(
            "DELETE FROM {$tabla} WHERE usuario_id = :usuario AND ingrediente_id = :ingrediente"
        )->execute(['usuario' => $usuarioId, 'ingrediente' => $ingredienteId]);
    }

    /** Marca o desmarca una receta como favorita. Devuelve true si quedó marcada. */
    public function alternarFavorita(int $usuarioId, int $recetaId): bool
    {
        $borrar = $this->db->prepare('DELETE FROM favorito WHERE usuario_id = :usuario AND receta_id = :receta');
        $borrar->execute(['usuario' => $usuarioId, 'receta' => $recetaId]);
        if ($borrar->rowCount() > 0) {
            return false;
        }
        $this->db->prepare(
            'INSERT INTO favorito (usuario_id, receta_id)
             SELECT :usuario, id FROM receta WHERE id = :receta'
        )->execute(['usuario' => $usuarioId, 'receta' => $recetaId]);
        return true;
    }

    /** @return list<int> ids de las recetas favoritas */
    public function favoritas(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT receta_id FROM favorito WHERE usuario_id = :usuario ORDER BY creado_en DESC'
        );
        $consulta->execute(['usuario' => $usuarioId]);
        return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    }
}
