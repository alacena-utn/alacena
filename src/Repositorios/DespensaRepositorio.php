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
     * @return list<array<string, mixed>>
     */
    public function listar(int $usuarioId, string $texto = '', string $ubicacion = ''): array
    {
        $consulta = $this->db->prepare(
            'SELECT d.id, d.ingrediente_id, i.nombre, c.nombre AS categoria, c.color_hex, d.nivel,
                    d.fecha_vencimiento, d.ubicacion, DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias
             FROM despensa_item d
             JOIN ingrediente i ON i.id = d.ingrediente_id
             JOIN categoria c ON c.id = i.categoria_id
             WHERE d.usuario_id = :usuario
               AND i.nombre LIKE :texto
               AND (:ubicacion1 = \'\' OR d.ubicacion = :ubicacion2)
             ORDER BY d.fecha_vencimiento IS NULL, d.fecha_vencimiento, i.nombre'
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
     * @param array{ingrediente_id: int, nivel: int, fecha_vencimiento: ?string, ubicacion: string} $datos
     */
    public function guardar(int $usuarioId, array $datos, int $id = 0): bool
    {
        $valores = [
            'usuario' => $usuarioId,
            'ingrediente' => $datos['ingrediente_id'],
            'nivel' => $datos['nivel'],
            'vence' => $datos['fecha_vencimiento'],
            'ubicacion' => $datos['ubicacion'],
        ];
        try {
            if ($id === 0) {
                $this->db->prepare(
                    'INSERT INTO despensa_item (usuario_id, ingrediente_id, nivel, fecha_vencimiento, ubicacion)
                     VALUES (:usuario, :ingrediente, :nivel, :vence, :ubicacion)'
                )->execute($valores);
            } else {
                $this->db->prepare(
                    'UPDATE despensa_item SET ingrediente_id = :ingrediente, nivel = :nivel,
                            fecha_vencimiento = :vence, ubicacion = :ubicacion
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
            'SELECT d.id, i.nombre, d.fecha_vencimiento, d.ubicacion, DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias
             FROM despensa_item d JOIN ingrediente i ON i.id = d.ingrediente_id
             WHERE d.usuario_id = :usuario AND d.nivel > 0 AND d.fecha_vencimiento IS NOT NULL
               AND d.fecha_vencimiento <= CURDATE() + INTERVAL :dias DAY
             ORDER BY d.fecha_vencimiento'
        );
        $consulta->execute(['usuario' => $usuarioId, 'dias' => $dias]);
        return $consulta->fetchAll();
    }

        /**
     * Dibujo del ingrediente en public/img/ingredientes/ (ruta para usar con url()).
     * Prueba con el nombre completo ("queso-cremoso") y después con la primera palabra ("queso"),
     * salvo nombres como "Crema de leche" o "Atún en lata", que no son la primera palabra.
     * Si un ingrediente nuevo todavía no tiene dibujo, devuelve un frasco genérico (comida.svg).
     */
    public static function dibujo(string $nombre): string
    {
        $tildes = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];
        $sinTildes = strtr(mb_strtolower($nombre), $tildes);
        $completo = trim((string) preg_replace('/[^a-z0-9]+/', '-', $sinTildes), '-');
        $candidatos = [$completo];
        if (!preg_match('/ (de|en) /', $sinTildes)) {
            $candidatos[] = strtok($completo, '-');
        }
        foreach ($candidatos as $archivo) {
            if (is_file(dirname(__DIR__, 2) . '/public/img/ingredientes/' . $archivo . '.svg')) {
                return 'img/ingredientes/' . $archivo . '.svg';
            }
        }
        return 'img/ingredientes/comida.svg';
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
