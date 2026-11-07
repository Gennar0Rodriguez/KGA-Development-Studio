
let todosLosTraslados = [];
let modoEdicion = false;
let trasladoEditando = null;

let choferesDisponibles = [];
let vehiculosDisponibles = [];
let enfermerosDisponibles = [];
let destinosDisponibles = [];
let pacientesDisponibles = [];

let esAdministrador = false;
let filtroSolicitud = 'pendiente';
let filtroEstadoAprobado = 'aprobado';
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

    configurarFiltrosSolicitudes();
    configurarTipoTransportable();

    // Abrir modal para crear un traslado.
    if (btnAbrir && modal && formulario) {
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
    buscarEnfermeros();
    buscarDestinos();
    buscarPacientes();

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

    const ciPaciente = document.getElementById('ciPaciente');
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
    if (ciPaciente) {
        ciPaciente.required = esPaciente;
    }

    if (paciente) {
        paciente.required = esPaciente;
        paciente.readOnly = true;
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

    // Comprobar que el vehículo provenga de las sugerencias.
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

    // Comprobar que se haya seleccionado un chofer registrado.
    const choferValido = choferesDisponibles.some(chofer =>
        String(chofer.ci_chofer) === String(ciChoferSeleccionado)
    );

    if (!choferValido) {
        alert('Debe seleccionar un conductor de la lista de sugerencias.');
        return;
    }

    const ciEnfermero = document.getElementById('enfermero').value;
    const idDestino = document.getElementById('destino').value;

    // Validar que el enfermero seleccionado exista.
    const enfermeroValido = enfermerosDisponibles.some(enfermero =>
        String(enfermero.ci_enfermero) === String(ciEnfermero)
    );

    if (!enfermeroValido) {
        alert('Debe seleccionar un enfermero de la lista de sugerencias.');
        return;
    }

    // Validar que el destino seleccionado exista.
    const destinoValido = destinosDisponibles.some(destino =>
        String(destino.id_destino) === String(idDestino)
    );

    if (!destinoValido) {
        alert('Debe seleccionar un destino de la lista de sugerencias.');
        return;
    }

    const tipoTransportable = document.getElementById(
        'tipoTransportable'
    ).value;

    const ciPaciente = document.getElementById('ciPaciente')?.value.trim();
    const nombrePaciente = document.getElementById('paciente')?.value.trim();

    // Validar la relación entre la CI y el nombre del paciente.
    if (tipoTransportable === 'paciente') {
        const pacienteValido = pacientesDisponibles.some(paciente =>
            String(paciente.ci_paciente) === String(ciPaciente) &&
            String(paciente.nombre_paciente) === String(nombrePaciente)
        );

        if (!pacienteValido) {
            alert('Debe seleccionar un paciente registrado mediante su CI.');
            return;
        }
    }

    const payload = {
        // Datos generales del traslado.
        matricula: matriculaSeleccionada,
        ci_chofer: ciChoferSeleccionado,
        ci_enfermero: ciEnfermero,
        id_destino: idDestino,
        fecha: document.getElementById('fecha').value,
        hora: document.getElementById('hora').value,
        observaciones: document.getElementById('observaciones').value,

        // Tipo de elemento que se transporta.
        tipo_transportable: tipoTransportable,

        // Datos del paciente.
        ci_paciente: tipoTransportable === 'paciente'
            ? ciPaciente
            : '',

        paciente: tipoTransportable === 'paciente'
            ? nombrePaciente
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
    if (document.querySelector('.filtros-solicitudes')) return;

    const filtros = document.createElement('section');
    filtros.className = 'filtros-solicitudes';

    filtros.innerHTML = `
        <button class="btn-filtro activo" data-estado="pendiente">
            Pendientes
        </button>
        <button class="btn-filtro" data-estado="aprobado">
            ${esAdministrador ? 'Aprobados' : 'Aprobados'}
        </button>
        <button class="btn-filtro" data-estado="rechazado">
            Rechazados
        </button>
    `;

    contenedor.parentNode.insertBefore(filtros, contenedor);

    const subFiltros = document.createElement('section');
    subFiltros.className = 'filtros-solicitudes filtros-estados-traslado';
    subFiltros.style.display = 'none';

    subFiltros.innerHTML = `
        <button class="btn-filtro activo" data-estado-traslado="aprobado">
            Programados
        </button>
        <button class="btn-filtro" data-estado-traslado="en proceso">
            En proceso
        </button>
        <button class="btn-filtro" data-estado-traslado="finalizado">
            Finalizados
        </button>
    `;

    contenedor.parentNode.insertBefore(subFiltros, contenedor);

    filtros.querySelectorAll('.btn-filtro').forEach(boton => {
        boton.addEventListener('click', () => {
            filtroSolicitud = boton.dataset.estado;

            filtros.querySelectorAll('.btn-filtro').forEach(b => {
                b.classList.remove('activo');
            });

            boton.classList.add('activo');

            subFiltros.style.display =
                filtroSolicitud === 'aprobado' ? 'flex' : 'none';

            filtrarTraslados();
        });
    });

    subFiltros.querySelectorAll('.btn-filtro').forEach(boton => {
        boton.addEventListener('click', () => {
            filtroEstadoAprobado = boton.dataset.estadoTraslado;

            subFiltros.querySelectorAll('.btn-filtro').forEach(b => {
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
        let coincideEstado;

        if (filtroSolicitud === 'aprobado') {
            coincideEstado =
                traslado.estado_solicitud === 'aprobado' &&
                traslado.estado_traslado === filtroEstadoAprobado;
        } else {
            coincideEstado =
                traslado.estado_solicitud === filtroSolicitud;
        }

        // Buscar por matrícula o cédula del paciente.
        const matricula = String(traslado.matricula || '')
            .toLowerCase();

        const cedula = String(traslado.ci_paciente || '')
            .toLowerCase();

        const coincideBusqueda =
            matricula.includes(textoBusqueda) ||
            cedula.includes(textoBusqueda);

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
            throw new Error('No se pudieron cargar las opciones del formulario.');
        }

        const listas = await response.json();

        // Guardamos los registros para utilizarlos en los autocompletados.
        choferesDisponibles = listas.choferes || [];
        vehiculosDisponibles = listas.vehiculos || [];
        enfermerosDisponibles = listas.enfermeros || [];
        destinosDisponibles = listas.destinos || [];
        pacientesDisponibles = listas.pacientes || [];

        console.log('Opciones cargadas correctamente:', {
            choferes: choferesDisponibles.length,
            vehiculos: vehiculosDisponibles.length,
            enfermeros: enfermerosDisponibles.length,
            destinos: destinosDisponibles.length,
            pacientes: pacientesDisponibles.length
        });

    } catch (error) {
        console.error('Error al cargar opciones:', error);
        alert('No se pudieron cargar los datos para el formulario.');
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
// BUSCADOR DE ENFERMEROS
// ======================================================

function buscarEnfermeros() {
    const input = document.getElementById('buscadorEnfermero');
    const campoCI = document.getElementById('enfermero');
    const sugerencias = document.getElementById('sugerenciasEnfermero');

    if (!input || !campoCI || !sugerencias) return;

    input.addEventListener('input', () => {
        const texto = input.value.toLowerCase().trim();

        // Al modificar el texto, invalidamos la selección anterior.
        campoCI.value = '';
        sugerencias.innerHTML = '';

        if (!texto) return;

        const resultados = enfermerosDisponibles.filter(enfermero => {
            const nombreCompleto = (
                enfermero.nombre_enfermero + ' ' +
                enfermero.apellido_enfermero
            ).toLowerCase();

            const ci = String(enfermero.ci_enfermero);

            return nombreCompleto.includes(texto) || ci.includes(texto);
        });

        resultados.forEach(enfermero => {
            const opcion = document.createElement('button');

            opcion.type = 'button';
            opcion.className = 'opcion-ambulancia';

            opcion.textContent =
                `${enfermero.nombre_enfermero} ${enfermero.apellido_enfermero} - CI: ${enfermero.ci_enfermero}`;

            opcion.addEventListener('click', () => {
                input.value =
                    `${enfermero.nombre_enfermero} ${enfermero.apellido_enfermero}`;

                campoCI.value = enfermero.ci_enfermero;
                sugerencias.innerHTML = '';
            });

            sugerencias.appendChild(opcion);
        });

        if (resultados.length === 0) {
            sugerencias.innerHTML =
                '<p class="sin-resultados-ambulancia">No se encontraron enfermeros.</p>';
        }
    });

    document.addEventListener('click', evento => {
        if (
            !input.contains(evento.target) &&
            !sugerencias.contains(evento.target)
        ) {
            sugerencias.innerHTML = '';
        }
    });
}


// ======================================================
// BUSCADOR DE DESTINOS
// ======================================================

function buscarDestinos() {
    const input = document.getElementById('buscadorDestino');
    const campoId = document.getElementById('destino');
    const sugerencias = document.getElementById('sugerenciasDestino');

    if (!input || !campoId || !sugerencias) return;

    input.addEventListener('input', () => {
        const texto = input.value.toLowerCase().trim();

        // Evitamos enviar un destino anterior si cambia el texto.
        campoId.value = '';
        sugerencias.innerHTML = '';

        if (!texto) return;

        const resultados = destinosDisponibles.filter(destino =>
            String(destino.nombre_destino).toLowerCase().includes(texto)
        );

        resultados.forEach(destino => {
            const opcion = document.createElement('button');

            opcion.type = 'button';
            opcion.className = 'opcion-ambulancia';
            opcion.textContent = destino.nombre_destino;

            opcion.addEventListener('click', () => {
                input.value = destino.nombre_destino;
                campoId.value = destino.id_destino;
                sugerencias.innerHTML = '';
            });

            sugerencias.appendChild(opcion);
        });

        if (resultados.length === 0) {
            sugerencias.innerHTML =
                '<p class="sin-resultados-ambulancia">No se encontraron destinos.</p>';
        }
    });

    document.addEventListener('click', evento => {
        if (
            !input.contains(evento.target) &&
            !sugerencias.contains(evento.target)
        ) {
            sugerencias.innerHTML = '';
        }
    });
}


// ======================================================
// BUSCADOR DE PACIENTES POR CÉDULA
// ======================================================

function buscarPacientes() {
    const inputCI = document.getElementById('ciPaciente');
    const inputNombre = document.getElementById('paciente');
    const sugerencias = document.getElementById('sugerenciasPaciente');

    if (!inputCI || !inputNombre || !sugerencias) return;

    // El nombre nunca puede modificarse manualmente.
    inputNombre.readOnly = true;

    inputCI.addEventListener('input', () => {
        const texto = inputCI.value.trim();

        // Si cambia la CI, se elimina el nombre anterior.
        inputNombre.value = '';
        sugerencias.innerHTML = '';

        if (!texto) return;

        const resultados = pacientesDisponibles.filter(paciente =>
            String(paciente.ci_paciente).includes(texto)
        );

        // Evitamos mostrar dos veces la misma CI.
        const pacientesUnicos = [];
        const cedulasMostradas = new Set();

        resultados.forEach(paciente => {
            const ci = String(paciente.ci_paciente);

            if (!cedulasMostradas.has(ci)) {
                cedulasMostradas.add(ci);
                pacientesUnicos.push(paciente);
            }
        });

        pacientesUnicos.forEach(paciente => {
            const opcion = document.createElement('button');

            opcion.type = 'button';
            opcion.className = 'opcion-ambulancia';

            opcion.textContent =
                `${paciente.ci_paciente} - ${paciente.nombre_paciente}`;

            opcion.addEventListener('click', () => {
                inputCI.value = paciente.ci_paciente;
                inputNombre.value = paciente.nombre_paciente;
                sugerencias.innerHTML = '';
            });

            sugerencias.appendChild(opcion);
        });

        if (pacientesUnicos.length === 0) {
            sugerencias.innerHTML =
                '<p class="sin-resultados-ambulancia">No se encontró ningún paciente con esa CI.</p>';
        }
    });

    document.addEventListener('click', evento => {
        if (
            !inputCI.contains(evento.target) &&
            !sugerencias.contains(evento.target)
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

        const estado = item.estado_traslado === 'aprobado'
            ? 'Programado'
            : item.estado_traslado === 'en proceso'
                ? 'En proceso'
                : item.estado_traslado === 'finalizado'
                    ? 'Finalizado'
                    : item.estado_traslado || 'Pendiente';

        const fechaTraslado = item.fecha_traslado
            ? item.fecha_traslado.split('-').reverse().join('/')
            : 'Sin fecha';

        card.innerHTML = `
            <h3>Matrícula: ${item.matricula || 'Sin matrícula'}</h3>

            <img src="../img/ambulanciaIMGG.png" alt="Vehículo">

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

                <p><strong>Fecha del traslado:</strong>
                    ${fechaTraslado}
                </p>

                <p><strong>Hora salida:</strong>
                    ${item.hora_salida || '--:--'}
                </p>

                <p><strong>Fecha llegada:</strong>
                    ${item.fecha_llegada
                        ? item.fecha_llegada.split('-').reverse().join('/')
                        : '--/--/----'}
                </p>

                <p><strong>Hora llegada:</strong>
                    ${item.hora_llegada || '--:--'}
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
                        ${item.estado_traslado === 'aprobado' ? `
                            <button
                                class="btn-seguimiento"
                                onclick="actualizarEstadoTraslado('${item.id_traslado}', 'en proceso')">
                                En proceso
                            </button>
                        ` : item.estado_traslado === 'en proceso' ? `
                            <button
                                class="btn-aprobar"
                                onclick="actualizarEstadoTraslado('${item.id_traslado}', 'finalizado')">
                                Finalizar
                            </button>
                        ` : item.estado_traslado === 'finalizado' ? `
                            <button
                                class="btn-seguimiento"
                                onclick="actualizarEstadoTraslado('${item.id_traslado}', 'en proceso')">
                                En proceso
                            </button>
                        ` : ''}
                    ` : ''}

                    ${['aprobado', 'rechazado'].includes(item.estado_solicitud) ? `
                        <button
                            class="btn-eliminar"
                            onclick="eliminarTraslado('${item.id_traslado}')">
                            Eliminar
                        </button>
                    ` : ''}

                    ${item.estado_solicitud === 'rechazado' ? `
                        <button
                            class="btn-aprobar"
                            onclick="resolverSolicitud('${item.id_traslado}', 'aprobado')">
                            Aprobar
                        </button>
                    ` : ''}
                </div>
            ` : ''}
        `;

        contenedor.appendChild(card);
    });
}


// ======================================================
// SOLICITAR UNA HORA MEDIANTE EL MODAL
// ======================================================

function solicitarHoraTraslado(tipo) {
    return new Promise(resolve => {
        const modal = document.getElementById('modalHoraTraslado');
        const formulario = document.getElementById('formHoraTraslado');
        const input = document.getElementById('inputHoraTraslado');
        const titulo = document.getElementById('tituloModalHora');
        const label = document.getElementById('labelHoraTraslado');

        if (!modal || !formulario || !input || !titulo || !label) {
            alert('No se encontró el modal para registrar la hora.');
            resolve(null);
            return;
        }

        titulo.textContent = tipo === 'salida'
            ? 'Registrar hora de salida'
            : 'Registrar hora de llegada';

        label.textContent = tipo === 'salida'
            ? 'Hora de salida'
            : 'Hora de llegada';

        input.value = '';
        modal.style.display = 'flex';
        input.focus();

        const cerrar = valor => {
            modal.style.display = 'none';
            formulario.onsubmit = null;

            document.getElementById('btnCancelarHora').onclick = null;
            document.getElementById('btnCerrarModalHora').onclick = null;

            resolve(valor);
        };

        formulario.onsubmit = evento => {
            evento.preventDefault();

            if (!input.value) {
                alert('Debe ingresar una hora válida.');
                return;
            }

            cerrar(input.value);
        };

        document.getElementById('btnCancelarHora').onclick = () => cerrar(null);
        document.getElementById('btnCerrarModalHora').onclick = () => cerrar(null);
    });
}


// ======================================================
// RESOLVER SOLICITUD
// ======================================================

// ======================================================
// RESOLVER SOLICITUD
// ======================================================

async function resolverSolicitud(idTraslado, estadoSolicitud) {

    const traslado = todosLosTraslados.find(item =>
        String(item.id_traslado) === String(idTraslado)
    );

    if (!traslado) {
        alert('No se encontró el traslado.');
        return;
    }

    const esReaprobacion = traslado.estado_solicitud === 'rechazado';

    const accion = estadoSolicitud === 'aprobado'
        ? (esReaprobacion ? 'volver a aprobar' : 'aprobar')
        : 'rechazar';

    if (!confirm(`¿Desea ${accion} esta solicitud?`)) return;

    let horaSalida = null;

    if (estadoSolicitud === 'aprobado') {

        // Al volver a aprobar una solicitud rechazada,
        // siempre solicitamos una nueva hora de salida.
        if (esReaprobacion || !traslado.hora_salida) {

            horaSalida = await solicitarHoraTraslado('salida');

            if (!horaSalida) return;

        } else {

            horaSalida = traslado.hora_salida;

        }

    }

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
                    estado_solicitud: estadoSolicitud,
                    hora_salida: horaSalida
                })
            }
        );

        const resultado = await response.json();

        if (!response.ok) {
            alert(resultado.error || 'No se pudo actualizar la solicitud.');
            return;
        }

        alert(resultado.mensaje || 'Solicitud actualizada.');

        // Recargamos los datos para que aparezca en Programados.
        await cargarTraslados();

    } catch (error) {

        console.error('Error al resolver solicitud:', error);

        alert('Ocurrió un error en la conexión.');

    }

}


// ======================================================
// ACTUALIZAR ESTADO OPERATIVO
// ======================================================

async function actualizarEstadoTraslado(idTraslado, nuevoEstado) {

    const traslado = todosLosTraslados.find(item =>
        String(item.id_traslado) === String(idTraslado)
    );

    if (!traslado) {
        alert('No se encontró el traslado.');
        return;
    }

    let horaLlegada = null;
    let fechaLlegada = null;

    if (nuevoEstado === 'finalizado') {

        // Solicitar la fecha real de llegada.
        const hoy = new Date();

        const fechaActual =
            hoy.getFullYear() + '-' +
            String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
            String(hoy.getDate()).padStart(2, '0');

        fechaLlegada = prompt(
            'Ingrese la fecha de llegada (AAAA-MM-DD):',
            fechaActual
        );

        if (!fechaLlegada) return;

        // Solicitar la hora real de llegada.
        horaLlegada = await solicitarHoraTraslado('llegada');

        if (!horaLlegada) return;

    } else {

        const mensaje = traslado.estado_traslado === 'finalizado'
            ? '¿Está seguro de que desea devolver este traslado a En proceso? Se eliminarán su fecha y hora de llegada.'
            : '¿Desea iniciar este traslado?';

        if (!confirm(mensaje)) return;

    }

    try {

        const response = await fetch(
            '/kgade/KGA-Development-Studio/API/traslados/traslados.php?action=estado',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_traslado: idTraslado,
                    estado_traslado: nuevoEstado,
                    hora_llegada: horaLlegada,
                    fecha_llegada: fechaLlegada
                })
            }
        );

        const resultado = await response.json();

        if (!response.ok) {
            alert(
                resultado.error ||
                'No se pudo actualizar el estado. Compruebe la fecha y las horas registradas.'
            );
            return;
        }

        alert(resultado.mensaje || 'Estado actualizado correctamente.');

        await cargarTraslados();

    } catch (error) {

        console.error(
            'Error al actualizar el estado del traslado:',
            error
        );

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

    // Recuperar la CI y el nombre del paciente.
    document.getElementById('ciPaciente').value =
        traslado.ci_paciente || '';

    document.getElementById('paciente').value =
        traslado.nombre_paciente || '';

    document.getElementById('paciente').readOnly = true;

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

    // Recuperar el enfermero seleccionado.
    const enfermero = enfermerosDisponibles.find(item =>
        String(item.ci_enfermero) === String(traslado.ci_enfermero)
    );

    document.getElementById('enfermero').value =
        traslado.ci_enfermero || '';

    document.getElementById('buscadorEnfermero').value = enfermero
        ? `${enfermero.nombre_enfermero} ${enfermero.apellido_enfermero}`
        : '';

    // Recuperar el destino seleccionado.
    const destino = destinosDisponibles.find(item =>
        String(item.id_destino) === String(traslado.id_destino)
    );

    document.getElementById('destino').value =
        traslado.id_destino || '';

    document.getElementById('buscadorDestino').value = destino
        ? destino.nombre_destino
        : '';

    document.getElementById('fecha').value =
        traslado.fecha_traslado || '';

    document.getElementById('hora').value =
        traslado.hora_salida || '';

    document.getElementById('observaciones').value =
        traslado.observaciones || '';

    // Limpiar todas las sugerencias al abrir el formulario.
    document.getElementById('sugerenciasAmbulancia').innerHTML = '';
    document.getElementById('sugerenciasConductor').innerHTML = '';
    document.getElementById('sugerenciasPaciente').innerHTML = '';
    document.getElementById('sugerenciasEnfermero').innerHTML = '';
    document.getElementById('sugerenciasDestino').innerHTML = '';

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
