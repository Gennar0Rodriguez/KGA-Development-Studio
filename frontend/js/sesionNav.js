document.addEventListener('DOMContentLoaded', controlarSidebarPorRol);

async function controlarSidebarPorRol() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/sesion.php');

        if (!response.ok) {
            console.error('Error al conectar con la API de sesión. Status:', response.status);
            return;
        }

        const datos = await response.json();

        if (!datos.autenticado) {
            window.location.href = 'index.html';
            return;
        }

        const usuario = datos.usuario;
        const cargo = (usuario.cargo || usuario.rol || '').toLowerCase().trim();

        const lblRol = document.getElementById('lblRolUsuario');
        const lblNombre = document.getElementById('lblNombreUsuario');

        if (lblRol) lblRol.textContent = cargo.toUpperCase();
        if (lblNombre) lblNombre.textContent = usuario.nombre_admin || usuario.nombre || 'Hospital de Clínicas';

        if (cargo !== 'administrador' && cargo !== 'admin') {
            const navDoc = document.getElementById('navDocumentos');
            const navUsu = document.getElementById('navUsuarios');

            if (navDoc) navDoc.style.setProperty('display', 'none', 'important');
            if (navUsu) navUsu.style.setProperty('display', 'none', 'important');
        }

    } catch (error) {
        console.error('Error al verificar sesión:', error);
    }
}