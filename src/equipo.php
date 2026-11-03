<?php

/**
 * Integrantes del equipo para la página "Quiénes somos" (public/nosotros.php).
 *
 * ACÁ SE CARGAN LOS DATOS DE CADA UNO. Lo que quede vacío ('') no se muestra:
 *   - nombre:      nombre (y apellido, si se quiere).
 *   - area:        la parte del proyecto de la que se ocupó; va al principio de la descripción.
 *   - descripcion: qué hizo, contado por tecnologías (lenguajes, herramientas, técnicas).
 *   - imagen:      el dibujo de public/img/equipo, sin ".svg".
 *   - github:      dirección completa del perfil, por ejemplo 'https://github.com/usuario'.
 *   - linkedin:    dirección completa, por ejemplo 'https://www.linkedin.com/in/usuario'.
 *   - correo:      dirección de correo (se abre con el programa de correo).
 * El orden de la lista es el orden en la página. El rol interno (P1 a P5) va solo como comentario.
 *
 * @return list<array{nombre: string, area: string, descripcion: string, imagen: string,
 *                    github: string, linkedin: string, correo: string}>
 */

return [
    // P1
    [
        'nombre' => 'Daiana',
        'area' => 'Cuentas e infraestructura',
        'descripcion' => 'Autenticación y sesiones en PHP, contraseñas con hash seguro y protección CSRF, '
            . 'entornos con Docker e integración continua con GitHub Actions.',
        'imagen' => 'daiana',
        'github' => 'https://github.com/Daiana1411',
        'linkedin' => '',
        'correo' => '',
    ],
    // P2
    [
        'nombre' => 'Julian',
        'area' => 'Despensa y diseño',
        'descripcion' => 'Diseño de interfaces y maquetación responsiva con HTML y CSS, ilustraciones en SVG, '
            . 'animaciones e interacción con JavaScript.',
        'imagen' => 'julian',
        'github' => 'https://github.com/julianmartinez093-tech',
        'linkedin' => '',
        'correo' => '',
    ],
    // P3
    [
        'nombre' => 'Pablo',
        'area' => 'Recetas y base de datos',
        'descripcion' => 'Modelado relacional y consultas SQL en MariaDB, acceso a datos con PDO, '
            . 'lógica de negocio en PHP y algoritmos de recomendación.',
        'imagen' => 'pablo',
        'github' => 'https://github.com/pablito2032',
        'linkedin' => '',
        'correo' => '',
    ],
    // P4
    [
        'nombre' => 'Ariana',
        'area' => 'Contacto y calidad',
        'descripcion' => 'Formularios con validación en JavaScript y PHP, envío de correos, '
            . 'pruebas automáticas con PHPUnit y accesibilidad web.',
        'imagen' => 'ariana',
        'github' => '',
        'linkedin' => '',
        'correo' => '',
    ],
    // P5
    [
        'nombre' => 'Susana',
        'area' => 'Administración',
        'descripcion' => 'Paneles de gestión en PHP y MariaDB, altas, bajas y modificaciones (CRUD) '
            . 'y control de roles y permisos.',
        'imagen' => 'susana',
        'github' => '',
        'linkedin' => '',
        'correo' => '',
    ],
];
