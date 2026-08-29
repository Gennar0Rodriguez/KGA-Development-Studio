const btnAbrirModalTraslado =
    document.getElementById("btnAbrirModalTraslado");

const modalNuevoTraslado =
    document.getElementById("modalNuevoTraslado");

const btnCerrarModalTraslado =
    document.getElementById("btnCerrarModalTraslado");

const btnCancelarModalTraslado =
    document.getElementById("btnCancelarModalTraslado");


btnAbrirModalTraslado.addEventListener("click", () => {

    modalNuevoTraslado.classList.add("mostrar");

});


btnCerrarModalTraslado.addEventListener("click", () => {

    modalNuevoTraslado.classList.remove("mostrar");

});


btnCancelarModalTraslado.addEventListener("click", () => {

    modalNuevoTraslado.classList.remove("mostrar");

});


modalNuevoTraslado.addEventListener("click", (event) => {

    if (event.target === modalNuevoTraslado) {

        modalNuevoTraslado.classList.remove("mostrar");

    }

});



document.addEventListener("keydown", (event) => {

    if (event.key === "Escape") {

        modalNuevoTraslado.classList.remove("mostrar");

    }

});


document.getElementById("formNuevoTraslado")
    .addEventListener("submit", (event) => {

        event.preventDefault();


        modalNuevoTraslado.classList.remove("mostrar");

    });