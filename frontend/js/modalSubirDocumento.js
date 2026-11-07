// ======================================================
// CONFIGURACIÓN GENERAL
// ======================================================

const API_DOCUMENTOS =
    '/kgade/KGA-Development-Studio/API/documentos/documentos.php';

const API_CATEGORIAS =
    '/kgade/KGA-Development-Studio/API/categorias/categorias.php';

// Información que se obtiene desde el servidor.
let todosLosDocumentos = [];
let categoriasActivas = [];
let todasLasCategorias = [];
let idDocumentoEnEdicion = null;

// Categoría seleccionada en el filtro público.
let categoriaSeleccionada = 'todas';


// ======================================================
// INICIALIZACIÓN
// ======================================================

document.addEventListener('DOMContentLoaded', () => {

    inicializarModal();
    inicializarFormulario();
    inicializarBuscadores();
    inicializarFiltros();

    inicializarGestionCategorias();
    inicializarAutocompletado();

    // Cargar los datos necesarios para ambas vistas.
    cargarDocumentos();
    cargarCategorias();

});


// ======================================================
// MODAL DE ADMINISTRACIÓN
// ======================================================

function inicializarModal() {

    const modal = document.getElementById('modalSubirDocumento');

    const btnAbrir = document.getElementById('btnAbrirModal');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCancelar = document.getElementById('btnCancelarModal');

    const formulario = document.getElementById('formSubirDocumento');

    // Abrir el modal para subir un documento nuevo.
    if (btnAbrir && modal) {

        btnAbrir.addEventListener('click', () => {

            limpiarFormularioDocumento();

            modal.style.display = 'flex';

            cargarCategorias();

        });

    }

    // Cerrar el modal y limpiar los campos.
    const cerrarModal = () => {

        modal.style.display = 'none';

        limpiarFormularioDocumento();

    };

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    if (modal) {

        modal.addEventListener('click', (e) => {

            if (e.target === modal) {
                cerrarModal();
            }

        });

    }

}

// ======================================================
// PREPARAR EL MODAL PARA SUBIR O EDITAR
// ======================================================

function limpiarFormularioDocumento() {

    const formulario = document.getElementById('formSubirDocumento');

    if (!formulario) return;

    formulario.reset();

    idDocumentoEnEdicion = null;

    const idCategoria = document.getElementById('id_categoria');

    if (idCategoria) {
        idCategoria.value = '';
    }

    const archivo = document.getElementById('documentFile');

    if (archivo) {
        archivo.required = true;
    }

    const encabezado = document.querySelector(
        '#modalSubirDocumento .modal-header h2'
    );

    const subtitulo = formulario.querySelector('h3');

    const botonGuardar = formulario.querySelector(
        'button[type="submit"]'
    );

    if (encabezado) {
        encabezado.textContent = 'Administrar documentos';
    }

    if (subtitulo) {
        subtitulo.textContent = 'Subir nuevo documento';
    }

    if (botonGuardar) {
        botonGuardar.innerHTML =
            '<i class="fa-solid fa-upload"></i> Subir documento';
    }

    ocultarSugerencias();

}


// Abrir el modal con los datos de un documento existente.
function abrirModalEdicion(doc) {

    const formulario = document.getElementById('formSubirDocumento');

    const modal = document.getElementById('modalSubirDocumento');

    if (!formulario || !modal) return;

    // Guardar el ID del documento que se está modificando.
    idDocumentoEnEdicion = doc.id_documentos;

    // Completar los campos con la información actual.
    document.getElementById('documentTitle').value =
        doc.titulo_docs || '';

    document.getElementById('documentDescription').value =
        doc.descripcion_docs || '';

    document.getElementById('categorySearch').value =
        doc.nombre_cat || '';

    document.getElementById('id_categoria').value =
        doc.id_categoria || '';

    // El PDF es opcional al editar.
    // Si no se selecciona uno nuevo, se conserva el existente.
    document.getElementById('documentFile').required = false;

    const encabezado = modal.querySelector('.modal-header h2');

    const subtitulo = formulario.querySelector('h3');

    const botonGuardar = formulario.querySelector(
        'button[type="submit"]'
    );

    if (encabezado) {
        encabezado.textContent = 'Editar documento';
    }

    if (subtitulo) {
        subtitulo.textContent = 'Modificar información del documento';
    }

    if (botonGuardar) {
        botonGuardar.innerHTML =
            '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
    }

    modal.style.display = 'flex';

    cargarCategorias();

}


// ======================================================
// FORMULARIO PARA SUBIR DOCUMENTOS
// ======================================================

function inicializarFormulario() {

    const formulario = document.getElementById('formSubirDocumento');

    if (!formulario) return;

    formulario.addEventListener('submit', async (e) => {

        e.preventDefault();

        const idCategoria = document.getElementById('id_categoria')?.value;

        if (!idCategoria) {

            alert('Debe seleccionar una categoría existente.');

            document.getElementById('categorySearch')?.focus();

            return;
        }

        const formData = new FormData(formulario);

        // Diferenciar una nueva subida de una edición.
        const estaEditando = idDocumentoEnEdicion !== null;

        if (estaEditando) {

            formData.append('accion', 'editar');

            formData.append(
                'id_documentos',
                idDocumentoEnEdicion
            );

        }

        try {

            const response = await fetch(API_DOCUMENTOS, {
                method: 'POST',
                body: formData
            });

            const resultado = await response.json();

            if (!response.ok) {

                alert(
                    resultado.error ||
                    'No se pudo guardar el documento.'
                );

                return;
            }

            alert(resultado.mensaje);

            const modal = document.getElementById('modalSubirDocumento');

            if (modal) {
                modal.style.display = 'none';
            }

            limpiarFormularioDocumento();

            // Actualizar la tabla con los datos nuevos.
            await cargarDocumentos();

        } catch (error) {

            console.error('Error al guardar documento:', error);

            alert('No se pudo conectar con el servidor.');

        }

    });

}


// ======================================================
// CARGAR DOCUMENTOS DESDE LA API
// ======================================================

async function cargarDocumentos() {

    try {

        const response = await fetch(API_DOCUMENTOS);

        if (!response.ok) {
            throw new Error('No se pudieron obtener los documentos');
        }

        const datos = await response.json();

        if (!Array.isArray(datos)) {
            throw new Error('La respuesta de documentos no es válida');
        }

        todosLosDocumentos = datos;

        aplicarFiltros();

    } catch (error) {

        console.error('Error al cargar documentos:', error);

        const tbody = document.querySelector('.tabla-documentos tbody');

        if (tbody) {
            tbody.innerHTML =
                '<tr><td colspan="4">No se pudieron cargar los documentos.</td></tr>';
        }

        const contenedor = document.querySelector('.contenedor-documentos');

        if (contenedor) {
            contenedor.textContent = 'No se pudieron cargar los documentos.';
        }

    }

}


// ======================================================
// CARGAR CATEGORÍAS DESDE LA API
// ======================================================

async function cargarCategorias() {

    const esAdmin = Boolean(
        document.getElementById('listaCategoriasAdmin')
    );

    try {

        const url = esAdmin
            ? `${API_CATEGORIAS}?admin=1`
            : API_CATEGORIAS;

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('No se pudieron obtener las categorías');
        }

        const datos = await response.json();

        if (!Array.isArray(datos)) {
            throw new Error('La respuesta de categorías no es válida');
        }

        if (esAdmin) {

            // El administrador recibe activas y deshabilitadas.
            todasLasCategorias = datos;

            categoriasActivas = datos.filter(
                categoria => categoria.estado_cat === 'activa'
            );

            renderizarCategoriasAdmin();

        } else {

            // La vista pública recibe solamente las activas.
            categoriasActivas = datos;

            renderizarFiltrosPublicos();

        }

        // Actualizar las sugerencias del autocompletado.
        renderizarSugerencias(
            document.getElementById('categorySearch')?.value || ''
        );

    } catch (error) {

        console.error('Error al cargar categorías:', error);

        const lista = document.getElementById('listaCategoriasAdmin');

        if (lista) {
            lista.textContent = 'No se pudieron cargar las categorías.';
        }

    }

}


// ======================================================
// ADMINISTRAR CATEGORÍAS
// ======================================================

function inicializarGestionCategorias() {

    const botonCrear = document.getElementById('btnCrearCategoria');

    const inputNombre = document.getElementById('inputNuevaCategoria');

    if (!botonCrear || !inputNombre) return;

    botonCrear.addEventListener('click', crearCategoria);

    inputNombre.addEventListener('keydown', (e) => {

        if (e.key === 'Enter') {

            e.preventDefault();

            crearCategoria();

        }

    });

}


// Crear una categoría nueva.
async function crearCategoria() {

    const input = document.getElementById('inputNuevaCategoria');

    const nombre = input.value.trim();

    if (!nombre) {

        alert('Debe ingresar un nombre para la categoría.');

        input.focus();

        return;
    }

    try {

        const response = await fetch(API_CATEGORIAS, {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                accion: 'crear',
                nombre_cat: nombre
            })

        });

        const resultado = await response.json();

        if (!response.ok) {

            alert(resultado.error || 'No se pudo crear la categoría');

            return;
        }

        alert(resultado.mensaje);

        input.value = '';

        await cargarCategorias();

    } catch (error) {

        console.error('Error al crear categoría:', error);

        alert('No se pudo conectar con el servidor.');

    }

}


// Cambiar el estado de una categoría.
async function cambiarEstadoCategoria(idCategoria, nuevoEstado) {

    const accionTexto = nuevoEstado === 'activa'
        ? 'reactivar'
        : 'deshabilitar';

    if (!confirm(`¿Desea ${accionTexto} esta categoría?`)) {
        return;
    }

    try {

        const response = await fetch(API_CATEGORIAS, {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                accion: 'cambiarEstado',
                id_categoria: idCategoria,
                estado_cat: nuevoEstado
            })

        });

        const resultado = await response.json();

        if (!response.ok) {

            alert(resultado.error || 'No se pudo actualizar la categoría');

            return;
        }

        alert(resultado.mensaje);

        await cargarCategorias();

    } catch (error) {

        console.error('Error al cambiar estado:', error);

        alert('No se pudo conectar con el servidor.');

    }

}


// Mostrar categorías en el panel del administrador.
function renderizarCategoriasAdmin() {

    const lista = document.getElementById('listaCategoriasAdmin');

    if (!lista) return;

    lista.innerHTML = '';

    if (todasLasCategorias.length === 0) {

        const mensaje = document.createElement('li');

        mensaje.textContent = 'Todavía no hay categorías creadas.';

        lista.appendChild(mensaje);

        return;
    }

    todasLasCategorias.forEach(categoria => {

        const elemento = document.createElement('li');

        elemento.className = 'item-categoria-admin';

        const nombre = document.createElement('span');

        nombre.textContent =
            `${categoria.nombre_cat} (${categoria.estado_cat})`;

        const boton = document.createElement('button');

        if (categoria.estado_cat === 'activa') {

            boton.textContent = 'Deshabilitar';

            boton.className = 'btn-deshabilitar-categoria';

            boton.addEventListener('click', () => {

                cambiarEstadoCategoria(
                    categoria.id_categoria,
                    'deshabilitada'
                );

            });

        } else {

            boton.textContent = 'Reactivar';

            boton.className = 'btn-reactivar-categoria';

            boton.addEventListener('click', () => {

                cambiarEstadoCategoria(
                    categoria.id_categoria,
                    'activa'
                );

            });

        }

        elemento.appendChild(nombre);
        elemento.appendChild(boton);

        lista.appendChild(elemento);

    });

}


// ======================================================
// AUTOCOMPLETADO DE CATEGORÍAS
// ======================================================

function inicializarAutocompletado() {

    const input = document.getElementById('categorySearch');

    const idCategoria = document.getElementById('id_categoria');

    if (!input || !idCategoria) return;

    input.addEventListener('input', () => {

        // Si el texto cambia, la selección anterior deja de ser válida.
        idCategoria.value = '';

        renderizarSugerencias(input.value);

    });

    input.addEventListener('focus', () => {

        renderizarSugerencias(input.value);

    });

    // Ocultar sugerencias al hacer clic fuera.
    document.addEventListener('click', (e) => {

        const contenedor = document.querySelector('.autocomplete-categoria');

        if (contenedor && !contenedor.contains(e.target)) {
            ocultarSugerencias();
        }

    });

}


// Generar las sugerencias según lo que se escribió.
function renderizarSugerencias(texto) {

    const lista = document.getElementById('sugerenciasCategorias');

    if (!lista) return;

    lista.innerHTML = '';

    const busqueda = texto.trim().toLowerCase();

    const coincidencias = categoriasActivas.filter(categoria => {

        return categoria.nombre_cat
            .toLowerCase()
            .includes(busqueda);

    });

    if (coincidencias.length === 0) {

        const elemento = document.createElement('li');

        elemento.textContent = 'No hay categorías disponibles con ese nombre.';

        lista.appendChild(elemento);

        lista.style.display = 'block';

        return;
    }

    coincidencias.forEach(categoria => {

        const elemento = document.createElement('li');

        elemento.textContent = categoria.nombre_cat;

        elemento.addEventListener('click', () => {

            seleccionarCategoria(categoria);

        });

        lista.appendChild(elemento);

    });

    lista.style.display = 'block';

}


// Guardar el ID real de la categoría seleccionada.
function seleccionarCategoria(categoria) {

    const input = document.getElementById('categorySearch');

    const idCategoria = document.getElementById('id_categoria');

    if (!input || !idCategoria) return;

    input.value = categoria.nombre_cat;

    idCategoria.value = categoria.id_categoria;

    ocultarSugerencias();

}


// Ocultar lista de sugerencias.
function ocultarSugerencias() {

    const lista = document.getElementById('sugerenciasCategorias');

    if (lista) {
        lista.style.display = 'none';
    }

}


function inicializarBuscadores() {
    const inputAdmin = document.getElementById('inputBuscarAdmin');

    if (inputAdmin) {
        inputAdmin.addEventListener('input', aplicarFiltros);
    }
}


// ======================================================
// FILTROS PÚBLICOS
// ======================================================

function inicializarFiltros() {

    const input = document.getElementById('inputBuscarPublico');

    if (input) {

        input.addEventListener('input', aplicarFiltros);

    }

}


// Generar los botones desde las categorías activas.
function renderizarFiltrosPublicos() {

    const contenedor = document.getElementById('contenedorCategorias');

    if (!contenedor) return;

    contenedor.innerHTML = '';

    const botonTodas = document.createElement('button');

    botonTodas.textContent = 'Todas';

    botonTodas.className = 'btn-categoria active';

    botonTodas.dataset.cat = 'todas';

    botonTodas.addEventListener('click', () => {

        seleccionarFiltro('todas', botonTodas);

    });

    contenedor.appendChild(botonTodas);

    categoriasActivas.forEach(categoria => {

        const boton = document.createElement('button');

        boton.textContent = categoria.nombre_cat;

        boton.className = 'btn-categoria';

        boton.dataset.cat = categoria.id_categoria;

        boton.addEventListener('click', () => {

            seleccionarFiltro(categoria.id_categoria, boton);

        });

        contenedor.appendChild(boton);

    });

}


// Cambiar el filtro seleccionado.
function seleccionarFiltro(idCategoria, botonSeleccionado) {

    categoriaSeleccionada = idCategoria;

    const botones = document.querySelectorAll(
        '#contenedorCategorias .btn-categoria'
    );

    botones.forEach(boton => {
        boton.classList.remove('active');
    });

    botonSeleccionado.classList.add('active');

    aplicarFiltros();

}


// ======================================================
// FILTRAR DOCUMENTOS
// ======================================================

function aplicarFiltros() {

    const inputAdmin = document.getElementById('inputBuscarAdmin');
    const inputPublico = document.getElementById('inputBuscarPublico');

    const inputBuscar = inputAdmin || inputPublico;

    const busqueda = (inputBuscar?.value || '')
        .toLowerCase()
        .trim();

    // Filtrar por título, descripción y categoría.
    const documentosFiltrados = todosLosDocumentos.filter(doc => {

        const titulo = String(doc.titulo_docs || '').toLowerCase();

        const descripcion = String(
            doc.descripcion_docs || ''
        ).toLowerCase();

        const categoria = String(
            doc.nombre_cat || ''
        ).toLowerCase();

        const coincideBusqueda =
            titulo.includes(busqueda) ||
            descripcion.includes(busqueda) ||
            categoria.includes(busqueda);

        const coincideCategoria =
            categoriaSeleccionada === 'todas' ||
            String(doc.id_categoria) === String(categoriaSeleccionada);

        return coincideBusqueda && coincideCategoria;

    });

    // Detectar si estamos en la página del administrador.
    const esAdmin = Boolean(inputAdmin);

    // Los documentos deshabilitados solamente se muestran al administrador.
    const documentosVisibles = esAdmin
        ? documentosFiltrados
        : documentosFiltrados.filter(
            doc => doc.estado_cat === 'activa'
        );

    // Renderizar cada vista una sola vez.
    renderizarVistaAdmin(documentosVisibles);
    renderizarVistaPublica(documentosVisibles);

}


// ======================================================
// TABLA DEL ADMINISTRADOR
// ======================================================
function renderizarVistaAdmin(lista) {

    const tbody = document.querySelector('.tabla-documentos tbody');

    if (!tbody) return;

    tbody.innerHTML = '';

    if (lista.length === 0) {

        const fila = document.createElement('tr');

        const celda = document.createElement('td');

        celda.colSpan = 4;

        celda.textContent = 'No se encontraron documentos.';

        fila.appendChild(celda);

        tbody.appendChild(fila);

        return;
    }

    lista.forEach(doc => {

        // Crear una fila nueva para cada documento.
        const fila = document.createElement('tr');

        // Columna: Documento.
        const celdaTitulo = document.createElement('td');

        celdaTitulo.textContent = doc.titulo_docs || 'Sin título';

        // Columna: Categoría.
        const celdaCategoria = document.createElement('td');

        celdaCategoria.textContent = doc.nombre_cat || 'Sin categoría';

        // Columna: Fecha.
        const celdaFecha = document.createElement('td');

        celdaFecha.textContent = formatearFecha(doc.fecha);

        // Columna: Acciones.
        const celdaAcciones = document.createElement('td');

        // Botón para visualizar el PDF.
        const botonVer = document.createElement('button');

        const botonEditar = document.createElement('button');

        botonEditar.type = 'button';
        botonEditar.textContent = 'Editar';
        botonEditar.className = 'btn-editar-doc';

        botonEditar.addEventListener('click', () => {

            abrirModalEdicion(doc);

        });

        // Botón para eliminar el documento.
        const botonEliminar = document.createElement('button');

        botonEliminar.type = 'button';
        botonEliminar.textContent = 'Eliminar';
        botonEliminar.className = 'btn-eliminar-doc';

        botonEliminar.addEventListener('click', () => {

            eliminarDocumento(doc.id_documentos);

        });

        // Agregar los botones a la columna de acciones.
        celdaAcciones.appendChild(botonVer);
        celdaAcciones.appendChild(botonEditar);
        celdaAcciones.appendChild(botonEliminar);

        // Agregar las cuatro columnas a la fila.
        fila.appendChild(celdaTitulo);
        fila.appendChild(celdaCategoria);
        fila.appendChild(celdaFecha);
        fila.appendChild(celdaAcciones);

        // Agregar la fila a la tabla.
        tbody.appendChild(fila);

    });

}


// ======================================================
// TARJETAS DE LA VISTA PÚBLICA
// ======================================================

function renderizarVistaPublica(lista) {

    const contenedor = document.querySelector('.contenedor-documentos');

    if (!contenedor) return;

    contenedor.innerHTML = '';

    if (lista.length === 0) {

        const mensaje = document.createElement('p');

        mensaje.textContent =
            'No se encontraron documentos en esta categoría o búsqueda.';

        contenedor.appendChild(mensaje);

        return;
    }

    lista.forEach(doc => {

        const rutaArchivo =
            `${API_DOCUMENTOS}?id=${encodeURIComponent(doc.id_documentos)}`;

        const tarjeta = document.createElement('article');

        tarjeta.className = 'card-documento';

        const titulo = document.createElement('h3');

        titulo.textContent = doc.titulo_docs;

        const descripcion = document.createElement('p');

        descripcion.textContent =
            doc.descripcion_docs || 'Sin descripción disponible.';

        const categoria = document.createElement('p');

        const nombreCategoria = document.createElement('strong');

        nombreCategoria.textContent = 'Categoría: ';

        categoria.appendChild(nombreCategoria);

        categoria.appendChild(
            document.createTextNode(doc.nombre_cat || 'Sin categoría')
        );

        const fecha = document.createElement('p');

        const etiquetaFecha = document.createElement('strong');

        etiquetaFecha.textContent = 'Fecha: ';

        fecha.appendChild(etiquetaFecha);

        fecha.appendChild(
            document.createTextNode(formatearFecha(doc.fecha))
        );

        const acciones = document.createElement('div');

        acciones.className = 'acciones';

        // Botón para ver el documento.
        const botonVer = document.createElement('button');

        botonVer.className = 'btn-ver';

        botonVer.title = 'Ver documento';

        botonVer.innerHTML = '<i class="fa-solid fa-eye"></i>';

        botonVer.addEventListener('click', () => {
            verPDF(doc.id_documentos);
        });

        // Botón para descargar el documento.
        const botonDescargar = document.createElement('button');

        botonDescargar.className = 'btn-descargar';

        botonDescargar.title = 'Descargar documento';

        botonDescargar.innerHTML =
            '<i class="fa-solid fa-download"></i>';

        botonDescargar.addEventListener('click', () => {

            descargarDocumento(rutaArchivo, doc.titulo_docs);

        });

        // Botón para compartir el enlace.
        const botonCompartir = document.createElement('button');

        botonCompartir.className = 'btn-compartir';

        botonCompartir.title = 'Compartir documento';

        botonCompartir.innerHTML =
            '<i class="fa-solid fa-share-nodes"></i>';

        botonCompartir.addEventListener('click', () => {

            compartirDocumento(doc.titulo_docs, rutaArchivo);

        });

        acciones.appendChild(botonVer);
        acciones.appendChild(botonDescargar);
        acciones.appendChild(botonCompartir);

        tarjeta.appendChild(titulo);
        tarjeta.appendChild(descripcion);
        tarjeta.appendChild(categoria);
        tarjeta.appendChild(fecha);
        tarjeta.appendChild(acciones);

        contenedor.appendChild(tarjeta);

    });

}

function verPDF(idDocumento) {
    const visorPDF = document.getElementById('visorPDF');

    if (!visorPDF) {
        alert('No se encontró el visor de documentos.');
        return;
    }

    visorPDF.src = `${API_DOCUMENTOS}?id=${encodeURIComponent(idDocumento)}`;
    visorPDF.hidden = false;
    visorPDF.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ======================================================
// FUNCIONES AUXILIARES
// ======================================================

// Convertir fecha YYYY-MM-DD a DD/MM/YYYY.
function formatearFecha(fecha) {

    if (!fecha) return 'N/A';

    const partes = fecha.split('-');

    if (partes.length !== 3) {
        return fecha;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;

}


// Descargar PDF.
function descargarDocumento(ruta, titulo) {

    const enlace = document.createElement('a');

    enlace.href = ruta;

    enlace.download = `${titulo}.pdf`;

    document.body.appendChild(enlace);

    enlace.click();

    enlace.remove();

}


// Compartir enlace del PDF.
async function compartirDocumento(titulo, ruta) {

    const urlCompleta = window.location.origin + ruta;

    try {

        await navigator.clipboard.writeText(urlCompleta);

        alert(`Enlace copiado al portapapeles para: ${titulo}`);

    } catch (error) {

        console.error('No se pudo copiar el enlace:', error);

        alert(`Enlace del documento: ${urlCompleta}`);

    }

}


// ======================================================
// ELIMINAR DOCUMENTO
// ======================================================

async function eliminarDocumento(idDoc) {

    if (!confirm('¿Desea eliminar este documento?')) {
        return;
    }

    try {

        const response = await fetch(
            `${API_DOCUMENTOS}?id=${encodeURIComponent(idDoc)}`,
            {
                method: 'DELETE'
            }
        );

        const resultado = await response.json();

        if (!response.ok) {

            alert(resultado.error || 'No se pudo eliminar el documento');

            return;
        }

        alert(resultado.mensaje);

        const visorPDF = document.getElementById('visorPDF');
        if (visorPDF && visorPDF.src.includes(encodeURIComponent(idDoc))) {
            visorPDF.removeAttribute('src');
            visorPDF.hidden = true;
        }

        cargarDocumentos();

    } catch (error) {

        console.error('Error al eliminar documento:', error);

        alert('No se pudo conectar con el servidor.');

    }

}