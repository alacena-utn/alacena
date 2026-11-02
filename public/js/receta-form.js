// Formulario de recetas: agregar y quitar filas de ingredientes sin recargar la página.
const contenedor = document.querySelector('#filas-ingredientes');
const botonAgregar = document.querySelector('#agregar-ingrediente');

// Cada fila nueva necesita un número distinto en el name: ingredientes[3][id], ingredientes[3][cantidad]...
let siguiente = contenedor.children.length;

function renumerar(fila, numero) {
  for (const control of fila.querySelectorAll('[data-campo]')) {
    const campo = control.dataset.campo;
    control.name = `ingredientes[${numero}][${campo}]`;
    if (control.id) {
      const label = fila.querySelector(`label[for="${control.id}"]`);
      control.id = `ing-${numero}-${campo}`;
      if (label) label.htmlFor = control.id;
    }
  }
}

botonAgregar.addEventListener('click', () => {
  const modelo = contenedor.firstElementChild;
  const fila = modelo.cloneNode(true);
  renumerar(fila, siguiente++);
  fila.querySelector('select').value = '0';
  fila.querySelectorAll('input[type="text"]').forEach((input) => { input.value = ''; });
  fila.querySelector('input[type="checkbox"]').checked = false;
  fila.querySelector('.fila-ingrediente__dibujo').src = contenedor.dataset.frasco;
  contenedor.append(fila);
  fila.querySelector('select').focus();
});

contenedor.addEventListener('click', (evento) => {
  const boton = evento.target.closest('[data-quitar]');
  if (!boton) return;
  const fila = boton.closest('.fila-ingrediente');
  if (contenedor.children.length > 1) {
    fila.remove();
  } else {
    // Siempre queda al menos una fila: la vaciamos en vez de borrarla
    fila.querySelector('select').value = '0';
    fila.querySelectorAll('input[type="text"]').forEach((input) => { input.value = ''; });
    fila.querySelector('.fila-ingrediente__dibujo').src = contenedor.dataset.frasco;
  }
});

// Al elegir un ingrediente, completa la unidad sugerida si todavía está vacía
contenedor.addEventListener('change', (evento) => {
  if (evento.target.dataset.campo !== 'id') return;
  const opcion = evento.target.selectedOptions[0];
  const fila = evento.target.closest('.fila-ingrediente');
  const unidad = fila.querySelector('[data-campo="unidad"]');
  // El dibujo de la fila cambia con el ingrediente elegido (y "salta" un poco)
  const dibujo = fila.querySelector('.fila-ingrediente__dibujo');
  dibujo.src = opcion?.dataset.dibujo || contenedor.dataset.frasco;
  dibujo.animate([{ transform: 'scale(.5) rotate(-20deg)' }, { transform: 'none' }], { duration: 300, easing: 'ease-out' });
  window.sonar?.('pop-arriba');
  if (opcion && opcion.dataset.unidad && unidad.value === '') {
    unidad.value = opcion.dataset.unidad;
  }
});
