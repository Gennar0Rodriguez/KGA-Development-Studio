
// ==========================================
// ADMINISTRAR ENTIDADES
// ==========================================

// Cantidad de registros que se muestran por página.
const REGISTROS_POR_PAGINA = 10;

// Ruta base utilizada por el resto del proyecto.
const RUTA_API = '/kgade/KGA-Development-Studio/API';

// Cada entidad consulta su propio controlador.
// No existe un controlador general de entidades.
const endpointsEntidades = {
    enfermero: `${RUTA_API}/enfermeros/enfermeros.php`,
    chofer: `${RUTA_API}/choferes/choferes.php`,
    elemento: `${RUTA_API}/elementos/elementos.php`,
    paciente: `${RUTA_API}/pacientes/paciente.php`,
    destino: `${RUTA_API}/destinos/destinos.php`,
    vehiculo: `${RUTA_API}/ambulancias/ambulancias.php`
};

// Configuración de títulos y columnas.
// Los nombres de los campos coinciden con los SELECT de los modelos.
const configuracionEntidades = {

    enfermero: {
        titulo: 'Enfermeros registrados',
        descripcion: 'Listado de enfermeros registrados en el sistema.',
        columnas: [
            { campo: 'ci_enfermero', titulo: 'CI' },
            { campo: 'nombre_enfermero', titulo: 'Nombre' },
            { campo: 'apellido_enfermero', titulo: 'Apellido' }
        ]
    },

    chofer: {
        titulo: 'Choferes registrados',
        descripcion: 'Listado de choferes registrados en el sistema.',
        columnas: [
            { campo: 'ci_chofer', titulo: 'CI' },
            { campo: 'nombre_chofer', titulo: 'Nombre' },
            { campo: 'apellido_chofer', titulo: 'Apellido' },
            { campo: 'telefono', titulo: 'Teléfono' }
        ]
    },

    elemento: {
        titulo: 'Elementos registrados',
        descripcion: 'Listado de elementos y sus datos de retiro.',
        columnas: [
            { campo: 'id_transportable', titulo: 'ID' },
            { campo: 'nombre_elemento', titulo: 'Nombre' },
            { campo: 'descripcion_elemento', titulo: 'Descripción' },
            { campo: 'tipo_elemento', titulo: 'Tipo' },
            { campo: 'nombre_enfermero', titulo: 'Enfermero' },
            { campo: 'habitacion_retiro', titulo: 'Habitación' },
            { campo: 'piso_retiro', titulo: 'Piso' }
        ]
    },

    paciente: {
        titulo: 'Pacientes registrados',
        descripcion: 'Listado de pacientes y sus datos de traslado.',
        columnas: [
            { campo: 'id_transportable', titulo: 'ID' },
            { campo: 'ci_paciente', titulo: 'CI' },
            { campo: 'nombre_paciente', titulo: 'Nombre' },
            { campo: 'accesorio', titulo: 'Accesorio' },
            { campo: 'oxigeno', titulo: 'Oxígeno' },
            { campo: 'aislamiento', titulo: 'Aislamiento' },
            { campo: 'nombre_enfermero', titulo: 'Enfermero' },
            { campo: 'habitacion_retiro', titulo: 'Habitación' },
            { campo: 'piso_retiro', titulo: 'Piso' }
        ]
    },

    destino: {
        titulo: 'Destinos registrados',
        descripcion: 'Listado de destinos disponibles.',
        columnas: [
            { campo: 'id_destino', titulo: 'ID' },
            { campo: 'nombre_destino', titulo: 'Nombre' },
            { campo: 'direccion', titulo: 'Dirección' },
            { campo: 'ruta', titulo: 'Ruta' }
        ]
    },

    vehiculo: {
        titulo: 'Vehículos y ambulancias registrados',
        descripcion: 'Listado de vehículos disponibles en el sistema.',
        columnas: [
            { campo: 'matricula', titulo: 'Matrícula' },
            { campo: 'modelo', titulo: 'Modelo' },
            { campo: 'capacidad', titulo: 'Capacidad' },
            { campo: 'tipo_vehiculo', titulo: 'Tipo' },
            { campo: 'camilla', titulo: 'Camilla' }
        ]
    }

};


// Registros recibidos desde el servidor.
let todasLasEntidades = [];

// Página actual de la tabla.
let paginaActual = 1;

// Entidad seleccionada.
let entidadActual = 'enfermero';


// ==========================================
// INICIALIZACIÓN
// ==========================================

document.addEventListener('DOMContentLoaded', () => {

    const selector = document.getElementById('selectEntidad');
    const buscador = document.getElementById('inputBuscar');

    // Al cambiar de entidad, consultamos su endpoint.
    selector.addEventListener('change', () => {

        entidadActual = selector.value;

        // Reiniciamos la búsqueda y la paginación.
        buscador.value = '';
        paginaActual = 1;

        cargarEntidades();

    });

    // La búsqueda se realiza mientras se escribe.
    buscador.addEventListener('input', () => {

        paginaActual = 1;

        filtrarEntidades();

    });

    // Cargamos inicialmente los enfermeros.
    cargarEntidades();

});


// ==========================================
// CARGAR ENTIDADES
// ==========================================

async function cargarEntidades() {

    const tbody = document.getElementById('tablaEntidadesBody');

    tbody.innerHTML = `
        <tr>
            <td class="mensaje-tabla">
                Cargando registros...
            </td>
        </tr>
    `;

    actualizarEncabezado();

    try {

        const endpoint = endpointsEntidades[entidadActual];

        const respuesta = await fetch(endpoint, {
            method: 'GET',
            credentials: 'same-origin'
        });

        const datos = await respuesta.json();

        if (!respuesta.ok) {

            throw new Error(
                datos.mensaje ||
                datos.error ||
                'No se pudieron obtener los registros.'
            );

        }

        if (!Array.isArray(datos)) {
            throw new Error('La respuesta del servidor no tiene un formato válido.');
        }

        todasLasEntidades = datos;

        // Actualizamos el contador con el total sin filtrar.
        document.getElementById('cantidadEntidades').textContent =
            todasLasEntidades.length;

        paginaActual = 1;

        filtrarEntidades();

    } catch (error) {

        console.error('Error al cargar entidades:', error);

        todasLasEntidades = [];

        document.getElementById('cantidadEntidades').textContent = '0';

        document.getElementById('tablaEntidadesHead').innerHTML = '';

        tbody.innerHTML = `
            <tr>
                <td class="mensaje-tabla">
                    ${escaparHTML(error.message)}
                </td>
            </tr>
        `;

        document.getElementById('informacionResultados').textContent =
            'No se pudieron cargar los registros.';

        document.getElementById('contenedorPaginacion').innerHTML = '';

    }

}


// ==========================================
// ACTUALIZAR ENCABEZADO
// ==========================================

function actualizarEncabezado() {

    const configuracion = configuracionEntidades[entidadActual];

    document.getElementById('tituloEntidad').textContent =
        configuracion.titulo;

    document.getElementById('descripcionEntidad').textContent =
        configuracion.descripcion;

}


// ==========================================
// BUSCAR REGISTROS
// ==========================================

function filtrarEntidades() {

    const busqueda = document.getElementById('inputBuscar')
        .value
        .toLowerCase()
        .trim();

    // Buscamos el texto en todos los campos de cada registro.
    const registrosFiltrados = todasLasEntidades.filter(registro => {

        return Object.values(registro).some(valor => {

            return String(valor ?? '')
                .toLowerCase()
                .includes(busqueda);

        });

    });

    // Guardamos el resultado para que la paginación trabaje
    // sobre los registros filtrados y no sobre todos los originales.
    paginaActual = 1;

    renderizarTabla(registrosFiltrados);

}


// ==========================================
// RENDERIZAR TABLA
// ==========================================

function renderizarTabla(lista) {

    const encabezado = document.getElementById('tablaEntidadesHead');
    const tbody = document.getElementById('tablaEntidadesBody');

    const configuracion = configuracionEntidades[entidadActual];

    encabezado.innerHTML = '';
    tbody.innerHTML = '';

    // Creamos los encabezados según la entidad seleccionada.
    const filaEncabezado = document.createElement('tr');

    configuracion.columnas.forEach(columna => {

        const th = document.createElement('th');

        th.textContent = columna.titulo;

        filaEncabezado.appendChild(th);

    });

    encabezado.appendChild(filaEncabezado);

    // Si no hay resultados, mostramos un mensaje.
    if (lista.length === 0) {

        const fila = document.createElement('tr');
        const celda = document.createElement('td');

        celda.colSpan = configuracion.columnas.length;
        celda.className = 'mensaje-tabla';
        celda.textContent = 'No se encontraron registros.';

        fila.appendChild(celda);
        tbody.appendChild(fila);

        document.getElementById('informacionResultados').textContent =
            'No hay resultados para mostrar.';

        document.getElementById('contenedorPaginacion').innerHTML = '';

        return;

    }

    // Calculamos los registros que corresponden a la página actual.
    const inicio = (paginaActual - 1) * REGISTROS_POR_PAGINA;
    const fin = inicio + REGISTROS_POR_PAGINA;

    const registrosPagina = lista.slice(inicio, fin);

    registrosPagina.forEach(registro => {

        const fila = document.createElement('tr');

        configuracion.columnas.forEach(columna => {

            const celda = document.createElement('td');

            let valor = registro[columna.campo];

            // Los campos booleanos se muestran como Sí o No.
            if (
                ['camilla', 'oxigeno', 'aislamiento'].includes(columna.campo)
            ) {

                if (valor !== null && valor !== undefined) {
                    valor = Number(valor) === 1 ? 'Sí' : 'No';
                }

            }

            // Evitamos mostrar null o undefined.
            celda.textContent =
                valor !== null && valor !== undefined && valor !== ''
                    ? valor
                    : '-';

            fila.appendChild(celda);

        });

        tbody.appendChild(fila);

    });

    document.getElementById('informacionResultados').textContent =
        `Mostrando ${inicio + 1} - ${Math.min(fin, lista.length)} de ${lista.length} registros`;

    renderizarPaginacion(lista);

}


// ==========================================
// PAGINACIÓN
// ==========================================

function renderizarPaginacion(lista) {

    const contenedor = document.getElementById('contenedorPaginacion');

    contenedor.innerHTML = '';

    const totalPaginas = Math.ceil(
        lista.length / REGISTROS_POR_PAGINA
    );

    if (totalPaginas <= 1) {
        return;
    }

    for (let numero = 1; numero <= totalPaginas; numero++) {

        const boton = document.createElement('button');

        boton.type = 'button';
        boton.textContent = numero;

        if (numero === paginaActual) {
            boton.classList.add('pagina-activa');
        }

        boton.addEventListener('click', () => {

            paginaActual = numero;

            renderizarTabla(lista);

        });

        contenedor.appendChild(boton);

    }

}


// ==========================================
// ESCAPAR TEXTO
// ==========================================

// Se utiliza para mostrar mensajes de error sin insertar
// contenido HTML proveniente del servidor.
function escaparHTML(texto) {

    const elemento = document.createElement('div');

    elemento.textContent = texto;

    return elemento.innerHTML;

}
