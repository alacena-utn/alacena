# Alacena

Aplicación web que te dice qué cocinar con lo que tenés en la despensa y te avisa qué está por vencer.
Proyecto integrador · Tecnicatura Universitaria en Programación · UTN Facultad Regional Haedo.

**Stack:** HTML + CSS + JavaScript (sin frameworks) · PHP 8 · MariaDB / MySQL

- 📋 Tablero: *(link al GitHub Project)*
- 🎯 Versión actual: **incremento 1** (2.º parcial de Programación 4). Qué cumple cada requisito: [docs/requisitos-parcial.md](docs/requisitos-parcial.md)

## Usuarios de prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@alacena.test` | `Admin1234` |
| Administrador | `docente@alacena.test` | `Admin1234` |
| General | `maria@alacena.test` (con despensa cargada) | `Usuario1234` |
| General | `juan@alacena.test` · `sofia@alacena.test` (despensa vacía) | `Usuario1234` |

Las contraseñas se guardan encriptadas con `password_hash()`; las de arriba son solo de prueba.

## Estructura

```
public/                 ← lo único que ve el navegador (raíz web)
  index.php             página principal (pública)
  registro.php · login.php · salir.php · error.php · contacto.php
  app/                  páginas del usuario logueado: Mi cocina, Mi despensa, Recetas
  admin/                páginas solo para administradores: panel y ABM
  css/ · js/ · img/
  api/index.php         API JSON (buscador de ingredientes)
src/                    código PHP que no se ve desde el navegador
  bootstrap.php         arranque: cada página empieza con require de este archivo
  ayudas.php            e() para escapar HTML, url(), redirigir(), parcial()...
  Sesion.php            sesión del usuario, roles, protección de páginas y token CSRF
  Correo.php            envío con mail()
  Repositorios/         todas las consultas SQL (una clase por tabla principal)
  Vistas/               partes comunes: encabezado (con el menú) y pie
database/               schema.sql (estructura) y seed.sql (datos de ejemplo)
tests/                  pruebas con PHPUnit
docs/                   decisiones, modelo de datos, requisitos del parcial
docker/ + docker-compose.yml   PHP + MariaDB + phpMyAdmin + Mailpit
```

## Cómo levantarlo

```bash
git clone https://github.com/alacena-utn/alacena.git
cd alacena
cp .env.example .env
```

**Con Docker (recomendado)**
```bash
docker compose up -d --build
docker compose exec web composer install
```
| Qué | Dónde |
|---|---|
| Aplicación | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| Correos enviados (Mailpit) | http://localhost:8025 |

**Con XAMPP o Laragon**
1. Copiá la carpeta del proyecto dentro de `htdocs`.
2. En phpMyAdmin creá la base `alacena` (cotejamiento `utf8mb4_unicode_ci`) e importá `database/schema.sql` y después `database/seed.sql`.
3. En `.env`: `DB_HOST=127.0.0.1`, `DB_USER=root`, `DB_PASS=` (vacío). Sin `.env` también funciona: esos son los valores por defecto.
4. Abrí `http://localhost/alacena/public/`.

> Con XAMPP la función `mail()` no tiene servidor de correo: la consulta de contacto se guarda igual en la base y la página lo indica en el panel de consultas.

## Comandos

| Comando | Qué hace | Con Docker |
|---|---|---|
| `composer lint` | Sintaxis de todos los .php + estilo PSR-12 | `docker compose exec web composer lint` |
| `composer test` | Pruebas (unitarias y de páginas) | `docker compose exec web composer test` |
| `composer servidor` | Servidor PHP en http://localhost:8080 (sin Docker) | — |
| `docker compose down -v` | Apagar y **borrar** la base local (se recrea al levantar) | |

## Equipo

| | Módulo | Nombre | GitHub |
|---|---|---|---|
| P1 | Cuentas + Infraestructura | Daiana | Daiana1411 |
| P2 | Despensa + Diseño | Julian | julianmartinez093-tech |
| P3 | Recetas + Base de datos (coordinación) | Pablo | pablito2032 |
| P4 | Contacto + Calidad | | |
| P5 | Administración: ABM de usuarios e ingredientes | | |

División de tareas: [docs/tareas-incremento-1.md](docs/tareas-incremento-1.md) · Cómo trabajamos: [CONTRIBUTING.md](CONTRIBUTING.md) · Decisiones: [docs/decisiones.md](docs/decisiones.md) · Modelo de datos: [docs/modelo-datos.md](docs/modelo-datos.md)

## Problemas comunes

| Qué pasa | Solución |
|---|---|
| Error de conexión a la base con Docker | Esperá 30 s la primera vez (MariaDB se inicializa) y recargá. Revisá que `.env` diga `DB_HOST=db` |
| Error de conexión con XAMPP | Iniciá MySQL en el panel de XAMPP. En `.env`: `DB_HOST=127.0.0.1`, `DB_USER=root`, `DB_PASS=` |
| Cambió `schema.sql` o `seed.sql` y no se ve | La base solo se crea la primera vez: `docker compose down -v` y `docker compose up -d` |
| No llegan los correos a Mailpit | Reconstruí la imagen: `docker compose up -d --build` |
| El puerto 8080, 8081, 8025 o 3306 está ocupado | Cerrá XAMPP si usás Docker (o al revés) |
