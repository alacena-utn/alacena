<?php
namespace Alacena\Contoladores;
use Alacena\Db;
use
Alacena\Repositorios\UsuarioRepositorio;

class UsuarioControlador 
{
    public function index() :array
    {
    $repo = new UsuarioRepositorio(Db::conexion());
        $usuarios = $repo->listar();

        return $usuarios;


     }
}
