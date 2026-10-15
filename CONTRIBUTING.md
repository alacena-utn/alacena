# Cómo trabajamos

## La regla de oro
Nadie sube nada directo a `main`. Todo entra por un Pull Request (PR) que revisa otra persona y con la CI en verde.

## El ciclo de cada tarea
```bash
git switch main
git pull                                    # traer lo último
git switch -c feature/P2-03-estructura      # rama nueva para la tarea
# ... trabajar ...
composer lint && composer test              # (con Docker: docker compose exec web composer lint)
git add .
git commit -m "feat(web): navegación inferior y secciones"
git push -u origin feature/P2-03-estructura # la primera vez; después alcanza con git push
gh pr create --web                          # abre el PR en el navegador, con la plantilla
```
En GitHub: completar la plantilla, pedir revisión → cuando lo aprueban y la CI está en verde, **Squash and merge**.

## Nombres de ramas
`feature/<id>-descripcion-corta` · `fix/<id>-descripcion` · `docs/<tema>`

## Mensajes de commit (Conventional Commits)
| Prefijo | Para qué |
|---|---|
| `feat:` | algo nuevo |
| `fix:` | arreglo |
| `docs:` | documentación |
| `test:` | pruebas |
| `chore:` | configuración, dependencias |
| `refactor:` | cambiar código sin cambiar comportamiento |
| `style:` | formato, sin cambios de lógica |

Opcional, el área entre paréntesis: `feat(despensa): ...`, `fix(api): ...`, `feat(db): ...`

## Dónde va cada cosa
| Si estás haciendo… | Va en… |
|---|---|
| Una pantalla | `public/index.html` + `public/js/pantallas/<pantalla>.js` + `public/css/` |
| Un endpoint nuevo | ruta en `public/api/index.php` → `src/Controladores/` → SQL en `src/Repositorios/` |
| Lógica de negocio (puntaje, porciones, unidades) | `src/Dominio/` + su prueba en `tests/Dominio/` |
| Un cambio en las tablas | `database/schema.sql` (y avisar en el grupo) |
| Una decisión | `docs/decisiones.md` |

## Reglas de código
- PHP: estilo PSR-12 (lo revisa `composer lint`), `declare(strict_types=1);` en cada archivo.
- SQL: **siempre** consultas preparadas con PDO (`prepare` + parámetros). Nunca concatenar datos del usuario.
- JS: mostrar datos del usuario con `textContent`, nunca con `innerHTML` (evita XSS).
- Contraseñas: solo `password_hash()` / `password_verify()`.
- Nada de claves en el código: van en `.env`.

## Revisar un PR
- Respondé en menos de 24 h.
- Bajalo y probalo si toca algo visible: `gh pr checkout <número>`.
- Comentá concreto y amable. **Approve** si está bien; **Request changes** solo si algo está roto o falta algo del "Terminado cuando".

## Si tu rama quedó atrás de main
```bash
git switch main && git pull
git switch mi-rama
git merge main          # resolver conflictos si aparecen, luego git add . && git commit
git push
```

## Definición de "terminado"
- [ ] Entró a `main` por PR, con una aprobación y la CI en verde
- [ ] Si es visible, se probó en el celular (360 px) y en escritorio
- [ ] Si cambió la base, `schema.sql` está actualizado y se avisó
- [ ] Si hubo una decisión, está en `docs/decisiones.md`

## Responsabilidad sobre el código
Quien abre un PR es responsable de entender y poder explicar todo el código que contiene, lo haya escrito como lo haya escrito.
