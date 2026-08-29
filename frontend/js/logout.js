document.addEventListener('click', (event) => {

    if (event.target.closest('#btnLogout')) {
        cerrarSesion();
    }

});


async function cerrarSesion() {

    if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        return;
    }

    try {

        await fetch('/kgade/KGA-Development-Studio/API/logout.php', {
            method: 'GET'
        });

    } catch (error) {

        console.error('Error al cerrar sesión:', error);

    } finally {

        window.location.href = '../html/index.html';

    }
}