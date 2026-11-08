// sidebar del empleado: sirve para las paginas que abren empleados y administradores. Si no hay sesion va al login

verificarSesion();

async function verificarSesion() {
    try {
        const response = await fetch('../../API/sesion.php');

        if (!response.ok) {
            window.location.href = 'index.html';
            return;
        }

        const datos = await response.json();

        // sin sesion iniciada va al login
        if (!datos.autenticado) {
            window.location.href = 'index.html';
            return;
        }

        const usuario = datos.usuario || {};
        const cargo = (usuario.cargo || '').toString().toLowerCase().trim();

        cargarSidebar(usuario, cargo);

    } catch (error) {
        console.error('Error al verificar la sesion:', error);
        window.location.href = 'index.html';
    }
}

function cargarSidebar(usuario, cargo) {
    const contenedorSidebar = document.getElementById("sidebar");

    if (!contenedorSidebar) {
        return;
    }

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

            const lblRol = document.getElementById('lblRolUsuario');
            const lblNombre = document.getElementById('lblNombreUsuario');

            if (lblRol) lblRol.textContent = cargo.toUpperCase();
            if (lblNombre) {
                lblNombre.textContent = usuario.nombre_admin || 'Usuario';
            }

            const esAdmin = (cargo === 'administrador');

            // el empleado no ve las paginas del administrador
            if (!esAdmin) {
                const navDoc = document.getElementById('navDocumentos');
                const navUsu = document.getElementById('navUsuarios');
                const navEnc = document.getElementById('navEncuesta');
                const navEnt = document.getElementById('navEntidades');

                if (navDoc) navDoc.style.setProperty('display', 'none', 'important');
                if (navUsu) navUsu.style.setProperty('display', 'none', 'important');
                if (navEnc) navEnc.style.setProperty('display', 'none', 'important');
                if (navEnt) navEnt.style.setProperty('display', 'none', 'important');
            }

            // Evento para avisar que la sidebar y la campanita se cargaron
            document.dispatchEvent(new CustomEvent("sidebarCargada"));
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