<?php

declare(strict_types=1);

namespace Alacena\Repositorios;

use PDO;

/**
 * Acceso a datos de ingredientes. Toda consulta SQL de ingredientes vive acá.
 * Hay dos clases: los de la lista general (usuario_id NULL) y los propios que un usuario agrega
 * desde su despensa cuando no encuentra el que busca (solo los ve él; el dibujo es el frasco).
 */
final class IngredienteRepositorio
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Busca por nombre o sinónimo. La intercalación utf8mb4_unicode_ci hace que
     * "morron" encuentre "Morrón" (ignora tildes y mayúsculas). Solo la lista general.
     *
     * @return list<array<string, mixed>>
     */
    public function buscar(string $texto, int $limite = 10): array
    {
        $sql = 'SELECT DISTINCT i.id, i.nombre, c.nombre AS categoria
                FROM ingrediente i
                JOIN categoria c ON c.id = i.categoria_id
                LEFT JOIN sinonimo s ON s.ingrediente_id = i.id
                WHERE i.usuario_id IS NULL AND (i.nombre LIKE :texto1 OR s.texto LIKE :texto2)
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
     * Los ingredientes de la lista general con su categoría, para armar listas desplegables (<select>).
     * Con $usuarioId suma los propios de ese usuario (la despensa). "propio" = 1 si es de él;
     * "sinonimos" = los otros nombres separados por coma (para el buscador con stickers).
     * @return list<array<string, mixed>>
     */
    public function todos(int $usuarioId = 0): array
    {
        $consulta = $this->db->prepare(
            'SELECT i.id, i.nombre, i.unidad_default, c.nombre AS categoria, i.usuario_id IS NOT NULL AS propio,
                    (SELECT GROUP_CONCAT(s.texto SEPARATOR \',\') FROM sinonimo s
                     WHERE s.ingrediente_id = i.id) AS sinonimos
             FROM ingrediente i JOIN categoria c ON c.id = i.categoria_id
             WHERE i.usuario_id IS NULL OR i.usuario_id = :usuario
             ORDER BY c.orden, i.nombre'
        );
        $consulta->execute(['usuario' => $usuarioId]);
        return $consulta->fetchAll();
    }

    /**
     * El ingrediente que se llama así (o que tiene ese sinónimo) en la lista general o entre los propios
     * del usuario. Sirve para no crear "Arroz" propio si ya está en la lista. Devuelve el id o null.
     */
    public function idPorNombre(string $nombre, int $usuarioId): ?int
    {
        $consulta = $this->db->prepare(
            'SELECT i.id FROM ingrediente i
             WHERE (i.usuario_id IS NULL OR i.usuario_id = :usuario)
               AND (i.nombre = :nombre1
                    OR EXISTS (SELECT 1 FROM sinonimo s WHERE s.ingrediente_id = i.id AND s.texto = :nombre2))
             ORDER BY i.usuario_id IS NOT NULL
             LIMIT 1'
        );
        $consulta->execute(['usuario' => $usuarioId, 'nombre1' => $nombre, 'nombre2' => $nombre]);
        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** Crea un ingrediente propio del usuario (lo agregó desde su despensa). Devuelve el id. */
    public function crearPropio(int $usuarioId, string $nombre, int $categoriaId, string $unidad): int
    {
        $this->db->prepare(
            'INSERT INTO ingrediente (nombre, categoria_id, unidad_default, usuario_id)
             VALUES (:nombre, :categoria, :unidad, :usuario)'
        )->execute(['nombre' => $nombre, 'categoria' => $categoriaId, 'unidad' => $unidad, 'usuario' => $usuarioId]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Pasa un ingrediente propio a la lista general (lo decide el administrador).
     * Devuelve false si en la lista general ya hay uno con ese nombre.
     */
    public function pasarALaListaGeneral(int $id): bool
    {
        $ingrediente = $this->obtener($id);
        $yaEsGeneral = $ingrediente === null || $ingrediente['usuario_id'] === null;
        if ($yaEsGeneral || $this->existeNombre($ingrediente['nombre'], $id)) {
            return false;
        }
        $this->db->prepare('UPDATE ingrediente SET usuario_id = NULL WHERE id = :id')->execute(['id' => $id]);
        return true;
    }

    /**
     * Listado para el ABM, con buscador por nombre o sinónimo y filtro por categoría.
     * Incluye en cuántas recetas y despensas se usa cada ingrediente, y de quién es si es propio ("duenio").
     * Con $soloPropios = true, solo los que agregaron los usuarios.
     * @return list<array<string, mixed>>
     */
    public function listar(string $texto = '', int $categoriaId = 0, bool $soloPropios = false): array
    {
        $sql = 'SELECT i.*, c.nombre AS categoria, u.nombre AS duenio,
                       (SELECT COUNT(*) FROM receta_ingrediente ri WHERE ri.ingrediente_id = i.id) AS en_recetas,
                       (SELECT COUNT(*) FROM despensa_item d WHERE d.ingrediente_id = i.id) AS en_despensas
                FROM ingrediente i
                JOIN categoria c ON c.id = i.categoria_id
                LEFT JOIN usuario u ON u.id = i.usuario_id
                WHERE (i.nombre LIKE :texto1
                       OR EXISTS (SELECT 1 FROM sinonimo s WHERE s.ingrediente_id = i.id AND s.texto LIKE :texto2))
                  AND (:categoria1 = 0 OR i.categoria_id = :categoria2)
                  AND (:propios = 0 OR i.usuario_id IS NOT NULL)
                ORDER BY i.usuario_id IS NOT NULL, c.orden, i.nombre';
        $consulta = $this->db->prepare($sql);
        $consulta->execute([
            'texto1' => "%{$texto}%",
            'texto2' => "%{$texto}%",
            'categoria1' => $categoriaId,
            'categoria2' => $categoriaId,
            'propios' => $soloPropios ? 1 : 0,
        ]);
        return $consulta->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function obtener(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM ingrediente WHERE id = :id');
        $consulta->execute(['id' => $id]);
        return $consulta->fetch() ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function categorias(): array
    {
        return $this->db->query('SELECT id, nombre FROM categoria ORDER BY orden')->fetchAll();
    }

    /** ¿Ya hay uno con ese nombre? En la lista general ($usuarioId 0) o entre los propios de un usuario. */
    public function existeNombre(string $nombre, int $exceptoId = 0, int $usuarioId = 0): bool
    {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM ingrediente WHERE nombre = :nombre AND id <> :id AND ambito = :ambito'
        );
        $consulta->execute(['nombre' => $nombre, 'id' => $exceptoId, 'ambito' => $usuarioId]);
        return (int) $consulta->fetchColumn() > 0;
    }

    /**
     * Alta (si $id es 0) o modificación.
     * @param array{nombre: string, categoria_id: int, unidad_default: string, es_basico: bool,
     *              perecedero: bool, vida_util_dias: ?int} $datos
     */
    public function guardar(array $datos, int $id = 0): int
    {
        $valores = [
            'nombre' => $datos['nombre'],
            'categoria' => $datos['categoria_id'],
            'unidad' => $datos['unidad_default'],
            'basico' => $datos['es_basico'] ? 1 : 0,
            'perecedero' => $datos['perecedero'] ? 1 : 0,
            'vida' => $datos['vida_util_dias'],
        ];
        if ($id === 0) {
            $this->db->prepare(
                'INSERT INTO ingrediente (nombre, categoria_id, unidad_default, es_basico, perecedero, vida_util_dias)
                 VALUES (:nombre, :categoria, :unidad, :basico, :perecedero, :vida)'
            )->execute($valores);
            return (int) $this->db->lastInsertId();
        }
        $this->db->prepare(
            'UPDATE ingrediente SET nombre = :nombre, categoria_id = :categoria, unidad_default = :unidad,
                    es_basico = :basico, perecedero = :perecedero, vida_util_dias = :vida
             WHERE id = :id'
        )->execute($valores + ['id' => $id]);
        return $id;
    }

    /**
     * Borra un ingrediente. Si lo usa alguna receta o despensa, la base no lo permite
     * (clave foránea) y se devuelve false sin borrar nada.
     */
    public function borrar(int $id): bool
    {
        try {
            $this->db->prepare('DELETE FROM ingrediente WHERE id = :id')->execute(['id' => $id]);
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {   // violación de clave foránea
                return false;
            }
            throw $e;
        }
    }
}
