    const btnAbrirModal = document.getElementById("btnAbrirModal");
const modal = document.getElementById("modalSubirDocumento");
const btnCerrarModal = document.getElementById("btnCerrarModal");
const btnCancelarModal = document.getElementById("btnCancelarModal");


btnAbrirModal.addEventListener("click", () => {

    modal.classList.add("mostrar");

});


btnCerrarModal.addEventListener("click", () => {

    modal.classList.remove("mostrar");

});


btnCancelarModal.addEventListener("click", () => {

    modal.classList.remove("mostrar");

});


// Cerrar haciendo click fuera del modal

modal.addEventListener("click", (event) => {

    if (event.target === modal) {
        modal.classList.remove("mostrar");
    }

});


// Cerrar con ESC

document.addEventListener("keydown", (event) => {

    if (event.key === "Escape") {
        modal.classList.remove("mostrar");
    }

});