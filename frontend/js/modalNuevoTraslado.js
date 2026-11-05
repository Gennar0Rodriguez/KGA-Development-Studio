let todosLosTraslados = [];
let modoEdicion = false;
let trasladoEditando = null;

// Listas que se cargan desde la API.
let choferesDisponibles = [];
let vehiculosDisponibles = [];

// Guarda el rol y los filtros de solicitudes.
let esAdministrador = false;
let filtroSolicitud = 'pendiente';
let textoBusqueda = '';


document.addEventListener('DOMContentLoaded', async () => {

    

    console.log('JS Traslados cargado correctamente');

    // Consultamos la sesión para saber qué opciones mostrar.
    const datosSesion = await obtenerSesion();

    if (!datosSesion || !datosSesion.autenticado) {
        window.location.href = 'index.html';
        return;
    }

    const usuarioActual = datosSesion.usuario || {};

    esAdministrador = (usuarioActual.cargo || '').toLowerCase() === 'administrador';

    const modal = document.getElementById('modalNuevoTraslado');
    const btnAbrir = document.getElementById('btnAbrirModalTraslado');
    const btnCerrar = document.getElementById('btnCerrarModalTraslado');
    const btnCancelar = document.getElementById('btnCancelarModalTraslado');
    const formTraslado = document.getElementById('formNuevoTraslado');

    // Creamos los botones para cambiar entre solicitudes.
    configurarFiltrosSolicitudes();

    // 1. Manejo de apertura del modal.
    if (btnAbrir && modal) {

        btnAbrir.addEventListener('click', async (e) => {

            e.preventDefault();

            modal.style.display = 'flex';

            // Actualizamos los vehículos cada vez que abrimos el formulario.
            await cargarOpcionesFormulario();

        });

    }

    // Función para cerrar y reiniciar el formulario.
    const cerrarModal = () => {

        if (modal) modal.style.display = 'none';
        if (formTraslado) formTraslado.reset();

        // Limpiamos las sugerencias del buscador.
        const sugerencias = document.getElementById('sugerenciasAmbulancia');

        if (sugerencias) {
            sugerencias.innerHTML = '';
        }

        // Volvemos al modo de creación de un traslado.
        modoEdicion = false;
        trasladoEditando = null;

        const tituloModal = document.querySelector('.modal-traslado-header h2');

        if (tituloModal) {
            tituloModal.textContent = 'Nuevo Traslado';
        }

    };

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    // 2. Cargar opciones iniciales y traslados.
    await cargarOpcionesFormulario();
    await cargarTraslados();

    // 3. Configurar el buscador de ambulancias.
    buscarAmbulancias();
    buscarChoferes();

    // 4. Envío del formulario.
    if (formTraslado) {

        formTraslado.addEventListener('submit', async (e) => {

            e.preventDefault();

            const matriculaSeleccionada = document.getElementById('ambulancia').value;

            // Comprobamos que la matrícula corresponda a un vehículo existente.
            const vehiculoValido = vehiculosDisponibles.some(vehiculo =>
                vehiculo.matricula.toLowerCase() === matriculaSeleccionada.toLowerCase()
            );

            if (!vehiculoValido) {
                alert('Debe seleccionar un vehículo de la lista de sugerencias.');
                return;
            }

            // Comprobamos que el chofer seleccionado exista en la lista.
            const ciChoferSeleccionado = document.getElementById('conductor').value;

            const choferValido = choferesDisponibles.some(chofer =>
                String(chofer.ci_chofer) === ciChoferSeleccionado
            );

            if (!choferValido) {
                alert('Debe seleccionar un conductor de la lista de sugerencias.');
                return;
            }

            // Datos que se enviarán al servidor.
            const payload = {

                matricula: matriculaSeleccionada,
                paciente: document.getElementById('paciente')?.value,
                ci_chofer: document.getElementById('conductor')?.value,
                ci_enfermero: document.getElementById('enfermero')?.value,
                id_destino: document.getElementById('destino')?.value,
                fecha: document.getElementById('fecha')?.value,
                hora: document.getElementById('hora')?.value,

                // Enviamos las observaciones para guardarlas en MySQL.
                observaciones: document.getElementById('observaciones')?.value,

                estado: document.getElementById('estado')?.value,
                oxigeno: document.getElementById('oxigeno')?.value,
                camilla: document.getElementById('camilla')?.value

            };

            // Si estamos editando, enviamos también los identificadores.
            if (modoEdicion) {

                payload.id_traslado = trasladoEditando.id_traslado;
                payload.id_transportable = trasladoEditando.id_transportable;

            }

            const metodoHttp = modoEdicion ? 'PUT' : 'POST';

            console.log('Enviando datos de traslado:', payload);

            try {

                const response = await fetch('/kgade/KGA-Development-Studio/API/traslados/traslados.php', {

                    method: metodoHttp,

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify(payload)

                });

                const res = await response.json();

                if (response.ok) {

                    alert(res.mensaje || 'Traslado guardado correctamente');

                    cerrarModal();

                    await cargarTraslados();

                } else {

                    alert(res.error || 'Error al guardar el traslado');

                }

            } catch (error) {

                console.error('Error al guardar traslado:', error);

                alert('Ocurrió un error en la conexión.');

            }

        });

    }

    // 5. Buscador general de traslados por matrícula.
    const inputBuscador = document.querySelector('.btn-principal .buscador');

    if (inputBuscador) {

        inputBuscador.addEventListener('input', () => {

            textoBusqueda = inputBuscador.value.toLowerCase().trim();

            filtrarTraslados();

        });

    }

});


// ======================================================
// OBTENER SESIÓN DEL USUARIO
// ======================================================

async function obtenerSesion() {

    try {

        const response = await fetch('/kgade/KGA-Development-Studio/API/sesion.php');

        if (!response.ok) return null;

        return await response.json();

    } catch (error) {

        console.error('Error al obtener sesión:', error);

        return null;

    }

}


// ======================================================
// CONFIGURAR FILTROS DE SOLICITUDES
// ======================================================

function configurarFiltrosSolicitudes() {

    const contenedor = document.querySelector('.contenedor-ambulancias');

    if (!contenedor) return;

    const filtros = document.createElement('section');

    filtros.className = 'filtros-solicitudes';

    filtros.innerHTML = `
        <button class="btn-filtro activo" data-estado="pendiente">
            Pendientes
        </button>

        <button class="btn-filtro" data-estado="aprobado">
            ${esAdministrador ? 'Confirmados' : 'Aprobadas'}
        </button>

        <button class="btn-filtro" data-estado="rechazado">
            Rechazados
        </button>
    `;

    // Insertamos los filtros antes de las tarjetas.
    contenedor.parentNode.insertBefore(filtros, contenedor);

    filtros.querySelectorAll('.btn-filtro').forEach(boton => {

        boton.addEventListener('click', () => {

            filtroSolicitud = boton.dataset.estado;

            // Actualizamos cuál botón está seleccionado.
            filtros.querySelectorAll('.btn-filtro').forEach(b => {
                b.classList.remove('activo');
            });

            boton.classList.add('activo');

            filtrarTraslados();

        });

    });

}


// ======================================================
// FILTRAR TRASLADOS
// ======================================================

function filtrarTraslados() {

    const filtrados = todosLosTraslados.filter(traslado => {

        const coincideEstado = traslado.estado_solicitud === filtroSolicitud;

        const coincideBusqueda = String(traslado.matricula || '')
            .toLowerCase()
            .includes(textoBusqueda);

        return coincideEstado && coincideBusqueda;

    });

    renderizarTarjetasTraslado(filtrados);

}


// ======================================================
// CARGAR OPCIONES DEL FORMULARIO
// ======================================================

// ======================================================
// CARGAR OPCIONES DEL FORMULARIO
// ======================================================

async function cargarOpcionesFormulario() {

    try {

        const res = await fetch('/kgade/KGA-Development-Studio/API/traslados/traslados.php?action=auxiliares');

        if (!res.ok) {

            console.error('No se pudieron cargar las opciones del formulario.');

            return;

        }

        // Convertimos la respuesta de PHP en un objeto JavaScript.
        const listas = await res.json();

        // Guardamos los datos para utilizarlos en los autocompletados.
        choferesDisponibles = listas.choferes || [];
        vehiculosDisponibles = listas.vehiculos || [];

        // Cargar enfermeros.
        const selectEnfermero = document.getElementById('enfermero');

        if (selectEnfermero && listas.enfermeros) {

            selectEnfermero.innerHTML = '<option value="">Seleccione un enfermero</option>';

            listas.enfermeros.forEach(e => {

                const opcion = document.createElement('option');

                opcion.value = e.ci_enfermero;
                opcion.textContent = e.nombre_enfermero + ' ' + e.apellido_enfermero;

                selectEnfermero.appendChild(opcion);

            });

        }

        // Transformar el campo de destino en un selector.
        const inputDestino = document.getElementById('destino');

        if (inputDestino && listas.destinos) {

            const selectDestino = document.createElement('select');

            selectDestino.id = 'destino';
            selectDestino.required = true;

            selectDestino.innerHTML = '<option value="">Seleccione un destino</option>';

            listas.destinos.forEach(d => {

                const opcion = document.createElement('option');

                opcion.value = d.id_destino;
                opcion.textContent = d.nombre_destino;

                selectDestino.appendChild(opcion);

            });

            inputDestino.replaceWith(selectDestino);

        }

        console.log('Opciones cargadas correctamente:', {
            choferes: choferesDisponibles.length,
            vehiculos: vehiculosDisponibles.lengthg
        });

    } catch (error) {

        console.error('Error al cargar opciones:', error);

    }

}

// ======================================================
// BUSCADOR AUTOMÁTICO DE VEHÍCULOS
// ======================================================

function buscarAmbulancias() {

    const input = document.getElementById('ambulancia');
    const sugerencias = document.getElementById('sugerenciasAmbulancia');

    if (!input || !sugerencias) return;

    // Detectamos cada vez que el usuario escribe.
    input.addEventListener('input', () => {

        const texto = input.value.toLowerCase().trim();

        // Limpiamos los resultados anteriores.
        sugerencias.innerHTML = '';

        // Si el campo está vacío, no mostramos sugerencias.
        if (texto === '') return;

        // Buscamos por matrícula o modelo.
        const resultados = vehiculosDisponibles.filter(vehiculo =>

            vehiculo.matricula.toLowerCase().includes(texto) ||
            vehiculo.modelo.toLowerCase().includes(texto)

        );

        // Mostramos cada vehículo encontrado.
        resultados.forEach(vehiculo => {

            const opcion = document.createElement('button');

            opcion.type = 'button';
            opcion.className = 'opcion-ambulancia';

            opcion.textContent = vehiculo.matricula + ' - ' + vehiculo.modelo;

            // Al seleccionar, guardamos únicamente la matrícula.
            opcion.addEventListener('click', () => {

                input.value = vehiculo.matricula;

                sugerencias.innerHTML = '';

            });

            sugerencias.appendChild(opcion);

        });

        // Mensaje cuando no existen coincidencias.
        if (resultados.length === 0) {

            const mensaje = document.createElement('p');

            mensaje.textContent = 'No se encontraron vehículos.';
            mensaje.className = 'sin-resultados-ambulancia';

            sugerencias.appendChild(mensaje);

        }

    });

    // Si el usuario hace clic fuera del buscador, cerramos las sugerencias.
    document.addEventListener('click', (e) => {

        if (!input.contains(e.target) && !sugerencias.contains(e.target)) {

            sugerencias.innerHTML = '';

        }

    });

}

// ======================================================
// BUSCADOR AUTOMÁTICO DE CHOFERES
// ======================================================

function buscarChoferes() {

    const input = document.getElementById('buscadorConductor');
    const campoCI = document.getElementById('conductor');
    const sugerencias = document.getElementById('sugerenciasConductor');

    if (!input || !campoCI || !sugerencias) return;

    // Detectamos cada vez que el usuario escribe.
    input.addEventListener('input', () => {

        const texto = input.value.toLowerCase().trim();

        // Si modifica el texto, deja de haber un chofer seleccionado.
        campoCI.value = '';

        // Limpiamos los resultados anteriores.
        sugerencias.innerHTML = '';

        if (texto === '') return;

        // Buscamos coincidencias por nombre, apellido o CI.
        const resultados = choferesDisponibles.filter(chofer => {

            const nombreCompleto = (
                chofer.nombre_chofer + ' ' + chofer.apellido_chofer
            ).toLowerCase();

            const ci = String(chofer.ci_chofer);

            return (
                nombreCompleto.includes(texto) ||
                ci.includes(texto)
            );

        });

        // Mostramos cada chofer encontrado.
        resultados.forEach(chofer => {

            const opcion = document.createElement('button');

            opcion.type = 'button';
            opcion.className = 'opcion-ambulancia';

            opcion.textContent =
                chofer.nombre_chofer + ' ' +
                chofer.apellido_chofer +
                ' - CI: ' + chofer.ci_chofer;

            // Al seleccionar, guardamos su CI.
            opcion.addEventListener('click', () => {

                input.value =
                    chofer.nombre_chofer + ' ' +
                    chofer.apellido_chofer +
                    ' - CI: ' + chofer.ci_chofer;

                campoCI.value = chofer.ci_chofer;

                sugerencias.innerHTML = '';

            });

            sugerencias.appendChild(opcion);

        });

        // Mensaje cuando no existen coincidencias.
        if (resultados.length === 0) {

            const mensaje = document.createElement('p');

            mensaje.textContent = 'No se encontraron choferes.';
            mensaje.className = 'sin-resultados-ambulancia';

            sugerencias.appendChild(mensaje);

        }

    });

    // Cerramos las sugerencias al hacer clic fuera.
    document.addEventListener('click', (e) => {

        if (
            !input.contains(e.target) &&
            !sugerencias.contains(e.target)
        ) {
            sugerencias.innerHTML = '';
        }

    });

}


// ======================================================
// CARGAR TRASLADOS DESDE LA BASE DE DATOS
// ======================================================

async function cargarTraslados() {

    try {

        const response = await fetch('/kgade/KGA-Development-Studio/API/traslados/traslados.php');

        if (!response.ok) {

            console.error('No se pudieron cargar los traslados.');

            return;

        }

        todosLosTraslados = await response.json();

        filtrarTraslados();

    } catch (error) {

        console.error('Error al cargar traslados:', error);

    }

}


// ======================================================
// MOSTRAR TARJETAS DE TRASLADOS
// ======================================================

function renderizarTarjetasTraslado(lista) {

    const contenedor = document.querySelector('.contenedor-ambulancias');

    if (!contenedor) return;

    contenedor.innerHTML = '';

    if (!Array.isArray(lista) || lista.length === 0) {

        contenedor.innerHTML = '<p style="text-align:center; width:100%; grid-column: 1/-1;">No hay solicitudes en esta sección.</p>';

        return;

    }

    lista.forEach(item => {

        const card = document.createElement('div');

        card.className = 'card-ambulancia';

        card.innerHTML = `

            <h3>Matrícula: ${item.matricula}</h3>

            <img src="../img/ambulanciaIMG.png" alt="Ambulancia">

            <div class="info-principal">

                <p><strong>Tipo:</strong> Ambulancia</p>
                <p><strong>Destino:</strong> ${item.nombre_destino || 'No asignado'}</p>
                <p><strong>Estado:</strong> ${item.estado_traslado || 'Pendiente'}</p>
                <p><strong>Solicitud:</strong> ${item.estado_solicitud || 'Sin estado'}</p>
                <p><strong>Hora salida:</strong> ${item.hora_salida || 'Pendiente'}</p>
                <p><strong>Hora llegada:</strong> ${item.hora_llegada || 'Pendiente'}</p>

            </div>

            <div class="info-extra">

                <h3>INFORMACIÓN EXTRA</h3>

                <p><strong>Conductor:</strong> ${item.chofer || 'No asignado'}</p>
                <p><strong>Enfermero:</strong> ${item.enfermero || 'No asignado'}</p>
                <p><strong>Paciente:</strong> ${item.nombre_paciente || 'N/A'}</p>

                <!-- Mostramos las observaciones guardadas en la base de datos. -->
                <p><strong>Observaciones:</strong> ${item.observaciones || 'Sin observaciones'}</p>

                <p><strong>Oxígeno:</strong> ${item.oxigeno ? 'Sí' : 'No'}</p>

            </div>

    ${esAdministrador ? `

        <div class="acciones">

            <!-- El administrador siempre puede editar. -->
            <button class="btn-editar"
                onclick="editarTraslado('${item.id_traslado}', '${item.id_transportable}')">
                Editar
            </button>

            <!-- Confirmar y rechazar solamente aparecen si está pendiente. -->
            ${item.estado_solicitud === 'pendiente' ? `

                <button class="btn-aprobar"
                    onclick="resolverSolicitud('${item.id_traslado}', 'aprobado')">
                    Confirmar
                </button>

                <button class="btn-rechazar"
                    onclick="resolverSolicitud('${item.id_traslado}', 'rechazado')">
                    Rechazar
                </button>

            ` : ''}

            <!-- Eliminar solamente aparece si ya fue aprobado. -->
            ${item.estado_solicitud === 'aprobado' ? `

                <button class="btn-eliminar"
                    onclick="eliminarTraslado('${item.id_traslado}')">
                    Eliminar
                </button>

            ` : ''}

        </div>

    ` : ''}

        `;

        contenedor.appendChild(card);

    });

}


// ======================================================
// RESOLVER UNA SOLICITUD
// ======================================================

async function resolverSolicitud(idTraslado, estadoSolicitud) {

    const accion = estadoSolicitud === 'aprobado'
        ? 'confirmar'
        : 'rechazar';

    if (!confirm(`¿Desea ${accion} esta solicitud?`)) return;

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/traslados/traslados.php?action=resolver',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify({
                    id_traslado: idTraslado,
                    estado_solicitud: estadoSolicitud
                })
            }
        );

        const res = await response.json();

        if (response.ok) {

            alert(res.mensaje);

            // Actualizamos las tarjetas para reflejar el nuevo estado.
            await cargarTraslados();

        } else {

            alert(res.error || 'No se pudo actualizar la solicitud');

        }

    } catch (error) {

        console.error('Error al resolver solicitud:', error);

        alert('Ocurrió un error en la conexión.');

    }

}


// ======================================================
// EDITAR UN TRASLADO
// ======================================================

function editarTraslado(idTraslado, idTransportable) {

    const traslado = todosLosTraslados.find(t =>

        String(t.id_traslado) === idTraslado &&
        String(t.id_transportable) === idTransportable

    );

    if (!traslado) return;

    modoEdicion = true;

    trasladoEditando = traslado;

    // Recuperamos los datos del traslado.
    document.getElementById('ambulancia').value = traslado.matricula || '';

    document.getElementById('paciente').value = traslado.nombre_paciente || '';

    const chofer = choferesDisponibles.find(c =>
        String(c.ci_chofer) === String(traslado.ci_chofer)
    );

    document.getElementById('conductor').value = traslado.ci_chofer || '';

    document.getElementById('buscadorConductor').value = chofer
        ? `${chofer.nombre_chofer} ${chofer.apellido_chofer} - CI: ${chofer.ci_chofer}`
        : '';

    document.getElementById('enfermero').value = traslado.ci_enfermero || '';

    document.getElementById('destino').value = traslado.id_destino || '';

    document.getElementById('fecha').value = traslado.fecha_traslado || '';

    document.getElementById('hora').value = traslado.hora_salida || '';

    // Recuperamos las observaciones para que aparezcan al editar.
    document.getElementById('observaciones').value = traslado.observaciones || '';

    document.getElementById('estado').value =
        (traslado.estado_traslado === 'finalizado') ? 'Finalizado' : 'En curso';
        
    document.getElementById('oxigeno').value =
        (Number(traslado.oxigeno) === 1) ? 'Si' : 'No';

    // Limpiamos cualquier sugerencia anterior.
    const sugerencias = document.getElementById('sugerenciasAmbulancia');

    if (sugerencias) {
        sugerencias.innerHTML = '';
    }

    const tituloModal = document.querySelector('.modal-traslado-header h2');

    if (tituloModal) {
        tituloModal.textContent = 'Editar Traslado';
    }

    document.getElementById('modalNuevoTraslado').style.display = 'flex';

}


// ======================================================
// ELIMINAR UN TRASLADO
// ======================================================

async function eliminarTraslado(id) {

    if (!confirm(`¿Desea eliminar el traslado ${id}?`)) return;

    try {

        const response = await fetch(
            `/kgade/KGA-Development-Studio/API/traslados/traslados.php?id=${id}`,
            {
                method: 'DELETE'
            }
        );

        const res = await response.json();

        if (response.ok) {

            alert(res.mensaje);

            await cargarTraslados();

        } else {

            alert(res.error || 'No se pudo eliminar');

        }

    } catch (error) {

        console.error('Error al eliminar:', error);

    }

}
