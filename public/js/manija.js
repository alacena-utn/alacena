// La manija de la heladera funciona como barra de desplazamiento.
// - Con el mouse: tocás en cualquier parte de la manija y la página va ahí; arrastrando, la seguís.
// - En el celular: mantené apretada la manija un momento (vibra si el teléfono lo permite) y arrastrá.
// La barra común del navegador sigue estando: esto es un agregado, no la reemplaza.
(() => {
  const manija = document.querySelector('.manija');
  if (!manija) return;
  const agarre = manija.querySelector('.manija__agarre');
  const ESPERA_TACTIL = 300;   // ms apretando antes de empezar a arrastrar en pantallas táctiles

  const recorrido = () => document.documentElement.scrollHeight - window.innerHeight;

  // Ubica el agarre según cuánto se bajó y lo achica según qué parte de la página se ve
  function dibujar() {
    const total = recorrido();
    manija.classList.toggle('manija--quieta', total <= 4);
    const alto = manija.clientHeight;
    const altoAgarre = Math.max(46, alto * (window.innerHeight / document.documentElement.scrollHeight));
    const posicion = total > 0 ? (window.scrollY / total) * (alto - altoAgarre) : 0;
    agarre.style.height = altoAgarre + 'px';
    agarre.style.transform = `translateY(${posicion}px)`;
  }

  // Lleva la página al lugar que corresponde a la altura y del puntero sobre la manija
  function irA(y) {
    const caja = manija.getBoundingClientRect();
    const altoAgarre = agarre.offsetHeight;
    const proporcion = (y - caja.top - altoAgarre / 2) / (caja.height - altoAgarre);
    window.scrollTo({ top: Math.max(0, Math.min(1, proporcion)) * recorrido(), behavior: 'instant' });
  }

  let arrastrando = false;
  let espera = null;
  let inicio = null;

  function empezar(evento) {
    arrastrando = true;
    manija.classList.add('manija--agarrada');
    try {
      manija.setPointerCapture(evento.pointerId);   // sigue recibiendo el movimiento aunque el dedo salga de la manija
    } catch {
      // Si el puntero ya no está activo, se arrastra igual mientras siga sobre la manija
    }
    irA(evento.clientY);
  }

  manija.addEventListener('pointerdown', (evento) => {
    if (manija.classList.contains('manija--quieta')) return;
    if (evento.pointerType === 'mouse') {
      evento.preventDefault();
      empezar(evento);
      return;
    }
    // Táctil: hay que mantener apretado
    inicio = { x: evento.clientX, y: evento.clientY };
    espera = setTimeout(() => {
      navigator.vibrate?.(12);
      empezar(evento);
    }, ESPERA_TACTIL);
  });

  manija.addEventListener('pointermove', (evento) => {
    if (arrastrando) {
      evento.preventDefault();
      irA(evento.clientY);
    } else if (espera && Math.hypot(evento.clientX - inicio.x, evento.clientY - inicio.y) > 10) {
      clearTimeout(espera);   // se movió antes de tiempo: no era para agarrar la manija
      espera = null;
    }
  });

  const soltar = () => {
    clearTimeout(espera);
    espera = null;
    arrastrando = false;
    manija.classList.remove('manija--agarrada');
  };
  manija.addEventListener('pointerup', soltar);
  manija.addEventListener('pointercancel', soltar);
  manija.addEventListener('contextmenu', (evento) => evento.preventDefault());

  window.addEventListener('scroll', dibujar, { passive: true });
  window.addEventListener('resize', dibujar);
  new ResizeObserver(dibujar).observe(document.body);
  dibujar();
})();
