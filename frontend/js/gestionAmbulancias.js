// Ruta del endpoint que administra los vehículos.
const RUTA_API_AMBULANCIAS = '/kgade/KGA-Development-Studio/API/ambulancias/ambulancias.php';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalNuevaAmbulancia');
    const formulario = document.getElementById('formNuevaAmbulancia');
    const btnAbrir = document.getElementById('btnAbrirModalAmbulancia');
    const btnCerrar = document.getElementById('btnCerrarModalAmbulancia');
    const btnCancelar = document.getElementById('btnCancelarModalAmbulancia');
    const btnGuardar = document.getElementById('btnGuardarAmbulancia');
    const mensaje = document.getElementById('mensajeAmbulancia');

    // Si falta algún elemento, evitamos que el resto del JavaScript falle.
    if (!modal || !formulario || !btnAbrir) {
        console.error('No se encontró el formulario o el modal para añadir vehículos.');
        return;
    }

    // Abrir el modal y dejar el formulario listo para un registro nuevo.
    btnAbrir.addEventListener('click', () => {
        formulario.reset();
        mostrarMensaje('');
        modal.classList.add('mostrar');
    });

    // Una única función se encarga de cerrar y limpiar el modal.
    const cerrarModal = () => {
        modal.classList.remove('mostrar');
        formulario.reset();
        mostrarMensaje('');
    };

    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);

    // Cerrar al hacer clic en el fondo oscuro, fuera de la ventana.
    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) cerrarModal();
    });

    // También permitimos cerrar el modal con la tecla Escape.
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && modal.classList.contains('mostrar')) {
            cerrarModal();
        }
    });

    // Enviar el formulario al servidor.
    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        // FormData permite obtener los valores usando el atributo name de cada campo.
        const datosFormulario = new FormData(formulario);

        const vehiculo = {
            matricula: datosFormulario.get('matricula').trim(),
            modelo: datosFormulario.get('modelo').trim(),
            capacidad: datosFormulario.get('capacidad'),
            tipo_vehiculo: datosFormulario.get('tipo_vehiculo'),
            camilla: datosFormulario.get('camilla')
        };

        // Validación adicional en el navegador; el servidor vuelve a validar todo.
        if (Object.values(vehiculo).some(valor => valor === null || String(valor).trim() === '')) {
            mostrarMensaje('Completá todos los campos antes de guardar.', true);
            return;
        }

        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Guardando...';
        }

        mostrarMensaje('Guardando vehículo...');

        try {
            const respuesta = await fetch(RUTA_API_AMBULANCIAS, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(vehiculo)
            });

            const resultado = await leerRespuestaJSON(respuesta);

            if (!respuesta.ok) {
                throw new Error(resultado.error || 'No se pudo registrar el vehículo.');
            }

            mostrarMensaje(resultado.mensaje || 'Vehículo registrado correctamente.');
            formulario.reset();

            // Actualizamos la lista para que el vehículo aparezca sin recargar la página.
            await cargarVehiculos();

            // Dejamos visible la confirmación para que el usuario sepa que se guardó.
            // El formulario queda limpio y puede cerrarse con la X o Cancelar.

        } catch (error) {
            console.error('Error al registrar el vehículo:', error);
            mostrarMensaje(error.message || 'Ocurrió un error de conexión.', true);
        } finally {
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar vehículo';
            }
        }
    });

    // Al entrar a la página, mostramos los vehículos que ya existen en MySQL.
    cargarVehiculos();
});

// Lee JSON de forma segura y permite detectar si PHP devolvió HTML o una advertencia.
async function leerRespuestaJSON(respuesta) {
    const texto = await respuesta.text();

    try {
        return JSON.parse(texto);
    } catch (error) {
        console.error('La API no devolvió JSON válido:', texto);
        throw new Error('El servidor devolvió una respuesta inesperada. Revisá la consola y el log de PHP.');
    }
}

// Muestra un mensaje dentro del modal.
function mostrarMensaje(texto, esError = false) {
    const elemento = document.getElementById('mensajeAmbulancia');
    if (!elemento) return;

    elemento.textContent = texto;
    elemento.classList.toggle('mensaje-error', esError);
    elemento.classList.toggle('mensaje-exito', texto !== '' && !esError);
}

// Consulta el endpoint y dibuja nuevamente las tarjetas.
async function cargarVehiculos() {
    const contenedor = document.getElementById('contenedorVehiculos');
    if (!contenedor) return;

    contenedor.textContent = 'Cargando vehículos...';

    try {
        const respuesta = await fetch(RUTA_API_AMBULANCIAS);
        const vehiculos = await leerRespuestaJSON(respuesta);

        if (!respuesta.ok) {
            throw new Error(vehiculos.error || 'No se pudieron cargar los vehículos.');
        }

        renderizarVehiculos(vehiculos);
    } catch (error) {
        console.error('Error al cargar vehículos:', error);
        contenedor.textContent = 'No se pudieron cargar los vehículos. Intentá nuevamente.';
    }
}

// Construye las tarjetas usando textContent para no insertar datos de la BD como HTML.
function renderizarVehiculos(vehiculos) {
    const contenedor = document.getElementById('contenedorVehiculos');
    if (!contenedor) return;

    contenedor.innerHTML = '';

    if (!Array.isArray(vehiculos) || vehiculos.length === 0) {
        contenedor.textContent = 'Todavía no hay vehículos registrados.';
        return;
    }

    vehiculos.forEach(vehiculo => {
        const tarjeta = document.createElement('article');
        tarjeta.className = 'card-ambulancia';

        const titulo = document.createElement('h3');
        titulo.textContent = 'Matrícula: ' + vehiculo.matricula;

        const imagen = document.createElement('img');
        imagen.src = '../img/ambulanciaIMG.png';
        imagen.alt = 'Vehículo registrado';

        const informacion = document.createElement('div');
        informacion.className = 'info-principal';

        agregarDato(informacion, 'Modelo:', vehiculo.modelo);
        agregarDato(informacion, 'Capacidad:', vehiculo.capacidad);
        agregarDato(informacion, 'Tipo:', vehiculo.tipo_vehiculo);
        agregarDato(informacion, 'Camilla:', Number(vehiculo.camilla) === 1 ? 'Sí' : 'No');

        tarjeta.appendChild(titulo);
        tarjeta.appendChild(imagen);
        tarjeta.appendChild(informacion);
        contenedor.appendChild(tarjeta);
    });
}

// Agrega una línea de información a la tarjeta.
function agregarDato(contenedor, etiqueta, valor) {
    const parrafo = document.createElement('p');
    const textoEtiqueta = document.createElement('strong');

    textoEtiqueta.textContent = etiqueta + ' ';
    parrafo.appendChild(textoEtiqueta);
    parrafo.appendChild(document.createTextNode(valor ?? 'Sin información'));
    contenedor.appendChild(parrafo);
}
