// sidebar del administrador: ve todas las paginas. Si no es administrador lo saca de la pagina

verificarAdministrador();

async function verificarAdministrador() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/KGA-Development-Studio/API/sesion.php');

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

        // un empleado no puede entrar a las paginas del administrador
        if (cargo !== 'administrador') {
            window.location.href = 'gestionAmbulancias.html';
            return;
        }

        cargarSidebar(usuario, cargo);

    } catch (error) {
        console.error('Error al verificar el administrador:', error);
        window.location.href = 'index.html';
    }
}

function cargarSidebar(usuario, cargo) {
    const contenedorSidebar = document.getElementById("sidebar");

    // ambulanciaFormulario.html no tiene sidebar
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

            // Evento para avisar que la sidebar y la campanita se cargaron
            document.dispatchEvent(new CustomEvent("sidebarCargada"));
        })
        .catch(err => console.error("Error cargando la sidebar:", err));
}