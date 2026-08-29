fetch("../components/sidebar.html")
    .then(response => response.text())
    .then(data => {
        const sidebarElem = document.getElementById("sidebar");
        if (sidebarElem) {
            sidebarElem.innerHTML = data;

            const paginaActual = window.location.pathname.split("/").pop();
            const enlaces = document.querySelectorAll(".sidebar nav a");

            enlaces.forEach(enlace => {
                const paginaEnlace = enlace.getAttribute("href");
                if (paginaEnlace === paginaActual) {
                    enlace.classList.add("active");
                }
            });

            if (typeof controlarSidebarPorRol === "function") {
                controlarSidebarPorRol();
            }
        }
    })
    .catch(err => console.error("Error al cargar sidebar:", err));

fetch("../components/header.html")
    .then(response => response.text())
    .then(data => {
        const headerElem = document.getElementById("header");
        if (headerElem) {
            headerElem.innerHTML = data;

            const titulo = document.body.dataset.titulo;
            const tituloElem = document.getElementById("tituloPagina");

            if (tituloElem && titulo) {
                tituloElem.textContent = titulo;
            }
        }
    })
    .catch(err => console.error("Error al cargar header:", err));