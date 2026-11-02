// Página principal: el dibujo al lado de la pregunta cambia por otro al azar cuando se lo toca.
// (El baile y el "acercarse" al pasar el mouse los hace el CSS.)
const boton = document.querySelector('.portada__dibujo');
if (boton) {
  const dibujos = JSON.parse(boton.dataset.dibujos || '[]');
  const imagen = boton.querySelector('img');
  boton.addEventListener('click', () => {
    if (dibujos.length < 2) return;
    const actual = imagen.src.split('/').pop().replace('.svg', '');
    let nuevo = actual;
    while (nuevo === actual) nuevo = dibujos[Math.floor(Math.random() * dibujos.length)];
    imagen.src = boton.dataset.carpeta + nuevo + '.svg';
    window.sonar?.('pop-arriba');
    // Reinicia el "salto" de cambio
    boton.classList.remove('portada__dibujo--cambia');
    void boton.offsetWidth;
    boton.classList.add('portada__dibujo--cambia');
  });
}
