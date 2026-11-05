# Contrato de la API v1

Todas las rutas empiezan con `api/` y responden JSON (`Content-Type: application/json; charset=utf-8`).
Los errores tienen siempre la forma `{ "error": "mensaje para mostrar" }`.
La sesión viaja en la cookie de sesión de PHP (la misma que usan las páginas): el frontend no maneja tokens.
Las peticiones que modifican datos (`POST`, `DELETE`…) tienen que mandar `Content-Type: application/json`:
un formulario de otro sitio no puede hacerlo sin permiso, así que eso también protege contra CSRF.

| Método | Ruta | Sesión | Qué hace | Desde |
|---|---|:-:|---|---|
| GET | `/api/salud` | No | Estado de la API y la base | Incremento 1 |
| GET | `/api/ingredientes?q=texto` | No | Busca por nombre o sinónimo, ignora tildes | Incremento 1 |
| POST | `/api/usuarios` | No | Registro (y queda logueado) | Incremento 2 |
| POST | `/api/sesion` | No | Ingreso (con bloqueo por intentos) | Incremento 2 |
| GET | `/api/yo` | Sí | Usuario logueado | Incremento 2 |
| DELETE | `/api/sesion` | No | Salir | Incremento 2 |
| GET | `/api/recetas?q=&pagina=&por_pagina=` | No | Catálogo paginado | Incremento 2 |
| GET | `/api/recetas/{id}?porciones=N` | No | Una receta para N porciones | Incremento 2 |
| GET | `/api/recomendaciones?limite=20` | Sí | Qué puede cocinar, por puntaje | Incremento 2 |
| GET | `/api/despensa` | Sí | La despensa del usuario | Incremento 2 |
| POST | `/api/favoritos/{id}` | Sí | Marca la receta como favorita | Incremento 2 |
| DELETE | `/api/favoritos/{id}` | Sí | La desmarca | Incremento 2 |

Códigos comunes:
- `401`: hace falta iniciar sesión.
- `404`: no existe.
- `405`: el método no va con esa ruta.
- `415`: no se mandó JSON.
- `422`: datos inválidos.
- `429`: demasiados intentos.

---

## GET /api/ingredientes

Busca ingredientes por nombre canónico o sinónimo.

**Parámetros:** `q` (texto, mínimo 2 caracteres).

**Respuesta 200**
```json
{ "resultados": [ { "id": 3, "nombre": "Morrón", "categoria": "Verduras" } ] }
```

**Errores:** `400` si `q` tiene menos de 2 caracteres.

---

## POST /api/usuarios

**Cuerpo:** `{ "nombre": "Ana", "email": "ana@ejemplo.com", "contrasena": "Clave1234" }`

**Respuesta 201** (queda logueada): `{ "usuario": { "id": 9, "nombre": "Ana", "email": "ana@ejemplo.com", "rol": "general" } }`

**Errores:**
- `422`: `{ "error": "Revisá los datos", "campos": { "email": "El email no es válido." } }`.
- `409`: ya hay una cuenta con ese email.

## POST /api/sesion · DELETE /api/sesion · GET /api/yo

- **`POST /api/sesion`**
  - **Cuerpo:** `{ "email": "...", "contrasena": "..." }`.
  - **Respuesta 200:** `{ "usuario": {...} }`.
  - **Errores:**
    - `401`: datos incorrectos (el mensaje es el mismo exista o no la cuenta).
    - `429`: el email está bloqueado por 5 errores en 15 minutos (DEC-014).
- **`DELETE /api/sesion`:** responde `204` sin cuerpo.
- **`GET /api/yo`:** responde `{ "usuario": {...} }`, o `401` si no hay sesión.

## GET /api/recetas

**Parámetros:**
- `q`: busca en el título, la descripción y los ingredientes.
- `pagina`: por defecto 1.
- `por_pagina`: de 1 a 50; por defecto 12.

**Respuesta 200**
```json
{
  "recetas": [
    { "id": 1, "titulo": "Tortilla de papas", "descripcion": "...", "minutos": 40, "dificultad": "Fácil",
      "porciones": 4, "etiquetas": ["economica", "sin_tacc"], "ingredientes": 5, "tengo": 5, "favorita": true }
  ],
  "paginacion": { "pagina": 1, "paginas": 6, "por_pagina": 12, "total": 62 }
}
```
Sin sesión, `tengo` cuenta solo los básicos y `favorita` es `false`.

## GET /api/recetas/{id}

**Parámetros:** `porciones` (1 a 12; por defecto, las de la receta).

**Respuesta 200**
```json
{
  "id": 1, "titulo": "Tortilla de papas", "porciones": 2, "porciones_base": 4, "minutos": 40,
  "dificultad": "Fácil", "imagen": null, "etiquetas": ["economica"], "favorita": false,
  "ingredientes": [
    { "id": 1, "nombre": "Papa", "cantidad": 300, "unidad": "g", "texto": "300 g",
      "opcional": false, "tengo": true, "sustituto": null }
  ],
  "pasos": [ { "numero": 2, "texto": "Calentar el aceite y freír...", "segundos": 900 } ]
}
```

**Errores:** `404` si no existe.

## GET /api/recomendaciones

Pide sesión.

**Parámetros:** `limite` (1 a 60; por defecto 20).

Cada recomendación trae lo mismo que una receta del catálogo, y además:
- `puntaje`;
- `grupo` (`ahora`, `falta1` o `casi`);
- `faltantes`;
- `sustituciones` (`[{ "falta": "Crema de leche", "usar": "Leche" }]`);
- `usa_lo_que_vence`.

## GET /api/despensa

Pide sesión.

**Respuesta 200**
```json
{ "despensa": [ { "id": 12, "ingrediente_id": 1, "nombre": "Papa", "nivel": "Normal", "cantidad": 1,
                  "unidad": "kg", "vence": "2026-11-10", "dias": 7, "ubicacion": "alacena" } ] }
```

## POST · DELETE /api/favoritos/{id}

Pide sesión.

**Respuesta 200:** `{ "receta": 2, "favorita": true }`. Si ya estaba así, no cambia nada y responde igual.

**Errores:** `404` si la receta no existe.
