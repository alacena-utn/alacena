// Detalle de receta: tildar los pasos para saber por dónde vas.
// Lo tildado se recuerda en este navegador, por receta (no se guarda en la cuenta: eso será el modo cocina).
const listaPasos = document.querySelector('.lista-pasos[data-receta]');

if (listaPasos) {
  const clave = 'cooki.pasos.' + listaPasos.dataset.receta;
  const pasos = [...listaPasos.querySelectorAll('.paso')];
  const checks = pasos.map((paso) => paso.querySelector('.paso__check'));
  const acciones = document.querySelector('.pasos-acciones');

  let hechos = [];
  try {
    hechos = JSON.parse(localStorage.getItem(clave) || '[]');
  } catch {
    hechos = [];
  }
  checks.forEach((check, i) => { check.checked = hechos.includes(i); });

  // Marca el paso actual (el primero sin tildar) y muestra "Empezar de nuevo" si hay algo tildado
  function actualizar() {
    const actual = checks.findIndex((check) => !check.checked);
    pasos.forEach((paso, i) => {
      paso.classList.toggle('paso--hecho', checks[i].checked);
      paso.classList.toggle('paso--actual', i === actual);
    });
    acciones.hidden = !checks.some((check) => check.checked);
    const lista = checks.flatMap((check, i) => (check.checked ? [i] : []));
    try {
      if (lista.length) localStorage.setItem(clave, JSON.stringify(lista));
      else localStorage.removeItem(clave);
    } catch {
      // Sin almacenamiento: los tildes valen hasta que se cierre la página
    }
  }

  listaPasos.addEventListener('change', (evento) => {
    if (evento.target.checked) window.sonar?.('birome');
    else window.sonar?.('clic');
    actualizar();
  });
  document.querySelector('#reiniciar-pasos').addEventListener('click', () => {
    checks.forEach((check) => { check.checked = false; });
    window.sonar?.('nota-pega');
    actualizar();
  });
  actualizar();
}

// Favorita (♥): se marca sin recargar la página. Sin JavaScript, el formulario recarga y vuelve acá.
const formFavorita = document.querySelector('.favorita');
if (formFavorita) {
  formFavorita.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const boton = formFavorita.querySelector('.favorita__boton');
    try {
      const respuesta = await fetch(formFavorita.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: new FormData(formFavorita),
      });
      if (!respuesta.ok) throw new Error(String(respuesta.status));
      const { favorita } = await respuesta.json();
      boton.setAttribute('aria-pressed', favorita ? 'true' : 'false');
      boton.title = favorita ? 'Sacar de mis favoritas' : 'Guardar en mis favoritas';
      boton.querySelector('.favorita__corazon').textContent = favorita ? '♥' : '♡';
      boton.querySelector('.favorita__texto').textContent = favorita ? 'En mis favoritas' : 'Guardar';
      window.sonar?.(favorita ? 'sticker-pega' : 'sticker-arranca');
    } catch {
      formFavorita.submit();
    }
  });
}
