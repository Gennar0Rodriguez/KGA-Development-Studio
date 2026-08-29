
fetch("../components/sidebar.html")
    .then(response => response.text())
    .then(data => {

        document.getElementById("sidebar").innerHTML = data;

        const paginaActual = window.location.pathname.split("/").pop();

        const enlaces = document.querySelectorAll(".sidebar nav a");

        enlaces.forEach(enlace => {

            const paginaEnlace = enlace.getAttribute("href");

            if (paginaEnlace === paginaActual) {
                enlace.classList.add("active");
            }

        });

        controlarSidebarPorRol();

    });

fetch("../components/header.html")
    .then(response => response.text())
    .then(data => {

        document.getElementById("header").innerHTML = data;

        const titulo = document.body.dataset.titulo;

        document.getElementById("tituloPagina").textContent = titulo;

    });