// Mi despensa: filtra los ingredientes mientras se escribe, sin recargar la página.
const filtro = document.querySelector('#filtro');
const items = document.querySelectorAll('#lista-despensa .item-despensa');
const sinResultados = document.querySelector('#sin-resultados');

// Quita tildes y pasa a minúsculas: así "limon" encuentra "Limón"
const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

if (filtro) {
  filtro.addEventListener('input', () => {
    const buscado = normalizar(filtro.value.trim());
    let visibles = 0;
    for (const item of items) {
      const coincide = normalizar(item.dataset.nombre).includes(buscado);
      item.hidden = !coincide;
      if (coincide) visibles++;
    }
    sinResultados.hidden = visibles > 0;
  });
}

// La fecha de vencimiento no puede ser de hace mucho: avisamos si parece un error de tipeo
const fecha = document.querySelector('#fecha_vencimiento');
if (fecha) {
  fecha.addEventListener('change', () => {
    const elegida = new Date(fecha.value);
    const haceUnMes = new Date();
    haceUnMes.setMonth(haceUnMes.getMonth() - 1);
    fecha.setCustomValidity(elegida < haceUnMes ? 'Esa fecha ya pasó hace más de un mes. ¿Está bien?' : '');
    fecha.reportValidity();
  });
}
