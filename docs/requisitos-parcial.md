# Requisitos del 2.º parcial · dónde se cumple cada uno

Materia: Programación 4 · Proyecto integrador. Responsable de mantener esta tabla al día: P4.
Probar cada punto con los usuarios de prueba del README y marcar ✅ cuando esté verificado.

| # | Requisito | Dónde se cumple | Cómo mostrarlo | ✅ |
|---|---|---|---|---|
| 1 | Temática libre, articulada con otras materias | Alacena es el proyecto final de la tecnicatura (Gestión de Desarrollo de Software) | README | |
| 2 | Base MySQL con al menos 3 tablas: contactos, usuarios y contenido, relacionadas | `contacto`, `usuario`, `receta` (+ ingredientes, pasos, despensa). Relaciones: contacto→usuario, receta→usuario, despensa→usuario e ingrediente | `database/schema.sql`, diagrama en `docs/modelo-datos.md`, phpMyAdmin → Diseñador | |
| 3 | Dos niveles de usuario; admins y generales cargados; contraseñas encriptadas | Columna `usuario.rol` (`admin`/`general`). `seed.sql` carga 2 administradores y 3 generales. `password_hash()` al crear y `password_verify()` al ingresar | phpMyAdmin → tabla usuario (se ven los hashes `$2y$...`) | |
| 4 | Página principal con nombre, menú, descripción, registro de generales y acceso con login | `public/index.php` (menú en `src/Vistas/encabezado.php`), `registro.php`, `login.php` | Abrir la raíz del sitio | |
| 5 | Contacto con mapa de Google y formulario que usa `mail()` y graba en la tabla | `public/contacto.php`, `src/Correo.php` (`mail()`), `ContactoRepositorio` | Enviar una consulta → verla en Mailpit (http://localhost:8025) y en Administración → Consultas | |
| 6 | Login verificado con sesión y sus variables; si es correcto, contenido según el rol; si no, redirige a una página con mensaje de error | `login.php` guarda `$_SESSION['usuario']` (con el rol) mediante `Sesion::ingresar()`. General → `app/index.php`; admin → `admin/index.php`. Error → `error.php?motivo=login` | Ingresar con contraseña incorrecta, con María y con admin | |
| 7 | Usuario general: página interna con contenido de la base (sobre el que se hace el ABM) | `app/recetas.php` (recetas desde la base), `app/receta.php`, `app/index.php`. Además ABM de su propia despensa en `app/despensa.php` | Ingresar como María | |
| 8 | Administrador: página adicional con el contenido y ABM (se valoran más ABM) | `admin/index.php` + ABM de recetas (`admin/recetas.php`, `admin/receta-editar.php`), ingredientes, usuarios y consultas | Ingresar como admin | |
| 9 | Al menos un buscador sobre la base | Recetas por título, ingrediente o sinónimo, con filtros (`app/recetas.php`); buscadores en el ABM de recetas, ingredientes, usuarios y consultas | Buscar "pimiento" en Recetas: aparece Arroz con pollo | |

## Tecnologías evaluadas

| Tecnología | Dónde se ve |
|---|---|
| HTML | Páginas semánticas (`header`, `nav`, `main`, `section`, `article`), formularios con `label`, validación nativa (`required`, `type="email"`, `minlength`) |
| CSS | `public/css/estilos.css`: variables, grid, flexbox, diseño adaptable a celular |
| JavaScript | `js/sitio.js` (menú en celular, confirmar borrados), `js/validacion.js` (registro), `js/despensa.js` (filtro en vivo), `js/receta-form.js` (filas dinámicas de ingredientes), `js/contacto.js` (contador) |
| PHP | Sesiones, `password_hash`, `mail()`, PDO con consultas preparadas, validación del lado del servidor |
| MySQL | 17 tablas con claves foráneas, `JOIN`, subconsultas, `GROUP BY`, transacciones al guardar recetas |

## Seguridad (para la defensa)

- **Inyección SQL:** todas las consultas son preparadas con PDO; nunca se concatena lo que escribe el usuario.
- **XSS:** todo dato se imprime con `e()` (`htmlspecialchars`).
- **CSRF:** cada formulario lleva un token de sesión que se verifica al procesarlo.
- **Contraseñas:** `password_hash()` / `password_verify()`; mismo mensaje de error si falla el email o la contraseña.
- **Sesión:** `session_regenerate_id()` al ingresar; cookie `HttpOnly` y `SameSite=Lax`.
- **Acceso:** `Sesion::requiereLogin()` y `Sesion::requiereAdmin()` al principio de cada página privada; cada usuario solo ve y modifica su propia despensa.
