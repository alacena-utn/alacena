# Incremento 2 · Entrega B: qué cambió y de quién es cada parte

> **Base:** `main` con la Heladera 4.1, más el nombre nuevo (Cooki), Quiénes somos y la entrega A.
> **Pruebas:** **117 en verde** (eran 78). El lint queda limpio.
> **Recorrido:** las páginas nuevas se recorrieron a 360, 768 y 1280 px, con el color normal y con "noche", sin desbordes ni errores de PHP o JavaScript.

## 1. Qué trae

| Bloque del plan | Qué se hizo | Rol |
|---|---|---|
| 2.2 Modo cocina | `app/cocinar.php`:<br>• paso a paso con letra grande;<br>• "Listo" sin recargar;<br>• temporizadores que suenan y vibran;<br>• la pantalla queda encendida;<br>• el progreso queda guardado en la cuenta. Se sigue desde Mi cocina o desde la receta.<br>Al terminar descuenta de la despensa lo que quedó tildado, una sola vez (DEC-011) | P4 (P3 el descuento) |
| 2.5 Preferencias | `app/mis-preferencias.php`:<br>• "Siempre tengo";<br>• "No puedo comer";<br>• favoritas.<br>Además:<br>• ♡ en el detalle de la receta;<br>• ♥ en las tarjetas;<br>• "Solo mis favoritas" en el buscador;<br>• aviso "Tiene algo que no comés" (DEC-012) | P1 + P3 |
| 2.4 Recuperar la contraseña | `olvide.php` y `restablecer.php`: enlace por correo de un solo uso que vence en 1 hora. Cliente SMTP propio (`src/Smtp.php`); con Docker los correos llegan a Mailpit (DEC-013) | P1 |
| 2.4 Bloqueo | 5 contraseñas mal en 15 minutos bloquean ese email 15 minutos, en la página y en la API (DEC-014) | P1 |
| 2.6 Foto de receta | Se sube desde el editor (JPG, PNG o WebP de hasta 2 MB, validada por contenido) y se ve en el detalle (DEC-015) | P3 |
| 2.6 Paginación | Catálogo de recetas (12 por página) y listados de administración: recetas, usuarios e ingredientes (DEC-015) | P3 + P5 |
| 2.7 API | Registro, sesión, `yo`, recetas paginadas, receta por porciones, recomendaciones, despensa y favoritas ([api.md](api.md), DEC-016) | P3 |
| 2.9 Hosting | `APP_ENV=produccion` sin errores en pantalla, encabezados de seguridad, `FORZAR_HTTPS`, `APP_URL`, `scripts/empaquetar.php` y [publicar.md](publicar.md) (DEC-016) | P1 |
| 2.8 Calidad | Pruebas nuevas: `DescuentoTest`, `CocinaTest`, `PreferenciasTest`, `SmtpTest` (con un servidor SMTP de mentira), `BloqueoTest`, `RecuperarTest`, `PaginacionTest`, `ListadosTest`, `FotoRecetaTest` y la API ampliada | P4 revisa; cada prueba va con su parte |

## 2. Base de datos

- **Tablas nuevas:** `recuperacion` e `intento_ingreso`. Migración `database/migraciones/006-incremento-2-b.sql`.
- **Tablas que ya estaban y ahora se usan:** `sesion_cocina`, `paso_completado`, `usuario_basico`, `restriccion_usuario`, `favorito` y `receta.imagen`.
- **Datos de ejemplo:** María (`maria@cooki.test`) trae ajo en "Siempre tengo" y dos favoritas.

Para actualizar después de unir:
- **Con Docker:** `docker compose down -v` y `docker compose up -d`.
- **Sin borrar datos:** `mysql -u cooki -p cooki < database/migraciones/006-incremento-2-b.sql`.

En `.env` hay claves nuevas: copiá las de `.env.example`.
- `APP_URL` y `FORZAR_HTTPS`.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_SEGURIDAD`, `MAIL_USUARIO` y `MAIL_CONTRASENA`.

Con Docker, `MAIL_HOST=mailpit` y `MAIL_PORT=1025`. Con XAMPP sin Mailpit, `MAIL_HOST` vacío.

## 3. Cómo probarlo en 5 minutos (con Docker)

1. **Modo cocina.** Ingresá como `maria@cooki.test` / `Usuario1234` → Recetas → Tortilla de papas → **Cocinar paso a paso**.
   - Marcá un paso y recargá la página: sigue marcado.
   - Empezá un temporizador.
   - Al final, **Terminé**: Mi cocina avisa qué se descontó.
2. **Preferencias.** Menú de María → **Mis preferencias**: agregá "Huevo" en "No puedo comer". En Recetas, las que llevan huevo dicen "Tiene algo que no comés".
3. **Bloqueo.** Salí e ingresá 5 veces con una contraseña mal: aparece "Demasiados intentos".
4. **Recuperar la contraseña.** Pedí el enlace en "¿Olvidaste tu contraseña?" y abrilo desde http://localhost:8025. Elegí una contraseña nueva: también desbloquea el ingreso.
5. **Foto de receta.** Como `admin@cooki.test`, editá una receta y subile una foto.

## 4. Orden de los PR

La entrega B va en **dos PR**, uno después del otro. El detalle está en el LEEME del paquete de parches.

1. **Entrega B:** se revisa por partes, en este orden:
   - la base (P3): migración 006 y datos de ejemplo;
   - el modo cocina (P4);
   - las preferencias (P1 + P3);
   - las cuentas (P1): correo, recuperar la contraseña y bloqueo;
   - la foto y la paginación (P3 + P5).
2. **API, hosting y documentación** (P3 + P1).

## 5. Ajustes de diseño y navegación

Van dentro de los PR de la entrega A y de la entrega B, con la parte que corresponde. Detalle en DEC-017.

| Dónde | Qué cambió |
|---|---|
| Todo el sitio | La flecha de volver es una flecha a birome que vuelve a la página de la que se vino. Al lado lleva atajos ("o ir a Recetas"). El nombre "COOKI" hace la ola solo cada 9 segundos, sin sonido |
| Mi cocina | Lo vencido ya no está en "Usalo pronto": un papelito rosa al lado dice cuántos hay y lleva a la despensa |
| Cocinar paso a paso | Los ingredientes llevan su dibujo |
| Contacto, Recetas, Mi cuenta y Mis preferencias | Dibujos de ingredientes al azar en los espacios vacíos (no se muestran en el celular) |
| Menú de la cuenta | Hoja de cuaderno con "Mi cuenta" y "Mis preferencias" como imanes; Normal/Panorámica como papelitos de cinta y Salir como imán rojo |
| Administración | Editar y Borrar quedan uno al lado del otro en las tablas; la casilla de Ingredientes queda alineada con el buscador; un ingrediente de receta sin cantidad queda "a gusto" |

## 6. Lo que queda para después

- **Política de contenido (CSP):** el mapa de Contacto y los estilos en línea necesitan excepciones que hay que probar en el hosting.
- **Correo en los hostings gratuitos:** muchos bloquean SMTP. [publicar.md](publicar.md) explica las opciones.
- **El tiempo de los temporizadores** se guarda en el navegador. Los pasos hechos, en cambio, quedan en la cuenta.
