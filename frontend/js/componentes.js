const contenedorSidebar = document.getElementById("sidebar");

if (contenedorSidebar) {
    fetch("../components/sidebar.html")
        .then(response => response.text())
        .then(data => {
            contenedorSidebar.innerHTML = data;

            const paginaActual = window.location.pathname.split("/").pop();
            const enlaces = document.querySelectorAll(".sidebar nav a");

            enlaces.forEach(enlace => {
                const paginaEnlace = enlace.getAttribute("href");
                if (paginaEnlace === paginaActual) {
                    enlace.classList.add("active");
                }
            });

            aplicarPermisosSidebar();
        })
        .catch(err => console.error("Error cargando la sidebar:", err));
}

const contenedorHeader = document.getElementById("header");

if (contenedorHeader) {
    fetch("../components/header.html")
        .then(response => response.text())
        .then(data => {
            contenedorHeader.innerHTML = data;

            const titulo = document.body.dataset.titulo;
            const elemTitulo = document.getElementById("tituloPagina");

            if (elemTitulo && titulo) {
                elemTitulo.textContent = titulo;
            }
        })
        .catch(err => console.error("Error cargando el header:", err));
}

async function aplicarPermisosSidebar() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/sesion.php');
        if (!response.ok) return;

        const datos = await response.json();

        if (!datos.autenticado) {
            window.location.href = 'index.html';
            return;
        }

        const usuario = datos.usuario || {};
        const cargo = (usuario.cargo).toString().toLowerCase().trim();

        const lblRol = document.getElementById('lblRolUsuario');
        const lblNombre = document.getElementById('lblNombreUsuario');

        if (lblRol) lblRol.textContent = cargo.toUpperCase();
        if (lblNombre) {
            lblNombre.textContent = usuario.nombre_admin;
        }

        const esAdmin = (cargo === 'administrador');

        if (!esAdmin) {
            const navDoc = document.getElementById('navDocumentos');
            const navUsu = document.getElementById('navUsuarios');

            if (navDoc) navDoc.style.setProperty('display', 'none', 'important');
            if (navUsu) navUsu.style.setProperty('display', 'none', 'important');
        }
    } catch (error) {
        console.error('Error al aplicar permisos en la sidebar:', error);
    }
}