# P4-D: Lista de requisitos y validación W3C y accesibilidad

## 1. Objetivo

Documentar los requisitos funcionales y técnicos del proyecto Alacena, y registrar las verificaciones de accesibilidad y validación del HTML realizadas sobre sus páginas.

## 2. Lista de requisitos

| ID | Requisito | Página o módulo | Estado | Evidencia |
|---|---|---|---|---|
| RF-01 | Registro de usuarios | `public/registro.php` | Verificado correctamente | Prueba funcional |
| RF-02 | Inicio y cierre de sesión | Módulo de autenticación | Verificado correctamente | Prueba funcional |
| RF-03 | Consulta y gestión de recetas | Módulo de recetas | Verificado correctamente | Prueba funcional |
| RF-04 | Gestión de la despensa | `public/app/despensa.php` | Verificado correctamente | Prueba funcional |
| RF-05 | Envío de consultas de contacto | Página de contacto | Verificado correctamente | Prueba funcional |
| RF-06 | Administración de consultas recibidas | `public/admin/consultas.php` | Verificado correctamente | Prueba funcional |
| RT-01 | Uso de consultas preparadas para acceder a la base de datos | Repositorios y acceso a datos | Pendiente de verificar | Revisión de código |
| RT-02 | Validación de datos del lado del servidor | Formularios y controladores | Pendiente de verificar | Revisión de código |
| RT-03 | Protección de formularios mediante CSRF cuando corresponda | Formularios que modifican datos | Pendiente de verificar | Revisión de código |
| RT-04 | Escapado de datos al generar HTML | Vistas PHP | Pendiente de verificar | Revisión de código |

Los estados deberán actualizarse después de comprobar cada requisito. No se considera cumplido un requisito solamente porque exista el archivo correspondiente.

## 3. Verificación de accesibilidad

Revisar las páginas y formularios del proyecto considerando:

- Declaración del idioma del documento mediante `lang`.
- Título de página descriptivo.
- Uso de elementos HTML semánticos, como `header`, `nav` y `main`.
- Jerarquía lógica de encabezados.
- Imágenes con texto alternativo apropiado.
- Campos de formulario con etiquetas asociadas.
- Mensajes de error comprensibles.
- Navegación mediante teclado y foco visible.
- Contraste suficiente entre texto y fondo.
- Tablas con encabezados correctamente identificados.

Registrar las observaciones y corregir los problemas encontrados dentro del alcance de la tarea.

## 4. Validación W3C

Validar el HTML generado por las páginas relevantes mediante el validador oficial:

https://validator.w3.org/

Cuando corresponda, utilizar la opción de validar mediante entrada directa o archivo HTML. Si se valida una página PHP, utilizar el HTML que genera al ejecutarse.

Registrar para cada página la fecha de validación, los errores encontrados y las correcciones realizadas. No declarar una página validada hasta haber realizado la comprobación.

## 5. Pruebas automáticas

Ejecutar desde la raíz del proyecto:

```powershell
composer lint
composer test
```

Registrar los resultados. Si existen pruebas omitidas, documentarlas sin considerarlas pruebas aprobadas.

## 6. Evidencias y cierre

Para cerrar la tarea:

- Actualizar el estado de los requisitos comprobados.
- Registrar los resultados de accesibilidad y validación W3C.
- Corregir los problemas detectados que estén dentro del alcance.
- Ejecutar nuevamente las pruebas automáticas.
- Adjuntar evidencias relevantes en el Pull Request.
- Solicitar revisión de otro integrante del equipo.
