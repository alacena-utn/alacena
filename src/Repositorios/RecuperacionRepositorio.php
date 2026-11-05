<?php

declare(strict_types=1);

namespace Cooki\Repositorios;

use PDO;

/**
 * "Olvidé mi contraseña": enlaces de un solo uso que vencen en una hora.
 * En la base se guarda el hash SHA-256 del token, nunca el token: el token solo viaja en el correo.
 */
final class RecuperacionRepositorio
{
    public const MINUTOS_VALIDEZ = 60;
    /** Como mucho 3 pedidos por hora para la misma cuenta (evita llenarle la casilla de correos). */
    public const PEDIDOS_POR_HORA = 3;

    public function __construct(private PDO $db)
    {
    }

    /** Crea un pedido y devuelve el token (64 caracteres hexadecimales, imposible de adivinar). */
    public function crear(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->prepare(
            'INSERT INTO recuperacion (usuario_id, token_hash, vence_en)
             VALUES (:usuario, :hash, NOW() + INTERVAL :minutos MINUTE)'
        )->execute(['usuario' => $usuarioId, 'hash' => hash('sha256', $token), 'minutos' => self::MINUTOS_VALIDEZ]);
        return $token;
    }

    public function pedidosRecientes(int $usuarioId): int
    {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM recuperacion WHERE usuario_id = :usuario AND creado_en >= NOW() - INTERVAL 1 HOUR'
        );
        $consulta->execute(['usuario' => $usuarioId]);
        return (int) $consulta->fetchColumn();
    }

    /**
     * El pedido de ese token si todavía sirve (no venció y no se usó), con el email y el nombre del usuario.
     * @return array<string, mixed>|null
     */
    public function vigente(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $consulta = $this->db->prepare(
            'SELECT r.id, r.usuario_id, u.email, u.nombre FROM recuperacion r JOIN usuario u ON u.id = r.usuario_id
             WHERE r.token_hash = :hash AND r.usado_en IS NULL AND r.vence_en > NOW()'
        );
        $consulta->execute(['hash' => hash('sha256', $token)]);
        return $consulta->fetch() ?: null;
    }

    /** Marca el pedido como usado y anula los otros enlaces pendientes de esa cuenta. */
    public function usar(int $usuarioId): void
    {
        $this->db->prepare(
            'UPDATE recuperacion SET usado_en = NOW() WHERE usuario_id = :usuario AND usado_en IS NULL'
        )->execute(['usuario' => $usuarioId]);
    }
}
