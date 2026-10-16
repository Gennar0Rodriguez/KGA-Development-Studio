let todosLosTraslados = [];

document.addEventListener('DOMContentLoaded', async () => {
    console.log('JS Traslados cargado correctamente');

    const modal = document.getElementById('modalNuevoTraslado');
    const btnAbrir = document.getElementById('btnAbrirModalTraslado');
    const btnCerrar = document.getElementById('btnCerrarModalTraslado');
    const btnCancelar = document.getElementById('btnCancelarModalTraslado');
    const formTraslado = document.getElementById('formNuevoTraslado');

    // 1. Manejo de abrir/cerrar modal
    if (btnAbrir && modal) {
        btnAbrir.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'flex';
        });
    }

    const cerrarModal = () => {
        if (modal) modal.style.display = 'none';
        if (formTraslado) formTraslado.reset();
    };

    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);

    // 2. Cargar Selects y Traslados de la BD
    await cargarOpcionesFormulario();
    await cargarTraslados();

    // 3. Envío del Formulario
    if (formTraslado) {
        formTraslado.addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                matricula: document.getElementById('ambulancia')?.value,
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

            console.log('Enviando datos de traslado:', payload);

            try {
                const response = await fetch('/kgade/KGA-Development-Studio/API/traslados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const res = await response.json();

                if (response.ok) {
                    alert(res.mensaje || 'Traslado guardado correctamente');
                    cerrarModal();
                    cargarTraslados();
                } else {
                    alert(res.error || 'Error al guardar el traslado');
                }
            } catch (error) {
                console.error('Error al guardar traslado:', error);
                alert('Ocurrió un error en la conexión.');
            }
        });
    }

    // 4. Buscador
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

// Cargar Choferes, Ambulancias, Enfermeros y Destinos
async function cargarOpcionesFormulario() {
    try {
        const res = await fetch('/kgade/KGA-Development-Studio/API/traslados.php?action=auxiliares');
        if (!res.ok) return;

        const listas = await res.json();

        // Llenar Ambulancias (Vehículos)
        const selectAmbulancia = document.getElementById('ambulancia');
        if (selectAmbulancia && listas.vehiculos) {
            selectAmbulancia.innerHTML = '<option value="">Seleccione una ambulancia</option>';
            listas.vehiculos.forEach(v => {
                selectAmbulancia.innerHTML += `<option value="${v.matricula}">${v.matricula} - ${v.modelo}</option>`;
            });
        }

        // Llenar Conductores
        const selectChofer = document.getElementById('conductor');
        if (selectChofer && listas.choferes) {
            selectChofer.innerHTML = '<option value="">Seleccione un conductor</option>';
            listas.choferes.forEach(c => {
                selectChofer.innerHTML += `<option value="${c.ci_chofer}">${c.nombre_chofer} ${c.apellido_chofer}</option>`;
            });
        }

        // Llenar Enfermeros
        const selectEnfermero = document.getElementById('enfermero');
        if (selectEnfermero && listas.enfermeros) {
            selectEnfermero.innerHTML = '<option value="">Seleccione un enfermero</option>';
            listas.enfermeros.forEach(e => {
                selectEnfermero.innerHTML += `<option value="${e.ci_enfermero}">${e.nombre_enfermero} ${e.apellido_enfermero}</option>`;
            });
        }

        // Transformar input de destino en select dinámico
        const inputDestino = document.getElementById('destino');
        if (inputDestino && listas.destinos) {
            const selectDestino = document.createElement('select');
            selectDestino.id = 'destino';
            selectDestino.required = true;
            selectDestino.innerHTML = '<option value="">Seleccione un destino</option>';
            listas.destinos.forEach(d => {
                selectDestino.innerHTML += `<option value="${d.id_destino}">${d.nombre_destino}</option>`;
            });
            inputDestino.replaceWith(selectDestino);
        }

    } catch (error) {
        console.error('Error al cargar opciones:', error);
    }
}

// Cargar lista de traslados desde el servidor
async function cargarTraslados() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/traslados.php');
        if (!response.ok) return;

        todosLosTraslados = await response.json();
        renderizarTarjetasTraslado(todosLosTraslados);
    } catch (error) {
        console.error('Error al cargar traslados:', error);
    }
}

// Renderizar tarjetas en el HTML
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
                <button class="btn-eliminar" onclick="eliminarTraslado('${item.id_traslado}')">Eliminar</button>
            </div>
        `;
        contenedor.appendChild(card);
    });
}

// Eliminar traslado
async function eliminarTraslado(id) {
    if (!confirm(`¿Desea eliminar el traslado ${id}?`)) return;

    try {
        const response = await fetch(`/kgade/KGA-Development-Studio/API/traslados.php?id=${id}`, {
            method: 'DELETE'
        });

        const res = await response.json();
        if (response.ok) {
            alert(res.mensaje);
            cargarTraslados();
        } else {
            alert(res.error || 'No se pudo eliminar');
        }
    } catch (error) {
        console.error('Error al eliminar:', error);
    }
}