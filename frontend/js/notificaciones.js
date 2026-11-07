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

// ======================================================
// ACTUALIZAR CONTADOR DE NOTIFICACIONES
// ======================================================

function actualizarBadgeContador(cantidad) {

    const badge = document.getElementById('badgeNotificaciones');

    // Si la barra lateral todavía no está cargada, no hacemos nada.
    if (!badge) return;

    if (cantidad > 0) {
        badge.textContent = cantidad > 9 ? '+9' : cantidad;
        badge.style.display = 'inline-block';
    } else {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

function renderizarNotificaciones(lista) {

    const main = document.querySelector('main');

    if (!main) return;

    let titulo = main.querySelector('h2');

    if (!titulo) {
        titulo = document.createElement('h2');
        main.prepend(titulo);
    }

    titulo.textContent = 'Notificaciones';

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

        // Botón para marcar la notificación como leída.
const botonLeido = document.createElement('button');
botonLeido.className = 'btn-leido';
botonLeido.type = 'button';

if (Number(item.leido) === 1) {

    botonLeido.textContent = 'Leída';
    botonLeido.disabled = true;

} else {

    botonLeido.textContent = 'Marcar como leído';

    botonLeido.addEventListener('click', () => {
        marcarNotificacionLeida(item);
    });

}

    // Botón para eliminar la notificación.
    const botonEliminar = document.createElement('button');
    botonEliminar.className = 'btn-eliminar-notificacion';
    botonEliminar.type = 'button';
    botonEliminar.textContent = 'Eliminar';

    botonEliminar.addEventListener('click', () => {
        eliminarNotificacion(item);
    });

    card.append(
        tituloCard,
        descripcion,
        fecha,
        botonLeido,
        botonEliminar
    );

        contenedor.appendChild(card);

    });
}

// ======================================================
// MARCAR NOTIFICACIÓN COMO LEÍDA
// ======================================================

async function marcarNotificacionLeida(item) {

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/notificaciones/notificaciones.php',
            {
                method: 'PUT',

                credentials: 'same-origin',

                headers: {
                    'Content-Type': 'application/json'
                },

            body: JSON.stringify({
                id_traslado: item.id_traslado,
                ci_chofer: item.ci_chofer,
                matricula: item.matricula,
                id_destino: item.id_destino,
                ci_admin: item.ci_admin
            })
            }
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'No se pudo actualizar');
        }

        // Actualizamos el contador y las tarjetas.
        await iniciarPollingNotificaciones();

    } catch (error) {

        console.error('Error al marcar como leída:', error);

    }
}

// ======================================================
// ELIMINAR UNA NOTIFICACIÓN
// ======================================================

async function eliminarNotificacion(item) {

    if (!confirm('¿Desea eliminar esta notificación?')) {
        return;
    }

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/notificaciones/notificaciones.php',
            {
                method: 'DELETE',
                credentials: 'same-origin',

                headers: {
                    'Content-Type': 'application/json'
                },

                // Enviamos los identificadores que utiliza la tabla notifica.
                body: JSON.stringify({
                    id_traslado: item.id_traslado,
                    ci_chofer: item.ci_chofer,
                    matricula: item.matricula,
                    id_destino: item.id_destino,
                    ci_admin: item.ci_admin
                })
            }
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.error || 'No se pudo eliminar la notificación.'
            );
        }

        alert(data.mensaje || 'Notificación eliminada correctamente.');

        // Actualizar las tarjetas y el contador.
        await iniciarPollingNotificaciones();

    } catch (error) {

        console.error('Error al eliminar la notificación:', error);

        alert('No se pudo eliminar la notificación.');

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
