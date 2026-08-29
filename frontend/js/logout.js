document.addEventListener('DOMContentLoaded', () => {
    // Verificar si existe el botón de logout en la vista actual
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', cerrarSesion);
    }
});

async function cerrarSesion() {
    if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) return;

    try {
        await fetch('/kgade/KGA-Development-Studio/API/logout.php', {
            method: 'GET' // O POST, según tengas mapeada la API
        });
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    } finally {
        // Redirige al login sin importar si el fetch falló o tuvo éxito
        window.location.href = '../login.html'; 
    }
}