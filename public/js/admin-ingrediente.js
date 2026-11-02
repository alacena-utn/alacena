// Editar ingrediente: vista previa del dibujo mientras se escribe el nombre.
// Usa la misma regla que iconoIngrediente() en PHP: primero el nombre completo sin tildes y con guiones
// ("Cebolla de verdeo" → cebolla-de-verdeo.svg), después la primera palabra; si no hay, el frasco.
const vista = document.querySelector('.vista-dibujo');
const nombre = document.querySelector('#nombre');

if (vista && nombre) {
  const dibujos = new Set(JSON.parse(vista.dataset.dibujos || '[]'));
  const imagen = vista.querySelector('img');
  const texto = vista.querySelector('figcaption');
  let anterior = imagen.src;

  const palabras = (valor) => valor.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .split(/[^a-z]+/).filter(Boolean);

  nombre.addEventListener('input', () => {
    const partes = palabras(nombre.value);
    const elegido = [partes.join('-'), partes[0]].find((candidato) => candidato && dibujos.has(candidato));
    const nuevo = vista.dataset.carpeta + (elegido || 'frasco') + '.svg';
    texto.textContent = elegido || nombre.value.trim() === '' ? 'Dibujo' : 'Sin dibujo: frasco';
    if (nuevo !== anterior) {
      imagen.src = nuevo;
      anterior = nuevo;
      imagen.animate([{ transform: 'scale(.5) rotate(-20deg)' }, { transform: 'none' }], { duration: 300, easing: 'ease-out' });
    }
  });
}
