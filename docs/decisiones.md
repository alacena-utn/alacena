# Registro de decisiones

Cada decisión importante se anota acá: qué se decidió, por qué y qué alternativas se descartaron.
Lo más nuevo va arriba. Sirve para el informe y para la defensa.
Formato: decisiones tipo ADR (ver la guía de metodología del equipo, sección 9.2).

---

## DEC-003 · AAAA-MM-DD · (título)
- **Contexto:** 
- **Decisión:** 
- **Alternativas descartadas:** 
- **Consecuencias:** 
- **Participaron:** 

---

## DEC-002 · 2026-10-15 · Incremento 1: páginas PHP para el 2.º parcial de Programación 4
- **Contexto:** el 2.º parcial de Programación 4 pide, en dos semanas, páginas concretas: principal con registro e ingreso, contacto con mapa y `mail()`, sesiones con dos roles, página interna para el usuario general, página de administración con ABM y un buscador, con al menos tres tablas (contactos, usuarios y contenido).
- **Decisión:** el incremento 1 se arma con páginas PHP que procesan formularios y generan el HTML en el servidor (`public/*.php`, `public/app/`, `public/admin/`), con sesiones de PHP y JavaScript para validar, filtrar y armar formularios dinámicos. Se agregan el rol del usuario (`general` o `admin`), la tabla `contacto` y el autor de cada receta. La API JSON queda solo para el buscador de ingredientes. El modo cocina, la recomendación con puntaje y el hosting pasan al incremento 2.
- **Alternativas descartadas:** la aplicación de una sola página que consume la API (más trabajo y menos directo para cumplir cada punto del parcial en dos semanas).
- **Consecuencias:** cada requisito del parcial corresponde a una página concreta y fácil de mostrar ([requisitos-parcial.md](requisitos-parcial.md)). El plan anterior del Sprint 0 se reemplaza por este y el repositorio se rehace con el kit nuevo, ya que todavía no se había programado nada.
- **Participaron:** P1, P2, P3, P4

---

## DEC-001 · (completar en C-01) · Stack tecnológico
- **Contexto:** la materia de Programación Web exige HTML, CSS, JavaScript, PHP y MySQL/MariaDB, y el proyecto final debe poder presentarse también ahí.
- **Decisión:** frontend en HTML + CSS + JavaScript sin frameworks; API REST en PHP 8 sin framework; base MariaDB (compatible con MySQL); entorno local con Docker; hosting PHP + MySQL gratuito.
- **Alternativas descartadas:** TypeScript + React + Node + PostgreSQL (no cumple la materia); Laravel (pendiente de confirmar con la cátedra si se permite).
- **Consecuencias:** un solo proyecto sirve para las dos materias; la lógica de recomendación se escribe en PHP; la app móvil futura reutiliza la misma API.
- **Participaron:** P1, P2, P3, P4
