// Contacto: contador de caracteres del mensaje y aviso antes de enviar si falta algo.
const formulario = document.querySelector('#form-contacto');
const mensaje = document.querySelector('#mensaje');
const contador = document.querySelector('#contador');

function actualizarContador() {
  const largo = mensaje.value.length;
  contador.textContent = `${largo} / ${mensaje.maxLength}`;
  contador.classList.toggle('error-campo', largo > 0 && largo < mensaje.minLength);
}

mensaje.addEventListener('input', actualizarContador);
actualizarContador();

formulario.addEventListener('submit', (evento) => {
  // checkValidity() usa las reglas del HTML (required, minlength, type="email")
  if (!formulario.checkValidity()) {
    evento.preventDefault();
    formulario.reportValidity();   // el navegador muestra qué campo corregir
  }
});
