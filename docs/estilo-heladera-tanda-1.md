# Estilo Heladera · Tanda 1 de cambios

> **Fecha:** 17/10/2026
> **Sobre:** la versión Heladera del proyecto completo (`alacena-estilo-heladera.zip` del 16/10).
> **Resultado:** `alacena-estilo-heladera-tanda-1.zip`.
> **Verificación:**
> - `composer lint` sin errores ni advertencias.
> - **28 pruebas en verde.** Había 22; se sumaron 6.
> - Recorrido en el navegador a 390 px y a 1280 px, con el color verde y con el oscuro, sin errores en la consola.
> - Probados en el navegador: agregar, editar y filtrar en la despensa, la cantidad, el selector, los colores y los sonidos.

---

## Resumen

| # | Pedido | Estado | Incremento | Rol en el reparto |
|---|---|---|---|---|
| 1 | Nombre nuevo | **No se hizo**: ninguna opción convenció | — | — |
| 2 | Relieve en imanes y chinches, más un clip | Hecho | 1 | P2 (común) |
| 3 | Dibujo para cada ingrediente | Hecho: **30 nuevos**, ahora los 43 tienen el suyo | 1 (adelanta parte de "Datos" del 2) | P2 |
| 4 | Toda la tarjeta de receta clickeable | Hecho | 1 | P3 |
| 5 | Dibujos de los ingredientes en la tarjeta, con los que faltan en gris y tachados | Hecho | 1 (adelanta lo visual de "mostrar qué falta" del 2) | P3 |
| 6 | Sonidos | Hecho: apagados por defecto, con un imán para prenderlos | 1 | P2 |
| 7 | Editor de la despensa como nota que se despega, con niebla alrededor | Hecho | 1 | P2 |
| 8 | Cantidad exacta de cada producto | Hecho | 1 | P2 |
| 9 | Color de la página guardado por usuario, con uno oscuro | Hecho: 5 colores, incluido "noche" | 1 (adelanta "Preferencias" del 2) | P2 + P3 (base) |
| 10 | Selector de ingredientes en Mi cocina | Hecho | 1 | P2 (+ P3 en la consulta) |
| 11 | Texto apoyado sobre los renglones | Hecho | 1 | P2 (común) |

**Cambia la base de datos:** se agregó la columna `usuario.color`.
- **Con Docker:** `docker compose down -v` y después `docker compose up -d`.
- **Sin perder datos:** `database/migraciones/001-tanda-heladera.sql`.

La cantidad exacta **no** requirió cambios en la base: `despensa_item.cantidad` y `unidad` ya existían.

---

## Detalle por pedido

### 2 · Relieve en imanes, chinches y clip

| Qué | Cómo |
|---|---|
| Imanes del menú | Brillo blanco arriba a la izquierda, borde oscuro abajo y sombra proyectada sobre la puerta. Al pasar el mouse el imán "se separa" y la sombra se alarga. |
| Chinches de los post-its y avisos | El mismo brillo, más la sombra corrida de la aguja (`--sombra-chinche`). |
| Globo "¡ya!" e imanes nuevos (colores y sonido) | Usan el mismo relieve. |
| Clip (nuevo) | Las hojas de recetas (tarjetas y detalle) van sujetas con un **clip metálico** (`public/img/clip.svg`) en vez de cinta. En la grilla, el clip alterna de lado. |

Variables nuevas en `:root`: `--sombra-iman` (reescrita), `--sombra-iman-alto` y `--sombra-chinche`.

### 3 · Dibujos de los ingredientes

Antes había 13 dibujos y 30 ingredientes usaban el frasco genérico. Se dibujaron los 30 que faltaban con el mismo estilo (`viewBox` 96, relleno plano, contorno `#1D1B18` de 3,5 y un brillo):

> aceite, arroz, arveja, atún, azúcar, banana, batata, calabaza, caldo, carne (picada), cebolla de verdeo, choclo, crema (de leche), fideos, frutilla, jamón, lechuga, lenteja, manteca, palta, pan (rallado), perejil, pimentón, pimienta, polenta, poroto, puré (de tomate), sal, tapa (de tarta) y zapallito.

`iconoIngrediente()` (en `src/ayudas.php`) ahora busca **primero el nombre completo** (`cebolla-de-verdeo.svg`) y después la primera palabra. Así "Cebolla de verdeo" tiene su propio dibujo y no usa el de la cebolla.

**Para sumar un dibujo:** guardar el SVG en `public/img/ingredientes/` con el nombre sin tildes ni mayúsculas, con guiones (`dulce-de-leche.svg`) o solo la primera palabra (`dulce.svg`). El script que generó estos 30 (`iconos.py`) queda en la carpeta personal, no en el repo.

### 4 y 5 · Tarjetas de recetas

- **Toda la hoja es clickeable.** El enlace del título se estira sobre la tarjeta con `h2 a::before { position: absolute; inset: 0 }`, así que sigue habiendo **un solo enlace** (bien para los lectores de pantalla). Al pasar el mouse, la hoja se endereza y sube; con el teclado se marca con un contorno azul.
- **Dibujos de los ingredientes:** hasta 6, de más a menos importante (`receta_ingrediente.peso`), y "+N" si hay más. Los que faltan van **en gris y con una raya roja** de birome. Cada dibujo tiene un `alt` ("Tapa de tarta (te falta)"), así que no depende solo del color.
- La consulta de recetas (`RecetaRepositorio::SELECT_RESUMEN`) trae una columna nueva, `ingredientes`, y `RecetaRepositorio::ingredientesDeTarjeta()` la separa. Se usa en el catálogo, en "Podés cocinar" y en el selector.

### 6 · Sonidos (`public/js/sonidos.js`, nuevo)

- **No hay archivos de audio.** Los sonidos se generan con Web Audio, así que no hay licencias que citar.
- **Arrancan apagados.** Se prenden con el **imán del parlante**, al lado de los colores. La elección queda guardada en ese navegador (`localStorage`).
- Sin JavaScript no hay imán ni sonidos.

| Sonido | Cuándo |
|---|---|
| `clack` (imán) | Menú, pestañas de la despensa y colores |
| `papel` | Abrir la nota del editor |
| `pegar` | Agregar o guardar en la despensa, y elegir un ingrediente en el selector |
| `despegar` | Quitar de la despensa (después de confirmar) |
| `tic` | Prender los sonidos |

Para que el sonido no se corte, los enlaces del menú y los formularios con `data-sonido` esperan unos 150 ms antes de cambiar de página. Desde cualquier archivo se usa `window.sonar('clack')`.

### 7 · Editor de la despensa como nota despegada

- Arriba a la derecha hay un botón rojo: **"+ Agregar a la heladera"**.
- Con JavaScript, el formulario se mueve a un `<dialog>`. Al abrirse, la nota se agranda, se inclina, proyecta una sombra larga y **todo lo de atrás queda desenfocado**: la niebla es `::backdrop` con `backdrop-filter: blur`.
- **Editar** completa la nota con los datos de la fila (atributos `data-*` del `<tr>`), **sin recargar**.
- Se cierra con Cancelar, con Escape o tocando la niebla.
- Si el servidor devuelve errores, o se entra con `?editar=ID`, la nota aparece abierta sola (`data-abrir="1"`).
- **Sin JavaScript** funciona como antes: el formulario queda arriba de la lista.

### 8 · Cantidad exacta

- Campo nuevo **"Cantidad exacta (opcional)"**. Al lado se muestra la unidad del ingrediente (unidades, g o ml), que se guarda junto con la cantidad.
- **Regla con el nivel**, la misma en JavaScript y en PHP:
  - cantidad 0 → *Agotado*;
  - si hay cantidad y el nivel era *Agotado*, pasa a *Normal*;
  - sin cantidad, se elige el nivel como siempre.
- En el sticker se ve "6 unidades", "500 g" o "1.000 ml" en lugar de "Normal" o "Poco". El color sigue siendo el del nivel.
- Funciones nuevas en `DespensaRepositorio`: `textoCantidad()` y `guardar()`, que ahora recibe `cantidad` y `unidad`.
- En `seed.sql`, María tiene 4 de sus ítems con cantidad (huevo, leche, fideos y papa).

### 9 · Colores de la heladera por usuario

- **Cinco imanes** al lado del nombre: verde agua (el de siempre), crema, celeste, rosa y **noche (oscuro)**.
- Al tocar uno, el color cambia **al instante** y se guarda en la cuenta en segundo plano. Sin JavaScript, el formulario va a `app/color.php` y vuelve a la misma página.
- Se guarda en **`usuario.color`**. Los valores válidos están en `UsuarioRepositorio::COLORES`; cualquier otro se rechaza.
- Al ingresar se carga en la sesión. Se aplica con `<html data-color="...">`.
- **Cómo está armado el modo oscuro:** hay tres variables nuevas para el texto que va directo sobre la puerta: `--color-sobre-puerta`, `--color-sobre-puerta-suave` y `--color-enlace`. En "noche" se aclaran. Las hojas (tarjetas, post-its, tablas y la nota del editor) las vuelven a tinta, así que el papel se ve igual en todos los colores. También se oscurecen la manija y la niebla del editor, se suavizan las rayitas de la puerta y el rótulo del logo se invierte.
- `app/color.php` solo vuelve a direcciones del mismo sitio (que empiecen con una sola `/`), para que nadie pueda usarlo para redirigir a otra página.

### 10 · Selector de ingredientes en Mi cocina

- Sección nueva **"¿Qué querés usar?"** entre "Usalo pronto" y "Podés cocinar". Muestra los stickers de **lo que tenés disponible**: sin agotados ni vencidos, y sin repetir aunque esté en dos ubicaciones.
- **Elegir un sticker:** se despega un poco y aparece un globo rojo con la tilde. Las recetas aparecen **al instante**: `public/js/cocina.js` pide la misma página y reemplaza solo el resultado.
- Por defecto busca recetas que usen **todos** los elegidos; se puede cambiar a **alguno**. Si no hay ninguna con todos, lo sugiere.
- **Sin JavaScript:** botón "Ver recetas" y `app/index.php?ing[]=15&ing[]=1&modo=todos`.
- Solo se aceptan ingredientes que el usuario tiene. Cada uno va como parámetro de la consulta preparada (`:ing0`, `:ing1`…), nunca pegado en el SQL.
- Funciones nuevas: `DespensaRepositorio::disponibles()` y los filtros `ingredientes` y `modo` en `RecetaRepositorio::buscar()`.

### 11 · Texto sobre los renglones

- El texto de las hojas usa **una altura de línea igual a un renglón** (`--renglon: 32px`). Cada bloque mide renglones enteros, así cada línea queda apoyada sobre su raya.
  - Título: 1 renglón.
  - Campo de formulario: rótulo + caja + aire = 3 renglones.
  - Mensaje de error: 1 renglón.
- Las rayas se corren con `--renglon-desfase: -6px`, para que caigan 2 o 3 px debajo de la letra. Se midió en el navegador con la letra Figtree.
- Aplica a todas las tarjetas: recetas, detalle, formularios de ingreso y registro, contacto y administración.

---

## Archivos

| Archivo | Cambio |
|---|---|
| `database/schema.sql` | `usuario.color` |
| `database/seed.sql` | Cantidades de ejemplo en la despensa de María |
| `database/migraciones/001-tanda-heladera.sql` | **Nuevo**: agrega `usuario.color` a una base ya creada |
| `database/README.md` | Sección "Migraciones" |
| `docs/decisiones.md` | **DEC-003**: color guardado en la cuenta |
| `docs/modelo-datos.md` | Columna `color` |
| `docs/estilo-heladera.md` | Enlace a este registro |
| `docs/estilo-heladera-tanda-1.md` | **Nuevo**: copia de este registro dentro del repo |
| `public/css/estilos.css` | Relieve, renglones, colores, nota despegada, clip, tarjeta clickeable, dibujos en la tarjeta y selector (de 404 a 611 líneas) |
| `public/img/clip.svg` | **Nuevo** |
| `public/img/ingredientes/*.svg` | **30 nuevos** |
| `public/js/sonidos.js` | **Nuevo** |
| `public/js/cocina.js` | **Nuevo**: selector de Mi cocina |
| `public/js/despensa.js` | Nota despegada, editar sin recargar, unidad y nivel automático |
| `public/js/sitio.js` | Cambio de color al instante |
| `public/app/color.php` | **Nuevo**: guarda el color |
| `public/app/despensa.php` | Botón "+ Agregar", campo de cantidad y datos de cada fila para editar |
| `public/app/index.php` | Selector de ingredientes |
| `src/Vistas/encabezado.php` | `data-color`, imanes de color, imán de sonido y `sonidos.js` |
| `src/Vistas/tarjeta-receta.php` | Dibujos de los ingredientes; la tarjeta entera es el enlace |
| `src/ayudas.php` | `iconoIngrediente()` busca primero el nombre completo |
| `src/Sesion.php` | `color` en la sesión, `color()` y `cambiarColor()` |
| `src/Repositorios/UsuarioRepositorio.php` | `COLORES` y `guardarColor()` |
| `src/Repositorios/DespensaRepositorio.php` | Cantidad y unidad, `disponibles()` y `textoCantidad()` |
| `src/Repositorios/RecetaRepositorio.php` | Columna `ingredientes`, filtros `ingredientes` y `modo`, e `ingredientesDeTarjeta()` |
| `tests/Integracion/PaginasTest.php` | +4 pruebas: color, cantidad, selector y tarjetas con lo que falta |
| `tests/Dominio/FormatoDespensaTest.php` | **Nuevo**: 2 pruebas de formato |

## Pruebas nuevas

1. `testElColorDeLaHeladeraSeGuardaEnLaCuenta`: guarda "noche", vuelve a la página de origen y rechaza un color inventado y una dirección de otro sitio.
2. `testLaDespensaGuardaLaCantidadExactaConSuUnidad`: 3 limones → "3 unidades" y nivel Normal; cantidad 0 → Agotado.
3. `testElSelectorDeMiCocinaBuscaRecetasConLosIngredientesElegidos`: huevo + papa con "todos" trae la tortilla y no los panqueques; con "alguno" trae los panqueques; un ingrediente que no está en la despensa se ignora.
4. `testLasTarjetasDeRecetasMuestranLoQueFaltaTachado`: la tarta de zapallitos muestra "Tapa de tarta (te falta)".
5. `testTextoDeLaCantidad` y `testIngredientesDeLaTarjeta`: funciones de formato, sin base.

---

## Si se pasa al repositorio del equipo

Conviene hacerlo **cuando las páginas del incremento 1 ya estén en `main`**, y avisar en el grupo porque cambia la base:

1. **P3:** `schema.sql`, `seed.sql`, la migración, `Sesion.php`, `UsuarioRepositorio`, `RecetaRepositorio`, `tarjeta-receta.php`, `color.php`, `DEC-003` y las pruebas.
2. **P2:** `estilos.css`, las imágenes, `sonidos.js`, `cocina.js`, `despensa.js`, `sitio.js`, `encabezado.php`, `despensa.php`, `app/index.php` y `DespensaRepositorio`.

Los dos PR se tocan en `encabezado.php` y en `estilos.css`: conviene unir primero el de P3.

## Pendiente o para decidir

- **Nombre del proyecto:** quedó igual, a pedido.
- En la heladera oscura, los formularios de administración y el contacto se ven bien porque están sobre papel. Si se suman pantallas con texto directo sobre la puerta, hay que usar `--color-sobre-puerta`, no `--color-texto`.
- Las páginas públicas (inicio, ingreso, registro, contacto) siempre usan el verde: el color es de la cuenta y ahí todavía no hay nadie ingresado.
