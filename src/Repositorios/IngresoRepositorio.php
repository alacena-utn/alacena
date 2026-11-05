<?php

declare(strict_types=1);

namespace Cooki\Repositorios;

use Cooki\Dominio\Bloqueo;
use PDO;

/**
 * Intentos de ingreso fallidos (tabla intento_ingreso), para bloquear un email después de varios errores.
 * Las reglas (cuántos y por cuánto tiempo) están en Dominio\Bloqueo.
 */
final class IngresoRepositorio
{
    public function __construct(private PDO $db)
    {
    }

    /** Minutos que le quedan de bloqueo a ese email (0 = puede intentar). */
    public function minutosDeBloqueo(string $email): int
    {
        return Bloqueo::minutosRestantes($this->fallos($email), time());
    }

    /** Intentos que le quedan antes de bloquearse. */
    public function intentosRestantes(string $email): int
    {
        return Bloqueo::intentosRestantes($this->fallos($email), time());
    }

    public function registrarFallo(string $email, string $ip): void
    {
        // De paso se borran los intentos viejos: no sirven para nada después de un día
        $this->db->exec('DELETE FROM intento_ingreso WHERE creado_en < NOW() - INTERVAL 1 DAY');
        $this->db->prepare('INSERT INTO intento_ingreso (email, ip) VALUES (:email, :ip)')
            ->execute(['email' => mb_substr($email, 0, 190), 'ip' => mb_substr($ip, 0, 45)]);
    }

    /** Después de ingresar bien (o de cambiar la contraseña) se empieza de cero. */
    public function limpiar(string $email): void
    {
        $this->db->prepare('DELETE FROM intento_ingreso WHERE email = :email')->execute(['email' => $email]);
    }

    /** @return list<int> momentos de los fallos de los últimos minutos */
    private function fallos(string $email): array
    {
        $consulta = $this->db->prepare(
            'SELECT UNIX_TIMESTAMP(creado_en) FROM intento_ingreso
             WHERE email = :email AND creado_en >= NOW() - INTERVAL :minutos MINUTE'
        );
        $consulta->execute(['email' => $email, 'minutos' => Bloqueo::MINUTOS]);
        return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    }
}
