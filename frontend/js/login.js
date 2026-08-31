document.getElementById('formLogin').addEventListener('submit', async (event) => {
    event.preventDefault();

    const ciInput = document.getElementById('ci_admin').value;
    const passInput = document.getElementById('pass').value;

    const datos = {
        ci_admin: ciInput,
        pass: passInput
    };

    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const resultado = await response.json();

        if (!response.ok) {
            alert(resultado.error || 'Credenciales incorrectas');
            return;
        }

        // Inspeccionar qué estructura exactametne devuelve la API
        console.log('Datos recibidos tras login:', resultado);

        // Extraer el rol (admitiendo tanto 'cargo' como 'rol')
        const cargoUsuario = (resultado.usuario?.cargo).toLowerCase().trim();

        // Redirección directa según el rol
        if (cargoUsuario === 'administrador') {
            window.location.href = 'ambulanciaFormulario.html';
        } else {
            // Empleado, enfermero o cualquier otro rol
            window.location.href = 'gestionAmbulancias.html';
        }

    } catch (error) {
        console.error('Error en la petición:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
});