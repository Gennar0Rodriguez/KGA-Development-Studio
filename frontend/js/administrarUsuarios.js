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
        formAgregarUsuario.addEventListener('submit', guardarOCambiarUsuario);
        // Si el usuario borra los campos a mano, detecta si vació todo
        formAgregarUsuario.addEventListener('input', actualizarBotonFormulario);
    }

    // Listener para el botón de la X (Limpiar / Cancelar Edición)
    const btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', limpiarFormulario);
    }
});

// Cargar usuarios desde la API
async function cargarUsuarios() {
    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/usuario/usuarios.php');
        if (!response.ok) throw new Error('Error al obtener respuesta de la API');
        
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

// Renderizar tabla paginada
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

        // Acciones
        const celdaAcciones = document.createElement('td');

        const botonEditar = document.createElement('button');
        botonEditar.className = 'btn-editar';
        botonEditar.innerHTML = '<i class="fa-solid fa-pen"></i>';
        botonEditar.style.cursor = 'pointer';
        botonEditar.style.marginRight = '5px';
        botonEditar.addEventListener('click', () => editarUsuario(usuario));

        const botonEliminar = document.createElement('button');
        botonEliminar.className = 'btn-eliminar';
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

// Paginación
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

// Filtrar usuarios
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

// Cargar los datos en el formulario para editar
function editarUsuario(usuario) {
    modoEdicion = true;

    const inputCi = document.getElementById('ci');
    if (inputCi) {
        inputCi.value = usuario.ci_admin;
        inputCi.readOnly = true; // No modificable en UI pero se envía
    }

    const inputNombre = document.getElementById('nombre');
    const inputApellido = document.getElementById('apellido');
    const inputPass = document.getElementById('pass');
    
    if (inputNombre) inputNombre.value = usuario.nombre_admin;
    if (inputApellido) inputApellido.value = usuario.apellido_admin;
    
    if (inputPass) {
        inputPass.value = '';
        inputPass.required = false; // Al editar la contraseña es opcional
    }

    const selectRol = document.getElementById('rol');
    if (selectRol) selectRol.value = usuario.cargo.toLowerCase();

    const labelUser = document.getElementById('label-User');
    const botonAgregar = document.getElementById('btn-agregar');
    const btnCancelar = document.getElementById('btn-cancelar');
    
    if (labelUser) labelUser.innerHTML = 'Editar Usuario';
    if (botonAgregar) botonAgregar.innerHTML = '<i class="fa-solid fa-pen"></i> Editar Usuario';
    
    // Mostrar la X
    if (btnCancelar) btnCancelar.style.display = 'block';
}

// Restablecer formulario a "Agregar Usuario" y vaciar campos
function limpiarFormulario() {
    modoEdicion = false;

    const form = document.getElementById('formAgregarUsuario');
    if (form) form.reset();

    const inputCi = document.getElementById('ci');
    if (inputCi) inputCi.readOnly = false;

    const inputPass = document.getElementById('pass');
    if (inputPass) inputPass.required = true; // Vuelve a ser obligatorio para crear

    const labelUser = document.getElementById('label-User');
    const botonAgregar = document.getElementById('btn-agregar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const cargo = document.getElementById('rol');

    if (labelUser) labelUser.innerHTML = "Agregar Usuario";
    if (botonAgregar) botonAgregar.innerHTML = '<i class="fa-solid fa-user-plus"></i> Agregar Usuario';
    if (cargo) cargo.selectedIndex = 0;

    // Ocultar la X
    if (btnCancelar) btnCancelar.style.display = 'none';

    // Limpiar mensaje
    const msg = document.getElementById('msgResultado');
    if (msg) msg.textContent = '';
}

// Si se borran manualmente los datos de los inputs, vuelve a estado "Agregar"
function actualizarBotonFormulario() {
    const ci = document.getElementById('ci')?.value.trim() || '';
    const nombre = document.getElementById('nombre')?.value.trim() || '';
    const apellido = document.getElementById('apellido')?.value.trim() || '';
    
    if (ci === '' && nombre === '' && apellido === '') {
        limpiarFormulario();
    } else if (modoEdicion) {
        const btnCancelar = document.getElementById('btn-cancelar');
        if (btnCancelar) btnCancelar.style.display = 'block';
    }
}

// Enviar formulario (POST/PUT)
async function guardarOCambiarUsuario(event) {
    event.preventDefault();

    const msg = document.getElementById('msgResultado');
    if (msg) {
        msg.textContent = "Procesando...";
        msg.style.color = "#0284C7";
    }

    const inputCi = document.getElementById('ci');
    const datos = {
        ci: inputCi ? inputCi.value : '',
        nombre: document.getElementById('nombre')?.value || '',
        apellido: document.getElementById('apellido')?.value || '',
        pass: document.getElementById('pass')?.value || '',
        rol: document.getElementById('rol')?.value || ''
    };

    const metodoHttp = modoEdicion ? 'PUT' : 'POST';

    try {
        const response = await fetch('/kgade/KGA-Development-Studio/API/usuario/usuarios.php', {
            method: metodoHttp,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        const resultado = await response.json();

        if (!response.ok) {
            if (msg) {
                msg.textContent = resultado.error || 'Error en la solicitud.';
                msg.style.color = '#dc2626';
            }
            return;
        }

        if (msg) {
            msg.textContent = modoEdicion ? '¡Usuario actualizado correctamente!' : '¡Usuario guardado correctamente!';
            msg.style.color = '#16a34a';
        }

        limpiarFormulario();
        cargarUsuarios();

    } catch (error) {
        console.error('Error al procesar usuario:', error);
        if (msg) {
            msg.textContent = 'Error de conexión con el servidor.';
            msg.style.color = '#dc2626';
        }
    }
}

// Eliminar usuario
async function eliminarUsuario(ci) {
    if (!confirm(`¿Desea eliminar el usuario con CI ${ci}?`)) return;

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
        console.error('Error al eliminar:', error);
    }
}