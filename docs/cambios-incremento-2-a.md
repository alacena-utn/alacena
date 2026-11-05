# Incremento 2 · Entrega A: qué cambió y de quién es cada parte

> **Base:** Heladera 4.1 (lo que queda en `main` cuando se unen los 7 PR de integración).
> **Pruebas:** **77 en verde** (eran 46). El lint queda limpio.
> **Recorrido:** se recorrieron las páginas a 360, 768 y 1280 px sin desbordes ni errores de JavaScript, también con el color "noche".

## 1. Qué trae

| Bloque del plan | Qué se hizo | Rol |
|---|---|---|
| 2.1 Recomendación | Puntaje del informe: pesos, lo que vence, principales que faltan, cocinada hace poco. Grupos "Podés hacerla ahora", "Te falta 1" y "Casi" en Mi cocina. "Ordenar: recomendadas" en Recetas | P3 |
| 2.1 Peso | Selector "Importancia" (principal, secundario o accesorio) por ingrediente en el ABM de recetas | P3 |
| 2.1 Sustituciones | 20 sustituciones cargadas. La tarjeta dice "Sin crema: podés usar leche" y el detalle muestra "⇄ Podés usar…" | P3 |
| 2.1 Porciones | Botones − / + en el detalle (1 a 12), cantidades con redondeo de cocina y pasos con `{ing:...}` | P3 |
| 2.3 Datos | Formato JSON, importador y validador, 50 recetas originales (62 en total), etiqueta "dulce", 31 ingredientes nuevos con otros nombres | P3 |
| 2.3 Dibujos | 31 dibujos nuevos con el mismo estilo (`public/img/ingredientes/`) | P2 |
| 2.3 Otros nombres | Campo "Otros nombres" en el ABM de ingredientes | P3 |
| 2.2 (adelanto) | El ABM de recetas guarda el temporizador de cada paso (`[⏱ 15 min]`) y la nota de cada ingrediente | P3 |
| 2.4 Cuentas | "Mi cuenta": cambiar nombre y email, cambiar la contraseña pidiendo la actual, borrar la cuenta | P1 |
| 2.8 Calidad | Pruebas nuevas: `ImportadorRecetasTest`, `PasosTest`, `PorcionesTest`, `RecetarioTest`, `RecomendacionTest`, `PerfilTest` y otros nombres en el ABM de ingredientes | P4 revisa; cada prueba va con su parte |

## 2. Archivos por rol (para armar los parches)

### P3 · Recomendación, porciones y recetario

| Tipo | Archivos |
|---|---|
| **Nuevos** | `src/Dominio/Porciones.php`, `src/Dominio/Pasos.php`, `src/Dominio/ImportadorRecetas.php`, `scripts/importar-recetas.php`, `database/recetas/*.json` (50), `database/recetas.sql`, `database/migraciones/005-incremento-2-a.sql`, `docs/guia-recetas.md`, `tests/Dominio/PorcionesTest.php`, `tests/Dominio/PasosTest.php`, `tests/Dominio/ImportadorRecetasTest.php`, `tests/Integracion/RecetarioTest.php`, `tests/Integracion/RecomendacionTest.php` |
| **Cambiados** | `src/Dominio/Puntaje.php`, `src/Repositorios/RecetaRepositorio.php`, `public/app/index.php`, `public/app/recetas.php`, `public/app/receta.php`, `src/Vistas/tarjeta-receta.php`, `public/admin/receta-editar.php`, `public/js/receta-form.js`, `tests/Dominio/PuntajeTest.php` |
| **Cambiados (solo los otros nombres)** | `src/Repositorios/IngredienteRepositorio.php`, `public/admin/ingredientes.php`, la parte de otros nombres de `tests/Integracion/PaginasTest.php` |
| **Base y documentación** | `database/seed.sql` (ingredientes 77 a 107, otros nombres, etiqueta "dulce", sustituciones), `database/README.md`, `docker-compose.yml` (carga `recetas.sql`), `.github/workflows/ci.yml` (también), `docs/decisiones.md` (DEC-008 y DEC-009), `docs/modelo-datos.md` |
| **CSS** | La sección "Incremento 2 · Recomendación (P3)" de `public/css/estilos.css`, más la columna nueva de la fila de ingredientes del editor (con el corte a 900 px) |

### P2 · Dibujos

- **Nuevos:** los 31 `public/img/ingredientes/*.svg`: aceitunas, acelga, albahaca, apio, avena, cacao, carne-para-guiso, cerdo, coco-rallado, comino, dulce-de-leche, dulce-de-membrillo, esencia-de-vainilla, galletitas, garbanzo, jengibre, laurel, levadura, maicena, mostaza, nueces, nuez-moscada, oregano, panceta, pasas-de-uva, polvo-de-hornear, puerro, queso-crema, repollo, salsa-de-soja y vinagre.
- Van junto con el recetario de P3. Sin ellos, los ingredientes nuevos aparecerían con el frasco genérico y fallaría la prueba de "cada ingrediente tiene su dibujo".

### P1 · Mi cuenta

- **Nuevos:** `public/app/perfil.php`, `tests/Integracion/PerfilTest.php`.
- **Cambiados:**
  - `src/Repositorios/UsuarioRepositorio.php`: `actualizarDatos`, `cambiarContrasena` y `contrasenaCorrecta`.
  - `src/Sesion.php`: `actualizarDatos`.
  - `src/Vistas/encabezado.php`: el enlace "Mi cuenta" en el menú de la cuenta.
  - La sección "Incremento 2 · Mi cuenta (P1)" de `estilos.css`.
  - DEC-010.

## 3. Cómo se une

La entrega A va en **un solo PR**, que se revisa por partes, en este orden:
1. **P2:** dibujos.
2. **P3:** recomendación, porciones y recetario (lo más grande).
3. **P1:** Mi cuenta.

Los ajustes de diseño y navegación (DEC-017) van en el mismo PR.

## 4. Para actualizar la base después de unir

- **Con Docker:** `docker compose down -v` y `docker compose up -d`. Carga schema, seed y recetas.
- **Sin borrar datos:**
  ```bash
  mysql -u cooki -p cooki < database/migraciones/005-incremento-2-a.sql
  mysql -u cooki -p cooki < database/recetas.sql
  ```

## 5. Notas

- **El puntaje ya respeta "Siempre tengo" (`usuario_basico`) y las alergias (`restriccion_usuario`)**, pero las pantallas para cargarlos llegan en la entrega B. Mientras tanto, esas tablas están vacías y no cambian nada.
- **El temporizador de cada paso ya se guarda**, pero todavía no se ve en el detalle: se usa en el modo cocina (entrega B).
- **Se arregló un choque de variables:** el encabezado usa `$usuario` y `$nombre`, y si una página usa los mismos nombres antes de incluirlo, el encabezado los pisa. En `perfil.php` se usan nombres distintos (`$cuenta`, `$datoNombre`).
