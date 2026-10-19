<?php

declare(strict_types=1);

namespace Alacena\Repositorios;

use PDO;

final class ContactoRepositorio
{
    public const ESTADOS = [
        'nueva' => 'Nueva',
        'respondida' => 'Respondida',
    ];

    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array{nombre: string, email: string, asunto: string, mensaje: string} $datos
     */
    public function crear(array $datos, ?int $usuarioId): int
    {
        $this->db->prepare(
            'INSERT INTO contacto (usuario_id, nombre, email, asunto, mensaje)
             VALUES (:usuario, :nombre, :email, :asunto, :mensaje)'
        )->execute($datos + ['usuario' => $usuarioId]);

        return (int) $this->db->lastInsertId();
    }

    public function marcarCorreoEnviado(int $id): void
    {
        $this->db->prepare(
            'UPDATE contacto SET correo_enviado = TRUE WHERE id = :id'
        )->execute(['id' => $id]);
    }

    /**
     * Listado con buscador y filtro por estado.
     *
     * @return list<array<string, mixed>>
     */
    public function listar(string $texto = '', string $estado = ''): array
    {
        $consulta = $this->db->prepare(
            'SELECT c.*, u.nombre AS usuario
             FROM contacto c LEFT JOIN usuario u ON u.id = c.usuario_id
             WHERE (c.nombre LIKE :t1 OR c.email LIKE :t2
                    OR c.asunto LIKE :t3 OR c.mensaje LIKE :t4)
               AND (:estado1 = \'\' OR c.estado = :estado2)
             ORDER BY c.estado = \'respondida\', c.creado_en DESC'
        );

        $patron = "%{$texto}%";

        $consulta->execute([
            't1' => $patron,
            't2' => $patron,
            't3' => $patron,
            't4' => $patron,
            'estado1' => $estado,
            'estado2' => $estado,
        ]);

        return $consulta->fetchAll();
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE contacto SET estado = :estado WHERE id = :id'
        );

        $consulta->execute(['estado' => $estado, 'id' => $id]);

        return $consulta->rowCount() > 0;
    }

    public function borrar(int $id): bool
    {
        $consulta = $this->db->prepare(
            'DELETE FROM contacto WHERE id = :id'
        );

        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }
}
