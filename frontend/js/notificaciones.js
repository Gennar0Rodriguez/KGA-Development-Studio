let intervaloNotificaciones = null;
let consultaEnCurso = false;

// Iniciar cuando el documento esté preparado.
document.addEventListener('DOMContentLoaded', () => {
    // La barra lateral se carga mediante fetch().
    document.addEventListener('sidebarCargada', () => {
        iniciarPollingNotificaciones();
    });

    // También intentar consultar si la barra ya está disponible.
    iniciarPollingNotificaciones();

    // Actualizar el contador automáticamente cada 4 segundos.
    intervaloNotificaciones = setInterval(() => {
        iniciarPollingNotificaciones();
    }, 4000);
});

async function iniciarPollingNotificaciones() {
    // Evitar que se acumulen consultas simultáneas.
    if (consultaEnCurso) {
        return;
    }

    consultaEnCurso = true;

    try {
        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/notificaciones/notificaciones.php',
            {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        if (!response.ok) {
            throw new Error(
                `Error HTTP ${response.status} al consultar notificaciones`
            );
        }

        const data = await response.json();

        // Asegurar que la respuesta tenga el formato esperado.
        const notificaciones = Array.isArray(data.notificaciones)
            ? data.notificaciones
            : [];

        // Usar el total calculado por la API.
        const noLeidas = Number(data.no_leidas);

        if (!Number.isFinite(noLeidas) || noLeidas < 0) {
            throw new Error('La cantidad de notificaciones no es válida');
        }

        // Actualizar el contador de la barra lateral.
        actualizarBadgeContador(noLeidas);

        // Renderizar únicamente en la página de notificaciones.
        if (
            window.location.pathname.includes('notificaciones.html')
        ) {
            renderizarNotificaciones(notificaciones);
        }

    } catch (error) {
        console.error(
            'Error al consultar las notificaciones:',
            error
        );
    } finally {
        consultaEnCurso = false;
    }
}

function actualizarBadgeContador(noLeidas) {
    const badge = document.getElementById('badgeNotificaciones');

    // La barra lateral puede no haberse insertado todavía.
    if (!badge) {
        return;
    }

    const cantidad = Math.max(0, Math.floor(Number(noLeidas) || 0));

    if (cantidad === 0) {
        badge.textContent = '';
        badge.style.display = 'none';
        badge.removeAttribute('title');
        badge.setAttribute('aria-label', 'No tienes notificaciones sin leer');
        return;
    }

    // Mostrar 1 a 9; a partir de 10, mostrar 9+.
    badge.textContent = cantidad > 9 ? '9+' : String(cantidad);

    // El CSS define display:flex para centrar el número.
    badge.style.display = 'inline-flex';

    badge.title = `${cantidad} notificación${cantidad === 1 ? '' : 'es'} sin leer`;
    badge.setAttribute(
        'aria-label',
        `${cantidad} notificación${cantidad === 1 ? '' : 'es'} sin leer`
    );
}

function renderizarNotificaciones(lista) {
    const main = document.querySelector('main');

    if (!main) {
        return;
    }

    // Crear el encabezado sin destruir otros elementos de la página.
    let titulo = main.querySelector('h2');

    if (!titulo) {
        titulo = document.createElement('h2');
        main.prepend(titulo);
    }

    titulo.textContent = '🔔 Notificaciones';

    // Crear o reutilizar el contenedor de las tarjetas.
    let contenedor = main.querySelector('.lista-notificaciones');

    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.className = 'lista-notificaciones';
        main.appendChild(contenedor);
    }

    contenedor.replaceChildren();

    if (lista.length === 0) {
        const mensaje = document.createElement('p');
        mensaje.className = 'sin-notificaciones';
        mensaje.textContent = 'No tienes notificaciones pendientes.';
        contenedor.appendChild(mensaje);
        return;
    }

    lista.forEach(item => {
        const card = document.createElement('article');
        card.className = 'card-notificacion';

        const tituloCard = document.createElement('h3');
        tituloCard.textContent = item.titulo || 'Notificación';

        const descripcion = document.createElement('p');
        descripcion.textContent = item.descripcion || '';

        const fecha = document.createElement('small');
        fecha.className = 'fecha-notificacion';
        fecha.textContent = formatearFecha(item.fecha_envio);

        const boton = document.createElement('button');
        boton.className = 'btn-leido';
        boton.type = 'button';
        boton.textContent = 'Marcar como leído';

        boton.addEventListener('click', () => {
            eliminarNotificacion(item.id_notificacion);
        });

        card.append(tituloCard, descripcion, fecha, boton);
        contenedor.appendChild(card);
    });
}

async function eliminarNotificacion(id) {
    try {
        const response = await fetch(
            `/kgade/KGA-Development-Studio/API/notificaciones/notificaciones.php?id=${encodeURIComponent(id)}`,
            {
                method: 'DELETE',
                credentials: 'same-origin'
            }
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'No se pudo eliminar la notificación');
        }

        await iniciarPollingNotificaciones();

    } catch (error) {
        console.error('Error al eliminar la notificación:', error);
    }
}

function formatearFecha(fechaStr) {
    if (!fechaStr) {
        return '';
    }

    const fecha = new Date(fechaStr);

    if (Number.isNaN(fecha.getTime())) {
        return fechaStr;
    }

    return fecha.toLocaleString('es-UY', {
        dateStyle: 'short',
        timeStyle: 'short'
    });
}