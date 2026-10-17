const formulario = document.querySelector('#form-contacto');
const mensaje = document.querySelector('#mensaje');
const contador = document.querySelector('#contador');

if (formulario && mensaje && contador) {
    function actualizarContador() {
        const largo = mensaje.value.length;

        contador.textContent = `${largo} / ${mensaje.maxLength}`;
        contador.classList.toggle(
            'error-campo',
            largo > 0 && largo < mensaje.minLength
        );
    }

    mensaje.addEventListener('input', actualizarContador);
    actualizarContador();

    formulario.addEventListener('submit', (evento) => {
        if (!formulario.checkValidity()) {
            evento.preventDefault();
            formulario.reportValidity();
        }
    });
}
