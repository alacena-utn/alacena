<?php

declare(strict_types=1);

namespace Alacena;

/**
 * Todo lo relacionado con la sesión del usuario: quién está logueado, qué rol tiene,
 * protección de páginas, mensajes de aviso y el token contra envíos falsos (CSRF).
 *
 * Los datos del usuario logueado se guardan en la variable de sesión $_SESSION['usuario'].
 */
final class Sesion
{
    public const ROL_GENERAL = 'general';
    public const ROL_ADMIN = 'admin';

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('alacena');
        session_set_cookie_params([
            'httponly' => true,      // JavaScript no puede leer la cookie de sesión
            'samesite' => 'Lax',     // otro sitio no puede enviar formularios con nuestra sesión
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    /** @return array{id: int, nombre: string, email: string, rol: string}|null */
    public static function usuario(): ?array
    {
        self::iniciar();
        return $_SESSION['usuario'] ?? null;
    }

    public static function estaLogueado(): bool
    {
        return self::usuario() !== null;
    }

    public static function esAdmin(): bool
    {
        return (self::usuario()['rol'] ?? '') === self::ROL_ADMIN;
    }

    public static function idUsuario(): int
    {
        return (int) (self::usuario()['id'] ?? 0);
    }

    /**
     * Guarda en la sesión al usuario que acaba de ingresar.
     * @param array<string, mixed> $usuario fila de la tabla usuario
     */
    public static function ingresar(array $usuario): void
    {
        self::iniciar();
        session_regenerate_id(true);   // nuevo identificador de sesión: evita el robo de sesión
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nombre' => (string) $usuario['nombre'],
            'email' => (string) $usuario['email'],
            'rol' => (string) $usuario['rol'],
        ];
    }

    public static function salir(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    /** Si no hay nadie logueado, manda a la página de error. Va al principio de cada página privada. */
    public static function requiereLogin(): void
    {
        if (!self::estaLogueado()) {
            redirigir('error.php?motivo=sin-sesion');
        }
    }

    /** Solo deja pasar a los administradores. */
    public static function requiereAdmin(): void
    {
        self::requiereLogin();
        if (!self::esAdmin()) {
            redirigir('error.php?motivo=sin-permiso');
        }
    }

    /** Campo oculto que va dentro de cada <form method="post">. */
    public static function campoCsrf(): string
    {
        self::iniciar();
        return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
    }

    /** Se llama al procesar cada POST: si el token no coincide, el envío no vino de nuestro sitio. */
    public static function verificarCsrf(): void
    {
        self::iniciar();
        if (!hash_equals((string) $_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
            redirigir('error.php?motivo=formulario-vencido');
        }
    }

    /** Guarda un mensaje para mostrar en la próxima página. Tipos: exito, error, info. */
    public static function avisar(string $tipo, string $mensaje): void
    {
        self::iniciar();
        $_SESSION['avisos'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /** @return list<array{tipo: string, mensaje: string}> Devuelve los avisos pendientes y los borra. */
    public static function avisos(): array
    {
        self::iniciar();
        $avisos = $_SESSION['avisos'] ?? [];
        unset($_SESSION['avisos']);
        return $avisos;
    }
}
