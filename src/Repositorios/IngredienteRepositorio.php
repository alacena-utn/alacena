<?php

declare(strict_types=1);

namespace Alacena\Repositorios;

use PDO;

/** Acceso a datos de ingredientes. Toda consulta SQL de ingredientes vive acá. */
final class IngredienteRepositorio
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Busca por nombre o sinónimo. La intercalación utf8mb4_unicode_ci hace que
     * "morron" encuentre "Morrón" (ignora tildes y mayúsculas).
     *
     * @return list<array<string, mixed>>
     */
    public function buscar(string $texto, int $limite = 10): array
    {
        $sql = 'SELECT DISTINCT i.id, i.nombre, c.nombre AS categoria
                FROM ingrediente i
                JOIN categoria c ON c.id = i.categoria_id
                LEFT JOIN sinonimo s ON s.ingrediente_id = i.id
                WHERE i.nombre LIKE :texto1 OR s.texto LIKE :texto2
                ORDER BY i.nombre
                LIMIT :limite';
        $consulta = $this->db->prepare($sql);
        $patron = '%' . $texto . '%';
        $consulta->bindValue(':texto1', $patron);
        $consulta->bindValue(':texto2', $patron);
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    /**
     * Todos los ingredientes con su categoría, para armar listas desplegables (<select>).
     * @return list<array<string, mixed>>
     */
    public function todos(): array
    {
        return $this->db->query(
            'SELECT i.id, i.nombre, i.unidad_default, c.nombre AS categoria
             FROM ingrediente i JOIN categoria c ON c.id = i.categoria_id
             ORDER BY c.orden, i.nombre'
        )->fetchAll();
    }
}
