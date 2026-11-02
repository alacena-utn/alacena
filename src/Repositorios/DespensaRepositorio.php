<?php

declare(strict_types=1);

namespace Alacena\Repositorios;

use PDO;

/**
 * La despensa de cada usuario. Todas las consultas filtran por usuario_id:
 * nadie puede ver ni modificar la despensa de otro, aunque cambie el id en la URL.
 */
final class DespensaRepositorio
{
    public const NIVELES = [0 => 'Agotado', 1 => 'Poco', 2 => 'Normal', 3 => 'Mucho'];
    public const UBICACIONES = ['alacena' => 'Alacena', 'heladera' => 'Heladera', 'freezer' => 'Freezer'];

    public function __construct(private PDO $db)
    {
    }

    /**
     * Ítems de la despensa del usuario. "dias" = días que faltan para que venza (negativo si ya venció).
     * Orden: primero lo que vence antes; lo que no tiene fecha, después; lo vencido, al final.
     * @return list<array<string, mixed>>
     */
    public function listar(int $usuarioId, string $texto = '', string $ubicacion = ''): array
    {
        $consulta = $this->db->prepare(
            'SELECT d.id, d.ingrediente_id, i.nombre, i.usuario_id IS NOT NULL AS propio,
                    c.nombre AS categoria, c.color_hex, d.nivel,
                    d.cantidad, COALESCE(d.unidad, i.unidad_default) AS unidad, d.fecha_vencimiento,
                    d.ubicacion, DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias
             FROM despensa_item d
             JOIN ingrediente i ON i.id = d.ingrediente_id
             JOIN categoria c ON c.id = i.categoria_id
             WHERE d.usuario_id = :usuario
               AND i.nombre LIKE :texto
               AND (:ubicacion1 = \'\' OR d.ubicacion = :ubicacion2)
             ORDER BY COALESCE(d.fecha_vencimiento < CURDATE(), 0),
                      d.fecha_vencimiento IS NULL, d.fecha_vencimiento, i.nombre'
        );
        $consulta->execute([
            'usuario' => $usuarioId,
            'texto' => "%{$texto}%",
            'ubicacion1' => $ubicacion,
            'ubicacion2' => $ubicacion,
        ]);
        return $consulta->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function obtener(int $usuarioId, int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM despensa_item WHERE id = :id AND usuario_id = :usuario');
        $consulta->execute(['id' => $id, 'usuario' => $usuarioId]);
        return $consulta->fetch() ?: null;
    }

    /**
     * Alta (si $id es 0) o modificación. Devuelve false si ese ingrediente ya está
     * cargado en la misma ubicación (la base no permite duplicados).
     *
     * La cantidad exacta es opcional: si viene, se guarda con la unidad del ingrediente.
     *
     * @param array{ingrediente_id: int, nivel: int, cantidad: ?float, unidad: ?string,
     *              fecha_vencimiento: ?string, ubicacion: string} $datos
     */
    public function guardar(int $usuarioId, array $datos, int $id = 0): bool
    {
        $valores = [
            'usuario' => $usuarioId,
            'ingrediente' => $datos['ingrediente_id'],
            'nivel' => $datos['nivel'],
            'cantidad' => $datos['cantidad'],
            'unidad' => $datos['cantidad'] === null ? null : $datos['unidad'],
            'vence' => $datos['fecha_vencimiento'],
            'ubicacion' => $datos['ubicacion'],
        ];
        try {
            if ($id === 0) {
                $this->db->prepare(
                    'INSERT INTO despensa_item (usuario_id, ingrediente_id, nivel, cantidad, unidad,
                                                fecha_vencimiento, ubicacion)
                     VALUES (:usuario, :ingrediente, :nivel, :cantidad, :unidad, :vence, :ubicacion)'
                )->execute($valores);
            } else {
                $this->db->prepare(
                    'UPDATE despensa_item SET ingrediente_id = :ingrediente, nivel = :nivel,
                            cantidad = :cantidad, unidad = :unidad, fecha_vencimiento = :vence, ubicacion = :ubicacion
                     WHERE id = :id AND usuario_id = :usuario'
                )->execute($valores + ['id' => $id]);
            }
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {   // clave única repetida
                return false;
            }
            throw $e;
        }
    }

    public function borrar(int $usuarioId, int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM despensa_item WHERE id = :id AND usuario_id = :usuario');
        $consulta->execute(['id' => $id, 'usuario' => $usuarioId]);
        return $consulta->rowCount() > 0;
    }

    /**
     * Lo que vence en los próximos $dias días (o ya venció y sigue cargado).
     * @return list<array<string, mixed>>
     */
    public function porVencer(int $usuarioId, int $dias = 7): array
    {
        $consulta = $this->db->prepare(
            'SELECT d.id, i.nombre, i.usuario_id IS NOT NULL AS propio, d.fecha_vencimiento, d.ubicacion,
                    DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias
             FROM despensa_item d JOIN ingrediente i ON i.id = d.ingrediente_id
             WHERE d.usuario_id = :usuario AND d.nivel > 0 AND d.fecha_vencimiento IS NOT NULL
               AND d.fecha_vencimiento <= CURDATE() + INTERVAL :dias DAY
             ORDER BY d.fecha_vencimiento'
        );
        $consulta->execute(['usuario' => $usuarioId, 'dias' => $dias]);
        return $consulta->fetchAll();
    }

    /**
     * Ingredientes que el usuario tiene disponibles (no agotados ni vencidos), sin repetir
     * aunque estén en dos ubicaciones. Es la lista del selector de Mi cocina.
     * "dias" = días hasta el vencimiento más cercano (null si no tiene fecha).
     * Orden: lo que vence antes primero; lo que no tiene fecha, al final.
     * @return list<array{id: int, nombre: string, dias: ?int}>
     */
    public function disponibles(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            // Va adentro de otra consulta: MariaDB no deja ordenar por el alias de un MIN() con IS NULL
            'SELECT * FROM (
                SELECT i.id, i.nombre, i.usuario_id IS NOT NULL AS propio,
                       MIN(DATEDIFF(d.fecha_vencimiento, CURDATE())) AS dias
                FROM despensa_item d JOIN ingrediente i ON i.id = d.ingrediente_id
                WHERE d.usuario_id = :usuario AND d.nivel > 0
                  AND (d.fecha_vencimiento IS NULL OR d.fecha_vencimiento >= CURDATE())
                GROUP BY i.id, i.nombre, i.usuario_id
             ) AS disponibles
             ORDER BY dias IS NULL, dias, nombre'
        );
        $consulta->execute(['usuario' => $usuarioId]);
        return $consulta->fetchAll();
    }

    /**
     * Cantidad legible: (6, 'u') → "6 unidades", (1, 'u') → "1 unidad", (500, 'g') → "500 g",
     * (1.5, 'kg') → "1,5 kg". Devuelve '' si no hay cantidad.
     */
    public static function textoCantidad(string|float|null $cantidad, ?string $unidad): string
    {
        if ($cantidad === null || $cantidad === '') {
            return '';
        }
        $numero = rtrim(rtrim(number_format((float) $cantidad, 2, ',', '.'), '0'), ',');
        if ($unidad === 'u' || $unidad === null || $unidad === '') {
            return $numero . ((float) $cantidad === 1.0 ? ' unidad' : ' unidades');
        }
        return $numero . ' ' . $unidad;
    }

    /** Grupos del contador de la despensa (clave => texto). "por_vencer" = de hoy a 3 días. */
    public const ESTADOS = [
        'bien' => 'bien',
        'por_vencer' => 'por vencer',
        'vencidos' => 'vencidos',
        'agotados' => 'agotados',
    ];

    /**
     * Grupo del contador al que pertenece un ítem de listar(). Cada ítem va en un solo grupo:
     * agotado gana a vencido, y vencido a por vencer.
     * @param array<string, mixed> $item
     */
    public static function estado(array $item): string
    {
        $dias = $item['dias'] === null ? null : (int) $item['dias'];
        return match (true) {
            (int) $item['nivel'] === 0 => 'agotados',
            $dias !== null && $dias < 0 => 'vencidos',
            $dias !== null && $dias <= 3 => 'por_vencer',
            default => 'bien',
        };
    }

    /**
     * Cuántos ítems hay en cada grupo del contador.
     * @param list<array<string, mixed>> $items filas de listar()
     * @return array{bien: int, por_vencer: int, vencidos: int, agotados: int}
     */
    public static function resumen(array $items): array
    {
        $resumen = ['bien' => 0, 'por_vencer' => 0, 'vencidos' => 0, 'agotados' => 0];
        foreach ($items as $item) {
            $resumen[self::estado($item)]++;
        }
        return $resumen;
    }

    /** Texto y estilo para mostrar el vencimiento: ["Vence mañana", "alerta"]. */
    public static function estadoVencimiento(?int $dias): array
    {
        return match (true) {
            $dias === null => ['Sin fecha', ''],
            $dias < 0 => ['Vencido', 'error'],
            $dias === 0 => ['Vence hoy', 'error'],
            $dias === 1 => ['Vence mañana', 'alerta'],
            $dias <= 3 => ["Vence en {$dias} días", 'alerta'],
            default => ["Vence en {$dias} días", 'exito'],
        };
    }
}
