<?php

/**
 * Página principal (pública): nombre del proyecto, menú, descripción
 * y acceso al registro y al ingreso. Estilo del modelo Heladera.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Alacena\Sesion;

$titulo = 'Tu despensa y qué cocinar con lo que tenés';

// Imanes de muestra para la puerta: [archivo, nombre, nota, ¿vence ya?]
$imanes = [
    ['morron', 'Morrón', 'vence mañana', true],
    ['huevo', 'Huevo', 'dura 9 días', false],
    ['zanahoria', 'Zanahoria', 'dura 8 días', false],
    ['tomate', 'Tomate', 'vence en 2 días', true],
    ['queso', 'Queso', 'dura 6 días', false],
    ['papa', 'Papa', 'dura 12 días', false],
];

require parcial('encabezado');
?>
<section class="portada">
    <div>
        <h1 class="portada__titulo">¿Qué cocino con lo que tengo?</h1>
        <p class="portada__bajada"><strong>Alacena</strong> es la puerta de tu heladera: anotá lo que tenés en la heladera y la alacena, y te decimos qué podés cocinar hoy. Sin ir al súper, sin tirar comida.</p>
        <p class="acciones">
            <?php if (Sesion::estaLogueado()) : ?>
                <a class="boton" href="<?= e(url('app/index.php')) ?>">Ir a mi cocina</a>
            <?php else : ?>
                <a class="boton" href="<?= e(url('registro.php')) ?>">Crear cuenta gratis</a>
                <a class="boton boton--secundario" href="<?= e(url('login.php')) ?>">Ya tengo cuenta</a>
            <?php endif; ?>
        </p>
        <p class="portada__nota">→ y usá primero lo que vence</p>
    </div>

    <ul class="puerta-portada" aria-hidden="true">
        <?php foreach ($imanes as [$archivo, $nombre, $nota, $venceYa]) : ?>
            <li class="iman">
                <img src="<?= e(url('img/ingredientes/' . $archivo . '.svg')) ?>" alt="" width="96" height="96">
                <?php if ($venceYa) : ?>
                    <span class="iman__ya">¡ya!</span>
                <?php endif; ?>
                <span class="iman__cinta"><?= e($nombre) ?></span>
                <small><?= e($nota) ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section aria-labelledby="como-funciona">
    <h2 id="como-funciona" class="titulo-mano">Cómo funciona</h2>
    <ol class="pasos-portada">
        <li class="postit">
            <span class="postit__chinche"></span>
            <h3>1. Cargá tu despensa</h3>
            <p>Elegí los ingredientes que tenés, cuánto te queda y cuándo vencen.</p>
        </li>
        <li class="postit">
            <span class="postit__chinche"></span>
            <h3>2. Mirá qué cocinar</h3>
            <p>Te mostramos las recetas para las que ya tenés todo, o casi todo.</p>
        </li>
        <li class="postit">
            <span class="postit__chinche"></span>
            <h3>3. Aprovechá lo que vence</h3>
            <p>Te avisamos qué conviene usar primero para no tirar comida.</p>
        </li>
    </ol>
</section>

<section class="tarjeta portada__porque">
    <h2>¿Por qué Alacena?</h2>
    <p>En Argentina se pierden o desperdician alrededor de 16 millones de toneladas de alimentos por año, y buena parte se tira en casa porque se vence o porque nos olvidamos de que estaba. Alacena es un proyecto de estudiantes de la UTN Facultad Regional Haedo para ayudar a decidir qué cocinar con lo que ya hay.</p>
    <p><a href="<?= e(url('contacto.php')) ?>">Escribinos</a> si tenés sugerencias.</p>
</section>
<?php require parcial('pie'); ?>