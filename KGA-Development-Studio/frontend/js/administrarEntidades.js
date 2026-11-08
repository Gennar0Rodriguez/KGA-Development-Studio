// ==========================================
// ADMINISTRAR ENTIDADES
// ==========================================

const REGISTROS_POR_PAGINA = 10;

const RUTA_API = '/kgade/KGA-Development-Studio/KGA-Development-Studio/API';

// Cada entidad utiliza su propio controlador.
const endpointsEntidades = {
    enfermero: `${RUTA_API}/enfermeros/enfermeros.php`,
    chofer: `${RUTA_API}/choferes/choferes.php`,
    elemento: `${RUTA_API}/elementos/elementos.php`,
    paciente: `${RUTA_API}/pacientes/paciente.php`,
    destino: `${RUTA_API}/destinos/destinos.php`,
    vehiculo: `${RUTA_API}/ambulancias/ambulancias.php`
};

// Configuración de las columnas de cada tabla.
const configuracionEntidades = {

    enfermero: {
        titulo: 'Enfermeros registrados',
        descripcion: 'Listado de enfermeros registrados en el sistema.',
        columnas: [
            { campo: 'ci_enfermero', titulo: 'CI' },
            { campo: 'nombre_enfermero', titulo: 'Nombre' },
            { campo: 'apellido_enfermero', titulo: 'Apellido' }
        ],
        identificador: 'ci_enfermero'
    },

    chofer: {
        titulo: 'Choferes registrados',
        descripcion: 'Listado de choferes registrados en el sistema.',
        columnas: [
            { campo: 'ci_chofer', titulo: 'CI' },
            { campo: 'nombre_chofer', titulo: 'Nombre' },
            { campo: 'apellido_chofer', titulo: 'Apellido' },
            { campo: 'telefono', titulo: 'Teléfono' }
        ],
        identificador: 'ci_chofer'
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
        ],
        identificador: 'id_transportable'
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
        ],
        identificador: 'id_transportable'
    },

    destino: {
        titulo: 'Destinos registrados',
        descripcion: 'Listado de destinos disponibles.',
        columnas: [
            { campo: 'id_destino', titulo: 'ID' },
            { campo: 'nombre_destino', titulo: 'Nombre' },
            { campo: 'direccion', titulo: 'Dirección' },
            { campo: 'ruta', titulo: 'Ruta' }
        ],
        identificador: 'id_destino'
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
        ],
        identificador: 'matricula'
    }

};

let todasLasEntidades = [];
let registrosFiltrados = [];
let paginaActual = 1;
let entidadActual = 'enfermero';


// ==========================================
// INICIALIZACIÓN
// ==========================================

document.addEventListener('DOMContentLoaded', () => {

    const selector = document.getElementById('selectEntidad');
    const buscador = document.getElementById('inputBuscar');

    

    selector.addEventListener('change', () => {

        entidadActual = selector.value;

        buscador.value = '';
        paginaActual = 1;

        cargarEntidades();

    });

    buscador.addEventListener('input', () => {

        paginaActual = 1;
        filtrarEntidades();

    });

    document.addEventListener('entidad-guardada', () => {
    cargarEntidades();
});

    cargarEntidades();

});


// ==========================================
// CARGAR ENTIDADES
// ==========================================

async function cargarEntidades() {

    const tbody = document.getElementById('tablaEntidadesBody');

    tbody.innerHTML = `
        <tr>
            <td class="mensaje-tabla">Cargando registros...</td>
        </tr>
    `;

    actualizarEncabezado();

    try {

        const respuesta = await fetch(endpointsEntidades[entidadActual], {
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

        document.getElementById('cantidadEntidades').textContent =
            todasLasEntidades.length;

        filtrarEntidades();

    } catch (error) {

        console.error('Error al cargar entidades:', error);

        todasLasEntidades = [];
        registrosFiltrados = [];

        document.getElementById('cantidadEntidades').textContent = '0';
        document.getElementById('tablaEntidadesHead').innerHTML = '';

        tbody.innerHTML = '';

        const fila = document.createElement('tr');
        const celda = document.createElement('td');

        celda.className = 'mensaje-tabla';
        celda.textContent = error.message;

        fila.appendChild(celda);
        tbody.appendChild(fila);

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

    registrosFiltrados = todasLasEntidades.filter(registro => {

        return Object.values(registro).some(valor => {

            return String(valor ?? '')
                .toLowerCase()
                .includes(busqueda);

        });

    });

    paginaActual = 1;

    renderizarTabla();

}


// ==========================================
// RENDERIZAR TABLA
// ==========================================

function renderizarTabla() {

    const encabezado = document.getElementById('tablaEntidadesHead');
    const tbody = document.getElementById('tablaEntidadesBody');

    const configuracion = configuracionEntidades[entidadActual];

    encabezado.innerHTML = '';
    tbody.innerHTML = '';

    // Encabezados de las columnas.
    const filaEncabezado = document.createElement('tr');

    configuracion.columnas.forEach(columna => {

        const th = document.createElement('th');
        th.textContent = columna.titulo;

        filaEncabezado.appendChild(th);

    });

    const thAcciones = document.createElement('th');
    thAcciones.textContent = 'Acciones';
    filaEncabezado.appendChild(thAcciones);

    encabezado.appendChild(filaEncabezado);

    if (registrosFiltrados.length === 0) {

        const fila = document.createElement('tr');
        const celda = document.createElement('td');

        celda.colSpan = configuracion.columnas.length + 1;
        celda.className = 'mensaje-tabla';
        celda.textContent = 'No se encontraron registros.';

        fila.appendChild(celda);
        tbody.appendChild(fila);

        document.getElementById('informacionResultados').textContent =
            'No hay resultados para mostrar.';

        document.getElementById('contenedorPaginacion').innerHTML = '';

        return;

    }

    const inicio = (paginaActual - 1) * REGISTROS_POR_PAGINA;
    const fin = inicio + REGISTROS_POR_PAGINA;

    const registrosPagina = registrosFiltrados.slice(inicio, fin);

    registrosPagina.forEach(registro => {

        const fila = document.createElement('tr');

        configuracion.columnas.forEach(columna => {

            const celda = document.createElement('td');

            let valor = registro[columna.campo];

            if (['camilla', 'oxigeno', 'aislamiento'].includes(columna.campo)) {
                valor = Number(valor) === 1 ? 'Sí' : 'No';
            }

            celda.textContent =
                valor !== null && valor !== undefined && valor !== ''
                    ? valor
                    : '-';

            fila.appendChild(celda);

        });

        // Botones de acciones para cada registro.
        const celdaAcciones = document.createElement('td');
        const acciones = document.createElement('div');

        acciones.className = 'acciones-fila';

        const btnEditar = document.createElement('button');
        btnEditar.type = 'button';
        btnEditar.className = 'btn-editar';
        btnEditar.title = 'Editar entidad';
        btnEditar.innerHTML = '<i class="fa-solid fa-pen"></i>';

        btnEditar.addEventListener('click', () => {

            // Enviamos el registro seleccionado al modal.
            document.dispatchEvent(new CustomEvent('editar-entidad', {
                detail: {
                    tipo: entidadActual,
                    registro: registro
                }
            }));

        });

        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.className = 'btn-eliminar';
        btnEliminar.title = 'Eliminar entidad';
        btnEliminar.innerHTML = '<i class="fa-solid fa-trash"></i>';

        btnEliminar.addEventListener('click', () => {
            eliminarEntidad(registro);
        });

        acciones.append(btnEditar, btnEliminar);
        celdaAcciones.appendChild(acciones);
        fila.appendChild(celdaAcciones);

        tbody.appendChild(fila);

    });

    document.getElementById('informacionResultados').textContent =
        `Mostrando ${inicio + 1} - ${Math.min(fin, registrosFiltrados.length)} de ${registrosFiltrados.length} registros`;

    renderizarPaginacion();

}


// ==========================================
// PAGINACIÓN
// ==========================================

function renderizarPaginacion() {

    const contenedor = document.getElementById('contenedorPaginacion');

    contenedor.innerHTML = '';

    const totalPaginas = Math.ceil(
        registrosFiltrados.length / REGISTROS_POR_PAGINA
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
            renderizarTabla();

        });

        contenedor.appendChild(boton);

    }

}


// ==========================================
// ELIMINAR ENTIDAD
// ==========================================

async function eliminarEntidad(registro) {

    const configuracion = configuracionEntidades[entidadActual];
    const identificador = configuracion.identificador;
    const valorId = registro[identificador];

    const confirmar = confirm(
        `¿Está seguro de que desea eliminar este registro?\n\n${identificador}: ${valorId}`
    );

    if (!confirmar) {
        return;
    }

    // Los controladores recibirán el identificador por query string.
    const parametros = new URLSearchParams({
        [identificador]: valorId
    });

    try {

        const respuesta = await fetch(
            `${endpointsEntidades[entidadActual]}?${parametros.toString()}`,
            {
                method: 'DELETE',
                credentials: 'same-origin'
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(
                resultado.mensaje ||
                resultado.error ||
                'No se pudo eliminar el registro.'
            );
        }

        alert(resultado.mensaje || 'Registro eliminado correctamente.');

        await cargarEntidades();

    } catch (error) {

        console.error('Error al eliminar entidad:', error);

        alert(error.message);

    }

}