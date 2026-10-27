<?php

namespace Alacena\Controladores;

use Alacena\Db;
use Alacena\Repositorios\UsuarioRepositorio;

class UsuarioControlador
{
    public function index(): array
    {
        $repo = new UsuarioRepositorio(Db::conexion());
        $usuarios = $repo->listar();

        return $usuarios;
    }
    public function crear(
        string $nombre,
        string $email,
        string $password,
        string $rol
    ): int {
        $repo = new UsuarioRepositorio(Db::conexion());
        $id = $repo->crear($nombre, $email, $password, $rol);
        return $id;
    }
    public function buscarPorId(int $id): ?array
    {
        $repo = new UsuarioRepositorio(Db::conexion());

        return $repo->buscarPorId($id);
    }
    public function actualizar(
        int $id,
        string $nombre,
        string $email,
        string $rol,
        string $password
    ): void {
        $repo = new UsuarioRepositorio(Db::conexion());

        $usuario = $repo->buscarPorId($id);
        if ($usuario === null) {
            throw new \RuntimeException('El usuario no existe.');
        }
        if ($usuario['rol'] === 'admin' && $rol !== 'admin' && $repo->contarAdministradores() <= 1) {
            throw new \RuntimeException('No podés quitar el rol al último administrador.');
        }
        $repo->actualizar($id, $nombre, $email, $rol, $password);
    }
    public function borrar(int $id): void
    {
        $repo = new UsuarioRepositorio(Db::conexion());

        $usuario = $repo->buscarPorId($id);

        if ($usuario === null) {
            throw new \RuntimeException('El usuario no existe.');
        }

        if ($id === \Alacena\Sesion::idUsuario()) {
            throw new \RuntimeException('No podés eliminar tu propia cuenta.');
        }

        if ($usuario['rol'] === 'admin' && $repo->contarAdministradores() <= 1) {
            throw new \RuntimeException('No podés eliminar al último administrador.');
        }

        $repo->borrar($id);
    }
}
