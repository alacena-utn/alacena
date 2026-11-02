# Estilo Heladera · Tanda 2 de cambios

> **Fecha:** 17/10/2026
> **Sobre:** `alacena-estilo-heladera-tanda-1.zip`
> **Resultado:** `alacena-estilo-heladera-tanda-2.zip`
> **Verificación:**
> - `composer lint` sin errores ni advertencias.
> - **33 pruebas en verde** (antes eran 28; se sumaron 5 y se actualizó 1).
> - En el navegador se recorrió a 390, 1280 y 1600 px, en disposición normal y panorámica y con los colores verde y noche, sin errores en la consola.
> - Se probaron a mano en el navegador:
>   - el clic en cualquier parte de la hoja de receta;
>   - los pasos tildados después de recargar la página;
>   - el menú de la cuenta (se cierra al tocar afuera);
>   - "Cambiar fecha", que abre la nota con la fecha seleccionada;
>   - los sonidos de cada acción.

---

## Resumen

| # | Pedido | Estado | Incremento | Rol |
|---|---|---|---|---|
| 1 | Sonidos en la mayoría de las interacciones, sutiles y no repetidos | Hecho: **13 sonidos** distintos (los 9 de la propuesta, con ida y vuelta para el pop, la nota y el sticker, más "tic" al prenderlos) | 1 | P2 |
| 2 | Imanes con poco brillo y con sombra de profundidad | Hecho | 1 | P2 |
| 3 | Colores, sonido, nombre y Salir dentro de un menú, también en la PC | Hecho: menú de la cuenta desplegable | 1 | P2 + P1 (encabezado) |
| 4 | "+ Agregar a la heladera" → "Agregar producto" | Hecho | 1 | P2 |
| 5 | Distinguir el queso cremoso del rallado y agregar un dibujo de carne | Hecho: `queso-rallado.svg` y `bife.svg`, más el ingrediente **Bife** (44) | 1 | P2 + P3 (base) |
| 6 | La hoja de receta entera clickeable (no solo el título) | Arreglado | 1 | P3 |
| 7 | Pasos de la receta con casilla | Hecho, versión simple | **Adelanta parte del 2** (modo cocina) | P3 |
| 8 | Todas las hojas de receta se levantan al pasar el mouse | Hecho | 1 | P3 |
| 9 | Movimiento en los clips | Hecho | 1 | P3 |
| 10 | Vencidos en gris, con "Cambiar fecha" y un contador arriba | Hecho | 1 | P2 |
| 11 | "¡ya!" en el selector de Mi cocina, con esos ingredientes primero | Hecho | 1 | P2 |
| 12 | Revisar medidas y alineaciones | Hecho | 1 | P2 |
| 13 | Movimiento en las notas pegadas | Hecho | 1 | P2 |
| 14 | Disposición a lo ancho | Hecho: **Normal / Panorámica**, guardada en la cuenta | Fuera de los dos (preferencia, como el color) | P2 + P3 (base) |

**Cambia la base de datos:**
- columna `usuario.disposicion` (DEC-004);
- ingrediente 44, "Bife", con los sinónimos "churrasco" y "filete".

Para actualizarla tenés dos opciones:
- **Con Docker:** `docker compose down -v` y después `docker compose up -d`.
- **Sin perder datos:** correr `database/migraciones/002-tanda-heladera-2.sql`. Si no corriste la 001, va primero esa.

---

## Detalle

### 1 · Sonidos (`public/js/sonidos.js`, reescrito)

- **Arrancan prendidos**, salvo que el sistema tenga activado "reducir movimiento". El navegador no deja sonar nada hasta el primer clic.
- Se apagan con el imán del parlante, que ahora está en el menú de la cuenta y dice "Sonidos prendidos" o "Sonidos apagados". La elección se guarda en el navegador.
- **Más bajos que antes:** todo pasa por un volumen general de 0,55.
- **Cada vez suenan un poco distinto:** el tono varía ±4 % y el ruido se genera de nuevo en cada reproducción.

| Acción | Sonido |
|---|---|
| Cambiar de sección desde el menú | `iman`, con un tono distinto para cada sección |
| Abrir una receta (tarjeta o enlace) | `hoja`: papel que se da vuelta |
| Elegir o soltar un ingrediente en el selector | `pop-arriba` / `pop-abajo` |
| Tildar un paso | `birome` (destildar: `clic`) |
| Abrir o cerrar la nota del editor | `nota-despega` / `nota-pega` |
| Guardar o quitar en la despensa | `sticker-pega` / `sticker-arranca` |
| Pestañas, filtros, botones, enlaces y el menú de la cuenta | `clic` (muy corto) |
| Elegir color o disposición | `iman-grave` |
| Formulario con un campo mal cargado (validación del navegador) | `error` |
| Prender los sonidos | `tic` |

- **Un solo sonido por clic:** una lista de reglas (`REGLAS`) decide cuál suena; gana la primera que coincide. Los sonidos de la despensa, el selector y los pasos los pone cada página (`despensa.js`, `cocina.js`, `receta.js`).
- Antes de cambiar de página se esperan 120 ms, para que el sonido no se corte.
- **Límite:** los errores que devuelve el servidor no suenan, porque llegan en una página nueva y el navegador no deja sonar sin un clic. Sí suenan los errores que detecta el navegador antes de enviar el formulario.

### 2 · Imanes

- Se sacó el punto de brillo blanco de los imanes del menú, los colores, el parlante, las chinches y los globos. Quedan con el degradé suave de antes.
- `--sombra-iman` ahora es un borde oscuro abajo más una sombra proyectada en dos capas, que es lo que les da profundidad. Al pasar el mouse, la sombra se alarga (`--sombra-iman-alto`).

### 3 · Menú de la cuenta (`src/Vistas/encabezado.php`, `public/js/sitio.js`)

- A la derecha del menú hay un **imán con tu nombre** ("María ▾"). Al tocarlo se despliega un **post-it** con:
  - tu nombre y tu email;
  - el color de la heladera;
  - la disposición (solo se ve en pantallas de 1100 px o más);
  - el sonido;
  - Salir.
- Es un `<details>`, así que funciona sin JavaScript. Con JS se cierra al tocar afuera o con Escape.
- En el celular, el mismo bloque aparece dentro del panel del botón "Menú", alineado a la izquierda con todo lo demás.
- Si no hay nadie ingresado, el parlante queda en la barra, al lado de Ingresar y Crear cuenta.
- `app/color.php` pasa a llamarse **`app/preferencias.php`** y guarda tanto el color como la disposición. `UsuarioRepositorio::guardarColor()` pasa a llamarse `guardarPreferencia()`, y `Sesion::cambiarColor()`, `cambiarPreferencia()`.

### 5 · Dibujos

- `queso-rallado.svg`: un montoncito de queso rallado con el rallador al lado. `iconoIngrediente()` lo encuentra solo, porque prueba primero el nombre completo.
- `bife.svg`: un bife con hueso en T y marcas de la parrilla, para que no se confunda con la carne picada (que no se tocó) ni con el jamón.
- Ingrediente nuevo **"Bife"** (Carnes, por unidad, 200 g, dura 3 días).
- **Prueba nueva:** cada uno de los 44 ingredientes tiene un dibujo propio y no se repite ninguno.

### 6, 8 y 9 · Hojas de receta

- **Por qué solo se podía tocar el título:** en la tanda 1, para alinear los títulos con los renglones, les puse `position: relative`. Eso achicó el enlace estirado al tamaño del título. Ahora el `h2` de las recetas va con `position: static`, y quedó anotado en el CSS para que no vuelva a pasar.
- **Por qué algunas hojas se movían y otras no:** las hojas inclinadas tenían una regla más específica que la del hover. Ahora la inclinación es una variable (`--giro`) y **todas** usan el mismo efecto: suben 9 px, se inclinan un poco hacia el otro lado, crecen apenas y la sombra se aleja.
- **Clip:** cuando la hoja se levanta, se balancea desde arriba (`clip-balanceo`, 1 segundo) y su sombra se aleja.

### 7 · Pasos con casilla (`public/js/receta.js`, nuevo)

- Cada paso tiene una casilla dibujada a mano.
  - **Al tildarlo:** se tacha con birome roja.
  - **El paso que sigue:** se marca con resaltador amarillo.
  - **"Empezar de nuevo":** aparece cuando hay algo tildado.
- Se recuerda **en ese navegador, por receta** (`localStorage`). Guardarlo en la cuenta, con temporizador y descuento de la despensa, es el **modo cocina del incremento 2**.
- Sin JavaScript, las casillas se pueden tildar, pero no se recuerdan.

### 10 · Despensa: vencidos y contador

- **Contador arriba**, con un imán de color por grupo: **N bien** (verde), **N por vencer** (hoy a 3 días, amarillo), **N vencidos** (rojo) y **N agotados** (gris, solo si hay alguno).
  - Cuenta **toda la despensa**, aunque estés viendo una sola ubicación.
  - Cada producto cuenta una sola vez: agotado gana a vencido, y vencido a por vencer.
  - Lo calcula `DespensaRepositorio::resumen()`, que tiene su prueba.
- **Vencidos:**
  - el sticker va en gris y el nombre en cinta gris;
  - en lugar del "¡ya!" aparece un cartelito **"¿Venció? Cambiar fecha"**, que abre la nota con la fecha seleccionada (y el calendario abierto, si el navegador lo permite);
  - van **al final de la lista**.
- El "¡ya!" queda solo para lo que vence hoy o mañana.
- Botón: **"+ Agregar producto"**.

### 11 · Selector de Mi cocina

- Los stickers se ordenan por vencimiento: lo más próximo primero y lo que no tiene fecha al final. Los vencidos no aparecen, porque no se pueden usar.
- Lo que vence hoy o mañana lleva el globo **"¡ya!"** arriba a la izquierda (a la derecha va la tilde cuando lo elegís). Para los lectores de pantalla dice "(vence hoy)" o "(vence mañana)".

### 12 · Medidas y alineaciones

- El borde derecho del menú de la cuenta queda alineado con el de las hojas y los botones de la página.
- En el celular, el bloque de la cuenta queda alineado a la izquierda con el resto del menú (antes "Salir" quedaba corrido).
- En la despensa, los botones Editar y Quitar de todos los stickers de una fila quedan a la misma altura, y la fecha ya no se corta en dos renglones.
- Los dibujos de las tarjetas se achican un poco si la hoja es angosta, así siempre entran los 5 y el "+N".

### 13 · Notas que se mueven

Los post-its ("Usalo pronto", el menú de la cuenta) y los avisos se **hamacan desde la chinche** al pasar el mouse (`hamacar`, 1,2 segundos). Los formularios no se mueven, para que no molesten mientras escribís.

### 14 · Disposición panorámica

- Se elige en el menú de la cuenta: **Normal / Panorámica**.
  - Se guarda en `usuario.disposicion` (DEC-004).
  - Se aplica con `<html data-disposicion="...">`.
  - **Solo cambia algo en pantallas de 1100 px o más.** En el celular y en notebooks chicas siempre se ve normal, y la opción no aparece.
- **Qué cambia:**
  - el ancho máximo pasa de 1180 a 1760 px;
  - **Mi cocina:** a la izquierda "Usalo pronto" y el selector, con stickers más chicos; a la derecha, el resultado del selector y "Podés cocinar";
  - **Recetas:** los filtros pasan a una columna fija a la izquierda, que acompaña al bajar, y las hojas quedan en 4 o 5 columnas;
  - **Despensa:** entran más stickers por fila.
- Para esto, `app/index.php` y `app/recetas.php` ahora agrupan su contenido en bloques (`.cocina__lateral`, `.cocina__principal`, `.catalogo`, `.catalogo__resultados`). En la disposición normal se ven exactamente como antes.

---

## Archivos

| Archivo | Cambio |
|---|---|
| `database/schema.sql` | `usuario.disposicion` |
| `database/seed.sql` | Ingrediente 44 "Bife" y sus sinónimos |
| `database/migraciones/002-tanda-heladera-2.sql` | **Nuevo** |
| `database/README.md` | Migraciones en orden |
| `docs/decisiones.md` | **DEC-004** y nota en DEC-003 |
| `docs/modelo-datos.md` | Columna `disposicion` |
| `docs/estilo-heladera-tanda-2.md` | **Nuevo**: copia de este registro |
| `public/app/color.php` → `public/app/preferencias.php` | Renombrado; guarda el color y la disposición |
| `public/app/despensa.php` | Contador, vencidos con "Cambiar fecha" y "Agregar producto" |
| `public/app/index.php` | Columnas lateral y principal; "¡ya!" en el selector |
| `public/app/recetas.php` | Bloque `.catalogo` para la disposición panorámica |
| `public/app/receta.php` | Pasos con casilla + `receta.js` |
| `public/css/estilos.css` | Imanes, menú de la cuenta, hojas que se levantan, clip, notas, pasos, contador, vencidos y panorámica (de 611 a 765 líneas) |
| `public/img/ingredientes/queso-rallado.svg`, `bife.svg` | **Nuevos** |
| `public/js/sonidos.js` | Reescrito: 13 sonidos y reglas por clic |
| `public/js/receta.js` | **Nuevo** |
| `public/js/sitio.js` | Menú de la cuenta y preferencias |
| `public/js/despensa.js` | Abrir con foco en la fecha y sonidos de la nota |
| `public/js/cocina.js` | Sonidos pop |
| `src/Vistas/encabezado.php` | Menú de la cuenta y `data-disposicion` |
| `src/Sesion.php` | `disposicion()` y `cambiarPreferencia()` |
| `src/Repositorios/UsuarioRepositorio.php` | `DISPOSICIONES` y `guardarPreferencia()` |
| `src/Repositorios/DespensaRepositorio.php` | Vencidos al final, `disponibles()` con días y `resumen()` |
| `tests/…` | Ver abajo |

## Pruebas

| Prueba | Qué comprueba |
|---|---|
| `testElColorYLaDisposicionSeGuardanEnLaCuenta` (reemplaza a la del color) | Guarda "noche" y "panorámica", rechaza valores inventados y no redirige a otro sitio |
| `testLaDespensaMuestraElContadorYLosVencidosAlFinal` | 1 vencido en el contador, el tomate gris al final, "Cambiar fecha" y "Agregar producto" |
| `testElSelectorPonePrimeroLoQueVenceConElGloboYa` | El queso cremoso (vence mañana) va primero con "¡ya!"; el tomate vencido no aparece |
| `testElDetalleTienePasosParaTildar` | Una casilla por paso |
| `testResumenDeLaDespensa` | Los grupos del contador (unitaria) |
| `testCadaIngredienteTieneSuPropioDibujo` | Los 44 ingredientes tienen dibujo y no se repiten |

## Si se pasa al repositorio del equipo

Sigue valiendo lo de la tanda 1: primero un PR de **P3** con la base, `Sesion`, los repositorios, `preferencias.php`, las pruebas y DEC-004; después uno de **P2** con el CSS, el JS, los dibujos y las páginas. `receta.php` y `receta.js` son de **P3**.

## Pendiente o para decidir

- Los errores que valida el servidor no suenan (explicado arriba).
- Las casillas de los pasos se guardan solo en el navegador hasta el modo cocina del incremento 2.

---

## Ajustes después de revisarla (tanda 2.1 · 17/10/2026)

> **Resultado:** `alacena-estilo-heladera-tanda-2-1.zip`
> **Verificación:**
> - `composer lint` sin errores ni advertencias y **34 pruebas en verde** (se sumó 1).
> - En el navegador: la nota a mitad del hamaqueo, el menú de la cuenta en blanco y el filtro "por vencer" con sus 3 productos.

| # | Lo que se notó | Qué se cambió | Archivos |
|---|---|---|---|
| 13 | Las notas casi no se movían | Antes solo se hamacaban ±2° y volvían a su lugar, casi no se notaba. Ahora, al pasar el mouse, la nota **se despega y queda levantada** (sube 4 px, gira 1,2° y la sombra se aleja) y se hamaca más (+4°, −3°, +2,4°, −0,6°, en 1,4 s). Si el sistema tiene activado "reducir movimiento", no se hamaca, pero igual queda levantada, así que siempre se ve que reacciona | `estilos.css` (`.aviso`, `.postit`, `@keyframes hamacar`) |
| — | No se podía filtrar por bien, por vencer o vencidos | **Cada número del contador es un filtro**: muestra solo esos productos y, si lo tocás de nuevo, vuelven todos. Se puede combinar con las pestañas de ubicación (`?ubicacion=heladera&estado=por_vencer`), y las pestañas mantienen el filtro elegido. El elegido queda en negro con borde punteado. Si no hay ninguno, dice "No hay productos vencidos…" con "Ver todos". El contador sigue contando **toda** la despensa | `despensa.php`, `DespensaRepositorio` (`ESTADOS`, `estado()`, `resumen()` usa `estado()`), `estilos.css` |
| — | El menú de la cuenta era amarillo, como "Usalo pronto" | Ahora es una **hoja blanca** (papel) con la chinche azul, distinta de los post-its amarillos | `estilos.css` (`.cuenta__panel`) |

**Prueba nueva:** `testLosNumerosDelContadorFiltranLaDespensa`. Comprueba que "vencidos" muestra solo el tomate, que "por vencer" muestra el queso cremoso, la leche y el zapallito, y que el freezer con "vencidos" está vacío y lo dice.

No cambia la base de datos.
