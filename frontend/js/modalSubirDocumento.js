let todosLosDocumentos = [];
let categoriaSeleccionada = 'todas';

document.addEventListener('DOMContentLoaded', () => {
    // Manejo de modales para el administrador
    const modal = document.getElementById('modalSubirDocumento');
    const btnAbrir = document.getElementById('btnAbrirModal');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCancelar = document.getElementById('btnCancelarModal');
    const formSubir = document.getElementById('formSubirDocumento');

    if (btnAbrir && modal) {
        btnAbrir.addEventListener('click', () => { modal.style.display = 'flex'; });
    }

    const cerrarModal = () => {
        if (modal) modal.style.display = 'none';
        if (formSubir) formSubir.reset();
    };

    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);

    // Cargar documentos iniciales
    cargarDocumentos();

    // Evento Subir Documento (Admin)
    if (formSubir) {
        formSubir.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formSubir);

            try {
                const response = await fetch('/kgade/KGA-Development-Studio/API/documentos/documentos.php', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await response.json();

                if (response.ok) {
                    alert(resultado.mensaje || 'Documento subido correctamente');
                    cerrarModal();
                    cargarDocumentos();
                } else {
                    alert(resultado.error || 'Error al subir el documento');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al conectar con el servidor.');
            }
        });
    }

    // -----------------------------------------------------------
    // LISTENERS PARA FILTRADO Y BÚSQUEDA
    // -----------------------------------------------------------
    
    // Buscador en vista Admin
    const inputBuscarAdmin = document.querySelector('.busqueda-documentos .buscador') || document.getElementById('inputBuscarAdmin');
    if (inputBuscarAdmin) {
        inputBuscarAdmin.addEventListener('input', aplicarFiltros);
    }

    // Buscador en vista Paciente
    const inputBuscarPublico = document.getElementById('inputBuscarPublico');
    if (inputBuscarPublico) {
        inputBuscarPublico.addEventListener('input', aplicarFiltros);
    }

    // Botones de categoría en vista Paciente
    const botonesCat = document.querySelectorAll('#contenedorCategorias .btn-categoria');
    botonesCat.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Remueve la clase activa de los otros botones
            botonesCat.forEach(b => b.classList.remove('active'));
            
            // Marca el clickeado como activo
            btn.classList.add('active');
            
            // Guarda la categoría seleccionada y aplica el filtro
            categoriaSeleccionada = btn.dataset.cat || 'todas';
            aplicarFiltros();
        });
    });
});

// Cargar lista completa desde la API
async function cargarDocumentos() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/documentos/documentos.php');
        if (!response.ok) return;

        todosLosDocumentos = await response.json();
        aplicarFiltros();

    } catch (error) {
        console.error('Error al cargar documentos:', error);
    }
}

// Función principal que filtra la lista y llama a los renderizadores
function aplicarFiltros() {
    const inputBuscar = document.getElementById('inputBuscarPublico') || document.querySelector('.busqueda-documentos .buscador');
    const busqueda = (inputBuscar?.value || '').toLowerCase().trim();

    const documentosFiltrados = todosLosDocumentos.filter(doc => {
        // 1. Filtro por Búsqueda (Título o Descripción)
        const titulo = String(doc.titulo_docs || '').toLowerCase();
        const descripcion = String(doc.descripcion_docs || '').toLowerCase();
        const coincideBusqueda = titulo.includes(busqueda) || descripcion.includes(busqueda);

        // 2. Filtro por Categoría
        const coincideCategoria = (categoriaSeleccionada === 'todas') || (doc.id_categoria === categoriaSeleccionada);

        return coincideBusqueda && coincideCategoria;
    });

    renderizarVistaAdmin(documentosFiltrados);
    renderizarVistaPacientes(documentosFiltrados);
}

// Renderiza la tabla del Administrador
function renderizarVistaAdmin(lista) {
    const tbody = document.querySelector('.tabla-documentos tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!Array.isArray(lista) || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No se encontraron documentos.</td></tr>';
        return;
    }

    lista.forEach(doc => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${doc.titulo_docs}</td>
            <td>${formatearFecha(doc.fecha)}</td>
            <td>
                <button class="btn-eliminar-doc" onclick="eliminarDocumento('${doc.id_documentos}')">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Renderiza las Tarjetas de Pacientes
// Renderiza las Tarjetas de Pacientes
function renderizarVistaPacientes(lista) {
    const contenedorPacientes = document.querySelector('.contenedor-documentos');
    if (!contenedorPacientes) return;

    contenedorPacientes.innerHTML = '';

    if (!Array.isArray(lista) || lista.length === 0) {
        contenedorPacientes.innerHTML = '<p style="text-align:center; width:100%; color: #666;">No se encontraron documentos en esta categoría o búsqueda.</p>';
        return;
    }

    lista.forEach(doc => {
        const rutaArchivo = `/kgade/KGA-Development-Studio/uploads/documentos/${doc.id_documentos}.pdf`;

        const card = document.createElement('div');
        card.className = 'card-documento';
        card.innerHTML = `
            <h3>${doc.titulo_docs}</h3>
            <p>${doc.descripcion_docs || 'Sin descripción disponible.'}</p>
            <p><strong>Fecha:</strong> ${formatearFecha(doc.fecha)}</p>
            
            <div class="acciones">
                <button class="btn-ver" title="Ver documento" onclick="window.open('${rutaArchivo}', '_blank')">
                    <i class="fa-solid fa-eye"></i>
                </button>
                
                <button class="btn-descargar" title="Descargar documento" onclick="descargarDocumento('${rutaArchivo}', '${doc.titulo_docs}')">
                    <i class="fa-solid fa-download"></i>
                </button>
                
                <button class="btn-compartir" title="Compartir documento" onclick="compartirDocumento('${doc.titulo_docs}', '${rutaArchivo}')">
                    <i class="fa-solid fa-share-nodes"></i>
                </button>
            </div>
        `;
        contenedorPacientes.appendChild(card);
    });
}

// Funciones auxiliares
function formatearFecha(fechaStr) {
    if (!fechaStr) return 'N/A';
    const partes = fechaStr.split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : fechaStr;
}

function compartirDocumento(titulo, ruta) {
    const urlCompleta = window.location.origin + ruta;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(urlCompleta);
        alert(`Enlace copiado al portapapeles para: ${titulo}`);
    } else {
        alert(`Enlace del documento: ${urlCompleta}`);
    }
}

async function eliminarDocumento(idDoc) {
    if (!confirm(`¿Desea eliminar el documento con ID ${idDoc}?`)) return;

    try {
        const response = await fetch(`/kgade/KGA-Development-Studio/API/documentos/documentos.php?id=${idDoc}`, {
            method: 'DELETE'
        });

        const res = await response.json();
        if (response.ok) {
            alert(res.mensaje);
            cargarDocumentos();
        } else {
            alert(res.error || 'No se pudo eliminar');
        }
    } catch (error) {
        console.error('Error al eliminar:', error);
    }
}