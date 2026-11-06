let todosLosTraslados = [];
let modoEdicion = false;
let trasladoEditando = null;

let choferesDisponibles = [];
let vehiculosDisponibles = [];

let esAdministrador = false;
let filtroSolicitud = 'pendiente';
let textoBusqueda = '';


// ======================================================
// INICIO
// ======================================================

document.addEventListener('DOMContentLoaded', async () => {

    console.log('JS Traslados cargado correctamente');

    const datosSesion = await obtenerSesion();

    if (!datosSesion || !datosSesion.autenticado) {
        window.location.href = 'index.html';
        return;
    }

    const usuarioActual = datosSesion.usuario || {};

    esAdministrador =
        (usuarioActual.cargo || '').toLowerCase() === 'administrador';

    const modal = document.getElementById('modalNuevoTraslado');
    const formulario = document.getElementById('formNuevoTraslado');

    const btnAbrir = document.getElementById('btnAbrirModalTraslado');
    const btnCerrar = document.getElementById('btnCerrarModalTraslado');
    const btnCancelar = document.getElementById('btnCancelarModalTraslado');

    const selectorTipo = document.getElementById('tipoTransportable');

    configurarFiltrosSolicitudes();
    configurarTipoTransportable();

    // Abrir modal para crear un traslado.
    if (btnAbrir && modal) {

        btnAbrir.addEventListener('click', async (e) => {

            e.preventDefault();

            modoEdicion = false;
            trasladoEditando = null;

            formulario.reset();

            actualizarCamposTransportable();

            const titulo = document.querySelector(
                '.modal-traslado-header h2'
            );

            if (titulo) {
                titulo.textContent = 'Nuevo Traslado';
            }

            modal.style.display = 'flex';

            await cargarOpcionesFormulario();

        });

    }

    // Cerrar y limpiar el formulario.
    const cerrarModal = () => {

        if (modal) {
            modal.style.display = 'none';
        }

        if (formulario) {
            formulario.reset();
        }

        document.getElementById('sugerenciasAmbulancia').innerHTML = '';
        document.getElementById('sugerenciasConductor').innerHTML = '';

        modoEdicion = false;
        trasladoEditando = null;

        actualizarCamposTransportable();

        const titulo = document.querySelector(
            '.modal-traslado-header h2'
        );

        if (titulo) {
            titulo.textContent = 'Nuevo Traslado';
        }

    };

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    // Cargar datos auxiliares y traslados.
    await cargarOpcionesFormulario();

    buscarAmbulancias();
    buscarChoferes();

    await cargarTraslados();

    // Envío del formulario.
    if (formulario) {

        formulario.addEventListener('submit', async (e) => {

            e.preventDefault();

            await guardarTraslado(cerrarModal);

        });

    }

    // Buscador general.
    const inputBuscador = document.querySelector(
        '.btn-principal .buscador'
    );

    if (inputBuscador) {

        inputBuscador.addEventListener('input', () => {

            textoBusqueda = inputBuscador.value.toLowerCase().trim();

            filtrarTraslados();

        });

    }

});


// ======================================================
// OBTENER SESIÓN
// ======================================================

async function obtenerSesion() {

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/sesion.php'
        );

        if (!response.ok) {
            return null;
        }

        return await response.json();

    } catch (error) {

        console.error('Error al obtener sesión:', error);

        return null;

    }

}


// ======================================================
// CAMBIAR ENTRE PACIENTE Y ÓRGANO
// ======================================================

function configurarTipoTransportable() {

    const selector = document.getElementById('tipoTransportable');

    if (!selector) return;

    selector.addEventListener('change', actualizarCamposTransportable);

    actualizarCamposTransportable();

}


function actualizarCamposTransportable() {

    const tipo = document.getElementById('tipoTransportable')?.value;

    const camposPaciente = document.getElementById('camposPaciente');
    const camposOrgano = document.getElementById('camposOrgano');

    const paciente = document.getElementById('paciente');
    const oxigeno = document.getElementById('oxigeno');

    const nombreElemento = document.getElementById('nombreElemento');
    const descripcionElemento = document.getElementById(
        'descripcionElemento'
    );

    const esPaciente = tipo === 'paciente';

    if (camposPaciente) {
        camposPaciente.style.display = esPaciente ? 'block' : 'none';
    }

    if (camposOrgano) {
        camposOrgano.style.display = esPaciente ? 'none' : 'block';
    }

    // Los campos ocultos no deben ser obligatorios.
    if (paciente) {
        paciente.required = esPaciente;
    }

    if (oxigeno) {
        oxigeno.required = esPaciente;
    }

    if (nombreElemento) {
        nombreElemento.required = !esPaciente;
    }

    if (descripcionElemento) {
        descripcionElemento.required = !esPaciente;
    }

}


// ======================================================
// GUARDAR O ACTUALIZAR TRASLADO
// ======================================================

async function guardarTraslado(cerrarModal) {

    const matriculaSeleccionada = document.getElementById(
        'ambulancia'
    ).value.trim();

    const vehiculoValido = vehiculosDisponibles.some(vehiculo =>
        String(vehiculo.matricula).toLowerCase() ===
        matriculaSeleccionada.toLowerCase()
    );

    if (!vehiculoValido) {

        alert('Debe seleccionar un vehículo de la lista de sugerencias.');

        return;

    }

    const ciChoferSeleccionado = document.getElementById(
        'conductor'
    ).value;

    const choferValido = choferesDisponibles.some(chofer =>
        String(chofer.ci_chofer) === String(ciChoferSeleccionado)
    );

    if (!choferValido) {

        alert('Debe seleccionar un conductor de la lista de sugerencias.');

        return;

    }

    const tipoTransportable = document.getElementById(
        'tipoTransportable'
    ).value;

    const payload = {

        // Datos generales del traslado.
        matricula: matriculaSeleccionada,

        ci_chofer: ciChoferSeleccionado,

        ci_enfermero: document.getElementById('enfermero').value,

        id_destino: document.getElementById('destino').value,

        fecha: document.getElementById('fecha').value,

        hora: document.getElementById('hora').value,

        observaciones: document.getElementById('observaciones').value,

        estado: document.getElementById('estado').value,

        // Tipo de elemento que se transporta.
        tipo_transportable: tipoTransportable,

        // Datos del paciente.
        paciente: tipoTransportable === 'paciente'
            ? document.getElementById('paciente').value.trim()
            : '',

        oxigeno: tipoTransportable === 'paciente'
            ? document.getElementById('oxigeno').value
            : 'No',

        // Datos del órgano.
        nombre_elemento: tipoTransportable === 'organo'
            ? document.getElementById('nombreElemento').value.trim()
            : '',

        descripcion_elemento: tipoTransportable === 'organo'
            ? document.getElementById('descripcionElemento').value.trim()
            : ''

    };

    if (!payload.ci_enfermero) {

        alert('Debe seleccionar un enfermero.');

        return;

    }

    if (!payload.id_destino) {

        alert('Debe seleccionar un destino.');

        return;

    }

    if (modoEdicion && trasladoEditando) {

        payload.id_traslado = trasladoEditando.id_traslado;

        payload.id_transportable = trasladoEditando.id_transportable;

    }

    const metodoHttp = modoEdicion ? 'PUT' : 'POST';

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/traslados/traslados.php',
            {
                method: metodoHttp,

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify(payload)
            }
        );

        const resultado = await response.json();

        if (!response.ok) {

            alert(resultado.error || 'No se pudo guardar el traslado.');

            return;

        }

        alert(resultado.mensaje || 'Traslado guardado correctamente.');

        cerrarModal();

        await cargarTraslados();

    } catch (error) {

        console.error('Error al guardar el traslado:', error);

        alert('Ocurrió un error en la conexión.');

    }

}


// ======================================================
// CONFIGURAR FILTROS DE SOLICITUDES
// ======================================================

function configurarFiltrosSolicitudes() {

    const contenedor = document.querySelector('.contenedor-ambulancias');

    if (!contenedor || !contenedor.parentNode) return;

    // Evitamos crear los filtros más de una vez.
    if (document.querySelector('.filtros-solicitudes')) return;

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

    contenedor.parentNode.insertBefore(filtros, contenedor);

    filtros.querySelectorAll('.btn-filtro').forEach(boton => {

        boton.addEventListener('click', () => {

            filtroSolicitud = boton.dataset.estado;

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

        const coincideEstado =
            traslado.estado_solicitud === filtroSolicitud;

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

async function cargarOpcionesFormulario() {

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/traslados/traslados.php?action=auxiliares'
        );

        if (!response.ok) {

            console.error('No se pudieron cargar las opciones del formulario.');

            return;

        }

        const listas = await response.json();

        choferesDisponibles = listas.choferes || [];
        vehiculosDisponibles = listas.vehiculos || [];

        // Enfermeros.
        const selectEnfermero = document.getElementById('enfermero');

        if (selectEnfermero) {

            selectEnfermero.innerHTML =
                '<option value="">Seleccione un enfermero</option>';

            (listas.enfermeros || []).forEach(enfermero => {

                const opcion = document.createElement('option');

                opcion.value = enfermero.ci_enfermero;

                opcion.textContent =
                    enfermero.nombre_enfermero + ' ' +
                    enfermero.apellido_enfermero;

                selectEnfermero.appendChild(opcion);

            });

        }

        // Destinos.
        const selectDestino = document.getElementById('destino');

        if (selectDestino) {

            selectDestino.innerHTML =
                '<option value="">Seleccione un destino</option>';

            (listas.destinos || []).forEach(destino => {

                const opcion = document.createElement('option');

                opcion.value = destino.id_destino;

                opcion.textContent = destino.nombre_destino;

                selectDestino.appendChild(opcion);

            });

        }

        console.log('Opciones cargadas correctamente:', {
            choferes: choferesDisponibles.length,
            vehiculos: vehiculosDisponibles.length
        });

    } catch (error) {

        console.error('Error al cargar opciones:', error);

    }

}


// ======================================================
// BUSCADOR DE VEHÍCULOS
// ======================================================

function buscarAmbulancias() {

    const input = document.getElementById('ambulancia');

    const sugerencias = document.getElementById(
        'sugerenciasAmbulancia'
    );

    if (!input || !sugerencias) return;

    input.addEventListener('input', () => {

        const texto = input.value.toLowerCase().trim();

        sugerencias.innerHTML = '';

        if (texto === '') return;

        const resultados = vehiculosDisponibles.filter(vehiculo => {

            const matricula = String(vehiculo.matricula || '').toLowerCase();

            const modelo = String(vehiculo.modelo || '').toLowerCase();

            return matricula.includes(texto) || modelo.includes(texto);

        });

        resultados.forEach(vehiculo => {

            const opcion = document.createElement('button');

            opcion.type = 'button';

            opcion.className = 'opcion-ambulancia';

            opcion.textContent =
                vehiculo.matricula + ' - ' + vehiculo.modelo;

            opcion.addEventListener('click', () => {

                input.value = vehiculo.matricula;

                sugerencias.innerHTML = '';

            });

            sugerencias.appendChild(opcion);

        });

        if (resultados.length === 0) {

            const mensaje = document.createElement('p');

            mensaje.textContent = 'No se encontraron vehículos.';

            mensaje.className = 'sin-resultados-ambulancia';

            sugerencias.appendChild(mensaje);

        }

    });

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
// BUSCADOR DE CHOFERES
// ======================================================

function buscarChoferes() {

    const input = document.getElementById('buscadorConductor');

    const campoCI = document.getElementById('conductor');

    const sugerencias = document.getElementById(
        'sugerenciasConductor'
    );

    if (!input || !campoCI || !sugerencias) return;

    input.addEventListener('input', () => {

        const texto = input.value.toLowerCase().trim();

        // Si cambia el texto, se invalida el chofer seleccionado.
        campoCI.value = '';

        sugerencias.innerHTML = '';

        if (texto === '') return;

        const resultados = choferesDisponibles.filter(chofer => {

            const nombreCompleto = (
                chofer.nombre_chofer + ' ' +
                chofer.apellido_chofer
            ).toLowerCase();

            const ci = String(chofer.ci_chofer);

            return nombreCompleto.includes(texto) || ci.includes(texto);

        });

        resultados.forEach(chofer => {

            const opcion = document.createElement('button');

            opcion.type = 'button';

            opcion.className = 'opcion-ambulancia';

            opcion.textContent =
                chofer.nombre_chofer + ' ' +
                chofer.apellido_chofer +
                ' - CI: ' + chofer.ci_chofer;

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

        if (resultados.length === 0) {

            const mensaje = document.createElement('p');

            mensaje.textContent = 'No se encontraron choferes.';

            mensaje.className = 'sin-resultados-ambulancia';

            sugerencias.appendChild(mensaje);

        }

    });

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

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/traslados/traslados.php'
        );

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

        contenedor.innerHTML = `
            <p style="text-align:center; width:100%; grid-column:1/-1;">
                No hay solicitudes en esta sección.
            </p>
        `;

        return;

    }

    lista.forEach(item => {

        const card = document.createElement('div');

        card.className = 'card-ambulancia';

        const esOrgano = item.tipo_transportable === 'organo';

        const descripcionTransportable = esOrgano
            ? `
                <p><strong>Órgano:</strong> ${item.nombre_elemento || 'Sin nombre'}</p>
                <p><strong>Descripción:</strong> ${item.descripcion_elemento || 'Sin descripción'}</p>
            `
            : `
                <p><strong>Paciente:</strong> ${item.nombre_paciente || 'Sin nombre'}</p>
                <p><strong>Oxígeno:</strong> ${Number(item.oxigeno) === 1 ? 'Sí' : 'No'}</p>
            `;

        const estado = item.estado_traslado || 'Pendiente';

        card.innerHTML = `

            <h3>Matrícula: ${item.matricula || 'Sin matrícula'}</h3>

            <img src="../img/ambulanciaIMG.png" alt="Vehículo">

            <div class="info-principal">

                <p><strong>Tipo:</strong> Traslado</p>

                <p><strong>Transportable:</strong>
                    ${esOrgano ? 'Órgano' : 'Paciente'}
                </p>

                <p><strong>Destino:</strong>
                    ${item.nombre_destino || 'No asignado'}
                </p>

                <p><strong>Estado:</strong> ${estado}</p>

                <p><strong>Solicitud:</strong>
                    ${item.estado_solicitud || 'Sin estado'}
                </p>

                <p><strong>Hora salida:</strong>
                    ${item.hora_salida || 'Pendiente'}
                </p>

                <p><strong>Hora llegada:</strong>
                    ${item.hora_llegada || 'Pendiente'}
                </p>

            </div>

            <div class="info-extra">

                <h3>INFORMACIÓN EXTRA</h3>

                <p><strong>Conductor:</strong>
                    ${item.chofer || 'No asignado'}
                </p>

                <p><strong>Enfermero:</strong>
                    ${item.enfermero || 'No asignado'}
                </p>

                ${descripcionTransportable}

                <p><strong>Observaciones:</strong>
                    ${item.observaciones || 'Sin observaciones'}
                </p>

            </div>

            ${esAdministrador ? `

                <div class="acciones">

                    <button
                        class="btn-editar"
                        onclick="editarTraslado('${item.id_traslado}', '${item.id_transportable}')">
                        Editar
                    </button>

                    ${item.estado_solicitud === 'pendiente' ? `

                        <button
                            class="btn-aprobar"
                            onclick="resolverSolicitud('${item.id_traslado}', 'aprobado')">
                            Confirmar
                        </button>

                        <button
                            class="btn-rechazar"
                            onclick="resolverSolicitud('${item.id_traslado}', 'rechazado')">
                            Rechazar
                        </button>

                    ` : ''}

                    ${item.estado_solicitud === 'aprobado' ? `

                        <button
                            class="btn-eliminar"
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
// RESOLVER SOLICITUD
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

        const resultado = await response.json();

        if (!response.ok) {

            alert(resultado.error || 'No se pudo actualizar la solicitud.');

            return;

        }

        alert(resultado.mensaje || 'Solicitud actualizada.');

        await cargarTraslados();

    } catch (error) {

        console.error('Error al resolver solicitud:', error);

        alert('Ocurrió un error en la conexión.');

    }

}


// ======================================================
// EDITAR TRASLADO
// ======================================================

async function editarTraslado(idTraslado, idTransportable) {

    const traslado = todosLosTraslados.find(item =>

        String(item.id_traslado) === String(idTraslado) &&
        String(item.id_transportable) === String(idTransportable)

    );

    if (!traslado) {

        alert('No se encontró el traslado.');

        return;

    }

    modoEdicion = true;

    trasladoEditando = traslado;

    // Cargamos nuevamente las opciones para asegurar que los
    // select tengan sus valores antes de asignarlos.
    await cargarOpcionesFormulario();

    const tipo = traslado.tipo_transportable === 'organo'
        ? 'organo'
        : 'paciente';

    document.getElementById('tipoTransportable').value = tipo;

    actualizarCamposTransportable();

    document.getElementById('ambulancia').value =
        traslado.matricula || '';

    document.getElementById('paciente').value =
        traslado.nombre_paciente || '';

    document.getElementById('oxigeno').value =
        Number(traslado.oxigeno) === 1 ? 'Si' : 'No';

    document.getElementById('nombreElemento').value =
        traslado.nombre_elemento || '';

    document.getElementById('descripcionElemento').value =
        traslado.descripcion_elemento || '';

    const chofer = choferesDisponibles.find(item =>
        String(item.ci_chofer) === String(traslado.ci_chofer)
    );

    document.getElementById('conductor').value =
        traslado.ci_chofer || '';

    document.getElementById('buscadorConductor').value = chofer
        ? `${chofer.nombre_chofer} ${chofer.apellido_chofer} - CI: ${chofer.ci_chofer}`
        : '';

    document.getElementById('enfermero').value =
        traslado.ci_enfermero || '';

    document.getElementById('destino').value =
        traslado.id_destino || '';

    document.getElementById('fecha').value =
        traslado.fecha_traslado || '';

    document.getElementById('hora').value =
        traslado.hora_salida || '';

    document.getElementById('observaciones').value =
        traslado.observaciones || '';

    const estado = traslado.estado_traslado;

    document.getElementById('estado').value =
        estado === 'finalizado'
            ? 'Finalizado'
            : estado === 'en proceso'
                ? 'En curso'
                : 'Programado';

    document.getElementById('sugerenciasAmbulancia').innerHTML = '';

    document.getElementById('sugerenciasConductor').innerHTML = '';

    const titulo = document.querySelector(
        '.modal-traslado-header h2'
    );

    if (titulo) {
        titulo.textContent = 'Editar Traslado';
    }

    document.getElementById('modalNuevoTraslado').style.display = 'flex';

}


// ======================================================
// ELIMINAR TRASLADO
// ======================================================

async function eliminarTraslado(id) {

    if (!confirm(`¿Desea eliminar el traslado ${id}?`)) return;

    try {

        const response = await fetch(
            `/kgade/KGA-Development-Studio/API/traslados/traslados.php?id=${encodeURIComponent(id)}`,
            {
                method: 'DELETE'
            }
        );

        const resultado = await response.json();

        if (!response.ok) {

            alert(resultado.error || 'No se pudo eliminar el traslado.');

            return;

        }

        alert(resultado.mensaje || 'Traslado eliminado correctamente.');

        await cargarTraslados();

    } catch (error) {

        console.error('Error al eliminar traslado:', error);

        alert('Ocurrió un error en la conexión.');

    }

}