// Mi cocina: selector de ingredientes. Al tocar un sticker se piden las recetas
// a la misma página (index.php?ing[]=...) y se reemplaza solo el resultado, sin recargar.
// Sin JavaScript se usa el botón "Ver recetas" y la página se recarga.
const formSelector = document.querySelector('#form-selector');
const resultado = document.querySelector('#resultado-selector');

if (formSelector && resultado) {
  formSelector.querySelector('.selector__botones').hidden = true;
  let pedido = 0;

  async function actualizar() {
    const parametros = new URLSearchParams(new FormData(formSelector));
    const direccion = formSelector.action.split('#')[0] + '?' + parametros.toString();
    const numero = ++pedido;
    resultado.setAttribute('aria-busy', 'true');
    try {
      const respuesta = await fetch(direccion, { headers: { 'X-Requested-With': 'fetch' } });
      const html = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
      const nuevo = html.querySelector('#resultado-selector');
      // Si mientras tanto se tocó otro sticker, se descarta esta respuesta vieja
      if (numero === pedido && nuevo) {
        // Se mueven los nodos ya armados por el servidor (con todo escapado por e()); no se usa innerHTML
        resultado.replaceChildren(...nuevo.childNodes);
        history.replaceState(null, '', direccion);
      }
    } catch {
      formSelector.submit();   // sin conexión con el servidor: se envía de la forma común
    } finally {
      resultado.removeAttribute('aria-busy');
    }
  }

  formSelector.addEventListener('change', (evento) => {
    if (evento.target.type === 'checkbox') window.sonar?.(evento.target.checked ? 'pop-arriba' : 'pop-abajo');
    else window.sonar?.('clic');
    actualizar();
  });
}
