<?php

declare(strict_types=1);

namespace Cooki\Repositorios;

use Cooki\Dominio\Descuento;
use PDO;

/**
 * Modo cocina: la receta que el usuario está cocinando (sesion_cocina), los pasos que ya hizo
 * (paso_completado) y el descuento de la despensa al terminar.
 *
 * El progreso queda en la base: si recarga la página, cambia de dispositivo o vuelve mañana,
 * sigue en el mismo paso. Cada usuario cocina una receta a la vez.
 */
final class CocinaRepositorio
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Empieza a cocinar una receta. Si ya la estaba cocinando, sigue esa misma sesión (con las porciones nuevas);
     * si estaba cocinando otra, esa queda abandonada.
     */
    public function iniciar(int $usuarioId, int $recetaId, int $porciones): int
    {
        $actual = $this->enCurso($usuarioId);
        if ($actual !== null && (int) $actual['receta_id'] === $recetaId) {
            $this->db->prepare('UPDATE sesion_cocina SET porciones = :porciones WHERE id = :id')
                ->execute(['porciones' => $porciones, 'id' => $actual['id']]);
            return (int) $actual['id'];
        }
        $this->db->prepare(
            "UPDATE sesion_cocina SET estado = 'abandonada', finalizada_en = NOW()
             WHERE usuario_id = :usuario AND estado = 'en_curso'"
        )->execute(['usuario' => $usuarioId]);
        $this->db->prepare(
            'INSERT INTO sesion_cocina (usuario_id, receta_id, porciones) VALUES (:usuario, :receta, :porciones)'
        )->execute(['usuario' => $usuarioId, 'receta' => $recetaId, 'porciones' => $porciones]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * La receta que el usuario está cocinando ahora, con el título, cuántos pasos tiene y cuántos hizo.
     * @return array<string, mixed>|null
     */
    public function enCurso(int $usuarioId): ?array
    {
        $consulta = $this->db->prepare(
            "SELECT s.id, s.receta_id, s.porciones, s.iniciada_en, r.titulo,
                    (SELECT COUNT(*) FROM paso p WHERE p.receta_id = s.receta_id) AS pasos,
                    (SELECT COUNT(*) FROM paso_completado pc WHERE pc.sesion_id = s.id) AS hechos
             FROM sesion_cocina s JOIN receta r ON r.id = s.receta_id
             WHERE s.usuario_id = :usuario AND s.estado = 'en_curso'
             ORDER BY s.iniciada_en DESC LIMIT 1"
        );
        $consulta->execute(['usuario' => $usuarioId]);
        return $consulta->fetch() ?: null;
    }

    /**
     * Una sesión del usuario (de otro usuario no se puede ver) con los ids de los pasos hechos.
     * @return array<string, mixed>|null
     */
    public function obtener(int $usuarioId, int $sesionId): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM sesion_cocina WHERE id = :id AND usuario_id = :usuario'
        );
        $consulta->execute(['id' => $sesionId, 'usuario' => $usuarioId]);
        $sesion = $consulta->fetch();
        if (!$sesion) {
            return null;
        }
        $consulta = $this->db->prepare('SELECT paso_id FROM paso_completado WHERE sesion_id = :id');
        $consulta->execute(['id' => $sesionId]);
        $sesion['hechos'] = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
        return $sesion;
    }

    /** Marca o desmarca un paso. Solo si la sesión es del usuario, está en curso y el paso es de esa receta. */
    public function marcarPaso(int $usuarioId, int $sesionId, int $pasoId, bool $hecho): bool
    {
        $consulta = $this->db->prepare(
            "SELECT COUNT(*) FROM sesion_cocina s JOIN paso p ON p.receta_id = s.receta_id
             WHERE s.id = :sesion AND s.usuario_id = :usuario AND s.estado = 'en_curso' AND p.id = :paso"
        );
        $consulta->execute(['sesion' => $sesionId, 'usuario' => $usuarioId, 'paso' => $pasoId]);
        if ((int) $consulta->fetchColumn() === 0) {
            return false;
        }
        $sql = $hecho
            ? 'INSERT IGNORE INTO paso_completado (sesion_id, paso_id) VALUES (:sesion, :paso)'
            : 'DELETE FROM paso_completado WHERE sesion_id = :sesion AND paso_id = :paso';
        $this->db->prepare($sql)->execute(['sesion' => $sesionId, 'paso' => $pasoId]);
        return true;
    }

    /** Deja de cocinar sin descontar nada. */
    public function abandonar(int $usuarioId, int $sesionId): void
    {
        $this->db->prepare(
            "UPDATE sesion_cocina SET estado = 'abandonada', finalizada_en = NOW()
             WHERE id = :id AND usuario_id = :usuario AND estado = 'en_curso'"
        )->execute(['id' => $sesionId, 'usuario' => $usuarioId]);
    }

    /**
     * Lo que se descontaría de la despensa al terminar: cada ingrediente obligatorio de la receta que el usuario
     * tiene (sin vencer y con algo), salvo los básicos (sal, aceite... y los que marcó como "siempre tengo").
     * Si lo tiene en más de un lugar, se usa primero el que vence antes.
     * Cada fila trae cómo queda ("antes" y "despues") para mostrarlo antes de confirmar.
     * @return list<array<string, mixed>>
     */
    public function paraDescontar(int $usuarioId, int $sesionId): array
    {
        $consulta = $this->db->prepare(
            "SELECT ri.ingrediente_id, i.nombre, i.gramos_por_unidad, ri.cantidad AS receta_cantidad,
                    ri.unidad AS receta_unidad,
                    r.porciones_base, s.porciones,
                    (SELECT d.id FROM despensa_item d
                      WHERE d.usuario_id = s.usuario_id AND d.ingrediente_id = ri.ingrediente_id AND d.nivel > 0
                        AND (d.fecha_vencimiento IS NULL OR d.fecha_vencimiento >= CURDATE())
                      ORDER BY d.fecha_vencimiento IS NULL, d.fecha_vencimiento, d.id LIMIT 1) AS item_id
             FROM sesion_cocina s
             JOIN receta r ON r.id = s.receta_id
             JOIN receta_ingrediente ri ON ri.receta_id = s.receta_id AND ri.es_opcional = 0
             JOIN ingrediente i ON i.id = ri.ingrediente_id
             WHERE s.id = :sesion AND s.usuario_id = :usuario AND i.es_basico = 0
               AND NOT EXISTS (SELECT 1 FROM usuario_basico ub
                                WHERE ub.usuario_id = s.usuario_id AND ub.ingrediente_id = ri.ingrediente_id)
             ORDER BY ri.peso DESC, i.nombre"
        );
        $consulta->execute(['sesion' => $sesionId, 'usuario' => $usuarioId]);
        $items = $this->db->prepare('SELECT * FROM despensa_item WHERE id = :id');
        $resultado = [];
        foreach ($consulta->fetchAll() as $fila) {
            if ($fila['item_id'] === null) {
                continue;   // no lo tiene: no hay nada que descontar
            }
            $items->execute(['id' => $fila['item_id']]);
            $item = $items->fetch();
            $usado = $fila['receta_cantidad'] === null ? null
                : (float) $fila['receta_cantidad'] * (int) $fila['porciones'] / max(1, (int) $fila['porciones_base']);
            $gramos = $fila['gramos_por_unidad'] === null ? null : (float) $fila['gramos_por_unidad'];
            $despues = Descuento::aplicar($item, $usado, $fila['receta_unidad'], $gramos);
            $resultado[] = [
                'ingrediente_id' => (int) $fila['ingrediente_id'],
                'nombre' => $fila['nombre'],
                'item' => $item,
                'despues' => $despues,
            ];
        }
        return $resultado;
    }

    /**
     * Termina de cocinar: marca la sesión como completada y descuenta de la despensa los ingredientes elegidos.
     * Todo en una transacción. stock_descontado evita descontar dos veces (doble clic, volver atrás y reenviar):
     * si la sesión ya estaba terminada, no hace nada y devuelve null.
     *
     * @param list<int> $ingredientes ids de ingrediente a descontar (los que el usuario dejó tildados)
     * @return list<string>|null nombres de lo que se descontó
     */
    public function terminar(int $usuarioId, int $sesionId, array $ingredientes): ?array
    {
        $this->db->beginTransaction();
        try {
            $marcar = $this->db->prepare(
                "UPDATE sesion_cocina SET estado = 'completada', finalizada_en = NOW(), stock_descontado = TRUE
                 WHERE id = :id AND usuario_id = :usuario AND estado = 'en_curso' AND stock_descontado = FALSE"
            );
            $marcar->execute(['id' => $sesionId, 'usuario' => $usuarioId]);
            if ($marcar->rowCount() === 0) {
                $this->db->rollBack();
                return null;
            }
            $elegidos = array_fill_keys(array_map('intval', $ingredientes), true);
            $actualizar = $this->db->prepare(
                'UPDATE despensa_item SET nivel = :nivel, cantidad = :cantidad, unidad = :unidad
                 WHERE id = :id AND usuario_id = :usuario'
            );
            $descontados = [];
            foreach ($this->paraDescontar($usuarioId, $sesionId) as $fila) {
                if (!isset($elegidos[$fila['ingrediente_id']])) {
                    continue;
                }
                $actualizar->execute([
                    'nivel' => $fila['despues']['nivel'],
                    'cantidad' => $fila['despues']['cantidad'],
                    'unidad' => $fila['despues']['unidad'],
                    'id' => $fila['item']['id'],
                    'usuario' => $usuarioId,
                ]);
                $descontados[] = $fila['nombre'];
            }
            $this->db->commit();
            return $descontados;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
