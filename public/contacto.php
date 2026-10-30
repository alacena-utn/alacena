<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Config;
use Alacena\Correo;
use Alacena\Db;
use Alacena\Repositorios\ContactoRepositorio;
use Alacena\Sesion;

$usuario = Sesion::usuario();
$errores = [];

$datos = [
    'nombre' => $usuario['nombre'] ?? '',
    'email' => $usuario['email'] ?? '',
    'asunto' => '',
    'mensaje' => '',
];

if (esPost()) {
    Sesion::verificarCsrf();

    $datos = [
        'nombre' => campo('nombre'),
        'email' => mb_strtolower(campo('email')),
        'asunto' => campo('asunto'),
        'mensaje' => campo('mensaje'),
    ];

    if (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 80) {
        $errores['nombre'] = 'Ingresá tu nombre (entre 2 y 80 caracteres).';
    }

    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Ingresá un email válido para poder responderte.';
    }

    if (mb_strlen($datos['asunto']) < 3 || mb_strlen($datos['asunto']) > 120) {
        $errores['asunto'] = 'El asunto debe tener entre 3 y 120 caracteres.';
    }

    if (mb_strlen($datos['mensaje']) < 10 || mb_strlen($datos['mensaje']) > 2000) {
        $errores['mensaje'] = 'El mensaje debe tener entre 10 y 2000 caracteres.';
    }

    if ($errores === []) {
        $repo = new ContactoRepositorio(Db::conexion());
        $id = $repo->crear($datos, $usuario['id'] ?? null);

        $cuerpo = "Nueva consulta desde el sitio Alacena\n\n"
            . "Nombre: {$datos['nombre']}\n"
            . "Email: {$datos['email']}\n"
            . "Asunto: {$datos['asunto']}\n\n"
            . $datos['mensaje'] . "\n\n"
            . "Consulta n.º {$id} · " . date('d/m/Y H:i');

        $enviado = Correo::enviar(
            Config::get('MAIL_CONTACTO', 'contacto@alacena.test'),
            'Consulta: ' . $datos['asunto'],
            $cuerpo,
            $datos['email']
        );

        if ($enviado) {
            $repo->marcarCorreoEnviado($id);
        }

        Sesion::avisar(
            'exito',
            '¡Gracias! Recibimos tu consulta y te vamos a responder a ' . $datos['email'] . '.'
        );

        redirigir('contacto.php');
    }
}

$error = fn (string $campo) => isset($errores[$campo])
    ? '<p class="error-campo" id="error-' . $campo . '">'
        . e($errores[$campo]) . '</p>'
    : '';

$titulo = 'Contacto';
require parcial('encabezado');
?>

<h1>Contacto</h1>

<div class="contacto">
  <section class="tarjeta">
    <h2>Escribinos</h2>

    <form class="formulario" method="post" id="form-contacto">
      <?= Sesion::campoCsrf() ?>

      <div class="campo">
        <label for="nombre">Nombre</label>
        <input
          id="nombre"
          name="nombre"
          type="text"
          required
          minlength="2"
          maxlength="80"
          autocomplete="name"
          value="<?= e($datos['nombre']) ?>"
        >
        <?= $error('nombre') ?>
      </div>

      <div class="campo">
        <label for="email">Email</label>
        <input
          id="email"
          name="email"
          type="email"
          required
          maxlength="190"
          autocomplete="email"
          value="<?= e($datos['email']) ?>"
        >
        <?= $error('email') ?>
      </div>

      <div class="campo">
        <label for="asunto">Asunto</label>
        <input
          id="asunto"
          name="asunto"
          type="text"
          required
          minlength="3"
          maxlength="120"
          value="<?= e($datos['asunto']) ?>"
        >
        <?= $error('asunto') ?>
      </div>

      <div class="campo">
        <label for="mensaje">Mensaje</label>
        <textarea
          id="mensaje"
          name="mensaje"
          required
          minlength="10"
          maxlength="2000"
          rows="6"
          aria-describedby="contador"
        ><?= e($datos['mensaje']) ?></textarea>

        <small id="contador" aria-live="polite">0 / 2000</small>
        <?= $error('mensaje') ?>
      </div>

      <button class="boton" type="submit">Enviar consulta</button>
    </form>
  </section>

  <section class="tarjeta">
    <h2>Dónde estamos</h2>
    <p>UTN Facultad Regional Haedo · París 532, Haedo, Buenos Aires</p>

    <iframe
      class="mapa"
      title="Mapa: UTN Facultad Regional Haedo"
      src="https://www.google.com/maps?q=UTN+Facultad+Regional+Haedo,+Par%C3%ADs+532,+Haedo&amp;output=embed"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
    ></iframe>

    <p class="texto-suave">
      <small>
        Correo: <?= e(Config::get('MAIL_CONTACTO', 'contacto@alacena.test')) ?>
        (dirección de prueba del proyecto)
      </small>
    </p>
  </section>
</div>

<script src="<?= e(url('js/contacto.js')) ?>" defer></script>

<?php require parcial('pie'); ?>
