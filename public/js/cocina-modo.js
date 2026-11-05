// Modo cocina (app/cocinar.php)
// - "Listo" marca el paso en la cuenta sin recargar la página (sin JavaScript, el formulario recarga).
// - Temporizadores: el momento en que terminan se guarda en este navegador, así siguen andando
//   aunque se recargue la página. Al terminar suena, vibra (en el celular) y se avisa a los lectores de pantalla.
// - Mientras se cocina, se le pide al navegador que no apague la pantalla (si lo permite).
const cocinar = document.querySelector('.cocinar[data-sesion]');

if (cocinar) {
  const pasos = [...cocinar.querySelectorAll('.paso-cocina')];
  const contador = cocinar.querySelector('[data-hechos]');
  const barra = cocinar.querySelector('.cocinar__barra span');
  const avisoTiempo = cocinar.querySelector('.cocinar__aviso-tiempo');

  function actualizarProgreso() {
    const hechos = pasos.filter((p) => p.classList.contains('paso-cocina--hecho')).length;
    const actual = pasos.find((p) => !p.classList.contains('paso-cocina--hecho'));
    pasos.forEach((p) => p.classList.toggle('paso-cocina--actual', p === actual));
    contador.textContent = String(hechos);
    barra.style.width = `${pasos.length ? Math.round((100 * hechos) / pasos.length) : 0}%`;
    return actual;
  }

  // ── Marcar pasos sin recargar ──
  for (const form of cocinar.querySelectorAll('.paso-cocina__form')) {
    form.addEventListener('submit', async (evento) => {
      evento.preventDefault();
      const paso = form.closest('.paso-cocina');
      const campoHecho = form.elements.hecho;
      const marcar = campoHecho.value === '1';
      const boton = form.querySelector('.paso-cocina__listo');
      boton.disabled = true;
      try {
        const respuesta = await fetch(form.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'fetch' },
          body: new FormData(form),
        });
        if (!respuesta.ok) throw new Error(String(respuesta.status));
        paso.classList.toggle('paso-cocina--hecho', marcar);
        boton.setAttribute('aria-pressed', marcar ? 'true' : 'false');
        boton.querySelector('.paso-cocina__etiqueta').textContent = marcar ? 'Hecho' : 'Listo';
        campoHecho.value = marcar ? '0' : '1';
        window.sonar?.(marcar ? 'birome' : 'clic');
        const siguiente = actualizarProgreso();
        if (marcar && siguiente) {
          siguiente.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (marcar && !siguiente) {
          document.querySelector('#terminar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      } catch {
        form.submit();   // si falla el pedido en segundo plano, se envía el formulario común
      } finally {
        boton.disabled = false;
      }
    });
  }

  // ── Temporizadores ──
  const clave = (t) => `cooki.temporizador.${t.dataset.clave}`;
  const formato = (s) => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;

  function leer(t) {
    try {
      return Number(localStorage.getItem(clave(t))) || 0;
    } catch {
      return Number(t.dataset.fin) || 0;
    }
  }

  function guardar(t, fin) {
    t.dataset.fin = String(fin);
    try {
      if (fin) localStorage.setItem(clave(t), String(fin));
      else localStorage.removeItem(clave(t));
    } catch {
      // Sin almacenamiento: el temporizador sigue mientras la página esté abierta
    }
  }

  function sonarAlarma(t) {
    const numero = t.closest('.paso-cocina')?.querySelector('.paso-cocina__numero')?.textContent ?? '';
    avisoTiempo.textContent = `¡Terminó el tiempo! ${numero}`;
    t.classList.add('temporizador--sonando');
    navigator.vibrate?.([300, 150, 300, 150, 300]);
    // Tres tandas de tres sonidos, con una pausa entre una tanda y la otra
    for (let tanda = 0; tanda < 3; tanda += 1) {
      for (let vez = 0; vez < 3; vez += 1) {
        setTimeout(() => window.sonar?.('marca'), tanda * 4000 + vez * 900);
      }
    }
  }

  function mostrar(t) {
    const reloj = t.querySelector('.temporizador__reloj');
    const empezar = t.querySelector('.temporizador__boton');
    const cancelar = t.querySelector('.temporizador__cancelar');
    const total = Number(t.dataset.segundos);
    const fin = leer(t);
    const restante = fin ? Math.max(0, Math.ceil((fin - Date.now()) / 1000)) : total;
    reloj.textContent = formato(restante);
    t.classList.toggle('temporizador--andando', Boolean(fin) && restante > 0);
    empezar.hidden = Boolean(fin) && restante > 0;
    empezar.textContent = fin && restante === 0 ? '↻ De nuevo' : '▶ Empezar';
    cancelar.hidden = !fin;
    if (fin && restante === 0 && !t.dataset.avisado) {
      t.dataset.avisado = '1';
      sonarAlarma(t);
    }
  }

  const temporizadores = [...cocinar.querySelectorAll('.temporizador')];
  for (const t of temporizadores) {
    // Si al volver a la página ya había terminado hace rato, no vuelve a sonar
    const fin = leer(t);
    if (fin && fin < Date.now() - 5000) t.dataset.avisado = '1';
    t.querySelector('.temporizador__boton').addEventListener('click', () => {
      delete t.dataset.avisado;
      t.classList.remove('temporizador--sonando');
      guardar(t, Date.now() + Number(t.dataset.segundos) * 1000);
      window.sonar?.('tic');
      mostrar(t);
    });
    t.querySelector('.temporizador__cancelar').addEventListener('click', () => {
      guardar(t, 0);
      delete t.dataset.avisado;
      t.classList.remove('temporizador--sonando');
      window.sonar?.('clic');
      mostrar(t);
    });
    mostrar(t);
  }
  if (temporizadores.length) {
    setInterval(() => temporizadores.forEach(mostrar), 500);
  }

  // ── Pantalla encendida mientras se cocina (si el navegador lo permite) ──
  let bloqueo = null;
  async function mantenerPantalla() {
    try {
      bloqueo = (await navigator.wakeLock?.request('screen')) ?? null;
      bloqueo?.addEventListener('release', () => { bloqueo = null; });
    } catch {
      bloqueo = null;
    }
  }
  mantenerPantalla();
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !bloqueo) mantenerPantalla();
  });

  actualizarProgreso();
}
