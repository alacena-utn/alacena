# Registro de decisiones

Cada decisión importante se anota acá: qué se decidió, por qué y qué alternativas se descartaron.
Lo más nuevo va arriba. Sirve para el informe y para la defensa.
Formato: decisiones tipo ADR (ver la guía de metodología del equipo, sección 9.2).

---

## DEC-011 · AAAA-MM-DD · (título)
- **Contexto:** 
- **Decisión:** 
- **Alternativas descartadas:** 
- **Consecuencias:** 
- **Participaron:** 

---

## DEC-010 · 2026-10-31 · Perfil: cambiar datos y contraseña, y dar de baja la cuenta
- **Contexto:** el incremento 1 no dejaba al usuario cambiar su nombre, su email ni su contraseña, ni borrar su cuenta. Lo pide el bloque 2.4 del plan.
- **Decisión:**
  - Página `app/perfil.php` con tres formularios. Para cambiar la contraseña o borrar la cuenta pide la contraseña actual.
  - La baja pide además una casilla de confirmación.
  - Al borrar la cuenta, la despensa, los ingredientes propios y las preferencias se van con ella (`ON DELETE CASCADE`). Las consultas de contacto quedan sin usuario.
  - La última cuenta de administración no se puede borrar.
  - Se entra desde el menú de la cuenta.
- **Alternativas descartadas:**
  - Una baja lógica (marcar la cuenta como inactiva): conserva datos personales que ya no hacen falta.
  - Pedir la confirmación por mail: queda para cuando esté el correo real (entrega B).
- **Consecuencias:** sin cambios en la base. Pruebas en `tests/Integracion/PerfilTest.php`.
- **Participaron:** P1

---

## DEC-009 · 2026-10-31 · Recetario en JSON con importador y validador
- **Contexto:** cargar decenas de recetas desde el ABM, de a una, es lento y no deja revisarlas entre todos en un PR.
- **Decisión:**
  - Cada receta es un archivo en `database/recetas/*.json` (formato en `docs/guia-recetas.md`).
  - `scripts/importar-recetas.php` las valida contra el catálogo y las carga en la base. Si la receta ya existe (mismo título), la actualiza.
  - Con `--sql` genera `database/recetas.sql`, que Docker carga al crear la base y que sirve para el hosting.
  - El validador revisa ingredientes, números, unidades y marcadores `{ing:...}`, y que las etiquetas `vegetariana` y `sin_tacc` sean ciertas.
  - Se suman la etiqueta `dulce`, 31 ingredientes (con dibujo y otros nombres) y **50 recetas originales del equipo**: 62 en total.
  - Los otros nombres de cada ingrediente se editan en el ABM de ingredientes.
  - Los pasos con temporizador se escriben `[⏱ 15 min]` en el ABM de recetas.
- **Alternativas descartadas:**
  - Planilla CSV: no representa bien la lista de ingredientes y los pasos de cada receta.
  - Copiar recetas de sitios web: tienen derechos de autor.
- **Consecuencias:**
  - Migración `005-incremento-2-a.sql` (ingredientes, otros nombres, etiqueta y sustituciones).
  - La prueba `RecetarioTest` falla si alguien cambia un .json y no regenera `recetas.sql`.
- **Participaron:** P3 (importador y recetas), P2 (dibujos)

---

## DEC-008 · 2026-10-31 · Recomendación con puntaje, sustituciones y porciones
- **Contexto:** en el incremento 1, "Podés cocinar" ordenaba solo por cuántos ingredientes tenía el usuario. El informe del proyecto (sección 10) define un puntaje con pesos, prioridad a lo que vence y sustituciones.
- **Decisión:**
  - `Dominio\Puntaje::calcular()` aplica la fórmula del informe:
    - disponibilidad 1, ½ (con sustituto) o 0;
    - cobertura ponderada con pesos 3, 2 y 1 (los opcionales no cuentan);
    - +5 por cada ingrediente que vence en 3 días o menos (hasta +15);
    - −6 por cada principal que falta;
    - −8 si la cocinó en la última semana.
  - Agrupa en "Podés hacerla ahora", "Te falta 1" y "Casi". No muestra las que no tienen ni la mitad de lo principal ni las que chocan con una restricción del usuario.
  - El peso se elige en el ABM de recetas ("Importancia").
  - Las sustituciones se cargan en la tabla `sustitucion`.
  - `Dominio\Porciones` escala cantidades y pasos (`{ing:papa}` → "papa (600 g)") de 1 a 12 porciones, con redondeo de cocina.
- **Alternativas descartadas:**
  - Calcular el puntaje en SQL: difícil de leer y de probar.
  - Mostrar una sola lista ordenada: no responde "¿qué puedo hacer ya?".
- **Consecuencias:**
  - Mi cocina muestra los tres grupos.
  - El listado de recetas suma "Ordenar: recomendadas".
  - Las tarjetas avisan qué usan de lo que vence y con qué reemplazar lo que falta.
  - Pruebas unitarias (`PuntajeTest`, `PorcionesTest`) y de integración (`RecomendacionTest`).
- **Participaron:** P3

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
  - sin cuenta, el color se guarda en la cookie `cooki_color` (un año) y pasa a la cuenta al registrarse.
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
