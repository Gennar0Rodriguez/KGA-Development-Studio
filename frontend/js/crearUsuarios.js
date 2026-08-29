document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAgregarUsuario');

    if (form) {
        form.addEventListener('submit', registrarUsuario);
    }
});

async function registrarUsuario(e) {
    e.preventDefault();

    const msg = document.getElementById('msgResultado');

    // Armamos el objeto con las claves que espera $datos en el PHP del profe
    const datos = {
        ci: document.getElementById('ci').value,
        nombre: document.getElementById('nombre').value,
        apellido: document.getElementById('apellido').value,
        user: document.getElementById('user').value,
        pass: document.getElementById('pass').value,
        rol: document.getElementById('rol').value
    };

    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/usuario/usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const resultado = await response.json();

        if (response.ok) {
            msg.textContent = resultado.mensaje;
            msg.style.color = "#16a34a";
            e.target.reset();
        } else {
            msg.textContent = resultado.Error || resultado.error || "No se pudo crear el usuario";
            msg.style.color = "#dc2626";
        }
    } catch (error) {
        console.error('Error:', error);
        msg.textContent = "Error de conexión con el servidor";
        msg.style.color = "#dc2626";
    }
}