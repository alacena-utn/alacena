// Validación de formularios en el navegador (registro y usuarios).
// El servidor vuelve a validar todo: esto solo ayuda a corregir antes de enviar.

function mostrarError(campo, mensaje) {
  let error = campo.parentElement.querySelector('.error-campo[data-js]');
  if (!error) {
    error = document.createElement('p');
    error.className = 'error-campo';
    error.dataset.js = '';
    campo.parentElement.append(error);
  }
  error.textContent = mensaje;
  campo.setAttribute('aria-invalid', mensaje ? 'true' : 'false');
}

function validarRegistro(formulario) {
  const nombre = formulario.elements.nombre;
  const email = formulario.elements.email;
  const contrasena = formulario.elements.contrasena;
  const repetir = formulario.elements.repetir;
  let valido = true;

  const reglas = [
    [nombre, nombre.value.trim().length >= 2, 'Ingresá tu nombre (al menos 2 letras).'],
    [email, email.validity.valid && email.value !== '', 'Ingresá un email válido.'],
    [contrasena, contrasena.value.length >= 8 && /[A-Za-z]/.test(contrasena.value) && /\d/.test(contrasena.value),
      'Al menos 8 caracteres, con letras y números.'],
    [repetir, repetir.value === contrasena.value && repetir.value !== '', 'Las contraseñas no coinciden.'],
  ];
  for (const [campo, ok, mensaje] of reglas) {
    mostrarError(campo, ok ? '' : mensaje);
    if (!ok && valido) {
      campo.focus();
      valido = false;
    }
  }
  return valido;
}

const registro = document.querySelector('#form-registro');
if (registro) {
  registro.addEventListener('submit', (evento) => {
    if (!validarRegistro(registro)) {
      evento.preventDefault();
    }
  });
}
