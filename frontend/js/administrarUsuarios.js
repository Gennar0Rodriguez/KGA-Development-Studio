let usuarioActual = null;
let todosLosUsuarios = []; 
let paginaActual = 1;
const usuariosPorPagina = 10;
let modoEdicion = false;

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    const inputBuscar = document.getElementById('inputBuscar');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarUsuarios);
    }

    const formAgregarUsuario = document.getElementById('formAgregarUsuario');
    if (formAgregarUsuario) {
        formAgregarUsuario.addEventListener('submit', guardarUsuario);
        formAgregarUsuario.addEventListener('reset', resetearFormulario);
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

            if (elemNombre) elemNombre.textContent = usuarioActual.nombre_admin;
            if (elemRol) elemRol.textContent = usuarioActual.cargo;
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

    const inicio = (paginaActual - 1) * usuariosPorPagina;
    const fin = inicio + usuariosPorPagina;
    const usuariosPagina = lista.slice(inicio, fin);

    usuariosPagina.forEach((usuario) => {
        const fila = document.createElement('tr');

        const celdaCi = document.createElement('td');
        celdaCi.textContent = usuario.ci_admin;
        fila.appendChild(celdaCi);

        const celdaNombre = document.createElement('td');
        celdaNombre.textContent = usuario.nombre_admin;
        fila.appendChild(celdaNombre);

        const celdaApellido = document.createElement('td');
        celdaApellido.textContent = usuario.apellido_admin;
        fila.appendChild(celdaApellido);

        const celdaRol = document.createElement('td');
        celdaRol.textContent = usuario.cargo;
        fila.appendChild(celdaRol);

        const celdaAcciones = document.createElement('td');

        const botonEditar = document.createElement('button');
        botonEditar.className = 'btn-editar';
        botonEditar.dataset.ci = usuario.ci_admin;
        botonEditar.innerHTML = '<i class="fa-solid fa-pen"></i>';
        botonEditar.style.cursor = 'pointer';
        botonEditar.style.marginRight = '5px';
        botonEditar.addEventListener('click', () => editarUsuario(usuario));

        const botonEliminar = document.createElement('button');
        botonEliminar.className = 'btn-eliminar';
        botonEliminar.dataset.ci = usuario.ci_admin;
        botonEliminar.innerHTML = '<i class="fa-solid fa-trash"></i>';
        botonEliminar.style.cursor = 'pointer';
        botonEliminar.addEventListener('click', () => eliminarUsuario(usuario.ci_admin));

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

    const usuariosFiltrados = todosLosUsuarios.filter((u) => {
        const ci = String(u.ci_admin || '').toLowerCase();
        const nombre = String(u.nombre_admin || '').toLowerCase();
        const apellido = String(u.apellido_admin || '').toLowerCase();

        return ci.includes(busqueda) || nombre.includes(busqueda) || apellido.includes(busqueda);
    });

    paginaActual = 1;
    renderizarTablaPaginada(usuariosFiltrados);
}

// Carga los datos en el formulario para editar
function editarUsuario(usuario) {
    modoEdicion = true;

    const inputCi = document.getElementById('ci');
    if (inputCi) {
        inputCi.value = usuario.ci_admin;
        inputCi.readOnly = true; // Deshabilita la edición de la clave primaria
    }

    document.getElementById('nombre').value = usuario.nombre_admin;
    document.getElementById('apellido').value = usuario.apellido_admin;
    document.getElementById('pass').value = ''; // Se borra por seguridad

    const selectRol = document.getElementById('rol');
    if (selectRol) selectRol.value = usuario.cargo.toLowerCase();

    const botonAgregar = document.getElementById('btn-agregar');
    const labelUser = document.getElementById('label-User');
    if (botonAgregar) {
        botonAgregar.innerHTML = 'Editar Usuario <i class="fa-solid fa-pen"></i>';
    }
    if (labelUser) {
        labelUser.innerHTML = 'Editar Usuario';
    }
}

// Maneja el guardado (tanto creación con POST como edición con PUT)
async function guardarUsuario(event) {
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

    const metodo = modoEdicion ? 'PUT' : 'POST';

    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/usuario/usuarios.php', {
            method: metodo,
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
            console.error("Respuesta RAW del servidor:", textoRespuesta);
            throw new Error("El servidor no devolvió JSON válido.");
        }

        if (!response.ok) {
            if (msg) {
                msg.textContent = resultado.error || 'No se pudo procesar la solicitud.';
                msg.style.color = '#dc2626';
            }
            return;
        }

        if (msg) {
            msg.textContent = modoEdicion ? '¡Usuario actualizado correctamente!' : '¡Usuario creado correctamente!';
            msg.style.color = '#16a34a';
        }

        resetearFormulario();
        cargarUsuarios();

    } catch (error) {
        console.error('Error al guardar usuario:', error);
        if (msg) {
            msg.textContent = error.message || 'Error de conexión con el servidor.';
            msg.style.color = '#dc2626';
        }
    }
}

// Restablece la interfaz del formulario
function resetearFormulario() {
    modoEdicion = false;
    const form = document.getElementById('formAgregarUsuario');
    if (form) form.reset();

    const inputCi = document.getElementById('ci');
    if (inputCi) inputCi.readOnly = false;

    const botonAgregar = document.getElementById('btn-agregar');
    const labelUser = document.getElementById('label-User');
    if (botonAgregar) {
        botonAgregar.innerHTML = 'Añadir Usuario <i class="fa-solid fa-user-plus"></i>';
    }
    if (labelUser) {
        labelUser.innerHTML = 'Agregar Usuario';
    }
}

// Elimina un usuario por su CI
async function eliminarUsuario(ci) {
    if (!confirm(`¿Desea eliminar el usuario con CI ${ci}?`)) {
        return;
    }

    try {
        const response = await fetch(`/kgade/KGA-Development-Studio/API/usuario/usuarios.php?id=${ci}`, {
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
        console.error('Error al eliminar usuario:', error);
    }
}