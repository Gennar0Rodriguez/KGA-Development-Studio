let usuarioActual = null;
let todosLosUsuarios = []; 
let paginaActual = 1;
const usuariosPorPagina = 10;

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    const inputBuscar = document.getElementById('inputBuscar');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarUsuarios);
    }

    const formAgregarUsuario = document.getElementById('formAgregarUsuario');
    if (formAgregarUsuario) {
        formAgregarUsuario.addEventListener('submit', crearUsuario);
        
        formAgregarUsuario.addEventListener('input', actualizarBotonFormulario);
        
        formAgregarUsuario.addEventListener('reset', () => {
            setTimeout(actualizarBotonFormulario, 10);
        });
    }
});

async function cargarSesion() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/sesion.php');
        if (!response.ok) return;

        const datos = await response.json();

        if (datos.autenticado) {
            usuarioActual = datos.usuario;

            const elemNombre = document.getElementById('nombreUsuario');
            const elemRol = document.getElementById('rolUsuario');

            if (elemNombre) elemNombre.textContent = usuarioActual.nombre || usuarioActual.nombre_admin;
            if (elemRol) elemRol.textContent = usuarioActual.rol || usuarioActual.cargo;
        }
    } catch (error) {
        console.error('Error al cargar sesión:', error);
    }
}

async function cargarUsuarios() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/usuario/usuarios.php');
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        
        todosLosUsuarios = await response.json();
        
        paginaActual = 1;
        renderizarTablaPaginada(todosLosUsuarios);
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        const tbody = document.getElementById('tablaUsuariosBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Error al obtener usuarios del servidor.</td></tr>`;
        }
    }
}

function renderizarTablaPaginada(lista) {
    const tbody = document.getElementById('tablaUsuariosBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!Array.isArray(lista) || lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">No hay usuarios registrados.</td></tr>`;
        renderizarPaginador([]);
        return;
    }

    // Paginación: Cortar 10 usuarios según la página actual
    const inicio = (paginaActual - 1) * usuariosPorPagina;
    const fin = inicio + usuariosPorPagina;
    const usuariosPagina = lista.slice(inicio, fin);

    usuariosPagina.forEach((usuario) => {
        const fila = document.createElement('tr');

        const ciVal = usuario.ci_admin || usuario.ci || '';
        const nomVal = usuario.nombre_admin || usuario.nombre || '';
        const apeVal = usuario.apellido_admin || usuario.apellido || '';
        const cargoVal = usuario.cargo || usuario.rol || '';

        const celdaCi = document.createElement('td');
        celdaCi.textContent = ciVal;
        fila.appendChild(celdaCi);

        const celdaNombre = document.createElement('td');
        celdaNombre.textContent = nomVal;
        fila.appendChild(celdaNombre);

        const celdaApellido = document.createElement('td');
        celdaApellido.textContent = apeVal;
        fila.appendChild(celdaApellido);

        const celdaRol = document.createElement('td');
        celdaRol.textContent = cargoVal;
        fila.appendChild(celdaRol);

        // --- CELDAS DE ACCIONES (EDITAR Y ELIMINAR) ---
        const celdaAcciones = document.createElement('td');

        // Botón Editar (con ícono de lápiz)
        const botonEditar = document.createElement('button');
        botonEditar.className = 'btn-editar';
        botonEditar.dataset.ci = ciVal;
        botonEditar.innerHTML = '<i class="fa-solid fa-pen"></i>';
        botonEditar.style.cursor = 'pointer';
        botonEditar.style.marginRight = '5px';

        botonEditar.addEventListener('click', () => {
            editarUsuario(usuario);
        });

        const botonEliminar = document.createElement('button');
        botonEliminar.className = 'btn-eliminar';
        botonEliminar.dataset.ci = ciVal;
        botonEliminar.innerHTML = '<i class="fa-solid fa-trash"></i>';
        botonEliminar.style.cursor = 'pointer';

        botonEliminar.addEventListener('click', () => {
            eliminarUsuario(ciVal);
        });

        celdaAcciones.appendChild(botonEditar);
        celdaAcciones.appendChild(botonEliminar);
        fila.appendChild(celdaAcciones);

        tbody.appendChild(fila);
    });

    renderizarPaginador(lista);
}

function renderizarPaginador(lista) {
    let contenedorPaginacion = document.getElementById('contenedorPaginacion');
    const seccionTabla = document.querySelector('.usuarios');
    
    if (!contenedorPaginacion && seccionTabla) {
        contenedorPaginacion = document.createElement('div');
        contenedorPaginacion.id = 'contenedorPaginacion';
        contenedorPaginacion.style.marginTop = '15px';
        contenedorPaginacion.style.display = 'flex';
        contenedorPaginacion.style.justifyContent = 'center';
        contenedorPaginacion.style.gap = '8px';
        seccionTabla.appendChild(contenedorPaginacion);
    }

    if (!contenedorPaginacion) return;

    contenedorPaginacion.innerHTML = '';
    const totalPaginas = Math.ceil(lista.length / usuariosPorPagina);

    if (totalPaginas <= 1) return;

    for (let i = 1; i <= totalPaginas; i++) {
        const btnPagina = document.createElement('button');
        btnPagina.textContent = i;
        btnPagina.style.padding = '5px 10px';
        btnPagina.style.cursor = 'pointer';
        
        if (i === paginaActual) {
            btnPagina.style.fontWeight = 'bold';
            btnPagina.style.backgroundColor = '#0284C7';
            btnPagina.style.color = '#fff';
            btnPagina.style.border = '1px solid #0284C7';
            btnPagina.style.borderRadius = '4px';
        }

        btnPagina.addEventListener('click', () => {
            paginaActual = i;
            renderizarTablaPaginada(lista);
        });

        contenedorPaginacion.appendChild(btnPagina);
    }
}

function filtrarUsuarios() {
    const busqueda = (document.getElementById('inputBuscar')?.value || '').toLowerCase().trim();

    const usuariosFiltrados = todosLosUsuarios.filter((asd) => {
        const ci = String(asd.ci_admin || asd.ci || '').toLowerCase();
        const nombre = String(asd.nombre_admin || asd.nombre || '').toLowerCase();
        const apellido = String(asd.apellido_admin || asd.apellido || '').toLowerCase();

        return ci.includes(busqueda) || nombre.includes(busqueda) || apellido.includes(busqueda);
    });

    paginaActual = 1;
    renderizarTablaPaginada(usuariosFiltrados);
}

function editarUsuario(usuario) {
    const ciVal = usuario.ci_admin || usuario.ci || '';
    const nomVal = usuario.nombre_admin || usuario.nombre || '';
    const apeVal = usuario.apellido_admin || usuario.apellido || '';
    const userVal = usuario.user_name || usuario.user || '';
    const cargoVal = usuario.cargo || usuario.rol || '';

    document.getElementById('ci').value = ciVal;
    document.getElementById('nombre').value = nomVal;
    document.getElementById('apellido').value = apeVal;
    document.getElementById('user').value = userVal;

    const selectRol = document.getElementById('rol');
    if (selectRol) selectRol.value = cargoVal.toLowerCase();

    const botonAgregar = document.getElementById('btn-agregar');
    if (botonAgregar) {
        botonAgregar.innerHTML = 'Editar Usuario <i class="fa-solid fa-pen"></i>';
    }
}

function actualizarBotonFormulario() {
    const ci = document.getElementById('ci')?.value.trim() || '';
    const nombre = document.getElementById('nombre')?.value.trim() || '';
    const apellido = document.getElementById('apellido')?.value.trim() || '';
    const user = document.getElementById('user')?.value.trim() || '';

    const botonAgregar = document.getElementById('btn-agregar');
    if (!botonAgregar) return;

    // Si TODOS los campos de texto están vacíos, vuelve a "Añadir Usuario"
    if (ci === '' && nombre === '' && apellido === '' && user === '') {
        botonAgregar.innerHTML = 'Añadir Usuario <i class="fa-solid fa-user-plus"></i>';
    }
}

async function crearUsuario(event) {
    event.preventDefault();

    const msg = document.getElementById('msgResultado');
    if (msg) {
        msg.textContent = "Procesando...";
        msg.style.color = "#0284C7";
    }

    const datos = {
        ci: document.getElementById('ci').value,
        nombre: document.getElementById('nombre').value,
        apellido: document.getElementById('apellido').value,
        pass: document.getElementById('pass').value,
        rol: document.getElementById('rol').value
    };

    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/usuario/usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const textoRespuesta = await response.text();

        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            console.error("Respuesta RAW del servidor (HTML de error):", textoRespuesta);
            throw new Error("El servidor no devolvió JSON. Revisa la consola para ver el HTML devuelto.");
        }

        if (!response.ok) {
            if (msg) {
                msg.textContent = resultado.error || 'No se pudo procesar la solicitud.';
                msg.style.color = '#dc2626';
            }
            return;
        }

        if (msg) {
            msg.textContent = '¡Usuario guardado correctamente!';
            msg.style.color = '#16a34a';
        }

        event.target.reset();
        cargarUsuarios();

    } catch (error) {
        console.error('Error al guardar usuario:', error);
        if (msg) {
            msg.textContent = error.message || 'Error de conexión con el servidor.';
            msg.style.color = '#dc2626';
        }
    }
}

async function eliminarUsuario(ci) {
    if (!confirm(`¿Desea eliminar el usuario con CI ${ci}?`)) {
        return;
    }

    try {
        const response = await fetch(`/KGA-Development-Studio/API/usuario/usuarios.php?id=${ci}`, {
            method: 'DELETE'
        });

        const datos = await response.json();

        if (!response.ok) {
            alert(datos.error || 'No se pudo eliminar el usuario.');
            return;
        }

        alert(datos.mensaje || 'Usuario eliminado correctamente.');
        cargarUsuarios();

    } catch (error) {
        console.error('Error al eliminar:', error);
    }
}