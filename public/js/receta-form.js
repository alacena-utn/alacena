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
    }
});

// Al elegir un ingrediente, completa la unidad sugerida si todavía está vacía
contenedor.addEventListener('change', (evento) => {
    if (evento.target.dataset.campo !== 'id') return;
    const opcion = evento.target.selectedOptions[0];
    const unidad = evento.target.closest('.fila-ingrediente').querySelector('[data-campo="unidad"]');
    if (opcion && opcion.dataset.unidad && unidad.value === '') {
        unidad.value = opcion.dataset.unidad;
    }
});