# Estilo Heladera · Tanda 4 de cambios

> **Fecha:** 28/10/2026
> **Sobre:** la carpeta `alacena-estilo-heladera-tanda-3`, con los cambios hechos a mano: el dibujo inicial al azar, la animación de "¿Por qué Alacena?", la vitrina desparramada y los "¡ya!" al azar.
> **Resultado:** `alacena-estilo-heladera-tanda-4.zip`
> **Verificación:**
> - `composer lint` sin errores ni advertencias.
> - **43 pruebas en verde** (antes eran 37; se sumaron 6).
> - Recorrido en el navegador a 360, 768, 1024 y 1600 px por todas las páginas (sin cuenta, con cuenta y de administración): sin desbordes horizontales ni errores en la consola. También se revisó el color "noche".
> - **Probado en el navegador:**
>   - el color sin cuenta (se mantiene al recargar);
>   - el ojo de la contraseña;
>   - el "¿Seguro?" al salir;
>   - la nota al quitar un producto;
>   - el buscador con stickers ("piña" encuentra Ananá; Enter elige el primero);
>   - agregar "Quinoa" como ingrediente nuevo y editarlo después;
>   - responder una consulta y verla en "Mis consultas".

---

## Resumen

| # | Pedido | Estado | Incremento | Rol |
|---|---|---|---|---|
| 1 | Barra de navegación de un solo estilo | Hecho: Ingresar, Crear cuenta y la cuenta son imanes como Inicio y Contacto | 1 | P2 |
| 2 | Cambiar el color sin cuenta | Hecho: imán "Ajustes ▾" con los colores, el sonido y el volumen; se guarda en una cookie y pasa a la cuenta al registrarse | 1 | P2 + P3 |
| 3 | Dibujos en las partes vacías | Hecho: Ingresar, Crear cuenta, error, Contacto y las listas vacías | 1 | P2 |
| 4 | Efecto al pasar el mouse por "ALACENA" | Hecho: las letras saltan como una ola, los fideos echan vapor y suenan siete notas | 1 | P2 |
| 5 | Dibujos al agregar un ingrediente y agregar uno que no está | Hecho: buscador con stickers e ingrediente propio con el frasco; aclaración de "Agotado" | Adelanta el 2 | P2 + P3 |
| 6 | Aviso visual al eliminar | Hecho: nota despegada con niebla, en los 5 lugares donde se borra | 1 | P2 |
| 7 | Responder las consultas desde la administración | Hecho: se guarda, sale por correo y el usuario la ve en "Mis consultas" | Adelanta el 2 | P3 + P5 |
| 8 | Flecha para volver en la administración | Hecho: "← Volver al panel" (en el editor de recetas, "← Volver a recetas") | 1 | P1 + P5 |
| 9 | Ver la contraseña | Hecho: un ojo adentro de cada campo de contraseña | 1 | P2 |
| 10 | No repetir "Hola, María" | Hecho: el aviso al ingresar solo sale en el panel de administración, que no saluda | 1 | P3 |
| 11 | Confirmar al cerrar la sesión | Hecho: "¿Seguro que querés salir?" en el mismo lugar; salir pasa a ser un formulario con CSRF | 1 | P3 |
| 12 | Que la vitrina no se frene | Hecho | 1 | P2 |

**Cambia la base de datos** (DEC-006 y DEC-007):
- `ingrediente.usuario_id` y la columna generada `ambito`: el nombre es único dentro de la lista general y dentro de los propios de cada uno;
- `contacto.respuesta`, `respondida_en`, `respondida_por` y `respuesta_enviada`.

Para actualizarla tenés dos opciones:
- **Con Docker:** `docker compose down -v` y después `docker compose up -d`.
- **Sin perder datos:** correr, en orden, `database/migraciones/001` a `004`. La 004 se puede correr dos veces sin romper nada.

---

## Detalle

### 1 · Barra de navegación (`src/Vistas/encabezado.php`)

- **Sin cuenta:** Inicio, Contacto, Ingresar y Crear cuenta son imanes. Cada uno tiene su color, su nombre abajo y el mismo movimiento al pasar el mouse.
- **Ajustes:** al final hay un imán más, con los cinco colores de la heladera, llamado "Ajustes ▾".
- **Con cuenta:** la píldora "María ▾" ahora es un imán turquesa con el nombre abajo y la flechita. Al abrirlo queda marcado con la línea punteada, como la página en la que estás.
- **Página actual:** la línea punteada del imán toma el color del texto que va sobre la puerta, así se ve también en "noche".
- **Celular:** en el menú desplegable, los imanes van en fila con el nombre al lado.

### 2 · Color sin cuenta

- "Ajustes" despliega la misma hoja blanca del menú de la cuenta, con:
  - los imanes de colores;
  - el sonido y el volumen (aparecen solo con JavaScript);
  - una nota: "Con una cuenta, el color queda guardado en todos tus dispositivos".
- **Dónde se guarda:** el color va a la cookie `alacena_color` (un año) desde `app/preferencias.php`, que ahora acepta pedidos sin cuenta, pero solo para el color.
  - `Sesion::color()` lee la cookie y solo acepta valores de `UsuarioRepositorio::COLORES`, así que un valor trucho se ignora.
  - Con JavaScript cambia al instante; sin JavaScript, el formulario recarga la página.
- **Al registrarse:** `registro.php` pasa el color de la cookie a la cuenta nueva.

### 3 · Dibujos en las partes vacías

- **Ingresar, Crear cuenta y error:** stickers pegados a los costados de la hoja (`src/Vistas/stickers.php`):
  - ocho en Ingresar y Crear cuenta, cuatro en el error, y en cada página son ingredientes distintos;
  - se mecen despacio, cada uno a su tiempo, y al pasar el mouse se agrandan y se menean;
  - aparecen desde 1000 px de ancho; en el celular no, porque no sobra lugar;
  - la hoja ya estaba centrada y así queda mejor: los stickers llenan los costados.
- **Error:** además, el frasco pegado en la esquina de la hoja.
- **Contacto:**
  - el mate baila al lado del título;
  - una taza de café queda pegada en la esquina de "Dónde estamos" y se mueve cuando el mouse pasa por la hoja.
- **Listas vacías:** el frasco arriba del texto (`.vacio::before`). Aparece en:
  - recetas sin resultados;
  - despensa vacía;
  - Mi cocina sin sugerencias;
  - consultas, recetas, ingredientes y usuarios de la administración.

### 4 · Efecto en "ALACENA"

- Cada letra es un `<span>`. Al pasar el mouse (o al llegar con el teclado), las letras saltan una tras otra, como una ola.
- Los fideos echan tres hilos de vapor.
- Suenan siete notas suaves que suben, una por letra. Como mucho una vez cada 1,5 segundos, y el navegador recién deja sonar después del primer clic en la página.
- Los lectores de pantalla leen "Alacena" de corrido.

### 5 · Agregar un ingrediente (`public/app/despensa.php`, `public/js/despensa.js`)

- **Buscador con stickers:** con JavaScript, la lista desplegable se cambia por:
  - arriba, el ingrediente elegido con su dibujo;
  - un buscador;
  - la grilla de stickers.
- **Cómo busca:**
  - encuentra por el comienzo de cualquier palabra y por sinónimo ("tom" trae Tomate y Puré de tomate; "piña" trae Ananá);
  - **Enter** elige el primero que se ve.
- **Sin JavaScript:** queda la lista de siempre.
- **Si no está:** aparece "+ Agregar "Quinoa" como nuevo". Se abre "¿No está en la lista? Agregalo" con el nombre, el tipo (Almacén de entrada) y en qué se mide. Ese bloque es un `<details>`, así que también funciona sin JavaScript.
- **Ingrediente propio** (DEC-006):
  - solo lo ve quien lo agregó;
  - lleva el **frasco**, que no usa ningún producto de la lista;
  - en el buscador se marca con una ★.
- **Si ya existía:** si el nombre escrito ya está en la lista general o es un sinónimo, se usa ese y no se crea otro (escribir "piña" carga Ananá).
- **En la administración** (Ingredientes):
  - los propios aparecen al final, marcados "propio de María", y se pueden ver solos con la casilla "Solo los que agregaron los usuarios";
  - el botón "A la lista general" lo pasa a la lista general: desde ahí lo ven todos y se puede usar en recetas.
- **"Agotado":** debajo de los campos dice que es "se te terminó, pero lo dejás anotado para acordarte de comprarlo. No cuenta para las recetas."

### 6 · Aviso al eliminar (`public/js/sitio.js`)

- En lugar del cartel del navegador, aparece una **nota amarilla con chinche** sobre la niebla, como el editor de la despensa.
- **Qué muestra:**
  - el mensaje;
  - "Cancelar";
  - el botón rojo para borrar;
  - en la despensa y en los ingredientes, también el dibujo, que se menea.
- **Cómo se cierra:** con Escape o tocando la niebla se cancela.
- **Sonidos:** "nota que se despega" al abrir y "nota que se pega" al cancelar; al confirmar suena lo de siempre ("sticker que se arranca" en la despensa).
- Sirve para todos los `<form data-confirmar>`:
  - quitar de la despensa;
  - en la administración, borrar recetas, ingredientes, usuarios y consultas.
- `data-confirmar-si` cambia el texto del botón y `data-confirmar-dibujo` agrega el dibujo.

### 7 · Responder consultas (`public/admin/consultas.php`, `public/contacto.php`)

- **Responder:** cada consulta tiene un botón "Responder" (es un `<details>`) que despliega el campo para escribir. Al enviarla:
  1. se guarda en la base, con quién respondió y cuándo;
  2. se manda por correo a quien escribió (en Docker llega a Mailpit), con su consulta citada abajo;
  3. la consulta queda como respondida.
- **Si el correo no sale:** queda guardada igual y se avisa.
- **Después:** la respuesta queda como una notita verde abajo del mensaje y el botón pasa a decir "Responder de nuevo".
- **Mis consultas:** si quien escribió tenía cuenta, en Contacto aparece "**Mis consultas**": una nota por consulta, verde si ya tiene respuesta.

### 8 · Volver en la administración

- "← Volver al panel" en Consultas, Ingredientes, Recetas y Usuarios. Al editar un ingrediente o un usuario: "← Volver a ingredientes/usuarios"; en el editor de recetas: "← Volver a recetas".
- La flecha se corre a la izquierda al pasar el mouse. Es la función `enlaceVolver()` de `src/ayudas.php`.

### 9 · Ver la contraseña

- Cada campo de contraseña lleva un ojo adentro, a la derecha (`sitio.js`). Está en Ingresar, Crear cuenta (los dos campos) y el formulario de usuarios de la administración.
- Al tocarlo, la contraseña se ve y el ojo aparece tachado en rojo.
- Para los lectores de pantalla dice "Mostrar la contraseña" u "Ocultar la contraseña".

### 10 · Aviso al ingresar

- Ya no sale "Hola, María López" arriba de "Hola, María".
- Al administrador sí le sale "Hola, Administración." (es el nombre de la cuenta de prueba), porque el panel no tiene saludo.
- El aviso de bienvenida al crear la cuenta queda, porque dice otra cosa.

### 11 · Confirmar al salir

- "Salir" ahora es un formulario POST con el token CSRF. Así otra página no puede cerrarte la sesión con un enlace, y entrar a `salir.php` directo no hace nada.
- Al tocarlo, en el mismo lugar aparece "**¿Seguro que querés salir?**" con "Sí, salir" (rojo) y "No, me quedo".
- Si se cierra el menú, vuelve a mostrar solo "Salir".

### 12 · Vitrina

- Se sacó la regla que la frenaba. Sigue moviéndose aunque el mouse esté encima, y el dibujo de abajo del mouse igual se agranda y se menea.

### Otros arreglos

- **Desborde en Ingredientes (celular):** la página se desbordaba porque el texto oculto "Acciones" de la tabla se salía de la caja. Ahora `.tabla-contenedor` tiene `position: relative`.
- **Casillas del buscador:** pueden partirse en dos renglones en el celular.

---

## Archivos

| Archivo | Qué cambió |
|---|---|
| `database/schema.sql` · `migraciones/004-tanda-heladera-4.sql` · `README.md` | Ingredientes propios y respuestas |
| `docs/decisiones.md` · `modelo-datos.md` · `estilo-heladera*.md` | DEC-006, DEC-007; se limpiaron campos vacíos que habían quedado de la plantilla |
| `src/Sesion.php` | Color sin cuenta (`COOKIE_COLOR`, `recordarColor()`) |
| `src/ayudas.php` | `iconoIngrediente($nombre, $propio)` y `enlaceVolver()` |
| `src/Repositorios/IngredienteRepositorio.php` | `todos($usuarioId)`, `idPorNombre()`, `crearPropio()`, `pasarALaListaGeneral()`; `existeNombre()` por ámbito; `buscar()` solo de la lista general |
| `src/Repositorios/DespensaRepositorio.php` | `propio` en las consultas (para el frasco) |
| `src/Repositorios/ContactoRepositorio.php` | `obtener()`, `responder()`, `delUsuario()` |
| `src/Vistas/encabezado.php` · `stickers.php` (nuevo) | Barra, Ajustes, letras del nombre, salir; stickers de los costados |
| `public/app/preferencias.php` · `registro.php` · `login.php` · `salir.php` · `error.php` | Color sin cuenta, aviso, salir por POST, dibujos |
| `public/app/despensa.php` · `index.php` | Ingrediente nuevo, frasco, "Agotado" |
| `public/admin/*.php` | Volver; ingredientes propios; responder consultas |
| `public/contacto.php` | Mate, café y "Mis consultas" |
| `public/js/sitio.js` | Nota para confirmar, "¿Seguro?" al salir, ojo de la contraseña |
| `public/js/despensa.js` | Buscador con stickers e ingrediente nuevo |
| `public/js/sonidos.js` | Sonido "marca", imán de la cuenta |
| `public/css/estilos.css` | Todo lo anterior (bloques "Tanda 4" al final) |
| `tests/Integracion/PaginasTest.php` · `Navegador.php` | 6 pruebas nuevas; `agregarCookie()` |

## Pruebas nuevas

1. `testLaBarraSinCuentaEsTodaDeImanesConAjustes`
2. `testSinCuentaElColorSeGuardaEnUnaCookieYPasaALaCuentaNueva`: también comprueba que un valor inválido en la cookie no se usa.
3. `testAlIngresarNoSeRepiteElSaludo`
4. `testSalirPreguntaYSoloCierraLaSesionPorPost`
5. `testUnIngredienteQueNoEstaSeAgregaComoPropioConElFrasco`: comprueba que:
   - otro usuario no lo ve;
   - "piña" no crea nada;
   - el administrador lo pasa a la lista general.
6. `testElAdministradorRespondeYElUsuarioVeLaRespuesta`

## Pendiente

- **Repo del equipo:** sigue pendiente lo que habíamos dejado para después de la tanda 3:
  - `admin/usuarios.php` da error 500 sin `composer install`;
  - las páginas de P5 cargan las tipografías desde Google;
  - el repo tiene 18 pruebas y le falta `PaginasTest`.
- **Para llevar al repo** (incremento 2), si el equipo lo quiere:
  - los ingredientes propios;
  - las respuestas a las consultas;
  - salir por POST.

---

## Tanda 4.1 · Ajustes (29/10/2026)

> **Resultado:** `alacena-estilo-heladera-tanda-4-1.zip`. Sin cambios en la base de datos.
> **Verificación:** lint en verde y **43 pruebas en verde** (la de la barra comprueba también la versión del JS y los 15 stickers de Ingresar). Recorrido a 360, 1100, 1280 y 1600 px sin desbordes ni errores.

| # | Pedido | Qué se hizo |
|---|---|---|
| 0 | El ojito y los dibujos del buscador no aparecían | Era la **caché del navegador**: guardaba el JavaScript de la tanda 3. Ahora todos los `.js` (y el CSS) se piden con `recurso()`, que agrega `?v=fecha del archivo`; cuando un archivo cambia, el navegador baja el nuevo solo |
| 1 | Crear cuenta: dibujos más grandes | Hasta **110 px** (antes 76), con `clamp()` para pantallas más chicas |
| 2 | Ingresar: dibujos más grandes y llenar abajo de la hoja | 110 px. La puerta ocupa todo el alto: **10 stickers a los costados**, de arriba hasta abajo, y **5 desparramados debajo de la hoja**, cada uno a otra altura (no en fila) |
| 3 | Error: lo mismo | Igual que Ingresar (15 dibujos distintos) |
| 4 | Inicio: dibujos a los costados de "¿Por qué Alacena?" | 3 por lado (mandarina, ricota, espinaca / chocolate, langostinos, merluza), que se mecen y se agrandan al pasar el mouse. Desde 1260 px, porque más angosto no hay lugar |
| 5 | Agregar ingrediente: ver el dibujo elegido | Además del dibujo chico arriba del buscador, el elegido aparece **grande en la esquina de la nota** y se menea cada vez que cambia. En el celular no se muestra, porque no hay lugar al lado del título |

**Otros:**
- el botón "+ Agregar "…" como nuevo" ya no se desarma en el celular;
- `src/Vistas/stickers.php` tiene tres zonas (`costados`, `abajo` y `porque`), cada una con su lista de lugares.

**Archivos:**
- `src/ayudas.php`: `recurso()`;
- `src/Vistas/encabezado.php` y las páginas que cargan JS: `recurso()`;
- `src/Vistas/stickers.php`;
- `public/login.php`, `registro.php`, `error.php` e `index.php`;
- `public/js/despensa.js`;
- `public/css/estilos.css`;
- `tests/Integracion/PaginasTest.php`.
