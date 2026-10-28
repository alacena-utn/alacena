const buscador = document.getElementById("buscarUsuario");
const lista = document.getElementById("tablaUsuarios");
buscador.addEventListener("input", function () {
  const texto = buscador.value
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  lista.querySelectorAll("tbody tr").forEach(function (usuario) {
    const nombre = usuario.textContent
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");
    usuario.hidden = !nombre.includes(texto);
  });
});
