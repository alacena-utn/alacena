<?php

declare(strict_types=1);

namespace Alacena\Repositorios;

use PDO;

/** Acceso a las recetas, sus ingredientes, pasos y etiquetas. */
final class RecetaRepositorio
{
    public const DIFICULTADES = [1 => 'Fácil', 2 => 'Media', 3 => 'Difícil'];

    public function __construct(private PDO $db)
    {
    }

    /**
     * Buscador de recetas. Filtros posibles (todos opcionales):
     *   q          texto: busca en el título, la descripción y los ingredientes (también por sinónimo)
     *   dificultad 1, 2 o 3
     *   etiqueta   id de etiqueta
     *   puedo      true: solo las que se pueden hacer con lo que hay en la despensa
     * Si se pasa $usuarioId, cada receta trae cuántos ingredientes tiene el usuario ("tengo").
     *
     * @param array{q?: string, dificultad?: int, etiqueta?: int, puedo?: bool} $filtros
     * @return list<array<string, mixed>>
     */
    public function buscar(array $filtros = [], int $usuarioId = 0): array
    {
        $condiciones = [];
        $parametros = ['usuario' => $usuarioId];

        $texto = trim($filtros['q'] ?? '');
        if ($texto !== '') {
            $condiciones[] = '(r.titulo LIKE :q1 OR r.descripcion LIKE :q2 OR EXISTS (
                SELECT 1 FROM receta_ingrediente ri2
                JOIN ingrediente i2 ON i2.id = ri2.ingrediente_id
                LEFT JOIN sinonimo s ON s.ingrediente_id = i2.id
                WHERE ri2.receta_id = r.id AND (i2.nombre LIKE :q3 OR s.texto LIKE :q4)))';
            foreach (['q1', 'q2', 'q3', 'q4'] as $clave) {
                $parametros[$clave] = '%' . $texto . '%';
            }
        }
        if (!empty($filtros['dificultad'])) {
            $condiciones[] = 'r.dificultad = :dificultad';
            $parametros['dificultad'] = (int) $filtros['dificultad'];
        }
        if (!empty($filtros['etiqueta'])) {
            $condiciones[] = 'EXISTS (SELECT 1 FROM receta_etiqueta re
                WHERE re.receta_id = r.id AND re.etiqueta_id = :etiqueta)';
            $parametros['etiqueta'] = (int) $filtros['etiqueta'];
        }

        // La consulta de resumen va adentro de otra para poder filtrar y ordenar por "tengo" y "total"
        $sql = 'SELECT * FROM (' . self::SELECT_RESUMEN
            . ($condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '')
            . ' GROUP BY r.id) AS resumen'
            . (!empty($filtros['puedo']) ? ' WHERE tengo >= total' : '')
            . ' ORDER BY titulo';
        $consulta = $this->db->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    /**
     * Recetas ordenadas por cuántos de sus ingredientes tiene el usuario (las más completas primero).
     * @return list<array<string, mixed>>
     */
    public function sugeridas(int $usuarioId, int $limite = 6): array
    {
        $sql = 'SELECT * FROM (' . self::SELECT_RESUMEN . ' GROUP BY r.id) AS resumen
                WHERE tengo > 0 AND total > 0
                ORDER BY tengo / total DESC, total - tengo, titulo
                LIMIT ' . max(1, $limite);
        $consulta = $this->db->prepare($sql);
        $consulta->execute(['usuario' => $usuarioId]);
        return $consulta->fetchAll();
    }

    /**
     * Datos de una receta con sus ingredientes (indicando si el usuario los tiene), pasos y etiquetas.
     * @return array<string, mixed>|null
     */
    public function obtener(int $id, int $usuarioId = 0): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT r.*, u.nombre AS autor FROM receta r LEFT JOIN usuario u ON u.id = r.autor_id WHERE r.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $receta = $consulta->fetch();
        if (!$receta) {
            return null;
        }

        $consulta = $this->db->prepare(
            'SELECT ri.ingrediente_id, i.nombre, ri.cantidad, ri.unidad, ri.es_opcional, ri.nota, i.es_basico,
                    EXISTS (SELECT 1 FROM despensa_item d
                            WHERE d.usuario_id = :usuario AND d.ingrediente_id = ri.ingrediente_id AND d.nivel > 0
                              AND (d.fecha_vencimiento IS NULL OR d.fecha_vencimiento >= CURDATE())) AS en_despensa
             FROM receta_ingrediente ri
             JOIN ingrediente i ON i.id = ri.ingrediente_id
             WHERE ri.receta_id = :receta
             ORDER BY ri.es_opcional, ri.peso DESC, i.nombre'
        );
        $consulta->execute(['usuario' => $usuarioId, 'receta' => $id]);
        $receta['ingredientes'] = $consulta->fetchAll();

        $consulta = $this->db->prepare('SELECT numero, instruccion FROM paso WHERE receta_id = :id ORDER BY numero');
        $consulta->execute(['id' => $id]);
        $receta['pasos'] = $consulta->fetchAll();

        $consulta = $this->db->prepare(
            'SELECT e.id, e.nombre FROM receta_etiqueta re JOIN etiqueta e ON e.id = re.etiqueta_id
             WHERE re.receta_id = :id ORDER BY e.nombre'
        );
        $consulta->execute(['id' => $id]);
        $receta['etiquetas'] = $consulta->fetchAll();
        return $receta;
    }

    /** @return list<array{id: int, nombre: string}> */
    public function etiquetas(): array
    {
        return $this->db->query('SELECT id, nombre FROM etiqueta ORDER BY nombre')->fetchAll();
    }

    /**
     * Alta (si $id es 0) o modificación de una receta completa, en una transacción:
     * si algo falla, no queda nada guardado a medias.
     *
     * @param array<string, mixed> $datos titulo, descripcion, porciones_base, minutos_prep, minutos_coccion, dificultad
     * @param list<array{id: int, cantidad: ?float, unidad: string, opcional: bool}> $ingredientes
     * @param list<string> $pasos
     * @param list<int> $etiquetas
     */
    public function guardar(
        array $datos,
        array $ingredientes,
        array $pasos,
        array $etiquetas,
        int $autorId,
        int $id = 0
    ): int {
        $this->db->beginTransaction();
        try {
            $valores = [
                'titulo' => $datos['titulo'],
                'slug' => $this->slugUnico($datos['titulo'], $id),
                'descripcion' => $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
                'porciones' => $datos['porciones_base'],
                'prep' => $datos['minutos_prep'],
                'coccion' => $datos['minutos_coccion'],
                'dificultad' => $datos['dificultad'],
            ];
            if ($id === 0) {
                $this->db->prepare(
                    'INSERT INTO receta (titulo, slug, descripcion, porciones_base, minutos_prep, minutos_coccion,
                                         dificultad, fuente, autor_id)
                     VALUES (:titulo, :slug, :descripcion, :porciones, :prep, :coccion, :dificultad, :fuente, :autor)'
                )->execute($valores + ['fuente' => 'Receta del equipo', 'autor' => $autorId]);
                $id = (int) $this->db->lastInsertId();
            } else {
                $this->db->prepare(
                    'UPDATE receta SET titulo = :titulo, slug = :slug, descripcion = :descripcion,
                            porciones_base = :porciones, minutos_prep = :prep, minutos_coccion = :coccion,
                            dificultad = :dificultad
                     WHERE id = :id'
                )->execute($valores + ['id' => $id]);
                // Lo más simple y seguro: borrar los ingredientes, pasos y etiquetas y volver a cargarlos.
                foreach (['receta_ingrediente', 'paso', 'receta_etiqueta'] as $tabla) {
                    $this->db->prepare("DELETE FROM {$tabla} WHERE receta_id = :id")->execute(['id' => $id]);
                }
            }

            $insertar = $this->db->prepare(
                'INSERT INTO receta_ingrediente (receta_id, ingrediente_id, cantidad, unidad, es_opcional)
                 VALUES (:receta, :ingrediente, :cantidad, :unidad, :opcional)'
            );
            foreach ($ingredientes as $ing) {
                $insertar->execute([
                    'receta' => $id,
                    'ingrediente' => $ing['id'],
                    'cantidad' => $ing['cantidad'],
                    'unidad' => $ing['unidad'] !== '' ? $ing['unidad'] : null,
                    'opcional' => $ing['opcional'] ? 1 : 0,
                ]);
            }
            $insertar = $this->db->prepare(
                'INSERT INTO paso (receta_id, numero, instruccion) VALUES (:receta, :numero, :texto)'
            );
            foreach (array_values($pasos) as $i => $texto) {
                $insertar->execute(['receta' => $id, 'numero' => $i + 1, 'texto' => $texto]);
            }
            $insertar = $this->db->prepare(
                'INSERT INTO receta_etiqueta (receta_id, etiqueta_id) VALUES (:receta, :etiqueta)'
            );
            foreach (array_unique($etiquetas) as $etiqueta) {
                $insertar->execute(['receta' => $id, 'etiqueta' => $etiqueta]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function borrar(int $id): void
    {
        // receta_ingrediente, paso y receta_etiqueta se borran solos (ON DELETE CASCADE)
        $this->db->prepare('DELETE FROM receta WHERE id = :id')->execute(['id' => $id]);
    }

    /** "Arroz con pollo" → "arroz-con-pollo" (y "arroz-con-pollo-2" si ya existe). */
    private function slugUnico(string $titulo, int $exceptoId): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($titulo)) ?: $titulo;
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($texto)), '-') ?: 'receta';
        $slug = $base;
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM receta WHERE slug = :slug AND id <> :id');
        for ($n = 2; $consulta->execute(['slug' => $slug, 'id' => $exceptoId]) && $consulta->fetchColumn() > 0; $n++) {
            $slug = $base . '-' . $n;
        }
        return $slug;
    }

    /**
     * Total de ingredientes obligatorios de cada receta y cuántos tiene el usuario.
     * Los básicos (sal, aceite, pimienta…) se cuentan como disponibles siempre.
     * Lo vencido o agotado (nivel 0) no cuenta.
     */
    private const SELECT_RESUMEN = 'SELECT r.id, r.titulo, r.descripcion, r.dificultad, r.porciones_base,
            r.minutos_prep + r.minutos_coccion AS minutos,
            COUNT(CASE WHEN ri.es_opcional = 0 THEN 1 END) AS total,
            COUNT(CASE WHEN ri.es_opcional = 0
                        AND (d.ingrediente_id IS NOT NULL OR i.es_basico = 1) THEN 1 END) AS tengo,
            (SELECT GROUP_CONCAT(e.nombre ORDER BY e.nombre SEPARATOR \', \')
               FROM receta_etiqueta re JOIN etiqueta e ON e.id = re.etiqueta_id
              WHERE re.receta_id = r.id) AS etiquetas
        FROM receta r
        LEFT JOIN receta_ingrediente ri ON ri.receta_id = r.id
        LEFT JOIN ingrediente i ON i.id = ri.ingrediente_id
        LEFT JOIN (SELECT DISTINCT ingrediente_id FROM despensa_item
                    WHERE usuario_id = :usuario AND nivel > 0
                      AND (fecha_vencimiento IS NULL OR fecha_vencimiento >= CURDATE())) d
               ON d.ingrediente_id = ri.ingrediente_id';
}
