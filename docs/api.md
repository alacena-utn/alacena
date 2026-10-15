# Contrato de la API v1

Todas las rutas empiezan con `api/` y responden JSON (`Content-Type: application/json; charset=utf-8`).
Los errores tienen siempre la forma `{ "error": "mensaje para mostrar" }`.
La sesión viaja en la cookie de sesión de PHP: el frontend no maneja tokens.
En el incremento 1 el registro, el ingreso y los ABM son páginas PHP con formularios (ver DEC-002).
La API se amplía en el incremento 2, para el modo cocina y la futura app móvil.

| Método | Ruta | Requiere sesión | Qué hace | Estado |
|---|---|:-:|---|---|
| GET | `/api/salud` | No | Estado de la API y la base | ✅ hecho (kit) |
| GET | `/api/ingredientes?q=texto` | No | Busca por nombre o sinónimo, ignora tildes | ✅ hecho (kit) |
| POST | `/api/usuarios` | No | Registro | Incremento 2 |
| POST | `/api/sesion` | No | Ingreso | Incremento 2 |
| GET | `/api/yo` | Sí | Usuario logueado | Incremento 2 |
| DELETE | `/api/sesion` | Sí | Salir | Incremento 2 |

---

## GET /api/ingredientes

Busca ingredientes por nombre canónico o sinónimo.

**Parámetros:** `q` (texto, mínimo 2 caracteres).

**Respuesta 200**
```json
{ "resultados": [ { "id": 3, "nombre": "Morrón", "categoria": "Verduras" } ] }
```

**Errores:** `400` si `q` tiene menos de 2 caracteres.
