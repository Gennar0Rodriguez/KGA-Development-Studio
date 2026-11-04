
let todosLosTraslados = [];
let modoEdicion = false;
let trasladoEditando = null;

// Guarda los vehículos recibidos desde la base de datos.
let vehiculosDisponibles = [];

document.addEventListener('DOMContentLoaded', async () => {

    console.log('JS Traslados cargado correctamente');

    const modal = document.getElementById('modalNuevoTraslado');
    const btnAbrir = document.getElementById('btnAbrirModalTraslado');
    const btnCerrar = document.getElementById('btnCerrarModalTraslado');
    const btnCancelar = document.getElementById('btnCancelarModalTraslado');
    const formTraslado = document.getElementById('formNuevoTraslado');

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

            // Datos que se enviarán al servidor.
            const payload = {

                matricula: matriculaSeleccionada,
                paciente: document.getElementById('paciente')?.value,
                ci_chofer: document.getElementById('conductor')?.value,
                ci_enfermero: document.getElementById('enfermero')?.value,
                id_destino: document.getElementById('destino')?.value,
                fecha: document.getElementById('fecha')?.value,
                hora: document.getElementById('hora')?.value,
                observaciones: document.getElementById('observaciones')?.value,
                estado: document.getElementById('estado')?.value,
                oxigeno: document.getElementById('oxigeno')?.value,
                camilla: document.getElementById('camilla')?.value,
                prioridad: document.getElementById('prioridad')?.value

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

            const texto = inputBuscador.value.toLowerCase().trim();

            const filtrados = todosLosTraslados.filter(t =>
                String(t.matricula || '').toLowerCase().includes(texto)
            );

            renderizarTarjetasTraslado(filtrados);

        });

    }

});


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

        const listas = await res.json();

        // Guardamos los vehículos para utilizarlos en el autocompletado.
        vehiculosDisponibles = listas.vehiculos || [];

        // Cargar conductores.
        const selectChofer = document.getElementById('conductor');

        if (selectChofer && listas.choferes) {

            selectChofer.innerHTML = '<option value="">Seleccione un conductor</option>';

            listas.choferes.forEach(c => {

                const opcion = document.createElement('option');

                opcion.value = c.ci_chofer;
                opcion.textContent = c.nombre_chofer + ' ' + c.apellido_chofer;

                selectChofer.appendChild(opcion);

            });

        }

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

        renderizarTarjetasTraslado(todosLosTraslados);

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

        contenedor.innerHTML = '<p style="text-align:center; width:100%; grid-column: 1/-1;">No hay traslados registrados.</p>';

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
                <p><strong>Estado:</strong> ${item.estado || 'Programado'}</p>
                <p><strong>Hora salida:</strong> ${item.hora_salida || 'Pendiente'}</p>
                <p><strong>Hora llegada:</strong> ${item.hora_llegada || 'Pendiente'}</p>

            </div>

            <div class="info-extra">

                <h3>INFORMACIÓN EXTRA</h3>

                <p><strong>Conductor:</strong> ${item.chofer || 'No asignado'}</p>
                <p><strong>Enfermero:</strong> ${item.enfermero || 'No asignado'}</p>
                <p><strong>Paciente:</strong> ${item.nombre_paciente || 'N/A'}</p>
                <p><strong>Oxígeno:</strong> ${item.oxigeno ? 'Sí' : 'No'}</p>

            </div>

            <div class="acciones">

                <button class="btn-editar" onclick="editarTraslado('${item.id_traslado}', '${item.id_transportable}')">
                    Editar
                </button>

                <button class="btn-eliminar" onclick="eliminarTraslado('${item.id_traslado}')">
                    Eliminar
                </button>

            </div>

        `;

        contenedor.appendChild(card);

    });

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

    // Recuperamos la matrícula del traslado.
    document.getElementById('ambulancia').value = traslado.matricula || '';

    document.getElementById('paciente').value = traslado.nombre_paciente || '';

    document.getElementById('conductor').value = traslado.ci_chofer || '';

    document.getElementById('enfermero').value = traslado.ci_enfermero || '';

    document.getElementById('destino').value = traslado.id_destino || '';

    document.getElementById('fecha').value = traslado.fecha_traslado || '';

    document.getElementById('hora').value = traslado.hora_salida || '';

    document.getElementById('estado').value =
        (traslado.estado === 'aprobado') ? 'Finalizado' : 'En curso';

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
