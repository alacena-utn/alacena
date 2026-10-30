# Registro de decisiones

Cada decisión importante se anota acá: qué se decidió, por qué y qué alternativas se descartaron.
Lo más nuevo va arriba. Sirve para el informe y para la defensa.
Formato: decisiones tipo ADR (ver la guía de metodología del equipo, sección 9.2).

---

## DEC-008 · AAAA-MM-DD · (título)
- **Contexto:** 
- **Decisión:** 
- **Alternativas descartadas:** 
- **Consecuencias:** 
- **Participaron:** 

---

## DEC-007 · 2026-10-28 · Respuestas a las consultas desde la administración
- **Contexto:** las consultas de contacto solo se podían marcar como respondidas; la respuesta había que mandarla por fuera del sistema.
- **Decisión:** columnas `contacto.respuesta`, `respondida_en`, `respondida_por` (→ usuario) y `respuesta_enviada`. Desde `admin/consultas.php` se escribe la respuesta: se guarda, se manda por correo con `Correo::enviar()` (en Docker llega a Mailpit) y la consulta queda como respondida. Si quien consultó tenía cuenta, la ve en Contacto → "Mis consultas".
- **Alternativas descartadas:** solo abrir el programa de correo con un enlace `mailto:` (no queda registro en el sistema); solo mostrarla en la página (quien escribió sin cuenta no se entera).
- **Consecuencias:** migración `database/migraciones/004-tanda-heladera-4.sql`. Si el correo no sale, la respuesta queda guardada igual y se avisa.
- **Participaron:** P3

---

## DEC-006 · 2026-10-28 · Ingredientes propios de cada usuario y color sin cuenta
- **Contexto:** al cargar la despensa, el usuario puede tener algo que no está en la lista de ingredientes. Además, quien no tiene cuenta también quiere elegir el color de la heladera.
- **Decisión:**
  - columna `ingrediente.usuario_id` (NULL = lista general; con un usuario = ingrediente propio, que solo ve él). El nombre deja de ser único en toda la tabla: es único dentro de la lista general y dentro de los propios de cada usuario (columna generada `ambito` + `UNIQUE (nombre, ambito)`). Si el nombre ya existe en la lista general (o es un sinónimo, como "piña"), se usa ese y no se crea otro. Los propios llevan siempre el dibujo del frasco, que no usa ningún ingrediente de la lista. El administrador los ve marcados y los puede pasar a la lista general; recién ahí se pueden usar en recetas;
  - sin cuenta, el color se guarda en la cookie `alacena_color` (un año) y pasa a la cuenta al registrarse.
- **Alternativas descartadas:** que el ingrediente nuevo vaya directo a la lista general (se llenaría de nombres repetidos o mal escritos); una tabla aparte para los propios (habría que duplicar todas las consultas de la despensa); guardar el color sin cuenta en el navegador con JavaScript (la página cargaría primero en verde y después cambiaría).
- **Consecuencias:** migración `database/migraciones/004-tanda-heladera-4.sql`. `IngredienteRepositorio::todos()` y `buscar()` (la API) muestran solo la lista general, salvo en la despensa del dueño. Al borrar un usuario se borran sus ingredientes propios.
- **Participaron:** P3

---

## DEC-005 · 2026-10-28 · Volumen de los sonidos por usuario y más ingredientes
- **Contexto:** los sonidos necesitan poder bajarse o subirse, y la lista de ingredientes tenía pocas frutas y verduras.
- **Decisión:** columna `usuario.volumen` (0 a 100, por defecto 50), guardada desde el menú de la cuenta como el color y la disposición (`app/preferencias.php`). Para quien no ingresó, el volumen se guarda en el navegador. Se suman 32 ingredientes (ids 45 a 76), cada uno con su dibujo, y "Pollo (pechuga)" pasa a llamarse "Pollo" ("pechuga" queda como sinónimo).
- **Alternativas descartadas:** guardar el volumen solo en el navegador (no sigue al usuario en otra computadora).
- **Consecuencias:** migración `database/migraciones/003-tanda-heladera-3.sql`. La prueba de dibujos exige que los 76 ingredientes tengan uno propio.
- **Participaron:** P3

---

## DEC-004 · 2026-10-17 · Disposición de la pantalla guardada en la cuenta
- **Contexto:** en la computadora, algunos usuarios prefieren ver todo sin desplazarse hacia abajo (más cosas a lo ancho).
- **Decisión:** columna `usuario.disposicion` (`normal` o `panoramica`, por defecto `normal`), igual que el color (DEC-003): valores en `UsuarioRepositorio::DISPOSICIONES`, se guarda desde el menú de la cuenta con `app/preferencias.php` y se aplica con `<html data-disposicion="...">`. En pantallas angostas siempre se ve la disposición normal.
- **Alternativas descartadas:** guardarlo en el navegador (se pierde al cambiar de computadora); una sola disposición ancha para todos (en notebooks chicas queda apretado).
- **Consecuencias:** migración `database/migraciones/002-tanda-heladera-2.sql`. Se suma también el ingrediente "Bife" (44) para tener un dibujo de carne distinto de la carne picada.
- **Participaron:** P3

---

## DEC-003 · 2026-10-17 · Color de la heladera guardado en la cuenta
- **Contexto:** en el estilo Heladera cada usuario puede elegir el color de la puerta (verde agua, crema, celeste, rosa o noche, que es oscuro). La elección tiene que seguirlo aunque entre desde otra computadora.
- **Decisión:** columna `usuario.color` (`VARCHAR(10)`, por defecto `verde`). Los valores válidos están en `UsuarioRepositorio::COLORES`; la página `app/color.php` (desde la tanda 2, `app/preferencias.php`) lo guarda y lo actualiza en la sesión. El color se aplica con `<html data-color="...">` y variables CSS.
- **Alternativas descartadas:** guardarlo solo en el navegador (`localStorage`): no necesita cambiar la base, pero se pierde al cambiar de computadora o de navegador.
- **Consecuencias:** quien ya tenía la base creada corre `database/migraciones/001-tanda-heladera.sql` (o, con Docker, `docker compose down -v`). Adelanta una parte de "Preferencias del usuario" del incremento 2.
- **Participaron:** P3

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
