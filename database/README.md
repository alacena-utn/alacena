# database/

| Archivo / carpeta | Qué es | Dueño |
|---|---|---|
| `schema.sql` | Crea todas las tablas. Se puede correr las veces que haga falta (borra y recrea) | P3 |
| `seed.sql` | Datos iniciales: categorías, ingredientes, sinónimos y una receta de ejemplo | P3 |
| `datos/ingredientes.csv` | Planilla de ingredientes exportada (P2-04), fuente del seed | P2 |
| `recetas/*.json` | Una receta por archivo, con el formato de `docs/guia-recetas.md` | todos |

## Cargar la base

**Con Docker:** se carga sola la primera vez que se crea el contenedor. Para empezar de cero:
```bash
docker compose down -v      # borra la base (¡se pierden los datos locales!)
docker compose up -d        # la recrea con schema.sql + seed.sql
```

**Sin Docker (XAMPP / Laragon) o en el hosting:** en phpMyAdmin, crear la base `alacena`
(intercalación `utf8mb4_unicode_ci`) → pestaña **Importar** → `schema.sql`, y después `seed.sql`.

**Por consola:**
```bash
mysql -u root -p alacena < database/schema.sql
mysql -u root -p alacena < database/seed.sql
```

## Reglas
- Todo cambio de tablas va en `schema.sql` **y** se avisa en el grupo: afecta a los cuatro.
- Nunca se sube un volcado con datos de usuarios reales.
- La intercalación `utf8mb4_unicode_ci` hace que las búsquedas ignoren tildes y mayúsculas ("morron" = "Morrón"). No cambiarla.
