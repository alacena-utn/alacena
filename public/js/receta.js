// Detalle de receta: tildar los pasos para saber por dónde vas.
// Lo tildado se recuerda en este navegador, por receta (no se guarda en la cuenta: eso será el modo cocina).
const listaPasos = document.querySelector('.lista-pasos[data-receta]');

if (listaPasos) {
  const clave = 'alacena.pasos.' + listaPasos.dataset.receta;
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
