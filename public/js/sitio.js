// JavaScript común a todas las páginas (se carga desde el encabezado).

// 1. Menú desplegable en el celular
const botonMenu = document.querySelector('.barra__boton');
const menu = document.querySelector('#menu-principal');
if (botonMenu && menu) {
  botonMenu.addEventListener('click', () => {
    const abierto = menu.classList.toggle('abierto');
    botonMenu.setAttribute('aria-expanded', String(abierto));
  });
}

// 2. Confirmar antes de borrar: <form data-confirmar="¿Borrar la receta?"> ... </form>
document.addEventListener('submit', (evento) => {
  const mensaje = evento.target.dataset.confirmar;
  if (mensaje && !window.confirm(mensaje)) {
    evento.preventDefault();
  }
});
