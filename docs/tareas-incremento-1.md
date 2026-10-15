# División de tareas · Incremento 1 (2.º parcial de Programación 4)

Quién hace qué en este incremento. Los nombres y usuarios de cada rol están en la tabla **Equipo** del [README](../README.md).
Cada tarea es un issue en el tablero del proyecto; su PR lo cierra con `Cierra #N`.
Qué requisito del parcial cumple cada página: [requisitos-parcial.md](requisitos-parcial.md).

## Resumen por integrante

| Rol | Módulo | Tareas | Archivos principales |
|---|---|---|---|
| P1 | Cuentas y panel de administración | P1-A Registro · P1-B Ingreso, salida y error · P1-C Panel | `registro.php`, `login.php`, `salir.php`, `error.php`, `admin/index.php`, `UsuarioRepositorio`, `ResumenRepositorio`, `js/validacion.js` |
| P2 | Despensa y diseño | P2-A Identidad visual · P2-B Página principal · P2-C Mi despensa · P2-D Filtro y vista de celular · P2-E Mi cocina | `css/estilos.css` (parte común), `index.php`, `app/despensa.php`, `app/index.php`, `DespensaRepositorio`, `js/despensa.js` |
| P3 | Recetas y base de datos (coordina) | P3-A Repositorio y base · P3-B Catálogo con buscador · P3-C Detalle · P3-D ABM de recetas · P3-E Pre-entrega | `database/`, `app/recetas.php`, `app/receta.php`, `admin/recetas.php`, `admin/receta-editar.php`, `RecetaRepositorio`, `js/receta-form.js` |
| P4 | Contacto y calidad | P4-A Contacto · P4-B Consultas · P4-C Pruebas automáticas · P4-D Lista de requisitos y W3C | `contacto.php`, `admin/consultas.php`, `Correo.php`, `ContactoRepositorio`, `js/contacto.js`, `tests/Integracion/PaginasTest.php` |
| P5 | Administración: ABM de usuarios e ingredientes | P5-A ABM de usuarios · P5-B ABM de ingredientes | `admin/usuarios.php`, `admin/ingredientes.php`, métodos del ABM en `IngredienteRepositorio` |
| Todos | — | C-A Clonar el repositorio y levantar el proyecto | Fila propia en la tabla Equipo del README |

## Dependencias

- **P1-B (ingreso)** va primero: todas las páginas privadas lo necesitan para probarse. P5-A usa el `UsuarioRepositorio` que se crea en esa tarea.
- **P2-E (Mi cocina)** usa `RecetaRepositorio::sugeridas()` y la tarjeta de receta de **P3-B**.
- **P4-C (pruebas automáticas)** comprueba páginas de todos, entre ellas el ABM de ingredientes de **P5-B**: va al final.

## Archivos compartidos

`src/Sesion.php`, `src/ayudas.php`, `src/Vistas/encabezado.php` y `pie.php`, `database/schema.sql` y `seed.sql` son de todos: no se cambian sin avisar. En `public/css/estilos.css` cada uno escribe debajo del comentario de su módulo.
