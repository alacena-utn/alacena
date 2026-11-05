<?php

declare(strict_types=1);

namespace Cooki\Controladores;

use Cooki\Db;
use Cooki\Http\ErrorHttp;
use Cooki\Http\Respuesta;
use Cooki\Repositorios\IngresoRepositorio;
use Cooki\Repositorios\UsuarioRepositorio;
use Cooki\Sesion;

/**
 * Cuentas por la API (para una app o un frontend aparte). Usan la misma sesión de PHP que las páginas.
 *   POST   /api/usuarios  {nombre, email, contrasena} → crea la cuenta y entra (201)
 *   POST   /api/sesion    {email, contrasena}         → entra (con el mismo bloqueo por intentos que la página)
 *   GET    /api/yo                                    → quién está logueado
 *   DELETE /api/sesion                                → sale (204)
 */
final class SesionControlador
{
    /** Id del usuario logueado; si no hay nadie, corta con 401. Lo usan todas las rutas que piden sesión. */
    public static function requiereSesion(): int
    {
        if (!Sesion::estaLogueado()) {
            throw new ErrorHttp('Tenés que iniciar sesión', 401);
        }
        return Sesion::idUsuario();
    }

    public static function registrar(): void
    {
        $datos = Respuesta::cuerpo();
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $email = mb_strtolower(trim((string) ($datos['email'] ?? '')));
        $contrasena = (string) ($datos['contrasena'] ?? '');
        $errores = UsuarioRepositorio::validar($nombre, $email, $contrasena);
        if ($errores !== []) {
            Respuesta::json(['error' => 'Revisá los datos', 'campos' => $errores], 422);
            return;
        }
        $repo = new UsuarioRepositorio(Db::conexion());
        if ($repo->existeEmail($email)) {
            throw new ErrorHttp('Ya hay una cuenta con ese email', 409);
        }
        $id = $repo->crear($nombre, $email, $contrasena, Sesion::ROL_GENERAL);
        Sesion::ingresar(['id' => $id, 'nombre' => $nombre, 'email' => $email, 'rol' => Sesion::ROL_GENERAL]);
        Respuesta::json(['usuario' => self::publico()], 201);
    }

    public static function ingresar(): void
    {
        $datos = Respuesta::cuerpo();
        $email = mb_strtolower(trim((string) ($datos['email'] ?? '')));
        $intentos = new IngresoRepositorio(Db::conexion());
        $bloqueo = $intentos->minutosDeBloqueo($email);
        if ($bloqueo > 0) {
            throw new ErrorHttp("Demasiados intentos. Probá de nuevo en {$bloqueo} minutos.", 429);
        }
        $usuario = (new UsuarioRepositorio(Db::conexion()))
            ->verificarCredenciales($email, (string) ($datos['contrasena'] ?? ''));
        if ($usuario === null) {
            $intentos->registrarFallo($email, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
            throw new ErrorHttp('El email o la contraseña no son correctos', 401);
        }
        $intentos->limpiar($email);
        Sesion::ingresar($usuario);
        Respuesta::json(['usuario' => self::publico()]);
    }

    public static function yo(): void
    {
        self::requiereSesion();
        Respuesta::json(['usuario' => self::publico()]);
    }

    public static function salir(): void
    {
        Sesion::salir();
        http_response_code(204);
    }

    /** @return array{id: int, nombre: string, email: string, rol: string} los datos que se pueden mostrar */
    private static function publico(): array
    {
        $u = Sesion::usuario();
        return ['id' => (int) $u['id'], 'nombre' => $u['nombre'], 'email' => $u['email'], 'rol' => $u['rol']];
    }
}
