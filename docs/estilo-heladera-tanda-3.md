# Estilo Heladera · Tanda 3 de cambios

> **Fecha:** 28/10/2026
> **Sobre:** `alacena-estilo-heladera-tanda-2-1.zip`. Se usaron como referencia el diseño y los dibujos del repositorio del equipo (`main` al 27/10, commit `08c2ea5`).
> **Resultado:** `alacena-estilo-heladera-tanda-3.zip`
> **Verificación:**
> - `composer lint` sin errores ni advertencias.
> - **37 pruebas en verde** (antes eran 34; se sumaron 3 y se actualizaron 2).
> - Recorrido en el navegador a 360, 768, 1024, 1280 y 1600 px por inicio, ingreso, contacto, Mi cocina, despensa, recetas, detalle y administración: sin desbordes horizontales ni errores en la consola. También se revisó el color "noche".
> - **Probado en el navegador:**
>   - la manija, con el mouse (arrastrar y tocar) y en el celular (mantener apretado y arrastrar);
>   - la barra de volumen (se guarda y sigue al recargar);
>   - la vista previa del dibujo al editar un ingrediente;
>   - el dibujo de cada fila al editar una receta.

---

## Resumen

| # | Pedido | Estado | Incremento | Rol |
|---|---|---|---|---|
| A | Inicio como el del repo: pregunta en vez del nombre, frase "Alacena es la puerta de tu heladera:", "→ y usá primero lo que vence" | Hecho | 1 | P2 |
| B | Título "¿Qué puedo cocinar?" con un dibujo que baila siempre, se acerca al pasar el mouse y cambia al tocarlo | Hecho | 1 | P2 |
| C | Vitrina: todos los dibujos moviéndose dentro del recuadro (sin cinta a la vista, sin nombres) y cada uno se mueve al pasar el mouse | Hecho | 1 | P2 |
| D | "Cómo funciona" con papeles de colores que se mueven como las demás notas | Hecho | 1 | P2 |
| E | Sacar "la puerta de tu heladera" del encabezado | Hecho (está debajo de la pregunta) | 1 | P2 |
| F | Manija más grande y que sirva para desplazarse (mouse; en el celular, manteniendo apretado) | Hecho | 1 | P2 |
| 1 | Dibujo de los fideos al lado del nombre y como ícono de la pestaña | Hecho | 1 | P2 |
| 2 | Simetría de todas las páginas y cómo se acomodan al cambiar de tamaño | Hecho (detalle abajo) | 1 | P2 |
| 3 | Barra de volumen (arranca en 50 y se guarda) | Hecho: en la cuenta, o en el navegador si no hay nadie ingresado | 1 | P2 + P3 (base) |
| 4 | Administración mejorada: dibujos al editar y tarjetas con movimiento | Hecho | 1 | P1 + P3 + P5 |
| 5 | Reemplazar 18 dibujos por los del repo; "Pollo (pechuga)" → "Pollo" | Hecho | 1 | P2 + P3 |
| 6 | Más dibujos (frutas y demás) | Hecho: **32 ingredientes nuevos** con su dibujo (76 en total) | Adelanta "Datos" del 2 | P2 + P3 |

**Cambia la base de datos:**
- columna `usuario.volumen`;
- "Pollo (pechuga)" pasa a llamarse "Pollo";
- 32 ingredientes nuevos con sus sinónimos (DEC-005).

Para actualizarla tenés dos opciones:
- **Con Docker:** `docker compose down -v` y después `docker compose up -d`.
- **Sin perder datos:** correr, en orden, `database/migraciones/001`, `002` y `003`.

---

## Detalle

### A–E · Página de inicio (`public/index.php`, `public/js/inicio.js`)

- **Título:** "**¿Qué puedo cocinar?**", en letra a mano roja. El dibujo de al lado (arranca con los fideos):
  - **baila siempre** (`@keyframes bailar`);
  - **se agranda** al pasar el mouse;
  - **al tocarlo cambia por otro dibujo al azar**, con un saltito y el sonido "pop".
- **Debajo:** "**Alacena es la puerta de tu heladera:** anotá lo que tenés…", los botones y "→ y usá primero lo que vence".
- **Vitrina:**
  - **todos los dibujos** (76) se desplazan solos de derecha a izquierda, en tres filas, dentro del recuadro punteado;
  - no hay una cinta dibujada: los bordes se esfuman para que parezca que entran y salen del recuadro;
  - **al pasar el mouse por un dibujo, ese se agranda y se menea**, y la vitrina se frena mientras tanto para que no se escape;
  - igual que en la versión anterior, sin nombres ni fechas; solo algunos llevan el "¡ya!" (morrón, tomate, leche, frutilla);
  - la lista va dos veces seguida para que la vuelta no se note; da una vuelta completa en 90 segundos.
- **Cómo funciona:** tres **papeles de colores** (amarillo, rosa y celeste) con chinche, que se levantan y se hamacan al pasar el mouse, como los demás post-its.
- **¿Por qué Alacena?:** igual que en el repo, como hoja rayada angosta y centrada.
- **"la puerta de tu heladera"** ya no aparece al lado del logo en ninguna página.
- **Puerta punteada:** como en el repo, en la computadora (desde 900 px) el contenido de todas las páginas va dentro de un recuadro punteado redondeado. En el celular no aparece, para no perder espacio.

### F · La manija como barra de desplazamiento (`public/js/manija.js`)

- La manija pasó de **16 × 44 % de la pantalla** a **30 × 68 %**. Adentro tiene un **agarre** que muestra dónde estás y qué parte de la página se ve.
- **Con el mouse:** tocás cualquier parte y la página va ahí; arrastrando, la seguís.
- **En el celular:** se **mantiene apretada** un momento (300 ms; vibra si el teléfono lo permite) y después se arrastra. Si el dedo se mueve antes, no hace nada, así no se agarra sin querer al desplazarse.
- **Tamaño según la pantalla:**
  - en pantallas medianas (hasta 1300 px) es más angosta (18 px) y el contenido le deja lugar a la derecha;
  - en el celular mide 12 px y queda pegada al borde;
  - si la página no se puede desplazar, la manija queda quieta, sin agarre.
- La barra común del navegador sigue estando: la manija se agrega, no la reemplaza.

### 1 · El dibujo de los fideos como marca

- En todas las páginas, el **sticker de los fideos** va al lado del rótulo "ALACENA" (se mueve un poco al pasar el mouse) y es el **ícono de la pestaña** (`<link rel="icon">`).

### 2 · Simetría y tamaños

- **Encabezado:** todo se alinea con el **centro de los imanes** del menú, no con su texto (el logo, el nombre de la cuenta y los botones Ingresar y Crear cuenta). Antes se centraban con el bloque imán + texto y quedaban más abajo.
- **Buscador de recetas:** la casilla "Solo las que puedo hacer ya" y el botón Buscar quedan a la altura de los campos; antes estaban corridos y con mucho espacio arriba.
- **Editar receta:** el dibujo, "Opcional" y "Quitar" de cada fila quedan centrados con los campos, no con sus rótulos.
- **Editar ingrediente:** la vista previa queda al lado del campo del nombre sin pisar el renglón de abajo.
- **Heladera oscura:** los botones negros que van directo sobre la puerta (por ejemplo, "Ir a mi cocina") pasan a ser claros.
- Se recorrieron todas las páginas a 360, 768, 1024, 1280 y 1600 px: no hay desbordes horizontales en ninguna.

### 3 · Volumen

- **Con cuenta:** debajo de "Sonidos prendidos", en el menú de la cuenta, hay una barra de **0 a 100**.
  - Arranca en **50**, que es el volumen de antes.
  - Cambia al instante mientras la movés y se guarda al soltarla.
  - Se guarda en **`usuario.volumen`** (vía `app/preferencias.php`), así te sigue en otra computadora.
- **Sin cuenta:** el parlante de la barra despliega una hoja con el botón para prender o apagar y la barra; el volumen se guarda en ese navegador.
- La ganancia real es volumen ÷ 100 × 1,1 (50 → 0,55, como antes). En 0 no suena nada.

### 4 · Administración

- **Panel:** las cuatro secciones son **notas de colores** (Recetas, Ingredientes, Consultas y Usuarios), cada una con su dibujo (fideos, zanahoria, mate y manzana).
  - Toda la nota es el enlace.
  - Se levantan y se hamacan al pasar el mouse, y el dibujo gira.
- **Ingredientes:** el dibujo al lado de cada nombre en la lista.
- **Editar ingrediente:** **vista previa del dibujo** que cambia mientras escribís el nombre (`admin-ingrediente.js`, con la misma regla que `iconoIngrediente()`). Si no hay dibujo, muestra el frasco y avisa "Sin dibujo: frasco".
- **Recetas:** en la lista, la columna Ingredientes muestra **hasta 5 dibujos** + "+N" (el nombre completo aparece al pasar el mouse).
- **Editar receta:** cada fila de ingrediente tiene **su dibujo**, que cambia (con un saltito y el "pop") al elegir otro ingrediente.
- **Movimiento:** las hojas con tablas se despegan un poco al pasar el mouse, sin girar porque se está leyendo. Los formularios no se mueven, como dice la constitución.

### 5 y 6 · Dibujos

- **Del repo**, se reemplazaron 18 dibujos: aceite, arveja, atún en lata, batata, carne picada, choclo, crema de leche, jamón cocido, manteca, pan rallado, pimentón, pimienta, poroto, puré de tomate, queso cremoso, queso rallado (en el repo se llama `queso.svg`), tapa de tarta y zapallito.
  - Ahora los archivos llevan el nombre completo (`carne-picada.svg`, `atun-en-lata.svg`…).
  - Se borraron los nuestros que quedaban sin uso.
- **"Pollo (pechuga)" → "Pollo"**, con "pechuga" como sinónimo. El dibujo es el mismo.
- **32 ingredientes nuevos, cada uno con su dibujo** (31 dibujos nuevos; "Pan" usa el pan que ya existía):
  - **Frutas:** manzana, naranja, pera, durazno, uva, ananá, kiwi, sandía, mandarina, cereza.
  - **Verduras:** berenjena, brócoli, espinaca, pepino, champiñón, remolacha, coliflor, rabanito.
  - **Lácteos:** yogur, ricota, mozzarella.
  - **Carnes y pescado:** salchicha, chorizo, merluza, langostinos.
  - **Almacén:** pan, tapas de empanada, mayonesa, miel, chocolate, yerba (un mate), café.
- **Sinónimos nuevos:** piña, melocotón, tangerina, hongo, betabel, rábano, yogurt, requesón, muzzarella, pescado, camarones, discos de empanada y pechuga.
- En total hay **76 ingredientes con 76 dibujos distintos**, más el frasco genérico. La prueba `testCadaIngredienteTieneSuPropioDibujo` lo controla.
- La galería está en `dibujos-ingredientes.html`.

---

## Archivos

| Archivo | Cambio |
|---|---|
| `database/schema.sql`, `seed.sql` | `usuario.volumen`, "Pollo" y 32 ingredientes con sinónimos |
| `database/migraciones/003-tanda-heladera-3.sql` | **Nuevo** (probado sobre una base de la tanda 2) |
| `database/README.md`, `docs/decisiones.md` (**DEC-005**), `docs/modelo-datos.md` | Documentación |
| `docs/estilo-heladera-tanda-3.md` | **Nuevo**: copia de este registro |
| `public/index.php` + `public/js/inicio.js` | Inicio nuevo |
| `public/js/manija.js` | **Nuevo**: manija desplazadora |
| `public/js/admin-ingrediente.js` | **Nuevo**: vista previa del dibujo |
| `public/js/sonidos.js` | Volumen (cuenta o navegador) y parlante sin cuenta |
| `public/js/receta-form.js` | Dibujo por fila al editar recetas |
| `public/admin/index.php`, `ingredientes.php`, `recetas.php`, `receta-editar.php` | Notas del panel, dibujos y vista previa |
| `public/app/preferencias.php` | Acepta `volumen` |
| `public/css/estilos.css` | Manija, logo, puerta punteada, inicio, vitrina, notas de colores, volumen, administración y alineaciones (de 779 a 926 líneas) |
| `public/img/ingredientes/` | 18 reemplazados por los del repo, 31 nuevos, 7 viejos borrados (77 archivos) |
| `src/Vistas/encabezado.php` | Fideos + favicon, sin la frase, volumen y parlante sin cuenta |
| `src/Vistas/pie.php` | La manija |
| `src/Sesion.php`, `src/Repositorios/UsuarioRepositorio.php` | `volumen()`; `guardarPreferencia()` acepta el volumen (de 0 a 100) |
| `src/ayudas.php` | Ejemplo del comentario actualizado |
| `tests/…` | Ver abajo |

## Pruebas

| Prueba | Qué comprueba |
|---|---|
| `testPaginaPrincipal…` (actualizada) | "¿Qué puedo cocinar?", "Alacena es la puerta de tu heladera:" y el dibujo de los fideos |
| `testAdministradorVaAlPanel` (actualizada) | El panel enlaza a la gestión de recetas |
| `testElVolumenSeGuardaEnLaCuenta` | Arranca en 50, guarda 80 y rechaza 150 |
| `testElPanelDeAdministracionTieneNotasConDibujos` | Las 4 notas, el dibujo en la lista de ingredientes y la vista previa |
| `testHayFrutasVerdurasYOtrosIngredientesNuevos` | Hay 76 ingredientes, "Pollo" y el sinónimo "piña" → Ananá |

## Pendiente o para decidir

- **Problemas del repo del equipo** (quedaron para después de esta tanda):
  - `admin/usuarios.php` da error si no se corrió `composer install`;
  - las páginas de P5 cargan las tipografías desde Google (sin internet se ven con otra letra);
  - faltan las pruebas de páginas: el repo tiene 18 y el kit traía más.
- Pasar todo esto al repo del equipo: hace falta un plan por rol, porque el repo ya tiene su propia versión de varias páginas.
